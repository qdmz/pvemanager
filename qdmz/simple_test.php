<?php
// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/html; charset=utf-8');
echo "<h2>简化测试页面</h2>";

echo "<h3>1. 测试包含配置文件:</h3>";
if (file_exists('inc/conn.php')) {
    include 'inc/conn.php';
    echo "<p style='color: green;'>配置文件包含成功</p>";
    echo "<p>UpDir: $UpDir</p>";
    echo "<p>tiaojian1: $tiaojian1</p>";
} else {
    echo "<p style='color: red;'>配置文件不存在</p>";
    exit;
}

echo "<h3>2. 测试characet/charaget函数:</h3>";
$testStr = "测试字符串";
echo "<p>characet('{$testStr}'): " . characet($testStr) . "</p>";
echo "<p>charaget('{$testStr}'): " . charaget($testStr) . "</p>";

echo "<h3>3. 测试traverse函数:</h3>";
echo "<p>尝试列出Excel文件:</p>";
echo "<select>";
traverse($UpDir."/");
echo "</select>";

echo "<h3>4. 测试Excel读取器:</h3>";
if (file_exists('inc/excel_reader.php')) {
    echo "<p>尝试包含Excel读取器...</p>";
    include 'inc/excel_reader.php';
    echo "<p style='color: green;'>Excel读取器包含成功</p>";
    
    if (class_exists('ExcelReader')) {
        echo "<p style='color: green;'>ExcelReader类存在</p>";
        
        try {
            $reader = new ExcelReader();
            echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>ExcelReader实例创建失败: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>ExcelReader类不存在</p>";
    }
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<br><a href='index.php'>返回index.php</a> | <a href='full_debug.php'>全面调试</a>";

?>