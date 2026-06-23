# Python简介与环境搭建

## 1.1 什么是Python

Python是一种高级编程语言，由Guido van Rossum于1989年发明。它的设计哲学强调代码的可读性和简洁性，使用缩进来定义代码块，而非大括号。

Python的主要特点：

- **解释型语言**：代码无需编译即可直接运行
- **动态类型**：变量不需要声明类型，运行时自动推断
- **跨平台**：Windows、Linux、macOS均可运行
- **丰富的生态**：拥有庞大的第三方库支持

Python适用于Web开发、数据分析、人工智能、自动化运维、安全工具开发等多个领域。

## 1.2 安装Python

### Windows系统

1. 访问Python官方网站（python.org），下载最新版本的Python安装包
2. 运行安装程序，务必勾选"Add Python to PATH"选项
3. 点击"Install Now"完成安装

验证安装是否成功，打开命令提示符（cmd）或PowerShell，输入：

```powershell
python --version
```

若显示类似 `Python 3.12.x` 的版本号，说明安装成功。

### 配置包管理器pip

pip是Python的包管理工具，用于安装第三方库。新版本Python自带pip。验证pip是否可用：

```powershell
pip --version
```

若提示"pip不是内部或外部命令"，可执行以下命令重新安装pip：

```powershell
python -m ensurepip --upgrade
```

## 1.3 开发环境选择

### 方案一：VS Code（推荐）

1. 下载并安装VS Code
2. 安装Python扩展（由Microsoft提供）
3. 创建 `.py` 文件即可开始编写代码

### 方案二：PyCharm

JetBrains公司出品的专业Python IDE，社区版免费，功能完整。

### 方案三：Jupyter Notebook

适合数据分析和教学，支持逐行执行代码并查看结果。

## 1.4 第一个Python程序

创建一个文件名为 `hello.py` 的文件，输入以下内容：

```python
print("Hello, World!")
```

在终端中运行：

```powershell
python hello.py
```

终端将输出：

```
Hello, World!
```

## 1.5 代码运行方式

### 交互式模式

直接在终端输入 `python` 进入交互式环境，逐行输入代码并立即看到结果：

```python
>>> 1 + 2
3
>>> print("Hello")
Hello
```

### 脚本模式

将代码写入 `.py` 文件，使用 `python 文件名.py` 执行。

## 1.6 基础语法规则

- **注释**：使用 `#` 表示单行注释
- **缩进**：使用4个空格表示代码块，同一代码块必须保持相同缩进
- **换行**：语句结束无需分号

```python
# 这是一个注释
print("Hello")  # 行尾注释

if True:
    print("缩进正确")
```

## 1.7 本章小结

本章学习了Python的基本概念、安装方法、开发环境选择以及第一个程序的编写。接下来的章节将从变量和数据类型开始，逐步深入Python编程的各个方面。
