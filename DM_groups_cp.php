<?php
 set_time_limit(60);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");

include_once 'DM_db.php';
$adm_level = is_adm();
//-------------------修改或添加域信息-----------------------------------
if (isset($_GET['editGroup'])){
	$id = intval($_GET['editGroup']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_groups where id = $id");
		$sql = "update fs_groups set ";
		$sql_end = " where id = $id";
		$showinfo .=" id $id 更新 ";
	}else{
		$result = false;
		$sql = "insert into fs_groups (`group_id`,`group_name`,`calltimeout`,`domain_id`,`calltype`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 ";
}

$fail = 0;
if ($result)
	$row = $result->fetch_array();
else 
	$row = array();

	if ($adm_level >4 && !empty($_POST)){
	$group_name = $_POST['group_name'];
	$group_id = $_POST['group_id'];
	if (empty($group_id) || empty($group_name)) {
		$showinfo .= "<span class='bgred'>必须提交组名及组标识！</span><br/>";
		$fail = 1;
	}
	$validRegExp =  '/^[0-9]+$/';
	$prefixlen = strlen($_POST['group_id']);
	if ($prefixlen && ($prefixlen>20 || !preg_match($validRegExp, $_POST['group_id']))) {
		$showinfo .= "<span class='bgred'>为确保兼容性，组标识必须是数字！且不得超过20位</span><br/>";
		$fail = 1;
		$prefix = "";
	}
	
	$group_name = $mysqli->real_escape_string($group_name);
	$group_id = $mysqli->real_escape_string($group_id);
	$calltimeout = intval($_POST['calltimeout']);
	if ($calltimeout>255)
		$calltimeout = 255;
	elseif ($calltimeout<0)
		$calltimeout = 0;
	$calltype = empty($_POST['calltype'])?'':$mysqli->real_escape_string($_POST['calltype']);

	//修改组id需要同步修改原来本组下级用户的相关隶属信息
	$change_user = 0;
	if (empty($row['group_id']))
		$oldgid = "";
	else 
		$oldgid = $row['group_id'];
	if (!empty($_POST['group_id']) && $oldgid && $_POST['group_id']!=$oldgid){
		$change_user = 1;
		$oldgid = $row['group_id'];
		$showinfo .= "<span class='bgblue'>核心标识已经修改！将同步修改用户数据！</span><br/>";
		$totle = $mysqli->query("select `id` from fs_groups where  `group_id`='$group_id'");
		$row_ = $totle->fetch_array(MYSQLI_NUM);
		if (!empty($row_[0])){
			$showinfo .= "<span class='bgred'>组标识已经存在了，请修改！</span><br/>";
			$fail = 1;
		}
	}
	if ($id)
		$sql .= "`enabled`=0,`group_id`='$group_id',`group_name`='$group_name',`calltimeout`=$calltimeout,`calltype`='$calltype'";
	else
		$sql .= "'$group_id','$group_name',$calltimeout,'$_SESSION[domainid]','$calltype'";
	$dmold ="";
}else{
	$group_name = @$row['group_name'];
	if (isset($row['group_id'])){
		$group_id = $row['group_id'];
		if ($row['domain_id'] != $_SESSION['domainid'])
			die("非法操作！");
	}else 
		$group_id = date('His');
	$calltimeout = (@$row['calltimeout']?$row['calltimeout']:10);
	$calltype = @$row['calltype'];
	$dmold = $group_name.$group_id.$calltimeout.$calltype;
}
$html = <<<HTML
<tr class='bg1'><td width=80><em>组名称：</em></td><td><input id="group_name" name="group_name" size="30"  maxlength="20" value="$group_name" onclick="this.select();" class="inputline1"/> <span class="smallgray smallsize-font"> * 长度不得超过20，请用中英文、数字及横线，不得重复</span></td></tr>
<tr class='bg2'><td><em>组标识：</em></td><td><input id="group_id" name="group_id" value="$group_id" size=10 class="inputline1" maxlength="20"/> <span class="smallgray smallsize-font"> * 组标识必须全局唯一，最长20位，仅限数字</span></td></tr>
<tr class='bg1'><td><em>信息项</em></td><td><input type="hidden" name="dmold" value="$dmold"><em>呼叫超时：</em> <input id="calltimeout" name="calltimeout" value="$calltimeout" size=2 class="inputline1" /> &nbsp; <em>呼叫方式：</em> <label><input id="calltypea" name="calltype" value="+A" type="radio" />同时振铃 +A</label> &nbsp; <label><input id="calltypef" name="calltype" value="+F" type="radio" />依次振铃 +F</label> &nbsp; <label><input id="calltypee" name="calltype" value="+E" type="radio" />多线程振铃 +E</label></td></tr>
HTML;
if ($adm_level >4)
	$submitbutton = "<input type=\"submit\" value=\"确认提交\" />";
else 
	$submitbutton = "*无权操作*";
if ($adm_level >4 && !empty($_POST)){
	$submitbutton = ' <a href="?editGroup='.$id.'">刷新页面</a>';
	$sql  .= $sql_end;
	$result = false;
	if ($group_name.$group_id.$calltimeout.$calltype==$_POST['dmold']){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
	}elseif (!$fail)
		$result = $mysqli->query($sql);
	if ($result){
		$showinfo .= "<span class='bggreen'>操作成功！已自动禁用</span>";
		if ($change_user)
			$mysqli->query("update fs_users set `group_id` = replace(`group_id`,',$oldgid,',',$group_id,') where  `domain_id`='$_SESSION[domainid]' and `group_id` is not NULL and `group_id` <>'' ");
	}else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
$ct = "<script>if ('$calltype'=='+F') $('#calltypef').prop('checked','checked'); else if ('$calltype'=='+A') $('#calltypea').prop('checked','checked'); else if ('$calltype'=='+E') $('#calltypee').prop('checked','checked'); </script>";
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>组详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回组主控页</a></p><table class="tablegreen" width="1000" align="center"><form method="post"><th colspan=2>$showinfo</th>$html<tr class='bg2'><th></th><th>$submitbutton</th></tr></form></table>$ct</body></html>
HTML;
	exit;
}
//-----------域管理-------域数据库 ---POST提交操作-----------------------------

//删除记录
if ($adm_level >4 && !empty($_POST['del'])){
	$id = intval($_POST['did']);
	$result = $mysqli->query("select `group_id` from fs_groups where id = $id and  `domain_id`='$_SESSION[domainid]'  and `enabled`=0");
	$row = $result->fetch_array();
	if (!empty($row[0])){
		$mysqli->query("delete from fs_groups where id = $id and  `domain_id`='$_SESSION[domainid]' limit 1");
		$aleng = strlen($row[0]);
// 		$mysqli->query("update fs_users set `group_id` = '' where `group_id` = '$row[0]' ");
// 		$mysqli->query("update fs_users set `group_id` = substr(`group_id`,$aleng+2) where `group_id` like '$row[0],%' ");
// 		$mysqli->query("update fs_users set `group_id` = substr(`group_id`,1,length(`group_id`)-$aleng-1) where `group_id` like '%,$row[0]' ");
		$mysqli->query("update fs_users set `group_id` = replace(`group_id`,',$row[0],','') where  `domain_id`='$_SESSION[domainid]' and `group_id` is not NULL and `group_id` <>'' ");
		die("id $id 操作完毕");
	}
	die("id $id 使用中，不能操作！");
}
//设置启用或禁用
if ($adm_level >4 && empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_groups set `enabled` = 1 where id = $id  and  `domain_id`='$_SESSION[domainid]' limit 1");
		die("id $id 设置为可用完毕");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_groups set `enabled` = 0 where id = $id  and  `domain_id`='$_SESSION[domainid]' limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示---------- 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script>
function del(sid){var a = confirm(\"删除操作不可撤销，你确认提交？\");if (a) { \$.post( \"DM_groups_cp.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"DM_groups_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"DM_groups_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en88(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script></head><body>";
$where = " where  `domain_id` = '$_SESSION[domainid]'  ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['gid'])){
	$temp = $mysqli->real_escape_string($_GET['gid']);
	$where .= " and `group_name` like '%$temp%' ";
	$showget .=" 组名称含 '$temp' ";
}
$count = 20;
$getstr = "";
$totle = $mysqli->query("select count(*) from fs_groups $where");
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
	$showtools = "";
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
	
	echo '<p class="pcenter" style="font-size:18pt;">组管理控制台 '.$showget.'  <a style="font-size:12pt;" href="?editGroup=0">【新建组】</a></p><table class="tablegreen" width="90%" align="center"><th colspan=6><form method="get">组名称：<input id="gid" name="gid" value="" size=10> <input type="submit" value="确认"> <a href="?">【看全部】</a>'.$showtools.'</form></th>';
	$result = $mysqli->query("select * from fs_groups $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=6 align=center><span class="smallred smallsize-font">*组新建或修改后默认被禁用，需启用！必须将域重新部署才能使用修改或添加的内容！</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
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
		
			$totle = array('0'=>0,'1'=>0);
			$totle0 = $mysqli->query("SELECT `enabled` ,COUNT(*)  FROM fs_users WHERE `group_id` like '%,$row[group_id],%'  GROUP BY `enabled` order by `enabled` ");
			while (($row_= $totle0->fetch_array(MYSQLI_NUM))!==false) {
				if($row_)
					$totle[$row_[0]] +=$row_[1];
				else break;
			}
			$showuser = "含用户：可用<strong> $totle[1] </strong>  不可用<strong> $totle[0] </strong>  <a href='DM_users_cp.php?gid=$row[group_id]'>&raquo;&nbsp;管理用户</a>";
			$options = "呼叫超时：<strong>".$row["calltimeout"]."</strong> &nbsp; ";
			$options .= "呼叫方式： <strong>";
			if ($row['calltype'])
				$options .= ($row['calltype']=="+A"?'同时振铃':($row['calltype']=="+F"?'依次振铃':'多线程振铃'));
			else
				$options .= "默认";
			$options .= "</strong> &nbsp; ";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr $bgcolor><td>$showa &nbsp; <em class='red'>$row[group_name]</em> </td><td>组标识：<strong>$row[group_id]</strong></td><td> $showuser</td><td>$options</td><td><a href='?editGroup=$row[id]'>详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>  &nbsp;  $showalert </span></td></tr>";
		}
	}
$mysqli->close();
