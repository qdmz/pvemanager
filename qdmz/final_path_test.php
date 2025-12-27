<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>最终路径修复测试</h2>";

echo "<h3>1. 基础路径检查:</h3>";
echo "<p>当前目录: " . __DIR__ . "</p>";
echo "<p>vendor/autoload.php 存在: " . (file_exists('vendor/autoload.php') ? '是' : '否') . "</p>";

echo "<h3>2. 模拟Excel读取器路径查找:</h3>";
$excelReaderDir = __DIR__ . '/inc'; // 假设在inc目录下
echo "<p>Excel读取器目录: $excelReaderDir</p>";

if (file_exists($excelReaderDir . '/excel_reader.php')) {
    echo "<p>Excel读取器文件存在</p>";
    
    // 模拟Excel读取器中的路径查找逻辑
    $dir = $excelReaderDir; // 这是Excel读取器中的__DIR__
    
    $paths_to_try = [
        dirname($dir) . '/vendor/autoload.php',           // dirname(inc) = 项目根目录
        $dir . '/../vendor/autoload.php',                // inc/../vendor/autoload.php = 项目根目录/vendor/autoload.php
        dirname(dirname($dir)) . '/vendor/autoload.php', // dirname(dirname(inc)) = 项目根目录的上级
        dirname($dir, 2) . '/vendor/autoload.php',      // 项目根目录的上级
    ];
    
    foreach ($paths_to_try as $i => $path) {
        echo "<p>路径 " . ($i+1) . ": $path " . (file_exists($path) ? "<span style='color: green;'>存在</span>" : "<span style='color: red;'>不存在</span>") . "</p>";
    }
    
    echo "<h4>实际正确的路径:</h4>";
    $correctPath = __DIR__ . '/vendor/autoload.php';
    echo "<p>正确路径: $correctPath " . (file_exists($correctPath) ? "<span style='color: green;'>存在</span>" : "<span style='color: red;'>不存在</span>") . "</p>";
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<h3>3. 尝试包含Excel读取器:</h3>";
if (file_exists('inc/excel_reader.php')) {
    echo "<p>开始包含Excel读取器...</p>";
    try {
        include 'inc/excel_reader.php';
        echo "<p style='color: green;'>Excel读取器包含成功</p>";
        
        if (class_exists('ExcelReader')) {
            echo "<p style='color: green;'>ExcelReader类定义成功</p>";
            
            try {
                $reader = new ExcelReader();
                echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>ExcelReader实例创建异常: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>ExcelReader类未定义</p>";
        }
    } catch (Error $e) {
        echo "<p style='color: red;'>包含Excel读取器时出错: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<h3>4. 检查配置文件:</h3>";
if (file_exists('inc/conn.php')) {
    include 'inc/conn.php';
    echo "<p>配置文件包含成功</p>";
    echo "<p>数据目录: $UpDir</p>";
    echo "<p>查询条件: $tiaojian1</p>";
} else {
    echo "<p style='color: red;'>配置文件不存在</p>";
}

echo "<br><a href='index.php'>尝试访问index.php</a> | <a href='admin.php'>管理后台</a>";

?>