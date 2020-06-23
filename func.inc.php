<?php 
/* ！！！警告：：本文件已经调整为APPID选择模式，与其他控制不同~~~ ！！！！
即本程序中，login_users数据库注册的任意用户均可登录，登录时需选择APPID，使用pdo
注意：本处用户登录后返回的APPID将是登录用户所处的APPID！ */
$_login_comment_str_ = "";
if (!isset($login_mode))  // 如果USER 表示仅用于普通的用户登录，否则 表示进入后台权限管理模式
	$login_mode = "USER";
$appids = array("datamanage"=>"系统管理","lmxcc"=>"CC系统","FSlmx"=>"FS控制台");

function DB_init($DB='shoudian'){
	global $pdo;
//	if ($_SERVER['SERVER_ADDR']=='::1')
		try {	$pdo = new PDO("mysql:host=localhost;dbname=$DB","limx","limaoxiang");} catch (PDOException $e) { die('localhost 无法连接！');}
//		else{
//			try {
//				$pdo = new PDO("mysql:host=182.106.129.234;dbname=$DB","limx","limaoxiang");
//			} catch (PDOException $e) {
//				echo '69数据库 连接错误： ' . $e->getMessage();
//				echo ' (' . $pdo->errorCode() . ' ) '. $pdo->errorInfo();
//			}
//		}
		$pdo->query("set names UTF8");
		return $pdo;
}
function get_proxy_ip()
{
	$arr_ip_header = array(
			'HTTP_CDN_SRC_IP',
			'HTTP_PROXY_CLIENT_IP',
			'HTTP_WL_PROXY_CLIENT_IP',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
	);
	$client_ip = 'unknown';
	foreach ($arr_ip_header as $key)
	{
		if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != 'unknown')
		{
			$client_ip = $_SERVER[$key];
			break;
		}
	}
	return $client_ip;
}

function checkip(){
	global $limitIPs; //$limitIPS 是黑名单IP数组，由被拒绝使用的ip组成！如果需要禁止某些ip使用系统，仅需要将之记录在这个数组中即可
	if (empty($limitIPs))
		return;
	$ip = get_proxy_ip();
	define("USERLOGIN_IP", $ip);
	if (!in_array($ip, $limitIPs))
		die("$ip 不允许未注册的IP地址登录使用！");
}

function showlogin($label='登录系统前，请输入用户信息：',$mode='onlyuser'){
	global  $_login_comment_str_,$appids,$login_mode;
	if ($_login_comment_str_) $label = $_login_comment_str_;
	if ($login_mode == 'USER'){
		$label .= "<input name='APPID' value='".APPID."' type='hidden'>";
	}else{
	$label .= "<select name='APPID'>";
	foreach ($appids as $k=>$v)
		$label .= "<option value='$k' ".($k==APPID?"selected":"").">$v</option>";
	$label .= "</select>";
	}
	if (!headers_sent()){
		header("Content-type: text/html; charset=utf-8");
		echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/><link rel="stylesheet" href="css/login.css">
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script></head><body>
HTML;
	}
	$footdiv = '<div><button type="submit" class="btn" id="ulogin" onclick="this.value=\'请等待！... ... ...\';this.submit();">登陆</button></div>
<div class="sercopy smallgray smallsize-font" style=" text-align:right;">*忘记密码，请联系管理员 &nbsp; </div></form></div><div class="footer">©2019-2020 &nbsp; LMXCC @<a href="main.php" target="_blank">FSlmx</a> &nbsp; &nbsp; <a href="user_manage.php" target="_blank" class="smallsize-font">登录管理</a></div> ';
	if ($mode=='onlyuser')
		die('<div class="pubbox" style="margin-top:100pt;">
<div class="formbox"><form method="post" id="form1" name="form1"><div class="sercopy" style="color:#ff4f4f;">'.$label.'</div><div class="textbg"><input id="username" name="username" type="text" value="请输入用户名" onfocus="if (this.value ==\'请输入用户名\'){this.value =\'\';}" onblur="if (this.value ==\'\'){this.value=\'请输入用户名\';}"></div><div class="passwordbg"><input id="psd" name="psd" type="text" value="用户密码" onfocus="psw(this)" onblur="txt(this)"><script type="text/javascript">
function psw(el) {if (el.value == \'用户密码\') { el.value = \'\'; el.type = \'password\';  }} function txt(el) {if (!el.value) { el.type = \'text\'; el.value = \'用户密码\'; }} </script></div>'.$footdiv.'</div>    
</div></div></body></html>');
	else{
		if (empty($_GET['domainid']))
			$domain = '<div class="domainbg"><input id="domain" name="domainid" type="text" value="请输入域ID" onfocus="if (this.value ==\'请输入域ID\'){this.value =\'\';}" onblur="if (this.value ==\'\'){this.value=\'请输入域ID\';}"></div>';
		else
			$domain = "";
			die('<div class="main"><div class="topbg"><div class="logo"><img src="css/logo.png"></div></div><div class="pubbox"><div style="padding: 30px 10px 40px 68px;"><form method="post" id="form1" name="form1"><div class="sercopy" style="color:#ff4f4f;">'.$label.'</div>'.$domain.'<div class="textbg"><input id="username" name="username" type="text" value="请输入用户名" onfocus="if (this.value ==\'请输入用户名\'){this.value =\'\';}" onblur="if (this.value ==\'\'){this.value=\'请输入用户名\';}"></div>
<div class="passwordbg"><input id="psd" name="psd" type="text" value="用户密码" onfocus="psw(this)" onblur="txt(this)"><script type="text/javascript">
function psw(el) {if (el.value == \'用户密码\') { el.value = \'\'; el.type = \'password\';  }} function txt(el) {if (!el.value) { el.type = \'text\'; el.value = \'用户密码\'; }} </script></div>'.$footdiv.'</div></div></body></html>');
	}
}

function login($m='domain'){
	global $pdo,$_login_comment_str_,$login_mode;
	if (!defined('USERLOGIN_IP'))
		define("USERLOGIN_IP", get_proxy_ip());
	$user = @$_POST['username'];
	if (empty($user))
		return false;
	$pwd = @$_POST['psd'];
	$APPID = @$_POST['APPID'];
	if (empty($_POST['domainid']))
		$area = @$_GET['domainid'];
	else{
		$area = $_POST['domainid'];
		if ($area == '请输入域ID')
			$area = '';
	}
	$mode = @$_POST['mode'];
	if (empty($pdo))
		$pdo = DB_init();
	$user = trim($user);
	if (!isset($_SESSION['oauth_failure_count']))
		$_SESSION['oauth_failure_count'] = 0;
	elseif ($_SESSION['oauth_failure_count'] > 5){
		$_login_comment_str_ = "错误登录超过5次，已禁止登录！";
		return false;
	}
	$log = $pdo->prepare("select count(*) from login_log where `APPID`=? and `IP`=? and `last_time`>'".date("Y-m-d H:i:s",time()-86400)."' and result = 0");
	$log->execute(array($APPID,USERLOGIN_IP));
	if ($log->fetchColumn()>5){
		$_SESSION['oauth_failure_count'] = 99;
		$_login_comment_str_ = "本IP错误登录超过5次，已禁止登录！";
		return false;
	}else
		if ($m=='domain' && $login_mode=='USER' && empty($area)){
			$_login_comment_str_ = "没有提交域信息！";
			return false;
		}elseif ($m=='domain')
			$area1 = " and `area` = ? ";
		else 
			$area1 = "";
	if (empty($mode))
		$mode1 = "";
	else
		$mode1 = " and `mode` = ? ";
	$url = "$_SERVER[PHP_SELF]";
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !='off')
		$host = "https://$_SERVER[SERVER_ADDR]:$_SERVER[SERVER_PORT]";
	else
		$host = "http://$_SERVER[SERVER_ADDR]:$_SERVER[SERVER_PORT]";
	if (is_numeric($user)){
		$result = $pdo->prepare("select `umin`,`umax`,`id` from login_users where (`user` is NULL or `user` ='') and binary `app` = ? and `enabled`=1 and binary `pwd`=?  $area1 $mode1");
		$datas = array($APPID,$pwd);
		if (isset($_POST['domainid']))
			array_push($datas,$area);
		if ($mode)
			array_push($datas,$mode);
		$rows = $result->execute($datas);
		if (!$rows) die($pdo->error);
		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			if ($row['umin']!='' && $user< $row['umin'])
				continue;
			if ($row['umax']!='' && $user>$row['umax'])
				continue;
			$log = $pdo->prepare("insert into login_log (`IP`,`name`,`pwd`,`APPID`,`url`,`result`,`host`) values(?,?,?,?,?,?,?)");
			$log->execute(array(USERLOGIN_IP,$user,'***',$APPID,$url,1,$host));
			// 			file_put_contents(USERLOGIN_LOG, date("Y-m-d H:i:s")."  ".USERLOGIN_IP."  ".APPID." ".$url." $user $pwd $area $mode -- SUCC  \n",FILE_APPEND);
			$_SESSION['APPID'] = $APPID;
			$_SESSION['user_is_ADMIN'] = 0;
			$_SESSION['user_id'] = $row['id'];
			$_SESSION['oauth_failure_count'] = 0;
			$_SESSION['domainid'] = $area;
			return true;
		}
		$log = $pdo->prepare("insert into login_log (`IP`,`name`,`pwd`,`APPID`,`url`,`result`,`host`) values(?,?,?,?,?,?,?)");
		$log->execute(array(USERLOGIN_IP,$user,$pwd,APPID,$url,0,$host));
		// 		file_put_contents(USERLOGIN_LOG, date("Y-m-d H:i:s")."  ".USERLOGIN_IP."  ".APPID." ".$url." $user $pwd $area $mode -- FAIL  \n",FILE_APPEND);
		$_SESSION['oauth_failure_count'] ++;
		$_login_comment_str_ = "信息错误，请重新输入！";
		return false;
	}else{
		$result = $pdo->prepare("select `isadm`,`id` from login_users where binary `app` = '".$APPID."' and `enabled`=1  and binary `user`=? and binary `pwd` = ? $area1 $mode1");
		$datas = array($user,$pwd);
		if (isset($_POST['domainid']))
			array_push($datas,$area);
		if ($mode)
			array_push($datas,$mode);
		$rows = $result->execute($datas);
		if (!$rows) die(implode(" ", $pdo->errorInfo()));
		$row = $result->fetch();
		if ($row){
			$_SESSION['APPID'] = $APPID;
			$_SESSION['user_is_ADMIN'] = $row[0];
			$_SESSION['user_id'] = $row[1];
			$_SESSION['oauth_failure_count'] = 0;
			$_SESSION['domainid'] = $area;
			$log = $pdo->prepare("insert into login_log (`IP`,`name`,`pwd`,`APPID`,`url`,`result`,`host`) values(?,?,?,?,?,?,?)");
			$log->execute(array(USERLOGIN_IP,$user,'***',$APPID,$url,1,$host));
			return true;
		}
		$log = $pdo->prepare("insert into login_log (`IP`,`name`,`pwd`,`APPID`,`url`,`result`,`host`) values(?,?,?,?,?,?,?)");
		$log->execute(array(USERLOGIN_IP,$user,$pwd,$APPID,$url,0,$host));
		// 		file_put_contents(USERLOGIN_LOG, date("Y-m-d H:i:s")."  ".USERLOGIN_IP."  ".APPID." ".$url." $user $pwd $area $mode -- FAIL  \n",FILE_APPEND);
		$_SESSION['oauth_failure_count']++;
		$_login_comment_str_ = "信息错误，请重新输入！";
		return false;
	}
}

function is_adm(){
	if (isset($_SESSION['user_is_ADMIN']))
		return $_SESSION['user_is_ADMIN'];
	else
		return false;
}

function change_gbk($a){
	foreach ($a as $k=>$v){
		$a[$k] = iconv('utf-8','gbk',$v);
	}
	return $a;
}