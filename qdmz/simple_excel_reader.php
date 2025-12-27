<?php
// 简化版Excel读取器，完全兼容老版本PHP

// 尝试加载自动加载器
$autoload_path = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload_path)) {
    $autoload_path = __DIR__ . '/../vendor/autoload.php';
}
if (!file_exists($autoload_path)) {
    $autoload_path = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
}

if (file_exists($autoload_path)) {
    require_once $autoload_path;
    
    // 检查PhpOffice\PhpSpreadsheet类是否可用
    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
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
                    'numRows' => 0,
                    'numCols' => 0,
                    'cells' => array()
                );
            }

            public function setOutputEncoding($encoding) {
                // 保持向后兼容
            }

            public function read($filename) {
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filename);
                    $this->objPHPExcel = $spreadsheet;
                    $this->sheet = $spreadsheet->getActiveSheet();
                    
                    $this->numRows = $this->sheet->getHighestRow();
                    $this->numCols = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($this->sheet->getHighestColumn());
                    
                    for ($row = 1; $row <= $this->numRows; $row++) {
                        for ($col = 1; $col <= $this->numCols; $col++) {
                            $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                            $cell = $this->sheet->getCell($cellCoordinate);
                            $value = $cell->getValue();
                            $this->sheets[0]['cells'][$row][$col] = $value !== null ? (string)$value : '';
                        }
                    }
                    
                    $this->sheets[0]['numRows'] = $this->numRows;
                    $this->sheets[0]['numCols'] = $this->numCols;
                    
                } catch (Exception $e) {
                    throw new Exception("无法读取Excel文件: " . $e->getMessage());
                }
            }
            
            public function __get($name) {
                if ($name === 'sheets') {
                    return $this->sheets;
                }
                return null;
            }
        }
    } else {
        // 如果PhpSpreadsheet不可用，使用旧方法
        if (!class_exists('Spreadsheet_Excel_Reader')) {
            include_once 'excel.php';
        }
        
        if (class_exists('Spreadsheet_Excel_Reader')) {
            class_alias('Spreadsheet_Excel_Reader', 'ExcelReader');
        }
    }
} else {
    // 如果没有自动加载器，使用旧方法
    if (!class_exists('Spreadsheet_Excel_Reader')) {
        include_once 'excel.php';
    }
    
    if (class_exists('Spreadsheet_Excel_Reader')) {
        class_alias('Spreadsheet_Excel_Reader', 'ExcelReader');
    }
}
?>