<?php
define('APPID', 'lmxcc');
set_time_limit(60);
session_start(); 
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
$phpFileUploadErrors = array(
		0 => '文件上传成功！',
		1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。',
		2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。 ',
		3 => '文件只有部分被上传。 ',
		4 => '没有文件被上传。',
		6 => '找不到临时文件夹。',
		7 => '文件写入失败。',
		8 => '一个PHP的扩展库终止了文件上传操作。',
);
if (empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");

$DIR = strstr(PHP_OS, 'WIN') ? str_replace( array('\\\\','\\') , '/', __DIR__ )  : __DIR__ ;

include_once 'DM_db.php';
$adm_level = is_adm();
	
function xmlentities($string){ //不允许 < > " 三种符号
	$value = str_replace(array("<",">",'"'),'_', $string);
	return $value;
}

$id = $_SESSION['domainid'];
$showinfo = "";
if ($id){
	$result = $mysqli->query("select callcenter_config,id,group_prefix,user_prefix,out_prefix,agent_login,agent_out,agent_break,autocall_self,domain_id,DID from fs_domains where domain_id = '$id'");
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

if ($adm_level>4 && !empty($_POST)){
	$group_prefix = $row['group_prefix'];
	$user_prefix = $row['user_prefix'];
	$out_prefix = $row['out_prefix'];
	$agent_login =  intval($_POST['agent_login']);
	$agent_out =  intval($_POST['agent_out']);
	$agent_break = intval($_POST['agent_break']);
	$autocall_self =  $row['autocall_self'];
	$_POST['moh-sound'] = 0;
	$file_changed = 0;
	if (!empty($_FILES)){ //若数据可用，保存上传文件
		if (!file_exists ( $DIR."/$_SESSION[domainid]"))
			mkdir( $DIR."/$_SESSION[domainid]");
		if (!empty($_FILES["moh-sound"]["size"])){
			$size= $_FILES["moh-sound"]["size"];
			$temp= $_FILES["moh-sound"]["tmp_name"];
			$error= $phpFileUploadErrors[$_FILES["moh-sound"]["error"]];
			$showinfo .= "等待音乐上传  $error <br/>";
			if ($size<12000000 &&  $_FILES["moh-sound"]["type"]=="audio/wav")
				$re = move_uploaded_file($temp, $DIR."/$_SESSION[domainid]/moh_sound.wav");
			else $re = 0;
			if ($re){
				$_POST['moh-sound'] = $DIR."/$_SESSION[domainid]/moh_sound.wav";
				$file_changed = 1;
			}
		}
	}
	$callcenter = $row['callcenter_config'];
	if ($callcenter)
		$cc = json_decode($callcenter,true);
	if ($_POST['moh-sound'] === 0)
		$_POST['moh-sound'] = $cc['moh-sound'];
	$check_ = [];
	if (!$agent_login || !$agent_break || !$agent_out || $agent_login>100 || $agent_out>100 || $agent_break>100 ) {
		$showinfo .= "<span class='bgred'>坐席签入\签出\示忙的号码 设置为最多2位数字，且不能为0 不能相同！</span><br/>";
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
	$callcenter= ['strategy'=> $_POST['strategy'],'moh-sound'=>xmlentities($_POST['moh-sound']),'record-template'=>xmlentities($_POST['record-template']),'time-base-score'=>$_POST['time-base-score'],'max-wait-time'=>intval($_POST['max-wait-time']),'max-wait-time-with-no-agent'=>intval($_POST['max-wait-time-with-no-agent']),'max-wait-time-with-no-agent-time-reached'=>intval($_POST['max-wait-time-with-no-agent-time-reached']),'tier-rules-apply'=>$_POST['tier-rules-apply'],'tier-rule-wait-second'=>intval($_POST['tier-rule-wait-second']),'tier-rule-wait-multiply-level'=>$_POST['tier-rule-wait-multiply-level'],'tier-rule-no-agent-no-wait'=>$_POST['tier-rule-no-agent-no-wait'],'abandoned-resume-allowed'=>$_POST['abandoned-resume-allowed'],'discard-abandoned-after'=>intval($_POST['discard-abandoned-after'])];
	$cc = $callcenter;
	$callcenter = json_encode($callcenter);
	$dmold = crc32($agent_login.$agent_out.$agent_break.$callcenter.$file_changed);
	$callcenter =  $mysqli->real_escape_string($callcenter);
	$sql .= "`last_date`=now(),`agent_out`='$agent_out',`agent_login`='$agent_login',`agent_break`='$agent_break',`callcenter_config`='$callcenter'";
	$gwold= "";
}else{
	$agent_login = ($row['agent_login']?$row['agent_login']:50);
	$agent_out = ($row['agent_out']?$row['agent_out']:51);
	$agent_break = ($row['agent_break']?$row['agent_break']:52);
	$callcenter = $row['callcenter_config'];
	$gwold = "";
	$dmold = crc32($agent_login.$agent_out.$agent_break.$callcenter."0");
	$cc = false;
	if ($callcenter)
		$cc = json_decode($callcenter,true);
	if (!is_array($cc))
		$cc = ['strategy'=>'longest-idle-agent','moh-sound'=>'$${hold_music}','record-template'=>'$${recordings_dir}/${strftime(%Y/%m/%d)}/${uuid}.wav','time-base-score'=>'system','max-wait-time'=>0,'max-wait-time-with-no-agent'=>0,'max-wait-time-with-no-agent-time-reached'=>5,'tier-rules-apply'=>'false','tier-rule-wait-second'=>300,'tier-rule-wait-multiply-level'=>'false','tier-rule-no-agent-no-wait'=>'false','abandoned-resume-allowed'=>'false','discard-abandoned-after'=>60];
	$gwold = "<input type=\"hidden\" name=\"dmold\" value=\"$dmold\">";
}	
$domain_id = $row['domain_id'];
$did = $row['DID'];
if (!empty($cc['moh-sound'])){
	$pos = strpos($cc['moh-sound'], "/$domain_id/");
	if ($pos)
		$mohsound = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\"等待音乐\");$(\"#player\").attr(\"src\",\"".substr($cc['moh-sound'], $pos+1)."\");'> 【已上传，试听】 </button>";
	else 
		$mohsound = "<span class=bgblue>【当前默认】</span>";
}else 
	$mohsound = "<span class=bggray>【已取消】</span>";
$html = <<<HTML
<tr class='bg2'><td style='line-height:25pt;'><em>坐席拨号 签入：</em> <input id="agent_login" name="agent_login" value="$agent_login" size=3  maxlength="2" class="inputline1" oninput = "value=value.replace(/[^\d]/g,'')"/> &nbsp; <em>签出：</em> <input id="agent_out" name="agent_out" value="$agent_out" size=3  maxlength="2" class="inputline1" oninput = "value=value.replace(/[^\d]/g,'')"/> <em>示忙\休息：</em> <input id="agent_break" name="agent_break" value="$agent_break" size=3  maxlength="2" class="inputline1" oninput = "value=value.replace(/[^\d]/g,'')"/><span class="smallgray smallsize-font"> * 均限定2位数字，示闲=签入</span><br/><input type="hidden" name="record-template" value="{$cc['record-template']}"/>
<em>振铃策略：</em><select name='strategy' id='strategy' class='inputline1'><option value='ring-all'>所有坐席振铃</option><option value='longest-idle-agent'>空闲时长最长振铃</option><option value='round-robin'>轮循振铃</option><option value='top-down'>顺序振铃</option><option value='agent-with-least-talk-time'>通话时长最小振铃</option><option value='agent-with-fewest-calls'>接听最少振铃</option><option value='sequentially-by-agent-order'>优先级振铃</option><option value='random'>随机振铃</option><option value='ring-progressively'>渐进振铃</option></select>
 <em>时间积分：</em><select name='time-base-score' id='time-base-score' class='inputline1'><option value='queue'>不增加积分</option><option value='system'>进入系统时积分</option></select><br/>
<em>等待音乐：</em> <input type="hidden" name="MAX_FILE_SIZE" value="12000000" />$mohsound <input name='moh-sound' type='file' class='fileInput'  accept='audio/wav' onchange='soundchange("mohsound");'/>  <span id='mohsoundlab' class='bgred'></span><br/><em>最大超时：</em> <input id="max-wait-time" name="max-wait-time" value="{$cc['max-wait-time']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/>秒 <em>无成员超时：</em> <input id="max-wait-time-with-no-agent" name="max-wait-time-with-no-agent" value="{$cc['max-wait-time-with-no-agent']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/>秒 <em>无成员超时后延迟：</em> <input id="max-wait-time-with-no-agent-time-reached" name="max-wait-time-with-no-agent-time-reached" value="{$cc['max-wait-time-with-no-agent-time-reached']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/>秒<br/>
<em>梯队匹配：</em><select name='tier-rules-apply' id='tier-rules-apply' class='inputline1'><option value='false'>不启动tier规则</option><option value='true'>匹配规则（tier-rule*）</option></select> 
<em>梯队等待：</em> <input id="tier-rule-wait-second" name="tier-rule-wait-second" value="{$cc['tier-rule-wait-second']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/>秒 <em>梯队等级等待：</em><select name='tier-rule-wait-multiply-level' id='tier-rule-wait-multiply-level' class='inputline1'><option value='false'>不启用</option><option value='true'>启用</option></select><br/>
<em>跳过无座席：</em><select name='tier-rule-no-agent-no-wait' id='tier-rule-no-agent-no-wait' class='inputline1'><option value='false'>不启用</option><option value='true'>启用</option></select>
<em>呼入丢弃恢复：</em><select name='abandoned-resume-allowed' id='abandoned-resume-allowed' class='inputline1'><option value='false'>不恢复</option><option value='true'>可恢复</option></select> 
<em>呼入丢弃超时：</em> <input id="discard-abandoned-after" name="discard-abandoned-after" value="{$cc['discard-abandoned-after']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/>秒
</td></tr><script>$('#strategy').val('$cc[strategy]');$('#time-base-score').val('{$cc['time-base-score']}');$('#tier-rules-apply').val('{$cc['tier-rules-apply']}');$('#tier-rule-wait-multiply-level').val('{$cc['tier-rule-wait-multiply-level']}');$('#abandoned-resume-allowed').val('{$cc['abandoned-resume-allowed']}');$('#tier-rule-no-agent-no-wait').val('{$cc['tier-rule-no-agent-no-wait']}');</script>
HTML;
if ($adm_level>4)
	$submitbutton = "&nbsp; &nbsp; <input type=\"submit\" value=\"确认提交\" style=\"width:100px;height:35px;\"/>&nbsp; &nbsp; &nbsp; &nbsp;  <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en81($row[id],'$row[domain_id]')\" value='呼叫中心更新部署'/>";
else 
	$submitbutton = "";
if ($adm_level>4 && !empty($_POST)){
	$submitbutton = ' &nbsp; <p style="float:left">&nbsp; <a href="?editDomain='.$id.'">刷新页面</a> &nbsp; &nbsp;  <input type="button" onclick="this.value=\'连接中，请等待反馈...\';$(this).attr(\'disabled\',\'true\');en81('.$row['id'].',\''.$row['domain_id'].'\')" value="呼叫中心更新部署"/></p>';
	$sql  .= $sql_end;
	$result = false;
	if ($dmold ==$_POST['dmold']){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
	}elseif (!$fail)
	$result = $mysqli->query($sql);
	if ($result)
		$showinfo .= "<span class='bggreen'>操作成功！</span>";
	else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
 <script> function soundchange(which) { $("#"+which+"lab").text('已选择文件！');};
function en81(sid,lab){ $.post( "DM_domain_func.php", { yid: sid, en0: "81", en1: lab}) .done(function( data ) { alert( "呼叫中心应用部署 " + data);$('#info'+sid).html(data+'<br/>');});}
</script></head><body><div id="win" style="display:none;"><h2 id="tt"><span id="close" onclick="$('#win').css('display','none')" style="pading:5px;"> × </span></h2><p id="title" class="act2"></p><audio id="player" controls="controls" src="">你的浏览器不支持audio标签。</audio></div><form enctype="multipart/form-data" method="post" id="formarea"><p class="pcenter" style="font-size:18pt;">呼叫中心  <span style="color:red;font-size:10pt;">DID号：<span class=bold12>$did</span></span>  <a style='font-size:10pt;' href='DM_cp.php'>&raquo;&nbsp;基础概要</a> <a style='font-size:10pt;' href='DM_ivr.php'>&raquo;&nbsp;管理IVR</a></p><table class="tablegreen" width="1200" align="center"><tr class='bg1'><th><span id='info$row[id]' style='font-size:9pt;color:red;'></span>$showinfo </th></tr>
 $html<tr class='bg2'><td  align=center>$gwold $submitbutton</td></tr></table></form></body></html>
HTML;
