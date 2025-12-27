<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>调试数据结构</h2>";

// 包含必要的文件
require_once 'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/Shared/File.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/IOFactory.php';

include 'inc/conn.php';
include 'inc/excel_reader.php';

// 测试Excel文件
$testFile = $UpDir . '/2025.xls';
echo "<p>测试文件: $testFile</p>";
echo "<p>文件存在: " . (file_exists($testFile) ? '是' : '否') . "</p>";

if (file_exists($testFile)) {
    try {
        $data = new ExcelReader();
        $data->setOutputEncoding('UTF-8');
        
        echo "<p>开始读取文件...</p>";
        $data->read($testFile);
        
        echo "<h3>读取后的数据结构:</h3>";
        echo "<p>检查sheets属性:</p>";
        var_dump(isset($data->sheets));
        
        if (isset($data->sheets)) {
            echo "<p>数据结构预览:</p>";
            echo "<pre>";
            print_r(array_slice($data->sheets, 0, 2)); // 只打印前2个元素
            echo "</pre>";
            
            if (isset($data->sheets[0])) {
                echo "<p>第一张工作表数据:</p>";
                echo "numRows: " . (isset($data->sheets[0]['numRows']) ? $data->sheets[0]['numRows'] : '不存在') . "<br>";
                echo "numCols: " . (isset($data->sheets[0]['numCols']) ? $data->sheets[0]['numCols'] : '不存在') . "<br>";
                
                if (isset($data->sheets[0]['cells'])) {
                    echo "<p>前几行单元格数据:</p>";
                    for ($i = 1; $i <= min(3, $data->sheets[0]['numRows'] ?? 3); $i++) {
                        echo "第{$i}行: ";
                        for ($j = 1; $j <= min(3, $data->sheets[0]['numCols'] ?? 3); $j++) {
                            $cellValue = $data->sheets[0]['cells'][$i][$j] ?? 'NULL';
                            echo "[{$j}]{$cellValue} ";
                        }
                        echo "<br>";
                    }
                }
            }
        }
        
        // 测试旧方法
        echo "<h3>测试旧方法 (Spreadsheet_Excel_Reader):</h3>";
        if (file_exists('inc/excel.php')) {
            include_once 'inc/excel.php';
            if (class_exists('Spreadsheet_Excel_Reader')) {
                $oldReader = new Spreadsheet_Excel_Reader();
                $oldReader->setOutputEncoding('UTF-8');
                $oldReader->read($testFile);
                
                echo "<p>旧方法数据结构:</p>";
                echo "numRows: " . $oldReader->sheets[0]['numRows'] . "<br>";
                echo "numCols: " . $oldReader->sheets[0]['numCols'] . "<br>";
                
                echo "<p>前几行数据:</p>";
                for ($i = 1; $i <= min(3, $oldReader->sheets[0]['numRows']); $i++) {
                    echo "第{$i}行: ";
                    for ($j = 1; $j <= min(3, $oldReader->sheets[0]['numCols']); $j++) {
                        $cellValue = $oldReader->sheets[0]['cells'][$i][$j] ?? 'NULL';
                        echo "[{$j}]{$cellValue} ";
                    }
                    echo "<br>";
                }
            } else {
                echo "<p>Spreadsheet_Excel_Reader类不可用</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>读取失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>测试文件不存在</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='final_excel_test.php'>最终测试</a>";

?>