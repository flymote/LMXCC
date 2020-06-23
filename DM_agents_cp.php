<?php
define('APPID', 'lmxcc');
set_time_limit(60);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['lmxccusers']) || empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");
$domainid = $_SESSION['domainid'];
include_once 'DM_db.php';
$showinfo ="本处将组内成员按设定参数加入坐席队列！重复加入即更新";
$adm_level = is_adm();
if ($adm_level<5)
	die("没有权限！");
if (!empty($_POST)){
	if (!empty($_POST['aid']) && !empty($_POST['del'])){   //ESL 删除坐席
		require_once "detect_switch.php";
		$info = new detect_switch();
		$info->run("api","callcenter_config agent del $_POST[aid]",0);
		$info->run("api","callcenter_config tire del agents@$domainid $_POST[aid]",0);
		die(" agents@$domainid $_POST[aid] 坐席删除！");
	}
	if ( !empty($_POST['group_user'])){  //ESL 按组添加坐席
		$level = intval($_POST['level']);
		$level = $level?$level:1;
		$maxnoanswer = intval($_POST['max-no-answer']);
		$maxnoanswer = $maxnoanswer?$maxnoanswer:3;
		$wrapuptime = intval($_POST['wrap-up-time']);
		$wrapuptime = $wrapuptime?$wrapuptime:10;
		$rejectdelaytime = intval($_POST['reject-delay-time']);
		$rejectdelaytime = $rejectdelaytime?$rejectdelaytime:10;
		$busydelaytime = intval($_POST['busy-delay-time']);
		$busydelaytime = $busydelaytime?$busydelaytime:60;
		$noanswerdelaytime = intval($_POST['no_answer_delay_time']);
		$noanswerdelaytime = $noanswerdelaytime?$noanswerdelaytime:20;
		$group_user = explode(',', $_POST['group_user']);
		if ($group_user){
			$add = $upd = 0;
			$fsdb = freeswitchDB();
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type content=text/html;charset=utf-8"/> <link rel="stylesheet" type="text/css" href="css/main.css"/></head><html><body>';
			$ext_result = $fsdb->query("SELECT `name` FROM agents where `name` like '%@$domainid'");
			$agents = [];
			while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
				if (!$row0) break;
				$agents[]= $row0[0];
			}
			require_once "detect_switch.php";
			$info = new detect_switch();
			foreach ($group_user as $one){
				$one = "$one@$domainid";
				if (in_array($one, $agents)){
					$upd++;
					$r = $fsdb->query("update agents set `contact`= 'user/$one',`max_no_answer`= $maxnoanswer,`wrap_up_time`= $wrapuptime,`reject_delay_time`= $rejectdelaytime,`busy_delay_time`= $busydelaytime,`no_answer_delay_time`= $noanswerdelaytime where `name`= '$one'");
					if ($r){
						$info->run("api","callcenter_config agent reload $one ");
						$r = $fsdb->query("update  tiers set `level`= $level where `agent`= '$one'");
						if (!$r)
							$info->run("api","callcenter_config tier set level agents@$domainid $one $level");
					}
				}else{
					$add++;
					$r = $fsdb->query("insert into agents ( `name`,`system`,`type`,`contact`,`status`,`state`,`max_no_answer`,`wrap_up_time`,`reject_delay_time`,`busy_delay_time`,`no_answer_delay_time` )values('$one','single_box','callback','user/$one','Logged Out','Waiting',$maxnoanswer,$wrapuptime,$rejectdelaytime,$busydelaytime,$noanswerdelaytime)");
					if ($r){
						$info->run("api","callcenter_config agent reload $one ");
						$r = $fsdb->query("insert into tiers ( `queue`,`agent`,`state`,`level`,`position` )values('agents@$domainid','$one','Ready',$level,1)");
						if (!$r)
							$info->run("api","callcenter_config tier add agents@$domainid $one $level");
					}else{ //数据库失败
						$info->run("api","callcenter_config agent add $one callback");
						$info->run("api","callcenter_config agent set contact $one user/$one");
						$info->run("api","callcenter_config tier add agents@$domainid $one");
					}
				}
				if (!$r){ //数据库失败
					$info->run("api","callcenter_config agent set max_no_answer $one $maxnoanswer");
					$info->run("api","callcenter_config agent set wrap_up_time $one $wrapuptime");
					$info->run("api","callcenter_config agent set reject_delay_time $one $rejectdelaytime");
					$info->run("api","callcenter_config agent set busy_delay_time $one $busydelaytime");
					$info->run("api","callcenter_config agent set no_answer_delay_time $one $noanswerdelaytime");
					$info->run("api","callcenter_config tier set level agents@$domainid $one $level");
				}
			}
			$info->run("api","callcenter_config queue reload agents@$domainid");
			die("<p class='pcenter bggreen'>将 添加坐席 $add 个，更新坐席 $upd 个  处理完毕！</p><br/><p class='pcenter'><a href='?'>返回</a></p></body></html>");
		}else
			$showinfo .= "<span class='bgred'>提交的成员不正确，不能添加坐席！</span>";
	}
}else
	$showinfo .= "";

$ext_result = $mysqli->query("select `group_name`,`group_id` from fs_groups where `enabled`=1 and `domain_id`='$domainid'");
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$groups[$domainid][] = $row0;
}
$groups[$domainid][] = ['未分组',0];

$ext_result = $mysqli->query("select `user_name`,`user_id`,`domain_id`,`group_id` from fs_users where `enabled`=1 and `domain_id`='$domainid'");
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
$ext_result = $mysqli->query("SELECT `agent`,`level`,`max_no_answer`,`no_answer_delay_time`,`wrap_up_time`,`reject_delay_time`,`busy_delay_time` FROM tiers t LEFT JOIN agents a ON t.agent=a.name where  `queue` = 'agents@$domainid' ");
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$tiers[$row0[1]][]= [$row0[0],"最多$row0[2]次无应答被置忙，无应答后延迟$row0[3]秒，通话后延迟$row0[4]秒，拒接后延迟$row0[5]秒，遇忙后延迟$row0[6]秒"];
}
$html = '';
$tier_html ='';
if (!empty($tiers)){
	ksort($tiers);
	foreach ($tiers as $this_level=>$tier){
		$tier_html .= "<ul style='padding:5px;'>坐席级别 <span class='orange'>$this_level</span>";
		$i = 1;
		$br = "";
		foreach ($tier as $this_pos=>$agent){
			$n = explode("@", $agent[0]);
			$info = $agent[1];
			if (isset($n[1]) && isset($users[$n[1]][$n[0]])){
				$agent = $users[$n[1]][$n[0]];
				$aid =  "$n[0]@$n[1]";
			}else{
				$aid =   $agent[0];
				$agent = $agent[0];
			}
			$a = str_replace("@", "", $aid);
			$tier_html .= "<li>&nbsp; <span id='i$a' class='bold12'><div style='float:left;width:300px;text-align:left;padding-left:10px;'>$agent </div> <span class='smallgray smallsize-font'>$info</span>&nbsp;<span style=\"cursor:pointer;color:red;\" onclick=\"del('$agent','$aid','$a')\" title=\"删除坐席\">&otimes;</span></span></li>";
		}
		$tier_html .= "</ul>";
	}
}else
	$tier_html .="<p class='pcenter red'>没有配置坐席！</p>";
	
	$html .= "<tr><td colspan=\"2\"><span class=bold12>坐席列表：</span>$tier_html</td></tr><tr><td colspan=\"2\"><span class=bold12>分组列表：</span></td></tr>";
foreach ($groups as $k => $v){
	$i=0;
	foreach ($v as $g){
		$i++;
		$disabled = "";
		$bgcolor = fmod($i,2)>0?"class='bg1'":"class='bg2'";
		if (isset($gusers[$g[1]])){
			$guser = implode(',', $gusers[$g[1]]);
			$a = count($gusers[$g[1]]);
		}else{
			$guser = '';
			$a = '没有';
			$disabled = "disabled=\"disabled\"";
		}
		$html .="<tr $bgcolor><td>".($g[1]?'<span class="bgblue">组':'<span class="bggray">　')."</span> &nbsp; <span class=bold12>&bull;  $g[0] </span> &nbsp; &nbsp; &nbsp;  <strong>$a 成员</strong>  &nbsp;  <a href=\"DM_users_cp.php?gid=$g[1]\"> &raquo; 查看成员</a></td><td> <form method='post'><input name=\"domain_id\" value=\"$k\" type=\"hidden\">
 <input name=\"group_id\" value=\"$g[1]\" type=\"hidden\"><input name=\"group_user\" value=\"$guser\" type=\"hidden\">
最多<input id=\"max-no-answer\" name=\"max-no-answer\" placeholder=\"3\" style=\"width:15px;\">次无应答被置忙，
无应答后延迟 <input id=\"no_answer_delay_time\" name=\"no_answer_delay_time\" placeholder=\"20\" style=\"width:20px;\">秒，
通话后延迟 <input id=\"wrap-up-time\" name=\"wrap-up-time\" placeholder=\"10\" style=\"width:20px;\">秒，
拒接后延迟 <input id=\"reject-delay-time\" name=\"reject-delay-time\" placeholder=\"10\" style=\"width:20px;\">秒，
遇忙后延迟 <input id=\"busy-delay-time\" name=\"busy-delay-time\" placeholder=\"60\" style=\"width:20px;\">秒，
级别 <input id=\"level\" name=\"level\" placeholder=\"1\" style=\"width:20px;\"> <button type=\"submit\" $disabled>加坐席</button></form></td></tr>";
	}
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script><script>
function del(showname,aid,eid){var a = confirm("警告！！\\n本操作会删除 "+showname+" 坐席全部设置，不可撤销！！\\n你确认提交？");if (a) { $.post("DM_agents_cp.php", { aid:aid,del:"1" })
  .done(function( data ) { alert( "删除成功！" + data);$('#i'+eid).html('--  已删除  -- ' + data); });} }
</script>
</head><body><p class='pcenter' style='font-size:18pt;'>坐席配置</p>
<table class="tablegreen" width="1100" align="center"><th colspan=2><span class=red>$showinfo</span>
</th>
$html
HTML;
echo "</table></body></html>";