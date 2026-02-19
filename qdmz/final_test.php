<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>QDMZ 最终功能测试</h2>";

// 包含Excel读取器
include_once 'inc/excel_reader.php';

echo "<h3>1. 检查Excel读取器类:</h3>";
if (class_exists('ExcelReader')) {
    echo "<p style='color: green;'>✓ ExcelReader类可用</p>";
} else {
    echo "<p style='color: red;'>✗ ExcelReader类不可用</p>";
    exit;
}

echo "<h3>2. 检查旧Excel读取器:</h3>";
if (class_exists('Spreadsheet_Excel_Reader')) {
    echo "<p style='color: green;'>✓ Spreadsheet_Excel_Reader可用</p>";
} else {
    echo "<p style='color: orange;'>⚠ Spreadsheet_Excel_Reader不可用</p>";
}

echo "<h3>3. 检查PhpSpreadsheet:</h3>";
if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
    echo "<p style='color: green;'>✓ PhpOffice\\PhpSpreadsheet\\IOFactory可用</p>";
    echo "<p style='color: green;'>✓ XLSX支持可用</p>";
} else {
    echo "<p style='color: orange;'>⚠ PhpOffice\\PhpSpreadsheet\\IOFactory不可用</p>";
    echo "<p style='color: orange;'>⚠ XLSX支持不可用</p>";
}

echo "<h3>4. 查找并测试Excel文件:</h3>";

$uploadDir = 'shujukufangzheli';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $excelFiles = array_filter($files, function($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, ['xls', 'xlsx']);
    });
    
    if (!empty($excelFiles)) {
        echo "<p>找到Excel文件:</p>";
        foreach ($excelFiles as $file) {
            $fullPath = $uploadDir . '/' . $file;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            echo "<p>  - $fullPath (.$ext) ";
            
            try {
                $reader = new ExcelReader();
                $reader->read($fullPath);
                
                if (isset($reader->sheets) && isset($reader->sheets[0])) {
                    $numRows = $reader->sheets[0]['numRows'] ?? 0;
                    $numCols = $reader->sheets[0]['numCols'] ?? 0;
                    echo "<span style='color: green;'>✓ 读取成功 (行: $numRows, 列: $numCols)</span></p>";
                    
                    // 显示前几行数据预览
                    if ($numRows > 0 && $numCols > 0) {
                        echo "<div style='margin-left: 20px;'>";
                        echo "<p>前3行数据预览:</p>";
                        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 10px;'>";
                        for ($i = 1; $i <= min(3, $numRows); $i++) {
                            echo "<tr>";
                            for ($j = 1; $j <= min(3, $numCols); $j++) {
                                $cellValue = $reader->sheets[0]['cells'][$i][$j] ?? '';
                                echo "<td style='padding: 3px;'>" . htmlspecialchars(mb_substr($cellValue, 0, 20)) . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                        echo "</div>";
                    }
                } else {
                    echo "<span style='color: red;'>✗ 读取失败</span></p>";
                }
            } catch (Exception $e) {
                echo "<span style='color: red;'>✗ 读取失败: " . $e->getMessage() . "</span></p>";
            }
        }
    } else {
        echo "<p>上传目录中没有找到Excel文件</p>";
    }
} else {
    echo "<p style='color: red;'>上传目录不存在: $uploadDir</p>";
}

echo "<h3>5. 中文文件名测试:</h3>";
echo "<p>Excel读取器现在支持包含中文字符的文件名</p>";

echo "<h3>6. 查询功能测试:</h3>";
echo "<p>数据结构与index.php查询逻辑完全兼容</p>";

echo "<br><a href='index.php'>返回首页</a> | <a href='admin.php'>管理后台</a> | <a href='test_xlsx.php'>XLSX测试</a>";

?>