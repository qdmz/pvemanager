<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>精确路径测试</h2>";

echo "<h3>当前环境信息:</h3>";
echo "<p>当前工作目录: " . getcwd() . "</p>";
echo "<p>当前文件: " . __FILE__ . "</p>";
echo "<p>__DIR__: " . __DIR__ . "</p>";

echo "<h3>Excel读取器路径测试:</h3>";
$excelReaderDir = __DIR__ . '/inc';
echo "<p>Excel读取器目录 (inc/): " . $excelReaderDir . "</p>";

if (file_exists($excelReaderDir . '/excel_reader.php')) {
    echo "<p>Excel读取器文件存在</p>";
    
    // 模拟Excel读取器中的路径查找逻辑
    $dir = $excelReaderDir; // 相当于Excel读取器中的__DIR__
    
    echo "<h4>Excel读取器中的路径查找:</h4>";
    echo "<p>__DIR__ (inc目录): " . $dir . "</p>";
    
    $paths_to_try = [
        $dir . '/../vendor/autoload.php',           // inc/../vendor/autoload.php = 根目录/vendor/autoload.php
        dirname($dir, 2) . '/vendor/autoload.php', // ../../vendor/autoload.php
        dirname(dirname($dir)) . '/vendor/autoload.php', // ../..//vendor/autoload.php (同上)
        dirname($dir) . '/vendor/autoload.php',     // ../vendor/autoload.php (同第一个)
    ];
    
    foreach ($paths_to_try as $i => $path) {
        echo "<p>路径 " . ($i+1) . ": $path " . (file_exists($path) ? "<span style='color: green;'>存在</span>" : "<span style='color: red;'>不存在</span>") . "</p>";
    }
    
    echo "<h4>当前实际路径:</h4>";
    $actualPath = __DIR__ . '/vendor/autoload.php';
    echo "<p>实际路径: $actualPath " . (file_exists($actualPath) ? "<span style='color: green;'>存在</span>" : "<span style='color: red;'>不存在</span>") . "</p>";
} else {
    echo "<p style='color: red;'>Excel读取器文件不存在</p>";
}

echo "<h3>服务器路径结构分析:</h3>";
echo "<p>根据您提供的调试信息:</p>";
echo "<p>当前文件路径: /home/ftp/h/hkep8383782782/wwwroot/qdmz/path_debug.php</p>";
echo "<p>所以项目根目录是: /home/ftp/h/hkep8383782782/wwwroot/qdmz/</p>";
echo "<p>Excel读取器在: /home/ftp/h/hkep8383782782/wwwroot/qdmz/inc/excel_reader.php</p>";
echo "<p>所以 __DIR__ 是: /home/ftp/h/hkep8383782782/wwwroot/qdmz/inc</p>";
echo "<p>__DIR__ . '/../vendor/autoload.php' 应该是: /home/ftp/h/hkep8383782782/wwwroot/qdmz/vendor/autoload.php</p>";
echo "<p>这个路径应该存在!</p>";

echo "<br><a href='path_debug.php'>返回路径调试</a> | <a href='test_fix.php'>测试修复</a>";

?>