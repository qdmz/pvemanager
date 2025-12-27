<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>自动加载器测试页面</h2>";

echo "<h3>1. 测试路径构造:</h3>";
$className = 'PhpOffice\\PhpSpreadsheet\\Spreadsheet';
$basePath = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/';
$relativePath = substr($className, strlen('PhpOffice\\PhpSpreadsheet\\'));
$constructedPath = $basePath . str_replace('\\', '/', $relativePath) . '.php';

echo "<p>类名: $className</p>";
echo "<p>相对路径: $relativePath</p>";
echo "<p>构造路径: $constructedPath</p>";
echo "<p>文件存在: " . (file_exists($constructedPath) ? '是' : '否') . "</p>";

echo "<h3>2. 测试自动加载器:</h3>";
$autoload_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
    echo "<p>自动加载器引入成功</p>";
} else {
    echo "<p style='color: red'>自动加载器文件不存在</p>";
    exit;
}

echo "<h3>3. 测试类是否存在:</h3>";
$classes = [
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx'
];

foreach ($classes as $class) {
    $exists = class_exists($class, true); // 第二个参数true表示强制自动加载
    echo "<p>" . ($exists ? "<span style='color: green'>$class 存在</span>" : "<span style='color: red'>$class 不存在</span>") . "</p>";
}

echo "<h3>4. 尝试创建对象:</h3>";
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "<p style='color: green'>Spreadsheet对象创建成功</p>";
    
    $reflection = new ReflectionClass($spreadsheet);
    echo "<p>类名确认: " . $reflection->getName() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red'>创建失败: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php'>返回首页</a> | <a href='detail_test.php'>返回详细测试</a>";

?>