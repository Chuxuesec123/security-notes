<?php
/**
 * DVWA 命令注入靶场
 * 低/中/高三级难度
 */

$result_html = '';
$cmd_display = '';
$filtered_display = '';
$return_code = -1;
$level_label = 'low';
$level_msg = '';

if (isset($_POST['ping_submit']) && !empty($_POST['ip_input'])) {
    $input = $_POST['ip_input'];
    $level_label = $_POST['level'] ?? 'low';
    $filtered = $input;

    switch ($level_label) {
        case 'medium':
            // 中级：只过滤分号
            $filtered = str_replace([';', '；'], '', $input);
            $cmd_display = "ping -c 2 " . $filtered;
            $level_msg = '<div class="alert alert-warning"><strong>🟡 中级过滤：</strong> 已过滤 <code>;</code>，试试 <code>|</code> <code>||</code> <code>&&</code> <code>%0a</code></div>';
            break;

        case 'high':
            // 高级：过滤多个危险字符，但保留换行和$IFS的绕过可能
            // 过滤: ; & | 空格 / < > 但保留 $ 用于变量技巧
            $filtered = preg_replace('/[;&|<>\/\s]/', '', $input);
            $cmd_display = "ping -c 2 " . $filtered;
            $level_msg = '<div class="alert alert-danger"><strong>🔴 高级过滤：</strong> 已过滤 <code>;</code> <code>&amp;</code> <code>|</code> <code>/</code> <code>&lt;&gt;</code> 和空格';
            $level_msg .= '<br>💡 提示：试试 <code>$IFS</code>、<code>${}</code> 变量截取、<code>\</code> 反斜杠拼接、<code>%0a</code> 换行注入</div>';
            break;

        default:
            // 低级：无过滤
            $filtered = $input;
            $cmd_display = "ping -c 2 " . $input;
            $level_msg = '<div class="alert alert-success"><strong>🟢 低级：</strong> 无过滤，直接注入！</div>';
            break;
    }

    // 执行
    ob_start();
    system($cmd_display . ' 2>&1', $return_code);
    $output = ob_get_clean();

    $filtered_display = htmlspecialchars($filtered);
    $cmd_escaped = htmlspecialchars($cmd_display);
    $output_html = $output ? htmlspecialchars($output) : '<span class="info">（无输出）</span>';

    $result_html = <<<HTML
    $level_msg
    <div class="status-bar">
        <span><span class="label">执行命令:</span> <code>$cmd_escaped</code></span>
    </div>
    <div class="output-section">
        <h4>执行结果：</h4>
        <div class="output-box">$output_html</div>
        <div class="status-bar">
            <span><span class="label">返回码:</span> $return_code</span>
            <span><span class="label">难度:</span> $level_label</span>
            <span><span class="label">过滤后:</span> <code>$filtered_display</code></span>
        </div>
    </div>
HTML;
}
?>
<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot" id="levelDot" style="background:#27ae60;"></span>
        <span class="badge" id="levelBadge" style="background:#d4edda;color:#155724;">简单</span>
        <h2 style="margin:0">🌐 DVWA命令注入</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>模拟 DVWA 靶场的命令注入场景。输入 IP 执行 ping 命令，
        通过命令连接符注入恶意命令，分低/中/高三级。
    </div>

    <div class="difficulty-selector">
        <span style="font-weight:600;margin-right:8px;">选择难度：</span>
        <button class="diff-btn active" data-level="low" onclick="setLevel('low')">🟢 低级（无过滤）</button>
        <button class="diff-btn" data-level="medium" onclick="setLevel('medium')">🟡 中级（过滤;）</button>
        <button class="diff-btn" data-level="high" onclick="setLevel('high')">🔴 高级（过滤;&|和空格）</button>
    </div>

    <form method="POST">
        <input type="hidden" name="level" id="levelInput" value="low">
        <div class="form-group">
            <label for="ip_input">输入 IP 地址：</label>
            <div class="form-row">
                <input type="text" name="ip_input" id="ip_input" 
                       placeholder="例如: 127.0.0.1"
                       value="<?php echo htmlspecialchars($_POST['ip_input'] ?? '127.0.0.1'); ?>">
                <button type="submit" name="ping_submit" class="submit-btn">Ping 🚀</button>
            </div>
        </div>
    </form>

    <?php echo $result_html; ?>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            📚 查看源码
        </div>
        <div class="tip-content">
            <h4>低级 - 无过滤</h4>
            <div class="source-code"><pre><span class="php">&lt;?php
\$cmd = "ping -c 2 " . \$_POST['ip_input'];
system(\$cmd);
?&gt;</span></pre></div>

            <h4>中级 - 过滤分号</h4>
            <div class="source-code"><pre><span class="php">&lt;?php
\$input = str_replace([';', '；'], '', \$_POST['ip_input']);
\$cmd = "ping -c 2 " . \$input;
system(\$cmd);
// 绕过: 用 | || && %0a 替代 ;
?&gt;</span></pre></div>

            <h4>高级 - 过滤;&|空格/&lt;&gt;</h4>
            <div class="source-code"><pre><span class="php">&lt;?php
\$filtered = preg_replace('/[;&|&lt;&gt;\/\s]/', '', \$input);
\$cmd = "ping -c 2 " . \$filtered;
system(\$cmd);
// 保留字符: \$ ( ) { } \ %0a
// 绕过: \$IFS \${\} 反斜杠拼接
?&gt;</span></pre></div>
        </div>
    </div>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            🎯 Payload 参考
        </div>
        <div class="tip-content">
            <h4>低级：</h4>
            <ul class="payload-list">
                <li><code>127.0.0.1; whoami</code></li>
                <li><code>127.0.0.1 | whoami</code></li>
                <li><code>127.0.0.1 && whoami</code></li>
                <li><code>127.0.0.1 || whoami</code></li>
                <li><code>127.0.0.1$(whoami)</code></li>
                <li><code>127.0.0.1%0awhoami</code></li>
            </ul>

            <h4>中级（; 被过滤）：</h4>
            <ul class="payload-list">
                <li><code>127.0.0.1 | whoami</code></li>
                <li><code>127.0.0.1 || whoami</code></li>
                <li><code>127.0.0.1 && whoami</code></li>
                <li><code>127.0.0.1%0awhoami</code></li>
            </ul>

        </div>
    </div>
</div>

<script>
function setLevel(level) {
    document.getElementById('levelInput').value = level;
    document.getElementById('ip_input').focus();
    
    const btns = document.querySelectorAll('.diff-btn');
    btns.forEach(b => b.classList.remove('active'));
    document.querySelector(`.diff-btn[data-level="${level}"]`).classList.add('active');
    
    const dot = document.getElementById('levelDot');
    const badge = document.getElementById('levelBadge');
    
    const config = {
        'low': { color: '#27ae60', bg: '#d4edda', text: '#155724', label: '简单' },
        'medium': { color: '#f39c12', bg: '#fff3cd', text: '#856404', label: '中等' },
        'high': { color: '#e74c3c', bg: '#f8d7da', text: '#721c24', label: '困难' }
    };
    
    dot.style.background = config[level].color;
    badge.style.background = config[level].bg;
    badge.style.color = config[level].text;
    badge.textContent = config[level].label;
}
</script>
