<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2018/1/3
 * Time: 10:01
 */
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Notice extends Controller{
    public function notice(){
        $request = Request::instance();
        $user_name = Session::get("user_name")?:'';
        $notice = Db::table('notice')->where('isshow','是')->order("addtime",'desc')->select();
        if(!empty($notice)){
            $total=count($notice);
            $pageSize=10;
            $pageNow=$request->param('pageNow')?:1;;
            $noticeid = $request->param('noticeid')?:'';
            $url="/public/index/notice/notice?pageNow={page}";
            $showPages=2;
            $limit = ($pageNow-1)*$pageSize;
            $paginator = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPages);
            $list = $paginator->render();
            $res = Db::query("select * from notice where isshow='是' order by addtime desc limit $limit,$pageSize");
            for($i=0;$i<count($res);$i++){
          /*      $a = ($pageNow-1)*$pageSize+$i+1;
                $res[$i]['rid'] = $a;*/
                $res[$i]['addtime'] = date("Y-m-d H:i:s",$res[$i]['addtime']);
            }
            if(!$noticeid){
                $this->assign("list",$list);
                $this->assign("notice",$res);
            }else{
                $notice = Db::query("select * from notice where id=$noticeid");
                $notice[0]['addtime'] = date("Y-m-d H:i:s",$notice[0]['addtime']);
                $this->assign("notice");
                $this->assign("list");
                $this->assign("res",$notice[0]);
            }
        }
        $this->assign("user_name",$user_name);
        return $this->fetch('notice');
    }
}