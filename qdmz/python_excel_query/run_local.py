#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
本地运行脚本
用于在本地环境中启动Excel查询系统
"""

import os
import sys
import subprocess

def check_python_version():
    """检查Python版本"""
    if sys.version_info < (3, 6):
        print("错误: 需要Python 3.6或更高版本")
        return False
    return True

def install_dependencies():
    """安装Python依赖"""
    print("正在安装Python依赖...")
    
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"])
        print("✓ 依赖安装成功")
        return True
    except subprocess.CalledProcessError:
        print("✗ 依赖安装失败")
        return False

def create_directories():
    """创建必要的目录"""
    print("创建目录结构...")
    
    os.makedirs("uploads", exist_ok=True)
    os.makedirs("templates", exist_ok=True)
    os.makedirs("logs", exist_ok=True)
    
    print("✓ 目录结构已创建")

def run_app():
    """运行应用"""
    print("\n启动Excel查询系统...")
    print("请访问 http://localhost:5000")
    print("按 Ctrl+C 停止服务\n")
    
    # 导入并运行app
    try:
        from app import app
        app.run(debug=True, host='127.0.0.1', port=5000)
    except ImportError as e:
        print(f"导入应用失败: {e}")
        return False
    except Exception as e:
        print(f"运行应用时出错: {e}")
        return False
    
    return True

def main():
    """主函数"""
    print("Excel查询系统 - 本地部署")
    print("="*40)
    
    # 检查Python版本
    if not check_python_version():
        return
    
    # 创建目录
    create_directories()
    
    # 安装依赖
    if not install_dependencies():
        print("\n请手动安装依赖: pip install -r requirements.txt")
        return
    
    # 运行应用
    run_app()

if __name__ == "__main__":
    main()