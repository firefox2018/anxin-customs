<?php
namespace app\index\Controller;
use think\Controller;
use think\View;
use think\Request;
use think\Db;
use think\Session;
use PHPMailer\phpmailer;
/*
 * 用户登录控制器
 */
class Login extends Controller{
    protected $salt='';
    /*
     * 显示网站首页:登陆页面
     */
	public function index(){
		return $this->fetch();
	}
	/*
	 * 接收登陆信息
	 */
	public function login(){
        $request = Request::instance();
        if($request->isAjax()){
            $post = $request->param();
            $user_name = htmlspecialchars(trim($post['user_name']));
            $user_pwd = md5($post['user_pwd']);
            if(strpos($user_name,'@')){
                $user_email = $user_name;
                $res = Db::table('users')->where('user_email',$user_email)->where('user_pwd',$user_pwd)->find();
            }else{
                $res = Db::table('users')->where('user_name',$user_name)->where('user_pwd',$user_pwd)->find();
            }
            if(!empty($res)) {
                if($res['islocked']=='是'){
                    echo 2;
                }else{
                    Session::set('user_name',$res['user_name']);
                    Session::set('id',$res['id']);
                    Db::table('users')->where('id',$res['id'])->update(["lastlogintime"=>time()]);
                    echo  0;
                }
            }else{
                echo 1;
            }
        }
    }
    //接收用户发送过来的邮件,判断邮件是否有效,有效发送修改密码地址
    public function findPwd(){
        $request = Request::instance();
        if($request->isAjax()){
            $user_email = $request->post('user_email');
            $sql = "select id from users where user_email= '$user_email'";
            $res = Db::query($sql);
            if(!$res){
                echo 0;
            }else{
                $this->salt = md5(time().$user_email);
                echo $this->sendEmail($user_email,$res[0]['id'],$this->salt);
            }
        }
    }
    //发邮件函数
    public function sendEmail($user_email,$id,$salt){
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet='UTF-8';
        $mail->Host="smtp.qiye.163.com";
        $mail->SMTPAuth='true';
        $mail->SMTPSecure = "openssl";
        $mail->Username="admin-hp@anxin-ex.com";
        $mail->Password="dWLgTAxGJNBAvDBP";//这里并不是邮箱的登陆密码,而是在邮箱里面设置的客户端授权密码
        $mail->setFrom('admin-hp@anxin-ex.com','修改密码邮件');
        $mail->addAddress($user_email);
        $mail->Subject="安心转运报关系统修改密码邮件!";
        $mail->IsHTML(true);
        $mail->Body="亲爱的安心转运报关系统用户,<br/>这封邮件由安心转运报关系统网站运营团队发出.<br/>您点击了安心转运报关系统网站忘记密码按钮，系统发送了此邮件给您，如果您需要重新设置您的个人账号密码，请点击以下链接:<br/><a href='http://anxin.com/public/index/login/changePwd?id=$id&salt=$salt'>修改密码</a>";
        if(!$mail->send()){
            echo $mail->ErrorInfo;
        }else{
            return Db::table('users')->where('id',$id)->setField('modifytime',time());
        }
    }
    /*
     * 修改密码
     */
    public function changePwd(){
        $request = Request::instance();
        $id = (int)$request->get('id')?$request->get('id'):0;
        $modifytime = Db::table('users')->where('id',$id)->value('modifytime');
        if($modifytime==0 || $modifytime=="undefined" || $modifytime==''){
            $this->error("非法请求,或该邮件已经失效!");
            exit;
        }
        //设置邮件过期时间:24小时.  24*60*60 ;
        $res = Db::table('users')->where('id',$id)->find();
        //当前时间减去邮件过期时间,如果大于邮件发送时间,说明邮件已经过期
        if((time()-24*60*60)>$res['modifytime']){
            $this->error("该邮件已经失效,请重新发送!");
        }else{
            $this->assign("id",$id);
            return $this->fetch("login/changepwd");
        }
    }
    //接收传递修改的密码,修改数据,跳转到首页
    public function change(){
        $request = Request::instance();
        $post = $request->param();
        $user_pwd = md5($post['user_pwd']);
        $id=$post['id'];
        $res = Db::table('users')->where('id',$id)->update(['user_pwd'=>$user_pwd]);
        if($res){
            Session::set('id',$id);
            Db::table('users')->where('id',$id)->update(["modifytime"=>0]);
            $this->success("修改成功!","login/index");
        }else{
            $this->error("修改失败!");
        }
    }
    public function logout(){
        $id = Session::get('id');
        Db::table('users')->where('id',$id)->update(["lastlogouttime"=>time()]);
        Session::set('id','');
        $this->redirect('login/index');
    }

}