<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
        $username = request()->get('username/a');
        db('user')->where(['id' => 1])->update(['username' => $username]);
        return 'hello world';
    }

}
