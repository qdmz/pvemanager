# PVE Manager 快速部署指南

## 三种部署方式

### 方式一：PM2 部署（推荐用于生产环境）

适用于：Linux 服务器，需要稳定运行和进程管理

#### 快速开始

```bash
# 1. 上传代码到服务器
scp -r ./pve-manager user@your-server:/tmp/

# 2. SSH 登录服务器
ssh user@your-server

# 3. 移动项目到正式目录
sudo mv /tmp/pve-manager /var/www/
cd /var/www/pve-manager

# 4. 运行部署脚本
chmod +x deploy.sh
./deploy.sh --install

# 5. 配置环境变量
nano .env

# 6. 启动应用
./deploy.sh --install
```

#### 更新应用

```bash
cd /var/www/pve-manager
./deploy.sh --update
```

#### 查看状态

```bash
# 查看应用状态
./deploy.sh --monitor

# 查看日志
./deploy.sh --logs
```

#### 备份数据库

```bash
# 备份
./deploy.sh --backup

# 恢复
./deploy.sh --restore
```

---

### 方式二：Docker 部署

适用于：需要隔离环境、快速部署、容器化部署

#### 前置条件

```bash
# 安装 Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# 安装 Docker Compose
sudo apt install -y docker-compose
```

#### 快速开始

```bash
# 1. 复制环境变量模板
cp .env.example .env

# 2. 编辑环境变量
nano .env

# 必须配置的变量：
# - POSTGRES_PASSWORD: PostgreSQL 密码
# - JWT_SECRET: JWT 密钥（使用 openssl rand -base64 64 生成）
# - NEXT_PUBLIC_APP_URL: 应用访问地址

# 3. 构建并启动
docker-compose up -d

# 4. 初始化数据库
docker-compose exec -T postgres psql -U pve_user -d pve_manager < scripts/init-db.sql

# 5. 查看日志
docker-compose logs -f app
```

#### 常用命令

```bash
# 启动服务
docker-compose up -d

# 停止服务
docker-compose down

# 重启服务
docker-compose restart

# 查看日志
docker-compose logs -f

# 查看状态
docker-compose ps

# 进入应用容器
docker-compose exec app bash

# 更新代码后重新部署
docker-compose down
docker-compose build
docker-compose up -d
```

---

### 方式三：直接部署（适用于测试）

适用于：快速测试、开发环境

```bash
# 1. 安装依赖
pnpm install --frozen-lockfile

# 2. 配置环境变量
cp .env.example .env
nano .env

# 3. 初始化数据库
pnpm db:init

# 4. 构建应用
pnpm build

# 5. 启动应用
pnpm start
```

---

## 环境变量配置

### 必须配置的变量

```bash
# 数据库连接
DATABASE_URL="postgresql://pve_user:password@localhost:5432/pve_manager"

# JWT 密钥（重要！）
JWT_SECRET="your-very-secure-jwt-secret-key"

# 应用 URL
NEXT_PUBLIC_APP_URL="https://your-domain.com"
```

### 生成安全密钥

```bash
# 生成 JWT 密钥
openssl rand -base64 64

# 生成数据库密码
openssl rand -base64 32
```

---

## 验证部署

### 1. 检查应用状态

```bash
# PM2 方式
pm2 status

# Docker 方式
docker-compose ps
```

### 2. 访问应用

```bash
# 测试接口
curl http://localhost:5000/api/test

# 或在浏览器访问
open http://localhost:5000
```

### 3. 查看日志

```bash
# PM2 方式
pm2 logs pve-manager

# Docker 方式
docker-compose logs -f app
```

---

## 配置 HTTPS（推荐）

### 使用 Let's Encrypt

```bash
# 安装 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取证书
sudo certbot --nginx -d your-domain.com

# 自动续期
sudo certbot renew --dry-run
```

### 使用 Cloudflare

1. 在 Cloudflare 添加域名
2. 配置 DNS A 记录指向服务器 IP
3. 开启 Cloudflare 的 SSL/TLS

---

## 常见问题

### 问题 1：端口被占用

```bash
# 查找占用 5000 端口的进程
sudo lsof -i :5000

# 杀死进程
sudo kill -9 <PID>
```

### 问题 2：数据库连接失败

```bash
# 检查 PostgreSQL 服务
sudo systemctl status postgresql

# 测试连接
psql -U pve_user -h localhost -d pve_manager

# 查看环境变量
cat .env | grep DATABASE_URL
```

### 问题 3：构建失败

```bash
# 清除缓存
rm -rf .next
rm -rf node_modules

# 重新安装依赖
pnpm install

# 重新构建
pnpm build
```

### 问题 4：PM2 应用自动重启

```bash
# 查看日志
pm2 logs pve-manager --lines 100

# 检查内存
pm2 monit

# 增加内存限制（编辑 ecosystem.config.js）
max_memory_restart: '2G'
```

---

## 性能优化

### 1. 启用 Nginx 缓存

在 Nginx 配置中添加：
```nginx
proxy_cache_path /var/cache/nginx levels=1:2 keys_zone=my_cache:10m max_size=1g inactive=60m;

location / {
    proxy_cache my_cache;
    proxy_pass http://127.0.0.1:5000;
}
```

### 2. 启用 Gzip 压缩

在 Nginx 配置中添加：
```nginx
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript;
```

### 3. 使用 Redis 缓存

```bash
# 启动 Redis
docker-compose up -d redis

# 在 .env 中配置
REDIS_URL="redis://localhost:6379"
```

---

## 监控和告警

### 使用 PM2 监控

```bash
# 实时监控
pm2 monit

# 查看 Web 监控
pm2 web
```

### 日志管理

```bash
# 查看错误日志
pm2 logs pve-manager --err

# 清空日志
pm2 flush

# 配置日志轮转（在 ecosystem.config.js 中）
log_date_format: 'YYYY-MM-DD HH:mm:ss'
```

---

## 备份策略

### 自动备份脚本

添加到 crontab：

```bash
# 编辑 crontab
crontab -e

# 每天凌晨 2 点备份数据库
0 2 * * * cd /var/www/pve-manager && ./deploy.sh --backup

# 每周日凌晨 3 点备份整个应用
0 3 * * 0 tar -czf /var/backups/pve-manager/app_$(date +\%Y\%m\%d).tar.gz /var/www/pve-manager
```

---

## 安全加固

### 1. 配置防火墙

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

### 2. 限制数据库访问

```bash
# 只允许本地访问
# 编辑 /etc/postgresql/*/main/postgresql.conf
listen_addresses = 'localhost'

# 编辑 /etc/postgresql/*/main/pg_hba.conf
host    pve_manager    pve_user    127.0.0.1/32    scram-sha-256
```

### 3. 定期更新

```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 更新应用
cd /var/www/pve-manager
./deploy.sh --update
```

---

## 下一步

1. 配置管理员账户
2. 添加 PVE 服务器
3. 创建虚拟机
4. 配置用户权限
5. 设置到期管理

详细文档请参考 [DEPLOYMENT.md](./DEPLOYMENT.md)
