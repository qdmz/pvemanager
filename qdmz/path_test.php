<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>路径测试页面</h2>";

echo "<h3>1. 当前工作目录:</h3>";
echo "<p>" . getcwd() . "</p>";

echo "<h3>2. 检查vendor/autoload.php (相对路径):</h3>";
$relative_path = file_exists('vendor/autoload.php');
echo "<p>vendor/autoload.php (相对): " . ($relative_path ? '存在' : '不存在') . "</p>";

echo "<h3>3. 检查vendor/autoload.php (绝对路径):</h3>";
$absolute_path = file_exists(__DIR__ . '/vendor/autoload.php');
echo "<p>__DIR__ . '/vendor/autoload.php': " . ($absolute_path ? '存在' : '不存在') . "</p>";

echo "<h3>4. 尝试引入autoload.php:</h3>";
if ($relative_path) {
    echo "<p>尝试引入相对路径...</p>";
    require_once 'vendor/autoload.php';
    echo "<p>相对路径引入成功</p>";
} elseif ($absolute_path) {
    echo "<p>尝试引入绝对路径...</p>";
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p>绝对路径引入成功</p>";
} else {
    echo "<p style='color: red'>autoload.php 文件不存在</p>";
}

echo "<h3>5. 检查PhpSpreadsheet类:</h3>";
if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
    echo "<p style='color: green'>IOFactory类存在</p>";
} else {
    echo "<p style='color: red'>IOFactory类不存在</p>";
}

if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    echo "<p style='color: green'>Spreadsheet类存在</p>";
} else {
    echo "<p style='color: red'>Spreadsheet类不存在</p>";
}

echo "<h3>6. 尝试创建Spreadsheet对象:</h3>";
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "<p style='color: green'>Spreadsheet对象创建成功</p>";
} catch (Exception $e) {
    echo "<p style='color: red'>创建失败: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php'>返回首页</a>";

?>