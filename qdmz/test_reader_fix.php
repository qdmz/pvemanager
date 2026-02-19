<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>修复后的Excel读取器测试</h2>";

echo "<h3>1. 检查Excel读取器:</h3>";
if (file_exists('inc/excel_reader.php')) {
    echo "<p>Excel读取器文件存在</p>";
    include 'inc/excel_reader.php';
    echo "<p>Excel读取器包含成功</p>";
    
    if (class_exists('ExcelReader')) {
        echo "<p style='color: green;'>ExcelReader类已定义</p>";
    } else {
        echo "<p style='color: red;'>ExcelReader类未定义</p>";
    }
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<h3>2. 检查vendor/autoload.php:</h3>";
$autoloadPaths = [
    'vendor/autoload.php',
    './vendor/autoload.php',
    '../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];

foreach ($autoloadPaths as $path) {
    $exists = false;
    $resolvedPath = $path;
    
    if (strpos($path, __DIR__) === 0 || strpos($path, dirname(__DIR__)) === 0) {
        // 绝对路径
        $exists = file_exists($path);
    } else {
        // 相对路径
        $exists = file_exists($path);
        $resolvedPath = realpath($path) ?: $path;
    }
    
    echo "<p>" . ($exists ? "<span style='color: green'>路径 $path 存在</span>" : "<span style='color: red'>路径 $path 不存在</span>") . "</p>";
}

echo "<h3>3. 尝试创建ExcelReader实例:</h3>";
try {
    if (class_exists('ExcelReader')) {
        $reader = new ExcelReader();
        echo "<p style='color: green;'>ExcelReader实例创建成功</p>";
        
        // 检查数据目录
        $dataDir = 'shujukufangzheli';
        if (is_dir($dataDir)) {
            echo "<p>数据目录 $dataDir 存在</p>";
            
            $files = scandir($dataDir);
            $excelFiles = [];
            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['xls', 'xlsx'])) {
                    $excelFiles[] = $file;
                }
            }
            
            if (!empty($excelFiles)) {
                echo "<p>找到Excel文件: " . implode(', ', $excelFiles) . "</p>";
                
                // 尝试读取第一个文件
                $firstFile = $excelFiles[0];
                $filePath = $dataDir . '/' . $firstFile;
                echo "<p>尝试读取文件: $filePath</p>";
                
                try {
                    $reader->read($filePath);
                    if (isset($reader->sheets[0]['numRows']) && isset($reader->sheets[0]['numCols'])) {
                        echo "<p style='color: green;'>文件读取成功！行数: " . $reader->sheets[0]['numRows'] . ", 列数: " . $reader->sheets[0]['numCols'] . "</p>";
                    } else {
                        echo "<p style='color: orange;'>文件读取完成，但未获取到行列信息</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>读取文件失败: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p style='color: orange;'>数据目录中没有Excel文件</p>";
            }
        } else {
            echo "<p style='color: red;'>数据目录 $dataDir 不存在</p>";
        }
    } else {
        echo "<p style='color: red;'>无法创建ExcelReader实例，类不存在</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>创建实例失败: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='path_debug.php'>路径调试</a>";

?>