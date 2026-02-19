# 构建阶段
FROM rust:1.75-alpine AS builder

WORKDIR /app

# 安装依赖
RUN apk add --no-cache postgresql-dev pkgconfig openssl-dev

# 复制 Cargo 文件
COPY Cargo.toml Cargo.lock* ./
COPY shared/ ./shared/
COPY server/ ./server/

# 构建项目
RUN cargo build --release

# 运行阶段
FROM alpine:latest

RUN apk add --no-cache postgresql-client

WORKDIR /app

# 复制编译后的二进制文件
COPY --from=builder /app/server/target/release/pve-server /app/pve-server
COPY --from=builder /app/shared/target/release/libpve_shared.so /app/lib/ 2>/dev/null || true

# 复制静态文件
COPY static/ /app/static/
COPY config/ /app/config/
COPY server/migrations/ /app/migrations/

EXPOSE 8080

CMD ["./pve-server"]
