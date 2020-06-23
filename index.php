<?php
define('APPID', 'lmxcc');
set_time_limit(600);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );

if (!empty($_POST['logout'])){
	$_SESSION=[];
	$_COOKIE=[];
	die("已退出系统！");
}

require_once 'func.inc.php';
$limitIPs = []; //ip黑名单
checkip();
if (isset($_POST['changepwd'])){
	header("Content-type: text/html; charset=utf-8");
	if (isset($_SESSION['user_id'] )){
		$changepwd = $_POST['changepwd'];
		$old = $_POST['old'];
		DB_init();
		$a = $pdo->prepare("update login_users set `pwd` = ? where id = ? and pwd = ? limit 1");
		$b = $a->execute([$changepwd,$_SESSION['user_id'] ,$old]);
		if ($b) die("更新密码完毕！");
		else die("更新时操作失败！");
	}else die(" 无权修改！");
}
if (isset($_POST['username']) && !empty($_POST['psd']) ) {
	if (login()){
		$_SESSION['lmxccusers'] = $_POST['username'];
	}else
		showlogin("需要验证身份！","");
}elseif (!isset($_SESSION['lmxccusers']))
	showlogin("需要验证身份！","");
$adm_level = is_adm();
if ($adm_level>4){
	$menu = '        <li class="highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-users"></i>
                <span>呼叫账号</span>
            </a>
            <a href="DM_users_cp.php" target="mainiframe" class="item">呼叫人员</a>
            <a href="DM_users_batch.php" target="mainiframe" class="item">批量设置</a>
            <a href="DM_groups_cp.php" target="mainiframe" class="item">分组设置</a>
            <a href="DM_agents_cp.php" target="mainiframe" class="item">坐席设置</a>
        </li>
        <li class="highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-diamond"></i>
                <span>呼叫管理</span>
            </a>
            <a href="DM_agents_status.php" target="mainiframe" class="item">坐席状态</a>
            <a href="DM_cdr_list.php" target="mainiframe" class="item">通话记录</a>
            <a href="DM_crm.php" target="_blank" class="item">顾客信息</a>
            <a href="DM_callcenter.php" target="mainiframe" class="item">呼叫中心</a>
            <a href="DM_cp.php?editDomain=8" target="mainiframe" class="item">呼叫信息</a>
            <a href="DM_ivr.php" target="mainiframe" class="item">管理IVR</a>
        </li>
        <li class="highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-list-alt"></i>
                <span>自动外呼</span>
            </a>
            <a href="DM_tasks_cp.php" target="mainiframe" class="item">任务管理</a>
            <a href="DM_tasks_cp.php?edittask=0" target="mainiframe" class="item">新建任务</a>
        </li>
         <li class="highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-phone"></i>
                <span>使用话机</span>
            </a>
          <a href="sipml5/DM_call.php" target="mainiframe" class="item">在线呼叫</a>
        </li>
';
}else{
	$menu = '        <li class="highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-users"></i>
                <span>呼叫账号</span>
            </a>
            <a href="DM_users_cp.php" target="mainiframe" class="item">呼叫人员</a>
            <a href="DM_groups_cp.php" target="mainiframe" class="item">分组设置</a>
        </li>
        <li class="highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-diamond"></i>
                <span>呼叫管理</span>
            </a>
            <a href="DM_agents_status.php" target="mainiframe" class="item">坐席状态</a>
            <a href="DM_cdr_list.php" target="mainiframe" class="item">通话记录</a>
            <a href="DM_crm.php" target="_blank" class="item">顾客信息</a>
            <a href="DM_callcenter.php" target="mainiframe" class="item">呼叫中心</a>
            <a href="DM_cp.php?editDomain=8" target="mainiframe" class="item">呼叫信息</a>
        </li>
         <li class="highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-phone"></i>
                <span>使用话机</span>
            </a>
          <a href="sipml5/DM_call.php" target="mainiframe" class="item">在线呼叫</a>
        </li>
';
}?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>CallCenter Domain User Control Plane 域用户控制面板</title>
<link rel="stylesheet" href="css/helang.css"><script type="text/javascript" src="css/jquery.js"></script>
<script type="text/javascript">
function logout(){var a = confirm("你确认退出？退出后需要重新进行用户登录！");if (a) {$.post("index.php", { logout:"1"}).done(function( data ) { alert("执行退出：" + data);window.location="?";});}};
function changepwd(){var code =prompt("请输入新密码："); if (code.length >2 && code!='undefined') {	var code1 =prompt("请再次输入新密码：");	if (code1 == code) {	var code2 =prompt("请输入当前密码：");	if (code2.length >2 && code2!='undefined') {	$.post("index.php", { old: code2,changepwd:code }).done(function( data ) { alert( "【修改密码】 " + data);window.location.href="index.php"; });	}else  alert('输入的现密码不对！');	}else alert('两次密码不一致！');}else alert('请输入密码，长度不能少于3个字符！');}
</script>
<link rel="stylesheet" type="text/css" href="css/font-awesome.min.css">
<style type="text/css">
	.info_box{
		margin: 100px auto 0 auto;
		width: 400px;
		background-color: #ffffff;
		color: #333333;
		padding:0 0 0 30px;
	}
	.info_box>li{
		padding: 15px 0;
		font-size: 14px;
		border-top: #e5e5e5 dashed 1px;
	}
	.info_box>li:first-child{
		list-style: none;
		font-size: 16px;
		color: #FD463E;
		border-top: none;
		font-weight: bold;
	}
	.info_box>li:last-child{
		list-style: none;
		font-size: 12px;
		color: #999999;
	}
</style>
</head>
<body>
<nav class="hl_nav">
    <ul class="nav_list">
        <li>
            <a class="nav_head" href="javascipt:;">
                <span>&nbsp;<?=$_SESSION['domainid'];?>&nbsp;</span><i class="fa fa-angle-right" aria-hidden="true"> </i>
            </a>
            <a href="#"  class="item">账号：<?=$_SESSION['lmxccusers'];?></a>
        </li>
<?=$menu;?>
        <li class="right highlight">
            <a class="nav_head" href="javascipt:;">
                <i class="icon fa fa-cog "></i>
                <span>基础设置</span>
            </a>
            <a href="DM_cp.php" target="mainiframe" class="item">配置概要</a>
            <a href=""  onclick ="changepwd();" class="item">修改密码</a>
            <a href="javascipt:;" onclick ="logout();" class="item">退出帐号</a>
        </li>
    </ul>
    <div class="shade"></div>
</nav>
 <iframe class="resp-iframe" src="DM_cp.php"  id="mainiframe" name="mainiframe" ></iframe>
 <script type="text/javascript" language="javascript">
	function changeFrameHeight(){
		$("#mainiframe").height($(document).height()-64);
	}
	window.onresize=function(){ changeFrameHeight();}
	$(function(){changeFrameHeight();});
</script>
</body>
</html>
