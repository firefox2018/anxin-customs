<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/10/30
 * Time: 15:28
 */
namespace app\admin\controller;
use think\Controller;
use think\Route;
use think\Request;
use think\View;
use think\Db;
header("Content-Type:text/html;charset=utf-8");
//提单管理
class BillManage extends Controller{
    //显示空运提单页面
    public function index(){
    	$request = Request::instance();
    	$allAirInfo = Db::table('airportbill')->order('uploadtime','desc')->select();    	
    	$pageSize = $request->param('pageSize') ?:20;//每页显示条数    	
    	$pageNow = $request->param('pageNow')?:1;//当前页
    	$showPage = 2;// 分页栏中 显示 几页, 这里为2就显示2*$showpage+1页
    	$limit = ($pageNow-1)*$pageSize; //查询语句的条件
    	$value = $request->param('value')?:20;        
    	if(!is_numeric($value)){
    		$value=20;	
    	}
        $status = $request->param('status')?:'';
              	
    	if($status != ''){    		
    		if($status != '全部显示' ){
	    		$sql = "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id where a.status='$status' order by a.uploadtime desc limit $limit,$pageSize";
                $sql1 = "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id where a.status='$status' order by a.uploadtime desc ";
	    		$total = count(Db::query($sql1)); 
    		}else{
	    		$total= count($allAirInfo); //查询的总条数
	    		$sql = "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id order by a.uploadtime desc limit $limit,$pageSize"; 
                $sql1= "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id order by a.uploadtime desc "; 	
    		}
    	}else{
    		$total= count($allAirInfo); //查询的总条数
    		$sql = "select a.*,b.balance,b.user_phone,b.relname from airportbill as a join users as b on a.userid=b.id  order by a.uploadtime desc limit $limit,$pageSize";
            $sql1 =   "select a.*,b.balance,b.user_phone,b.relname from airportbill as a join users as b on a.userid=b.id  order by a.uploadtime desc "; 	
    	}
        //根据输入的信息查询.
        //输入用户ID时查询信息
        $userid = $request->param('userid')?:'';       
        if($userid){                        
            $res = Db::table('airportbill')->where('userid',$userid)->select();
            $a = count($res);
            if($a==0){
              echo 0 ;die;
            }else{
                $sql = "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id where a.userid=$userid order by a.uploadtime desc limit $limit,$pageSize";
                $sql1 = "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id where a.userid=$userid order by a.uploadtime desc "; 
                $total = count(Db::query($sql1));  
            }
        }               
        $url="/public/admin/bill_manage/index?pageNow={page}&pageSize=$pageSize&value=$value&userid=$userid&status=$status";
        //输入提单号时查询信息
        $airid = $request->param('airid')?:'';
        if($airid){
            $res = Db::table('airportbill')->where('airid',$airid)->select();
            $a = count($res);
            if($a==0){
                echo 0; die;
            }else{
                $sql = "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id where a.airid='$airid' order by a.uploadtime desc limit $limit,$pageSize";
                $sql1 =  "select a.*,b.balance,b.user_phone,b.relname from airportbill as a  join users as b on a.userid=b.id where a.airid='$airid' order by a.uploadtime desc ";
                $total = count(Db::query($sql1));
            }
        }     
    	$airInfo = Db::query($sql);
    	for($i=0;$i<count($airInfo);$i++){
    		$airInfo[$i]['uploadtime'] = date('Y年m月d日 H:i:s',$airInfo[$i]['uploadtime']);
    		$airInfo[$i]['rid'] = $i+1+$limit;
    	}
        $airInfo1 = Db::query($sql1);
        for($i=0;$i<count($airInfo1);$i++){
            $airInfo1[$i]['uploadtime'] = date('Y年m月d日 H:i:s',$airInfo1[$i]['uploadtime']);
            $airInfo1[$i]['rid'] = $i+1;
        }    	    	 	
    	$paginator = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPage);
        
    	$list = $paginator->render();
    	$this->assign('value',$value);
    	$this->assign('airInfo',$airInfo);
        $this->assign('airInfo1',$airInfo1);
    	$this->assign('list',$list);
        return $this->fetch('airbill');
    }

    /*
	 *更改运单状态
	 *@access public
     */
    public function changeStatusByAjax(){
    	$request = Request::instance();
    	if($request->isAjax()){
    		$choseBills = json_decode($request->param('choseBills'),true);
    		if(is_array($choseBills)){    			
    		//file_put_contents(ROOT_PATH."/public/logs/admin/test.txt",$choseBills);
    			$flag = 0;
    			foreach($choseBills as $choseBill){
    				$airid = $choseBill['airid'];
    				$userid = $choseBill['userid'];
    				$status = $choseBill['status'];
    				$res = Db::table('airportbill')->where('airid',$airid)->where('userid',$userid)->update(['status'=>$status,'updatetime'=>time()]);
    				if(!$res){
    					file_put_contents(ROOT_PATH."/public/logs/admin/airportbill.txt",date('Y-m-d H:i:s',time())."修改状态失败,空运单号:".$airid.",用户ID:".$userid."\n");
    					$flag +=1;
    				}else{
                        $airlog = '';
                        switch($status){
/*                            case '待付款':
                                $airlog = "订单状态更改为待付款";
                                break;
                            case '已付款':
                                $airlog = "订单状态更改为已付款";
                                break;*/
                            case '待到港':
                                $airlog = "您的提单即将到达目的港!";
                                break;
                            case '已到港':
                                $airlog = "您的提单已抵达目的港!";
                                break;
                            case '已提货':
                                $airlog = "已在目的港提货成功! ";
                                break;
                            case '清关中':
                                $airlog = "您的提单货物正在清关中,请耐心等待!";
                                break;
/*                            case '已转单':
                                $airlog = "已转国内快递,快递单号:";
                                break;*/
                            case '已收货':
                                $airlog = "提单货物已签收,欢迎下次继续使用安心报关系统!";
                                break;
                        }
    					$data = [
    						'id'=>'',
    						'airid'=>$airid,
    						'userid'=>$userid,
    						'status'=>$status,
    						'modifytime'=>time(),    						
    						'operator'=>'admin',
    						'airlog'=>$airlog
    					];
    					Db::table('airportlog')->insert($data);
    				}    				
    			}
    			if($flag>0){
    				echo 0; //表示修改状态失败!
    			}else{ 
    				echo 1;//修改状态成功
    			}   			
    		}
    	}
    }
    /*
     *后台扣费
     */
    public function deduction(){
        $request = Request::instance();
        if($request->isAjax()){
            $checkedInfo = json_decode($request->param('checkedInfo'),true);
            $errLog = [];
            $sucLog = [];
            $koufeiResult = [];
            for($i=1;$i<=count($checkedInfo);$i++){
                $airid = $checkedInfo[$i]['airid'];
                $userid = $checkedInfo[$i]['userid'];
                $balance = Db::table('users')->where('id',$userid)->value('balance');
                $fee = Db::table('airportbill')->where('userid',$userid)->where('airid',$airid)->value('fee');
                $status = Db::table('airportbill')->where('userid',$userid)->where('airid',$airid)->value('status');
                if($balance<$fee ){
                    //余额不足,将提单号保存到数组中.
                    $err = [];
                    $err[] = $airid;
                    $err[] = $userid;
                    $errLog[$i] = $err;
                }else{
                    $newBalance = $balance-$fee;
                    $res = Db::table("users")->where('id',$userid)->update(["balance"=>$newBalance]);
                    $res1 = Db::table("airportbill")->where('userid',$userid)->where('airid',$airid)->update(["fee"=>0]);
                    if($res && $res1){
                        //扣费成功!
                        $suc = [];
                        $suc[] = $userid;
                        $suc[] = $airid;
                        $sucLog[$i] = $suc;
                        $data = [
                        'id'=>'',
                        'airid'=>$airid,
                        'userid'=>$userid,
                        'status'=>$status,
                        'modifytime'=>time(),
                        'operator'=>'admin',
                        'airlog'=>'提单扣费成功,扣除费用'.$fee.'元!'
                        ];
                     //   Db::table('airportbill')->where('userid',$userid)->where('airid',$airid)->update(['status'=>'已付款']);
                        Db::table('airportlog')->insert($data);
                    }
                }           
            }
            if(!empty($errLog)){
                $koufeiResult['err'] = $errLog;
            }else{
                $koufeiResult['err'] = '';
            }
            if(!empty($sucLog)){
                $koufeiResult['suc'] = $sucLog;
            }else{
                $koufeiResult['suc'] = '';
            }
            echo json_encode($koufeiResult);
        }
    }
    /*
     *根据空运提单号,用户id显示对应的操作历史记录
     *@access public 
     *@param 
     *@return 
     */
    public function showLogs(){
    	$request=Request::instance();
    	if($request->isAjax()){
    		$airid = $request->param('airid');
    		$userid = $request->param('userid');
    		$logs = Db::table('airportlog')->where('airid',$airid)->where('userid',$userid)->select();
    		if(!empty($logs)){
    			echo json_encode($logs);
    		}else{
    			echo 0;
    		}
    	}
    }

    //显示海运提单页面
    public function shipBill(){
        $request = Request::instance();
        $allOceanBill = Db::table('oceanportbill')->order('uploadtime','desc')->select(); 
        $pageSize = $request->param('pageSize')?:10;
        $pageNow = $request->param('pageNow')?:1 ;
        $showPage = 2;
        $value = $request->param('value')?:10;
        $limit = ($pageNow-1)*$pageSize;
        $status = $request->param('status')?:'';
        if($status){
            if($status !=='全部显示'){
                $sql = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a  join users as b on a.userid=b.id where a.status='$status' order by a.uploadtime desc limit $limit,$pageSize";
                $sql1 = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a  join users as b on a.userid=b.id where a.status='$status' order by a.uploadtime desc ";
                $total = count(Db::query($sql1));
            }else{
                $sql = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a join users as b on a.userid=b.id order by a.uploadtime desc limit $limit,$pageSize";
                $sql1 = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a join users as b on a.userid=b.id order by a.uploadtime desc";
                $total = count($allOceanBill);
            }
        }else{
            $sql = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a  join users as b on a.userid=b.id order by a.uploadtime desc limit $limit,$pageSize";
            $sql1 = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a  join users as b on a.userid=b.id order by a.uploadtime desc";
            $total = count($allOceanBill);
        }

        //根据输入的信息查询.
        //输入用户ID时查询信息
        $userid = $request->param('userid')?:'';       
        if($userid){                        
            $res = Db::table('oceanportbill')->where('userid',$userid)->select();
            $a = count($res);
            if($a==0){
              echo 0 ;die;
            }else{
                $sql = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a join users as b on a.userid=b.id where a.userid=$userid order by a.uploadtime desc limit $limit,$pageSize";
                $sql1 = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a join users as b on a.userid=b.id where a.userid=$userid order by a.uploadtime desc"; 
                $total = count(Db::query($sql1));  
            }
        } 
       //输入提单号时查询信息
        $oceanid = $request->param('oceanid')?:'';
        if($oceanid){
            $res = Db::table('oceanportbill')->where('oceanid',$oceanid)->select();
            $a = count($res);
            if($a==0){
                echo 0; die;
            }else{
                $sql = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a join users as b on a.userid=b.id where a.oceanid='$oceanid' order by a.uploadtime desc limit $limit,$pageSize";
                $sql1 = "select a.*,b.balance,b.user_phone,b.relname from oceanportbill as a join users as b on a.userid=b.id where a.oceanid='$oceanid' order by a.uploadtime desc";
                $total = count(Db::query($sql1));
            }
        } 
        $url = "/public/admin/bill_manage/shipBill?pageNow={page}&pageSize=$pageSize&value=$value&userid=$userid&status=".$status;  

        $oceanBill = Db::query($sql);
        for($i=0;$i<count($oceanBill);$i++){
            $oceanBill[$i]['rid'] = $i+1+$limit;
            $oceanBill[$i]['uploadtime'] = date('Y年m月d日 H:i:s',$oceanBill[$i]['uploadtime']);
        }
        $oceanBill1 = Db::query($sql1);
        for($i=0;$i<count($oceanBill1);$i++){
            $oceanBill1[$i]['uploadtime'] = date('Y年m月d日 H:i:s',$oceanBill1[$i]['uploadtime']);
            $oceanBill1[$i]['rid'] = $i+1;
        }  

        $paginator= new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPage);
        $list = $paginator->render($id='page-ship');
        $this->assign('value',$value);
        $this->assign('list',$list);
        $this->assign('allOceanBill1',$oceanBill1);
        $this->assign('allOceanBill',$oceanBill);
        return $this->fetch();
    }
    //更改海运提单状态
    public function changeStatus(){
        $request = Request::instance();
        if($request->isAjax()){
            $checkedBills = json_decode($request->param('checkedBills'),true);
            $flag = 0;            
            foreach($checkedBills as $checkedBill){
                $userid = $checkedBill['userid'];
                $oceanid = $checkedBill['oceanid'];
                $status = $checkedBill['status'];
                $res = Db::table('oceanportbill')->where('oceanid',$oceanid)->where('userid',$userid)->update(['status'=>$status,'updatetime'=>time()]);
                    if(!$res){
                        file_put_contents(ROOT_PATH."/public/logs/admin/oceanportbill.txt",date('Y-m-d H:i:s',time())."修改状态失败,海运单号:".$oceanid.",用户ID:".$userid."\n");
                        $flag +=1;
                    }else{
                        $oceanlog = '';
                        switch($status){
/*                            case '待付款':
                                $oceanlog = "订单状态更改为待付款";
                                break;
                            case '已付款':
                                $oceanlog = "订单状态更改为已付款";
                                break;*/
                            case '待到港':
                                $oceanlog = "您的提单即将到达目的港,请耐心等候!";
                                break;
                            case '已到港':
                                $oceanlog = "您的提单已抵达目的港!";
                                break;
                            case '已提货':
                                $oceanlog = "已在目的港成功提货!";
                                break;
                            case '清关中':
                                $oceanlog = "您的提单货物正在清关中,请耐心等待!";
                                break;
                           /* case '已转单':
                                $oceanlog = "已转国内快递,快递单号:";
                                break;*/
                            case '已收货':
                                $oceanlog = "提单货物已顺利签收,欢迎下次继续使用安心报关系统!";
                                break;
                        }
                        $data = [
                            'id'=>'',
                            'oceanid'=>$oceanid,
                            'userid'=>$userid,
                            'status'=>$status,
                            'modifytime'=>time(),                           
                            'operator'=>'admin',
                            'oceanlog'=>$oceanlog
                        ];
                        $res = Db::table('oceanportlog')->insert($data);
                        if(!$res){
                            file_put_contents(ROOT_PATH."/public/logs/admin/oceanportbill.txt",date('Y-m-d H:i:s',time())."修改状态失败,海运单号:".$oceanid.",用户ID:".$userid."\n");
                        }
                    }
            }

            if($flag>0){
                echo 0; //表示修改状态失败!
            }else{ 
                echo 1;//修改状态成功
            } 

        }
    } 
    /*
     *根据海运提单号,用户id显示对应的操作历史记录
     *@access public 
     *@param 
     *@return 
     */
    public function showoceanLogs(){
        $request=Request::instance();
        if($request->isAjax()){
            $oceanid = $request->param('oceanid');
            $userid = $request->param('userid');
            $logs = Db::table('oceanportlog')->where('oceanid',$oceanid)->where('userid',$userid)->select();
            if(!empty($logs)){
                echo json_encode($logs);
            }else{
                echo 0;
            }
        }
    }

    /*
     *后台扣费,海运提单
     */
    public function deduction_ship(){
        $request = Request::instance();
        if($request->isAjax()){
            $checkedInfo = json_decode($request->param('checkedInfo'),true);
            $errLog = [];
            $sucLog = [];
            $koufeiResult = [];
            for($i=1;$i<=count($checkedInfo);$i++){
                $oceanid = $checkedInfo[$i]['oceanid'];
                $userid = $checkedInfo[$i]['userid'];
                $balance = Db::table('users')->where('id',$userid)->value('balance');
                $fee = Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$oceanid)->value('fee');
                $status =  Db::table('oceanportbill')->where('userid',$userid)->where('oceanid',$oceanid)->value('status');
                if($balance<$fee ){
                    //余额不足,将提单号保存到数组中.
                    $err = [];
                    $err[] = $airid;
                    $err[] = $userid;
                    $errLog[$i] = $err;
                }else{
                    $newBalance = $balance-$fee;
                    $res = Db::table("users")->where('id',$userid)->update(["balance"=>$newBalance]);
                    $res1 = Db::table("oceanportbill")->where('userid',$userid)->where('oceanid',$oceanid)->update(["fee"=>0]);                
                    if($res && $res1){
                        //扣费成功!
                        $suc = [];
                        $suc[] = $userid;
                        $suc[] = $airid;
                        $sucLog[$i] = $suc;
                        $data = [
                        'id'=>'',
                        'oceanid'=>$oceanid,
                        'userid'=>$userid,
                        'status'=>$status,
                        'modifytime'=>time(),
                        'operator'=>'admin',
                        'oceanlog'=>'提单扣费成功,扣除费用'.$fee.'元!'
                        ];
                        Db::table('oceanportlog')->insert($data);
                    }
                }           
            }
            if(!empty($errLog)){
                $koufeiResult['err'] = $errLog;
            }else{
                $koufeiResult['err'] = '';
            }
            if(!empty($sucLog)){
                $koufeiResult['suc'] = $sucLog;
            }else{
                $koufeiResult['suc'] = '';
            }
            echo json_encode($koufeiResult);
        }
    }

}