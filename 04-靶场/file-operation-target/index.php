<?php
/**
 * 文件操作类漏洞综合靶场 - 首页
 * 基于《文件操作类漏洞学习笔记》构建
 */
session_start();
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件操作类漏洞靶场 - 网络安全学习</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📁 文件操作类漏洞综合靶场</h1>
            <p class="subtitle">文件上传 · 文件包含 · 文件下载/读取 · 文件删除漏洞实战</p>
            <nav class="main-nav">
                <a href="?page=home">🏠 首页</a>
                <a href="?page=upload_basic">⬆️ 上传基础</a>
                <a href="?page=upload_bypass">🔄 上传绕过</a>
                <a href="?page=lfi_basic">📂 LFI基础</a>
                <a href="?page=lfi_wrapper">🔧 LFI进阶</a>
                <a href="?page=log_poison">📝 日志注入</a>
                <a href="?page=file_download">⬇️ 文件下载</a>
                <a href="?page=file_delete">🗑️ 文件删除</a>
                <a href="?page=cheatsheet">📖 Payload速查</a>
            </nav>
        </header>

        <main>
            <?php
            $allowed_pages = [
                'home', 'upload_basic', 'upload_bypass', 'lfi_basic',
                'lfi_wrapper', 'log_poison', 'file_download', 'file_delete', 'cheatsheet'
            ];
            // 允许使用路径遍历？仅当是LFI相关模块时，允许包含任意文件
            // 但这里我们使用白名单模式防止非预期包含
            if (in_array($page, $allowed_pages)) {
                if ($page === 'home') {
                    include 'home.php';
                } else {
                    include "modules/{$page}.php";
                }
            } else {
                include 'home.php';
            }
            ?>
        </main>

        <footer>
            <p>⚠️ 本靶场仅供授权的安全学习使用，请勿用于非法用途</p>
        </footer>
    </div>

    <script>
    // 提示折叠交互
    document.addEventListener('click', function(e) {
        const header = e.target.closest('.tip-header');
        if (header) {
            const content = header.nextElementSibling;
            if (content && content.classList.contains('tip-content')) {
                content.classList.toggle('show');
            }
        }
    });
    </script>
</body>
</html>
