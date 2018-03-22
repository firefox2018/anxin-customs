<?php

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\View;
use think\Session;
use app\index\controller\Base;
use think\Db;
use PHPExcel\IOFactory;
class Upload extends Base{
    //显示上传文件页面
    public function airport(){
        $this->assign('userid',Session::get('id'));
        $this->assign('user_name',Session::get('user_name'));
        return $this->fetch();
    }

    //接收上传空运提单信息
    public function getAirport(){
        $request = Request::instance();
        $airportNum = trim($request->post('airportNum'));
        $goodsWeight = trim($request->post('goodsWeight'));
        //获取用户ID,用以创建一个以用户ID为编号的文件夹,存放用户上传的信息.
        $id = Session::get('id');
        $files = $_FILES;
        if(!$id || !$airportNum || !$goodsWeight ||!$files){
            $this->error("上传信息有误,请核查后再上传!");
        }else if(!empty($files)){
            //这里先将部分数据插入数据表 airporbill 空运提单表中.
            //先根据空运提单号,用户ID查看是否已经上传过
            $res = Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->select();
            if(empty($res)){
                $data = ['id'=>'','airid'=>$airportNum,'userid'=>$id];
                $res = Db::table('airportbill')->insert($data);               
                if(!$res){
                    $this->error("数据异常701,请联系网站管理员!");
                    die;
                }
            } 
            //先判断,该文件夹是否存在,如果存在就不创建.
            $path = ROOT_PATH.'public/uploads/airport/'.$id;
            if(!is_dir($path)){
                mkdir(ROOT_PATH.'public/uploads/airport/'.$id);//以用户ID创建一个文件夹.
            }
            //再以用户上传的运单号创建一个文件夹,将该运单号对应的文档存入进入.
            $filepath = $path.'/'.$airportNum;
            if(!is_dir($filepath)){                
                mkdir($path.'/'.$airportNum);
            }
            $info = $files['filename'];

            $flag= '';
            $mark = 0;
            for($i=0;$i<count($info['name']);$i++){
                $mark+=1;
                if($info['type'][$i] == "application/pdf"){
                    $name = md5($id.$airportNum.$i).'.pdf';
                    //这里判断用户是否重复上传.
                    if(file_exists($filepath.'/'.$name)){
                        unlink($filepath.'/'.$name);
                    }
                    //对于上传的excel表,还需要检查表中的部分数据,后续还要增加一个读取excel表功能.
                }else if($info['type'][$i]=='application/vnd.ms-excel'){
                    //上传的文件为xls格式时,type为application/vnd.ms-excel
                    $name = md5($id.$airportNum.$i).'.xls';
                    if(file_exists($filepath.'/'.$name)){
                       unlink($filepath.'/'.$name);
                    }
                }else if($info['type'][$i]=='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
                    //上传的文件为xlsx格式时,type为application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
                    $name = md5($id.$airportNum.$i).'.xlsx';
                    if(file_exists($filepath.'/'.$name)){
                       unlink($filepath.'/'.$name);
                    }
                }else{
                    $this->error("上传的文件格式有误!");
                    die;
                }
                $tmp_name = $info['tmp_name'][$i];
                $result = move_uploaded_file($tmp_name,$filepath.'/'.$name);
                //这里根据上传文件名,判断是pdf还是excel,然后分别存储.
                if($info['type'][$i] == "application/pdf"){
                    $res1 = Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->update(['airpdf'=>$filepath.'/'.$name,'updatetime'=>time()]);
                    if(!$res1){
                        $this->error("数据异常702,请联系网站管理员!");
                        die;
                    }
                }else{
                    if($mark!==3){
                        $res2 = Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->update(['airexcel'=>$filepath.'/'.$name,'updatetime'=>time()+1]);
                        if(!$res2){
                            $this->error("数据异常703,请联系管理员!");
                            die;
                        }
                    }else{
                        if(!$this->getExcel($filepath.'/'.$name,$goodsWeight)){
                            //如果获取excel内容,校验发生错误. 删除已经上传的文件以及写到数据库的数据.
                            $this->delDirAndFile($filepath);
                            Db::table('airportbill')->where('airid',$airportNum)->delete();
                            $this->error("商品总重量与原始发货文件中总重量相差较大,不在允许范围之内,请核对后重新上传!");
                            die;
                        }else if($this->getExcel($filepath.'/'.$name,$goodsWeight) == 1){
                            $this->delDirAndFile($filepath);
                            Db::table('airportbill')->where('airid',$airportNum)->delete();
                            $this->error("上传excel内容有误或格式不正确,请核实后重新上传!");
                            die;
                        }else{
                            $res3 = Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->update(['originalexcel'=>$filepath.'/'.$name,'updatetime'=>time()+1]);
                            if(!$res3){
                                $this->error("数据异常704,请联系管理员!");
                                die;
                            }
                        }
                    }
                }
                $flag = (!$result) ? 0: 1;
            }
            if($flag =1){
                Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->update(['isSendToCustom'=>0,'isSendToUser'=>0]);
                $res = Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->value('status');
                if(!$res){
                    Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->update(['uploadtime'=>time(),'status'=>'待到港']);
                }
                //上传完成后,修改空运提单状态表.
                $data = [
                    'id'=>'',
                    'airid'=>$airportNum,
                    'userid'=>$id,
                    'status'=>'待到港',
                    'modifytime'=>time(),                    
                    'operator'=>Session::get('user_name'),
                    'airlog'=>"您的空运提单即将到港!",
                    'opentouser'=>1,

                ];
                $res = Db::table('airportlog')->where('airid',$airportNum)->where('userid',$id)->order('modifytime','desc')->select();
                if(empty($res)){
                    $res = Db::table('airportlog')->insert($data);
                    if(!$res){
                        $this->error("数据有误,请联系网站管理员!");
                        die();
                    }
                }else{
                    $status = $res[0]['status'];
                    $data = [
                    'id'=>'',
                    'airid'=>$airportNum,
                    'userid'=>$id,
                    'status'=>$status,
                    'modifytime'=>time(),                   
                    'operator'=>Session::get('user_name'),
                    'airlog'=>"您修改了空运提单!",
                     'opentouser'=>1,
                ];
                   $res = Db::table('airportlog')->insert($data);
                    if(!$res){
                        $this->error("数据有误,请联系网站管理员!");
                        die();
                    }                    
                    Db::table('airportbill')->where('airid',$airportNum)->where('userid',$id)->update(['updatetime'=>time(),'status'=>$status,'isException'=>'否']);
                }
               
                $this->success("上传成功!");
            }
        }
    }
    //从上传excel中获取数据,并进行判断
    /*
     * @access public
     * @param  string  $path the path of excel Example:ROOT_PATH.'public/uploads/airport/1/test3/567fc2467f59499efb318f54cccdc0ea.xlsx;
     * @return int 0 or 1;
     */
    public function getExcel($path,$goodsWeight){
        $suffix = substr($path,strrpos($path,'.')+1);
        $param = '';
        switch($suffix){
            case 'xlsx':
                $param= "Excel2007";
                break;
            case 'xls':
                $param= "Excel5";
                break;
            case 'CSV':
                $param= "CSV";
                break;
            default:
                $param = "Excel2007";
        }
        if(!$suffix || !$goodsWeight){
            return  1; //"请传入正确的参数"
        }else{
            $reader= \PHPExcel\IOFactory::createReader($param);
            $PHPExcel = $reader->load($path);
            $sheet= $PHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $hightestColumn = $sheet->getHighestColumn();
            $num = 0;
            if($sheet->getCell('A1')->getCalculatedValue()!=="重量"){
                return  1; //"表格格式有误,首列应为重量"
            }else{
                for($row=2;$row<=$highestRow;$row++){
                    $num += $sheet->getCell('A'.$row)->getCalculatedValue();
                }
                if(abs($num-$goodsWeight)>20){
                    return  0; //"商品预报总重量与原始发货文件商品总重量误差超出允许范围,允许相差±20kg!"
                }else{
                    return  2;
                }
            }
        }
    }
    public function checkAirid(){
        $request = Request::instance();
        if($request->isAjax()){
            $airid = $request->param('airid');
            $userid = $request->param('userid');
            $res = Db::table('airportbill')->where('airid',$airid)->where('userid',$userid)->select();
            if(!empty($res)){
                echo 1;
            }else{
                echo 0;
            }
        }    
    }
    /*
     * 显示海运上传页面
     */
    public function shipping(){
        $this->assign('userid',Session::get('id'));
        $this->assign('user_name',Session::get('user_name'));
        return $this->fetch();
    }
    //接收上传海运提单信息
    public function getShipping(){        
        $request = Request::instance();
        if($request->isPost()){            
            $shippingNum = trim($request->post('shippingNum'));
            $goodsWeight = trim($request->param('shipgoodsWeight'));
            //获取用户ID,用以创建一个以用户ID为编号的文件夹,存放用户上传的信息.
            $id = Session::get('id');
            $files = $_FILES;
            if(!$id || !$shippingNum || !$files || !$goodsWeight){
                $this->error("上传信息有误,请核查后再上传!");
                die();
            }else if(!empty($files)){
            //这里先将部分数据插入数据表 oceanportbill 空运提单表中.
            //先根据用户ID和oceanid查一次,看该提单是否已经上传过.如果已经上传就不用再插入一次数据
                $res= Db::table('oceanportbill')->where('userid',$id)->where('oceanid',$shippingNum)->select();               
                if(!$res){
                    $data = ['id'=>'','oceanid'=>$shippingNum,'userid'=>$id];
                    $res = Db::table('oceanportbill')->insert($data);
                    if(!$res){
                        $this->error("数据异常701,请联系网站管理员!");
                        die();
                    }
                }   
               //先判断,该文件夹是否存在,如果存在就不创建.
                $path = ROOT_PATH.'public/uploads/oceanport/'.$id;
                if(!is_dir($path)){
                    mkdir(ROOT_PATH.'public/uploads/oceanport/'.$id);//以用户ID创建一个文件夹.
                }
                //再以用户上传的运单号创建一个文件夹,将该运单号对应的文档存入进入.
                $filepath = $path.'/'.$shippingNum;
                if(!is_dir($filepath)){                
                    mkdir($path.'/'.$shippingNum);
                }
                $info = $files['filename'];
                $flag = 0;
                $mark = 0;
                for($i=0;$i<count($info['name']);$i++){
                    $mark+=1;
                    if($info['type'][$i] == "application/pdf"){
                        $name = md5($id.$shippingNum.$i).'.pdf';
                        if(file_exists($filepath.'/'.$name)){
                            unlink($filepath.'/'.$name);
                        }
                        //对于上传的excel表,还需要检查表中的部分数据,后续还要增加一个读取excel表功能.
                    }else if($info['type'][$i]=='application/vnd.ms-excel'){
                        //上传的文件为xls格式时,type为application/vnd.ms-excel
                        $name = md5($id.$shippingNum.$i).'.xls';
                        if(file_exists($filepath.'/'.$name)){
                           unlink($filepath.'/'.$name);
                        }
                    }else if($info['type'][$i]=='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
                        //上传的文件为xlsx格式时,type为application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
                        $name = md5($id.$shippingNum.$i).'.xlsx';
                        if(file_exists($filepath.'/'.$name)){
                           unlink($filepath.'/'.$name);
                        }
                    }else{
                        $this->error("上传的文件格式有误!");
                        die();
                    }
                    $tmp_name = $info['tmp_name'][$i];
                    $result = move_uploaded_file($tmp_name,$filepath.'/'.$name);
                    if($result) $flag+=1;
                    //这里根据上传文件名,判断是pdf还是excel,然后分别存储.
                    if($info['type'][$i] == "application/pdf"){                    
                        $res1 = Db::table('oceanportbill')->where('oceanid',$shippingNum)->where('userid',$id)->update(['oceanpdf'=>$filepath.'/'.$name,'updatetime'=>time()]);                       
                        if(!$res1){
                            $this->error("数据异常702,请联系网站管理员!");
                            die();
                        }
                    }else{
                        if($mark!==3){
                            $res2 = Db::table('oceanportbill')->where('oceanid',$shippingNum)->where('userid',$id)->update(['oceanexcel'=>$filepath.'/'.$name,'updatetime'=>time()+1]);
                            if(!$res2){
                                $this->error("数据异常703,请联系管理员!");
                                die();
                            }
                        }else{
                            if(!$this->getExcel($filepath.'/'.$name,$goodsWeight)){
                                $this->delDirAndFile($filepath);
                                Db::table('oceanportbill')->where('oceanid',$shippingNum)->delete();
                                $this->error("商品总重量与原始发货文件中总重量相差较大,不在允许范围之内,请核对后重新上传!");
                                die;
                            }else if($this->getExcel($filepath.'/'.$name,$goodsWeight) == 1){
                                $this->delDirAndFile($filepath);
                                Db::table('oceanportbill')->where('oceanid',$shippingNum)->delete();
                                $this->error("上传失败!excel格式不正确!");
                                die;
                            }else{
                                $res3 = Db::table('oceanportbill')->where('oceanid',$shippingNum)->where('userid',$id)->update(['originalexcel'=>$filepath.'/'.$name,'updatetime'=>time()+1]);
                                if(!$res3){
                                    $this->error("数据异常703,请联系管理员!");
                                    die;
                                }
                            }
                        }
                    }
                }
                if($flag ==3 ){
                    Db::table('oceanportbill')->where('oceanid',$shippingNum)->where('userid',$id)->update(['isSendToCustom'=>0,'isSendToUser'=>0]);
                    $res = Db::table('oceanportbill')->where('oceanid',$shippingNum)->where('userid',$id)->value('status');
                    if(!$res){
                        Db::table('oceanportbill')->where('oceanid',$shippingNum)->where('userid',$id)->update(['uploadtime'=>time(),'status'=>'待到港']);
                    }                  
                    //上传完成后,修改海运提单状态表.
                    $data = [
                        'id'=>'',
                        'oceanid'=>$shippingNum,
                        'userid'=>$id,
                        'status'=>'待到港',
                        'modifytime'=>time(),                        
                        'operator'=>Session::get('user_name'),
                        'oceanlog'=>"您的提单即将到港!",
                        'opentouser'=>1,
                    ];                  

                    $res = Db::table('oceanportlog')->where('oceanid',$shippingNum)->where('userid',$id)->select();
                    if(empty($res)){
                        $res = Db::table('oceanportlog')->insert($data);
                        if(!$res){
                            $this->error("数据有误,请联系网站管理员!");
                            die();
                        }
                    }else{
                        $status = $res[0]['status'];
                        $data = [
                        'id'=>'',
                        'oceanid'=>$shippingNum,
                        'userid'=>$id,
                        'status'=> $status,
                        'modifytime'=>time(),                        
                        'operator'=>Session::get('user_name'),
                        'oceanlog'=>"您修改了海运提单!",
                        'opentouser'=>1,
                        ]; 
                        $res = Db::table('oceanportlog')->insert($data);
                          if(!$res){
                            $this->error("数据有误,请联系网站管理员!");
                            die();
                        }
                    } 
                    $this->success("上传成功!");
                }
            }
        }
    }
    /*
     * 前台ajax校验 海运提单号
     */
    public function checkOceanid(){
        $request=Request::instance();      
        if($request->isAjax()){
            $oceanid = $request->param('oceanid');
            $userid = $request->param('userid');            
            $res = Db::table('oceanportbill')->where('oceanid',$oceanid)->where('userid',$userid)->select();
            if(!empty($res)){
                echo 1;
            }else{
                echo 0;
            }
        }
    }

    /*
     * 递归删除文件夹及文件夹的内容.
     * @param string $dir 文件夹路径
     * @return
     */
    public function delDirAndFile($dir){
        if($handle = opendir($dir)){
            while(false !== ($item = readdir($handle))){
                if($item != '.' && $item != '..'){
                    if(is_dir($dir.'/'.$item)){
                        $this->delDirAndFile($dir.'/'.$item);
                    }else{
                        unlink($dir.'/'.$item);                       
                    }
                }
            }
            rmdir($dir);
        }
    }
}