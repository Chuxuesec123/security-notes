# 📁 文件操作类漏洞综合靶场

基于《文件操作类漏洞学习笔记》构建的 PHP 漏洞练习靶场，涵盖文件上传、文件包含（LFI/RFI）、文件下载/读取、文件删除等核心文件操作漏洞。

## 🏗️ 目录结构

```
file-operation-target/
├── index.php          # 主入口
├── home.php           # 首页
├── config.php         # 模拟敏感配置文件
├── flag.txt           # 挑战Flag
├── README.md          # 本文档
├── css/
│   └── style.css      # 样式表
├── uploads/           # 上传文件目录
│   └── .gitkeep
└── modules/
    ├── upload_basic.php    # 文件上传基础 - 无过滤
    ├── upload_bypass.php   # 文件上传绕过 - 4级挑战
    ├── lfi_basic.php       # LFI基础 - 路径遍历
    ├── lfi_wrapper.php     # LFI进阶 - PHP封装协议
    ├── log_poison.php      # 日志注入 + LFI 组合攻击
    ├── file_download.php   # 文件下载/读取漏洞
    ├── file_delete.php     # 文件删除漏洞
    └── cheatsheet.php      # Payload速查表
```

## 🚀 部署方式

### 方式一：Docker 快速部署

```bash
# 创建 Dockerfile
cat > Dockerfile << 'EOF'
FROM php:7.4-apache
COPY . /var/www/html/
RUN chmod -R 755 /var/www/html/ && \
    chmod -R 777 /var/www/html/uploads/
RUN docker-php-ext-install fileinfo
EOF

# 构建并运行
docker build -t file-ops-target .
docker run -d -p 8080:80 file-ops-target
```

### 方式二：直接部署到 Apache/Nginx

1. 将 `file-operation-target/` 目录复制到 Web 根目录
2. 确保 `uploads/` 目录可写：`chmod -R 777 uploads/`
3. 确保 PHP 已开启 `fileinfo` 扩展（上传绕过模块需要）
4. 访问 `http://your-server/file-operation-target/`

### 方式三：PHP 内置服务器（测试用）

```bash
cd file-operation-target
php -S 0.0.0.0:8080
# 访问 http://localhost:8080
```

## 📚 模块说明

| 模块 | 难度 | 说明 |
|------|------|------|
| 上传基础 | ⭐ 简单 | 无过滤文件上传，直接上传WebShell |
| 上传绕过 | ⭐⭐ 中等 | 4级难度，Content-Type/黑名单/文件头/多重检测绕过 |
| LFI基础 | ⭐ 简单 | 路径遍历读取文件，后缀追加绕过 |
| LFI进阶 | ⭐⭐ 中等 | php://filter/input、data://、expect://协议利用 |
| 日志注入 | ⭐⭐⭐ 困难 | User-Agent注入+LFI组合RCE |
| 文件下载 | ⭐⭐ 中等 | 路径遍历下载敏感文件 |
| 文件删除 | ⭐⭐⭐ 困难 | 任意文件删除漏洞 |
| Payload速查 | - | 常用Payload和防御方案参考 |

## ⚠️ 免责声明

本靶场仅供授权的安全学习使用，请勿用于非法用途。使用者应遵守当地法律法规。

## 📖 参考资料

- 《文件操作类漏洞学习笔记》
- OWASP File Upload
- OWASP File Inclusion
- OWASP Path Traversal
