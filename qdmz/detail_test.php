<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>详细诊断页面</h2>";

echo "<h3>1. 当前工作目录:</h3>";
echo "<p>" . getcwd() . "</p>";

echo "<h3>2. 检查vendor/autoload.php:</h3>";
$autoload_path = __DIR__ . '/vendor/autoload.php';
echo "<p>绝对路径: $autoload_path</p>";
echo "<p>文件存在: " . (file_exists($autoload_path) ? '是' : '否') . "</p>";

echo "<h3>3. 尝试引入autoload.php:</h3>";
if (file_exists($autoload_path)) {
    echo "<p>引入前的类列表数量: " . count(get_declared_classes()) . "</p>";
    require_once $autoload_path;
    echo "<p>引入后检查是否成功</p>";
} else {
    echo "<p style='color: red'>autoload.php 文件不存在</p>";
    exit;
}

echo "<h3>4. 检查关键类是否存在:</h3>";
$classes_to_check = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls'
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "<p style='color: green'>$class 存在</p>";
    } else {
        echo "<p style='color: red'>$class 不存在</p>";
    }
}

echo "<h3>5. 尝试创建IOFactory类:</h3>";
try {
    if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        echo "<p>IOFactory类存在，尝试获取方法列表...</p>";
        $methods = get_class_methods('PhpOffice\\PhpSpreadsheet\\IOFactory');
        echo "<p>IOFactory方法数量: " . count($methods) . "</p>";
        echo "<p>部分方法: " . implode(', ', array_slice($methods, 0, 5)) . "</p>";
    } else {
        echo "<p style='color: red'>IOFactory类不存在</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>创建IOFactory失败: " . $e->getMessage() . "</p>";
}

echo "<h3>6. 尝试创建Spreadsheet对象:</h3>";
try {
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        echo "<p style='color: green'>Spreadsheet对象创建成功</p>";
    } else {
        echo "<p style='color: red'>Spreadsheet类不存在</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>创建失败: " . $e->getMessage() . "</p>";
}

echo "<h3>7. 检查所有已声明的类（包含PhpOffice的）:</h3>";
$all_classes = get_declared_classes();
$phpoffice_classes = [];
foreach ($all_classes as $class) {
    if (strpos($class, 'PhpOffice') !== false) {
        $phpoffice_classes[] = $class;
    }
}
echo "<p>找到 " . count($phpoffice_classes) . " 个PhpOffice相关类</p>";
if (count($phpoffice_classes) > 0) {
    echo "<ul>";
    foreach ($phpoffice_classes as $class) {
        echo "<li>$class</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange'>没有找到PhpOffice相关类</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='path_test.php'>返回路径测试</a>";

?>