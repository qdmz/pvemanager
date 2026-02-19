<?php
/**
 * 简化版项目兼容Excel文件合并工具 (PHP版本)
 * 使用项目中已验证的Excel读取机制
 * 暂时注释掉添加新行的功能，只处理现有数据
 */

// 抑制PHP废弃警告和精度丢失警告
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// 设置脚本执行时间限制
ini_set('max_execution_time', 300); // 5分钟
ini_set('memory_limit', '512M');

// 包含项目中的Excel读取器
require_once __DIR__ . '/inc/excel_reader.php';

// 定义目录路径
$data_dir = "shujukufangzheli";

// 检查目录是否存在
if (!is_dir($data_dir)) {
    exit("目录 {$data_dir} 不存在\n");
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
    exit("目录 {$data_dir} 中没有找到Excel文件\n");
}

echo "找到 " . count($excel_files) . " 个Excel文件\n";

// 找到基础文件"田冲2147总表.xls"
$base_file = null;
foreach ($excel_files as $file) {
    if (strpos(basename($file), '田冲2147总表') !== false) {
        $base_file = $file;
        break;
    }
    // 如果没有精确匹配，尝试包含"田冲"和"总表"的文件
    if (strpos(basename($file), '田冲') !== false && strpos(basename($file), '总表') !== false) {
        $base_file = $file;
        break;
    }
}

if (!$base_file) {
    // 如果找不到基础文件，使用第一个文件作为基础
    $base_file = $excel_files[0];
    echo "未找到'田冲2147总表'，使用 '" . basename($base_file) . "' 作为基础文件\n";
} else {
    echo "使用 '" . basename($base_file) . "' 作为基础文件\n";
}

// 检查基础文件是否存在
if (!file_exists($base_file)) {
    exit("基础文件不存在: {$base_file}\n");
}

echo "基础文件大小: " . filesize($base_file) . " 字节\n";

// 使用项目中的ExcelReader读取基础文件
echo "尝试使用项目ExcelReader读取基础文件...\n";

$excel = new ExcelReader();
try {
    $excel->read($base_file);
    
    // 获取第一个工作表
    $sheet = $excel->sheets[0];
    
    // 获取行数和列数
    $rowcount = isset($sheet['numRows']) ? $sheet['numRows'] : 0;
    $colcount = isset($sheet['numCols']) ? $sheet['numCols'] : 0;
    
    echo "基础文件读取成功，共 {$rowcount} 行，{$colcount} 列\n";
    
    // 获取基础数据
    $base_data = [];
    $col_names = [];
    
    // 提取基础文件的列名（第一行）
    for ($col = 1; $col <= $colcount; $col++) {
        // ExcelReader中行索引从1开始
        $col_names[] = isset($sheet['cells'][1][$col]) ? $sheet['cells'][1][$col] : '';
    }
    
    echo "基础文件列名: " . implode(", ", $col_names) . "\n";
    
    // 提取基础文件的数据（从第二行开始）
    for ($row = 2; $row <= $rowcount; $row++) {
        $row_data = [];
        for ($col = 1; $col <= $colcount; $col++) {
            $row_data[] = isset($sheet['cells'][$row][$col]) ? $sheet['cells'][$row][$col] : '';
        }
        $base_data[] = $row_data;
    }
    
    echo "基础文件包含 " . count($base_data) . " 行数据\n";
    
} catch (Exception $e) {
    exit("无法使用项目ExcelReader读取基础文件: " . $e->getMessage() . "\n");
}

// 确保基础表有姓名和身份证列
$name_col_index = -1;
$id_col_index = -1;

foreach ($col_names as $index => $col_name) {
    $col_name_lower = strtolower(trim($col_name));
    if (strpos($col_name_lower, '姓名') !== false || strpos($col_name_lower, 'name') !== false) {
        $name_col_index = $index;
        break;
    }
}

foreach ($col_names as $index => $col_name) {
    $col_name_lower = strtolower(trim($col_name));
    if (strpos($col_name_lower, '身份证') !== false || strpos($col_name_lower, 'id') !== false || strpos($col_name_lower, '证件') !== false) {
        $id_col_index = $index;
        break;
    }
}

if ($name_col_index == -1) {
    echo "警告: 基础文件中未找到'姓名'列\n";
} else {
    echo "姓名列索引: {$name_col_index}\n";
}

if ($id_col_index == -1) {
    echo "警告: 基础文件中未找到'身份证'列\n";
} else {
    echo "身份证列索引: {$id_col_index}\n";
}

// 获取所有需要合并的文件
$files_to_merge = array_filter($excel_files, function($file) use ($base_file) {
    return $file !== $base_file;
});

echo "需要合并 " . count($files_to_merge) . " 个文件\n";

// 合并其他文件的数据
foreach ($files_to_merge as $file_path) {
    echo "正在处理文件: " . basename($file_path) . "\n";
    
    // 检查文件是否存在
    if (!file_exists($file_path)) {
        echo "  - 文件不存在，跳过\n";
        continue;
    }
    
    echo "  - 文件大小: " . filesize($file_path) . " 字节\n";
    
    try {
        // 使用项目ExcelReader读取当前文件
        $current_excel = new ExcelReader();
        $current_excel->read($file_path);
        
        // 获取当前Excel文件的行数和列数
        $current_sheet = $current_excel->sheets[0];
        $current_rowcount = isset($current_sheet['numRows']) ? $current_sheet['numRows'] : 0;
        $current_colcount = isset($current_sheet['numCols']) ? $current_sheet['numCols'] : 0;
        
        echo "  - 文件包含 {$current_rowcount} 行，{$current_colcount} 列\n";
        
        // 获取当前文件的数据
        $current_col_names = [];
        $current_data = [];
        
        // 提取当前文件的列名（第一行）
        for ($col = 1; $col <= $current_colcount; $col++) {
            $current_col_names[] = isset($current_sheet['cells'][1][$col]) ? $current_sheet['cells'][1][$col] : '';
        }
        
        echo "  - 当前文件列名: " . implode(", ", $current_col_names) . "\n";
        
        // 提取当前文件的数据（从第二行开始）
        for ($row = 2; $row <= $current_rowcount; $row++) {
            $row_data = [];
            for ($col = 1; $col <= $current_colcount; $col++) {
                $row_data[] = isset($current_sheet['cells'][$row][$col]) ? $current_sheet['cells'][$row][$col] : '';
            }
            $current_data[] = $row_data;
        }
        
        // 确保当前文件也有姓名和身份证列
        $curr_name_col_index = -1;
        $curr_id_col_index = -1;
        
        foreach ($current_col_names as $index => $col_name) {
            $col_name_lower = strtolower(trim($col_name));
            if (strpos($col_name_lower, '姓名') !== false || strpos($col_name_lower, 'name') !== false) {
                $curr_name_col_index = $index;
                break;
            }
        }
        
        foreach ($current_col_names as $index => $col_name) {
            $col_name_lower = strtolower(trim($col_name));
            if (strpos($col_name_lower, '身份证') !== false || strpos($col_name_lower, 'id') !== false || strpos($col_name_lower, '证件') !== false) {
                $curr_id_col_index = $index;
                break;
            }
        }
        
        if ($curr_name_col_index == -1 && $curr_id_col_index == -1) {
            echo "  - 跳过文件，未找到姓名或身份证列\n";
            continue; // 跳过没有姓名或身份证列的文件
        }
        
        echo "  - 当前文件姓名列索引: {$curr_name_col_index}, 身份证列索引: {$curr_id_col_index}\n";
        
        // 合并数据（只更新现有行，不添加新行）
        foreach ($current_data as $current_row) {
            $name_value = isset($current_row[$curr_name_col_index]) ? trim((string)$current_row[$curr_name_col_index]) : '';
            $id_value = isset($current_row[$curr_id_col_index]) ? trim((string)$current_row[$curr_id_col_index]) : '';
            
            // 根据姓名和身份证查找匹配的行
            for ($i = 0; $i < count($base_data); $i++) {
                $base_name = isset($base_data[$i][$name_col_index]) ? trim((string)$base_data[$i][$name_col_index]) : '';
                $base_id = isset($base_data[$i][$id_col_index]) ? trim((string)$base_data[$i][$id_col_index]) : '';
                
                // 检查是否匹配
                if (($name_col_index != -1 && $curr_name_col_index != -1 && $base_name == $name_value) ||
                    ($id_col_index != -1 && $curr_id_col_index != -1 && $base_id == $id_value)) {
                    
                    echo "  - 找到匹配项: {$name_value}\n";
                    
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
                    
                    break; // 找到匹配行后跳出循环
                }
            }
            // 注意：这里注释掉了添加新行的逻辑，只处理现有匹配的数据
        }
    } catch (Exception $e) {
        echo "  - 读取文件时发生异常: " . $e->getMessage() . "，跳过\n";
        continue; // 跳过无法读取的文件
    }
}

// 创建CSV格式的合并文件
$output_file = $data_dir . DIRECTORY_SEPARATOR . "allinone.csv";

// 打开文件写入
$fp = fopen($output_file, 'w');
if ($fp === false) {
    exit("无法创建输出文件: {$output_file}\n");
}

// 写入列名
fputcsv($fp, $col_names);

// 写入数据行
foreach ($base_data as $row) {
    fputcsv($fp, $row);
}

fclose($fp);

echo "合并完成！结果已保存到: {$output_file}\n";
echo "最终文件包含 " . count($base_data) . " 行数据\n";
echo "最终文件包含 " . count($col_names) . " 列\n";
?>