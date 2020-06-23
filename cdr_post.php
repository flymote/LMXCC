<?php
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
define('BYPASS_LOGIN',1);
require_once "Shoudian_db.php";
include_once 'Logger.php';
$Logger = new Logger( __DIR__.'/logs', LogLevel::DEBUG, array (
		'extension' => 'log', //扩展名
		'prefix' => 'xmlCDR_',
		'flushFrequency' => 5 //缓冲写日志的行数
));

//set debug
$debug = $save_xml = false; //true //false
$file =__DIR__.'/.Config';
if (is_file($file))
	$conf = @unserialize(file_get_contents($file));
if (isset($conf['CDR_debug']))
	$debug = ($conf['CDR_debug']=='true'?true:false);
if (isset($conf['CDR_xml']))
	$save_xml = ($conf['CDR_xml']=='true'?true:false);
$time5 = microtime(true);
$insert_time=$insert_count=0;


function xml_cdr_log($msg,$level="debug") {
	global $debug,$Logger;
	if (!$debug) 	return;
	$Logger->$level($msg);
}

function check_str($string, $trim = true) {
	global $mysqli;
	$tmp_str = mysqli_real_escape_string($mysqli, $string);
	if (strlen($tmp_str)) {
		$string = $tmp_str;
	}else {
		$search = array("\x00", "\n", "\r", "\\", "'", "\"", "\x1a");
		$replace = array("\\x00", "\\n", "\\r", "\\\\" ,"\'", "\\\"", "\\\x1a");
		$string = str_replace($search, $replace, $string);
	}
	$string = ($trim) ? trim($string) : $string;
	return $string;
}

//increase limits
set_time_limit(3600);
ini_set('memory_limit', '256M');
ini_set("precision", 6);

//define the process_xml_cdr function
function process_xml_cdr($db, $leg, $xml_string) {
	global $debug,$save_xml,$Logger;
	
	//fix the xml by escaping the contents of <sip_full_XXX>
	$xml_string = preg_replace_callback("/<([^><]+)>(.*?[><].*?)<\/\g1>/",
	function ($matches) {	return '<' . $matches[1] . '>' .	str_replace(">", "&gt;",str_replace("<", "&lt;", $matches[2])	) .	'</' . $matches[1] . '>';},	
	$xml_string
	);
	//parse the xml to get the call detail record info
	try {
// 			xml_cdr_log($xml_string);
			$xml = simplexml_load_string($xml_string);
			if ($xml)
				xml_cdr_log("\n success load XML \n");
			else{
				xml_cdr_log("\n Error for load XML \n","error");
				return false;
			}
				
	}
	catch(Exception $e) {
		echo $e->getMessage();
		$Logger->error("\n Failed ： " . $e->getMessage() . "\n");
	}

$table = "FS_xml_cdr";

	//misc
$uuid = check_str(urldecode($xml->variables->uuid));
if ($save_xml)
	$fields['xml'] = $db->real_escape_string(gzcompress($xml_string));
$fields['uuid'] = $uuid;
$fields['accountcode'] = check_str(urldecode($xml->variables->accountcode));
if (empty($xml->variables->bridge_uuid))
	if (empty($xml->variables->signal_bond))
		$buuid =  $xml->variables->originate_signal_bond;
	else 
		$buuid =  $xml->variables->signal_bond;
else 
	$buuid = $xml->variables->bridge_uuid;
$fields['bridge_uuid'] = check_str(urldecode($buuid));

$ips = "本地：".$xml->variables->sip_local_network_addr." 网络：".$xml->variables->sip_network_ip.":".$xml->variables->sip_network_port;
if (!empty($xml->variables->sip_received_ip))
	$ips .= " 接收：".$xml->variables->sip_received_ip.":".$xml->variables->sip_received_port;
if (!empty($xml->variables->sip_reply_host))
	$ips .= " 回应：".$xml->variables->sip_reply_host.":".$xml->variables->sip_reply_port;
if (!empty($xml->variables->remote_media_ip))
	$ips .=" 远端媒体：".$xml->variables->remote_media_ip.':'.$xml->variables->remote_media_port;
	$ips .= (empty($xml->variables->sip_via_protocol)?'':($xml->variables->sip_via_protocol=='udp'?' UDP ':" ".$xml->variables->sip_via_protocol)).($xml->variables->sip_authorized=='true'?" 【SIP认证】":" ");
$fields['ip'] = check_str(urldecode($ips));

$fields['sip_hangup_disposition'] = check_str(urldecode($xml->variables->sip_hangup_disposition));
$fields['pin_number'] = check_str(urldecode($xml->variables->pin_number));
$fields['sip_gateway'] = check_str(urldecode($xml->variables->sip_gateway_name));
//time
$fields['start_epoch'] = check_str(urldecode($xml->variables->start_epoch));
$start_stamp = check_str(urldecode($xml->variables->start_stamp));
$fields['start_stamp'] = $start_stamp;
$fields['answer_stamp'] = check_str(urldecode($xml->variables->answer_stamp));
$fields['answer_epoch'] = check_str(urldecode($xml->variables->answer_epoch));
$fields['end_epoch'] = check_str(urldecode($xml->variables->end_epoch));
$fields['end_stamp'] = check_str(urldecode($xml->variables->end_stamp));
if (empty($fields['start_stamp']))
	$fields['start_stamp'] = '1900-01-01';
if (empty($fields['answer_stamp']))
	$fields['answer_stamp'] = '1900-01-01';
if (empty($fields['end_stamp']))
	$fields['end_stamp'] = '1900-01-01';
$fields['duration'] = check_str(urldecode($xml->variables->duration));
$fields['mduration'] = check_str(urldecode($xml->variables->mduration));
$fields['billsec'] = check_str(urldecode($xml->variables->billsec));
$fields['billmsec'] = check_str(urldecode($xml->variables->billmsec));
//codecs
$fields['read_codec'] = check_str(urldecode($xml->variables->read_codec));
$fields['read_rate'] = check_str(urldecode($xml->variables->read_rate));
$fields['write_codec'] = check_str(urldecode($xml->variables->write_codec));
$fields['write_rate'] = check_str(urldecode($xml->variables->write_rate));
$fields['hangup_cause'] = check_str(urldecode($xml->variables->hangup_cause));
$fields['hangup_cause_q850'] = check_str(urldecode($xml->variables->hangup_cause_q850));
//call center
$fields['cc_side'] = check_str(urldecode($xml->variables->cc_side));
$fields['cc_member_uuid'] = check_str(urldecode($xml->variables->cc_member_uuid));
$fields['cc_queue_joined_epoch'] = check_str(urldecode($xml->variables->cc_queue_joined_epoch));
$fields['cc_queue'] = check_str(urldecode($xml->variables->cc_queue));
$fields['cc_member_session_uuid'] = check_str(urldecode($xml->variables->cc_member_session_uuid));
$fields['cc_agent'] = check_str(urldecode($xml->variables->cc_agent));
$fields['cc_agent_type'] = check_str(urldecode($xml->variables->cc_agent_type));
$fields['waitsec'] = check_str(urldecode($xml->variables->waitsec));
//app info
$fields['last_app'] = check_str(urldecode($xml->variables->last_app));
$fields['last_arg'] = check_str(urldecode($xml->variables->last_arg));
//conference
$fields['conference_name'] = check_str(urldecode($xml->variables->conference_name));
$fields['conference_uuid'] = check_str(urldecode($xml->variables->conference_uuid));
$fields['conference_member_id'] = check_str(urldecode($xml->variables->conference_member_id));
//call quality
$rtp_audio_in_mos = check_str(urldecode($xml->variables->rtp_audio_in_mos));
	if (strlen($rtp_audio_in_mos) > 0) {
		$fields['rtp_audio_in_mos'] = $rtp_audio_in_mos;
	}
//get the values from the callflow.
$x = 0;
$fields['extension_uuid'] = "";
foreach ($xml->callflow as $row) {
if ($x == 0) {
	$context = check_str(urldecode($row->caller_profile->context));
	$fields['destination_number'] = check_str(urldecode($row->caller_profile->destination_number));
	$fields['context'] = $context;
	$fields['network_addr'] = check_str(urldecode($row->caller_profile->network_addr));
	$fields['orig_caller_number'] = urldecode(@$row->caller_profile->originator->originator_caller_profile->caller_id_number);
	$fields['orig_callee_number'] = urldecode(@$row->caller_profile->originator->originator_caller_profile->destination_number);
}
$fields['caller_id_name'] = check_str(urldecode($row->caller_profile->caller_id_name));
$fields['caller_id_number'] = check_str(urldecode($row->caller_profile->caller_id_number));
if ($row->extension)
	foreach ($row->extension->attributes() as $v){
		$fields['extension_uuid'] .= "$v "; 
	}
$x++;
}

//if last_sent_callee_id_number is set use it for the destination_number
if (strlen($xml->variables->last_sent_callee_id_number) > 0) {
	$fields['destination_number'] = urldecode($xml->variables->last_sent_callee_id_number);
}
//store the call leg
$fields['leg'] = $leg;
//store the call direction
$fields['direction'] = check_str(urldecode($xml->variables->direction));

//store post dial delay, in milliseconds
$fields['pdd_ms'] = check_str(urldecode($xml->variables->progress_mediamsec) + urldecode($xml->variables->progressmsec));

//get the domain values from the xml
$domain_name = check_str(urldecode($xml->variables->domain_name));
$domain_uuid = check_str(urldecode($xml->variables->domain_uuid));

//get the domain name from sip_req_host
if (strlen($domain_name) == 0) {
	$domain_name = check_str(urldecode($xml->variables->sip_req_host));
}

//send the domain name to the cdr log
xml_cdr_log("\ndomain_name is `$domain_name`; domain_uuid is '$domain_uuid'\n");

//set values in the database
if (strlen($domain_uuid) > 0) 
	$fields['domain_uuid'] = $domain_uuid;
if (strlen($domain_name) > 0) 
	$fields['domain_name'] = $domain_name;

$time5_insert = microtime(true);
GLOBAL $insert_time;
//insert xml_cdr into the db
$result = false;
	if (strlen($start_stamp) > 0) {
		$key = implode("`,`",array_keys($fields));
		$value = implode("','",array_values($fields));
		$sql = "insert into fs_xml_cdr (`$key`) values ('$value')";
		$result = $db->query($sql);
		$insert_time+=microtime(true)-$time5_insert; //add this current query.
		if ($result)
			return true;
		else{
			$Logger->error("\n insert failed： " .$db->error);
			return false;
		}
	}
}
//get cdr details from the http post
if (!empty($_POST["cdr"])) {
// 	if ($debug){
// 		print_r ($_POST["cdr"]);
// 	}

//通过其他页面传递xmlcdr的验证信息过来，如果有信息就验证；没有信息则忽略
if (!empty($_SESSION['xmlcdr_auth'] )) {
	//get the contents of xml_cdr.conf.xml
	$conf_xml_string = file_get_contents($_SESSION['conf_dir'].'/autoload_configs/xml_cdr.conf.xml');
	//parse the xml to get the call detail record info
	try {
		$conf_xml = simplexml_load_string($conf_xml_string);
	}catch(Exception $e) {
		echo $e->getMessage();
		$Logger->error("\nfail loadxml: " . $e->getMessage() . "\n");
	}
	foreach ($conf_xml->settings->param as $row) {
		if ($row->attributes()->name == "cred") {
			$auth_array = explode(":", $row->attributes()->value);
		}
	}
	//check for the correct username and password
	if ($auth_array[0] != $_SESSION['xmlcdr_auth'][0] || $auth_array[1] != $_SESSION['xmlcdr_auth'][1]) {
		$Logger->error("\nfail auth: failed ! \n");
		die("验证失败！");
		return;
	}
}
//get the http post variable
$xml_string = trim($_POST["cdr"]);
//get the leg of the call
if (substr($_REQUEST['uuid'], 0, 2) == "a_") {
	$leg = "a";
}else {
	$leg = "b";
}
xml_cdr_log("process cdr via post\n");
//parse the xml and insert the data into the db
process_xml_cdr($mysqli, $leg, $xml_string);
}else 
	xml_cdr_log("CDR info not postin !\n");
if (!empty($_SESSION['log_dir'])){
	$xml_cdr_dir = $_SESSION['log_dir'].'/xml_cdr';
	xml_cdr_log("process cdr in local dir: $xml_cdr_dir \n");
	$dir_handle = opendir($xml_cdr_dir);
	$x = $y = 0;
	while($file = readdir($dir_handle)) {
		if ($file != '.' && $file != '..') {
			if ( !is_dir($xml_cdr_dir . '/' . $file) ) {
					//get the leg of the call
					if (substr($file, 0, 2) == "a_") 
						$leg = "a";
					else
						$leg = "b";
			//get the xml cdr string
			$xml_string = urldecode(file_get_contents($xml_cdr_dir.'/'.$file));
			//parse the xml and insert the data into the db
			$result = process_xml_cdr($mysqli, $leg, $xml_string);
			if ($result){
				unlink($xml_cdr_dir.'/'.$file);
				$x++;
			}else 
				$y++;
			
			if ($x>10000)
				break;
			}
		}
	}
	xml_cdr_log(" $x times run in local dir \n");
	echo "<ul><li><h2>对XML CDR中提交失败的数据进行二次处理  <a style=\"font-size:10pt;\" href=\"main.php\">返回主控</a></h2></li><li>限定成功处理10000条数据，当前成功已处理 $x 条，失败 $y 条 ！请到CDR列表查看</li>";
	closedir($dir_handle);
}

if (!empty($_SESSION['log_dir'])){
	$time = "数据插入用时: ".number_format($insert_time,5). " 秒 \n";
	$time .= "其他处理时间: ".number_format((microtime(true)-$time5-$insert_time),5). " 秒\n";
	echo "<li>$time</li></ul>";
	xml_cdr_log($time);
}

