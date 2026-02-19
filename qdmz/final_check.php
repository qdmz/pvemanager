<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>QDMZ 最终部署检查</h2>";

echo "<h3>1. 检查Excel读取器:</h3>";
if (file_exists('inc/excel_reader.php')) {
    echo "<p style='color: green;'>✓ inc/excel_reader.php 存在</p>";
    
    // 检查内容
    $content = file_get_contents('inc/excel_reader.php');
    if (strpos($content, 'Spreadsheet_Excel_Reader') !== false) {
        echo "<p style='color: green;'>✓ 包含Spreadsheet_Excel_Reader兼容代码</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 可能缺少Spreadsheet_Excel_Reader兼容代码</p>";
    }
} else {
    echo "<p style='color: red;'>✗ inc/excel_reader.php 不存在</p>";
}

echo "<h3>2. 检查Excel处理文件:</h3>";
if (file_exists('inc/excel.php')) {
    echo "<p style='color: green;'>✓ inc/excel.php 存在</p>";
} else {
    echo "<p style='color: red;'>✗ inc/excel.php 不存在</p>";
}

echo "<h3>3. 检查PhpSpreadsheet库:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>✓ vendor/autoload.php 存在</p>";
} else {
    echo "<p style='color: orange;'>⚠ vendor/autoload.php 不存在</p>";
}

echo "<h3>4. 检查配置文件:</h3>";
if (file_exists('inc/conn.php')) {
    echo "<p style='color: green;'>✓ inc/conn.php 存在</p>";
    
    $conn_content = file_get_contents('inc/conn.php');
    if (strpos($conn_content, '$UpDir') !== false) {
        preg_match('/\$UpDir\s*=\s*[\'"](.+?)[\'"]/', $conn_content, $matches);
        if (isset($matches[1])) {
            $uploadDir = $matches[1];
            echo "<p>上传目录: $uploadDir</p>";
            if (file_exists($uploadDir)) {
                echo "<p style='color: green;'>✓ 上传目录存在</p>";
            } else {
                echo "<p style='color: orange;'>⚠ 上传目录不存在</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>✗ inc/conn.php 不存在</p>";
}

echo "<h3>5. 检查Admin文件:</h3>";
if (file_exists('admin.php')) {
    echo "<p style='color: green;'>✓ admin.php 存在</p>";
    
    $admin_content = file_get_contents('admin.php');
    if (strpos($admin_content, '中文') !== false || strpos($admin_content, 'utf-8') !== false) {
        echo "<p style='color: green;'>✓ admin.php 包含中文支持代码</p>";
    } else {
        echo "<p style='color: orange;'>⚠ admin.php 可能缺少中文支持代码</p>";
    }
} else {
    echo "<p style='color: red;'>✗ admin.php 不存在</p>";
}

echo "<h3>6. 检查主页文件:</h3>";
if (file_exists('index.php')) {
    echo "<p style='color: green;'>✓ index.php 存在</p>";
} else {
    echo "<p style='color: red;'>✗ index.php 不存在</p>";
}

echo "<h3>7. 测试Excel读取功能:</h3>";
$testFile = 'shujukufangzheli/2025.xls'; // 使用您之前提到的测试文件
if (file_exists($testFile)) {
    echo "<p>测试文件存在: $testFile</p>";
    
    // 尝试包含Excel读取器并测试
    if (file_exists('inc/excel_reader.php')) {
        include_once 'inc/excel_reader.php';
        
        if (class_exists('ExcelReader')) {
            echo "<p style='color: green;'>✓ ExcelReader类可用</p>";
            
            try {
                $reader = new ExcelReader();
                $reader->read($testFile);
                echo "<p style='color: green;'>✓ Excel文件读取成功</p>";
                
                if (isset($reader->sheets) && isset($reader->sheets[0])) {
                    $numRows = $reader->sheets[0]['numRows'] ?? 0;
                    $numCols = $reader->sheets[0]['numCols'] ?? 0;
                    echo "<p>数据行数: $numRows, 列数: $numCols</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Excel文件读取失败: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ ExcelReader类不可用</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Excel读取器文件不存在</p>";
    }
} else {
    echo "<p>未找到测试文件 $testFile</p>";
    
    // 查找可用的Excel文件
    $uploadDir = 'shujukufangzheli';
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        $excelFiles = array_filter($files, function($file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, ['xls', 'xlsx']);
        });
        
        if (!empty($excelFiles)) {
            echo "<p>找到Excel文件:</p>";
            foreach ($excelFiles as $file) {
                echo "<p>  - $uploadDir/$file</p>";
            }
            
            $firstFile = $uploadDir . '/' . reset($excelFiles);
            echo "<p>尝试读取第一个文件: $firstFile</p>";
            
            if (file_exists('inc/excel_reader.php')) {
                include_once 'inc/excel_reader.php';
                
                if (class_exists('ExcelReader')) {
                    try {
                        $reader = new ExcelReader();
                        $reader->read($firstFile);
                        echo "<p style='color: green;'>✓ Excel文件读取成功</p>";
                        
                        if (isset($reader->sheets) && isset($reader->sheets[0])) {
                            $numRows = $reader->sheets[0]['numRows'] ?? 0;
                            $numCols = $reader->sheets[0]['numCols'] ?? 0;
                            echo "<p>数据行数: $numRows, 列数: $numCols</p>";
                        }
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>✗ Excel文件读取失败: " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ ExcelReader类不可用</p>";
                }
            }
        } else {
            echo "<p>上传目录中没有找到Excel文件</p>";
        }
    }
}

echo "<br><a href='index.php'>返回首页</a> | <a href='admin.php'>管理后台</a> | <a href='debug_data_structure.php'>调试工具</a>";

?>