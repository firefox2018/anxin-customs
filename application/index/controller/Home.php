<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/12/19
 * Time: 17:06
 */
namespace app\index\Controller;
use think\Controller;
use think\View;
use think\Session;
use think\Request;
use think\Db;
use app\index\controller\Base;

class Home extends Controller {
    public function home(){
        $user_name = Session::get('user_name')?:'';
        $res = Db::table('notice')->where('isshow','是')->order('addtime','desc')->limit(8)->select();
        $this->assign('res',$res);
        $this->assign('user_name',$user_name);
        return $this->fetch();
    }
}