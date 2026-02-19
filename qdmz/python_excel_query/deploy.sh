#!/bin/bash

# Excel查询系统 - 部署脚本
# 作者: Qoder
# 日期: 2025-12-27

echo "==================================="
echo "Excel查询系统 - 部署脚本"
echo "==================================="

# 检查是否安装了必要的工具
check_prerequisites() {
    echo "检查系统依赖..."
    
    if ! command -v docker &> /dev/null; then
        echo "错误: Docker 未安装"
        echo "请先安装 Docker: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        echo "错误: Docker Compose 未安装"
        echo "请先安装 Docker Compose: https://docs.docker.com/compose/install/"
        exit 1
    fi
    
    echo "✓ Docker 已安装"
    echo "✓ Docker Compose 已安装"
}

# 创建必要的目录
setup_directories() {
    echo "创建目录结构..."
    
    mkdir -p uploads
    mkdir -p config
    mkdir -p logs
    
    echo "✓ 目录结构已创建"
}

# 生成安全的密钥
generate_secret_key() {
    if [ ! -f .env ]; then
        echo "生成安全密钥..."
        SECRET_KEY=$(python3 -c 'import secrets; print(secrets.token_urlsafe(32))')
        echo "SECRET_KEY=$SECRET_KEY" > .env
        echo "✓ 安全密钥已生成并保存到 .env 文件"
    else
        echo "✓ 使用现有的 .env 文件"
    fi
}

# 构建并启动服务
start_services() {
    echo "构建并启动服务..."
    
    docker-compose up -d --build
    
    if [ $? -eq 0 ]; then
        echo "✓ 服务启动成功"
        echo ""
        echo "==================================="
        echo "部署完成!"
        echo "访问地址: http://localhost"
        echo "==================================="
        echo ""
        echo "常用命令:"
        echo "  查看服务状态: docker-compose ps"
        echo "  查看日志: docker-compose logs -f"
        echo "  停止服务: docker-compose down"
        echo "  重启服务: docker-compose restart"
        echo ""
    else
        echo "✗ 服务启动失败"
        exit 1
    fi
}

# 主流程
main() {
    check_prerequisites
    setup_directories
    generate_secret_key
    start_services
    
    echo "部署脚本执行完成!"
    echo "请访问 http://localhost 使用Excel查询系统"
}

# 执行主流程
main