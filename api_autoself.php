<?php
/*
 * 坐席自动外呼的API，输入参数：GET domainid 域ID，也即企业id  callerid 坐席号（预留给按坐席导入外呼号码队列时使用，目前没有使用）  返回 拨打的号码（从批量外呼号码队列中取）
 */
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
define('BYPASS_LOGIN',1);
require_once "Shoudian_db.php";
include_once 'Logger.php';
$Logger = new Logger( __DIR__.'/logs', LogLevel::DEBUG, array (
		'extension' => 'log', //扩展名
		'prefix' => 'apiAutoSelf_',
		'flushFrequency' => 5 //缓冲写日志的行数
));

//set debug，移到Shoudian_db.php中
//$debug = true; //true //false

function api_log($msg,$level="debug") {
	global $debug,$Logger;
	if (!$debug) 	return;
	$Logger->$level($msg);
}

if (empty($_GET['domainid']))
	exit();
$domainid = $_GET['domainid'];
$callerid = @$_GET['callerid'];
$redis = redisDB();
$disabled = $redis->get("task_disabled");
if (empty($disabled))
	$str = "SELECT `phone`,`taskid` FROM fs_phones WHERE `enabled`=1 AND `iscalled`=0 AND `domain_id`='$domainid' ORDER BY `level`,`taskid`,`id` LIMIT 1";
else
	$str = "SELECT `phone`,`taskid` FROM fs_phones WHERE `enabled`=1 AND `iscalled`=0 AND `domain_id`='$domainid' AND `taskid` NOT IN ($disabled) ORDER BY `level`,`taskid`,`id` LIMIT 1";
$result = $mysqli->query($str);
$phones = $result->fetch_row();
if (!empty($phones[0])){
	$mysqli->query("update fs_phones set `iscalled`=1 where `phone` = $phones[0] and `taskid` = $phones[1] limit 1");
	api_log("domain: $domainid caller: $callerid 提取： phone: $phones[0] @ taskid: $phones[1] ");
}else{
	api_log("domain: $domainid caller: $callerid 目前无数据 ");
	exit();
}
die($phones[0]);