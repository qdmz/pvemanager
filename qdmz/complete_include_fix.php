<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>完整包含修复</h2>";

echo "<h3>包含所有必要的PhpSpreadsheet文件:</h3>";

// 递归获取所有需要的文件
function getAllSpreadsheetFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $relativePath = str_replace($dir . '/', '', $file->getPathname());
            // 只包含src目录下的文件
            if (strpos($relativePath, 'src/') === 0) {
                $files[] = $file->getPathname();
            }
        }
    }
    return $files;
}

$spreadsheetDir = __DIR__ . '/vendor/phpoffice/phpspreadsheet';
$allFiles = getAllSpreadsheetFiles($spreadsheetDir);

// 按类型分类文件，优先包含基础类
$coreFiles = [];
$interfaceFiles = [];
$otherFiles = [];

foreach ($allFiles as $file) {
    $relativePath = str_replace($spreadsheetDir . '/', '', $file);
    
    if (strpos($relativePath, 'src/Cell/') === 0 ||
        strpos($relativePath, 'src/Shared/') === 0 ||
        strpos($relativePath, 'src/Calculation/Calculation.php') !== false ||
        strpos($relativePath, 'src/Calculation/Functions.php') !== false ||
        strpos($relativePath, 'src/ReferenceHelper.php') !== false) {
        $coreFiles[] = $file;
    } elseif (strpos($relativePath, '.php') !== false && 
              (strpos(basename($file), 'Interface') !== false || 
               strpos(file_get_contents($file, false, null, 0, 1000), 'interface ') !== false)) {
        $interfaceFiles[] = $file;
    } else {
        $otherFiles[] = $file;
    }
}

// 首先包含基础类
echo "<h4>包含基础类:</h4>";
$includedFiles = [];
$successCount = 0;
$failCount = 0;

foreach ($coreFiles as $file) {
    if (file_exists($file) && !in_array($file, $includedFiles)) {
        try {
            require_once $file;
            $includedFiles[] = $file;
            echo "<p style='color: green;'>✓ " . str_replace(__DIR__ . '/', '', $file) . "</p>";
            $successCount++;
        } catch (Exception $e) {
            echo "<p style='color: orange;'>→ " . str_replace(__DIR__ . '/', '', $file) . " - " . $e->getMessage() . "</p>";
            $failCount++;
        } catch (Error $e) {
            echo "<p style='color: orange;'>→ " . str_replace(__DIR__ . '/', '', $file) . " - " . $e->getMessage() . "</p>";
            $failCount++;
        }
    }
}

// 然后包含接口
echo "<h4>包含接口:</h4>";
foreach ($interfaceFiles as $file) {
    if (file_exists($file) && !in_array($file, $includedFiles)) {
        try {
            require_once $file;
            $includedFiles[] = $file;
            echo "<p style='color: green;'>✓ " . str_replace(__DIR__ . '/', '', $file) . "</p>";
            $successCount++;
        } catch (Exception $e) {
            echo "<p style='color: orange;'>→ " . str_replace(__DIR__ . '/', '', $file) . " - " . $e->getMessage() . "</p>";
            $failCount++;
        } catch (Error $e) {
            echo "<p style='color: orange;'>→ " . str_replace(__DIR__ . '/', '', $file) . " - " . $e->getMessage() . "</p>";
            $failCount++;
        }
    }
}

// 最后包含其他文件
echo "<h4>包含其他类:</h4>";
foreach ($otherFiles as $file) {
    if (file_exists($file) && !in_array($file, $includedFiles)) {
        try {
            require_once $file;
            $includedFiles[] = $file;
            echo "<p style='color: green;'>✓ " . str_replace(__DIR__ . '/', '', $file) . "</p>";
            $successCount++;
        } catch (Exception $e) {
            echo "<p style='color: orange;'>→ " . str_replace(__DIR__ . '/', '', $file) . " - " . $e->getMessage() . "</p>";
            $failCount++;
        } catch (Error $e) {
            echo "<p style='color: orange;'>→ " . str_replace(__DIR__ . '/', '', $file) . " - " . $e->getMessage() . "</p>";
            $failCount++;
        }
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
    'PhpOffice\\PhpSpreadsheet\\Reader\\Security\\XmlScanner',
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