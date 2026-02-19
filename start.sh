#!/bin/bash

echo "🚀 Starting PVE Manager..."

# 检查 Docker 是否安装
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# 检查 Docker Compose 是否安装
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# 创建必要的目录
mkdir -p config
mkdir -p static/css
mkdir -p static/js
mkdir -p server/migrations

# 启动服务
echo "📦 Starting PostgreSQL database..."
docker-compose up -d postgres

echo "⏳ Waiting for database to be ready..."
sleep 5

echo "🔧 Building and starting server..."
docker-compose up -d server

echo ""
echo "✅ PVE Manager started successfully!"
echo ""
echo "📝 Login credentials:"
echo "   Email: admin@pve.local"
echo "   Password: admin123"
echo ""
echo "🌐 Access the web interface at: http://localhost:8080"
echo ""
echo "📊 View logs: docker-compose logs -f"
echo "🛑 Stop services: docker-compose down"
echo ""
