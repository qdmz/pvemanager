@echo off
echo Starting PVE Manager...

REM 检查 Docker 是否安装
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Docker is not installed. Please install Docker first.
    pause
    exit /b 1
)

REM 检查 Docker Compose 是否安装
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Docker Compose is not installed. Please install Docker Compose first.
    pause
    exit /b 1
)

REM 创建必要的目录
if not exist config mkdir config
if not exist static\css mkdir static\css
if not exist static\js mkdir static\js
if not exist server\migrations mkdir server\migrations

REM 启动服务
echo Starting PostgreSQL database...
docker-compose up -d postgres

echo Waiting for database to be ready...
timeout /t 5 /nobreak >nul

echo Building and starting server...
docker-compose up -d server

echo.
echo PVE Manager started successfully!
echo.
echo Login credentials:
echo    Email: admin@pve.local
echo    Password: admin123
echo.
echo Access the web interface at: http://localhost:8080
echo.
echo View logs: docker-compose logs -f
echo Stop services: docker-compose down
echo.
pause
