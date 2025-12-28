#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
# Excel数据分析和可视化工具
# 读取allinone.csv文件，生成各种统计图表
"""

import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from matplotlib import rcParams
import numpy as np
import os
import glob
from datetime import datetime

# 设置中文字体
rcParams['font.sans-serif'] = ['SimHei', 'DejaVu Sans']
rcParams['axes.unicode_minus'] = False

def load_data():
    """加载数据"""
    data_dir = "shujukufangzheli"
    
    # 查找allinone.csv文件
    csv_files = glob.glob(os.path.join(data_dir, "allinone.csv"))
    if csv_files:
        data_file = csv_files[0]
    else:
        print("未找到allinone.csv文件")
        return None
    
    try:
        df = pd.read_csv(data_file, encoding='utf-8-sig')
        print(f"成功加载数据，共 {len(df)} 行，{len(df.columns)} 列")
        print(f"列名: {list(df.columns)}")
        return df
    except Exception as e:
        print(f"加载数据时出错: {e}")
        return None

def analyze_subsidies(df):
    """分析补贴情况"""
    print("\n=== 补贴情况分析 ===")
    
    # 找出可能的补贴列（包含关键词）
    subsidy_keywords = ['补贴', '补助', '费用', '津贴', '发放', '金额', '资金', '救助']
    subsidy_cols = []
    
    for col in df.columns:
        col_str = str(col).lower()
        if any(keyword.lower() in col_str for keyword in subsidy_keywords):
            subsidy_cols.append(col)
    
    print(f"发现 {len(subsidy_cols)} 个可能的补贴相关列: {subsidy_cols}")
    
    # 统计有补贴的人数
    if subsidy_cols:
        for col in subsidy_cols:
            # 统计该列非空值的数量
            non_empty_count = df[col].notna().sum()
            total_amount = pd.to_numeric(df[col], errors='coerce').sum()
            print(f"{col}: {non_empty_count} 人获得补贴，总金额: {total_amount}")
    
    return subsidy_cols

def analyze_population(df):
    """分析人口统计信息"""
    print("\n=== 人口统计分析 ===")
    
    # 查找年龄相关列（可能包含出生日期或年龄）
    age_cols = []
    for col in df.columns:
        col_str = str(col).lower()
        if '年龄' in col_str or 'age' in col_str or '出生' in col_str or 'date' in col_str:
            age_cols.append(col)
    
    print(f"发现 {len(age_cols)} 个可能的年龄相关列: {age_cols}")
    
    # 查找性别相关列
    gender_cols = []
    for col in df.columns:
        col_str = str(col).lower()
        if '性别' in col_str or 'gender' in col_str or 'sex' in col_str:
            gender_cols.append(col)
    
    print(f"发现 {len(gender_cols)} 个可能的性别相关列: {gender_cols}")
    
    # 查找姓名列
    name_col = None
    for col in df.columns:
        col_str = str(col).lower()
        if '姓名' in col_str or 'name' in col_str:
            name_col = col
            break
    
    print(f"姓名列: {name_col}")
    
    return age_cols, gender_cols, name_col

def create_visualizations(df):
    """创建可视化图表"""
    print("\n=== 生成可视化图表 ===")
    
    # 创建输出目录
    output_dir = "shujukufangzheli/charts"
    os.makedirs(output_dir, exist_ok=True)
    
    # 1. 补贴情况分析
    subsidy_keywords = ['补贴', '补助', '费用', '津贴', '发放', '金额', '资金', '救助']
    subsidy_cols = []
    
    for col in df.columns:
        col_str = str(col).lower()
        if any(keyword.lower() in col_str for keyword in subsidy_keywords):
            subsidy_cols.append(col)
    
    # 生成补贴分布图
    if subsidy_cols:
        plt.figure(figsize=(12, 8))
        
        # 统计每种补贴的获得人数
        subsidy_counts = []
        subsidy_names = []
        
        for col in subsidy_cols:
            count = df[col].notna().sum()
            if count > 0:  # 只统计有数据的补贴
                subsidy_counts.append(count)
                subsidy_names.append(col)
        
        if subsidy_names:
            plt.barh(subsidy_names, subsidy_counts)
            plt.title('各类补贴获得人数统计')
            plt.xlabel('获得人数')
            plt.ylabel('补贴类型')
            plt.tight_layout()
            plt.savefig(os.path.join(output_dir, 'subsidy_distribution.png'), dpi=300, bbox_inches='tight')
            plt.close()
            print(f"已生成补贴分布图: {os.path.join(output_dir, 'subsidy_distribution.png')}")
    
    # 2. 性别分布
    gender_col = None
    for col in df.columns:
        col_str = str(col).lower()
        if '性别' in col_str or 'gender' in col_str or 'sex' in col_str:
            gender_col = col
            break
    
    if gender_col and gender_col in df.columns:
        plt.figure(figsize=(10, 6))
        
        gender_counts = df[gender_col].value_counts()
        plt.pie(gender_counts.values, labels=gender_counts.index, autopct='%1.1f%%', startangle=90)
        plt.title(f'{gender_col}分布')
        plt.tight_layout()
        plt.savefig(os.path.join(output_dir, 'gender_distribution.png'), dpi=300, bbox_inches='tight')
        plt.close()
        print(f"已生成性别分布图: {os.path.join(output_dir, 'gender_distribution.png')}")
    
    # 3. 数据概览
    plt.figure(figsize=(12, 8))
    df_count = len(df)
    plt.text(0.1, 0.8, f'总记录数: {df_count}', fontsize=16, transform=plt.gca().transAxes)
    plt.text(0.1, 0.7, f'总列数: {len(df.columns)}', fontsize=16, transform=plt.gca().transAxes)
    plt.text(0.1, 0.6, f'补贴类型数: {len(subsidy_cols)}', fontsize=16, transform=plt.gca().transAxes)
    plt.text(0.1, 0.5, f'补贴总人数: {sum(df[col].notna().sum() for col in subsidy_cols) if subsidy_cols else 0}', fontsize=16, transform=plt.gca().transAxes)
    plt.axis('off')
    plt.title('数据概览')
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, 'data_overview.png'), dpi=300, bbox_inches='tight')
    plt.close()
    print(f"已生成数据概览图: {os.path.join(output_dir, 'data_overview.png')}")
    
    # 4. 列类型分布
    plt.figure(figsize=(12, 8))
    col_types = df.dtypes.value_counts()
    plt.pie(col_types.values, labels=[str(t) for t in col_types.index], autopct='%1.1f%%', startangle=90)
    plt.title('列数据类型分布')
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, 'column_types.png'), dpi=300, bbox_inches='tight')
    plt.close()
    print(f"已生成列类型分布图: {os.path.join(output_dir, 'column_types.png')}")
    
    print(f"\n所有图表已保存到: {output_dir}")

def generate_report(df):
    """生成数据报告"""
    print("\n=== 生成数据报告 ===")
    
    output_dir = "shujukufangzheli/reports"
    os.makedirs(output_dir, exist_ok=True)
    
    report_file = os.path.join(output_dir, 'data_analysis_report.txt')
    
    with open(report_file, 'w', encoding='utf-8') as f:
        f.write(f"Excel数据分析报告\n")
        f.write(f"生成时间: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        f.write(f"数据源: allinone.csv\n\n")
        
        f.write(f"数据概览:\n")
        f.write(f"- 总记录数: {len(df)}\n")
        f.write(f"- 总列数: {len(df.columns)}\n")
        f.write(f"- 内存使用: {df.memory_usage(deep=True).sum() / 1024**2:.2f} MB\n\n")
        
        f.write(f"列名列表:\n")
        for i, col in enumerate(df.columns, 1):
            f.write(f"{i}. {col}\n")
        f.write(f"\n")
        
        # 补贴分析
        subsidy_keywords = ['补贴', '补助', '费用', '津贴', '发放', '金额', '资金', '救助']
        subsidy_cols = []
        
        for col in df.columns:
            col_str = str(col).lower()
            if any(keyword.lower() in col_str for keyword in subsidy_keywords):
                subsidy_cols.append(col)
        
        f.write(f"补贴相关列分析:\n")
        for col in subsidy_cols:
            non_empty_count = df[col].notna().sum()
            total_amount = pd.to_numeric(df[col], errors='coerce').sum()
            f.write(f"- {col}: {non_empty_count} 人获得补贴，总金额: {total_amount}\n")
        f.write(f"\n")
        
        # 性别分析
        gender_col = None
        for col in df.columns:
            col_str = str(col).lower()
            if '性别' in col_str or 'gender' in col_str or 'sex' in col_str:
                gender_col = col
                break
        
        if gender_col and gender_col in df.columns:
            f.write(f"性别分布:\n")
            gender_counts = df[gender_col].value_counts()
            for gender, count in gender_counts.items():
                percentage = (count / len(df)) * 100
                f.write(f"- {gender}: {count} 人 ({percentage:.2f}%)\n")
            f.write(f"\n")
    
    print(f"数据报告已保存到: {report_file}")

def main():
    """主函数"""
    print("开始分析Excel数据...")
    
    # 加载数据
    df = load_data()
    if df is None:
        return
    
    # 分析补贴情况
    subsidy_cols = analyze_subsidies(df)
    
    # 分析人口统计
    age_cols, gender_cols, name_col = analyze_population(df)
    
    # 生成可视化图表
    create_visualizations(df)
    
    # 生成数据报告
    generate_report(df)
    
    print("\n数据分析完成！")
    print("生成的文件:")
    print("- 图表保存在: shujukufangzheli/charts/")
    print("- 报告保存在: shujukufangzheli/reports/")

if __name__ == "__main__":
    main()