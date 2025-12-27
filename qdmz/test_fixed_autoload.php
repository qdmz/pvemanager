<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>修复后的自动加载器测试</h2>";

echo "<h3>检查自动加载器文件:</h3>";
$autoload_path = __DIR__ . '/vendor/autoload.php';
echo "<p>路径: $autoload_path</p>";
echo "<p>文件存在: " . (file_exists($autoload_path) ? '是' : '否') . "</p>";

if (file_exists($autoload_path)) {
    echo "<h3>引入自动加载器:</h3>";
    require_once $autoload_path;
    echo "<p>引入成功</p>";
    
    echo "<h3>测试类加载:</h3>";
    
    // 测试各种PhpSpreadsheet类
    $testClasses = [
        'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
        'PhpOffice\\PhpSpreadsheet\\IOFactory',
        'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
        'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx'
    ];
    
    foreach ($testClasses as $class) {
        $exists = class_exists($class, true); // 强制自动加载
        echo "<p>" . ($exists ? "<span style='color: green'>$class 存在</span>" : "<span style='color: red'>$class 不存在</span>") . "</p>";
        
        if ($exists && strpos($class, 'Spreadsheet') !== false) {
            try {
                $obj = new $class();
                echo "<p style='color: green'>$class 对象创建成功</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange'>$class 对象创建失败: " . $e->getMessage() . "</p>";
            }
        }
    }
} else {
    echo "<p style='color: red'>自动加载器文件不存在</p>";
}

?>