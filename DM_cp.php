<?php
define('APPID', 'lmxcc');
set_time_limit(60);
session_start(); 
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['lmxccusers']) || empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");

$DIR = strstr(PHP_OS, 'WIN') ? str_replace( array('\\\\','\\') , '/', __DIR__ )  : __DIR__ ;

include_once 'DM_db.php';
$adm_level = is_adm();
//-------------------修改或添加域信息-----当存在 $_GET['editDomain']------------------------------------------------------------------
if (!empty($_GET['editDomain'])){
	
function xmlentities($string){ //不允许 < > " 三种符号
	$value = str_replace(array("<",">",'"'),'_', $string);
	return $value;
}

$id = $_SESSION['domainid'];
$showinfo = "";
if ($id){
	$result = $mysqli->query("select domain_name,group_prefix,user_prefix,out_prefix,agent_login,agent_out,agent_break,autocall_self,domain_id,out_config,autocall_lines,DID from fs_domains where domain_id = '$id'");
	$sql = "update fs_domains set ";
	$sql_end = " where domain_id = '$id'";
}else{
	die("错误ID");
}

$fail = 0;
if ($result)
	$row = $result->fetch_array();
else 
	die("数据无效！");
if ($row['domain_id'] != $_SESSION['domainid']) //避免操作者数据异常动了其他人的数据
	die("数据非法！不可操作！");

if ($adm_level>4 && !empty($_POST)){
	$domain_name = $_POST['domain_name'];
	$group_prefix = intval($_POST['group_prefix']);
	$user_prefix = intval($_POST['user_prefix']);
	$out_prefix = intval($_POST['out_prefix']);
	$agent_login = ($row['agent_login']?$row['agent_login']:50);
	$agent_out = ($row['agent_out']?$row['agent_out']:51);
	$agent_break = ($row['agent_break']?$row['agent_break']:52);
	$autocall_self =  intval($_POST['autocall_self']);
	if (empty($domain_name)) {
		$showinfo .= "<span class='bgred'>必须提交域名！</span><br/>";
		$fail = 1;
	}
	$check_ = [];
	if (!$group_prefix || !$user_prefix || !$out_prefix || $group_prefix>999 || $user_prefix>999 || $out_prefix>999) {
		$showinfo .= "<span class='bgred'>用户前缀和组前缀、呼出前缀须设置为最多3位数字，且不能为0 不能相同！</span><br/>";
		$fail = 1;
	}
	if ( !$autocall_self || $autocall_self >100) {
		$showinfo .= "<span class='bgred'>用户自动外呼号码 设置为最多2位数字，且不能为0 不能相同！</span><br/>";
		$fail = 1;
	}
	$check_[$user_prefix] = "";
	$check_[$group_prefix] = "";
	$check_[$autocall_self] = "";
	$check_[$out_prefix] = "";
	$check_[$agent_login] = "";
	$check_[$agent_out] = "";
	$check_[$agent_break] = "";
	if (count($check_)<7){
		$showinfo .= "<span class='bgred'>坐席签入\签出\示忙的号码，坐席自动外呼号码、用户前缀、组前缀、呼出前缀，存在重复设置！</span><br/>";
		$fail = 1;
	}
	$out_config =['callerout' =>$_POST['callerout'],'callerout_name' => $_POST['callerout_name'],'callerout_id' => $_POST['callerout_id'],'callerout_gw' => $_POST['callerout_gw'],'callerout_gw_name' => $_POST['callerout_gw_name'],	'callerout_to' =>$_POST['callerout_to'],'callerout_to_prefix' =>$_POST['callerout_to_prefix']];
	$out_config1 = $out_config;
	$out_config = json_encode($out_config);
	$dmold = crc32($domain_name.$user_prefix.$group_prefix.$out_prefix.$out_config.$autocall_self);
	$domain_name = $mysqli->real_escape_string($domain_name);
	$out_config =  $mysqli->real_escape_string($out_config);
	$sql .= "`domain_name`='$domain_name',`last_date`=now(),`user_prefix`='$user_prefix',`group_prefix`='$group_prefix',`out_prefix`='$out_prefix',`out_config`='$out_config',`autocall_self`='$autocall_self'";
	$gwold= "";
}else{
	$domain_name = $row['domain_name'];
	$out_prefix =  ($row['out_prefix']?$row['out_prefix']:7);
	$user_prefix = ($row['user_prefix']?$row['user_prefix']:8);
	$group_prefix = ($row['group_prefix']?$row['group_prefix']:9);
	$autocall_self = ($row['autocall_self']?$row['autocall_self']:6);
	$out_config =  $row['out_config'];
	$gwold = "";
	$dmold = crc32($domain_name.$user_prefix.$group_prefix.$out_prefix.$out_config.$autocall_self);
	$out_config1 = false;
	if ($out_config)
		$out_config1 = json_decode($out_config,true);
	if (!is_array($out_config1))
		$out_config1 =['callerout' =>'default','callerout_name' => '','callerout_id' => '','callerout_gw' => 'default','callerout_gw_name' => '',	'callerout_to' => '','callerout_to_prefix' => ''];
	$gwold = "<input type=\"hidden\" name=\"dmold\" value=\"$dmold\">";
}	
$domain_id = $row['domain_id'];
$autocall_lines = ($row['autocall_lines']?$row['autocall_lines']:3);
$html = <<<HTML
<tr class='bg1'><td width=80><em>域名称：</em></td><td><input id="domain_name" name="domain_name" size="30"  maxlength="20" value="$domain_name" onclick="this.select();" class="inputline1"/> <em>标识 ：</em> <span class=bold12>$domain_id</span> </em>&nbsp; <span class="smallgray smallsize-font"> * 域名称不超过20，不得重复</span></td></tr>
<tr class='bg2'><td><em>信息项</em>$gwold </td><td style='line-height:20pt;'><em>用户前缀</em> <input id="user_prefix" name="user_prefix" value="$user_prefix" size=4 maxlength="3" class="inputline1" oninput = "value=value.replace(/[^\d]/g,'')"/> &nbsp; <em>组前缀</em> <input id="group_prefix" name="group_prefix" value="$group_prefix" size=4  maxlength="3" class="inputline1" oninput = "value=value.replace(/[^\d]/g,'')"/><span class="smallgray smallsize-font"> * 用户前缀和组前缀：用来在拨号时区分用户和组的前缀数字，不得相同，不得为空</span></td></tr>
<tr class='bg1'><td><em>外呼设置：</em><br/><br/><span style="color:red;">并发：<span class=bold16>$autocall_lines</span></span></td><td style='line-height:25pt;'><em>主叫信息：</em> <label><input id="calleroutd" name="callerout" value="default" type="radio" />默认</label> &nbsp; <label><input id="calleroutm" name="callerout" value="set" type="radio" />设定：主叫名称 <input name="callerout_name" value="$out_config1[callerout_name]" class="inputline1" style="width:50pt;" onclick="$('#calleroutm').prop('checked',true);"/> 主叫ID <input name="callerout_id" value="$out_config1[callerout_id]" class="inputline1" style="width:50pt;" onclick="$('#calleroutm').prop('checked',true);"/></label>  <br/><em>被叫号码：</em><label><input id="callerout_to" name="callerout_to" value="" type="radio" />直接拨出</label> &nbsp; <label><input id="callerout_topre" name="callerout_to" value="prefix" type="radio" />固定前缀 <input name="callerout_to_prefix" value="$out_config1[callerout_to_prefix]" class="inputline1" style="width:50pt;" onclick="$('#callerout_topre').prop('checked',true);"/></label> <br/><em>外呼前缀：</em> <input id="out_prefix" name="out_prefix" value="$out_prefix" size=4  maxlength="3" class="inputline1" oninput = "value=value.replace(/[^\d]/g,'')"/> &nbsp; <em>坐席自动外呼号码：</em> <input id="autocall_self" name="autocall_self" value="$autocall_self" size=4  maxlength="2" class="inputline1" oninput = "value=value.replace(/[^\d]/g,'')"/> <input type="hidden" name="callerout_gw" value="$out_config1[callerout_gw]" /><input name="callerout_gw_name" value="$out_config1[callerout_gw_name]" type="hidden"/></td></tr>
<script type="text/javascript">
if ('$out_config1[callerout]'=='default') $('#calleroutd').prop('checked','checked'); else if ('$out_config1[callerout]'=='set') $('#calleroutm').prop('checked','checked'); else $('#calleroutapi').prop('checked','checked'); 
if ('$out_config1[callerout_to]'=='') $('#callerout_to').prop('checked','checked'); else if ('$out_config1[callerout_to]'=='prefix') $('#callerout_topre').prop('checked','checked'); else $('#callerout_toapi').prop('checked','checked');
</script>
HTML;
if ($adm_level>4)
	$submitbutton = "&nbsp; &nbsp; <input type=\"submit\" value=\"确认提交\" style=\"width:100px;height:35px;\"/>";
else 
	$submitbutton ="";
if (!empty($_POST)){
	$submitbutton = ' &nbsp; <p style="float:left">&nbsp; <a href="?editDomain='.$id.'">刷新页面</a></p>';
	$sql  .= $sql_end;
	$result = false;
	if ($dmold ==$_POST['dmold']){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
	}elseif (!$fail)
	$result = $mysqli->query($sql);
	if ($result)
		$showinfo .= "<span class='bggreen'>操作成功！需重新部署应用才会生效！</span>";
	else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script></head><body><form method="post" id="formarea"><p class="pcenter" style="font-size:18pt;">呼叫信息设置   <a style='font-size:10pt;' href='DM_cp.php'>&raquo;&nbsp;基础概要</a> <a style='font-size:10pt;' href='DM_callcenter.php'>&raquo;&nbsp;呼叫中心</a> <a style='font-size:10pt;' href='DM_ivr.php'>&raquo;&nbsp;IVR管理</a></p><table class="tablegreen" width="1200" align="center"><th colspan=2>$showinfo </th>$html<tr class='bg2'><td colspan=2 align=center><span class="smallgray smallsize-font">  **用户标识（拨打两个*加用户id），如 **12345 为对12345的呼出通话进行强行代接 </span> $submitbutton</td></tr></table></form></body></html>
HTML;
	exit;
}

//-----------域管理---------ajax提交部署、停用、启用、禁用、删除等域的相关操作-------------------------------------------
//----------------------显示----------域列表--------------------------------------------------------------------------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css\main.css\"/><script src=\"css\jquery.js\"></script>";
if ($adm_level >4)
	echo "<script>
function en66(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en6: \"66\", en1: lab})
  .done(function( data ) { alert( \"启用域外呼任务程序 \" + data);$('#info'+sid).html('外呼程序已调用！');});}
function en77(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en6: \"77\", en1: lab})
  .done(function( data ) { alert( \"停止域外呼任务程序 \" + data);$('#info'+sid).html('外呼程序已停止！');});}
function en88(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"DM_domain_func.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script>";
echo "</head><body>";
	echo '<table class="tablegreen" width="600" height="400" align="center" style="margin-top:50px;"><th colspan=2>~~ 下面是对基础设置的操作，请谨慎进行 ~~</span></th>';
	$result = $mysqli->query("select * from fs_domains where `domain_id`='$_SESSION[domainid]'");
	$row = $result->fetch_array();
	if (!$row)
			die('</table></body></html>');
	else{
			if ($row['enabled']){
				$file_ = @$_SESSION['conf_dir']."/directory/".$row['domain_id'].".xml";
				if (is_file($file_)){
					$showalert= ' 域ID：'.$row['id'].' &nbsp; <em class=\'bold14\'>'.$row['domain_name'].'&nbsp;  </em><span class="bggreen">已应用 </span>';
					$showtools=" <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en99($row[id],'$row[domain_id]')\" value='停用'/> <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en66($row[id],'$row[domain_id]')\" value='开始外呼'/> <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en77($row[id],'$row[domain_id]')\" value='停止外呼'/>";
				}else{
					$showalert= '域ID：'.$row['id'].' &nbsp; <em class=\'bold14\'>'.$row['domain_name'].'&nbsp;  </em><span class="bgblue">已停用 </span>';
					$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en88($row[id],'$row[domain_id]')\" value='部署应用'/>";
				}
			}else 
				$showalert= '域ID：'.$row['id'].'  &nbsp; <em class=\'bold14\'>'.$row['domain_name'].'&nbsp; </em><span class="bgred">已禁止 </span>';
			
			$totle = $mysqli->query("SELECT `enabled` ,COUNT(*) FROM fs_groups WHERE `domain_id` = '$row[domain_id]'  GROUP BY `enabled` order by `enabled` ");
			$dialplans = result_fetch_all($totle);
			unset($totle);
			$totle = array('0'=>0,'1'=>0);
			foreach ($dialplans as $one){
				if ($one[0]=='1') $totle['1']=$one[1];
				else $totle[$one[0]]=$one[1];
			}
			$showguser = "含组：可用<span class=bold16> $totle[1] </span>   不可用<span class=bold16> $totle[0] </span>  <a href='DM_groups_cp.php?dmid=$row[domain_id]'>&raquo;&nbsp;管理组</a>";
			
			$totle = $mysqli->query("SELECT `enabled` ,COUNT(*) FROM fs_users WHERE `domain_id` = '$row[domain_id]'  GROUP BY `enabled` order by `enabled` ");
			$dialplans = result_fetch_all($totle);
			unset($totle);
			$totle = array('0'=>0,'1'=>0);
			foreach ($dialplans as $one){
				if ($one[0]=='1') $totle['1']=$one[1];
				else $totle[$one[0]]=$one[1];
			}
			$showuser = "含用户：可用<span class=bold16> $totle[1] </span>  不可用<span class=bold16> $totle[0] </span>  <a href='DM_users_cp.php?dmid=$row[domain_id]'>&raquo;&nbsp;管理用户</a>";
			
			$options = "Level:<strong>".$row["level"]."</strong>";
			$options .= " &nbsp; DID:<span class=bold16>".$row["DID"]."</span>";
			echo "<tr class='bg1'><td>$showalert</td><td>域标识：$row[domain_id]</td></tr>
<tr class='bg2'><td> $showguser</td><td> $showuser</td></tr><tr class='bg1'><td>$options</td><td>";
			if ($adm_level>4)
				echo "<a href='?editDomain=$row[id]'> &raquo;&nbsp;&raquo;&nbsp;信息修改...</a> <a href='DM_callcenter.php'> &raquo;&nbsp;&raquo;&nbsp;呼叫中心...</a> <a href='DM_ivr.php'> &raquo;&nbsp;&raquo;&nbsp;IVR修改...</a>";
			echo "<span id='info$row[id]' style='font-size:9pt;color:red;'></span></tr><tr class='bg2'><td align=center colspan=2>";
			if ($row['enabled']){
				if ($adm_level >4)
					echo $showtools;
			}else 
				echo " 本域已停用";
			echo "</span></td></tr>";
		}
$mysqli->close();