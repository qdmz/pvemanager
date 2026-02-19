#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Excel查询系统 - Python版本
功能与PHP版本完全一致
支持XLS和XLSX格式，支持中文文件名
"""

import os
import sys
from flask import Flask, render_template, request, redirect, url_for, flash, jsonify, session
import pandas as pd
from werkzeug.utils import secure_filename
import hashlib
from datetime import datetime
import json
import logging
from logging.handlers import RotatingFileHandler
from functools import wraps

# 创建Flask应用
app = Flask(__name__)

# 从环境变量获取密钥，如果不存在则使用默认值（生产环境中应设置）
app.secret_key = os.environ.get('SECRET_KEY', 'dev-secret-key-change-in-production')

# 配置
class Config:
    UPLOAD_FOLDER = os.environ.get('UPLOAD_FOLDER', 'uploads')
    MAX_CONTENT_LENGTH = 100 * 1024 * 1024  # 100MB max file size
    ALLOWED_EXTENSIONS = {'xls', 'xlsx'}
    ADMIN_PASSWORD = os.environ.get('ADMIN_PASSWORD', 'admin123')  # 默认密码，生产环境应修改

app.config.from_object(Config)

# 确保上传目录存在
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

def login_required(f):
    """管理员登录装饰器"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not session.get('admin_logged_in'):
            return redirect(url_for('admin_login'))
        return f(*args, **kwargs)
    return decorated_function

def allowed_file(filename):
    """检查文件扩展名是否允许"""
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in app.config['ALLOWED_EXTENSIONS']

def get_excel_files():
    """获取上传目录中的所有Excel文件"""
    files = []
    try:
        for f in os.listdir(app.config['UPLOAD_FOLDER']):
            if allowed_file(f):
                # 读取别名信息
                original_name = f
                alias = get_file_alias(f)
                files.append({
                    'filename': f,
                    'original_name': original_name,
                    'alias': alias if alias else original_name
                })
    except Exception as e:
        app.logger.error(f"Error reading upload directory: {e}")
    return files

def get_file_alias(filename):
    """获取文件别名"""
    alias_file = os.path.join(app.config['UPLOAD_FOLDER'], f"{filename}.alias")
    if os.path.exists(alias_file):
        with open(alias_file, 'r', encoding='utf-8') as f:
            return f.read().strip()
    return None

def set_file_alias(filename, alias):
    """设置文件别名"""
    alias_file = os.path.join(app.config['UPLOAD_FOLDER'], f"{filename}.alias")
    with open(alias_file, 'w', encoding='utf-8') as f:
        f.write(alias)

def get_excel_columns(filepath):
    """获取Excel文件的列名"""
    try:
        df = pd.read_excel(filepath, sheet_name=0, header=0)
        return list(df.columns)
    except Exception as e:
        app.logger.error(f"Error reading Excel columns {filepath}: {e}")
        return []

def query_excel_data(filepath, search_column, search_value, fuzzy=False):
    """
    查询Excel数据
    :param filepath: Excel文件路径
    :param search_column: 搜索列名
    :param search_value: 搜索值
    :param fuzzy: 是否模糊查询
    :return: 查询结果
    """
    try:
        # 使用pandas读取Excel文件
        df = pd.read_excel(filepath, sheet_name=0, header=0)
        
        # 查找搜索列的索引
        col_idx = -1
        for i, col in enumerate(df.columns):
            if str(col).strip() == str(search_column):
                col_idx = i
                break
        
        if col_idx == -1:
            return {"error": f"未找到列: {search_column}"}
        
        # 查找匹配的行
        results = []
        for idx, row in df.iterrows():
            cell_value = str(row[col_idx]).strip()
            search_val = str(search_value).strip()
            
            if fuzzy:
                # 模糊匹配
                if search_val.lower() in cell_value.lower():
                    result_row = {}
                    for i, col in enumerate(df.columns):
                        result_row[str(col)] = str(row[i]) if pd.notna(row[i]) else ""
                    results.append(result_row)
            else:
                # 精确匹配
                if cell_value == search_val:
                    result_row = {}
                    for i, col in enumerate(df.columns):
                        result_row[str(col)] = str(row[i]) if pd.notna(row[i]) else ""
                    results.append(result_row)
        
        return {"success": True, "data": results, "total": len(results)}
    except Exception as e:
        app.logger.error(f"Error querying Excel file {filepath}: {e}")
        return {"error": f"查询文件时出错: {str(e)}"}

@app.route('/')
def index():
    """首页 - 显示查询界面"""
    excel_files = get_excel_files()
    return render_template('index.html', excel_files=excel_files)

@app.route('/get_columns/<filename>')
def get_columns(filename):
    """获取文件列名的API"""
    # 防止路径遍历攻击
    if '..' in filename or filename.startswith('/'):
        return jsonify({"error": "Invalid filename"})
    
    filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
    if not os.path.exists(filepath):
        return jsonify({"error": "File not found"})
    
    columns = get_excel_columns(filepath)
    return jsonify({"columns": columns})

@app.route('/query', methods=['POST'])
def query():
    """处理查询请求"""
    try:
        excel_file = request.form.get('excel_file')
        search_column = request.form.get('search_column')
        search_value = request.form.get('search_value')
        fuzzy_search = request.form.get('fuzzy_search') == 'on'
        
        if not excel_file or not search_column or not search_value:
            flash('请填写所有查询条件')
            return redirect(url_for('index'))
        
        filepath = os.path.join(app.config['UPLOAD_FOLDER'], excel_file)
        if not os.path.exists(filepath):
            flash('文件不存在')
            return redirect(url_for('index'))
        
        result = query_excel_data(filepath, search_column, search_value, fuzzy_search)
        
        if "error" in result:
            flash(f'查询错误: {result["error"]}')
            return redirect(url_for('index'))
        
        # 获取文件别名用于显示
        file_alias = get_file_alias(excel_file)
        display_name = file_alias if file_alias else excel_file
        
        return render_template('results.html', 
                             results=result["data"], 
                             total=result["total"],
                             search_column=search_column,
                             search_value=search_value,
                             excel_file=excel_file,
                             display_name=display_name,
                             fuzzy_search=fuzzy_search)
    except Exception as e:
        app.logger.error(f"Query error: {e}")
        flash(f'查询出错: {str(e)}')
        return redirect(url_for('index'))

@app.route('/admin')
@login_required
def admin():
    """管理界面"""
    excel_files = get_excel_files()
    return render_template('admin.html', excel_files=excel_files)

@app.route('/admin/login', methods=['GET', 'POST'])
def admin_login():
    """管理员登录"""
    if request.method == 'POST':
        password = request.form.get('password')
        if password == app.config['ADMIN_PASSWORD']:
            session['admin_logged_in'] = True
            return redirect(url_for('admin'))
        else:
            flash('密码错误')
    return render_template('login.html')

@app.route('/admin/logout')
def admin_logout():
    """管理员登出"""
    session.pop('admin_logged_in', None)
    return redirect(url_for('index'))

@app.route('/admin/change_password', methods=['GET', 'POST'])
@login_required
def change_password():
    """修改密码"""
    if request.method == 'POST':
        old_password = request.form.get('old_password')
        new_password = request.form.get('new_password')
        confirm_password = request.form.get('confirm_password')
        
        if old_password != app.config['ADMIN_PASSWORD']:
            flash('原密码错误')
            return render_template('change_password.html')
        
        if new_password != confirm_password:
            flash('新密码与确认密码不匹配')
            return render_template('change_password.html')
        
        if len(new_password) < 6:
            flash('新密码长度至少6位')
            return render_template('change_password.html')
        
        # 更新密码（这里只是更新内存中的配置，实际应用中应持久化到配置文件或数据库）
        app.config['ADMIN_PASSWORD'] = new_password
        flash('密码修改成功')
        return redirect(url_for('admin'))
    
    return render_template('change_password.html')

@app.route('/upload', methods=['POST'])
@login_required
def upload_file():
    """上传文件"""
    if 'file' not in request.files:
        flash('没有选择文件')
        return redirect(url_for('admin'))
    
    file = request.files['file']
    if file.filename == '':
        flash('没有选择文件')
        return redirect(url_for('admin'))
    
    if file and allowed_file(file.filename):
        # 保留原始文件名（支持中文）
        original_filename = file.filename
        # 使用secure_filename处理文件名，但保留中文字符
        safe_filename = secure_filename(original_filename)
        if not safe_filename:  # 如果secure_filename返回空字符串，使用原文件名
            safe_filename = original_filename
        
        filepath = os.path.join(app.config['UPLOAD_FOLDER'], safe_filename)
        
        # 如果文件已存在，添加时间戳
        if os.path.exists(filepath):
            name, ext = os.path.splitext(safe_filename)
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            safe_filename = f"{name}_{timestamp}{ext}"
            filepath = os.path.join(app.config['UPLOAD_FOLDER'], safe_filename)
        
        file.save(filepath)
        
        # 设置文件别名（使用原始文件名）
        set_file_alias(safe_filename, original_filename)
        
        flash('文件上传成功')
    else:
        flash('不支持的文件格式，请上传XLS或XLSX文件')
    
    return redirect(url_for('admin'))

@app.route('/update_alias/<filename>', methods=['POST'])
@login_required
def update_alias(filename):
    """更新文件别名"""
    # 防止路径遍历攻击
    if '..' in filename or filename.startswith('/'):
        flash('无效的文件名')
        return redirect(url_for('admin'))
    
    new_alias = request.form.get('alias')
    if new_alias:
        set_file_alias(filename, new_alias)
        flash('文件别名更新成功')
    else:
        flash('别名不能为空')
    
    return redirect(url_for('admin'))

@app.route('/delete/<filename>')
@login_required
def delete_file(filename):
    """删除文件"""
    # 防止路径遍历攻击
    if '..' in filename or filename.startswith('/'):
        flash('无效的文件名')
        return redirect(url_for('admin'))
    
    filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
    if os.path.exists(filepath):
        try:
            os.remove(filepath)
            # 删除对应的别名文件
            alias_file = os.path.join(app.config['UPLOAD_FOLDER'], f"{filename}.alias")
            if os.path.exists(alias_file):
                os.remove(alias_file)
            flash('文件删除成功')
        except Exception as e:
            app.logger.error(f"Error deleting file {filepath}: {e}")
            flash('删除文件时出错')
    else:
        flash('文件不存在')
    return redirect(url_for('admin'))

@app.route('/download/<filename>')
@login_required
def download_file(filename):
    """下载文件"""
    # 防止路径遍历攻击
    if '..' in filename or filename.startswith('/'):
        flash('无效的文件名')
        return redirect(url_for('admin'))
    
    filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
    if os.path.exists(filepath):
        from flask import send_file
        return send_file(filepath, as_attachment=True)
    else:
        flash('文件不存在')
        return redirect(url_for('admin'))

def setup_logging():
    """设置日志"""
    if not app.debug:
        if not os.path.exists('logs'):
            os.mkdir('logs')
        
        file_handler = RotatingFileHandler('logs/excel_query.log', maxBytes=10240000, backupCount=10)
        file_handler.setFormatter(logging.Formatter(
            '%(asctime)s %(levelname)s: %(message)s [in %(pathname)s:%(lineno)d]'
        ))
        file_handler.setLevel(logging.INFO)
        app.logger.addHandler(file_handler)
        
        app.logger.setLevel(logging.INFO)
        app.logger.info('Excel Query System startup')

if __name__ == '__main__':
    # 设置日志
    setup_logging()
    
    # 创建模板目录
    os.makedirs('templates', exist_ok=True)
    
    # 检查是否在Docker环境中运行
    if os.environ.get('FLASK_ENV') == 'production':
        # 生产环境配置
        app.config['TEMPLATES_AUTO_RELOAD'] = False
    else:
        # 开发环境配置
        app.config['TEMPLATES_AUTO_RELOAD'] = True
    
    # 创建基本模板（如果不存在）
    templates = {
        'index.html': '''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Excel查询系统</title>
    <style>
        body {
            font-family: "microsoft yahei", "微软雅黑", Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #0180CF;
            border-bottom: 2px solid #0180CF;
            padding-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        select, input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            background: #0180CF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #015a99;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Excel查询系统</h1>
        </div>
        
        {% with messages = get_flashed_messages() %}
            {% if messages %}
                {% for message in messages %}
                    <div class="message error">{{ message }}</div>
                {% endfor %}
            {% endif %}
        {% endwith %}
        
        <form method="post" action="{{ url_for('query') }}">
            <div class="form-group">
                <label for="excel_file">选择Excel文件:</label>
                <select name="excel_file" id="excel_file" required onchange="loadColumns(this.value)">
                    <option value="">请选择文件</option>
                    {% for file in excel_files %}
                    <option value="{{ file.filename }}">{{ file.alias }}</option>
                    {% endfor %}
                </select>
            </div>
            
            <div class="form-group">
                <label for="search_column">查询列名:</label>
                <select name="search_column" id="search_column" required>
                    <option value="">请选择列名</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="search_value">查询值:</label>
                <input type="text" name="search_value" id="search_value" placeholder="请输入要查询的值" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="fuzzy_search" id="fuzzy_search">
                <label for="fuzzy_search">模糊查询</label>
            </div>
            
            <button type="submit" class="btn">查询</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="{{ url_for('admin') }}" class="btn">管理文件</a>
        </p>
    </div>
    
    <script>
        function loadColumns(filename) {
            const columnSelect = document.getElementById('search_column');
            columnSelect.innerHTML = '<option value="">加载中...</option>';
            
            if (filename) {
                fetch('/get_columns/' + encodeURIComponent(filename))
                    .then(response => response.json())
                    .then(data => {
                        columnSelect.innerHTML = '<option value="">请选择列名</option>';
                        if (data.columns) {
                            data.columns.forEach(column => {
                                const option = document.createElement('option');
                                option.value = column;
                                option.textContent = column;
                                columnSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        columnSelect.innerHTML = '<option value="">加载失败</option>';
                    });
            } else {
                columnSelect.innerHTML = '<option value="">请选择文件</option>';
            }
        }
    </script>
</body>
</html>''',
        'results.html': '''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>查询结果</title>
    <style>
        body {
            font-family: "microsoft yahei", "微软雅黑", Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #0180CF;
            border-bottom: 2px solid #0180CF;
            padding-bottom: 15px;
        }
        .result-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #0180CF;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .btn {
            background: #0180CF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #015a99;
        }
        .btn-print {
            background: #28a745;
        }
        .btn-print:hover {
            background: #218838;
        }
        .btn-export {
            background: #ffc107;
            color: #212529;
        }
        .btn-export:hover {
            background: #e0a800;
        }
        .result-actions {
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>查询结果</h1>
        </div>
        
        <div class="result-info">
            <p>在文件 <strong>{{ display_name }}</strong> 中查询 <strong>{{ search_column }}</strong> = <strong>{{ search_value }}</strong>
            {% if fuzzy_search %}(模糊查询){% endif %}</p>
            <p>共找到 <strong>{{ total }}</strong> 条记录</p>
        </div>
        
        {% if results %}
        <div class="result-actions">
            <button class="btn btn-print" onclick="window.print()">打印结果</button>
            <button class="btn btn-export" onclick="exportResults()">导出结果</button>
        </div>
        
        <table id="results-table">
            <thead>
                <tr>
                    {% for key in results[0].keys() %}
                    <th>{{ key }}</th>
                    {% endfor %}
                </tr>
            </thead>
            <tbody>
                {% for row in results %}
                <tr>
                    {% for key in row.keys() %}
                    <td>{{ row[key] }}</td>
                    {% endfor %}
                </tr>
                {% endfor %}
            </tbody>
        </table>
        {% else %}
        <p>未找到匹配的记录</p>
        {% endif %}
        
        <p>
            <a href="{{ url_for('index') }}" class="btn">返回查询</a>
        </p>
    </div>
    
    <script>
        function exportResults() {
            // 创建CSV内容
            const table = document.getElementById('results-table');
            let csv = '';
            
            // 添加表头
            const headers = table.querySelectorAll('thead th');
            for (let i = 0; i < headers.length; i++) {
                csv += '"' + headers[i].innerText + '"';
                if (i < headers.length - 1) csv += ',';
            }
            csv += '\\n';
            
            // 添加数据行
            const rows = table.querySelectorAll('tbody tr');
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('td');
                for (let j = 0; j < cells.length; j++) {
                    csv += '"' + cells[j].innerText + '"';
                    if (j < cells.length - 1) csv += ',';
                }
                csv += '\\n';
            }
            
            // 创建并下载CSV文件
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'query_results.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>''',
        'admin.html': '''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>文件管理</title>
    <style>
        body {
            font-family: "microsoft yahei", "微软雅黑", Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #0180CF;
            border-bottom: 2px solid #0180CF;
            padding-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="file"], input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            background: #0180CF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #015a99;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .file-list {
            margin-top: 20px;
        }
        .file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-info {
            flex: 1;
            min-width: 200px;
        }
        .file-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .alias-form {
            margin-top: 5px;
            display: flex;
            gap: 5px;
        }
        .alias-form input {
            flex: 1;
            min-width: 150px;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .logout {
            text-align: right;
            margin-bottom: 20px;
        }
        .password-change {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>文件管理</h1>
        </div>
        
        <div class="logout">
            <a href="{{ url_for('admin_logout') }}" class="btn btn-danger">退出登录</a>
            <a href="{{ url_for('change_password') }}" class="btn btn-warning">修改密码</a>
        </div>
        
        {% with messages = get_flashed_messages() %}
            {% if messages %}
                {% for message in messages %}
                    <div class="message {% if '成功' in message %}success{% else %}error{% endif %}">{{ message }}</div>
                {% endfor %}
            {% endif %}
        {% endwith %}
        
        <h3>上传Excel文件</h3>
        <form method="post" action="{{ url_for('upload_file') }}" enctype="multipart/form-data">
            <div class="form-group">
                <label for="file">选择Excel文件 (XLS/XLSX):</label>
                <input type="file" id="file" name="file" accept=".xls,.xlsx" required>
            </div>
            <button type="submit" class="btn">上传文件</button>
        </form>
        
        <div class="file-list">
            <h3>已上传文件</h3>
            {% if excel_files %}
                {% for file in excel_files %}
                <div class="file-item">
                    <div class="file-info">
                        <div><strong>原始名:</strong> {{ file.original_name }}</div>
                        <div><strong>显示名:</strong> {{ file.alias }}</div>
                        <form method="post" action="{{ url_for('update_alias', filename=file.filename) }}" class="alias-form">
                            <input type="text" name="alias" value="{{ file.alias }}" placeholder="输入文件别名">
                            <button type="submit" class="btn btn-success">更新</button>
                        </form>
                    </div>
                    <div class="file-actions">
                        <a href="{{ url_for('download_file', filename=file.filename) }}" class="btn">下载</a>
                        <a href="{{ url_for('delete_file', filename=file.filename) }}" class="btn btn-danger" 
                           onclick="return confirm('确定要删除文件 {{ file.original_name }} 吗？')">删除</a>
                    </div>
                </div>
                {% endfor %}
            {% else %}
                <p>暂无Excel文件</p>
            {% endif %}
        </div>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="{{ url_for('index') }}" class="btn">返回首页</a>
        </p>
    </div>
</body>
</html>''',
        'login.html': '''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>管理员登录</title>
    <style>
        body {
            font-family: "microsoft yahei", "微软雅黑", Arial, sans-serif;
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #0180CF;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            background: #0180CF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #015a99;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>管理员登录</h1>
        </div>
        
        {% with messages = get_flashed_messages() %}
            {% if messages %}
                {% for message in messages %}
                    <div class="message error">{{ message }}</div>
                {% endfor %}
            {% endif %}
        {% endwith %}
        
        <form method="post">
            <div class="form-group">
                <label for="password">密码:</label>
                <input type="password" name="password" id="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn">登录</button>
        </form>
    </div>
</body>
</html>''',
        'change_password.html': '''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>修改密码</title>
    <style>
        body {
            font-family: "microsoft yahei", "微软雅黑", Arial, sans-serif;
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #0180CF;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            background: #0180CF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #015a99;
        }
        .btn-back {
            background: #6c757d;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>修改密码</h1>
        </div>
        
        {% with messages = get_flashed_messages() %}
            {% if messages %}
                {% for message in messages %}
                    <div class="message {% if '成功' in message %}success{% else %}error{% endif %}">{{ message }}</div>
                {% endfor %}
            {% endif %}
        {% endwith %}
        
        <form method="post">
            <div class="form-group">
                <label for="old_password">原密码:</label>
                <input type="password" name="old_password" id="old_password" placeholder="请输入原密码" required>
            </div>
            <div class="form-group">
                <label for="new_password">新密码:</label>
                <input type="password" name="new_password" id="new_password" placeholder="请输入新密码(至少6位)" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">确认新密码:</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="请再次输入新密码" required>
            </div>
            <button type="submit" class="btn">修改密码</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ url_for('admin') }}" class="btn btn-back">返回管理</a>
        </div>
    </div>
</body>
</html>'''
    }
    
    for filename, content in templates.items():
        filepath = os.path.join('templates', filename)
        if not os.path.exists(filepath):
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
    
    # 根据环境变量决定运行模式
    if os.environ.get('FLASK_ENV') == 'production':
        # 生产环境：监听所有接口
        app.run(host='0.0.0.0', port=5000, debug=False)
    else:
        # 开发环境
        app.run(debug=True, host='0.0.0.0', port=5000)