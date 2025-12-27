<?php
// 终极兼容版Excel读取器 - 支持XLS/XLSX，包含中文文件名支持和完整回退机制

// 首先尝试包含旧的Excel读取器
if (!class_exists('Spreadsheet_Excel_Reader')) {
    if (file_exists('inc/excel.php')) {
        include_once 'inc/excel.php';
    } else {
        include_once __DIR__ . '/excel.php';
    }
}

// 尝试初始化PhpSpreadsheet（如果可用）
$phpSpreadsheetAvailable = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        $phpSpreadsheetAvailable = true;
    }
}

// 如果自动加载不可用，尝试直接包含必要的文件
if (!$phpSpreadsheetAvailable) {
    $requiredFiles = [
        '../vendor/phpoffice/phpspreadsheet/src/Cell/Coordinate.php',
        '../vendor/phpoffice/phpspreadsheet/src/Cell/DataType.php',
        '../vendor/phpoffice/phpspreadsheet/src/Shared/File.php',
        '../vendor/phpoffice/phpspreadsheet/src/Worksheet/Worksheet.php',
        '../vendor/phpoffice/phpspreadsheet/src/Reader/IReader.php',
        '../vendor/phpoffice/phpspreadsheet/src/Reader/BaseReader.php',
        '../vendor/phpoffice/phpspreadsheet/src/Reader/Xlsx.php',
        '../vendor/phpoffice/phpspreadsheet/src/Spreadsheet.php',
        '../vendor/phpoffice/phpspreadsheet/src/IOFactory.php',
    ];
    
    foreach ($requiredFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath) && !class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory', false)) {
            require_once $fullPath;
        }
    }
    
    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        $phpSpreadsheetAvailable = true;
    }
}

class ExcelReader {
    public $sheets;
    private $numRows;
    private $numCols;

    public function __construct() {
        $this->sheets = array();
        $this->sheets[0] = array(
            'numRows' => 0,
            'numCols' => 0,
            'cells' => array()
        );
    }

    public function setOutputEncoding($encoding) {
        // 保持向后兼容
    }

    public function read($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // 检查文件是否存在
        if (!file_exists($filename)) {
            throw new Exception('文件不存在: ' . $filename);
        }
        
        // 根据文件扩展名和可用性选择读取方法
        if ($ext === 'xlsx' && class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            // 使用PhpSpreadsheet读取XLSX文件
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filename);
                
                $worksheet = $spreadsheet->getActiveSheet();
                $this->numRows = $worksheet->getHighestRow();
                $this->numCols = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                
                // 初始化数据结构
                $this->sheets[0]['numRows'] = $this->numRows;
                $this->sheets[0]['numCols'] = $this->numCols;
                $this->sheets[0]['cells'] = array();
                
                // 读取数据
                for ($row = 1; $row <= $this->numRows; $row++) {
                    for ($col = 1; $col <= $this->numCols; $col++) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $cellValue = $worksheet->getCell($columnLetter . $row)->getValue();
                        $this->sheets[0]['cells'][$row][$col] = (string)$cellValue;
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
        if (class_exists('Spreadsheet_Excel_Reader')) {
            $reader = new Spreadsheet_Excel_Reader();
            $reader->setOutputEncoding('UTF-8');
            $reader->read($filename);
            
            // 复制数据结构
            $this->sheets = $reader->sheets;
            $this->numRows = isset($this->sheets[0]['numRows']) ? $this->sheets[0]['numRows'] : 0;
            $this->numCols = isset($this->sheets[0]['numCols']) ? $this->sheets[0]['numCols'] : 0;
            
            // 确保所有单元格值都是字符串
            for ($row = 1; $row <= $this->numRows; $row++) {
                for ($col = 1; $col <= $this->numCols; $col++) {
                    if (isset($this->sheets[0]['cells'][$row][$col])) {
                        $this->sheets[0]['cells'][$row][$col] = (string)$this->sheets[0]['cells'][$row][$col];
                    }
                }
            }
        } else {
            throw new Exception('无法读取Excel文件：没有可用的读取器');
        }
    }

    public function __get($name) {
        if ($name === 'sheets') {
            return $this->sheets;
        }
        return null;
    }
}
?>