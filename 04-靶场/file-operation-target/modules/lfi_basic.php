<div class="panel fade-in">
    <div class="level-info">
        <span class="level-dot easy"></span>
        <span class="badge badge-easy">简单</span>
        <h2 style="margin:0">📂 LFI本地文件包含基础 - 路径遍历</h2>
    </div>

    <div class="alert alert-info">
        <strong>📖 模块说明：</strong>本模块演示了本地文件包含（LFI）漏洞。
        通过对 <code>page</code> 参数的控制，可以读取服务器上的任意文件。
    </div>

    <div class="vuln-code">
        <span class="vuln-label">⚠️ 漏洞代码</span>
        <pre>&lt;?php
// 直接使用用户输入的 page 参数，没有做任何过滤！
$page = $_GET['page'];
include($page . '.php');
?&gt;</pre>
    </div>

    <div class="alert alert-danger">
        <strong>🎯 目标：</strong>通过路径遍历读取系统上的 <code>config.php</code> 文件和 <code>/etc/passwd</code> 文件。
    </div>

    <h3>🔍 文件包含演示</h3>
    <?php
    $page = isset($_GET['file']) ? $_GET['file'] : '';

    if ($page !== '') {
        // 模拟 include($page . '.php') 的行为 - 添加.php后缀
        $include_path = $page . '.php';
        echo '<div class="vuln-code">';
        echo '<span class="vuln-label">📄 实际执行</span>';
        echo '<pre>include(\'' . htmlspecialchars($include_path) . '\');</pre>';
        echo '</div>';

        echo '<div class="output-section">';
        echo '<h4>📄 包含结果：</h4>';
        echo '<div class="output-box">';

        // 尝试包含文件 - 这里会执行包含操作（但限制只读非php文件）
        if (file_exists($include_path)) {
            // 如果包含PHP文件，只读取不执行（防止意外）
            $ext = strtolower(pathinfo($include_path, PATHINFO_EXTENSION));
            if ($ext === 'php') {
                // 使用php://filter读取源码
                $content = file_get_contents($include_path);
                echo htmlspecialchars($content);
            } else {
                include($include_path);
            }
        } else {
            // 尝试通过路径遍历读取文件
            // 移除末尾的.php后缀尝试读取
            $alt_path = preg_replace('/\.php$/', '', $page);
            if ($alt_path !== $page && file_exists($alt_path)) {
                echo htmlspecialchars(file_get_contents($alt_path));
            } else {
                echo '<span class="error">❌ 文件不存在或无法读取</span>';
                echo "\n尝试的路径: " . htmlspecialchars($include_path);
            }
        }
        echo '</div>';
        echo '</div>';
    }
    ?>

    <h3>📝 尝试文件包含</h3>
    <form method="GET">
        <input type="hidden" name="page" value="lfi_basic">
        <div class="form-group">
            <label for="file">文件路径（page参数）：</label>
            <div class="form-row">
                <input type="text" name="file" id="file"
                       placeholder="例如: ../../../etc/passwd"
                       value="<?php echo htmlspecialchars($_GET['file'] ?? ''); ?>">
                <button type="submit" class="submit-btn">包含 🔍</button>
            </div>
        </div>
    </form>

    <div class="tip-collapse">
        <div class="tip-header">💡 通关提示</div>
        <div class="tip-content">
            <p><strong>目标1：读取 config.php 配置文件</strong></p>
            <ul>
                <li>输入: <code>../config</code>（注意代码会自动添加 .php 后缀）</li>
                <li>实际执行: <code>include('../config.php');</code></li>
            </ul>
            <p><strong>目标2：读取 /etc/passwd 系统文件</strong></p>
            <ul>
                <li>需要绕过 .php 后缀添加</li>
                <li>方法1：空字节截断（PHP &lt; 5.3.4）：<code>../../../etc/passwd%00</code></li>
                <li>方法2：路径长度截断：<code>../../../etc/passwd/./././...[超长]/./</code></li>
                <li>方法3：使用 php://filter 协议（参考 LFI进阶模块）</li>
            </ul>
            <p><strong>提示：</strong>本模块代码为 <code>include($page . '.php');</code>，每次会追加 .php 后缀。</p>
        </div>
    </div>
</div>
