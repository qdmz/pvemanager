# PVE 管理系统部署指南

本文档提供将 PVE 管理系统部署到远程服务器的详细步骤。

## 目录
- [环境要求](#环境要求)
- [服务器准备](#服务器准备)
- [数据库配置](#数据库配置)
- [项目部署](#项目部署)
- [使用 PM2 部署（推荐）](#使用-pm2-部署推荐)
- [使用 Docker 部署](#使用-docker-部署)
- [常见问题](#常见问题)

---

## 环境要求

### 服务器要求
- **操作系统**: Linux (推荐 Ubuntu 22.04+ / Debian 12+)
- **CPU**: 2核及以上
- **内存**: 4GB 及以上
- **硬盘**: 20GB 及以上可用空间
- **网络**: 公网 IP 或可访问的内网 IP

### 软件要求
- **Node.js**: 24.x 或更高版本
- **pnpm**: 9.0.0 或更高版本
- **PostgreSQL**: 14.x 或更高版本
- **Git**: 用于代码管理
- **Nginx** (可选): 用于反向代理和 SSL
- **PM2** (推荐): 进程管理器

---

## 服务器准备

### 1. 更新系统
```bash
sudo apt update && sudo apt upgrade -y
```

### 2. 安装必要软件

#### 安装 Node.js 24.x
```bash
# 使用 NodeSource 仓库安装 Node.js 24
curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
sudo apt install -y nodejs

# 验证安装
node --version  # 应显示 v24.x.x
npm --version
```

#### 安装 pnpm
```bash
npm install -g pnpm@9
# 或者使用 corepack
corepack enable
corepack prepare pnpm@9 --activate

# 验证安装
pnpm --version  # 应显示 9.x.x
```

#### 安装 PostgreSQL
```bash
sudo apt install -y postgresql postgresql-contrib

# 启动 PostgreSQL 服务
sudo systemctl start postgresql
sudo systemctl enable postgresql

# 验证安装
sudo -u postgres psql --version
```

#### 安装 PM2（推荐）
```bash
pnpm install -g pm2
pm2 --version
```

#### 安装 Nginx（可选，用于反向代理）
```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

---

## 数据库配置

### 1. 创建数据库和用户
```bash
# 切换到 postgres 用户
sudo -u postgres psql

# 在 PostgreSQL 命令行中执行以下命令：
CREATE DATABASE pve_manager;
CREATE USER pve_user WITH ENCRYPTED PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE pve_manager TO pve_user;
ALTER DATABASE pve_manager OWNER TO pve_user;
\q
```

### 2. 配置 PostgreSQL 远程访问（如果数据库不在本地）

编辑 `/etc/postgresql/*/main/postgresql.conf`:
```bash
sudo nano /etc/postgresql/*/main/postgresql.conf
```

修改或添加以下行：
```conf
listen_addresses = '*'
```

编辑 `/etc/postgresql/*/main/pg_hba.conf`:
```bash
sudo nano /etc/postgresql/*/main/pg_hba.conf
```

添加以下行（在文件末尾）：
```conf
host    pve_manager    pve_user    your_client_ip/32    scram-sha-256
```

重启 PostgreSQL:
```bash
sudo systemctl restart postgresql
```

### 3. 初始化数据库表
```bash
# 在项目目录中
cd /path/to/pve-manager

# 设置环境变量
export DATABASE_URL="postgresql://pve_user:your_secure_password_here@localhost/pve_manager"

# 运行数据库初始化脚本
pnpm db:init
```

---

## 项目部署

### 1. 上传代码到服务器

#### 方法 1: 使用 Git（推荐）
```bash
# 在服务器上克隆代码库
cd /var/www
sudo git clone <your-git-repo-url> pve-manager
cd pve-manager

# 或者从本地推送
# 在本地开发环境
git remote add deploy ssh://user@your-server-ip/var/www/pve-manager
git push deploy main
```

#### 方法 2: 使用 SCP
```bash
# 在本地机器上
scp -r ./pve-manager user@your-server-ip:/var/www/
```

### 2. 创建环境变量文件

```bash
cd /var/www/pve-manager
cp .env.example .env
nano .env
```

配置以下环境变量：

```env
# 数据库配置
DATABASE_URL="postgresql://pve_user:your_secure_password_here@localhost/pve_manager"

# JWT 密钥（生成一个安全的随机字符串）
JWT_SECRET="your-very-secure-jwt-secret-key-min-32-chars"

# PVE API 配置（管理员后台配置后自动更新）
# PVE_API_URL="https://your-pve-server:8006"
# PVE_API_USER="root@pam"
# PVE_API_PASSWORD="your-pve-password"
# PVE_API_TOKEN_ID="token-name"
# PVE_API_TOKEN_VALUE="token-secret"

# 应用配置
NEXT_PUBLIC_APP_NAME="PVE Manager"
NEXT_PUBLIC_APP_URL="https://your-domain.com"

# 管理员初始配置（首次部署后通过注册页面创建）
# ADMIN_USERNAME=admin
# ADMIN_EMAIL=admin@example.com
# ADMIN_PASSWORD=your_secure_password
```

**生成安全的 JWT_SECRET:**
```bash
openssl rand -base64 64
```

### 3. 安装依赖并构建

```bash
cd /var/www/pve-manager

# 安装依赖
pnpm install --frozen-lockfile

# 构建项目
pnpm build
```

### 4. 验证构建

```bash
# 检查构建输出目录
ls -la .next/

# 测试运行（前台运行，用于调试）
PORT=5000 pnpm start
```

如果一切正常，按 `Ctrl+C` 停止进程。

---

## 使用 PM2 部署（推荐）

PM2 可以自动管理进程、日志、重启等。

### 1. 创建 PM2 配置文件

创建 `ecosystem.config.js`:

```javascript
module.exports = {
  apps: [
    {
      name: 'pve-manager',
      script: 'node_modules/next/dist/bin/next',
      args: 'start -p 5000',
      cwd: '/var/www/pve-manager',
      instances: 1,
      exec_mode: 'fork',
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'production',
        PORT: 5000,
        DATABASE_URL: 'postgresql://pve_user:your_secure_password_here@localhost/pve_manager',
        JWT_SECRET: 'your-very-secure-jwt-secret-key-min-32-chars',
        NEXT_PUBLIC_APP_URL: 'https://your-domain.com'
      },
      error_file: './logs/pm2-error.log',
      out_file: './logs/pm2-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z'
    }
  ]
};
```

### 2. 使用 PM2 启动应用

```bash
cd /var/www/pve-manager

# 创建日志目录
mkdir -p logs

# 启动应用
pm2 start ecosystem.config.js

# 查看状态
pm2 status

# 查看日志
pm2 logs pve-manager

# 停止应用
pm2 stop pve-manager

# 重启应用
pm2 restart pve-manager

# 删除应用
pm2 delete pve-manager
```

### 3. 设置 PM2 开机自启

```bash
# 保存 PM2 进程列表
pm2 save

# 生成启动脚本
pm2 startup

# 按照提示执行生成的命令（通常是）
sudo env PATH=$PATH:/usr/bin pm2 startup systemd -u your-username --hp /home/your-username
```

### 4. PM2 常用命令

```bash
# 查看所有应用状态
pm2 list

# 查看实时日志
pm2 logs

# 查看应用详情
pm2 show pve-manager

# 监控应用
pm2 monit

# 清空日志
pm2 flush

# 更新应用（代码更新后）
git pull
pnpm install
pnpm build
pm2 restart pve-manager
```

---

## 使用 Docker 部署

### 1. 创建 Dockerfile

```dockerfile
FROM node:24-slim

# 安装 pnpm
RUN corepack enable && corepack prepare pnpm@9 --activate

WORKDIR /app

# 复制依赖文件
COPY package.json pnpm-lock.yaml ./

# 安装依赖
RUN pnpm install --frozen-lockfile

# 复制项目文件
COPY . .

# 构建应用
RUN pnpm build

# 暴露端口
EXPOSE 5000

# 启动应用
CMD ["pnpm", "start"]
```

### 2. 创建 docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: pve-manager
    ports:
      - "5000:5000"
    environment:
      - NODE_ENV=production
      - PORT=5000
      - DATABASE_URL=postgresql://pve_user:your_secure_password_here@postgres:5432/pve_manager
      - JWT_SECRET=your-very-secure-jwt-secret-key-min-32-chars
      - NEXT_PUBLIC_APP_URL=https://your-domain.com
    depends_on:
      - postgres
    restart: unless-stopped
    networks:
      - pve-network

  postgres:
    image: postgres:16-alpine
    container_name: pve-postgres
    environment:
      - POSTGRES_DB=pve_manager
      - POSTGRES_USER=pve_user
      - POSTGRES_PASSWORD=your_secure_password_here
    volumes:
      - postgres-data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    restart: unless-stopped
    networks:
      - pve-network

volumes:
  postgres-data:

networks:
  pve-network:
    driver: bridge
```

### 3. 构建和运行

```bash
cd /var/www/pve-manager

# 构建并启动容器
docker-compose up -d

# 查看日志
docker-compose logs -f app

# 停止容器
docker-compose down

# 重启容器
docker-compose restart
```

### 4. 初始化数据库

```bash
# 进入 PostgreSQL 容器
docker-compose exec postgres psql -U pve_user -d pve_manager

# 或者在应用容器中运行初始化脚本
docker-compose exec app pnpm db:init
```

---

## 配置 Nginx 反向代理（可选但推荐）

### 1. 创建 Nginx 配置文件

```bash
sudo nano /etc/nginx/sites-available/pve-manager
```

添加以下配置：

```nginx
server {
    listen 80;
    server_name your-domain.com;

    # 如果使用 HTTPS，建议配置 HTTP 到 HTTPS 的重定向
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    # SSL 证书配置（使用 Let's Encrypt）
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # SSL 安全配置
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # 安全头部
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # 日志
    access_log /var/log/nginx/pve-manager-access.log;
    error_log /var/log/nginx/pve-manager-error.log;

    # 反向代理到 Next.js
    location / {
        proxy_pass http://127.0.0.1:5000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
    }

    # Next.js 静态资源缓存
    location /_next/static {
        proxy_pass http://127.0.0.1:5000;
        proxy_cache_valid 200 60m;
        add_header Cache-Control "public, immutable, max-age=31536000";
    }

    # 文件上传大小限制（如果需要）
    client_max_body_size 100M;
}
```

### 2. 启用配置

```bash
# 创建软链接
sudo ln -s /etc/nginx/sites-available/pve-manager /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重启 Nginx
sudo systemctl restart nginx
```

### 3. 配置 SSL 证书（Let's Encrypt）

```bash
# 安装 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取证书（会自动配置 Nginx）
sudo certbot --nginx -d your-domain.com

# 测试自动续期
sudo certbot renew --dry-run
```

---

## 安全加固建议

### 1. 防火墙配置

```bash
# 安装 UFW
sudo apt install -y ufw

# 默认策略
sudo ufw default deny incoming
sudo ufw default allow outgoing

# 允许 SSH
sudo ufw allow 22/tcp

# 允许 HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# 启用防火墙
sudo ufw enable

# 查看状态
sudo ufw status
```

### 2. 数据库安全

```bash
# 确保 PostgreSQL 只监听本地（如果数据库在本地）
# 编辑 /etc/postgresql/*/main/postgresql.conf
listen_addresses = 'localhost'

# 限制远程访问
# 编辑 /etc/postgresql/*/main/pg_hba.conf
# 只允许特定 IP 访问
```

### 3. 定期备份

```bash
# 创建数据库备份脚本
cat > /home/user/backup-db.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/backups/pve-manager"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

pg_dump -U pve_user pve_manager > $BACKUP_DIR/pve_manager_$DATE.sql
gzip $BACKUP_DIR/pve_manager_$DATE.sql

# 删除 30 天前的备份
find $BACKUP_DIR -name "pve_manager_*.sql.gz" -mtime +30 -delete
EOF

chmod +x /home/user/backup-db.sh

# 添加到 crontab（每天凌晨 2 点备份）
crontab -e
# 添加：0 2 * * * /home/user/backup-db.sh
```

---

## 常见问题

### 1. 端口被占用

```bash
# 查找占用 5000 端口的进程
sudo lsof -i :5000
# 或
sudo netstat -tulpn | grep :5000

# 杀死进程
sudo kill -9 <PID>
```

### 2. 数据库连接失败

```bash
# 检查 PostgreSQL 服务状态
sudo systemctl status postgresql

# 检查数据库连接
psql -U pve_user -h localhost -d pve_manager

# 检查防火墙
sudo ufw status

# 检查 PostgreSQL 日志
sudo tail -f /var/log/postgresql/postgresql-*-main.log
```

### 3. 构建失败

```bash
# 清除缓存
rm -rf .next
rm -rf node_modules
pnpm install
pnpm build
```

### 4. PM2 应用自动重启

```bash
# 查看日志排查原因
pm2 logs pve-manager --lines 100

# 检查内存使用
pm2 monit

# 增加内存限制（在 ecosystem.config.js 中）
max_memory_restart: '2G'
```

### 5. 文件权限问题

```bash
# 设置正确的文件权限
sudo chown -R your-user:your-user /var/www/pve-manager
chmod -R 755 /var/www/pve-manager
```

---

## 监控和维护

### 1. 设置监控

使用 PM2 监控：
```bash
pm2 monit
```

### 2. 日志管理

```bash
# 查看应用日志
pm2 logs pve-manager

# 查看错误日志
pm2 logs pve-manager --err

# 清空日志
pm2 flush
```

### 3. 更新应用

```bash
cd /var/www/pve-manager

# 拉取最新代码
git pull origin main

# 安装依赖
pnpm install

# 构建应用
pnpm build

# 重启 PM2
pm2 restart pve-manager
```

---

## 联系和支持

如有问题，请查看：
- 项目文档
- 日志文件
- PM2 监控面板

---

## 快速部署脚本（一键部署）

创建 `quick-deploy.sh`:

```bash
#!/bin/bash
set -e

echo "=== PVE Manager 快速部署脚本 ==="

# 配置变量
DB_USER="pve_user"
DB_PASSWORD=$(openssl rand -base64 32)
DB_NAME="pve_manager"
JWT_SECRET=$(openssl rand -base64 64)
APP_DIR="/var/www/pve-manager"

# 1. 安装依赖
echo "1. 安装系统依赖..."
sudo apt update
sudo apt install -y nodejs npm postgresql postgresql-contrib nginx git

# 2. 安装 pnpm
echo "2. 安装 pnpm..."
npm install -g pnpm@9

# 3. 配置数据库
echo "3. 配置数据库..."
sudo -u postgres psql <<EOF
CREATE DATABASE $DB_NAME;
CREATE USER $DB_USER WITH ENCRYPTED PASSWORD '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
ALTER DATABASE $DB_NAME OWNER TO $DB_USER;
\q
EOF

# 4. 克隆项目（修改为你的 Git 仓库）
echo "4. 克隆项目..."
sudo mkdir -p $APP_DIR
sudo chown $USER:$USER $APP_DIR
git clone <your-git-repo> $APP_DIR
cd $APP_DIR

# 5. 创建环境变量
echo "5. 创建环境变量..."
cat > .env <<EOF
DATABASE_URL="postgresql://$DB_USER:$DB_PASSWORD@localhost/$DB_NAME"
JWT_SECRET="$JWT_SECRET"
NODE_ENV=production
PORT=5000
EOF

# 6. 安装依赖和构建
echo "6. 安装依赖并构建..."
pnpm install --frozen-lockfile
pnpm build

# 7. 配置 PM2
echo "7. 配置 PM2..."
pnpm install -g pm2
pm2 start ecosystem.config.js
pm2 save

# 8. 配置 Nginx
echo "8. 配置 Nginx..."
sudo cp config/nginx/pve-manager.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/pve-manager /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl restart nginx

echo "=== 部署完成！==="
echo "数据库密码: $DB_PASSWORD"
echo "JWT Secret: $JWT_SECRET"
echo "请妥善保存这些信息！"
```

使用方法：
```bash
chmod +x quick-deploy.sh
./quick-deploy.sh
```

---

**注意**: 在生产环境部署前，请务必：
1. 修改所有默认密码
2. 配置 HTTPS
3. 设置防火墙
4. 配置定期备份
5. 监控系统日志
