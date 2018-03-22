<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/11/17
 * Time: 15:51
 */
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\View;
use think\Session;
//设置基类,防止用户根据网址直接访问页面.
class Base extends Controller{
    public function __construct(){
        parent::__construct();
        $id= Session::get('id');
        if(!$id){
            $this->error("请先登陆!","login/index");
        }
    }
}