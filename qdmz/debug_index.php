<?php
// 开启错误报告来查看index.php的问题
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// 设置输出编码为UTF-8
define('DEFAULT_ENCODING', 'UTF-8');
header('Content-Type: text/html; charset=' . DEFAULT_ENCODING);

echo "<h2>Index.php 错误调试页面</h2>";

echo "<h3>1. 检查依赖文件:</h3>";
echo "<p>检查 inc/conn.php: " . (file_exists('inc/conn.php') ? '存在' : '不存在') . "</p>";
echo "<p>检查 inc/excel_reader.php: " . (file_exists('inc/excel_reader.php') ? '存在' : '不存在') . "</p>";
echo "<p>检查 vendor/autoload.php: " . (file_exists('vendor/autoload.php') ? '存在' : '不存在') . "</p>";

echo "<h3>2. 尝试包含配置文件:</h3>";
if (file_exists('inc/conn.php')) {
    include 'inc/conn.php';
    echo "<p>inc/conn.php 包含成功</p>";
    echo "<p>数据目录: $UpDir</p>";
    echo "<p>查询条件: $tiaojian1</p>";
    echo "<p>验证码设置: $ismas</p>";
} else {
    echo "<p style='color: red;'>inc/conn.php 不存在</p>";
}

echo "<h3>3. 尝试包含Excel读取器:</h3>";
if (file_exists('inc/excel_reader.php')) {
    include 'inc/excel_reader.php';
    echo "<p>inc/excel_reader.php 包含成功</p>";
    echo "<p>ExcelReader类定义: " . (class_exists('ExcelReader') ? '是' : '否') . "</p>";
} else {
    echo "<p style='color: red;'>inc/excel_reader.php 不存在</p>";
}

echo "<h3>4. 尝试创建ExcelReader实例:</h3>";
if (class_exists('ExcelReader')) {
    try {
        $reader = new ExcelReader();
        echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>ExcelReader实例创建失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>ExcelReader类不存在</p>";
}

echo "<h3>5. 检查数据目录:</h3>";
if (isset($UpDir) && is_dir($UpDir)) {
    echo "<p>数据目录 $UpDir 存在</p>";
    $files = scandir($UpDir);
    $excelFiles = [];
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['xls', 'xlsx'])) {
            $excelFiles[] = $file;
        }
    }
    echo "<p>Excel文件: " . implode(', ', $excelFiles) . "</p>";
} else {
    echo "<p style='color: red;'>数据目录 $UpDir 不存在或未定义</p>";
}

echo "<br><a href='index.php'>尝试访问index.php</a> | <a href='admin.php'>管理后台</a>";

?>