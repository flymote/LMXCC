<?php
if (!defined('BYPASS_LOGIN') && empty($_SESSION['FSlmxusers'])){
	header("Location:./main.php");
	die("login first!!");
}

define("SYSDB_HOST",  'localhost'); //mysql数据库主机
define("SYSDB_USER", 'limx'); //mysql数据库用户名
define("SYSDB_PASSWORD",'limaoxiang'); //mysql数据库密码
define("SYSDB_MAINDB",'shoudian'); //mysql数据库名，业务数据（本系统的数据库）
define("SYSDB_FSDB",'freeswitch'); //mysql数据库名，freeswitch数据库（这是FS用ODBC访问的运行数据库，需修改FS使用mysql数据库，而后在这里被系统调用）
define("REDIS_HOST",'192.168.0.198'); //redis 主机
define("REDIS_PORT",6379); //redis 端口
define("REDIS_PASSWORD",'lmx'); //redis 密码
define("REDIS_DB",0); //redis 密码

//控制API的几个文件的log是否记录
$debug = true; //true //false

$mysqli = new mysqli(SYSDB_HOST, SYSDB_USER, SYSDB_PASSWORD, SYSDB_MAINDB);
// $mysqli = new mysqli('localhost', 'root', 'root', 'shoudian');
if ($mysqli->connect_error) {
    die('数据库 连接错误 (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
}

$mysqli->query("set names UTF8");

//设置返回数据类型 MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH. 
function result_fetch_all($result,$tag=MYSQLI_BOTH){
	if (empty($result))
		return false;
	$results = array();
	while (($row = $result->fetch_array($tag))!==false) {
		if (!$row) return $results;
		$results[] = $row;
	}
}

//建立于freeswitch的连接
function freeswitchDB(){
	$mysqli = new mysqli(SYSDB_HOST, SYSDB_USER, SYSDB_PASSWORD, SYSDB_FSDB);
	if ($mysqli->connect_error) {
		die('数据库 连接错误 (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
	}
	return $mysqli;
}

function redisDB(){
	if (class_exists('Redis')){
		try {
			$redis = new Redis();
			$redis->connect(REDIS_HOST, REDIS_PORT, 1, 'MA', 100);			
			if (REDIS_PASSWORD)
				$reply = $redis->auth(REDIS_PASSWORD);
			if (!$reply)
				die(REDIS_HOST." Master redis 身份验证失败！");
			$redis->select(REDIS_DB);
		} catch (Exception $e) {
			die( 'REDIS严重问题：无法操作redis服务器！错误代码 '. $e->getCode());
		}
	}else 
		die(' 没有配置好操作redis的环境，请先安装！');
	return $redis;
}
