<?php
header('Content-Type: text/html; charset=utf-8');

// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>修复验证页面</h2>";

echo "<h3>1. 检查Excel读取器文件:</h3>";
if (file_exists('inc/excel_reader.php')) {
    echo "<p>Excel读取器文件存在</p>";
    
    // 包含Excel读取器
    include 'inc/excel_reader.php';
    echo "<p>Excel读取器包含成功</p>";
    
    if (class_exists('ExcelReader')) {
        echo "<p style='color: green;'>ExcelReader类定义成功</p>";
        
        // 尝试创建实例
        try {
            $reader = new ExcelReader();
            echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>ExcelReader实例创建失败: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>ExcelReader类未定义</p>";
    }
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<h3>2. 检查vendor/autoload.php:</h3>";
$autoload_path = __DIR__ . '/vendor/autoload.php';
echo "<p>vendor/autoload.php 存在: " . (file_exists($autoload_path) ? '是' : '否') . "</p>";

if (file_exists($autoload_path)) {
    require_once $autoload_path;
    echo "<p>自动加载器包含成功</p>";
    
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        echo "<p style='color: green;'>PhpSpreadsheet类可用</p>";
    } else {
        echo "<p style='color: orange;'>PhpSpreadsheet类不可用，将使用旧方法</p>";
    }
}

echo "<h3>3. 检查配置文件:</h3>";
if (file_exists('inc/conn.php')) {
    include 'inc/conn.php';
    echo "<p>配置文件包含成功</p>";
    echo "<p>数据目录: $UpDir</p>";
    echo "<p>查询条件: $tiaojian1</p>";
} else {
    echo "<p style='color: red;'>配置文件不存在</p>";
}

echo "<br><a href='index.php'>返回index.php测试</a> | <a href='debug_index.php'>返回调试页面</a>";

?>