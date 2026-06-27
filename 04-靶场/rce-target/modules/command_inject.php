<?php
/**
 * 命令注入基础模块
 * 演示 system/exec/shell_exec/passthru/反引号 等命令执行函数
 * 所有PHP逻辑在顶部集中处理，避免模板混合中的语法问题
 */

$cmd_output_html = '';
$cmd_error_html = '';
$cmd_func_name = '';
$cmd_return_code = -1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cmd_input'])) {
    $func = $_POST['func_type'] ?? 'system';
    $raw_cmd = $_POST['cmd_input'];
    $exec_out_arr = [];
    $return_var = -1;
    $output_str = '';
    $error_msg = '';
    
    ob_start();
    try {
        switch ($func) {
            case 'system':
                system($raw_cmd, $return_var);
                $output_str = ob_get_contents();
                break;
            case 'exec':
                exec($raw_cmd, $exec_out_arr, $return_var);
                $output_str = implode("\n", $exec_out_arr);
                break;
            case 'shell_exec':
                $output_str = shell_exec($raw_cmd);
                break;
            case 'passthru':
                passthru($raw_cmd, $return_var);
                $output_str = ob_get_contents();
                break;
            case 'backtick':
                $output_str = `$raw_cmd`;
                break;
        }
    } catch (Throwable $e) {
        $error_msg = $e->getMessage();
    }
    ob_end_clean();
    
    if ($output_str === null || $output_str === '') {
        $output_str = '（命令无输出或执行失败）';
    }
    
    $cmd_func_name = $func;
    $cmd_return_code = $return_var;
    
    if ($error_msg) {
        $cmd_error_html = '<div class="alert alert-danger"><strong>执行出错：</strong> ' . htmlspecialchars($error_msg) . '</div>';
    }
    
    $cmd_output_html = '
    <div class="output-section">
        <h4>执行结果（' . htmlspecialchars($func) . '()）：</h4>
        <div class="output-box">' . htmlspecialchars($output_str) . '</div>
        <div class="status-bar">
            <span><span class="label">函数:</span> ' . htmlspecialchars($func) . '()</span>
            <span><span class="label">返回码:</span> ' . $return_var . '</span>
        </div>
    </div>';
}
?>
<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot low"></span>
        <span class="badge badge-low">简单</span>
        <h2 style="margin:0">💻 命令注入基础</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>演示 PHP 中常见的命令执行函数。将用户输入直接传递给这些函数是非常危险的行为，会导致 RCE 漏洞。
    </div>

    <form method="POST" class="form-group">
        <label for="cmd_input">输入要执行的命令：</label>
        <div class="form-row">
            <input type="text" name="cmd_input" id="cmd_input" 
                   placeholder="例如: whoami, ls -la, id, pwd" 
                   value="<?php echo htmlspecialchars($_POST['cmd_input'] ?? ''); ?>">
            <select name="func_type" id="func_type">
                <option value="system" <?php echo (($_POST['func_type'] ?? 'system') === 'system') ? 'selected' : ''; ?>>system()</option>
                <option value="exec" <?php echo (($_POST['func_type'] ?? '') === 'exec') ? 'selected' : ''; ?>>exec()</option>
                <option value="shell_exec" <?php echo (($_POST['func_type'] ?? '') === 'shell_exec') ? 'selected' : ''; ?>>shell_exec()</option>
                <option value="passthru" <?php echo (($_POST['func_type'] ?? '') === 'passthru') ? 'selected' : ''; ?>>passthru()</option>
                <option value="backtick" <?php echo (($_POST['func_type'] ?? '') === 'backtick') ? 'selected' : ''; ?>>反引号 ``</option>
            </select>
            <button type="submit" class="submit-btn">执行命令 ⚡</button>
        </div>
    </form>

    <?php echo $cmd_error_html; ?>
    <?php echo $cmd_output_html; ?>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            📚 查看源码 - 漏洞原理
        </div>
        <div class="tip-content">
            <p><strong>漏洞代码：</strong>直接将用户输入传递给命令执行函数，没有做任何过滤。</p>
            <div class="source-code">
                <pre><span class="php">&lt;?php
// 漏洞代码示例
$cmd = $_POST['cmd_input'];  // 用户输入未过滤
system($cmd);                 // 直接执行

// 其他危险函数同理
exec($cmd, $output);
shell_exec($cmd);
passthru($cmd);
$output = `$cmd`;  // 反引号
?&gt;</span></pre>
            </div>
            <p><strong>防御措施：</strong></p>
            <ul>
                <li>尽量避免使用命令执行函数</li>
                <li>使用白名单验证输入</li>
                <li>使用 <code>escapeshellarg()</code> / <code>escapeshellcmd()</code> 转义</li>
                <li>在 <code>php.ini</code> 中禁用危险函数</li>
            </ul>
        </div>
    </div>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            💡 各函数区别
        </div>
        <div class="tip-content">
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>函数</th>
                        <th>返回值</th>
                        <th>直接输出</th>
                        <th>适用场景</th>
                    </tr>
                    <tr>
                        <td><code>system()</code></td>
                        <td>最后一行（字符串）</td>
                        <td>✅ 是</td>
                        <td>需要实时输出</td>
                    </tr>
                    <tr>
                        <td><code>exec()</code></td>
                        <td>最后一行（字符串）</td>
                        <td>❌ 否</td>
                        <td>只需获取结果</td>
                    </tr>
                    <tr>
                        <td><code>shell_exec()</code></td>
                        <td>完整输出（字符串）</td>
                        <td>❌ 否</td>
                        <td>需要完整输出</td>
                    </tr>
                    <tr>
                        <td><code>passthru()</code></td>
                        <td>无</td>
                        <td>✅ 是</td>
                        <td>二进制输出</td>
                    </tr>
                    <tr>
                        <td><code>反引号``</code></td>
                        <td>完整输出（字符串）</td>
                        <td>❌ 否</td>
                        <td>快速执行</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            🎯 推荐练习Payload
        </div>
        <div class="tip-content">
            <ul class="payload-list">
                <li><code>whoami</code> - 查看当前用户</li>
                <li><code>id</code> - 查看用户ID和组</li>
                <li><code>pwd</code> - 查看当前目录</li>
                <li><code>ls -la</code> - 列出文件</li>
                <li><code>uname -a</code> - 查看系统信息</li>
                <li><code>cat /etc/passwd</code> - 读取用户文件</li>
                <li><code>ifconfig</code> (Linux) / <code>ipconfig</code> (Windows) - 网络信息</li>
                <li><code>netstat -tulnp</code> - 端口监听状态</li>
                <li><code>ps aux</code> - 进程列表</li>
                <li><code>find / -name "*.php" 2>/dev/null</code> - 查找PHP文件</li>
            </ul>
        </div>
    </div>
</div>
