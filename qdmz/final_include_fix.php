<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>最终包含修复</h2>";

echo "<h3>包含所有必要的PhpSpreadsheet文件:</h3>";

// 按依赖顺序包含所有必要的文件
$allFiles = [
    // 基础类
    'vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    
    // 引用助手类
    'vendor/phpoffice/phpspreadsheet/src/ReferenceHelper.php',
    
    // 共享类
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Font.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/PasswordHasher.php',
    
    // 样式相关
    'vendor/phpoffice/phpspreadsheet/src/Style/Supervisor.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Color.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Border.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Alignment.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Style.php',
    
    // 计算相关
    'vendor/phpoffice/phpspreadsheet/src/Calculation/Calculation.php',
    'vendor/phpoffice/phpspreadsheet/src/Calculation/Functions.php',
    
    // 工作表相关
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
    
    // 读取器接口和基类
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/XlsBase.php',
    
    // Xlsx内部类
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/Namespaces.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/BaseParserClass.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/WorkbookView.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/SheetViews.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/SheetViewOptions.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/Styles.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/Theme.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/Properties.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/PageSetup.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/Hyperlinks.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/DataValidations.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/ConditionalStyles.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/ColumnAndRowAttributes.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/TableReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/SharedFormula.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/AutoFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx/Chart.php',
    
    // 读取器实现
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    
    // 主要类
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
];

$successCount = 0;
$failCount = 0;

foreach ($allFiles as $file) {
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "<p style='color: green;'>✓ $file</p>";
            $successCount++;
        } catch (Exception $e) {
            echo "<p style='color: orange;'>→ $file - " . $e->getMessage() . "</p>";
            $failCount++;
        } catch (Error $e) {
            echo "<p style='color: orange;'>→ $file - " . $e->getMessage() . "</p>";
            $failCount++;
        }
    } else {
        echo "<p style='color: gray;'>→ $file (不存在)</p>";
    }
}

echo "<p><strong>包含结果: $successCount 个成功, $failCount 个失败</strong></p>";

echo "<h3>检查关键类可用性:</h3>";
$testClasses = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls',
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
    'PhpOffice\\PhpSpreadsheet\\ReferenceHelper',
];

foreach ($testClasses as $class) {
    $available = class_exists($class, false);
    echo "<p>$class: " . ($available ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
}

echo "<h3>尝试创建读取器实例:</h3>";
if (class_exists('PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx', false)) {
    try {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        echo "<p style='color: green;'>✓ Xlsx读取器实例创建成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Xlsx读取器实例创建失败: " . $e->getMessage() . "</p>";
    } catch (Error $e) {
        echo "<p style='color: red;'>✗ Xlsx读取器实例创建错误: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Xlsx类不可用</p>";
}

if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory', false)) {
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        echo "<p style='color: green;'>✓ IOFactory创建Xlsx读取器成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ IOFactory创建Xlsx读取器失败: " . $e->getMessage() . "</p>";
    } catch (Error $e) {
        echo "<p style='color: red;'>✗ IOFactory创建Xlsx读取器错误: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ IOFactory类不可用</p>";
}

// 简单测试XLSX文件
echo "<h3>测试XLSX文件读取:</h3>";
$testFile = 'shujukufangzheli/a田冲2147新.xlsx';

if (file_exists($testFile) && class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory', false)) {
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($testFile);
        
        $worksheet = $spreadsheet->getActiveSheet();
        $numRows = $worksheet->getHighestRow();
        $numCols = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        
        echo "<p style='color: green;'>✓ XLSX文件读取成功</p>";
        echo "<p>数据行数: $numRows, 列数: $numCols</p>";
        
        if ($numRows > 0 && $numCols > 0) {
            echo "<h4>前3行数据预览:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            for ($row = 1; $row <= min(3, $numRows); $row++) {
                echo "<tr>";
                for ($col = 1; $col <= min(3, $numCols); $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                    echo "<td style='padding: 5px;'>" . htmlspecialchars($cellValue) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ XLSX文件读取失败: " . $e->getMessage() . "</p>";
    } catch (Error $e) {
        echo "<p style='color: red;'>✗ XLSX文件读取错误: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>无法测试XLSX文件读取</p>";
}

echo "<br><a href='index.php'>首页</a> | <a href='admin.php'>管理后台</a> | <a href='simple_xlsx_test.php'>简单XLSX测试</a>";

?>