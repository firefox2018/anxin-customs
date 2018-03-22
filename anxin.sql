
CREATE DATABASE anxin_test ;
//用户表
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(6)  NOT NULL  AUTO_INCREMENT COMMENT '用户ID',
  `user_name` varchar(20) NOT NULL DEFAULT  '' COMMENT '用户名',
  `user_pwd` varchar(20) NOT NULL DEFAULT  '' COMMENT '用户密码',
  `user_email` varchar(20) NOT NULL DEFAULT  '' COMMENT '邮箱',
  `user_phone` varchar(20) NOT NULL DEFAULT  '' COMMENT '手机号码',
  `user_createtime` varchar(20) NOT NULL DEFAULT  '' COMMENT '创建时间',
  `user_status` smallint(1) NOT NULL  "用户激活状态,0:未激活,1:已激活",
  `salt` varchar(50) NOT NULL DEFAULT  '' COMMENT '用户验证加盐',
  `modifytime` varchar(15) NOT NULL DEFAULT  0 COMMENT "验证邮件发送时间",
  `regtime` varchar(15) NOT NULL DEFAULT  0 COMMENT "发送注册邮件时间",
  `lastlogintime` varchar(15) NOT NULL DEFAULT  0 COMMENT "上次登陆时间",
  `lastlogouttime` varchar(15) NOT NULL DEFAULT  0 COMMENT "上次退出时间",
  PRIMARY KEY(`id`)
 ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=UTF8;

//空运提单表
DROP TABLE IF EXISTS `airportbill`;
CREATE TABLE `airportbill`(
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一编号',
`airid` varchar(40) NOT NULL COMMENT '空运提单id',
`airpdf` varchar(40) NOT NULL COMMENT '空运提单的pdf文件',
`airexcel` varchar(40) NOT NULL COMMENT '空运提单excel文件',
`userid` int(11) NOT NULL COMMENT '对应的用户ID',
`uploadtime` varchar(20) NOT NULL DEFAULT 0 COMMENT '上传提单时间',
`updatetime` varchar(20) NOT NULL DEFAULT 0 COMMENT '修改提单时间',
PRIMARY KEY(`id`)
)ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

//空运提单状态表
DROP TABLE IF EXISTS `airportlog`;
CREATE TABLE `airportlog`(
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一编码id',
`airid` varchar(40) NOT NULL COMMENT '空运提单id',
`userid` int(11) NOT NULL COMMENT '对应的用户ID',
`status` varchar(10) NOT NULL COMMENT '空运提单状态',
`modifytime` varchar(15) NOT NULL COMMENT '提单状态更改时间',
`payment` char(1) NOT NULL DEFAULT '否' COMMENT '付款状态',
PRIMARY KEY(`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

//海运提单表
DROP TABLE IF EXISTS `oceanportbill`;
CREATE TABLE `oceanportbill`(
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一编号',
`oceanid` varchar(40) NOT NULL COMMENT '海运提单id',
`oceanpdf` varchar(40) NOT NULL COMMENT '海运提单的pdf文件',
`oceanexcel` varchar(40) NOT NULL COMMENT '海运提单excel文件',
`userid` int(11) NOT NULL COMMENT '对应的用户ID',
`uploadtime` varchar(20) NOT NULL DEFAULT 0 COMMENT '上传提单时间',
`updatetime` varchar(20) NOT NULL DEFAULT 0 COMMENT '修改提单时间',
PRIMARY KEY(`id`)
)ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

//海运提单状态表
DROP TABLE IF EXISTS `oceanportlog`;
CREATE TABLE `oceanportlog`(
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一编码id',
`oceanid` varchar(40) NOT NULL COMMENT '海运提单id',
`userid` int(11) NOT NULL COMMENT '对应的用户ID',
`status` varchar(10) NOT NULL COMMENT '海运提单状态',
`modifytime` varchar(15) NOT NULL COMMENT '提单状态更改时间',
`payment` char(1) NOT NULL DEFAULT '否' COMMENT '付款状态',
PRIMARY KEY(`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

//用户操作日志,记录后台管理员对用户的操作,和用户自己对提单的操作,对账户充值的操作
DROP TABLE IF EXISTS `userslog`;
CREATE TABLE `userslog` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一编码ID',
`userid` int(11) NOT NULL COMMENT '用户ID号',
`username` varchar(255) NOT NULL COMMENT '用户名',
`relname` varchar(255) NOT NULL COMMENT '真实姓名',
`logs` varchar(255) NOT NULL COMMENT '操作日志',
`operator` varchar(255) NOT NULL COMMENT '操作人',
`operatetime` varchar(255) NOT NULL COMMENT '操作时间',
PRIMARY KEY(`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

//公告表
DROP TABLE IF EXISTS `notice`;
CREATE TABLE `notice` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一编码ID',
`title` varchar(255) NOT NULL COMMENT '公告标题',
`content` text NOT NULL COMMENT '公告内容',
`istop` char(3) NOT NULL DEFAULT '是' COMMENT '是否置顶',
`addtime` varchar(25) NOT NULL COMMENT '公告添加时间',
`updatetime` varchar(25) NOT NULL COMMENT '更新时间',
PRIMARY KEY(`id`)
)ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

//管理员表
DROP TABLE IF EXISTS  `manager`;
CREATE TABLE `manager` (
`mg_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一标识ID',
`mg_name` varchar(255) NOT NULL COMMENT '用户名',
`mg_pwd` varchar(255) NOT NULL COMMENT '用户密码',
`mg_time` int(10) unsigned NOT NULL COMMENT '创建时间',
`mg_role_id` tinyint(3) NOT NULL DEFAULT '0' COMMENT '角色ID',
PRIMARY KEY(`mg_id`)
)ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
//权限表
/*全路径用于排序
1.如果是顶级权限,全路径等于本条记录主键值. 这个主键就是按照顶级权限顺序来插入的
2.如果不是顶级权限,全路径等于父级全路径-本条记录主键值
一般就只有2层权限,父级就是顶级权限了,  例如这样的权限:
关于我们
新闻中心
产品中心
   产品展示
   最新产品
   产品分类
  这其中前面3条是顶级权限, 全路径分别为1 2 3 , 产品展示为次级权限, .
  /*
顶级权限级别为0 次级权限级别为1 权限用于呈现缩进关系使用. 这是 竖排 权限使用,如果所有权限是横排的, 下拉形式的就不需要了.
 */
 */
DROP TABLE IF EXISTS `auth`;
CREATE TABLE `auth` (
`auth_id` int(11) NOT NULL AUTO_INCREMENT,
`auth_name` varchar(255) NOT NULL COMMENT '名称',
`auth_pid` smallint(6) NOT NULL COMMENT '父ID',
`auth_c` varchar(32) NOT NULL DEFAULT '' COMMENT '控制器',
`auth_a` varchar(32) NOT NULL DEFAULT  '' COMMENT '操作方法',
`auth_path` varchar(32) NOT NULL COMMENT '全路径',
`auth_level` tinyint(4) NOT NULL DEFAULT '0' COMMENT '级别',
PRIMARY KEY(`auth_id`)
)ENGINE=MyISAM  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

//角色表
DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
`role_id` int(11) NOT NULL AUTO_INCREMENT,
`role_name` varchar(32) NOT NULL COMMENT '角色名称',
`role_auth_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '权限ids',
`role_auth_ac` text  COMMENT '权限-操作',
PRIMARY KEY(`role_id`)
)ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



