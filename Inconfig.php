<?php
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");

$file =__DIR__.'/.Config';

$conf = false;
$fail = 0;

if ($_POST){
	function xmlentities($string){
		return str_replace(array("&","<",">",'"',"'"),'_', trim($string));
	}
	
	$conf['CDR_url'] = xmlentities($_POST['CDR_url']);
	$conf['CDR_xml'] = xmlentities($_POST['CDR_xml']);
	$conf['realm'] = xmlentities($_POST['realm']);
	$conf['wss'] = xmlentities($_POST['wss']);
	$conf['API_url_caller'] = xmlentities($_POST['API_url_caller']);
	$conf['API_url_callee'] = xmlentities($_POST['API_url_callee']);
	$conf['API_url_autoself'] = xmlentities($_POST['API_url_autoself']);
	$conf['API_url_autoserv'] = xmlentities($_POST['API_url_autoserv']);
	if (!empty($_POST['CDR_file']))
		$conf['CDR_file'] = xmlentities($_POST['CDR_file']);
	else 
		$conf['CDR_file'] = '@start_stamp@_@destination_number@_@caller_id_number@.wav';
	$conf['odbcdsn'] = xmlentities($_POST['odbcdsn']);
	$conf['CDR_debug'] = xmlentities($_POST['CDR_debug']);
	$conf['default_password'] = xmlentities($_POST['default_password']);
	$conf['sound_prefix'] = xmlentities($_POST['sound_prefix']);
	$conf['default_language'] = xmlentities($_POST['default_language']);
	$conf['default_dialect'] = xmlentities($_POST['default_dialect']);
	$conf['default_voice'] = xmlentities($_POST['default_voice'] );
	$conf['modules_add'] = xmlentities($_POST['modules_add'] );
	if (file_put_contents($file, serialize($conf)))
		$label = "配置已经保存成功！";
}

if (!is_file($file)){
	$fail = 1;
}elseif(!$conf)
$conf = @unserialize(file_get_contents($file));
if (!$conf)
	$fail = 1;
else{
	if (isset($conf['odbcdsn']))
		$odbcdsncss = "inputline1";
	else
		$odbcdsncss = "inputline";
	if (isset($conf['wss']))
		$wsscss = "inputline1";
	else
		$wsscss = "inputline";
	if (isset($conf['realm']))
		$realmcss = "inputline1";
	else
		$realmcss = "inputline";
	if (isset($conf['CDR_debug']))
		$CDR_debugcss = "inputline1";
	else
		$CDR_debugcss = "inputline";
	if (isset($conf['CDR_url']))
		$CDR_urlcss = "inputline1";
	else
		$CDR_urlcss = "inputline";
	if (isset($conf['API_url_caller']))
		$API_url_callercss = "inputline1";
	else
		$API_url_callercss = "inputline";
	if (isset($conf['API_url_autoself']))
		$API_url_autoselfcss = "inputline1";
	else
		$API_url_autoselfcss = "inputline";
	if (isset($conf['API_url_autoserv']))
		$API_url_autoservcss = "inputline1";
	else
		$API_url_autoservcss = "inputline";
	if (isset($conf['API_url_callee']))
		$API_url_calleecss = "inputline1";
	else
		$API_url_calleecss = "inputline";
	if (isset($conf['CDR_url']))
		$CDR_urlcss = "inputline1";
	else
		$CDR_urlcss = "inputline";
	if (isset($conf['CDR_xml']))
		$CDR_xmlcss = "inputline1";
	else
		$CDR_xmlcss = "inputline";
		if (isset($conf['default_password']))
		$default_passwordcss = "inputline1";
	else
		$default_passwordcss = "inputline";
	if (isset($conf['sound_prefix']))
		$sound_prefixcss = "inputline1";
	else
		$sound_prefixcss = "inputline";
	if (isset($conf['default_language']))
		$default_languagecss = "inputline1";
	else
		$default_languagecss = "inputline";
	if (isset($conf['default_dialect']))
		$default_dialectcss = "inputline1";
	else
		$default_dialectcss = "inputline";
	if (isset($conf['default_voice']))
		$default_voicecss = "inputline1";
	else
		$default_voicecss = "inputline";
	if (isset($conf['modules_add']))
		$modules_addcss = "inputline1";
	else
		$modules_addcss = "inputline";
	if (isset($conf['CDR_file']))
		$CDR_filecss = "inputline1";
	else
		$CDR_filecss = "inputline";
}
	
if ($fail)
	$label = "设置参数有误，需全部重设！";
else
	$label = "本处是平台最初的设置，一旦后面在具体功能修改了相关信息，则这里的设置会被自动覆盖：";

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class="pcenter" style="font-size:18pt;">系统参数设置 <a style="font-size:10pt;" href="main.php">返回主控</a></p>
<table class="tablegreen" width="800" align="center"><form action="" method="post" enctype="multipart/form-data" id="form">
 <th colspan=2>$label</th>
<tr><td colspan=2>--- * 核心参数 *---</td></tr>
<tr><td><em>SIP服务器 realm：</em><input type="text" class="$realmcss" value="$conf[realm]" name="realm" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*在线话机必须！</span>设置在线话机或其他呼叫端等连接本服务器的主机地址，域名端口或IP端口，格式如：1.2.3.4:6789</span></td></tr>
<tr><td><em>WebSocket服务器 wss：</em><input type="text" class="$wsscss" value="$conf[wss]" name="wss" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*在线话机必须！</span>设置WebSocket 服务器的主机地址，域名端口或IP端口，格式如：wss://1.2.3.4:7443</span></td></tr>
<tr><td><em>数据库DSN odbc-dsn：</em><input type="text" class="$odbcdsncss" value="$conf[odbcdsn]" name="odbcdsn" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*呼叫中心必须！</span>设置FS使用ODBC数据库连接的DSN信息</span></td></tr>
 <tr><td><em>CDR WEB提交 debug：</em><input type="text" class="$CDR_debugcss" value="$conf[CDR_debug]" name="CDR_debug" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*通话记录必须！</span>设置xmlCDR模块中 WEB提交是否开启调试跟踪，开启后会把信息写入系统根目录的日志</span></td></tr>
<tr><td><em>CDR XML保存：</em><input type="text" class="$CDR_xmlcss" value="$conf[CDR_xml]" name="CDR_xml" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*通话记录必须！</span>设置xmlCDR模块中 是否在数据库中保存XML的原始数据</span></td></tr>
<tr><td><em>CDR WEB提交地址：</em><input type="text" class="$CDR_urlcss" value="$conf[CDR_url]" name="CDR_url" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*通话记录必须！</span>设置xmlCDR模块中 WEB提交CDR的url地址</span></td></tr>
<tr><td><em>CDR 录音文件名：</em><input type="text" class="$CDR_filecss" value="$conf[CDR_file]" name="CDR_file" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*通话记录必须！</span>调用的xmlCDR录音文件名称，路径固定为 FS录音目录/年/月/日/，使用字段名，默认为@uuid@.wav，应与域设置中录音设置保持一致！否则无法调取</span></td></tr>
<tr><td><em>API 获取主叫ID：</em><input type="text" class="$API_url_callercss" value="$conf[API_url_caller]" name="API_url_caller" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置curl进行API调用处理主叫信息的url地址，传入参数：domainid 域id callerid 坐席id，返回主叫显示id </span></td></tr>
<tr><td><em>API 处理被叫：</em><input type="text" class="$API_url_calleecss" value="$conf[API_url_callee]" name="API_url_callee" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置curl进行API调用处理被叫号码的url地址，传入参数：domainid 域id callerid 坐席id callee 被叫号码，返回0为拒绝呼叫，返回其他为被叫id </span></td></tr>
<tr><td><em>API 坐席自动外呼：</em><input type="text" class="$API_url_autoselfcss" value="$conf[API_url_autoself]" name="API_url_autoself" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置curl进行API调用获取坐席进行自动外呼的号码的url地址，传入参数：domainid 域id callerid 坐席id，返回0为拒绝呼叫，返回其他为被叫id </span></td></tr>
<tr><td><em>API 域自动外呼：</em><input type="text" class="$API_url_autoservcss" value="$conf[API_url_autoserv]" name="API_url_autoserv" size=45/></td>
<td width=50%><span class="smallred smallsize-font"><span class=bgred>*自动外呼必须！</span>设置curl进行API调用获取域自动外呼服务的状态及任务语音的url地址，传入参数：domainid 域id callerid 呼出号码 taskid 任务id status 呼出状态 </span></td></tr>
<tr><td colspan=2>--- * 一般性设置 *---</td></tr>
<tr><td><em>默认用户密码：</em><input type="text" class="$default_passwordcss" value="$conf[default_password]" name="default_password" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置创建用户的默认注册密码</span></td></tr>
<tr><td><em>默认语言：</em><input type="text" class="$default_languagecss" value="$conf[default_language]" name="default_language" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置默认语言，切记freeswitch.xml需包含相应文件</span></td></tr>
<tr><td><em>语音文件路径：</em><input type="text" class="$sound_prefixcss" value="$conf[sound_prefix]" name="sound_prefix" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置语音文件的路径</span></td></tr>
<tr><td><em>默认方言：</em><input type="text" class="$default_dialectcss" value="$conf[default_dialect]" name="default_dialect" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置语音文件中的方言目录</span></td></tr>
<tr><td><em>默认语音：</em><input type="text" class="$default_voicecss" value="$conf[default_voice]" name="default_voice" size=45/></td>
<td width=50%><span class="smallred smallsize-font">设置语音文件中的语音目录</span></td></tr>
<tr><td><em>附加系统模块：</em><input type="text" class="$modules_addcss" value="$conf[modules_add]" name="modules_add" size=45/></td>
<td width=50%><span class="smallred smallsize-font">为本系统运行所需要特别声明的功能模块（非基础模块），用“|”分隔</span></td></tr>
<tr><td colspan=2 align="center">**设置系统初始信息，实际设置需在各控制台调整！设置值不能含&、引号、尖括号 <input type="submit" value="确认提交" id="submint" onclick="return confirm('是否确认提交？');"/></td></tr>
</table></form>
</body></html>
HTML;
