<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>调试XLSX读取</h2>";

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 包含Excel读取器
include_once 'inc/excel_reader.php';

$testFile = 'shujukufangzheli/a田冲2147新.xlsx';

echo "<p>测试文件: $testFile</p>";

if (!file_exists($testFile)) {
    echo "<p style='color: red;'>文件不存在</p>";
    exit;
}

echo "<p>文件大小: " . filesize($testFile) . " 字节</p>";
echo "<p>开始读取...</p>";

try {
    echo "<p>创建ExcelReader实例...</p>";
    $reader = new ExcelReader();
    echo "<p>实例创建成功</p>";
    
    echo "<p>开始读取文件...</p>";
    $reader->read($testFile);
    echo "<p>读取完成</p>";
    
    if (isset($reader->sheets) && isset($reader->sheets[0])) {
        $numRows = $reader->sheets[0]['numRows'] ?? 0;
        $numCols = $reader->sheets[0]['numCols'] ?? 0;
        echo "<p>数据行数: $numRows, 列数: $numCols</p>";
        
        if ($numRows > 0 && $numCols > 0) {
            echo "<h3>前几行数据:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            for ($i = 1; $i <= min(5, $numRows); $i++) {
                echo "<tr>";
                for ($j = 1; $j <= min(5, $numCols); $j++) {
                    $cellValue = $reader->sheets[0]['cells'][$i][$j] ?? 'NULL';
                    echo "<td style='padding: 5px;'>" . htmlspecialchars($cellValue) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>没有获取到数据结构</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>异常: " . $e->getMessage() . "</p>";
    echo "<p>文件追踪: <pre>" . $e->getTraceAsString() . "</pre></p>";
} catch (Error $e) {
    echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
    echo "<p>文件追踪: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<br><a href='final_test.php'>返回最终测试</a> | <a href='index.php'>首页</a>";

?>