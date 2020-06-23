<?php
define('APPID', 'lmxcc');
set_time_limit(60);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['lmxccusers']) || empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");

include_once 'DM_db.php';
$domainid = $_SESSION['domainid'];
if (!empty($_POST)){
	if ( !empty($_POST['aid']) && !empty($_POST['del'])){   //ESL 删除坐席
		require_once "detect_switch.php";
		$info = new detect_switch();
		$info->run("api","callcenter_config agent del  $_POST[aid]",0);
		$info->run("api","callcenter_config tire del  agents@$domainid $_POST[aid]",0);
		die(" agents@$domainid $_POST[aid] 坐席删除！");
	}
}

$ext_result = $mysqli->query("select `user_name`,`user_id`,`domain_id`,`group_id` from fs_users where `enabled`=1 and `domain_id`='$_SESSION[domainid]'");
$users = $gusers = [];
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$users[$row0[2]][$row0[1]] = $row0[0];
	if ($row0[3]){
		$group_ = explode(",", $row0[3]);
		if ($group_)
			foreach ($group_ as $a)
				if ($a)
				$gusers[$a][] = $row0[1];
	}else 
		$gusers[0][] = $row0[1];
}
$mysqli = freeswitchDB();
$ext_result = $mysqli->query("SELECT `agent`,`level`,`status`,a.`state`,`last_bridge_end`,`last_status_change`,`no_answer_count`,`calls_answered` FROM tiers t LEFT JOIN agents a ON t.agent=a.name where  `queue` = 'agents@$_SESSION[domainid]' ");
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$date1 = $row0[4]?date("Y-m-d H:i:s",$row0[4]):"--";
	$date2 = $row0[5]?date("Y-m-d H:i:s",$row0[5]):"--";
	$tiers[$row0[1]][]= [$row0[0],$row0[2],"状态：<b>$row0[3]</b> &nbsp; 挂机：$date1 ，状态更新：$date2 ，应答数：$row0[7] ，未应答数：$row0[6]"];
}
$html = '';
	$tier_html ='';
	if (isset($tiers)){
		ksort($tiers);
		foreach ($tiers as $this_level=>$tier){
			$tier_html .= "<ul style='padding:5px;'>坐席级别 <span class='orange'>$this_level</span>";
			$i = 1;
			$br = "";
			foreach ($tier as $this_pos=>$agent){
				$n = explode("@", $agent[0]);
				$info = $agent[2];
				if (empty($agent[1]) || $agent[1]=='Logged Out')
					$css = 'bgred';
				elseif ($agent[1]=='On Break')
					$css = 'bgblue';
				elseif ($agent[1]=='Available')
					$css = 'bggreen';
				else
					$css = 'bggray';
				if (isset($n[1]) && isset($users[$n[1]][$n[0]])){
					$agent = $users[$n[1]][$n[0]];
					$aid =  "$n[0]@$n[1]";
				}else{
					$aid =   $agent[0];
					$agent = $agent[0];
				}
				$a = str_replace("@", "", $aid);
				$tier_html .= "<li>&nbsp; <span id='i$a' ><div style='float:left;width:300px;text-align:left;padding-left:10px;' class='bold12 $css'>$agent </div> <span class='smallgray smallsize-font'>$info</span>&nbsp;<span style=\"cursor:pointer;color:red;\" onclick=\"del('$agent','$aid','$a')\" title=\"删除坐席\">&otimes;</span></span></li>";
			}
			$tier_html .= "</ul>";
		}
	}else 
			$tier_html .="<p class='pcenter red'>  没有配置坐席！&nbsp; &nbsp; &nbsp; <a href='DM_agents_cp.php' style='font-size:10pt;'>配置坐席</a></p>";

			$html .= "<tr><td colspan=\"2\" style=\"background:#decedd;\" class=\"blod14\">域 <em>$domainid</em>  &nbsp; &nbsp; 标注：<span class=bggreen>接收呼叫</span> &nbsp; <span class=bgblue>停止接收</span> &nbsp; <span class=bggray>将要停止</span> &nbsp; <span class=bgred>没有登录</span> $tier_html</td></tr>";
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/><meta http-equiv="refresh" content="10">
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script><script>
function del(showname,aid,eid){var a = confirm("警告！！\\n本操作会删除 "+showname+" 坐席全部设置，不可撤销！！\\n你确认提交？");if (a) { $.post("DM_agents_cp.php", { aid:aid,del:"1" })
  .done(function( data ) { alert( "删除成功！" + data);$('#i'+eid).html(''); });} }
</script>
</head><body><p class='pcenter' style='font-size:18pt;'>坐席状态 &nbsp; &nbsp; &nbsp; &nbsp; <a href="?" style="font-size:10pt;">每10秒自动刷新，点击立即刷新</a></p>
<table class="tablegreen" width="1100" align="center">$html
HTML;
echo "</table></body></html>";