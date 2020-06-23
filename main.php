<?php
define('APPID', 'FSlmx');
set_time_limit(600);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );

require_once 'func.inc.php';
if (isset($_POST['username']) && !empty($_POST['psd']) ) {
	if (login('')){
		$_SESSION['FSlmxusers'] = $_POST['username'];
	}else
		showlogin("FSlmx 请输入身份信息，需要验证身份！");
}elseif (!isset($_SESSION['FSlmxusers']))
showlogin("FSlmx 请输入身份信息，需要验证身份！");

//服务器操作
if (isset($_POST['sysctl'])){
	if (!IS_WIN){
		if (isset($_POST['startFS'])){
			$output = `freeswitch -u freeswitch -g daemon -nc`;
			die(" FS服务启动： \n$output");
		}
		if (isset($_POST['stopFS'])){
			$output = `killall -9 freeswitch`;
			die(" FS服务停止： \n$output");
		}
		if (isset($_POST['deltask'])){
			$output = `killall -9 lmxcc`;
			die(" 杀死外呼服务： \n$output");
		}
		}else die("本命令系对Linux服务器的本机环境进行操作！目前环境不可用");
}

require_once "Shoudian_db.php";
$result = $mysqli->query("select * from fs_setting where `enabled` = 9 limit 1");
$row = $result->fetch_array();
if (empty($row)){
	$showinfo = "<span style='font-size:16pt;color:red;'>未设定主控，请先设置主控服务器！</span>";
	define("ESL_HOST", "localhost");
	define("ESL_PORT", 8021);
	define("ESL_PASSWORD", 'ClueCon');
	if (IS_WIN)
		$_SESSION['conf_dir'] = "d://freeswitch//conf";
	else 
		$_SESSION['conf_dir'] = "/etc/freeswitch";
}else{
	define("ESL_HOST", $row['ESL_host']);
	define("ESL_PORT", $row['ESL_port']);
	define("ESL_PASSWORD",$row['ESL_password']);
	$showinfo = "<span style='font-size:20pt;color:gray;'>@".ESL_HOST."</span>";
	$_SESSION['log_dir'] = $row['log_dir'];
	$_SESSION['conf_dir'] = $row['conf_dir'];
	$_SESSION['default_provider'] = $row['default_provider'];
	$_SESSION['recordings_dir'] = $row['recordings_dir'];
	$_SESSION['ESL_HOST'] = ESL_HOST;
	$_SESSION['ESL_PORT'] = ESL_PORT;
	$_SESSION['ESL_PASSWORD'] = ESL_PASSWORD;
	$_SESSION['xmlcdr_auth'] = array(); //如果设置有值：array('user','password')，即为使用认证，将启用xmlcdr配置文件中的用户认证
}
require_once "detect_switch.php";
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type content=text/html;charset=utf-8"/><script type="text/javascript" src="css/jquery.js"></script><link rel="stylesheet" type="text/css" href="css/main.css"/><link rel="stylesheet" type="text/css" href="css/font-awesome.min.css">'."<script>
function en1(){var a = confirm(\"警告！！\\n将尝试启动Freeswitch服务\\n      你确认提交？\");if (a) { \$.post( \"main.php\", { startFS: 1,sysctl:1 })
  .done(function( data ) { alert( \"操作完毕！\" + data);});} }
function stop1(){var a = confirm(\"警告！！\\n将强行停止Freeswitch服务，所有当前通话均会中断！务必谨慎！！\\n      你确认提交？\");if (a) { \$.post( \"main.php\", { stopFS: 1,sysctl:1 })
  .done(function( data ) { alert( \"操作完毕！\" + data);});} }
function deltask(){var a = confirm(\"警告！！\\n将强行停止批量外呼服务，所有批量外呼活动均会中断！务必谨慎！！\\n      你确认提交？\");if (a) { \$.post( \"main.php\", { deltask: 1,sysctl:1 })
  .done(function( data ) { alert( \"操作完毕！\" + data);});} }
</script>".'</head><body>';
echo "<p class='pcenter' style='font-size:22pt;'>FreeSwitch 控制台 $showinfo <a class=\"btn btn-blue\" href='FS_setting.php' style='font-size:10pt;'><i class=\"fa fa-cog fa-2x pull-left\"></i> 服务器<br/>设置</a> <a class=\"btn btn-blue\" href='Inconfig.php' style='font-size:10pt;'><i class=\"fa fa-info-circle fa-2x pull-left\"></i> 参数<br/>设置</a> <a class=\"btn btn-blue\" href='esl_cmd.php' style='font-size:10pt;'><i class=\"fa fa-grav fa-2x pull-left\"></i> ESL<br/>控制</a> <a class=\"btn btn-blue\" href='sipml5/call.php' style='font-size:10pt;'><i class=\"fa fa-phone fa-2x pull-left\"></i> WEBrtc<br/>话机</a></p>";
echo "<div style='width:1000px;margin: 0 auto;position: relative;text-align:center;'><form method=‘get’ style='float:left;width:500px;text-align:left;'> <span style='font-size:14pt;color:#FF8C00'> ⋙  </span> <input name='sps' value='' style='width:60pt;' placeholder='限制会话数..'> <input type='submit' name='sps_b' value='每秒新会话限制'> <span style='font-size:14pt;color:#FF8C00'> ⋙  </span> <input name='maxsessions' value='' style='width:60pt;'  placeholder='最大会话数..'> <input type='submit' name='max_b' value='最大会话数限制'> </form> &nbsp;  &nbsp; <span style='font-size:14pt;color:#FF8C00'> ⋙  </span><button onclick=\"en1()\">启动FS</button> &nbsp; <button onclick=\"stop1()\">停止FS</button> &nbsp; <button onclick=\"deltask()\">强停任务</button></div>
<div style='width:1000px;margin: 0 auto;line-height: 1.5;position: relative;font-size:10pt;text-align:center;'><span style='font-size:14pt;color:#FF8C00'> ☏  </span> 加载：<a href='cdr_post.php'>本地CDR</a> &nbsp; <a href='?reloadxml=1'>reloadxml</a> &nbsp; <a href='?reloadsofia=1'>reload mod_sofia</a> &nbsp; <a href='?reloadxmlcdr=1'>reload xml_cdr</a> &nbsp; <a href='?reloadacl=1'>reloadacl</a> &nbsp; <a href='?restart=1'>重启".ESL_HOST."</a><span style='font-size:14pt;color:gray'> ☏  </span> 查看：<a href='?'>FS信息</a> &nbsp; <a href='?channels=1'>查看channels</a> &nbsp; <a href='?calls=1'>查看Calls</a></div>
<p style='width:1000px;margin: 0 auto;position: relative;cursor:pointer;text-align:center;background:#1d9d74;color:white;border:1px solid #DCDCDC;' onclick='$(\"#FSsystemMenu\").toggle(300);'> =↕↕=  管理菜单 =↕↕=</p><div id='FSsystemMenu' style='width:1000px;margin: 0 auto;position: relative;'><span style='font-size:14pt;color:#FF8C00'> ☎  </span> 系统管理：<br/><a class=\"btn btn-green\" href='FS_freeswitch_cp.php'><i class=\"fa fa-object-group fa-2x pull-left\"></i> 系统<br/>配置</a> &nbsp; <a class=\"btn btn-green\"  href='FS_switch_cp.php'><i class=\"fa fa-cogs fa-2x pull-left\"></i> SWITCH<br/>参数配置</a> &nbsp; <a class=\"btn btn-green\"  href='FS_vars_cp.php'><i class=\"fa fa-first-order fa-2x pull-left\"></i> &nbsp; VARS &nbsp;<br/>参数配置</a> &nbsp; <a  class=\"btn btn-green\" href='FS_modules_cp.php'><i class=\"fa fa-modx fa-2x pull-left\"></i> Modules <br/>系统配置</a>
<hr/><span style='font-size:14pt;color:#FF8C00'> ☎  </span> SIP管理：<br/><a class=\"btn btn-green\"  href='FS_sofiaExternal_cp.php'><i class=\"fa fa-sign-out fa-2x pull-left\"></i> External<br/>sofia管理</a> &nbsp; <a class=\"btn btn-green\"  href='FS_sofiaInternal_cp.php'><i class=\"fa fa-sign-in fa-2x pull-left\"></i> Internal<br/>sofia管理</a> <a class=\"btn btn-green\"  href='FS_gateways_cp.php'><i class=\"fa fa-random fa-2x pull-left\"></i> 路由<br/>管理</a> &nbsp; <a class=\"btn btn-green\"  href='FS_xmlcdr_cp.php'><i class=\"fa fa-comments-o fa-2x pull-left\"></i> 呼叫记录<br/>CDR配置</a> &nbsp; <a class=\"btn btn-green\"  href='FS_acl_cp.php'><i class=\"fa fa-bolt fa-2x pull-left\"></i>&nbsp;  ACL &nbsp; <br/>访问控制</a>
<hr/><span style='font-size:14pt;color:#FF8C00'> ☎  </span> 拨号管理：<br/><a class=\"btn btn-green\" href='FS_files_edit.php'><i class=\"fa fa-pencil-square-o fa-2x pull-left\"></i> 配置文件<br/>管理</a> &nbsp; <a class=\"btn btn-green\"  href='FS_extensions_cp.php'><i class=\"fa fa-tasks fa-2x pull-left\"></i> Extensions<br/>拨号管理</a> &nbsp; <a class=\"btn btn-green\"  href='FS_dialplans_cp.php'><i class=\"fa fa-thumb-tack fa-2x pull-left\"></i> DIALplans<br/>拨号管理</a> &nbsp; <a class=\"btn btn-green\"  href='FS_xmlcdr_list.php'><i class=\"fa fa-tty fa-2x pull-left\"></i> CDR记录<br/>查看</a>
<hr/><span style='font-size:14pt;color:#FF8C00'> ☎  </span> 域及用户管理：<br/><a  class=\"btn btn-green\" href='FS_domains_cp.php'><i class=\"fa fa-universal-access fa-2x pull-left\"></i> 域管理<br/>控制台</a> &nbsp; <a class=\"btn btn-green\"  href='FS_groups_cp.php'><i class=\"fa fa-users fa-2x pull-left\"></i> 用户组<br/>管理</a> &nbsp; <a class=\"btn btn-green\"  href='FS_users_cp.php'><i class=\"fa fa-user fa-2x pull-left\"></i> 用户<br/>管理</a> &nbsp; <a  class=\"btn btn-green\" href='FS_callcenter_cp.php'><i class=\"fa fa-creative-commons fa-2x pull-left\"></i> 呼叫中心<br/>管理</a> &nbsp; <a  class=\"btn btn-green\" href='FS_tasks_cp.php'><i class=\"fa fa-volume-control-phone fa-2x pull-left\"></i> 外呼任务<br/>管理</a></p></div><div style='width:1000px;margin: 0 auto;position: relative;'>";

$info = new detect_switch();

//下面 run 中 apireload 是使用api reload；reload 是使用bgapi reload；其他命令仅是sofia 和 show 可以带自定义的参数
$sps = intval(@$_GET['sps']);
$mss = intval(@$_GET['maxsessions']);
if (isset($_GET['restart'])){
	$info-> restart_switch();
}elseif (isset($_GET['sps_b']) && $sps){
	$info->run('api',"fsctl sps $sps");
	$redis = redisDB();
	$redis->set("FS_sps",$sps);
}elseif (isset($_GET['max_b']) && $mss){
	$info->run('api',"fsctl max_sessions $mss");
	$redis = redisDB();
	$redis->set("FS_maxsessions",$mss);
}elseif (isset($_GET['reloadxml'])){
	$info-> run('reloadxml');
}elseif (isset($_GET['reloadacl'])){
	$info-> run('reloadacl');
}elseif (isset($_GET['reloadsofia'])){
	$info-> run('apireload','mod_sofia');
}elseif (isset($_GET['reloadxmlcdr'])){
	$info-> run('apireload','mod_xml_cdr');
}elseif (isset($_GET['channels'])){
	$info-> run('channels');
}elseif (isset($_GET['calls'])){
	$info-> run('calls');
}else 
	$info-> list_switch_info();
echo "</div></body></html>";