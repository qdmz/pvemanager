# Excel查询系统 - Python版

基于Flask和pandas的Excel文件查询系统，完全支持XLS和XLSX格式，包括中文文件名。

## 功能特性

- ✅ 支持XLS和XLSX格式文件
- ✅ 支持中文文件名
- ✅ 文件别名功能（显示名与实际文件名分离）
- ✅ 文件上传、下载、删除管理
- ✅ 管理员密码验证
- ✅ 修改密码功能
- ✅ 动态列名选择（根据选择的文件自动加载列名）
- ✅ 模糊查询支持
- ✅ 查询结果导出和打印
- ✅ 数据查询功能
- ✅ 响应式Web界面
- ✅ Docker一键部署
- ✅ Nginx反向代理

## 部署方式

### 方式一：Docker Compose（推荐）

1. **安装依赖**
   ```bash
   # 确保已安装 Docker 和 Docker Compose
   docker --version
   docker-compose --version
   ```

2. **启动服务**
   ```bash
   # 给部署脚本添加执行权限
   chmod +x deploy.sh
   
   # 运行部署脚本
   ./deploy.sh
   ```

3. **访问应用**
   ```
   http://localhost
   ```

### 方式二：本地部署

1. **安装Python依赖**
   ```bash
   pip install -r requirements.txt
   ```

2. **设置环境变量**
   ```bash
   export SECRET_KEY="your-very-secure-secret-key"
   ```

3. **创建上传目录**
   ```bash
   mkdir -p uploads
   ```

4. **启动应用**
   ```bash
   python app.py
   ```

## 目录结构

```
python_excel_query/
├── app.py              # 主应用文件
├── requirements.txt    # Python依赖
├── Dockerfile          # Docker构建文件
├── docker-compose.yml  # Docker Compose配置
├── nginx.conf          # Nginx配置
├── deploy.sh           # 部署脚本
├── README.md           # 说明文档
├── uploads/            # 上传文件目录
├── config/             # 配置文件目录
└── templates/          # HTML模板目录
```

## 环境变量

- `SECRET_KEY`: Flask应用密钥（必需）
- `FLASK_ENV`: 环境模式（development/production）

## Docker Compose服务

- **excel-query**: Flask应用服务
- **nginx**: Nginx反向代理服务

## 端口映射

- 容器内端口: 5000
- 外部访问端口: 80

## 数据持久化

- 上传的Excel文件存储在 `./uploads` 目录
- 配置文件存储在 `./config` 目录

## 管理命令

```bash
# 查看服务状态
docker-compose ps

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down

# 重启服务
docker-compose restart

# 构建并启动（重新构建镜像）
docker-compose up -d --build
```

## 安全建议

1. 生产环境中请使用强密钥替换默认SECRET_KEY
2. 配置SSL证书以启用HTTPS
3. 限制文件上传大小和类型
4. 定期备份上传的文件

## 技术栈

- **后端**: Python 3.9 + Flask
- **数据处理**: pandas + openpyxl + xlrd
- **Web服务器**: Nginx
- **容器化**: Docker + Docker Compose
- **前端**: HTML + CSS

## 支持格式

- **XLS**: 通过xlrd库支持
- **XLSX**: 通过openpyxl库支持
- **文件大小限制**: 100MB（可在nginx.conf中调整）

## 故障排除

1. **容器启动失败**
   - 检查Docker和Docker Compose是否正确安装
   - 查看容器日志: `docker-compose logs`

2. **无法访问应用**
   - 检查端口是否被占用
   - 确认防火墙设置

3. **Excel文件读取失败**
   - 检查文件格式是否正确
   - 确认文件没有被其他程序占用