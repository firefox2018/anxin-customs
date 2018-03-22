<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2018/1/2
 * Time: 17:03
 */
namespace app\index\controller;
use think\Controller;
use think\View;
use think\Db;
use think\Session;

class Company extends Controller{
    //公司简介
    public function companyProfile(){
        $user_name = Session::get('user_name')?:'';
        $this->assign("user_name",$user_name);
        return $this->fetch('companyProfile');
    }
}