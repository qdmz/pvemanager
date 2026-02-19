<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>完整修复PhpSpreadsheet自动加载</h2>";

// 检查源文件结构
$srcDir = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src';
if (is_dir($srcDir)) {
    echo "<h3>源目录结构:</h3>";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $phpFiles = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
            $relativePath = substr($file->getPathname(), strlen($srcDir) - 3); // -3 to include 'src'
            $phpFiles[] = $relativePath;
        }
    }
    
    echo "<p>找到 " . count($phpFiles) . " 个PHP文件:</p><ul>";
    foreach (array_slice($phpFiles, 0, 20) as $file) { // 显示前20个文件
        echo "<li>$file</li>";
    }
    if (count($phpFiles) > 20) {
        echo "<li>... 还有 " . (count($phpFiles) - 20) . " 个文件</li>";
    }
    echo "</ul>";
    
    // 创建完整的自动加载器
    $autoloaderContent = '<?php
// 完整的PhpSpreadsheet自动加载器

// 定义根目录
define("SPREADSHEET_ROOT", __DIR__ . "/vendor/phpoffice/phpspreadsheet/src/");

// 自定义自动加载函数
spl_autoload_register(function ($class) {
    // 只处理PhpOffice\PhpSpreadsheet命名空间下的类
    if (strpos($class, "PhpOffice\\\\PhpSpreadsheet") !== 0) {
        return false;
    }
    
    // 将命名空间转换为文件路径
    $relativePath = str_replace("PhpOffice\\\\PhpSpreadsheet\\\\", "", $class);
    $relativePath = str_replace("\\\\", "/", $relativePath) . ".php";
    
    $filePath = SPREADSHEET_ROOT . $relativePath;
    
    if (file_exists($filePath)) {
        require_once $filePath;
        return true;
    }
    
    // 如果标准路径不存在，尝试一些特殊路径
    $specialPaths = [
        // 对于Shared命名空间的特殊处理
        SPREADSHEET_ROOT . "Shared/" . $relativePath,
        // 对于Cell命名空间的特殊处理
        SPREADSHEET_ROOT . "Cell/" . $relativePath,
        // 某些类可能在不同的子目录中
        SPREADSHEET_ROOT . "Calculation/" . $relativePath,
        SPREADSHEET_ROOT . "Reader/" . $relativePath,
        SPREADSHEET_ROOT . "Writer/" . $relativePath,
    ];
    
    foreach ($specialPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
    }
    
    return false;
});

// 也可以尝试包含Composer自动生成的自动加载器
$composerAutoloader = __DIR__ . "/vendor/autoload.php";
if (file_exists($composerAutoloader)) {
    require_once $composerAutoloader;
}
';

    // 写入新的自动加载器
    $result = file_put_contents(__DIR__ . '/vendor/complete_autoloader.php', $autoloaderContent);
    
    if ($result !== false) {
        echo "<p style='color: green;'>✓ 完整自动加载器创建成功</p>";
        
        // 包含新的自动加载器
        require_once __DIR__ . '/vendor/complete_autoloader.php';
        
        // 测试关键类
        $classesToTest = [
            'PhpOffice\\PhpSpreadsheet\\IOFactory',
            'PhpOffice\\PhpSpreadsheet\\Shared\\File',
            'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
            'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
            'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
            'PhpOffice\\PhpSpreadsheet\\Reader\\Xls'
        ];
        
        echo "<h3>测试类加载 (使用新自动加载器):</h3>";
        foreach ($classesToTest as $class) {
            // 先尝试手动包含文件来测试是否存在
            $classFileExists = false;
            $relativePath = str_replace("PhpOffice\\\\PhpSpreadsheet\\\\", "", $class);
            $relativePath = str_replace("\\\\", "/", $relativePath) . ".php";
            $filePath = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/' . $relativePath;
            
            if (file_exists($filePath)) {
                $classFileExists = true;
            } else {
                // 尝试一些特殊路径
                $specialPaths = [
                    __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Shared/' . $relativePath,
                    __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Cell/' . $relativePath,
                    __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Calculation/' . $relativePath,
                    __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Reader/' . $relativePath,
                    __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Writer/' . $relativePath,
                ];
                
                foreach ($specialPaths as $path) {
                    if (file_exists($path)) {
                        $classFileExists = true;
                        break;
                    }
                }
            }
            
            $exists = class_exists($class, true); // 强制加载
            echo "<p>$class: " . 
                 ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . 
                 " (文件" . ($classFileExists ? "存在" : "不存在") . ")</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 完整自动加载器创建失败</p>";
    }
} else {
    echo "<p style='color: red;'>源目录不存在: $srcDir</p>";
}

echo "<br><a href='check_library.php'>重新检查库</a> | <a href='excel_debug.php'>Excel调试</a>";

?>