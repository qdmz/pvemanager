<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>语法修复测试</h2>";

echo "<h3>1. 检查Excel读取器语法:</h3>";
$excelReaderPath = 'inc/excel_reader.php';
if (file_exists($excelReaderPath)) {
    echo "<p>Excel读取器文件存在</p>";
    
    // 检查语法错误
    $output = shell_exec('php -l ' . escapeshellarg(__DIR__ . '/inc/excel_reader.php'));
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<p style='color: green;'>Excel读取器语法正确</p>";
    } else {
        echo "<p style='color: red;'>Excel读取器存在语法错误: " . htmlspecialchars($output) . "</p>";
    }
    
    echo "<h3>2. 尝试包含Excel读取器:</h3>";
    try {
        include 'inc/excel_reader.php';
        echo "<p style='color: green;'>Excel读取器包含成功</p>";
        
        if (class_exists('ExcelReader')) {
            echo "<p style='color: green;'>ExcelReader类定义成功</p>";
            
            try {
                $reader = new ExcelReader();
                echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
                
                // 测试基本功能
                if (isset($reader->sheets)) {
                    echo "<p style='color: green;'>sheets属性访问正常</p>";
                } else {
                    echo "<p style='color: orange;'>sheets属性访问异常</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>ExcelReader实例创建异常: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>ExcelReader类未定义</p>";
        }
    } catch (Error $e) {
        echo "<p style='color: red;'>包含Excel读取器时出错: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<h3>3. 检查依赖:</h3>";
echo "<p>vendor/autoload.php: " . (file_exists('vendor/autoload.php') ? '存在' : '不存在') . "</p>";
echo "<p>inc/conn.php: " . (file_exists('inc/conn.php') ? '存在' : '不存在') . "</p>";
echo "<p>inc/excel.php: " . (file_exists('inc/excel.php') ? '存在' : '不存在') . "</p>";

echo "<br><a href='index.php'>返回index.php</a> | <a href='final_path_test.php'>返回路径测试</a>";

?>