<?php
 set_time_limit(600);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
include 'Shoudian_db.php';
$file =__DIR__.'/.Config';
$conf = @unserialize(file_get_contents($file));
//----------------------显示---------------------------------
$_SESSION['POST_submit_once']=0;

$Q850_label = array('UNSPECIFIED'=>'未指定','UNALLOCATED_NUMBER'=>'404 无此号','NO_ROUTE_TRANSIT_NET'=>'404 无法传输','NO_ROUTE_DESTINATION'=>'404 无路由','CHANNEL_UNACCEPTABLE'=>'频道不可接受','CALL_AWARDED_DELIVERED'=>'呼叫被授予','NORMAL_CLEARING'=>'正常清除','USER_BUSY'=>'486 用户忙','NO_USER_RESPONSE'=>'408 无响应','NO_ANSWER'=>' 480 未回答','SUBSCRIBER_ABSENT'=>'480 订户缺席','CALL_REJECTED'=>'603 呼叫被拒绝','NUMBER_CHANGED'=>'410 号码已变','REDIRECTION_TO_NEW_DESTINATION'=>'410 已重定向','EXCHANGE_ROUTING_ERROR'=>'483 路由错','DESTINATION_OUT_OF_ORDER'=>'502  目的地无序','INVALID_NUMBER_FORMAT'=>'484 无效格式','FACILITY_REJECTED'=>'501 设施被拒绝','RESPONSE_TO_STATUS_ENQUIRY'=>'状态的响应','NORMAL_UNSPECIFIED'=>'正常未定','NORMAL_CIRCUIT_CONGESTION'=>'503 无电路可用','NETWORK_OUT_OF_ORDER'=>'503 网络故障','NORMAL_TEMPORARY_FAILURE'=>'503 暂时失败','SWITCH_CONGESTION'=>'503 交换机拥塞','ACCESS_INFO_DISCARDED'=>'访问信息丢弃','REQUESTED_CHAN_UNAVAIL'=>'503 电路不可用','PRE_EMPTED'=>'预占位','RESOURCE_UNAVAILABLE'=>'资源不可用','FACILITY_NOT_SUBSCRIBED'=>'设施未订','OUTGOING_CALL_BARRED'=>'403 呼出被禁','INCOMING_CALL_BARRED'=>'403 来电被禁','BEARERCAPABILITY_NOTAUTH'=>'403 承载未授权','BEARERCAPABILITY_NOTAVAIL'=>'503 不可承载','SERVICE_UNAVAILABLE'=>'503 服务不可用','BEARERCAPABILITY_NOTIMPL'=>'488 承载未实现','CHAN_NOT_IMPLEMENTED'=>'通道未实现','FACILITY_NOT_IMPLEMENTED'=>'501 设施未实施','SERVICE_NOT_IMPLEMENTED'=>'501 服务未指定','INVALID_CALL_REFERENCE'=>'无效呼叫参考','INCOMPATIBLE_DESTINATION'=>'488 不兼容目的地','INVALID_MSG_UNSPECIFIED'=>'无效消息','MANDATORY_IE_MISSING'=>'缺失必要信息','MESSAGE_TYPE_NONEXIST'=>'消息不存在','WRONG_MESSAGE'=>'消息不兼容','IE_NONEXIST'=>'信息被丢弃','INVALID_IE_CONTENTS'=>'信息无效','WRONG_CALL_STATE'=>'呼叫不兼容','RECOVERY_ON_TIMER_EXPIRE'=>'504 定时到期恢复','MANDATORY_IE_LENGTH_ERROR'=>'参数不存在','PROTOCOL_ERROR'=>'协议错误','INTERWORKING'=>'互通未指定','ORIGINATOR_CANCEL'=>'487 请求被取消','CRASH'=>'崩溃','SYSTEM_SHUTDOWN'=>'系统关闭','LOSE_RACE'=>'进程中断','MANAGER_REQUEST'=>'命令挂断','BLIND_TRANSFER'=>'隐蔽的转移','ATTENDED_TRANSFER'=>'附加的转移','ALLOTTED_TIMEOUT'=>'指定的超时','USER_CHALLENGE'=>'用户质疑','MEDIA_TIMEOUT'=>'媒体超时','PICKED_OFF'=>'被分机截取','USER_NOT_REGISTERED'=>'用户未注册','PROGRESS_TIMEOUT'=>'处理超时','GATEWAY_DOWN'=>'网关关闭','INVALID_GATEWAY'=>'网关错误');
$hangup_label = array(""=>"--","send_bye"=>"发送挂机","recv_bye"=>"收到挂机","send_refuse"=>"发送拒绝","send_cancel"=>"发送取消","recv_refuse"=>"收到拒绝","recv_cancel"=>"收到取消");
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/>
<script type=\"text/javascript\" src=\"css/jquery.js\"></script><script type=\"text/javascript\">
function showcodeL(sid,iid){\$.post( \"FS_error_code.php\", { Q850_reason_label: sid }).done(function( data ) { alert(sid+' 说明：\\n'+data);});}
function showcodeF(sid,iid){\$.post( \"FS_error_code.php\", { FS_reason_label: sid,FS_direction:iid }).done(function( data ) { alert(sid+' 说明：\\n'+data);});}
function showcodeC(sid,iid){\$.post( \"FS_error_code.php\", { Q850_reason_code: sid }).done(function( data ) { $('#info'+iid).html(data);});}
function showcodeS(sid,iid){\$.post( \"FS_error_code.php\", { SIP_reason_code: sid }).done(function( data ) { $('#info'+iid).html(data);});}
</script></head><body><div id=\"win\"><h2 id=\"tt\"><span id=\"close\" onclick=\"$('#win').css('display','none')\" style=\"pading:5px;\"> × </span></h2>
    <p id=\"title\" class=\"act2\"></p>
<audio id=\"player\" controls=\"controls\" src=\"\">你的浏览器不支持audio标签。</audio></div>";
$count = 15;
$getstr = "";
$showget = "<span class='smallred smallsize-font'> ";
$where = 'where 1 ';
$date = time();
$startdate = date("Y-m-d H:i:s",$date-86400);
$enddate = date("Y-m-d H:i:s",$date);
$limit_ans_c = "";
if (!empty($_GET['viewrelation'])){
	$ids = explode(",",$_GET['viewrelation']);
	$result = $mysqli->query("select * from fs_xml_cdr where uuid in ( '$ids[0]','$ids[1]') ");
	$totle = count($ids);
	$p = 0;
	$pages = 1;
	$showget .= "（ $totle 条，$pages 页）</span>";
}else{
	if (!empty($_GET['startdate']) && !empty($_GET['enddate'])){
		$date = strtotime($_GET['startdate']);
		if ($date)
			$date1 = strtotime($_GET['enddate']);
			if($date && $date1){
				$where .= "and `start_stamp` >= '$_GET[startdate]' and `start_stamp` <= '$_GET[enddate]'";
				$getstr .="&startdate=$_GET[startdate]&enddate=$_GET[enddate]";
				$showget .=" <b>$_GET[startdate] </b> 到  <b>$_GET[enddate]</b> ";
				$startdate = $_GET['startdate'];
				$enddate = $_GET['enddate'];
			}
	}
	if (!empty($_GET['limit_ans'])){
		$where .= "and `answer_epoch` > 0 ";
		$getstr.= "&limit_ans=1";
		$showget .=" 仅显示已应答话单 ";
		$limit_ans_c = "checked=\"checked\" ";
	}
	if (!empty($_GET['phone']) && is_numeric ($_GET['phone'])){
		$phone = $_GET['phone'];
		$where .= "and `destination_number` like '%$phone%' ";
		$getstr.= "&phone=$phone";
		$showget .=" 被叫号<b>【 $phone 】</b>";
	}
	if (!empty($_GET['phone0']) && is_numeric ($_GET['phone0'])){
		$phone = $_GET['phone0'];
		$where .= "and `caller_id_number` like '%$phone%' ";
		$getstr.= "&phone0=$phone";
		$showget .=" 主叫id<b>【 $phone 】</b>";
	}
	if (!empty($_GET['domain'])){
		$domain = $_GET['domain'];
		$where .= "and (`domain_name` = '$domain' or `context` ='$domain') ";
		$getstr.= "&domain=$domain";
		$showget .=" 域id<b>【 $domain 】</b>";
	}
	$totle = $mysqli->query("select count(*) from fs_xml_cdr $where");
	$row = $totle->fetch_array(MYSQLI_NUM);
	$totle = $row[0];
	$pages = ceil($totle/$count);
	if (empty($_GET['p']))
		$p = 0;
	else{
		$p = intval($_GET['p']);
		if ($p>$pages)
			$p = $pages;
		if ($p<0)
			$p = 0;
		}
		$showget .= "（ $totle 条，$pages 页）</span>";
	$result = $mysqli->query("select * from fs_xml_cdr $where ORDER BY start_stamp DESC LIMIT ".($p*$count).",$count");
}
	echo '<p class="pcenter" style="font-size:18pt;">CDR 通话详单  '.$showget.' <a style="font-size:10pt;" href="main.php">&raquo;&nbsp;返回主控</a></p><form method="get"><p style="text-align:center;margin-bottom:5px;">域：<input id="domain" name="domain" value="" size=10> 主叫id：<input id="phone0" name="phone0" value="" size=10> 被叫id：<label><input id="phone" name="phone"  value=""> 开始：<input id="startdate" name="startdate" value="'.$startdate.'" size="15"> 结束：<input id="enddate" name="enddate" value="'.$enddate.'" size="15"> <label><input id="limit_ans" name="limit_ans" value="1" type="checkbox" '.$limit_ans_c.' >仅显示已应答</label> &nbsp;  <input type="submit" value="确认"/></p></form><table class="tablegreen" width="90%" align="center"><tr><th>主叫</th><th>被叫</th><th>域</th><th>时间</th><th>动作</th><th>编码</td><th>状态</th></tr>';
	$i=0;
	if (!isset($conf['CDR_file']))
		$filename = '@start_stamp@_@destination_number@_@caller_id_number@.wav';
	else 
		$filename = $conf['CDR_file'];

	while (($row = $result->fetch_array())!==false) {
		if (!$row){
			$pp = $p -1;
			if ($pp < 0)
				$first = '';
			elseif($pp < 1)
				$first = '<a href="?list=1&p=0'.$getstr.'">&laquo; 首页</a>';
			else
				$first = '<a href="?list=1&p=0'.$getstr.'">&laquo; 首页</a> <a href="?list=1&p='.($pp).$getstr.'">前一页</a>';
			if ($p+1 >= $pages)
				$end = '  <a href="?p='.($p).$getstr.'">尾页 &raquo;</a> ';
			else
				$end = '  <a href="?p='.($p+1).$getstr.'">下一页</a>  <a href="?p='.($pages-1).$getstr.'">尾页 &raquo;</a>';
			die("</table><p class='red'>$first ".($p==0?1:$p+1).$end.'
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/> <a href="?">最新</a> </p></body></html>');
		}
		else{
			if ($row['bridge_uuid'])
				$relation = (isset($_GET['viewrelation'])?"":"<a href='?viewrelation=$row[uuid],$row[bridge_uuid]'>&harr;</a>");
			else
				$relation = "";
			if ($row['direction']=='inbound')
				$shownumber = " <div style='float:left;'><span class=\"bggreen bold14\"> $row[caller_id_number] </span> ($row[caller_id_name]) </div><div style='float:right;'>⋙<span class=\"orange\">◈</span></div>";
			else
				$shownumber= " <div style='float:left;'><span class=\"bold12\">$row[caller_id_number] </span>($row[caller_id_name]) </div><div style='float:right;'><span class=\"orange\">◈</span>⋙</div>";
			if ($row['read_codec'])
				$showcode = " 读：<b>$row[read_codec] $row[read_rate]</b><br/> 写：<b>$row[write_codec] $row[write_rate]</b>";
			else 
				$showcode = "<span class='smallgray smallsize-font'>无编码信息</span>";
			if ($row['accountcode'])
				$accountcode = " <b> $row[accountcode]</b> @ ";
			else $accountcode = "";
			if ($row['extension_uuid'])
				$extension_uuid = "EXT：<b>$row[extension_uuid]</b><br/>";
			else $extension_uuid = "";
			if ($row['domain_name'])
				$domain_name = "$row[domain_name] ";
			else $domain_name = "";
			if ($row['context'])
				$context = ($domain_name?"<br/>":"")."context：<b>$row[context]</b> ";
			else $context = "";
			if ($row['sip_gateway'])
				$sip_gateway = " sip_gateway：<b>$row[sip_gateway]</b><br/>";
			else $sip_gateway = "";
			if ($row['last_app'])
				$last_app = "APP：<b>$row[last_app] $row[last_arg]</b> ";
			else $last_app = "";
			$filestr = "";
			if (!empty($row['answer_epoch'])){
				$answertime =   "应答于 ".substr($row['answer_stamp'],11)." 通话 $row[billsec] 秒  ($row[billmsec] 毫秒)";
				$pathstr = date("Y/m/d", $row['start_epoch']);
				$namefind = $namerepl = $namerepl1 = array();
				preg_match_all('|@([^@]*)@|',$filename,$nameparts);
				if (is_array($nameparts[1]))
					foreach ($nameparts[1] as $one){
						if (in_array($one, ['start_stamp','end_stamp','answer_stamp'])){
							$namefind[] = "@$one@";
							$namerepl[] = str_replace(array(" ",":"),"-", $row[$one]);
							$tempone = substr($row[$one],-2) + 1; //提取秒钟并加1，针对某些因为保存录音时间延迟而产生的文件名错
							$tempone = $tempone>59?'00':($tempone<10?"0$tempone":"$tempone");
							$namerepl1[] = str_replace(array(" ",":"),"-", substr($row[$one],0,-2).$tempone);
						}elseif ($one == 'uuid'){
							$namefind[] = "@$one@";
							$namerepl[] = $row[$one];
							$namerepl1[] = $row['bridge_uuid'];
						}else{
							$namefind[] = "@$one@";
							$namerepl[] = $row[$one];
							$namerepl1[] = $row[$one];
						}
				}
				$filestr0 = str_replace($namefind,$namerepl,$filename);
				$filestr1 = str_replace($namefind,$namerepl1,$filename);
				$filestr = @$_SESSION['recordings_dir'].'/'.$pathstr.'/'.$filestr0;
				$filestr2 = @$_SESSION['recordings_dir'].'/'.$pathstr.'/'.$filestr1;
				$fileplay = '/files/'.$pathstr.'/'.$filestr0;
				$fileplay1 = '/files/'.$pathstr.'/'.$filestr1;
				if (file_exists($filestr) )
					$filestr = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\" <b>主叫：</b>$row[caller_id_number] <b>被叫：</b>$row[destination_number] \");$(\"#player\").attr(\"src\",\"$fileplay\");'> 【WAV语音】 </button>";
				elseif (file_exists($filestr2) )
					$filestr = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\" <b>主叫：</b>$row[caller_id_number] <b>被叫：</b>$row[destination_number] \");$(\"#player\").attr(\"src\",\"$fileplay1\");'> 【WAV语音】 </button>";
				elseif (file_exists($filestr.".mp3"))
					$filestr = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\" <b>主叫：</b>$row[caller_id_number] <b>被叫：</b>$row[destination_number] \");$(\"#player\").attr(\"src\",\"".$fileplay.".mp3\");'> 【MP3语音】 </button>";
				elseif (file_exists($filestr2.".mp3"))
					$filestr = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\" <b>主叫：</b>$row[caller_id_number] <b>被叫：</b>$row[destination_number] \");$(\"#player\").attr(\"src\",\"".$fileplay1.".mp3\");'> 【MP3语音】 </button>";
				else
					$filestr = $filestr1 = $fileplay = $fileplay1 = "";
			}else
				$answertime = "";
			$showtime = $row['start_stamp'].' 到 '.substr($row['end_stamp'],11)." <br/>$answertime 呼叫 $row[duration] 秒";
			$save_xml = ($conf['CDR_xml']=='true'?true:false);
			if ($save_xml)
				if (!empty(($row["xml"]))){
					$dtailinfo = "<br/><span onclick='\$(\"#cdr-$row[uuid]\").toggle()' style=\"cursor:pointer;\"> 【** 点击展开关闭 话单详情 **】-> </span><span id='cdr-$row[uuid]' style='display:none;'>".urldecode(htmlentities(@gzuncompress ($row["xml"]),ENT_QUOTES))."</span>";
				}else 
					$dtailinfo = "<br/><span class='smallgray smallsize-font'>无XML信息</span>";
			else 
				$dtailinfo = "";
			$showip = "<span class='smallblack smallsize-font'><b>IP: </b>$row[ip]$dtailinfo</span>";
			$bgcolor = fmod($i,2)>0?"class='bg1'":"class='bg2'";
			$i++;
			$showend = "<span class=\"smallgray smallsize-font\" style=\"cursor:pointer;\" onclick=\"showcodeL('$row[hangup_cause]',$i)\">".$Q850_label[$row['hangup_cause']]."</span> &nbsp;  <span class=\"smallgray smallsize-font\" style=\"cursor:pointer;\" onclick=\"showcodeF('$row[sip_hangup_disposition]','$row[direction]')\">".$hangup_label[$row['sip_hangup_disposition']].'</span>';
			echo "<tr $bgcolor><td width='240px'>$shownumber</td><td><div style='float:left;'><span class=\"bold14\">$row[destination_number]</span></div><div style='float:right;'>$relation</div></td><td align=center>$accountcode $domain_name $context</td><td align=center>$showtime</td><td>$extension_uuid $sip_gateway $last_app</td><td>$showcode</td><td>$showend  [$row[leg]] <br/>$filestr <span id='info$i' class=\"smallgray smallsize-font\"></span></td>";
			echo "</tr><tr><td colspan=7>".nl2br($showip)."</td></tr>";
		}
	}
$mysqli->close();
