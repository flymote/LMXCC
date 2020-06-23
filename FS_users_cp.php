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
//-------------------修改或添加域信息-----------------------------------
if (isset($_GET['editUser'])){
	$id = intval($_GET['editUser']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_users where id = $id");
		$sql = "update fs_users set ";
		$sql_end = " where id = $id";
		$showinfo .=" id $id 更新 ";
	}else{
		$result = false;
		$sql = "insert into fs_users (`user_id`,`user_name`,`password`,`domain_id`,`group_id`,`reverse_user`,`reverse_pwd`,`user_context`,`gateway`,`cidr`,`dial_str`,`variables`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 ";
	}

$fail = 0;
if ($result)
	$row = $result->fetch_array();
else 
	$row = array();

if (!empty($_POST)){
	$user_name = $_POST['user_name'];
	$user_id = $_POST['user_id'];
	$cidr = $mysqli->real_escape_string($_POST['cidr']);
	if (empty($user_id) || empty($user_name)) {
		$showinfo .= "<span class='bgred'>必须提交组名及组标识！</span><br/>";
		$fail = 1;
	}
	$validRegExp =  '/^[0-9]+$/';
	$prefixlen = strlen($_POST['user_id']);
	if ($prefixlen && ($prefixlen>10 || !preg_match($validRegExp, $_POST['user_id']))) {
		$showinfo .= "<span class='bgred'>为确保兼容性，用户标识必须是数字！且不得超过10位</span><br/>";
		$fail = 1;
		$prefix = "";
	}
	$reverse_user = $mysqli->real_escape_string($_POST['reverse_user']);
	$reverse_pwd = $mysqli->real_escape_string($_POST['reverse_pwd']);
	if (!empty($_POST['gateway']))
		$gateway = $mysqli->real_escape_string(implode(",", $_POST['gateway']));
	else 
		$gateway = "";
	$gw = $gateway;
	$group_id = "";
	$variables = $mysqli->real_escape_string($_POST['variables']);
	$user_name = $mysqli->real_escape_string($user_name);
	$user_context = $mysqli->real_escape_string($_POST['user_context']);
	$dial_str = $mysqli->real_escape_string($_POST['dial_str']);
	$user_id = $mysqli->real_escape_string($user_id);
	if (!empty($_POST['domain_id'])){
		$domain_id =  $mysqli->real_escape_string($_POST['domain_id']);
		$dmlist = $_POST['domain_id'];
	}else {
		$dmlist = " <span class=\"smallgray smallsize-font\"> *无隶属域* </span>";
		$domain_id = '';
	}
	$password = empty($_POST['password'])?'':$mysqli->real_escape_string($_POST['password']);
	
	//提交的组ID是组合编码，如果与domainid和groupid保存的一致，表示组数据没有修改；否则，以提交的数据替换库
	if (isset($_POST['group_id'])){
		$domain_id0 = $group_id = "";
		foreach ($_POST['group_id'] as $a){
			$temp = explode(" ", $a);
			$group_id .= ",$temp[1],";
			if ($domain_id0=="")
				$domain_id0 =  $temp[0];
			elseif($domain_id0 != $temp[0]){
				$fail = 1;
				$showinfo .= "<span class='bgred'>用户隶属的组不允许跨域！</span><br/>";
				break;
			}
		}
		$domain_id = $mysqli->real_escape_string($domain_id0);
		$group_id = $mysqli->real_escape_string($group_id);
	}elseif ($_POST['domain_id'] != @$row['domain_id']){ //如果提交组数据为空，且域修改了，原组数据将清除
		$group_id = "";
	}
	
	//进行预定参数的设置
	
// 	if (empty($variables)){
// 		$variables = "toll_allow===domestic,international,local\naccountcode===$user_id\neffective_caller_id_name===$user_name\neffective_caller_id_number===$user_id\noutbound_caller_id_name===\$\${outbound_caller_name}\noutbound_caller_id_number===\$\${outbound_caller_id}";
// 	}
	$gw_sql = [];
	if ($id){
		$sql .= "`user_id`='$user_id',`user_name`='$user_name',`password`='$password',`domain_id`='$domain_id',`group_id`='$group_id',`reverse_user`='$reverse_user',`reverse_pwd`='$reverse_pwd',`user_context`='$user_context',`gateway`='$gateway',`cidr`='$cidr',`dial_str`='$dial_str',`variables`='$variables'";
	}else
		$sql .= "'$user_id','$user_name','$password','$domain_id','$group_id','$reverse_user','$reverse_pwd','$user_context','$gateway','$cidr','$dial_str','$variables'";
	if (!empty($_POST['gateway']))
	foreach ($_POST['gateway'] as $gw1)
		$gw_sql[] = "update fs_gateways set `domain_id`='$domain_id',`domain_user`='$user_id' where `gatewayname` = '$gw1'";
	$glist = $group_id;
	$dmlist = $domain_id;
	$dmold ="";
}else{
	$user_name = @$row['user_name'];
	if (isset($row['user_id']))
		$user_id = $row['user_id'];
	else 
		$user_id = rand(10000,999999);
	$user_context = (@$row['user_context']?$row['user_context']:'');
	$password = @$row['password'];
	$domain_id = @$row['domain_id'];
	$group_id = @$row['group_id'];
	if ($group_id)
		$group_id_a = explode(",", $group_id);
	else
		$group_id_a = array();
	$cidr = @$row['cidr'];
	$gateway = @$row['gateway'];
	if ($gateway)
		$gateway_a = explode(",", $gateway);
	else 
		$gateway_a = array();
	$variables = @$row['variables'];
	$gresult = $mysqli->query("select gatewayname from fs_gateways where enabled=0 and (domain_id = '$domain_id' or domain_id is NULL or domain_id='')");
	$gw = "";
	while (($row_ = $gresult->fetch_array(MYSQLI_NUM))!==false)
		if ($row_ ){
			$gw .= "<label><input type='checkbox' name='gateway[]' value='$row_[0]' ";
			if (in_array($row_[0], $gateway_a))
				$gw .= " checked='checked' ";
			$gw .="/>$row_[0]</label> &nbsp; ";
	}else break;
	if ($gw=='')
		$gw = "** 尚无路由可选 **";

	$reverse_user = @$row['reverse_user'];
	$reverse_pwd = @$row['reverse_pwd'];
	$dial_str = @$row['dial_str'];
	$dmold = $user_name.$user_id.$user_context.$password.$domain_id.$group_id.$reverse_user.$reverse_pwd.$gateway.$cidr.$dial_str.$variables;

	$ext_ = $mysqli->query("select `domain_name`,`domain_id` from fs_domains order by id DESC");
	$dmlist = "<option value=''>[无隶属域]</option>";
	while (($row_ = $ext_->fetch_array(MYSQLI_NUM))!==false)
		if ($row_)
			$dmlist .= "<option value='$row_[1]'>$row_[0]</option>";
		else break;
	$dmlist = "<select name='domain_id' id='domain_id' class='inputline1'>$dmlist</select><script>";
	if ($domain_id)
		$dmlist .= "$('#domain_id').val('$domain_id');</script>";
	else 
		$dmlist .="$('#domain_id').val('');</script>";	
	
	$ext_ = $mysqli->query("select `group_name`,`group_id`,`domain_id` from fs_groups order by `domain_id`,id DESC");
	$glist = "";
	while (($row_ = $ext_->fetch_array(MYSQLI_NUM))!==false)
		if ($row_){
			$glist .= " <br/><label><input type='checkbox' name='group_id[]' value='$row_[2] $row_[1]' ";
			if ($row_[2]==$domain_id && in_array($row_[1], $group_id_a))
				$glist .= " checked='checked' ";
				$glist .="/>$row_[2] : $row_[0] : $row_[1]</label>";
		}else break;
}
$html = <<<HTML
<tr class='bg1'><td width=80><em>用户</em></td><td>名称：<input id="user_name" name="user_name" size="20"  maxlength="20" value="$user_name" onclick="this.select();" class="inputline1"/> 密码：<input id="password" name="password" size="20"  maxlength="20" value="$password" onclick="this.select();" class="inputline1"/> <span class="smallgray smallsize-font"> * 长度不得超过20</span></td></tr>
<tr class='bg2'><td><em>用户标识：</em></td><td><input id="user_id" name="user_id" value="$user_id" size=20 class="inputline1" maxlength="20"/> <span class="smallgray smallsize-font"> * 用户标识，最长20位，仅限数字，也是坐席号（即登录账号）</span></td></tr>
<tr class='bg1'><td><em>隶属</em></td><td>选择组：（可多个组，但不允许跨域）$glist  <br/>或 &nbsp;  域： $dmlist <span class="smallgray smallsize-font"> * 请选择其上级组 或 选择域，组优先</span></td></tr>
<tr class='bg2'><td><em>信息项</em></td><td><input type="hidden" name="dmold" value="$dmold"><em>user_context:</em> <input id="user_context" name="user_context" value="$user_context" size=8 class="inputline1" /> &nbsp; <br/><em>反向认证用户:</em> <input id="reverse_user" name="reverse_user" value="$reverse_user" size=6 class="inputline1" /> &nbsp; <em>反向认证密码:</em> <input id="reverse_pwd" name="reverse_pwd" value="$reverse_pwd" size=6 class="inputline1" /><br/><em>登录IP: </em> <input id="cidr" name="cidr" value="$cidr" size=70 class="inputline1" /> <span class="smallgray smallsize-font">* 如12.34.56.78/32,10.11.12.0/24,20.0.0.0/8 注意掩码</span></td></tr>
<tr class='bg1'><td><em>拨号设置</em></td><td><span class="smallgray smallsize-font">默认值：\${sofia_contact(\${dialed_user}@\${dialed_domain})}<br/>通道变量 {presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(\${dialed_user}@\${dialed_domain})}<br/>通道变量{transfer_fallback_extension=\${dialed_user}}\${sofia_contact(\${dialed_user}@\${dialed_domain})}<br/>动作\${sofia_contact(\${dialed_user}@\${dialed_domain})},pickup/\${dialed_user}@\${dialed_domain}<br/>变量及动作{sip_invite_domain=\${dialed_domain},presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(\${dialed_user}@\${dialed_domain})},pickup/\${dialed_user}@\${dialed_domain}<br/>{^^:sip_invite_domain=\${dialed_domain}:presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(*/\${dialed_user}@\${dialed_domain})},\${verto_contact(\${dialed_user}@\${dialed_domain})}</span><br/><input id="dial_str" name="dial_str" value="$dial_str" size=120 class="inputline1" maxlength="200"/></td></tr>
<tr class='bg2'><td><em>专用路由</em></td><td>$gw <span class="smallgray smallsize-font">* 选择需随本用户注册而启用的路由</span></td></tr>
<tr class='bg1'><td><em>附加变量</em></td><td><span class="smallgray smallsize-font"><span class="smallgray smallsize-font">**** 用 参数===值 的格式表现：****<br/>如强制注册到期设置，可以设 sip-force-expires===180 &nbsp;  &nbsp;  &nbsp; 如强制用ID1000（必须存在本ID），可设置 sip-force-user===1000<br/>**** 注意，下面是默认的几个设置，不设置变量时会自动使用，否则请自行设置：****<br/>
toll_allow===domestic,international,local   ####设置拨号允许范围<br/>
accountcode===12    ####设置accountcode为12，一般是注册的账户id<br/>
effective_caller_id_name===张三   ####设置主叫的用户名<br/>
effective_caller_id_number===12   ####设置主叫的号码<br/>
outbound_caller_id_name===\$\${outbound_caller_name}   ####设置出局的主叫用户名，这里调用默认系统设置<br/>
outbound_caller_id_number===\$\${outbound_caller_id}   ####设置出局的主叫用户id，这里调用默认系统设置</span><textarea rows="5" cols="125"  id="variables" name="variables" class="inputline1">$variables</textarea></td></tr>
HTML;
$submitbutton = "<input type=\"submit\" value=\"确认提交\" />";
if (!empty($_POST)){
	$submitbutton = ' <a href="?editUser='.$id.'">刷新页面</a>';
	$sql  .= $sql_end;
	$result = false;
	if ($user_name.$user_id.$user_context.$password.$domain_id.$group_id.$reverse_user.$reverse_pwd.$gateway.$cidr.$dial_str.$variables == $mysqli->real_escape_string($_POST['dmold'])){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
		$result = 1;
	}elseif (!$fail)
		$result = $mysqli->query($sql);
	if ($result){
		$showinfo .= "<span class='bggreen'>操作成功！</span>";
		foreach ($gw_sql as $sql)
			$mysqli->query($sql);
	}else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>用户详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回用户主控页</a></p><table class="tablegreen" width="1000" align="center"><form method="post"><th colspan=2>$showinfo</th>$html<tr class='bg1'><th></th><th>$submitbutton</th></tr></form></table></body></html>
HTML;
	exit;
}
//-----------域管理-------域数据库 ---POST提交操作-----------------------------

//删除域记录
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$mysqli->query("delete from fs_users where id = $id and `enabled` = 0 limit 1");
	die("id $id 操作完毕");
}
//设置启用或禁用
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_users set `enabled` = 1 where id = $id limit 1");
		die("id $id 设置为可用完毕");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_users set `enabled` = 0 where id = $id limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示----------域数据库 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script>
function del(sid){var a = confirm(\"删除操作不可撤销，你确认提交？\");if (a) { \$.post( \"FS_users_cp.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"FS_users_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"FS_users_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en88(sid,lab){\$.post( \"FS_domains_func.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"FS_domains_func.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script></head><body>";
$where = " where 1 ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['dmid'])){
	$temp = $mysqli->real_escape_string($_GET['dmid']);
	$where .= " and `domain_id` like '%$temp%' ";
	$showget .=" 域标识含 '$temp' ";
}
if (!empty($_GET['gid'])){
	$temp = $mysqli->real_escape_string($_GET['gid']);
	$where .= " and `group_id` like '%,$temp,%' ";
	$showget .=" 组标识含 '$temp' ";
}
$count = 20;
$getstr = "";
$totle = $mysqli->query("select count(*) from fs_users $where");
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
	echo '<p class="pcenter" style="font-size:18pt;">用户管理控制台 '.$showget.'  <a style="font-size:12pt;" href="?editUser=0">【新建用户】</a> &nbsp; <a style="font-size:10pt;" href="FS_callcenter_cp.php">【呼叫中心】</a> &nbsp;  &nbsp; <a style="font-size:10pt;" href="main.php">返回主控</a></p><table class="tablegreen" width="90%" align="center"><th colspan=5><form method="get">域标识：<input id="dmid" name="dmid" value="" size=10> 组标识：<input id="gid" name="gid" value="" size=10> <input type="submit" value="确认"> <a href="?">【看全部】</a> <a href="FS_domains_cp.php">【看域】</a>	 <a href="FS_groups_cp.php">【看组】</a></form></th>';
	$result = $mysqli->query("select * from fs_users $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=5 align=center><span class="smallred smallsize-font"> *用户新建后默认被禁用，需启用后方可应用！已应用的组可获取信息 或 停用；组设置后需启用，并需 用户管理中进行调用<br/> *添加或修改用户，都需先启用组及用户，而后必须将域重新部署启用！！</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			if ($row['enabled']){
				$file_ = @$_SESSION['conf_dir']."/directory/".$row['domain_id'].".xml";
				if (is_file($file_)){
					$showalert= ' <span class="bggreen">已应用 </span> &nbsp; <em class=\'red\'>'.$row['user_name'].'</em>';
					$showtools=" <input type='button' onclick=\"this.value='连接中，请等待反馈...';en99($row[id],'$row[domain_id]')\" value='停用'/>";
				}else{
					$showalert= ' <span class="bgblue">已停用 </span> &nbsp; <em class=\'red\'>'.$row['user_name'].'</em>';
					$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';en88($row[id],'$row[domain_id]')\" value='部署应用'/> &nbsp;  <input type='button' onclick=\"en0($row[id])\" value='禁止'/>";
				}
			}else 
				$showalert= ' <span class="bgred">已禁止 </span> &nbsp; <em class=\'red\'>'.$row['user_name'].'</em>';
			if ($row['domain_id']){
				$totle = $mysqli->query("select `domain_name` from fs_domains where `domain_id`='$row[domain_id]'");
				$row_ = $totle->fetch_array(MYSQLI_NUM);
				$totle = $row_[0];
				$showu = " 隶属域：<strong>$totle</strong>";
			}else
				$showu = "<span class=\"smallgray smallsize-font\">无隶属域</span>";
			if ($row['group_id']){
				$a = explode(',', $row['group_id']);
				$b = "";
				$totle = $mysqli->query("select `group_name`, `group_id` from fs_groups where `domain_id`='$row[domain_id]'  ");
				while (($row0 = $totle->fetch_array(MYSQLI_NUM))!==false) {
					if ($row0 && in_array($row0[1], $a))
						$b .= "$row0[0] &nbsp; ";
					elseif (!$row0) break;
				}
				$showg = " 隶属组：<strong>$b</strong>";
			}else
				$showg = "<span class=\"smallgray smallsize-font\">无隶属组</span>";
			$options = "context：<strong>".$row["user_context"]."</strong> &nbsp; ";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr $bgcolor><td>$showalert</td><td>用户标识：<strong>$row[user_id]</strong></td><td> $showu &nbsp; $showg</td><td>$options</td><td><a href='FS_call_list.php?users=$row[user_id]@$row[domain_id]'>最近通话</a> &nbsp; <a href='?editUser=$row[id]'>详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>";
			if ($row['enabled']){
				echo $showtools;
			}else 
				echo " <button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id])\">删除</button>";
			echo "</span></td></tr>";
		}
	}
$mysqli->close();
