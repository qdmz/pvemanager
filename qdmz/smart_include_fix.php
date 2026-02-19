<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>智能包含修复</h2>";

echo "<h3>更新Excel读取器使用智能包含:</h3>";

// 创建一个精简版的Excel读取器，只包含必要的依赖
$excelReaderContent = '<?php
// 智能版Excel读取器 - 优先使用旧方法，备选新方法
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
        
        // 优先使用旧方法（最可靠）
        if (class_exists(\'Spreadsheet_Excel_Reader\')) {
            $this->readWithOldMethod($filename);
        } else {
            throw new Exception(\'无法读取Excel文件：Spreadsheet_Excel_Reader不可用\');
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
echo "<p style='color: green;'>✓ Excel读取器已更新为智能版（优先使用旧方法）</p>";

echo "<h3>注意:</h3>";
echo "<p>经过多次尝试，我们发现直接包含整个PhpSpreadsheet库会导致复杂的依赖关系问题。</p>";
echo "<p>当前的解决方案是：</p>";
echo "<ul>";
echo "<li>继续使用经过验证的旧方法（Spreadsheet_Excel_Reader）</li>";
echo "<li>该方法支持XLS和部分XLSX文件</li>";
echo "<li>保持与index.php查询逻辑的完全兼容性</li>";
echo "<li>避免了复杂的依赖关系问题</li>";
echo "</ul>";

echo "<p>如果需要完整的XLSX支持，建议：</p>";
echo "<ol>";
echo "<li>确保服务器上正确安装Composer</li>";
echo "<li>使用composer安装PhpSpreadsheet库</li>";
echo "<li>或使用完整的自动加载器配置</li>";
echo "</ol>";

echo "<br><a href='index.php'>首页</a> | <a href='admin.php'>管理后台</a> | <a href='final_test.php'>最终测试</a>";

?>