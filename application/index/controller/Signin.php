<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/11/4
 * Time: 10:10
 */
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\View;
use PHPMailer\phpmailer;
use think\Session;
/*
 * 创建用户注册控制器
 */
class Signin extends Controller{
    //显示注册页面
    public function index(){
        return $this->fetch();
    }
    //前台ajax验证用户名是否已经被注册!
    public function checkUser(){
        $request = Request::instance();
        if($request->isAjax()){
            $user_name = htmlspecialchars(trim($request->post('user_name')));
            $res = Db::query("select id from users where user_name = '$user_name'");
            if($res) echo 0;//返回0 表示用户名已经存在
            else  echo 1;//返回1表示可以正常注册
        }
    }
    //前台ajax验证邮箱是否已经被注册.
    public function checkEmail(){
        $request = Request::instance();
        $user_email =trim($request->post('user_email'));
        if($request->isAjax()){
            $res = Db::query("select id from users where user_email = '$user_email'");
            if($res){
                echo 0;
            }else{
                echo 1;
            }
        }
    }
    //前台ajax检测验证码输入是否正确
    public function checkCaptcha(){
        $request = Request::instance();
        if($request->isAjax()){
            $captcha = $request->post('captcha');
            //获取session中保存的captcha
           $captchaCode = Session::get('captchaCode');
           if($captcha!=$captchaCode){
                echo 0;
            }else{
                echo 1;
            }
        }
    }
    //接收用户注册提交的信息并处理
    public function sign(){
        //接收注册参数,处理比对后,写入数据库,同时给用户发送验证邮件
        $request = Request::instance();
        if($request->isPost()){
            $res = $request->param();
            $user_name=$res['user_name'];
            $user_pwd = md5($res['user_pwd']);
            $relname = trim($res['relname']);
            $user_email = trim($res['user_email']);
            $user_phone = $res['user_phone'];
            $user_createtime = time();
            $salt = md5($user_pwd.time());
            $data = [
                'user_name'=>$user_name,
                'user_pwd'=>$user_pwd,
                'relname'=>$relname,
                'user_email'=>$user_email,
                'user_phone'=>$user_phone,
                'user_createtime'=>$user_createtime,
                'salt'=>$salt
            ];
            $id = Db::name('users')->insertGetId($data);
            if($id){
                $this->sendEmail($user_email,$id,$salt);
            }else{
                $this->error('系统错误!');
            }
        }
    }
    //发送邮件函数
    public function sendEmail($user_email,$id,$salt){
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet='UTF-8';
        $mail->Host="smtp.qiye.163.com";
        $mail->SMTPAuth='true';
        $mail->SMTPSecure = "openssl";
        $mail->Username="admin-hp@anxin-ex.com";
        $mail->Password="dWLgTAxGJNBAvDBP";//这里并不是邮箱的登陆密码,而是在邮箱里面设置的客户端授权密码
        $mail->setFrom('admin-hp@anxin-ex.com','邮件激活');
        $mail->addAddress($user_email);
        $mail->Subject="安心转运报关系统验证邮件!";
        $mail->IsHTML(true);
        $mail->Body="亲爱的安心转运报关系统用户,<br/>这封邮件由安心转运报关系统网站运营团队发出.<br/>非常感谢您的注册,请点击以下链接激活您的账号完成注册!<br/><a href='http://anxin.com/public/index/Signin/active?id=$id&salt=$salt'>点击激活邮件</a>";
        if(!$mail->send()){
            echo $mail->ErrorInfo;
        }else{
            Session::set('id',$id);
            Db::table('users')->where('id',$id)->setField('regtime',time());
            $this->redirect('index/index');
        }
    }
    //邮件激活
    public function active($id,$salt){
        $id = input('get.id');
        $salt = input('get.salt');
        //计算邮件过期时间 24*60*60
        $row = Db::table('users')->where('id',$id)->value('regtime');
        if((time()-24*60*60)>$row){
            $this->error("邮件已失效,请重新发送!");
        }else{
            $sql = "select user_name from users where id=? and salt=?";
            $res = Db::query($sql,[$id,$salt]);
            if(!$res){
                return $this->error("激活失败!");
            }else{
                $row = Db::table('users')->update(['user_status'=>1,'id'=>$id]);
                if($row){
                    Session::set('id',$id);
                    $this->success('激活成功!','index/index');
                }
            }
        }

    }
    //调用验证码类,生成验证码
    public function verify(){
        $captcha = new \captcha\captcha(80,40,4,5,5);
        $captcha->show();
    }
}
