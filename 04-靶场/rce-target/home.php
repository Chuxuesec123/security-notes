<div class="hero">
    <h2>🎯 欢迎来到 RCE 漏洞练习靶场</h2>
    <p class="hero-desc">
        涵盖命令注入、代码执行、绕过技术、文件读写、无回显利用、反序列化等核心内容。
    </p>
</div>

<div class="modules-grid">
    <div class="module-card">
        <div class="card-icon">💻</div>
        <h3>命令注入基础</h3>
        <p>演示PHP中常见的命令执行函数：<code>system()</code>、<code>exec()</code>、<code>shell_exec()</code>、<code>passthru()</code> 等</p>
        <div class="card-tags">
            <span class="tag tag-easy">简单</span>
            <span class="tag">system</span>
            <span class="tag">exec</span>
        </div>
        <a href="?page=command_inject" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">🌐</div>
        <h3>DVWA-Ping注入</h3>
        <p>经典DVWA命令注入场景，分低/中/高三级难度，练习命令连接符和绕过技巧</p>
        <div class="card-tags">
            <span class="tag tag-easy">简单</span>
            <span class="tag tag-medium">中等</span>
            <span class="tag tag-hard">困难</span>
        </div>
        <a href="?page=dvwa_ping" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">📝</div>
        <h3>代码执行漏洞</h3>
        <p>PHP代码执行函数演示：<code>eval()</code>、<code>assert()</code>、<code>call_user_func()</code>、动态函数调用等</p>
        <div class="card-tags">
            <span class="tag tag-easy">简单</span>
            <span class="tag">eval</span>
            <span class="tag">assert</span>
        </div>
        <a href="?page=code_exec" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">🔄</div>
        <h3>绕过过滤挑战</h3>
        <p>练习各种WAF绕过技术：空格绕过、关键词绕过、编码绕过、拼接绕过等高级技巧</p>
        <div class="card-tags">
            <span class="tag tag-hard">困难</span>
            <span class="tag">绕过</span>
            <span class="tag">WAF</span>
        </div>
        <a href="?page=bypass" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">📂</div>
        <h3>文件读写利用</h3>
        <p>利用RCE写入WebShell、读取敏感文件、列目录等操作，掌握权限维持技巧</p>
        <div class="card-tags">
            <span class="tag tag-medium">中等</span>
            <span class="tag">WebShell</span>
            <span class="tag">提权</span>
        </div>
        <a href="?page=file_rw" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">👁️</div>
        <h3>无回显RCE</h3>
        <p>当命令执行无回显时的利用技术：时间盲注、DNS外带、HTTP外带等</p>
        <div class="card-tags">
            <span class="tag tag-hard">困难</span>
            <span class="tag">盲注</span>
            <span class="tag">外带</span>
        </div>
        <a href="?page=blind_rce" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">🧩</div>
        <h3>反序列化RCE</h3>
        <p>PHP反序列化漏洞演示，利用魔法方法<code>__destruct()</code>、<code>__wakeup()</code>触发RCE</p>
        <div class="card-tags">
            <span class="tag tag-hard">困难</span>
            <span class="tag">反序列化</span>
            <span class="tag">POP链</span>
        </div>
        <a href="?page=deserialize" class="btn">开始练习 →</a>
    </div>

    <div class="module-card">
        <div class="card-icon">📖</div>
        <h3>命令速查表</h3>
        <p>常用命令速查、Payload汇总、反弹Shell命令等参考内容</p>
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
        <li>从 <strong>命令注入基础</strong> 开始，了解各函数的区别</li>
        <li>完成 <strong>DVWA-Ping注入</strong> 的三个难度级别</li>
        <li>练习 <strong>代码执行漏洞</strong> 掌握各种执行方式</li>
        <li>挑战 <strong>绕过过滤</strong> 和 <strong>无回显RCE</strong> 的高级技巧</li>
        <li>使用 <strong>命令速查表</strong> 辅助练习</li>
    </ol>
</div>

<style>
.hero {
    text-align: center;
    padding: 3rem 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    margin: 1.5rem 0;
}
.hero h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
}
.hero-desc {
    font-size: 1.1rem;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
    opacity: 0.95;
}
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}
.module-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}
.module-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}
.card-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}
.module-card h3 {
    margin: 0.5rem 0;
    color: #333;
    font-size: 1.3rem;
}
.module-card p {
    color: #666;
    line-height: 1.5;
    flex-grow: 1;
}
.module-card code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.9em;
}
.card-tags {
    margin: 1rem 0;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.tag {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    background: #e8e8e8;
    color: #555;
}
.tag-easy {
    background: #d4edda;
    color: #155724;
}
.tag-medium {
    background: #fff3cd;
    color: #856404;
}
.tag-hard {
    background: #f8d7da;
    color: #721c24;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #667eea;
    color: white !important;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
    transition: background 0.2s;
    margin-top: auto;
}
.btn:hover {
    background: #5a6fd6;
}
.info-box {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-left: 4px solid #667eea;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}
.info-box h4 {
    margin-top: 0;
    color: #333;
}
.info-box ol {
    margin: 0;
    padding-left: 1.2rem;
}
.info-box li {
    margin: 0.5rem 0;
    line-height: 1.5;
}
</style>
