<?php
session_start();

// 引入配置文件
include_once 'inc/conn.php';

// 如果配置文件中没有定义管理员信息，则使用默认值
if (!isset($admin_user)) $admin_user = 'admin';
if (!isset($admin_pass)) $admin_pass = 'admin123'; // 实际部署时请修改此默认密码

$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';

// 处理登录
if ($action == 'login') {
    $input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $input_pass = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if ($input_user == $admin_user && $input_pass == $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $admin_user;
        header('Location: admin.php');
        exit;
    } else {
        $message = '用户名或密码错误！';
    }
}

// 处理登出
if ($action == 'logout') {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// 检查是否已登录
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

// 如果已登录，处理文件管理操作
if ($is_logged_in) {
    // 上传文件
    if (isset($_POST['upload'])) {
        $upload_dir = $UpDir;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
            $file_tmp = $_FILES['excel_file']['tmp_name'];
            $file_name = $_FILES['excel_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // 验证文件类型
            if (in_array($file_ext, ['xls', 'xlsx'])) {
                // 验证文件名，支持中文，只允许字母、数字、中文、下划线、横线、点号
                $clean_file_name = preg_replace('/[<>:"/\\|?*]/', '_', $file_name); // 移除Windows文件名不允许的字符
                
                // 检查是否文件名被修改了
                if ($clean_file_name !== $file_name) {
                    $message = '原文件名包含不允许的字符，已自动替换为下划线。';
                    $file_name = $clean_file_name;
                }
                
                $target_file = $upload_dir . '/' . $file_name;
                
                if (file_exists($target_file)) {
                    $message = '文件已存在，请先删除或重命名原文件！';
                } else if (move_uploaded_file($file_tmp, $target_file)) {
                    $message = '文件上传成功！' . (isset($message) ? ' ' . $message : '');
                } else {
                    $message = '文件上传失败！';
                }
            } else {
                $message = '只允许上传XLS和XLSX格式的文件！';
            }
        } else {
            $message = '请选择要上传的文件！';
        }
    }
    
    // 删除文件
    if ($action == 'delete' && isset($_GET['file'])) {
        $file_to_delete = basename($_GET['file']);
        $file_path = $UpDir . '/' . $file_to_delete;
        
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                $message = '文件删除成功！';
            } else {
                $message = '文件删除失败！';
            }
        } else {
            $message = '文件不存在！';
        }
    }
    
    // 重命名文件
    if ($action == 'rename' && isset($_POST['old_filename']) && isset($_POST['new_filename'])) {
        $old_filename = basename($_POST['old_filename']);
        $new_filename = trim($_POST['new_filename']);
        
        // 验证新文件名
        if (empty($new_filename)) {
            $message = '新文件名不能为空！';
        } else if (!preg_match('/^[\w\x{4e00}-\x{9fa5}.-]+\.(xls|xlsx)$/u', $new_filename)) {
            $message = '文件名只能包含字母、数字、中文、下划线、横线和点号，且必须是.xls或.xlsx扩展名！';
        } else {
            $old_file_path = $UpDir . '/' . $old_filename;
            $new_file_path = $UpDir . '/' . $new_filename;
            
            if (!file_exists($old_file_path)) {
                $message = '原文件不存在！';
            } else if (file_exists($new_file_path)) {
                $message = '目标文件已存在！';
            } else if (rename($old_file_path, $new_file_path)) {
                $message = '文件重命名成功！';
            } else {
                $message = '文件重命名失败！';
            }
        }
    }
    
    // 修改密码
    if (isset($_POST['change_pass'])) {
        $old_pass = isset($_POST['old_pass']) ? trim($_POST['old_pass']) : '';
        $new_pass = isset($_POST['new_pass']) ? trim($_POST['new_pass']) : '';
        $confirm_pass = isset($_POST['confirm_pass']) ? trim($_POST['confirm_pass']) : '';
        
        if ($old_pass != $admin_pass) {
            $message = '原密码错误！';
        } elseif ($new_pass != $confirm_pass) {
            $message = '新密码与确认密码不一致！';
        } elseif (strlen($new_pass) < 6) {
            $message = '新密码长度不能少于6位！';
        } else {
            // 更新密码并保存到conn.php
            $conn_content = file_get_contents('inc/conn.php');
            
            // 替换$admin_pass的值
            $conn_content = preg_replace('/(\$admin_pass\s*=\s*[\'"]).*?([\'"])/', '${1}' . $new_pass . '${2}', $conn_content);
            
            if (file_put_contents('inc/conn.php', $conn_content)) {
                $admin_pass = $new_pass;
                $message = '密码修改成功！';
            } else {
                $message = '密码修改失败，无法写入配置文件！';
            }
        }
    }
}

// 获取文件列表
$files = [];
if ($is_logged_in) {
    if (is_dir($UpDir)) {
        $dir = opendir($UpDir);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($file_ext, ['xls', 'xlsx'])) {
                    $files[] = $file;
                }
            }
        }
        closedir($dir);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Excel文件管理后台</title>
    <style>
        body {
            font-family: "microsoft yahei", "微软雅黑", Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #0180CF;
            border-bottom: 2px solid #0180CF;
            padding-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"], input[type="password"], input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            background: #0180CF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #015a99;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .file-list {
            margin-top: 20px;
        }
        .file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-actions {
            display: flex;
            gap: 5px;
        }
        
        .file-name {
            word-break: break-all;
            max-width: 70%;
        }
        .login-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .tab {
            margin-bottom: 20px;
        }
        .tab button {
            background: #e9ecef;
            border: 1px solid #ddd;
            padding: 10px 20px;
            cursor: pointer;
        }
        .tab button.active {
            background: #0180CF;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Excel文件管理后台</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '成功') ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$is_logged_in): ?>
            <div class="login-form">
                <h2>管理员登录</h2>
                <form method="post" action="admin.php?action=login">
                    <div class="form-group">
                        <label for="username">用户名:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">密码:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn">登录</button>
                </form>
            </div>
        <?php else: ?>
            <div class="welcome">
                <p>欢迎, <strong><?php echo htmlspecialchars($_SESSION['admin_user']); ?></strong> | 
                <a href="admin.php?action=logout" class="btn btn-danger">退出登录</a></p>
            </div>
            
            <div class="tab">
                <button class="tab-button active" onclick="showTab('upload')">文件上传</button>
                <button class="tab-button" onclick="showTab('manage')">文件管理</button>
                <button class="tab-button" onclick="showTab('password')">修改密码</button>
            </div>
            
            <div id="upload" class="tab-content active">
                <h3>上传Excel文件</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="excel_file">选择Excel文件 (XLS/XLSX):</label>
                        <input type="file" id="excel_file" name="excel_file" accept=".xls,.xlsx" required>
                    </div>
                    <button type="submit" name="upload" class="btn btn-success">上传文件</button>
                </form>
            </div>
            
            <div id="manage" class="tab-content">
                <h3>文件管理</h3>
                <div class="file-list">
                    <?php if (count($files) > 0): ?>
                        <?php foreach ($files as $file): ?>
                            <div class="file-item">
                                <span class="file-name" id="file-<?php echo urlencode($file); ?>"><?php echo htmlspecialchars($file); ?></span>
                                <div class="file-actions">
                                    <a href="<?php echo $UpDir . '/' . urlencode($file); ?>" class="btn" download>下载</a>
                                    <button onclick="showRenameForm('<?php echo addslashes($file); ?>')" class="btn btn-success">重命名</button>
                                    <a href="admin.php?action=delete&file=<?php echo urlencode($file); ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('确定要删除文件 <?php echo htmlspecialchars($file); ?> 吗？')">删除</a>
                                </div>
                            </div>
                            <div id="rename-form-<?php echo urlencode($file); ?>" style="display:none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <form method="post" action="admin.php?action=rename" style="display:flex; gap: 10px;">
                                    <input type="hidden" name="old_filename" value="<?php echo htmlspecialchars($file); ?>">
                                    <input type="text" name="new_filename" value="<?php echo htmlspecialchars($file); ?>" required style="flex: 1; padding: 5px;">
                                    <button type="submit" class="btn btn-success">确认</button>
                                    <button type="button" onclick="hideRenameForm('<?php echo urlencode($file); ?>')" class="btn">取消</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>暂无Excel文件</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="password" class="tab-content">
                <h3>修改密码</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="old_pass">原密码:</label>
                        <input type="password" id="old_pass" name="old_pass" required>
                    </div>
                    <div class="form-group">
                        <label for="new_pass">新密码:</label>
                        <input type="password" id="new_pass" name="new_pass" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_pass">确认新密码:</label>
                        <input type="password" id="confirm_pass" name="confirm_pass" required>
                    </div>
                    <button type="submit" name="change_pass" class="btn btn-success">修改密码</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function showTab(tabName) {
            // 隐藏所有内容区域
            var contents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < contents.length; i++) {
                contents[i].classList.remove('active');
            }
            
            // 移除所有按钮的active类
            var buttons = document.getElementsByClassName('tab-button');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
            }
            
            // 显示选中的内容区域
            document.getElementById(tabName).classList.add('active');
            
            // 激活选中的按钮
            event.target.classList.add('active');
        }
        
        function showRenameForm(filename) {
            // 隐藏所有重命名表单
            var forms = document.querySelectorAll('[id^="rename-form-"]');
            for (var i = 0; i < forms.length; i++) {
                forms[i].style.display = 'none';
            }
            
            // 显示当前文件的重命名表单
            document.getElementById('rename-form-' + encodeURIComponent(filename)).style.display = 'block';
        }
        
        function hideRenameForm(filename) {
            document.getElementById('rename-form-' + encodeURIComponent(filename)).style.display = 'none';
        }
    </script>
</body>
</html>