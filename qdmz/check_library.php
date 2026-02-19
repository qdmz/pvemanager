<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>PhpSpreadsheet库完整性检查</h2>";

echo "<h3>检查关键库文件:</h3>";

$requiredFiles = [
    'vendor/autoload.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists($file);
    echo "<p>$file: " . ($exists ? "<span style='color: green;'>✓ 存在</span>" : "<span style='color: red;'>✗ 不存在</span>") . "</p>";
}

echo "<h3>检查vendor目录结构:</h3>";
if (is_dir('vendor')) {
    $dirs = scandir('vendor');
    echo "<p>vendor目录内容:</p><ul>";
    foreach ($dirs as $dir) {
        if ($dir != '.' && $dir != '..') {
            if (is_dir("vendor/$dir")) {
                echo "<li>[DIR] $dir";
                if ($dir == 'phpoffice') {
                    $phpoffice_dirs = scandir("vendor/$dir");
                    echo "<ul>";
                    foreach ($phpoffice_dirs as $subdir) {
                        if ($subdir != '.' && $subdir != '..') {
                            echo "<li>$subdir</li>";
                        }
                    }
                    echo "</ul>";
                }
                echo "</li>";
            } else {
                echo "<li>$dir</li>";
            }
        }
    }
    echo "</ul>";
}

echo "<h3>测试关键类是否存在:</h3>";
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    
    $classesToTest = [
        'PhpOffice\\PhpSpreadsheet\\IOFactory',
        'PhpOffice\\PhpSpreadsheet\\Shared\\File',
        'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
        'PhpOffice\\PhpSpreadsheet\\Spreadsheet'
    ];
    
    foreach ($classesToTest as $class) {
        $exists = class_exists($class);
        echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
    }
} else {
    echo "<p style='color: red;'>自动加载器不存在</p>";
}

echo "<br><a href='excel_debug.php'>返回Excel调试</a> | <a href='index.php'>首页</a>";

?>