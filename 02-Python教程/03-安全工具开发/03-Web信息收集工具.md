# Web信息收集工具

## 3.1 Web信息收集概述

Web信息收集是渗透测试的第一步，包括识别Web服务、目录结构、技术栈、敏感文件等信息。

### 信息收集流程

```
目标URL
  ├── HTTP响应头分析（服务器、版本、技术栈）
  ├── Web目录/文件枚举（后台、备份文件、敏感路径）
  ├── 网页源码分析（注释、链接、表单）
  ├── 技术栈识别（CMS、框架、库版本）
  └── JavaScript分析（API端点、隐藏功能）
```

## 3.2 HTTP请求工具类

```python
import socket
import ssl
from urllib.parse import urlparse
import json

class HTTPClient:
    """原始HTTP客户端（无需第三方库）"""
    
    def __init__(self, timeout=10):
        self.timeout = timeout
    
    def request(self, url, method="GET", headers=None, body=None):
        """
        发送HTTP请求
        
        Args:
            url: 完整URL
            method: HTTP方法
            headers: 自定义请求头
            body: 请求体
        
        Returns:
            dict: 包含 status, headers, body, time
        """
        parsed = urlparse(url)
        host = parsed.netloc
        path = parsed.path or "/"
        if parsed.query:
            path += "?" + parsed.query
        
        port = 443 if parsed.scheme == "https" else 80
        use_ssl = parsed.scheme == "https"
        
        # 构造请求
        if headers is None:
            headers = {}
        
        default_headers = {
            "Host": host,
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                          "AppleWebKit/537.36 (KHTML, like Gecko) "
                          "Chrome/120.0.0.0 Safari/537.36",
            "Accept": "*/*",
            "Connection": "close"
        }
        default_headers.update(headers)
        
        request_line = f"{method} {path} HTTP/1.1\r\n"
        header_lines = "".join(
            f"{k}: {v}\r\n" for k, v in default_headers.items()
        )
        request_body = body or ""
        request = request_line + header_lines + "\r\n" + request_body
        
        # 建立连接并发送
        import time
        start_time = time.time()
        
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(self.timeout)
            
            if use_ssl:
                context = ssl.create_default_context()
                context.check_hostname = False
                context.verify_mode = ssl.CERT_NONE
                sock = context.wrap_socket(sock, server_hostname=host)
            
            sock.connect((host.split(":")[0], port))
            sock.send(request.encode())
            
            # 接收响应
            response = b""
            while True:
                try:
                    data = sock.recv(4096)
                    if not data:
                        break
                    response += data
                except socket.timeout:
                    break
            
            sock.close()
            
            # 解析响应
            header_end = response.find(b"\r\n\r\n")
            raw_headers = response[:header_end].decode("utf-8", errors="ignore")
            body = response[header_end + 4:]
            
            # 解析状态行
            lines = raw_headers.split("\r\n")
            status_line = lines[0]
            http_version, status_code, *reason = status_line.split(" ")
            
            # 解析响应头
            resp_headers = {}
            for line in lines[1:]:
                if ":" in line:
                    key, value = line.split(":", 1)
                    resp_headers[key.strip()] = value.strip()
            
            duration = time.time() - start_time
            
            return {
                "status_code": int(status_code),
                "headers": resp_headers,
                "body": body,
                "time": round(duration, 3)
            }
        
        except socket.timeout:
            return {"error": f"请求 {url} 超时（{self.timeout}s）"}
        except Exception as e:
            return {"error": str(e)}
    
    def get(self, url, headers=None):
        """发送GET请求"""
        return self.request(url, "GET", headers)
    
    def post(self, url, data=None, headers=None):
        """发送POST请求"""
        if data and isinstance(data, dict):
            import urllib.parse
            body = urllib.parse.urlencode(data)
        else:
            body = data or ""
        
        if headers is None:
            headers = {}
        if "Content-Type" not in headers:
            headers["Content-Type"] = "application/x-www-form-urlencoded"
        headers["Content-Length"] = str(len(body))
        
        return self.request(url, "POST", headers, body)


# 使用示例
if __name__ == "__main__":
    client = HTTPClient(timeout=10)
    result = client.get("http://example.com")
    if "error" not in result:
        print(f"状态码：{result['status_code']}")
        print(f"耗时：{result['time']}s")
        print(f"服务器：{result['headers'].get('Server', 'N/A')}")
    else:
        print(f"错误：{result['error']}")
```

## 3.3 Web服务器指纹识别

```python
class WebServerFingerprint:
    """识别Web服务器类型和版本"""
    
    # 服务器指纹库
    FINGERPRINTS = {
        "Apache": [
            "Apache", "apache",
            r"Server: Apache"
        ],
        "Nginx": [
            "nginx", "Nginx",
            r"Server: nginx"
        ],
        "IIS": [
            "Microsoft-IIS", "IIS",
            r"Server: Microsoft-IIS",
            "X-Powered-By: ASP.NET"
        ],
        "Tomcat": [
            "Apache-Coyote", "Tomcat",
            "X-Powered-By: Servlet"
        ],
        "Node.js": [
            "Node.js", "Express"
        ],
        "Python": [
            "Python", "gunicorn", "uwsgi"
        ]
    }
    
    @staticmethod
    def identify(headers, body):
        """
        识别Web服务器
        
        Args:
            headers: HTTP响应头字典
            body: 响应体字符串
        
        Returns:
            list: 可能的技术栈列表
        """
        info = {
            "server": None,
            "programming_language": None,
            "framework": None,
            "details": []
        }
        
        # 从Server头识别
        server = headers.get("Server", "")
        info["server"] = server
        
        # 检查指纹库
        text = json.dumps(headers) + " " + (body[:2000] if body else "")
        
        for tech, patterns in WebServerFingerprint.FINGERPRINTS.items():
            import re
            for pattern in patterns:
                if re.search(pattern, text, re.IGNORECASE):
                    info["details"].append(tech)
                    break
        
        # 从X-Powered-By识别
        x_powered = headers.get("X-Powered-By", "")
        if x_powered:
            info["details"].append(x_powered)
        
        # 从Cookie识别
        set_cookie = headers.get("Set-Cookie", "")
        if "PHPSESSID" in set_cookie:
            info["details"].append("PHP")
        if "JSESSIONID" in set_cookie:
            info["details"].append("Java/JSP")
        if "ASP.NET_SessionId" in set_cookie:
            info["details"].append("ASP.NET")
        
        return info


# 使用示例
def get_server_info(url):
    client = HTTPClient()
    result = client.get(url)
    if "error" not in result:
        info = WebServerFingerprint.identify(
            result["headers"], 
            result["body"].decode("utf-8", errors="ignore")
        )
        return info
    return None
```

## 3.4 Web目录枚举器

```python
import concurrent.futures
import threading

class DirectoryEnumerator:
    """
    Web目录/文件枚举器
    
    通过字典爆破发现Web目录结构
    """
    
    # 常见敏感路径
    COMMON_PATHS = [
        "admin", "login", "wp-admin", "wp-login",
        "backup", "bak", "db", "sql",
        "config", "config.php", "config.asp",
        ".git/", ".svn/", ".env", "robots.txt",
        "sitemap.xml", "crossdomain.xml",
        "phpinfo.php", "info.php", "test.php",
        "upload/", "uploads/", "files/",
        "api/", "api/v1/", "swagger/",
        "web.config", ".htaccess", "Dockerfile",
        "README.md", "CHANGELOG", "composer.json"
    ]
    
    def __init__(self, base_url, timeout=5, max_workers=20):
        self.base_url = base_url.rstrip("/")
        self.client = HTTPClient(timeout=timeout)
        self.max_workers = max_workers
        self.found = []
        self.lock = threading.Lock()
        self.scanned = 0
        self.total = 0
    
    def check_path(self, path):
        """检查路径是否存在"""
        url = f"{self.base_url}/{path.lstrip('/')}"
        result = self.client.get(url)
        
        with self.lock:
            self.scanned += 1
        
        if "error" not in result:
            status = result["status_code"]
            
            # 200-399 表示存在（排除404）
            if 200 <= status < 400:
                size = len(result["body"])
                with self.lock:
                    self.found.append({
                        "url": url,
                        "status": status,
                        "size": size
                    })
                print(f"[{status}] {url} ({size} bytes)")
            
            return path, status
        
        return path, None
    
    def enumerate(self, wordlist=None):
        """
        开始枚举
        
        Args:
            wordlist: 自定义字典，默认使用内置字典
        """
        paths = wordlist or self.COMMON_PATHS
        self.total = len(paths)
        
        print(f"开始枚举目录：{self.base_url}")
        print(f"字典大小：{len(paths)}")
        print("-" * 50)
        
        with concurrent.futures.ThreadPoolExecutor(
            max_workers=self.max_workers
        ) as executor:
            futures = {
                executor.submit(self.check_path, path): path 
                for path in paths
            }
            concurrent.futures.wait(futures)
        
        print(f"\n扫描完成")
        print(f"发现 {len(self.found)} 个路径")
        
        return sorted(self.found, key=lambda x: x["status"])


# 使用示例
def enumerate_directories(url):
    enum = DirectoryEnumerator(url)
    return enum.enumerate()

# 从文件加载自定义字典
def load_wordlist(filename):
    with open(filename, "r", encoding="utf-8") as f:
        return [line.strip() for line in f if line.strip()]
```

## 3.5 网页爬虫（简单版）

```python
import re
from urllib.parse import urljoin

class SimpleCrawler:
    """简单的Web爬虫，用于信息收集"""
    
    def __init__(self, start_url, max_pages=50):
        self.start_url = start_url
        self.max_pages = max_pages
        self.visited = set()
        self.to_visit = {start_url}
        self.client = HTTPClient(timeout=5)
        
        # 收集的信息
        self.emails = set()
        self.forms = []
        self.links = []
        self.js_files = []
        self.comments = []
    
    def extract_emails(self, text):
        """提取邮箱地址"""
        pattern = r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
        return re.findall(pattern, text)
    
    def extract_links(self, html, base_url):
        """提取页面中的所有链接"""
        patterns = [
            r'href=["\'](.*?)["\']',
            r'src=["\'](.*?)["\']',
            r'action=["\'](.*?)["\']'
        ]
        
        links = []
        for pattern in patterns:
            matches = re.findall(pattern, html)
            for match in matches:
                full_url = urljoin(base_url, match)
                if full_url.startswith(("http://", "https://")):
                    links.append(full_url)
        return links
    
    def extract_comments(self, html):
        """提取HTML注释"""
        pattern = r"<!--(.*?)-->"
        return re.findall(pattern, html, re.DOTALL)
    
    def extract_forms(self, html, base_url):
        """提取表单信息"""
        form_pattern = r'<form(.*?)>(.*?)</form>'
        input_pattern = r'<input(.*?)>'
        
        forms = []
        for form_match in re.finditer(form_pattern, html, re.DOTALL):
            form_html = form_match.group()
            form_info = {
                "action": "",
                "method": "GET",
                "inputs": []
            }
            
            # 提取表单属性
            attrs = form_match.group(1)
            action_match = re.search(r'action=["\'](.*?)["\']', attrs)
            if action_match:
                form_info["action"] = urljoin(base_url, action_match.group(1))
            
            method_match = re.search(r'method=["\'](.*?)["\']', attrs, re.I)
            if method_match:
                form_info["method"] = method_match.group(1).upper()
            
            # 提取输入字段
            for input_match in re.finditer(input_pattern, form_html):
                input_html = input_match.group()
                input_info = {
                    "name": None,
                    "type": "text",
                    "value": None
                }
                
                name_match = re.search(r'name=["\'](.*?)["\']', input_html)
                if name_match:
                    input_info["name"] = name_match.group(1)
                
                type_match = re.search(r'type=["\'](.*?)["\']', input_html)
                if type_match:
                    input_info["type"] = type_match.group(1)
                
                form_info["inputs"].append(input_info)
            
            forms.append(form_info)
        
        return forms
    
    def crawl(self):
        """开始爬取"""
        while self.to_visit and len(self.visited) < self.max_pages:
            url = self.to_visit.pop()
            if url in self.visited:
                continue
            
            print(f"[爬取] {url}")
            result = self.client.get(url)
            
            if "error" in result:
                continue
            
            self.visited.add(url)
            html = result["body"].decode("utf-8", errors="ignore")
            
            # 提取信息
            emails = self.extract_emails(html)
            self.emails.update(emails)
            
            links = self.extract_links(html, url)
            for link in links:
                if link.startswith(self.start_url.rstrip("/")):
                    self.to_visit.add(link)
                if link.endswith(".js"):
                    self.js_files.append(link)
                self.links.append(link)
            
            comments = self.extract_comments(html)
            for comment in comments:
                self.comments.append(comment.strip())
            
            forms = self.extract_forms(html, url)
            self.forms.extend(forms)
        
        return {
            "pages_crawled": len(self.visited),
            "emails": list(self.emails),
            "forms": self.forms,
            "js_files": self.js_files,
            "comments": self.comments,
            "links": self.links[:50]  # 限制输出
        }
```

## 3.6 综合Web信息收集工具

```python
class WebInfoCollector:
    """Web信息收集综合工具"""
    
    def __init__(self, target_url):
        self.target_url = target_url.rstrip("/")
        self.client = HTTPClient(timeout=10)
        self.results = {
            "target": self.target_url,
            "server_info": {},
            "directories": [],
            "forms": [],
            "emails": [],
            "js_files": [],
            "technologies": []
        }
    
    def collect(self):
        """执行信息收集"""
        print(f"开始信息收集：{self.target_url}")
        print("=" * 50)
        
        # 1. 基本信息
        print("\n[1/4] 获取服务器信息...")
        base_result = self.client.get(self.target_url)
        if "error" not in base_result:
            body_text = base_result["body"].decode("utf-8", errors="ignore")
            self.results["server_info"] = WebServerFingerprint.identify(
                base_result["headers"], body_text
            )
            print(f"  Server: {self.results['server_info'].get('server', 'N/A')}")
            print(f"  技术栈: {', '.join(self.results['server_info'].get('details', ['N/A']))}")
        
        # 2. 目录枚举
        print("\n[2/4] 枚举目录...")
        enum = DirectoryEnumerator(self.target_url, max_workers=15)
        directories = enum.enumerate()
        self.results["directories"] = directories
        
        # 3. 爬取信息
        print("\n[3/4] 爬取页面信息...")
        crawler = SimpleCrawler(self.target_url, max_pages=10)
        info = crawler.crawl()
        self.results["forms"] = info["forms"]
        self.results["emails"] = info["emails"]
        self.results["js_files"] = info["js_files"]
        self.results["comments"] = info["comments"]
        
        # 4. 报告输出
        print("\n[4/4] 生成报告...")
        self._print_report()
        
        return self.results
    
    def _print_report(self):
        """打印信息收集报告"""
        print("\n" + "=" * 50)
        print("信息收集报告")
        print("=" * 50)
        print(f"目标: {self.target_url}")
        
        print("\n--- 服务器信息 ---")
        for key, value in self.results["server_info"].items():
            if value:
                print(f"  {key}: {value}")
        
        print("\n--- 发现的目录 ---")
        for d in self.results["directories"]:
            print(f"  [{d['status']}] {d['url']}")
        
        print(f"\n--- 发现的表单 ({len(self.results['forms'])}) ---")
        for form in self.results["forms"]:
            print(f"  {form['method']} {form['action']}")
            for inp in form["inputs"]:
                if inp["name"]:
                    print(f"    - {inp['name']} ({inp['type']})")
        
        print(f"\n--- 发现的邮箱 ({len(self.results['emails'])}) ---")
        for email in self.results["emails"]:
            print(f"  {email}")
        
        print(f"\n--- 发现的JS文件 ({len(self.results['js_files'])}) ---")
        for js in self.results["js_files"][:10]:
            print(f"  {js}")
        
        print(f"\n--- HTML注释 ({len(self.results.get('comments', []))}) ---")
        for comment in self.results.get("comments", [])[:5]:
            if comment.strip():
                print(f"  <!-- {comment[:100]} -->")
        
        print("=" * 50)


if __name__ == "__main__":
    import sys
    target = sys.argv[1] if len(sys.argv) > 1 else "http://testphp.vulnweb.com"
    collector = WebInfoCollector(target)
    results = collector.collect()
```

## 3.7 本章练习

1. 为信息收集工具添加WAF（Web应用防火墙）识别功能
2. 添加CMS（WordPress、Joomla、Drupal等）指纹识别
3. 添加子域名枚举功能
4. 实现JavaScript端点提取和敏感信息分析

## 3.8 本章小结

本章构建了一个完整的Web信息收集工具集，包含HTTP客户端、服务器指纹识别、目录枚举、网页爬虫和信息聚合等功能。Web信息收集是渗透测试的关键环节，为后续的漏洞发现和利用提供基础支撑。
