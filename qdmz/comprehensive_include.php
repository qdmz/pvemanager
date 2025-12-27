<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>全面包含修复</h2>";

echo "<h3>包含所有必要的PhpSpreadsheet文件:</h3>";

// 首先包含基础文件
$basicFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Font.php',
];

foreach ($basicFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 不存在</p>";
    }
}

// 包含样式相关文件（需要先包含Supervisor）
$styleFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Style/Supervisor.php',  // 首先包含Supervisor
    'vendor/phpoffice/phpspreadsheet/src/Style/Color.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Border.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Alignment.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Style.php',
];

foreach ($styleFiles as $file) {
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "<p style='color: green;'>✓ $file</p>";
        } catch (Error $e) {
            echo "<p style='color: orange;'>→ $file (可能不存在或不依赖Supervisor)</p>";
            // 尝试包含其他文件即使Supervisor不存在
            if (file_exists($file)) {
                include_once $file;
            }
        }
    } else {
        echo "<p style='color: orange;'>→ $file (不存在)</p>";
    }
}

// 包含工作表相关文件
$worksheetFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
];

foreach ($worksheetFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 不存在</p>";
    }
}

// 包含读取器相关文件
$readerFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/XlsBase.php',
];

foreach ($readerFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 不存在</p>";
    }
}

// 包含Xlsx内部类
$xlsxInternalFiles = [
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
];

echo "<h3>包含Xlsx内部类:</h3>";
foreach ($xlsxInternalFiles as $file) {
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "<p style='color: green;'>✓ $file</p>";
        } catch (Error $e) {
            echo "<p style='color: orange;'>→ $file - " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: gray;'>→ $file (不存在)</p>";
    }
}

// 最后包含主要的读取器和主类
$mainFiles = [
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
];

echo "<h3>包含主要类:</h3>";
foreach ($mainFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 不存在</p>";
    }
}

echo "<h3>检查关键类可用性:</h3>";
$testClasses = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls',
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
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