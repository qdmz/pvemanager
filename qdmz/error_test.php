<?php
// 开启错误报告以查看index.php的具体错误
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// 设置输出编码为UTF-8
define('DEFAULT_ENCODING', 'UTF-8');
header('Content-Type: text/html; charset=' . DEFAULT_ENCODING);

echo "<h2>错误测试页面</h2>";
echo "<p>尝试加载index.php中的配置...</p>";

// 先测试包含conn.php是否有错误
echo "<h3>1. 测试包含inc/conn.php...</h3>";
if(file_exists('inc/conn.php')) {
    echo "<p>inc/conn.php 存在</p>";
    include 'inc/conn.php';
    echo "<p>inc/conn.php 包含成功</p>";
} else {
    echo "<p style='color: red'>inc/conn.php 不存在</p>";
}

// 测试包含excel_reader.php
echo "<h3>2. 测试包含inc/excel_reader.php...</h3>";
if(file_exists('inc/excel_reader.php')) {
    echo "<p>inc/excel_reader.php 存在</p>";
    include 'inc/excel_reader.php';
    echo "<p>inc/excel_reader.php 包含成功</p>";
} else {
    echo "<p style='color: red'>inc/excel_reader.php 不存在</p>";
}

// 测试ExcelReader类是否可用
echo "<h3>3. 测试ExcelReader类...</h3>";
if(class_exists('ExcelReader')) {
    echo "<p>ExcelReader类已定义</p>";
} else {
    echo "<p style='color: red'>ExcelReader类未定义</p>";
}

echo "<h3>4. 测试完成，准备输出页面内容...</h3>";

// 尝试输出基本HTML结构
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>错误测试</title>
</head>
<body>
    <h1>错误测试完成</h1>
    <p>如果能看到这段文字，说明HTML输出正常</p>
    <a href="index.php">返回首页</a>
</body>
</html>';
?>