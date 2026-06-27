<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot medium"></span>
        <span class="badge badge-medium">中等</span>
        <h2 style="margin:0">⬇️ 文件下载/读取漏洞 - 路径遍历</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>文件下载漏洞通常出现在文件下载功能中，
        应用程序未对用户输入的下载路径做严格过滤，导致攻击者可以通过 <code>../</code> 路径遍历读取任意文件。
    </div>

    <div class="vuln-code">
        <span class="vuln-label">⚠️ 漏洞代码</span>
        <pre>&lt;?php
// 直接使用用户传入的文件名，未过滤路径遍历
$file = $_GET['file'];
$path = "downloads/" . $file;
readfile($path);  // 或者 file_get_contents + echo
?&gt;</pre>
    </div>

    <h3>📂 文件下载</h3>
    <form method="GET">
        <input type="hidden" name="page" value="file_download">
        <div class="form-group">
            <label for="file_path">下载文件路径：</label>
            <div class="form-row">
                <input type="text" name="file" id="file_path"
                       placeholder="例如: ../../../../etc/passwd"
                       value="<?php echo htmlspecialchars($_GET['file'] ?? ''); ?>">
                <button type="submit" class="submit-btn">下载 ⬇️</button>
            </div>
        </div>
    </form>

    <div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="?page=file_download&file=../config.php" class="submit-btn info">📄 读取 config.php</a>
        <a href="?page=file_download&file=../flag.txt" class="submit-btn success">🏁 读取 flag.txt</a>
        <a href="?page=file_download&file=../../../../etc/passwd" class="submit-btn warning">🐧 /etc/passwd</a>
    </div>

    <?php
    if (isset($_GET['file'])) {
        $requested_file = $_GET['file'];
        // 模拟存储目录为 downloads/
        $base_dir = __DIR__ . '/../';
        $full_path = $base_dir . $requested_file;

        // 安全检查：防止访问到靶场外的文件系统
        $real_base = realpath(__DIR__ . '/../');
        $real_path = realpath($full_path);

        echo '<div class="output-section">';
        echo '<h4>📄 文件内容：</h4>';
        echo '<div class="output-box">';

        if ($real_path === false) {
            echo '<span class="error">❌ 路径无效或文件不存在</span>';
        } elseif (strpos($real_path, $real_base) !== 0) {
            // 超出靶场范围 - 真实环境可访问，这里模拟
            echo '<span class="info">⚠️ 已超出靶场范围（\'/etc\' 等系统文件在真实环境中可读取）</span>';
            echo "\n\n--- 模拟读取 /etc/passwd ---\n";
            echo "root:x:0:0:root:/root:/bin/bash\n";
            echo "daemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin\n";
            echo "bin:x:2:2:bin:/bin:/usr/sbin/nologin\n";
            echo "www-data:x:33:33:www-data:/var/www:/usr/sbin/nologin\n";
            echo "mysql:x:100:101:MySQL Server:/var/run/mysqld:/usr/sbin/nologin\n";
        } elseif (is_file($real_path) && is_readable($real_path)) {
            $content = file_get_contents($real_path);
            $ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
            if ($ext === 'php') {
                echo htmlspecialchars($content);
            } else {
                echo $content;
            }
            echo "\n\n--- 文件信息 ---\n";
            echo "大小: " . filesize($real_path) . " bytes\n";
            echo "路径: " . htmlspecialchars($real_path);
        } elseif (is_dir($real_path)) {
            echo '<span class="info">📁 目录列表：</span>';
            $files = scandir($real_path);
            foreach ($files as $f) {
                if ($f !== '.' && $f !== '..') {
                    $fpath = $real_path . DIRECTORY_SEPARATOR . $f;
                    $type = is_dir($fpath) ? '[DIR]' : '[FILE]';
                    $size = is_file($fpath) ? ' (' . filesize($fpath) . ' B)' : '';
                    echo "\n" . htmlspecialchars("{$type} {$f}{$size}");
                }
            }
        } else {
            echo '<span class="error">❌ 文件不存在或不可读</span>';
        }

        echo '</div>';
        echo '</div>';
    }
    ?>

    <div class="tip-collapse">
        <div class="tip-header">💡 通关提示</div>
        <div class="tip-content">
            <p><strong>目标：</strong>读取配置文件 <code>config.php</code> 和 <code>flag.txt</code></p>
            <p><strong>常用Payload：</strong></p>
            <ul class="payload-list">
                <li><code>../../../etc/passwd</code> - Linux用户文件</li>
                <li><code>../../../etc/shadow</code> - Linux密码哈希</li>
                <li><code>../../../var/www/html/config.php</code> - Web配置</li>
                <li><code>..\..\..\windows\win.ini</code> - Windows</li>
                <li><code>..\..\..\windows\system32\drivers\etc\hosts</code></li>
            </ul>
        </div>
    </div>
</div>
