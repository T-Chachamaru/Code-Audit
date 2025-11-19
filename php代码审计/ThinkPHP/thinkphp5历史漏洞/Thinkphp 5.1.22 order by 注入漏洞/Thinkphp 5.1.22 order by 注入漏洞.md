## 漏洞影响

- **受影响版本**：ThinkPHP ≤ 5.1.22

## 漏洞分析

ThinkPHP ≤ 5.1.22 版本在框架处理数据库查询的 `order()` 方法时，对传入数组参数的 `key` 值未进行严格过滤和转义。当用户可控的 `order` 参数以数组形式传递，框架会将该数组直接合并到查询选项`（$options['order']）`中，并在 `SQL Builder` 的 `parseOrder()` 方法中遍历处理。
在 `parseOrder()` 循环中，对于每个数组项`（$key => $val）`，框架调用 `parseKey($key, true)` 将 `key` 作为字段名转义为 MySQL 标识符（加反引号 \`）。然而，在漏洞版本中，`parseKey()` 的正则检查`（/[\'\"\*$$  $$.\s]/）`仅用于决定是否包裹反引号，但不拒绝或清理包含特殊字符的 key（如反引号 \`\`  、逗号 `,`、括号 `()`、`|`、`#`）。这允许攻击者构造恶意 key ，导致生成的 ORDER BY 子句闭合标识符并注入有效 SQL 表达式。

## 漏洞复现

1. 漏洞主要源于 `order()` 方法的链式调用，其中排序参数如果是命名数组，其中的`key`未经严格过滤便直接传入底层 SQL 构建逻辑。

![图片1](/img/img-1.png)

2. 在 `order($order)` 方法中，由于传入的`$field`参数为命名数组，它跳过了前面的分支语句，直接来到了 `if (is_array($field))` 中，`$this->options['order']`为空数组，`array_merge($this->options['order'], $field)` 将传入参数原封不动地复制进`$this->options['order']`中。

![图片2](/img/img-2.png)

![图片3](/img/img-3.png)

3. 链式调用 `select()` 方法，`$data` 为 `null` ，它跳过了前两条分支语句，主要执行了 `$this->parseOptions();` 和 `$resultSet = $this->connection->select($this);`。

![图片4](/img/img-4.png)

4. `parseOptions()` 方法调用 `$this->parseOptions()` ，`getOptions()` 方法默认返回当前 `$this->options` ，其中包含恶意的 `$this->options['order']`。设置默认`table = 'user'，where = []，field = '*'`， `foreach (['data', 'order'] as $name) { if (!isset($options[$name])) { $options[$name] = []; } }`中 order 已存在不重置，跳过。其他选项初始化为空。`$this->options = $options;`，更新 options ，恶意 order 保留。

![图片5](/img/img-5.png)

![图片6](/img/img-6.png)

![图片7](/img/img-7.png)

5. `$resultSet = $this->connection->select($this);` 调用 `Connection` 对象的 `select` 方法，`$options = $query->getOptions();` 获取包含 order 的完整 options。`$sql = $this->builder->select($query);` 开始关键调用，生成查询 SQL。

![图片8](/img/img-8.png)

![图片9](/img/img-9.png)

6. 可以看到，`$this->builder` 由 `$class = $this->getBuilderClass();` 获取，`getBuilderClass()` 方法使用 `return $this->getConfig('builder') ?: '\\think\\db\\builder\\' . ucfirst($this->getConfig('type'));` 检测当前数据库类别，我的是MySQL，因此 `$this->builder` 实则是 `\\think\\db\\builder\\Mysql` 对象。该对象并没有实现 `select` 方法，它继承父类 `Builder` 的 `select` 方法。

![图片10](/img/img-10.png)

![图片11](/img/img-11.png)

7. `Builder` 的 `select` 方法调用 `parseOrder($options)` 处理 order 数组，如果 order 命名数组的值也是数组则调用 `$array[] = $this->parseOrderField($query, $key, $val);`。

![图片12](/img/img-12.png)

![图片13](/img/img-13.png)

8. `parseOrderField` 方法通过 `return 'field(' . $this->parseKey($query, $key, true) . ',' . implode(',', $val) . ')' . $sort;` 拼接语句，其中 `$this->parseKey($query, $key, true)` 直接返回 `$key` ，原值拼接，造成漏洞。如传入参数，```order[id`,111)|updatexml(1,concat(0x3a,database()),1)%23][]=1```，拼接为 ```field(`id`,111)|updatexml(1,concat(0x3a,database()),1)#`,:data__id`,111)|updatexml(1,concat(0x3a,database()),1)#0)```。

![图片14](/img/img-14.png)

9. 成功验证。

![图片15](/img/img-15.png)