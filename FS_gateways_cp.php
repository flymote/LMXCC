<?php
 set_time_limit(600);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
define("ESL_HOST", @$_SESSION['ESL_HOST']);
define("ESL_PORT", @$_SESSION['ESL_PORT']);
define("ESL_PASSWORD",@$_SESSION['ESL_PASSWORD']);
$lab = array("gatewayname"=>"网关的名称标识，全局唯一，使用后不可修改","realm"=>"认证的域名或服务器地址，如果默认端口不是5060，需要用冒号加上端口", "username"=>"认证的用户名","password"=>"认证的密码","register"=>"认证服务器是否需要注册，如果不设置，则设置了用户名默认true，否则默认false","from-user"=>"指定在SIP消息中的源用户信息，没有配置则默认和username相同","from-domain"=>"指定显示的域或服务器信息，它和from-user共同影响SIP中的“From”头域。","regitster-proxy"=>"需要注册到的代理服务器域名或地址，不设置则使用认证服务器信息","outbound-proxy"=>"表示呼出时指向的地址，这里其实和注册地址是一致的","expire-seconds"=>"设置注册时SIP信息的Expires字段的值，默认3600","caller-id-in-from"=>"将主叫号码（要发给对方的）放到SIP的From字段，默认是放Remote-Party-ID字段","extension"=>"设置来话显示的分机用户信息，不设置则使用认证用户名","proxy"=>"使用的代理服务器域名或地址，默认使用认证服务器信息","register-transport"=>"设置SIP信息是通过udp还是tcp通讯，默认udp","retry-seconds"=>"设置当注册超时或失败后间隔多少秒重试","contact-params"=>"设置SIP中Contact字段中的额外参数（根据具体需求而定），如：tport=tcp","ping"=>"每隔多少秒发送一个SIP OPTIONS信息，以保持连接避免被服务器注销","addon"=>"设置其他的更多参数，每个参数需按照<param name=\"参数名\" value=\"参数值\"/>的定义格式，如<param name=\"extension-in-contact\" value=\"true\"/><param name=\"max-calls\" value=\"200\"/>","variables"=>"设置在本网关通话时使用的附加变量，必须是合法的XML，而且必须是variables下的variable元素组合，如 <variables><variable name=\"来话变量名\"  value=\"来话变量值，后面direction是inbound\"  direction=\"inbound\"/><variable name=\"去话变量名\" value=\"去话变量值，后面direction是outbound\" direction=\"outbound\"/><variable name=\"变量名\" value=\"不限方向变量的值\"/></variables>");

include 'Shoudian_db.php';
//-------------------修改或添加路由信息-----------------------------------
if (isset($_GET['editGateway'])){
	$id = intval($_GET['editGateway']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_gateways where id = $id");
		$sql = "update fs_gateways set ";
		$sql_end = " where id = $id";
		$showinfo .=" id $id 更新 ";
	}else{
		$result = false;
		$tmp = implode(array_keys($lab), "`,`");
		$sql = "insert into fs_gateways (`$tmp`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 ";
	}	
	function xmlentities($string){
		$value = str_replace(array("&","<",">",'"',"'"),'_', $string);
		return $value;
	}
	if ($result)
		$row = $result->fetch_array();
	else 
		$row = array();
$html = "";
$i = 0;
$tmp = array();
if (isset($_POST['register']) && !in_array($_POST['register'],array("true","false"))){
	if (empty($_POST['username']))
		$_POST['register'] = 'false';
	else
		$_POST['register'] = 'true';
	$showinfo .= "<span class='bgblue'>是否注册 被设为 $_POST[register] </span><br/>";
}
$domain = empty($row['domain_id'])?"":"<span class='bgblue'>【域 : $row[domain_id] $row[domain_user]】</span> &nbsp; ";
foreach ($lab as $key=>$value){
	if ($i<5)
		$css = 'inputline1';
	else 
		$css = 'inputline';
	$i++;
	if (isset($_POST[$key])){
		if (in_array($key, array("expire-seconds","retry-seconds","ping"))){
			$_POST[$key] = intval($_POST[$key]);
			$showv = $v = $_POST[$key];
		}elseif ($key=='variables'){
			$result = @simplexml_load_string($_POST[$key]);
			if ($result){
				$v = $mysqli->real_escape_string($_POST[$key]);
				$showv = htmlentities($_POST[$key],ENT_QUOTES,"UTF-8");
			}else{
				$showinfo .="<span class='bgblue'>variables 不是正确的XML格式，被忽略！</span><br/>";
				$showv = $v = "";
			}
		}elseif ($key=='addon'){
				$v = $mysqli->real_escape_string($_POST[$key]);
				$showv = htmlentities($_POST[$key],ENT_QUOTES,"UTF-8");
		}else{
			$v = xmlentities($_POST[$key]);
			$v = $mysqli->real_escape_string($v);
			$showv = htmlentities($v,ENT_QUOTES,"UTF-8");
		}
		$css = 'inputline1';
	}else{
		$v = empty($row[$key])?"":$row[$key];
		$showv = htmlentities($v,ENT_QUOTES,"UTF-8");
	}
	if ($id){ 
		if ( $key !='gatewayname' ) //gatewayname 不能修改
			$tmp[] = "`$key` = ".(is_int($v)?$v:"'$v'");
	}else
		$tmp[] = (is_int($v)?$v:"'$v'");
	$value = htmlentities($value,ENT_QUOTES,"UTF-8");
	$html .= "<tr><td><em>$key ：</em><br/><input id=\"$key\" name=\"$key\" value=\"$showv\" size=45 onclick=\"this.select();\" class=\"$css\"/></td><td width=\"50%\"><span class=\"smallred smallsize-font\">$value</span></td></tr>";
}
if (!empty($_POST)){
	$submitbutton = ' <a href="?editGateway='.$id.'">刷新页面</a>';
	$sql .= implode($tmp, ","); 
	$sql  .= $sql_end;
	$result = true;
	if (empty($_POST['gatewayname'])){
		$result = false;
		$showinfo .= "<span class='bgred'>路由名称必须填写！</span><br/>";
	}elseif ($id==0){
		$validRegExp =  '/^[a-zA-Z0-9_\-~#@]+$/';
		if (!preg_match($validRegExp, $_POST['gatewayname'])) {
			$result = false;
			$showinfo .= "<span class='bgred'>路由名称不能用中文不能用引号括号等，必须是英文字母数字及 _-~#@ </span><br/>";
		}
		if ($result){
			$result = $mysqli->query("select id from fs_gateways where gatewayname = '$_POST[gatewayname]' limit 1");
			$row = $result->fetch_array();
			if (empty($row['id']))
				$result = $mysqli->query($sql);
			else{
				$showinfo .= "<span class='bgred'>路由名称已经使用，需要修改！</span>";
				$result = false;
			}
		}
	}else{
		$result = $mysqli->query($sql);
	}
	if ($result){
		$showinfo .= "<span class='bggreen'>数据操作完成！</span>";
	}else
		$showinfo .= "<span class='bgred'>数据操作失败！{$mysqli->error}</span>";
}else{
	if (empty($row['enabled'])){
		$submitbutton = ' <input type="submit" value="确认提交" onclick="return confirm(\'请谨慎操作，是否确认提交？\');"/>';
		if ($id)
			$submitbutton .= "<script>\$('#gatewayname').attr('readonly','readonly');</script>";
	}else
			$submitbutton = ' 【已启用路由不得修改】';
	$showinfo = "";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>路由详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回路由主控页</a></p>
<table class="tablegreen" width="800" align="center"><th colspan=2>$domain * 路由名称必须唯一，橙色说明的部分必须设置<br/>$showinfo</th><form method="post">
$html
<th><span class="smallred smallsize-font">*提交后即刻生效，请谨慎操作</span></th><th> $submitbutton </th>
</table></body></html>
HTML;
exit;
}
//-----------路由管理-------路由数据库 ---POST提交操作-----------------------------
//应用部署及停用，ESL
if (empty($_SESSION['POST_submit_once']) && isset($_POST['yid'])){
	if (in_array($_POST['en0'],array("88","99"))) {
		$yid = intval($_POST['yid']);
		$name = $_POST['en1'];
		require_once "detect_switch.php";
		$result = $mysqli->query("select * from fs_gateways where id = $yid and gatewayname = '$name' and `enabled`=1");
		$row = $result->fetch_array();
		$file_ = @$_SESSION['conf_dir']."/sip_profiles/external/LMX{$yid}_$name.xml";
		$_SESSION['POST_submit_once']=1;
		if ($_POST['en0']=="99" && is_file($file_)){
			$result = unlink($file_);
			if ($result){
				$info = new detect_switch();
				$info->run("api","sofia profile external killgw $name",0);
				die(" $name 路由已被停用！");
			}else 
				die("$name 路由数据无法清除，无法停用！");
		}else{
			if (!empty($row['domain_id']))
				die("本路由为域用户专用，不可单独部署！");
			$xml = "<include>\n";
			$i = 0;
			foreach ($lab as $key=>$value){
				if  ($i==0)
					$xml .= " <gateway name=\"$row[gatewayname]\">\n";
				elseif($i<5)
					$xml .= " <param name=\"$key\" value=\"" . $row[$key] . "\"/>\n";
				elseif(!empty($row[$key]))
					if ($key=='variables' || $key=='addon' )
						$xml .= "$row[$key]\n";
					else
						$xml .= " <param name=\"$key\" value=\"" . $row[$key] . "\"/>\n";
				$i++;
			}
			$xml .=	" </gateway>\n</include>";
			$result = @file_put_contents($file_, $xml);
			unset($xml);
			if ($result){
				$info = new detect_switch();
				$info->run("api","sofia profile external rescan",0);
				die(" $name 路由已被添加并更新状态！");
			}else
				die("$name 路由数据添加失败！");
		}
	}
	die("信息不完整，非法提交操作！");
}
//删除路由记录
if (!empty($_POST['delgw'])){
	$id = intval($_POST['did']);
	$mysqli->query("delete from fs_gateways where id = $id and `enabled` = 0 limit 1");
	$domain = $_POST['domain'];
	require 'FS_domains_func.php';
	domain_build($domain,99);
	die("id $id 操作完毕");
}
//获取路由信息，ESL
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['getinfo'])){
	require_once "detect_switch.php";
	$sid = $_POST['ssid'];
	$info = new detect_switch();
	$info->run('api',"sofia xmlstatus gateway $sid",1,1);
	exit;
}
//设置启用或禁用
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_gateways set `enabled` = 1 where id = $id and (domain_id is NULL or domain_id='') limit 1");
		if ($mysqli->affected_rows)
			die("id $id 设置为可用完毕");
		else 
			die("id $id 设置失败，是不是已经被域用户使用了？");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_gateways set `enabled` = 0 where id = $id limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示----------路由数据库 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script>
function del(sid,domainid){var dd;if (domainid !='') dd=\" \\r\\n业务中断警告： \\r\\n本网关有域用户使用，删除网关将会停止此域的使用！而导致业务中断！务必谨慎！\\r\\n\"; else dd= \"\\r\\n\";var a = confirm(\"删除操作不可撤销！\"+dd+\"你确认提交？\");if (a) { \$.post( \"FS_gateways_cp.php\", { did: sid, delgw: \"1\",domain:domainid })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"FS_gateways_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"FS_gateways_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en88(sid,lab){\$.post( \"FS_gateways_cp.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"FS_gateways_cp.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
function getinfo(sid,iid){\$.post( \"FS_gateways_cp.php\", { ssid: sid, getinfo: \"1\" })
  .done(function( data ) { alert('OK，请查看页面显示！');$('#gwinfo'+iid).html(data);$('#btn'+iid).val('已获取信息');$('#btn'+iid).attr('disabled','disabled'); });}
</script></head><body>";
$where = " where 1 ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['gwname'])){
	$temp = $mysqli->real_escape_string($_GET['gwname']);
	$where .= " and `gatewayname` like '%$temp%' ";
	$showget .=" 路由名称包含 '$temp' ";
}
if (!empty($_GET['gwrealm'])){
	$temp = $mysqli->real_escape_string($_GET['gwrealm']);
	$where .= " and `realm` like '%$temp%' ";
	$showget .=" 服务器地址包含 '$temp' ";
}
$count = 20;
$getstr = "";
$totle = $mysqli->query("select count(*) from fs_gateways $where");
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
	$showget .= " （$totle 条，$pages 页）</span>";
	echo '<p class="pcenter" style="font-size:18pt;">网关管理控制台 '.$showget.'  <a style="font-size:12pt;" href="?editGateway=0">【新建网关】</a><a style="font-size:10pt;" href="main.php">返回主控</a></p><table class="tablegreen" width="90%" align="center"><tr><th colspan=6><form method="get">路由名称：<input id="gwname" name="gwname" value="" size=10> 服务器：<input id="gwrealm" name="gwrealm" value="" size=10> <input type="submit" value="确认"> <a href="?">【看全部】</a>	</form></th></tr>';
	$result = $mysqli->query("select * from fs_gateways $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=6 align=center><span class="smallred smallsize-font"> *路由新建后默认被禁用，在本页面启用后可全局应用；被禁用的路由才可被域用户使用！！</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			$fromuser ="";
			$domain = empty($row['domain_user'])?"全局可用":" 用户: $row[domain_id] $row[domain_user] ";
			if ($row['enabled']){
				$file_ = @$_SESSION['conf_dir']."/sip_profiles/external/LMX$row[id]_$row[gatewayname].xml";
				if (is_file($file_)){
					$showalert= ' <span class="bggreen">已应用 </span> &nbsp; <em class=\'red\'>'.$row['gatewayname'].'</em>';
					$showtools="<input type='button' id='btn$row[id]' onclick=\"this.value='连接中，请等待反馈...';getinfo('$row[gatewayname]',$row[id]);\" value='获取信息'/> &nbsp;  <input type='button' onclick=\"this.value='连接中，请等待反馈...';en99($row[id],'$row[gatewayname]')\" value='停用'/>";
				}else{
					$showalert= ' <span class="bgblue">已停用 </span> &nbsp; <em class=\'red\'>'.$row['gatewayname'].'</em>';
					$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';en88($row[id],'$row[gatewayname]')\" value='部署应用'/> &nbsp;  <input type='button' onclick=\"en0($row[id])\" value='禁止'/>";
				}
			}else 
				$showalert= ' <span class="bgred">已禁止 </span> &nbsp; <em class=\'red\'>'.$row['gatewayname'].'</em>';
			$showuser = "";
			if ($row['username'])
				$showuser .= "用户名：<strong>$row[username]</strong> ";
			else
				$showuser .= "<span class=\"smallgray smallsize-font\">无认证用户信息</span>";
			if ($row['password'])
				$showuser .= " 密码：<strong>$row[password]</strong>";
			if ($row['from-user'])
				$fromuser .= "From用户名：<strong>".$row["from-user"]."</strong>";
			else
				$fromuser .= "<span class=\"smallgray smallsize-font\">无From-user信息</span>";
			if ($row['from-domain'])
				$fromuser .= " From域：<strong>".$row['from-domain']."</strong>";
			if ($row['register']=='true')
				$row['realm'] .= " &nbsp; <span class=\"smallblue smallsize-font\">[ 使用注册 ]</span>";
			else
				$row['realm'] .= " &nbsp;  <span class=\"smallblack smallsize-font\">[ IP对接 ]</span>";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr $bgcolor><td>$showalert</td><td>$domain</td><td>服务器：<strong>$row[realm]</strong><span id='gwinfo$row[id]'></span></td><td> $showuser </td><td>$fromuser</td><td><a href='?editGateway=$row[id]'>详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>";
			if ($row['enabled']){
				echo $showtools;
			}else{
				$ddd = empty($row['domain_user'])?"":$row['domain_id'];
				echo " <button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id],'$ddd')\">删除</button>";
			}
			echo "</span></td></tr>";
		}
	}
$mysqli->close();