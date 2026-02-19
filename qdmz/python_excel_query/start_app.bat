@echo off
echo 启动Excel查询系统...

REM 检查Python是否安装
python --version >nul 2>&1
if errorlevel 1 (
    echo 错误: 未找到Python。请先安装Python 3.6或更高版本。
    pause
    exit /b 1
)

REM 检查依赖是否已安装
if not exist "venv" (
    echo 创建虚拟环境...
    python -m venv venv
)

REM 激活虚拟环境并安装依赖
call venv\Scripts\activate.bat
pip install -r requirements.txt

echo.
echo 启动Excel查询系统...
echo 请访问 http://localhost:5000
echo 按 Ctrl+C 停止服务
echo.
python app.py

pause