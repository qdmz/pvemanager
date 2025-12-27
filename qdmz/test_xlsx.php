<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>测试XLSX文件读取</h2>";

// 包含Excel读取器
include_once 'inc/excel_reader.php';

$testFile = 'shujukufangzheli/a田冲2147新.xlsx';

if (!file_exists($testFile)) {
    echo "<p style='color: red;'>测试文件不存在: $testFile</p>";
    
    // 查找可用的XLSX文件
    $files = scandir('shujukufangzheli');
    $xlsxFiles = array_filter($files, function($file) {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'xlsx';
    });
    
    if (!empty($xlsxFiles)) {
        $testFile = 'shujukufangzheli/' . reset($xlsxFiles);
        echo "<p>找到XLSX文件: $testFile</p>";
    } else {
        echo "<p style='color: red;'>未找到任何XLSX文件</p>";
        exit;
    }
}

echo "<p>尝试读取文件: $testFile</p>";

try {
    $reader = new ExcelReader();
    $reader->read($testFile);
    
    echo "<p style='color: green;'>✓ 文件读取成功</p>";
    
    if (isset($reader->sheets) && isset($reader->sheets[0])) {
        $numRows = $reader->sheets[0]['numRows'] ?? 0;
        $numCols = $reader->sheets[0]['numCols'] ?? 0;
        echo "<p>数据行数: $numRows, 列数: $numCols</p>";
        
        if ($numRows > 0 && $numCols > 0) {
            echo "<h3>前几行数据预览:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            for ($i = 1; $i <= min(5, $numRows); $i++) {
                echo "<tr>";
                for ($j = 1; $j <= min(5, $numCols); $j++) {
                    $cellValue = $reader->sheets[0]['cells'][$i][$j] ?? '';
                    echo "<td style='padding: 5px;'>" . htmlspecialchars($cellValue) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 读取失败: " . $e->getMessage() . "</p>";
    
    // 尝试其他方法
    echo "<h3>尝试使用PhpOffice直接读取:</h3>";
    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        try {
            $ext = strtolower(pathinfo($testFile, PATHINFO_EXTENSION));
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext));
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($testFile);
            
            echo "<p style='color: green;'>✓ PhpOffice读取成功</p>";
        } catch (Exception $e2) {
            echo "<p style='color: red;'>✗ PhpOffice读取失败: " . $e2->getMessage() . "</p>";
        }
    } else {
        echo "<p>PhpOffice\\PhpSpreadsheet\\IOFactory 不可用</p>";
    }
}

echo "<br><a href='index.php'>返回首页</a> | <a href='admin.php'>管理后台</a> | <a href='final_check.php'>最终检查</a>";

?>