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
$showinfo = '<span class="smallred smallsize-font"> 设定并启用IVR后，所有呼入均被自动接入IVR！IVR不设定操作提示语音或菜单是不能生效的！</span><br/>';
if ($id){
	$result = $mysqli->query("select ivr_config,domain_id,id from fs_domains where domain_id = '$id'");
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
if ($adm_level >4 && !empty($_POST)){
	$_POST['greet-long'] =   $_POST['greet-short'] =  $_POST['invalid-sound'] = $_POST['exit-sound'] = 0;
	$file_changed = 0;
	if (!empty($_FILES)){ //若数据可用，保存上传文件
		if (!file_exists ( $DIR."/$_SESSION[domainid]"))
			mkdir( $DIR."/$_SESSION[domainid]");
	if (!empty($_FILES["greet-long"]["size"])){
			$size= $_FILES["greet-long"]["size"];
			$temp= $_FILES["greet-long"]["tmp_name"];
			$error= $phpFileUploadErrors[$_FILES["greet-long"]["error"]];
			$showinfo .= "IVR操作提示上传  $error <br/>";
			if ($size<12000000 &&  $_FILES["greet-long"]["type"]=="audio/wav")
				$re = move_uploaded_file($temp, $DIR."/$_SESSION[domainid]/greet_long.wav");
			else $re = 0;
			if ($re){
				$_POST['greet-long'] = $DIR."/$_SESSION[domainid]/greet_long.wav";
				$file_changed = 1;
			}
		}
	if (!empty($_FILES["greet-short"]["size"])){
			$size= $_FILES["greet-short"]["size"];
			$temp= $_FILES["greet-short"]["tmp_name"];
			$error= $phpFileUploadErrors[$_FILES["greet-short"]["error"]];
			$showinfo .= "IVR输入提示上传  $error <br/>";
			if ($size<12000000 &&  $_FILES["greet-short"]["type"]=="audio/wav")
				$re = move_uploaded_file($temp, $DIR."/$_SESSION[domainid]/greet_short.wav");
			else $re = 0;
			if ($re){
				$_POST['greet-short'] = $DIR."/$_SESSION[domainid]/greet_short.wav";
				$file_changed = 1;
			}
		}
	if (!empty($_FILES["invalid-sound"]["size"])){
			$size= $_FILES["invalid-sound"]["size"];
			$temp= $_FILES["invalid-sound"]["tmp_name"];
			$error= $phpFileUploadErrors[$_FILES["invalid-sound"]["error"]];
			$showinfo .= "IVR输入错误提示上传  $error <br/>";
			if ($size<12000000 &&  $_FILES["invalid-sound"]["type"]=="audio/wav")
				$re = move_uploaded_file($temp, $DIR."/$_SESSION[domainid]/invalid_sound.wav");
			else $re = 0;
			if ($re){
				$_POST['invalid-sound'] = $DIR."/$_SESSION[domainid]/invalid_sound.wav";
				$file_changed = 1;
			}
		}
	if (!empty($_FILES["exit-sound"]["size"])){
			$size= $_FILES["exit-sound"]["size"];
			$temp= $_FILES["exit-sound"]["tmp_name"];
			$error= $phpFileUploadErrors[$_FILES["exit-sound"]["error"]];
			$showinfo .= "IVR退出提示上传  $error <br/>";
			if ($size<12000000 &&  $_FILES["exit-sound"]["type"]=="audio/wav")
				$re = move_uploaded_file($temp, $DIR."/$_SESSION[domainid]/exit_sound.wav");
			else $re = 0;
			if ($re){
				$_POST['exit-sound'] = $DIR."/$_SESSION[domainid]/exit_sound.wav";
				$file_changed = 1;
			}
		}						
	}
	$ivr = $row['ivr_config'];
	if ($ivr)
		$ivr1 = json_decode($ivr,true);
	if ($_POST['greet-long'] ===0)
		$_POST['greet-long'] = $ivr1['greet-long'];
	if ($_POST['greet-short'] ===0)
		$_POST['greet-short'] = $ivr1['greet-short'];
	if ($_POST['invalid-sound'] ===0)
		$_POST['invalid-sound']  = $ivr1['invalid-sound'];
	if ($_POST['exit-sound'] === 0)
		$_POST['exit-sound'] = $ivr1['exit-sound'];

	$menu = [];
	if (is_array(@$_POST['menu'])){
		foreach ($_POST['menu'] as $k=>$one){
			if ($one['d'] != ''){
				$one['d'] = xmlentities($one['d']);
				if ($one['a'] == 'menu-play-sound'){
					if (!empty($_FILES["menu$k"]["size"])){
						$size= $_FILES["menu$k"]["size"];
						$temp= $_FILES["menu$k"]["tmp_name"];
						$error= $phpFileUploadErrors[$_FILES["menu$k"]["error"]];
						$showinfo .= "IVR菜单语音上传  $error <br/>";
						$tname = $DIR."/$_SESSION[domainid]/menu$k.wav";
						if ($size<12000000 &&  $_FILES["menu$k"]["type"]=="audio/wav")
							$re = move_uploaded_file($temp, $tname);
						else $re = 0;
						if ($re){
							$one['p'] = $tname;
							$file_changed = 1;
						}else continue;
					}else 
						$one['p'] = xmlentities($one['p']);
				}elseif(isset($one['p']))
				$one['p'] = xmlentities($one['p']);
			$menu[] = $one;
			}
		}
		if ($menu)
			$_POST['menu'] = $menu;
	}else $_POST['menu'] = [];
	$ivr =['greet-long' =>xmlentities($_POST['greet-long']),'timeout' => intval($_POST['timeout']),'greet-short' => xmlentities($_POST['greet-short']),'max-timeouts' => intval($_POST['max-timeouts']),'invalid-sound' => xmlentities($_POST['invalid-sound']),'exit-sound' => xmlentities($_POST['exit-sound']),'digit-len' => $_POST['digit-len'],	'inter-digit-timeout' =>intval($_POST['inter-digit-timeout']),'confirm-key'=>$_POST['confirm-key'],'max-failures'=>intval($_POST['max-failures']),'menu'=>$_POST['menu']];
	$ivr1 = $ivr;
	$ivr = json_encode($ivr);
	$dmold = crc32($ivr.$file_changed);
	$ivr = $mysqli->real_escape_string($ivr);
	$sql .= "`last_date`=now(),`ivr_config`='$ivr'";
	$gwold= "";
}else{
	$ivr = @$row['ivr_config'];
	$dmold = crc32($ivr."0");
	$ivr1= false;
	if ($ivr)
		$ivr1 = json_decode($ivr,true);
	if (!is_array($ivr1))
		$ivr1 =['greet-long' =>'','timeout' => '30000','greet-short' => 'ivr/ivr-enter_ext.wav','max-timeouts' => '','invalid-sound' => 'ivr/ivr-please_check_number_try_again.wav',	'exit-sound' => 'voicemail/vm-goodbye.wav','digit-len' =>4,	'inter-digit-timeout' =>3000,'confirm-key'=>'','max-failures'=>3];
	$menu = isset($ivr1['menu'])?$ivr1['menu']:[];
	$gwold = "<input type=\"hidden\" name=\"dmold\" value=\"$dmold\">";
}
$domain_id = $row['domain_id'];
$menuHtml = $maDefault = "";
$menucount = count($menu);
if ($menu){
foreach ($menu as $k =>$one){
	$k++;
	if  ( $one['a'] =='menu-play-sound' ){
		if (empty($one['p']))
			continue;
		$pos = strpos($one['p'], "/$domain_id/");
		if ($pos)
			$msound = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\"IVR菜单语音\");$(\"#player\").attr(\"src\",\"".substr($one['p'], $pos+1)."\");'> 【已上传，试听】 </button>";
		else 
			$msound = "<span class=bgblue>【当前默认】</span>";
		$input = " 按键： <input name='menu[$k][d]' oninput ='limitnum(this)' value='$one[d]' style='width:30px;'> 播放的声音上传： <input name='menu$k' type='file' class='fileInput'  accept='audio/wav'> <span class='smallgray smallsize-font'> 按下相应键值后即播放所设定的声音</span><input type='hidden' value='$one[p]' name='menu[$k][p]'> $msound";
	}elseif  ($one['a']=='menu-exec-app'){
		if (empty($one['p']))
			continue;
		$input = " 按键： <input name='menu[$k][d]' value='$one[d]' oninput ='limitnum(this)' style='width:30px;'> 转分机： <input name='menu[$k][p]' oninput ='limitnum(this)' value='$one[p]'> <span class='smallgray smallsize-font'> 按下相应键值后跳转设定分机，对应固定分机！</span>";
	}elseif ( $one['a'] =='menu-exec-app1' )
		$input = " 分机号码长度： <input name='menu[$k][d]' oninput ='limitnum(this)' value='$one[d]' style='width:30px;'> <span class='smallgray smallsize-font'> 填写分机号码的长度，如输入4，表示连续输入4位分机号后即跳转此分机！</span>";
	elseif ( $one['a'] =='menu-exec-app2' )
		$input = " 按键： <input name='menu[$k][d]' value='$one[d]' oninput ='limitnum(this)' style='width:30px;'> <span class='smallgray smallsize-font'> 按下相应键值后跳转到坐席队列，由坐席按队列顺序自动接听！</span>";
	else $input = " 按键： <input name='menu[$k][d]' oninput ='limitnum(this)' style='width:30px;' value='$one[d]'>";
	$menuHtml .="<p class='pcenter'><select name='menu[$k][a]'  id='s$k' onchange='changeinput(\"$k\")'><option value='menu-exec-app'>指定分机</option><option value='menu-exec-app1'>拨分机号</option><option value='menu-exec-app2'>坐席自动</option><option value='menu-play-sound'>播放声音</option><option value='menu-top'>回主菜单</option><option value='menu-exit'>退出菜单</option></select><span id='p$k'> $input </span><span onclick='remove(this)' style='cursor:pointer;' title='删除'>&otimes;</span></p>";
	$maDefault .= "\$(\"#s$k option[value=$one[a]]\").attr(\"selected\", \"selected\");";
}
}
if (!empty($ivr1['greet-long'])){
	$pos = strpos($ivr1['greet-long'], "/$domain_id/");
	if ($pos)
		$greetlongsound = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\"IVR欢迎提示\");$(\"#player\").attr(\"src\",\"".substr($ivr1['greet-long'], $pos+1)."\");'> 【已上传，试听】 </button>";
	else
		$greetlongsound = "<span class=bgblue>【当前默认】</span>";
}else
	$greetlongsound = "<span class=bggray>【未设置】</span>";
if (!empty($ivr1['greet-short'])){
	$pos = strpos($ivr1['greet-short'], "/$domain_id/");
	if ($pos)
		$greetshortsound = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\"待输入提示语音\");$(\"#player\").attr(\"src\",\"".substr($ivr1['greet-short'], $pos+1)."\");'> 【已上传，试听】 </button>";
	else
		$greetshortsound = "<span class=bgblue>【当前默认】</span>";
}else
	$greetshortsound = "<span class=bggray>【未设置】</span>";
if (!empty($ivr1['invalid-sound'])){
	$pos = strpos($ivr1['invalid-sound'], "/$domain_id/");
	if ($pos)
		$invalidsound = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\"输入错误语音\");$(\"#player\").attr(\"src\",\"".substr($ivr1['invalid-sound'], $pos+1)."\");'> 【已上传，试听】 </button>";
	else
		$invalidsound = "<span class=bgblue>【当前默认】</span>";
}else
	$invalidsound = "<span class=bggray>【未设置】</span>";
if (!empty($ivr1['exit-sound'])){
	$pos = strpos($ivr1['exit-sound'], "/$domain_id/");
	if ($pos)
		$exitsound = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\"退出语音\");$(\"#player\").attr(\"src\",\"".substr($ivr1['exit-sound'], $pos+1)."\");'> 【已上传，试听】 </button>";
	else
		$exitsound = "<span class=bgblue>【当前默认】</span>";
}else
	$exitsound = "<span class=bggray>【未设置】</span>";
$c_key = empty($ivr1['confirm-key'])?"#":$ivr1['confirm-key'];
$html = <<<HTML
<script>var num=$menucount;</script>
<tr class='bg2'><td style='line-height:25pt;'> 操作提示语音： $greetlongsound  <input name='greet-long' type='file' class='fileInput'  accept='audio/wav' onchange='soundchange("greetlong");'/>  <span id='greetlonglab' class='bgred'></span> ，<br/>
若在 <input id="timeout" name="timeout" value="{$ivr1['timeout']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/>毫秒内未输入，播放待输入提示语音：  $greetshortsound  <input name='greet-short' type='file' class='fileInput'  accept='audio/wav' onchange='soundchange("greetshort");'/>  <span id='greetshortlab' class='bgred'></span>，若未输入，系统再播放 <input id="max-timeouts" name="max-timeouts" value="{$ivr1['max-timeouts']}" class="inputline1" style="width:15px;" oninput = "value=value.replace(/[^\d]/g,'')"/> -1次待输入提示后退出；<br/>
若输入错误，系统播放输入错误语音： $invalidsound  <input name='invalid-sound' type='file' class='fileInput'  accept='audio/wav' onchange='soundchange("invalidsound");'/>  <span id='invalidsoundlab' class='bgred'></span>，<br/>
最多允许输入错误 <input id="max-failures" name="max-failures" value="{$ivr1['max-failures']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/> 次；菜单最长{$ivr1['digit-len']}位数字<input type="hidden" name="digit-len" value="{$ivr1['digit-len']}">，等待输入超时 <input id="inter-digit-timeout" name="inter-digit-timeout" value="{$ivr1['inter-digit-timeout']}" class="inputline1" size=1 oninput = "value=value.replace(/[^\d]/g,'')"/>毫秒 ，按 $c_key <input type="hidden" name="confirm-key" value="{$ivr1['confirm-key']}">键结束 ，结束语音：  $exitsound <input name='exit-sound' type='file' class='fileInput'  accept='audio/wav' onchange='soundchange("exit");'/> <span id='exitlab' class='bgred'></span><p class=pcenter> ->>>> <input type='button' value='点这添加菜单项' onclick='add(num)'><<<<- <span class="smallgray smallsize-font" style="line-height:14pt;"> 按键是数值如 1、5、23 </span></p><span id='menu_area'>$menuHtml</span></td></tr>
<script type="text/javascript">
$maDefault
//添加一行<tr>
function add() {
num++;
var content = "<p class='pcenter'>";
content += "<select id='s"+num+"' name='menu["+num+"][a]' onchange='changeinput(\""+num+"\")'><option value='menu-exec-app'>指定分机</option><option value='menu-exec-app1'>拨分机号</option><option value='menu-exec-app2'>坐席自动</option><option value='menu-play-sound'>播放声音</option><option value='menu-top'>回主菜单</option><option value='menu-exit'>退出菜单</option></select><span id='p"+num+"'> 按键： <input name='menu["+num+"][d]' oninput ='limitnum(this)' style='width:30px;'> 转分机： <input name='menu["+num+"][p]' oninput ='limitnum(this)'> <span class='smallgray smallsize-font'> 按下相应键值后跳转设定的分机，对应固定分机！</span></span> <span onclick='remove(this)' style='cursor:pointer;'  title='删除'>&otimes;</span>";
content +="</p>"
$("#menu_area").append(content);
}
//删除当前行
function remove(obj) {
$(obj).parent().remove();
}
function changeinput(sid) {
getv = $('#s'+sid).val();
if  ( getv =='menu-play-sound' )
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> 播放的声音上传： <input name='menu"+sid+"' type='file' class='fileInput'  accept='audio/wav'> <span class='smallgray smallsize-font'> 按下相应键值后即播放所设定的声音</span>");
else
if  (getv =='menu-exec-app')
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> 转分机： <input name='menu["+sid+"][p]' oninput ='limitnum(this)'> <span class='smallgray smallsize-font'> 按下相应键值后跳转设定分机，对应固定分机！</span>");
else
if ( getv =='menu-exec-app1' )
	$('#p'+sid).html(" 分机号码长度： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> <span class='smallgray smallsize-font'> 填写分机号码的长度，如输入4，表示连续输入4位分机号后即跳转此分机！</span>");
else
if  (getv =='menu-exec-app2')
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> <span class='smallgray smallsize-font'> 按下相应键值后跳转到坐席队列，由坐席按队列顺序自动接听！</span>");
else
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'>");
}
function limitnum(a){
a.value=a.value.replace(/[^\\d]/g,'');
}
</script>
HTML;
if ($adm_level>4)
	$submitbutton = "&nbsp; &nbsp; <input type=\"submit\" value=\"确认提交\" style=\"width:100px;height:35px;\"/> &nbsp; &nbsp; &nbsp; &nbsp;  <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en80($row[id],'$row[domain_id]')\" value='IVR更新部署'/>";
else 
	$submitbutton = "";
if ($adm_level>4 && !empty($_POST)){
	$submitbutton = ' &nbsp; <p style="float:left">&nbsp; <a href="?editDomain='.$id.'">刷新页面</a> &nbsp; &nbsp;  <input type="button" onclick="this.value=\'连接中，请等待反馈...\';$(this).attr(\'disabled\',\'true\');en80('.$row['id'].',\''.$row['domain_id'].'\')" value="IVR更新部署"/></p>';
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
function en80(sid,lab){ $.post( "DM_domain_func.php", { yid: sid, en0: "80", en1: lab}) .done(function( data ) { alert( "IVR应用部署 " + data);$('#info'+sid).html(data+'<br/>');});}
</script></head><body><div id="win" style="display:none;"><h2 id="tt"><span id="close" onclick="$('#win').css('display','none')" style="pading:5px;"> × </span></h2><p id="title" class="act2"></p><audio id="player" controls="controls" src="">你的浏览器不支持audio标签。</audio></div><form enctype="multipart/form-data" method="post" id="formarea"><p class="pcenter" style="font-size:18pt;">IVR菜单   <a style='font-size:10pt;' href='DM_cp.php'>&raquo;&nbsp;基础概要</a> <a style='font-size:10pt;' href='DM_callcenter.php'>&raquo;&nbsp;呼叫中心</a></p><table class="tablegreen" width="1200" align="center"><tr class='bg1'><th><span id='info$row[id]' style='font-size:9pt;color:red;'></span>$showinfo </th></tr>$html<tr class='bg1'><td align=center>$gwold $submitbutton</td></tr></table></form></body></html>
HTML;
