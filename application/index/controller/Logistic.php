<?php

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\View;
use think\Session;
use think\Db;
use app\index\controller\Base;

header("Content-Type:text/html;charset=utf-8");
//显示物流信息页面
class Logistic extends Base{
    //显示空运提单页面.
    public function airport(){
        $request = Request::instance();
        $userid= Session::get('id'); 
        $pageNow = $request->param('pageNow')?:1;
        $status = $request->param('status')?:'';
        $pageSize =5;
        $limit= ($pageNow-1)*$pageSize;       
        if($status && $status!='ALL'){            
            $sql="select * from airportbill where userid='$userid' and status='$status' order by uploadtime desc limit $limit,$pageSize";
            $sql1= "select * from airportbill where userid='$userid' and  status='$status' ";   
            $url="/public/index/logistic/airport?pageNow={page}&status=$status"; 
                              
        }else{
            $sql="select * from airportbill where userid='$userid' order by uploadtime desc limit $limit,$pageSize"; 
            $sql1= "select * from airportbill where userid='$userid' ";  
            $url="/public/index/logistic/airport?pageNow={page}";      
        }
        $airInfo = Db::query($sql);         
        $info = Db::query($sql1);
        $total = count($info);
         //查出的数据,重新编号.
         /*for($i=0;$i<count($info);$i++){
            $info[$i]['orderId'] = $i+1;
        }*/    
       
        $page = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPages=2);
        $list = $page->render();
       // echo "尾页:".$page->pageEnd."首页:".$page->pageStart."总页数:".$page->pageCount; die;
        $this->assign('status',$status);  
        $this->assign('list',$list);
        $this->assign('airInfo',$airInfo);              
        $this->assign('user_name',Session::get('user_name'));
        return $this->fetch();
    }
//点击物流轨迹后,查询具体某个提单的物流轨迹情况.
    public function getAirlog(){
        $userid = Session::get('id');
        $request=Request::instance();
        if($request->isAjax()){
            $airid = $request->param('airid');
            $res = Db::table('airportlog')->where('airid',$airid)->where('userid',$userid)->where('opentouser',1)->select();
            if(!empty($res)){
                echo json_encode($res);
            }else{
                echo 0;
            }
        }

    }

    //显示海运提单页面.
    public function shipping(){
        $request = Request::instance();
        $userid= Session::get('id'); 
        $pageNow = $request->param('pageNow')?:1;
        $status = $request->param('status')?:'';
        $pageSize =5;
        $limit= ($pageNow-1)*$pageSize;       
        if($status && $status!='ALL'){            
            $sql="select * from oceanportbill where userid='$userid' and status='$status' order by uploadtime desc limit $limit,$pageSize";
            $sql1= "select * from oceanportbill where userid='$userid' and  status='$status' ";   
            $url="/public/index/logistic/shipping?pageNow={page}&status=$status";                               
        }else{
            $sql="select * from oceanportbill where userid='$userid' order by uploadtime desc limit $limit,$pageSize"; 
            $sql1= "select * from oceanportbill where userid='$userid' ";  
            $url="/public/index/logistic/shipping?pageNow={page}";      
        }
        $oceanInfo = Db::query($sql);         
        $info = Db::query($sql1);
        $total = count($info);       
        $page = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPages=2);
        $list = $page->render();
       // echo "尾页:".$page->pageEnd."首页:".$page->pageStart."总页数:".$page->pageCount; die;
        $this->assign('status',$status);  
        $this->assign('list',$list);
        $this->assign('oceanInfo',$oceanInfo); 
        $this->assign('user_name',Session::get('user_name'));        
        return $this->fetch();
    }

    public function getOceanlog(){
        $userid = Session::get('id');
        $request=Request::instance();
        if($request->isAjax()){
            $oceanid = $request->param('oceanid');
            $res = Db::table('oceanportlog')->where('oceanid',$oceanid)->where('userid',$userid)->where('opentouser',1)->select();
            if(!empty($res)){
                echo json_encode($res);
            }else{
                echo 0;
            }
        }

    }
}