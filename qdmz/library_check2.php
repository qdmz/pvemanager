<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>PhpSpreadsheet库完整性检查 (续)</h2>";

echo "<h3>继续检查vendor目录结构:</h3>";
$vendorDir = 'vendor/phpoffice/phpspreadsheet';

// 继续列出目录结构
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendorDir));
$files = array();
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $relativePath = str_replace($vendorDir . '/', '', $file->getPathname());
        $files[] = $relativePath;
    }
}

// 查找关键的Reader文件
$readerFiles = array_filter($files, function($file) {
    return strpos($file, 'Reader/') !== false;
});

echo "<p>Reader相关文件:</p><pre>";
foreach ($readerFiles as $file) {
    echo "  $file\n";
}
echo "</pre>";

// 检查关键文件
echo "<h3>检查关键文件:</h3>";
$requiredFiles = [
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file 存在</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 不存在</p>";
    }
}

echo "<h3>检查autoload.php:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>✓ vendor/autoload.php 存在</p>";
    
    // 尝试包含autoload.php
    require_once 'vendor/autoload.php';
    
    echo "<h3>检查类是否可用:</h3>";
    $testClasses = [
        'PhpOffice\\PhpSpreadsheet\\IOFactory',
        'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
        'PhpOffice\\PhpSpreadsheet\\Reader\\Xls',
        'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
        'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
    ];
    
    foreach ($testClasses as $class) {
        $available = class_exists($class, true); // 第二个参数强制自动加载
        echo "<p>$class: " . ($available ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
    }
    
    // 检查命名空间注册
    echo "<h3>检查自动加载器注册:</h3>";
    $autoloaders = spl_autoload_functions();
    if ($autoloaders) {
        echo "<p>已注册的自动加载器:</p><pre>";
        foreach ($autoloaders as $autoloader) {
            if (is_array($autoloader) && isset($autoloader[0])) {
                echo "  " . (is_object($autoloader[0]) ? get_class($autoloader[0]) : $autoloader[0]) . "\n";
            } else {
                echo "  " . (is_string($autoloader) ? $autoloader : 'unknown') . "\n";
            }
        }
        echo "</pre>";
    }
    
    // 尝试手动包含Xlsx读取器
    echo "<h3>尝试手动包含Xlsx.php:</h3>";
    $xlsxPath = 'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php';
    if (file_exists($xlsxPath)) {
        echo "<p>包含 $xlsxPath ...</p>";
        try {
            require_once $xlsxPath;
            echo "<p style='color: green;'>✓ 手动包含成功</p>";
            
            // 再次检查类是否可用
            $available = class_exists('PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx');
            echo "<p>现在Xlsx类是否可用: " . ($available ? "<span style='color: green;'>✓ 是</span>" : "<span style='color: red;'>✗ 否</span>") . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ 手动包含失败: " . $e->getMessage() . "</p>";
        } catch (Error $e) {
            echo "<p style='color: red;'>✗ 手动包含错误: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ $xlsxPath 不存在</p>";
    }
    
    // 尝试创建读取器实例
    echo "<h3>尝试创建Xlsx读取器实例:</h3>";
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx')) {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            echo "<p style='color: green;'>✓ Xlsx读取器实例创建成功</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Xlsx读取器实例创建失败: " . $e->getMessage() . "</p>";
        } catch (Error $e) {
            echo "<p style='color: red;'>✗ Xlsx读取器实例创建错误: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Xlsx类仍然不可用</p>";
    }
} else {
    echo "<p style='color: red;'>✗ vendor/autoload.php 不存在</p>";
}

echo "<br><a href='index.php'>首页</a> | <a href='admin.php'>管理后台</a> | <a href='library_check.php'>库检查</a>";

?>