<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>检查读取器可用性</h2>";

echo "<h3>检查旧的Spreadsheet_Excel_Reader:</h3>";
echo "<p>检查文件 'inc/excel.php' 存在: " . (file_exists('inc/excel.php') ? '是' : '否') . "</p>";

if (file_exists('inc/excel.php')) {
    echo "<p>尝试包含 'inc/excel.php'...</p>";
    include_once 'inc/excel.php';
    
    echo "<p>Spreadsheet_Excel_Reader类存在: " . (class_exists('Spreadsheet_Excel_Reader') ? '是' : '否') . "</p>";
    
    if (class_exists('Spreadsheet_Excel_Reader')) {
        echo "<p>尝试创建Spreadsheet_Excel_Reader实例...</p>";
        try {
            $reader = new Spreadsheet_Excel_Reader();
            echo "<p style='color: green;'>✓ 实例创建成功</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ 实例创建失败: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>文件 'inc/excel.php' 不存在</p>";
}

echo "<h3>检查PhpOffice\\PhpSpreadsheet\\IOFactory:</h3>";
if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
    echo "<p style='color: green;'>✓ IOFactory类可用</p>";
    
    // 尝试手动加载Xls文件
    echo "<p>尝试手动加载Xls文件...</p>";
    $testFile = 'shujukufangzheli/2025.xls';
    if (file_exists($testFile)) {
        echo "<p>测试文件存在</p>";
        
        try {
            // 尝试直接创建Xlsx读取器（即使文件是xls格式）
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            echo "<p>✓ Xlsx读取器创建成功</p>";
        } catch (Exception $e) {
            echo "<p>✗ Xlsx读取器创建失败: " . $e->getMessage() . "</p>";
        }
        
        try {
            // 尝试直接创建Xls读取器（即使不可用）
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
            echo "<p>✓ Xls读取器创建成功</p>";
        } catch (Exception $e) {
            echo "<p>✗ Xls读取器创建失败: " . $e->getMessage() . "</p>";
        }
        
        try {
            // 尝试使用自动检测
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($testFile);
            echo "<p>✓ 自动检测读取器创建成功</p>";
        } catch (Exception $e) {
            echo "<p>✗ 自动检测读取器创建失败: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>测试文件不存在</p>";
    }
} else {
    echo "<p style='color: red;'>IOFactory类不可用</p>";
}

echo "<h3>检查Excel文件信息:</h3>";
$testFile = 'shujukufangzheli/2025.xls';
if (file_exists($testFile)) {
    echo "<p>文件大小: " . filesize($testFile) . " 字节</p>";
    echo "<p>文件MIME类型: " . mime_content_type($testFile) . "</p>";
    echo "<p>文件扩展名: " . pathinfo($testFile, PATHINFO_EXTENSION) . "</p>";
} else {
    echo "<p>测试文件不存在</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='comprehensive_fix.php'>全面修复</a>";

?>