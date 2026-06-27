<?php
/**
 * RCE靶场 - 首页
 */
session_start();
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCE漏洞靶场 - 网络安全学习</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🛡️ RCE漏洞综合靶场</h1>
            <p class="subtitle">远程代码/命令执行漏洞实战</p>
            <nav class="main-nav">
                <a href="?page=home">🏠 首页</a>
                <a href="?page=command_inject">💻 命令注入基础</a>
                <a href="?page=dvwa_ping">🌐 DVWA-Ping注入</a>
                <a href="?page=code_exec">📝 代码执行漏洞</a>
                <a href="?page=bypass">🔄 绕过过滤挑战</a>
                <a href="?page=file_rw">📂 文件读写利用</a>
                <a href="?page=blind_rce">👁️ 无回显RCE</a>
                <a href="?page=deserialize">🧩 反序列化RCE</a>
                <a href="?page=cheatsheet">📖 命令速查</a>
            </nav>
        </header>

        <main>
            <?php
            switch ($page) {
                case 'command_inject':
                    include 'modules/command_inject.php';
                    break;
                case 'dvwa_ping':
                    include 'modules/dvwa_ping.php';
                    break;
                case 'code_exec':
                    include 'modules/code_exec.php';
                    break;
                case 'bypass':
                    include 'modules/bypass.php';
                    break;
                case 'file_rw':
                    include 'modules/file_rw.php';
                    break;
                case 'blind_rce':
                    include 'modules/blind_rce.php';
                    break;
                case 'deserialize':
                    include 'modules/deserialize.php';
                    break;
                case 'cheatsheet':
                    include 'modules/cheatsheet.php';
                    break;
                default:
                    include 'home.php';
            }
            ?>
        </main>

        <footer>
            <p>⚠️ 本靶场仅供授权的安全学习使用，请勿用于非法用途</p>
        </footer>
    </div>
</body>
</html>
