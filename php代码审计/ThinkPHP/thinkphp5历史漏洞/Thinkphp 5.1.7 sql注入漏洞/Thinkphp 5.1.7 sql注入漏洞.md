## 漏洞影响

- **受影响版本**：5.1.6 ≤ ThinkPHP ≤ 5.1.7

## 漏洞分析

ThinkPHP 5.1.6 至 5.1.7 版本存在一处 SQL 注入漏洞，源于 `Mysql` 驱动的 `parseArrayData` 方法对用户输入数组未进行充分过滤，直接拼接进 UPDATE 语句的 SET 子句中，从而允许执行任意 SQL。

## 漏洞复现

1. 漏洞主要源于驱动类的 `parseArrayData` 方法的未过滤拼接，`Query` 查询类的 `update` 调用了该方法。

![图片1](/img/img-1.png)

2. `Query` 查询类将 `update(['username' => $username])` 的参数用 `$this->options['data'] = array_merge($this->options['data'], $data)` 合并至查询选项中，以待后续用于解析构建查询表达式。随后，委托调用 `$this->connection->update($this)` 。

![图片2](/img/img-2.png)

3. `Connection` 类的 `update` 方法代码较多，该方法不直接生成 SQL，而是对 `$options` 进行预处理，后续进行关键调用 `$sql  = $this->builder->update($query)` 构建 SQL。

![图片3](/img/img-3.png)

![图片4](/img/img-4.png)

4. `Builder` 类的 `update` 方法使用 `$data = $this->parseData($query, $options['data'])` 生成 SQL 子句并绑定至完整 SQL 的 SET 子句中。

![图片5](/img/img-5.png)

5. `update` 方法的传入了查询对象与查询数据，`$options['data']` 即是 `Query` 查询类的 `['username' => $username]` 参数。这里的 SQL 子句建构的关键点在 `elseif (is_array($val) && !empty($val))` 分支，下此分支在之前的版本也出现过注入漏洞。因此，在 `foreach ($data as $key => $val)` 循环中的 `$val` 是用户传入的 `$username` 参数，只要该参数的 `switch ($val[0])` 不为 `case 'INC'` 和 `case 'DEC'` 即可进入 `default` 分支，从而执行 `$value = $this->parseArrayData($query, $val)` 调用。

![图片6](/img/img-6.png)

![图片7](/img/img-7.png)

6. `parseArrayData` 方法的具体实现在 `Mysql` 类中，`list($type, $value) = $data` 获取用户传入的 `$username` 数组的首两个元素，接着 `switch (strtolower($type))` 判断数组的首个元素，如果值为 `case 'point'` ，则执行以下代码：

   ```php
   $fun   = isset($data[2]) ? $data[2] : 'GeomFromText';
   $point = isset($data[3]) ? $data[3] : 'POINT';
   if (is_array($value)) {
   	$value = implode(' ', $value);
   }
   $result = $fun . '(\'' . $point . '(' . $value . ')\')';
   break;
   ```

7. 可以看到 `$result = $fun . '(\'' . $point . '(' . $value . ')\')'` 进行了没有过滤的拼接。

![图片8](/img/img-8.png)

8. 漏洞验证成功。

![图片9](/img/img-9.png)