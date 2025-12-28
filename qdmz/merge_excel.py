#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
# Excel文件合并工具 (Python版本)
# 将shujukufangzheli目录下的所有Excel文件合并到一个总表中
# 以"田冲2147总表.xls"为基础，根据姓名和身份证合并其他文件的数据
# 新增列名包含数据来源文件名，便于追踪数据来源
# 保持基础表原数据不变，只追加新列
"""

import os
import pandas as pd
from pathlib import Path
glob

def merge_excel_files():
    """合并Excel文件"""
    # 定义目录路径
    data_dir = "shujukufangzheli"
    
    # 检查目录是否存在
    if not os.path.exists(data_dir):
        print(f"目录 {data_dir} 不存在")
        return
    
    # 获取目录下所有Excel文件
    excel_files = []
    for file in os.listdir(data_dir):
        if file.lower().endswith(('.xls', '.xlsx')):
            excel_files.append(os.path.join(data_dir, file))
    
    if not excel_files:
        print(f"目录 {data_dir} 中没有找到Excel文件")
        return
    
    print(f"找到Excel文件: {excel_files}")
    
    # 找到基础文件"田冲2147总表.xls"
    base_file = None
    for file in excel_files:
        if "田冲2147总表" in os.path.basename(file):
            base_file = file
            break
    
    # 如果没有精确匹配，尝试包含"田冲"和"总表"的文件
    if not base_file:
        for file in excel_files:
            filename = os.path.basename(file)
            if "田冲" in filename and "总表" in filename:
                base_file = file
                break
    
    if not base_file:
        # 如果找不到基础文件，使用第一个文件作为基础
        base_file = excel_files[0]
        print(f"未找到'田冲2147总表'，使用 '{os.path.basename(base_file)}' 作为基础文件")
    else:
        print(f"使用 '{os.path.basename(base_file)}' 作为基础文件")
    
    print(f"基础文件: {base_file}")
    
    # 读取基础文件
    try:
        base_df = pd.read_excel(base_file)
        print(f"基础文件包含 {len(base_df)} 行数据")
        print(f"基础文件列: {list(base_df.columns)}")
    except Exception as e:
        print(f"读取基础文件时出错: {e}")
        return
    
    # 确保基础表有姓名和身份证列
    name_col = None
    id_col = None
    
    for col in base_df.columns:
        col_str = str(col).lower().strip()
        if '姓名' in col_str or 'name' in col_str:
            name_col = col
        if '身份证' in col_str or 'id' in col_str or '证件' in col_str:
            id_col = col
    
    if name_col is None:
        print("警告: 基础文件中未找到'姓名'列")
    else:
        print(f"姓名列: {name_col}")
    
    if id_col is None:
        print("警告: 基础文件中未找到'身份证'列")
    else:
        print(f"身份证列: {id_col}")
    

    
    # 获取所有需要合并的文件
    files_to_merge = [f for f in excel_files if f != base_file]
    print(f"需要合并 {len(files_to_merge)} 个文件")
    
    # 合并其他文件的数据
    for file_path in files_to_merge:
        source_filename = os.path.splitext(os.path.basename(file_path))[0]  # 获取不带扩展名的文件名
        print(f"正在处理文件: {os.path.basename(file_path)}")
        
        try:
            # 读取当前文件
            current_df = pd.read_excel(file_path)
            print(f"  - 文件包含 {len(current_df)} 行数据")
            
            # 确保当前文件也有姓名和身份证列
            curr_name_col = None
            curr_id_col = None
            
            for col in current_df.columns:
                col_str = str(col).lower().strip()
                if '姓名' in col_str or 'name' in col_str:
                    curr_name_col = col
                if '身份证' in col_str or 'id' in col_str or '证件' in col_str:
                    curr_id_col = col
            
            if curr_name_col is None and curr_id_col is None:
                print("  - 跳过文件，未找到姓名或身份证列")
                continue  # 跳过没有姓名或身份证列的文件
            
            print(f"  - 当前文件姓名列: {curr_name_col}, 身份证列: {curr_id_col}")
            
            # 合并数据（只追加新列，不修改原有数据）
            for idx, current_row in current_df.iterrows():
                
                name_value = str(current_row[curr_name_col]) if curr_name_col and pd.notna(current_row[curr_name_col]) else ''
                id_value = str(current_row[curr_id_col]) if curr_id_col and pd.notna(current_row[curr_id_col]) else ''
                
                # 根据姓名和身份证查找匹配的行
                for base_idx, base_row in base_df.iterrows():
                    base_name = str(base_row[name_col]) if name_col and pd.notna(base_row[name_col]) else ''
                    base_id = str(base_row[id_col]) if id_col and pd.notna(base_row[id_col]) else ''
                    
                    # 检查是否匹配
                    if ((name_col and curr_name_col and base_name.strip() == name_value.strip()) or
                        (id_col and curr_id_col and base_id.strip() == id_value.strip())):
                        
                        print(f"  - 找到匹配项: {name_value}")
                        
                        # 找到匹配行，追加新列数据（列名包含来源文件名）
                        for col in current_df.columns:
                            if col != curr_name_col and col != curr_id_col:  # 不处理姓名和身份证列
                                original_col_name = str(col)
                                new_col_name = f"{source_filename}_{original_col_name}"  # 包含来源文件名的新列名
                                
                                # 检查基础数据中是否已存在该列（检查原始列名和带来源的列名）
                                if original_col_name not in base_df.columns and new_col_name not in base_df.columns:
                                    # 添加新列到基础表（使用包含来源文件名的新列名）
                                    base_df[new_col_name] = ''
                                    print(f"    - 添加新列: {new_col_name}")
                                else:
                                    # 如果列已存在，使用已有的列名
                                    if original_col_name in base_df.columns:
                                        new_col_name = original_col_name
                                    elif new_col_name in base_df.columns:
                                        new_col_name = new_col_name
                                
                                # 只在新列中添加数据，不修改原有列的数据
                                if pd.notna(current_row[col]):
                                    if new_col_name not in base_df.columns:
                                        # 添加新列
                                        base_df[new_col_name] = ''
                                    base_df.at[base_idx, new_col_name] = current_row[col]
                                    print(f"    - 在新列 '{new_col_name}' 添加值: {current_row[col]}")
                        
                        break  # 找到匹配行后跳出循环
        
        except Exception as e:
            print(f"  - 读取文件时发生异常: {str(e)}，跳过")
            continue  # 跳过无法读取的文件
        
        except Exception as e:
            print(f"处理文件 {file_path} 时出错: {e}")
            continue
    
    # 保存合并后的文件
    output_file = os.path.join(data_dir, "allinone.csv")
    base_df.to_csv(output_file, index=False, encoding='utf-8-sig')
    
    print(f"合并完成！结果已保存到: {output_file}")
    print(f"最终文件包含 {len(base_df)} 行数据")
    print(f"最终文件包含 {len(base_df.columns)} 列")

if __name__ == "__main__":
    merge_excel_files()