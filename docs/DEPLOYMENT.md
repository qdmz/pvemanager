# PVE Manager 部署指南

## 生产环境部署

### 1. 系统要求

- 操作系统: Linux (Ubuntu 20.04+, CentOS 8+, Debian 11+) 推荐
- CPU: 2 核心以上
- 内存: 4GB 以上
- 磁盘: 20GB 以上可用空间
- Docker: 20.10+
- Docker Compose: 2.0+

### 2. 安装 Docker

#### Ubuntu/Debian
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
```

#### CentOS/RHEL
```bash
sudo yum install -y yum-utils
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
sudo yum install docker-ce docker-ce-cli containerd.io
sudo systemctl start docker
sudo systemctl enable docker
```

### 3. 配置环境变量

创建 `.env` 文件:

```bash
cp .env.example .env
```

编辑 `.env` 文件，修改以下配置:

```env
# 服务器配置
PVE_HOST=0.0.0.0
PVE_PORT=8080

# 数据库配置
PVE_DATABASE_URL=postgresql://postgres:strong_password_here@postgres/pve_manager

# JWT 密钥（使用强随机密钥）
PVE_JWT_SECRET=$(openssl rand -base64 32)

# PVE 服务器配置
PVE_PVE_URL=https://your-pve-server.com:8006
PVE_PVE_USERNAME=root@pam
PVE_PVE_PASSWORD=your-pve-password
PVE_PVE_REALM=pam

# 日志级别
RUST_LOG=pve_server=info,tower_http=info
```

### 4. 配置反向代理 (Nginx)

安装 Nginx:

```bash
sudo apt install nginx  # Ubuntu/Debian
# 或
sudo yum install nginx  # CentOS/RHEL
```

创建 Nginx 配置文件 `/etc/nginx/sites-available/pve-manager`:

```nginx
upstream pve_manager {
    server 127.0.0.1:8080;
}

server {
    listen 80;
    server_name pve-manager.yourdomain.com;

    # 强制 HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pve-manager.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/pve-manager.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pve-manager.yourdomain.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    client_max_body_size 10M;

    location / {
        proxy_pass http://pve_manager;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

启用配置:

```bash
sudo ln -s /etc/nginx/sites-available/pve-manager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. 配置 SSL 证书 (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d pve-manager.yourdomain.com
```

### 6. 启动服务

```bash
docker-compose up -d
```

### 7. 设置自动重启

```bash
docker-compose up -d --restart unless-stopped
```

### 8. 配置防火墙

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 9. 数据库备份

创建备份脚本 `/usr/local/bin/backup-pve-db.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/pve-manager"
DATE=$(date +%Y%m%d_%H%M%S)
CONTAINER_NAME="pve-manager-db"

mkdir -p $BACKUP_DIR

docker exec $CONTAINER_NAME pg_dump -U postgres pve_manager | gzip > $BACKUP_DIR/pve_manager_$DATE.sql.gz

# 保留最近 7 天的备份
find $BACKUP_DIR -name "pve_manager_*.sql.gz" -mtime +7 -delete
```

设置定时任务:

```bash
sudo crontab -e
```

添加每天凌晨 2 点执行备份:

```
0 2 * * * /usr/local/bin/backup-pve-db.sh
```

## 监控和日志

### 查看服务日志

```bash
# 所有服务日志
docker-compose logs -f

# 仅服务器日志
docker-compose logs -f server

# 仅数据库日志
docker-compose logs -f postgres
```

### 日志持久化

修改 `docker-compose.yml` 添加日志卷:

```yaml
services:
  server:
    volumes:
      - ./logs:/app/logs
```

## 更新部署

```bash
# 拉取最新代码
git pull origin main

# 重新构建
docker-compose build server

# 重启服务
docker-compose up -d server
```

## 安全检查清单

- [ ] 修改默认管理员密码
- [ ] 使用强 JWT 密钥
- [ ] 配置 HTTPS
- [ ] 限制数据库访问
- [ ] 配置防火墙
- [ ] 设置定期备份
- [ ] 配置日志监控
- [ ] 限制 API 访问速率
- [ ] 启用失败登录监控
- [ ] 定期更新系统和依赖

## 故障排除

### 服务无法启动

```bash
# 查看详细日志
docker-compose logs server

# 检查端口占用
sudo lsof -i :8080

# 检查数据库连接
docker-compose exec postgres psql -U postgres -d pve_manager
```

### 数据库连接失败

```bash
# 检查数据库状态
docker-compose ps postgres

# 重启数据库
docker-compose restart postgres
```

### 性能问题

```bash
# 查看资源使用
docker stats

# 优化数据库
docker-compose exec postgres psql -U postgres -d pve_manager -c "VACUUM ANALYZE;"
```

## 扩展配置

### 多节点部署

使用负载均衡器:

```nginx
upstream pve_manager {
    server 192.168.1.10:8080;
    server 192.168.1.11:8080;
    server 192.168.1.12:8080;
}
```

### Redis 缓存 (计划中)

在 `docker-compose.yml` 中添加:

```yaml
services:
  redis:
    image: redis:alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
```

更新环境变量添加 Redis URL。
