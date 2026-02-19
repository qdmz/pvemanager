<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>修复自动加载器</h2>";

// 检查是否已有自动加载器
if (file_exists('vendor/autoload.php')) {
    echo "<p>现有自动加载器存在</p>";
    
    // 检查phpspreadsheet目录结构
    $psr4Map = [
        'PhpOffice\\PhpSpreadsheet\\' => __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/',
    ];
    
    // 创建自动加载函数
    $autoloaderContent = '<?php
// 自定义自动加载器，用于PhpSpreadsheet库
class CustomAutoloader 
{
    private static $psr4Map = ' . var_export($psr4Map, true) . ';

    public static function autoload($class) {
        foreach (self::$psr4Map as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace(\'\\\\\', \'/\', $relativeClass) . \'.php\';
            
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    }
}

// 注册自动加载器
spl_autoload_register([\'CustomAutoloader\', \'autoload\']);

// 包含Composer自动加载器（如果存在）
$composerAutoloader = __DIR__ . \'/autoload.php\';
if (file_exists($composerAutoloader)) {
    require_once $composerAutoloader;
}
';

    // 写入新的自动加载器
    $result = file_put_contents(__DIR__ . '/vendor/custom_autoloader.php', $autoloaderContent);
    
    if ($result !== false) {
        echo "<p style='color: green;'>✓ 自定义自动加载器创建成功</p>";
        
        // 测试新自动加载器
        require_once __DIR__ . '/vendor/custom_autoloader.php';
        
        $classesToTest = [
            'PhpOffice\\PhpSpreadsheet\\IOFactory',
            'PhpOffice\\PhpSpreadsheet\\Shared\\File',
            'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
            'PhpOffice\\PhpSpreadsheet\\Spreadsheet'
        ];
        
        echo "<h3>测试类加载:</h3>";
        foreach ($classesToTest as $class) {
            $exists = class_exists($class, true); // 强制加载
            echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 自定义自动加载器创建失败</p>";
    }
} else {
    echo "<p style='color: red;'>vendor/autoload.php 不存在</p>";
}

// 尝试解压库文件（如果存在zip文件）
$zipFiles = glob('*.zip');
if (!empty($zipFiles)) {
    echo "<h3>发现ZIP文件，尝试解压:</h3>";
    foreach ($zipFiles as $zipFile) {
        if (strpos($zipFile, 'phpspread') !== false || strpos($zipFile, 'PhpSpreadsheet') !== false) {
            echo "<p>处理: $zipFile</p>";
            
            try {
                $zip = new ZipArchive();
                if ($zip->open($zipFile) === TRUE) {
                    // 解压到临时目录
                    $tempDir = __DIR__ . '/temp_extract';
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    
                    $zip->extractTo($tempDir);
                    $zip->close();
                    
                    // 查找src目录并复制到正确位置
                    $srcDir = null;
                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir));
                    foreach ($iterator as $file) {
                        if (basename($file) === 'src' && is_dir($file)) {
                            if (file_exists($file . '/IOFactory.php')) { // 验证是正确的src目录
                                $srcDir = $file;
                                break;
                            }
                        }
                    }
                    
                    if ($srcDir) {
                        echo "<p>找到src目录: $srcDir</p>";
                        
                        // 确保目标目录存在
                        $targetDir = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src';
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        
                        // 复制文件
                        $srcIterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
                        );
                        
                        foreach ($srcIterator as $item) {
                            if ($item->isFile()) {
                                $relativePath = substr($item->getPathname(), strlen($srcDir) + 1);
                                $targetPath = $targetDir . '/' . $relativePath;
                                $targetDirName = dirname($targetPath);
                                
                                if (!is_dir($targetDirName)) {
                                    mkdir($targetDirName, 0755, true);
                                }
                                
                                copy($item->getPathname(), $targetPath);
                            }
                        }
                        
                        echo "<p style='color: green;'>✓ 库文件已复制到正确位置</p>";
                    } else {
                        echo "<p style='color: orange;'>未找到src目录</p>";
                    }
                    
                    // 清理临时目录
                    $this->removeDir($tempDir);
                } else {
                    echo "<p style='color: red;'>无法打开ZIP文件</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>解压错误: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// 辅助函数：删除目录
function removeDir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") {
                    removeDir($dir."/".$object);
                } else {
                    unlink($dir."/".$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

echo "<br><a href='check_library.php'>重新检查库</a> | <a href='excel_debug.php'>返回Excel调试</a>";

?>