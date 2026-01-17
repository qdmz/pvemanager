# 使用 Node.js 24 官方镜像
FROM node:24-slim

# 设置工作目录
WORKDIR /app

# 安装 pnpm
RUN corepack enable && corepack prepare pnpm@9 --activate

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# 复制依赖文件
COPY package.json pnpm-lock.yaml ./

# 安装依赖
RUN pnpm install --frozen-lockfile --prod=false

# 复制项目文件
COPY . .

# 构建应用
RUN pnpm build

# 只安装生产依赖
RUN pnpm install --frozen-lockfile --prod

# 创建日志目录
RUN mkdir -p logs

# 暴露端口
EXPOSE 5000

# 健康检查
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
  CMD node -e "require('http').get('http://localhost:5000/api/test', (r) => {process.exit(r.statusCode === 200 ? 0 : 1)})"

# 非root用户运行
RUN useradd -m -u 1000 -s /bin/bash nodejs && \
    chown -R nodejs:nodejs /app

USER nodejs

# 启动应用
CMD ["node", "node_modules/next/dist/bin/next", "start", "-p", "5000"]
