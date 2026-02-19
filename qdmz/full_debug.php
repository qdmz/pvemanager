<?php
// 开启所有错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h2>全面错误调试</h2>";

echo "<h3>1. PHP环境信息:</h3>";
echo "<p>PHP版本: " . phpversion() . "</p>";
echo "<p>当前工作目录: " . getcwd() . "</p>";
echo "<p>当前文件: " . __FILE__ . "</p>";

echo "<h3>2. 检查所有依赖文件:</h3>";
$files_to_check = [
    'index.php',
    'inc/conn.php',
    'inc/excel_reader.php',
    'inc/excel.php',
    'vendor/autoload.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    echo "<p>$file: " . ($exists ? "<span style='color: green;'>存在</span>" : "<span style='color: red;'>不存在</span>") . "</p>";
}

echo "<h3>3. 检查目录权限:</h3>";
$dirs_to_check = [
    'shujukufangzheli',
    'inc',
    'vendor',
    '.'
];

foreach ($dirs_to_check as $dir) {
    $exists = is_dir($dir);
    $readable = $exists ? is_readable($dir) : false;
    $writable = $exists ? is_writable($dir) : false;
    echo "<p>$dir: 存在=" . ($exists ? '是' : '否') . ", 可读=" . ($readable ? '是' : '否') . ", 可写=" . ($writable ? '是' : '否') . "</p>";
}

echo "<h3>4. 测试包含配置文件:</h3>";
if (file_exists('inc/conn.php')) {
    echo "<p>尝试包含 inc/conn.php...</p>";
    include 'inc/conn.php';
    echo "<p style='color: green;'>配置文件包含成功</p>";
    echo "<p>UpDir: $UpDir</p>";
    echo "<p>tiaojian1: $tiaojian1</p>";
    echo "<p>ismas: $ismas</p>";
} else {
    echo "<p style='color: red;'>配置文件不存在</p>";
}

echo "<h3>5. 测试包含Excel读取器:</h3>";
if (file_exists('inc/excel_reader.php')) {
    echo "<p>尝试包含 inc/excel_reader.php...</p>";
    try {
        include 'inc/excel_reader.php';
        echo "<p style='color: green;'>Excel读取器包含成功</p>";
        
        if (class_exists('ExcelReader')) {
            echo "<p style='color: green;'>ExcelReader类存在</p>";
            
            try {
                $reader = new ExcelReader();
                echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>ExcelReader实例创建失败: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>ExcelReader类不存在</p>";
        }
    } catch (Error $e) {
        echo "<p style='color: red;'>包含Excel读取器时出错: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<h3>6. 测试自动加载器:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p>尝试包含 vendor/autoload.php...</p>";
    try {
        require_once 'vendor/autoload.php';
        echo "<p style='color: green;'>自动加载器包含成功</p>";
        
        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            echo "<p style='color: green;'>PhpSpreadsheet类可用</p>";
        } else {
            echo "<p style='color: orange;'>PhpSpreadsheet类不可用</p>";
        }
    } catch (Error $e) {
        echo "<p style='color: red;'>自动加载器加载失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>自动加载器文件不存在</p>";
}

echo "<h3>7. 尝试直接执行index.php的部分代码:</h3>";
echo "<p>测试设置输出编码...</p>";
define('DEFAULT_ENCODING', 'UTF-8');
header('Content-Type: text/html; charset=' . DEFAULT_ENCODING);
echo "<p style='color: green;'>输出编码设置成功</p>";

echo "<br><a href='index.php'>尝试访问index.php</a> | <a href='test_syntax_fix.php'>语法修复测试</a>";

?>