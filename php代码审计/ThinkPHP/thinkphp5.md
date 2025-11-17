## ThinkPHP 5.0

### 1\. 标准目录结构

```text
project  应用部署目录
├─application           应用目录（可设置）
│  ├─common             公共模块目录（可更改）
│  ├─index              模块目录(可更改)
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─ ...            更多类库目录
│  ├─command.php        命令行工具配置文件
│  ├─common.php         应用公共（函数）文件
│  ├─config.php         应用（公共）配置文件
│  ├─database.php       数据库配置文件
│  ├─tags.php           应用行为扩展定义文件
│  └─route.php          路由配置文件
├─extend                扩展类库目录（可定义）
├─public                WEB 部署目录（对外访问目录）
│  ├─static             静态资源存放目录(css,js,image)
│  ├─index.php          应用入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于 apache 的重写
├─runtime               应用的运行时目录（可写，可设置）
├─vendor                第三方类库目录（Composer）
├─thinkphp              框架系统目录
│  ├─lang               语言包目录
│  ├─library            框架核心类库目录
│  │  ├─think           Think 类库包目录
│  │  └─traits          系统 Traits 目录
│  ├─tpl                系统模板目录
│  ├─.htaccess          用于 apache 的重写
│  ├─.travis.yml        CI 定义文件
│  ├─base.php           基础定义文件
│  ├─composer.json      composer 定义文件
│  ├─console.php        控制台入口文件
│  ├─convention.php     惯例配置文件
│  ├─helper.php         助手函数文件（可选）
│  ├─LICENSE.txt        授权说明文件
│  ├─phpunit.xml        单元测试配置文件
│  ├─README.md          README 文件
│  └─start.php          框架引导文件
├─build.php             自动生成定义文件（参考）
├─composer.json         composer 定义文件
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
├─think                 命令行入口文件
```

### 2\. 入口文件

入口文件主要完成：

  * 定义框架路径、项目路径（可选）
  * 定义系统相关常量（可选）
  * 载入框架入口文件（必须）

5.0默认的应用入口文件位于`public/index.php`，内容如下：

```php
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
```

入口文件位置的设计是为了让应用部署更安全，`public`目录为web可访问目录，其他的文件都可以放到非WEB访问目录下面。

在有些情况下，你可能需要加载框架的基础引导文件`base.php`，该引导文件和`start.php`的区别是不会主动执行应用，而是需要自己进行应用执行，下面是一个例子：

```php
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架基础引导文件
require __DIR__ . '/../thinkphp/base.php';
// 添加额外的代码
// ...
// 执行应用
\think\App::run()->send();
```

### 3\. 路由机制

ThinkPHP5.0的路由比较灵活，并且不需要强制定义，可以总结归纳为如下三种方式：

#### 3.1. 普通模式

关闭路由，完全使用默认的PATH\_INFO方式URL：

```php
'url_route_on'  =>  false,
```

路由关闭后，不会解析任何路由规则，采用默认的PATH\_INFO 模式访问URL：
`http://serverName/index.php/module/controller/action/param/value/...`

但仍然可以通过操作方法的参数绑定、空控制器和空操作等特性实现URL地址的简化。

可以设置`url_param_type`配置参数来改变pathinfo模式下面的参数获取方式，默认是按名称成对解析，支持按照顺序解析变量，只需要更改为：

```php
// 按照顺序解析变量
'url_param_type'    =>  1,
```

#### 3.2. 混合模式

开启路由，并使用路由定义+默认PATH\_INFO方式的混合：

```php
'url_route_on'  =>  true,
'url_route_must'=>  false,
```

该方式下面，只需要对需要定义路由规则的访问地址定义路由规则，其它的仍然按照第一种普通模式的PATH\_INFO模式访问URL。

#### 3.3. 强制模式

开启路由，并设置必须定义路由才能访问：

```php
'url_route_on'  		=>  true,
'url_route_must'		=>  true,
```

这种方式下面必须严格给每一个访问地址定义路由规则（包括首页），否则将抛出异常。
首页的路由规则采用`/`定义即可，例如下面把网站首页路由输出Hello,world\!

```php
Route::get('/',function(){
    return 'Hello,world!';
});
```

#### 3.4. 注册路由

路由定义采用`\think\Route`类的`rule`方法注册，通常是在应用的路由配置文件`application/route.php`进行注册，格式是：

`Route::rule('路由表达式','路由地址','请求类型','路由参数（数组）','变量规则（数组）');`

例如注册如下路由规则：

```php
use think\Route;
// 注册路由到index模块的News控制器的read操作
Route::rule('new/:id','index/News/read');
```

我们访问：
`http://serverName/new/5`

ThinkPHP5.0的路由规则定义是从根目录开始，而不是基于模块名的。
会自动路由到：
`http://serverName/index/news/read/id/5`
并且原来的访问地址会自动失效。

路由表达式（第一个参数）支持定义命名标识，例如：

```php
// 定义new路由命名标识
Route::rule(['new','new/:id'],'index/News/read');
```

注意，路由命名标识必须唯一，定义后可以用于URL的快速生成。

可以在`rule`方法中指定请求类型，不指定的话默认为任何请求类型，例如：

```php
Route::rule('new/:id','News/update','POST');
```

表示定义的路由规则在POST请求下才有效。

#### 3.5. 路由表达式

路由表达式统一使字符串定义，采用规则定义的方式。
正则路由定义功能已经废除，改由变量规则定义完成。

#### 3.6. 规则表达式

规则表达式通常包含静态地址和动态地址，或者两种地址的结合，例如下面都属于有效的规则表达式：

```php
'/' => 'index', // 首页访问路由
'my'        =>  'Member/myinfo', // 静态地址路由
'blog/:id'  =>  'Blog/read', // 静态地址和动态地址结合
'new/:year/:month/:day'=>'News/read', // 静态地址和动态地址结合
':user/:blog_id'=>'Blog/read',// 全动态地址
```

规则表达式的定义以`/`为参数分割符（无论你的PATH\_INFO分隔符设置是什么，请确保在定义路由规则表达式的时候统一使用`/`进行URL参数分割）。

每个参数中以“:”开头的参数都表示动态变量，并且会自动绑定到操作方法的对应参数。

#### 3.7. 可选定义

支持对路由参数的可选定义，例如：

`'blog/:year/[:month]'=>'Blog/archive',`

`[:month]`变量用`[ ]`包含起来后就表示该变量是路由匹配的可选变量。
以上定义路由规则后，下面的URL访问地址都可以被正确的路由匹配：

`http://serverName/index.php/blog/2015`
`http://serverName/index.php/blog/2015/12`

采用可选变量定义后，之前需要定义两个或者多个路由规则才能处理的情况可以合并为一个路由规则。

#### 3.8. 完全匹配

规则匹配检测的时候只是对URL从头开始匹配，只要URL地址包含了定义的路由规则就会匹配成功，如果希望完全匹配，可以在路由表达式最后使用`$`符号，例如：

`'new/:cate$'=> 'News/category',`

`http://serverName/index.php/new/info`
会匹配成功,而
`http://serverName/index.php/new/info/2`
则不会匹配成功。

如果是采用
`'new/:cate'=> 'News/category',`
方式定义的话，则两种方式的URL访问都可以匹配成功。

如果你希望所有的路由定义都是完全匹配的话，可以直接配置

```php
// 开启路由定义的全局完全匹配
'route_complete_match'  =>  true,
```

当开启全局完全匹配的时候，如果个别路由不需要使用完整匹配，可以添加路由参数覆盖定义：

```php
Route::rule('new/:id','News/read','GET|POST',['complete_match' => false]);
```

#### 3.9. 额外参数

在路由跳转的时候支持额外传入参数对（额外参数指的是不在URL里面的参数，隐式传入需要的操作中，有时候能够起到一定的安全防护作用，后面我们会提到）。例如：

`'blog/:id'=>'blog/read?status=1&app_id=5',`

上面的路由规则定义中额外参数的传值方式都是等效的。status和app\_id参数都是URL里面不存在的，属于隐式传值，当然并不一定需要用到，只是在需要的时候可以使用。

### 4\. 控制器

ThinkPHP V5.0的控制器定义比较灵活，可以无需继承任何的基础类，也可以继承官方封装的`\think\Controller`类或者其他的控制器类。

#### 4.1. 控制器定义

一个典型的控制器类定义如下：

```php
namespace app\index\controller;
class Index 
{
    public function index()
    {
        return 'index';
    }
}
```

控制器类文件的实际位置是
`application\index\controller\Index.php`

控制器类可以无需继承任何类，命名空间默认以`app`为根命名空间。
控制器的根命名空间可以设置，例如我们在应用配置文件中修改：

```php
// 修改应用类库命名空间
'app_namespace' => 'application',
```

V5.0.8+版本的话，`app_namespace`配置参数改为`APP_NAMESPACE`常量在入口文件中定义。
则实际的控制器类应该更改定义如下：

```php
namespace application\index\controller;
class Index 
{
    public function index()
    {
        return 'index';
    }
}
```

只是命名空间改变了，但实际的文件位置和文件名并没有改变。
使用该方式定义的控制器类，如果要在控制器里面渲染模板，可以使用

```php
namespace app\index\controller;
use think\View;
class Index 
{
    public function index()
    {
        $view = new View();
        return $view->fetch('index');
    }
}
```

或者直接使用`view`助手函数渲染模板输出，例如：

```php
namespace app\index\controller;
class Index 
{
    public function index()
    {
        return view('index');
    }
}
```

如果继承了`think\Controller`类的话，可以直接调用`think\View`及`think\Request`类的方法，例如：

```php
namespace app\index\controller;
use think\Controller;
class Index extends Controller
{
    public function index()
    {
        // 获取包含域名的完整URL地址
        $this->assign('domain',$this->request->url(true));
        return $this->fetch('index');
    }
}
```

#### 4.2. 渲染输出

默认情况下，控制器的输出全部采用`return`的方式，无需进行任何的手动输出，系统会自动完成渲染内容的输出。
下面都是有效的输出方式：

```php
namespace app\index\controller;
class Index 
{
    public function hello()
    {
        return 'hello,world!';
    }
    public function json()
    {
        return json_encode($data);
    }
    public function read()
    {
        return view();
    }
}
```

控制器一般不需要任何输出，直接`return`即可。

#### 4.3. 输出转换

默认情况下，控制器的返回输出不会做任何的数据处理，但可以设置输出格式，并进行自动的数据转换处理，前提是控制器的输出数据必须采用`return`的方式返回。

如果控制器定义为：

```php
namespace app\index\controller;
class Index 
{
    public function hello()
    {
        return 'hello,world!';
    }
    public function data()
    {
        return ['name'=>'thinkphp','status'=>1];
    }
}
```

当我们设置输出数据格式为JSON：

```php
// 默认输出类型
'default_return_type'   => 'json',
```

我们访问
`http://localhost/index.php/index/Index/hello`
`http://localhost/index.php/index/Index/data`

输出的结果变成：

```json
"hello,world!"
```

```json
{"name":"thinkphp","status":1}
```

默认情况下，控制器在ajax请求会对返回类型自动转换，默认为json。
如果我们控制器定义

```php
namespace app\index\controller;
class Index 
{
    public function data()
    {
        return ['name'=>'thinkphp','status'=>1];
    }
}
```

我们访问
`http://localhost/index.php/index/Index/data`
输出的结果变成：

```json
{"name":"thinkphp","status":1}
```

当我们设置输出数据格式为html：

```php
// 默认输出类型
'default_ajax_return'   => 'html',
```

这种情况下ajax请求不会对返回内容进行转换。

### 5\. 请求

如果要获取当前的请求信息，可以使用`\think\Request`类。
除了下文中的
`$request = Request::instance();`
也可以使用助手函数
`$request = request();`

#### 5.1. 获取 URL 信息

```php
$request = Request::instance();
// 获取当前域名
echo 'domain: ' . $request->domain() . '<br/>';
// 获取当前入口文件
echo 'file: ' . $request->baseFile() . '<br/>';
// 获取当前URL地址 不含域名
echo 'url: ' . $request->url() . '<br/>';
// 获取包含域名的完整URL地址
echo 'url with domain: ' . $request->url(true) . '<br/>';
// 获取当前URL地址 不含QUERY_STRING
echo 'url without query: ' . $request->baseUrl() . '<br/>';
// 获取URL访问的ROOT地址
echo 'root:' . $request->root() . '<br/>';
// 获取URL访问的ROOT地址
echo 'root with domain: ' . $request->root(true) . '<br/>';
// 获取URL地址中的PATH_INFO信息
echo 'pathinfo: ' . $request->pathinfo() . '<br/>';
// 获取URL地址中的PATH_INFO信息 不含后缀
echo 'pathinfo: ' . $request->path() . '<br/>';
// 获取URL地址中的后缀信息
echo 'ext: ' . $request->ext() . '<br/>';
```

#### 5.2. 获取请求参数

```php
$request = Request::instance();
echo '请求方法：' . $request->method() . '<br/>';
echo '资源类型：' . $request->type() . '<br/>';
echo '访问ip地址：' . $request->ip() . '<br/>';
echo '是否AJax请求：' . var_export($request->isAjax(), true) . '<br/>';
echo '请求参数：';
dump($request->param());
echo '请求参数：仅包含name';
dump($request->only(['name']));
echo '请求参数：排除name';
dump($request->except(['name']));
```

#### 5.3. 检测变量是否设置

可以使用`has`方法来检测一个变量参数是否设置，如下：

```php
Request::instance()->has('id','get');
Request::instance()->has('name','post');
```

或者使用助手函数

```php
input('?get.id');
input('?post.name');
```

变量检测可以支持所有支持的系统变量。

#### 5.4. 变量获取

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
| `session` | 获取 `$_SESSION` 变量 |
| `cookie` | 获取 `$_COOKIE` 变量 |
| `request` | 获取 `$_REQUEST` 变量 |
| `server` | 获取 `$_SERVER` 变量 |
| `env` | 获取 `$_ENV` 变量 |
| `route` | 获取 路由（包括PATHINFO） 变量 |
| `file` | 获取 `$_FILES` 变量 |

**获取 PARAM 变量**

PARAM变量是框架提供的用于自动识别GET、POST或者PUT请求的一种变量获取方式，是系统推荐的获取请求参数的方法，用法如下：

```php
// 获取当前请求的name变量
Request::instance()->param('name');
// 获取当前请求的所有变量（经过过滤）
Request::instance()->param();
// 获取当前请求的所有变量（原始数据）
Request::instance()->param(false);
// 获取当前请求的所有变量（包含上传文件）
Request::instance()->param(true);
```

`param`方法会把当前请求类型的参数和PATH\_INFO变量以及GET请求合并。

使用助手函数实现：

```php
input('param.name');
input('param.');
```

或者

```php
input('name');
input('');
```

因为`input`函数默认就采用PARAM变量读取方式。

**获取 GET 变量**

```php
Request::instance()->get('id'); // 获取某个get变量
Request::instance()->get('name'); // 获取get变量
Request::instance()->get(); // 获取所有的get变量（经过过滤的数组）
Request::instance()->get(false); // 获取所有的get变量（原始数组）
```

或者使用内置的助手函数`input`方法实现相同的功能：

```php
input('get.id');
input('get.name');
input('get.');
```

> **注：** pathinfo地址参数不能通过get方法获取，查看“获取PARAM变量”

**获取 POST 变量**

```php
Request::instance()->post('name'); // 获取某个post变量
Request::instance()->post(); // 获取经过过滤的全部post变量
Request::instance()->post(false); // 获取全部的post原始变量
```

使用助手函数实现：

```php
input('post.name');
input('post.');
```

**获取 PUT 变量**

```php
Request::instance()->put('name'); // 获取某个put变量
Request::instance()->put(); // 获取全部的put变量（经过过滤）
Request::instance()->put(false); // 获取全部的put原始变量
```

使用助手函数实现：

```php
input('put.name');
input('put.');
```

**获取 REQUEST 变量**

```php
Request::instance()->request('id'); // 获取某个request变量
Request::instance()->request(); // 获取全部的request变量（经过过滤）
Request::instance()->request(false); // 获取全部的request原始变量数据
```

使用助手函数实现：

```php
input('request.id');
input('request.');
```

**获取 SERVER 变量**

```php
Request::instance()->server('PHP_SELF'); // 获取某个server变量
Request::instance()->server(); // 获取全部的server变量
```

使用助手函数实现：

```php
input('server.PHP_SELF');
input('server.');
```

**获取 SESSION 变量**

```php
Request::instance()->session('user_id'); // 获取某个session变量
Request::instance()->session(); // 获取全部的session变量
```

使用助手函数实现：

```php
input('session.user_id');
input('session.');
```

**获取 Cookie 变量**

```php
Request::instance()->cookie('user_id'); // 获取某个cookie变量
Request::instance()->cookie(); // 获取全部的cookie变量
```

使用助手函数实现：

```php
input('cookie.user_id');
input('cookie.');
```

#### 5.5. 变量过滤

框架默认没有设置任何过滤规则，你可以是配置文件中设置全局的过滤规则：

```php
// 默认全局过滤方法 用逗号分隔多个
'default_filter'         => 'htmlspecialchars',
```

也支持使用Request对象进行全局变量的获取过滤，过滤方式包括函数、方法过滤，以及PHP内置的Types of filters，我们可以设置全局变量过滤方法，例如：

`Request::instance()->filter('htmlspecialchars');`

支持设置多个过滤方法，例如：

`Request::instance()->filter(['strip_tags','htmlspecialchars']),`

也可以在获取变量的时候添加过滤方法，例如：

```php
Request::instance()->get('name','','htmlspecialchars'); // 获取get变量 并用htmlspecialchars函数过滤
Request::instance()->param('username','','strip_tags'); // 获取param变量 并用strip_tags函数过滤
Request::instance()->post('name','','org\Filter::safeHtml'); // 获取post变量 并用org\Filter类的safeHtml方法过滤
```

可以支持传入多个过滤规则，例如：

`Request::instance()->param('username','','strip_tags,strtolower'); // 获取param变量 并依次调用strip_tags、strtolower函数过滤`

Request对象还支持PHP内置提供的Filter ID过滤，例如：

`Request::instance()->post('email','',FILTER_VALIDATE_EMAIL);`

框架对FilterID做了转换支持，因此也可以使用字符串的方式，例如：

`Request::instance()->post('email','','email');`

采用字符串方式定义FilterID的时候，系统会自动进行一次`filter_id`调用转换成Filter常量。
具体的字符串根据`filter_list`函数的返回值来定义。

需要注意的是，采用Filter ID 进行过滤的话，如果不符合过滤要求的话 会返回false，因此你需要配合默认值来确保最终的值符合你的规范。
例如，
`Request::instance()->post('email','',FILTER_VALIDATE_EMAIL);`
就表示，如果不是规范的email地址的话 返回空字符串。

如果当前不需要进行任何过滤的话，可以使用（V5.0.3+版本）

```php
// 获取get变量 并且不进行任何过滤 即使设置了全局过滤
Request::instance()->get('name','',null);
```

#### 5.6. 获取部分变量

如果你只需要获取当前请求的部分参数，可以使用：

```php
// 只获取当前请求的id和name变量
Request::instance()->only('id,name');
```

或者使用数组方式

```php
// 只获取当前请求的id和name变量
Request::instance()->only(['id','name']);
```

默认获取的是当前请求参数，如果需要获取其它类型的参数，可以使用第二个参数，例如：

```php
// 只获取GET请求的id和name变量
Request::instance()->only(['id','name'],'get');
// 只获取POST请求的id和name变量
Request::instance()->only(['id','name'],'post');
```

#### 5.7. 排除部分变量

也支持排除某些变量获取，例如

```php
// 排除id和name变量
Request::instance()->except('id,name');
```

或者使用数组方式

```php
// 排除id和name变量
Request::instance()->except(['id','name']);
```

同样支持指定变量类型获取：

```php
// 排除GET请求的id和name变量
Request::instance()->except(['id','name'],'get');
// 排除POST请求的id和name变量
Request::instance()->except(['id','name'],'post');
```

#### 5.8. 变量修饰符

`input`函数支持对变量使用修饰符功能，可以更好的过滤变量。

用法如下：
`input('变量类型.变量名/修饰符');`

或者
`Request::instance()->变量类型('变量名/修饰符');`

例如：

```php
input('get.id/d');
input('post.name/s');
input('post.ids/a');
Request::instance()->get('id/d');
```

ThinkPHP5.0版本默认的变量修饰符是`/s`，如果需要传入字符串之外的变量可以使用下面的修饰符，包括：

| 修饰符 | 作用 |
| :--- | :--- |
| `s` | 强制转换为字符串类型 |
| `d` | 强制转换为整型类型 |
| `b` | 强制转换为布尔类型 |
| `a` | 强制转换为数组类型 |
| `f` | 强制转换为浮点类型 |

> 如果你要获取的数据为数组，请一定注意要加上 `/a` 修饰符才能正确获取到。

#### 5.9. HTTP 头信息

可以使用Request对象的`header`方法获取当前请求的HTTP 请求头信息，例如：

```php
$info = Request::instance()->header();
echo $info['accept'];
echo $info['accept-encoding'];
echo $info['user-agent'];
```

也可以直接获取某个请求头信息，例如：

`$agent = Request::instance()->header('user-agent');`

HTTP请求头信息的名称不区分大小写，并且`_`会自动转换为`-`，所以下面的写法都是等效的：

```php
$agent = Request::instance()->header('user-agent');
$agent = Request::instance()->header('User-Agent');
$agent = Request::instance()->header('USER_AGENT');
```

### 6\. 参数绑定

方法参数绑定是把URL地址（或者路由地址）中的变量作为操作方法的参数直接传入。

#### 6.1. 操作方法参数绑定

**按名称绑定**

参数绑定方式默认是按照变量名进行绑定。例如，我们给Blog控制器定义了两个操作方法`read`和`archive`方法，由于`read`操作需要指定一个`id`参数，`archive`方法需要指定年份（`year`）和月份（`month`）两个参数，那么我们可以如下定义：

```php
namespace app\index\Controller;
class Blog 
{
    public function read($id)
    {
        return 'id='.$id;
    }
    public function archive($year='2016',$month='01')
    {
        return 'year='.$year.'&month='.$month;
    }
}
```

> **注意：** 这里的操作方法并没有具体的业务逻辑，只是简单的示范。

URL的访问地址分别是：
`http://serverName/index.php/index/blog/read/id/5`
`http://serverName/index.php/index/blog/archive/year/2016/month/06`

两个URL地址中的`id`参数和`year`和`month`参数会自动和`read`操作方法以及`archive`操作方法的同名参数绑定。
变量名绑定不一定由访问URL决定，路由地址也能起到相同的作用。

输出的结果依次是：
`id=5`
`year=2016&month=06`

按照变量名进行参数绑定的参数必须和URL中传入的变量名称一致，但是参数顺序不需要一致。也就是说
`http://serverName/index.php/index/blog/archive/month/06/year/2016`
和上面的访问结果是一致的，URL中的参数顺序和操作方法中的参数顺序都可以随意调整，关键是确保参数名称一致即可。

如果用户访问的URL地址是（至于为什么会这么访问暂且不提）：
`http://serverName/index.php/index/blog/read/`
那么会抛出下面的异常提示： `参数错误:id`

报错的原因很简单，因为在执行`read`操作方法的时候，`id`参数是必须传入参数的，但是方法无法从URL地址中获取正确的`id`参数信息。由于我们不能相信用户的任何输入，因此建议你给`read`方法的`id`参数添加默认值，例如：

```php
    public function read($id=0)
    {
        return 'id='.$id;
    }
```

这样，当我们访问 `http://serverName/index.php/index/blog/read/` 的时候 就会输出
`id=0`

> **提示：** 始终给操作方法的参数定义默认值是一个避免报错的好办法。

**按顺序绑定**

还可以支持按照URL的参数顺序进行绑定的方式，合理规划URL参数的顺序绑定对简化URL地址可以起到一定的帮助。
还是上面的例子，控制器不变，还是使用：

```php
namespace app\index\Controller;
class Blog 
{
    public function read($id)
    {
        return 'id='.$id;
    }

    public function archive($year='2016',$month='01')
    {
        return 'year='.$year.'&month='.$month;
    }
}
```

我们在配置文件中添加配置参数如下：

```php
// URL参数方式改成顺序解析
'url_param_type'         => 1,
```

接下来，访问下面的URL地址：
`http://serverName/index.php/index/blog/read/5`
`http://serverName/index.php/index/blog/archive/2016/06`

输出的结果依次是：
`id=5`
`year=2016&month=06`

按参数顺序绑定的话，参数的顺序不能随意调整，如果访问：
`http://serverName/index.php/index/blog/archive/06/2016`
最后的输出结果则变成：
`year=06&month=2016`

> **注意：** 按顺序绑定参数的话，操作方法的参数只能使用URL pathinfo变量，而不能使用get或者post变量。

参数绑定有一个特例，如果你的操作方法中定义有`Request`对象作为参数的话，无论参数位置在哪里，都会自动注入，而不需要进行参数绑定。

#### 6.2. 架构方法参数绑定（V5.0.1）

可以对架构函数进行参数绑定，当前请求的路由变量可以自动绑定到架构函数的参数，例如：

```php
namespace app\index\Controller;
class Blog 
{
	protected $name;
	public function __construct($name = null)
    {
    	$this->name = $name;
    }
}
```

如果访问
`http://localhost/index/index/index/name/thinkphp`
当前请求的路由变量`name`的值`thinkphp`会自动传入架构方法的`name`变量。

### 7\. 数据库

#### 7.1. 基本查询

查询一个数据使用：

```php
// table方法必须指定完整的数据表名
Db::table('think_user')->where('id',1)->find();
```

`find` 方法查询结果不存在，返回 `null`。

查询数据集使用：

```php
Db::table('think_user')->where('status',1)->select();
```

`select` 方法查询结果不存在，返回空数组。

如果设置了数据表前缀参数的话，可以使用

```php
Db::name('user')->where('id',1)->find();
Db::name('user')->where('status',1)->select();
```

如果你的数据表没有使用表前缀功能，那么`name`和`table`方法的一样的效果。
在`find`和`select`方法之前可以使用所有的链式操作方法。
默认情况下，`find`和`select`方法返回的都是数组。

#### 7.2. 助手函数

系统提供了一个`db`助手函数，可以更方便的查询：

```php
db('user')->where('id',1)->find();
db('user')->where('status',1)->select();
```

> **注意：**
>
>   * 5.0.9 版本之前：使用`db`助手函数默认每次都会重新连接数据库，而使用`Db::name`或者`Db::table`方法的话都是单例的。`db`函数如果需要采用相同的链接，可以传入第三个参数，例如：
>     ```php
>     db('user',[],false)->where('id',1)->find();
>     db('user',[],false)->where('status',1)->select();
>     ```
>     上面的方式会使用同一个数据库连接，第二个参数为数据库的连接参数，留空表示采用数据库配置文件的配置。
>   * **5.0.9 版本**：`db`助手函数默认不再强制重新连接。

#### 7.3. 使用 Query 对象或闭包查询

或者使用查询对象进行查询，例如：

```php
$query = new \think\db\Query();
$query->table('think_user')->where('status',1);
Db::find($query);
Db::select($query);
```

或者直接使用闭包函数查询，例如：

```php
Db::select(function($query){
    $query->table('think_user')->where('status',1);
});
```

#### 7.4. 值和列查询

查询某个字段的值可以用

```php
// 返回某个字段的值
Db::table('think_user')->where('id',1)->value('name');
```

`value` 方法查询结果不存在，返回 `null`。

查询某一列的值可以用

```php
// 返回数组
Db::table('think_user')->where('status',1)->column('name');
// 指定索引
Db::table('think_user')->where('status',1)->column('name','id');
// 同tp3的getField
Db::table('think_user')->where('status',1)->column('id,name'); 
```

`column` 方法查询结果不存在，返回空数组。

#### 7.5. JSON 类型数据查询（mysql V5.0.1）

```php
// 查询JSON类型字段 （info字段为json类型）
Db::table('think_user')->where('info$.email','thinkphp@qq.com')->find();
```

#### 7.6. 字符串条件

使用字符串条件直接查询和操作，例如：

`Db::table('think_user')->where('type=1 AND status=1')->select();`

最后生成的SQL语句是
`SELECT * FROM think_user WHERE type=1 AND status=1`

使用字符串条件的时候，建议配合预处理机制，确保更加安全，例如：

`Db::table('think_user')->where("id=:id and username=:name")->bind(['id'=>[1,\PDO::PARAM_INT],'name'=>'thinkphp'])->select();`

#### 7.7. 原生查询

Db类支持原生SQL查询操作，主要包括下面两个方法：

**query 方法**

`query`方法用于执行SQL查询操作，如果数据非法或者查询错误则返回false，否则返回查询结果数据集（同select方法）。

使用示例：

`Db::query("select * from think_user where status=1");`

如果你当前采用了分布式数据库，并且设置了读写分离的话，`query`方法始终是在读服务器执行，因此`query`方法对应的都是读操作，而不管你的SQL语句是什么。

**execute 方法**

`execute`用于更新和写入数据的sql操作，如果数据非法或者查询错误则返回false ，否则返回影响的记录数。

使用示例：

`Db::execute("update think_user set name='thinkphp' where status=1");`

如果你当前采用了分布式数据库，并且设置了读写分离的话，`execute`方法始终是在写服务器执行，因此`execute`方法对应的都是写操作，而不管你的SQL语句是什么。

**参数绑定**

支持在原生查询的时候使用参数绑定，包括问号占位符或者命名占位符，例如：

```php
Db::query("select * from think_user where id=? AND status=?",[8,1]);
// 命名绑定
Db::execute("update think_user set name=:name where status=:status",['name'=>'thinkphp','status'=>1]);
```

### 8\. 验证

#### 8.1. 概述

ThinkPHP5.0验证使用独立的`\think\Validate`类或者验证器进行验证。

#### 8.2. 独立验证

任何时候，都可以使用`Validate`类进行独立的验证操作，例如：

```php
$validate = new Validate([
    'name'  => 'require|max:25',
    'email' => 'email'
]);

$data = [
    'name'  => 'thinkphp',
    'email' => 'thinkphp@qq.com'
];

if (!$validate->check($data)) {
    dump($validate->getError());
}
```

#### 8.3. 验证器

这是5.0推荐的验证方式，为具体的验证场景或者数据表定义好验证器类，直接调用验证类的`check`方法即可完成验证，下面是一个例子：

我们定义一个`\app\index\validate\User`验证器类用于User的验证。

```php
namespace app\index\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'name'  =>  'require|max:25',
        'email' =>  'email',
    ];
}
```

在需要进行User验证的地方，添加如下代码即可：

```php
$data = [
    'name'=>'thinkphp',
    'email'=>'thinkphp@qq.com'
];

$validate = Loader::validate('User');

if(!$validate->check($data)){
    dump($validate->getError());
}
```

使用助手函数实例化验证器：
`$validate = validate('User');`

#### 8.4. 表单令牌

验证规则支持对表单的令牌验证，首先需要在你的表单里面增加下面隐藏域：

```html
<input type="hidden" name="__token__" value="{$Request.token}" />
```

或者

```html
{:token()}
```

然后在你的验证规则中，添加`token`验证规则即可，例如，如果使用的是验证器的话，可以改为：

```php
    protected $rule = [
        'name'  =>  'require|max:25|token',
        'email' =>  'email',
    ];
```

如果你的令牌名称不是`__token__`，则表单需要改为：

```html
<input type="hidden" name="__hash__" value="{$Request.token.__hash__}" />
```

或者：

```html
{:token('__hash__')}
```

验证器中需要改为：

```php
    protected $rule = [
        'name'  =>  'require|max:25|token:__hash__',
        'email' =>  'email',
    ];
```

如果需要自定义令牌生成规则，可以调用Request类的`token`方法，例如：

```php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        $token = $this->request->token('__token__', 'sha1');
        $this->assign('token', $token);
        return $this->fetch();
    }
}
```

然后在模板表单中使用：

```html
<input type="hidden" name="__token__" value="{$token}" />
```

或者不需要在控制器写任何代码，直接在模板中使用：

```html
{:token('__token__', 'sha1')}
```

### 9\. 安全

#### 9.1. 输入安全

虽然5.0的底层安全防护比之前版本要强大不少，但永远不要相信用户提交的数据，建议务必遵守下面规则：

  * 设置`public`目录为唯一对外访问目录，不要把资源文件放入应用目录；
  * 开启表单令牌验证避免数据的重复提交，能起到CSRF防御作用；
  * 使用框架提供的请求变量获取方法（Request类`param`方法及`input`助手函数）而不是原生系统变量获取用户输入数据；
  * 对不同的应用需求设置`default_filter`过滤规则（默认没有任何过滤规则），常见的安全过滤函数包括`stripslashes`、`htmlentities`、`htmlspecialchars`和`strip_tags`等，请根据业务场景选择最合适的过滤方法；
  * 使用验证类或者验证方法对业务数据设置必要的验证规则；
  * 如果可能开启强制路由或者设置MISS路由规则，严格规范每个URL请求；

#### 9.2. 数据库安全

在确保用户请求的数据安全之后，数据库的安全隐患就已经很少了，因为5.0版本的数据操作使用了PDO预处理机制及自动参数绑定功能，请确保：

  * 尽量少使用数组查询条件而应该使用查询表达式替代；
  * 尽量少使用字符串查询条件，如果不得已的情况下 使用手动参数绑定功能；
  * 不要让用户输入决定要查询或者写入的字段；
  * 对于敏感数据在输出的时候使用`hidden`方法进行隐藏；
  * 对于数据的写入操作应当做好权限检查工作；
  * 写入数据严格使用`field`方法限制写入字段；
  * 对于需要输出到页面的数据做好必要的XSS过滤；

#### 9.3. 上传安全

网站的上传功能也是一个非常容易被攻击的入口，所以对上传功能的安全检查是尤其必要的。

系统的`think\File`提供了文件上传的安全支持，包括对文件后缀、文件类型、文件大小以及上传图片文件的合法性检查，确保你已经在上传操作中启用了这些合法性检查。

为了方便版本升级，并且保证`public`目录为唯一的web可访问目录，资源文件可以放到项目之外，例如项目目录为
`/home/www/thinkphp/`
那么资源目录、上传文件保存的目录
`/home/www/resource/`
`/home/www/resource/upload/`

为了项目的可维护性，目录操作最好不超出本项目的根目录，所以需要把`resource`目录映射到根目录
`ln -s /home/www/resource/  /home/www/thinkphp/resource/`

如果上传文件也需要web访问，可以生成一个软连接到`public`
`ln -s /home/www/thinkphp/resource/upload/  /home/www/thinkphp/public/upload/`

### 10. ThinkPHP 5.0 与 ThinkPHP 5.1 的部分差异

#### 10.1. 核心架构变化

**1. 命名空间 (Namespace)**

* **5.0 写法:** 静态调用系统核心类库，例如 `use think\App;` 或 `use think\Config;`。应用命名空间在应用配置文件中定义，例如 `'app_namespace' => 'application'`。
* **5.1 写法:** 5.0 中的核心类库（如 `think\App`, `think\Cache`, `think\Config` 等）均改为使用 "facade" 门面类。
    * `think\App` 变为 `think\facade\App`。
    * `think\Config` 变为 `think\facade\Config`。
    * `think\Route` 变为 `think\facade\Route`。
    * 应用命名空间 (APP\_NAMESPACE) 不再通过配置文件设置，而是改为在 `.env` 文件中定义环境变量。

**2. 配置文件 (Configuration)**

* **5.0 写法:** 配置文件位于 `application/` 目录下，例如 `application/config.php` , `application/database.php` , `application/route.php`。
* **5.1 写法:**
    * **位置:** 原有的 `config.php` 文件被移动到与应用目录同级的 `config/` 目录，并拆分为 `app.php`, `cache.php`, `database.php` 等独立配置文件。
    * **层级:** 5.1 的配置全部采用二级配置方式。例如，`config('app_debug')` 等同于 `config('app.app_debug')`。
    * **模板配置:** 5.0 的 `view_replace_str` 配置参数在 5.1 中更改为 `template.php` 配置文件中的 `tpl_replace_string` 参数。

**3. 常量 (Constants)**

* **5.0 写法:** 框架内置了大量常量，如 `THINK_PATH`, `APP_PATH`, `ROOT_PATH`, `RUNTIME_PATH` 等。应用入口文件 `public/index.php` 中会定义 `APP_PATH`。
* **5.1 写法:** 取消了所有框架内置常量。必须使用 `think\facade\App` 和 `think\facade\Env` 类的方法来获取。
    * `THINK_VERSION` (5.0) 变为 `App::version()` (5.1)。
    * `APP_PATH` (5.0) 变为 `Env::get('app_path')` (5.1)。
    * `ROOT_PATH` (5.0) 变为 `Env::get('root_path')` (5.1)。
    * `RUNTIME_PATH` (5.0) 变为 `Env::get('runtime_path')` (5.1)。

#### 10.2. 功能模块差异

**1. 路由 (Routing)**

* **5.0 写法:**
    * **路由文件:** `application/route.php`。
    * **开启配置:** 通过 `'url_route_on' => true` 开启路由。
    * **批量绑定域名:** `Route::domain(['a' => 'a', 'b' => 'b'])`。
* **5.1 写法:**
    * **路由文件:** 路由定义文件（如 `route.php`）被移动到与应用同级的 `route/` 目录下。
    * **开启配置:** `url_route_on` 配置参数失效，路由始终会被检查。
    * **批量注册:** `Route::rule` 不再支持批量注册，需使用 `Route::rules` 替代。
    * **批量绑定域名:** 必须改为单独绑定，例如：`Route::domain('a','a');` 和 `Route::domain('b','b');`。

**2. 数据库 (Database)**

* **5.0 写法:**
    * **数组查询:** 支持 `where(['name' => ['like','think%'], 'id' => ['>',0]])`。
    * **JSON 查询 (MySQL):** 使用 `user$.name` 语法。
* **5.1 写法:**
    * **数组查询:** 针对多字段的数组查询语法改变。5.0 的写法必须调整为 `where([['name','like','think%'], ['id','>',0]])`。 (注意：纯等于的条件如 `where(['name'=>'think'])` 无需更改。)
    * **SQL 获取:** `select(false)` 用法被取消，应使用 `fetchSql()->select()` 替代。
    * **JSON 查询 (MySQL):** 语法从 `user$.name` 改为 `user->name`。

#### 10.3. 助手函数及其他

**1. Request (请求)**

* **5.0 写法:** 通过 `Request::instance()` 方法获取请求对象实例。
    * 例如: `Request::instance()->param('name');`
* **5.1 写法:** 不再需要 `instance()` 方法，可以直接调用 (应为通过 Facade)。

**2. Loader (加载器)**

* **5.0 写法:** (隐含) 使用 `Loader::import` 以及 `import` 和 `vendor` 助手函数。Loader 类包含 `controller`, `model`, `action`, `validate` 等方法。
* **5.1 写法:**
    * **废弃:** 废弃了 `Loader::import`、`import` 和 `vendor` 助手函数。推荐使用 PSR-4 自动加载，或使用 PHP 内置的 `include` / `require`。
    * **移出:** Loader 类的 `controller`, `model`, `action`, `validate` 方法被移至 `App` 类。

**3. 模板 (Template)**

* **5.0 写法:** (隐含) 变量输出 `{$var}` 默认不进行安全过滤。
* **5.1 写法:** 变量输出默认添加了 `htmlentities` 安全过滤。如果需要输出原始 HTML 内容，必须使用 `{$var|raw}` 方式。

### 11\. ThinkPHP 5.1 的中间件机制

中间件主要用于拦截或过滤应用的HTTP请求，并进行必要的业务处理。

#### 11.1. 定义中间件

可以通过命令行指令快速生成中间件

`php think make:middleware Check`

这个指令会 `application/http/middleware` 目录下面生成一个`Check`中间件。

```php
<?php

namespace app\http\middleware;

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

中间件的入口执行方法必须是`handle`方法，而且第一个参数是Request对象，第二个参数是一个闭包。

中间件`handle`方法的返回值必须是一个Response对象。

在这个中间件中我们判断当前请求的`name`参数等于`think`的时候进行重定向处理。否则，请求将进一步传递到应用中。要让请求继续传递到应用程序中，只需使用 `$request` 作为参数去调用回调函数 `$next` 。

在某些需求下，可以使用第三个参数传入额外的参数。

```php
<?php

namespace app\http\middleware;

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

#### 11.2. 前置/后置中间件

中间件是在请求具体的操作之前还是之后执行，完全取决于中间件的定义本身。

下面是一个**前置行为**的中间件

```php
<?php

namespace app\http\middleware;

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

namespace app\http\middleware;

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

来个比较实际的例子，我们需要判断当前浏览器环境是在微信或支付宝：

```php
namespace app\http\middleware;

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

然后在你的移动版的`module`里添加一个`middleware.php`文件
例如：`/path/application/mobile/middleware.php`

```php
return [
    app\http\middleware\InAppCheck::class,
];
```

然后在你的controller中可以通过`$this->request->InApp`获取相关的值

#### 11.3. 注册中间件

**路由中间件**

最常用的中间件注册方式是注册路由中间件

```php
Route::rule('hello/:name','hello')
	->middleware('Auth');
```

或者使用完整的中间件类名

```php
Route::rule('hello/:name','hello')
	->middleware(app\http\middleware\Auth::class);
```

支持注册多个中间件

```php
Route::rule('hello/:name','hello')
	->middleware(['Auth', 'Check']);
```

V5.1.7+版本，你可以直接在应用配置目录下的`middleware.php`中先预定义中间件（其实就是增加别名标识），例如：

```php
return [
	'auth'	=>	app\http\middleware\Auth::class,
    'check'	=>	app\http\middleware\Check::class
];
```

然后直接在路由中使用中间件别名注册

```php
Route::rule('hello/:name','hello')
	->middleware(['auth', 'check']);
```

V5.1.8+版本开始，可以支持使用别名定义一组中间件，例如：

```php
return [
	'check'	=>	[
    	app\http\middleware\Auth::class,
   		app\http\middleware\Check::class
    ],
];
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
})->middleware('Auth');
```

V5.1.8+版本开始支持对某个域名注册中间件

```php
Route::domain('admin', function(){
	// 注册域名下的路由规则
})->middleware('Auth');
```

如果需要传入额外参数给中间件，可以使用

```php
Route::rule('hello/:name','hello')
	->middleware('Auth:admin');
```

如果使用的是常量方式定义，可以在第二个参数传入中间件参数。

```php
Route::rule('hello/:name','hello')
	->middleware(Auth::class, 'admin');
```

如果需要定义多个中间件，使用数组方式

```php
Route::rule('hello/:name','hello')
	->middleware([Auth::class, 'Check']);
```

可以统一传入同一个额外参数

```php
Route::rule('hello/:name','hello')
	->middleware([Auth::class, 'Check'], 'admin');
```

或者单独指定中间件参数。

```php
Route::rule('hello/:name','hello')
	->middleware(['Auth:admin', 'Check:editor']);
```

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

**全局中间件**

你可以在应用目录下面定义`middleware.php`文件，使用下面的方式：

```php
<?php
return [
	\app\http\middleware\Auth::class,
    'Check',
    'Hello',
];
```

中间件的注册应该使用完整的类名，如果没有指定命名空间则使用`app\http\middleware`作为命名空间。
全局中间件的执行顺序就是定义顺序。可以在定义全局中间件的时候传入中间件参数，支持两种方式传入。

```php
<?php
return [
	[\app\http\middleware\Auth::class, 'admin'],
    'Check',
    'Hello:thinkphp',
];
```

上面的定义表示 给Auth中间件传入`admin`参数，给Hello中间件传入`thinkphp`参数。

**模块中间件**

V5.1.8+版本开始，支持模块中间件定义，你可以直接在模块目录下面增加`middleware.php`文件，定义方式和应用中间件定义一样，只是只会在该模块下面生效。

**控制器中间件**

V5.1.17+版本开始，支持为控制器定义中间件。首先你的控制器需要继承系统的`think\Controller`类，然后在控制器中定义`middleware`属性，例如：

```php
<?php
namespace app\index\controller;
use think\Controller;
class Index extends Controller
{
    protected $middleware = ['Auth'];
    
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

当执行`index`控制器的时候就会调用`Auth`中间件，一样支持使用完整的命名空间定义。
如果需要设置控制器中间的生效操作，可以如下定义：

```php
<?php
namespace app\index\controller;
use think\Controller;
class Index extends Controller
{
    protected $middleware = [ 
    	'Auth' 	=> ['except' 	=> ['hello'] ],
        'Hello' => ['only' 		=> ['hello'] ],
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

#### 11.4. 中间件向控制器传参

可以通过给请求对象赋值的方式传参给控制器（或者其它地方），例如：

```php
<?php
namespace app\http\middleware;
class Hello
{
    public function handle($request, \Closure $next)
    {
        $request->hello = 'ThinkPHP';
        return $next($request);
    }
}
```

> **注意：** \<i\>（原文中此部分与 11.3 控制器中间件部分重复，此处保留一份）\</i\> 传递的变量名称不要和param变量有冲突。

然后在控制器的方法里面可以直接使用：

```php
public function index(Request $request)
{
	return $request->hello; // ThinkPHP
}
```