<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>简单XLSX测试</h2>";

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

$testFile = 'shujukufangzheli/a田冲2147新.xlsx';

echo "<p>测试文件: $testFile</p>";

if (!file_exists($testFile)) {
    echo "<p style='color: red;'>文件不存在</p>";
    exit;
}

echo "<p>文件大小: " . filesize($testFile) . " 字节</p>";

// 尝试直接使用PhpSpreadsheet
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    
    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        echo "<p style='color: green;'>✓ IOFactory可用</p>";
        
        try {
            echo "<p>尝试创建Xlsx读取器...</p>";
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            echo "<p style='color: green;'>✓ Xlsx读取器创建成功</p>";
            
            echo "<p>设置仅读取数据模式...</p>";
            $reader->setReadDataOnly(true);
            
            echo "<p>加载文件...</p>";
            $spreadsheet = $reader->load($testFile);
            echo "<p style='color: green;'>✓ 文件加载成功</p>";
            
            echo "<p>获取活动工作表...</p>";
            $worksheet = $spreadsheet->getActiveSheet();
            echo "<p style='color: green;'>✓ 获取工作表成功</p>";
            
            $numRows = $worksheet->getHighestRow();
            $numCols = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
            
            echo "<p>数据行数: $numRows</p>";
            echo "<p>数据列数: $numCols</p>";
            
            if ($numRows > 0 && $numCols > 0) {
                echo "<h3>前几行数据:</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                for ($row = 1; $row <= min(5, $numRows); $row++) {
                    echo "<tr>";
                    for ($col = 1; $col <= min(5, $numCols); $col++) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $cell = $worksheet->getCell($columnLetter . $row);
                        $value = $cell->getValue();
                        echo "<td style='padding: 5px;'>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>异常: " . $e->getMessage() . "</p>";
            echo "<p>文件追踪: <pre>" . $e->getTraceAsString() . "</pre></p>";
        } catch (Error $e) {
            echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
            echo "<p>文件追踪: <pre>" . $e->getTraceAsString() . "</pre></p>";
        }
    } else {
        echo "<p style='color: red;'>✗ IOFactory不可用</p>";
    }
} else {
    echo "<p style='color: red;'>✗ vendor/autoload.php不存在</p>";
}

echo "<br><a href='debug_xlsx.php'>调试XLSX</a> | <a href='index.php'>首页</a>";

?>