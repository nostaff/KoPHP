-- phpMyAdmin SQL Dump
-- version 2.11.7
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2009 年 09 月 23 日 15:38
-- 服务器版本: 5.0.67
-- PHP 版本: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `game`
--

-- --------------------------------------------------------

--
-- 表的结构 `acl_privilege`
--

DROP TABLE IF EXISTS `acl_privilege`;
CREATE TABLE IF NOT EXISTS `acl_privilege` (
  `privilegeid` smallint(5) unsigned NOT NULL auto_increment,
  `code` varchar(20) collate utf8_unicode_ci NOT NULL,
  `name` varchar(50) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`privilegeid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ACL Privileges' AUTO_INCREMENT=28 ;

--
-- 导出表中的数据 `acl_privilege`
--

-- --------------------------------------------------------

--
-- 表的结构 `acl_role`
--

DROP TABLE IF EXISTS `acl_role`;
CREATE TABLE IF NOT EXISTS `acl_role` (
  `roleid` tinyint(3) unsigned NOT NULL auto_increment,
  `name` varchar(32) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`roleid`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ACL Roles' AUTO_INCREMENT=9 ;

--
-- 导出表中的数据 `acl_role`
--

INSERT INTO `acl_role` (`roleid`, `name`) VALUES
(1, '系统管理员'),
(2, '高级管理员'),
(3, '操作员'),
(4, '编辑'),
(5, '用户');

-- --------------------------------------------------------

--
-- 表的结构 `acl_role_privilege`
--

DROP TABLE IF EXISTS `acl_role_privilege`;
CREATE TABLE IF NOT EXISTS `acl_role_privilege` (
  `roleid` tinyint(3) unsigned NOT NULL,
  `privilegeid` smallint(5) unsigned NOT NULL,
  PRIMARY KEY  (`roleid`,`privilegeid`),
  KEY `privilegeid` (`privilegeid`),
  KEY `roleid` (`roleid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ACL Role Privileges';

--
-- 导出表中的数据 `acl_role_privilege`
--


-- --------------------------------------------------------

--
-- 表的结构 `acl_user`
--

DROP TABLE IF EXISTS `acl_user`;
CREATE TABLE IF NOT EXISTS `acl_user` (
  `userid` smallint(5) NOT NULL auto_increment,
  `name` varchar(50) character set utf8 collate utf8_bin default NULL,
  `loginname` varchar(30) NOT NULL,
  `password` varchar(32) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `email` varchar(50) NOT NULL,
  `addtime` datetime NOT NULL,
  `uptime` datetime NOT NULL,
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `loginname` (`loginname`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- 导出表中的数据 `acl_user`
--

INSERT INTO `acl_user` (`userid`, `name`, `loginname`, `password`, `enabled`, `email`, `addtime`, `uptime`) VALUES
(1, 'admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', 1, 'nostaff@sina.com', '0000-00-00 00:00:00', '2009-04-01 23:02:21'),
(2, 'wangzhenhua', 'nostaff', '21c31bde62a7b145c98ea6c07f99575a', 1, 'nostaff@sina.com', '2009-03-31 15:57:51', '2009-04-02 20:44:26'),
(3, 'test10230', 'test10231', 'ef99c0f3a35ff3ff3eed2f9906a68f6e', 1, 'test@test.com', '2009-09-23 11:19:07', '0000-00-00 00:00:00'),
(4, 'test30308', 'test4301', 'ef99c0f3a35ff3ff3eed2f9906a68f6e', 1, 'test@test.com', '2009-09-23 11:19:32', '0000-00-00 00:00:00'),
(5, 'test19842', 'test19603', 'ef99c0f3a35ff3ff3eed2f9906a68f6e', 1, 'test@test.com', '2009-09-23 11:19:53', '0000-00-00 00:00:00'),
(6, 'test5328', 'test5328', 'ef99c0f3a35ff3ff3eed2f9906a68f6e', 1, 'test@test.com', '2009-09-23 11:20:31', '0000-00-00 00:00:00'),
(7, 'test27849', 'test27849', '5f4dcc3b5aa765d61d8327deb882cf99', 1, 'test@test.com', '2009-09-23 11:28:45', '2009-09-23 11:28:45'),
(8, 'test16334', 'test16334', '5f4dcc3b5aa765d61d8327deb882cf99', 1, 'test@test.com', '2009-09-23 11:29:18', '2009-09-23 11:29:18'),
(9, 'test7919', 'test7919', '5f4dcc3b5aa765d61d8327deb882cf99', 1, 'test@test.com', '2009-09-23 11:30:06', '2009-09-23 11:30:06');

-- --------------------------------------------------------

--
-- 表的结构 `acl_user_role`
--

DROP TABLE IF EXISTS `acl_user_role`;
CREATE TABLE IF NOT EXISTS `acl_user_role` (
  `userid` smallint(5) unsigned NOT NULL,
  `roleid` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`roleid`,`userid`),
  KEY `acl_user_id` (`userid`),
  KEY `acl_role_id` (`roleid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ACL User Roles';

--
-- 导出表中的数据 `acl_user_role`
--

INSERT INTO `acl_user_role` (`userid`, `roleid`) VALUES
(1, 1),
(2, 1),
(1, 2),
(2, 3);

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- 表的结构 `userinfo`
--

DROP TABLE IF EXISTS `userinfo`;
CREATE TABLE IF NOT EXISTS `userinfo` (
  `userid` int(11) NOT NULL auto_increment COMMENT '用户编号',
  `channelid` smallint(5) unsigned default NULL COMMENT '频道ID',
  `productid` smallint(5) NOT NULL COMMENT '产品ID',
  `projectid` smallint(5) NOT NULL COMMENT '项目ID',
  `name` varchar(10) NOT NULL COMMENT '姓名',
  `title` varchar(10) NOT NULL COMMENT '头衔',
  `sex` tinyint(1) NOT NULL COMMENT '性别',
  `age` smallint(2) NOT NULL COMMENT '年龄',
  `career` varchar(30) default NULL COMMENT '职业',
  `job` varchar(30) NOT NULL COMMENT '职位',
  `province` varchar(30) NOT NULL COMMENT '省份',
  `city` varchar(30) NOT NULL COMMENT '城市',
  `birthday` date default NULL COMMENT '出生年月',
  `degree` varchar(10) default NULL COMMENT '学历',
  `cardno` varchar(18) default NULL COMMENT '身份证号码',
  `unit` varchar(200) NOT NULL COMMENT '工作单位',
  `department` varchar(30) NOT NULL COMMENT '所在部门',
  `focus` varchar(50) NOT NULL COMMENT '研究方向',
  `phone` varchar(50) NOT NULL COMMENT '电话',
  `mobile` varchar(50) NOT NULL COMMENT '移动电话',
  `im` varchar(50) NOT NULL COMMENT '即时通讯',
  `address` varchar(100) NOT NULL COMMENT '联系地址',
  `postcode` varchar(6) NOT NULL COMMENT '邮政编码',
  `email` varchar(50) NOT NULL COMMENT '邮箱地址',
  `sso_id` int(10) NOT NULL COMMENT '通行证ID',
  `sso_loginname` varchar(20) NOT NULL COMMENT '通行证用户名',
  `adduser` smallint(5) NOT NULL COMMENT '添加人',
  `upuser` smallint(5) NOT NULL COMMENT '更新人',
  `addtime` datetime NOT NULL COMMENT '添加时间',
  `uptime` datetime NOT NULL COMMENT '更新时间',
  `status` tinyint(1) NOT NULL COMMENT '审核状态',
  PRIMARY KEY  (`userid`),
  KEY `cpps` (`channelid`,`productid`,`projectid`,`status`),
  KEY `channelid` (`channelid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- 导出表中的数据 `userinfo`
--

