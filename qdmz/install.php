<?php
/**
 * Excel查询系统安装脚本
 * 用于安装必要的依赖和配置系统
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Excel查询系统 - 安装向导</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0180CF;
            border-bottom: 2px solid #0180CF;
            padding-bottom: 10px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .btn {
            background: #0180CF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #015a99;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Excel查询系统 - 安装向导</h1>
        
        <div class="step">
            <h2>步骤 1: 安装依赖库</h2>
            <p>本系统需要安装 PhpSpreadsheet 库来支持 XLSX 文件格式。请按照以下方法之一进行安装：</p>
            
            <h3>方法 1: 使用 Composer (推荐)</h3>
            <p>在项目根目录打开命令行，执行以下命令：</p>
            <div class="code">
                composer install
            </div>
            
            <h3>方法 2: 手动下载</h3>
            <p>如果您没有安装 Composer，可以手动下载库文件：</p>
            <ol>
                <li>访问 <a href="https://github.com/PHPOffice/PhpSpreadsheet" target="_blank">https://github.com/PHPOffice/PhpSpreadsheet</a></li>
                <li>下载最新版本的库文件</li>
                <li>将文件解压到项目根目录下的 <code>vendor</code> 文件夹</li>
                <li>确保 <code>vendor/autoload.php</code> 文件存在</li>
            </ol>
        </div>
        
        <div class="step">
            <h2>步骤 2: 验证安装</h2>
            <p>安装完成后，请验证系统是否能正常工作：</p>
            <ol>
                <li>将 XLS 和 XLSX 格式的 Excel 文件放入 <code>shujukufangzheli</code> 目录</li>
                <li>访问 <a href="./index.php">首页</a> 测试系统功能</li>
                <li>尝试查询数据以确认系统正常运行</li>
            </ol>
        </div>
        
        <div class="step">
            <h2>系统特性</h2>
            <ul>
                <li>支持 XLS 和 XLSX 两种 Excel 格式</li>
                <li>统一使用 UTF-8 编码，解决中文乱码问题</li>
                <li>兼容 PHP 7.0 及以上版本</li>
                <li>现代化的用户界面</li>
                <li>优化的代码结构</li>
            </ul>
        </div>
        
        <a href="./index.php" class="btn">进入系统首页</a>
    </div>
</body>
</html>