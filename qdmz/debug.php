<?php
// 调试文件，用于检测系统配置和功能
header('Content-Type: text/html; charset=utf-8');

echo "<h2>系统调试信息</h2>";

echo "<h3>1. PHP版本信息:</h3>";
echo "<p>PHP版本: " . phpversion() . "</p>";

echo "<h3>2. 检查必要扩展:</h3>";
$extensions = ['gd', 'mbstring', 'xml', 'zip', 'json'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '已安装' : '未安装';
    $color = extension_loaded($ext) ? 'green' : 'red';
    echo "<p style='color: $color'>扩展 $ext: $status</p>";
}

echo "<h3>3. 检查vendor/autoload.php:</h3>";
$autoload_exists = file_exists('vendor/autoload.php');
$autoload_status = $autoload_exists ? '存在' : '不存在';
$autoload_color = $autoload_exists ? 'green' : 'red';
echo "<p style='color: $autoload_color'>autoload.php: $autoload_status</p>";

if ($autoload_exists) {
    require_once 'vendor/autoload.php';
    echo "<p>自动加载器引入成功</p>";
    
    if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        echo "<p style='color: green'>PhpSpreadsheet类可用</p>";
    } else {
        echo "<p style='color: red'>PhpSpreadsheet类不可用</p>";
    }
}

echo "<h3>4. 检查目录权限:</h3>";
$upload_dir = 'shujukufangzheli';
if (is_dir($upload_dir)) {
    $writable = is_writable($upload_dir) ? '可写' : '不可写';
    $readable = is_readable($upload_dir) ? '可读' : '不可读';
    echo "<p>上传目录 ($upload_dir): $readable, $writable</p>";
} else {
    echo "<p>上传目录 ($upload_dir): 不存在</p>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p>上传目录已创建</p>";
    } else {
        echo "<p style='color: red'>无法创建上传目录</p>";
    }
}

echo "<h3>5. 检查配置文件:</h3>";
$conn_exists = file_exists('inc/conn.php');
echo "<p>inc/conn.php: " . ($conn_exists ? '存在' : '不存在') . "</p>";

if ($conn_exists) {
    include 'inc/conn.php';
    echo "<p>配置文件加载成功</p>";
    echo "<p>数据目录: $UpDir</p>";
    echo "<p>查询条件: $tiaojian1</p>";
    echo "<p>验证码设置: $ismas</p>";
}

echo "<h3>6. 检查Excel读取器:</h3>";
include 'inc/excel_reader.php';
if (class_exists('ExcelReader')) {
    echo "<p style='color: green'>ExcelReader类已定义</p>";
} else {
    echo "<p style='color: red'>ExcelReader类未定义</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='admin.php'>管理后台</a>";

?>