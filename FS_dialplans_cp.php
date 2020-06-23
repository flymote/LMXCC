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
include 'Shoudian_db.php';
//-------------------修改或添加DIAL信息-----------------------------------
$break_list = "<option value=''>默认设置</option>
<option value='on-false'>匹配失败时停止 on-false</option>
<option value='on-true'>匹配成功时停止 on-true</option>
<option value='always'>总是停止 always</option>
<option value='never'>永不停止 never</option>
</select>";
$condition_list = "<option value=''>不设置条件</option>
<optgroup label='常用作用域 --- --- --- ---  --- --- --- ---  --- --- ---'>
	<option value='destination_number'>被叫号码</option>
	<option value='caller_id_name'>主叫名称（外显）</option>
	<option value='caller_id_number'>主叫号码</option>
	<option value='network_addr'>主叫IP</option>
	<option value='username'>用户名</option>
	<option value='rdnis'>被转移的号码（在呼叫转移中设置）</option>
	<option value='context'>当前Context</option>
	<option value='direction'>呼叫方向（inbound outbound）</option>
	<option value='state'>通道的状态（CS_NEW CS_RESET等）</option>
	<option value='bridge_hangup_cause'>呼叫结束原因（NO_ANSWER等）</option>
	<option value='dialplan'>Dialplan模块（XML inline enum等）</option>
	<option value='uuid'>通道的UUID</option>
	<option value='source'>呼叫源（mod_sofia mod_portaudio等）</option>
	<option value='channel_name'>通道名（sofia/1000）</option>
<option value='regex-any'>正则任意匹配（格式：变量===值 一行一个，可多个）</option>
<option value='regex-all'>正则全部匹配（格式：变量===值 一行一个，可多个）</option>
<option value='regex-xor'>正则异或匹配（格式：变量===值 一行一个，可多个）</option>
<option value='specify-var'>自定义条件（格式：变量名===值）</option>
</optgroup>
<optgroup label='时间 --- --- --- ---  --- --- --- ---  --- --- --- ---  --- ---'>
	<option value='time-of-day'>一天中的时间，如08:00:00-09:00:00</option>
	<option value='date-time'>日期时间，如2010-10-01 00:00:01~2010-10-15 23:59:59</option>
	<option value='hour'>小时，0-23</option>
	<option value='minute'>分，0-59</option>
	<option value='minute-of-day'>一天中第几分，1-1440</option>
	<option value='mday'>日，1-31</option>
	<option value='mweek'>本月第几周，1-6</option>
	<option value='mon'>月，1-12</option>
	<option value='yday'>一年中第几天，1-366</option>
	<option value='year'>年，4位</option>
	<option value='wday'>一周中第几天，1-7 周日为1</option>
	<option value='week'>一年中第几周，1-53</option>
</optgroup>
</select>";

$ext_result = $mysqli->query("select `ext-name`,`id` from fs_extensions ORDER BY `enabled` DESC,`ext-level`");
$exts = result_fetch_all($ext_result);
$ext_result = "<option value=''>请选择..</option>";
foreach ($exts as $one)
	$ext_result .= "<option value='$one[1]'>[$one[1]] $one[0]</option>";
$ext_result = "<select name='extid' id='extid' class='inputline1'>$ext_result</select>";

$gateway_result = $mysqli->query("select `gatewayname` from fs_gateways where `enabled`>0");
$exts = result_fetch_all($gateway_result);
$gateway_result = "<option value=''>请选择..</option>";
foreach ($exts as $one)
	$gateway_result .= "<option value='$one[0]'>$one[0]</option>";
$gateway_result = "<select name='gwname' id='gwname' class='inputline1'>$gateway_result</select>";
unset($exts);

if (isset($_GET['editDIAL'])){
	$id = intval($_GET['editDIAL']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select a.*,b.`ext-name`,b.`context-name`,b.`ext-level` from fs_dialplans a,fs_extensions b where a.`ext-id`=b.id and a.id = $id");
		$sql = "update fs_dialplans set ";
		$sql_end = " where id = $id";
		$showinfo .=" id $id 更新 ";
	}else{
		$result = false;
		$sql = "insert into fs_dialplans (`ext-id`,`level`,`prefix`,`act`,`condition`,`destnumber-len`,`recording`,`gateway`,`break`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 ";
	}

	$fail = 0;
	if ($result)
		$row = $result->fetch_array();
	else
		$row = array();
$submitbutton = "<input type=\"submit\" value=\"确认提交\" onclick=\"var vv=$('#expression').val();$('#condition_value').val(vv);\"/>";
if (isset($row['ext-name'])){
	$file_ = @$_SESSION['conf_dir']."/dialplan/".$row['context-name']."/".$row['ext-level']."_".$row['ext-name'].".xml";
	if (is_file($file_)){
		$submitbutton = ' <a href="?editDIAL='.$id.'">刷新页面</a>';
		$showinfo .= "<span class='bgred'>已经部署生效，不得修改，要修改需先停用！</span><br/>";
		if (!empty($_POST))
			unset($_POST);
	}
}
if (!empty($_POST)){
	$extid = intval($_POST['extid']);
	if (empty($extid)){
		$showinfo .= "<span class='bgred'>必须选择隶属extension！</span><br/>";
		$fail = 1;
	}
	$validRegExp =  '/^[0-9]+$/';
	$prefixlen = strlen($_POST['prefix']);
	if ($prefixlen && ($prefixlen>10 || !preg_match($validRegExp, $_POST['prefix']))) {
		$showinfo .= "<span class='bgred'>前缀必须是0-9的数字！且不得超过10位</span><br/>";
		$fail = 1;
		$prefix = "";
	}else
		$prefix = $_POST['prefix'];
	$validRegExp =  '/^[0-9\-]+$/';
	$destnumberlen = strlen($_POST['destnumber-len']);	
	if ($destnumberlen && ($destnumberlen>5 || !preg_match($validRegExp, $_POST['destnumber-len']))) {
		$showinfo .= "<span class='bgred'>号码长度内容不合法！输入数字及横线，且不得超过5位</span><br/>";
		$fail = 1;
		$destnumberlen = "";
	}else
		$destnumberlen = $_POST['destnumber-len'];	
	$level = intval($_POST['level']);
	if ($level>120)
		$level = 120;
	elseif ($level<0)
		$level = 0;
		$break = $mysqli->real_escape_string($_POST['condition_break']);
	if (empty($_POST['condition_field'])){
		$condition = $expression = $expression_html = $condition_str = "";
	}else{
		$condition = $_POST['condition_field'];
		$expression = $_POST['condition_value'];
		$expression_html = '<input id="expression" name="expression" value="'.$expression.'" size=90 onclick="this.select();" class="inputline1"/>';
		$condition_str = "$condition=LMX=$_POST[condition_value]";
		$condition = $mysqli->real_escape_string($condition_str);
	}
	$act = $mysqli->real_escape_string($_POST['action']);
	
	if ($_POST['record']=='2' && !empty($_POST['record9'])){
		$record = $mysqli->real_escape_string($_POST['record9']);
		$had_rec = '2';
	}elseif ($_POST['record']=='1'){
		$record = "=LMX=";
		$had_rec = '1';
	}else
		$had_rec = $record = '';
	$gwname="";
	if ($_POST['gateway']=='2' && !empty($_POST['gateway9'])){
		$had_gw = '2';
		$gateway = $mysqli->real_escape_string($_POST['gateway9']);
	}elseif  ($_POST['gateway']=='1' && !empty($_POST['gwname'])){
		$gateway = '=LMX='.$mysqli->real_escape_string($_POST['gwname']);
		$had_gw = '1';
		$gwname = $_POST['gwname'];
	}else
		$gateway = $had_gw = '';
	
	if ($id)
		$sql .= "`ext-id`=$extid,`level`=$level,`prefix`='$prefix',`act`='$act',`condition`='$condition',`destnumber-len`='$destnumberlen',`recording`='$record',`gateway`='$gateway',`break`='$break'";
	else
		$sql .= "$extid,$level,'$prefix','$act','$condition','$destnumberlen','$record','$gateway','$break'";
}else{
	$extid = @$row['ext-id'];
	$prefix = @$row['prefix'];
	$destnumberlen = @$row['destnumber-len'];
	$break = @$row['break'];
	$level = @$row['level'];
	if (empty($row['condition']))
		$condition = $condition_str = $expression_html = $expression = "";
	else{
		$condition_str = explode("=LMX=", $row['condition']);
		$condition = $condition_str[0];
		$expression = $condition_str[1];
		if (strpos($condition, 'regex-')===0)
			$expression_html = '<textarea rows="4" cols="125"  id="expression" name="expression" class="inputline1">'.$expression.'</textarea>';
		else
			$expression_html = '<input id="expression" name="expression" value="'.$expression.'" size=90 onclick="this.select();" class="inputline1"/>';
	}
	$act = @$row['act'];
	$gwname = "";
	if (empty($row['gateway']))
		$gateway = $had_gw = "";
	else{
		$gateway = $row['gateway'];
		$find = strpos($gateway,'=LMX=');
		if ($find === 0){
			$gwname = substr($gateway,$find+5);
			$had_gw = '1';
			$gateway = "";
		}else 
			$had_gw = '2';
	}
	if (empty($row['recording']))
		$record = $had_rec = "";
	else{
		$record = @$row['recording'];
		if ($record=='=LMX='){
			$record = "";
			$had_rec = '1';
		}else 
			$had_rec = '2';
	}
}
$html = <<<HTML
<tr class='bg1'><td width=80><em>extension：</em></td><td> $ext_result </td></tr>
<tr class='bg2'><td>✦<em>拨号条件：</em></td><td>前缀 <input id="prefix" name="prefix" value="$prefix" size=3 onclick="this.select();" class="inputline1"/> &nbsp; 号码长度 <input id="destnumber-len" name="destnumber-len" value="$destnumberlen" size=3 onclick="this.select();" class="inputline1"/> <span class="smallgray smallsize-font"> * 指定被叫前缀及被叫号码长度，长度是 数字 或 区间，如11 或 7-12</span></td></tr>
<tr class='bg1'><td>✦<em>选条件：</em><input type="hidden" name="condition_value" id="condition_value" value="$expression"/></td><td><select name='condition_field' id='condition_field' class='inputline1'>$condition_list <script>
$('#condition_field').val('$condition');$('#condition_field').on('change',function(){
    if($(this).val()){
		var selectText = $(this).find('option:selected').val();
		var index = selectText.indexOf('regex-');
		if (index==0)
			$('#expvalue').html('<textarea rows="3" cols="125"  id="expression" name="expression" class="inputline1"></textarea>');
		else
			$('#expvalue').html('<input id="expression" name="expression" value="" size=90 onclick="this.select();" class="inputline1"/>');
		if($('#condition_value').val()) $('#expression').val($('#condition_value').val());
	}});</script></td></tr>
<tr class='bg1'><td> &nbsp; <em>表达式：</em></td><td id='expvalue'>$expression_html</td></tr>
<tr class='bg2'><td><em>比对后：</em></td><td><select name='condition_break' id='condition_break' class='inputline1'>$break_list  <script>$('#condition_break').val('$break')</script></td></tr>
<tr class='bg1'><td><em>动作：</em></td><td><span class="smallgray smallsize-font">条件为真时执行动作，格式：动作===内容，如：set===domain_name=\$\${domain} inline<br/>条件为假时执行动作，格式：!!动作===内容， 如： !!respond===503<br/>后面加inline表示立即执行(主要是变量存取)，其他说明：<a href="https://freeswitch.org/confluence/display/FREESWITCH/mod_dptools" target="_blank">动作命令说明</a> &nbsp; <a href="https://freeswitch.org/confluence/display/FREESWITCH/Channel+Variables" target="_blank">可用通道变量说明</a></span><textarea rows="7" cols="125"  id="action" name="action" class="inputline1">$act</textarea></td></tr>
<tr class='bg2'><td><em>优先级：</em></td><td><input id="level" name="level" class="inputline1" value="$level" size=3/> <span class="smallgray smallsize-font"> * 0到120的数字，数字越小级别越高</span></td></tr>
<tr class='bg1'><td><em>录音：</em></td><td><label><input id="record0" name="record"  type="radio" value="0">不录音</label> <label><input id="record1" name="record"  type="radio" value="1">默认录音</label> <label><input id="record2" name="record" type="radio" value="2">自定义<textarea rows="6" cols="125"  id="record9" name="record9" class="inputline1" onclick='$("#record2").prop("checked","checked");if (!$(this).val()) $(this).val(re);'>$record</textarea></label></td></tr>
<tr class='bg2'><td><em>路由：</em></td><td><label><input id="gateway0" name="gateway"  type="radio" value="0">无路由</label> <label><input id="gateway1" name="gateway"  type="radio" value="1">选路由 $gateway_result</label> <label><input id="gateway2" name="gateway" type="radio" value="2">自定义<textarea rows="2" cols="125"  id="gateway9" name="gateway9" class="inputline1" onclick='$("#gateway2").prop("checked","checked");if (!$(this).val()) $(this).val("bridge===sofia/gateway/这里写路由名/$1");'>$gateway</textarea></label></td></tr>
HTML;
if (!empty($_POST)){
	$submitbutton = ' <a href="?editDIAL='.$id.'">刷新页面</a>';
	$sql  .= $sql_end;
	$result = false;
	if (!$fail)
		$result = $mysqli->query($sql);
	if ($result){
		$showinfo .= "<span class='bggreen'>操作成功！</span>";
	}else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
<script>var re ='set===RECORD_TITLE=Recording \${destination_number} \${caller_id_number} \${strftime(\%Y-\%m-\%d \%H:\%M:\%S)}\\nset===RECORD_DATE=\${strftime(\%Y-\%m-\%d \%H:\%M:\%S)}\\nset===RECORD_STEREO=true\\nrecord_session===\$\${recordings_dir}/\${strftime(\%Y/\%m/\%d)}/\${uuid}.wav';
</script>
</head><body><p class='pcenter' style='font-size:18pt;'>dialplan详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回dialplan主控页</a></p><table class="tablegreen" width="1000" align="center"><form method="post"><th colspan=2>$showinfo</th>$html<tr class='bg1'><th></th><th> $submitbutton </th></tr></form></table><script>
$('#extid').val('$extid');$('#gwname').val('$gwname');
if ('$had_rec'=='') $("#record0").prop("checked","checked"); else if ('$had_rec'=='2') $("#record2").prop("checked","checked"); else $("#record1").prop("checked","checked"); 
if ('$had_gw'=='') $("#gateway0").prop("checked","checked"); else if ('$had_gw'=='2') $("#gateway2").prop("checked","checked"); else $("#gateway1").prop("checked","checked");
</script></body></html>
HTML;
exit;
}
//-----------dialplan管理-------dialplan数据库 ---POST提交操作-----------------------------

//删除dialplan记录
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$mysqli->query("delete from fs_dialplans where id = $id and `enabled` = 0 limit 1");
	die("id $id 操作完毕");
}

//设置启用或禁用
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$result = $mysqli->query("select b.`ext-name`,b.`context-name`,b.`ext-level` from fs_dialplans a,fs_extensions b where a.`ext-id`=b.id and a.id = $id");
		$row = $result->fetch_array();
		if (isset($row['ext-name'])){
			$file_ = @$_SESSION['conf_dir']."/dialplan/".$row['context-name']."/".$row['ext-level']."_".$row['ext-name'].".xml";
			if (is_file($file_))
				die("项目已经部署生效，不能启用新拨号计划，要修改需先停用！");
		}
		$mysqli->query("update fs_dialplans set `enabled` = 1 where id = $id limit 1");
		die("id $id 设置为可用完毕");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_dialplans set `enabled` = 0 where id = $id limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示----------dialplan数据库 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script>
function del(sid){var a = confirm(\"删除操作不可撤销，你确认提交？\");if (a) { \$.post( \"FS_dialplans_cp.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"FS_dialplans_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"FS_dialplans_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en88(sid,lab){\$.post( \"FS_extensions_cp.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"FS_extensions_cp.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script></head><body>";
$where = "";
$showget = "<span class='smallred smallsize-font'> ";
$count = 20;
$getstr = "";
if (!empty($_GET['extid'])){
	$tmp = intval($_GET['extid']);
	$where .= " and a.`ext-id` = '$tmp' ";
	$showget .=" extension 为 '$tmp' ";
}
if (!empty($_GET['enabled'])){
	$_GET['enabled'] = intval($_GET['enabled']);
	if ($_GET['enabled']>1) $_GET['enabled'] = 0;
	$showget .=" 查看 '".($_GET['enabled']?"可用":"不可用")."' 的信息 ";
	$where .= " and a.`enabled` = '$_GET[enabled]' ";
}
if (!empty($_GET['gwname'])){
	$_GET['gwname'] = $mysqli->real_escape_string($_GET['gwname']);
	$showget .=" 路由包含 '$_GET[gwname]’ 的信息 ";
	$where .= " and a.`gateway` like '%$_GET[gwname]%' ";
}
$totle = $mysqli->query("select count(*) from fs_dialplans a where 1 $where");
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
	echo '<p class="pcenter" style="font-size:18pt;">dialplan管理控制台  '.$showget.' <a style="font-size:12pt;" href="?editDIAL=0">【 新建 】</a> <a style="font-size:10pt;" href="main.php">&raquo;&nbsp;返回主控</a></p><table class="tablegreen" width="90%" align="center"><th colspan=6><form method="get">ext：'.$ext_result.' 路由：'.$gateway_result.' 可用否：<label><input id="a" name="enabled"  type="radio" value="0" checked>全部 </label> <label><input id="y" name="enabled"  type="radio" value="1">可用 </label> <label><input id="n" name="enabled"  type="radio" value="2">不可用 </label> <input type="submit" value="确认"> <a href="?">【看全部】</a> <a href="FS_extensions_cp.php">【看ext】</a></form></th>';
	$result = $mysqli->query("select a.*,b.`ext-name`,b.`context-name`,b.`ext-level` from fs_dialplans a,fs_extensions b where a.`ext-id`=b.id $where ORDER BY a.id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=6 align=center><span class="smallred smallsize-font"> *dialplan新建后默认禁用，需启用后方可应用！已应用的可停用 拨号信息设置后需启用并部署生效</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a>
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
			else{
				$fromuser ="";
				if ($row['enabled']){
					$file_ = @$_SESSION['conf_dir']."/dialplan/".$row['context-name']."/".$row['ext-level']."_".$row['ext-name'].".xml";
					if (is_file($file_)){
						$showalert= "<span class='smallgray smallsize-font'> [id $row[id]] </span> <span class='bggreen'> 已应用 </span>@ ".$row['context-name'].' &nbsp;<span class="smallgray smallsize-font">[ext] </span><em>'.$row['ext-name'].'</em>';
						$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';en99(".$row['ext-id'].",'".$row['ext-name']."')\" value='停用'/>";
					}else{
						$showalert= "<span class='smallgray smallsize-font'> [id $row[id]] </span> <span class='bgblue'>  ext已停用 </span>@ ".$row['context-name'].' &nbsp; <span class="smallgray smallsize-font">[ext] </span><em>'.$row['ext-name'].'</em>';
						$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';en88(".$row['ext-id'].",'".$row['ext-name']."')\" value='部署应用'/> &nbsp;  <input type='button' onclick=\"en0($row[id])\" value='禁止'/>";
					}
				}else
					$showalert= "<span class='smallgray smallsize-font'> [id $row[id]] </span> <span class='bgred'> 已禁止 </span>@ ".$row['context-name'].'  &nbsp;<span class="smallgray smallsize-font">[ext] </span><em>'.$row['ext-name'].'</em>';
				$condition=$row['condition'];
				$action = $row['act'];
				$find = strpos($condition,'=LMX=');
				if ($find)
					$condition = '<span class="smallgray smallsize-font">[条件] </span>'.substr($condition,0,$find);
				$find = strpos($action,'===');
				if ($find)
					$action =  '<span class="smallgray smallsize-font">[动作] </span>'.substr($action,0,$find)." ... ";
				$continue = "比对后：<select id='condition_break$row[id]' class='inputline1'>$break_list <script>$('#condition_break$row[id]').val('$row[break]');</script>";
				$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
				echo "<tr $bgcolor><td>$showalert<span id='gwinfo$row[id]'></span></td><td align='center'>$continue</td><td>$condition</td><td>$action</td><td>优先级：$row[level]</td><td><a href='?editDIAL=$row[id]'>&raquo;&nbsp;详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>";
				if ($row['enabled']){
					echo $showtools;
				}else
					echo " <button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id])\">删除</button>";
				echo "</span></td></tr>";
			}
	}
	$mysqli->close();
	