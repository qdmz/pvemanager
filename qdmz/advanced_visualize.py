#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
# 高级Excel数据分析和可视化工具
# 读取allinone.csv文件，生成各种统计图表和报告
"""

import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from matplotlib import rcParams
import numpy as np
import os
import glob
from datetime import datetime
import re

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
    subsidy_keywords = ['补贴', '补助', '费用', '津贴', '发放', '金额', '资金', '救助', '补贴金', '补助金']
    subsidy_cols = []
    
    for col in df.columns:
        col_str = str(col).lower()
        if any(keyword.lower() in col_str for keyword in subsidy_keywords):
            subsidy_cols.append(col)
    
    print(f"发现 {len(subsidy_cols)} 个可能的补贴相关列: {subsidy_cols}")
    
    # 统计有补贴的人数和总金额
    if subsidy_cols:
        for col in subsidy_cols:
            # 统计该列非空值的数量
            non_empty_count = df[col].notna().sum()
            numeric_values = pd.to_numeric(df[col], errors='coerce')
            total_amount = numeric_values.sum()
            avg_amount = numeric_values.mean()
            print(f"{col}: {non_empty_count} 人获得补贴，总金额: {total_amount:.2f}，平均金额: {avg_amount:.2f}")
    
    return subsidy_cols

def analyze_age_groups(df):
    """分析年龄组分布"""
    print("\n=== 年龄组分析 ===")
    
    # 查找年龄相关列（可能包含年龄或出生日期）
    age_cols = []
    birth_cols = []
    
    for col in df.columns:
        col_str = str(col).lower()
        if '年龄' in col_str or 'age' in col_str:
            age_cols.append(col)
        elif '出生' in col_str or 'birth' in col_str or 'date' in col_str:
            birth_cols.append(col)
    
    print(f"年龄相关列: {age_cols}")
    print(f"出生日期相关列: {birth_cols}")
    
    age_group_counts = {}
    
    # 如果有年龄列，直接分组
    if age_cols:
        age_col = age_cols[0]  # 使用第一个年龄列
        age_data = pd.to_numeric(df[age_col], errors='coerce')
        
        # 定义年龄组
        age_bins = [0, 18, 35, 60, 100]
        age_labels = ['儿童(0-18)', '青年(19-35)', '中年(36-60)', '老年(61+)']
        
        age_groups = pd.cut(age_data, bins=age_bins, labels=age_labels, right=False)
        age_group_counts = age_groups.value_counts()
        
        print("年龄组分布:")
        for group, count in age_group_counts.items():
            print(f"  {group}: {count} 人")
    
    # 如果有出生日期列，计算年龄后分组
    elif birth_cols:
        birth_col = birth_cols[0]  # 使用第一个出生日期列
        try:
            birth_data = pd.to_datetime(df[birth_col], errors='coerce')
            current_year = datetime.now().year
            ages = current_year - birth_data.dt.year
            ages = ages.dropna()
            
            # 定义年龄组
            age_bins = [0, 18, 35, 60, 100]
            age_labels = ['儿童(0-18)', '青年(19-35)', '中年(36-60)', '老年(61+)']
            
            age_groups = pd.cut(ages, bins=age_bins, labels=age_labels, right=False)
            age_group_counts = age_groups.value_counts()
            
            print("年龄组分布:")
            for group, count in age_group_counts.items():
                print(f"  {group}: {count} 人")
        except:
            print("无法解析出生日期列")
    
    return age_group_counts

def analyze_gender(df):
    """分析性别分布"""
    print("\n=== 性别分析 ===")
    
    gender_col = None
    for col in df.columns:
        col_str = str(col).lower()
        if '性别' in col_str or 'gender' in col_str or 'sex' in col_str:
            gender_col = col
            break
    
    if gender_col and gender_col in df.columns:
        gender_counts = df[gender_col].value_counts()
        print(f"性别列: {gender_col}")
        print("性别分布:")
        for gender, count in gender_counts.items():
            percentage = (count / len(df)) * 100
            print(f"  {gender}: {count} 人 ({percentage:.2f}%)")
        return gender_counts
    else:
        print("未找到性别相关列")
        return None

def analyze_villages(df):
    """分析各村分布"""
    print("\n=== 各村分布分析 ===")
    
    # 查找可能表示村庄的列
    village_keywords = ['村', '村名', '地址', '居住地', '住址', 'village', 'address']
    village_col = None
    
    for col in df.columns:
        col_str = str(col).lower()
        if any(keyword.lower() in col_str for keyword in village_keywords):
            village_col = col
            break
    
    if village_col and village_col in df.columns:
        village_counts = df[village_col].value_counts()
        print(f"村庄列: {village_col}")
        print("各村分布:")
        for village, count in village_counts.items():
            percentage = (count / len(df)) * 100
            print(f"  {village}: {count} 人 ({percentage:.2f}%)")
        return village_counts
    else:
        print("未找到村庄相关列")
        return None

def create_visualizations(df):
    """创建可视化图表"""
    print("\n=== 生成可视化图表 ===")
    
    # 创建输出目录
    output_dir = "shujukufangzheli/charts"
    os.makedirs(output_dir, exist_ok=True)
    
    # 设置图表样式
    plt.style.use('seaborn-v0_8')
    
    # 1. 补贴情况分析
    subsidy_keywords = ['补贴', '补助', '费用', '津贴', '发放', '金额', '资金', '救助', '补贴金', '补助金']
    subsidy_cols = []
    
    for col in df.columns:
        col_str = str(col).lower()
        if any(keyword.lower() in col_str for keyword in subsidy_keywords):
            subsidy_cols.append(col)
    
    # 生成补贴分布图
    if subsidy_cols:
        plt.figure(figsize=(14, 8))
        
        # 统计每种补贴的获得人数
        subsidy_counts = []
        subsidy_names = []
        
        for col in subsidy_cols:
            count = df[col].notna().sum()
            if count > 0:  # 只统计有数据的补贴
                subsidy_counts.append(count)
                subsidy_names.append(col)
        
        if subsidy_names:
            bars = plt.bar(subsidy_names, subsidy_counts)
            plt.title('各类补贴获得人数统计', fontsize=16)
            plt.xlabel('补贴类型', fontsize=12)
            plt.ylabel('获得人数', fontsize=12)
            plt.xticks(rotation=45, ha='right')
            
            # 在柱子上显示数值
            for bar, count in zip(bars, subsidy_counts):
                plt.text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.1,
                        str(count), ha='center', va='bottom')
            
            plt.tight_layout()
            plt.savefig(os.path.join(output_dir, 'subsidy_distribution.png'), dpi=300, bbox_inches='tight')
            plt.close()
            print(f"已生成补贴分布图: {os.path.join(output_dir, 'subsidy_distribution.png')}")
    
    # 2. 性别分布饼图
    gender_col = None
    for col in df.columns:
        col_str = str(col).lower()
        if '性别' in col_str or 'gender' in col_str or 'sex' in col_str:
            gender_col = col
            break
    
    if gender_col and gender_col in df.columns:
        plt.figure(figsize=(8, 8))
        
        gender_counts = df[gender_col].value_counts()
        plt.pie(gender_counts.values, labels=gender_counts.index, autopct='%1.1f%%', startangle=90)
        plt.title(f'{gender_col}分布', fontsize=16)
        plt.tight_layout()
        plt.savefig(os.path.join(output_dir, 'gender_distribution.png'), dpi=300, bbox_inches='tight')
        plt.close()
        print(f"已生成性别分布图: {os.path.join(output_dir, 'gender_distribution.png')}")
    
    # 3. 年龄组分布
    age_group_counts = analyze_age_groups(df)  # 重新获取年龄组数据
    age_cols = []
    birth_cols = []
    
    for col in df.columns:
        col_str = str(col).lower()
        if '年龄' in col_str or 'age' in col_str:
            age_cols.append(col)
        elif '出生' in col_str or 'birth' in col_str or 'date' in col_str:
            birth_cols.append(col)
    
    if age_cols:
        age_col = age_cols[0]
        age_data = pd.to_numeric(df[age_col], errors='coerce')
        
        # 定义年龄组
        age_bins = [0, 18, 35, 60, 100]
        age_labels = ['儿童(0-18)', '青年(19-35)', '中年(36-60)', '老年(61+)']
        
        age_groups = pd.cut(age_data, bins=age_bins, labels=age_labels, right=False)
        age_group_counts = age_groups.value_counts()
        
        if not age_group_counts.empty:
            plt.figure(figsize=(10, 6))
            bars = plt.bar(age_group_counts.index, age_group_counts.values)
            plt.title('年龄组分布', fontsize=16)
            plt.xlabel('年龄组', fontsize=12)
            plt.ylabel('人数', fontsize=12)
            
            # 在柱子上显示数值
            for bar, count in zip(bars, age_group_counts.values):
                plt.text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.1,
                        str(count), ha='center', va='bottom')
            
            plt.tight_layout()
            plt.savefig(os.path.join(output_dir, 'age_group_distribution.png'), dpi=300, bbox_inches='tight')
            plt.close()
            print(f"已生成年龄组分布图: {os.path.join(output_dir, 'age_group_distribution.png')}")
    
    # 4. 各村分布
    village_keywords = ['村', '村名', '地址', '居住地', '住址', 'village', 'address']
    village_col = None
    
    for col in df.columns:
        col_str = str(col).lower()
        if any(keyword.lower() in col_str for keyword in village_keywords):
            village_col = col
            break
    
    if village_col and village_col in df.columns:
        village_counts = df[village_col].value_counts().head(10)  # 只显示前10个村庄
        
        plt.figure(figsize=(12, 8))
        bars = plt.bar(village_counts.index, village_counts.values)
        plt.title(f'前10个村庄人口分布 - {village_col}', fontsize=16)
        plt.xlabel('村庄', fontsize=12)
        plt.ylabel('人数', fontsize=12)
        plt.xticks(rotation=45, ha='right')
        
        # 在柱子上显示数值
        for bar, count in zip(bars, village_counts.values):
            plt.text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.1,
                    str(count), ha='center', va='bottom')
        
        plt.tight_layout()
        plt.savefig(os.path.join(output_dir, 'village_distribution.png'), dpi=300, bbox_inches='tight')
        plt.close()
        print(f"已生成村庄分布图: {os.path.join(output_dir, 'village_distribution.png')}")
    
    # 5. 数据概览
    plt.figure(figsize=(12, 8))
    df_count = len(df)
    plt.text(0.1, 0.9, f'总记录数: {df_count}', fontsize=16, transform=plt.gca().transAxes)
    plt.text(0.1, 0.8, f'总列数: {len(df.columns)}', fontsize=16, transform=plt.gca().transAxes)
    plt.text(0.1, 0.7, f'补贴类型数: {len(subsidy_cols)}', fontsize=16, transform=plt.gca().transAxes)
    plt.text(0.1, 0.6, f'补贴总人数: {sum(df[col].notna().sum() for col in subsidy_cols) if subsidy_cols else 0}', fontsize=16, transform=plt.gca().transAxes)
    
    # 显示性别分布
    if gender_col:
        gender_counts = df[gender_col].value_counts()
        if len(gender_counts) > 0:
            gender_text = "性别分布: "
            for gender, count in gender_counts.items():
                percentage = (count / len(df)) * 100
                gender_text += f"{gender}({count}人, {percentage:.1f}%) "
            plt.text(0.1, 0.5, gender_text, fontsize=14, transform=plt.gca().transAxes)
    
    plt.axis('off')
    plt.title('数据概览', fontsize=18)
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, 'data_overview.png'), dpi=300, bbox_inches='tight')
    plt.close()
    print(f"已生成数据概览图: {os.path.join(output_dir, 'data_overview.png')}")
    
    print(f"\n所有图表已保存到: {output_dir}")

def generate_detailed_report(df):
    """生成详细数据报告"""
    print("\n=== 生成详细数据报告 ===")
    
    output_dir = "shujukufangzheli/reports"
    os.makedirs(output_dir, exist_ok=True)
    
    report_file = os.path.join(output_dir, 'detailed_analysis_report.txt')
    
    with open(report_file, 'w', encoding='utf-8') as f:
        f.write(f"Excel数据分析详细报告\n")
        f.write(f"生成时间: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        f.write(f"数据源: allinone.csv\n\n")
        
        f.write(f"=== 数据概览 ===\n")
        f.write(f"- 总记录数: {len(df)}\n")
        f.write(f"- 总列数: {len(df.columns)}\n")
        f.write(f"- 内存使用: {df.memory_usage(deep=True).sum() / 1024**2:.2f} MB\n\n")
        
        f.write(f"=== 列名列表 ===\n")
        for i, col in enumerate(df.columns, 1):
            f.write(f"{i:3d}. {col}\n")
        f.write(f"\n")
        
        # 补贴分析
        subsidy_keywords = ['补贴', '补助', '费用', '津贴', '发放', '金额', '资金', '救助', '补贴金', '补助金']
        subsidy_cols = []
        
        for col in df.columns:
            col_str = str(col).lower()
            if any(keyword.lower() in col_str for keyword in subsidy_keywords):
                subsidy_cols.append(col)
        
        f.write(f"=== 补贴相关列分析 ===\n")
        for col in subsidy_cols:
            non_empty_count = df[col].notna().sum()
            numeric_values = pd.to_numeric(df[col], errors='coerce')
            total_amount = numeric_values.sum()
            avg_amount = numeric_values.mean()
            max_amount = numeric_values.max()
            min_amount = numeric_values.min()
            f.write(f"- {col}:\n")
            f.write(f"  * 获得补贴人数: {non_empty_count}\n")
            f.write(f"  * 总金额: {total_amount:.2f}\n")
            f.write(f"  * 平均金额: {avg_amount:.2f}\n")
            f.write(f"  * 最高金额: {max_amount:.2f}\n")
            f.write(f"  * 最低金额: {min_amount:.2f}\n")
        f.write(f"\n")
        
        # 性别分析
        gender_col = None
        for col in df.columns:
            col_str = str(col).lower()
            if '性别' in col_str or 'gender' in col_str or 'sex' in col_str:
                gender_col = col
                break
        
        if gender_col and gender_col in df.columns:
            f.write(f"=== 性别分布 ===\n")
            gender_counts = df[gender_col].value_counts()
            for gender, count in gender_counts.items():
                percentage = (count / len(df)) * 100
                f.write(f"- {gender}: {count} 人 ({percentage:.2f}%)\n")
            f.write(f"\n")
        
        # 年龄组分析
        age_cols = []
        for col in df.columns:
            col_str = str(col).lower()
            if '年龄' in col_str or 'age' in col_str:
                age_cols.append(col)
        
        if age_cols:
            age_col = age_cols[0]
            age_data = pd.to_numeric(df[age_col], errors='coerce')
            
            # 定义年龄组
            age_bins = [0, 18, 35, 60, 100]
            age_labels = ['儿童(0-18)', '青年(19-35)', '中年(36-60)', '老年(61+)']
            
            age_groups = pd.cut(age_data, bins=age_bins, labels=age_labels, right=False)
            age_group_counts = age_groups.value_counts()
            
            f.write(f"=== 年龄组分布 ===\n")
            for group, count in age_group_counts.items():
                percentage = (count / len(df)) * 100
                f.write(f"- {group}: {count} 人 ({percentage:.2f}%)\n")
            f.write(f"\n")
        
        # 村庄分析
        village_keywords = ['村', '村名', '地址', '居住地', '住址', 'village', 'address']
        village_col = None
        
        for col in df.columns:
            col_str = str(col).lower()
            if any(keyword.lower() in col_str for keyword in village_keywords):
                village_col = col
                break
        
        if village_col and village_col in df.columns:
            f.write(f"=== 村庄分布（前10个） ===\n")
            village_counts = df[village_col].value_counts().head(10)
            for village, count in village_counts.items():
                percentage = (count / len(df)) * 100
                f.write(f"- {village}: {count} 人 ({percentage:.2f}%)\n")
            f.write(f"\n")
        
        f.write(f"=== 数据质量分析 ===\n")
        missing_data = df.isnull().sum()
        missing_percent = 100 * missing_data / len(df)
        missing_table = pd.concat([missing_data, missing_percent], axis=1, keys=['缺失数量', '缺失百分比'])
        missing_table = missing_table[missing_table['缺失数量'] > 0].sort_values('缺失百分比', ascending=False)
        
        if not missing_table.empty:
            f.write(f"缺失数据列（按缺失率排序）:\n")
            for idx, row in missing_table.head(10).iterrows():
                f.write(f"- {idx}: {int(row['缺失数量'])} ({row['缺失百分比']:.2f}%)\n")
        else:
            f.write(f"无缺失数据\n")
        f.write(f"\n")
    
    print(f"详细数据报告已保存到: {report_file}")

def main():
    """主函数"""
    print("开始分析Excel数据...")
    
    # 加载数据
    df = load_data()
    if df is None:
        return
    
    # 分析补贴情况
    subsidy_cols = analyze_subsidies(df)
    
    # 分析年龄组
    age_group_counts = analyze_age_groups(df)
    
    # 分析性别
    gender_counts = analyze_gender(df)
    
    # 分析村庄分布
    village_counts = analyze_villages(df)
    
    # 生成可视化图表
    create_visualizations(df)
    
    # 生成详细数据报告
    generate_detailed_report(df)
    
    print("\n数据分析完成！")
    print("生成的文件:")
    print("- 图表保存在: shujukufangzheli/charts/")
    print("- 报告保存在: shujukufangzheli/reports/")

if __name__ == "__main__":
    main()