## ThinkPHP 3.2

### 1. 标准目录结构

```text
www  WEB部署目录（或者子目录）
├─index.php       入口文件
├─README.md       README文件
├─Application     应用目录
├─Public          资源文件目录
└─ThinkPHP        框架目录
```

### 2\. 框架目录结构

```text
├─ThinkPHP 框架系统目录（可以部署在非web目录下面）
│  ├─Common       核心公共函数目录
│  ├─Conf         核心配置目录 
│  ├─Lang         核心语言包目录
│  ├─Library      框架类库目录
│  │  ├─Think     核心Think类库包目录
│  │  ├─Behavior  行为类库目录
│  │  ├─Org       Org类库包目录
│  │  ├─Vendor    第三方类库目录
│  │  ├─ ...      更多类库目录
│  ├─Mode         框架应用模式目录
│  ├─Tpl          系统模板目录
│  ├─LICENSE.txt  框架授权协议文件
│  ├─logo.png     框架LOGO文件
│  ├─README.txt   框架README文件
│  └─ThinkPHP.php 框架入口文件
```

### 3\. 入口文件

入口文件主要完成：

  * 定义框架路径、项目路径（可选）
  * 定义调试模式和应用模式（可选）
  * 定义系统相关常量（可选）
  * 载入框架入口文件（必须）

默认情况下，框架已经自带了一个应用入口文件（以及默认的目录结构），内容如下：

```php
define('APP_PATH','./Application/');
require './ThinkPHP/ThinkPHP.php';
```

如果你改变了项目目录（例如把Application更改为Apps），只需要在入口文件更改`APP_PATH`常量定义即可：

```php
define('APP_PATH','./Apps/');
require './ThinkPHP/ThinkPHP.php';
```

#### 入口文件中的其他定义

一般不建议在入口文件中做过多的操作，但可以重新定义一些系统常量，入口文件中支持定义（建议）的一些系统常量包括：

| 常量 | 描述 |
| :--- | :--- |
| `THINK_PATH` | 框架目录 |
| `APP_PATH` | 应用目录 |
| `RUNTIME_PATH` | 应用运行时目录（可写） |
| `APP_DEBUG` | 应用调试模式 （默认为false） |
| `STORAGE_TYPE` | 存储类型（默认为File） |
| `APP_MODE` | 应用模式（默认为common） |

例如，我们可以在入口文件中重新定义相关目录并且开启调试模式：

```php
// 定义应用目录
define('APP_PATH','./Apps/');
// 定义运行时目录
define('RUNTIME_PATH','./Runtime/');
// 开启调试模式
define('APP_DEBUG',True);
// 更名框架目录名称，并载入框架入口文件
require './Think/ThinkPHP.php';
```

### 4\. 控制器

我们可以在自动生成的`Application/Home/Controller`目录下面找到一个 `IndexController.class.php` 文件，这就是默认的Index控制器文件。

控制器类的命名方式是：控制器名（驼峰法，首字母大写）+`Controller`
控制器文件的命名方式是：类名+`class.php`（类文件后缀）

默认的欢迎页面其实就是访问的Home模块下面的Index控制器类的index操作方法 我们修改默认的index操作方法如下：

```php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        echo 'hello,world!';
    }
}
```

再次运行应用入口文件，浏览器会显示：hello,world\!。

我们再来看下控制器类，`IndexController`控制器类的开头是命名空间定义：

```php
namespace Home\Controller;
```

这是系统的规范要求，表示当前类是Home模块下的控制器类，命名空间和实际的控制器文件所在的路径是一致的，也就是说： `Home\Controller\IndexController`类 对应的控制器文件位于应用目录下面的 `Home/Controller/IndexController.class.php`，如果你改变了当前的模块名，那么这个控制器类的命名空间也需要随之修改。

### 5\. URL 模式

入口文件是应用的单一入口，对应用的所有请求都定向到应用入口文件，系统会从URL参数中解析当前请求的模块、控制器和操作：

`http://serverName/index.php/模块/控制器/操作`

这是3.2版本的标准URL格式。

可以通过设置模块绑定或者域名部署等方式简化URL地址中的模块及控制器名称。

如果我们直接访问入口文件的话，由于URL中没有模块、控制器和操作，因此系统会访问默认模块（Home）下面的默认控制器（Index）的默认操作（index），因此下面的访问是等效的：

`http://serverName/index.php`
`http://serverName/index.php/Home/Index/index`

这种URL模式就是系统默认的PATHINFO模式，不同的URL模式获取模块和操作的方法不同，ThinkPHP支持的URL模式有四种：普通模式、PATHINFO、REWRITE和兼容模式，可以设置`URL_MODEL`参数改变URL模式。

#### 普通模式

普通模式也就是传统的GET传参方式来指定当前访问的模块和操作，例如： `http://localhost/?m=home&c=user&a=login&var=value`

m参数表示模块，c参数表示控制器，a参数表示操作（当然这些参数都是可以配置的），后面的表示其他GET参数。

如果默认的变量设置和你的应用变量有冲突的话，你需要重新设置系统配置，例如改成下面的：

```php
'VAR_MODULE'            =>  'module',     // 默认模块获取变量
'VAR_CONTROLLER'        =>  'controller',    // 默认控制器获取变量
'VAR_ACTION'            =>  'action',    // 默认操作获取变量
```

上面的访问地址则变成： `http://localhost/?module=home&controller=user&action=login&var=value`

#### PATHINFO 模式

PATHINFO模式是系统的默认URL模式，提供了最好的SEO支持，系统内部已经做了环境的兼容处理，所以能够支持大多数的主机环境。对应上面的URL模式，PATHINFO模式下面的URL访问地址是： `http://localhost/index.php/home/user/login/var/value/`

PATHINFO地址的前三个参数分别表示模块/控制器/操作。

不过，PATHINFO模式下面，依然可以采用普通URL模式的参数方式，例如： `http://localhost/index.php/home/user/login?var=value` 依然是有效的

PATHINFO模式下面，URL是可定制的，例如，通过下面的配置：

```php
// 更改PATHINFO参数分隔符
'URL_PATHINFO_DEPR'=>'-',
```

我们还可以支持下面的URL访问： `http://localhost/index.php/home-user-login-var-value`

#### REWRITE 模式

REWRITE模式是在PATHINFO模式的基础上添加了重写规则的支持，可以去掉URL地址里面的入口文件index.php，但是需要额外配置WEB服务器的重写规则。

如果是Apache则需要在入口文件的同级添加`.htaccess`文件，内容如下：

```apache
<IfModule mod_rewrite.c>
 RewriteEngine on
 RewriteCond %{REQUEST_FILENAME} !-d
 RewriteCond %{REQUEST_FILENAME} !-f
 RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
</IfModule>
```

接下来，就可以用下面的URL地址访问了： `http://localhost/home/user/login/var/value`

#### 兼容模式

兼容模式是用于不支持PATHINFO的特殊环境，URL地址是： `http://localhost/?s=/home/user/login/var/value`

可以更改兼容模式变量的名称定义，例如：

```php
'VAR_PATHINFO'          =>  'path'
```

PATHINFO参数分隔符对兼容模式依然有效，例如：

```php
// 更改PATHINFO参数分隔符
'URL_PATHINFO_DEPR'=>'-',
```

使用以上配置的话，URL访问地址可以变成： `http://localhost/?path=/home-user-login-var-value`

兼容模式配合Web服务器重写规则的定义，可以达到和REWRITE模式一样的URL效果。

### 6\. 启用路由

要使用路由功能，前提是你的URL支持PATH\_INFO（或者兼容URL模式也可以，采用普通URL模式的情况下不支持路由功能），并且在应用（或者模块）配置文件中开启路由：

```php
// 开启路由
'URL_ROUTER_ON'  
  => true,
```

路由功能可以针对模块，也可以针对全局，针对模块的路由则需要在模块配置文件中开启和设置路由，如果是针对全局的路由，则是在公共模块的配置文件中开启和设置（后面我们以模块路由定义为例）。

然后就是配置路由规则了，在模块的配置文件中使用`URL_ROUTE_RULES`参数进行配置，配置格式是一个数组，每个元素都代表一个路由规则，例如：

```php
'URL_ROUTE_RULES'=>array(
    'news/:year/:month/:day' => array('News/archive', 'status=1'),
    'news/:id'               => 'News/read',
    'news/read/:id'          => '/news/:1',
),
```

系统会按定义的顺序依次匹配路由规则，一旦匹配到的话，就会定位到路由定义中的控制器和操作方法去执行（可以传入其他的参数），并且后面的规则不会继续匹配。

### 7\. 操作绑定到类

ThinkPHP3.2版本提供了把每个操作方法定位到一个类的功能，可以让你的开发工作更细化，可以设置参数`ACTION_BIND_CLASS`，例如：

```php
'ACTION_BIND_CLASS'    =>    True,
```

设置后，我们的控制器定义有所改变，以URL访问为 `http://serverName/Home/Index/index`为例，原来的控制器文件定义位置为：

`Application/Home/Controller/IndexController.class.php`

控制器类的定义如下：

```php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller{
    public function index(){
        echo '执行Index控制器的index操作';
    }
}
```

可以看到，实际上我们调用的是 `Home\Controller\IndexController` 类的`index`方法。

设置后，控制器文件位置改为：

`Application/Home/Controller/Index/index.class.php`

控制器类的定义如下：

```php
namespace Home\Controller\Index;
use Think\Controller;
class index extends Controller{
    public function run(){
        echo '执行Index控制器的index操作';
    }
}
```

现在，我们调用的其实是 `Home\Controller\Index\index` 类的`run`方法。

run方法依旧可以支持传入参数和进行Action参数绑定操作，但不再支持A方法实例化和R方法远程调用，我们建议R方法不要进行当前访问控制器的远程调用。

### 8\. 重定向

Controller类的`redirect`方法可以实现页面的重定向功能。

`redirect`方法的参数用法和U函数的用法一致（参考URL生成部分），例如：

```php
//重定向到New模块的Category操作
$this->redirect('New/category', array('cate_id' => 2), 5, '页面跳转中...');
```

上面的用法是停留5秒后跳转到New模块的category操作，并且显示页面跳转中字样，重定向后会改变当前的URL地址。

如果你仅仅是想重定向要一个指定的URL地址，而不是到某个模块的操作方法，可以直接使用`redirect`函数重定向，例如：

```php
//重定向到指定的URL地址
redirect('/New/category/cate_id/2', 5, '页面跳转中...');
```

`Redirect`函数的第一个参数是一个URL地址。

控制器的`redirect`方法和`redirect`函数的区别在于前者是用URL规则定义跳转地址，后者是一个纯粹的URL地址。

### 9\. 获取变量

`I`方法是ThinkPHP用于更加方便和安全的获取系统输入变量，可以用于任何地方，用法格式如下：

`I('变量类型.变量名/修饰符',['默认值'],['过滤方法或正则'],['额外数据源'])`

我们以GET变量类型为例，说明下`I`方法的使用：

```php
echo I('get.id'); // 相当于 $_GET['id']
echo I('get.name'); // 相当于 $_GET['name']
```

支持默认值：

```php
echo I('get.id',0); // 如果不存在$_GET['id'] 则返回0
echo I('get.name',''); // 如果不存在$_GET['name'] 则返回空字符串
```

采用方法过滤：

```php
// 采用htmlspecialchars方法对$_GET['name'] 进行过滤，如果不存在则返回空字符串
echo I('get.name','','htmlspecialchars');
```

支持直接获取整个变量类型，例如：

```php
// 获取整个$_GET 数组
I('get.');
```

用同样的方式，我们可以获取post或者其他输入类型的变量，例如：

```php
I('post.name','','htmlspecialchars'); // 采用htmlspecialchars方法对$_POST['name'] 进行过滤，如果不存在则返回空字符串
I('session.user_id',0); // 获取$_SESSION['user_id'] 如果不存在则默认为0
I('cookie.'); // 获取整个 $_COOKIE 数组
I('server.REQUEST_METHOD'); // 获取 $_SERVER['REQUEST_METHOD']
```

`param`变量类型是框架特有的支持自动判断当前请求类型的变量获取方式，例如：

```php
echo I('param.id');
```

如果当前请求类型是GET，那么等效于 `$_GET['id']`，如果当前请求类型是POST或者PUT，那么相当于获取 `$_POST['id']` 或者 PUT参数id。

由于`param`类型是`I`函数默认获取的变量类型，因此事实上`param`变量类型的写法可以简化为：

```php
I('id'); // 等同于 I('param.id')
I('name'); // 等同于 I('param.name')
```

`path`类型变量可以用于获取URL参数（必须是PATHINFO模式参数有效，无论是GET还是POST方式都有效），例如：
当前访问URL地址是 `http://serverName/index.php/New/2013/06/01`

那么我们可以通过

```php
echo I('path.1'); // 输出2013
echo I('path.2'); // 输出06
echo I('path.3'); // 输出01
```

`data`类型变量可以用于获取不支持的变量类型的读取，例如：

```php
I('data.file1','','',$_FILES);
```

#### 变量过滤

如果你没有在调用`I`函数的时候指定过滤方法的话，系统会采用默认的过滤机制（由`DEFAULT_FILTER`配置），事实上，该参数的默认设置是：

```php
// 系统默认的变量过滤机制
'DEFAULT_FILTER'        => 'htmlspecialchars'
```

也就说，`I`方法的所有获取变量如果没有设置过滤方法的话都会进行`htmlspecialchars`过滤，那么：

```php
// 等同于 htmlspecialchars($_GET['name'])
I('get.name');
```

同样，该参数也可以设置支持多个过滤，例如：

```php
'DEFAULT_FILTER'        => 'strip_tags,htmlspecialchars'
```

设置后，我们在使用：

```php
// 等同于 htmlspecialchars(strip_tags($_GET['name']))
I('get.name');
```

如果我们在使用`I`方法的时候 指定了过滤方法，那么就会忽略`DEFAULT_FILTER`的设置，例如：

```php
// 等同于 strip_tags($_GET['name'])
echo I('get.name','','strip_tags');
```

`I`方法的第三个参数如果传入函数名，则表示调用该函数对变量进行过滤并返回（在变量是数组的情况下自动使用`array_map`进行过滤处理），否则会调用PHP内置的`filter_var`方法进行过滤处理，例如：

```php
I('post.email','',FILTER_VALIDATE_EMAIL);
```

表示 会对`$_POST['email']` 进行 格式验证，如果不符合要求的话，返回空字符串。 （关于更多的验证格式，可以参考 官方手册的`filter_var`用法。）
或者可以用下面的字符标识方式：

```php
I('post.email','','email');
```

可以支持的过滤名称必须是`filter_list`方法中的有效值（不同的服务器环境可能有所不同），可能支持的包括：

  * int
  * boolean
  * float
  * validate\_regexp
  * validate\_url
  * validate\_email
  * validate\_ip
  * string
  * stripped
  * encoded
  * special\_chars
  * unsafe\_raw
  * email
  * url
  * number\_int
  * number\_float
  * magic\_quotes
  * callback

还可以支持进行正则匹配过滤，例如：

```php
// 采用正则表达式进行变量过滤
I('get.name','','/^[A-Za-z]+$/');
I('get.id',0,'/^\d+$/');
```

如果正则匹配不通过的话，则返回默认值。

在有些特殊的情况下，我们不希望进行任何过滤，即使`DEFAULT_FILTER`已经有所设置，可以使用：

```php
// 下面两种方式都不采用任何过滤方法
I('get.name','','');
I('get.id','',false);
```

一旦过滤参数设置为空字符串或者false，即表示不再进行任何的过滤。

#### 变量修饰符

最新版本的`I`函数支持对变量使用修饰符功能，可以更方便的通过类型过滤变量。

用法如下：
`I('变量类型.变量名/修饰符')`

例如：

```php
I('get.id/d'); // 强制变量转换为整型
I('post.name/s'); // 强制转换变量为字符串类型
I('post.ids/a'); // 强制变量转换为数组类型
```

### 10\. Cookie 设置

```php
cookie('name','value'); //设置cookie
cookie('name','value',3600); // 指定cookie保存时间
```

还可以支持参数传入的方式完成复杂的cookie赋值，下面是对cookie的值设置3600秒有效期，并且加上cookie前缀`think_`

```php
cookie('name','value',array('expire'=>3600,'prefix'=>'think_'))
```

数组参数可以采用query形式参数

```php
cookie('name','value','expire=3600&prefix=think_')
```

和上面的用法等效。

后面的参数支持`prefix`,`expire`,`path`,`domain`和`httponly`（**3.2.2版本新增**）五个索引参数，如果没有传入或者传入空值的话，会默认取`COOKIE_PREFIX`、`COOKIE_EXPIRE`、`COOKIE_PATH`、`COOKIE_DOMAIN`和`COOKIE_HTTPONLY`五个配置参数。如果只传入个别参数，那么也会和默认的配置参数合并。

支持给cookie设置数组值（采用JSON编码格式保存），例如：

```php
cookie('name',array('value1','value2'));
```

#### Cookie 获取

获取cookie很简单，无论是怎么设置的cookie，只需要使用：

```php
$value = cookie('name');
```

如果没有设置cookie前缀的话 相当于
`$value = $_COOKIE['name'];`

如果设置了cookie前缀的话，相当于
`$value = $_COOKIE['前缀+name'];`

如果要获取所有的cookie，可以使用：

```php
$value = cookie();
```

该用法相当于
`$value = $_COOKIE;`

注意，该用法会返回所有的cookie而无论是否当前的前缀。

#### Cookie 删除

删除某个cookie的值，使用：

```php
cookie('name',null);
```

要删除所有的Cookie值，可以使用：

```php
cookie(null); // 清空当前设定前缀的所有cookie值
cookie(null,'think_'); //  清空指定前缀的所有cookie值
```

### 11\. 上传操作

ThinkPHP文件上传操作使用`Think\Upload`类。假设前面的表单提交到当前控制器的upload方法，我们来看下upload方法的实现代码：

```php
public function upload(){
    $upload = new \Think\Upload();// 实例化上传类
    $upload->maxSize   =     3145728 ;// 设置附件上传大小
    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
    $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
    $upload->savePath  =     ''; // 设置附件上传（子）目录
    // 上传文件 
    $info   =   $upload->upload();
    if(!$info) {// 上传错误提示错误信息
        $this->error($upload->getError());
    }else{// 上传成功
        $this->success('上传成功！');
    }
}
```

上传类对图片文件的上传安全做了支持，如果企图上传非法的图像文件，系统会提示 非法图像文件。 为了更好的使用上传功能，建议你的服务器开启`finfo`模块支持。

#### 上传参数

在上传操作之前，我们可以对上传的属性进行一些设置。Upload类支持的属性设置包括：

| 属性 | 描述 |
| :--- | :--- |
| `maxSize` | 文件上传的最大文件大小（以字节为单位），0为不限大小 |
| `rootPath` | 文件上传保存的根路径 |
| `savePath` | 文件上传的保存路径（相对于根路径） |
| `saveName` | 上传文件的保存规则，支持数组和字符串方式定义 |
| `saveExt` | 上传文件的保存后缀，不设置的话使用原文件后缀 |
| `replace` | 存在同名文件是否是覆盖，默认为false |
| `exts` | 允许上传的文件后缀（留空为不限制），使用数组或者逗号分隔的字符串设置，默认为空 |
| `mimes` | 允许上传的文件类型（留空为不限制），使用数组或者逗号分隔的字符串设置，默认为空 |
| `autoSub` | 自动使用子目录保存上传文件 默认为true |
| `subName` | 子目录创建方式，采用数组或者字符串方式定义 |
| `hash` | 是否生成文件的hash编码 默认为true |
| `callback` | 检测文件是否存在回调，如果存在返回文件信息数组 |

上面的属性可以通过两种方式传入：

**1. 实例化传入**

我们可以在实例化的时候直接传入参数数组，例如：

```php
$config = array(
    'maxSize'    =>    3145728,
    'rootPath'   =>    './Uploads/',
    'savePath'   =>    '',
    'saveName'   =>    array('uniqid',''),
    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
    'autoSub'    =>    true,
    'subName'    =>    array('date','Ymd'),
);
$upload = new \Think\Upload($config);// 实例化上传类
```

关于`saveName`和`subName`的使用后面我们会有详细的描述。

**2. 动态赋值**

支持在实例化后动态赋值上传参数，例如：

```php
$upload = new \Think\Upload();// 实例化上传类
$upload->maxSize = 3145728;
$upload->rootPath = './Uploads/';
$upload->savePath = '';
$upload->saveName = array('uniqid','');
$upload->exts     = array('jpg', 'gif', 'png', 'jpeg');
$upload->autoSub  = true;
$upload->subName  = array('date','Ymd');
```

上面的设置和实例化传入的效果是一致的。

#### 上传文件信息

设置好上传的参数后，就可以调用`Think\Upload`类的`upload`方法进行附件上传，如果失败，返回false，并且用`getError`方法获取错误提示信息；如果上传成功，就返回成功上传的文件信息数组。

```php
$upload = new \Think\Upload();// 实例化上传类
$upload->maxSize   =     3145728 ;// 设置附件上传大小
$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
$upload->savePath  =      ''; // 设置附件上传（子）目录
// 上传文件 
$info   =   $upload->upload();
if(!$info) {// 上传错误提示错误信息
    $this->error($upload->getError());
}else{// 上传成功 获取上传文件信息
    foreach($info as $file){
        echo $file['savepath'].$file['savename'];
    }
}
```

每个文件信息又是一个记录了下面信息的数组，包括：

| 属性 | 描述 |
| :--- | :--- |
| `key` | 附件上传的表单名称 |
| `savepath` | 上传文件的保存路径 |
| `name` | 上传文件的原始名称 |
| `savename` | 上传文件的保存名称 |
| `size` | 上传文件的大小 |
| `type` | 上传文件的MIME类型 |
| `ext` | 上传文件的后缀类型 |
| `md5` | 上传文件的md5哈希验证字符串 仅当hash设置开启后有效 |
| `sha1` | 上传文件的sha1哈希验证字符串 仅当hash设置开启后有效 |

文件上传成功后，就可以使用这些文件信息来进行其他的数据操作，例如保存到当前数据表或者单独的附件数据表。

例如，下面表示把上传信息保存到数据表的字段：

```php
$model = M('Photo');
// 取得成功上传的文件信息
$info = $upload->upload();
// 保存当前数据对象
$data['photo'] = $info['photo']['savename'];
$data['create_time'] = NOW_TIME;
$model->add($data);
```

#### 单文件上传

`upload`方法支持多文件上传，有时候，我们只需要上传一个文件，就可以使用Upload类提供的`uploadOne`方法上传单个文件，例如：

```php
public function upload(){
    $upload = new \Think\Upload();// 实例化上传类
    $upload->maxSize   =     3145728 ;// 设置附件上传大小
    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
    $upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
    // 上传单个文件 
    $info   =   $upload->uploadOne($_FILES['photo1']);
    if(!$info) {// 上传错误提示错误信息
        $this->error($upload->getError());
    }else{// 上传成功 获取上传文件信息
         echo $info['savepath'].$info['savename'];
    }
}
```

`uploadOne`方法上传成功后返回的文件信息和`upload`方法的区别是只有单个文件信息的一维数组。

#### 上传文件的命名规则

上传文件的命名规则（`saveName`）用于确保文件不会产生冲突或者覆盖的情况。命名规则的定义可以根据你的业务逻辑来调整，不是固定的。例如，如果你采用时间戳的方式来定义命名规范，那么在同时上传多个文件的时候可能产生冲突（因为同一秒内可以上传多个文件），因此你需要根据你的业务需求来设置合适的上传命名规则。这里顺便来说下`saveName`参数的具体用法。

**1. 采用函数方式**

如果传入的字符串是一个函数名，那么表示采用函数动态生成上传文件名（不包括文件后缀），例如：

```php
// 采用时间戳命名
$upload->saveName = 'time';
// 采用GUID序列命名
$upload->saveName = 'com_create_guid';
```

也可以采用用户自定义函数

```php
// 采用自定义函数命名
$upload->saveName = 'myfun';
```

默认的命名规则设置是采用`uniqid`函数生成一个唯一的字符串序列。

`saveName`的值支持数组和字符串两种方式，如果是只有一个参数或者没有参数的函数，直接使用字符串设置即可，如果需要传入额外的参数，可以使用数组方式，例如：

```php
// 采用date函数生成命名规则 传入Y-m-d参数
$upload->saveName = array('date','Y-m-d');
// 如果有多个参数需要传入的话 可以使用数组
$upload->saveName = array('myFun',array('__FILE__','val1','val2'));
```

如果需要使用上传的原始文件名，可以采用`__FILE__`传入，所以上面的定义规则，最终的结果是 `myFun('上传文件名','val1','val2')`执行的结果。

**2. 直接设置上传文件名**

如果传入的参数不是一个函数名，那么就会直接当做是上传文件名，例如：

```php
$upload->saveName = time().'_'.mt_rand();
```

表示上传的文件命名采用时间戳加一个随机数的组合字符串方式。

当然，如果觉得有必要，你还可以固定设置一个上传文件的命名规则，用于固定保存某个上传文件。

```php
$upload->saveName = 'ThinkPHP';
```

**3. 保持上传文件名不变**

如果你想保持上传的文件名不变，那么只需要设置命名规范为空即可，例如：

```php
$upload->saveName = '';
```

一般来说不建议保持不变，因为会导致相同的文件名上传后被覆盖的情况。

#### 子目录保存

`saveName`只是用于设置文件的保存规则，不涉及到目录，如果希望对上传的文件分子目录保存，可以设置`autoSub`和`subName`参数来完成，例如：

```php
// 开启子目录保存 并以日期（格式为Ymd）为子目录
$upload->autoSub = true;
$upload->subName = array('date','Ymd');
```

可以使用自定义函数来保存，例如：

```php
// 开启子目录保存 并调用自定义函数get_user_id生成子目录
$upload->autoSub = true;
$upload->subName = 'get_user_id';
```

和`saveName`参数一样，`subName`的定义可以采用数组和字符串的方式。

注意：如果`get_user_id`函数未定义的话，会直接以`get_user_id`字符串作为子目录的名称保存。

子目录保存和文件命名规则可以结合使用。

#### 上传驱动

上传类可以支持不同的环境，通过相应的上传驱动来解决，默认情况下使用本地（Local）上传驱动，当然，你还可以设置当前默认的上传驱动类型，例如：

```php
'FILE_UPLOAD_TYPE'    =>    'Ftp',
'UPLOAD_TYPE_CONFIG'  =>    array(        
        'host'     => '192.168.1.200', //服务器
        'port'     => 21, //端口
        'timeout'  => 90, //超时时间
        'username' => 'ftp_user', //用户名
        'password' => 'ftp_pwd', //密码 ),
```

表示当前使用Ftp作为上传类的驱动，上传的文件会通过FTP传到指定的远程服务器。

也可以在实例化上传类的时候指定，例如：

```php
$config = array(
    'maxSize'    =    3145728,
    'rootPath'   =    './Uploads/',
    'savePath'   =    '',
    'saveName'   =    array('uniqid',''),
    'exts'       =    array('jpg', 'gif', 'png', 'jpeg'),
    'autoSub'    =    true,
    'subName'    =    array('date','Ymd'),
);
$ftpConfig     =    array(        
        'host'     => '192.168.1.200', //服务器
        'port'     => 21, //端口
        'timeout'  => 90, //超时时间
        'username' => 'ftp_user', //用户名
        'password' => 'ftp_pwd', //密码
);
$upload = new \Think\Upload($config,'Ftp',$ftpConfig);// 实例化上传类
```

### 12\. 查询方式

ThinkPHP可以支持直接使用字符串作为查询条件，但是大多数情况推荐使用数组或者对象来作为查询条件，因为会更加安全。

#### 使用字符串作为查询条件

这是最传统的方式，但是安全性不高，例如：

```php
$User = M("User"); // 实例化User对象
$User->where('type=1 AND status=1')->select();
```

最后生成的SQL语句是：
`SELECT * FROM think_user WHERE type=1 AND status=1`

采用字符串查询的时候，我们可以配合使用字符串条件的安全预处理机制。

#### 使用数组作为查询条件

这种方式是最常用的查询方式，例如：

```php
$User = M("User"); // 实例化User对象
$condition['name'] = 'thinkphp';
$condition['status'] = 1;
// 把查询条件传入查询方法
$User->where($condition)->select();
```

最后生成的SQL语句是：
` SELECT * FROM think_user WHERE  `name`='thinkphp' AND status=1`

如果进行多字段查询，那么字段之间的默认逻辑关系是 逻辑与 `AND`，但是用下面的规则可以更改默认的逻辑判断，通过使用 `_logic` 定义查询逻辑：

```php
$User = M("User"); // 实例化User对象
$condition['name'] = 'thinkphp';
$condition['account'] = 'thinkphp';
$condition['_logic'] = 'OR';
// 把查询条件传入查询方法
$User->where($condition)->select();
```

最后生成的SQL语句是：
` SELECT * FROM think_user WHERE  `name` ='thinkphp' OR  `account`='thinkphp'`

#### 使用对象方式来查询

这里以`stdClass`内置对象为例：

```php
$User = M("User"); // 实例化User对象
// 定义查询条件
$condition = new stdClass(); 
$condition->name = 'thinkphp'; 
$condition->status= 1; 
$User->where($condition)->select();
```

最后生成的SQL语句和上面一样：
` SELECT * FROM think_user WHERE  `name`='thinkphp' AND status=1`

使用对象方式查询和使用数组查询的效果是相同的，并且是可以互换的，大多数情况下，我们建议采用数组方式更加高效。

在使用数组和对象方式查询的时候，如果传入了不存在的查询字段是会被自动过滤的，例如：

```php
$User = M("User"); // 实例化User对象
$condition['name'] = 'thinkphp';
$condition['status'] = 1;
$condition['test'] = 'test';
// 把查询条件传入查询方法
$User->where($condition)->select();
```

因为数据库的test字段是不存在的，所以系统会自动检测并过滤掉`$condition['test'] = 'test'`这一查询条件。
如果是3.2.2版本以上，当开启调试模式的话，则会抛出异常，显示：错误的查询条件。

### 13\. 原生 SQL 查询

ThinkPHP内置的ORM和ActiveRecord模式实现了方便的数据存取操作，而且新版增加的连贯操作功能更是让这个数据操作更加清晰，但是ThinkPHP仍然保留了原生的SQL查询和执行操作支持，为了满足复杂查询的需要和一些特殊的数据操作，SQL查询的返回值因为是直接返回的Db类的查询结果，没有做任何的处理。

主要包括下面两个方法：

#### QUERY 方法

`query`方法用于执行SQL查询操作，如果数据非法或者查询错误则返回false，否则返回查询结果数据集（同select方法）。

使用示例：

```php
$Model = new \Think\Model() // 实例化一个model对象 没有对应任何数据表
$Model->query("select * from think_user where status=1");
```

如果你当前采用了分布式数据库，并且设置了读写分离的话，`query`方法始终是在读服务器执行，因此`query`方法对应的都是读操作，而不管你的SQL语句是什么。

可以在`query`方法中使用表名的简化写法，便于动态更改表前缀，例如：

```php
$Model = new \Think\Model() // 实例化一个model对象 没有对应任何数据表
$Model->query("select * from __PREFIX__user where status=1");
// 3.2.2版本以上还可以直接使用
$Model->query("select * from __USER__ where status=1");
```

和上面的写法等效，会自动读取当前设置的表前缀。

#### EXECUTE 方法

`execute`用于更新和写入数据的sql操作，如果数据非法或者查询错误则返回false ，否则返回影响的记录数。

使用示例：

```php
$Model = new \Think\Model() // 实例化一个model对象 没有对应任何数据表
$Model->execute("update think_user set name='thinkPHP' where status=1");
```

如果你当前采用了分布式数据库，并且设置了读写分离的话，`execute`方法始终是在写服务器执行，因此`execute`方法对应的都是写操作，而不管你的SQL语句是什么。

也可以在`execute`方法中使用表名的简化写法，便于动态更改表前缀，例如：

```php
$Model = new \Think\Model() // 实例化一个model对象 没有对应任何数据表
$Model->execute("update __PREFIX__user set name='thinkPHP' where status=1");
// 3.2.2版本以上还可以直接使用
$Model->execute("update __USER__ set name='thinkPHP' where status=1");
```

和上面的写法等效，会自动读取当前设置的表前缀。

### 14\. 查询条件预处理

`where`方法使用字符串条件的时候，支持预处理（安全过滤），并支持两种方式传入预处理参数，例如：

```php
$Model->where("id=%d and username='%s' and xx='%f'",array($id,$username,$xx))->select();
// 或者
$Model->where("id=%d and username='%s' and xx='%f'",$id,$username,$xx)->select();
```

模型的`query`和`execute`方法 同样支持预处理机制，例如：

```php
$model->query('select * from user where id=%d and status=%d',$id,$status);
//或者
$model->query('select * from user where id=%d and status=%d',array($id,$status));
```

`execute`方法用法同`query`方法。

### 15\. 输入过滤

#### 使用 I 函数过滤

使用系统内置的`I`函数是避免输入数据出现安全隐患的重要手段。`I`函数默认的过滤方法是`htmlspecialchars` [cite: 13]。如果我们需要采用其他的方法进行安全过滤，有两种方式：

1.  **全局过滤**
    如果是全局的过滤方法，那么可以设置`DEFAULT_FILTER`，例如：

    ```php
    'DEFAULT_FILTER'        =>  'strip_tags',
    ```

    设置了`DEFAULT_FILTER`后，所有的`I`函数调用默认都会使用`strip_tags`进行过滤。
    当然，我们也可以设置多个过滤方法，例如：

    ```php
    'DEFAULT_FILTER'        =>  'strip_tags,stripslashes',
    ```

2.  **即时过滤**
    如果是仅需要对个别数据采用特殊的过滤方法，可以在调用`I`函数的时候传入过滤方法，例如：

    ```php
    I('post.id',0,'intval'); // 用intval过滤$_POST['id']
    I('get.title','','strip_tags'); // 用strip_tags过滤$_GET['title']
    ```

要尽量避免直接使用`$_GET` `$_POST` `$_REQUEST` 等数据，这些可能会导致安全的隐患。 就算你要获取整个`$_GET`数据，我们也建议你使用 `I('get.')` 的方式。

#### 写入数据过滤

如果你没有使用`I`函数进行数据过滤的话，还可以在模型的写入操作之前调用`filter`方法对数据进行安全过滤，例如：

```php
$this->data($data)->filter('strip_tags')->add();
```

### 16\. 表单合法性检测

在处理表单提交的数据的时候，建议尽量采用`Think\Model`类提供的`create`方法首先进行数据创建，然后再写入数据库。

`create`方法在创建数据的同时，可以进行更为安全的处理操作，而且这一切让你的表单处理变得更简单。

使用`create`方法创建数据对象的时候，可以使用数据的合法性检测，支持两种方式：

#### 1\. 配置 `insertFields` 和 `updateFields` 属性

可以分别为新增和编辑表单设置`insertFields`和 `updateFields`属性。使用`create`方法创建数据对象的时候，不在定义范围内的属性将直接丢弃，避免表单提交非法数据。

`insertFields` 和 `updateFields` 属性的设置采用字符串（逗号分割多个字段）或者数组的方式。

设置的字段应该是实际的数据表字段，而不受字段映射的影响。例如：

```php
namespace Home\Model;
class UserModel extends \Think\Model{
    protected $insertFields = array('account','password','nickname','email');
    protected $updateFields = array('nickname','email');
 }
```

定义后，调用`add`方法写入用户数据的时候，只能写入'account','password','nickname','email'这几个字段，编辑的时候只能更新'nickname','email'两个字段。

在使用的时候，我们调用`create`方法的时候，会根据提交类型自动识别`insertFields`和`updateFields`属性：

```php
D('User')->create();
```

#### 2\. 直接调用 `field` 方法

如果不想定义`insertFields`和`updateFields`属性，可以在调用`create`方法之前直接调用`field`方法，例如，实现和上面的例子同样的作用：

在新增用户数据的时候，使用：

```php
M('User')->field('account,password,nickname,email')->create();
```

而在更新用户数据的时候，使用：

```php
M('User')->field('nickname,email')->create();
```

这里的字段也是实际的数据表字段。
`field`方法也可以使用数组方式。

使用字段合法性检测后，你不再需要担心用户在提交表单的时候注入非法字段数据了。

### 17\. 表单令牌验证

ThinkPHP支持表单令牌验证功能，可以有效防止表单的重复提交等安全防护。

要启用表单令牌功能，需要配置行为绑定，在应用或者模块的配置目录下面的行为定义文件`tags.php`中，添加：

```php
return array(
     // 添加下面一行定义即可
     'view_filter' => array('Behavior\TokenBuild'),
    // 如果是3.2.1以上版本 需要改成
    // 'view_filter' => array('Behavior\TokenBuildBehavior'),
);
```

表示在`view_filter`标签位置执行表单令牌检测行为。

表单令牌验证相关的配置参数有：

```php
'TOKEN_ON'      =>    true,  // 是否开启令牌验证 默认关闭
'TOKEN_NAME'    =>    '__hash__',    // 令牌验证的表单隐藏字段名称，默认为__hash__
'TOKEN_TYPE'    =>    'md5',  //令牌哈希验证规则 默认为MD5
'TOKEN_RESET'   =>    true,  //令牌验证出错后是否重置令牌 默认为true
```

如果开启表单令牌验证功能，系统会自动在带有表单的模板文件里面自动生成以`TOKEN_NAME`为名称的隐藏域，其值则是`TOKEN_TYPE`方式生成的哈希字符串，用于实现表单的自动令牌验证。

自动生成的隐藏域位于表单Form结束标志之前。如果希望自己控制隐藏域的位置，可以手动在表单页面添加`{__TOKEN__}`标识，系统会在输出模板的时候自动替换。

> **注意：** 如果页面中存在多个表单，建议添加标识，并确保只有一个表单需要令牌验证。

如果个别页面输出不希望进行表单令牌验证，可以在控制器中的输出方法之前动态关闭表单令牌验证，例如：

```php
C('TOKEN_ON',false);
$this->display();
```

模型类在创建数据对象的同时会自动进行表单令牌验证操作。如果你没有使用`create`方法创建数据对象的话，则需要手动调用模型的`autoCheckToken`方法进行表单令牌验证。如果返回false，则表示表单令牌验证错误。例如：

```php
$User = M("User"); // 实例化User对象
 // 手动进行令牌验证
 if (!$User->autoCheckToken($_POST)){
 // 令牌验证错误
 }
```

### 18\. 用户可控输入 (ThinkPHP 5 vs ThinkPHP 3)

#### ThinkPHP 5 框架

  * **`Request::instance()->get()` / `input('get.')`**

      * 获取用户传入的URL参数。可用过滤器和类型转换。
      * **例子**: 获取url参数中的id值
          * `Request::instance()->get('id');` (调用时如不传入参数默认获取全部 `Request::instance()->get();`)
          * `input('get.id');` (调用时如传入`get.`则获取全部 `input('get.');`)
          * `input('get.id/d');` // 强制变量转换为整型
          * `Request::instance()->get('name','','htmlspecialchars');` // 过滤器
          * `input('get.name/s');` // 强制转换变量为字符串
          * `input('get.ids/a');` // 强制变量转换为数组 默认为/s

  * **`Request::instance()->post()` / `input('post.')`**

      * 获取用户传入的POST参数。
      * **例子**: 获取post请求body中的name值
          * `Request::instance()->post('name');`
          * `input('post.name');` (用法同get)

  * **`Request::instance()->param()` / `input('param.')` / `input('')`**

      * 自动判断用户提交方法(POST GET PUT)获取参数。
      * 用法同get。
      * 可直接调用`input('');`获取全部参数，或使用`input('name');`获取单个参数。
      * 注: `input`方法默认获取`param`。

  * **`Request::instance()->request()` / `input('request.')`**

      * 用法同get，获取`$_REQUEST`变量。

  * **`Request::instance()->server()` / `input('server.')`**

      * 用法同get，获取`$_SERVER` 变量。

  * **`Request::instance()->cookie()` / `input('cookie.')` / `Cookie::get('name')` / `cookie('name')`**

      * 用法同get，获取`$_COOKIE` 变量。

  * **`Request::instance()->header()` / `input('header.')`**

      * 用法同get，获取用户传入的HTTP头。

  * **`Request::instance()->file()`**

      * 用法同get，获取`$_FILES` 变量。

  * **`request()` 助手函数**

      * 实例化request对象。
      * **例子**:
        ```php
        $req=request(); 
        // 相当于 $req=Request::instance()
        ```
      * 这种使用方法比较常见，还可以获取用户传入的请求信息。可将前面的`Request::instance()`直接替换成`request()`。
      * **例子**: `request()->post();`

  * **`Request::instance()` 其他用户变量**

      * [https://www.kancloud.cn/manual/thinkphp5/158834](https://www.kancloud.cn/manual/thinkphp5/158834) (见官方文档)

  * **模板中获取参数**

      * `{$Request.变量类型.变量名}`

#### ThinkPHP 3.\* 框架

  * **`I('变量类型.变量名/修饰符',['默认值'],['过滤方法或正则'])`**
      * 获取变量。
      * **例子**: `I('get.id');`
      * `I('get.');` (使用方法同input)

### 19. 路由传入值 (Action参数绑定 - ThinkPHP 框架)

通过路由传入

```php
namespace Home\Controller;
use Think\Controller;

class BlogController extends Controller
{
    public function read($id)
    { 
        echo 'id='.$id; 
    }
    
    public function archive($year='2013',$month='01')
    {
        echo 'year='.$year.'&month='.$month;
    }
}
```

**访问 URL 示例：**

  * `/index.php/Home/Blog/read/id/5`
  * `/index.php/Home/Blog/archive/year/2013/month/11`
  * `?c=Blog&a=read&id=5`
  * `?c=Blog&a=archive&year=2013&month=11`