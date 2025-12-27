<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>语法检查页面</h2>";

echo "<h3>检查Excel读取器语法:</h3>";
$filePath = __DIR__ . '/inc/excel_reader.php';
echo "<p>检查文件: $filePath</p>";

// 检查文件是否存在
if (!file_exists($filePath)) {
    echo "<p style='color: red;'>文件不存在!</p>";
    exit;
}

// 使用php -l 检查语法
$lastLine = system('php -l ' . escapeshellarg($filePath), $returnVar);

if ($returnVar === 0) {
    echo "<p style='color: green;'>语法检查通过: 没有语法错误</p>";
} else {
    echo "<p style='color: red;'>发现语法错误!</p>";
    echo "<p>返回码: $returnVar</p>";
    if ($lastLine !== false) {
        echo "<p>错误信息: " . htmlspecialchars($lastLine) . "</p>";
    }
}

echo "<h3>尝试包含文件:</h3>";
try {
    include 'inc/excel_reader.php';
    echo "<p style='color: green;'>文件包含成功</p>";
    
    if (class_exists('ExcelReader')) {
        echo "<p style='color: green;'>ExcelReader类定义成功</p>";
        
        try {
            $reader = new ExcelReader();
            echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>实例创建异常: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>ExcelReader类未定义</p>";
    }
} catch (Error $e) {
    echo "<p style='color: red;'>包含文件时出错: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php'>返回index.php</a> | <a href='final_check.php'>最终检查</a>";

?>