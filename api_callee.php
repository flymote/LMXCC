<?php
/*
 * 被叫呼出号码的API，输入参数：GET domainid 域ID，也即企业id  callerid 主叫的用户id callee 被叫号码，输出参数：处理后的被叫号码，如果要拒绝呼出，输出0
 */
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
define('BYPASS_LOGIN',1);
require_once "Shoudian_db.php";
include_once 'Logger.php';
$Logger = new Logger( __DIR__.'/logs', LogLevel::DEBUG, array (
		'extension' => 'log', //扩展名
		'prefix' => 'apiCallee_',
		'flushFrequency' => 5 //缓冲写日志的行数
));

//set debug，移到Shoudian_db.php中
//$debug = true; //true //false

function api_log($msg,$level="debug") {
	global $debug,$Logger;
	if (!$debug) 	return;
	$Logger->$level($msg);
}

api_log(json_encode($_GET));

//已经指定返回为0，表示拒绝呼出！其他返回为呼出的被叫号码
echo "013";