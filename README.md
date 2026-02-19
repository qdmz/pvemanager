# PVE Manager - Proxmox VE 管理系统

基于 Rust 的 Proxmox VE 虚拟化平台管理系统，提供现代化的 Web 管理界面和完整的 API。

## ✨ 功能特性

### 核心功能
- 🔐 **用户认证与权限管理** - JWT + RBAC 权限控制，支持管理员和普通用户角色
- 🖥️ **虚拟机全生命周期管理** - 创建、启动、停止、重启、暂停、删除虚拟机
- 📊 **实时资源监控** - CPU、内存、磁盘、网络实时监控仪表板
- 🔥 **防火墙规则管理** - 配置入站/出站防火墙规则
- 📝 **操作日志审计** - 完整的操作审计日志记录
- 💾 **快照管理** - 创建和管理虚拟机快照
- 🌐 **现代化 Web 界面** - 响应式设计，深色主题，流畅动画

### 技术特点
- ⚡ 高性能 Rust 后端
- 🛡️ 类型安全的 Rust + TypeScript
- 🔄 RESTful API 设计
- 📦 Docker 容器化部署
- 🎨 现代化 UI 设计

## 🛠️ 技术栈

### 后端
- **Rust** 1.75+ - 高性能系统编程语言
- **Axum** - 现代化 Web 框架
- **SQLx** - 异步 SQL 工具包
- **PostgreSQL** - 关系型数据库
- **JWT** - 用户认证
- **Reqwest** - HTTP 客户端（PVE API 集成）

### 前端
- **HTML5 + CSS3 + Vanilla JavaScript**
- **响应式设计** - 支持桌面和移动设备
- **深色主题** - 现代化视觉效果
- **动画效果** - 流畅的用户体验

## 📋 系统要求

- Docker 20.10+
- Docker Compose 2.0+
- 或 Rust 1.75+ (本地开发)

## 🚀 快速开始

### 使用 Docker (推荐)

1. **克隆项目**
```bash
git clone <repository-url>
cd pve-manager
```

2. **配置环境**
```bash
cp .env.example .env
# 编辑 .env 文件，配置 PVE 服务器信息
```

3. **启动服务**

Windows:
```cmd
start.bat
```

Linux/Mac:
```bash
chmod +x start.sh
./start.sh
```

或使用 Docker Compose:
```bash
docker-compose up -d
```

4. **访问 Web 界面**
```
http://localhost:8080
```

默认登录凭证:
- 邮箱: `admin@pve.local`
- 密码: `admin123`

### 本地开发

1. **安装依赖**
```bash
# 安装 Rust
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh

# 安装 PostgreSQL
# 根据你的操作系统安装 PostgreSQL 15
```

2. **创建数据库**
```bash
createdb pve_manager
```

3. **配置环境变量**
```bash
export PVE_DATABASE_URL="postgresql://postgres:password@localhost/pve_manager"
export PVE_JWT_SECRET="your-secret-key"
# 设置其他环境变量
```

4. **运行服务器**
```bash
cd server
cargo run
```

## 📁 项目结构

```
pve-manager/
├── server/                 # 后端服务
│   ├── src/
│   │   ├── main.rs        # 主入口
│   │   ├── config.rs      # 配置管理
│   │   ├── db.rs          # 数据库连接
│   │   ├── handlers/      # API 处理器
│   │   ├── services/      # 业务逻辑
│   │   ├── middleware.rs  # 中间件
│   │   └── pve_client.rs  # PVE API 客户端
│   ├── migrations/        # 数据库迁移
│   └── Cargo.toml
├── shared/                # 共享代码
│   └── src/
│       ├── models.rs      # 数据模型
│       ├── dtos.rs        # 数据传输对象
│       └── error.rs       # 错误处理
├── static/                # 前端静态文件
│   ├── index.html
│   ├── css/
│   │   └── styles.css
│   └── js/
│       └── app.js
├── config/                # 配置文件
├── docker-compose.yml
├── Dockerfile
├── Cargo.toml
└── README.md
```

## 🔧 配置说明

### 环境变量

| 变量名 | 说明 | 默认值 |
|--------|------|--------|
| `PVE_HOST` | 服务器监听地址 | `0.0.0.0` |
| `PVE_PORT` | 服务器端口 | `8080` |
| `PVE_DATABASE_URL` | PostgreSQL 连接字符串 | - |
| `PVE_JWT_SECRET` | JWT 签名密钥 | - |
| `PVE_PVE_URL` | PVE 服务器 URL | - |
| `PVE_PVE_USERNAME` | PVE 用户名 | - |
| `PVE_PVE_PASSWORD` | PVE 密码 | - |
| `PVE_PVE_REALM` | PVE 认证域 | `pam` |

## 📖 API 文档

### 认证接口

#### 用户登录
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@pve.local",
  "password": "admin123"
}
```

#### 用户注册
```
POST /api/auth/register
Content-Type: application/json

{
  "username": "newuser",
  "email": "user@example.com",
  "password": "password123"
}
```

### 虚拟机接口

#### 获取虚拟机列表
```
GET /api/vms
Authorization: Bearer {token}
```

#### 创建虚拟机
```
POST /api/vms
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "my-vm",
  "cpu_cores": 2,
  "memory_mb": 2048,
  "disk_gb": 50,
  "node": "node1"
}
```

#### 虚拟机操作
```
POST /api/vms/{id}/action
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "start|stop|restart|pause|unpause"
}
```

#### 创建快照
```
POST /api/vms/{id}/snapshots
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "snapshot-name",
  "description": "Optional description"
}
```

### 监控接口

#### 获取系统统计
```
GET /api/stats/system
Authorization: Bearer {token}
```

### 防火墙接口

#### 获取防火墙规则
```
GET /api/vms/{id}/firewall
Authorization: Bearer {token}
```

#### 创建防火墙规则
```
POST /api/vms/{id}/firewall
Authorization: Bearer {token}
Content-Type: application/json

{
  "direction": "inbound",
  "action": "accept",
  "protocol": "TCP",
  "port": 22,
  "source": "0.0.0.0/0"
}
```

### 审计日志接口

#### 获取操作日志
```
GET /api/audit-logs?limit=50&offset=0
Authorization: Bearer {token}
```

## 🎨 功能截图

### 控制台仪表板
- 实时 CPU、内存、磁盘、网络监控
- 快速操作入口
- 系统信息概览

### 虚拟机管理
- 虚拟机列表展示
- 启动、停止、重启、暂停操作
- 快照管理
- 配置修改

### 防火墙管理
- 规则列表展示
- 规则创建、编辑、删除
- 入站/出出站规则配置

### 操作日志
- 操作历史记录
- 用户操作审计
- 时间线展示

## 🔐 安全建议

1. **修改默认密码** - 首次登录后请立即修改默认管理员密码
2. **更改 JWT Secret** - 生产环境中使用强随机密钥
3. **使用 HTTPS** - 生产环境部署时配置 SSL 证书
4. **网络安全** - 限制数据库和 PVE API 的网络访问
5. **定期备份** - 定期备份数据库和配置文件

## 🐛 故障排除

### 数据库连接失败
```bash
# 检查 PostgreSQL 是否运行
docker-compose ps postgres

# 查看日志
docker-compose logs postgres
```

### 服务器启动失败
```bash
# 查看服务器日志
docker-compose logs server

# 重新构建
docker-compose build server
docker-compose up -d server
```

### PVE API 连接失败
- 检查 PVE 服务器 URL 是否正确
- 确认 PVE 用户名和密码
- 验证网络连接和防火墙规则

## 📝 开发计划

- [ ] WebSocket 实时通信支持
- [ ] VNC 终端集成
- [ ] 更多 PVE 功能支持
- [ ] 用户权限细化
- [ ] API 密钥管理
- [ ] 邮件通知功能
- [ ] 自动备份功能
- [ ] 性能优化

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

MIT License

## 🙏 致谢

- [Proxmox VE](https://www.proxmox.com/) - 优秀的虚拟化管理平台
- [Axum](https://github.com/tokio-rs/axum) - 现代化的 Rust Web 框架
