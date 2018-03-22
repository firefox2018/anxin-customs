<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/10/30
 * Time: 15:53
 */
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\view;
use think\Db;
use PHPMailer\phpmailer;
class EmailManage extends Controller{
    //显示空运提单邮件管理页面
    public function airemail(){	
    	$request = Request::instance();
    	$pageSize = 10;
    	$pageNow = $request->param('pageNow')?:1;
    	$url = "/public/admin/email_manage/airemail?pageNow={page}";
    	$showPages = 2;
    	$limit = ($pageNow-1)*$pageSize;
    	$sql = "select a.*,b.relname,b.user_phone,b.user_email from airportbill as a join users as b on a.userid=b.id order by a.uploadtime desc limit $limit,$pageSize";
    	$airportbillInfo = Db::query($sql);
    	$total = count(Db::query("select a.*,b.relname,b.user_phone from airportbill as a join users as b on a.userid=b.id order by a.uploadtime"));    	
    	if($airportbillInfo){
    		for($i= 0;$i<count($airportbillInfo);$i++){
    			$airportbillInfo[$i]['rid'] = $i+1+$limit;
    			$airportbillInfo[$i]['uploadtime'] = date('Y-m-d',$airportbillInfo[$i]['uploadtime'] );
    			$airportbillInfo[$i]['airpdf'] = "http://anxin.com/public".substr($airportbillInfo[$i]['airpdf'],strpos($airportbillInfo[$i]['airpdf'],'/'));
    			$airportbillInfo[$i]['airexcel'] = "http://anxin.com/public".substr($airportbillInfo[$i]['airexcel'],strpos($airportbillInfo[$i]['airexcel'],'/'));
    		}
    	}    	
    	$paginator = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPages);
    	$list = $paginator->render("page-airemail");
    	$this->assign('list',$list);
    	$this->assign('airportInfo',$airportbillInfo);
        return $this->fetch();
    }
    //空运提单 发送邮件到报关公司
    public function sendEmailToCustom(){
        $request = Request::instance();
        if($request->isAjax()){
            $userid = $request->param('userid');
            $airid = $request->param('airid');
            $airpdf = ROOT_PATH.strstr($request->param('airpdf'),'public');
            $airexcel = ROOT_PATH.strstr($request->param('airexcel'),'public');
            $subject = "报关文件";
            $body = "<a href='#'>这是测试邮件!</a>";
            $res = $this->sendEmail("1090094838@qq.com",$airexcel,$airpdf,$airid.'.xlsx',$airid.'.pdf',$subject,$body);
            if($res == "ok"){
                Db::table('airportbill')->where('userid',$userid)->where('airid',$airid)->update(['isSendToCustom'=>1]);
                $status = Db::table('airportbill')->where('userid',$userid)->where('airid',$airid)->value('status');
                $data = [
                    'id'=>'',
                    'airid'=>$airid,
                    'userid'=>$userid,
                    'status'=>$status,
                    'modifytime'=>time(),
                    'operator'=>'admin',
                    'airlog'=>'发送邮件至报关公司!',
                    'opentouser'=>0,
                ];
                Db::table('airportlog')->insert($data);
                echo "ok";
            }else{
                echo "error";
            }
        }
    }
    //发送空运提单异常邮件到客户邮箱.
    public function sendEmailToUser(){
        $request = Request::instance();
        if($request->isAjax()){
            $userid = $request->param('userid');
            $useremail = $request->param('useremail');
            $airid = $request->param('airid');
            $subject = "安心报关异常邮件";
            $body = "<a href='#'>您的空运提单".$airid."上传的信息存在异常,请核查后重新上传,如有需要,请联系客服,TEL:11111;QQ:11111</a>";
            $res = $this->sendEmail($useremail,$excel='',$pdf='',$excelName='',$pdfName='',$subject,$body);
            if($res == 'ok'){
                $result = Db::table('airportbill')->where('userid',$userid)->where('airid',$airid)->update(["isException"=>"是","isSendToUser"=>1]);
                $status = Db::table('airportbill')->where('userid',$userid)->where('airid',$airid)->value('status');
                $data = [
                    'id'=>'',
                    'airid'=>$airid,
                    'userid'=>$userid,
                    'status'=>$status,
                    'modifytime'=>time(),
                    'operator'=>'admin',
                    'airlog'=>'发送异常邮件至客户邮箱!',
                    'opentouser'=>0,
                ];
                Db::table('airportlog')->insert($data);
                if($result){
                    echo 'ok';
                }else{
                    echo 'error';
                }
            }
        }
    }


    //显示海运提单邮件管理页面
    public function oceanemail(){
        $request = Request::instance();
        $pageSize = 10;
        $pageNow = $request->param('pageNow')?:1;
        $url = "/public/admin/email_manage/oceanemail?pageNow={page}";
        $showPages = 2;
        $limit = ($pageNow-1)*$pageSize;
        $sql = "select a.*,b.relname,b.user_phone,b.user_email from oceanportbill as a join users as b on a.userid=b.id order by a.uploadtime desc limit $limit,$pageSize";
        $oceanportbillInfo = Db::query($sql);
        $total = count(Db::query("select a.*,b.relname,b.user_phone from oceanportbill as a join users as b on a.userid=b.id order by a.uploadtime"));
        if($oceanportbillInfo){
            for($i= 0;$i<count($oceanportbillInfo);$i++){
                $oceanportbillInfo[$i]['rid'] = $i+1+$limit;
                $oceanportbillInfo[$i]['uploadtime'] = date('Y-m-d',$oceanportbillInfo[$i]['uploadtime'] );
                $oceanportbillInfo[$i]['oceanpdf'] = "http://anxin.com/public".substr($oceanportbillInfo[$i]['oceanpdf'],strpos($oceanportbillInfo[$i]['oceanpdf'],'/'));
                $oceanportbillInfo[$i]['oceanexcel'] = "http://anxin.com/public".substr($oceanportbillInfo[$i]['oceanexcel'],strpos($oceanportbillInfo[$i]['oceanexcel'],'/'));
            }
        }
        $paginator = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPages);
        $list = $paginator->render("page-oceanemail");
        $this->assign('list',$list);
        $this->assign('oceanportInfo',$oceanportbillInfo);
        return $this->fetch();
    }
//海运提单,发送邮件到报关公司.
    public function oceanToCustom(){
        $request = Request::instance();
        if($request->isAjax()){
            $userid = $request->param('userid');
            $oceanid = $request->param('oceanid');
            $oceanexcel = ROOT_PATH.strstr($request->param('oceanexcel'),'public');
            $oceanpdf = ROOT_PATH.strstr($request->param('oceanpdf'),'public');
            $subject = "报关文件";
            $body = "<a href='#'>这是海运提单测试邮件!</a>";
            $recieveEmail = "1090094838@qq.com";
            $res = $this->sendEmail($recieveEmail,$oceanexcel,$oceanpdf,$oceanid.'.xlsx',$oceanid.'.pdf',$subject,$body);
            if($res == "ok"){
                Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$oceanid)->update(['isSendToCustom'=>1]);
                $status = Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$oceanid)->value('status');
                $data = [
                    'id'=>'',
                    'oceanid'=>$oceanid,
                    'userid'=>$userid,
                    'status'=>$status,
                    'modifytime'=>time(),
                    'operator'=>'admin',
                    'oceanlog'=>'发送邮件至报关公司!',
                    'opentouser'=>0,
                ];
                Db::table('oceanportlog')->insert($data);
                echo "ok";
            }else{
                echo "error";
            }
        }
    }
    //海运提单,发送异常邮件到客户邮箱.
    public function oceanToUser(){
        $request = Request::instance();
        if($request->isAjax()){
            $useremail = $request->param('useremail');
            $oceanid = $request->param('oceanid');
            $userid = $request->param('userid');
            $subject = "安心报关异常邮件";
            $body = "<p>您的海运提单".$oceanid."上传的信息存在异常,请核查后重新上传!</p><p>如有不明之处,请拨打我们的客服电话:TEL:11111;QQ:11111</p><p>为方便正常接收我们给您发送的邮件,建议您将admin-hp@anxin-ex.com设置为信任邮箱!</p>";
            $res = $this->sendEmail($useremail,$excel='',$pdf='',$excelName='',$pdfName='',$subject,$body);
            if($res == 'ok'){
                $result = Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$oceanid)->update(["isException"=>"是","isSendToUser"=>1]);
                $status = Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$oceanid)->value('status');
                $data = [
                    'id'=>'',
                    'oceanid'=>$oceanid,
                    'userid'=>$userid,
                    'status'=>$status,
                    'modifytime'=>time(),
                    'operator'=>'admin',
                    'oceanlog'=>'发送异常邮件至客户邮箱!',
                    'opentouser'=>0,
                ];
                Db::table('oceanportlog')->insert($data);
                if($result){
                    echo 'ok';
                }else{
                    echo 'error';
                }
            }
        }

    }







    /*
     * 发送邮件函数
     * @param $user_email string 接收邮件的邮箱地址
     * @param $excel string 发送的excel附件,默认为空.
     * @param $pdf string 发送的pdf附件,默认为空.
     * @param $excelName string excel附件名.
     * @param $pdfName string pdf附件名
     * @param $subject string 发送邮件的标题名
     * @param $body string 发送的邮件主题内容
     * @retrun
     */
    public function sendEmail($user_email,$excel='',$pdf='',$excelName='',$pdfName='',$subject,$body){
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet='UTF-8';
        $mail->Host="smtp.qiye.163.com";
        $mail->SMTPAuth='true';
        $mail->SMTPSecure = "openssl";
        $mail->Username="admin-hp@anxin-ex.com";
        $mail->Password="dWLgTAxGJNBAvDBP";//这里并不是邮箱的登陆密码,而是在邮箱里面设置的客户端授权密码
        $mail->setFrom('admin-hp@anxin-ex.com','anxinExpress');
        $mail->addAddress($user_email);
        $mail->Subject=$subject;
        if(is_file($excel) && is_file($pdf)){
            $mail->AddAttachment($excel,$excelName);
            $mail->AddAttachment($pdf,$pdfName);
        }
        $mail->IsHTML(true);
        $mail->Body=$body;
        if(!$mail->send()){
            echo $mail->ErrorInfo;
        }else{
            return "ok";
        }
    }
}
