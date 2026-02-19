<?php
// 检查数据文件
header('Content-Type: text/html; charset=utf-8');

echo "<h2>文件检查页面</h2>";

include 'inc/conn.php';

echo "<h3>配置信息:</h3>";
echo "<p>数据目录: $UpDir</p>";
echo "<p>查询条件: $tiaojian1</p>";

echo "<h3>目录文件列表:</h3>";

if (is_dir($UpDir)) {
    echo "<p>目录 $UpDir 存在</p>";
    $files = scandir($UpDir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['xls', 'xlsx'])) {
                echo "<li style='color: green;'>$file (大小: " . filesize($UpDir.'/'.$file) . " 字节)</li>";
            } else {
                echo "<li>$file</li>";
            }
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>目录 $UpDir 不存在</p>";
    if (mkdir($UpDir, 0755, true)) {
        echo "<p>目录已创建</p>";
    } else {
        echo "<p style='color: red;'>无法创建目录</p>";
    }
}

echo "<h3>测试Excel读取器:</h3>";

include 'inc/excel_reader.php';

if (class_exists('ExcelReader')) {
    echo "<p>ExcelReader类已定义</p>";
    
    // 尝试读取目录中的第一个Excel文件
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['xls', 'xlsx'])) {
            $test_file = $UpDir . '/' . $file;
            echo "<p>尝试读取文件: $test_file</p>";
            
            try {
                $reader = new ExcelReader();
                $reader->setOutputEncoding('UTF-8');
                $reader->read($test_file);
                
                if (isset($reader->sheets[0]['numRows'])) {
                    echo "<p style='color: green;'>成功读取文件，行数: " . $reader->sheets[0]['numRows'] . "，列数: " . $reader->sheets[0]['numCols'] . "</p>";
                } else {
                    echo "<p style='color: orange;'>读取了文件，但没有获取到行列信息</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>读取文件失败: " . $e->getMessage() . "</p>";
            }
            break; // 只测试第一个文件
        }
    }
} else {
    echo "<p style='color: red;'>ExcelReader类未定义</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='admin.php'>管理后台</a>";

?>