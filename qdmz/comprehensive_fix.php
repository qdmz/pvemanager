<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>全面修复</h2>";

// 首先，尝试找出XlsBase类在哪里
echo "<h3>查找XlsBase类文件:</h3>";
$srcDir = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
$xlsRelatedFiles = [];

foreach ($files as $file) {
    if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($file->getPathname());
        if (preg_match('/class\s+XlsBase|extends\s+XlsBase|XlsBase/i', $content)) {
            $xlsRelatedFiles[] = $file->getPathname();
            echo "<p>可能包含XlsBase: " . str_replace(__DIR__, '', $file->getPathname()) . "</p>";
        }
    }
}

if (empty($xlsRelatedFiles)) {
    echo "<p>未找到包含XlsBase的文件</p>";
    
    // 搜索所有可能与XLS相关的文件
    $searchPattern = __DIR__ . '/vendor/phpoffice/phpspreadsheet/src/Reader/*ls*.php';
    $xlsFiles = glob($searchPattern);
    
    if (!empty($xlsFiles)) {
        echo "<p>找到XLS相关文件:</p>";
        foreach ($xlsFiles as $file) {
            echo "<p>" . str_replace(__DIR__, '', $file) . "</p>";
        }
    } else {
        echo "<p>未找到XLS相关文件</p>";
    }
}

// 包含所有可能需要的文件
$allRequiredFiles = [
    // 基础类
    'vendor/phpoffice/phpspreadsheet/src/Shared/StringHelper.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Date.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/Font.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/PasswordHasher.php',
    
    // 单元格相关
    'vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
    'vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php',
    
    // 共享类
    'vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/OLE.php',
    'vendor/phpoffice/phpspreadsheet/src/Shared/OLERead.php',
    
    // 工作表相关
    'vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
    
    // 读取器接口
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/IReadFilter.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/DefaultReadFilter.php',
    
    // 读取器基类（查找XlsBase类）
    'vendor/phpoffice/phpspreadsheet/src/Reader/XlsBase.php', // 尝试这个文件
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls\\XlsBase.php', // 或者这个
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls\\Base.php',
    
    // 其他读取器基类
    'vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
    
    // 读取器实现
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
    'vendor/phpoffice/phpspreadsheet/src/Reader/Xls.php',
    
    // 主要类
    'vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
    'vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
];

echo "<h3>尝试包含所有必需文件:</h3>";

$includedCount = 0;
$failedCount = 0;

foreach ($allRequiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        try {
            require_once $fullPath;
            echo "<p style='color: green;'>✓ $file</p>";
            $includedCount++;
        } catch (Error $e) {
            echo "<p style='color: red;'>✗ $file - " . $e->getMessage() . "</p>";
            $failedCount++;
        } catch (Exception $e) {
            echo "<p style='color: orange;'>→ $file - " . $e->getMessage() . "</p>";
            $failedCount++;
        }
    } else {
        // 尝试其他可能的路径
        $altPaths = [
            str_replace('XlsBase.php', 'Base.php', $fullPath),
            str_replace('Xls\\XlsBase.php', 'Base.php', $fullPath),
            str_replace('XlsBase.php', 'XlsBase.php', str_replace('/Reader/', '/Reader/Xls/', dirname($fullPath) . '/')) // 尝试子目录
        ];
        
        $found = false;
        foreach ($altPaths as $altPath) {
            if (file_exists($altPath)) {
                try {
                    require_once $altPath;
                    echo "<p style='color: green;'>✓ $altPath</p>";
                    $includedCount++;
                    $found = true;
                    break;
                } catch (Error $e) {
                    echo "<p style='color: red;'>✗ $altPath - " . $e->getMessage() . "</p>";
                }
            }
        }
        
        if (!$found) {
            echo "<p style='color: gray;'>→ 不存在: $file</p>";
        }
    }
}

echo "<p>成功包含: $includedCount 个文件，失败: $failedCount 个文件</p>";

// 检查关键类是否可用
echo "<h3>检查关键类可用性:</h3>";
$classes = [
    'PhpOffice\\PhpSpreadsheet\\IOFactory',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Reader\\Xls',
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate'
];

foreach ($classes as $class) {
    $exists = class_exists($class, false);
    echo "<p>$class: " . ($exists ? "<span style='color: green;'>✓ 可用</span>" : "<span style='color: red;'>✗ 不可用</span>") . "</p>";
}

// 由于Xls类可能仍然不可用，我们将使用一种替代方法
echo "<h3>更新Excel读取器使用替代方案:</h3>";

// 创建一个简化的Excel读取器，专注于实际可用的功能
$excelReaderContent = '<?php
// 兼容模式Excel读取器
class ExcelReader {
    private $objPHPExcel;
    private $sheet;
    private $numRows;
    private $numCols;
    private $cells;
    private $sheets;

    public function __construct() {
        $this->init_properties();
    }
    
    private function init_properties() {
        $this->cells = array();
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
        
        // 优先尝试使用旧的Spreadsheet_Excel_Reader
        if (file_exists(\'excel.php\')) {
            include_once \'excel.php\';
        }
        
        if (class_exists(\'Spreadsheet_Excel_Reader\')) {
            $this->readWithOldMethod($filename);
            return;
        }
        
        // 如果旧方法不可用，尝试PhpSpreadsheet（如果可用）
        if ($ext === \'xlsx\' && class_exists(\'\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory\')) {
            try {
                $reader = \\PhpOffice\\PhpSpreadsheet\\IOFactory::createReader(\'Xlsx\');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filename);
                
                $worksheet = $spreadsheet->getActiveSheet();
                $this->numRows = $worksheet->getHighestRow();
                $this->numCols = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                
                for ($row = 1; $row <= $this->numRows; $row++) {
                    for ($col = 1; $col <= $this->numCols; $col++) {
                        $columnLetter = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::stringFromColumnIndex($col);
                        $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                        $this->sheets[0][\'cells\'][$row][$col] = (string)$cellValue;
                    }
                }
                
                $this->sheets[0][\'numRows\'] = $this->numRows;
                $this->sheets[0][\'numCols\'] = $this->numCols;
                
                return;
            } catch (Exception $e) {
                // 如果新方法失败，继续尝试旧方法
            }
        }
        
        // 如果所有方法都失败，尝试旧的Spreadsheet_Excel_Reader
        $this->readWithOldMethod($filename);
    }
    
    private function readWithOldMethod($filename) {
        // 确保旧类可用
        if (!class_exists(\'Spreadsheet_Excel_Reader\')) {
            if (file_exists(\'excel.php\')) {
                include_once \'excel.php\';
            }
        }
        
        if (class_exists(\'Spreadsheet_Excel_Reader\')) {
            $reader = new Spreadsheet_Excel_Reader();
            $reader->setOutputEncoding(\'UTF-8\');
            $reader->read($filename);
            
            // 直接复制数据结构以确保兼容性
            $this->sheets = $reader->sheets;
            $this->numRows = $this->sheets[0][\'numRows\'];
            $this->numCols = $this->sheets[0][\'numCols\'];
            
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
';

file_put_contents(__DIR__ . '/inc/excel_reader.php', $excelReaderContent);
echo "<p style='color: green;'>✓ Excel读取器已更新为兼容模式</p>";

echo "<br><a href='index.php'>返回首页</a> | <a href='debug_data_structure.php'>调试数据结构</a>";

?>