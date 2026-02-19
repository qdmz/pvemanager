<?php
// 验证Excel读取器语法
header('Content-Type: text/html; charset=utf-8');

echo "<h2>语法验证工具</h2>";

$filePath = __DIR__ . '/inc/excel_reader.php';
echo "<p>验证文件: $filePath</p>";

// 检查语法
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin
   1 => array("pipe", "w"),  // stdout
   2 => array("pipe", "w")   // stderr
);

$process = proc_open("php -l " . escapeshellarg($filePath), $descriptorspec, $pipes);

if (is_resource($process)) {
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    $return_value = proc_close($process);
    
    if ($return_value === 0) {
        echo "<p style='color: green;'>✓ 语法检查通过: 没有语法错误</p>";
        
        // 尝试包含文件
        echo "<h3>尝试包含文件:</h3>";
        try {
            include 'inc/excel_reader.php';
            echo "<p style='color: green;'>✓ 文件包含成功</p>";
            
            if (class_exists('ExcelReader')) {
                echo "<p style='color: green;'>✓ ExcelReader类定义成功</p>";
                
                try {
                    $reader = new ExcelReader();
                    echo "<p style='color: green;'>✓ ExcelReader实例创建成功</p>";
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
        echo "<p style='color: red;'>✗ 发现语法错误</p>";
        echo "<p>错误信息: " . htmlspecialchars($errors) . "</p>";
        echo "<p>输出: " . htmlspecialchars($output) . "</p>";
    }
} else {
    echo "<p style='color: red;'>无法执行语法检查</p>";
}

echo "<br><a href='index.php'>返回index.php</a> | <a href='final_check.php'>最终检查</a>";

?>