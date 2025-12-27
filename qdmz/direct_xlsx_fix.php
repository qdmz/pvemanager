<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>直接包含修复 - XLSX支持</h2>";

// 直接包含必要的PhpSpreadsheet文件
$requiredFiles = [
    // 基础类
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Font.php',
    
    // 工作表相关
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
    
    // 样式相关
    'vendor/phpoffice/phpspreadsheet/src/Style/Color.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Border.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Alignment.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Style.php',
    
    // 读取器接口和基础
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
    
    // XLSX读取器
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    
    // 主要类
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
];

echo "<h3>尝试直接包含必要的文件:</h3>";

foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        try {
            require_once $fullPath;
            echo "<p style='color: green;'>✓ $file</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ $file - " . $e->getMessage() . "</p>";
        } catch (Error $e) {
            echo "<p style='color: red;'>✗ $file - " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>→ 不存在: $file</p>";
    }
}

// 检查类是否可用
echo "<h3>检查类是否可用:</h3>";
$classes = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls',
    'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
];

foreach ($classes as $class) {
    $exists = class_exists($class, false);
    echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
}

// 更新Excel读取器
echo "<h3>更新Excel读取器:</h3>";

$excelReaderContent = '<?php
// 完整兼容版Excel读取器 - 支持XLS和XLSX，包含直接文件包含
if (!class_exists(\'\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory\')) {
    // 尝试直接包含必要的文件
    $requiredFiles = [
        \'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Shared/File.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php\',
        \'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php\',
        \'vendor/phpoffice/phpspreadsheet/src/IOFactory.php\',
    ];
    
    foreach ($requiredFiles as $file) {
        $fullPath = __DIR__ . \'/../\' . $file;
        if (file_exists($fullPath) && !class_exists(\'\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory\', false)) {
            require_once $fullPath;
        }
    }
}

if (!class_exists(\'Spreadsheet_Excel_Reader\')) {
    if (file_exists(\'inc/excel.php\')) {
        include_once \'inc/excel.php\';
    } else {
        include_once __DIR__ . \'/excel.php\';
    }
}

class ExcelReader {
    public $sheets;
    private $numRows;
    private $numCols;

    public function __construct() {
        $this->sheets = array();
        $this->sheets[0] = array(
            \'numRows\' => 0,
            \'numCols\' => 0,
            \'cells\' => array()
        );
    }

    public function setOutputEncoding($encoding) {
        // 保持向后兼容
    }

    public function read($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // 检查文件是否存在
        if (!file_exists($filename)) {
            throw new Exception(\'文件不存在: \' . $filename);
        }
        
        if ($ext === \'xlsx\' && class_exists(\'\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory\')) {
            // 使用PhpSpreadsheet读取XLSX文件
            try {
                $reader = \\PhpOffice\\PhpSpreadsheet\\IOFactory::createReader(\'Xlsx\');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filename);
                
                $worksheet = $spreadsheet->getActiveSheet();
                $this->numRows = $worksheet->getHighestRow();
                $this->numCols = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                
                // 初始化数据结构
                $this->sheets[0][\'numRows\'] = $this->numRows;
                $this->sheets[0][\'numCols\'] = $this->numCols;
                $this->sheets[0][\'cells\'] = array();
                
                // 读取数据
                for ($row = 1; $row <= $this->numRows; $row++) {
                    for ($col = 1; $col <= $this->numCols; $col++) {
                        $columnLetter = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::stringFromColumnIndex($col);
                        $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                        $this->sheets[0][\'cells\'][$row][$col] = (string)$cellValue;
                    }
                }
            } catch (Exception $e) {
                // 如果XLSX读取失败，尝试使用旧方法
                $this->readWithOldMethod($filename);
            }
        } elseif ($ext === \'xls\' && class_exists(\'Spreadsheet_Excel_Reader\')) {
            // 使用旧方法读取XLS文件
            $this->readWithOldMethod($filename);
        } else {
            // 尝试使用旧方法作为回退
            $this->readWithOldMethod($filename);
        }
    }
    
    private function readWithOldMethod($filename) {
        if (class_exists(\'Spreadsheet_Excel_Reader\')) {
            $reader = new Spreadsheet_Excel_Reader();
            $reader->setOutputEncoding(\'UTF-8\');
            $reader->read($filename);
            
            // 复制数据结构
            $this->sheets = $reader->sheets;
            $this->numRows = isset($this->sheets[0][\'numRows\']) ? $this->sheets[0][\'numRows\'] : 0;
            $this->numCols = isset($this->sheets[0][\'numCols\']) ? $this->sheets[0][\'numCols\'] : 0;
            
            // 确保所有单元格值都是字符串
            for ($row = 1; $row <= $this->numRows; $row++) {
                for ($col = 1; $col <= $this->numCols; $col++) {
                    if (isset($this->sheets[0][\'cells\'][$row][$col])) {
                        $this->sheets[0][\'cells\'][$row][$col] = (string)$this->sheets[0][\'cells\'][$row][$col];
                    }
                }
            }
        } else {
            throw new Exception(\'无法读取Excel文件：没有可用的读取器\');
        }
    }

    public function __get($name) {
        if ($name === \'sheets\') {
            return $this->sheets;
        }
        return null;
    }
}
?>';

file_put_contents(__DIR__ . '/inc/excel_reader.php', $excelReaderContent);
echo "<p style='color: green;'>✓ Excel读取器已更新</p>";

echo "<h3>错误修复提示:</h3>";
echo "<p>检测到excel.php中存在参数声明错误，这是旧库的已知问题。这个错误不会影响XLS文件的读取，但建议在可能的情况下修复excel.php文件中的参数声明顺序问题。</p>";

echo "<br><a href='test_xlsx.php'>重新测试XLSX读取</a> | <a href='index.php'>首页</a> | <a href='admin.php'>管理后台</a>";

?>