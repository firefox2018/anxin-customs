<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2018/1/6
 * Time: 10:26
 */
namespace app\admin\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Request;

class Home extends Controller{
    public function index(){

        return $this->fetch();
    }
}