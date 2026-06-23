看懂SQL语句，知道数据库在干什么，为后面的SQL注入打基础

1. 什么是数据库

可以把数据库想象成一个excel表格：
![image.png](https://cdn.jsdelivr.net/gh/Chuxuesec123/obsidian-image/images/20260610005746755.png)

数据库的本质就是存储和管理数据
## 语法基础

所有语句都要以英文分号结尾。

1. 数据查询

```sql
-- 查数据
select 字段名,字段名 from 表名;
select 字段名,字段名 from 数据库名.表名;

-- 筛选
select username from users where id=1;

-- 排序
select username from users order by 1;

-- 限制查询结果行数
select username from users limit 1; -- 只返回一行结果
select username from users limit 0,1 -- 显示第一行
select username from users limit 2,1 -- 显示第三行

-- 分组，把字段值相同的归类到同一组，然后用聚合函数处理
select class,count(*) from students group by class； -- 统计每班的学生数量

```

2. 逻辑运算符

```sql
-- 并：and
select * from users where age>10 and age<60; -- 查询年龄大于10小于60的结果
select * from users where age>10 && age<60; -- 查询年龄大于10小于60的结果

-- 或：or
select * from users where age>10 or age=10; -- 查询年龄大于等于10的结果
select * from users where age>10 || age=10; -- 查询年龄大于等于10的结果
```