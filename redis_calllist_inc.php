<?php
header("refresh:2;");
$redis = redisDB();
if (empty($_GET['users']) && empty($_GET['account']))
	die("<p align='center'>请先指定坐席，以同步其通话动态...</p>");
elseif (isset($_GET['account'])){
	$account = $_GET['account'];
	$auto = @$_GET['auto'];
	$user = "$_GET[account]@$_SESSION[domainid]";
}else{
	$user = $_GET['users'];
	$account = $auto = 0;
}
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script src=\"css/blink.js\"></script></head>
<body><script>
$(document).ready(function() {  
  $('.blink').blink({delay: 300});
}); ";
if ($account)
	echo "function gopage(a){top.document.location.href='DM_crm.php?account=$account&callto='+a;}";
echo "</script>";
echo '<p class="pcenter" style="font-size:18pt;">最近通话列表 '.$user.'   &nbsp;  &nbsp; <a style="font-size:10pt;" href="javascript:history.go(-1);">返回</a></p><table class="tablegreen" width="90%" align="center"><tr><th>时间</th><th>主叫号码</th><th>主叫OrigID</th><th>目的号码</th><th>被叫号码</th></tr>';
$result = $redis->lRange($user, 0, -1);
$i=0;
if (!empty($account)) //DM_crm.php
	$showlink = 1;
else 
	$showlink = 0;

function getlink($str,$limit =7){
	global $account;
	if ( $account != $str && strlen($str) >$limit )
		return "<a href=\"javascript:gopage('$str');\">$str</a>";
		else return $str;
}
function autopage($results){
	global $account;
	$phone = 0;
	if ($account != $results['callerOId']){
		$co = intval($results['callerOId']);
		if ($co)
			$phone = $co;
	}
	if ($account != $results['callerId']){
		$co = intval($results['callerId']);
		if ($co > $phone)
			$phone = $co;
	}
	if ($account != $results['toId']){
		$co = intval($results['toId']);
		if ($co > $phone)
			$phone = $co;
	}
	if ($account != $results['calleeId']){
		$co = intval($results['calleeId']);
		if ($co > $phone)
			$phone = $co;
	}
	if (empty($_SESSION['__refresh_for_phone_once__']) || $_SESSION['__refresh_for_phone_once__'] != $phone){
		$_SESSION['__refresh_for_phone_once__'] = $phone;
		echo "<script>top.document.location.href='DM_crm.php?account=$account&auto=1&callto=$phone';</script>";
	}
}
	
foreach ($result as $row) {
		$bgcolor = fmod($i,2)>0?"class='bg1'":"class='bg2'";
		$results = $redis->hMget("c_$row",['eventTime', 'state', 'callerId', 'callerOId', 'calleeId', 'toId']);
		//$results['state']=array_rand(["DOWN"=>0,"DIALING"=>0,"RINGING"=>0,"EARLY"=>0,"ACTIVE"=>0,"HELD"=>0 ,"RING_WAIT"=>0,"HANGUP"=>0,"UNHELD"=>0]);
//"DOWN", CCS_DOWN},{"DIALING", CCS_DIALING},{"RINGING", CCS_RINGING},{"EARLY", CCS_EARLY},{"ACTIVE", CCS_ACTIVE},{"HELD", CCS_HELD},{"RING_WAIT", CCS_RING_WAIT},{"HANGUP", CCS_HANGUP},{"UNHELD", CCS_UNHELD},{NULL, 0}
		switch ($results['state']){
			case "RINGING": $info = "<span class='blink bgblue'>振铃中...</span>"; if ($auto) autopage($results); break;
			case "EARLY":	 $info = "<span class='blink bgblue'>呼叫中...</span>"; if ($auto) autopage($results); break;
			case "ACTIVE":	 $info = "<span class='blink bggreen'>通话中...</span>";break;
			case "HELD":	 $info = "<span class='blink bggreen'>通话挂起</span>";break;
			case "UNHELD":	 $info = "<span class='blink bggreen'>取消挂起</span>";break;
			case "HANGUP":	 $info = "<span class='bggray'>已挂机</span>";break;
			case "DOWN":	 $info = "<span class='bgred'>断开</span>";break;
			default: $info =  "<span class='bggray'> --- </span>";
		}
		if ($i>199){
			$redis->unlink("c_$row");
			$info .= "<span class='bgred'>数据已删</span>";
		}
		if ($showlink){
			$results['callerId'] = getlink($results['callerId']);
			$results['callerOId'] = getlink($results['callerOId']);
			$results['toId'] = getlink($results['toId']);
			$results['calleeId'] = getlink($results['calleeId']);
		}
		echo "<tr $bgcolor><td>".date("Y-m-d H:i:s",$results['eventTime'])." &nbsp; $info</td><td>$results[callerId]</td><td>$results[callerOId]</td><td>$results[toId]</td><td>$results[calleeId]</td></tr>";
	    $i++;
	}
	$info = "* 仅保留不超过200个通话记录";
	if ($i>199)
		$redis->ltrim($user,0,199);
	die('<tr><td colspan=5 align=center>'.$info.'</td></tr></table></body></html>');
