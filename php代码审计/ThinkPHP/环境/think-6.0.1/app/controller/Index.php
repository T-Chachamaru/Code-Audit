<?php
namespace app\controller;

use app\BaseController;
use think\facade\Request;
use think\facade\Session;

class Index extends BaseController
{
    public function index()
    {
        $username = Request::get('name');
        Session::set('username', $username);
        return 'hello,' . $username;
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }
}