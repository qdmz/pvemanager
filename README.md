# PVE 管理系统

一个功能完整的 Proxmox VE 服务器管理系统，支持用户注册登录、虚拟机管理、到期时间控制、Webshell/VNC 访问等功能。

## 功能特性

### 用户功能
- ✅ 用户注册和登录
- ✅ JWT 身份认证
- ✅ 查看名下虚拟机/容器列表
- ✅ 虚拟机基本操作：启动、停止、重启、关机
- ✅ 查看虚拟机详细信息（CPU、内存、磁盘、网络配置）
- ✅ NAT 端口转发显示
- ✅ 到期时间管理（到期后不可操作）
- ✅ 修改个人资料和密码
- ✅ **虚拟机高级操作**
  - 重置虚拟机密码
  - 重装系统
  - 获取 SSH 连接信息
  - Webshell/VNC 访问
- ✅ **虚拟机硬件配置**
  - 调整 CPU 核心数
  - 调整内存大小
  - 调整磁盘大小
  - 配置网络接口
  - 配置 IP 地址
- ✅ **统计面板**
  - 虚拟机状态概览
  - 资源使用统计

### 管理员功能
- ✅ PVE 服务器管理（添加、编辑、删除服务器连接）
- ✅ 用户管理（查看用户列表、编辑用户）
- ✅ 虚拟机全量管理
  - 查看所有虚拟机
  - 创建新虚拟机（克隆模板）
  - 修改虚拟机归属
  - 设置到期日期
  - 设置虚拟机配置（CPU、内存、磁盘等）
  - 删除虚拟机
- ✅ 虚拟机硬件配置（CPU、内存、硬盘、网卡、IP配置）
- ✅ 统计数据面板
  - 系统总览（用户数、虚拟机数、服务器数）
  - 资源使用情况
  - 到期虚拟机统计
- ✅ 到期管理
  - 查看待关机虚拟机列表
  - 到期自动关机（支持定时任务）

## 技术栈

- **框架**: Next.js 16 (App Router)
- **UI 库**: shadcn/ui + Radix UI
- **样式**: Tailwind CSS 4
- **语言**: TypeScript
- **数据库**: PostgreSQL
- **ORM**: Drizzle ORM
- **认证**: JWT + bcrypt
- **HTTP 客户端**: Fetch API
- **SDK**: coze-coding-dev-sdk (数据库连接)

## 数据库表结构

### users (用户表)
- id: 主键
- username: 用户名（唯一）
- email: 邮箱（唯一）
- password: 密码（加密）
- role: 角色 (user/admin)
- created_at/updated_at: 时间戳

### pve_servers (PVE 服务器配置表)
- id: 主键
- name: 服务器名称
- host: 主机地址
- port: 端口（默认 8006）
- username: PVE 用户名
- api_token: API Token
- realm: 认证域（默认 pam）
- is_active: 是否激活
- created_at/updated_at: 时间戳

### virtual_machines (虚拟机表)
- id: 主键
- vm_id: PVE VM ID
- server_id: 服务器 ID（外键）
- type: 类型 (vm/ct)
- name: 虚拟机名称
- user_id: 所属用户 ID（外键）
- status: 状态 (running/stopped/paused)
- cpu_cores: CPU 核心数
- memory: 内存（MB）
- disk_size: 磁盘大小（GB）
- template: 系统模板
- root_password: Root 密码
- ip_address: IP 地址
- gateway: 网关
- dns_server: DNS 服务器
- nat_enabled: 是否启用 NAT
- nat_port_forward: NAT 端口转发配置（JSONB）
- expires_at: 到期时间
- auto_shutdown_on_expiry: 到期是否自动关机
- node: PVE 节点名称
- created_at/updated_at: 时间戳

### vm_operations (虚拟机操作日志表)
- id: 主键
- vm_id: 虚拟机 ID（外键）
- operation: 操作类型
- status: 操作状态 (pending/success/failed)
- message: 操作消息
- user_id: 操作用户 ID（外键）
- created_at: 创建时间

### system_settings (系统设置表)
- id: 主键
- key: 设置键（唯一）
- value: 设置值
- description: 描述
- updated_at: 更新时间

## 核心 PVE API 封装

项目封装了完整的 PVE API 客户端 (`src/lib/pve-client.ts`)，支持以下功能：

### 基础 API
- ✅ 获取节点列表
- ✅ 获取虚拟机列表
- ✅ 获取虚拟机状态
- ✅ 获取虚拟机配置

### 虚拟机操作
- ✅ 启动虚拟机 (`startVM`)
- ✅ 停止虚拟机 (`stopVM`)
- ✅ 重启虚拟机 (`rebootVM`)
- ✅ 关机虚拟机 (`shutdownVM`)
- ✅ 克隆虚拟机 (`cloneVM`)

### 配置管理
- ✅ 更新虚拟机配置 (`updateVMConfig`)
- ✅ 调整 CPU (`resizeCPU`)
- ✅ 调整内存 (`resizeMemory`)
- ✅ 调整磁盘大小 (`resizeDisk`)
- ✅ 移动磁盘 (`moveDisk`)

### 网络配置
- ✅ 获取网络接口列表 (`getNetworkInterfaces`)
- ✅ 获取 IP 配置 (`getIPConfig`)
- ✅ 更新网络配置 (`updateNetworkConfig`)

### 容器操作
- ✅ 启动容器 (`startCT`)
- ✅ 停止容器 (`stopCT`)
- ✅ 重启容器 (`rebootCT`)
- ✅ 关机容器 (`shutdownCT`)

### 命令执行
- ✅ 执行 Shell 命令 (`execCommand`)

### 访问接口
- ✅ Termproxy 代理 (`termproxy`)
- ✅ VNC 代理 (`vncproxy`)
- ✅ 获取 VNC WebSocket 地址 (`getVncWebSocket`)

### 密码与重装
- ✅ 设置云主机密码 (`setUserPassword`)
- ✅ 设置 Root 密码 (`setRootPassword`)
- ✅ 重装系统 (`reinstall`)

## 快速开始

### 环境要求
- Node.js 20+
- PostgreSQL 12+
- pnpm 9+

### 安装依赖

```bash
pnpm install
```

### 配置环境变量

复制 `.env.local` 文件并修改配置：

```env
# Database
DATABASE_URL=postgresql://username:password@localhost:5432/pve-manager

# JWT Secret (生产环境请修改!)
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production

# App
NEXT_PUBLIC_APP_URL=http://localhost:5000
```

### 初始化数据库

```bash
pnpm run db:init
```

### 启动开发服务器

```bash
npx next dev --port 5000
```

访问 http://localhost:5000

### Docker 部署（推荐生产环境）

#### 1. 使用 Docker Compose（推荐）

##### 配置环境变量

创建 `.env` 文件：

```env
# 应用配置
APP_PORT=5000
NEXT_PUBLIC_APP_URL=https://your-domain.com
JWT_SECRET=your-super-secret-jwt-key-change-this

# 数据库配置
POSTGRES_DB=pve_manager
POSTGRES_USER=pve_user
POSTGRES_PASSWORD=your-strong-postgres-password
POSTGRES_PORT=5432

# Redis 配置（可选）
REDIS_PASSWORD=your-strong-redis-password
REDIS_PORT=6379
```

##### 启动服务

```bash
# 基础部署（应用 + PostgreSQL + 定时任务）
docker-compose up -d

# 完整部署（包含 Nginx + Redis）
docker-compose --profile with-nginx --profile with-redis up -d

# 查看日志
docker-compose logs -f app

# 停止服务
docker-compose down

# 停止并删除数据卷（慎用）
docker-compose down -v
```

#### 2. 使用 Docker 单独部署

##### 构建镜像

```bash
docker build -t pve-manager:latest .
```

##### 运行容器

```bash
docker run -d \
  --name pve-manager \
  -p 5000:5000 \
  -e DATABASE_URL=postgresql://pve_user:password@postgres:5432/pve_manager \
  -e JWT_SECRET=your-secret-key \
  -e NEXT_PUBLIC_APP_URL=https://your-domain.com \
  --link postgres:postgres \
  pve-manager:latest
```

#### 3. Docker 配置说明

##### Dockerfile 特性

- **多阶段构建**: 分离依赖安装、构建和运行阶段，减小镜像体积
- **安全性**: 使用非 root 用户运行应用
- **健康检查**: 自动检测应用运行状态
- **优雅退出**: 使用 dumb-init 处理信号

##### docker-compose.yml 服务

- **app**: 主应用服务
- **postgres**: PostgreSQL 数据库
- **cron**: 定时任务服务（每天凌晨 2 点检查到期虚拟机）
- **nginx**: 反向代理（可选，使用 `--profile with-nginx` 启用）
- **redis**: 缓存服务（可选，使用 `--profile with-redis` 启用）

##### 持久化存储

- PostgreSQL 数据存储在 `./data/postgres` 目录
- 应用日志存储在 `./logs` 目录

#### 4. 生产环境建议

##### 使用 Nginx 反向代理

启用 Nginx 服务并配置 SSL 证书：

```bash
# 放置 SSL 证书
mkdir -p config/nginx/ssl
cp your-cert.pem config/nginx/ssl/
cp your-key.pem config/nginx/ssl/

# 启动完整服务
docker-compose --profile with-nginx up -d
```

##### 资源限制

在 `docker-compose.yml` 中添加资源限制：

```yaml
app:
  deploy:
    resources:
      limits:
        cpus: '2'
        memory: 2G
      reservations:
        cpus: '1'
        memory: 1G
```

##### 数据备份

```bash
# 备份 PostgreSQL 数据
docker-compose exec postgres pg_dump -U pve_user pve_manager > backup.sql

# 恢复数据
docker-compose exec -T postgres psql -U pve_user pve_manager < backup.sql
```

访问 http://localhost:5000

### 构建生产版本

```bash
npx next build
pnpm run start
```

## 定时任务配置

### 到期虚拟机检查

系统提供了自动检查并关机到期虚拟机的功能。你可以使用以下方式配置定时任务：

#### 1. 使用 cron 定时任务（推荐）

在服务器上添加 cron 任务，每天凌晨执行一次：

```bash
# 编辑 crontab
crontab -e

# 添加以下行（每天凌晨 2 点执行）
0 2 * * * curl -X GET http://localhost:5000/api/system/check-expiry
```

#### 2. 使用 systemd timer

创建 `/etc/systemd/system/pve-check-expiry.service`:

```ini
[Unit]
Description=PVE Manager Check Expiry
After=network.target

[Service]
Type=simple
ExecStart=/usr/bin/curl -X GET http://localhost:5000/api/system/check-expiry
```

创建 `/etc/systemd/system/pve-check-expiry.timer`:

```ini
[Unit]
Description=PVE Manager Check Expiry Timer

[Timer]
OnCalendar=daily
Persistent=true

[Install]
WantedBy=timers.target
```

启用服务：

```bash
sudo systemctl enable pve-check-expiry.timer
sudo systemctl start pve-check-expiry.timer
```

## 安全建议

1. **JWT Secret**: 生产环境务必修改 `JWT_SECRET`，使用强密码
2. **HTTPS**: 生产环境请配置 HTTPS，使用反向代理（如 Nginx）
3. **数据库**: 确保数据库仅允许本地访问或使用强密码
4. **PVE API Token**: 使用权限受限的 PVE API Token，避免使用 root 账号
5. **备份**: 定期备份数据库数据

## API 路由

### 认证相关
- `POST /api/auth/register` - 用户注册
- `POST /api/auth/login` - 用户登录
- `GET /api/auth/me` - 获取当前用户信息
- `POST /api/auth/logout` - 用户登出

### 用户相关
- `GET /api/user/profile` - 获取用户资料
- `PUT /api/user/profile` - 更新用户资料/密码

### 虚拟机相关
- `GET /api/vms` - 获取当前用户的虚拟机列表
- `GET /api/vms/[id]` - 获取虚拟机详情
- `POST /api/vms/[id]/operations` - 执行虚拟机操作（启动/停止/重启/关机）
- `POST /api/vms/[id]/advanced` - 虚拟机高级操作
  - 重置密码
  - 重装系统
  - 获取 SSH 信息
  - 启动 Webshell
  - 启动 VNC

### 系统管理
- `GET /api/system/check-expiry` - 检查并处理到期虚拟机（定时任务）

### 统计数据
- `GET /api/dashboard/stats` - 用户仪表盘统计数据
- `GET /api/admin/stats` - 管理员后台统计数据

### 管理员相关
- `GET /api/admin/servers` - 获取 PVE 服务器列表
- `POST /api/admin/servers` - 添加 PVE 服务器
- `GET /api/admin/users` - 获取用户列表
- `GET /api/admin/vms` - 获取所有虚拟机列表
- `PUT /api/admin/vms/[id]` - 更新虚拟机配置
- `DELETE /api/admin/vms/[id]` - 删除虚拟机
- `POST /api/admin/vms/[id]/reconfigure` - 虚拟机硬件配置
  - 调整 CPU
  - 调整内存
  - 调整磁盘
  - 配置网络
  - 配置 IP

## 项目结构

```
pve-manager/
├── src/
│   ├── app/                    # Next.js App Router
│   │   ├── api/               # API 路由
│   │   │   ├── auth/         # 认证相关
│   │   │   ├── user/         # 用户相关
│   │   │   ├── vms/          # 虚拟机相关
│   │   │   │   └── [id]/     # 虚拟机操作
│   │   │   │       ├── advanced/    # 高级操作
│   │   │   │       └── operations/ # 基本操作
│   │   │   ├── admin/        # 管理员相关
│   │   │   │   ├── servers/  # 服务器管理
│   │   │   │   ├── users/    # 用户管理
│   │   │   │   ├── vms/      # 虚拟机管理
│   │   │   │   │   └── [id]/ # 虚拟机配置
│   │   │   │   └── stats/    # 统计数据
│   │   │   ├── dashboard/    # 仪表盘统计
│   │   │   └── system/       # 系统管理
│   │   │       └── check-expiry/  # 到期检查
│   │   ├── dashboard/         # 用户仪表盘
│   │   │   ├── vms/         # 虚拟机管理
│   │   │   │   └── [id]/    # 虚拟机详情
│   │   │   └── page.tsx     # 仪表盘主页
│   │   ├── admin/             # 管理员后台
│   │   ├── settings/          # 个人设置
│   │   ├── login/             # 登录页
│   │   └── register/          # 注册页
│   ├── components/            # React 组件
│   │   ├── ui/               # shadcn/ui 组件
│   │   └── dashboard-nav.tsx # 仪表盘导航
│   ├── db/                   # 数据库相关
│   │   ├── schema.ts         # 数据库表定义
│   │   └── index.ts          # 数据库连接
│   └── lib/                  # 工具函数
│       ├── auth.ts           # 认证相关
│       └── pve-client.ts     # PVE API 客户端
├── scripts/
│   └── init-db.ts           # 数据库初始化脚本
├── drizzle.config.ts        # Drizzle ORM 配置
├── .env.local               # 环境变量
└── package.json             # 项目依赖
```

## 开发注意事项

### 数据库连接

项目使用 `coze-coding-dev-sdk` 的 `getDb()` 函数获取数据库连接，确保连接池的正确管理。在所有 API 路由中，使用以下方式获取数据库实例：

```typescript
import { getDb } from 'coze-coding-dev-sdk';

const db = await getDb();
// 使用 db 进行数据库操作
```

### 虚拟机操作状态

虚拟机操作是异步的，操作状态记录在 `vm_operations` 表中。前端可以通过轮询或 WebSocket 获取操作状态更新。

### NAT 端口转发

NAT 端口转发配置以 JSONB 格式存储在虚拟机表中，格式如下：

```json
{
  "80": "8080",
  "443": "8443",
  "22": "2222"
}
```

表示外部端口 8080 映射到虚拟机的 80 端口，依此类推。

## 常见问题

### Q: 虚拟机到期后无法启动？

A: 这是预期行为。到期虚拟机会自动禁止启动、停止、重启等操作。如需恢复，请联系管理员延长到期时间。

### Q: Webshell/VNC 无法连接？

A: 请确保：
1. 虚拟机状态为 "running"
2. PVE 服务器的防火墙允许相关端口
3. 网络连接正常

### Q: 如何批量导入虚拟机？

A: 可以通过管理员后台的 PVE 服务器同步功能，批量导入现有虚拟机。

### Q: 定时任务未生效？

A: 请检查：
1. `/api/system/check-expiry` 接口是否可正常访问
2. cron 或 systemd 服务是否正常运行
3. 日志中是否有错误信息```

## 待开发功能

- [ ] Webshell/VNC 集成
- [ ] 虚拟机密码重置功能
- [ ] 虚拟机重装系统功能
- [ ] 管理员创建新虚拟机
- [ ] 虚拟机硬件配置（增减硬盘、网卡等）
- [ ] 统计数据和监控面板
- [ ] 虚拟机操作日志查看
- [ ] 系统通知功能
- [ ] 二步验证（2FA）
- [ ] API 密钥管理

## 安全建议

1. **生产环境必须修改 JWT_SECRET**
2. 使用 HTTPS 部署
3. 定期更新依赖包
4. 限制数据库访问权限
5. 启用 CORS 策略
6. 添加请求速率限制
7. 定期备份数据库

## 许可证

MIT

## 贡献

欢迎提交 Issue 和 Pull Request！
