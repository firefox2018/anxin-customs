<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/11/21
 * Time: 12:44
 */
namespace app\index\controller;
use think\View;
use think\Request;
use think\Db;
use think\Session;
use app\index\controller\Base;
//提单信息管理. 如果存在异常,就对提单信息进行修改,重新上传.
class Billmanage extends Base{
    //获取到用户对应的提单信息. 空运提单,海运提单分开获取, 前台在同一个页面显示.
    public function getAirportbill(){
        $airbill = array();
        $id = Session::get('id') ? : 0;
        if(!$id){
            $this->error("登陆异常!");
            die();
        }else{
            $user = Db::table('users')->where('id',$id)->value('user_name');
            $res = Db::table('airportbill')->where('userid',$id)->select();
            //这里获取到的pdf,excel地址都是绝对地址C:\wamp\www\anxin. 这里要将其替换成anxin.com.
            if(!empty($res)){
                for($i=0;$i<count($res);$i++){
                    $res[$i] ['airpdf'] = str_replace("C:\wamp\www\anxin","http://anxin.com",$res[$i] ['airpdf']);
                    $res[$i]['airexcel']= str_replace("C:\wamp\www\anxin","http://anxin.com", $res[$i]['airexcel']);
                }
                $airbill = $res;
            }
        }
        $this->assign('user_name',$user);
        $this->assign('airbill',$airbill);
        return $this->fetch('showInfo');
    }


}
