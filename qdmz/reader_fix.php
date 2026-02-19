<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>读取器修复</h2>";

// 包含必要的基础文件
$basicFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
];

echo "<h3>包含基础文件:</h3>";
foreach ($basicFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        require_once $fullPath;
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: red;'>✗ 不存在: $file</p>";
    }
}

// 包含读取器相关的文件（按依赖顺序）
$readerFiles = [
    // 读取器接口
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php',
    
    // 基础读取器类
    'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
    
    // XLSX读取器相关
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    
    // XLS读取器相关 (可能依赖OLE)
    'vendor/phpoffice/phpspreadsheet/src/Shared/OLERead.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    
    // IO工厂
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
];

echo "<h3>包含读取器相关文件:</h3>";
foreach ($readerFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        try {
            require_once $fullPath;
            echo "<p style='color: green;'>✓ $file</p>";
        } catch (Error $e) {
            echo "<p style='color: red;'>✗ 包含失败: $file - " . $e->getMessage() . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ 包含异常: $file - " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>→ 不存在: $file</p>";
    }
}

echo "<h3>测试读取器类可用性:</h3>";
$readerClasses = [
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls', 
    'PhpOffice\\PhpSpreadsheet\\IOFactory'
];

foreach ($readerClasses as $class) {
    $exists = class_exists($class, false);
    echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
}

// 测试创建读取器
echo "<h3>测试读取器创建:</h3>";
if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory', false)) {
    try {
        // 测试创建Xls读取器
        $xlsReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
        echo "<p style='color: green;'>✓ Xls读取器创建成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Xls读取器创建失败: " . $e->getMessage() . "</p>";
    }
    
    try {
        // 测试创建Xlsx读取器
        $xlsxReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        echo "<p style='color: green;'>✓ Xlsx读取器创建成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Xlsx读取器创建失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>IOFactory类不可用，无法测试读取器创建</p>";
}

// 更新Excel读取器，确保包含所有必要的读取器文件
echo "<h3>更新Excel读取器:</h3>";

$excelReaderContent = file_get_contents(__DIR__ . '/inc/excel_reader.php');

// 在Excel读取器中添加对读取器文件的直接包含
$includeCode = "
// 直接包含读取器相关文件以确保可用性
if (!class_exists('\\\\PhpOffice\\\\PhpSpreadsheet\\\\Reader\\\\Xls') || !class_exists('\\\\PhpOffice\\\\PhpSpreadsheet\\\\Reader\\\\Xlsx')) {
    \$readerFiles = [
        'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
        'vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php',
        'vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php',
        'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
        'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
        'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    ];
    
    foreach (\$readerFiles as \$file) {
        \$fullPath = dirname(__DIR__) . '/' . \$file;
        if (file_exists(\$fullPath)) {
            require_once \$fullPath;
        }
    }
}
";

// 将包含代码添加到Excel读取器的开始部分
$updatedContent = str_replace(
    '// 简化版Excel读取器，兼容无自动加载器环境',
    "// 简化版Excel读取器，兼容无自动加载器环境\n$includeCode",
    $excelReaderContent
);

file_put_contents(__DIR__ . '/inc/excel_reader.php', $updatedContent);
echo "<p style='color: green;'>✓ Excel读取器已更新</p>";

echo "<br><a href='debug_data_structure.php'>重新调试数据结构</a> | <a href='index.php'>首页</a>";

?>