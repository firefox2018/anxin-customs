<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\View;
use PHPExcel\IOFactory;
use think\Db;
/*
 * 后台index控制器,显示后台首页
 */
class Index extends Controller{
// 显示后台首页
	public function index(){
		return $this->fetch();
	}
/*
 * 接收页面上传的 到港 提货信息
 */
    public function receive(){
        $request = Request::instance();
        if($request->isAjax()){
            $type = $request->param('type');
            //如果$type是 arrive 代表是在操作 到港 处理,如果是delivery就是 提货 处理.
            $file = $_FILES;
            if(!$type || !$file){
                echo "error1"; // 没有正常接收到页面上传的数据.
            }else{
                //接收到页面上传的数据,暂时保存到系统中. 供后面读取数据使用, 读取数据完毕后,就删除掉.
                $time = time();
                $path = ROOT_PATH.'public/uploads/admin/'.$time;
                if(!is_dir($path)){
                    mkdir(ROOT_PATH.'public/uploads/admin/'.$time);
                }
                //上传文件的类型,如果不是 application/vnd.openxmlformats-officedocument.spreadsheetml.sheet 或 application/vnd.ms-excel
                //就报错,说明上传的不是excel文件
                $fileType = $file['file']['type'];
                if($fileType == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
                    $filePath = $path.'/'.$time.'.xlsx' ;
                    if(!move_uploaded_file($file['file']['tmp_name'],$filePath)){
                        echo "erro2"; //系统错误,没有正确上传.
                        die;
                    }
                }else if($fileType=='application/vnd.ms-excel'){
                    $filePath = $path.'/'.$time.'.xlsx';
                    if(!move_uploaded_file($file['file']['tmp_name'],$filePath)){
                        echo "erro3"; //系统错误,没有正确上传.
                        die;
                    }
                }else{
                    echo "error4"; //上传文件格式不正确.
                    die;
                }
            }
            //文件上传到临时目录后, 接下来引用PHPExcel读取文件内容.更改提单的状态.
            $info = $this->getExcelContent($filePath,$type);
            $this->delDirAndDoc($path);
            echo  json_encode($info);
        }
    }
/*
 *读取excel信息
 * @param $path string excel路径
 * @param $type 操作类型, 如果是 arrive 就是操作 到港, 如果是 delivery就是操作提货.
 * @return  修改成功的提单数量,修改失败的提单详情.(包括修改失败的数量,提单号,对应的用户ID)
 */
	public function getExcelContent($path,$type){
        //对传入文件格式进行判断.
        $suffix = substr($path,strrpos($path,'.')+1);
        $param = '';
        switch($suffix){
            case 'xlsx':
                $param = 'Excel2007';
                break;
            case 'xls':
                $param = 'Excel5';
                break;
            case 'CSV':
                $param= "CSV";
                break;
            default:
                $param = "Excel2007";
        }
        //引入PHPExcel
        $reader = \PHPExcel\IOFactory::createReader($param);
        $phpexcel = $reader->load($path);
        $sheet = $phpexcel->getSheet(0); //读取整张表
        $highestRow = $sheet->getHighestRow(); //获取最大行
        $highestColumn = $sheet->getHighestColumn(); //获取最大列
        $airBillInfo = []; //记录读取到的空运提单信息
        $oceanBillInfo = []; //记录读取到的海运提单信息.
        $info = []; //记录上传修改失败的信息和成功的数量
        $sucNum = 0; //记录修改成功的提单数量.
        for($i=2;$i<=$highestRow;$i++){
            //循环读取excel表中每一行,每一列的数据
            $billType = (int)$sheet->getCell('A'.$i)->getCalculatedValue();  //读取提单类型 1为空运提单, 2为海运提单.
            $userId = (int)$sheet->getCell('B'.$i)->getCalculatedValue();//读取提单对应的用户ID
            $billNum = $sheet->getCell('C'.$i)->getCalculatedValue();//读取提单号.
            if($billType!==1 && $billType !==2 ){
                $info[$i] = ['userid'=>$userId,'billNum'=>$billNum,'reason'=>'信息有误,请检查后重新上传!'];
                continue;
            }else{
                //将空运提单,和海运提单分开处理.
                if($billType==1){
                    $airBillInfo[$i] = ['userId'=>$userId,'airId'=>$billNum];
                }else if($billType==2){
                    $oceanBillInfo[$i] =  ['userId'=>$userId,'oceanId'=>$billNum];
                }
            }
        }
        //数据全部读取完后, 进行最后的判断,并修改数据库数据.
        //处理空运提单.
        if(!empty($airBillInfo)){
           foreach($airBillInfo as $k=>$v){
               $userId = $v['userId'];
               $airId = $v['airId'];
               $sql = "select id,userid,airid from airportbill where userid='$userId' and airid ='$airId' ";
               $res = Db::query($sql);
               if(!$res){  //如果没有查询到结果,说明上传的用户ID或者提单号码有误,记录错误信息.
                   $info[$k] = ['userid'=>$userId,'billNum'=>$airId,'reason'=>'上传的ID或者空运提单号有误!'];
                   continue;
               }else{
                   //如果查询到说明是正常的,就更改数据库.
                   //如果$type== 'arrive'就说明是在操作到港.
                   if($type == 'arrive'){
                       $res1=Db::table('airportbill')->where('id',$res[0]['id'])->update(['status'=>'已到港','updatetime'=>time()]);
                       if(!$res1){
                           //更改失败!
                           $info[$k] = ['userid'=>$userId,'billNum'=>$airId,'reason'=>'系统错误,请联系网站管理员'];
                           continue;
                       }else{
                            $data = [
                                'id'=>'',
                                'airid'=>$airId,
                                'userid'=>$userId,
                                'status'=>'已到港',
                                'modifytime'=>time(),
                                'operator'=>'admin', //后面要改成 后台当前登录的用户名.
                                'airlog'=>'您的提单已到达目的港!',
                                'opentouser'=>1,
                            ];
                            Db::table('airportlog')->insert($data);
                           $sucNum+=1;
                       }
                   //如果$type=='delivery'就说明是在操作提货
                   }else if($type=='delivery'){
                       $res2=Db::table('airportbill')->where('id',$res[0]['id'])->update(['status'=>'已提货','updatetime'=>time()]);
                       if(!$res2){
                           //更改失败!
                           $info[$k] = ['userid'=>$userId,'billNum'=>$airId,'reason'=>'系统错误,请联系网站管理员'];
                           continue;
                       }else{
                           $data = [
                               'id'=>'',
                               'airid'=>$airId,
                               'userid'=>$userId,
                               'status'=>'已提货',
                               'modifytime'=>time(),
                               'operator'=>'admin', //后面要改成 后台当前登录的用户名.
                               'airlog'=>'已在目的港提货,即将清关!',
                               'opentouser'=>1,
                           ];
                           Db::table('airportlog')->insert($data);
                           $sucNum+=1;
                       }
                   }else if($type=='customs'){
                       $res2=Db::table('airportbill')->where('id',$res[0]['id'])->update(['status'=>'清关中','updatetime'=>time()]);
                       if(!$res2){
                           //更改失败!
                           $info[$k] = ['userid'=>$userId,'billNum'=>$airId,'reason'=>'系统错误,请联系网站管理员'];
                           continue;
                       }else{
                           $data = [
                               'id'=>'',
                               'airid'=>$airId,
                               'userid'=>$userId,
                               'status'=>'清关中',
                               'modifytime'=>time(),
                               'operator'=>'admin', //后面要改成 后台当前登录的用户名.
                               'airlog'=>'您的提单正在清关中,请耐心等候!',
                               'opentouser'=>1,
                           ];
                           Db::table('airportlog')->insert($data);
                           $sucNum+=1;
                       }
                   }
               }
           }
         /*  array_push($info,['successNum'=>$sucNum]);
            return $info;*/
        }
        //处理海运提单.
        if(!empty($oceanBillInfo)){
            foreach($oceanBillInfo as $k=>$v){
                $userId = $v['userId'];
                $oceanId = $v['oceanId'];
                $sql = "select id,userid,oceanid from oceanportbill where userid='$userId' and oceanid = '$oceanId' ";
                $res = Db::query($sql);
                if(!$res){
                    $info[$k] = ['userid'=>$userId,'billNum'=>$oceanId,'reason'=>'上传的ID或者海运提单有误'];
                    continue;
                }else{
                    //如果查询到说明是正常的,就更改数据库.
                    //如果$type== 'arrive'就说明是在操作到港.
                    if($type == 'arrive'){
                        $res1=Db::table('oceanportbill')->where('id',$res[0]['id'])->update(['status'=>'已到港','updatetime'=>time()]);
                        if(!$res1){
                            //更改失败!
                            $info[$k] = ['userid'=>$userId,'billNum'=>$oceanId,'reason'=>'系统错误,请联系网站管理员'];
                            continue;
                        }else{
                            $data = [
                                'id'=>'',
                                'oceanid'=>$oceanId,
                                'userid'=>$userId,
                                'status'=>'已到港',
                                'modifytime'=>time(),
                                'operator'=>'admin', //后面要改成 后台当前登录的用户名.
                                'oceanlog'=>'您的提单已到达目的港!',
                                'opentouser'=>1,
                            ];
                            Db::table('oceanportlog')->insert($data);
                            $sucNum+=1;
                        }
                        //如果$type=='delivery'就说明是在操作提货
                    }else if($type=='delivery'){
                        $res2=Db::table('oceanportbill')->where('id',$res[0]['id'])->update(['status'=>'已提货','updatetime'=>time()]);
                        if(!$res2){
                            //更改失败!
                            $info[$k] = ['userid'=>$userId,'billNum'=>$oceanId,'reason'=>'系统错误,请联系网站管理员'];
                            continue;
                        }else{
                            $data = [
                                'id'=>'',
                                'oceanid'=>$oceanId,
                                'userid'=>$userId,
                                'status'=>'已提货',
                                'modifytime'=>time(),
                                'operator'=>'admin', //后面要改成 后台当前登录的用户名.
                                'oceanlog'=>'已在目的港提货,即将清关!',
                                'opentouser'=>1,
                            ];
                            Db::table('oceanportlog')->insert($data);
                            $sucNum+=1;
                        }
                    }else if($type=='customs'){
                        $res2=Db::table('oceanportbill')->where('id',$res[0]['id'])->update(['status'=>'清关中','updatetime'=>time()]);
                        if(!$res2){
                            //更改失败!
                            $info[$k] = ['userid'=>$userId,'billNum'=>$oceanId,'reason'=>'系统错误,请联系网站管理员'];
                            continue;
                        }else{
                            $data = [
                                'id'=>'',
                                'oceanid'=>$oceanId,
                                'userid'=>$userId,
                                'status'=>'清关中',
                                'modifytime'=>time(),
                                'operator'=>'admin', //后面要改成 后台当前登录的用户名.
                                'oceanlog'=>'您的提单正在清关中,请耐心等候!',
                                'opentouser'=>1,
                            ];
                            Db::table('oceanportlog')->insert($data);
                            $sucNum+=1;
                        }
                    }
                }
            }
        }
        array_push($info,['successNum'=>$sucNum]);
        return $info;
    }

    /*
     * 递归删除目录以及目录下的文件
     * @param $dir string 要删除的文件路径.
     * @return
     */
    public function delDirAndDoc($dir){
       if($handle = opendir($dir)){
           while(false !== ($item = readdir($handle))){
                if($item !=='.' && $item !=='..'){
                    if(is_dir($dir.'/'.$item)){
                        $this->delDirAndDoc($dir.'/'.$item);
                    }else{
                        unlink($dir.'/'.$item);
                    }
                }
           }
           rmdir($dir);
       }
    }
}