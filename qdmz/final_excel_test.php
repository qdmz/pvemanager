<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>最终Excel读取测试</h2>";

// 按依赖顺序包含关键文件
$essentialFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Font.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/PasswordHasher.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
];

echo "<h3>包含关键文件:</h3>";
foreach ($essentialFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        require_once $fullPath;
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: orange;'>→ 不存在: $file</p>";
    }
}

echo "<h3>测试关键类可用性:</h3>";
$essentialClasses = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Shared\\File'
];

foreach ($essentialClasses as $class) {
    $exists = class_exists($class, false);
    echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
}

echo "<h3>测试Excel读取功能:</h3>";

if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
    echo "<p style='color: green;'>✓ IOFactory类可用</p>";
    
    // 尝试读取Excel文件
    include 'inc/conn.php';
    $excelDir = $UpDir;
    
    if (is_dir($excelDir)) {
        $excelFiles = glob($excelDir . '/*.{xls,xlsx}', GLOB_BRACE);
        
        if (!empty($excelFiles)) {
            $testFile = $excelFiles[0];
            $fileName = basename($testFile);
            echo "<p>测试文件: $fileName</p>";
            
            try {
                // 尝试检测文件类型并创建相应的读取器
                $ext = strtolower(pathinfo($testFile, PATHINFO_EXTENSION));
                
                if ($ext === 'xlsx') {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                } elseif ($ext === 'xls') {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
                } else {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($testFile);
                }
                
                $reader->setReadDataOnly(true); // 只读取数据，不读取格式
                $spreadsheet = $reader->load($testFile);
                
                echo "<p style='color: green;'>✓ Excel文件加载成功</p>";
                
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                
                echo "<p>工作表信息:</p>";
                echo "<ul>";
                echo "<li>行数: $highestRow</li>";
                echo "<li>列数: $highestColumnIndex ($highestColumn)</li>";
                echo "</ul>";
                
                // 显示前几行数据作为示例
                echo "<p>前3行数据预览:</p>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                for ($row = 1; $row <= min(3, $highestRow); $row++) {
                    echo "<tr>";
                    for ($col = 1; $col <= min(5, $highestColumnIndex); $col++) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                        echo "<td>" . htmlspecialchars($cellValue) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>读取失败: " . $e->getMessage() . "</p>";
                
                // 如果IOFactory方式失败，尝试使用旧方法
                echo "<p>尝试使用旧的Spreadsheet_Excel_Reader方法:</p>";
                if (file_exists('inc/excel.php')) {
                    include_once 'inc/excel.php';
                    if (class_exists('Spreadsheet_Excel_Reader')) {
                        try {
                            $oldReader = new Spreadsheet_Excel_Reader();
                            $oldReader->setOutputEncoding('UTF-8');
                            $oldReader->read($testFile);
                            
                            echo "<p style='color: green;'>✓ 旧方法读取成功</p>";
                            echo "<p>行数: " . $oldReader->sheets[0]['numRows'] . "</p>";
                            echo "<p>列数: " . $oldReader->sheets[0]['numCols'] . "</p>";
                            
                        } catch (Exception $oldE) {
                            echo "<p style='color: orange;'>旧方法也失败: " . $oldE->getMessage() . "</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>旧的Spreadsheet_Excel_Reader类不可用</p>";
                    }
                }
            }
        } else {
            echo "<p style='color: orange;'>数据目录中没有找到Excel文件</p>";
        }
    } else {
        echo "<p style='color: red;'>数据目录不存在</p>";
    }
} else {
    echo "<p style='color: red;'>IOFactory类不可用</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='excel_debug.php'>Excel调试</a>";

?>