# QDMZ 部署文档

## 项目概述
QDMZ 是一个支持 XLS 和 XLSX 格式的 Excel 查询系统，具有现代化界面和完整的后台管理功能。

## 系统要求
- PHP 7.0 或更高版本
- 启用以下 PHP 扩展：
  - mbstring
  - xml
  - zip
  - json
  - gd

## 部署步骤

### 1. 文件上传
将所有项目文件上传到您的 Web 服务器目录。

### 2. 目录权限设置
确保以下目录具有写权限：
- `shujukufangzheli/` (用于存放 Excel 数据文件)
- 项目根目录 (用于配置文件修改)

### 3. 依赖安装
项目已包含 PhpSpreadsheet 库，无需额外安装。

### 4. 配置
- 默认管理员账户：admin
- 默认管理员密码：admin123 (请在首次登录后修改)

### 5. 访问
- 前台查询页面：`http://yourdomain.com/`
- 后台管理页面：`http://yourdomain.com/admin.php`

## 功能特性
- 支持 XLS 和 XLSX 格式文件
- 前台数据查询功能
- 后台文件管理（上传、下载、删除）
- 后台密码修改功能
- 验证码保护
- 响应式界面设计