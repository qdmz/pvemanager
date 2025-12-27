<?php
/**
 * QDMZ 部署脚本
 * 用于检查环境和快速部署
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h2>QDMZ 部署检查工具</h2>";

// 检查PHP版本
echo "<h3>1. PHP版本检查:</h3>";
$phpVersion = phpversion();
$requiredVersion = '7.0';
if (version_compare($phpVersion, $requiredVersion, '>=')) {
    echo "<p style='color: green;'>当前PHP版本: $phpVersion (满足要求)</p>";
} else {
    echo "<p style='color: red;'>当前PHP版本: $phpVersion (不满足要求，需要 $requiredVersion 或更高版本)</p>";
}

// 检查必需的扩展
echo "<h3>2. 必需扩展检查:</h3>";
$requiredExtensions = ['mbstring', 'xml', 'zip', 'json', 'gd'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>扩展 $ext: 已安装</p>";
    } else {
        echo "<p style='color: red;'>扩展 $ext: 未安装</p>";
    }
}

// 检查目录权限
echo "<h3>3. 目录权限检查:</h3>";
$dirsToCheck = ['shujukufangzheli', '.'];
foreach ($dirsToCheck as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p style='color: green;'>目录 $dir: 可写</p>";
        } else {
            echo "<p style='color: red;'>目录 $dir: 不可写，请设置写权限</p>";
        }
    } else {
        echo "<p style='color: orange;'>目录 $dir: 不存在，需要创建</p>";
        if ($dir === 'shujukufangzheli') {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color: green;'>目录 $dir: 已创建</p>";
            } else {
                echo "<p style='color: red;'>目录 $dir: 创建失败</p>";
            }
        }
    }
}

// 检查依赖
echo "<h3>4. 依赖检查:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>自动加载器: 存在</p>";
    
    // 尝试加载
    require_once 'vendor/autoload.php';
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet', true)) {
        echo "<p style='color: green;'>PhpSpreadsheet库: 可用</p>";
    } else {
        echo "<p style='color: red;'>PhpSpreadsheet库: 不可用</p>";
    }
} else {
    echo "<p style='color: red;'>自动加载器: 不存在</p>";
}

// 检查配置文件
echo "<h3>5. 配置文件检查:</h3>";
if (file_exists('inc/conn.php')) {
    echo "<p style='color: green;'>配置文件: 存在</p>";
    
    include 'inc/conn.php';
    echo "<p>数据目录: $UpDir</p>";
    echo "<p>查询条件: $tiaojian1</p>";
    echo "<p>验证码设置: $ismas</p>";
} else {
    echo "<p style='color: red;'>配置文件: 不存在</p>";
}

// 检查Excel读取器
echo "<h3>6. Excel读取器检查:</h3>";
if (file_exists('inc/excel_reader.php')) {
    include 'inc/excel_reader.php';
    if (class_exists('ExcelReader')) {
        echo "<p style='color: green;'>Excel读取器: 已定义</p>";
    } else {
        echo "<p style='color: red;'>Excel读取器: 未定义</p>";
    }
} else {
    echo "<p style='color: red;'>Excel读取器文件: 不存在</p>";
}

echo "<h3>7. 部署完成:</h3>";
echo "<p>如果以上检查大部分通过，系统已准备好运行。</p>";
echo "<p><a href='index.php'>访问前台页面</a> | <a href='admin.php'>访问后台管理</a></p>";

?>