<?php
define('APPID', 'workorder');
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");

$limit = 100;
include 'index_work_db.php';
require_once 'func.inc.php';

if (isset($_POST['area']) && isset($_POST['mode'])&& isset($_POST['username']) && !empty($_POST['realname']) ) {
	if (login()){
		$_SESSION['WorkUser']['user'] = $_POST['username'];
		$_SESSION['WorkUser']['realname'] = $_POST['realname'];
		$_SESSION['WorkUser']['area'] = $_POST['area'];
		$_SESSION['WorkUser']['mode'] = $_POST['mode'];
		$_SESSION['username'] = $_POST['username'];//设置查询手机信息用户
	}else
		showlogin('工单系统 需要验证身份：','area&mode');
}elseif (!isset($_SESSION['WorkUser']))
showlogin('工单系统 需要验证身份：','area&mode');

//===============ajax =============
if (isset($_POST['did'])){ //接受工单
	$id = intval($_POST['did']);
	$getter = $mysqli->real_escape_string($_SESSION['WorkUser']['user']);
	$real = $mysqli->real_escape_string($_SESSION['WorkUser']['realname']);
	$mysqli->query("update work set `getdate`=now(),`getter`='$getter',`realnameg`='$real',`mode`=2 where id = $id");
	if ($mysqli->affected_rows)
		die("成功");
	else die("失败");
}elseif (isset($_POST['nid'])){//反馈及索取验证码
	$id = intval($_POST['nid']);
	$reply = $mysqli->real_escape_string($_POST['re']);
	$mysqli->query("update work set `code`='',`reply`='$reply',`sendcodetime`=now(),`mode`=3 where id = $id");
	if ($mysqli->affected_rows)
		die("成功");
		else die("被忽略");
}elseif (isset($_POST['sid']) && isset($_POST['cd'])){//发送验证码
	$id = intval($_POST['sid']);
	$code = $mysqli->real_escape_string($_POST['cd']);
	$mysqli->query("update work set `code`='$code' where id = $id");
	if ($mysqli->affected_rows)
		die("成功");
		else die("被忽略");
}elseif (isset($_POST['eid'])){//业务完成
	$id = intval($_POST['eid']);
	$mysqli->query("update work set `endtime`=now(),`mode`=9 where id = $id");
	if ($mysqli->affected_rows)
		die("成功");
		else die("被忽略");
}elseif (isset($_POST['eeid'])){//放弃任务完成
	$id = intval($_POST['eeid']);
	$mysqli->query("update work set `endtime`=now(),`mode`=10,`code`='放弃任务' where id = $id");
	if ($mysqli->affected_rows)
		die("成功");
		else die("被忽略");
}elseif (isset($_POST['delid'])){//删除
	$id = intval($_POST['delid']);
	$mysqli->query("delete from work where id = $id and `mode`=1");
	if ($mysqli->affected_rows)
		die("成功");
		else die("被忽略");
}elseif (isset($_POST['sid']) && isset($_POST['mod'])){//修改描述
	$id = intval($_POST['sid']);
	$info = $mysqli->real_escape_string($_POST['mod']);
	$mysqli->query("update work set `info`='$info' where id = $id and `mode`=1");
	if ($mysqli->affected_rows)
		die("成功");
		else die("被忽略");
}
//===============================
if (empty($_GET['include']))
	$head = '<p class=\'pcenter\' style=\'font-size:18pt;\'>工单任务 &nbsp; &nbsp; <span class=\'orange\'>'.$_SESSION['WorkUser']['mode'].' 操作人：'.$_SESSION['WorkUser']['realname'].'('.$_SESSION['WorkUser']['user'].') &nbsp;   地区：'.$_SESSION['WorkUser']['area'].' </span>  <a href="?" style="font-size:9pt;">【刷新页面】本页面每5秒自动刷新</a></p>';
else
	$head ="";
if ($_SESSION['WorkUser']['mode']=='客服'){
	$zd = 0;
	$where = " `area`= '{$_SESSION['WorkUser']['area']}' and `creater`='{$_SESSION['WorkUser']['user']}'  ";
	$opt = 'function sendcode(sid){  var code =prompt("请输入验证码信息："); if (code.length >3)  $.post( "index_work_list.php", { sid: sid,cd: code })
  .done(function( data ) { alert( "发送验证码操作 " + data);window.location.reload(); });else alert(\'请输入验证码！\'); }
function del(sid){ $.post( "index_work_list.php", { delid: sid })
  .done(function( data ) { alert( "删除工单操作 " + data);window.location.reload();  }); }
function end(sid){ $.post("index_work_list.php", { eeid: sid })
  .done(function( data ) { alert( "放弃业务完成 " + data);window.location.reload();  });}
function modi(sid){ var code =prompt("请重新输入工单描述信息："); if (code.length >3) $.post( "index_work_list.php", { sid: sid,mod: code })
  .done(function( data ) { alert( "修改描述操作 " + data);window.location.reload();  });else alert(\'描述不能太少！\'); }';
}else{
	$zd = 1;
	$where = " `area`= '{$_SESSION['WorkUser']['area']}' and ( `getter`='{$_SESSION['WorkUser']['user']}' or `getter` is NULL )";
	$opt = 'function get(sid){ $.post( "index_work_list.php", { did: sid })
  .done(function( data ) { alert( "接受工单操作 " + data);window.location.reload();  }); }
function needcode(sid){  var code =prompt("请输入反馈信息："); if (code.length >2 && code!=\'undefined\') $.post("index_work_list.php", { nid: sid,re:code })
  .done(function( data ) { alert( "反馈及索取验证码 " + data);window.location.reload(); });else alert(\'请输入反馈信息，长度不能少于3个字符！\');}
function end(sid){ $.post("index_work_list.php", { eid: sid })
  .done(function( data ) { alert( "确认业务完成 " + data);window.location.reload();  });}';
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="main.css"/><script type="text/javascript" src="jquery.js"></script>
<script>
$opt
</script><style type="text/css">
td {padding:3px;}
</style></head><body> $head <table width=1280 border=0 align="center"><tr><td width="270" valign="top"><ul style="margin:2px;padding:2px;">待接受任务：
HTML;
$result = $mysqli->query("select * from work where $where order by lasttime DESC limit $limit");
$i = 1;$showgetter = $showwaitter ="";
while (($row = $result->fetch_array(MYSQLI_ASSOC))!==false) {
	if (!$row) break;
	$bg = fmod($i,2)?"bg1":"bg2";
	$i++;
	$opt= $reply = "";
	if (!empty($row['getter'])){//已经接单的
		$getter = "<span class=bgblue>$row[realnameg] 接单</span>";
		if ($zd){
			if ($row['mode']=='3' && $row['code']=='') //已索取验证码
				$getter .="<span class=bgred> 等验证</span>";
			if ($row['mode']=='3' && $row['code']!=''){//已发验证码
				$getter .="<span class=bggreen> 验证码 $row[code]</span>";
				$opt .= "<button onclick=\"end($row[id])\">业务完成</button> &nbsp;";
			}
			$opt .= "<button onclick=\"needcode($row[id])\">反馈验证</button>";
			$reply = "<br/> -- 反馈：$row[reply]";
		}else{
			if ($row['mode']=='3'){//已索取验证码
				if ( $row['code']=='')
					$getter .="<span class=bgred> 请求验证码</span>";
				else
					$getter .="<span class=bgred> 等确认验证码</span>";
				$opt .='<button onclick="sendcode('.$row['id'].')">发验证码</button>';
			}
			$opt .=' <button onclick="end('.$row['id'].')">放弃</button>';
			if ($row['sendcodetime'])
				$reply = "<br/><span class=bgred> -- 回复：$row[reply] $row[sendcodetime] </span>";
			else $reply = "<br/> -- 已接单尚未反馈...";
		}
	}else{//未接单的
		$getter = "<span class=bgred>待接单</span>";
		if ($zd){
			$opt = "<button onclick=\"get($row[id])\">接受</button>";
		}else{
			$opt = "<button onclick=\"modi($row[id])\">改描述</button> &nbsp; <button onclick=\"del($row[id])\">删除</button>";
		}
	}
	if ($row['mode']=='9'){ //已结束
		$getter .="<span class=bgred> 已完成</span>";
		$opt = "<span class=red> 业务完成！</span>";
	}
	if ($row['mode']=='10'){ //已结束
		$getter .="<span class=bgred> 放弃任务</span>";
		$opt = "<span class=red> 业务被取消！</span>";
	}
	if ($row['ARPU']<1) $row['ARPU'] = '无';
	if ($row['code']!='') $row['info'] .= "<span class=bggreen>（ 验证：$row[code] ）</span>";
	if (!empty($row['getter']))
		$showgetter .="<tr class=$bg><td width=110><span class=bold14>$row[phone]<br/><span class=red>ARPU $row[ARPU] </span></span></td><td width=160>$getter<span id='info$row[id]' style='font-size:9pt;color:red;'></span></td><td style='font-size:10pt;line-height: 140%;padding:2px;'>$row[info] $reply</td><td width=180 style='font-size:10pt;'>$opt</span></td></tr>";
	else 
		$showwaitter .="<li><span class=bold14>$row[phone] &nbsp; $opt<br/><span class='smallgray smallsize-font'>$row[info]</span></li>";
}
echo $showwaitter.'</ul></td><td valign="top"><table width="980" align="center" border=0 style="border-left:1px solid #2e7d98; border-collapse:collapse;">'.$showgetter.'</table></td></tr></table><script language="JavaScript"> 
function myrefresh(){ 
window.location.reload(); 
} 
setTimeout("myrefresh()",5000);  
</script></body></html>';