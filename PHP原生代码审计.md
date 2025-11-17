# PHP 原生代码审计笔记

## 一、 代码审计环境配置

搭建一个高效的审计环境是发现漏洞的第一步。这通常涉及 Web 服务器、数据库、PHP 环境以及一个功能强大的代码编辑器。

### 1\. 核心工具栈

  * **集成环境:** **PHPStudy** 或其他 WAMP/MAMP/LAMP 工具。这能让你快速切换 PHP 版本，这对于复现特定版本漏洞至关重要。
  * **调试器:** **Xdebug** ([https://xdebug.org/download](https://xdebug.org/download))。这是 PHP 动态调试的核心插件。
  * **代码编辑器:** **Visual Studio Code** ([https://code.visualstudio.com/](https://code.visualstudio.com/))。
  * **VSCode 扩展:**
      * **PHP Debug** (by *felixfbecker*): 官方推荐，用于连接 Xdebug。
      * **PHP Intelephense** (by *Ben Mewburn*): 提供强大的代码智能提示、定义跳转和引用查找功能。

### 2\. 详细配置步骤

#### 第 1 步：安装 Xdebug 插件

1.  **确定 PHP 版本:** 在 PHPStudy 中，查看你启用的 PHP 版本的 `phpinfo()`。
2.  **关键信息:** 记下 `phpinfo()` 中的 **PHP Version**、**Architecture** (x86 or x64) 和 **Thread Safety (TS or NTS)**。
3.  **下载插件:**
      * **自动:** 复制 `phpinfo()` 页面的全部内容，粘贴到 [Xdebug Wizard](https://xdebug.org/wizard) 中，它会为你提供精确的下载链接和配置指南。
      * **手动:** 根据你的版本信息，在[下载页面](https://xdebug.org/download)找到对应的 `.dll` 文件 (Windows) 或 `.so` 文件 (Linux)。
4.  **放置插件:** 将下载的 `php_xdebug.dll` 文件放入 PHPStudy 的 `ext` 目录中。
      * *示例路径:* `X:\phpstudy_pro\Extensions\php\php7.3.4nts\ext\`

#### 第 2 步：配置 `php.ini`

在对应 PHP 版本的 `php.ini` 文件末尾添加 Xdebug 配置。

**注意：** 笔记中的配置是 Xdebug 2.x 和 3.x 的混合体。Xdebug 3.x (2020年后) 的配置已简化，推荐使用新版配置：

```ini
[Xdebug]
; 1. 指定 Xdebug 插件路径
zend_extension=php_xdebug.dll

; 2. 启用 Xdebug (Xdebug 3+ 新增)
xdebug.mode = debug

; 3. 设置连接方式
; "yes" 表示PHP一旦启动就尝试连接IDE，适合Web调试
xdebug.start_with_request = yes

; 4. IDE (VSCode) 的主机和端口
; 默认9003，笔记中使用8777也可以，保持与 VSCode 一致即可
xdebug.client_port = 8777
xdebug.client_host = 127.0.0.1
```

**完成后，必须重启 PHP 服务。**

#### 第 3 步：配置 VSCode (PHP Debug)

1.  在 VSCode 中，打开“运行与调试”侧边栏 (Ctrl+Shift+D)。
2.  点击“创建 launch.json 文件”，选择 “PHP”。
3.  这将生成一个 `.vscode/launch.json` 文件。确保其 `port` 与 `php.ini` 中的 `xdebug.client_port` 一致。

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 8777 // 必须与 php.ini 中的 client_port 一致
        }
    ]
}
```

#### 第 4 步：解决调试超时问题

在动态调试 (F5) 时，PHP 脚本会暂停在断点处 (F9)，但这可能会超过服务器的默认超时时间，导致连接中断并返回 500 错误。

1.  **PHP 超时:** 修改 `php.ini`，延长脚本最大执行时间。
    ```ini
    ; 默认 30 或 60 秒，调试时可设为 300 或更高
    max_execution_time = 300
    ```
2.  **Apache 超时:** 修改 Apache 的 `httpd.conf` 或 `httpd-default.conf`。
      * *示例路径:* `X:\phpstudy_pro\Extensions\Apache\conf\original\extra\httpd-default.conf`
    ; 默认 60 秒，调试时可设为 300
    TimeOut 300

**修改后，必须重启 Apache 和 PHP 服务。**

### 3\. VSCode 审计技巧

`PHP Intelephense` 扩展功能非常实用，这里将其归纳：

**调试快捷键:**

  * `F9`: 设置/取消断点。
  * `F5`: 启动调试（监听 Xdebug）。
  * `F10`: 单步跳过（逐行执行）。
  * `F11`: 单步进入（进入函数内部）。
  * `Shift+F11`: 单步跳出（跳出当前函数）。

**代码导航 (Intelephense):**

  * `Ctrl + 鼠标左键` (或 `F12`): 跳转到函数/类/变量的定义。
  * `Shift + F12`: 查找所有引用（查看该函数/变量在何处被调用）。

-----

## 二、 审计思路

### 1\. 反向审计：回溯敏感函数

  * **思路:** 从已知的“危险函数”出发，逆向追踪调用它的地方，查看其参数是否可控。
  * **方法:** 全局搜索 `eval()`, `system()`, `unserialize()`, `file_get_contents()`, `mysqli_query()` 等函数。
  * **优点:** 目标明确，效率高，适合快速挖掘高质量漏洞。
  * **缺点:** 可能会遗漏由逻辑复杂或不常见的函数组合导致的漏洞。

### 2\. 正向审计：追踪用户输入

  * **思路:** 从所有用户可控的输入源（如 `$_GET`, `$_POST` 等）出发，正向追踪这些数据的流动路径。
  * **方法:** 检查数据在传递过程中是否经过了充分的过滤和净化，以及它们最终流入了哪些函数。
  * **优点:** 全面，能发现包括逻辑漏洞在内的各种问题。
  * **缺点:** 耗时耗力，在大型项目中可能会迷失在复杂的数据流中。

### 3\. 功能审计：分析关键业务

  * **思路:** 专注于特定业务功能点的安全实现，这是前两种思路的结合。
  * **方法:** 审计如“用户注册/登录”、“密码找回”、“文件上传/下载”、“订单支付”、“个人信息修改”等高风险功能。
  * **优点:** 结合业务逻辑，能发现隐藏较深的漏洞，如越权、支付漏洞等。

-----

## 三、 用户可控输入源

理解 PHP 如何接收外部数据是正向审计的起点。

  * `$_GET`
      * **来源:** URL 查询字符串 (`?id=1&page=2`)。
      * `$HTTP_GET_VARS` (已废弃，不应在现代代码中出现)。
  * `$_POST`
      * **来源:** HTTP 请求体 (Request Body)。
      * **前提:** `Content-Type` 通常为 `application/x-www-form-urlencoded` 或 `multipart/form-data`。
      * `$HTTP_POST_VARS` (已废弃)。
  * `$_FILES`
      * **来源:** `multipart/form-data` 表单中的文件上传。
      * **包含:** 文件的临时名 (`tmp_name`)、原文件名 (`name`)、大小 (`size`)、类型 (`type`) 等。
  * `$_COOKIE`
      * **来源:** HTTP 请求头中的 `Cookie` 字段。
      * `$HTTP_COOKIE_VARS` (已废弃)。
  * `$_REQUEST`
      * **来源:** 默认情况下，按顺序包含 `$_GET`、`$_POST` 和 `$_COOKIE` 的内容。
      * **注意:** 这个变量的构成顺序可在 `php.ini` ( `request_order` ) 中配置。
  * `$_SERVER`
      * **来源:** Web 服务器提供的环境变量和请求头信息。
      * **危险字段:**
          * `$_SERVER['REQUEST_URI']` / `$_SERVER['PHP_SELF']`
          * `$_SERVER['HTTP_USER_AGENT']` / `$_SERVER['HTTP_REFERER']`
          * `$_SERVER['HTTP_X_FORWARDED_FOR']` / `$_SERVER['HTTP_CLIENT_IP']`
          * `$_SERVER['PATH_INFO']`
  * `php://input`
      * **来源:** 原始 HTTP 请求体 (Raw Post Data)。
      * **用途:** 常用于接收非 `application/x-www-form-urlencoded` 格式的数据，如 JSON、XML (SOAP) 等。
      * **读取:** `file_get_contents('php://input');`
  * `$HTTP_RAW_POST_DATA`
      * (已废弃，PHP 7.0 中移除) 曾用于访问 `php://input`。
  * `getallheaders()` / `apache_request_headers()`
      * **来源:** 获取所有 HTTP 请求头。
      * **注意:** `apache_request_headers()` 仅在 Apache 模块下可用。`getallheaders()` 在 PHP 5.4+ (FPM 模式) 和 PHP 7.3+ (所有 SAPI) 中更通用。

-----

## 四、 敏感函数

审计的核心工作之一就是查找危险函数，并回溯其参数是否来自用户可控的输入。

### 1\. 命令执行

这类函数会执行操作系统命令。

| 函数 | 说明 | 示例 |
| :--- | :--- | :--- |
| **`system`** | 执行外部命令，并**直接输出**所有结果。 | `system('id');` |
| **`passthru`** | 同 `system`，常用于执行需要返回原始二进制数据的命令。 | `passthru('id');` |
| **`exec`** | 执行外部命令，**不输出结果**。只返回结果的**最后一行**。 | `exec('id', $output); print_r($output);` |
| **`shell_exec`** | 执行外部命令，并**返回所有输出**的字符串。 | `$a = shell_exec('id'); echo $a;` |
| **\`\` (反引号)** | `shell_exec` 的别名。 | ```$a = `id`;echo $a;``` |
| **`popen`** | 打开一个指向进程的管道，返回文件指针，可用于读/写。 | `$a = popen("id", "r"); echo fread($a, 2096);`  |
| `proc_open` | 功能更强大的  `popen`，提供对进程的 STDIN, STDOUT, STDERR 的完全控制。 | (见 PHP 手册) |
| **`pcntl_exec` ** | 在当前进程空间执行指定程序（PHP 需开启 pcntl 扩展）。 |  `pcntl_exec('/bin/bash', ['-c', 'id']);\` |

**间接执行:**

  * `mail()` / `mb_send_mail()`: 在非 `safe_mode` (一个古老的 PHP 特性) 下，其第 5 个参数 `additional_parameters` 可以传入 `sendmail` 的额外参数，如 `-f` 或 `-X`，在特定配置下可能导致命令注入。
  * `COM` (Windows Only):
      * **前提:** 开启 `com_dotnet` 扩展 (Windows 版 PHP 默认开启)。
      * **利用:** `WScript.Shell` 对象是 Windows 自动化的利器。
```php
$wsh = new COM('WScript.Shell');
$wsh->Run('calc.exe'); // 运行计算器
$exec = $wsh->Exec('cmd.exe /c whoami'); // 执行并获取输出
$output = $exec->StdOut->ReadAll();
echo $output;
```

### 2\. 代码注入 / 文件包含

#### 代码注入

这类函数会将字符串作为 PHP 代码来执行。

  * `eval()`
      * **说明:** 语言构造器，非函数。将字符串当作 PHP 代码执行。
      * **示例:** `eval('phpinfo();');`
  * `assert()`
      * **说明:** PHP 7 以前是函数，PHP 7 及以上是语言构造器。将字符串作为 PHP 代码执行。
      * **示例:** `assert('phpinfo();');`
  * `preg_replace()`
      * **说明:** 当使用 `/e` 修饰符时，第二个参数（`replacement`）会被当作 PHP 代码执行。
      * **注意:** `/e` 修饰符在 **PHP 5.5.0 起被废弃**，**PHP 7.0.0 起被移除**。
      * **示例:** `echo preg_replace("/e", "{${phpinfo()}}", "123");`
  * `create_function()`
      * **说明:** 在内部动态创建一个匿名函数（通过 `eval` 实现），并返回函数名。
      * **注意:** **PHP 7.2.0 起被废弃**，**PHP 8.0.0 起被移除**。
      * **示例:** `$f = create_function('', 'system($_GET[123]);'); $f();`
  * `call_user_func()` / `call_user_func_array()`
      * **说明:** 将第一个参数作为回调函数调用。如果第一个参数（函数名）可控，即可执行任意函数。
      * **示例:**
          * `call_user_func('assert', 'phpinfo();');`
          * `call_user_func_array('file_put_contents', ['shell.php', '<?php ...']);`

#### 文件包含

这类函数会包含并执行指定文件，如果包含路径可控，可能导致本地文件包含 (LFI) 或远程文件包含 (RFI)。

  * `include` / `include_once`
      * **说明:** 包含并运行指定文件。如果文件未找到，产生 **Warning** 并继续执行。
  * `require` / `require_once`
      * **说明:** 包含并运行指定文件。如果文件未找到，产生 **Fatal Error** 并停止执行。

**RFI (远程文件包含) 前提:** `php.ini` 中 `allow_url_include = On` (同时要求 `allow_url_fopen = On`)。

### 3\. 回调函数

许多函数接受一个 `callable`（可调用类型）作为参数。如果该参数可控，它们就能像 `call_user_func` 一样执行任意代码。

  * `array_map()`: 将回调函数作用到数组中的每个元素。
      * `array_map('assert', array('phpinfo();'));`
  * `array_filter()`: 用回调函数过滤数组中的单元。
      * `array_filter(array('phpinfo()'), 'assert');`
  * `array_walk()` / `array_walk_recursive()`: 使用用户自定义函数对数组中的每个元素起作用。
      * `array_walk($arr, 'assert');`
  * `usort()` / `uasort()` / `uksort()`: 使用用户自定义的比较函数对数组进行排序。
  * `register_shutdown_function()`: 注册一个在 PHP 脚本执行完毕或 `exit()` 后调用的函数。
      * `register_shutdown_function('assert', 'phpinfo();');`
  * `register_tick_function()`: 注册一个在每个“tick”上执行的函数（需与 `declare(ticks=1)` 配合）。
  * `ob_start()`: 接受一个回调函数来处理输出缓冲区的内容。
      * `ob_start('assert'); echo 'phpinfo()'; ob_end_flush();`

### 4\. SQL / LDAP 注入

这类函数用于执行数据库或目录服务查询。如果查询语句使用字符串拼接了用户输入，则极易产生注入。

  * **MySQL (已废弃):**
      * `mysql_query`: **ext/mysql 扩展在 PHP 5.5.0 中被废弃，在 PHP 7.0.0 中被移除。**
  * **MySQL (现代):**
      * `mysqli_query`、`mysqli::query`、`mysqli_db_query`、`mysqli_unbuffered_query`
  * **PostgreSQL:**
      * `pg_query`、`pg_query_params`、`pg_send_query`、`pg_send_query_params`
  * **PDO (PHP Data Objects):**
      * `PDO::query`: 如果 SQL 是拼接的，则有风险。
      * `PDO::exec`: 同上。
      * **安全方案:** `PDO::prepare` + `PDOStatement::execute` (预处理)。
  * **SQLite:**
      * `SQLite3::query`、`SQLite3::exec`
  * **NoSQL (MongoDB):**
      * `$collection->find($where)`: 如果 `$where` 数组由用户输入构成，可能导致 NoSQL 注入。
  * **LDAP:**
      * `ldap_search`: 过滤器的注入。
      * `ldap_bind`: DN 注入。

### 5\. 文件读取 / SSRF

绝大多数文件操作函数都支持 URL 包装器（如 `http://`, `ftp://`, `php://`）。如果文件名或 URL 由用户控制，就可能导致本地文件读取 (LFI)、任意文件读取或服务端请求伪造 (SSRF)。

**SSRF / RFI 前提:** `allow_url_fopen = On`。

| 函数 | 风险点 (LFI: 本地 / SSRF: 远程) |
| :--- | :--- |
| **`file_get_contents`** | LFI / SSRF |
| **`readfile`** | LFI / SSRF (读取并输出文件) |
| **`file`** | LFI / SSRF (将文件读入数组) |
| **`fopen`** / **`fread`** / ... | LFI / SSRF (文件流操作) |
| **`curl_exec`** | SSRF (功能最强，支持 `gopher://`, `dict://` 等) |
| **`fsockopen`** | SSRF (低级别 TCP/UDP 套接字连接) |
| **`highlight_file`** / **`show_source`** | LFI (语法高亮显示 PHP 文件源码) |
| **`parse_ini_file`** | LFI (读取并解析 .ini 文件) |
| **`simplexml_load_file`** | LFI / SSRF / XXE |

### 6\. 文件上传 / 写入 / 其他

  * **文件写入:**
      * `file_put_contents`: 将字符串写入文件。
      * `fopen` / `fwrite` / `fputs` (以 'w', 'a' 等模式打开时)。
  * **文件上传:**
      * `move_uploaded_file`: 将上传的临时文件移动到新位置。这是处理 `$_FILES` 的标准函数。
  * **文件/目录操作 (路径遍历风险):**
      * `copy`: 复制文件。
      * `rename`: 重命名或移动文件/目录。
      * `mkdir`: 创建目录。
      * `link`: 创建硬链接。
      * `symlink`: 创建符号链接 (软链接)。
  * **文件删除 (任意文件删除风险):**
      * `unlink`: 删除文件。
      * `rmdir`: 删除空目录。
  * **文件解压 (目录穿越 / Phar 反序列化):**
      * `ZipArchive::extractTo`: 将 ZIP 解压到目录。如果 ZIP 中包含 `../../shell.php` 这样的文件名，可能导致目录穿越写 shell。
  * **XML 解析 (XXE 风险):**
      * **`libxml_disable_entity_loader(true);` 是 PHP \< 8.0 的标准防御手段。**
      * **注意:** libxml \>= 2.9.0 (PHP \>= 8.0 默认使用) 默认禁止外部实体，`libxml_disable_entity_loader` 函数被废弃。
      * `DOMDocument::loadXML`
      * `simplexml_load_file`
      * `simplexml_load_string`
      * `simplexml_import_dom`

### 7\. 反序列化

  * `unserialize()`
      * **说明:** 将序列化字符串还原为 PHP 值。
      * **风险:** 漏洞本身不在函数，而在于程序中已定义的类。如果反序列化一个对象，其 `__wakeup()` 魔法函数会被自动调用。如果该类或其他相关类中存在 `__destruct()`, `__toString()`, `__get()` 等魔法函数，并且它们执行了危险操作（如文件写入、命令执行），攻击者就可以通过构造恶意的序列化字符串（称为“POP 链”或“Gadget 链”）来触发这些操作。
      * **常见触发点:** 除了直接调用 `unserialize()`，`phar://` 伪协议在被文件函数（如 `file_get_contents`, `file_exists`）访问时，会自动反序列化 Phar 文件中的元数据 (meta-data)。

-----

## 五、 PHP 内置过滤/防御函数

在审计时，不仅要看参数是否可控，还要看它们是否经过了正确的过滤。

| 分类 | 函数/方法 | 用途 | 常见问题/局限性 |
| :--- | :--- | :--- | :--- |
| **类型检测** | `intval`, `(int)` | **类型强转。**将输入强制转换为整数。 | 防护数字型 SQL 注入的**最佳方式**。 |
| **类型检测** | `is_int`, `is_string`, `is_numeric` 等 `is_*` 系列 | **类型判断。**检查变量是否为预期的数据类型。 | 必须与 `if` 语句配合使用，否则只返回布尔值。 |
| **通用过滤** | `filter_var()` | **验证/净化。**使用 `FILTER_SANITIZE_*` (如 `_STRING`, `_NUMBER_INT`) 来净化数据，或 `FILTER_VALIDATE_*` (如 `_EMAIL`, `_URL`) 来验证数据。 | 功能强大，是现代 PHP 中推荐的过滤方式。 |
| **命令注入** | `escapeshellarg` | **防止命令注入。**将传入的字符串转义并**添加单引号**，使其成为一个安全的命令行参数。 | 只能用于防护**单个参数**，不能用于防护命令本身。 |
| **命令注入** | `escapeshellcmd` | **防止命令注入。**转义字符串中 ```&#;` |\*?\~\<\>()[]{}$```等特殊字符，防止注入新命令。 | **与`escapeshellarg`一起使用时可能导致绕过** (双重转义问题)。单独使用时，仍可能被```echo "\<?php..." \> a.php``` 这样的方式绕过。 |
| **SQL 注入** |  `addslashes`  | **防止 SQL 注入 (已弃用)。**在 `(') (") () (NUL)`前加反斜线。 | 这是一个不安全的SQL防护方式。它不考虑数据库字符集，在`GBK`等多字节字符集下可被`0xbf27` (寬') 等字符绕过。 |
| **SQL 注入** |  `mysqli\_real\_escape\_string`| **防止 SQL 注入。**转义特殊字符 (`\\x00`,`\\n`,`\\r`,`\`, `'`, `"`, `\x1a`)，并**考虑当前数据库连接的字符集**。 | 必须在数据库连接后调用。**注意：** 转义后的字符串必须用**引号**包裹在 SQL 语句中，否则数字型注入仍然可能。 (旧的 `mysql_real_escape_string` 已废弃) |
| **SQL 注入** | `PDO::quote` | **防止 SQL 注入。**转义特殊字符，并**自动添加引号**。 | 推荐的字符串型参数防护方式。 |
| **SQL 注入** | `PDO::prepare` | **防止 SQL 注入 (最佳实践)。**使用预处理语句和参数绑定的方式，将 SQL 结构和数据完全分离。 | **这是最推荐的、最安全的**防护 SQL 注入的方法。 |
| **XSS 跨站** | `htmlspecialchars` | **防止 XSS。**将 `&`, `"`, `'`, `<`, `>` 转换为 HTML 实体。 | 默认不转义单引号 (需设置 `ENT_QUOTES` 参数)。如果输出在 JS 脚本中，还需要进行 JavaScript 字符串转义。 |
| **XSS 跨站** | `htmlentities` | **防止 XSS。**将所有适用的字符都转换为 HTML 实体。 | 功能更强，但也可能导致非预期的编码问题。 |
| **XSS 跨站** | `strip_tags` | **防止 XSS。**剥去字符串中的 HTML、XML 以及 PHP 的标签。 | 可能会破坏合法用户的正常输入；可指定允许的标签白名单。 |

## 六、 安全相关的 `php.ini` 配置

这些 `php.ini` 中的配置项决定了 PHP 环境的安全性。

  * `disable_functions` / `disable_classes`
      * **作用:** 禁用指定的函数和类，是 `safe_mode` 的升级版。
      * **示例:** `disable_functions = system,eval,exec,passthru,shell_exec,popen,proc_open,pcntl_exec`
  * `open_basedir`
      * **作用:** 将 PHP 可访问的文件系统路径限制在指定的目录树中。
      * **目的:** 防护 LFI 和任意文件读/写，限制 `file_get_contents('../../../etc/passwd')` 等行为。
  * `allow_url_fopen = On/Off`
      * **作用:** 决定是否允许将 URL (http://, ftp://) 当作文件来打开。
      * **风险 (On):** 允许 SSRF (如 `file_get_contents('http://...')`)。
  * `allow_url_include = On/Off`
      * **作用:** 决定是否允许 `include`/`require` 远程 URL。
      * **风险 (On):** 允许远程文件包含 (RFI) 漏洞。 (依赖 `allow_url_fopen=On`)。
  * `session.cookie_httponly = On/Off`
      * **作用:** `On` 时，设置 `HttpOnly` 标记，禁止 JavaScript (`document.cookie`) 访问 Cookie。
      * **目的:** 缓解 XSS 盗取 Session Cookie 的危害。
  * `session.cookie_secure = On/Off`
      * **作用:** `On` 时，强制 Cookie 只能通过 HTTPS 连接发送。
      * **目的:** 防止会话在 HTTP 明文传输中被嗅探。
  * `expose_php = On/Off`
      * **作用:** 决定是否在 HTTP 响应头中包含 PHP 版本信息 (如 `X-Powered-By: PHP/7.3.4`)。
      * **建议:** `Off` (安全实践，隐藏版本信息)。
  * `display_errors = On/Off`
      * **作用:** A决定是否将错误信息显示给用户。
      * **建议:** `Off` (生产环境)，`On` (开发环境)。在生产环境暴露错误会泄露敏感路径和信息。
  * `upload_tmp_dir`
      * **作用:** 设置文件上传的临时目录。如果该目录设置在 `open_basedir` 之外，可能导致某些安全限制失效。
  * `safe_mode`
      * **说明:** **已废弃**。旧版本 PHP 中用于限制某些危险函数，但存在很多绕过方式，已在 PHP 5.4.0 中被移除。
  * `magic_quotes_gpc`
      * **说明:** **已废弃**。自动对GET/POST/COOKIE数据进行转义。这种“一刀切”的方式并不可靠（例如会污染非数据库操作的数据），现已不推荐使用，应由代码层面（如 `PDO::prepare`）处理。

在代码入口处或通过 Web 服务器配置一个全局文件/模块，对所有传入的请求进行过滤。

1.  **关键内容检测 (黑/白名单)**: 检查请求参数中是否包含已知的攻击特征（黑名单），如 `select`, `<script>`, `../` 等；或只允许特定格式的输入（白名单）。
2.  **模仿流量检测 (基于规则或AI算法)**: 更高级的防护，通过 WAF (Web 应用防火墙) 或流量监控设备，使用预设规则或机器学习模型来识别异常和未知的攻击请求。

-----

## 七、 漏洞分析：命令注入

  * **定义:** 将用户输入作为命令的一部分拼接到命令行中执行，导致任意命令执行。
  * **示例代码:**
    ```php
    <?php
    $command = 'ping -c 1 ' . $_GET['ip'];
    system($command);
    ?>
    ```
```
  * **正常请求:** `.../ping.php?ip=114.114.114.114`
      * **执行命令:** `ping -c 1 114.114.114.114`
  * **攻击请求:** `.../ping.php?ip=114.114.114.114; whoami`
      * **执行命令:** `ping -c 1 114.114.114.114; whoami` (使用 `;`, `|`, `||`, `&&` 等分隔符)
  * **审计:** 检查 `system`, `exec` 等函数的参数是否包含可控变量。
  * **防御:**
      * **最佳:** 尽量不使用命令执行函数。
      * **次佳:** 使用 `escapeshellarg()` **包裹参数**。
        ```php
        $ip = escapeshellarg($_GET['ip']);
        $command = 'ping -c 1 ' . $ip; // $ip 会被 '...' 包裹
        system($command);
```

-----

## 八、 漏洞分析：代码注入

  * **定义:** 将用户输入作为 PHP 代码拼接到 `eval()` 等函数中执行，导致任意代码执行。
  * **示例代码 (计算器):**
    ```php
    <?php eval( 'echo (' . $_GET['a'] . ');'); ?>
    ```
  * **正常请求:** `.../calc.php?a=9*9`
      * **执行代码:** `eval( 'echo (9*9);');` (输出 81)
  * **攻击请求:** `.../calc.php?a=system('whoami')`
      * **执行代码:** `eval( 'echo (system('whoami'));');` (成功执行命令)
  * **其他形式:**
      * **动态函数执行:** `$a = $_GET['func']; $a('ls');`
      * **Curly Syntax (花括号语法):** ```$var = "aaabbbccc ${`ls`}";``` 或 `$foobar = "phpinfo"; ${"foobar"}();`
      * **回调函数:** `array_map($_GET["callback"], $some_array);` (如 `?callback=system`)
  * **防御:**
      * **最佳:** **杜绝使用 `eval`**、`assert` (用于代码执行)、`preg_replace /e`、`create_function`。
      * **次佳:** 对用户输入建立**严格的白名单**，过滤掉所有非预期的字符。

-----

## 九、 漏洞分析：文件包含

  * **定义:** 将用户输入作为文件路径拼接到 `include` / `require` 中，导致包含任意本地文件或远程文件。
  * **示例代码:**
    ```php
    <?php
    $file = $_GET['page'];
    include("pages/" . $file); // 存在目录拼接
    ?>
    ```
  * **攻击请求 (LFI):** `.../index.php?page=../../etc/passwd`
      * **包含文件:** `pages/../../etc/passwd` (目录穿越)
  * **利用技巧:**
      * **%00 截断:** `?page=../../etc/passwd%00` (需要 `magic_quotes_gpc=off` 且 PHP \< 5.3.4)。
      * **路径长度截断:** (PHP \< 5.2.8) 利用超长路径截断。
      * **点号截断:** (PHP \< 5.2.8, Windows) 利用 `...`。
      * **包含上传文件:** (最常见) 包含上传的图片马 `?page=../uploads/avatar.jpg`。
      * **包含日志:** 包含 Web 日志、SSH 日志等 (需权限)。
      * **包含 `/proc/self/environ`:** (Linux) 包含环境变量，可注入 User-Agent。
      * **包含 `session` 文件:** (需知道 session 存储路径和 ID) `?page=../tmp/sess_SESSIONID`。
      * **使用 PHP 伪协议 (Wrapper):**
          * `php://filter`:
            `?page=php://filter/read=convert.base64-encode/resource=index.php` (读取源码)
          * `php://input` (需 `allow_url_include=On`):
            `?page=php://input` (POST 请求体为 `<?php system('id'); ?>`)
          * `data://` (需 `allow_url_include=On`):
            `?page=data://text/plain;base64,PD9waHAgc3lzdGVtKCdpZCcpOyA/Pg==`
          * `zip://`, `phar://` (无需特殊配置):
            `?page=zip://path/to/archive.zip%23shell.php` (读取压缩包内的文件)
            `?page=phar://path/to/archive.phar/shell.php` (可触发反序列化)
  * **防御:**
      * **最佳:** 对 `page` 参数建立**严格的白名单**，如 `if (!in_array($file, ['home', 'about', 'contact'])) { die(); }`。
      * **次佳:** 过滤 `..`、`/`、`\`、`:` 等字符，并严格限制文件扩展名。

-----

## 十、 漏洞分析：SQL 注入

  * **定义:** 将用户输入拼接到 SQL 语句中，导致攻击者可以篡改 SQL 逻辑，进行数据窃取、篡改、删除，甚至获取服务器权限。
  * **示例代码 (数字型注入):**
    ```php
    <?php
    include('conn.php'); // 数据库连接省略
    // $mysqli 已被实例化
    $sql = "SELECT id, name FROM users WHERE id=" . $_GET['id'];
    $result = $mysqli->query($sql);
    // ... 后续代码
    ?>
    ```
  * **攻击请求:** `.../user.php?id=123 UNION SELECT name, password FROM users`
      * **执行 SQL:** `SELECT id, name FROM users WHERE id=123 UNION SELECT name, password FROM users;`
      * **危害:** 攻击者通过 `UNION` 查询，窃取了 `users` 表中的 `name` 和 `password` 字段。
  * **审计:** 检查所有数据库查询函数 (如 `mysqli_query`, `PDO::query`) 的参数是否通过字符串拼接了可控变量。
  * **防御 (最佳实践排序):**
    1.  **预处理语句 (PDO::prepare):**
          * **原理:** 将 SQL 语句模板和数据分开传输给数据库，从根本上杜绝了注入。
          * **示例:**
            ```php
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id=?;");
            $stmt->execute([$_GET['id']]);
            $user = $stmt->fetch();
            ```
    2.  **强制类型转换 (数字型):**
          * **原理:** 如果参数明确是数字，直接将其转换为数字类型。
          * **示例:** `$id = intval($_GET['id']);` 或 `$id = (int)$_GET['id'];`
    3.  **转义并加引号 (字符串型):**
          * **`PDO::quote`:** 自动转义并为字符串添加引号。
            ```php
            $name = $pdo->quote($_GET['name']); // 结果: 'O\'Malley'
            $sql = "SELECT ... WHERE name=" . $name;
            ```
          * **`mysqli_real_escape_string`:** 转义并考虑字符集，**但需要手动加引号**。
            ```php
            $name = mysqli_real_escape_string($conn, $_GET['name']);
            $sql = "SELECT ... WHERE name='" . $name . "'"; // 注意：必须手动加单引号
            ```
    4.  **`addslashes` (不推荐):**
          * **缺陷:** 不考虑数据库字符集，在 GBK 等多字节编码下可被轻松绕过。**不应**用于 SQL 防护。

-----

## 十一、 漏洞分析：文件删除

  * **定义:** `unlink` 或 `rmdir` 函数的参数可控，导致攻击者可以删除服务器上的任意文件。
  * **敏感函数:**
      * `unlink(文件路径)`: 删除文件。
      * `rmdir(文件夹路径)`: 删除**空**目录。
  * **常见利用:**
      * **删除 `.lock` 文件:** 绕过程序安装、重置等安全限制。
      * **删除关键文件:** 删除如数据库文件、配置文件、首页文件，导致网站拒绝服务 (DoS) 或数据丢失。
      * **条件竞争:** 在特定业务逻辑下，删除临时文件以触发其他漏洞。
  * **防御:**
      * 严格校验用户传入的路径，禁止 `../` 等目录穿越字符。
      * 将用户可删除的文件限制在特定、无权限的目录内。
      * 使用白名单校验文件名。

-----

## 十二、 漏洞分析：文件写入 / 上传

### 1\. 文件写入

  * **定义:** `file_put_contents` 等函数的写入路径或内容可控，导致写入 WebShell。
  * **敏感函数:**
      * `file_put_contents(路径, 字符串)`: 直接将字符串写入文件（不存在则创建）。
      * `fopen(文件路径, "w")` / `fwrite($fp, 字符串)`: 以写入模式打开文件并写入。
  * **防御:**
      * 对写入路径进行严格过滤，防止目录穿越和修改文件扩展名。
      * 对写入内容进行过滤，防止写入 `<?php` 等恶意代码。

### 2\. 文件上传

  * **定义:** `move_uploaded_file` 的目标路径可控，或对上传文件的类型、内容校验不严。
  * **标准流程:** 收到 POST 表单 -\> PHP 将文件保存到**临时目录** (`upload_tmp_dir`) -\> PHP 脚本执行 -\> `move_uploaded_file(临时文件路径, 目标文件路径)` -\> 脚本结束，删除未被移动的临时文件。
  * **敏感函数:**
      * `move_uploaded_file($_FILES["file"]["tmp_name"], $target_path)`
  * **风险点:**
      * `$target_path` 可控，导致任意目录写入和扩展名篡改（如 `../shell.php`）。
      * **校验绕过:** 仅在前端 JS 校验、仅检查 `Content-Type` 头、或使用黑名单（可被 `php5`, `phtml` 等绕过）。
  * **注意 (LFI 配合):**
      * 笔记中提到一个重要技巧：**PHP 只要收到文件上传请求，就会创建临时文件**，即使脚本中没有处理上传的逻辑。
      * 这个临时文件在脚本执行结束后才被删除。
      * 攻击者可利用 LFI (文件包含) 漏洞，通过**条件竞争** (Race Condition) 在临时文件被删除前包含它，从而执行代码。
  * **防御:**
      * **白名单校验扩展名:** 只允许如 `jpg`, `png`, `gif`。
      * **目标路径/文件名不可控:** 目标文件名应随机生成（如 `md5(time() . rand())`），目录应固定。
      * **内容检查:** 对图片文件使用 `getimagesize()` 或 `exif_imagetype()` 检查是否为真实图片。
      * **权限控制:** 上传目录应设置为不可执行脚本。

-----

## 十三、 漏洞分析：文件解压

  * **定义:** 使用 `ZipArchive::extractTo` 解压用户上传的 ZIP 文件时，未校验压缩包内的文件名，导致目录穿越。
  * **敏感函数:**
      * `$zip = new ZipArchive;`
      * `$zip->open($file_path);`
      * `$zip->extractTo($target_dir);` (**核心风险点**)
      * `$zip->close();`
  * **利用:**
      * 攻击者创建一个包含恶意路径的 ZIP 文件，例如文件名
        为 `../../var/www/shell.php`。
      * 当 `$zip->extractTo('upload/')` 执行时，文件会被解压到
        `upload/../../var/www/shell.php`，即成功写入 WebShell。
  * **防御:**
      * 在解压前，循环遍历 ZIP 包内的所有文件名 (`$zip->getNameIndex($i)`)。
      * 检查每个文件名是否包含 `../`，或是否是绝对路径。
      * 对文件名进行过滤，确保解压后的文件路径仍然在 `$target_dir` 目录内。

-----

## 十四、 漏洞分析：XSS (跨站脚本)

  * **定义:** 攻击者将恶意的 JavaScript 脚本注入到网页中，当其他用户访问该网页时，浏览器会执行这些脚本。
  * **示例代码 (反射型 XSS):**
    ```php
    <?php
    echo 'Hello ' . $_GET['name'] . '!';
    ?>
    ```
  * **攻击请求:** `.../index.php?name=<script>alert('XSS')</script>`
      * **浏览器收到:** `Hello <script>alert('XSS')</script>!`
      * **危害:** 脚本被执行。可用于盗取 Cookie、会话劫持、键盘记录、钓鱼等。
  * **防御:**
      * **核心防御 (输出时):**
          * `htmlspecialchars($input, ENT_QUOTES, 'UTF-8')`: 将 `<`, `>`, `&`, `'`, `"` 转义为 HTML 实体。这是防止 XSS 的标准函数。
      * **纵深防御 (HTTP 头):**
          * **CSP (内容安全策略):** `Content-Security-Policy` 头。
              * **作用:** 严格限制浏览器可以加载哪些来源的脚本、样式、图片等。
              * **示例:** `Content-Security-Policy: script-src 'self'` (只允许加载同源的 JS)。
          * **HttpOnly Cookie:**
              * **作用:** `Set-Cookie: sessionid=...; HttpOnly`。
              * **目的:** 禁止 JS (`document.cookie`) 访问 Cookie，极大缓解 XSS 盗取会话的危害。

-----

## 十五、 漏洞分析：SSRF (服务端请求伪造)

  * **定义:** 攻击者利用服务器端的代码（如远程图片下载），让服务器代替攻击者发起网络请求。
  * **危害:**
      * **访问/攻击内网:** 扫描内网端口、攻击内网服务 (如 Redis, Struts2)。
      * **获取内网信息:** 读取内网应用或元数据 (如 `http://169.254.169.254/` 获取云服务器元数据)。
      * **绕过 IP 限制:** 以服务器的 IP 访问受限资源。
  * **敏感函数:**
      * `file_get_contents()` (最常见)
      * `curl_exec()` (功能最强，支持 `gopher://`, `dict://` 等危险协议)
      * `fsockopen()` (可发起 TCP/UDP 请求)
      * 几乎所有**文件读取函数** (如 `readfile`, `fopen`, `file`) 在 `allow_url_fopen=On` 时都可触发 SSRF。
  * **防御:**
      * **协议白名单:** 严格限制只允许 `http` 和 `https` 协议。
      * **IP/域名白名单:** 限制请求的目标主机必须在允许的列表中。
      * **禁止 30x 跳转:** `curl` 需要设置 `CURLOPT_FOLLOWLOCATION` 为 `false`，防止绕过。
      * **解析 IP:** 将域名解析为 IP，并检查该 IP 是否为内网地址 (如 `10.x`, `172.16-31.x`, `192.168.x`, `127.0.0.1`)。

-----

## 十六、 漏洞分析：CSRF (跨站请求伪造)

  * **定义:** 攻击者诱使已登录的用户（受害者）在不知情的情况下，向目标网站发送一个恶意请求。该请求会携带受害者的 Cookie（身份凭证），从而以受害者的身份执行非预期的操作（如修改密码、发帖、转账）。
  * **核心:** 攻击者**只能“发起”请求**，但**不能“获取”响应**。

### 1\. 常见攻击类型

  * **GET 型表单/链接:**
      * **利用:** 攻击者在恶意网站上放置一个 `<img>`、`<a>` 或 `script` 标签，其 `src` / `href` 指向目标网站的危险操作。
      * **示例:** `<img src="http://target.com/user/delete?id=123" width="1" height="1">`
  * **POST 型表单:**
      * **利用:** 攻击者创建一个自动提交的 POST 表单，诱使用户访问。
      * **示例:**
        ```html
        <body onload="document.forms[0].submit()">
          <form action="http://target.com/user/update_password" method="POST">
            <input type="hidden" name="password" value="hacked" />
          </form>
        </body>
        ```
  * **JSONP 请求:**
      * **利用:** JSONP 通过 `<script>` 标签实现跨域，天然会携带 Cookie。如果一个 JSONP 接口同时执行了敏感的**写入**操作（这是错误的设计），就会导致 CSRF。
      * **危害:** 攻击者不仅能发起写操作，还能通过定义回调函数来**窃取**返回的 JSONP 数据（如个人信息、账户余额）。
  * **AJAX 请求 (CORS):**
      * **注意:** 现代浏览器有同源策略 (SOP) 保护。跨域 AJAX 默认**不会**携带 Cookie。
      * **风险点:** 如果目标服务器错误地设置了 `Access-Control-Allow-Origin: *` **并** `Access-Control-Allow-Credentials: true`，浏览器就会发送带有 Cookie 的跨域 AJAX 请求，导致 CSRF。

### 2\. 防御方法

1.  **Anti-CSRF Token:**
      * **原理:** 在用户会话中生成一个随机 Token，并将其嵌入所有表单和 AJAX 请求中。服务器在收到请求时，必须验证该 Token 是否与会话中存储的一致。
      * **优点:** 攻击者无法猜测 Token，因此无法伪造合法请求。
2.  **检查 `Referer` / `Origin` 头:**
      * **原理:** 检查 HTTP 请求头中的 `Referer` (来源页面) 或 `Origin` (来源域)，确保请求来自同源网站。
      * **注意:** **必须**拒绝空的 `Referer`，因为攻击者可以通过 `data:` URI 等方式发起空 `Referer` 请求。
3.  **SameSite Cookie 属性:**
      * **原理:** `Set-Cookie: sessionid=...; SameSite=Strict` (或 `Lax`)。
      * **`Strict`:** 完全禁止第三方网站携带此 Cookie。
      * **`Lax` (现代浏览器默认):** 允许 GET 链接跳转等导航操作携带，但禁止 POST 表单、AJAX、`<img>` 等跨域请求携带。
      * **优点:** 浏览器原生防御，非常有效。

-----

## 十七、 漏洞分析：XXE (XML 外部实体注入)

  * **定义:** XML 支持“外部实体”，允许在 XML 文档中引用外部文件或 URL。如果解析器未禁止此功能，攻击者就可以通过构造恶意的 XML 数据，在服务器上执行任意文件读取、SSRF 或 DoS 攻击。
  * **常见场景:** 支付回调、API 接口、SOAP 服务、RSS 解析等一切接收 XML 数据的入口。
  * **利用 (文件读取):**
    ```xml
    <?xml version="1.0" encoding="UTF-8"?>
    <!DOCTYPE root [
      <!ENTITY xxe SYSTEM "file:///etc/passwd">
    ]>
    <root>
      <user>&xxe;</user> </root>
    ```
  * **敏感函数:**
      * `simplexml_load_string`
      * `simplexml_load_file`
      * `DOMDocument::loadXML`
      * `simplexml_import_dom`
  * **防御:**
      * **PHP 8.0+:** **默认安全**。PHP 8.0 开始使用 libxml 2.9.0+，该版本**默认禁止**解析外部实体，且 `libxml_disable_entity_loader` 函数被废弃。
      * **PHP \< 8.0:** **必须**在解析 XML 之前，调用 `libxml_disable_entity_loader(true);` 来**全局禁用**外部实体。

-----

## 十八、 漏洞分析：反序列化

  * **定义:** `unserialize()` 函数将字符串还原为 PHP 对象时，会自动调用该对象的魔法函数（如 `__wakeup` 或 `__unserialize`）。如果该类或程序中其他可自动加载的类存在一系列被 `__wakeup()`, `__destruct()`, `__toString()` 等魔法函数调用的“危险操作”（称为 Gadget），攻击者就能通过构造恶意的序列化字符串（称为 POP 链）来触发这些操作。
  * **核心:** 漏洞不在 `unserialize()` 本身，而在**程序中已有的类 (Gadgets)**。

### 1\. 关键魔法函数

| 魔法函数 | 触发时机 |
| :--- | :--- |
| `__construct` | 对象被创建时 (当对象 `new` 的时候会自动调用)。 |
| `__destruct` | 对象被销毁时 (如脚本结束或 `unset()`)。|
| `__unserialize` | `unserialize()` 执行时。如果类中同时定义了 `__unserialize()` 和 `__wakeup()`，则**只有 `__unserialize()` 会生效**，`__wakeup()` 会被忽略。 |
| `__wakeup` | `unserialize()` 执行时 (前提是 `__unserialize` 未定义)。 |
| `__sleep` | `serialize()` 执行时。 |
| `__toString` | 当对象被当作字符串使用时 (如 `echo $obj;`)。 |
| `__get` | 读取一个不存在或不可访问的属性时。 |
| `__set` | 写入一个不存在或不可访问的属性时。 |
| `__call` | 调用一个不存在或不可访问的方法时。 |
| `__callStatic`| 在静态上下文中调用不可访问的方法时触发。 |
| `__invoke` | 当对象被当作函数调用时 (如 `$obj();`)。 |
| `__isset` | 在不可访问的属性上调用 `isset()` 或 `empty()` 触发。 |
| `__unset` | 在不可访问的属性上使用 `unset()` 时触发。 |
| `__set_state` | 调用 `var_export()` 导出类时，此静态方法会被调用。 |
| `__clone` | 当对象复制完成时调用。 |
| `__autoload` | 尝试加载未定义的类时。 |
| `__debugInfo`| 打印所需调试信息时 (`var_dump()`)。 |

### 2\. 常见反序列化触发点

  * **直接调用:** `unserialize($_POST['data']);`
  * **Phar 反序列化:**
      * **原理:** 大多数文件系统函数 (如 `file_exists`, `file_get_contents`, `unlink`, `fopen`) 在操作 `phar://` 伪协议时，会自动反序列化 Phar 文件中存储的 `meta-data` (元数据)。
      * **利用:** 攻击者上传一个精心构造的 `.phar` 文件 (可伪装成 `.jpg` 等)，然后利用 LFI 或其他文件操作漏洞，通过 `phar://` 协议访问它，即可触发反序列化。
      * **Payload 生成:** 需要修改 `php.ini` 中的 `phar.readonly = Off`，然后使用以下代码生成恶意的 Phar 文件。
        ```php
        $phar = new Phar("poc.phar");
        $phar->startBuffering();
        $phar->setStub("<?php __HALT_COMPILER(); ?>");

        $object = new YourGadget(); // 你的POP链对象
        $phar->setMetadata($object); // 将对象存入meta-data

        $phar->addFromString("test.txt", "text");
        $phar->stopBuffering();
        ```
  * **Session 反序列化:** PHP 的 Session 存储机制默认使用 `serialize()` / `unserialize()`。如果攻击者可以控制 Session 文件的内容，也能触发反序列化。

### 3\. 高级利用技巧与绕过

  * **`__wakeup` 绕过 (CVE-2016-7124)**

      * **原理:** 在 PHP 5.6.25 之前和 PHP 7.0.10 之前的版本中，如果序列化字符串中表示对象属性的个数大于实际定义的属性个数，`unserialize()` 会成功执行，但会**跳过 `__wakeup()`** 魔术方法的调用。
      * **利用:** 当 `__wakeup()` 方法会重置或干扰我们的 Payload 时（例如 `exit();`），可以通过修改序列化字符串中的属性计数值来绕过它。
      * **示例:**
          * 原始 Payload (1 个属性): `O:4:"xctf":1:{s:4:"flag";s:3:"111";}`
          * 绕过 Payload (声明 2 个属性): `O:4:"xctf":2:{s:4:"flag";s:3:"111";}`

  * **`__wakeup` 绕过 (引用赋值)**

      * **原理:** 利用 PHP 的引用赋值 `&`，使一个属性与 `wakeup` 属性指向同一内存地址。在 `__destruct` 中修改该属性，即可同步修改 `wakeup` 属性的值，从而绕过 `__wakeup` 中设置的 `True` 值。
      * **利用:**
        ```php
        class KeyPort{
            public $key;
            public $wakeup;

            public function __destruct(){
                // 此处修改 key，也会同时修改 wakeup
                $this->key = False; 
                if(!isset($this->wakeup) || !$this->wakeup){
                    echo "You get it!";
                }
            }
            public function __wakeup(){
                $this->wakeup = True;
            }
        }

        $keyport = new KeyPort();
        // 关键步骤：将 key 引用到 wakeup
        $keyport->key = &$keyport->wakeup; 
        echo serialize($keyport);
        // Payload: O:7:"KeyPort":2:{s:3:"key";N;s:6:"wakeup";R:2;}
        // R:2; 表示 wakeup 引用了第 2 个属性 (key)
        ```
      * **解析:** `__wakeup()` 先执行，将 `$wakeup` 设为 `True`（`$key` 也变为 `True`）。随后 `__destruct()` 执行，将 `$key` 设为 `False`（`$wakeup` 也变为 `False`），成功绕过检查。

  * **`__wakeup` 绕过 (fast-destruct / 提前析构)**

      * **原理:** 通过构造一个格式错误的序列化字符串（如缺少 `}`、错误的属性长度），使 `unserialize()` 在解析中途出错。当解析失败时，PHP 会立即销毁已成功解析的对象，触发其 `__destruct()` 方法。如果此时 `__wakeup()`（它在*成功*解析后才被调用）尚未被调用，则成功绕过。
      * **利用 (fast-destruct):** 故意破坏序列化字符串的结尾，例如删除最后一个 `}` 或在末尾添加多余字符。
          * `...s:9:"cat /flag";}s:4:"hint";r:10;`**`1}`**`}}` (在 `r:10;` 后加 `1`)
      * **利用 (PHP issue \#9618):** 适用于 PHP 7.4.x - 7.4.30 / 8.0.x。利用 `private` 或 `protected` 属性名在序列化时包含不可见字符 `\0`（如 `\0*\0prop`）的特性。当 payload 传入时，`\0` 字符可能被截断或错误处理，导致解析器认为字符串长度不匹配，从而触发解析错误，提前调用 `__destruct()`。
          * `O:1:"A":2:{s:4:"info";O:1:"B":1:{s:3:"znd";N;}s:6:"\0A\0end";s:1:"1";}`

  * **属性类型混淆**

      * **原理:** PHP 对 `public`, `protected`, `private` 三种属性的序列化格式不同：
          * `public $a` -\> `s:1:"a";`
          * `protected $a` -\> `s:4:"\0*\0a";` (URL 编码后为 `%00*%00a`)
          * `private $a` -\> `s:10:"\0ClassName\0a";` (URL 编码后为 `%00ClassName%00a`)
      * **利用:** 在 PHP 7.1+ 版本中，可以利用此特性绕过一些过滤。例如，一个过滤了 `\0*\0` (protected) 的 WAF，可以通过构造一个 `public` 属性的序列化字符串，让服务器在反序列化时将其解析为同名的 `protected` 属性，从而实现绕过。

  * **字符逃逸**

      * **原理:** 当程序对序列化后的字符串进行了不恰当的替换，导致字符串长度变化，但没有更新对应的长度值时，就会产生逃逸。
      * **字符增多:** 如 `filter('ab')` -\> `filter('cde')`。多出来的字符可以用来构造恶意的序列化内容，"挤出"并覆盖原有的部分。
      * **字符减少:** 如 `filter('abcde')` -\> `filter('f')`。减少的字符长度会导致反序列化时“吞掉”后续的正常字符，同样可以用来构造恶意对象。

  * **绕过 `throw new Exception` (强制 `__destruct` 执行)**

      * **原理:** 当 `unserialize()` 之后有 `throw new Exception()` 时，如果反序列化的对象被赋给了一个变量 (如 `$a = unserialize(...)`)，脚本会立即异常退出，导致变量 `$a` 无法被 GC (垃圾回收器) 正常销毁，`__destruct()` 也不会执行。
      * **绕过思路 (利用引用):** 构造一个特殊的序列化数组，在反序列化的**过程中**就强制触发 GC。
      * **示例:** `a:2:{i:0;O:4:"test":1:{...}i:0;N;}`
      * **解析:**
        1.  `unserialize()` 开始解析数组。
        2.  `i:0;O:4:"test":1:{...}`：将索引 `0` 设置为一个 `test` 对象。
        3.  `i:0;N;`：**再次**将索引 `0` 设置为 `NULL`。
        4.  此时，前面创建的 `test` 对象丢失了所有引用，GC 会**立即**将其回收并执行 `__destruct()`。
        5.  `unserialize()` 继续执行完毕，然后才遇到 `throw new Exception()`，但此时 `__destruct()` 早已执行完毕。

  * **绕过 WAF (Payload 格式)**

      * **利用 `+` 号:**
          * **原理:** PHP 的 `unserialize()` 函数在解析序列化格式时，会把 `+8` 正确地识别为整数 `8`。这同样适用于属性个数、字符串长度等。
          * **利用:** 不严谨的 WAF 规则可能会使用 `O:\d+:` 这样的正则表达式来匹配对象。`+` 号不属于 `\d` (数字)，因此 `O:+8:` 这样的 payload 就能成功绕过 WAF 对结构格式的检测。
      * **利用 `C:` (ArrayObject 绕过正则)**
          * **原理:** 某些 WAF 会使用 `preg_match("/^[Oa]:[\d]+/i", $data)` 这类正则，只允许以 `O:` (Object) 或 `a:` (Array) 开头的字符串。PHP 中一些实现了 `unserialize` 接口的内置类（如 `ArrayObject`, `ArrayIterator`, `SplObjectStorage`）在序列化时以 `C:` 开头，可以绕过此正则。
          * **利用:** 构造一个 `ArrayObject` 来包装恶意的对象 payload，使其序列化后以 `C:` 开头。
            ```php
            // 目标类
            class ctfshow {
                public $ctfshow;
                public function __wakeup(){ die("not allowed!"); }
                public function __destruct(){ system($this->ctfshow); }
            }

            $a = new ctfshow;
            $a->ctfshow = "whoami";

            // 使用 ArrayObject 包装
            $arr = array("evil" => $a);
            $oa = new ArrayObject($arr);
            $res = serialize($oa);

            // Payload (以 C: 开头，绕过 /^[Oa]:.../):
            // C:11:"ArrayObject":77:{x:i:0;a:1:{s:4:"evil";O:7:"ctfshow":1:{s:7:"ctfshow";s:6:"whoami";}};m:a:0:{}}
            ```
          * **注意:** 此方法（`ArrayObject` 输出为 `C:`）在较新 PHP 版本（如 7.4+）中可能已改变，默认输出为 `O:`，但 `ArrayIterator` 等其他类仍可能有效，具体取决于 PHP 版本。

### 4\. 原生类利用 (无 POP 链时)

当目标代码中没有可用的 POP 链时，可以利用 PHP 自带的内部类（原生类）中存在的魔术方法来发起攻击。

  * **`Error` / `Exception` → XSS (`__toString`)**: 这两个类的 `__toString` 方法会输出错误信息，可用于触发 XSS。
  * **`SoapClient` → SSRF / 任意请求 (`__call`)**:
      * **SSRF:** 当调用一个 `SoapClient` 对象不存在的方法时，会触发 `__call`，进而向其 `location` 选项指定的 URL 发送一个 SOAP (XML) 请求，造成 SSRF。
      * **CRLF 注入:** 攻击者还可以利用 CRLF 注入（即在 `User-Agent` 等参数中插入 `\r\n`），来构造**任意的 HTTP 请求**（如走私 POST 请求），对内网服务（如 Redis、MySQL）进行攻击。
  * **`SimpleXMLElement` → XXE (`__construct`)**: 实例化 `SimpleXMLElement` 时，如果传入一个 URL 并开启特定选项，它会尝试解析外部 XML 文件，造成 XXE。

-----

## 十九、 漏洞分析：LDAP 注入

  * **定义:** 类似于 SQL 注入，当用户输入被拼接到 LDAP (轻量目录访问协议) 的搜索过滤器 (Search Filter) 中时，攻击者可以篡改过滤器的逻辑。
  * **场景:** 常见于使用 LDAP 作为统一认证 (SSO) 的企业内部系统（如登录、用户信息查询）。
  * **敏感函数:** `ldap_search` (第 3 个参数 `filter`)。
  * **示例代码:**
    ```php
    // $ds 是已连接和绑定的 LDAP 资源
    $filter = "(|(cn=" . $_GET["user"] . ")(mail=" . $_GET["user"] . "))";
    $sr = ldap_search($ds, $dn, $filter); // $filter 可控，存在注入
    ```
  * **攻击:**
      * **查询所有用户:** `?user=*)`
          * **注入后:** `(|(cn=*)(mail=*))`
      * **绕过认证 (盲注):** `?user=*)(uid=admin)(password=*`
          * **注入后:** `(|(cn=*)(uid=admin)(password=*)(mail=*)(uid=admin)(password=*))`
          * 如果 `admin` 用户的密码第一位是 `a`，过滤器为真，返回结果。
  * **防御:**
      * 严格过滤或转义 LDAP 过滤器的特殊字符：`*`, `\`, `(`, `)`, `\x00`。
      * **示例过滤函数:**
        ```php
        function ldapspecialchars($string) {
            $sanitized = [
                '\\' => '\5c',
                '*'  => '\2a',
                '('  => '\28',
                ')'  => '\29',
                "\x00" => '\00'
            ];
            return str_replace(array_keys($sanitized), array_values($sanitized), $string);
        }
        ```

-----

## 二十、 漏洞分析：变量覆盖

  * **定义:** 由于 PHP 的某些特性或函数使用不当，导致用户可以通过输入（如 `$_GET`）来**覆盖**或**定义**程序中已有的变量，从而篡改程序逻辑。
  * **核心风险:** 覆盖关键变量，如 `$auth = false;` 或 `$is_admin = 0;`。

### 1\. 常见来源

  * `register_globals=On` (已移除)
      * **说明:** PHP 4.2.0 之前默认开启，PHP 5.4.0 起被**彻底移除**。
      * **作用:** 自动将 `$_GET`, `$_POST` 等数组的键值注册为全局变量。
      * **示例:** 访问 `test.php?auth=1` 会自动创建 `$auth = 1;`。
  * `extract()`
      * **函数:** `extract($_GET);`
      * **作用:** 将数组（如 `$_GET`）的键值导入到当前符号表（即注册为变量）。
      * **风险:** 默认使用 `EXTR_OVERWRITE` 模式，会覆盖已有变量。
      * **防御:** **绝对不要**对 `$_REQUEST`, `$_GET`, `$_POST` 使用 `extract()`。
  * `parse_str()`
      * **函数:** `parse_str($_SERVER["QUERY_STRING"]);`
      * **作用:** 将 URL 查询字符串解析到当前符号表（注册为变量）。
      * **风险:** 同 `extract()`。
      * **防御:** 始终使用第二个参数，将变量解析到数组中：`parse_str($_SERVER["QUERY_STRING"], $output_array);`
  * `import_request_variables()`
      * **函数:** `import_request_variables("G");` (导入 GET 变量)
      * **说明:** 已在 **PHP 5.4.0 中被移除**。
  * `$$` (可变变量) 与键值对循环
      * **原理:** 遍历 `$_GET`, `$_POST` 等数组，并将其键名作为变量名，键值作为变量值，动态地在当前作用域（或全局作用域）创建变量。
      * **示例 1: `foreach` + `$$` (可变变量)**
        ```php
        foreach ($_GET as $key => $value) {
            $$key = $value; // 风险点
        }
        ```
      * **利用:** 访问 `test.php?auth=1`，`$key = 'auth'`, `$value = 1`，执行 `$$key = $value` 即 `$'auth' = 1`，最终创建了 `$auth = 1`。
      * **示例 2: `foreach` + `$GLOBALS`**
          * **说明:** 通过 `$GLOBALS['key']` 声明或访问的变量是**全局变量**，无论在哪个作用域中使用。如果 `$key` 来自用户输入，攻击者就可以覆盖任意全局变量。
        ```php
        foreach ($_POST as $key => $value) {
            $GLOBALS[$key] = $value; // 风险点
        }
        ```
      * **示例 3: `foreach` + `$_SESSION`**
          * **说明:** `$_SESSION` 是一个包含会话变量的关联数组，用于在不同页面间持久化用户数据（如登录状态）。它也容易遭受变量覆盖攻击。
          * 在使用 `$_SESSION` 前需要 `session_start()` 函数开启会话。
        ```php
        // 示例：将用户提交的数据存入 Session
        session_start();
        foreach ($_POST as $key => $value) {
            $_SESSION[$key] = $value; // 风险点
        }
        ```
          * **风险:** 如果程序后续依赖 `$_SESSION['is_admin']` 来判断权限，攻击者可以通过 POST 提交 `is_admin=1` 来覆盖该会话变量，提升权限。
          * **关联风险:** 根据 PHP 版本的 Session 处理器配置不同，`$_SESSION` 还可能存在Session 反序列化漏洞。

-----

## 二十一、 其他漏洞（业务逻辑与配置缺陷）

### 1. 越权

* **定义:** 用户的操作超出了其应有的权限范围。
* **水平越权:**
    * **描述:** 访问/操作与当前用户**同等级**的其他用户的数据。
    * **示例:** 普通用户 A (ID=101) 访问 `.../order/view?id=888` 查看自己的订单。如果 A 将 ID 修改为 `889`（用户 B 的订单），并且系统**仅校验了用户 A 是否登录**，而**未校验订单 889 是否属于用户 A**，则产生了水平越权。
* **垂直越权:**
    * **描述:** 低权限用户访问/操作了**高权限**（如管理员）才能执行的功能。
    * **示例:** 日志审计员（低权限）访问后台，URL 为 `.../admin/log_view`。管理员（高权限）的管理页面为 `.../admin/user_manage`。如果日志审计员**直接访问 `.../admin/user_manage`** 并且系统成功响应（**仅校验了是否为后台用户，未校验角色权限**），则产生了垂直越权。
* **审计:** 检查所有涉及“修改”、“删除”、“查看”等操作的后端接口，确认其**是否同时校验了“用户已登录”（认证）和“资源是否属于该用户”/“用户是否有权操作”（授权）**。

### 2. 未授权访问 / 鉴权绕过

* **定义:** 访问/操作本应需要登录或特定权限的功能时，未进行权限检查，或权限检查不严谨导致可被绕过。
* **示例:**
    * 后台管理页面 `.../admin/dashboard.php` 未包含任何 `session` 或 `cookie` 校验，导致任何人都可以直接访问。
    * 某些后台功能为了方便（如 API 回调），设置了弱校验后门，如 `if ($_GET['token'] === 'admin123') { ... }`，或检查 User-Agent、IP 地址等易被伪造的特征。

### 3. 频率限制

* **定义:** 在关键业务点缺乏请求频率限制，导致攻击者可以利用自动化脚本进行爆破或资源滥用。
* **常见场景:**
    * **用户登录/注册:** 无限制会导致账号密码爆破、撞库。
    * **短信/邮箱验证码:** 无限制会导致“短信炸弹”攻击（大量消耗服务费用、骚扰用户）。
    * **密码找回:** 无限制可爆破验证码或重置凭证。
    * **发帖/评论/订单:** 无限制会导致垃圾信息填充、恶意刷单。
* **防御:**
    * 使用 IP / 用户名 / 手机号作为标识，在后端（如 Redis, Memcached）中记录单位时间内的请求次数。
    * 超出阈值时，要求输入验证码或临时锁定账号/IP。

### 4. 拒绝服务

* **定义:** 通过特殊输入，大量消耗服务器资源（CPU、内存、磁盘 I/O），导致服务缓慢或崩溃。
* **示例:**
    * **资源生成:** 生成验证码、二维码、报表、图片处理（如缩放、裁剪）的功能，如果未限制用户输入的**宽高、大小或数量**，攻击者可传入超大参数（如 99999x99999 像素）耗尽内存。
    * **ReDoS:** 正则表达式（Regex）编写不当，导致“灾难性回溯”，CPU 占用 100%。

### 5. URL 跳转

* **定义:** 程序接收用户传入的 URL 并进行跳转，但未对 URL 进行校验。
* **示例:** `.../login.php?redirect_url=http://evil.com`
* **危害:**
    * **钓鱼:** 攻击者利用可信域名（`target.com`）作为跳板，诱使用户点击，最终跳转到恶意钓鱼网站。
    * **SSRF:** 如果后端是使用 `curl` 等方式获取 `redirect_url` 的内容再跳转，可能演变为 SSRF。
    * **Head 注入 (XSS):** 如果跳转是使用 `Header("Location: ...")` 实现的，并且未过滤 `\r\n` (%0d%0a)，攻击者可能注入恶意响应头，甚至 XSS。
* **防御:**
    * 对跳转的 URL 建立**白名单**，只允许跳转到白名单内的域名。
    * 如果必须跳转任意地址，应弹出“您即将离开本站，请注意安全”的提示页面。

### 6. 验证码问题

#### A. 区分机器的验证码 (如图形、滑块)

* **无验证码/前端验证:** 后台登录、注册等关键操作无验证码，或验证码仅在前端 JS 校验（可被抓包绕过）。
* **验证码可重复使用:** 验证码在校验成功后，未在后端失效，导致可被重复使用（如用于爆破）。
* **验证码无有效期:** 验证码永不失效。
* **验证码过于简单:** 易被 OCR 识别（如背景无干扰、字符不扭曲）。

#### B. 临时凭据的验证码 (如短信/邮件/TOTP)

* **易被爆破:** 4 位或 6 位数字验证码，如果**无尝试次数/频率限制**，可被轻易爆破。
* **验证码泄露:** 验证码在特定条件下被返回给用户（如 API 响应中、错误信息中）。
* **验证链接易猜测:** 密码重置链接使用弱随机数（如 `time()` + `userid`）生成，易被猜测/遍历。
* **凭据无有效期:** 验证码或验证链接永不失效。

-----

## 二十二、 PHP 语言特性与安全

PHP 作为一种弱类型语言，在变量比较和类型转换时有许多特殊的行为。理解这些特性是发现和绕过安全过滤的关键。

### 1\. 弱类型比较 (`==`) 陷阱

#### 数字与字符串比较

  * **规则:** 当一个字符串与一个数字使用 `==` 比较时，PHP 会尝试将**字符串转换为数字**，然后进行数字比较。
  * **转换规则:** 从字符串开头开始转换，直到遇到非数字字符为止。如果开头不是数字，则转换为 `0`。
  * **示例:**
      * `0 == '0'` (true)
      * `0 == 'abcdefg'` (true, 'abcdefg' 转换为 0)
      * `1 == '1abcdef'` (true, '1abcdef' 转换为 1)
  * **安全绕过 (intval):** `intval('3abcd')` 会返回 `3`。如果过滤不严，`$a = "1002 union..."`，`intval($a)` 会返回 `1002`，可能绕过 `> 1000` 这样的检查，并将 `1002 union...` 带入 SQL 语句。
  * **严格比较 (`===`):** 严格比较会同时比较类型和值，`0 === 'abcdefg'` (false)。

#### 魔法 Hash

  * **规则:** 当字符串以 `0e` 开头，且后面全是数字时，PHP 会将其解析为**科学计数法**，其值为 `0`。
  * **示例:**
      * `"0e132456789" == "0e7124511451155"` (true, 两边都被解析为 0)
      * `"0e123456abc" == "0e1dddada"` (false, 因为包含非数字字符，按字符串比较)
      * `"0e1abc" == 0` (true, '0e1abc' 转换为 0)
  * **利用:** 在比较 Hash 值时（如 `if (md5($pass) == $db_hash)`），如果攻击者能找到一个 `md5` 值以 `0e` 开头的密码，并且数据库中的 Hash 也是 `0e` 开头的，就能绕过密码验证。

#### 十六进制字符串

  * **规则:** 当一个字符串以 `0x` 开头时，PHP 在 `==` 比较中会将其视为**十六进制数**。
  * **示例:**
      * `"0x1e240" == "123456"` (true, 0x1e240 转换为 123456)
      * `"0x1e240" == 123456` (true)
      * `"0x1e240" == "1e240"` (false, 字符串比较)

### 2\. `is_numeric()` 绕过

  * **函数:** `is_numeric()` 用于检测变量是否是数字或数字字符串。
  * **陷阱:** 该函数允许十六进制 (`0x...`)，并且会**自动跳过开头的空白字符**（如空格, `%20`, `%0a` 换行, `%0d` 回车, `%09` Tab 等）。
  * **示例:**
    ```php
    $a = $_GET['a'];
    if (is_numeric($a)) {
        // 允许 ' 123', '0x1A', '1e5'
        if ($a == 404) {
            echo "flag";
        }
    }
    ```
  * **绕过:** 传入 `?a=%20404` 或 `?a=0x194` (404的十六进制)。`is_numeric()` 为 true，且 `==` 比较也为 true。

### 3\. `in_array()` / `array_search()` 类型松散

  * **函数:** `in_array(needle, haystack, [strict])`
  * **陷阱:** 默认情况下，`strict` (第三个参数) 为 `false`，`in_array` 会使用**弱类型 `==`** 进行比较。
  * **示例:**
    ```php
    $array = [0, 1, 2, '3'];
    // 攻击者传入 'abc' 或 '1bc'
    var_dump(in_array('abc', $array)); // true, 'abc' == 0
    var_dump(in_array('1bc', $array)); // true, '1bc' == 1
    ```
  * **防御:** 始终设置 `strict` 为 `true`：`in_array($needle, $array, true)`。

### 4\. `strcmp()` 数组绕过

  * **函数:** `strcmp(string1, string2)` 用于比较两个**字符串**。
  * **陷阱:** 如果给 `strcmp` 传入一个**数组**，它会返回 `NULL` (或 PHP 8.0+ 的 `0` 并抛出 Warning)。在 PHP 8 之前，`NULL` 在弱类型比较中（如 `if ($ret == 0)`）会等同于 `0`。
  * **利用:** 常用于绕过 Hash 比较。
    ```php
    if (strcmp($_POST['password_hash'], $db_hash) == 0) {
        echo "Login success!";
    }
    ```
  * **绕过:** 提交 `password_hash[]=1`，`$_POST['password_hash']` 会变为一个数组。`strcmp` 返回 `NULL`，`NULL == 0` 为 `true`，成功绕过。

### 5\. `md5()` 数组绕过

  * **函数:** `md5(string)` 期望一个**字符串**参数。
  * **陷阱:** 如果给 `md5()` 传入一个**数组**，它不会报错，而是会返回 `NULL`。
  * **利用:** `if (md5($_POST['a']) == md5($_POST['b'])) { ... }`
  * **绕过:** 提交 `a[]=1&b[]=2`。`md5(array) == md5(array)` 变为 `NULL == NULL`，结果为 `true`。

### 6\. `switch()` 类型转换

  * **规则:** `switch` 语句在处理 `case` 时，会（非严格地）将其中的参数转换为**整数**进行比较。
  * **示例:**
    ```php
    $i = "2abc";
    switch ($i) {
        case 0:
        case 1:
        case 2:
            echo "i is less than 3 but not negative";
            break;
        case 3:
            echo "i is 3";
    }
    ```
  * **结果:** 输出 "i is less than 3 but not negative"。因为 `"2abc"` 被转换为了整数 `2`。

### 7\. `strpos()` 的 `0` 和 `false`

  * **函数:** `strpos(haystack, needle)` 返回 `needle` 在 `haystack` 中首次出现的**位置 (索引)**。
  * **陷阱:**
      * 如果 `needle` 在**开头** (索引 0)，函数返回 `0`。
      * 如果 `needle` **未找到**，函数返回 `false`。
  * **错误用法:**
    ```php
    // 寻找 'admin'，如果找不到则...
    if (strpos($username, 'admin') == false) { // 错误！
        echo "OK";
    }
    ```
  * **绕过:** 传入 `username=adminxxx`。`strpos` 返回 `0`，`0 == false` 为 `true`，检查被绕过。
  * **正确用法:** **必须**使用严格比较 `===`。
    ```php
    if (strpos($username, 'admin') === false) { // 正确
        echo "OK (not found)";
    }
    ```

### 8\. `mail()` / `mb_send_mail()` 命令注入

  * **函数:** `mail(to, subject, message, [additional_headers], [additional_parameters])`
  * **陷阱:** 第五个参数 `additional_parameters` 会被传递给 `sendmail` 程序作为命令行参数。
  * **利用:** 攻击者可通过该参数注入 `sendmail` 的选项，如 `-X` (指定日志文件) 或 `-f` (指定发件人)。
  * **示例:** `mail("a@b.c", "sub", "msg", "", "-X /var/www/shell.php -f'<?php ... ?>@evil.com'");`
      * `-X /var/www/shell.php`: 将邮件日志写入 Web 目录下的 `shell.php`。
      * `-f'...'`: `sendmail` 会将发件人地址（包含 PHP代码）写入该日志文件。
  * **防御:** 过滤 `additional_parameters`，或使用 `escapeshellarg`。

### 9\. `escapeshellarg()` + `escapeshellcmd()` 绕过

  * **陷阱:** **同时**使用这两个函数会造成安全问题。
  * **流程:**
    1.  输入: `127.0.0.1' -v -d a=1`
    2.  `escapeshellarg()` 处理: (转义 `'` 并用 `'` 包裹)
        `'127.0.0.1'\'' -v -d a=1'`
    3.  `escapeshellcmd()` 处理: (转义 `\` 和末尾的 `'`)
        `'127.0.0.1'\\'' -v -d a=1\'`
  * **结果:** 最终执行时，`\\` 被解释为转义的 `\`，`''` 变为空字符串。命令被解析为：
    `'127.0.0.1\'` (一个参数) `  -v ` `  -d ` `  a=1\' ` (另一个参数)
    这改变了原有命令的参数结构，可导致注入。

### 10\. `filter_var()` 绕过

  * **函数:** `filter_var($var, FILTER_VALIDATE_EMAIL)` 或 `FILTER_VALIDATE_URL`。
  * **陷阱 (`FILTER_VALIDATE_EMAIL`):** 允许在双引号中嵌套转义的空格和特殊字符。
  * **陷阱 (`FILTER_VALIDATE_URL`):**
      * `javascript://comment%0aalert(1);` 这种 `javascript:` 伪协议在某些情况下可绕过 URL 校验，导致 XSS。
      * `http://user:pass@evil.com` 这种带凭据的 URL 可能被用于绕过主机名检查。

### 11\. `class_exists()` 自动加载

  * **函数:** `class_exists(classname, [autoload])`
  * **陷阱:** 默认情况下 `autoload` 为 `true`。如果程序定义了 `__autoload` 或 `spl_autoload_register`，`class_exists()` 在检查不存在的类时，会**触发自动加载**。
  * **利用:** 如果自动加载函数 (如 `__autoload`) 中存在文件包含 `include $_GET['c'] . '.php'`，攻击者可通过 `class_exists($_GET['c'])` 来触发 LFI 或 RFI。

### 12\. FFI (PHP 7.4+)

从安全角度看，FFI 等同于在 PHP 脚本中获得了一个**原生的、可绕过 `disable_functions` 的RCE向量**。

#### (1) FFI 有什么用？

  * **绕过 `disable_functions`:** `disable_functions` 配置项是 PHP 用来禁用 `system`, `exec`, `shell_exec` 等危险函数的最后一道防线。但是，FFI 相关的类（`FFI`）和方法（`FFI::cdef`, `FFI::load`）通常**不会**在默认的 `disable_functions` 列表中。
  * **直接调用 `system`:** FFI 可以加载 `libc` (Linux) 或 `msvcrt.dll` (Windows) 动态库，并直接调用其中的 `system` C 函数，从而无视 `disable_functions` 的限制。

#### (2) 审计点与利用示例

审计时，需要全局搜索 `FFI::` 关键字，尤其是 `FFI::load` 和 `FFI::cdef`。

**利用 1：`FFI::cdef()`**

如果攻击者能以任何方式控制 `FFI::cdef` 或 `FFI::load` 的参数，就等同于 RCE。

```php
<?php
// 检查 FFI 是否可用
if (!class_exists('FFI')) {
    die("FFI is not available.\n");
}

// 1. 定义 C 函数的原型
// 攻击者不需要加载外部 .so，libc 几乎总是在 PHP 进程的内存中
$ffi = FFI::cdef("int system(const char *command);");

// 2. 调用 C 版本的 system 函数
// 这里的 'system' 是 C 函数，不是 PHP 函数，不受 disable_functions 限制
$ffi->system("id > /tmp/pwned_by_ffi");
?>
```

**审计点：** 查找 `FFI::cdef()`，检查其参数是否可控。

**利用 2：`FFI::load()` (加载任意动态库)**

如果攻击者可以上传一个自定义的 `.so` (Linux) 或 `.dll` (Windows) 文件，并控制 `FFI::load()` 的参数，他们可以加载这个恶意库并执行其中的任意代码。

```php
<?php
// 攻击者上传了 pwn.so，并可以控制 $lib_path 变量
$lib_path = '/var/www/uploads/pwn.so';

// 1. 加载恶意库
$ffi = FFI::load($lib_path);

// 2. 定义原型 (假设 pwn.so 中有一个叫 'run_payload' 的函数)
$ffi->cdef("void run_payload(void);");

// 3. 执行
$ffi->run_payload();
?>
```

**审计点：** 查找 `FFI::load()`，检查其参数是否来自可控变量（如文件上传路径）。

#### (3) 防御

  * **`php.ini` (首选):**
      * `ffi.enable = Off`: 完全禁用 FFI。
      * `ffi.enable = preload`: 默认值。仅允许在 `opcache.preload` 脚本中（即服务器启动时由 `root` 加载的脚本）使用 FFI。这相对安全，但如果预加载脚本可被写入，将是致命的。
  * **`disable_classes`:**
      * 在 `php.ini` 中添加 `FFI` 到 `disable_classes` 列表，可以禁用 FFI 类。

### 13\. 预加载 (PHP 7.4+)

预加载（`opcache.preload`）是一个性能特性，它允许 PHP 在 FPM 服务器启动时（通常以 `root` 权限）加载一次 PHP 脚本，并使其永久驻留在内存中，供所有请求共享。

#### 审计点与安全风险

  * **RCE 的持久化:** 如果攻击者获得了对服务器的写入权限，他们可以将恶意代码写入到 `php.ini` 中 `opcache.preload` 指令所指向的那个 PHP 脚本中。
  * **后果:**
    1.  恶意代码将**以 `root` 权限执行**（在 FPM 启动时）。
    2.  恶意代码将**永久驻留在内存中**，无法通过“删除 WebShell 文件”来清除。
    3.  这是 FFI 的一个主要攻击入口，因为 `ffi.enable = preload` 是默认配置，攻击者可将 FFI payload 写入预加载脚本中。

**审计点：** 检查 `php.ini` 文件，找到 `opcache.preload` 指令。然后，**审计**它所指向的那个 PHP 文件，并检查该文件的**写入权限**。