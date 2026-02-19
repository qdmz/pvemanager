<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Excel读取调试</h2>";

echo "<h3>1. PHP环境信息:</h3>";
echo "<p>PHP版本: " . phpversion() . "</p>";
echo "<p>当前目录: " . getcwd() . "</p>";

echo "<h3>2. 检查依赖文件:</h3>";
$files = [
    'vendor/autoload.php',
    'inc/excel_reader.php',
    'inc/excel.php'
];

foreach ($files as $file) {
    $exists = file_exists($file);
    echo "<p>$file: " . ($exists ? "<span style='color: green;'>存在</span>" : "<span style='color: red;'>不存在</span>") . "</p>";
}

echo "<h3>3. 检查自动加载器:</h3>";
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    echo "<p style='color: green;'>自动加载器包含成功</p>";
    
    // 检查PhpSpreadsheet类
    $hasSpreadsheet = class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet');
    $hasIOFactory = class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory');
    echo "<p>PhpSpreadsheet类: " . ($hasSpreadsheet ? "<span style='color: green;'>可用</span>" : "<span style='color: orange;'>不可用</span>") . "</p>";
    echo "<p>IOFactory类: " . ($hasIOFactory ? "<span style='color: green;'>可用</span>" : "<span style='color: orange;'>不可用</span>") . "</p>";
} else {
    echo "<p style='color: red;'>自动加载器文件不存在</p>";
}

echo "<h3>4. 检查数据目录:</h3>";
include 'inc/conn.php'; // 包含配置
echo "<p>数据目录: $UpDir</p>";
echo "<p>目录存在: " . (is_dir($UpDir) ? '是' : '否') . "</p>";
echo "<p>目录可读: " . (is_readable($UpDir) ? '是' : '否') . "</p>";

if (is_dir($UpDir)) {
    $files = scandir($UpDir);
    echo "<p>目录内容:</p><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['xls', 'xlsx'])) {
                echo "<li>$file (".filesize($UpDir.'/'.$file)." bytes)</li>";
            }
        }
    }
    echo "</ul>";
}

echo "<h3>5. 测试Excel读取器:</h3>";
include 'inc/excel_reader.php';
if (class_exists('ExcelReader')) {
    echo "<p style='color: green;'>ExcelReader类定义成功</p>";
    
    try {
        $reader = new ExcelReader();
        echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
        
        // 尝试读取第一个Excel文件
        if (is_dir($UpDir)) {
            $excelFiles = [];
            foreach (scandir($UpDir) as $file) {
                if ($file != '.' && $file != '..') {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['xls', 'xlsx'])) {
                        $excelFiles[] = $file;
                    }
                }
            }
            
            if (!empty($excelFiles)) {
                $testFile = $UpDir . '/' . $excelFiles[0];
                echo "<p>尝试读取文件: $testFile</p>";
                echo "<p>文件存在: " . (file_exists($testFile) ? '是' : '否') . "</p>";
                echo "<p>文件可读: " . (is_readable($testFile) ? '是' : '否') . "</p>";
                
                if (file_exists($testFile)) {
                    try {
                        $reader->read($testFile);
                        echo "<p style='color: green;'>文件读取成功!</p>";
                        echo "<p>行数: " . $reader->sheets[0]['numRows'] . "</p>";
                        echo "<p>列数: " . $reader->sheets[0]['numCols'] . "</p>";
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>文件读取失败: " . $e->getMessage() . "</p>";
                    }
                }
            } else {
                echo "<p style='color: orange;'>数据目录中没有找到Excel文件</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>ExcelReader实例创建失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>ExcelReader类未定义</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='admin.php'>管理后台</a>";

?>