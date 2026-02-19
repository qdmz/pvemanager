<?php
// 创建修复后的Excel读取器文件
$fixedContent = '<?php
// 兼容PHP 5.4+的新Excel读取类，支持XLS和XLSX格式

// 首先尝试使用PhpSpreadsheet，如果不可用则回退到旧方法
$autoload_path = dirname(__DIR__) . \'/vendor/autoload.php\'; // dirname(__DIR__) 获取上级目录，即项目根目录
if (!file_exists($autoload_path)) {
    $autoload_path = __DIR__ . \'/../vendor/autoload.php\'; // __DIR__是inc目录，../指向根目录
}
if (!file_exists($autoload_path)) {
    $autoload_path = dirname(dirname(__DIR__)) . \'/vendor/autoload.php\'; // 尝试向上两级
}
if (!file_exists($autoload_path)) {
    $autoload_path = dirname(dirname(dirname(__DIR__))) . \'/vendor/autoload.php\'; // 尝试向上三级
}

// 检查路径是否存在并包含自动加载器
if (file_exists($autoload_path)) {
    require_once $autoload_path;
    
    // 检查PhpOffice\\PhpSpreadsheet类是否可用
    if (class_exists(\'\\\\PhpOffice\\\\PhpSpreadsheet\\\\IOFactory\')) {
        // 定义一个使用PhpSpreadsheet的类
        class ExcelReader {
            private $objPHPExcel;
            private $sheet;
            private $numRows;
            private $numCols;
            private $cells;
            private $sheets;

            public function __construct() {
                $this->cells = array();
                $this->sheets = array();
                $this->sheets[0] = array(
                    \'numRows\' => 0,
                    \'numCols\' => 0,
                    \'cells\' => array()
                );
            }

            public function setOutputEncoding($encoding) {
                // 为保持向后兼容性而保留此方法
            }

            public function read($filename) {
                try {
                    $spreadsheet = \\PhpOffice\\PhpSpreadsheet\\IOFactory::load($filename);
                    $this->objPHPExcel = $spreadsheet;
                    $this->sheet = $spreadsheet->getActiveSheet();
                    
                    // 获取行列信息
                    $this->numRows = $this->sheet->getHighestRow();
                    $this->numCols = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::columnIndexFromString($this->sheet->getHighestColumn());
                    
                    // 读取所有单元格数据
                    for ($row = 1; $row <= $this->numRows; $row++) {
                        for ($col = 1; $col <= $this->numCols; $col++) {
                            $cellCoordinate = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::stringFromColumnIndex($col) . $row;
                            $cell = $this->sheet->getCell($cellCoordinate);
                            $value = $cell->getValue();
                            // 确保值是字符串类型
                            $this->sheets[0][\'cells\'][$row][$col] = $value !== null ? (string)$value : \'\';
                        }
                    }
                    
                    $this->sheets[0][\'numRows\'] = $this->numRows;
                    $this->sheets[0][\'numCols\'] = $this->numCols;
                    
                } catch (Exception $e) {
                    throw new Exception("无法读取Excel文件: " . $e->getMessage());
                }
            }
            
            public function __get($name) {
                if ($name === \'sheets\') {
                    return $this->sheets;
                }
                return null;
            }
        }
    } else {
        // 回退到旧的Spreadsheet_Excel_Reader类
        if (!class_exists(\'Spreadsheet_Excel_Reader\')) {
            include_once \'excel.php\';
        }
        
        // 重命名旧类以避免冲突
        if (class_exists(\'Spreadsheet_Excel_Reader\')) {
            class_alias(\'Spreadsheet_Excel_Reader\', \'ExcelReader\');
        }
    }
} else {
    // 回退到旧的Spreadsheet_Excel_Reader类
    if (!class_exists(\'Spreadsheet_Excel_Reader\')) {
        include_once \'excel.php\';
    }
    
    // 重命名旧类以避免冲突
    if (class_exists(\'Spreadsheet_Excel_Reader\')) {
        class_alias(\'Spreadsheet_Excel_Reader\', \'ExcelReader\');
    }
}
?>';

// 写入修复后的文件
$result = file_put_contents(__DIR__ . '/inc/excel_reader.php', $fixedContent);

if ($result !== false) {
    echo "<h2>Excel读取器文件修复成功!</h2>";
    echo "<p>已将修复后的代码写入 inc/excel_reader.php</p>";
    
    // 验证语法
    $output = shell_exec('php -l ' . escapeshellarg(__DIR__ . '/inc/excel_reader.php'));
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<p style='color: green;'>✓ 语法检查通过</p>";
    } else {
        echo "<p style='color: red;'>✗ 语法检查失败: " . htmlspecialchars($output) . "</p>";
    }
} else {
    echo "<h2>修复失败!</h2>";
    echo "<p>无法写入 inc/excel_reader.php 文件</p>";
}

echo "<br><a href='index.php'>尝试访问index.php</a> | <a href='validate_syntax.php'>重新验证语法</a>";

?>