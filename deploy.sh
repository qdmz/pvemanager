#!/bin/bash
# PVE Manager 自动化部署脚本
# 使用方法: ./deploy.sh [选项]
# 选项:
#   --install     首次安装
#   --update      更新部署
#   --backup      备份数据库
#   --restore     恢复数据库
#   --monitor     监控应用状态
#   --logs        查看日志

set -e

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 配置变量
APP_DIR="/var/www/pve-manager"
APP_NAME="pve-manager"
BACKUP_DIR="/var/backups/pve-manager"
LOG_DIR="$APP_DIR/logs"

# 函数：打印信息
info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# 函数：检查命令是否存在
check_command() {
    if ! command -v $1 &> /dev/null; then
        error "$1 未安装，请先安装"
    fi
}

# 函数：检查环境
check_environment() {
    info "检查环境..."

    check_command "node"
    check_command "pnpm"
    check_command "pm2"

    if [ ! -d "$APP_DIR" ]; then
        error "应用目录不存在: $APP_DIR"
    fi

    success "环境检查通过"
}

# 函数：停止应用
stop_app() {
    info "停止应用..."
    if pm2 list | grep -q "$APP_NAME"; then
        pm2 stop $APP_NAME || true
        success "应用已停止"
    else
        warning "应用未运行"
    fi
}

# 函数：启动应用
start_app() {
    info "启动应用..."
    cd $APP_DIR
    pm2 start ecosystem.config.js
    pm2 save
    success "应用已启动"
}

# 函数：重启应用
restart_app() {
    info "重启应用..."
    cd $APP_DIR
    pm2 restart $APP_NAME
    success "应用已重启"
}

# 函数：安装依赖
install_dependencies() {
    info "安装依赖..."
    cd $APP_DIR
    pnpm install --frozen-lockfile
    success "依赖安装完成"
}

# 函数：构建应用
build_app() {
    info "构建应用..."
    cd $APP_DIR
    pnpm build
    success "应用构建完成"
}

# 函数：首次安装
install() {
    info "开始首次安装..."

    # 检查环境
    check_command "node"
    check_command "pnpm"
    check_command "pm2"
    check_command "git"

    # 创建目录
    sudo mkdir -p $APP_DIR
    sudo mkdir -p $LOG_DIR
    sudo mkdir -p $BACKUP_DIR

    # 设置权限
    sudo chown -R $USER:$USER $APP_DIR
    sudo chown -R $USER:$USER $LOG_DIR
    sudo chown -R $USER:$USER $BACKUP_DIR

    # 克隆代码
    if [ -z "$GIT_REPO" ]; then
        read -p "请输入 Git 仓库地址: " GIT_REPO
    fi

    if [ -d "$APP_DIR/.git" ]; then
        warning "目录已存在，跳过克隆"
    else
        info "克隆代码仓库..."
        git clone $GIT_REPO $APP_DIR
        success "代码克隆完成"
    fi

    # 配置环境变量
    if [ ! -f "$APP_DIR/.env" ]; then
        info "创建环境变量文件..."
        cp $APP_DIR/.env.example $APP_DIR/.env
        warning "请编辑 $APP_DIR/.env 文件并配置环境变量"
        read -p "是否现在编辑 .env 文件? (y/n): " EDIT_ENV
        if [ "$EDIT_ENV" = "y" ] || [ "$EDIT_ENV" = "Y" ]; then
            nano $APP_DIR/.env
        fi
    fi

    # 安装依赖
    install_dependencies

    # 构建应用
    build_app

    # 启动应用
    start_app

    # 配置 PM2 开机自启
    info "配置 PM2 开机自启..."
    pm2 startup
    pm2 save
    success "PM2 开机自启配置完成"

    success "首次安装完成！"
    info "应用访问地址: http://localhost:5000"
}

# 函数：更新部署
update() {
    info "开始更新部署..."

    check_environment

    # 拉取最新代码
    info "拉取最新代码..."
    cd $APP_DIR
    git fetch origin
    git pull origin $(git rev-parse --abbrev-ref HEAD)
    success "代码更新完成"

    # 安装依赖
    install_dependencies

    # 构建应用
    build_app

    # 重启应用
    restart_app

    success "更新完成！"
}

# 函数：备份数据库
backup() {
    info "开始备份数据库..."

    # 读取数据库配置
    source $APP_DIR/.env

    # 创建备份目录
    mkdir -p $BACKUP_DIR

    # 生成备份文件名
    BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"

    # 备份数据库
    info "备份数据库到 $BACKUP_FILE..."
    pg_dump $DATABASE_URL > $BACKUP_FILE

    # 压缩备份文件
    gzip $BACKUP_FILE
    BACKUP_FILE="${BACKUP_FILE}.gz"

    success "数据库备份完成: $BACKUP_FILE"

    # 删除旧备份
    info "清理 30 天前的备份..."
    find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete
    success "清理完成"
}

# 函数：恢复数据库
restore() {
    info "开始恢复数据库..."

    # 列出可用的备份
    info "可用的备份文件:"
    ls -lh $BACKUP_DIR/backup_*.sql.gz 2>/dev/null || error "没有找到备份文件"

    # 选择备份文件
    read -p "请输入要恢复的备份文件名: " BACKUP_FILE
    BACKUP_FILE="$BACKUP_DIR/$BACKUP_FILE"

    if [ ! -f "$BACKUP_FILE" ]; then
        error "备份文件不存在: $BACKUP_FILE"
    fi

    # 确认恢复
    warning "警告: 此操作将覆盖当前数据库！"
    read -p "确定要恢复吗? (yes/no): " CONFIRM
    if [ "$CONFIRM" != "yes" ]; then
        info "操作已取消"
        exit 0
    fi

    # 读取数据库配置
    source $APP_DIR/.env

    # 恢复数据库
    info "恢复数据库..."
    gunzip -c $BACKUP_FILE | psql $DATABASE_URL

    success "数据库恢复完成"
}

# 函数：监控应用状态
monitor() {
    info "应用状态监控..."
    pm2 status
    echo ""
    pm2 describe $APP_NAME
}

# 函数：查看日志
logs() {
    info "查看应用日志..."
    pm2 logs $APP_NAME --lines 100 --nostream
}

# 主函数
main() {
    case "${1:-}" in
        --install)
            install
            ;;
        --update)
            update
            ;;
        --backup)
            backup
            ;;
        --restore)
            restore
            ;;
        --monitor)
            monitor
            ;;
        --logs)
            logs
            ;;
        *)
            echo "PVE Manager 部署脚本"
            echo ""
            echo "使用方法:"
            echo "  $0 --install    首次安装"
            echo "  $0 --update     更新部署"
            echo "  $0 --backup     备份数据库"
            echo "  $0 --restore    恢复数据库"
            echo "  $0 --monitor    监控应用状态"
            echo "  $0 --logs       查看日志"
            echo ""
            echo "示例:"
            echo "  GIT_REPO=https://github.com/user/pve-manager.git $0 --install"
            echo "  $0 --update"
            echo "  $0 --backup"
            exit 1
            ;;
    esac
}

# 运行主函数
main "$@"
