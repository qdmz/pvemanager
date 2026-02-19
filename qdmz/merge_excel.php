<?php
/**
 * Excel文件合并工具 (PHP版本)
 * 将shujukufangzheli目录下的所有Excel文件合并到一个总表中
 * 以"田冲2147总表.xls"为基础，根据姓名和身份证合并其他文件的数据
 */

// 设置脚本执行时间限制
ini_set('max_execution_time', 300); // 5分钟
ini_set('memory_limit', '512M');

echo "开始合并Excel文件...\n";

// 定义目录路径
$data_dir = "shujukufangzheli";

// 检查目录是否存在
if (!is_dir($data_dir)) {
    die("目录 {$data_dir} 不存在\n");
}

// 获取目录下所有Excel文件
$excel_files = [];
$dir_handle = opendir($data_dir);
if ($dir_handle) {
    while (($file = readdir($dir_handle)) !== false) {
        if (strtolower(substr($file, -4)) === '.xls' || strtolower(substr($file, -5)) === '.xlsx') {
            $excel_files[] = $data_dir . DIRECTORY_SEPARATOR . $file;
        }
    }
    closedir($dir_handle);
}

if (empty($excel_files)) {
    die("目录 {$data_dir} 中没有找到Excel文件\n");
}

echo "找到Excel文件:\n";
foreach ($excel_files as $file) {
    echo "  - {$file}\n";
}

// 找到基础文件"田冲2147总表.xls"
$base_file = null;
foreach ($excel_files as $file) {
    if (strpos($file, '田冲2147总表.xls') !== false) {
        $base_file = $file;
        break;
    }
}

if (!$base_file) {
    // 尝试其他可能的文件名
    foreach ($excel_files as $file) {
        if (strpos(basename($file), '田冲2147总表') !== false) {
            $base_file = $file;
            break;
        }
    }
}

if (!$base_file) {
    die("未找到基础文件'田冲2147总表.xls'\n");
}

echo "基础文件: {$base_file}\n";

// 检查是否可以包含Excel读取器
$excel_reader_path = __DIR__ . '/inc/excel_reader.php';
if (!file_exists($excel_reader_path)) {
    die("Excel读取器文件不存在: {$excel_reader_path}\n");
}

// 包含Excel读取器
require_once $excel_reader_path;

// 读取基础文件
$excel = new ExcelReader();
if (!$excel->read($base_file)) {
    die("无法读取基础文件: {$base_file}\n");
}

echo "基础文件读取成功\n";

// 获取基础数据
$base_data = [];
$col_names = [];

// 提取基础文件的数据
for ($row = 1; $row < $excel->rowcount; $row++) {
    $row_data = [];
    for ($col = 0; $col < $excel->colcount; $col++) {
        $value = $excel->val($row, $col);
        if ($row == 1) { // 第一行是列名
            $col_names[] = $value;
        } else {
            $row_data[] = $value;
        }
    }
    
    if ($row > 1) { // 从第二行开始是实际数据
        $base_data[] = $row_data;
    }
}

echo "基础文件包含 " . count($base_data) . " 行数据\n";
echo "基础文件列: " . implode(", ", $col_names) . "\n";

// 确保基础表有姓名和身份证列
$name_col_index = -1;
$id_col_index = -1;

foreach ($col_names as $index => $col_name) {
    if (strpos($col_name, '姓名') !== false || stripos($col_name, 'name') !== false) {
        $name_col_index = $index;
        break;
    }
}

foreach ($col_names as $index => $col_name) {
    if (strpos($col_name, '身份证') !== false || stripos($col_name, 'id') !== false || stripos($col_name, '证件') !== false) {
        $id_col_index = $index;
        break;
    }
}

if ($name_col_index == -1 && $id_col_index == -1) {
    die("基础文件中未找到姓名或身份证列，请确认列名\n");
}

echo "使用姓名列索引: {$name_col_index}, 身份证列索引: {$id_col_index}\n";

// 获取所有需要合并的文件
$files_to_merge = array_filter($excel_files, function($file) use ($base_file) {
    return $file !== $base_file;
});

echo "需要合并的文件数量: " . count($files_to_merge) . "\n";

// 合并其他文件的数据
foreach ($files_to_merge as $file_path) {
    echo "正在处理文件: {$file_path}\n";
    
    // 读取当前文件
    $current_excel = new ExcelReader();
    if (!$current_excel->read($file_path)) {
        echo "  - 无法读取文件: {$file_path}\n";
        continue;
    }
    
    // 获取当前文件的数据
    $current_col_names = [];
    $current_data = [];
    
    // 提取当前文件的列名和数据
    for ($row = 1; $row <= $current_excel->rowcount; $row++) {
        $row_data = [];
        for ($col = 0; $col < $current_excel->colcount; $col++) {
            $value = $current_excel->val($row, $col);
            if ($row == 1) { // 第一行是列名
                $current_col_names[] = $value;
            } else {
                $row_data[] = $value;
            }
        }
        
        if ($row > 1) { // 从第二行开始是实际数据
            $current_data[] = $row_data;
        }
    }
    
    echo "  - 当前文件包含 " . count($current_data) . " 行数据\n";
    echo "  - 当前文件列: " . implode(", ", $current_col_names) . "\n";
    
    // 确保当前文件也有姓名和身份证列
    $curr_name_col_index = -1;
    $curr_id_col_index = -1;
    
    foreach ($current_col_names as $index => $col_name) {
        if (strpos($col_name, '姓名') !== false || stripos($col_name, 'name') !== false) {
            $curr_name_col_index = $index;
            break;
        }
    }
    
    foreach ($current_col_names as $index => $col_name) {
        if (strpos($col_name, '身份证') !== false || stripos($col_name, 'id') !== false || stripos($col_name, '证件') !== false) {
            $curr_id_col_index = $index;
            break;
        }
    }
    
    if ($curr_name_col_index == -1 && $curr_id_col_index == -1) {
        echo "  - 跳过文件 {$file_path}，未找到姓名或身份证列\n";
        continue;
    }
    
    echo "  - 当前文件使用姓名列索引: {$curr_name_col_index}, 身份证列索引: {$curr_id_col_index}\n";
    
    // 合并数据
    foreach ($current_data as $current_row) {
        $match_found = false;
        $name_value = isset($current_row[$curr_name_col_index]) ? $current_row[$curr_name_col_index] : '';
        $id_value = isset($current_row[$curr_id_col_index]) ? $current_row[$curr_id_col_index] : '';
        
        // 根据姓名和身份证查找匹配的行
        for ($i = 0; $i < count($base_data); $i++) {
            $base_name = isset($base_data[$i][$name_col_index]) ? $base_data[$i][$name_col_index] : '';
            $base_id = isset($base_data[$i][$id_col_index]) ? $base_data[$i][$id_col_index] : '';
            
            // 检查是否匹配
            if (($name_col_index != -1 && $curr_name_col_index != -1 && $base_name == $name_value) ||
                ($id_col_index != -1 && $curr_id_col_index != -1 && $base_id == $id_value)) {
                
                // 找到匹配行，合并数据
                for ($j = 0; $j < count($current_col_names); $j++) {
                    if ($j != $curr_name_col_index && $j != $curr_id_col_index) { // 不处理姓名和身份证列
                        $col_name = $current_col_names[$j];
                        
                        // 检查基础数据中是否已存在该列
                        $base_col_index = array_search($col_name, $col_names);
                        if ($base_col_index === false) {
                            // 添加新列
                            $col_names[] = $col_name;
                            $base_col_index = count($col_names) - 1;
                            
                            // 为所有现有行添加新列（初始化为空值）
                            for ($k = 0; $k < count($base_data); $k++) {
                                $base_data[$k][] = '';
                            }
                        }
                        
                        // 更新值
                        if (isset($current_row[$j])) {
                            $base_data[$i][$base_col_index] = $current_row[$j];
                        }
                    }
                }
                
                $match_found = true;
                break;
            }
        }
        
        if (!$match_found) {
            // 没有找到匹配的行，添加新行
            $new_row = array_fill(0, count($col_names), '');
            
            // 填充来自当前文件的数据
            for ($j = 0; $j < count($current_col_names); $j++) {
                $col_name = $current_col_names[$j];
                
                if ($j == $curr_name_col_index && $name_col_index != -1) {
                    $new_row[$name_col_index] = isset($current_row[$j]) ? $current_row[$j] : '';
                } elseif ($j == $curr_id_col_index && $id_col_index != -1) {
                    $new_row[$id_col_index] = isset($current_row[$j]) ? $current_row[$j] : '';
                } elseif ($j != $curr_name_col_index && $j != $curr_id_col_index) {
                    $base_col_index = array_search($col_name, $col_names);
                    if ($base_col_index !== false) {
                        if (isset($current_row[$j])) {
                            $new_row[$base_col_index] = $current_row[$j];
                        }
                    } else {
                        // 添加新列
                        $col_names[] = $col_name;
                        $new_row[] = isset($current_row[$j]) ? $current_row[$j] : '';
                    }
                }
            }
            
            $base_data[] = $new_row;
            echo "    - 添加新行: {$name_value}\n";
        }
    }
}

echo "\n合并完成！正在生成allinone.xls文件...\n";

// 创建一个简单的CSV格式输出，然后可以转换为Excel格式
$output_file = $data_dir . DIRECTORY_SEPARATOR . "allinone.csv";

// 打开文件写入
$fp = fopen($output_file, 'w');
if ($fp === false) {
    die("无法创建输出文件: {$output_file}\n");
}

// 写入列名
fputcsv($fp, $col_names);

// 写入数据行
foreach ($base_data as $row) {
    fputcsv($fp, $row);
}

fclose($fp);

echo "结果已保存到: {$output_file}\n";
echo "最终文件包含 " . count($base_data) . " 行数据\n";
echo "最终文件包含列: " . implode(", ", $col_names) . "\n";

echo "\n注意：已生成CSV格式的合并文件。如需XLS格式，请使用Excel打开并另存为XLS格式。\n";
?>