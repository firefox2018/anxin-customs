<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/10/24
 * Time: 14:44
 */
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\View;
use think\Session;
use app\index\controller\Base;
use think\Loader;
use app\common\model\User;

/*
 *用户管理控制器
 */
class Manage extends Base{
    /*
     * 显示收货地址
     */
    public function address(){
        $this->assign('user_name',Session::get('user_name'));
        return $this->fetch();
    }
    /*
     * 显示用户信息
     */
    public function userInfo(){
        $id = Session::get('id');
        $user = Loader::model('User');
        $info = $user->getInfoById($id);
        $signtime = date('Y年m月d日 H:i:s',$info['user_createtime']);
        $this->assign('user_name',$info['user_name']);
        $this->assign('signtime',$signtime);
        $this->assign('data',$info);
        return $this->fetch();
    }
    /*
     * 显示付款信息
     */
    public function payInfo(){
        $this->assign('user_name',Session::get('user_name'));
        return $this->fetch();
    }
}
