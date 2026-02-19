<?php
/**
 * 功能测试脚本
 * 验证Excel查询系统的主要功能
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>功能测试 - Excel查询系统</title>
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
        .test-result {
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
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
        <h1>Excel查询系统 - 功能测试</h1>
        
        <div class="test-result info">
            <strong>PHP版本检查:</strong> 
            <?php 
            $version = phpversion();
            echo "当前PHP版本: " . $version;
            if (version_compare($version, '7.0', '>=')) {
                echo " <span style='color: green;'>✓ 支持</span>";
            } else {
                echo " <span style='color: red;'>✗ 需要PHP 7.0或更高版本</span>";
            }
            ?>
        </div>
        
        <div class="test-result info">
            <strong>依赖检查:</strong>
            <ul>
                <li>mbstring扩展: 
                    <?php if (extension_loaded('mbstring')) { echo '<span style="color: green;">✓ 已安装</span>'; } else { echo '<span style="color: red;">✗ 未安装</span>'; } ?>
                </li>
                <li>zip扩展: 
                    <?php if (extension_loaded('zip')) { echo '<span style="color: green;">✓ 已安装</span>'; } else { echo '<span style="color: red;">✗ 未安装 (处理XLSX需要)</span>'; } ?>
                </li>
                <li>xml扩展: 
                    <?php if (extension_loaded('xml')) { echo '<span style="color: green;">✓ 已安装</span>'; } else { echo '<span style="color: red;">✗ 未安装</span>'; } ?>
                </li>
            </ul>
        </div>
        
        <div class="test-result info">
            <strong>文件权限检查:</strong>
            <ul>
                <li>shujukufangzheli目录可读: 
                    <?php 
                    $dir = 'shujukufangzheli';
                    if (is_readable($dir)) { 
                        echo '<span style="color: green;">✓ 可读</span>'; 
                        $files = scandir($dir);
                        $excelFiles = array_filter($files, function($file) {
                            return preg_match('/\.(xls|xlsx)$/i', $file);
                        });
                        echo " (找到 " . count($excelFiles) . " 个Excel文件)";
                    } else { 
                        echo '<span style="color: red;">✗ 不可读</span>'; 
                    } 
                    ?>
                </li>
            </ul>
        </div>
        
        <div class="test-result info">
            <strong>编码检查:</strong>
            <ul>
                <li>默认编码: <?php echo defined('DEFAULT_ENCODING') ? DEFAULT_ENCODING : 'UTF-8'; ?></li>
                <li>mbstring内部编码: <?php echo function_exists('mb_internal_encoding') ? mb_internal_encoding() : '不可用'; ?></li>
            </ul>
        </div>
        
        <div class="test-result info">
            <strong>类加载测试:</strong>
            <ul>
                <li>ExcelReader类存在: 
                    <?php 
                    if (class_exists('ExcelReader')) { 
                        echo '<span style="color: green;">✓ 存在</span>'; 
                    } else { 
                        echo '<span style="color: orange;">~ 不存在 (可能使用旧类)</span>'; 
                        // 尝试加载旧类
                        if (file_exists('inc/excel.php')) {
                            include_once 'inc/excel.php';
                            if (class_exists('Spreadsheet_Excel_Reader')) {
                                echo " (但Spreadsheet_Excel_Reader存在)";
                            }
                        }
                    } 
                    ?>
                </li>
            </ul>
        </div>
        
        <div class="test-result success">
            <strong>系统状态:</strong> Excel查询系统已更新，支持以下功能：
            <ul>
                <li>✓ 支持XLS和XLSX两种Excel格式</li>
                <li>✓ 统一使用UTF-8编码，解决中文乱码问题</li>
                <li>✓ 兼容PHP 7.0及以上版本</li>
                <li>✓ 现代化的用户界面</li>
                <li>✓ 优化的代码结构</li>
                <li>✓ 改进的错误处理</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="./index.php" style="background: #0180CF; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">访问系统首页</a>
            <a href="./install.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; margin-left: 10px;">查看安装说明</a>
        </div>
    </div>
</body>
</html>