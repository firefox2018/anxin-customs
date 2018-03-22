<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\View;
use think\Session;
use think\Db;
use app\index\controller\Base;
header("Content-Type:text/html;charset=utf-8");
class Index extends Base{
    //这里接收 用户注册完, 发送邮件结束后,跳转到这里,  点击激活邮件后也跳转到这里来. 这里要根据user_status字段来判断
	public function index(){
	    $id = Session::get('id') ? Session::get('id') : 0;
	    if($id){
	        $arr = [];
            $res = Db::query("select user_name,user_status,user_email,user_phone,balance from users where id = ?",[$id]);
            $airNum = count(Db::table('airportbill')->where('userid',$id)->select());
            $oceanNum = count(Db::table('oceanportbill')->where('userid',$id)->select());
            $payNum = count(Db::table('airportbill')->where('userid',$id)->where('fee','GT',0)->select())+ count(Db::table('oceanportbill')->where('userid',$id)->where('fee','GT',0)->select());
            //获取需要支付的运单费用.
            $airportPay = Db::table('airportbill')->where('userid',$id)->where('fee','GT',0)->select();
            $oceanportPay = Db::table('oceanportbill')->where('userid',$id)->where('fee','GT',0)->select();
            $airportFee = 0;
            foreach($airportPay as $v){
                $airportFee += $v['fee'];
            }
            $oceanportFee = 0;
            foreach($oceanportPay as $v){
                    $oceanportFee += $v['fee'];
            }
            $allFee = $airportFee + $oceanportFee ;
            array_push($arr,$id,$res[0]['user_email'],$res[0]['user_phone'],$allFee,$payNum,$airNum,$oceanNum,$res[0]['balance']);
          /*  $this->assign('allFee',$allFee);
            $this->assign('payNum',$payNum);
            $this->assign('airNum',$airNum);
            $this->assign('oceanNum',$oceanNum);*/
            $this->assign('arr',$arr);
            if($res && $res[0]['user_status']==1){
                //空运海运提单数,以及需要支付的运单数.
                $this->assign('user_name',$res[0]['user_name']);
                $this->assign('modal_id','yiyanzheng');
               
            }else if($res && $res[0]['user_status']==0){
                $this->assign('user_name',$res[0]['user_name']);
                $this->assign('modal_id','yanzhengyouxiang');
                
            }
            Session::set('user_name',$res[0]['user_name']);
            return  $this->fetch();
        }
	}

    public function searchBill(){
        $userid = Session::get('id');
        $request = Request::instance();
        if($request->isAjax()){
            $billId = $request->param('billId');
        }
        $airInfo = Db::table('airportbill')->where('userid',$userid)->where('airid',$billId)->find() ? : 0;
        $oceanInfo = Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$billId)->find() ? :0;
        if(!$airInfo && !$oceanInfo){
            echo json_encode("该提单号不存在!");
        }else if($airInfo && !$oceanInfo){
            echo json_encode($airInfo);
        }else if(!$airInfo && $oceanInfo){
            echo json_encode($oceanInfo);
        }else if ($airInfo && $oceanInfo){
            $arr = [];
            $arr['airInfo'] = $airInfo;
            $arr['oceanInfo'] = $oceanInfo;
            echo json_encode($arr);
        }
    } 

    public function showSearchInfo(){
        $billId = input('get.billId');
        $userid = Session::get('id');
        $username = Db::table('users')->where('id',$userid)->value('user_name');
        if(!$billId || !$userid){
            return $this->fetch('index/index');
        }else{
            //可能存在海运提单,空运提单号码相同的情况.所以这里都查一次.以及对应的操作日志也查一次.
            $searchInfo = array();
            $searchInfo['airInfo'] =  Db::table('airportbill')->where('userid',$userid)->where('airid',$billId)->find();
             $airLog = Db::table('airportlog')->where('userid',$userid)->where('airid',$billId)->where('opentouser',1)->order('modifytime','desc')->select();
             //把日志中操作时间本地化
             for($i=0;$i<count($airLog);$i++){
                $airLog[$i]['modifytime'] = date('Y-m-d H:i:s',$airLog[$i]['modifytime'] );
             }
            $searchInfo['airLog'] = $airLog; 
            $searchInfo['oceanInfo'] =  Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$billId)->find();
            $oceanLog = Db::table('oceanportlog')->where('userid',$userid)->where('oceanid',$billId)->where('opentouser',1)->order('modifytime','desc')->select();
            for($i=0;$i<count($oceanLog);$i++){
                $oceanLog[$i]['modifytime'] = date('Y-m-d H:i:s',$oceanLog[$i]['modifytime']);
            }
            $searchInfo['oceanLog'] = $oceanLog;
           // dump($searchInfo);die;
           $this->assign('user_name',$username);           
            $this->assign('searchInfo',$searchInfo);
            return  $this->fetch('showSearchInfo');
        }
    }

    //ajax检测用户输入的提单是否存,如果不存在,页面就不跳转并给出提示.
    public function checkSearch(){
        $request = Request::instance();
        $userid = Session::get('id');
        if($request->isAjax()){
            $billId = $request->param('billId');
            $airInfo = Db::table('airportbill')->where('airid',$billId)->where('userid',$userid)->find();
            $oceanInfo = Db::table('oceanportbill')->where('oceanid',$billId)->where('userid',$userid)->find();
            if(!$airInfo && !$oceanInfo) echo 1;            
        }
    }





}
