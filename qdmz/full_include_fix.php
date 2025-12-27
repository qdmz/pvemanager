<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>完整包含修复</h2>";

echo "<h3>按依赖顺序包含PhpSpreadsheet核心文件:</h3>";

// 按依赖顺序包含文件
$coreFiles = [
    // 基础类
    'vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    
    // 共享类
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Font.php',
    
    // 样式相关
    'vendor/phpoffice/phpspreadsheet/src/Style/Color.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Border.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Alignment.php',
    'vendor/phpoffice/phpspreadsheet/src/Style/Style.php',
    
    // 工作表相关
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
    
    // 读取器接口和基类
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/XlsBase.php', // 之前发现这个文件存在
    
    // 读取器实现
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    
    // 主要类
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
];

$successCount = 0;
$failCount = 0;

foreach ($coreFiles as $file) {
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "<p style='color: green;'>✓ $file</p>";
            $successCount++;
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ $file - " . $e->getMessage() . "</p>";
            $failCount++;
        } catch (Error $e) {
            echo "<p style='color: red;'>✗ $file - " . $e->getMessage() . "</p>";
            $failCount++;
        }
    } else {
        echo "<p style='color: orange;'>→ $file (不存在)</p>";
    }
}

echo "<p><strong>包含结果: $successCount 个成功, $failCount 个失败</strong></p>";

echo "<h3>检查关键类可用性:</h3>";
$testClasses = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls',
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate',
];

foreach ($testClasses as $class) {
    $available = class_exists($class, false); // 不强制自动加载
    echo "<p>$class: " . ($available ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
}

echo "<h3>尝试创建读取器实例:</h3>";
if (class_exists('PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx', false)) {
    try {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        echo "<p style='color: green;'>✓ Xlsx读取器实例创建成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Xlsx读取器实例创建失败: " . $e->getMessage() . "</p>";
    } catch (Error $e) {
        echo "<p style='color: red;'>✗ Xlsx读取器实例创建错误: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Xlsx类不可用</p>";
}

if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory', false)) {
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        echo "<p style='color: green;'>✓ IOFactory创建Xlsx读取器成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ IOFactory创建Xlsx读取器失败: " . $e->getMessage() . "</p>";
    } catch (Error $e) {
        echo "<p style='color: red;'>✗ IOFactory创建Xlsx读取器错误: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ IOFactory类不可用</p>";
}

// 更新Excel读取器，使用完整包含
echo "<h3>更新Excel读取器使用完整包含:</h3>";

$excelReaderContent = '<?php
// 完整依赖版Excel读取器 - 包含所有必要的依赖

// 按依赖顺序包含PhpSpreadsheet核心文件
$coreFiles = [
    // 基础类
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php\',
    
    // 共享类
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Shared/File.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Shared/Date.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Shared/Font.php\',
    
    // 工作表相关
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php\',
    
    // 读取器接口和基类
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Reader/XlsBase.php\',
    
    // 读取器实现
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php\',
    
    // 主要类
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php\',
    __DIR__ . \'/../vendor/phpoffice/phpspreadsheet/src/IOFactory.php\',
];

foreach ($coreFiles as $file) {
    if (file_exists($file) && !class_exists(\'\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory\', false)) {
        require_once $file;
    }
}

// 包含旧的Excel读取器
if (!class_exists(\'Spreadsheet_Excel_Reader\')) {
    if (file_exists(__DIR__ . \'/excel.php\')) {
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
        
        // 根据文件扩展名和可用性选择读取方法
        if ($ext === \'xlsx\' && class_exists(\'\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory\')) {
            // 使用PhpSpreadsheet读取XLSX文件
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(\'Xlsx\');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filename);
                
                $worksheet = $spreadsheet->getActiveSheet();
                $this->numRows = $worksheet->getHighestRow();
                $this->numCols = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                
                // 初始化数据结构
                $this->sheets[0][\'numRows\'] = $this->numRows;
                $this->sheets[0][\'numCols\'] = $this->numCols;
                $this->sheets[0][\'cells\'] = array();
                
                // 读取数据
                for ($row = 1; $row <= $this->numRows; $row++) {
                    for ($col = 1; $col <= $this->numCols; $col++) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                        $this->sheets[0][\'cells\'][$row][$col] = (string)$cellValue;
                    }
                }
            } catch (Exception $e) {
                // 如果XLSX读取失败，尝试使用旧方法
                $this->readWithOldMethod($filename);
            }
        } else {
            // 使用旧方法读取XLS文件或作为回退
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
echo "<p style='color: green;'>✓ Excel读取器已更新为完整依赖版本</p>";

echo "<br><a href='simple_xlsx_test.php'>简单XLSX测试</a> | <a href='index.php'>首页</a> | <a href='admin.php'>管理后台</a>";

?>