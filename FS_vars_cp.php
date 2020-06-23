<?php
set_time_limit(600);
session_start();
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
date_default_timezone_set('Asia/Shanghai');
if(!empty($_SESSION['POST_submit_once']) && isset($_POST)){
	$_SESSION['POST_submit_once'] = 0;
	unset($_POST);
}
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['ESL_HOST']) || empty($_SESSION['conf_dir']))
	die("请正常登录使用！不允许直接进行操作");

define("ESL_HOST", $_SESSION['ESL_HOST']);
define("ESL_PORT", $_SESSION['ESL_PORT']);
define("ESL_PASSWORD",$_SESSION['ESL_PASSWORD']);
require_once "detect_switch.php";

function xmlentities($string){
	$value = str_replace(array("&",'"',"'"),'_', $string);
	return $value;
}

$conf_file = $_SESSION['conf_dir'].'/vars.xml';
// $conf_file = 'vars.xml';

$xml_string = file($conf_file);
$settings = $show_settings = $settings0 = $show_settings0 = array();
if (!$xml_string)
	die("无法读取系统配置文件！");

	$out_element = array('default_password','sound_prefix','default_language','default_dialect','default_voice','domain','domain_name','zrtp_enabled','zrtp_secure_media','unroll_loops','bind_server_ip','global_codec_prefs','outbound_codec_prefs','external_rtp_ip','external_sip_ip','outbound_caller_name','outbound_caller_id');
foreach($xml_string as $one){
	if (strpos($one, "<X-PRE-PROCESS ")!==false){
		$str = preg_replace("/<X-PRE-PROCESS.*data=\"(.*)\".*/", "$1", $one);
		$pos = strpos($str,"=");
		$key = substr($str,0,$pos);
		$key = trim(str_replace(array("<!--","-->","<",'>'),'', $key));
		$value = trim(substr($str,$pos+1));
		if (!in_array($key,$out_element))
			$show_settings[$key] = $value;
		$settings[$key] = $value;
	}elseif (strpos($one, "<LMXPRE-PROCESS ")!==false){
		$str = preg_replace("/<LMXPRE-PROCESS.*data=\"(.*)\".*/", "$1", $one);
		$pos = strpos($str,"=");
		$key = substr($str,0,$pos);
		$key = trim(str_replace(array("<!--","-->","<",'>'),'', $key));
		$value = trim(substr($str,$pos+1));
		if (!in_array($key,$out_element))
			$show_settings0[$key] = $value;
		$settings0[$key] = $value;
	}
}

$file =__DIR__.'/.Config';
if (is_file($file))
	$conf = @unserialize(file_get_contents($file));
else
	$conf = false;
	
$content = "<include>\n";
$html = "";

$css = "inputline";
$rm_default_password = '';
$lab = "默认的用户密码，当未对用户设置密码时使用";
if (!empty($_POST['default_password'])){
	$css = "inputline1";
	$default_password = xmlentities($_POST['default_password']);
	if (!empty($_POST['rm_default_password']))
		$rm_default_password = 'checked="checked" ';
}elseif(isset($settings["default_password"])){
	$css = "inputline1";
	$default_password = $settings["default_password"];
	$rm_default_password = 'checked="checked" ';
}elseif(isset($settings0["default_password"])){
	$default_password = $settings0["default_password"];
}else{
	if ($conf && !empty($conf['default_password'])){
		$default_password = $conf['default_password'];
		$rm_default_password = 'checked="checked" ';
	}else
		$default_password = "shoudian";
}
if ($rm_default_password)
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"default_password=$default_password\"/>\n";
else 
	$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"default_password=$default_password\"/>\n";
	
$html .= "<tr><td><em>默认用户密码：</em><input id=\"rm_default_password\" name=\"rm_default_password\" value=\"1\" type=\"checkbox\" $rm_default_password><input id=\"default_password\" name=\"default_password\" value=\"$default_password\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$lab = "语音文件路径";
$rm_sound_prefix = "";
if (!empty($_POST['sound_prefix'])){
	$css = "inputline1";
	$sound_prefix = xmlentities($_POST['sound_prefix']);
	if (!empty($_POST['rm_sound_prefix']))
		$rm_sound_prefix = 'checked="checked" ';
}elseif(isset($settings["sound_prefix"])){
	$css = "inputline1";
	$rm_sound_prefix = 'checked="checked" ';
	$sound_prefix = $settings["sound_prefix"];
}elseif(isset($settings0["sound_prefix"])){
	$sound_prefix = $settings0["sound_prefix"];
}else{
	if ($conf && !empty($conf['sound_prefix'])){
		$sound_prefix = $conf['sound_prefix'];
		$rm_sound_prefix = 'checked="checked" ';
	}else
		$sound_prefix = "$${sounds_dir}/zh/cn/yy";
}
if ($rm_sound_prefix)
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"sound_prefix=$sound_prefix\"/>\n";
else 
	$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"sound_prefix=$sound_prefix\"/>\n";
$html .= "<tr><td><em>语音文件：</em><input id=\"rm_sound_prefix\" name=\"rm_sound_prefix\" value=\"1\" type=\"checkbox\" $rm_sound_prefix><input id=\"sound_prefix\" name=\"sound_prefix\" value=\"$sound_prefix\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$rm_default_language = '';
$lab = "设置默认语言，切记修改freeswitch.xml包含相应文件";
if (!empty($_POST['default_language'])){
	$css = "inputline1";
	$default_language = xmlentities($_POST['default_language']);
	if (!empty($_POST['rm_default_language']))
		$rm_default_language = 'checked="checked" ';
}elseif(isset($settings["default_language"])){
	$css = "inputline1";
	$default_language = $settings["default_language"];
	$rm_default_language = 'checked="checked" ';
}elseif(isset($settings0["default_language"])){
	$default_language = $settings0["default_language"];
}else{
	if ($conf && !empty($conf['default_language'])){
		$default_language = $conf['default_language'];
		$rm_default_language = 'checked="checked" ';
	}else
		$default_language = "zh";
}
if ($rm_default_language)
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"default_language=$default_language\"/>\n";
else
	$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"default_language=$default_language\"/>\n";
$html .= "<tr><td><em>默认语言：</em><input id=\"rm_default_language\" name=\"rm_default_language\" value=\"1\" type=\"checkbox\" $rm_default_language><input id=\"default_language\" name=\"default_language\" value=\"$default_language\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$rm_default_dialect ='';
$lab = "设置语音文件中的方言目录";
if (!empty($_POST['default_dialect'])){
	$css = "inputline1";
	$default_dialect = xmlentities($_POST['default_dialect']);
	if (!empty($_POST['rm_default_dialect']))
		$rm_default_dialect = 'checked="checked" ';
}elseif(isset($settings["default_dialect"])){
	$css = "inputline1";
	$rm_default_dialect = 'checked="checked" ';
	$default_dialect = $settings["default_dialect"];
}elseif(isset($settings0["default_dialect"])){
	$default_dialect = $settings0["default_dialect"];
}else{
	if ($conf && !empty($conf['default_dialect'])){
		$default_dialect = $conf['default_dialect'];
		$rm_default_dialect = 'checked="checked" ';
	}else
		$default_dialect = "cn";
}
if ($rm_default_dialect)
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"default_dialect=$default_dialect\"/>\n";
else 
	$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"default_dialect=$default_dialect\"/>\n";
$html .= "<tr><td><em>默认方言：</em><input id=\"rm_default_dialect\" name=\"rm_default_dialect\" value=\"1\" type=\"checkbox\" $rm_default_dialect><input id=\"default_dialect\" name=\"default_dialect\" value=\"$default_dialect\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$lab = "设置语音文件中的语音目录";
$rm_default_voice = "";
if (!empty($_POST['default_voice'])){
	$css = "inputline1";
	$default_voice = xmlentities($_POST['default_voice']);
	if (!empty($_POST['rm_default_voice']))
		$rm_default_voice = 'checked="checked" ';
}elseif(isset($settings["default_voice"])){
	$css = "inputline1";
	$rm_default_voice = 'checked="checked" ';
	$default_voice = $settings["default_voice"];
}elseif(isset($settings0["default_voice"])){
	$default_voice = $settings0["default_voice"];
}else{
	if ($conf && !empty($conf['default_voice'])){
		$default_voice = $conf['default_voice'];
		$rm_default_voice = 'checked="checked" ';
	}else
		$default_voice = "yy";
}
if ($rm_default_voice)
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"default_voice=$default_voice\"/>\n";
else 
	$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"default_voice=$default_voice\"/>\n";
	$html .= "<tr><td><em>默认语音：</em><input id=\"rm_default_voice\" name=\"rm_default_voice\" value=\"1\" type=\"checkbox\" $rm_default_voice><input id=\"default_voice\" name=\"default_voice\" value=\"$default_voice\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$lab = "默认域是当其他域不可用时的默认定义";
if (!empty($_POST['domain'])){
	$css = "inputline1";
	$domain = xmlentities($_POST['domain']);
}elseif(isset($settings["domain"])){
	$css = "inputline1";
	$domain = $settings["domain"];
}else
	$domain = '$${local_ip_v4}';
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"domain=$domain\"/>\n";
	$html .= "<tr><td><em>默认域：</em><input id=\"domain\" name=\"domain\" value=\"$domain\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$lab = "默认域的域名";
if (!empty($_POST['domain_name'])){
	$css = "inputline1";
	$domain_name = xmlentities($_POST['domain_name']);
}elseif(isset($settings["domain_name"])){
	$css = "inputline1";
	$domain_name = $settings["domain_name"];
}else
	$domain_name = '$${local_ip_v4}';
$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"domain_name=$domain_name\"/>\n";
	$html .= "<tr><td><em>默认域域名：</em><input id=\"domain_name\" name=\"domain_name\" value=\"$domain_name\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";
	
$css = "inputline";
$rm_zrtp = "";
$lab = "ZRTP的设置，每个通道可以单独设置并覆盖它<br/> http://wiki.freeswitch.org/wiki/ZRTP ";
if (!empty($_POST['zrtp_secure_media'])){
	$css = "inputline1";
	$zrtp_secure_media = xmlentities($_POST['zrtp_secure_media']);
	if (!empty($_POST['rm_zrtp']))
		$rm_zrtp = 'checked="checked" ';
}elseif(isset($settings["zrtp_secure_media"])){
	$css = "inputline1";
	$rm_zrtp = 'checked="checked" ';
	$zrtp_secure_media = $settings["zrtp_secure_media"];
}elseif(isset($settings0["zrtp_secure_media"])){
	$zrtp_secure_media = $settings0["zrtp_secure_media"];
}else
	$zrtp_secure_media = 'true';
if ($rm_zrtp)
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"zrtp_secure_media=$zrtp_secure_media\"/>\n";
else 
	$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"zrtp_secure_media=$zrtp_secure_media\"/>\n";
	$html .= "<tr><td><em>ZRTP设置：</em><input id=\"rm_zrtp\" name=\"rm_zrtp\" value=\"1\" type=\"checkbox\" $rm_zrtp><input id=\"zrtp_secure_media\" name=\"zrtp_secure_media\" value=\"$zrtp_secure_media\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";
		
$css = "inputline";
$lab = "绑定服务器IP，仅仅用于DINGALING，可以是 ip , dns名,或 auto，如果要分隔RTP和SIP通信，则需要在出现此变量的地方使用不同的地址，Used by: dingaling.conf.xml";
if (!empty($_POST['bind_server_ip'])){
	$css = "inputline1";
	$bind_server_ip = xmlentities($_POST['bind_server_ip']);
}elseif(isset($settings["bind_server_ip"])){
	$css = "inputline1";
	$bind_server_ip = $settings["bind_server_ip"];
}else
	$bind_server_ip = 'auto';
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"bind_server_ip=$bind_server_ip\"/>\n";
	$html .= "<tr><td><em>绑定服务器IP：</em><input id=\"bind_server_ip\" name=\"bind_server_ip\" value=\"$bind_server_ip\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";
	
$css = "inputline";
$rm_external_ip = "";
$lab = "设置外部sip及rtp服务器地址（external_sip_ip被用于SDP），可以是ip地址、dns按格式：\"host:host.server.com\"、stun服务器按格式\"stun:stun.server.com\"，两个参数均需设置，如果不指定，会使用上面的绑定服务器ip参数，Used by: sofia.conf.xml dingaling.conf.xml";
if (!empty($_POST['external_rtp_ip']) && !empty($_POST['external_sip_ip'])){
	$css = "inputline1";
	$external_rtp_ip = xmlentities($_POST['external_rtp_ip']);
	$external_sip_ip =  xmlentities($_POST['external_sip_ip']);
	if (!empty($_POST['rm_external_ip']))
		$rm_external_ip = 'checked="checked" ';
}elseif(isset($settings["external_rtp_ip"]) && isset($settings["external_sip_ip"])){
	$css = "inputline1";
	$rm_external_ip = 'checked="checked" ';
	$external_rtp_ip = $settings["external_rtp_ip"];
	$external_sip_ip = $settings["external_sip_ip"];
}elseif(isset($settings0["external_rtp_ip"]) && isset($settings0["external_sip_ip"])){
	$external_rtp_ip = $settings0["external_rtp_ip"];
	$external_sip_ip = $settings0["external_sip_ip"];
}else{
	$external_rtp_ip = "stun:stun.freeswitch.org";
	$external_sip_ip = "stun:stun.freeswitch.org";
}
if ($rm_external_ip)
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"external_rtp_ip=$external_rtp_ip\"/>\n<X-PRE-PROCESS cmd=\"set\" data=\"external_sip_ip=$external_sip_ip\"/>\n";
else 
	$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"external_rtp_ip=$external_rtp_ip\"/>\n<LMXPRE-PROCESS cmd=\"set\" data=\"external_sip_ip=$external_sip_ip\"/>\n";
	$html .= "<tr><td><em>外部rtp地址：</em><input id=\"rm_external_ip\" name=\"rm_external_ip\" value=\"1\" type=\"checkbox\" $rm_external_ip><input id=\"external_rtp_ip\" name=\"external_rtp_ip\" value=\"$external_rtp_ip\" size=45 onclick=\"this.select();\" class=\"$css\"/><br/><em>外部sip地址：</em><input id=\"external_sip_ip\" name=\"external_sip_ip\" value=\"$external_sip_ip\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$lab = "设置启用SIP lookback回滚";
if (!empty($_POST['unroll_loops'])){
	$css = "inputline1";
	$unroll_loops = xmlentities($_POST['unroll_loops']);
}elseif(isset($settings["unroll_loops"])){
	$css = "inputline1";
	$unroll_loops = $settings["unroll_loops"];
}else
	$unroll_loops = 'true';
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"unroll_loops=$unroll_loops\"/>\n";
	$html .= "<tr><td><em>SIP lookback回滚：</em><input id=\"unroll_loops\" name=\"unroll_loops\" value=\"$unroll_loops\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";
	
$css = "inputline";
$lab = "设置使用编码，编码必须先编译，并加载，具体说明在本页面上部有，请参考；两个参数均需设置";
if (!empty($_POST['global_codec_prefs']) && !empty($_POST['outbound_codec_prefs'])){
	$css = "inputline1";
	$global_codec_prefs = xmlentities($_POST['global_codec_prefs']);
	$outbound_codec_prefs =  xmlentities($_POST['outbound_codec_prefs']);
}elseif(isset($settings["global_codec_prefs"]) && isset($settings["outbound_codec_prefs"])){
	$css = "inputline1";
	$global_codec_prefs = $settings["global_codec_prefs"];
	$outbound_codec_prefs = $settings["outbound_codec_prefs"];
}else{
	$global_codec_prefs = "OPUS,G722,G729,G723,PCMU,PCMA,VP8";
	$outbound_codec_prefs = "OPUS,G722,G729,G723,PCMU,PCMA,VP8";
}
$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"global_codec_prefs=$global_codec_prefs\"/>\n<X-PRE-PROCESS cmd=\"set\" data=\"outbound_codec_prefs=$outbound_codec_prefs\"/>\n";
$html .= "<tr><td><em>设置通用编码：</em><input id=\"global_codec_prefs\" name=\"global_codec_prefs\" value=\"$global_codec_prefs\" size=45 onclick=\"this.select();\" class=\"$css\"/><br/><em>设置带外编码：</em><input id=\"outbound_codec_prefs\" name=\"outbound_codec_prefs\" value=\"$outbound_codec_prefs\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline";
$lab = "设置默认的呼出用户名及id,Used by: conference.conf.xml 以及 用户目录下的默认用户设置";
if (!empty($_POST['outbound_caller_name']) && !empty($_POST['outbound_caller_id'])){
	$css = "inputline1";
	$outbound_caller_name = xmlentities($_POST['outbound_caller_name']);
	$outbound_caller_id =  xmlentities($_POST['outbound_caller_id']);
}elseif(isset($settings["outbound_caller_name"]) && isset($settings["outbound_caller_id"])){
	$css = "inputline1";
	$outbound_caller_name = $settings["outbound_caller_name"];
	$outbound_caller_id = $settings["outbound_caller_id"];
}else{
	$outbound_caller_name = "FreeSWITCH";
	$outbound_caller_id = "0000000000";
}
$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"outbound_caller_name=$outbound_caller_name\"/>\n<X-PRE-PROCESS cmd=\"set\" data=\"outbound_caller_id=$outbound_caller_id\"/>\n";
$html .= "<tr><td><em>设置默认用户名：</em><input id=\"outbound_caller_name\" name=\"outbound_caller_name\" value=\"$outbound_caller_name\" size=45 onclick=\"this.select();\" class=\"$css\"/><br/><em>设置默认用户ID：</em><input id=\"outbound_caller_id\" name=\"outbound_caller_id\" value=\"$outbound_caller_id\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"400\"><span class=\"smallred smallsize-font\">$lab</span></td></tr>";

$css = "inputline1";
foreach ($show_settings as $key=>$value){
	$temp = "rm_$key";
	$$temp =  'checked="checked" ';
	if (!empty($_POST[$key])){
		$$key = xmlentities($_POST[$key]);
		if (empty($_POST[$temp])) //这里默认是选了的
			$$temp = '';
	}else
		$$key = $value;
	if ($$temp)
		$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"$key={$$key}\"/>\n";
	else 
		$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"$key={$$key}\"/>\n";
	$html .= "<tr><td colspan=2><em>$key ：</em><input id=\"$temp\" name=\"$temp\" value=\"1\" type=\"checkbox\" {$$temp}><br/><input id=\"$key\" name=\"$key\" value=\"{$$key}\" size=100% onclick=\"this.select();\" class=\"$css\"/></td></tr>";
}

$css = "inputline";
foreach ($show_settings0 as $key=>$value){
	$temp = "rm_$key";
	$$temp =  '';
	if (!empty($_POST[$key])){
		$$key = xmlentities($_POST[$key]);
		if (!empty($_POST[$temp])) //这里默认是没选的
			$$temp = 'checked="checked" ';
	}else
		$$key = $value;
	if ($$temp)
		$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"$key={$$key}\"/>\n";
	else
		$content .= "<LMXPRE-PROCESS cmd=\"set\" data=\"$key={$$key}\"/>\n";
	$html .= "<tr><td colspan=2><em>$key ：</em><input id=\"$temp\" name=\"$temp\" value=\"1\" type=\"checkbox\" {$$temp}><br/><input id=\"$key\" name=\"$key\" value=\"{$$key}\" size=100% onclick=\"this.select();\" class=\"$css\"/></td></tr>";
}

$html .= "<tr><td style='background:#decedd;' align='center' colspan=2><em>添加设置</em> 名称：<input id=\"add_key\" name=\"add_key\" value=\"\" size=15 class=\"inputline\" />  值：<input id=\"add_value\" name=\"add_value\" value=\"\" size=25 class=\"inputline\" /></td></tr>";

if(!empty($_POST['add_key'])){
	$key = xmlentities($_POST['add_key']);
	$value =  xmlentities($_POST['add_value']);
	$content .= "<X-PRE-PROCESS cmd=\"set\" data=\"$key=$value\"/>\n";
}
$content .= "</include>";
$showinfo = "";
if (!empty($_POST)){
	$post_botton_enabled = "disabled=\"disabled\"";
	$showinfo .= "<span class='bgblue'>已经提交数据，当前显示提交的数据，不可连续提交！</span>";
}else
	$post_botton_enabled = "";
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>系统预处理参数控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><form method="post"><th colspan=2>* 本配置文件非标准xml，每个参数按行读取 <span onclick="$('#show_moreinfo_').toggle();" style='cursor:pointer;'>更多参数说明>>></span><br/>
* 橙色表示其参数当前存在；勾选表示已经启用；黑色表示被禁用<br/>
* 不允许&、引号；提交数据后会重启服务器，请务必谨慎<br/>
<span class='bgred'>*！警告：更新设置后需在 服务器设置 中 获取信息，而后需要重新部署已有域！否则系统可能出错！</span><br/>$showinfo
<textarea id='show_moreinfo_' class='smallblack smallsize-font' style='width:780px;height:200px;display:none;'/>
1）下面这几个变量是默认存在的：
hostname、local_ip_v4、local_mask_v4、local_ip_v6、switch_serial、base_dir、recordings_dir、sound_prefix、sounds_dir、conf_dir、log_dir、run_dir、db_dir、mod_dir、htdocs_dir、script_dir、grammar_dir、certs_dir、storage_dir、cache_dir、core_uuid、zrtp_enabled、nat_public_addr、temp_dir、nat_private_addr、nat_type，它们可以在这里设置并覆盖系统默认值

2）设置通讯时编码，必须先编译并加载：  codecname[@8000h|16000h|32000h[@XXi]]
       XX is the frame size must be multples allowed for the codec FreeSWITCH can support 10-120ms on some codecs.
       We do not support exceeding the MTU of the RTP packet.

       iLBC@30i         - iLBC using mode=30 which will win in all cases.
       DVI4@8000h@20i   - IMA ADPCM 8kHz using 20ms ptime. (multiples of 10)
       DVI4@16000h@40i  - IMA ADPCM 16kHz using 40ms ptime. (multiples of 10)
       speex@8000h@20i  - Speex 8kHz using 20ms ptime.
       speex@16000h@20i - Speex 16kHz using 20ms ptime.
       speex@32000h@20i - Speex 32kHz using 20ms ptime.
       BV16             - BroadVoice 16kb/s narrowband, 8kHz
       BV32             - BroadVoice 32kb/s wideband, 16kHz
       G7221@16000h     - G722.1 16kHz (aka Siren 7)
       G7221@32000h     - G722.1C 32kHz (aka Siren 14)
       CELT@32000h      - CELT 32kHz, only 10ms supported
       CELT@48000h      - CELT 48kHz, only 10ms supported
       GSM@40i          - GSM 8kHz using 40ms ptime. (GSM is done in multiples of 20, Default is 20ms)
       G722             - G722 16kHz using default 20ms ptime. (multiples of 10)
       PCMU             - G711 8kHz ulaw using default 20ms ptime. (multiples of 10)
       PCMA             - G711 8kHz alaw using default 20ms ptime. (multiples of 10)
       G726-16          - G726 16kbit adpcm using default 20ms ptime. (multiples of 10)
       G726-24          - G726 24kbit adpcm using default 20ms ptime. (multiples of 10)
       G726-32          - G726 32kbit adpcm using default 20ms ptime. (multiples of 10)
       G726-40          - G726 40kbit adpcm using default 20ms ptime. (multiples of 10)
       AAL2-G726-16     - Same as G726-16 but using AAL2 packing. (multiples of 10)
       AAL2-G726-24     - Same as G726-24 but using AAL2 packing. (multiples of 10)
       AAL2-G726-32     - Same as G726-32 but using AAL2 packing. (multiples of 10)
       AAL2-G726-40     - Same as G726-40 but using AAL2 packing. (multiples of 10)
       LPC              - LPC10 using 90ms ptime (only supports 90ms at this time in FreeSWITCH)
       L16              - L16 isn't recommended for VoIP but you can do it. L16 can exceed the MTU rather quickly.

       These are the passthru audio codecs:
       G729             - G729 in passthru mode. (mod_g729)
       G723             - G723.1 in passthru mode. (mod_g723_1)
       AMR              - AMR in passthru mode. (mod_amr)

       These are the passthru video codecs: (mod_h26x)
       H261             - H.261 Video
       H263             - H.263 Video
       H263-1998        - H.263-1998 Video
       H263-2000        - H.263-2000 Video
       H264             - H.264 Video

       RTP Dynamic Payload Numbers currently used in FreeSWITCH and their purpose.
       96  - AMR
       97  - iLBC (30)
       98  - iLBC (20)
       99  - Speex 8kHz, 16kHz, 32kHz
       100 -
       101 - telephone-event
       102 -
       103 -
       104 -
       105 -
       106 - BV16
       107 - G722.1 (16kHz)
       108 -
       109 -
       110 -
       111 -
       112 -
       113 -
       114 - CELT 32kHz, 48kHz
       115 - G722.1C (32kHz)
       116 -
       117 - SILK 8kHz
       118 - SILK 12kHz
       119 - SILK 16kHz
       120 - SILK 24kHz
       121 - AAL2-G726-40 && G726-40
       122 - AAL2-G726-32 && G726-32
       123 - AAL2-G726-24 && G726-24
       124 - AAL2-G726-16 && G726-16
       125 -
       126 -
       127 - BV32

3） 当使用SRTP时，关键是您不提供或接受可变比特率编解码，这样做将泄露信息并可能损害您的SRTP流
 Supported SRTP Crypto Suites:
      AEAD_AES_256_GCM_8
      :::This algorithm is identical to AEAD_AES_256_GCM, except that the tag length, t, is 8, and an authentication tag with a length of 8 octets (64 bits) is used. An AEAD_AES_256_GCM_8 ciphertext is exactly 8 octets longer than its corresponding plaintext.
      AEAD_AES_128_GCM_8
      :::This algorithm is identical to AEAD_AES_128_GCM, except that the tag length, t, is 8, and an authentication tag with a length of 8 octets (64 bits) is used. An AEAD_AES_128_GCM_8 ciphertext is exactly 8 octets longer than its corresponding plaintext.
      AES_CM_256_HMAC_SHA1_80 | AES_CM_192_HMAC_SHA1_80 | AES_CM_128_HMAC_SHA1_80
      :::AES_CM_128_HMAC_SHA1_80 is the SRTP default AES Counter Mode cipher and HMAC-SHA1 message authentication with an 80-bit authentication tag. The master-key length is 128 bits and has a default lifetime of a maximum of 2^48 SRTP packets or 2^31 SRTCP packets, whichever comes first.
      AES_CM_256_HMAC_SHA1_32 | AES_CM_192_HMAC_SHA1_32 | AES_CM_128_HMAC_SHA1_32 
      :::This crypto-suite is identical to AES_CM_128_HMAC_SHA1_80 except that the authentication tag is 32 bits. The length of the base64-decoded key and salt value for this crypto-suite MUST be 30 octets i.e., 240 bits; otherwise, the crypto attribute is considered invalid.
      AES_CM_128_NULL_AUTH
      :::The SRTP default cipher (AES-128 Counter Mode), but to use no authentication method.  This policy is NOT RECOMMENDED unless it is unavoidable.

SRTP variables that modify behaviors based on direction/leg:
      rtp_secure_media
      :::possible values:
          mandatory - Accept/Offer SAVP negotiation ONLY
          optional  - Accept/Offer SAVP/AVP with SAVP preferred
          forbidden - More useful for inbound to deny SAVP negotiation
          false     - implies forbidden
          true      - implies mandatory
          default if not set is accept SAVP inbound if offered.
      rtp_secure_media_inbound | rtp_secure_media_outbound
      :::This is the same as rtp_secure_media, but would apply to either inbound or outbound offers specifically.

How to specify crypto suites:
      By default without specifying any crypto suites FreeSWITCH will offer crypto suites from strongest to weakest accepting the strongest each endpoint has in common.  If you wish to force specific crypto suites you can do so by appending the suites in a comma separated list in the order that you wish to offer them in.
      Examples:
          rtp_secure_media=mandatory:AES_CM_256_HMAC_SHA1_80,AES_CM_256_HMAC_SHA1_32
          rtp_secure_media=true:AES_CM_256_HMAC_SHA1_80,AES_CM_256_HMAC_SHA1_32
          rtp_secure_media=optional:AES_CM_256_HMAC_SHA1_80
          rtp_secure_media=true:AES_CM_256_HMAC_SHA1_80
     Additionally you can narrow this down on either inbound or outbound by specifying as so:
          rtp_secure_media_inbound=true:AEAD_AES_256_GCM_8
          rtp_secure_media_inbound=mandatory:AEAD_AES_256_GCM_8
          rtp_secure_media_outbound=true:AEAD_AES_128_GCM_8
          rtp_secure_media_outbound=optional:AEAD_AES_128_GCM_8

      rtp_secure_media_suites
      Optionaly you can use rtp_secure_media_suites to dictate the suite list and only use rtp_secure_media=[optional|mandatory|false|true] without having to dictate the suite list with the rtp_secure_media* variables.

4）xmpp_client_profile can be any string.
    xmpp_server_profile is appended to "dingaling_" to form the database name containing the "subscriptions" table.
    used by: dingaling.conf.xml enum.conf.xml

5） Digits Dialed filter: 
       The digits stream may contain valid credit card numbers or social security numbers, These digit
       filters will allow you to make a valant effort to stamp out sensitive information for
       PCI/HIPPA compliance. (see xml_cdr dialed_digits)
       df_us_ssn   = US Social Security Number pattern
       df_us_luhn  = Visa, MasterCard, American Express, Diners Club, Discover and JCB

6）SIP 和 TLS 的设置参考 http://wiki.freeswitch.org/wiki/Tls
     可用： sslv2,sslv3,sslv23,tlsv1,tlsv1.1,tlsv1.2
     默认： tlsv1,tlsv1.1,tlsv1.2

7）TLS cipher suite 默认： ALL:!ADH:!LOW:!EXP:!MD5:@STRENGTH
   实际值是每个平台不同的，要查看哪些可用：openssl ciphers -v 'ALL:!ADH:!LOW:!EXP:!MD5:@STRENGTH'
     
</textarea></th>$html
<th><span class="smallred smallsize-font">*提交后即刻重启服务器，请谨慎操作</span></th><th> <input type="submit" value="确认提交" $post_botton_enabled onclick="return confirm('这里的参数是全局设置，务必谨慎！提交后将重启服务器！是否确认提交？');"/>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf_file,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> restart_switch();
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
echo "</th></form></table></body></html>";