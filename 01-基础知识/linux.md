## 安装php

```bash
sudo apt install php libapache2-mod-php

# 验证安装
php -v
```

## 给php安装sqlite扩展

```bash
sudo apt install php-sqlite3 -y
```

## 给php安装mb_substr扩展

```bash
sudo apt install php-mbstring -y

sudo systemctl restart php*
```