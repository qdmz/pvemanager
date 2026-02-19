@echo off
echo 正在安装Python依赖...

REM 检查Python是否安装
python --version >nul 2>&1
if errorlevel 1 (
    echo 错误: 未找到Python。请先安装Python 3.6或更高版本。
    pause
    exit /b 1
)

REM 检查pip是否可用
python -m pip --version >nul 2>&1
if errorlevel 1 (
    echo 正在升级pip...
    python -m ensurepip --upgrade
    if errorlevel 1 (
        echo 错误: 无法安装pip
        pause
        exit /b 1
    )
)

REM 安装依赖
echo 正在安装依赖包...
python -m pip install -r requirements.txt

if errorlevel 0 (
    echo.
    echo 依赖安装成功！
    echo.
    echo 现在可以运行应用了，执行: python app.py
    echo.
) else (
    echo.
    echo 依赖安装失败
)

pause