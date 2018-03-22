<?php
/**
 * Created by PhpStorm.
 * User: flyingfox717
 * Date: 2017/11/17
 * Time: 16:27
 */
namespace app\common\model;
use think\Model;
use think\Db;

class User extends Model{
    public function __construct(){
        parent::__construct();
    }
    //根据ID获取用户信息.
    public function getInfoById($id){
       return  $row = Db::table('users')->where('id',$id)->find();
    }
}