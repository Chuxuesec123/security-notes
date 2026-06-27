<div class="hero">
    <h2>🎯 欢迎来到文件操作类漏洞练习靶场</h2>
    <p class="hero-desc">
        涵盖文件上传、文件包含（LFI/RFI）、文件下载/读取、文件删除等核心文件操作漏洞。
        从基础到高级绕过，循序渐进掌握文件操作类漏洞的攻防技术。
    </p>
</div>

<div class="modules-grid">
    <div class="module-card">
        <div class="card-icon">⬆️</div>
        <h3>文件上传基础</h3>
        <p>演示无过滤的文件上传漏洞，上传任意文件到服务器，包含WebShell上传实战</p>
        <div class="card-tags">
            <span class="tag tag-easy">简单</span>
            <span class="tag">Upload</span>
            <span class="tag">WebShell</span>
        </div>
        <a href="?page=upload_basic" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">🔄</div>
        <h3>文件上传绕过</h3>
        <p>练习各种上传绕过技术：Content-Type绕过、扩展名黑/白名单绕过、文件头检查绕过等</p>
        <div class="card-tags">
            <span class="tag tag-medium">中等</span>
            <span class="tag">Bypass</span>
            <span class="tag">MIME</span>
        </div>
        <a href="?page=upload_bypass" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">📂</div>
        <h3>LFI本地文件包含基础</h3>
        <p>本地文件包含漏洞演示，通过路径遍历读取系统敏感文件，学习LFI基本原理</p>
        <div class="card-tags">
            <span class="tag tag-easy">简单</span>
            <span class="tag">LFI</span>
            <span class="tag">路径遍历</span>
        </div>
        <a href="?page=lfi_basic" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">🔧</div>
        <h3>LFI进阶 - PHP封装协议</h3>
        <p>利用PHP的各种封装协议：php://filter、data://、phar://、zip://等实现代码执行与信息读取</p>
        <div class="card-tags">
            <span class="tag tag-medium">中等</span>
            <span class="tag">Wrapper</span>
            <span class="tag">php://</span>
        </div>
        <a href="?page=lfi_wrapper" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">📝</div>
        <h3>日志注入 + LFI</h3>
        <p>通过User-Agent向日志写入恶意代码，再利用LFI包含日志文件实现RCE</p>
        <div class="card-tags">
            <span class="tag tag-hard">困难</span>
            <span class="tag">Log Poisoning</span>
            <span class="tag">RCE</span>
        </div>
        <a href="?page=log_poison" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">⬇️</div>
        <h3>文件下载/读取漏洞</h3>
        <p>任意文件下载漏洞演示，通过路径遍历下载服务器上的敏感配置文件</p>
        <div class="card-tags">
            <span class="tag tag-medium">中等</span>
            <span class="tag">下载</span>
            <span class="tag">路径遍历</span>
        </div>
        <a href="?page=file_download" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">🗑️</div>
        <h3>文件删除漏洞</h3>
        <p>任意文件删除漏洞演示，通过路径遍历删除服务器上的关键文件</p>
        <div class="card-tags">
            <span class="tag tag-hard">困难</span>
            <span class="tag">删除</span>
            <span class="tag">路径遍历</span>
        </div>
        <a href="?page=file_delete" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">📖</div>
        <h3>Payload速查表</h3>
        <p>文件操作类漏洞的常用Payload汇总、绕过技巧、防御方案参考</p>
        <div class="card-tags">
            <span class="tag">参考</span>
            <span class="tag">Payload</span>
            <span class="tag">备忘</span>
        </div>
        <a href="?page=cheatsheet" class="btn">查看 →</a>
    </div>
</div>

<div class="info-box">
    <h4>📚 学习建议</h4>
    <ol>
        <li>从 <strong>文件上传基础</strong> 开始，理解文件上传的基本原理</li>
        <li>完成 <strong>上传绕过</strong> 挑战，掌握各种WAF绕过技术</li>
        <li>练习 <strong>LFI基础</strong> 和 <strong>LFI进阶</strong>，理解文件包含的多种利用方式</li>
        <li>挑战 <strong>日志注入</strong> 组合拳，体会多种漏洞联合利用</li>
        <li>尝试 <strong>文件下载</strong> 和 <strong>文件删除</strong> 的高阶技巧</li>
        <li>使用 <strong>Payload速查表</strong> 辅助练习</li>
    </ol>
</div>
