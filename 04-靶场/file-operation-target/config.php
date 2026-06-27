<?php
/**
 * 数据库配置文件（模拟敏感文件）
 * 注意：此文件用于靶场练习，展示文件读取漏洞的危害
 * 实际部署请勿将敏感信息放在Web可访问目录
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'wordpress');
define('DB_USER', 'root');
define('DB_PASS', 'SuperSecretPassword@2024!');

// Redis配置
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASS', 'Redis@Admin#2024');

// 管理员账号（请勿泄露）
$admin_user = 'admin';
$admin_pass = '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456';

// API密钥
$api_keys = [
    'aliyun' => 'LTAI5t7G8H9J0K1L2M3N4P5Q',
    'tencent' => 'AKIDx6y7z8A1B2C3D4E5F6G7H8I9',
    'wechat' => 'wx1234567890abcdefghijklmn',
];

// Flag（挑战成功标志）
$flag = 'FLAG{File_0perati0n_Vuln_Master_2024}';
