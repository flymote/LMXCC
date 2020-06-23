<?php
/*
 * 自动呼出程序的回调API，输入参数：GET domainid 域ID，也即企业id  callerid 主叫的用户id status 回写状态，taskid 任务id
 * taskid 无status， 输出任务语音 同时对本任务的answer状态加1，taskid、status 则表示给相应任务状态（status里面指定状态，如answer）加1
 * doaminid、callerid 为对相应号码做应答处理
 */
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
define('BYPASS_LOGIN',1);
require_once "Shoudian_db.php";
include_once 'Logger.php';
$Logger = new Logger( __DIR__.'/logs', LogLevel::DEBUG, array (
		'extension' => 'log', //扩展名
		'prefix' => 'apiAutoServ_',
		'flushFrequency' => 5 //缓冲写日志的行数
));

//set debug，移到Shoudian_db.php中
//$debug = true; //true //false

function api_log($msg,$level="debug") {
	global $debug,$Logger;
	if (!$debug) 	return;
	$Logger->$level($msg);
}

$redis = redisDB();
if ($redis && !empty($_GET['taskid'])){
	$tid = $_GET['taskid'];
	$sound = $redis->hMGet("task_$tid",['enabled','sound','domain_id']);
	if (isset($sound['enabled']) && $sound['enabled']){
		if (!empty($_GET['status'])){
			$redis->hIncrBy("task_$tid",$_GET['status'],1);
			die("");
		}elseif (!empty($sound['sound'])){
			$redis->hIncrBy("task_$tid","answer",1);
			api_log("外呼任务 $tid 获取任务语音 $sound[domain_id] / $sound[sound] ");
			die(__DIR__."/".$sound['domain_id']."/".$sound['sound']);
		}else{
			api_log("外呼任务 $tid 任务无语音 @ $sound[domain_id] ");
			die("");
		}
	}else
		api_log("外呼任务 $tid 不可用 enabled=0 ，无法 API 获取任务的信息！");
	die("");
}
if ($redis && !empty($_GET['domainid']) && !empty($_GET['callerid'])){
	$mysqli->query("update fs_phones set iscalled=1 where phone = '$_GET[callerid]' and domain_id='$_GET[domainid]'");
}