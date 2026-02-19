<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>PhpSpreadsheet库完整性检查</h2>";

echo "<h3>1. 检查vendor目录结构:</h3>";
$vendorDir = 'vendor/phpoffice/phpspreadsheet';
if (is_dir($vendorDir)) {
    echo "<p style='color: green;'>✓ vendor/phpoffice/phpspreadsheet 目录存在</p>";
    
    // 递归列出目录结构
    echo "<p>目录结构:</p><pre>";
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendorDir));
    $files = array();
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace($vendorDir . '/', '', $file->getPathname());
            $files[] = $relativePath;
        }
    }
    
    sort($files);
    foreach ($files as $file) {
        echo "  $file\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>✗ vendor/phpoffice/phpspreadsheet 目录不存在</p>";
    
    // 检查是否有zip文件
    $zipFiles = glob('phpspreadsheet*.zip');
    if (!empty($zipFiles)) {
        echo "<p>发现zip文件: " . implode(', ', $zipFiles) . "</p>";
        echo "<p>需要解压到 vendor/phpoffice/phpspreadsheet 目录</p>";
    }
}

echo "<h3>2. 检查关键文件:</h3>";
$requiredFiles = [
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file 存在</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 不存在</p>";
    }
}

echo "<h3>3. 检查autoload.php:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>✓ vendor/autoload.php 存在</p>";
    
    // 尝试包含autoload.php
    require_once 'vendor/autoload.php';
    
    echo "<h3>4. 检查类是否可用:</h3>";
    $testClasses = [
        'PhpOffice\\PhpSpreadsheet\\IOFactory',
        'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
        'PhpOffice\\PhpSpreadsheet\\Reader\\Xls',
        'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
        'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
    ];
    
    foreach ($testClasses as $class) {
        $available = class_exists($class, true); // 第二个参数强制自动加载
        echo "<p>$class: " . ($available ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ vendor/autoload.php 不存在</p>";
}

echo "<h3>5. 检查zip文件:</h3>";
$zipFiles = glob('phpspreadsheet*.zip');
if (!empty($zipFiles)) {
    echo "<p>发现以下zip文件:</p>";
    foreach ($zipFiles as $zipFile) {
        $size = filesize($zipFile);
        echo "<p>  - $zipFile (" . number_format($size) . " 字节)</p>";
    }
    
    echo "<p>需要解压到 vendor/phpoffice/phpspreadsheet 目录</p>";
    echo "<p>解压命令示例: unzip $zipFiles[0] -d temp_dir/</p>";
    echo "<p>然后将 src 目录内容复制到 vendor/phpoffice/phpspreadsheet/</p>";
} else {
    echo "<p>未发现PhpSpreadsheet的zip文件</p>";
}

echo "<br><a href='index.php'>首页</a> | <a href='admin.php'>管理后台</a> | <a href='final_test.php'>最终测试</a>";

?>