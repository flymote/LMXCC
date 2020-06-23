<?php
session_start();
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
if (!defined("ESL_HOST")){
	date_default_timezone_set('Asia/Shanghai');
	header("Content-type: text/html; charset=utf-8");
	define("ESL_HOST", @$_SESSION['ESL_HOST']);
	define("ESL_PORT", @$_SESSION['ESL_PORT']);
	define("ESL_PASSWORD",@$_SESSION['ESL_PASSWORD']);
}
include_once 'Shoudian_db.php';

function ivr_xml($row){
	if (!empty($row['ivr_config']))
		$ivr = json_decode($row['ivr_config'],true);
		else
			return false;
			if (is_array($ivr)){
				$menuContent = "";
				$ivr_xml ="<include>\n  <menu name=\"ivr_$row[domain_id]\"";
				foreach ($ivr as $k=>$one)
					if (!is_array($one)){
						if ($k=='greet-long' && empty($one)) //说明语音没有就忽略ivr
							return false;
							elseif (empty($one)) //设置值为0或空的 忽略这个设置，注意，也不允许为0
							continue;
							$ivr_xml .= "\n      $k=\"$one\"";
				}else
					foreach ($one as $menu){
						$mp = "";
						switch ($menu['a']){
							case 'menu-exec-app':
								$mp = "param=\"transfer $menu[p] XML $row[domain_id]\"";
								$menuContent .="\n    <entry action=\"$menu[a]\"  digits=\"$menu[d]\" $mp/>";
								break;
							case 'menu-exec-app1':
								$mp = 'param="transfer $1 XML '.$row['domain_id'].'"';
								$menuContent .="\n    <entry action=\"menu-exec-app\"  digits=\"/^(\d{".$menu['d']."})$/\" $mp/>";
								break;
							case 'menu-exec-app2':
								$mp = 'param="transfer callcenter XML '.$row['domain_id'].'"';
								$menuContent .="\n    <entry action=\"menu-exec-app\"  digits=\"$menu[d]\" $mp/>";
								break;
							default:
								if (!empty($menu['p']))
									$mp = "param=\"$menu[p]\"";
									$menuContent .="\n    <entry action=\"$menu[a]\"  digits=\"$menu[d]\" $mp/>";
									break;
						}
				}
				if ($menuContent)
					$ivr_xml .= ">$menuContent \n  </menu>\n</include>";
					else
						return false;
						return $ivr_xml;
			}else
				return false;
}

//$name 域id  $mode 99为停用 其他为构建本域相关文件（替换重构）并启用  （domain_build 也会在用户路由被删除时调用以重构域配置）
function domain_build($name,$mode=88){
	global $mysqli;
	$redis = redisDB();
	$redis->set('FS_conf_dir', $_SESSION['conf_dir']);
	$redis->set('FS_default_gw', $_SESSION['default_provider']);
	require_once "detect_switch.php";
	$info = new detect_switch();
	$str = $info->get_api_reply('fsctl sps');
	$redis->set('FS_sps', trim(substr($str,strpos($str, ":")+1)));
	$str = $info->get_api_reply('fsctl max_sessions');
	$redis->set('FS_maxsessions', trim(substr($str,strpos($str, ":")+1)));
	
	$result = $mysqli->query("select * from fs_domains where domain_id = '$name' and `enabled`=1");
	$row = $result->fetch_array();
	$file_dir = @$_SESSION['conf_dir']."/directory/".$row['domain_id'].".xml";
	$file_dia = @$_SESSION['conf_dir']."/dialplan/".$row['domain_id'].".xml";
	$file_cc = @$_SESSION['conf_dir']."/autoload_configs/callcenter.conf.xml";
	$file_did = @$_SESSION['conf_dir']."/dialplan/public/$row[level]_$row[DID].xml";
	$file_diadir = @$_SESSION['conf_dir']."/dialplan/".$row['domain_id'];
	$file_ivr = @$_SESSION['conf_dir']."/ivr_menus/$row[domain_id].xml";
	if (empty($row['domain_id']))
		die("操作域不可用！请先启用！");
		$_SESSION['POST_submit_once']=1;
	if ($mode=="99")
		if (is_file($file_dir)){
			$result = @unlink($file_dir);
			if ($result){
				$id = $redis->get("lmxcc_".$name);
				if($id){
					$output = `kill {$id}`;
					$plabel =" 自动外呼任务id $id Killing... \n$output";
				}else
					$plabel =" 无自动外呼任务！";
				@unlink($file_dia);
				@unlink($file_did);
				@unlink($file_ivr);
				$info->run('reloadxml','',0);
				$info->run("api","callcenter_config queue unload agents@$row[domain_id]",0);
				$redis->hset("domain_$row[domain_id]","enabled",0);
				die(" $name 域已被停用！$plabel ");
			}else
				die("$name 域数据无法清除，无法停用！");
		}else
			die("$name 域数据不存在，无需再次停用！");
	else{
		//这里是初始化一个路由列表备用
		$ext_result = $mysqli->query("select * from fs_gateways where `enabled`=0"); //域中使用的路由必须是没有被平台使用的
		$exts = result_fetch_all($ext_result);
		$gwlist = array();
		foreach ($exts as $one)
			$gwlist[$one['gatewayname']] = $one;
		//获取平台的参数设置，如odbcDSN、curlAPI等
		$file =__DIR__.'/.Config';
		if (is_file($file))
			$ini_conf = @unserialize(file_get_contents($file));
		else
			$ini_conf = false;
		$out_config = $row['out_config'];
		if ($out_config)
			$out_config = json_decode($out_config,true);
		
		//呼出主叫号码设置
		$user_out_id_str = "<variable name=\"outbound_caller_id_name\" value=\"$row[DID]\"/>\n<variable name=\"outbound_caller_id_number\" value=\"$row[DID]\"/>\n";
		$user_out_id_orig_str = $out_id = $out_name = "";
		$user_out_id_api_str = "<action application=\"set\" data=\"caller=\${caller_id_number}\"/>\n";
		if (isset($out_config['callerout'])){
			if ($out_config['callerout']=='set' && !empty($out_config['callerout_id'])){
				$out_id = $out_config['callerout_id'];
				$user_out_id_str = "<variable name=\"outbound_caller_id_name\" value=\"$out_config[callerout_name]\"/>\n<variable name=\"outbound_caller_id_number\" value=\"$out_config[callerout_id]\"/>\n";
				$user_out_id_api_str = "<action application=\"set\" data=\"caller=$out_config[callerout_id]\" inline=\"true\"/>\n";
				$user_out_id_orig_str = ",origination_caller_id_number=$out_config[callerout_id]";
				if (!empty($out_config['callerout_name'])){
					$out_name = $out_config['callerout_name'];
					$user_out_id_orig_str .= ",origination_caller_id_name=$out_config[callerout_name]";
				}
			}elseif ($out_config['callerout']=='api' && !empty($ini_conf['API_url_caller'])) { //如果api 模式下，用户设置的外呼主叫还是用默认，而是在拨号计划中启用api
				$user_out_id_api_str = "<action application=\"curl\" data=\"$ini_conf[API_url_caller]?domainid=\${domain_name}&callerid=\${caller_id_number}\" inline=\"true\"/>\n<action application=\"set\" data=\"caller=\${curl_response_data}\" inline=\"true\"/>\n";
				$user_out_id_orig_str = ",origination_caller_id_number=\${caller}";
			}
		}
		
		//呼出路由的设置，默认用全局的默认路由；呼出的号码取$callee的设置
		$out_GW = $_SESSION['default_provider'];
		if (isset($out_config['callerout_gw'])){
			if ($out_config['callerout_gw']=='set' && !empty($out_config['callerout_gw_name']))
				$out_GW = $out_config['callerout_gw_name'];
		}
		if ($out_GW=='')
			die("路由没有配置！无法继续！如果选择默认路由就必须先设置好默认路由！若新设置，需到 【服务器设置】 中 获取信息，以更新状态！");
		$bridge_GWstr = "<action application=\"bridge\" data=\"{originate_timeout=30$user_out_id_orig_str}sofia/gateway/$out_GW/\${callee}\"/>\n";
		
		//呼出被叫号码默认是 $callee
		$calleestr ="<action application=\"set\" data=\"callee=\${1}\" inline=\"true\"/>\n";
		$out_prefix = ""; 
		if (isset($out_config['callerout_to'])){
			if ($out_config['callerout_to']=='prefix' && !empty($out_config['callerout_to_prefix'])){
				$out_prefix = $out_config['callerout_to_prefix']; 
				$bridge_GWstr = "<action application=\"bridge\" data=\"{originate_timeout=30$user_out_id_orig_str}sofia/gateway/$out_GW/$out_prefix\${callee}\"/>\n";
			}elseif ($out_config['callerout_to']=='api' && !empty($ini_conf['API_url_callee'])) { 
				$calleestr ="<action application=\"curl\" data=\"$ini_conf[API_url_callee]?domainid=\${domain_name}&callerid=\${caller_id_number}&callee=\${1}\" inline=\"true\"/>\n<action application=\"set\" data=\"callee=\${curl_response_data}\" inline=\"true\"/>\n</condition>\n<condition field=\"\${callee}\" expression=\"^0\$\" break=\"on-true\">\n<action application=\"playback\" data=\"ivr/ivr-call_rejected.wav\"/>\n<action application=\"sleep\" data=\"1500\"/>\n<action application=\"hangup\"/>\n</condition>\n<condition>";
			}
		}
		
		$context ="<include>\n<context name=\"$row[domain_id]\">\n<extension name=\"unloop\">\n<condition field=\"\${unroll_loops}\" expression=\"^true$\"/>\n<condition field=\"\${sip_looped_call}\" expression=\"^true$\">\n<action application=\"deflect\" data=\"\${destination_number}\"/>\n</condition>\n</extension>\n<X-PRE-PROCESS cmd=\"include\" data=\"$row[domain_id]/*.xml\"/>\n<extension name=\"intercept-ext\">\n<condition field=\"destination_number\" expression=\"^\\*\\*(\\d+)\$\">\n<action application=\"answer\"/>\n<action application=\"intercept\" data=\"\${hash(select/\${domain_name}-last_dial_ext/\$1)}\"/>\n<action application=\"sleep\" data=\"2000\"/>\n</condition>\n</extension>\n<extension name=\"global\" continue=\"true\">\n<condition field=\"\${rtp_has_crypto}\" expression=\"^(\$\${rtp_sdes_suites})\$\" break=\"never\">\n<action application=\"set\" data=\"rtp_secure_media=true\"/>\n</condition>\n<condition field=\"\${endpoint_disposition}\" expression=\"^(DELAYED NEGOTIATION)\"/>\n<condition field=\"\${switch_r_sdp}\" expression=\"(AES_CM_128_HMAC_SHA1_32|AES_CM_128_HMAC_SHA1_80)\" break=\"never\">\n<action application=\"set\" data=\"rtp_secure_media=true\"/>\n</condition>\n<condition>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial/\${caller_id_number}/\${destination_number}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial/global/\${uuid}\"/>\n<action application=\"export\" data=\"RFC2822_DATE=\${strftime(%a, %d %b %Y %T %z)}\"/>\n</condition>\n</extension>\n<extension name=\"Local_Extension\">\n<condition field=\"destination_number\" expression=\"^$row[user_prefix](\d{1,20})$\">\n<action application=\"export\" data=\"dialed_extension=$1\"/>\n<action application=\"set\" data=\"ringback=\${us-ring}\"/>\n<action application=\"set\" data=\"transfer_ringback=\$\${hold_music}\"/>\n<action application=\"set\" data=\"call_timeout=30\"/>\n<!-- <action application=\"set\" data=\"sip_exclude_contact=\${network_addr}\"/> -->\n<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n<action application=\"set\" data=\"continue_on_fail=true\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-call_return/\${dialed_extension}/\${caller_id_number}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial_ext/\${dialed_extension}/\${uuid}\"/>\n<action application=\"set\" data=\"called_party_callgroup=\${user_data(\${dialed_extension}@\${domain_name} var callgroup)}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial_ext/\${called_party_callgroup}/\${uuid}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial_ext/global/\${uuid}\"/>\n<action application=\"export\" data=\"nolocal:rtp_secure_media=\${user_data(\${dialed_extension}@\${domain_name} var rtp_secure_media)}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial/\${called_party_callgroup}/\${uuid}\"/>\n<action application=\"set\" data=\"RECORD_ANSWER_REQ=true\"/>\n<action application=\"set\" data=\"RECORD_STEREO=false\"/>\n<action application=\"record_session\" data=\"\$\${recordings_dir}/\${strftime(%Y/%m/%d}/\${uuid}.wav\"/>\n<action application=\"bridge\" data=\"user/\${dialed_extension}@\${domain_name}\"/>\n</condition>\n</extension>\n";
		$xml = "<include>\n<domain name=\"$row[domain_id]\">\n<params>\n<param name=\"dial-string\" value=\"{^^:sip_invite_domain=\${dialed_domain}:presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(*/\${dialed_user}@\${dialed_domain})}\"/>\n<param name=\"allow-empty-password\" value=\"false\"/>\n</params>\n<variables>\n<variable name=\"record_stereo\" value=\"true\"/>\n<variable name=\"default_areacode\" value=\"\$\${default_areacode}\"/>\n<variable name=\"transfer_fallback_extension\" value=\"operator\"/>\n</variables>\n<groups>\n<group name=\"default\">\n<users>";
		// 			处理用户账号-------------------------
		$result = $mysqli->query("select `user_name`,`user_id`,`password`,`group_id`,`reverse_user`, `reverse_pwd`,`dial_str`,`user_context`,`gateway`,`variables`,`cidr` from fs_users where `domain_id` = '$row[domain_id]' and `enabled`=1 order by group_id");
		$groups = array();
		$usrstr = "\n";
		while (($row0 = $result->fetch_array())!==false) {
			if (!$row0) break;
			$usrstr .= "<user id=\"$row0[user_id]\"";
			if ($row0['cidr'])
				$usrstr .="  cidr=\"$row0[cidr]\">\n";
			else
				$usrstr .= ">\n";
			$usrstr .= "<params>\n";
			$usrstr .= "<param name=\"password\" value=\"$row0[password]\"/>\n"; //	<param name="a1-hash" value="538db5a1dcf95cd9df62bf2ff0466c4b"/>  // ==  md5(username:domain:password)
			$usrstr .= "<param name=\"vm-password\" value=\"$row0[password]\"/>\n";
			if  ($row0['dial_str'])
				$usrstr .= "<param name=\"dial-string\" value=\"$row0[dial_str]\"/>\n";
			if ($row0['reverse_user'])
				$usrstr .= "<param name=\"reverse-auth-user\" value=\"$row0[reverse_user]\" />\n<param name=\"reverse-auth-pass\" value=\"$row0[reverse_pwd]\" />";
			$usrstr .= "</params>\n<variables>\n";
					
			if ($row0['variables']){
				$temp = explode("\n", $row0['variables']);
				foreach ($temp as $one){
					$var = explode("===", trim($one));
					if (isset($var[1]))
						$usrstr .= "<variable name=\"$var[0]\" value=\"$var[1]\"/>\n";
					else
						$usrstr .= "<variable name=\"$var[0]\"/>\n";
				}
			}else{
				$usrstr .= "<variable name=\"toll_allow\" value=\"domestic,international,local\"/>\n";
				$usrstr .= "<variable name=\"accountcode\" value=\"$row0[user_id]\"/>\n";
				$usrstr .= "<variable name=\"effective_caller_id_name\" value=\" $row0[user_name] \"/>\n";
				$usrstr .= "<variable name=\"effective_caller_id_number\" value=\"$row0[user_id]\"/>\n";
				$usrstr .= $user_out_id_str;
			}
			if ($row0['user_context'])
				$usrstr .= "<variable name=\"user_context\" value=\"$row0[user_context]\"/>\n";
			else
				$usrstr .= "<variable name=\"user_context\" value=\"$row[domain_id]\"/>\n";
			$usrstr .= "<variable name=\"callgroup\" value=\"$row[domain_id]\"/>\n"; //把代答组设置为域ID	，代答组的人可以代答呼叫；
			if ($row0['gateway']){
				$usrstr .= "<variable name=\"register-gateway\" value=\"$row0[gateway]\"/>\n";
				$usrstr .= "</variables>\n";
				$lab = array("gatewayname","realm", "username","password","register","from-user","from-domain","regitster-proxy","outbound-proxy","expire-seconds","caller-id-in-from","extension","proxy","register-transport","retry-seconds","contact-params","ping","addon","variables");
				$gws = explode(",",$row0['gateway']);
				if ($gws){
					$usrstr .="<gateways>\n";
					foreach ($gws as $one){
						if (!isset($gwlist[$one]))
							continue;
						$i = 0;
						foreach ($lab as $key){
							if  ($i==0)
								$usrstr .=" <gateway name=\"{$gwlist[$one]['gatewayname']}\">\n";
							elseif($i<5)
								$usrstr .= " <param name=\"$key\" value=\"" . $gwlist[$one][$key] . "\"/>\n";
							elseif(!empty($gwlist[$one][$key]))
								if ($key=='variables' || $key=='addon' )
									$usrstr .= "{$gwlist[$one][$key]}\n";
								else
									$usrstr .= " <param name=\"$key\" value=\"" . $gwlist[$one][$key] . "\"/>\n";
							$i++;
						}
						$usrstr .=	" </gateway>\n";
					}
					$usrstr .="</gateways>\n";
				}
			}else
				$usrstr .= "</variables>\n";
			$usrstr .="</user>\n";
			//将用户加入定义的组
			if ($row0['group_id']){
				$g = explode(",", $row0['group_id']);
				foreach ($g as $one){
					if (isset($groups[$one]))
						$groups[$one] .= "	  <user id=\"$row0[user_id]\" type=\"pointer\"/>\n";
					else
						$groups[$one] = "	  <user id=\"$row0[user_id]\" type=\"pointer\"/>\n";
				}
			}
		}
		$xml .= "$usrstr</users>\n</group>\n";
						
		// 			处理组--------------------------------------
		$result = $mysqli->query("select `group_id`,`calltype`,`calltimeout` from fs_groups where `domain_id` = '$row[domain_id]' and `enabled`=1");
		while (($row1 = $result->fetch_array())!==false) {
			if (!$row1) break;
			$xml .= "\n      <group name=\"$row1[group_id]\">\n";
			if (isset($groups[$row1['group_id']]))
				$xml .= "	<users>\n".$groups[$row1['group_id']]."	</users>\n";
			$context .= "\n<extension name=\"Group $row1[group_id]\">\n<condition field=\"destination_number\" expression=\"^$row[group_prefix]$row1[group_id]$\">\n<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n<action application=\"set\" data=\"continue_on_fail=true\"/>\n<action application=\"set\" data=\"originate_continue_on_timeout=true\"/>\n<action application=\"set\" data=\"call_timeout=$row1[calltimeout]\"/>\n<action application=\"bridge\" data=\"\${group_call($row1[group_id]@\${domain_name}$row1[calltype])}\"/>\n<action application=\"transfer\" data=\"$row1[group_id] XML default\"/>\n<action application=\"hangup\"/>\n</condition>\n</extension>\n";
			$xml .= "      </group>\n";
		}
		$xml .=	"</groups>\n</domain>\n</include>";
						
	// 			处理IVR-------------------------------------------------
	$ivr_xml = ivr_xml($row);
	// 			处理呼叫中心-------------------------------------
	//这里初始化一个callcenter的队列列表备用
	$ext_result = $mysqli->query("select `domain_id`,`callcenter_config` from fs_domains where `enabled`=1"); //域中使用的路由必须是没有被平台使用的
	$cc_conf = [];
	while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
		if (!$row0) break;
		$cc_conf[$row0[0]] = $row0[1];
	}
	$callcenter_str_fail = "";  //呼叫失败后全部坐席都拨打一下？  $callcenter_str_fail = "<action application=\"bridge\" data=\"{leg_timeout=15,ignore_early_media=true}\${group_call(default@\${domain_name})}\"/>";
	if ($ivr_xml)
		$ivr_dia = "    <action application=\"answer\"/>\n    <action application=\"sleep\" data=\"500\"/>\n    <action application=\"ivr\" data=\"ivr_$row[domain_id]\"/>\n";
	else
		$ivr_dia = "    <action application=\"set\" data=\"cc_export_vars=domain_name,call_timeout,rid,origination_caller_id_number,origination_caller_id_name\"/>\n    <action application=\"callcenter\" data=\"agents@$row[domain_id]\"/>\n";
	$did = '<include>
  <extension name="public_did_'.$row['domain_id'].'">
    <condition field="destination_number" expression="^('.$row['DID'].')$">
    <action application="set" data="domain_name='.$row['domain_id'].'"/>
    <action application="set" data="call_timeout=10"/>
    <action application="set" data="rid=${uuid}"/>
    <action application="set" data="origination_caller_id_name=${caller_id_name}"/>
    <action application="set" data="origination_caller_id_number=${caller_id_number}"/>
'.$ivr_dia.$callcenter_str_fail.'
    </condition>
  </extension>
</include>';
	$cc = '<configuration name="callcenter.conf" description="CallCenter">
<settings>';
	if  (!empty($ini_conf['odbcdsn']))
		$cc .="\n<param name=\"odbc-dsn\" value=\"$ini_conf[odbcdsn]\"/>";
	$cc .="\n</settings>\n\n<queues>\n";
	foreach ($cc_conf as $k=>$v){
		$cc .="\n<queue name=\"agents@$k\">\n";
		$temp = json_decode($v,true);
		if (is_array($temp))
			foreach ($temp as $k1=>$v1)
				$cc .="<param name=\"$k1\" value=\"$v1\"/>\n";
		$cc .="</queue>\n";
	}
	$cc .="\n</queues>\n\n<agents>\n</agents>\n\n<tiers>\n</tiers>\n\n</configuration>";
	$auto_api_str = $auto_api_str1 =  "";
	if (!empty($ini_conf['API_url_autoserv'])) { //answer 状态返回任务语音文件
		$auto_api_str = "<action application=\"curl\" data=\"$ini_conf[API_url_autoserv]?domainid=$row[domain_id]&callerid=\${caller_id_number}\"/>\n<action application=\"curl\" data=\"$ini_conf[API_url_autoserv]?taskid=\${taskid}\" inline=\"true\"/>\n<action application=\"set\" data=\"sound=\${curl_response_data}\" inline=\"true\"/>";
		$auto_api_str1 = "<action application=\"curl\" data=\"$ini_conf[API_url_autoserv]?taskid=\${taskid}&status=complete\"/>";
	}
	$context .='
<extension name="AutoCall_service">
<condition field="destination_number" expression="^service$">
<action application="answer"/>
'.$auto_api_str.'
</condition>
<condition field="${sound}" expression="wav$" break="never">
<action application="playback" data="${sound}"/>
</condition>
<condition field="destination_number" expression="^service$">
'.$auto_api_str1.'
<action application="set" data="call_timeout=10"/>
<action application="set" data="rid=${uuid}"/>
<action application="set" data="caller=${caller_id_number}"/>
<action application="set" data="origination_caller_id_number=${caller_id_number}"/>
<action application="set" data="cc_export_vars=taskid,call_timeout,rid,caller,origination_caller_id_number"/>
<action application="callcenter" data="agents@'.$row['domain_id'].'"/>
</condition>
</extension>
  <extension name="callcenter_'.$row['domain_id'].'">
    <condition field="destination_number" expression="^(callcenter)$">
    <action application="set" data="domain_name='.$row['domain_id'].'"/>
	<action application="set" data="call_timeout=10"/>
	<action application="set" data="rid=${uuid}"/>
	<action application="set" data="origination_caller_id_name=${caller_id_name}"/>
	<action application="set" data="origination_caller_id_number=${caller_id_number}"/>
    <action application="set" data="cc_export_vars=domain_name,call_timeout,rid,origination_caller_id_number,origination_caller_id_name"/>
    <action application="callcenter" data="agents@'.$row['domain_id'].'"/>
    </condition>
  </extension>
  <extension name="Outgoing">
    <condition field="destination_number" expression="^'.$row['out_prefix'].'(\d{6,17})$">
'.$calleestr.'
'.$user_out_id_api_str.'
	<action application="set" data="RECORD_ANSWER_REQ=true"/>
	<action application="set" data="RECORD_STEREO=false"/>
	<action application="record_session" data="$${recordings_dir}/${strftime(%Y/%m/%d}/${uuid}.wav"/>
	<action application="set" data="call_timeout=60"/>
	<action application="bridge_export" data="rid=${uuid}"/>
	<action application="bridge_export" data="caller=${caller}"/>
	<action application="bridge_export" data="callee=${callee}"/>
'.$bridge_GWstr.'
    </condition>
  </extension>
<extension name="AutoCall_agent">
<condition field="destination_number" expression="^'.$row['autocall_self'].'$">
<action application="curl" data="'.$ini_conf['API_url_autoself'].'?domainid=${domain_name}&callerid=${caller_id_number}" inline="true"/>
<action application="set" data="callee=${curl_response_data}" inline="true"/>
'.$user_out_id_api_str.'
</condition>
<condition field="${callee}" expression="^0$" break="on-true">
<action application="playback" data="ivr/ivr-call_rejected.wav"/>
<action application="sleep" data="1500"/>
<action application="hangup"/>
</condition>
<condition>
<action application="bridge_export" data="nolocal:bleg_uuid=${uuid}"/>
<action application="bridge_export" data="nolocal:accountcode=${accountcode}"/>
<action application="set" data="RECORD_ANSWER_REQ=true"/>
<action application="set" data="RECORD_STEREO=false"/>
<action application="record_session" data="$${recordings_dir}/${strftime(%Y/%m/%d}/${uuid}.wav"/>
<action application="set" data="call_timeout=60"/>
'.$bridge_GWstr.'
</condition>
</extension>
</context>
</include>';
	$agent_xml = '<include>
<extension name="agent_login">
  <condition field="destination_number" expression="^'.$row['agent_login'].'$">
    <action application="set" data="res=${callcenter_config(agent set status ${caller_id_number}@${domain_name} \'Available\')}" />
    <action application="answer" data=""/>
    <action application="sleep" data="500"/>
    <action application="playback" data="ivr/ivr-you_are_now_logged_in.wav"/>
    <action application="hangup" data=""/>
  </condition>
</extension>
<extension name="agent_break">
  <condition field="destination_number" expression="^'.$row['agent_break'].'$">
    <action application="set" data="res=${callcenter_config(agent set status ${caller_id_number}@${domain_name} \'On Break\')}" />
    <action application="answer" data=""/>
    <action application="sleep" data="500"/>
    <action application="playback" data="ivr/set_busy_success.wav"/>
    <action application="hangup" data=""/>
  </condition>
</extension>
<extension name="agent_logoff">
  <condition field="destination_number" expression="^'.$row['agent_out'].'$">
    <action application="set" data="res=${callcenter_config(agent set status ${caller_id_number}@${domain_name} \'Logged Out\')}" />
    <action application="answer" data=""/>
    <action application="sleep" data="500"/>
    <action application="playback" data="ivr/ivr-you_are_now_logged_out.wav"/>
    <action application="hangup" data=""/>
  </condition>
</extension>
</include>';
	$result = @file_put_contents($file_dir, $xml);
	unset($xml);
	if ($result){
		@file_put_contents($file_dia, $context);
		@file_put_contents($file_did, $did);
		@file_put_contents($file_cc, $cc);
		if ($ivr_xml)
			@file_put_contents($file_ivr, $ivr_xml);
			if (!is_dir($file_diadir))
				mkdir($file_diadir);
			@file_put_contents($file_diadir."/00_agent.xml", $agent_xml);
			$info->run("reloadxml","",0);
			$info->run("api","callcenter_config queue load agents@$row[domain_id]",0);
			$redis->hMset("domain_$row[domain_id]",["enabled"=>1,"level"=>$row['level'],"lines"=>$row['autocall_lines'],"GW"=>$out_GW,"prefix"=>$out_prefix,"callerName"=>$out_name,"callerID"=>$out_id]);
			die(" $name 域已被添加并更新状态！");
	}else
		die("$name 域数据添加失败！");
	}
}

//应用部署及停用，ESL
if (empty($_SESSION['POST_submit_once']) ){
	if (isset($_POST['yid'])){
		if (isset($_POST['en0']) && in_array($_POST['en0'],array("88","99")))
			domain_build($_POST['en1'],$_POST['en0']);
		elseif (isset($_POST['en6'])){
			$id = $_POST['en1'];
			if ($_POST['en6'] == '66'){
				$output = `/usr/lmxcc -sd{$id}`;
				die(" 域$id 以后台服务模式启动 \n$output");
			}elseif ($_POST['en6'] == '77'){
				$redis = redisDB();
				$id = $redis->get("lmxcc_".$_POST['en1']);
				if($id){
					$output = `kill {$id}`;
					die(" 任务id $id Killing... \n$output");
				}else
					die(" 无任务信息！");
			}
		}
		die("信息不完整，非法提交操作！");
	}
	//设置启用或禁用
	if (!empty($_POST['sid'])){
		$id = intval($_POST['sid']);
		$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
		if ($to === 1){
			$_SESSION['POST_submit_once']=1;
			$mysqli->query("update fs_domains set `enabled` = 1 where id = $id limit 1");
			die("id $id 设置为可用完毕");
		}else{
			$_SESSION['POST_submit_once']=1;
			$mysqli->query("update fs_domains set `enabled` = 0 where id = $id limit 1");
			die("id $id 设置为禁用完毕");
		}
	}
}
//删除域记录
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$result = $mysqli->query("select `domain_id` from fs_domains where id = $id and `enabled`=0");
	$row = $result->fetch_array();
	if (!empty($row[0])){
		$redis = redisDB();
		$mysqli->query("delete from fs_domains where id = $id limit 1");
		$mysqli->query("update fs_gateways set domain_id='',domain_user='' where domain_id = '$row[0]'");
		$mysqli->query("update fs_groups set `domain_id`=CONCAT('_DEL_',domain_id),`enabled`=0 where `domain_id` = '$row[0]' ");
		$mysqli->query("update fs_users set `domain_id`=CONCAT('_DEL_',domain_id),`enabled`=0,`group_id` = '' where `domain_id` = '$row[0]' ");
		$redis->del("domain_$row[domain_id]");
		die("id $id 操作完毕");
	}
	die("要删除的域，必须已被禁用！");
}