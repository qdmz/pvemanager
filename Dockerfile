# ============================================
# 多阶段构建 - Next.js PVE Manager
# ============================================

# 阶段 1: 依赖安装
FROM node:24-slim AS deps

# 启用 corepack 并准备 pnpm
RUN corepack enable && corepack prepare pnpm@9 --activate

# 设置工作目录
WORKDIR /app

# 安装系统依赖（仅构建所需）
RUN apt-get update && apt-get install -y \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# 复制依赖文件
COPY package.json pnpm-lock.yaml ./

# 安装所有依赖（包括 devDependencies，用于构建）
RUN pnpm install --frozen-lockfile

# ============================================
# 阶段 2: 构建应用
FROM node:24-slim AS builder

# 启用 corepack 并准备 pnpm
RUN corepack enable && corepack prepare pnpm@9 --activate

WORKDIR /app

# 复制依赖
COPY --from=deps /app/node_modules ./node_modules

# 复制项目文件
COPY . .

# 设置构建环境变量
ENV NEXT_TELEMETRY_DISABLED=1
ENV NODE_ENV=production

# 构建应用
RUN pnpm build

# ============================================
# 阶段 3: 生产镜像
FROM node:24-slim AS runner

# 启用 corepack 并准备 pnpm
RUN corepack enable && corepack prepare pnpm@9 --activate

WORKDIR /app

# 创建非 root 用户
RUN groupadd -r nodejs && useradd -r -g nodejs -G audio,video nodejs

# 安装运行时依赖
RUN apt-get update && apt-get install -y \
    openssl \
    curl \
    dumb-init \
    && rm -rf /var/lib/apt/lists/*

# 设置环境变量
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ENV PORT=5000
ENV HOSTNAME="0.0.0.0"

# 复制必要的文件
COPY --from=builder /app/public ./public
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

# 复制生产依赖
COPY --from=deps /app/node_modules ./node_modules
COPY package.json ./

# 创建日志目录
RUN mkdir -p /app/logs && chown -R nodejs:nodejs /app

# 切换到非 root 用户
USER nodejs

# 暴露端口
EXPOSE 5000

# 健康检查
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
  CMD curl -f http://localhost:5000/api/health || exit 1

# 使用 dumb-init 作为 PID 1，优雅处理信号
ENTRYPOINT ["dumb-init", "--"]

# 启动应用
CMD ["node", "server.js"]
