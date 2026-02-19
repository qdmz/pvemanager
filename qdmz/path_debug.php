<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>路径调试页面</h2>";

echo "<h3>1. 当前工作目录:</h3>";
echo "<p>" . getcwd() . "</p>";

echo "<h3>2. 当前文件路径:</h3>";
echo "<p>" . __FILE__ . "</p>";

echo "<h3>3. __DIR__ 值:</h3>";
echo "<p>" . __DIR__ . "</p>";

echo "<h3>4. vendor/autoload.php 路径测试:</h3>";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
$relativeAutoloadPath = __DIR__ . '/../vendor/autoload.php';
$upOneAutoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

echo "<p>绝对路径: " . $autoloadPath . "</p>";
echo "<p>文件存在: " . (file_exists($autoloadPath) ? '是' : '否') . "</p>";

echo "<h3>5. Excel读取器路径测试:</h3>";
$excelReaderPath = __DIR__ . '/inc/excel_reader.php';
echo "<p>Excel读取器路径: " . $excelReaderPath . "</p>";
echo "<p>文件存在: " . (file_exists($excelReaderPath) ? '是' : '否') . "</p>";

echo "<h3>6. 数据目录路径测试:</h3>";
$dataDir = __DIR__ . '/shujukufangzheli';
echo "<p>数据目录路径: " . $dataDir . "</p>";
echo "<p>目录存在: " . (is_dir($dataDir) ? '是' : '否') . "</p>";
echo "<p>目录可读: " . (is_readable($dataDir) ? '是' : '否') . "</p>";
echo "<p>目录可写: " . (is_writable($dataDir) ? '是' : '否') . "</p>";

if (is_dir($dataDir)) {
    echo "<h4>数据目录内容:</h4>";
    $files = scandir($dataDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['xls', 'xlsx'])) {
                echo "<p style='color: green;'>Excel文件: $file (大小: " . filesize($dataDir . '/' . $file) . " 字节)</p>";
            } else {
                echo "<p>其他文件: $file</p>";
            }
        }
    }
}

echo "<h3>7. Excel读取器内部路径测试:</h3>";
echo "<p>Excel读取器中使用的路径: __DIR__ . '/../vendor/autoload.php'</p>";
echo "<p>当前文件在 " . __DIR__ . " 目录</p>";
echo "<p>所以 '../vendor/autoload.php' 指向: " . dirname(__DIR__) . '/vendor/autoload.php' . "</p>";
echo "<p>该文件存在: " . (file_exists(dirname(__DIR__) . '/vendor/autoload.php') ? '是' : '否') . "</p>";

echo "<h3>8. 测试包含Excel读取器:</h3>";
if (file_exists('inc/excel_reader.php')) {
    echo "<p>尝试包含 inc/excel_reader.php...</p>";
    include 'inc/excel_reader.php';
    echo "<p>包含成功</p>";
    
    if (class_exists('ExcelReader')) {
        echo "<p style='color: green;'>ExcelReader类已定义</p>";
    } else {
        echo "<p style='color: red;'>ExcelReader类未定义</p>";
    }
} else {
    echo "<p style='color: red;'>inc/excel_reader.php 文件不存在</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='excel_test.php'>Excel测试</a>";

?>