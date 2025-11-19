<?php
namespace app\index\controller;

use think\Db;
use think\Request;

class Test {
    public function index(Request $request)
    {
        $order = $request->param('order');
        $data = Db::table('user')->order($order)->select();
        dump($data);
    }
}