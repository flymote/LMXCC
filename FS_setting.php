<?php
 set_time_limit(600);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
include 'Shoudian_db.php';
//-------------------操作-----------------------------
if (empty($_SESSION['POST_submit_once']) && isset($_POST['ESL_host'])){
	if (!empty($_POST['ESL_port']) && !empty($_POST['ESL_password']) ) {
		$port = intval($_POST['ESL_port']);
		$host = addslashes($_POST['ESL_host']);
		$psd = addslashes($_POST['ESL_password']);
		if ($port){
			$mysqli->query("insert into fs_setting (ESL_host,ESL_port,ESL_password) value ('$host','$port','$psd') ");
			$_SESSION['POST_submit_once']=1;
			die("服务器信息添加完毕！");
		}
	}else die("服务器信息请填写完整，不能为空！");
}
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$mysqli->query("delete from fs_setting where id = $id limit 1");
	die("id $id 操作完毕");
}
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['getinfo'])){
	require_once "detect_switch.php";
	$sid = intval($_POST['sid']);
	$result = $mysqli->query("select * from fs_setting where id = $sid");
	$row = $result->fetch_array();
	define("ESL_HOST", $row['ESL_host']);
	define("ESL_PORT", $row['ESL_port']);
	define("ESL_PASSWORD",$row['ESL_password']);
	$_SESSION['POST_submit_once']=1;
	$info = new detect_switch();
	$return = $info->get_switch_info($sid);
	if ($return)
		die('成功获取信息并保存！');
	else 
		die('保存获取信息出错！');
}
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_setting set `enabled` = 1 where id = $id limit 1");
		die("id $id 设置为可用完毕");
	}elseif ($to === 9){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_setting set `enabled` = 1 where `enabled` = 9");
		$mysqli->query("update fs_setting set `enabled` = 9 where id = $id limit 1");
		die("id $id 设置为主控服务器完毕");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_setting set `enabled` = 0 where id = $id limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示---------------------------------
$_SESSION['POST_submit_once']=0;

echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script>
function del(sid){\$.post( \"FS_setting.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });}
function en0(sid){\$.post( \"FS_setting.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"操作成功！\" + data);window.location.reload();});}
function en1(sid){\$.post( \"FS_setting.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"操作成功！\" + data);window.location.reload();});}
function en9(sid){\$.post( \"FS_setting.php\", { sid: sid, en9: \"1\" })
  .done(function( data ) { alert( \"操作成功！\" + data);$('#info'+sid).html(data); });}
function getinfo(sid){\$.post( \"FS_setting.php\", { sid: sid, getinfo: \"1\" })
  .done(function( data ) { alert( data);window.location.reload();});}
function add(ho,po,psd){\$.post( \"FS_setting.php\", { ESL_host: ho, ESL_port: po, ESL_password:psd })
  .done(function( data ) { alert( data);window.location.reload();});}
</script></head><body>";


$count = 20;
$getstr = $showget = "";
$totle = $mysqli->query("select count(*) from fs_setting ");
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
	$showget .= "共 $totle 条，$pages 页";
	echo '<p class="pcenter" style="font-size:18pt;">服务器ESL控制台 <a style="font-size:10pt;" href="main.php">&raquo;&nbsp;返回主控</a></p><table class="tablegreen" width="90%" align="center"><th colspan=5><span class="bggreen">FS 服务器</span>信息设置及管理</th>';
	$result = $mysqli->query("select * from fs_setting ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=5 align=center>主机：<input id="ESL_host" name="ESL_host" value="" size=10> 端口：<input id="ESL_port" name="ESL_port" value="" size=3>密码：<input id="ESL_password" name="ESL_password" value="" size=4> <button type="button" onclick="add($(\'#ESL_host\').val(),$(\'#ESL_port\').val(),$(\'#ESL_password\').val())">确认提交</button><span class="smallred smallsize-font"> *必须先配置连接ES ，获取信息 后才可设为 主控</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{

			if ($row['enabled']){
				if ($row['enabled']=='9')
					$showalert = '<span class="bggreen">主控 </span> @'.$row['ESL_host'];
				else
					$showalert= ' <span class="bgblue">可用 </span> @'.$row['ESL_host'];
			}else 
				$showalert= ' <span class="bgred">禁用 </span> @'.$row['ESL_host'];
			if ($row['version']){
				$showport = "$row[version] ";
			}else $showport = "<span class='smallgray smallsize-font'>--无可用信息--</span>";
			if ($row['conf_dir']){
				$showdir = "配置文件：$row[conf_dir] <br/>日志文件：$row[log_dir] <br/>录音文件：$row[recordings_dir]<br/>声音文件：$row[sounds_dir]";
			}else $showdir = "<span class='smallgray smallsize-font'>--无可用信息--</span>";
			if ($row['core_uuid']){
				$showuuid = "core_uuid：$row[core_uuid]<br/>内SIP端口：$row[internal_sip_port] <br/>外SIP端口：$row[external_sip_port]<br/>默认路由：$row[default_provider]";
			}else $showuuid = "<span class='smallgray smallsize-font'>--无可用信息--</span>";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr  $bgcolor ><td align=center>$showalert 端口：$row[ESL_port] 密码：$row[ESL_password] </td><td width=200>$showport</td><td>$showuuid</td><td>$showdir</td><td><span id='info$row[id]' style='font-size:9pt;color:red;'>";
			if ($row['enabled']){
				echo "<input type='button' onclick=\"this.value='连接中，请等待反馈...';getinfo($row[id]);\" value='获取信息'/>";
				if (!empty($row['core_uuid']) && $row['enabled'] != 9 )
					echo "<button onclick=\"en9($row[id])\">设为主控</button>";
				echo " <button onclick=\"en0($row[id])\">禁用</button>";
			}else 
				echo "<button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id])\">删除</button>";
			echo "</span></td></tr>";
		}
	}
$mysqli->close();
