<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/10/30
 * Time: 17:31
 */
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\View;
use think\Db;


header("Content-Type:text/html;charset=utf-8");
//系统设置
class SystemSetup extends Controller{

    //管理员设置
    public function adminSetup(){
        return $this->fetch();
    }
    //系统设置
    public function sysSetup(){
        return $this->fetch();
    }
    //公告管理
    public function noticeManage(){
        $request = Request::instance();
        $noticeTitle = $request->param('noticeTitle') ? : '';
        $top= $request->param('top')?:'';
        $content = $request->param('content')?:'';
        $noticeid = $request->param('noticeid')?:''; //如果这个值为空,说明是新增公告,如果为真则是修改公告.
        if(!$top && !$noticeid){ //如果这两个字段为空,说明用户是为了访问公告管理界面. 不是要新增公告也不是要修改公告
            $total=count(Db::query("select * from notice"));
            $pageSize=$request->param("pageSize")?:12;
            $pageNow=$request->param("pageNow")?:1;
            $url="/public/admin/system_setup/noticeManage?pageNow={page}&pageSize=$pageSize";
            $showPage=2;
            $limit = $pageSize*($pageNow-1);
            $paginator = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPage);
            $list = $paginator->render();
            $sql = "select * from notice order by addtime asc limit $limit,$pageSize";
            $notice = Db::query($sql);
            if($notice){
                for($i=0;$i<count($notice);$i++){
                    $notice[$i]['addtime'] = date('Y-m-d',$notice[$i]['addtime']);
                    if($notice[$i]['updatetime'] ){
                        $notice[$i]['updatetime'] = date('Y-m-d',$notice[$i]['updatetime']);
                    }
                }
                $this->assign("list",$list);
                $this->assign("notice",$notice);
            }else{
                $this->assign("notice",'');
            }
            return $this->fetch();
        }else if($noticeTitle && !$noticeid){ //如果 title不为空, $noticeid为空,说明用户是为了新增公告
            if($noticeTitle &&  $content){
                $data = [
                    "id"=>'',
                    "title"=>$noticeTitle,
                    "content"=>$content,
                    "istop"=>$top,
                    "addtime"=>time(),
                    "updatetime"=>''
                ];
                $res = Db::table("notice")->insert($data);
                if($res){
                    $total=count(Db::query("select * from notice"));
                    $pageSize=$request->param("pageSize")?:12;
                    $pageNow=$request->param("pageNow")?:1;
                    $url="/public/admin/system_setup/noticeManage?pageNow={page}&pageSize=$pageSize";
                    $showPage=2;
                    $limit = $pageSize*($pageNow-1);
                    $paginator = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPage);
                    $list = $paginator->render();
                    $sql = "select * from notice order by addtime asc limit $limit,$pageSize";
                    $notice = Db::query($sql);
                    if($notice){
                        for($i=0;$i<count($notice);$i++){
                            $notice[$i]['addtime'] = date('Y-m-d',$notice[$i]['addtime']);
                            if($notice[$i]['updatetime'] ){
                                $notice[$i]['updatetime'] = date('Y-m-d',$notice[$i]['updatetime']);
                            }
                        }
                        $this->assign("list",$list);
                        $this->assign("notice",$notice);
                    }else{
                        $this->assign("notice",'');
                    }
                    return  $this->fetch("noticemanage");
                }else{
                    return  $this->error("系统错误!");
                }
            }else{
                return $this->error("标题或内容不能为空!");
            }
        }else if($noticeTitle && $noticeid){// 如果这两个字段都不为空,则说明用户是为了修改公告.
            $res = Db::table('notice')->where('id',$noticeid)->update(["title"=>$noticeTitle,"content"=>$content,"istop"=>$top,"updatetime"=>time()]);
            if($res){
                $total=count(Db::query("select * from notice"));
                $pageSize=$request->param("pageSize")?:12;
                $pageNow=$request->param("pageNow")?:1;
                $url="/public/admin/system_setup/noticeManage?pageNow={page}&pageSize=$pageSize";
                $showPage=2;
                $limit = $pageSize*($pageNow-1);
                $paginator = new \mypaginator\mypaginator($total,$pageSize,$pageNow,$url,$showPage);
                $list = $paginator->render();
                $sql = "select * from notice order by addtime asc limit $limit,$pageSize";
                $notice = Db::query($sql);
                if($notice){
                    for($i=0;$i<count($notice);$i++){
                        $notice[$i]['addtime'] = date('Y-m-d',$notice[$i]['addtime']);
                        if($notice[$i]['updatetime'] ){
                            $notice[$i]['updatetime'] = date('Y-m-d',$notice[$i]['updatetime']);
                        }
                    }
                    $this->assign("list",$list);
                    $this->assign("notice",$notice);
                }else{
                    $this->assign("notice",'');
                }
                return  $this->fetch("noticemanage");
            }else{
                $this->error("修改失败!");
            }

        }
   }

    //编辑公告
    public function editNotice(){
        $request = Request::instance();
        if($request->isAjax()){
            $id = $request->param("id");
            $notice = Db::query("select title,content,istop from notice where id=$id ");
            if(!$notice){
                echo "系统错误!";
            }else{
                echo json_encode($notice);
            }
        }
    }
    //隐藏或显示公告.
    public function isshow(){
        $request = Request::instance();
        if($request->isAjax()){
            $hideid = $request->param('hideid') ?:'';
            if($hideid){
                $res = Db::table("notice")->where("id",$hideid)->update(["isshow"=>"否"]);
                if($res){
                    echo "ok";die;
                }else{
                    echo "error"; die;
                }
            }
            $showid= $request->param('showid')?:'';
            if($showid){
                $res = Db::table('notice')->where("id",$showid)->update(["isshow"=>"是"]);
                if($res){
                    echo "ok";die;
                }else{
                    echo "error";die;
                }
            }
        }
    }

}