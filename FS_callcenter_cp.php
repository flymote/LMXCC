<?php
set_time_limit(600);
session_start();
date_default_timezone_set('Asia/Shanghai');
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
if(!empty($_SESSION['POST_submit_once']) && !empty($_POST)){
	$_SESSION['POST_submit_once'] = 0;
	unset($_POST);
}
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['ESL_HOST']) || empty($_SESSION['conf_dir']))
	die("请正常登录使用！不允许直接进行操作");

include 'Shoudian_db.php';
$showinfo ="";
	define("ESL_HOST", $_SESSION['ESL_HOST']);
	define("ESL_PORT", $_SESSION['ESL_PORT']);
	define("ESL_PASSWORD",$_SESSION['ESL_PASSWORD']);
if (!empty($_POST)){
	if (!empty($_POST['domainid']) && !empty($_POST['aid']) && !empty($_POST['del'])){   //ESL 删除坐席
		require_once "detect_switch.php";
		$info = new detect_switch();
		$info->run("api","callcenter_config agent del  $_POST[aid]",0);
		$info->run("api","callcenter_config tire del  agents@$_POST[domainid] $_POST[aid]",0);
		die(" agents@$_POST[domainid] $_POST[aid] 坐席删除！");
	}
	if (!empty($_POST['domain_id']) && !empty($_POST['group_id']) && !empty($_POST['group_user'])){  //ESL 按组添加坐席
		$level = intval($_POST['level']);
		$level = $level?$level:1;
		$maxnoanswer = intval($_POST['max-no-answer']);
		$maxnoanswer = $maxnoanswer?$maxnoanswer:3;
		$wrapuptime = intval($_POST['wrap-up-time']);
		$wrapuptime = $wrapuptime?$wrapuptime:10;
		$rejectdelaytime = intval($_POST['reject-delay-time']);
		$rejectdelaytime = $rejectdelaytime?$rejectdelaytime:10;
		$busydelaytime = intval($_POST['busy-delay-time']);
		$busydelaytime = $busydelaytime?$busydelaytime:20;
		$noanswerdelaytime = intval($_POST['no_answer_delay_time']);
		$noanswerdelaytime = $noanswerdelaytime?$noanswerdelaytime:20;
		$vars = empty($_POST['vars'])?"[leg_timeout=10,call_timeout=10]":"[{$_POST['vars']}]";
		$domainid = $_POST['domain_id'];
		$group_user = explode(',', $_POST['group_user']);
		if ($group_user){
			$add = $upd = 0;
			$fsdb = freeswitchDB();
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type content=text/html;charset=utf-8"/> <link rel="stylesheet" type="text/css" href="css/main.css"/></head><html><body>';
			$ext_result = $fsdb->query("SELECT `name` FROM agents ");
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
					$r = $fsdb->query("update agents set `contact`= '{$vars}user/$one',`max_no_answer`= $maxnoanswer,`wrap_up_time`= $wrapuptime,`reject_delay_time`= $rejectdelaytime,`busy_delay_time`= $busydelaytime,`no_answer_delay_time`= $noanswerdelaytime where `name`= '$one'");
					if ($r){
						$info->run("api","callcenter_config agent reload $one ");
						$r = $fsdb->query("update  tiers set `level`= $level where `agent`= '$one'");
						if ($r)
							$info->run("api","callcenter_config tier reload agents@$domainid $one");
					}
				}else{
					$add++;
					$r = $fsdb->query("insert into agents ( `name`,`system`,`type`,`contact`,`status`,`state`,`max_no_answer`,`wrap_up_time`,`reject_delay_time`,`busy_delay_time`,`no_answer_delay_time` )values('$one','single_box','callback','{$vars}user/$one','Logged Out','Waiting',$maxnoanswer,$wrapuptime,$rejectdelaytime,$busydelaytime,$noanswerdelaytime)");
					if ($r){
						$info->run("api","callcenter_config agent reload $one ");
						$r = $fsdb->query("insert into tiers ( `queue`,`agent`,`state`,`level`,`position` )values('agents@$domainid','$one','Ready',$level,1)");
						if ($r)
							$info->run("api","callcenter_config tier reload agents@$domainid $one");
					}else{ //数据库失败
						$info->run("api","callcenter_config agent add $one callback");
						$info->run("api","callcenter_config agent set contact $one {$vars}user/$one");
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

$ext_result = $mysqli->query("select `domain_name`,`domain_id` from fs_domains where `enabled`=1 order by id desc");
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$domains[$row0[1]] = $row0[0];
}
$ext_result = $mysqli->query("select `group_name`,`group_id`,`domain_id` from fs_groups where `enabled`=1 order by `domain_id`");
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$groups[$row0[2]][] = $row0;
}
$ext_result = $mysqli->query("select `user_name`,`user_id`,`domain_id`,`group_id` from fs_users where `enabled`=1");
$users = $gusers = [];
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$users[$row0[2]][$row0[1]] = $row0[0];
	$group_ = explode(",", $row0[3]);
	if ($group_)
	foreach ($group_ as $a)
		if ($a)
		$gusers[$a][] = $row0[1];
}
$mysqli = freeswitchDB();
$ext_result = $mysqli->query("SELECT `queue`,`agent`,`level`,`status` FROM tiers t LEFT JOIN agents a ON t.agent=a.name");
while (($row0 = $ext_result->fetch_array(MYSQLI_NUM))!==false) {
	if (!$row0) break;
	$tiers[$row0[0]][$row0[2]][]= [$row0[1],$row0[3]];
}

$html = '';
foreach ($groups as $k => $v){
	if (!isset($domains[$k]))
		$domains[$k] = "本域未启用";
	$tier_html ='';
	if (isset($tiers["agents@$k"])){
		ksort($tiers["agents@$k"]);
		foreach ($tiers["agents@$k"] as $this_level=>$tier){
			$tier_html .= "<ul style='padding:5px;'>level <span class='orange'>$this_level</span><li>";
			$i = 1;
			$br = "";
			foreach ($tier as $this_pos=>$agent){
				$css = 'bggreen';
				$n = explode("@", $agent[0]);
				if (empty($agent[1]) || $agent[1]=='Logged Out')
					$css = 'bggray';
				elseif ($agent[1]=='On Break')
					$css = 'bgblue';
				if (isset($n[1]) && isset($users[$n[1]][$n[0]])){
					$agent = $users[$n[1]][$n[0]];
					$aid =  "$n[0]@$n[1]";
				}else{
					$aid =   $agent[0];
					$agent = $agent[0];
				}
				$a = str_replace("@", "", $aid);
				$tier_html .= "&nbsp; <span id='i$a' class='$css'>&nbsp; $agent &nbsp;<span style=\"cursor:pointer;\" onclick=\"del('$agent','$k','$aid','$a')\">&otimes;</span></span>";
				if ($i>10){
					$tier_html .= "</li><li>";
					$i = 1;
				}else{
					$i++;
				}
			}
			$tier_html .= "</li></ul>";
		}
	}else 
			$tier_html .="<p class='pcenter red'>$domains[$k]  没有配置坐席！</p>";

	$html .= "<tr><td colspan=\"2\" style=\"background:#decedd;\" class=\"blod14\">域 <span class=bold16> $domains[$k] </span> <em>$k</em>$tier_html</td></tr>";
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
		$html .="<tr $bgcolor><td class=\"blod12\"> &nbsp; &bull; <span class=bold12> $g[0] </span> <span class='smallgray smallsize-font'>$g[1]</span> &nbsp; &nbsp; &nbsp;  <strong>$a 成员</strong>  &nbsp;  <a href=\"FS_users_cp.php?gid=$g[1]\"> &raquo; 查看成员</a></td><td> <form method='post'><input name=\"domain_id\" value=\"$k\" type=\"hidden\">
 <input name=\"group_id\" value=\"$g[1]\" type=\"hidden\"><input name=\"group_user\" value=\"$guser\" type=\"hidden\">
 <input id=\"max-no-answer\" name=\"max-no-answer\" placeholder=\"无应答数,3\" style=\"width:65px;\">
<input id=\"no_answer_delay_time\" name=\"no_answer_delay_time\" placeholder=\"无应答延迟,20\" style=\"width:85px;\">
 <input id=\"wrap-up-time\" name=\"wrap-up-time\" placeholder=\"分配延迟,10\" style=\"width:72px;\">
 <input id=\"reject-delay-time\" name=\"reject-delay-time\" placeholder=\"拒接延迟,10\" style=\"width:72px;\">
 <input id=\"busy-delay-time\" name=\"busy-delay-time\" placeholder=\"忙延迟,20\" style=\"width:58px;\">
 <input id=\"vars\" name=\"vars\" placeholder=\"leg_timeout=10,call_timeout=10\" style=\"width:190px;\">
 <input id=\"level\" name=\"level\" placeholder=\"级别,1\" style=\"width:40px;\"> <input type=\"submit\" $disabled value=\"加坐席\" /></form></td></tr>";
	}
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script><script>
function del(showname,domainid,aid,eid){var a = confirm("警告！！\\n本操作会删除 "+showname+" 坐席全部设置，不可撤销！！\\n你确认提交？");if (a) { $.post("FS_callcenter_cp.php", { domainid:domainid,aid:aid,del:"1" })
  .done(function( data ) { alert( "删除成功！" + data);$('#i'+eid).html(''); });} }
</script>
</head><body><p class='pcenter' style='font-size:18pt;'>callcenter 呼叫中心配置控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="1100" align="center"><th colspan=2>* 本配置文件是callcenter的基于域队列的配置，用于控制各个域坐席的话务接入行为<br/>域的呼叫和域的组、用户等，请在域、组、用户相应管理单元进行管理；本处为callcenter的管理<br/>按用户组添加坐席组，坐席不可重复！坐席状态色：<span class=bggray> 离线 </span> &nbsp; <span class=bgblue> 置忙 </span> &nbsp; <span class=bggreen> 可用 </span><br/><span class=red>$showinfo</span>
</th>
$html
HTML;
echo "</table></body></html>";