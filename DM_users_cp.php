<?php
 set_time_limit(600);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");
include_once 'DM_db.php';
$adm_level = is_adm();
//-------------------修改或添加用户，单个处理-----------------------------------
if (isset($_GET['editUser'])){
	$id = intval($_GET['editUser']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_users where id = $id");
		$sql = "update fs_users set ";
		$sql_end = " where id = $id";
		$showinfo .=" id $id 更新 ";
	}else{
		$result = false;
		$sql = "insert into fs_users (`user_id`,`user_name`,`password`,`domain_id`,`group_id`,`cidr`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 ";
	}

$fail = 0;
if ($result)
	$row = $result->fetch_array();
else 
	$row = array();
	$domain_id =  $_SESSION['domainid'];
if ($adm_level>4 && !empty($_POST)){
	$cidr = $mysqli->real_escape_string($_POST['cidr']);
	$user_name = $_POST['user_name'];
	if (!$id){ //添加时需要提交user_id
		$user_id = $_POST['user_id'];
		if (empty($user_id) ) {
			$showinfo .= "<span class='bgred'>必须提交用户标识！</span><br/>";
			$fail = 1;
		}
		$validRegExp =  '/^[0-9]+$/';
		$prefixlen = strlen($_POST['user_id']);
		if ($prefixlen && ($prefixlen>10 || !preg_match($validRegExp, $_POST['user_id']))) {
			$showinfo .= "<span class='bgred'>为确保兼容性，用户标识必须是数字！且不得超过10位</span><br/>";
			$fail = 1;
		}
		$checkuser = $mysqli->query("select id from fs_users where user_id = '$user_id' and domain_id='$domain_id'");
		$checked = $checkuser->fetch_row();
		if ($checked && $checked[0]){
			$fail = 1;
			$showinfo .= "<span class='bgred'>用户标识不得重复设置！</span><br/>";
		}
	}else{
		$user_id = $row['user_id'];
	}
	if (empty($user_name)) {
		$showinfo .= "<span class='bgred'>必须提交用户姓名！</span><br/>";
		$fail = 1;
	}
	$group_id = "";
	$user_name = $mysqli->real_escape_string($user_name);
	$password = empty($_POST['password'])?'':$mysqli->real_escape_string($_POST['password']);
	
	if (isset($_POST['group_id'])){
		foreach ($_POST['group_id'] as $a){
			$a = intval($a);
			$group_id .= ",$a,"; //为便于搜索！
		}
	}

	if ($id){
		$sql .= "`user_name`='$user_name',`password`='$password',`group_id`='$group_id',`cidr`='$cidr',`enabled`=0";
	}else
		$sql .= "'$user_id','$user_name','$password','$domain_id','$group_id','$cidr'";
	$glist = $group_id;
	$dmold ="";
}else{
	$user_name = @$row['user_name'];
	if (isset($row['user_id'])){
		$user_id = $row['user_id'];
		if ($row['domain_id'] != $domain_id)
			die("非法操作！");
	}else 
		$user_id = rand(10000,999999);
	if ($adm_level<5)
		$password = '****';
	else
		$password = @$row['password'];
	$domain_id = @$row['domain_id'];
	$group_id = @$row['group_id'];
	if ($group_id)
		$group_id_a = explode(",", $group_id);
	else
		$group_id_a = array();
	$cidr = @$row['cidr'];
	$dmold = crc32($user_name.$user_id.$password.$domain_id.$group_id.$cidr);
	
	$ext_ = $mysqli->query("select `group_name`,`group_id` from fs_groups where `domain_id`='$_SESSION[domainid]' order by id DESC");
	$glist = "";
	while (($row_ = $ext_->fetch_array(MYSQLI_NUM))!==false)
		if ($row_){
			$glist .= " <br/><label><input type='checkbox' name='group_id[]' value='$row_[1]' ";
			if (in_array($row_[1], $group_id_a))
				$glist .= " checked='checked' ";
				$glist .="/>$row_[0]</label>";
		}else break;
}
if (isset($row['user_id']))
	$u = '<td><em>用户标识：</em></td><td><span class=bold14>'.$user_id.'</span> &nbsp; <span class="smallgray smallsize-font"> * 用户标识，也是坐席号（即登录账号）</span>';
else 
	$u = "<td><em>用户标识：</em></td><td><input id=\"user_id\" name=\"user_id\" value=\"$user_id\" size=20 class=\"inputline1\" maxlength=\"20\"/> <span class=\"smallgray smallsize-font\"> * 用户标识，最长20位，仅限数字，也是坐席号（即登录账号）</span>";
$html = <<<HTML
<tr class='bg1'><td width=80><em>用户</em></td><td>名称：<input id="user_name" name="user_name" size="20"  maxlength="20" value="$user_name" onclick="this.select();" class="inputline1"/> 密码：<input id="password" name="password" size="20"  maxlength="20" value="$password" onclick="this.select();" class="inputline1"/> <span class="smallgray smallsize-font"> * 长度不得超过20</span><br/>登录IP： <input id="cidr" name="cidr" value="$cidr" size=30 class="inputline1" /> <span class="smallgray smallsize-font">* 限制登录IP：单IP如12.34.56.78/32，IP段如10.11.12.0/24,20.0.0.0/8，不限制就留空，多IP以,分开！</span></td></tr>
<tr class='bg2'>$u</td></tr>
<tr class='bg1'><td><em>隶属</em></td><td>选择组：$glist  </td></tr>
HTML;
if ($adm_level>4 )
	$submitbutton = "<input type='hidden' value='$dmold' name='dmold'><input type=\"submit\" value=\"确认提交\" />";
else 
	$submitbutton = "*无权操作*";
if ($adm_level>4 && !empty($_POST)){
	$submitbutton = ' <a href="?editUser='.$id.'">刷新页面</a>';
	$sql  .= $sql_end;
	$result = false;
	if (crc32($user_name.$user_id.$password.$domain_id.$group_id.$cidr) == $_POST['dmold']){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
	}elseif (!$fail)
		$result = $mysqli->query($sql);
	if ($result){
		$showinfo .= "<span class='bggreen'>操作成功！已自动禁用</span>";
	}else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>用户详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回用户主控页</a></p><table class="tablegreen" width="1000" align="center"><form method="post"><th colspan=2>$showinfo</th>$html<tr class='bg1'><th></th><th>$submitbutton</th></tr></form></table></body></html>
HTML;
	exit;
}
//-----------域管理-------域数据库 ---POST提交操作-----------------------------

//删除域记录
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$mysqli->query("delete from fs_users where id = $id and `enabled` = 0 limit 1");
	die("id $id 操作完毕");
}
//设置启用或禁用
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_users set `enabled` = 1 where id = $id limit 1");
		die("id $id 设置为可用完毕");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_users set `enabled` = 0 where id = $id limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示----------域数据库 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
$getstr = $showtools =$showinsert ="";
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script>";
if ($adm_level>4){
echo"<script>
function del(sid){var a = confirm(\"删除操作不可撤销，你确认提交？\");if (a) { \$.post( \"DM_users_cp.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"DM_users_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"DM_users_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en88(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script>";
$showinsert = '  <a style="font-size:12pt;" href="?editUser=0">【新建用户】</a> <a style="font-size:12pt;" href="DM_users_batch.php">【批量设置】</a>';
}
echo "</head><body>";
$where = " where `domain_id` = '$_SESSION[domainid]' ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['gid'])){
	$temp = $mysqli->real_escape_string($_GET['gid']);
	$where .= " and `group_id` like '%,$temp,%' ";
	$showget .=" 组标识含 '$temp' ";
}
$count = 20;
$totle = $mysqli->query("select count(*) from fs_users $where");
$row = $totle->fetch_array(MYSQLI_NUM);
$totle = $row[0];
$pages = ceil($totle/$count);
if (empty($_GET['p']))
	$p = 0;
else{
		$p = intval($_GET['p']);
		if ($p>$pages)
			$p = $pages;
		if ($p<0)
			$p = 0;
}
	$showget .= " （$totle 条，$pages 页）</span>";
	$file_ = @$_SESSION['conf_dir']."/directory/$_SESSION[domainid].xml";
	if (is_file($file_)){
		$showa= ' <span class="bggreen">域使用中 </span>';
		if ($adm_level>4)
			$showtools=" <button onclick=\"this.value='连接中，请等待反馈...';en99(7,'$_SESSION[domainid]')\">停用当前域</button>";
		$showb = 0;
	}else{
		$showa= ' <span class="bgblue">域已停用 </span>';
		if ($adm_level>4)
			$showtools="<button onclick=\"this.value='连接中，请等待反馈...';en88(7,'$_SESSION[domainid]')\">重新部署域</button>";
		$showb = 1;
	}
	echo '<p class="pcenter" style="font-size:18pt;">呼叫人员管理 '.$showget.$showinsert.'</p><table class="tablegreen" width="90%" align="center"><th colspan=4><form method="get"> 组标识：<input id="gid" name="gid" value="" size=10> <input type="submit" value="确认"> <a href="?">【看全部】</a> <a href="DM_groups_cp.php">【组管理】</a> '.$showtools.'</form></th>';
	$result = $mysqli->query("select * from fs_users $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=4 align=center><span class="smallred smallsize-font"> *用户新建后默认被禁用，需启用后方可应用！已应用的组可获取信息 或 停用；组设置后需启用，并需 用户管理中进行调用<br/> *添加或修改用户，都需先启用组及用户，而后必须将域重新部署启用！！</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			if (!$row['enabled']) 
				$showalert= ' <span class="bgred">被禁</span>';
			else 
				$showalert= ' <span class="bggreen">可用</span>';
			if ($adm_level>4 && $showb) //停用时
				if (!$row['enabled'])
					$showalert .= "  &nbsp;   <button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id])\">删除</button>";
				else
					$showalert .= "  &nbsp;   <button onclick=\"en0($row[id])\">禁止</button>";
			if ($row['group_id']){
				$a = explode(',', $row['group_id']);
				$b = "";
				$totle = $mysqli->query("select `group_name`, `group_id` from fs_groups where `domain_id`='$row[domain_id]'  ");
				while (($row0 = $totle->fetch_array(MYSQLI_NUM))!==false) {
					if ($row0 && in_array($row0[1], $a))
						$b .= "$row0[0] &nbsp; ";
					elseif (!$row0) break;
				}
				$showg = " 隶属组：<strong>$b</strong>";
			}else
				$showg = "<span class=\"smallgray smallsize-font\">无隶属组</span>";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr $bgcolor><td>$showa &nbsp; <em class='red'>$row[user_name]</em> </td><td>用户标识：<strong>$row[user_id]</strong></td><td> $showg</td><td><a href='DM_call_list.php?users=$row[user_id]@$row[domain_id]'>最近通话</a> &nbsp; <a href='?editUser=$row[id]'>详情及修改...</a>  &nbsp;  $showalert <span id='info$row[id]' style='font-size:9pt;color:red;'>";
			echo "</span></td></tr>";
		}
	}
$mysqli->close();
