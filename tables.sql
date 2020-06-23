/*
SQLyog Ultimate v12.5.0 (64 bit)
MySQL - 5.7.19 : Database - shoudian
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`shoudian` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `shoudian`;

/*Table structure for table `e_mobilearea` */

DROP TABLE IF EXISTS `e_mobilearea`;

CREATE TABLE `e_mobilearea` (
  `id` int(11) NOT NULL,
  `mobileprefix` varchar(50) DEFAULT NULL,
  `areacode` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `memo` varchar(100) DEFAULT NULL,
  `sp` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobileprefix` (`mobileprefix`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `e_runtime` */

DROP TABLE IF EXISTS `e_runtime`;

CREATE TABLE `e_runtime` (
  `userid` bigint(20) NOT NULL COMMENT '用户ID',
  `limitmoney` double DEFAULT NULL COMMENT '排队动态金额',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `runtimedata` varchar(1500) DEFAULT NULL COMMENT '序列化的运行变量',
  PRIMARY KEY (`userid`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

/*Table structure for table `fs_calling` */

DROP TABLE IF EXISTS `fs_calling`;

CREATE TABLE `fs_calling` (
  `Timestamp` bigint(20) unsigned NOT NULL,
  `UUID` char(36) NOT NULL,
  `Event-Name` varchar(25) NOT NULL,
  `Channel-State` varchar(25) DEFAULT NULL,
  `Answer-State` varchar(25) DEFAULT NULL,
  `Hangup-Cause` varchar(25) DEFAULT NULL,
  `other-UUID` char(36) DEFAULT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

/*Table structure for table `fs_crm` */

DROP TABLE IF EXISTS `fs_crm`;

CREATE TABLE `fs_crm` (
  `phone` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sex` tinyint(1) NOT NULL DEFAULT '0',
  `place` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `age` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `job` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `edu` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modi` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `memo` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domainid` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`phone`,`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `fs_dialplans` */

DROP TABLE IF EXISTS `fs_dialplans`;

CREATE TABLE `fs_dialplans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ext-id` int(32) unsigned NOT NULL COMMENT '对应extensionID',
  `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '当前计划的优先级',
  `prefix` varchar(20) DEFAULT NULL COMMENT '拨号前缀数字',
  `enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可用',
  `act` varchar(1200) DEFAULT NULL COMMENT '动作内容',
  `condition` varchar(500) DEFAULT NULL COMMENT '条件',
  `destnumber-len` varchar(5) DEFAULT '15' COMMENT '拨号长度,用2,12表示2-12',
  `recording` varchar(500) DEFAULT NULL COMMENT '录音参数',
  `gateway` varchar(300) DEFAULT NULL COMMENT '路由参数',
  `break` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ext-name` (`ext-id`,`level`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_domains` */

DROP TABLE IF EXISTS `fs_domains`;

CREATE TABLE `fs_domains` (
  `domain_id` varchar(100) NOT NULL COMMENT '域名ID，指FS的域标识，唯一',
  `domain_name` varchar(20) NOT NULL COMMENT '域名称中文',
  `enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可用',
  `level` tinyint(1) NOT NULL DEFAULT '50' COMMENT '域等级，留做分类等',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级域的ID，域数据的ID，进行域的分组',
  `create_date` datetime DEFAULT NULL COMMENT '创建时间',
  `last_date` datetime DEFAULT NULL COMMENT '最后修改时间',
  `user_prefix` varchar(4) NOT NULL DEFAULT '8' COMMENT '用户ID前缀',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_prefix` varchar(4) NOT NULL DEFAULT '9' COMMENT '组的前缀数字',
  `DID` int(10) unsigned NOT NULL COMMENT '设置域的DID号码，也是callcenter调用号',
  `agent_login` smallint(5) unsigned DEFAULT '50' COMMENT '坐席签入号码',
  `agent_out` smallint(5) unsigned DEFAULT '51' COMMENT '坐席签出号码',
  `agent_break` smallint(5) unsigned DEFAULT '52' COMMENT '坐席示忙号码',
  `callcenter_config` varchar(2000) DEFAULT NULL COMMENT '呼叫中心的配置',
  `ivr_config` varchar(2000) DEFAULT NULL COMMENT 'ivr菜单定义',
  `out_prefix` varchar(4) NOT NULL DEFAULT '7' COMMENT '呼出前缀',
  `out_config` varchar(200) DEFAULT NULL COMMENT '呼出配置',
  `autocall_self` smallint(5) unsigned NOT NULL DEFAULT '6' COMMENT '坐席自动外呼号码',
  `autocall_lines` int(10) unsigned DEFAULT '3' COMMENT '自动外呼并发数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `DID` (`DID`),
  UNIQUE KEY `domain_id` (`domain_id`),
  KEY `level` (`level`),
  KEY `parent_id` (`parent_id`),
  KEY `enabled` (`enabled`,`parent_id`,`level`,`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_extensions` */

DROP TABLE IF EXISTS `fs_extensions`;

CREATE TABLE `fs_extensions` (
  `ext-name` varchar(100) NOT NULL COMMENT '本extension的名称',
  `context-name` varchar(50) DEFAULT NULL COMMENT '本extension隶属context标识',
  `ext-continue` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否继续，0 false 1 true',
  `enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可用',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ext-level` tinyint(1) DEFAULT '10' COMMENT '本extension的优先级',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ext-name` (`ext-name`),
  KEY `enabled` (`context-name`,`enabled`,`ext-level`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_gateways` */

DROP TABLE IF EXISTS `fs_gateways`;

CREATE TABLE `fs_gateways` (
  `gatewayname` varchar(32) NOT NULL,
  `realm` varchar(100) DEFAULT NULL COMMENT '域名',
  `username` varchar(30) DEFAULT NULL COMMENT '认证的用户名',
  `password` varchar(30) DEFAULT NULL COMMENT '认证的密码',
  `from-user` varchar(30) DEFAULT NULL COMMENT '指定在SIP消息中的源用户信息，没有配置则默认和username相同',
  `from-domain` varchar(30) DEFAULT NULL COMMENT '指定域，它们会影响SIP中的“From”头域。',
  `regitster-proxy` varchar(30) DEFAULT NULL COMMENT '表示注册的地址',
  `outbound-proxy` varchar(30) DEFAULT NULL COMMENT '表示呼出时指向的地址，这里其实和注册地址是一致的',
  `register` varchar(5) DEFAULT NULL COMMENT '是否注册',
  `expire-seconds` int(11) DEFAULT NULL COMMENT '注册的间隔时间',
  `domain_id` varchar(100) DEFAULT NULL COMMENT '关联的域',
  `enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可用',
  `caller-id-in-from` varchar(5) DEFAULT NULL COMMENT 'Use the callerid of an inbound call in the from field on outbound calls via this gateway',
  `extension` varchar(30) DEFAULT NULL COMMENT 'extension for inbound calls: same as username, if blank.',
  `proxy` varchar(30) DEFAULT NULL COMMENT 'proxy host,same as realm, if blank',
  `register-transport` varchar(5) DEFAULT NULL COMMENT 'which transport to use for register,udp or tcp',
  `retry-seconds` int(11) DEFAULT NULL COMMENT 'How many seconds before a retry when a failure or timeout occurs',
  `contact-params` varchar(200) DEFAULT NULL COMMENT 'extra sip params to send in the contact',
  `ping` int(11) DEFAULT NULL COMMENT 'send an options ping every x seconds, failure will unregister and/or mark it down',
  `domain_user` varchar(30) DEFAULT NULL COMMENT '关联的域用户',
  `variables` varchar(500) DEFAULT NULL COMMENT '附加参数 <variables>',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `addon` varchar(500) DEFAULT NULL COMMENT '更多参数，输入格式如：<param name="extension-in-contact" value="true"/>',
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`,`domain_id`,`domain_user`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_groups` */

DROP TABLE IF EXISTS `fs_groups`;

CREATE TABLE `fs_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(20) NOT NULL COMMENT '名称',
  `group_id` varchar(20) NOT NULL COMMENT '标识，仅限数字',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `domain_id` varchar(100) NOT NULL COMMENT '所隶属的域标识',
  `calltype` varchar(2) DEFAULT NULL COMMENT '串行+F，+A同振；如${group_call(200@${domain_name}+F)}',
  `calltimeout` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_id` (`group_id`),
  KEY `enabled` (`enabled`,`domain_id`),
  KEY `domain_id` (`domain_id`,`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_phones` */

DROP TABLE IF EXISTS `fs_phones`;

CREATE TABLE `fs_phones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `enabled` tinyint(1) DEFAULT NULL,
  `iscalled` tinyint(1) DEFAULT NULL,
  `taskid` int(10) unsigned NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `level` tinyint(4) DEFAULT NULL,
  `datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `domain_id` varchar(100) NOT NULL,
  KEY `id` (`id`),
  KEY `enabled` (`enabled`,`iscalled`,`type`,`phone`),
  KEY `taskid` (`taskid`,`iscalled`),
  KEY `getphones` (`enabled`,`iscalled`,`domain_id`,`level`,`taskid`,`id`),
  KEY `phone` (`phone`,`domain_id`),
  KEY `iscalled` (`iscalled`,`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=22945 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_setting` */

DROP TABLE IF EXISTS `fs_setting`;

CREATE TABLE `fs_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ESL_host` varchar(50) DEFAULT NULL,
  `ESL_port` int(10) unsigned DEFAULT NULL,
  `ESL_password` varchar(20) DEFAULT NULL,
  `conf_dir` varchar(100) DEFAULT NULL,
  `log_dir` varchar(100) DEFAULT NULL,
  `core_uuid` varchar(40) DEFAULT NULL,
  `internal_sip_port` int(10) unsigned DEFAULT NULL,
  `external_sip_port` int(10) unsigned DEFAULT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 无效 1 有效 9 主控',
  `version` varchar(150) DEFAULT NULL,
  `recordings_dir` varchar(100) DEFAULT NULL,
  `sounds_dir` varchar(100) DEFAULT NULL,
  `default_provider` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_tasks` */

DROP TABLE IF EXISTS `fs_tasks`;

CREATE TABLE `fs_tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL COMMENT '任务标识',
  `domain_id` varchar(100) NOT NULL COMMENT '隶属域',
  `type` varchar(20) NOT NULL COMMENT '话务类别如行业',
  `level` tinyint(4) NOT NULL DEFAULT '10' COMMENT '排序优先级',
  `phones` varchar(30) DEFAULT NULL COMMENT '电话号码csv文件',
  `sound` varchar(30) DEFAULT NULL COMMENT '任务播放语音文件',
  `tocc` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否转坐席',
  `enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用',
  `datetime` datetime NOT NULL COMMENT '任务创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_users` */

DROP TABLE IF EXISTS `fs_users`;

CREATE TABLE `fs_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(20) NOT NULL COMMENT '名称',
  `user_id` varchar(20) NOT NULL COMMENT '标识，仅限数字',
  `password` varchar(20) DEFAULT NULL COMMENT '密码',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `domain_id` varchar(100) NOT NULL COMMENT '所隶属的域标识(可直接隶属域)',
  `group_id` varchar(100) DEFAULT NULL COMMENT '所隶属的组标识',
  `reverse_user` varchar(30) DEFAULT NULL COMMENT '反向认证的用户名',
  `reverse_pwd` varchar(20) DEFAULT NULL COMMENT '反向认证的密码',
  `dial_str` varchar(200) DEFAULT NULL COMMENT '拨号串',
  `user_context` varchar(20) DEFAULT NULL COMMENT '用户指定context',
  `gateway` varchar(500) DEFAULT NULL COMMENT '用户自定义路由或指定某个路由',
  `variables` varchar(500) DEFAULT NULL COMMENT '用户自定义变量',
  `cidr` varchar(100) DEFAULT NULL COMMENT 'cidr设置',
  PRIMARY KEY (`id`),
  KEY `enabled` (`domain_id`,`enabled`),
  KEY `enabled_2` (`enabled`,`group_id`),
  KEY `group_id` (`group_id`),
  KEY `domain_id` (`domain_id`)
) ENGINE=MyISAM AUTO_INCREMENT=82 DEFAULT CHARSET=utf8;

/*Table structure for table `fs_xml_cdr` */

DROP TABLE IF EXISTS `fs_xml_cdr`;

CREATE TABLE `fs_xml_cdr` (
  `uuid` varchar(36) NOT NULL,
  `domain_uuid` varchar(50) DEFAULT NULL,
  `extension_uuid` varchar(200) DEFAULT NULL,
  `domain_name` varchar(50) DEFAULT NULL,
  `accountcode` varchar(50) DEFAULT NULL,
  `direction` varchar(50) DEFAULT NULL,
  `context` varchar(50) DEFAULT NULL,
  `orig_callee_number` varchar(50) DEFAULT NULL,
  `caller_id_name` varchar(50) DEFAULT NULL,
  `caller_id_number` varchar(50) DEFAULT NULL,
  `source_number` varchar(50) DEFAULT NULL,
  `destination_number` varchar(50) DEFAULT NULL,
  `start_epoch` decimal(10,0) DEFAULT NULL,
  `start_stamp` datetime DEFAULT NULL,
  `answer_stamp` datetime DEFAULT NULL,
  `answer_epoch` decimal(10,0) DEFAULT NULL,
  `end_epoch` decimal(10,0) DEFAULT NULL,
  `end_stamp` datetime DEFAULT NULL,
  `duration` decimal(10,0) DEFAULT NULL,
  `mduration` decimal(10,0) DEFAULT NULL,
  `billsec` decimal(10,0) DEFAULT NULL,
  `billmsec` decimal(10,0) DEFAULT NULL,
  `bridge_uuid` varchar(36) DEFAULT NULL,
  `read_codec` varchar(50) DEFAULT NULL,
  `read_rate` varchar(20) DEFAULT NULL,
  `write_codec` varchar(50) DEFAULT NULL,
  `write_rate` varchar(20) DEFAULT NULL,
  `network_addr` varchar(50) DEFAULT NULL,
  `sip_gateway` varchar(50) DEFAULT NULL,
  `leg` char(1) DEFAULT NULL,
  `pdd_ms` decimal(10,0) DEFAULT NULL,
  `rtp_audio_in_mos` decimal(10,0) DEFAULT NULL,
  `last_app` varchar(100) DEFAULT NULL,
  `last_arg` varchar(100) DEFAULT NULL,
  `cc_side` varchar(100) DEFAULT NULL,
  `cc_member_uuid` varchar(36) DEFAULT NULL,
  `cc_queue_joined_epoch` varchar(100) DEFAULT NULL,
  `cc_queue` varchar(100) DEFAULT NULL,
  `cc_member_session_uuid` varchar(36) DEFAULT NULL,
  `cc_agent` varchar(100) DEFAULT NULL,
  `cc_agent_type` varchar(100) DEFAULT NULL,
  `waitsec` decimal(10,0) DEFAULT NULL,
  `conference_name` varchar(100) DEFAULT NULL,
  `conference_uuid` varchar(36) DEFAULT NULL,
  `conference_member_id` varchar(100) DEFAULT NULL,
  `ip` varchar(200) DEFAULT NULL,
  `pin_number` varchar(100) DEFAULT NULL,
  `hangup_cause` varchar(100) DEFAULT NULL,
  `hangup_cause_q850` decimal(10,0) DEFAULT NULL,
  `sip_hangup_disposition` varchar(100) DEFAULT NULL,
  `xml` varbinary(6000) DEFAULT NULL,
  `orig_caller_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `time` (`start_stamp`,`answer_stamp`),
  KEY `start_epoch` (`start_epoch`,`billsec`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `login_log` */

DROP TABLE IF EXISTS `login_log`;

CREATE TABLE `login_log` (
  `last_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `IP` varchar(15) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `pwd` varchar(100) DEFAULT NULL,
  `APPID` varchar(30) NOT NULL,
  `url` varchar(400) DEFAULT NULL,
  `result` tinyint(1) DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `APPID` (`APPID`,`IP`,`last_time`),
  KEY `APPID_2` (`APPID`,`name`,`last_time`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;

/*Table structure for table `login_users` */

DROP TABLE IF EXISTS `login_users`;

CREATE TABLE `login_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(20) NOT NULL COMMENT '应用标志',
  `user` varchar(10) DEFAULT NULL,
  `pwd` varchar(20) DEFAULT NULL,
  `umin` double DEFAULT NULL,
  `umax` double DEFAULT NULL,
  `area` varchar(10) DEFAULT NULL,
  `mode` varchar(10) DEFAULT NULL,
  `isadm` tinyint(1) DEFAULT '0',
  `enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `app` (`app`,`enabled`,`user`,`pwd`,`area`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
