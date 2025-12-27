<?php
// 测试PhpSpreadsheet库是否可以正常加载
header('Content-Type: text/html; charset=utf-8');

// 尝试引入自动加载器
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    echo "<h2>自动加载器加载成功！</h2>";
} else {
    echo "<h2>错误：找不到自动加载器 (vendor/autoload.php)</h2>";
    exit;
}

// 尝试创建PhpSpreadsheet对象
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "<p>PhpSpreadsheet 对象创建成功！</p>";
    
    // 尝试访问一些基本功能
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', '测试PhpSpreadsheet');
    echo "<p>基本功能测试成功！</p>";
    
    echo "<h3>PhpSpreadsheet库安装成功！</h3>";
    echo "<p>您的系统现在可以使用PhpSpreadsheet库来处理XLSX文件了。</p>";
} catch (Exception $e) {
    echo "<h3>错误：无法创建PhpSpreadsheet对象</h3>";
    echo "<p>错误信息：" . $e->getMessage() . "</p>";
}

echo "<br><a href='javascript:history.back()'>返回</a>";
?>