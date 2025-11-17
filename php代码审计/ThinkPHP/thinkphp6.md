# ThinkPHP 6.* 

## 1\. 目录结构

相对于5.1来说，6.0版本目录结构的主要变化是核心框架纳入vendor目录，然后原来的application目录变成app目录。

6.0支持多应用模式部署，所以实际的目录结构取决于你采用的是单应用还是多应用模式，分别说明如下。

### 1.1. 单应用模式

默认安装后的目录结构就是一个单应用模式

```text
www  WEB部署目录（或者子目录）
├─app           应用目录
│  ├─controller      控制器目录
│  ├─model           模型目录
│  ├─ ...            更多类库目录
│  │
│  ├─common.php         公共函数文件
│  └─event.php          事件定义文件
│
├─config                配置目录
│  ├─app.php            应用配置
│  ├─cache.php          缓存配置
│  ├─console.php        控制台配置
│  ├─cookie.php         Cookie配置
│  ├─database.php       数据库配置
│  ├─filesystem.php     文件磁盘配置
│  ├─lang.php           多语言配置
│  ├─log.php            日志配置
│  ├─middleware.php     中间件配置
│  ├─route.php          URL和路由配置
│  ├─session.php        Session配置
│  ├─trace.php          Trace配置
│  └─view.php           视图配置
│
├─view            视图目录
├─route                 路由定义目录
│  ├─route.php          路由定义文件
│  └─ ...   
│
├─public                WEB目录（对外访问目录）
│  ├─index.php          入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于apache的重写
│
├─extend                扩展类库目录
├─runtime               应用的运行时目录（可写，可定制）
├─vendor                Composer类库目录
├─.example.env          环境变量示例文件
├─composer.json         composer 定义文件
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
├─think                 命令行入口文件
```

### 1.2. 多应用模式（扩展）

如果你需要一个多应用的项目架构，目录结构可以参考下面的结构进行调整（关于配置文件的详细结构参考后面章节），但首先需要安装ThinkPHP的多应用扩展，具体可以参考多应用模式。

```text
www  WEB部署目录（或者子目录）
├─app           应用目录
│  ├─app_name           应用目录
│  │  ├─common.php      函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  ├─config          配置目录
│  │  ├─route           路由目录
│  │  └─ ...            更多类库目录
│  │
│  ├─common.php         公共函数文件
│  └─event.php          事件定义文件
│
├─config                全局配置目录
│  ├─app.php            应用配置
│  ├─cache.php          缓存配置
│  ├─console.php        控制台配置
│  ├─cookie.php         Cookie配置
│  ├─database.php       数据库配置
│  ├─filesystem.php     文件磁盘配置
│  ├─lang.php           多语言配置
│  ├─log.php            日志配置
│  ├─middleware.php     中间件配置
│  ├─route.php          URL和路由配置
│  ├─session.php        Session配置
│  ├─trace.php          Trace配置
│  └─view.php           视图配置
│
├─public                WEB目录（对外访问目录）
│  ├─index.php          入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于apache的重写
│
├─extend                扩展类库目录
├─runtime               应用的运行时目录（可写，可定制）
├─vendor                Composer类库目录
├─.example.env          环境变量示例文件
├─composer.json         composer 定义文件
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
├─think                 命令行入口文件
```

> **注意：**
>
>   * 多应用模式部署后，记得删除`app`目录下的`controller`目录（系统根据该目录作为判断是否单应用的依据）。
>   * 在实际的部署中，请确保只有`public`目录可以对外访问。
>   * 在mac或者linux环境下面，注意需要设置`runtime`目录权限为777。

### 1.3. 默认应用文件

默认安装后，`app`目录下会包含下面的文件。

```text
├─app           应用目录
│  │
│  ├─BaseController.php    默认基础控制器类
│  ├─ExceptionHandle.php   应用异常定义文件
│  ├─common.php            全局公共函数文件
│  ├─middleware.php        全局中间件定义文件
│  ├─provider.php          服务提供定义文件
│  ├─Request.php           应用请求对象
│  └─event.php             全局事件定义文件
```

`BaseController.php`、`Request.php` 和`ExceptionHandle.php`三个文件是系统默认提供的基础文件，位置你可以随意移动，但注意要同步调整类的命名空间。如果你不需要使用`Request.php` 和`ExceptionHandle.php`文件，或者要调整类名，记得必须同步调整`provider.php`文件中的容器对象绑定。

## 2\. 配置

### 2.1. 配置目录

#### 2.1.1. 单应用模式

对于单应用模式来说，配置文件和目录很简单，根目录下的`config`目录下面就是所有的配置文件。每个配置文件对应不同的组件，当然你也可以增加自定义的配置文件。

```text
├─config（配置目录）
│  ├─app.php            应用配置
│  ├─cache.php          缓存配置
│  ├─console.php        控制台配置
│  ├─cookie.php         Cookie配置
│  ├─database.php       数据库配置
│  ├─filesystem.php     文件磁盘配置
│  ├─lang.php           多语言配置
│  ├─log.php            日志配置
│  ├─middleware.php     中间件配置
│  ├─route.php          URL和路由配置
│  ├─session.php        Session配置
│  ├─trace.php          Trace配置
│  ├─view.php           视图配置
│  └─ ...               更多配置文件
```

单应用模式的`config`目录下的所有配置文件系统都会自动读取，不需要手动加载。如果存在子目录，你可以通过Config类的`load`方法手动加载，例如：

```php
// 加载config/extra/config.php 配置文件 读取到extra
\think\facade\Config::load('extra/config', 'extra');
```

#### 2.1.2. 多应用模式

在多应用模式下，配置分为全局配置和应用配置。

  * **全局配置：** `config`目录下面的文件就是项目的全局配置文件，对所有应用有效。
  * **应用配置：** 每个应用可以有独立配置文件，相同的配置参数会覆盖全局配置。

<!-- end list -->

```text
├─app（应用目录）
│  ├─app1 （应用1）
│  │   └─config（应用配置）
│  │   	 ├─app.php            应用配置
│  │  	 ├─cache.php          缓存配置
│  │   	 ├─cookie.php         Cookie配置
│  │   	 ├─database.php       数据库配置
│  │  	 ├─lang.php           多语言配置
│  │  	 ├─log.php            日志配置
│  │     ├─route.php          URL和路由配置
│  │   	 ├─session.php        Session配置
│  │ 	 ├─view.php           视图及模板引擎配置
│  │   	 ├─trace.php          Trace配置
│  │ 	 └─ ...               更多配置文件
│  │ 
│  └─ app2... （更多应用）
│
├─config（全局配置）
│  ├─app.php            应用配置
│  ├─cache.php          缓存配置
│  ├─console.php        控制台配置
│  ├─cookie.php         Cookie配置
│  ├─database.php       数据库配置
│  ├─filesystem.php     文件磁盘配置
│  ├─lang.php           多语言配置
│  ├─log.php            日志配置
│  ├─middleware.php     中间件配置
│  ├─route.php          URL和路由配置
│  ├─session.php        Session配置
│  ├─trace.php          Trace配置
│  ├─view.php           视图配置
│  └─ ...               更多配置文件
```

### 2.2. 配置定义

可以直接在相应的全局或应用配置文件中修改或者增加配置参数，如果你要增加额外的配置文件，直接放入配置目录即可（文件名小写）。

除了一级配置外，配置参数名严格区分大小写，建议是使用小写定义配置参数的规范。

由于架构设计原因，下面的配置只能在环境变量中修改。

| 配置参数 | 描述 |
| :--- | :--- |
| `app_debug` | 应用调试模式 |
| `config_ext` | 配置文件后缀 |

### 2.3. 环境变量定义

可以在应用的根目录下定义一个特殊的`.env`环境变量文件，用于在开发过程中模拟环境变量配置（该文件建议在服务器部署的时候忽略）。`.env`文件中的配置参数定义格式采用ini方式，例如：

```ini
APP_DEBUG =  true
```

默认安装后的根目录有一个`.example.env`环境变量示例文件，你可以直接改成`.env`文件后进行修改。

> 如果你的部署环境单独配置了环境变量（ 环境变量的前缀使用`PHP_`），那么请删除`.env`配置文件，避免冲突。

环境变量配置的参数会全部转换为大写，值为 `off`，`no` 和 `false` 等效于 布尔值`false`，值为 `yes` 、`on`和 `true` 等效于 布尔值的`true`。

注意，环境变量不支持数组参数，如果需要使用数组参数可以，可以使用

```ini
[DATABASE]
USERNAME =  root
PASSWORD =  123456
```

如果要设置一个没有键值的数组参数，可以使用

```ini
PATHINFO_PATH[] =  ORIG_PATH_INFO
PATHINFO_PATH[] =  REDIRECT_PATH_INFO
PATHINFO_PATH[] =  REDIRECT_URL
```

获取环境变量的值可以使用下面的方式获取：

```php
use think\facade\Env;

Env::get('database.username');
Env::get('database.password');
Env::get('PATHINFO_PATH');
```

环境变量的获取不区分大小写。
可以支持默认值，例如：

```php
// 获取环境变量 如果不存在则使用默认值root
Env::get('database.username', 'root');
```

可以直接在配置文件中使用环境变量进行本地环境和服务器的自动配置，例如：

```php
return [
    'hostname'  =>  Env::get('hostname','127.0.0.1'),
];
```

#### 2.3.1. 多环境变量配置支持

V6.0.8+版本开始，可以支持定义多个环境变量配置文件，配置文件命名规范为

  * `.env.example`
  * `.env.testing`
  * `.env.develop`

然后，需要在入口文件中指定部署使用的环境变量名称：

```php
// 执行HTTP应用并响应
$http = (new App())->setEnvName('develop')->http;
$response = $http->run();
$response->send();
$http->end($response);
```

或者你可以继承`App`类 然后重载`loadEnv`方法实现 动态切换环境变量配置。

### 2.4. 其它配置格式支持

默认的配置文件都是PHP数组方式，如果你需要使用其它格式的配置文件，你可以通过改变`CONFIG_EXT`环境变量的方式来更改配置类型。

在应用根目录的`.env`或者系统环境变量中设置

```ini
CONFIG_EXT=".ini"
```

支持的配置类型包括`.ini`、`.xml`、`.json` 、`.yaml`和 `.php` 在内的格式支持，配置后全局或应用配置必须统一使用相同的配置类型。

### 2.5. 配置获取

要使用Config类，首先需要在你的类文件中引入

```php
use think\facade\Config;
```

然后就可以使用下面的方法读取某个配置参数的值：

读取一级配置的所有参数（每个配置文件都是独立的一级配置）

```php
Config::get('app');
Config::get('route');
```

读取单个配置参数

```php
Config::get('app.app_name');
Config::get('route.url_domain_root');
```

读取数组配置（理论上支持无限级配置参数读取）

```php
Config::get('database.default.host');
```

判断是否存在某个设置参数：

```php
Config::has('template');
Config::has('route.route_rule_merge');
```

### 2.6. 参数批量设置

Config类不再支持动态设置某个配置参数，但可以支持批量设置更新配置参数。

```php
// 批量设置参数
Config::set(['name1' => 'value1', 'name2' => 'value2'], 'config');
// 获取配置
Config::get('config');
```

### 2.7. 系统配置文件

下面系统自带的配置文件列表及其作用：

| 配置文件名 | 描述 |
| :--- | :--- |
| `app.php` | 应用配置 |
| `cache.php` | 缓存配置 |
| `console.php` | 控制台配置 |
| `cookie.php` | Cookie配置 |
| `database.php` | 数据库配置 |
| `filesystem.php` | 磁盘配置 |
| `lang.php` | 多语言配置 |
| `log.php` | 日志配置 |
| `middleware.php` | 中间件配置 |
| `route.php` | 路由和URL配置 |
| `session.php` | Session配置 |
| `trace.php` | 页面Trace配置 |
| `view.php` | 视图配置 |

具体的配置参数及默认值可以直接查看应用`config`目录下面的相关文件内容。

## 3\. 路由机制

要使用`Route`类注册路由必须首先在路由定义文件开头添加引用（后面不再重复说明）

```php
use think\facade\Route;
```

### 3.1. 注册路由

最基础的路由定义方法是：
[cite\_start]`Route::rule('路由表达式', '路由地址', '请求类型');` [cite: 1]

例如注册如下路由规则（假设为单应用模式）：

```php
// 注册路由到News控制器的read操作
Route::rule('new/:id','News/read');
```

我们访问：
`http://serverName/new/5`
会自动路由到：
`http://serverName/news/read/id/5`
[cite\_start]并且原来的访问地址会自动失效。 [cite: 1]

可以在`rule`方法中指定请求类型（不指定的话默认为任何请求类型有效），例如：

```php
Route::rule('new/:id', 'News/update', 'POST');
```

请求类型参数不区分大小写。
[cite\_start]表示定义的路由规则在POST请求下才有效。 [cite: 1]
如果要定义GET和POST请求支持的路由规则，可以用：

```php
Route::rule('new/:id','News/read','GET|POST');
```

不过通常我们更推荐使用对应请求类型的快捷方法，包括：

| 类型 | 描述 | 快捷方法 |
| :--- | :--- | :--- |
| GET | GET请求 | `get` |
| POST | POST请求 | `post` |
| PUT | PUT请求 | `put` |
| DELETE | DELETE请求 | `delete` |
| PATCH | PATCH请求 | `patch` |
| HEAD | HEAD请求 | `head` （V6.0.13+） |
| \* | 任何请求类型 | `any` |

快捷注册方法的用法为：
[cite\_start]`Route::快捷方法名('路由表达式', '路由地址');` [cite: 1]

使用示例如下：

```php
Route::get('new/<id>','News/read'); // 定义GET请求路由规则
Route::post('new/<id>','News/update'); // 定义POST请求路由规则
Route::put('new/:id','News/update'); // 定义PUT请求路由规则
Route::delete('new/:id','News/delete'); // 定义DELETE请求路由规则
Route::any('new/:id','News/read'); // 所有请求都支持的路由规则
```

[cite\_start]注册多个路由规则后，系统会依次遍历注册过的满足请求类型的路由规则，一旦匹配到正确的路由规则后则开始执行最终的调度方法，后续规则就不再检测。 [cite: 1]

### 3.2. 规则表达式

规则表达式通常包含静态规则和动态规则，以及两种规则的结合，例如下面都属于有效的规则表达式：

```php
Route::rule('/', 'index'); // 首页访问路由
Route::rule('my', 'Member/myinfo'); // 静态地址路由
Route::rule('blog/:id', 'Blog/read'); // 静态地址和动态地址结合
Route::rule('new/:year/:month/:day', 'News/read'); // 静态地址和动态地址结合
Route::rule(':user/:blog_id', 'Blog/read'); // 全动态地址
```

[cite\_start]规则表达式的定义以`/`为参数分割符（无论你的PATH\_INFO分隔符设置是什么，请确保在定义路由规则表达式的时候统一使用`/`进行URL参数分割，除非是使用组合变量的情况）。 [cite: 1]

[cite\_start]每个参数中可以包括动态变量，例如`:变量`或者`<变量>`都表示动态变量（新版推荐使用第二种方式，更利于混合变量定义），并且会自动绑定到操作方法的对应参数。 [cite: 1]

[cite\_start]你的URL访问PATH\_INFO分隔符使用`pathinfo_depr`配置，但无论如何配置，都不影响路由的规则表达式的路由分隔符定义。 [cite: 1]

### 3.3. 可选变量

支持对路由参数的可选定义，例如：

```php
Route::get('blog/:year/[:month]','Blog/archive');
// 或者
Route::get('blog/<year>/<month?>','Blog/archive');
```

[cite\_start]变量用`[ ]`包含起来后就表示该变量是路由匹配的可选变量。 [cite: 1]
以上定义路由规则后，下面的URL访问地址都可以被正确的路由匹配：

`http://serverName/index.php/blog/2015`
`http://serverName/index.php/blog/2015/12`

[cite\_start]采用可选变量定义后，之前需要定义两个或者多个路由规则才能处理的情况可以合并为一个路由规则。 [cite: 1]
[cite\_start]可选参数只能放到路由规则的最后，如果在中间使用了可选参数的话，后面的变量都会变成可选参数。 [cite: 1]

### 3.4. 完全匹配

[cite\_start]规则匹配检测的时候默认只是对URL从头开始匹配，只要URL地址开头包含了定义的路由规则就会匹配成功，如果希望URL进行完全匹配，可以在路由表达式最后使用`$`符号，例如： [cite: 1]

```php
Route::get('new/:cate$', 'News/category');
```

这样定义后
`http://serverName/index.php/new/info`
会匹配成功,而
`http://serverName/index.php/new/info/2`
[cite\_start]则不会匹配成功。 [cite: 1]

如果是采用
`Route::get('new/:cate', 'News/category');`
[cite\_start]方式定义的话，则两种方式的URL访问都可以匹配成功。 [cite: 1]

如果需要全局进行URL完全匹配，可以在路由配置文件中设置

```php
// 开启路由完全匹配
'route_complete_match'   => true,
```

[cite\_start]开启全局完全匹配后，如果需要对某个路由关闭完全匹配，可以使用 [cite: 1]

```php
Route::get('new/:cate', 'News/category')->completeMatch(false);
```

### 3.5. 额外参数

[cite\_start]在路由跳转的时候支持额外传入参数对（额外参数指的是不在URL里面的参数，隐式传入需要的操作中，有时候能够起到一定的安全防护作用，后面我们会提到）。例如： [cite: 1]

```php
Route::get('blog/:id','blog/read')
    ->append(['status' => 1, 'app_id' =>5]);
```

[cite\_start]上面的路由规则定义中`status`和`app_id`参数都是URL里面不存在的，属于隐式传值。可以针对不同的路由设置不同的额外参数。 [cite: 1]
[cite\_start]如果`append`方法中的变量和路由规则存在冲突的话，`append`方法传入的优先。 [cite: 1]

### 3.6. 路由标识

如果你需要快速的根据路由生成URL地址，可以在定义路由的时候指定生成标识（但要确保唯一）。
[cite\_start]例如 [cite: 1]

```php
// 注册路由到News控制器的read操作
Route::rule('new/:id','News/read')
    ->name('new_read');
```

生成路由地址的时候就可以使用
`url('new_read', ['id' => 10]);`

如果不定义路由标识的话，系统会默认使用路由地址作为路由标识，例如可以使用下面的方式生成
[cite\_start]`url('News/read', ['id' => 10]);` [cite: 1]

### 3.7. 强制路由

在路由配置文件中设置
`'url_route_must'		=>  true,`
[cite\_start]将开启强制使用路由，这种方式下面必须严格给每一个访问地址定义路由规则（包括首页），否则将抛出异常。 [cite: 1]

[cite\_start]首页的路由规则采用`/`定义即可，例如下面把网站首页路由输出Hello,world\! [cite: 1]

```php
Route::get('/', function () {
    return 'Hello,world!';
});
```

### 3.8. 路由地址

[cite\_start]路由地址表示定义的路由表达式最终需要路由到的实际地址（或者响应对象）以及一些需要的额外参数，支持下面几种方式定义： [cite: 1]

**路由到控制器/操作**

[cite\_start]这是最常用的一种路由方式，把满足条件的路由规则路由到相关的控制器和操作，然后由系统调度执行相关的操作，格式为： [cite: 1]
`控制器/操作`

解析规则是从操作开始解析，然后解析控制器，例如：

```php
// 路由到blog控制器
Route::get('blog/:id','Blog/read');
```

Blog类定义如下：

```php
<?php
namespace app\index\controller;
class Blog
{
    public function read($id)
    {
        return 'read:' . $id;
    }
}
```

[cite\_start]路由地址中支持多级控制器，使用下面的方式进行设置： [cite: 1]
`Route::get('blog/:id','group.Blog/read');`
表示路由到下面的控制器类：
`index/controller/group/Blog`

[cite\_start]还可以支持路由到动态的应用、控制器或者操作，例如： [cite: 1]
`// action变量的值作为操作方法传入`
`Route::get(':action/blog/:id', 'Blog/:action');`

**路由到类的方法**

这种方式的路由可以支持执行任何类的方法，而不局限于执行控制器的操作方法。
[cite\_start]路由地址的格式为（动态方法）： [cite: 1]
`\完整类名@方法名`
或者（静态方法）
`\完整类名::方法名`

例如：
`Route::get('blog/:id','\app\index\service\Blog@read');`
[cite\_start]执行的是 `\app\index\service\Blog`类的`read`方法。 [cite: 1]

[cite\_start]也支持执行某个静态方法，例如： [cite: 1]
`Route::get('blog/:id','\app\index\service\Blog::read');`

**重定向路由**

[cite\_start]可以直接使用`redirect`方法注册一个重定向路由 [cite: 1]
`Route::redirect('blog/:id', 'http://blog.thinkphp.cn/read/:id', 302);`

**路由到模板**

支持路由直接渲染模板输出。
`// 路由到模板文件`
`Route::view('hello/:name', 'index/hello');`
[cite\_start]表示该路由会渲染当前应用下面的`view/index/hello.html`模板文件输出。 [cite: 1]

[cite\_start]模板文件中可以直接输出当前请求的param变量，如果需要增加额外的模板变量，可以使用： [cite: 1]
`Route::view('hello/:name', 'index/hello', ['city'=>'shanghai']);`
在模板中可以输出`name`和`city`两个变量。
`Hello,{$name}--{$city}！`

**路由到闭包**

[cite\_start]我们可以使用闭包的方式定义一些特殊需求的路由，而不需要执行控制器的操作方法了，例如： [cite: 1]

```php
Route::get('hello', function () {
    return 'hello,world!';
});
```

[cite\_start]可以通过闭包的方式支持路由自定义响应输出，例如： [cite: 1]

```php
Route::get('hello/:name', function () {
    response()->data('Hello,ThinkPHP')
    ->code(200)
    ->contentType('text/plain');
});
```

**参数传递**
[cite\_start]闭包定义的时候支持参数传递，例如： [cite: 1]

```php
Route::get('hello/:name', function ($name) {
    return 'Hello,' . $name;
});
```

规则路由中定义的动态变量的名称 就是闭包函数中的参数名称，不分次序。
因此，如果我们访问的URL地址是：
`http://serverName/hello/thinkphp`
则浏览器输出的结果是：
[cite\_start]`Hello,thinkphp` [cite: 1]

### 3.9. 跨域请求

[cite\_start]如果某个路由或者分组需要支持跨域请求，可以使用 [cite: 1]

```php
Route::get('new/:id', 'News/read')
    ->ext('html')
    ->allowCrossDomain();
```

跨域请求一般会发送一条OPTIONS的请求，一旦设置了跨域请求的话，不需要自己定义OPTIONS请求的路由，系统会自动加上。

跨域请求系统会默认带上一些Header，包括：

```
Access-Control-Allow-Origin:*
Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE
Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With
```

[cite\_start]你可以添加或者更改Header信息，使用 [cite: 1]

```php
Route::get('new/:id', 'News/read')
    ->ext('html')
    ->allowCrossDomain([
        'Access-Control-Allow-Origin'        => 'thinkphp.cn',
        'Access-Control-Allow-Credentials'   => 'true'
    ]);
```

[cite\_start]V6.0.3+版本开始增加了默认的预检缓存有效期（默认为30分钟），你可以自定义有效期，例如： [cite: 1]

```php
Route::get('new/:id', 'News/read')
    ->ext('html')
    ->allowCrossDomain([
        'Access-Control-Allow-Origin'        => 'thinkphp.cn',
        'Access-Control-Allow-Credentials'   => 'true',
        'Access-Control-Max-Age'             => 600,
    ]);
```

## 4\. 中间件机制

中间件主要用于拦截或过滤应用的HTTP请求，并进行必要的业务处理。
新版部分核心功能使用中间件处理，你可以灵活关闭。包括Session功能、请求缓存和多语言功能。

### 4.1. 定义中间件

可以通过命令行指令快速生成中间件
`php think make:middleware Check`

这个指令会 `app/middleware`目录下面生成一个`Check`中间件。

```php
<?php
namespace app\middleware;
class Check
{
    public function handle($request, \Closure $next)
    {
        if ($request->param('name') == 'think') {
            return redirect('index/think');
        }
        return $next($request);
    }
}
```

  * 中间件的入口执行方法必须是`handle`方法，而且第一个参数是Request对象，第二个参数是一个闭包。
  * 中间件`handle`方法的返回值必须是一个Response对象。
  * 在这个中间件中我们判断当前请求的`name`参数等于`think`的时候进行重定向处理。否则，请求将进一步传递到应用中。要让请求继续传递到应用程序中，只需使用 `$request` 作为参数去调用回调函数 `$next` 。

在某些需求下，可以使用第三个参数传入额外的参数。

```php
<?php
namespace app\middleware;
class Check
{
    public function handle($request, \Closure $next, $name)
    {
        if ($name == 'think') {
            return redirect('index/think');
        }
        return $next($request);
    }
}
```

### 4.2. 结束调度

中间件支持定义请求结束前的回调机制，你只需要在中间件类中添加`end`方法。

```php
    public function end(\think\Response $response)
    {
        // 回调行为
    }
```

> **注意：** 在`end`方法里面不能有任何的响应输出。因为回调触发的时候请求响应输出已经完成了。

### 4.3. 前置/后置中间件

中间件是在请求具体的操作之前还是之后执行，完全取决于中间件的定义本身。

下面是一个**前置行为**的中间件

```php
<?php
namespace app\middleware;
class Before
{
    public function handle($request, \Closure $next)
    {
        // 添加中间件执行代码
        return $next($request);
    }
}
```

下面是一个**后置行为**的中间件

```php
<?php
namespace app\middleware;
class After
{
    public function handle($request, \Closure $next)
    {
		$response = $next($request);
        // 添加中间件执行代码
        return $response;
    }
}
```

中间件方法同样也可以支持依赖注入。

来个比较实际的例子，我们需要判断当前浏览器环境是在微信或支付宝：

```php
namespace app\middleware;
/**
 * 访问环境检查，是否是微信或支付宝等
 */
class InAppCheck
{
    public function handle($request, \Closure $next)
    {
        if (preg_match('~micromessenger~i', $request->header('user-agent'))) {
            $request->InApp = 'WeChat';
        } else if (preg_match('~alipay~i', $request->header('user-agent'))) {
            $request->InApp = 'Alipay';
        }
        return $next($request);
    }
}
```

然后在你的移动版的应用里添加一个`middleware.php`文件
例如：`/path/app/mobile/middleware.php`

```php
return [
    app\middleware\InAppCheck::class,
];
```

然后在你的controller中可以通过`request()->InApp`获取相关的值。

### 4.4. 定义中间件别名

可以直接在应用配置目录下的`middleware.php`中先预定义中间件（其实就是增加别名标识），例如：

```php
return [
    'alias' => [
        'auth'  => app\middleware\Auth::class,
        'check' => app\middleware\Check::class,
    ],
];
```

可以支持使用别名定义一组中间件，例如：

```php
return [
    'alias' => [
        'check' => [
            app\middleware\Auth::class,
            app\middleware\Check::class,
        ],
    ],
];
```

### 4.5. 注册中间件

新版的中间件分为全局中间件、应用中间件（多应用模式下有效）、路由中间件以及控制器中间件四个组。执行顺序分别为：
`全局中间件` -\> `应用中间件` -\> `路由中间件` -\> `控制器中间件`

**全局中间件**

全局中间件在`app`目录下面`middleware.php`文件中定义，使用下面的方式：

```php
<?php
return [
	\app\middleware\Auth::class,
    'check',
    'Hello',
];
```

  * 中间件的注册应该使用完整的类名，如果已经定义了中间件别名（或者分组）则可以直接使用。
  * 全局中间件的执行顺序就是定义顺序。
  * 可以在定义全局中间件的时候传入中间件参数，支持两种方式传入。

<!-- end list -->

```php
<?php
return [
	[\app\http\middleware\Auth::class, 'admin'],
    'Check',
    ['hello','thinkphp'],
];
```

上面的定义表示 给Auth中间件传入`admin`参数，给Hello中间件传入`thinkphp`参数。

**应用中间件**

如果你使用了多应用模式，则支持应用中间件定义，你可以直接在应用目录下面增加`middleware.php`文件，定义方式和全局中间件定义一样，只是只会在该应用下面生效。

**路由中间件**

最常用的中间件注册方式是注册路由中间件

```php
Route::rule('hello/:name','hello')
	->middleware(\app\middleware\Auth::class);
```

支持注册多个中间件

```php
Route::rule('hello/:name','hello')
	->middleware([\app\middleware\Auth::class, \app\middleware\Check::class]);
```

然后，直接使用下面的方式注册中间件

```php
Route::rule('hello/:name','hello')
	->middleware('check');
```

支持对路由分组注册中间件

```php
Route::group('hello', function(){
	Route::rule('hello/:name','hello');
})->middleware('auth');
```

支持对某个域名注册中间件

```php
Route::domain('admin', function(){
	// 注册域名下的路由规则
})->middleware('auth');
```

如果需要传入额外参数给中间件，可以使用

```php
Route::rule('hello/:name','hello')
	->middleware('auth', 'admin');
```

如果需要定义多个中间件，使用数组方式

```php
Route::rule('hello/:name','hello')
	->middleware([Auth::class, 'Check']);
```

可以统一传入同一个额外参数

```php
Route::rule('hello/:name','hello')
	->middleware(['auth', 'check'], 'admin');
```

或者分开多次调用，指定不同的参数

```php
Route::rule('hello/:name','hello')
	->middleware('auth', 'admin')
    ->middleware('hello', 'thinkphp');
```

如果你希望某个路由中间件是全局执行（不管路由是否匹配），可以不需要在路由里面定义，支持直接在路由配置文件中定义，例如在`config/route.php`配置文件中添加：

```php
'middleware'    =>    [
    app\middleware\Auth::class,
    app\middleware\Check::class,
],
```

这样，所有该应用下的请求都会执行`Auth`和`Check`中间件。

**使用闭包定义中间件**

你不一定要使用中间件类，在某些简单的场合你可以使用闭包定义中间件，但闭包函数必须返回Response对象实例。

```php
Route::group('hello', function(){
	Route::rule('hello/:name','hello');
})->middleware(function($request,\Closure $next){
    if ($request->param('name') == 'think') {
        return redirect('index/think');
    }
	return $next($request);
});
```

**控制器中间件**

支持为控制器定义中间件，只需要在控制器中定义`middleware`属性，例如：

```php
<?php
namespace app\controller;
class Index
{
    protected $middleware = ['auth'];
    
    public function index()
    {
        return 'index';
    }
    
    public function hello()
    {
        return 'hello';
    }
}
```

当执行`index`控制器的时候就会调用`auth`中间件，一样支持使用完整的命名空间定义。
如果需要设置控制器中间的生效操作，可以如下定义：

```php
<?php
namespace app\controller;
class Index
{
    protected $middleware = [ 
    	'auth' 	=> ['except' 	=> ['hello'] ],
        'check' => ['only' 		=> ['hello'] ],
    ];
    
    public function index()
    {
        return 'index';
    }
    
    public function hello()
    {
        return 'hello';
    }
}
```

### 4.6. 中间件向控制器传参

可以通过给请求对象赋值的方式传参给控制器（或者其它地方），例如

```php
<?php
namespace app\middleware;
class Hello
{
    public function handle($request, \Closure $next)
    {
        $request->hello = 'ThinkPHP';
        
        return $next($request);
    }
}
```

然后在控制器的方法里面可以直接使用

```php
public function index(Request $request)
{
	return $request->hello; // ThinkPHP
}
```

### 4.7. 执行优先级

如果对中间件的执行顺序有严格的要求，可以定义中间件的执行优先级。在配置文件中添加

```php
return [
    'alias'    => [
        'check' => [
            app\middleware\Auth::class,
            app\middleware\Check::class,
        ],
    ],
    'priority' => [
        think\middleware\SessionInit::class,
        app\middleware\Auth::class,
        app\middleware\Check::class,
    ],
];
```

### 4.8. 内置中间件

新版内置了几个系统中间件，包括：

| 中间件类 | 描述 |
| :--- | :--- |
| `think\middleware\AllowCrossDomain` | 跨域请求支持 |
| `think\middleware\CheckRequestCache` | 请求缓存 |
| `think\middleware\LoadLangPack` | 多语言加载 |
| `think\middleware\SessionInit` | Session初始化 |
| `think\middleware\FormTokenCheck` | 表单令牌 |

这些内置中间件默认都没有定义，你可以在应用的`middleware.php`文件中、路由或者控制器中定义这些中间件，如果不需要使用的话，取消定义即可。

## 5\. 控制器

### 5.1. 控制器定义

控制器文件通常放在`controller`下面，类名和文件名保持大小 crescente 一致，并采用驼峰命名（首字母大写）。

如果要改变`controller`目录名，需要在`route.php`配置文件中设置：
`'controller_layer'    =>    'controllers',`

**单应用模式**

如果使用的是单应用模式，那么控制器的类的定义如下：

```php
<?php
namespace app\controller;
class User 
{
    public function login()
    {
        return 'login';
    }
}
```

控制器类文件的实际位置则变成
`app\controller\User.php`

访问URL地址是（假设没有定义路由的情况下）
`http://localhost/user/login`

如果你的控制器是`HelloWorld`，并且定义如下：

```php
<?php
namespace app\controller;
class HelloWorld 
{
    public function hello()
    {
        return 'hello，world！';
    }
}
```

控制器类文件的实际位置是
`app\controller\HelloWorld.php`

访问URL地址是（假设没有定义路由的情况下）
`http://localhost/index.php/HelloWorld/hello`
并且也可以支持下面的访问URL
`http://localhost/hello_world/hello`

**多应用模式**

多应用模式下，控制器类定义仅仅是命名空间有所区别，例如：

```php
<?php
namespace app\shop\controller;
class User
{
    public function login()
    {
        return 'login';
    }
}
```

控制器类文件的实际位置是
`app\shop\controller\User.php`

访问URL地址是（假设没有定义路由的情况下）
`http://localhost/index.php/shop/user/login`

**控制器后缀**

如果你希望避免引入同名模型类的时候冲突，可以在`route.php`配置文件中设置

```php
// 使用控制器后缀
'controller_suffix'     => true,
```

这样，上面的控制器类就需要改成

```php
<?php
namespace app\controller;
class UserController
{
    public function login()
    {
        return 'login';
    }
}
```

相应的控制器类文件也要改为
`app\controller\UserController.php`

### 5.2. 渲染输出

默认情况下，控制器的输出全部采用`return`的方式，无需进行任何的手动输出，系统会自动完成渲染内容的输出。
下面都是有效的输出方式：

```php
<?php
namespace app\index\controller;
class Index 
{
    public function hello()
    {
    	// 输出hello,world!
        return 'hello,world!';
    }
    public function json()
    {
    	// 输出JSON
        return json($data);
    }
    public function read()
    {
    	// 渲染默认模板输出
        return view();
    }
}
```

控制器一般不需要任何输出，直接`return`即可。并且控制器在json请求会自动转换为json格式输出。

> **注意：** 不要在控制器中使用包括`die`、`exit`在内的中断代码。如果你需要调试并中止执行，可以使用系统提供的`halt`助手函数。
> `halt('输出测试');`

### 5.3. 多级控制器

支持任意层次级别的控制器，并且支持路由，例如：

```php
<?php
namespace app\index\controller\user;
class  Blog 
{
    public function index()
    {
        return 'index';
    }
    
}
```

该控制器类的文件位置为：
`app/index/controller/user/Blog.php`

访问地址可以使用
`http://serverName/index.php/user.blog/index`

由于URL访问不能访问默认的多级控制器（可能会把多级控制器名误识别为URL后缀），因此建议所有的多级控制器都通过路由定义后访问，如果要在路由定义中使用多级控制器，可以使用：

`Route::get('user/blog','user.blog/index');`

## 6\. 请求

当前的请求对象由`think\Request`类负责，该类不需要单独实例化调用，通常使用依赖注入即可。在其它场合则可以使用`think\facade\Request`静态类操作。

项目里面应该使用`app\Request`对象，该对象继承了系统的`think\Request`对象，但可以增加自定义方法或者覆盖已有方法。项目里面已经在`provider.php`中进行了定义，所以你仍然可以和之前一样直接使用容器和静态代理操作请求对象。

### 6.1. 构造方法注入

一般适用于没有继承系统的控制器类的情况。

```php
<?php
namespace app\index\controller;
use think\Request;
class Index 
{
    /**
     * @var \think\Request Request实例
     */
    protected $request;
    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct(Request $request)
    {
		$this->request = $request;
    }
    public function index()
    {
		return $this->request->param('name');
    }    
}
```

### 6.2. 操作方法注入

另外一种选择是在每个方法中使用依赖注入。

```php
<?php
namespace app\index\controller;
use think\Request;
class Index
{
    public function index(Request $request)
    {
		return $request->param('name');
    }    
}
```

无论是否继承系统的控制器基类，都可以使用操作方法注入。

### 6.3. 静态调用

在没有使用依赖注入的场合，可以通过Facade机制来静态调用请求对象的方法（注意use引入的类库区别）。

```php
<?php
namespace app\index\controller;
use think\facade\Request;
class Index
{
    public function index()
    {
		return Request::param('name');
    }    
}
```

该方法也同样适用于依赖注入无法使用的场合。

### 6.4. 助手函数

为了简化调用，系统还提供了`request`助手函数，可以在任何需要的时候直接调用当前请求对象。

```php
<?php
namespace app\index\controller;
class Index
{
    public function index()
    {
        return request()->param('name');
    }
}
```

### 6.5. 请求信息

Request对象支持获取当前的请求信息，包括：

| 方法 | 含义 |
| :--- | :--- |
| `host` | 当前访问域名或者IP |
| `scheme` | 当前访问协议 |
| `port` | 当前访问的端口 |
| `remotePort` | 当前请求的REMOTE\_PORT |
| `protocol` | 当前请求的SERVER\_PROTOCOL |
| `contentType` | 当前请求的CONTENT\_TYPE |
| `domain` | 当前包含协议的域名 |
| `subDomain` | 当前访问的子域名 |
| `panDomain` | 当前访问的泛域名 |
| `rootDomain` | 当前访问的根域名 |
| `url` | 当前完整URL |
| `baseUrl` | 当前URL（不含QUERY\_STRING） |
| `query` | 当前请求的QUERY\_STRING参数 |
| `baseFile` | 当前执行的文件 |
| `root` | URL访问根地址 |
| `rootUrl` | URL访问根目录 |
| `pathinfo` | 当前请求URL的pathinfo信息（含URL后缀） |
| `ext` | 当前URL的访问后缀 |
| `time` | 获取当前请求的时间 |
| `type` | 当前请求的资源类型 |
| `method` | 当前请求类型 |
| `rule` | 当前请求的路由对象实例 |

对于上面的这些请求方法，一般调用无需任何参数，但某些方法可以传入`true`参数，表示获取带域名的完整地址，例如：

```php
use think\facade\Request;

// 获取完整URL地址 不带域名
Request::url();
// 获取完整URL地址 包含域名
Request::url(true);
// 获取当前URL（不含QUERY_STRING） 不带域名
Request::baseFile();
// 获取当前URL（不含QUERY_STRING） 包含域名
Request::baseFile(true);
// 获取URL访问根地址 不带域名
Request::root();
// 获取URL访问根地址 包含域名
Request::root(true);
```

> **注意：** `domain`方法的值本身就包含协议和域名

### 6.6. 获取当前控制器/操作

可以通过请求对象获取当前请求的控制器/操作名。

| 方法 | 含义 |
| :--- | :--- |
| `controller` | 当前请求的控制器名 |
| `action` | 当前请求的操作名 |

**获取当前控制器**
`Request::controller();`
返回的是控制器的驼峰形式（首字母大写），和控制器类名保持一致（不含后缀）。

如果需要返回小写可以使用
`Request::controller(true);`

如果要返回小写+下划线的方式，可以使用
`parse_name(Request::controller());`

**获取当前操作**
`Request::action();`
返回的是当前操作方法的实际名称，如果需要返回小写可以使用
`Request::action(true);`

如果要返回小写+下划线的方式，可以使用
`parse_name(Request::action());`

如果使用了多应用模式，可以通过下面的方法来获取当前应用
`app('http')->getName();`

### 6.7. 输入变量

可以通过Request对象完成全局输入变量的检测、获取和安全过滤，支持包括`$_GET`、`$_POST`、`$_REQUEST`、`$_SERVER`、`$_SESSION`、`$_COOKIE`、`$_ENV`等系统变量，以及文件上传信息。

```php
use think\facade\Request;
```

如果你使用的是依赖注入，请自行调整代码为动态调用即可。

主要内容包括：

#### 6.7.1. 检测变量是否设置

可以使用`has`方法来检测一个变量参数是否设置，如下：

```php
Request::has('id','get');
Request::has('name','post');
```

变量检测可以支持所有支持的系统变量，包括get/post/put/request/cookie/server/session/env/file。

#### 6.7.2. 变量获取

变量获取使用`\think\Request`类的如下方法及参数：

`变量类型方法('变量名/变量修饰符','默认值','过滤方法')`

变量类型方法包括：

| 方法 | 描述 |
| :--- | :--- |
| `param` | 获取当前请求的变量 |
| `get` | 获取 `$_GET` 变量 |
| `post` | 获取 `$_POST` 变量 |
| `put` | 获取 PUT 变量 |
| `delete` | 获取 DELETE 变量 |
| `session` | 获取 SESSION 变量 |
| `cookie` | 获取 `$_COOKIE` 变量 |
| `request` | 获取 `$_REQUEST` 变量 |
| `server` | 获取 `$_SERVER` 变量 |
| `env` | 获取 `$_ENV` 变量 |
| `route` | 获取 路由（包括PATHINFO） 变量 |
| `middleware` | 获取 中间件赋值/传递的变量 |
| `file` | 获取 `$_FILES` 变量 |
| `all` V6.0.8+ | 获取包括 `$_FILES` 变量在内的请求变量，相当于param+file |

**获取PARAM变量**

PARAM类型变量是框架提供的用于自动识别当前请求的一种变量获取方式，是系统推荐的获取请求参数的方法，用法如下：

```php
// 获取当前请求的name变量
Request::param('name');
// 获取当前请求的所有变量（经过过滤）
Request::param();
// 获取当前请求未经过滤的所有变量
Request::param(false);
// 获取部分变量
Request::param(['name', 'email']);
```

`param`方法会把当前请求类型的参数和路由变量以及GET请求合并，并且路由变量是优先的。
其它的输入变量获取方法和`param`方法用法基本一致。

> **注意：** 你无法使用`get`方法获取路由变量，例如当访问地址是
> `http://localhost/index.php/index/index/hello/name/thinkphp`
> 下面的用法是错误的
> `echo Request::get('name'); // 输出为空`
> 正确的用法是
> `echo Request::param('name'); // 输出thinkphp`

> 除了`server`和`env`方法的变量名不区分大小写（会自动转为大写后获取），其它变量名区分大小写。

**默认值**

获取输入变量的时候，可以支持默认值，例如当URL中不包含`$_GET['name']`的时候，使用下面的方式输出的结果比较。

```php
Request::get('name'); // 返回值为null
Request::get('name',''); // 返回值为空字符串
Request::get('name','default'); // 返回值为default
```

前面提到的方法都支持在第二个参数中传入默认值的方式。

#### 6.7.3. 变量过滤

框架默认没有设置任何全局过滤规则，你可以在`app\Request`对象中设置`filter`全局过滤属性：

```php
namespace app;
class Request extends \think\Request
{
    protected $filter = ['htmlspecialchars'];
}
```

也支持使用Request对象进行全局变量的获取过滤，过滤方式包括函数、方法过滤，以及PHP内置的Types of filters，我们可以设置全局变量过滤方法，支持设置多个过滤方法，例如：

`Request::filter(['strip_tags','htmlspecialchars']),`

也可以在获取变量的时候添加过滤方法，例如：

```php
Request::get('name','','htmlspecialchars'); // 获取get变量 并用htmlspecialchars函数过滤
Request::param('username','','strip_tags'); // 获取param变量 并用strip_tags函数过滤
Request::post('name','','org\Filter::safeHtml'); // 获取post变量 并用org\Filter类的safeHtml方法过滤
```

可以支持传入多个过滤规则，例如：

`Request::param('username','','strip_tags,strtolower'); // 获取param变量 并依次调用strip_tags、strtolower函数过滤`

如果当前不需要进行任何过滤的话，可以使用

```php
// 获取get变量 并且不进行任何过滤 即使设置了全局过滤
Request::get('name', '', null);
```

> **注意：** 对于body中提交的json对象，你无需使用`php://input`去获取，可以直接当做表单提交的数据使用，因为系统已经自动处理过了

#### 6.7.4. 获取部分变量

如果你只需要获取当前请求的部分参数，可以使用：

```php
// 只获取当前请求的id和name变量
Request::only(['id','name']);
```

采用`only`方法能够安全的获取你需要的变量，避免额外变量影响数据处理和写入。
`only`方法可以支持批量设置默认值，如下：

```php
// 设置默认值
Request::only(['id'=>0,'name'=>'']);
```

表示id的默认值为0，name的默认值为空字符串。
默认获取的是当前请求参数（PARAM类型变量），如果需要获取其它类型的参数，可以在第二个参数传入，例如：

```php
// 只获取GET请求的id和name变量
Request::only(['id','name'], 'get');
// 等效于
Request::get(['id', 'name']);

// 只获取POST请求的id和name变量
Request::only(['id','name'], 'post');
// 等效于
Request::post(['id', 'name']);
```

也支持排除某些变量后获取，例如

```php
// 排除id和name变量
Request::except(['id','name']);
```

同样支持指定变量类型获取：

```php
// 排除GET请求的id和name变量
Request::except(['id','name'], 'get');
// 排除POST请求的id和name变量
Request::except(['id','name'], 'post');
```

#### 6.7.5. 变量修饰符

支持对变量使用修饰符功能，可以一定程度上简单过滤变量，更为严格的过滤请使用前面提过的变量过滤功能。
用法如下：
`Request::变量类型('变量名/修饰符');`

支持的变量修饰符，包括：

| 修饰符 | 作用 |
| :--- | :--- |
| `s` | 强制转换为字符串类型 |
| `d` | 强制转换为整型类型 |
| `b` | 强制转换为布尔类型 |
| `a` | 强制转换为数组类型 |
| `f` | 强制转换为浮点类型 |

下面是一些例子：

```php
Request::get('id/d');
Request::post('name/s');
Request::post('ids/a');
```

#### 6.7.6. 中间件变量

可以在中间件里面设置和获取请求变量的值，这个值的改变不会影响PARAM变量的获取。

```php
<?php
namespace app\http\middleware;
class Check
{
    public function handle($request, \Closure $next)
    {
        if ('think' == $request->name) {
        	$request->name = 'ThinkPHP';
        }
        return $next($request);
    }
}
```

### 6.8. 助手函数

为了简化使用，还可以使用系统提供的`input`助手函数完成上述大部分功能。

**判断变量是否定义**

```php
input('?get.id');
input('?post.name');
```

**获取PARAM参数**

```php
input('param.name'); // 获取单个参数
input('param.'); // 获取全部参数
// 下面是等效的
input('name'); 
input('');
```

**获取GET参数**

```php
// 获取单个变量
input('get.id');
// 使用过滤方法获取 默认为空字符串
input('get.name');
// 获取全部变量
input('get.');
```

**使用过滤方法**

```php
input('get.name','','htmlspecialchars'); // 获取get变量 并用htmlspecialchars函数过滤
input('username','','strip_tags'); // 获取param变量 并用strip_tags函数过滤
input('post.name','','org\Filter::safeHtml'); // 获取post变量 并用org\Filter类的safeHtml方法过滤
```

**使用变量修饰符**

```php
input('get.id/d');
input('post.name/s');
input('post.ids/a');
```

### 6.9. 获取请求类型

在很多情况下面，我们需要判断当前操作的请求类型是GET、POST、PUT、DELETE或者HEAD，一方面可以针对请求类型作出不同的逻辑处理，另外一方面有些情况下面需要验证安全性，过滤不安全的请求。

请求对象`Request`类提供了下列方法来获取或判断当前请求类型：

| 用途 | 方法 |
| :--- | :--- |
| 获取当前请求类型 | `method` |
| 判断是否GET请求 | `isGet` |
| 判断是否POST请求 | `isPost` |
| 判断是否PUT请求 | `isPut` |
| 判断是否DELETE请求 | `isDelete` |
| 判断是否AJAX请求 | `isAjax` |
| 判断是否PJAX请求 | `isPjax` |
| 判断是否JSON请求 | `isJson` |
| 判断是否手机访问 | `isMobile` |
| 判断是否HEAD请求 | `isHead` |
| 判断是否PATCH请求 | `isPatch` |
| 判断是否OPTIONS请求 | `isOptions` |
| 判断是否为CLI执行 | `isCli` |
| 判断是否为CGI模式 | `isCgi` |

`method`方法返回的请求类型始终是大写，这些方法都不需要传入任何参数。

> **提示：** 没有必要在控制器中判断请求类型再来执行不同的逻辑，完全可以在路由中进行设置。

### 6.10. 请求类型伪装

支持请求类型伪装，可以在POST表单里面提交`_method`变量，传入需要伪装的请求类型，例如：

```html
<form method="post" action="">
    <input type="text" name="name" value="Hello">
    <input type="hidden" name="_method" value="PUT" >
    <input type="submit" value="提交">
</form>
```

提交后的请求类型会被系统识别为PUT请求。
你可以设置为任何合法的请求类型，包括GET、POST、PUT和DELETE等，但伪装变量`_method`只能通过POST请求进行提交。

如果要获取原始的请求类型，可以使用
`Request::method(true);`

在命令行下面执行的话，请求类型返回的始终是GET。
如果你需要改变伪装请求的变量名，可以修改自定义Request类的`varMethod`属性。

### 6.11. AJAX/PJAX伪装

可以对请求进行AJAX请求伪装，如下：
`http://localhost/index?_ajax=1`
或者PJAX请求伪装
`http://localhost/index?_pjax=1`

如果你需要改变伪装请求的变量名，可以修改自定义Request类的`varAjax`和`varPjax`属性。
`_ajax`和`_pjax`可以通过GET/POST/PUT等请求变量伪装。

### 6.12. HTTP 头信息

可以使用Request对象的`header`方法获取当前请求的HTTP请求头信息，例如：

```php
$info = Request::header();
echo $info['accept'];
echo $info['accept-encoding'];
echo $info['user-agent'];
```

也可以直接获取某个请求头信息，例如：
`$agent = Request::header('user-agent');`

HTTP请求头信息的名称不区分大小写，并且`_`会自动转换为`-`，所以下面的写法都是等效的：

```php
$agent = Request::header('user-agent');
$agent = Request::header('USER_AGENT');
```

### 6.13. 参数绑定

参数绑定是把当前请求的变量作为操作方法（也包括架构方法）的参数直接传入，参数绑定并不区分请求类型。

参数绑定传入的值会经过全局过滤，如果你有额外的过滤需求可以在操作方法中单独处理。

参数绑定方式默认是按照变量名进行绑定，例如，我们给Blog控制器定义了两个操作方法`read`和`archive`方法，由于`read`操作需要指定一个`id`参数，`archive`方法需要指定年份（`year`）和月份（`month`）两个参数，那么我们可以如下定义：

```php
<?php
namespace app\controller;
class Blog 
{
    public function read($id)
    {
        return 'id=' . $id;
    }
    public function archive($year, $month='01')
    {
        return 'year=' . $year . '&month=' . $month;
    }
}
```

> **注意：** 这里的操作方法并没有具体的业务逻辑，只是简单的示范。

URL的访问地址分别是：
`http://serverName/index.php/blog/read/id/5`
`http://serverName/index.php/blog/archive/year/2016/month/06`

两个URL地址中的`id`参数和`year`和`month`参数会自动和`read`操作方法以及`archive`操作方法的同名参数绑定。
变量名绑定不一定由访问URL决定，路由地址也能起到相同的作用。

输出的结果依次是：
`id=5`
`year=2016&month=06`

按照变量名进行参数绑定的参数必须和URL中传入的变量名称一致，但是参数顺序不需要一致。也就是说
`http://serverName/index.php/blog/archive/month/06/year/2016`
和上面的访问结果是一致的，URL中的参数顺序和操作方法中的参数顺序都可以随意调整，关键是确保参数名称一致即可。

如果用户访问的URL地址是：
`http://serverName/index.php/blog/read`
那么会抛出下面的异常提示： `参数错误:id`

报错的原因很简单，因为在执行`read`操作方法的时候，`id`参数是必须传入参数的，但是方法无法从URL地址中获取正确的`id`参数信息。由于我们不能相信用户的任何输入，因此建议你给`read`方法的`id`参数添加默认值，例如：

```php
public function read($id = 0)
{
    return 'id=' . $id;
}
```

这样，当我们访问 `http://serverName/index.php/blog/read/` 的时候 就会输出
`id=0`

> **提示：** 始终给操作方法的参数定义默认值是一个避免报错的好办法（依赖注入参数除外）

为了更好的配合前端规范，支持自动识别小写+下划线的请求变量使用驼峰注入，例如：
`http://serverName/index.php/blog/read/blog_id/5`

可以使用下面的方式接收`blog_id`变量，所以请确保在方法的参数使用驼峰（首字母小写）规范。

```php
public function read($blogId = 0)
{
    return 'id=' . $blogId;
}
```

## 7\. 数据库

### 7.1. 字符串条件

使用字符串条件直接查询和操作，例如：

`Db::table('think_user')->whereRaw('type=1 AND status=1')->select();`

最后生成的SQL语句是
`SELECT * FROM think_user WHERE type=1 AND status=1`

> **注意：** 使用字符串查询条件和表达式查询的一个区别在于，不会对查询字段进行避免关键词冲突处理。

使用字符串条件的时候，如果需要传入变量，建议配合预处理机制，确保更加安全，例如：

```php
Db::table('think_user')
->whereRaw("id=:id and username=:name", ['id' => 1 , 'name' => 'thinkphp'])
->select();
```

同样支持原生查询。

## 8\. 验证

V6.1 版本的验证器 (Validate) 是一个独立的功能，用于对（通常是 Request 传入的）数据进行专业、批量和场景化的验证。

### 8.1. 验证器类结构

5.1 验证器定义验证器类通常存放在 `app/validate` 目录下，并继承 `think\Validate`。
使用命令行创建验证器：
`php think make:validate User`
(会创建 `app/validate/User.php` 文件)

验证器类结构：

```php
<?php
namespace app\validate;
use think\Validate;

class User extends Validate
{
    // 1. 验证规则 (rule)
    protected $rule = [
        'name'  => 'require|max:25',
        'email' => 'require|email|unique:user', // 验证邮箱唯一
        'age'   => 'require|number|between:1,120',
    ];

    // 2. 错误提示信息 (message)
    protected $message = [
        'name.require'  => '名称必须填写',
        'name.max'      => '名称最多不能超过25个字符',
        'email.require' => '邮箱必须填写',
        'email.email'   => '邮箱格式错误',
        'email.unique'  => '邮箱已被注册',
        'age.number'    => '年龄必须是数字',
        'age.between'   => '年龄必须在1-120之间',
    ];

    // 3. 验证场景 (scene)
    protected $scene = [
        'create' => ['name', 'email', 'age'], // 新增时需要验证的字段
        'edit'   => ['name', 'age'],       // 编辑时需要验证的字段
        'login'  => ['email'],             // 登录时
    ];
}
```

### 8.2. 验证的使用

在控制器中使用验证器是最佳实践。

**1. 在控制器中自动验证 (推荐)**

在控制器中直接调用 `validate()` 方法。如果验证失败，系统会自动抛出 `ValidateException` 异常，并由异常处理机制（默认）返回一个包含错误信息的 JSON 响应。

```php
<?php
namespace app\controller;
use think\Request;
use app\BaseController;

class User extends BaseController
{
    // 1. 自动验证 (使用 app\validate\User 验证器)
    public function save(Request $request)
    {
        $data = $request->param();
        
        // 验证 $data 数据
        // 如果验证失败，会直接抛出异常，中断执行
        $this->validate($data, 'app\validate\User');
        
        // ... 验证通过，继续执行业务逻辑 ...
        // Model::create($data);
        return 'success';
    }

    // 2. 验证 + 场景 (推荐)
    public function update(Request $request)
    {
        $data = $request->param();
        
        // 使用 User 验证器的 edit 场景
        $this->validate($data, 'app\validate\User.edit');
        
        // ... 验证通过 ...
        return 'update success';
    }
}
```

**2. 手动实例化验证 (不推荐在控制器用)**

如果不想验证失败时自动抛出异常，而是想手动捕获错误信息。

```php
use think\facade\Validate;

$data = $request->param();

// 实例化验证器
$validate = Validate::rule([
    'name' => 'require|max:25',
    'email'=> 'email'
]);

// 检查数据
if (!$validate->check($data)) {
    // 验证失败，获取错误信息
    $errors = $validate->getError();
    // $errors 是一个数组或字符串
    return json(['code' => 0, 'msg' => $errors], 400);
}

// ... 验证通过 ...
```

### 8.3. 常用验证规则

| 规则 | 说明 |
| :--- | :--- |
| `require` | 必须 |
| `number` | 必须是数字 |
| `boolean` | 必须是布尔值 |
| `email` | 必须是 Email 格式 |
| `url` | 必须是 URL 格式 |
| `ip` | 必须是 IP 格式 |
| `confirm` | 验证两个字段值是否一致 (如：`password_confirm` 字段和 `password` 字段) |
| `different` | 验证两个字段值是否不一致 |
| `in` | 值必须在 `in:1,2,3` 范围内 |
| `notIn` | 值必须不在 `notIn:1,2,3` 范围内 |
| `between` | 值必须在 `between:1,10` 之间 (闭区间) |
| `length` | 长度必须在 `length:6,20` 之间 |
| `max` | 最大值 (数字) 或 最大长度 (字符串) |
| `min` | 最小值 (数字) 或 最小长度 (字符串) |
| `regex` | 正则表达式验证 (如 `regex:\d{11}`) |
| `unique` | 数据库表唯一 (如 `unique:user` 验证 user 表) |

### 8.4. 验证场景 (Scene)

验证场景用于在同一个验证器中，针对不同的操作（如“新增”和“编辑”）设置不同的验证规则。

**定义 (如 8.1 所示)：**

```php
protected $scene = [
    'create' => ['name', 'email', 'age'], // 验证 3 个字段
    'edit'   => ['name', 'age'],       // 只验证 2 个字段
];
```

**使用 (在控制器中)：**

```php
// 验证器名.场景名
$this->validate($data, 'app\validate\User.create'); // (使用 create 场景)
$this->validate($data, 'app\validate\User.edit');   // (使用 edit 场景)
```

**场景中修改规则：**
可以在场景中临时覆盖 `rule` 中的规则。

```php
protected $scene = [
    'login' => [
        'email' => 'require|email', // 登录时，email 只需要验证格式
        'pass'  => 'require'
    ],
    'create' => [
         // create 时，email 不仅要验证格式，还要验证唯一性
        'email' => 'require|email|unique:user', 
    ]
];
```

### 8.5. 自定义验证规则

如果内置规则不满足需求，可以自定义验证方法。

**在验证器类中定义方法：**

```php
<?php
namespace app\validate;
use think\Validate;

class User extends Validate
{
    protected $rule = [
        'name'  => 'require|checkNameUnique', // 规则调用 checkNameUnique 方法
        'ip'    => 'checkIpBanned', // 规则直接是方法名
    ];

    protected $message = [
        'name.checkNameUnique' => '名称已被占用',
        'ip.checkIpBanned'     => '您的IP已被封禁',
    ];

    // 验证方法 (方法名必须是 "check" 开头 + 规则名)
    // 规则: checkNameUnique
    protected function checkNameUnique($value, $rule, $data)
    {
        // $value 是 'name' 字段的当前值
        // $data 是所有提交的数据
        // 假设检查数据库
        $count = Db::name('user')->where('name', $value)->count();
        return $count > 0 ? false : true;
    }

    // 验证方法 (规则名和方法名一致)
    protected function checkIpBanned($value, $rule, $data)
    {
        // $value 是 'ip' 字段的值
        $bannedList = ['127.0.0.1', '192.168.1.1'];
        return in_array($value, $bannedList) ? false : true;
    }
}
```