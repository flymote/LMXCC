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
	$value = str_replace(array("&",'"',"'"),'_', $string);
	return $value;
}
$conf = $_SESSION['conf_dir'].'/autoload_configs/switch.conf.xml';
// $conf = 'freeswitch/autoload_configs/switch.conf.test';
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
$content = "<configuration name=\"switch.conf\" description=\"Core Configuration\">\n";
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
			if ($addkey=='codec'){
				if (is_numeric($addvalue)){
					$content .=  "<$addkey name=\"$addname\" ptime=\"$addvalue\"/>\n";
					$show_info .= "添加 $addkey name: $addname value: $addvalue ！ <br/>";
				}else
					$show_info .= "不能添加 $addkey name: $addname $addvalue 值不是数字！ <br/>";
			}else{
				$content .=  "<$addkey name=\"$addname\" value=\"$addvalue\"/>\n";
				$show_info .= "添加 $addkey name: $addname value: $addvalue ！ <br/>";
			}
		}
	
		foreach ($data as $k1=>$items){//分组下的一个元素组下（每个分组可以多个元素组）,如 param元素
			foreach ($items as $getone){ //一个参数，其中的参数项目也是数组
				$modi = 0;
				if(isset($getone['name']))
					$one = $getone['name'];
				else $one =$getone[0];
				if ($posted && !empty($_POST['del']) && in_array("{$k1}-{$one}",$_POST['del'])) {//post 删除的 跳过
					$show_info .= "$one 被删除！<br/>";
					continue;
				}elseif ($posted && !empty($_POST['mod']) && in_array("{$k1}-{$one}",$_POST['mod'])) {//修改value
					$modi = 1;
					$modvalue=xmlentities (trim($_POST["{$k1}{$one}m"]));
					if ($k1=='codec' && !is_numeric($modvalue)){
						$modi = 0;
						$show_info .= "$one 不能修改，$modvalue 不是数字！<br/>";
					}else
					if (empty($modvalue))
						$show_info .= "$k1 $one 被修改为空值 ！<br/>";
					else 
						$show_info .= "$k1 $one 值被修改为 $modvalue ！<br/>";
				};
				$str ="";
				foreach ($getone as $tmpk=>$tmpv){
					if ($modi && ($tmpk =="value"||$tmpk =="ptime") )
						$tmpv = $modvalue;
						if (($one =='core-db-dsn' || $one =='odbc-dsn') && (!empty($ini_conf['odbcdsn']) && $tmpk =="value" && $tmpv != $ini_conf['odbcdsn'] )){
						$str.="$tmpk=\"$tmpv\" <br/><span class=red>与设置 $ini_conf[odbcdsn] 冲突！请修正！</span>";
					}else
						$str.="$tmpk=\"$tmpv\" ";
				}
				if (fmod($i,2)==0)
					$bg = "class='bg1'";
				else 
					$bg = "class='bg2'";
				$i++;
				
				$html .="<tr $bg><td class='blod14'>$i.<em class='bgblue'>$k1 </em>&nbsp; $str </td><td>【改为<input type='text' name='{$k1}{$one}m' value='' size=8 class='inputline'/><label><input type='checkbox' name='mod[]' value='{$k1}-{$one}'/>选择修改</label>】 <label>【<input type='checkbox' name='del[]' value='{$k1}-{$one}'/>选择删除】</label> </td></tr>";
				$content .= "<$k1 $str/>\n";
			}
		}
		if ($key =="cli-keybindings")
			$input ="<input type='hidden' value='key' name='addkey' >新快捷命令：<input type='text' value='' name='addname' class='inputline' size=2 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		elseif ($key =="default-ptimes")
			$input ="<input type='hidden' value='codec' name='addkey' >设置SDP中codec的ptime(默认20ms)，编码：<input type='text' value='' name='addname' class='inputline' size=6 > (如G729) &nbsp; 值：<input type='text' name='addvalue' size=4 value='' class='inputline'/>(如40)";
		elseif ($key =="settings")
			$input ="<input type='hidden' value='param' name='addkey' >新设置：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		elseif ($key =="variables")
			$input ="<input type='hidden' value='variable' name='addkey' >这里设置的变量任意通道均可用，新变量：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		else
			$input ="项目：<input type='text' value='param' name='addkey' > 设置：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		$html .="<tr><td  style='background:#decfff;' align='center' class='blod14' colspan=2><input type='hidden' value='$key' name='lists' >$key $input &nbsp; <input type='submit'  value='提交操作' $post_botton_enabled onclick=\"return confirm('是否确认提交，提交后会重启服务器！请务必谨慎');\"></td></tr></form>";
			$content .= "</$key>\n";
		}
if (!empty($_POST['list_add'])){
	$listname = xmlentities (trim($_POST['list_add']));
	$show_info .= "添加新分组 $listname ！刷新页面后可见";
	$content .= "<$listname>\n</$listname>\n";
}
$content .= "</configuration>";

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>switch配置控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><th colspan=2>* 本配置文件是freeswitch的系统配置，Core Configuration 控制运行的相关参数<br/>修改信息前务必谨慎！！！不允许输入 &、引号，新加内容均为刷新页面后可见</span> <span onclick="$('#show_moreinfo_').toggle();" style='cursor:pointer;'>更多参数说明>>></span><br/><span class=red>$show_info $showinfo</span><textarea id='show_moreinfo_'  style='width:780px;height:200px;display:none;'/>
1）将控制台着色
 <param name="colorize-console" value="true"/>
2）在拨号计划日志中包含完整的时间戳
 <param name="dialplan-timestamps" value="false"/>
3）默认在20ms处运行计时器，并根据需要下拉，除非设置1m-timer=true，这是以前的默认设置
 <param name="1ms-timer" value="true"/>
4）在HA下设置switch的名称，设置后会覆盖全部DB和CURL请求的系统主机名，允许集群环境(如RHCS)具有相同的FreeSWITCH配置，但运行不同的主机名。
 <param name="switchname" value="freeswitch"/> 
 <param name="cpu-idle-smoothing-depth" value="30"/> 
5）同时打开的DB句柄的最大数目 
 <param name="max-db-handles" value="50"/>
6）DB连接失败前等待的秒数
 <param name="db-handle-timeout" value="10"/>
7）拒绝呼叫发起前最小的CPU可用数
 <param name="min-idle-cpu" value="25"/>
8）心跳事件间隔 
 <param name="event-heartbeat-interval" value="20"/>
9）允许在任何给定时间内允许的最大会话数  注意：一个通话有2个会话
 <param name="max-sessions" value="1000"/>
10）每秒最多可创建的通道数
 <param name="sessions-per-second" value="30"/>
11）全局的日志级别，取值为 debug,info,notice,warning,err,crit,alert 之一
 <param name="loglevel" value="debug"/>
12）核心调试级别 (0-10) 
 <param name="debug-level" value="10"/> 
13） SQL缓冲长度，取值 32k 到 10m
 <param name="sql-buffer-len" value="1m"/>
       最大SQL缓冲长度必须大于 sql-buffer-len
 <param name="max-sql-buffer-len" value="2m"/>
14）min-dtmf-duration指定要用于传出事件的最小DTMF持续时间。短于此值的事件的持续时间将增加，以匹配min-dtmf-duration。不能在低于此设置的配置文件上配置DTMF持续时间。您可以增加此值，但不能将其设置为低于400。此值不能超过max-dtmf-duration.
 <param name="min-dtmf-duration" value="400"/>
      max-dtmf-duration限制DTMF事件在指定持续时间内的播放。超过此持续时间的事件将被截断为此持续时间。不能在超过此设置的配置文件上配置持续时间。此设置可以降低，但不能超过192000。此设置不能设置低于min-dtmf-duration.
 <param name="max-dtmf-duration" value="192000"/>
      default-dtmf-duration指定要在原始DTMF事件或在未指定持续时间的情况下接收的事件上使用的DTMF持续时间。这个值可以增加或降低。此值在min-dtmf-duration上，上限为max-dtmf-duration。
 <param name="default-dtmf-duration" value="2000"/> -->
15）如果要通过Windows发送语音邮件通知，则需要将mailer-app变量更改为以下设置：
  <param name="mailer-app" value="msmtp"/>，不需要设置mailer-app-args
      其他情况设置：
  <param name="mailer-app" value="sendmail"/>
  <param name="mailer-app-args" value="-t"/>
  <param name="dump-cores" value="yes"/>
16）启用详细的通道事件，以在每个事件中包含有关通道的每个细节
   <param name="verbose-channel-events" value="no"/>
17）启用更精确的睡眠时钟nanosleep
   <param name="enable-clock-nanosleep" value="true"/>
18）启用单调定时
    <param name="enable-monotonic-timing" value="true"/>
19）RTP 端口范围
    <param name="rtp-start-port" value="16384"/>
     <param name="rtp-end-port" value="32768"/>
20）分配RTP端口前测试端口确保可用
     <param name="rtp-port-usage-robustness" value="true"/>
21）启用ZRTP
    <param name="rtp-enable-zrtp" value="false"/>
22）将安全媒体的加密密钥存储在信道变量中，并调用CDR。默认false。 警告：如果true，任何拥有CDR访问权限的人都可以解密安全媒体！
    <param name="rtp-retain-crypto-keys" value="true"/>
23）核心数据库设置，设置core-db-dsn将启用odbc并替代默认的sqlite
    <!-- <param name="core-db-dsn" value="pgsql://hostaddr=127.0.0.1 dbname=freeswitch user=freeswitch password='' options='-c client_min_messages=NOTICE'" /> -->
    <param name="core-db-dsn" value="freeswitch:root:limaoxiang" /> 
    <param name="odbc-dsn" value="freeswitch:root:limaoxiang" />
     允许在不同的位置指定sqlitdb(在本例中，将其移动到ramDrive，以便在大多数Linux发行版上获得更好的性能 (注意，如果重新启动，就会丢失数据))
    <!-- <param name="core-db-name" value="/dev/shm/core.db" /> -->
    <!-- The system will create all the db schemas automatically, set this to false to avoid this behaviour -->
    <!-- <param name="auto-create-schemas" value="true"/> -->
    <!-- <param name="auto-clear-sql" value="true"/> -->
    <!-- <param name="enable-early-hangup" value="true"/> -->
     核心数据库类型
    <!-- <param name="core-dbtype" value="MSSQL"/> -->
24）允许在中央登记表中的同一帐户进行多次注册
     <param name="multiple-registrations" value="true"/>
25）可新建variables分组，这里的任意变量定义均为每个通道可用，如
  <variables>
    <variable name="uk-ring" value="%(400,200,400,450);%(400,2200,400,450)"/>
    <variable name="us-ring" value="%(2000, 4000, 440.0, 480.0)"/>
    <variable name="bong-ring" value="v=4000;>=0;+=2;#(60,0);v=2000;%(940,0,350,440)"/>
  </variables>
</textarea></span></th>$html
<form method="post"><tr><td colspan=2 style='background:#decedd;'><em>新建分组</em> 分组名称：<input id="list_add" name="list_add" value="" size=20 class="inputline"/> 如 variables &nbsp; <input type="submit" value="添加" $post_botton_enabled onclick="return confirm('是否是否确认提交，提交后会重启服务器！请务必谨慎');"/></td></tr>
<th colspan=2>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> restart_switch();
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
echo "</th></form></table></body></html>";