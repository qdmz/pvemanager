<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>自动加载器测试</h2>";

echo "<h3>检查自动加载器文件:</h3>";
$autoload_path = __DIR__ . '/vendor/autoload.php';
echo "<p>路径: $autoload_path</p>";
echo "<p>文件存在: " . (file_exists($autoload_path) ? '是' : '否') . "</p>";

if (file_exists($autoload_path)) {
    echo "<h3>引入自动加载器:</h3>";
    require_once $autoload_path;
    echo "<p>引入成功</p>";
    
    echo "<h3>检查关键文件是否存在:</h3>";
    $spreadsheet_file = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php';
    echo "<p>Spreadsheet.php 文件存在: " . (file_exists($spreadsheet_file) ? '是' : '否') . "</p>";
    
    echo "<h3>尝试使用类:</h3>";
    
    // 直接包含文件测试
    $file_path = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php';
    if (file_exists($file_path)) {
        echo "<p>直接包含 Spreadsheet.php...</p>";
        require_once $file_path;
        echo "<p>直接包含成功</p>";
    } else {
        echo "<p style='color: red'>Spreadsheet.php 文件不存在</p>";
    }
    
    // 检查类是否存在
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        echo "<p style='color: green'>PhpOffice\\PhpSpreadsheet\\Spreadsheet 类存在</p>";
    } else {
        echo "<p style='color: red'>PhpOffice\\PhpSpreadsheet\\Spreadsheet 类不存在</p>";
    }
    
    // 尝试创建对象
    try {
        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $obj = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            echo "<p style='color: green'>对象创建成功</p>";
        } else {
            echo "<p style='color: red'>无法创建对象，类不存在</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red'>创建对象失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red'>自动加载器文件不存在</p>";
}

?>