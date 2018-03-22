<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2018/1/3
 * Time: 10:13
 */
namespace app\index\controller;
use think\Session;
use think\Db;
use think\Controller;

class Contact extends Controller{
    public function contact(){
        $user_name = Session::get("user_name");
        $this->assign("user_name",$user_name);
        return $this->fetch("contact");
    }
}