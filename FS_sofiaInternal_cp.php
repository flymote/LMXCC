<?php
set_time_limit(600);
session_start();
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
date_default_timezone_set('Asia/Shanghai');
if(!empty($_SESSION['POST_submit_once']) && !empty($_POST)){
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
$showinfo ="";
if (!empty($_POST)){
	$post_botton_enabled = "disabled=\"disabled\"";
	$showinfo .= "<span class='bgblue'>已经提交数据，不可连续提交！</span>";
}else
	$post_botton_enabled = "";
	
function xmlentities($string){
	$value = str_replace(array("&","<",">",'"',"'"),'_', $string);
	return $value;
}
$conf = $_SESSION['conf_dir'].'/sip_profiles/internal.xml';
// $conf = 'freeswitch/sip_profiles/internal.test';
$xml_string = file_get_contents($conf);
$xml = simplexml_load_string($xml_string);
if (!$xml)
	die("无法读取系统配置文件！");

$file =__DIR__.'/.Config';
if (is_file($file))
	$ini_conf = @unserialize(file_get_contents($file));
else
	$ini_conf = false;

$settings = array();
$name = "";
foreach($xml as $key=>$one ){
	$settings[$key] = array();
	foreach ($one as $k1=>$v1){ //遍历参数
		$tmp = array();
		foreach ($v1->attributes() as $k2 =>$v2){
			$tmp["$k2"]="$v2";
		}
		$settings[$key][$k1][] = $tmp;
	}
};
$show_info = $html = '';
$content = "<profile name=\"internal\">\n";
$i=0;
foreach ($settings as $key=>$data){
	$html .=  "<form method=\"post\">";
	$html .="<tr><td colspan=2 style='background:#decedd;' class='blod14'><em>$key ---- ---- ---- ----</em> </td></tr>";
	$content .= "<$key>\n";
	$posted = (@$_POST['lists']==$key); //是否提交数据，按分组提交
		if ($posted && !empty($_POST['addname'])){
			$addname= xmlentities (trim($_POST['addname']));
			if (!empty($_POST['addkey']))
				$addkey= xmlentities (trim($_POST['addkey']));
			else $addkey = "";
			if (!empty($_POST['addvalue']))
				$addvalue= xmlentities (trim($_POST['addvalue']));
			else $addvalue ="";
			if (!empty($_POST['addmore']))
				$addmore= xmlentities (trim($_POST['addmore']));
			else $addmore="";
			switch ($addkey){
				case "X-PRE-PROCESS":
					$content .=  "<$addkey cmd=\"$addname\" data=\"$addvalue\"/>\n";
					$show_info .= "添加 $addkey 控制命令：$addname $addvalue ！<br/>";
					break;
				case "alias":
					$content .=  "<$addkey name=\"$addname\"/>\n";
					$show_info .= "添加 $addkey 别名：$addname  ！ <br/>";
					break;
				case "domain":
					if ($addvalue <>'true') $addvalue = 'false';
					if ($addmore <>'true') $addmore = 'false';
					$content .=  "<$addkey name=\"$addname\" alias=\"$addvalue\" parse=\"$addmore\"/>\n";
					$show_info .= "添加 $addkey 域：$addname alas: $addvalue parse: $addmore !<br/>";
					break;
				case "param":
					$content .=  "<$addkey name=\"$addname\" value=\"$addvalue\"/>\n";
					$show_info .= "添加 $addkey 设置：$addname $addvalue ！ <br/>";
					break;
				default:
					$content .=  "<$addkey name=\"$addname\" value=\"$addvalue\"/>\n";
					$show_info .= "添加 $addkey name: $addname value: $addvalue ！ <br/>";
			}
		}
	
		foreach ($data as $k1=>$items){//分组下的一个元素组下（每个分组可以多个元素组）,如 param元素
			foreach ($items as $getone){ //一个参数，其中的参数项目也是数组
				$modi = 0;
				if(isset($getone['name']))
					$one = $getone['name'];
				elseif(isset($getone['cmd']))
					$one = $getone['cmd'];
				else $one =$getone[0];
				if ($posted && !empty($_POST['del']) && in_array("{$k1}-{$one}",$_POST['del'])) {//post 删除的 跳过
					$show_info .= "$one 被删除！<br/>";
					continue;
				}elseif ($posted && !empty($_POST['mod']) && in_array("{$k1}-{$one}",$_POST['mod'])) {//修改value
					$modi = 1;
					$modvalue=xmlentities (trim($_POST["{$k1}{$one}m"]));
					if (empty($modvalue))
						$show_info .= "$one 被修改为空值 ！<br/>";
					else 
						$show_info .= "$one 被修改为 $modvalue ！<br/>";
				};
				$str ="";
				foreach ($getone as $tmpk=>$tmpv){
					if ($modi && ($tmpk =="value" || $tmpk =="data" || ($k1=='alias' || $k1=='domain') && $tmpk =="name"))
						$tmpv = $modvalue;
					if ($one =='odbc-dsn' && (!empty($ini_conf['odbcdsn']) && $tmpk =="value" && $tmpv != $ini_conf['odbcdsn'] ))
						$str.="$tmpk=\"$tmpv\" <br/><span class=red>与设置 $ini_conf[odbcdsn] 冲突！请修正！</span>";
					else $str.="$tmpk=\"$tmpv\" ";
				}
				if (fmod($i,2)==0)
					$bg = "class='bg1'";
				else 
					$bg = "class='bg2'";
				$i++;
				$html .="<tr $bg><td class='blod14'>$i.<em class='bgblue'>$k1 </em>&nbsp; $str </td><td width=320>【改为<input type='text' name='{$k1}{$one}m' value='' size=8 class='inputline'/><label><input type='checkbox' name='mod[]' value='{$k1}-{$one}'/>选择修改</label>】 <label>【<input type='checkbox' name='del[]' value='{$k1}-{$one}'/>选择删除】</label> </td></tr>";
				$content .= "<$k1 $str/>\n";
			}
		}
		if ($key =="gateways")
			$input ="<input type='hidden' value='X-PRE-PROCESS' name='addkey' >新加命令：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		elseif ($key =="aliases")
			$input ="<input type='hidden' value='alias' name='addkey' >新别名：<input type='text' value='' name='addname' class='inputline' size=8 >";
		elseif ($key =="domains")
			$input ="<input type='hidden' value='domain' name='addkey' >新加域：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp;是否别名：<input type='text' name='addvalue'  value='' class='inputline' size=4/>  &nbsp;是否解析：<input type='text' name='addmore'  value='' class='inputline' size=4/>";
		elseif ($key =="settings")
			$input ="<input type='hidden' value='param' name='addkey' >新设置：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		else
			$input ="项目：<input type='text' value='param' name='addkey' > 设置：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		$html .="<tr><td  style='background:#decfff;' align='center' class='blod14' colspan=2><input type='hidden' value='$key' name='lists' >$key $input &nbsp; <input type='submit'  value='提交操作' $post_botton_enabled onclick=\"return confirm('是否确认提交，提交后会重启服务器！请务必谨慎');\"></td></tr></form>";
			$content .= "</$key>\n";
		}
if (!empty($_POST['list_add'])){
	$listname = xmlentities ($_POST['list_add']);
	$show_info .= "添加新分组 $listname ！刷新页面后可见";
	$content .= "<$listname>\n</$listname>\n";
}
$content .= "</profile>";

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>sofia Internal主配置控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><th colspan=2>* 本配置文件是sofia Internal的主干配置，用于内呼控制<br/>修改信息前务必谨慎！！！不允许输入 &、引号、尖括号</span> <span onclick="$('#show_moreinfo_').toggle();" style='cursor:pointer;'>更多参数说明>>></span><br/><span class=red>$show_info $showinfo</span><textarea id='show_moreinfo_' style='width:780px;height:800px;display:none;'/>
1）dtmf信号之间设定延迟时间 (每个通道都可设置 rtp_digit_delay)
    <param name="rtp-digit-delay" value="40"/>
2）如果 FreeSWITCH 是没有媒体（No Media/Bypass Media）的，那么如果设置了该参数，当你在话机上按下 hold 键时， FreeSWITCH 将会回到有媒体的状态
    <param name="media-option" value="resume-media-on-hold"/>
     Attended Transfer 译即出席转移，也称协商转，它需要媒体才能完成工作。但如果在执行att-xfer之前没有媒体，该参数能让att-xfer 执行时有通过 reINVITE请求要回媒体，等到转移结束后再回到 Bypass Media 状态
    <param name="media-option" value="bypass-media-after-att-xfer"/>
3）设置 SIP 消息中显示的 User-Agent 字段，可以设置 "_undef_" 来清除 User-Agent 
    <param name="user-agent-string" value="FreeSWITCH Rocks!"/>
4）如果希望 FreeSWITCH在加载这个配置文件失败时停止运行，可以使用
    <param name="shutdown-on-fail" value="true"/>
5）Use presence_map.conf.xml to convert extension regex to presence protos for routing
    <param name="presence-proto-lookup" value="true"/>
6）让DTMF总是提供 2833 ，并接收 2833 和 INFO 
    <param name="liberal-dtmf" value="true"/>
     设置 SDP 中 RFC2833 的Payload值。RFC2833 是传递 DTMF 的标准
     <param name="rfc2833-pt" value="101"/>
     设置 DTMF 的时长。
     <param name="dtmf-duration" value="2000"/>
     DTMF 收号的类型。有三种方式，info、inband、rfc2833。
     info 方式是采用 SIP 的 INFO 消息传送 DTMF 按键信息的，由于 SIP 和 RTP 是分开走的，所以，可能会造成不同步。
     inband 是在 RTP 包中象普通语音数据那样进行带内传送，由于需要对所有包进行鉴别和提取，需要占用更多的资源。
     rfc2833 也是在带内传送，但它的 RTP 包有特殊的标记，因而比 inband 方式节省资源。它是在 RFC2833 中定义的。
    <param name="dtmf-type" value="info"/>
    是否透传 RFC2833 DTMF 包
    <param name="pass-rfc2833" value="true"/>
7） 有时，在极罕见的边缘情况下，SofiaSIP堆栈可能停止响应。这些选项允许您启用和控制SofiaSIP堆栈上的监视狗，以便如果它停止响应指定的毫秒数，它将导致FreeSWITCH立即崩溃。如果您在HA环境中运行，并且需要确保从这样的条件自动恢复，这是非常有用的。注意，如果您的服务器经常空闲，则由于没有接收到任何SIP消息，看门狗可能会触发。因此，如果您期望您的系统处于空闲状态，则将禁用监视狗。它可以通过FreeSWITCHCLI进行开关，无论是基于单个配置文件，还是对所有配置文件进行全局切换。因此，如果您在带有主程序和从服务器的HA环境中运行，则应该使用CLI来确保只在主服务器上启用看门狗。 如果发生这种崩溃，FreeSWITCH将在允许的情况下转储核心。堆栈跟踪将包括函数WatchDog_STARTH_ABORT()。
   <param name="watchdog-enabled" value="no"/>
   <param name="watchdog-step-timeout" value="30000"/>
   <param name="watchdog-event-timeout" value="30000"/>
8）设置是否启用调试。取值有 0 和 1，如果是 1 则会输出更多的调试信息 <param name="debug" value="0"/> 
9）是否开启 SIP 消息跟踪 <param name="sip-trace" value="no"/>
10）是否将认证错误写入日志 <param name="log-auth-failures" value="false"/>
11）context 是 dialplan 中的环境。在此指定来话要落到 dialplan 的哪个 context 环境中。需要指出，如果用户注册到该 profile 上（或是经过认证的用户，即本地用户），则用户目录（directory）中设置的 contex 优先级要比这里高
      <param name="context" value="public"/> 
12）<param name="dialplan" value="XML"/>设置对应 dialplan 的类型
13）支持的来话语音编码，用于语音编码协商。global_codec_prefs 是在 vars.xml中定义的。
     <param name="inbound-codec-prefs" value="\$\${global_codec_prefs}"/>
     支持的去话语音编码
     <param name="outbound-codec-prefs" value="\$\${global_codec_prefs}"/>
14）RTP 定时器名称，其他的定时器可以在 FreeSWITCH 中用“show timers”命令得到
     <param name="rtp-timer-name" value="soft"/>
15）UA 进行 hold 状态时默认播放的音乐 <param name="hold-music" value="\$\${hold_music}"/>
16）用于判断哪些 IP 地址涉及到 NAT<param name="apply-nat-acl" value="nat.auto"/>
17）是否启用扩展 INFO 解析支持，扩展 INFO 支持可用于向 FreeSWITCH发送事件、 API 命令等 
     <param name="extended-info-parsing" value="true"/>
18）NAT穿越，检测 SIP 消息中的 IP 地址与实际的 IP 地址是否相符
     <param name="aggressive-nat-detection" value="true"/>
19）是否启用时钟。默认是启用的。启用时钟后，在指定的时间内（如 20ms）如果收不到 RTP 数据，则返回静音（CNG）数据。如果不启用该功能，则会一直等待直到收到数据
     <param name="enable-timer" value="false"/>
20）SIP 会话超时值，在 SIP 消息中设置 Min-SE
     <param name="minimum-session-expires" value="120"/>
21）对来话采用哪个 ACL
      <param name="apply-inbound-acl" value="domains"/>
      对注册请求采用哪个 ACL
      <param name="apply-register-acl" value="domains"/>
      默认情况下， FreeSWITCH 会自动检测本地网络，并创建一条 localnet.auto ACL 规则。在 NAT 穿越时有用。也可以手工指定其它的 ACL
      <param name="local-network-acl" value="localnet.auto"/>
22）(default true) set to false if you do not wish to have called party info in 1XX responses 
    <param name="cid-in-1xx" value="false"/>
23）设置监听的 SIP 端口号 <param name="sip-port" value="5060"/>
      RTP 使用的地址 <param name="rtp-ip" value="\$\${local_ip_v4}"/>
      SIP 监听的 IP 地址 <param name="sip-ip" value="\$\${local_ip_v4}"/>
24）录音文件的默认存放路径
    <param name="record-path" value="\$\${recordings_dir}"/>
      录音文件名模板
    <param name="record-template" value="\${caller_id_number}.\${target_domain}.\${strftime(%Y-%m-%d-%H-%M-%S)}.wav"/>
25）设置是否使用 PRACK 对 SIP 183 消息进行证实，不建议启用
    <param name="enable-100rel" value="true"/>
26）if you don't wish to try a next SRV destination on 503 response   RFC3263 Section 4.3
    <param name="disable-srv503" value="true"/>
27） 是否压缩 SIP 头。压缩 SIP 头域能使用 SIP 包变小一些
    <param name="enable-compact-headers" value="true"/>
28） 是否在 Ping 失败后取消分机注册。为了 NAT 穿越或支持 Keep Alive， FreeSWITCH 向通过 NAT 方式注册到它的分机（nat-options-ping）或所有注册到它的分机（all-reg-options-ping）周期性地发一些 OPTIONS 包，相当于ping功能
    <param name="unregister-on-options-fail" value="true"/>
    <param name="nat-options-ping" value="true"/>
     <!-- <param name="all-reg-options-ping" value="true"/> -->
29）rtp ip 和 sip ip 不要用主机名，这里需要用ip地址
30）如何发送请求消息。true 是每次都发送，而 first-only 只是首次注册时发送
    <param name="send-message-query-on-register" value="true"/>
31）'true' means every time 'first-only' means on the first register
    <param name="send-presence-on-register" value="first-only"/>
32）设置来电显示的类型，rpid 将会在 SIP 消息中设置 Remote-Party-ID，而 pid 则会设置 P-*-Identity，如果不需要这些，可以设置成 none。
    Remote-Party-ID header
    <param name="caller-id-type" value="rpid"/>
    P-*-Identity family of headers
    <param name="caller-id-type" value="pid"/>
    neither one
    <param name="caller-id-type" value="none"/>
33）send a presence probe on each register to query devices to send presence instead of sending presence with less info
    <param name="presence-probe-on-register" value="true"/>
34）是否支持列席,如果不用的话可以关掉以节省资源。
      <param name="manage-presence" value="true"/>
      是否支持 SLA - Shared Line Apperance
     <param name="manage-shared-appearance" value="true"/>
      这两个参数用以在多个 profile 间共享列席信息。
    <param name="dbname" value="share_presence"/>
    <param name="presence-hosts" value="\$\${domain}"/>
35）设置 G726 的  AAL2 bitpacking
    <param name="bitpacking" value="aal2"/>
36）最大的开放对话（SIP Dialog）数
    <param name="max-proceeding" value="1000"/>
37）所有通话的会话超时时间
    <param name="session-timeout" value="1800"/>
38）是否支持多点注册，取值可以是contact或true。开启多点注册后多个 UA 可以用同一个分机注册上来，有人呼叫该分机时所有 UA 都会振铃
    <param name="multiple-registrations" value="contact"/>
39）SDP 中的语音编码协商，如果设成 greedy，则自己提供的语音编码列表会有优先权
    <param name="inbound-codec-negotiation" value="generous"/>
40）该参数设置的值会附加在 Contact 地址上
    <param name="bind-params" value="transport=udp"/>
41）是否支持 TLS，默认否 <param name="tls" value="true"/>
      设置 TLS 的其它绑定参数 <param name="tls-bind-params" value="transport=tls"/>
      TLS 的监听端口号 <param name="tls-sip-port" value="5061"/>
      存放 TLS 证书的目录 <param name="tls-cert-dir" value="\$\${internal_ssl_dir}"/>
     使用的 TLS 版本，有sslv23（默认）或tlsv1两种 <param name="tls-version" value="sslv23"/>
42）给 Sip Profile 设置别名 <param name="alias" value="sip:10.0.1.251:5555"/>
43）该选项默认为 true。即在桥接电话是是否自动 flush 媒体数据（如果套接字上已有数据时，它会忽略定时器睡眠，能有效减少延迟）
    <param name="rtp-autoflush-during-bridge" value="false"/>
44）是否重写或透传 RTP 时间戳。如果透传， FreeSWITCH 有时会产生不连续的时间戳，有的设备对此可能比较敏感，该选项可以让 FreeSWITCH 产生自己的时间戳。
    <param name="rtp-rewrite-timestamps" value="true"/>
45）使用 ODBC 数据库代替默认的 SQLite
    <param name="odbc-dsn" value="freeswitch:root:limaoxiang"/>
     Or, if you have PGSQL support, you can use that
    <param name="odbc-dsn" value="pgsql://hostaddr=127.0.0.1 dbname=freeswitch user=freeswitch password='' options='-c client_min_messages=NOTICE' application_name='freeswitch'" />
46）将所有来电设置为媒体绕过模式，即媒体流（RTP）不经过FreeSWITCH
    <param name="inbound-bypass-media" value="true"/>
47）将所有来电设置为媒体透传。媒体经过 FreeSWITCH 但 FreeSWITCH不处理，直接转发
    <param name="inbound-proxy-media" value="true"/>
48）是否开启晚协商。默认情况下 FreeSWITCH 对来话会先协商媒体编码，然后再进入 Dialplan。开启晚协商有助于在协商媒体编码之前，先前电话送到 Dialplan，因而在 Dialplan 中可以进行个性化的媒体协商。
    <param name="inbound-late-negotiation" value="true"/>
49）Allow ZRTP clients to negotiate end-to-end security associations (also enables late negotiation) 
    <param name="inbound-zrtp-passthru" value="true"/>
50）该选项允许任何电话注册，而不检查用户和密码及其它设置
    <param name="accept-blind-reg" value="true"/>
     与上一条类似，该选项允许任何电话通过认证 
    <param name="accept-blind-auth" value="true"/>
51）抑制 CNG，即不使用静音包
    <param name="suppress-cng" value="true"/>
52）设置 SIP 认证中 nonce 的生存时间（秒）
    <param name="nonce-ttl" value="60"/>
53）禁止转码，如果该项为 true 则在 bridge 其他电话时，只提供与 a-leg 兼容或相同的语音编码列表进行协商，以避免引起转码
    <param name="disable-transcoding" value="true"/>
54）允许在 Dialplan 中进行人工转向
    <param name="manual-redirect" value="true"/>
55）禁止转移
    <param name="disable-transfer" value="true"/>
56）禁止注册
    <param name="disable-register" value="true"/>
57）有一些电话对 Chanllenge ACK 的回复在哈希值里会有 INVITE 方法，该选项容忍这种行为。
    <param name="NDLB-broken-auth-hash" value="true"/>
58）为支持某些NAT穿越，在Contact 头域中增加;received="<ip>:<port>"字符串。
    <param name="NDLB-received-in-nat-reg-contact" value="true"/>
59）是否对来电进行鉴权
    <param name="auth-calls" value="\$\${internal_auth_calls}"/>
60）强制注册用户与 SIP 认证用户必须相同
    <param name="inbound-reg-force-matching-username" value="true"/>
61）对所有的 SIP 消息都进行鉴权，而不是仅仅是针对 INVITE 和 REGISTER消息
    <param name="auth-all-packets" value="false"/>
62）设置 NAT 环境中公网的 RTP IP。该设置会影响 SDP 中的 IP 地址。有以下几种可能：
       一个IP 地址，如 12.34.56.78
       一个 stun 服务器，它会使用 stun 协议获得公网 IP， 如 stun:stun.server.com
       一个 DNS 名称，如 host:host.server.com
       auto ， 它会自动检测 IP 地址
       auto-nat，如果路由器支持 NAT-PMP 或 UPNP，则可以使用这些协议获取公网 IP。
    <param name="ext-rtp-ip" value="auto-nat"/>
    设置SIP IP，同上
    <param name="ext-sip-ip" value="auto-nat"/>
63）设置 RTP 超时值（秒）。指定的时间内 RTP 没有数据收到，则挂机
    <param name="rtp-timeout-sec" value="300"/>
       RTP 处于保持状态的最大时长（秒）
    <param name="rtp-hold-timeout-sec" value="1800"/>
64）语音活动状态检测，有三种可能，可设为入、出，或双向，通常来说“出”（out）是一个比较好的选择
    <param name="vad" value="in"/>
    <param name="vad" value="out"/>
    <param name="vad" value="both"/>
65）下面选项默认是起作用的，它们会在注册及订阅时在数据库中写入同样的域信息。如果你在使用一个 FreeSWITCH 支持多个域时，不要选这些选项
    对所有用户都强制使用某一域（Domain）
    <param name="force-register-domain" value="\$\${domain}"/>
    对所有订阅都强制使用某一域（Domain）
    <param name="force-subscription-domain" value="\$\${domain}"/>
    对所有经过认证的用户都使用该域（Domain）存入数据库
    <param name="force-register-db-domain" value="\$\${domain}"/>
66）设置 WebSocket 的监听地址和端口号（用于 SIP over WebSocket，一般是WebRTC 呼叫）
    <param name="ws-binding"  value=":5066"/>
67）设置安全 WebSocket 监听地址和端口号。该选择需要相关的安全证书（wss.pem存放在  \$\${certs_dir}
    <param name="wss-binding" value=":7443"/>
68）launch a new thread to process each new inbound register when using heavier backends
    <param name="inbound-reg-in-new-thread" value="true"/>
69）enable rtcp on every channel also can be done per leg basis with rtcp_audio_interval_msec variable set to passthru to pass it across a call
    <param name="rtcp-audio-interval-msec" value="5000"/>
    <param name="rtcp-video-interval-msec" value="5000"/>
70）强制一个比较短的订阅超时时间
    <param name="force-subscription-expires" value="60"/>
71） add a random deviation to the expires value of the 202 Accepted
    <param name="sip-subscription-max-deviation" value="120"/>
72）大多数情况下，为了更好的穿越 NAT，FreeSWITCH 会自动调整 RTP 包的 IP 地址，但在某些情况下（尤其是在 mod_dingaling 中会有多个候选 IP），FreeSWITCH 可能会改变本来正确的 IP 地址。该参数禁用此功能
    <param name="disable-rtp-auto-adjust" value="true"/>
73）是否支持 3PCC 呼叫。该选项有两个值， true或proxy。 true则直接接受 3PCC来电；如果选Proxy，则会一直等待电话应答后才回送接受。
    <param name="enable-3pcc" value="true"/>
74）在 NAT 时强制 rport。除非你很了解该参数，否则后果自负
    <param name="NDLB-force-rport" value="true"/>
75）设置 SIP Challenge 是使用的 realm 字段是从哪个域获取，auto_from 和 auto_to 分别是从 from 和 to 中获取，除了这两者，也可以是任意的值，默认是 auto_to 
        If you want URL dialing to work you'll want to set this to auto_from.
        If you use any other value besides auto_to or auto_from you'll loose the ability to do multiple domains.
    <param name="challenge-realm" value="auto_from"/>
76）在 FreeSWITCH 是，每一个 Channel 都有一个 UUID， 该 UUID 是由系统生成的全局唯一的。对于来话，你可以使用 SIP 中的 callid 字段来做 UUID. 在某些情况下对于信令的跟踪分析比较有用
    <param name="inbound-use-callid-as-uuid" value="true"/>
77）与上一个参数差不多，只是在去话时可以使用 UUID 作为 callid
    <param name="outbound-use-uuid-as-callid" value="true"/>
78）在某些情况下自动修复 RTP 时间戳。如果语音质量有问题，可以尝试将该值设成 false
    <param name="rtp-autofix-timing" value="false"/>
79）在支持的话机或系统间传送相关消息以更新被叫号码（在多个 FreeSWITCH实例间使用X-FS-Display-Name和X-FS-Display-NumberSIP 头域实现）。可以设置是否支持这种功能。默认情况下 FreeSWITCH 会设置额外的 X- SIP 消息头，在 SIP 标准中，所有 X- 打头的消息头都是应该忽略的。但并不是所有的实现都符合标准，所以在对方的网关不支持这种 SIP 头时，该选项允许你关掉它
    <param name="pass-callee-id" value="false"/>
80）某些运营商的设备不符合标准。为了最大限度的支持这些设备，FreeSWITCH 在这方面进行了妥协。可能的取值有CISCO_SKIP_MARK_BIT_2833和SONUS_SEND_INVALID_TIMESTAMP_2833等。使用该参数时要小心
    <param name="auto-rtp-bugs" data="clear"/>
81）这两个参数可以规避 DNS 中某些错误的 SRV 或 NAPTR 记录
    <param name="disable-srv" value="false" />
    <param name="disable-naptr" value="false" />
82）这几个参数允许根据需要调整 sofia 库中底层的时钟，一般情况下不需要改动
            <param name="timer-T1" value="500" />
            <param name="timer-T1X64" value="32000" />
            <param name="timer-T2" value="4000" />
            <param name="timer-T4" value="4000" />
83）Turn on a jitterbuffer for every call
    <param name="auto-jitterbuffer-msec" value="60"/>
84）By default mod_sofia will ignore the codecs in the sdp for hold/unhold operations
       Set this to true if you want to actually parse the sdp and re-negotiate the codec during hold/unhold.
       It's probably not what you want so stick with the default unless you really need to change this.
    <param name="renegotiate-codec-on-hold" value="true"/>
85）By default mod_sofia will send "100 Trying" in response to a SIP INVITE. Set this to false if you want to turn off this behavior and manually send the "100 Trying" via the acknowledge_call application.
    <param name="auto-invite-100" value="false"/>
86）另外的给本 sip profile 设置别名
    <param name="alias" value="sip:10.0.1.251:5555"/>
87）指定域的设置中设置 parse="true" 以用于从域目录中获取网关
    <domain name="\$\${domain}" parse="true"/>
    对所有域设置 parse="true" 以用于从他们的域目录中获取网关和别名
    <domain name="all" alias="true" parse="true"/></textarea></span></th>$html
<form method="post"><tr><td colspan=2 style='background:#decedd;'><em>新建分组</em> 分组名称：<input id="list_add" name="list_add" value="" size=20 class="inputline"/> <span class="smallred  smallsize-font">* 不允许&、引号、尖括号</span> &nbsp; <input type="submit" value="添加" $post_botton_enabled onclick="return confirm('是否是否确认提交，提交后会重启服务器！请务必谨慎');"/></td></tr>
<th colspan=2>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> run('sofia','profile internal restart');
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
echo "</th></form></table></body></html>";