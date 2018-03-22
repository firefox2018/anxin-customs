<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/10/30
 * Time: 16:01
 */
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\View;
use think\Db;
//用户管理
class UserManage extends Controller{
    //显示用户管理页面
    public function index(){
        $request = Request::instance();
        if($request->isAjax()){
            $queryCondition = $request->param("queryCondition");
            $sql = "select * from users where id='$queryCondition' or user_name='$queryCondition' or relname = '$queryCondition'";
            $res = Db::query($sql);
            if($res){
                echo "ok";die;
            }else{
                echo "error";die;
            }
        }
        if($condition = $request->param("condition")){
            $sql = "select * from users where id='$condition' or user_name='$condition' or relname = '$condition'";
        }else{
            $sql = "select * from users limit 1,50";
        }
        $res = Db::query($sql);
        if($res){
            for($i=0;$i<count($res);$i++){
                $res[$i]['regtime'] = Date('Y-m-d H:i:s',$res[$i]['regtime']);
            }
            $this->assign('userInfo',$res);
        }
        return $this->fetch();
    }
    //后台给用户充值
    public function recharge(){
        $request = Request::instance();
        if($request->isAjax()){
            $user_id = $request->param("user_id");
            $sum = $request->param('sum');
            $remarks = $request->param("remarks");
            $pattern = '/^(\d+)\.?\d+/';
            preg_match_all($pattern,$user_id,$res);
            if(!$res){
                echo "error"; die;
            }else{
                $balance = (float)Db::table('users')->where('id',$user_id)->value('balance');
                $newbalance = $balance + $sum ;
                $username= Db::table('users')->where('id',$user_id)->value('user_name');
                $relname = Db::table('users')->where('id',$user_id)->value("relname");
                $res = Db::table('users')->where('id',$user_id)->update(["balance"=>$newbalance]);
                if($res){
                    $data = [
                        'id'=>'',
                        'userid'=>$user_id,
                        'username'=>$username,
                        'relname'=>$relname,
                        'logs'=>"充值".$sum."元到用户账户",
                        'operator'=>"admin",
                        'operatetime'=>time(),
                        'remarks'=>$remarks
                    ];
                    Db::table('userslog')->insert($data);
                    echo "ok";die;
                }else{
                    echo "error";die;
                }
            }
        }
    }
    //后台给用户扣费
    public function deduction(){
        $request = Request::instance();
        if($request->isAjax()){
            $userid = $request->param('userid')?:'';
            $deduction_sum = $request->param('deduction_sum')?:0;
            $balance = (float)Db::table("users")->where("id",$userid)->value("balance");
            $username = Db::table("users")->where("id",$userid)->value("user_name");
            $relname = Db::table("users")->where("id",$userid)->value("relname");
            $deduction_remarks = $request->param("deductionremarks");
            if($balance<=0){
                echo "用户余额不足!";die;
            }else{
                $newbalance = $balance - $deduction_sum;
                if($newbalance < 0){
                    echo "用户余额不足!";die;
                }else {
                    $res = Db::table("users")->where("id", $userid)->update(["balance" => $newbalance]);
                    if ($res) {
                        $data = [
                            "id" => '',
                            "userid" => $userid,
                            "username" => $username,
                            "relname" => $relname,
                            "logs" => "扣费用户费用:" . $deduction_sum . "元",
                            "operator" => "admin",
                            "operatetime" => time(),
                            "remarks" => $deduction_remarks
                        ];
                        Db::table("userslog")->insert($data);
                        echo "ok";
                        die;
                    } else {
                        echo "error";
                        die;
                    }
                }
            }
        }
    }
    //锁定用户
    public function lockUser(){
        $request = Request::instance();
        if($request->isAjax()){
            $id = $request->param('userid');
            $res=Db::table('users')->where('id',$id)->update(["islocked"=>"是"]);
            $username= Db::table('users')->where('id',$id)->value("user_name");
            $relname = Db::table('users')->where('id',$id)->value('relname');
            if($res){
                $data = [
                    'id'=>'',
                    'userid'=>$id,
                    'username'=>$username,
                    'relname'=>$relname,
                    'logs'=>"锁定用户",
                    'operator'=>"admin",
                    'operatetime'=>time(),
                    'remarks'=>''
                ];
                Db::table("userslog")->insert($data);
                echo "ok"; die;
            }else{
                echo "error";die;
            }
        }
    }
    //解除锁定
    public function relieve(){
        $request = Request::instance();
        if($request->isAjax()){
            $userid = $request->param('userid');
            $username = Db::table('users')->where('id',$userid)->value('user_name');
            $relname = Db::table('users')->where('id',$userid)->value('relname');
            $res = Db::table('users')->where('id',$userid)->update(["islocked"=>"否"]);
            if($res){
                $data = [
                    'id'=>'',
                    'userid'=>$userid,
                    'username'=>$username,
                    'relname'=>$relname,
                    'logs'=>"解除用户锁定",
                    'operator'=>"admin",
                    'operatetime'=>time(),
                    'remarks'=>''
                ];
                Db::table("userslog")->insert($data);
                echo "ok"; die;
            }else{
                echo "error";die;
            }
        }
    }
    //修改用户密码
    public function changePwd(){
        $request = Request::instance();
        if($request->isAjax()){
            $userid = $request->param('userid');
            $newpwd = md5($request->param('newpwd'));
            $newpwd_remarks = $request->param('newpwd_remarks');
            $res = Db::table("users")->where('id',$userid)->update(["user_pwd"=>$newpwd]);
            $username = Db::table("users")->where('id',$userid)->value('user_name');
            $relname = Db::table("users")->where('id',$userid)->value("relname");
            if($res){
                $data = [
                    'id'=>'',
                    'userid'=>$userid,
                    'username'=>$username,
                    'relname'=>$relname,
                    'logs'=>"修改密码",
                    'operator'=>"admin",
                    'operatetime'=>time(),
                    'remarks'=> $newpwd_remarks
                ];
                Db::table('userslog')->insert($data);
                echo "ok";die;
            }else{
                echo "error";die;
            }

        }
    }
    //跳过邮箱验证
    public function skipVerify(){
        $request = Request::instance();
        if($request->isAjax()){
            $userid = $request->param('userid');
            $username = Db::table("users")->where('id',$userid)->value("user_name");
            $relname = Db::table('users')->where('id',$userid)->value('relname');
            $res = Db::table("users")->where('id',$userid)->update(["user_status"=>1]);
            if($res){
                $data = [
                    'id'=>'',
                    'userid'=>$userid,
                    'username'=>$username,
                    'relname'=>$relname,
                    'logs'=>"跳过邮箱验证",
                    'operator'=>"admin",
                    'operatetime'=>time(),
                    'remarks'=> ''
                ];
                Db::table('userslog')->insert($data);
                echo "ok";die;
            }else{
                echo "error";die;
            }
        }
    }

}

