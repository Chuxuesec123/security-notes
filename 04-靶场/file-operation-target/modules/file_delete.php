<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot high"></span>
        <span class="badge badge-hard">困难</span>
        <h2 style="margin:0">🗑️ 文件删除漏洞</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>文件删除漏洞通常出现在删除文件/图片功能中，
        应用程序未对删除路径做严格过滤，攻击者可通过路径遍历删除服务器上的任意文件。
        危害极大，可删除配置、日志、甚至系统文件。
    </div>

    <div class="vuln-code">
        <span class="vuln-label">⚠️ 漏洞代码</span>
        <pre>&lt;?php
// 未过滤路径遍历的删除操作
$filename = $_POST['filename'];
$path = "uploads/" . $filename;
unlink($path);
?&gt;</pre>
    </div>

    <?php
    $upload_dir = __DIR__ . '/../uploads/';
    $delete_msg = '';

    if (isset($_POST['delete_file'])) {
        $target = $_POST['target_path'];

        // 安全检查：防止删除靶场核心文件
        $real_target = realpath($target);
        $real_base = realpath(__DIR__ . '/../');

        if ($real_target === false || $real_target === $real_base) {
            $delete_msg = '<div class="alert alert-danger"><strong>❌ 删除失败：</strong>无效的路径！</div>';
        } elseif (strpos($real_target, $real_base) !== 0) {
            // 模拟删除系统文件（实际只能删除靶场内文件）
            $delete_msg = '<div class="alert alert-warning"><strong>⚠️ 模拟提示：</strong>在真实环境中，你可以删除系统文件！<br>试图删除: ' . htmlspecialchars($target) . '</div>';
        } elseif (is_file($real_target)) {
            if (unlink($real_target)) {
                $delete_msg = '<div class="alert alert-success"><strong>✅ 文件删除成功！</strong><br>已删除: ' . htmlspecialchars($real_target) . '</div>';
            } else {
                $delete_msg = '<div class="alert alert-danger"><strong>❌ 删除失败：</strong>权限不足</div>';
            }
        } elseif (is_dir($real_target)) {
            // 删除目录（需要recursive）
            $delete_msg = '<div class="alert alert-danger"><strong>❌ 删除失败：</strong>不能直接删除目录，需指定文件</div>';
        } else {
            $delete_msg = '<div class="alert alert-danger"><strong>❌ 删除失败：</strong>文件不存在</div>';
        }
    }

    // 创建一些模拟文件供删除练习
    $demo_files = ['demo.txt', 'test.conf', 'backup.php', 'readme.md'];
    foreach ($demo_files as $df) {
        $df_path = $upload_dir . $df;
        if (!file_exists($df_path)) {
            file_put_contents($df_path, "This is a demo file: {$df}\nCreated for deletion practice.\n");
        }
    }
    ?>

    <?php echo $delete_msg; ?>

    <h3>📁 当前上传目录中的文件</h3>
    <div class="output-box">
        <?php
        $files = scandir($upload_dir);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $fpath = $upload_dir . $f;
            if (is_file($fpath)) {
                $size = filesize($fpath);
                $icon = (strpos($f, '.php') !== false) ? '🐘' : '📄';
                echo htmlspecialchars("{$icon} {$f}  ({$size} bytes)") . "\n";
            }
        }
        ?>
    </div>

    <h3>🗑️ 删除文件</h3>

    <div class="alert alert-danger">
        <strong>⚠️ 注意：</strong>本模块演示任意文件删除漏洞。<br>
        <strong>🎯 挑战目标：</strong>删除 <code>config.php</code> 文件（模拟删除网站配置文件）。
        提示：使用路径遍历 <code>../config.php</code>
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="target_path">要删除的文件路径（相对于靶场根目录）：</label>
            <div class="form-row">
                <input type="text" name="target_path" id="target_path"
                       placeholder="例如: ../config.php"
                       value="<?php echo htmlspecialchars($_POST['target_path'] ?? 'demo.txt'); ?>">
                <button type="submit" name="delete_file" class="submit-btn danger">删除 🗑️</button>
            </div>
        </div>
    </form>

    <div class="tip-collapse">
        <div class="tip-header">💡 通关提示</div>
        <div class="tip-content">
            <p><strong>文件删除漏洞的危害：</strong></p>
            <ul>
                <li><strong>删除配置文件</strong>：删除 config.php 导致网站无法连接数据库</li>
                <li><strong>删除安装锁文件</strong>：删除 install.lock 可重装CMS</li>
                <li><strong>删除 .htaccess</strong>：解除URL重写规则</li>
                <li><strong>删除日志文件</strong>：销毁攻击痕迹</li>
                <li><strong>删除索引文件</strong>：如 index.php，导致网站瘫痪</li>
            </ul>
            <p><strong>常见Payload：</strong></p>
            <ul class="payload-list">
                <li><code>../config.php</code> - 删除数据库配置</li>
                <li><code>../index.php</code> - 删除网站首页</li>
                <li><code>.htaccess</code> - 删除Apache配置</li>
                <li><code>../../../etc/passwd</code> - 删除系统文件（真实环境）</li>
            </ul>
        </div>
    </div>
</div>
