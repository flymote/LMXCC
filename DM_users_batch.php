<?php
 set_time_limit(600);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");

include_once 'DM_db.php';
$adm_level = is_adm();
if ($adm_level<5)
	die("没有权限！");
//-------------------修改或添加用户，批量处理-----------------------------------
$showinfo = "";$submitbutton = "<input type=\"submit\" value=\"确认提交\" />";
$fail = 0;
if (!empty($_POST)){
	if(empty($_POST['act'])){
		$fail =1;
		$showinfo = "<span class='bgred'>没有选择做什么操作！</span><br/>";
	}
	$user_name = $_POST['user_name'];
	$domain_id =  $_SESSION['domainid'];
	if ($_POST['act']=='add'){
		$password = $mysqli->real_escape_string($_POST['password']);
		if(empty($password)){
			$fail =1;
			$showinfo .= "<span class='bgred'>需要提交统一设置的密码 ！密码不可为空</span><br/>";
		}elseif (empty($_POST['user_id']) || empty($user_name)){
			$showinfo .= "<span class='bgred'>需要提交需批量创建的人员信息 并 设置起始的用户标识 ！</span><br/>";
			$fail = 1;
		}
		$validRegExp =  '/^[0-9]+$/';
		$prefixlen = strlen($_POST['user_id']);
		if ($prefixlen && ($prefixlen>10 || !preg_match($validRegExp, $_POST['user_id']))) {
			$showinfo .= "<span class='bgred'>为确保兼容性，用户标识必须是数字！且不得超过10位</span><br/>";
			$fail = 1;
		}
		$user_id = $_POST['user_id'];
	}elseif ($_POST['act']=='change' && empty($user_name)) {
		$showinfo .= "<span class='bgred'>需要提交需批量调整分组信息的人员信息  ！</span><br/>";
		$fail = 1;
	}
	$group_id = "";
	$user_ids = $group_names = [];
	$check = $mysqli->query("select user_id from fs_users where domain_id='$domain_id'");
	$ids = $check->fetch_all(MYSQLI_NUM);
	foreach ($ids as $one)
		$user_ids[] = $one[0];
	$check = $mysqli->query("select group_name,group_id from fs_groups where domain_id='$domain_id'");
	$ids = $check->fetch_all(MYSQLI_NUM);
	foreach ($ids as $one)
		$group_names[$one[0]] = $one[1];
//	$submitbutton = ' <a href="?">刷新页面</a>';
	$check = 0;
	if (!$fail){
		$users = explode("\r\n", $user_name);
		if ($_POST['act']=='add')
			$sql = "insert into fs_users (`user_id`,`user_name`,`password`,`domain_id`,`group_id`) values ";
		foreach ($users as $one){
			$one=str_replace(['，','.','。',';','；','/'],',',trim($one));
			$user_ = explode(",", $one);
			$name = $mysqli->real_escape_string(trim($user_[0]));
			if (isset($user_[1])){
				$group = trim($user_[1]);
				if (isset($group_names[$group]))
					$group = $group_names[$group];
				else $group = "";
			}else
				$group = "";
			if ($_POST['act']=='add'){
				if (!in_array($user_id,$user_ids)){
					$sql .= "($user_id,'$name','$password','$domain_id','$group'),";
				}else {
					while(in_array($user_id,$user_ids))
						$user_id++;
					$sql .= "($user_id,'$name','$password','$domain_id','$group'),";
				}
				$user_id++;
			}elseif ($group){
				$sql = "update fs_users set `group_id` = ',$group,' where `user_name` = '$name' ";
				$mysqli->query($sql);
				$check += $mysqli->affected_rows;
			}
		}
		if ($_POST['act']=='add'){
			$sql = substr($sql,0,-1);
			$mysqli->query($sql);
			$check= $mysqli->affected_rows;
		}
	}
	if ($check>0)
		$showinfo .= "<span class='bggreen'>操作成功！$check 条数据</span>";
	else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script><script type="text/javascript">
function checkact(act){
if(act==2){ alert("警告：将按批量人员信息重设分组，每人仅允许设置隶属一个组！");$("#user_id").attr("disabled",true);  $("#password").attr("disabled",true); } else { $("#user_id").attr("disabled",false);  $("#password").attr("disabled",false); };}</script>
</head><body><p class='pcenter' style='font-size:18pt;'>用户批量设置 <a style='font-size:10pt;' href='DM_users_cp.php'>&raquo;&nbsp;返回用户主控页</a></p><table class="tablegreen" width="1000" align="center"><form method="post"><th colspan=2>$showinfo</th><tr class='bg1'><td width=80>批量信息：<textarea id="user_name" name="user_name" size="20" rows=20 placeholder="姓名\n或\n姓名,分组名称\n\n每行一人，可重名，分组不存在则忽略，每人仅允许设置隶属一个组"/></textarea></td><td valign="top">设置统一密码：<br/>&nbsp;<input id="password" name="password" size="20"  maxlength="20" value="" onclick="this.select();" class="inputline1"/> <span class="smallgray smallsize-font"> * 不允许空密码，长度不得超过20</span><br/><br/>起始用户标识：<br/>&nbsp;<input id="user_id" name="user_id" value="" size=20 class="inputline1" maxlength="20"/> <span class="smallgray smallsize-font"> * 这是坐席号（即登录账号）必须提供，最长20位，仅限数字，处理时自动递增</span><br/><br/>&nbsp; 选择操作：<br/>&nbsp; &nbsp; &nbsp; <label><input name="act" value="add" type="radio" onclick="checkact(1)" checked>批量创建用户</label><br/>&nbsp; &nbsp; &nbsp; <label><input name="act" value="change" type="radio" onclick="checkact(2)">批量修改分组</label> <br/><br/><br/>&nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;$submitbutton</td></tr></form></table></body></html>
HTML;
$mysqli->close();
