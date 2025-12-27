<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>最终自动加载器修复</h2>";

// 创建一个更精确的自动加载器
$autoloaderContent = '<?php
// 针对PhpSpreadsheet的精确自动加载器

// 注册自动加载函数
spl_autoload_register(function ($class) {
    // 只处理PhpOffice\PhpSpreadsheet命名空间
    if (strpos($class, "PhpOffice\\\\PhpSpreadsheet") !== 0) {
        return false;
    }
    
    // 将命名空间转换为文件路径
    $relativePath = str_replace("PhpOffice\\\\PhpSpreadsheet\\\\", "", $class);
    $relativePath = str_replace("\\\\", "/", $relativePath) . ".php";
    
    // 构建文件路径
    $filePath = __DIR__ . "/vendor/phpoffice/phpspreadsheet/src/" . $relativePath;
    
    // 检查文件是否存在
    if (file_exists($filePath)) {
        require_once $filePath;
        return true;
    }
    
    // 如果标准路径不存在，尝试常见子目录
    $subdirs = ["Shared/", "Cell/", "Reader/", "Writer/", "Calculation/", "Style/", "RichText/"];
    foreach ($subdirs as $subdir) {
        $testPath = __DIR__ . "/vendor/phpoffice/phpspreadsheet/src/" . $subdir . str_replace($subdir, "", $relativePath) . ".php";
        if (file_exists($testPath)) {
            require_once $testPath;
            return true;
        }
    }
    
    // 特殊处理某些类
    if ($class === "PhpOffice\\\\PhpSpreadsheet\\\\Shared\\\\File") {
        $specialPath = __DIR__ . "/vendor/phpoffice/phpspreadsheet/src/Shared/File.php";
        if (file_exists($specialPath)) {
            require_once $specialPath;
            return true;
        }
    }
    
    if ($class === "PhpOffice\\\\PhpSpreadsheet\\\\Cell\\\\Coordinate") {
        $specialPath = __DIR__ . "/vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php";
        if (file_exists($specialPath)) {
            require_once $specialPath;
            return true;
        }
    }
    
    return false;
});

// 同时包含原有的自动加载器
$existingAutoloader = __DIR__ . "/vendor/autoload.php";
if (file_exists($existingAutoloader)) {
    require_once $existingAutoloader;
}
';

// 写入最终的自动加载器
$result = file_put_contents(__DIR__ . '/vendor/final_autoloader.php', $autoloaderContent);

if ($result !== false) {
    echo "<p style='color: green;'>✓ 最终自动加载器创建成功</p>";
    
    // 立即包含自动加载器
    require_once __DIR__ . '/vendor/final_autoloader.php';
    
    // 测试关键类
    $classesToTest = [
        'PhpOffice\\PhpSpreadsheet\\IOFactory',
        'PhpOffice\\PhpSpreadsheet\\Shared\\File',
        'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
        'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
        'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
        'PhpOffice\\PhpSpreadsheet\\Reader\\Xls'
    ];
    
    echo "<h3>测试类加载 (使用最终自动加载器):</h3>";
    foreach ($classesToTest as $class) {
        $exists = class_exists($class, true); // 强制加载
        echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
    }
    
    echo "<h3>更新Excel读取器使用新自动加载器:</h3>";
    
    // 更新Excel读取器，强制包含自动加载器
    $excelReaderContent = file_get_contents(__DIR__ . '/inc/excel_reader.php');
    
    // 检查是否已经包含自动加载器引用
    if (strpos($excelReaderContent, 'vendor/final_autoloader.php') === false) {
        $excelReaderContent = str_replace(
            'require_once $autoload_path;',
            "require_once \$autoload_path;\nrequire_once dirname(__DIR__) . '/vendor/final_autoloader.php';",
            $excelReaderContent
        );
        
        // 如果没有找到替换点，就在文件开头添加
        if (strpos($excelReaderContent, 'vendor/final_autoloader.php') === false) {
            $excelReaderContent = str_replace(
                '<?php',
                "<?php\n// 强制包含最终自动加载器\nrequire_once dirname(__DIR__) . '/vendor/final_autoloader.php';",
                $excelReaderContent
            );
        }
        
        file_put_contents(__DIR__ . '/inc/excel_reader.php', $excelReaderContent);
        echo "<p style='color: green;'>✓ Excel读取器已更新以使用最终自动加载器</p>";
    } else {
        echo "<p style='color: green;'>✓ Excel读取器已使用最终自动加载器</p>";
    }
} else {
    echo "<p style='color: red;'>✗ 最终自动加载器创建失败</p>";
}

echo "<br><a href='check_library.php'>重新检查库</a> | <a href='excel_debug.php'>Excel调试</a> | <a href='index.php'>首页</a>";

?>