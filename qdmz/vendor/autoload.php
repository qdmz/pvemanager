<?php
// 高级自动加载器，用于加载PhpSpreadsheet库
spl_autoload_register(function ($class) {
    // 检查类是否属于PhpOffice\PhpSpreadsheet命名空间
    if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        // 将命名空间转换为文件路径
        $relative_class = substr($class, strlen('PhpOffice\\PhpSpreadsheet\\'));
        $file = __DIR__ . '/phpoffice/phpspreadsheet/src/' . $relative_class . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
        
        // 如果上面的路径不存在，尝试在PhpSpreadsheet子目录中查找
        $file = __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/' . $relative_class . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});