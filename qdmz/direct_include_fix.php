<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>直接包含修复</h2>";

// 直接包含所有必要的PhpSpreadsheet文件
$spreadsheetFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    'vendor/phpoffice/phpspreadsheet/src/Calculation/Calculation.php',
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Cell.php',
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Style.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Calculation/Functions.php'
];

echo "<h3>直接包含PhpSpreadsheet核心文件:</h3>";

$includedCount = 0;
foreach ($spreadsheetFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        // 检查文件是否已经包含过
        $content = file_get_contents($fullPath);
        if (!preg_match('/class\s+Coordinate|class\s+File|class\s+Xlsx|class\s+Xls/', $content) || 
            !class_exists('PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate', false)) {
            require_once $fullPath;
            echo "<p style='color: green;'>✓ 包含: $file</p>";
            $includedCount++;
        } else {
            echo "<p style='color: orange;'>→ 已存在: $file</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ 不存在: $file</p>";
    }
}

echo "<p>总共包含 $includedCount 个文件</p>";

// 测试类是否可用
$classesToTest = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Shared\\File',
    'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls'
];

echo "<h3>测试类加载 (直接包含后):</h3>";
foreach ($classesToTest as $class) {
    $exists = class_exists($class, false); // 不自动加载
    echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
}

// 更新Excel读取器，使用直接包含方式
echo "<h3>更新Excel读取器使用直接包含:</h3>";

$excelReaderContent = file_get_contents(__DIR__ . '/inc/excel_reader.php');

// 创建一个直接包含所有必要文件的版本
$directIncludeCode = "
// 直接包含必要的PhpSpreadsheet文件
if (!class_exists('\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory')) {
    \$spreadsheetFiles = [
        'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
        'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
        'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
        'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
        'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
        'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
    ];
    
    foreach (\$spreadsheetFiles as \$file) {
        \$fullPath = dirname(__DIR__) . '/' . \$file;
        if (file_exists(\$fullPath) && !preg_match('/class\\\\s+\\\\w+/', file_get_contents(\$fullPath)) || !class_exists(str_replace(['src/', '.php', '/'], ['\\\\', '', '\\\\'], ucfirst(substr(\$file, strpos(\$file, 'src/') + 4))), false)) {
            require_once \$fullPath;
        }
    }
}
";

// 在Excel读取器开头添加直接包含代码
$updatedContent = str_replace(
    '<?php',
    "<?php\n// 直接包含必要的PhpSpreadsheet文件\n$directIncludeCode",
    $excelReaderContent
);

file_put_contents(__DIR__ . '/inc/excel_reader.php', $updatedContent);
echo "<p style='color: green;'>✓ Excel读取器已更新</p>";

echo "<br><a href='check_library.php'>重新检查库</a> | <a href='excel_debug.php'>Excel调试</a> | <a href='index.php'>首页</a>";

?>