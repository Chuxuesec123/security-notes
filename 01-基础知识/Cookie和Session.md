这是一个重要的知识点

和以下漏洞相关：

- 登录认证
- 越权漏洞
- CSRF
- XSS
- 会话劫持
- JWT

**如何理解cookie和session**

假设你登录了QQ：

```text
账号：admin
密码：123456
```

登录成功，但是，下次访问这个网站的时候，服务器怎么知道你已经登录了

http有一个特点就是无状态

无状态：

```text
请求1
↓
服务器处理完
↓
结束

请求2
↓
服务器不知道你是谁
```

所以在http协议基础上，需要额外的机制来记录身份

这就是cookie和session出现的原因

Cookie的本质是在浏览器保存的一小段数据

例如：

```text
Set-Cookie: username=admin
```

服务器返回给浏览器：帮我保存这个东西，浏览器收到后就保存下来这个信息。

下次访问：

```text
GET /profile HTTP/1.1
Cookie: username=admin
```

浏览器的请求会自动带上

流程：

```text
服务器
    ↓
Set-Cookie
    ↓
浏览器保存
    ↓
以后自动携带
```

**cookie长什么样**

```text
Cookie:
username=admin;
theme=dark;
language=zh
```

多个cookie用分号分隔

**cookie存在哪里**

浏览器本地

例如：

```text
Chrome
↓
Application
↓
Cookies
```

这个在浏览器F12可以看到

**Cookie的问题**

如果直接：

```text
Cookie: username=admin
```

攻击者可以修改：

```text
Cookie: username=root
```

也许就可以用root身份操作，显然很不安全，目前也不会怎么做。

**Session是什么**

session本质是服务器报错的登录状态

例如，用户用admin登录成功

服务器创建session，内容如下：

```text
SessionID=abc123

用户名=admin
权限=普通用户
登录时间=...
```

这些信息就会存储在服务器

**cookie+session联合工作**

这是最经典的模式

流程：

```text
登录
↓
服务器创建Session
↓
SessionID=abc123
↓
服务器保存
```

然后：

```text
Set-Cookie:
PHPSESSID=abc123
```

把上面的内容发送给浏览器

浏览器保存：

```text
Cookie:
PHPSESSID=abc123
```

以后请求这个网站的时候会自动带上参数：

```text
GET /profile

Cookie:
PHPSESSID=abc123
```

服务器：

```text
收到abc123
↓
查Session
↓
发现是admin
↓
允许访问
```

**为什么要这么设计**

因为：

```text
cookie在客户端

session在服务端
```

敏感的数据放在服务端更加安全

客户端只保存sessionid

**PHPSESSID是什么**

抓包的时候经常看到：

```text
Cookie:
PHPSESSID=q4ks8m2u6...
```

这是php默认的session名字，含义是session的唯一编号，不是密码，只是敏感数据的索引

**会话劫持**

假设攻击者获得了：

```text
PHPSESSID=abc123
```

那么：

```text
Cookie:
PHPSESSID=abc123
```

把上面的数据包发送到服务器，服务器就会把你当成admin，这就是会话劫持

**XSS为什么能偷取Cookie**

例如：

```js
<script>
alert(document.cookie)
</script>
```

上面的代码可以把cookie读取出来

攻击者就可以通过这个手段获取cookie

**httponly**

为了防止：

```text
document.cookie
```

读取cookie

服务器可以设置：

```text
Set-Cookie:
PHPSESSID=abc123;
HttpOnly
```

此时document.cookie就不能获取cookie了

**Secure**

设置：

```text
Set-Cookie:
PHPSESSID=abc123;
Secure
```

表示只能https传输，http不发送。

**SameSite**

用来防御CSRF

例如：

```text
Set-Cookie:
PHPSESSID=abc123;
SameSite=Strict
```

表示跨站请求不能携带Cookie

**面试常问**

cookie和session的区别

cookie存储在客户端，session存储在服务端

cookie可以被用户查看，session不可以

cookie占用客户端空间，session占用服务端空间