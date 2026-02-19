<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>验证修复</h2>";

// 检查语法
echo "<h3>语法检查:</h3>";
$filePath = __DIR__ . '/inc/excel_reader.php';
$output = shell_exec('php -l ' . escapeshellarg($filePath));

if (strpos($output, 'No syntax errors') !== false) {
    echo "<p style='color: green;'>✓ 语法检查通过</p>";
    
    echo "<h3>尝试包含文件:</h3>";
    try {
        include 'inc/excel_reader.php';
        echo "<p style='color: green;'>✓ 文件包含成功</p>";
        
        if (class_exists('ExcelReader')) {
            echo "<p style='color: green;'>✓ ExcelReader类定义成功</p>";
            
            try {
                $reader = new ExcelReader();
                echo "<p style='color: green;'>✓ ExcelReader实例创建成功</p>";
                
                // 检查是否正确初始化了属性
                if (isset($reader->sheets) && is_array($reader->sheets)) {
                    echo "<p style='color: green;'>✓ sheets属性正确初始化</p>";
                } else {
                    echo "<p style='color: orange;'>⚠ sheets属性未正确初始化</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠ 实例创建异常: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ ExcelReader类未定义</p>";
        }
    } catch (Error $e) {
        echo "<p style='color: red;'>✗ 包含文件时出错: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ 语法检查失败: " . htmlspecialchars($output) . "</p>";
}

echo "<br><a href='index.php'>尝试访问index.php</a> | <a href='execute_fix.php'>重新执行修复</a>";

?>