# QDMZ - Excel 查询系统

QDMZ 是一个功能强大的 Excel 查询系统，支持 XLS 和 XLSX 格式文件，具有现代化的用户界面和完整的后台管理功能。

## 功能特性

- ✅ 支持 XLS 和 XLSX 格式文件
- ✅ 前台数据查询功能
- ✅ 后台文件管理（上传、下载、删除）
- ✅ 后台密码修改功能
- ✅ 后台文件重命名功能
- ✅ 文件名验证和清理功能
- ✅ 验证码保护
- ✅ 响应式界面设计
- ✅ PHP 7+ 兼容
- ✅ UTF-8 编码，支持中文

## 系统要求

- PHP 7.0 或更高版本
- 启用以下 PHP 扩展：
  - mbstring
  - xml
  - zip
  - json
  - gd

## 安装部署

### 方法一：直接部署
1. 将所有文件上传到您的 Web 服务器
2. 确保 `shujukufangzheli` 目录具有写权限
3. 访问 `http://yourdomain.com/` 开始使用

### 方法二：使用部署脚本
1. 上传文件到服务器
2. 运行 `http://yourdomain.com/deploy.php` 进行环境检查
3. 根据提示完成部署

## 默认配置

- 管理员账户：`admin`
- 管理员密码：`admin123` (请在首次登录后修改)

## 使用说明

### 前台查询
1. 访问首页
2. 选择要查询的 Excel 文件
3. 输入查询条件
4. 点击查询按钮

### 后台管理
1. 访问 `admin.php`
2. 使用管理员账户登录
3. 可进行以下操作：
   - 上传 Excel 文件
   - 下载 Excel 文件
   - 删除 Excel 文件
   - 修改管理员密码

## 文件结构

```
qdmz/
├── index.php           # 前台查询页面
├── admin.php           # 后台管理页面
├── inc/                # 包含文件目录
│   ├── conn.php        # 配置文件
│   ├── excel_reader.php # Excel 读取器
│   ├── code.php        # 验证码
│   ├── excel.php       # 旧版 Excel 读取器
│   ├── css/            # 样式文件
│   └── js/             # JavaScript 文件
├── vendor/             # 依赖库目录
│   ├── autoload.php    # 自动加载器
│   └── phpoffice/      # PhpSpreadsheet 库
├── shujukufangzheli/   # Excel 数据文件目录
├── deploy.php          # 部署检查脚本
└── DEPLOY.md           # 部署文档
```

## 技术栈

- PHP 7+
- PhpSpreadsheet (处理 XLSX 文件)
- 原生 PHP Excel 读取器 (处理 XLS 文件)
- HTML/CSS/JavaScript
- 响应式设计

## 许可证

此项目仅供学习和参考使用。

## 更新日志

### v2.0
- 新增 XLSX 文件支持
- 重构 Excel 读取器
- 添加后台管理功能
- 优化用户界面
- 修复多项 bug

### v1.0
- 基础 XLS 文件查询功能
- 验证码保护
- 简单管理功能