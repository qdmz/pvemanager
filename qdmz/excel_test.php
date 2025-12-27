<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Excel读取器测试页面</h2>";

// 测试Excel读取器
include 'inc/excel_reader.php';

echo "<h3>1. 检查ExcelReader类:</h3>";
if (class_exists('ExcelReader')) {
    echo "<p style='color: green;'>ExcelReader类已定义</p>";
} else {
    echo "<p style='color: red;'>ExcelReader类未定义</p>";
    exit;
}

echo "<h3>2. 尝试读取Excel文件:</h3>";

// 查找数据目录中的Excel文件
$uploadDir = 'shujukufangzheli';
$excelFiles = [];

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['xls', 'xlsx'])) {
            $excelFiles[] = $file;
        }
    }
} else {
    echo "<p style='color: red;'>数据目录 $uploadDir 不存在</p>";
    exit;
}

if (empty($excelFiles)) {
    echo "<p style='color: orange;'>在 $uploadDir 目录中未找到Excel文件</p>";
    exit;
}

echo "<p>找到Excel文件: " . implode(', ', $excelFiles) . "</p>";

// 尝试读取第一个Excel文件
$firstFile = $excelFiles[0];
$filePath = $uploadDir . '/' . $firstFile;

echo "<p>尝试读取文件: $filePath</p>";

try {
    $reader = new ExcelReader();
    $reader->setOutputEncoding('UTF-8');
    $reader->read($filePath);
    
    if (isset($reader->sheets[0]['numRows']) && isset($reader->sheets[0]['numCols'])) {
        echo "<p style='color: green;'>成功读取文件!</p>";
        echo "<p>行数: " . $reader->sheets[0]['numRows'] . "</p>";
        echo "<p>列数: " . $reader->sheets[0]['numCols'] . "</p>";
        
        // 显示前几行数据
        echo "<h3>3. 前3行数据预览:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        $maxRows = min(3, $reader->sheets[0]['numRows']);
        for ($i = 1; $i <= $maxRows; $i++) {
            echo "<tr>";
            $maxCols = min(5, $reader->sheets[0]['numCols']); // 只显示前5列
            for ($j = 1; $j <= $maxCols; $j++) {
                $cellValue = isset($reader->sheets[0]['cells'][$i][$j]) ? $reader->sheets[0]['cells'][$i][$j] : '';
                echo "<td style='padding: 5px;'>" . htmlspecialchars($cellValue) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>读取了文件，但没有获取到行列信息</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>读取文件失败: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='admin.php'>管理后台</a>";

?>