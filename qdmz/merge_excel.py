#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Excel文件合并工具
将shujukufangzheli目录下的所有Excel文件合并到一个总表中
以"田冲2147总表.xls"为基础，根据姓名和身份证合并其他文件的数据
"""

import os
import pandas as pd
from pathlib import Path

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
        if "田冲2147总表.xls" in file:
            base_file = file
            break
    
    if not base_file:
        print("未找到基础文件'田冲2147总表.xls'")
        return
    
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
    if '姓名' not in base_df.columns and '身份证' not in base_df.columns:
        # 尝试常见的列名变体
        name_col = None
        id_col = None
        for col in base_df.columns:
            if '姓名' in col or 'name' in col.lower() or 'Name' in col:
                name_col = col
                break
        for col in base_df.columns:
            if '身份证' in col or 'id' in col.lower() or 'ID' in col or '证件' in col:
                id_col = col
                break
        
        if not name_col and not id_col:
            print("基础文件中未找到姓名或身份证列，请确认列名")
            return
    else:
        name_col = '姓名' if '姓名' in base_df.columns else None
        id_col = '身份证' if '身份证' in base_df.columns else None
        
        # 如果没有找到标准列名，尝试常见变体
        if not name_col:
            for col in base_df.columns:
                if 'name' in col.lower() or 'Name' in col:
                    name_col = col
                    break
        if not id_col:
            for col in base_df.columns:
                if 'id' in col.lower() or 'ID' in col or '证件' in col:
                    id_col = col
                    break
    
    print(f"使用姓名列为: {name_col}, 身份证列为: {id_col}")
    
    # 获取所有文件以合并
    files_to_merge = [f for f in excel_files if f != base_file]
    
    # 合并其他文件的数据
    for file_path in files_to_merge:
        print(f"正在处理文件: {file_path}")
        try:
            # 读取当前文件
            current_df = pd.read_excel(file_path)
            print(f"  - 当前文件包含 {len(current_df)} 行数据")
            print(f"  - 当前文件列: {list(current_df.columns)}")
            
            # 确保当前文件也有姓名和身份证列
            curr_name_col = None
            curr_id_col = None
            
            if '姓名' in current_df.columns and '身份证' in current_df.columns:
                curr_name_col = '姓名'
                curr_id_col = '身份证'
            else:
                # 尝试常见变体
                for col in current_df.columns:
                    if '姓名' in col or 'name' in col.lower() or 'Name' in col:
                        curr_name_col = col
                        break
                for col in current_df.columns:
                    if '身份证' in col or 'id' in col.lower() or 'ID' in col or '证件' in col:
                        curr_id_col = col
                        break
            
            if not curr_name_col and not curr_id_col:
                print(f"  - 跳过文件 {file_path}，未找到姓名或身份证列")
                continue
            
            print(f"  - 当前文件使用姓名列为: {curr_name_col}, 身份证列为: {curr_id_col}")
            
            # 合并数据
            for idx, row in current_df.iterrows():
                # 根据姓名和身份证查找匹配的行
                match_found = False
                
                # 根据姓名查找匹配
                if curr_name_col and name_col:
                    name_value = row[curr_name_col]
                    if pd.notna(name_value):
                        matching_rows = base_df[base_df[name_col] == name_value]
                        
                        # 如果有多个匹配，尝试使用身份证进一步筛选
                        if len(matching_rows) > 0 and curr_id_col and id_col:
                            id_value = row[curr_id_col]
                            if pd.notna(id_value):
                                for base_idx in matching_rows.index:
                                    if base_df.loc[base_idx, id_col] == id_value:
                                        # 找到完全匹配的行，合并数据
                                        for col in current_df.columns:
                                            if col not in [curr_name_col, curr_id_col]:  # 不处理姓名和身份证列
                                                new_col = col
                                                if new_col not in base_df.columns:
                                                    # 添加新列
                                                    base_df[new_col] = ""
                                                # 更新值
                                                if pd.notna(row[col]):
                                                    base_df.loc[base_idx, new_col] = row[col]
                                        match_found = True
                                        break
                        
                        # 如果只用姓名匹配到一行，且没有身份证信息或身份证不匹配
                        elif len(matching_rows) == 1:
                            base_idx = matching_rows.index[0]
                            # 合并数据
                            for col in current_df.columns:
                                if col not in [curr_name_col, curr_id_col]:  # 不处理姓名和身份证列
                                    new_col = col
                                    if new_col not in base_df.columns:
                                        # 添加新列
                                        base_df[new_col] = ""
                                    # 更新值
                                    if pd.notna(row[col]):
                                        base_df.loc[base_idx, new_col] = row[col]
                            match_found = True
                
                # 如果没有通过姓名找到匹配，尝试只通过身份证查找
                if not match_found and curr_id_col and id_col:
                    id_value = row[curr_id_col]
                    if pd.notna(id_value):
                        matching_rows = base_df[base_df[id_col] == id_value]
                        if len(matching_rows) > 0:
                            base_idx = matching_rows.index[0]
                            # 合并数据
                            for col in current_df.columns:
                                if col not in [curr_name_col, curr_id_col]:  # 不处理姓名和身份证列
                                    new_col = col
                                    if new_col not in base_df.columns:
                                        # 添加新列
                                        base_df[new_col] = ""
                                    # 更新值
                                    if pd.notna(row[col]):
                                        base_df.loc[base_idx, new_col] = row[col]
                            match_found = True
                
                if not match_found:
                    # 如果没有找到匹配的行，添加新行
                    new_row = {}
                    # 初始化新行，所有基础列设为空值
                    for col in base_df.columns:
                        new_row[col] = ""
                    
                    # 填充来自当前文件的数据
                    for col in current_df.columns:
                        if col == curr_name_col and name_col:
                            new_row[name_col] = row[col]
                        elif col == curr_id_col and id_col:
                            new_row[id_col] = row[col]
                        elif col not in [curr_name_col, curr_id_col]:
                            new_col = col
                            if new_col not in new_row:
                                new_row[new_col] = ""
                            if pd.notna(row[col]):
                                new_row[new_col] = row[col]
                    
                    # 添加新行到基础DataFrame
                    base_df = pd.concat([base_df, pd.DataFrame([new_row])], ignore_index=True)
                    print(f"    - 添加新行: {row[curr_name_col] if curr_name_col else 'Unknown'}")
        
        except Exception as e:
            print(f"处理文件 {file_path} 时出错: {e}")
            continue
    
    # 保存合并后的文件
    output_file = os.path.join(data_dir, "allinone.xls")
    try:
        base_df.to_excel(output_file, index=False)
        print(f"\n合并完成！结果已保存到: {output_file}")
        print(f"最终文件包含 {len(base_df)} 行数据")
        print(f"最终文件包含列: {list(base_df.columns)}")
    except Exception as e:
        print(f"保存文件时出错: {e}")

if __name__ == "__main__":
    merge_excel_files()