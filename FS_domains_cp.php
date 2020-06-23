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

include_once 'Shoudian_db.php';

//-------------------修改或添加域信息-----当存在 $_GET['editDomain']------------------------------------------------------------------
if (isset($_GET['editDomain'])){
	
	function xmlentities($string){ //不允许 < > " 三种符号
		$value = str_replace(array("<",">",'"'),'_', $string);
		return $value;
	}
	
	$id = intval($_GET['editDomain']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_domains where id = $id");
		$sql = "update fs_domains set ";
		$sql_end = " where id = $id";
		$showinfo .=" id $id 更新 ";
	}else{
		$result = false;
		$sql = "insert into fs_domains (`domain_id`,`domain_name`,`level`,`parent_id`,`create_date`,`last_date`,`user_prefix`,`group_prefix`,`DID`,`agent_login`,`agent_out`,`agent_break`,`callcenter_config`,`ivr_config`,`out_prefix`,`out_config`,`autocall_self`,`autocall_lines`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 ";
}

$fail = 0;
if ($result)
	$row = $result->fetch_array();
else 
	$row = array();

$ext_result = $mysqli->query("select `id`,`domain_id`,`parent_id`,`enabled`,`domain_name` from fs_domains order by id DESC");
$domains = result_fetch_all($ext_result,MYSQLI_NUM);
$domain_up = "<option value=''>[无上级域]</option>";
$domain_lists = array();
foreach ($domains as $one){
	$domain_lists[$one[0]] = $one[1];
	if ($one[0] != $id && $one[3])
		$domain_up .= "<option value='$one[0] $one[1]'>[$one[0]] $one[4]</option>";
}
unset($domains);

if (!empty($_POST)){
	$domain_name = $_POST['domain_name'];
	$group_prefix = intval($_POST['group_prefix']);
	$user_prefix = intval($_POST['user_prefix']);
	$out_prefix = intval($_POST['out_prefix']);
	$domain_id = $_POST['domain_id'];
	$did =  intval($_POST['did']);
	$agent_login =  intval($_POST['agent_login']);
	$agent_out =  intval($_POST['agent_out']);
	$agent_break = intval($_POST['agent_break']);
	$autocall_self =  intval($_POST['autocall_self']);
	$autocall_lines =   intval($_POST['autocall_lines']);
	if (empty($domain_id) || empty($domain_name)) {
		$showinfo .= "<span class='bgred'>必须提交域名及域标识！</span><br/>";
		$fail = 1;
	}
	$check_ = [];
	if (!$did || $did>999999 || $did<1000 ) {
		$showinfo .= "<span class='bgred'>DID必须提交，且为4-6位的整数！</span><br/>";
		$fail = 1;
	}
	if (!$group_prefix || !$user_prefix || !$out_prefix || $group_prefix>999 || $user_prefix>999 || $out_prefix>999) {
		$showinfo .= "<span class='bgred'>用户前缀和组前缀、呼出前缀须设置为最多3位数字，且不能为0 不能相同！</span><br/>";
		$fail = 1;
	}
	if (!$agent_login || !$agent_break || !$agent_out || !$autocall_self || $agent_login>100 || $agent_out>100 || $agent_break>100 || $autocall_self >100) {
		$showinfo .= "<span class='bgred'>坐席签入\签出\示忙的号码、用户自动外呼号码 设置为最多2位数字，且不能为0 不能相同！</span><br/>";
		$fail = 1;
	}
	$check_[$did] = "";
	$check_[$user_prefix] = "";
	$check_[$group_prefix] = "";
	$check_[$autocall_self] = "";
	$check_[$out_prefix] = "";
	$check_[$agent_login] = "";
	$check_[$agent_out] = "";
	$check_[$agent_break] = "";
	if (count($check_)<8){
		$showinfo .= "<span class='bgred'>坐席签入\签出\示忙的号码，坐席自动外呼号码、用户前缀、组前缀、呼出前缀、DID，存在重复设置！</span><br/>";
		$fail = 1;
	}
	$callcenter= ['strategy'=> $_POST['strategy'],'moh-sound'=>xmlentities($_POST['moh-sound']),'record-template'=>xmlentities($_POST['record-template']),'time-base-score'=>$_POST['time-base-score'],'max-wait-time'=>intval($_POST['max-wait-time']),'max-wait-time-with-no-agent'=>intval($_POST['max-wait-time-with-no-agent']),'max-wait-time-with-no-agent-time-reached'=>intval($_POST['max-wait-time-with-no-agent-time-reached']),'tier-rules-apply'=>$_POST['tier-rules-apply'],'tier-rule-wait-second'=>intval($_POST['tier-rule-wait-second']),'tier-rule-wait-multiply-level'=>$_POST['tier-rule-wait-multiply-level'],'tier-rule-no-agent-no-wait'=>$_POST['tier-rule-no-agent-no-wait'],'abandoned-resume-allowed'=>$_POST['abandoned-resume-allowed'],'discard-abandoned-after'=>intval($_POST['discard-abandoned-after'])];
	$cc = $callcenter;
	$callcenter = json_encode($callcenter);
	$menu = [];
	if (is_array(@$_POST['menu'])){
		foreach ($_POST['menu'] as $one){
			if ($one['d'] != ''){
				$one['d'] = xmlentities($one['d']);
				if (isset($one['p']))
					$one['p'] = xmlentities($one['p']);
				$menu[] = $one;
			}
		}
		if ($menu)
			$_POST['menu'] = $menu;
		unset($a);
	}else $_POST['menu'] = [];
	$ivr =['greet-long' =>xmlentities($_POST['greet-long']),'timeout' => intval($_POST['timeout']),'greet-short' => xmlentities($_POST['greet-short']),'max-timeouts' => intval($_POST['max-timeouts']),'invalid-sound' => xmlentities($_POST['invalid-sound']),'exit-sound' => xmlentities($_POST['exit-sound']),'digit-len' => intval($_POST['digit-len']),	'inter-digit-timeout' =>intval($_POST['inter-digit-timeout']),'confirm-key'=>xmlentities($_POST['confirm-key']),'max-failures'=>intval($_POST['max-failures']),'menu'=>$_POST['menu']];
	$ivr1 = $ivr;
	$ivr = json_encode($ivr);
	$out_config =['callerout' =>$_POST['callerout'],'callerout_name' => $_POST['callerout_name'],'callerout_id' => $_POST['callerout_id'],'callerout_gw' => $_POST['callerout_gw'],'callerout_gw_name' => $_POST['callerout_gw_name'],	'callerout_to' =>$_POST['callerout_to'],'callerout_to_prefix' =>$_POST['callerout_to_prefix']];
	$out_config1 = $out_config;
	$out_config = json_encode($out_config);
	
	$validRegExp =  '/^[a-z0-9\-\_\.]+$/';
	$prefixlen = strlen($_POST['domain_id']);
	if ($prefixlen && ($prefixlen>100 || !preg_match($validRegExp, $_POST['domain_id']))) {
		$showinfo .= "<span class='bgred'>域标识必须是小写字母数字！且不得超过100位，请修改域名称</span><br/>";
		$fail = 1;
		$prefix = "";
	}
	if ($id==0 && array_search($domain_id, $domain_lists)!==false){
		$showinfo .= "<span class='bgred'>域标识必须唯一，请修改域名称</span><br/>";
		$fail = 1;
	}

	if (!empty($_POST['parent_id'])){
		$temp = explode(" ", $_POST['parent_id']);
		$parent_id = intval($temp[0]);
		$domain_up = $_POST['parent_id'];
	}else {
		$domain_up = " <span class=\"smallgray smallsize-font\"> *无上级域* </span>";
		$parent_id = 0;
	}
	$level = intval($_POST['level']);
	if ($level>120)
		$level = 120;
	elseif ($level<0)
		$level = 0;

	$change_user = 0;
	if ($id && ($_POST['domain_id']!=@$row['domain_id'])){
		$change_user = 1;
		$olddid = @$row['domain_id'];
		$showinfo .= "<span class='bgblue'>域标识已经修改！将同步修改相关数据！</span><br/>";
	}
	$dmold = crc32($domain_name.$domain_id.$level.$user_prefix.$parent_id.$group_prefix.$did.$agent_login.$agent_out.$agent_break.$callcenter.$ivr.$out_prefix.$out_config.$autocall_self.$autocall_lines);
	$domain_name = $mysqli->real_escape_string($domain_name);
	$callcenter =  $mysqli->real_escape_string($callcenter);
	$ivr = $mysqli->real_escape_string($ivr);
	$out_config =  $mysqli->real_escape_string($out_config);
	if ($id)
		$sql .= "`domain_id`='$domain_id',`domain_name`='$domain_name',`level`=$level,`parent_id`=$parent_id,`last_date`=now(),`user_prefix`='$user_prefix',`group_prefix`='$group_prefix',`DID`='$did',`agent_out`='$agent_out',`agent_login`='$agent_login',`agent_break`='$agent_break',`callcenter_config`='$callcenter',`ivr_config`='$ivr',`out_prefix`='$out_prefix',`out_config`='$out_config',`autocall_self`='$autocall_self',`autocall_lines`='$autocall_lines'";
	else
		$sql .= "'$domain_id','$domain_name',$level,'$parent_id',now(),now(),'$user_prefix','$group_prefix',$did,$agent_login,$agent_out,$agent_break,'$callcenter','$ivr',$out_prefix,'$out_config','$autocall_self','$autocall_lines'";
	$gwold= "";
	$didoldfile = "<input type=\"hidden\" name=\"didoldfile\" value=\"$_POST[didoldfile]\">";
}else{
	$domain_name = @$row['domain_name'];
	$domain_id = @$row['domain_id'];
	$level = (@$row['level']?$row['level']:50);
	$out_prefix =  (@$row['out_prefix']?$row['out_prefix']:7);
	$user_prefix = (@$row['user_prefix']?$row['user_prefix']:8);
	$group_prefix = (@$row['group_prefix']?$row['group_prefix']:9);
	$parent_id = intval(@$row['parent_id']);
	$did = @$row['DID'];
	$agent_login = (@$row['agent_login']?$row['agent_login']:50);
	$agent_out = (@$row['agent_out']?$row['agent_out']:51);
	$agent_break = (@$row['agent_break']?$row['agent_break']:52);
	$autocall_self = (@$row['autocall_self']?$row['autocall_self']:6);
	$autocall_lines = (@$row['autocall_lines']?$row['autocall_lines']:3);
	$callcenter = @$row['callcenter_config'];
	$ivr = @$row['ivr_config'];
	$out_config =  @$row['out_config'];
	$gwold = "";
	$dmold = crc32($domain_name.$domain_id.$level.$user_prefix.$parent_id.$group_prefix.$did.$agent_login.$agent_out.$agent_break.$callcenter.$ivr.$out_prefix.$out_config.$autocall_self.$autocall_lines);
	$domain_up = "<select name='parent_id' id='parent_id' class='inputline1'>$domain_up</select><script>";
	$cc = $ivr1= $out_config1 = false;
	if ($callcenter)
		$cc = json_decode($callcenter,true);
	if ($ivr)
		$ivr1 = json_decode($ivr,true);
	if ($out_config)
		$out_config1 = json_decode($out_config,true);
	if (!is_array($cc))
		$cc = ['strategy'=>'longest-idle-agent','moh-sound'=>'$${hold_music}','record-template'=>'$${recordings_dir}/${strftime(%Y/%m/%d)}/${uuid}.wav','time-base-score'=>'system','max-wait-time'=>0,'max-wait-time-with-no-agent'=>0,'max-wait-time-with-no-agent-time-reached'=>5,'tier-rules-apply'=>'false','tier-rule-wait-second'=>300,'tier-rule-wait-multiply-level'=>'false','tier-rule-no-agent-no-wait'=>'false','abandoned-resume-allowed'=>'false','discard-abandoned-after'=>60];
	if (!is_array($ivr1))
		$ivr1 =['greet-long' =>'','timeout' => '30000','greet-short' => 'ivr/ivr-enter_ext.wav','max-timeouts' => '','invalid-sound' => 'ivr/ivr-please_check_number_try_again.wav',	'exit-sound' => 'voicemail/vm-goodbye.wav','digit-len' =>4,	'inter-digit-timeout' =>3000,'confirm-key'=>'','max-failures'=>3];
	if (!is_array($out_config1))
		$out_config1 =['callerout' =>'default','callerout_name' => '','callerout_id' => '','callerout_gw' => 'default','callerout_gw_name' => '',	'callerout_to' => '','callerout_to_prefix' => ''];
	$menu = isset($ivr1['menu'])?$ivr1['menu']:[];
	if ($parent_id)
		$domain_up .= "$('#parent_id').val('$parent_id $domain_lists[$parent_id]');</script>";
	else 
		$domain_up .="$('#parent_id').val('');</script>";
	$gwold = "<input type=\"hidden\" name=\"dmold\" value=\"$dmold\">";
	$didoldfile = "<input type=\"hidden\" name=\"didoldfile\" value=\"{$level}_{$did}\">";
}	
$menuHtml = $maDefault = "";
$menucount = count($menu);
if ($menu){
foreach ($menu as $k =>$one){
	$k++;
	if  ( $one['a'] =='menu-play-sound' ){
		if (empty($one['p']))
			continue;
		$pos = strpos($one['p'], "/$domain_id/");
		if ($pos)
			$msound = "<span class=bgblue>【已上传】 </span>";
		else
			$msound = "<span class=bgblue>【当前默认】</span>";
		$input = " 按键： <input name='menu[$k][d]' oninput ='limitnum(this)' value='$one[d]' style='width:30px;'> 播放的声音上传： <input name='menu$k' type='file' class='fileInput'  accept='audio/wav'> <span class='smallgray smallsize-font'> 按下相应键值后即播放所设定的声音</span><input type='hidden' value='$one[p]' name='menu[$k][p]'> $msound";
	}elseif  ($one['a']=='menu-exec-app'){
		if (empty($one['p']))
			continue;
		$input = " 按键： <input name='menu[$k][d]' value='$one[d]' oninput ='limitnum(this)' style='width:30px;'> 转分机： <input name='menu[$k][p]' oninput ='limitnum(this)' value='$one[p]'> <span class='smallgray smallsize-font'> 按下相应键值后跳转设定分机，对应固定分机！</span>";
	}elseif ( $one['a'] =='menu-exec-app1' )
	$input = " 分机号码长度： <input name='menu[$k][d]' oninput ='limitnum(this)' value='$one[d]' style='width:30px;'> <span class='smallgray smallsize-font'> 填写分机号码的长度，如输入4，表示连续输入4位分机号后即跳转此分机！</span>";
	elseif ( $one['a'] =='menu-exec-app2' )
	$input = " 按键： <input name='menu[$k][d]' value='$one[d]' oninput ='limitnum(this)' style='width:30px;'> <span class='smallgray smallsize-font'> 按下相应键值后跳转到坐席队列，由坐席按队列顺序自动接听！</span>";
	elseif ( $one['a'] =='menu-sub'  ||  $one['a'] =='menu-say-phrase' )
	$input = " 按键： <input name='menu[$k][d]' value='$one[d]' oninput ='limitnum(this)' style='width:30px;'> 名称： <input name='menu[$k][p]' value='$one[p]'> <span class='smallgray smallsize-font'> 按下相应键值后跳转设定内容！</span>";
	else $input = " 按键： <input name='menu[$k][d]' oninput ='limitnum(this)' style='width:30px;' value='$one[d]'>";
	$menuHtml .="<p class='pcenter'><select name='menu[$k][a]'  id='s$k' onchange='changeinput(\"$k\")'><option value='menu-exec-app'>执行app</option><option value='menu-exec-app1'>拨分机号</option><option value='menu-exec-app2'>坐席自动</option><option value='menu-play-sound'>播放声音</option><option value='menu-sub'>调子菜单</option><option value='menu-say-phrase'>播放宏</option><option value='menu-back'>返回上级</option><option value='menu-top'>回主菜单</option><option value='menu-exit'>退出菜单</option></select><span id='p$k'> $input </span><span onclick='remove(this)' style='cursor:pointer;' title='删除'>&otimes;</span></p>";
	$maDefault .= "\$(\"#s$k option[value=$one[a]]\").attr(\"selected\", \"selected\");";
}
}
$html = <<<HTML
<tr class='bg1'><td width=80><em>域名称：</em></td><td><input id="domain_name" name="domain_name" size="30"  maxlength="20" value="$domain_name" onclick="this.select();" class="inputline1"/> <span class="smallgray smallsize-font"> * 长度不得超过20，可中英文，不得重复</span></td></tr>
<tr class='bg2'><td>✦<em>域标识：</em></td><td><input id="domain_id" name="domain_id" value="$domain_id" size=80 class="inputline1" readonly="readonly" /> <span class="smallgray smallsize-font"> * 不可编辑，请点击 &raquo; <button type='button' onclick="getinfo($('#domain_name').val());">由域名称生成标识</button></span></td></tr>
<tr class='bg1'><td><em>上级域：</em></td><td>$domain_up <span class="smallgray smallsize-font"> * 若是下级域，请选择其上级的域；否则忽略之</span></td></tr>
<tr class='bg2'><td><em>信息项</em>$gwold $didoldfile</td><td style='line-height:20pt;'><em>level</em> <input id="level" name="level" value="$level" size=2 class="inputline1" /> &nbsp; <em>用户前缀</em> <input id="user_prefix" name="user_prefix" value="$user_prefix" size=4 maxlength="3" class="inputline1" /> &nbsp; <em>组前缀</em> <input id="group_prefix" name="group_prefix" value="$group_prefix" size=4  maxlength="3" class="inputline1" /><span class="smallgray smallsize-font"> * 用户前缀和组前缀：用来在拨号时区分用户和组的前缀数字，不得相同，不得为空</span></td></tr>
<tr class='bg1'><td><em>呼叫中心：</em></td><td style='line-height:25pt;'><em>DID号码</em> <input id="did" name="did" value="$did" size=4  maxlength="6" class="inputline1" /><span class="smallgray smallsize-font"> * DID是呼入用户ID，4-6位数字，这里将配置为调用呼叫中心的接入号，全平台唯一，不得为空</span>
<br/><em>坐席 签入号</em> <input id="agent_login" name="agent_login" value="$agent_login" size=3  maxlength="2" class="inputline1" /> &nbsp; <em>签出号</em> <input id="agent_out" name="agent_out" value="$agent_out" size=3  maxlength="2" class="inputline1" /> <em>示忙\休息</em> <input id="agent_break" name="agent_break" value="$agent_break" size=3  maxlength="2" class="inputline1" /><span class="smallgray smallsize-font"> * 2位数字，呼叫中心坐席进行 签入\签出\示忙\休息 操作时拨打（示闲=签入）</span><br/>
<em>振铃策略strategy</em><select name='strategy' id='strategy' class='inputline1'><option value='ring-all'>所有坐席振铃</option><option value='longest-idle-agent'>空闲时长最长振铃</option><option value='round-robin'>轮循振铃</option><option value='top-down'>顺序振铃</option><option value='agent-with-least-talk-time'>通话时长最小振铃</option><option value='agent-with-fewest-calls'>接听最少振铃</option><option value='sequentially-by-agent-order'>优先级振铃</option><option value='random'>随机振铃</option><option value='ring-progressively'>渐进振铃</option></select>
<em>等待音乐moh-sound</em> <input id="moh-sound" name="moh-sound" value="{$cc['moh-sound']}" class="inputline1" /> <em>时间积分time-base-score</em><select name='time-base-score' id='time-base-score' class='inputline1'><option value='queue'>不增加积分</option><option value='system'>进入系统时积分</option></select><br/>
<em>录音设置record-template</em> <input id="record-template" name="record-template" value="{$cc['record-template']}" class="inputline1" style="width:510pt;"/><br/>
<em>最大超时max-wait-time</em> <input id="max-wait-time" name="max-wait-time" value="{$cc['max-wait-time']}" class="inputline1" size=1 /> <em>无成员超时max-wait-time-with-no-agent</em> <input id="max-wait-time-with-no-agent" name="max-wait-time-with-no-agent" value="{$cc['max-wait-time-with-no-agent']}" class="inputline1" size=1 /> <em>无成员超时后延迟max-wait-time-with-no-agent-time-reached</em> <input id="max-wait-time-with-no-agent-time-reached" name="max-wait-time-with-no-agent-time-reached" value="{$cc['max-wait-time-with-no-agent-time-reached']}" class="inputline1" size=1 /><br/>
<em>梯队匹配tier-rules-apply</em><select name='tier-rules-apply' id='tier-rules-apply' class='inputline1'><option value='false'>不启动tier规则</option><option value='true'>匹配规则（tier-rule*）</option></select> 
<em>梯队等待tier-rule-wait-second</em> <input id="tier-rule-wait-second" name="tier-rule-wait-second" value="{$cc['tier-rule-wait-second']}" class="inputline1" size=1 /> <em>梯队等级等待tier-rule-wait-multiply-level</em><select name='tier-rule-wait-multiply-level' id='tier-rule-wait-multiply-level' class='inputline1'><option value='false'>不启用</option><option value='true'>启用</option></select><br/>
<em>跳过无座席tier-rule-no-agent-no-wait</em><select name='tier-rule-no-agent-no-wait' id='tier-rule-no-agent-no-wait' class='inputline1'><option value='false'>不启用</option><option value='true'>启用</option></select>
<em>呼入丢弃恢复abandoned-resume-allowed</em><select name='abandoned-resume-allowed' id='abandoned-resume-allowed' class='inputline1'><option value='false'>不恢复</option><option value='true'>可恢复</option></select> 
<em>呼入丢弃超时discard-abandoned-after</em> <input id="discard-abandoned-after" name="discard-abandoned-after" value="{$cc['discard-abandoned-after']}" class="inputline1" size=1 /> 
</td></tr><script>var num=$menucount;$('#strategy').val('$cc[strategy]');$('#time-base-score').val('{$cc['time-base-score']}');$('#tier-rules-apply').val('{$cc['tier-rules-apply']}');$('#tier-rule-wait-multiply-level').val('{$cc['tier-rule-wait-multiply-level']}');$('#abandoned-resume-allowed').val('{$cc['abandoned-resume-allowed']}');$('#tier-rule-no-agent-no-wait').val('{$cc['tier-rule-no-agent-no-wait']}');</script>
<tr><td><em>IVR菜单：</em></td><td style='line-height:25pt;'><span class="smallred smallsize-font"> * 下面 欢迎及操作提示语音 或 菜单项 没有填时，ivr无效！其他项不填或0则忽略，时间为毫秒；如果单纯设接入时重复播放语音，请设置上面 呼叫中心  等待音乐！</span><br/>欢迎及操作提示语音 <input id="greet-long" name="greet-long" value="{$ivr1['greet-long']}" class="inputline1" placeholder=" 未指定语音文件..."/> ，若在总超时时间 <input id="timeout" name="timeout" value="{$ivr1['timeout']}" class="inputline1" size=1 /> 内未输入，播放待输入提示语音 <input id="greet-short" name="greet-short" value="{$ivr1['greet-short']}" class="inputline1"  placeholder="  未指定语音文件..."/><br/>若用户一直未输入，系统在播放 <input id="max-timeouts" name="max-timeouts" value="{$ivr1['max-timeouts']}" class="inputline1" size=1 /> -1次待输入提示语音后关闭ivr<br/>如果用户在总超时时间内输入了错误信息，系统会播放输入错误语音 <input id="invalid-sound" name="invalid-sound" value="{$ivr1['invalid-sound']}" class="inputline1" placeholder="  未指定语音文件..." />，最多允许输入错误 <input id="max-failures" name="max-failures" value="{$ivr1['max-failures']}" class="inputline1" size=1 /> 次<br/>退出时播放结束语音 <input id="exit-sound" name="exit-sound" value="{$ivr1['exit-sound']}" class="inputline1"  placeholder="  未指定语音文件..."/> ，菜单长度 <input id="digit-len" name="digit-len" value="{$ivr1['digit-len']}" class="inputline1" size=1 />  位数字，等待输入超时 <input id="inter-digit-timeout" name="inter-digit-timeout" value="{$ivr1['inter-digit-timeout']}" class="inputline1" size=1 /> ，输入按<input id="confirm-key" name="confirm-key" value="{$ivr1['confirm-key']}" class="inputline1" size=1 />键结束(默认#) <br/> >>> <input type='button' value='点这添加菜单项' onclick='add(num)'> <span class="smallgray smallsize-font" style="line-height:14pt;"><br/> 按键是数值如 1、5、23 或 正则表达式如 /^(10[01][0-9])$/ <br/>app参数如 transfer 9996 XML default 或 bridge sofia/gateway/xx/123456789<br/>调菜单和宏用菜单或宏的名称</span><span id='menu_area'>$menuHtml</span></td></tr>
<script type="text/javascript">
$maDefault
//添加一行<tr>
function add() {
num++;
var content = "<p class='pcenter'>";
content += "<select name='menu["+num+"][a]'><option value='menu-exec-app'>执行app</option><option value='menu-exec-api'>执行api</option><option value='menu-play-sound'>播放声音</option><option value='menu-sub'>调子菜单</option><option value='menu-say-phrase'>播放宏</option><option value='menu-back'>返回上级</option><option value='menu-top'>回主菜单</option><option value='menu-exit'>退出菜单</option></select> 按键 <input name='menu["+num+"][d]'> 参数 <input name='menu["+num+"][p]' size=60> <span onclick='remove(this)' style='cursor:pointer;'  title='删除'>&otimes;</span>";
content +="</p>"
$("#menu_area").append(content);
}
//删除当前行
function remove(obj) {
$(obj).parent().remove();
}
function changeinput(sid) {
getv = $('#s'+sid).val();
if  ( getv =='menu-play-sound' )
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> 播放的声音上传： <input name='menu"+sid+"' type='file' class='fileInput'  accept='audio/wav'> <span class='smallgray smallsize-font'> 按下相应键值后即播放所设定的声音</span>");
else
if  (getv =='menu-exec-app')
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> 转分机： <input name='menu["+sid+"][p]' oninput ='limitnum(this)'> <span class='smallgray smallsize-font'> 按下相应键值后跳转设定分机，对应固定分机！</span>");
else
if ( getv =='menu-exec-app1' )
	$('#p'+sid).html(" 分机号码长度： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> <span class='smallgray smallsize-font'> 填写分机号码的长度，如输入4，表示连续输入4位分机号后即跳转此分机！</span>");
else
if  (getv =='menu-exec-app2')
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'> <span class='smallgray smallsize-font'> 按下相应键值后跳转到坐席队列，由坐席按队列顺序自动接听！</span>");
else
if  (getv =='menu-sub' || getv =='menu-say-phrase' )
$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'>  名称： <input name='menu["+sid+"][p]'> <span class='smallgray smallsize-font'> 按下相应键值后跳转设定内容！</span>");
else
	$('#p'+sid).html(" 按键： <input name='menu["+sid+"][d]' oninput ='limitnum(this)' style='width:30px;'>");
}
</script>
<tr class='bg1'><td><em>外呼设置：</em></td><td style='line-height:25pt;'><em>主叫信息：</em> <label><input id="calleroutd" name="callerout" value="default" type="radio" />默认</label> &nbsp; <label><input id="calleroutm" name="callerout" value="set" type="radio" />设定：主叫名称 <input name="callerout_name" value="$out_config1[callerout_name]" class="inputline1" style="width:50pt;" onclick="$('#calleroutm').prop('checked',true);"/> 主叫ID <input name="callerout_id" value="$out_config1[callerout_id]" class="inputline1" style="width:50pt;" onclick="$('#calleroutm').prop('checked',true);"/></label> &nbsp; <label><input id="calleroutapi" name="callerout" value="api" type="radio" />curlAPI主叫ID(!)</label> <br/><span class="smallgray smallsize-font"> * 默认 用户主叫用 DID 号码；设定 指定用户的主叫；culAPI 动态获取主叫并直接修改通道变量（!自动外呼无效）需在平台参数设置API</span><br/> <em>被叫号码：</em><label><input id="callerout_to" name="callerout_to" value="" type="radio" />直接拨出</label> &nbsp; <label><input id="callerout_topre" name="callerout_to" value="prefix" type="radio" />固定前缀 <input name="callerout_to_prefix" value="$out_config1[callerout_to_prefix]" class="inputline1" style="width:50pt;" onclick="$('#callerout_topre').prop('checked',true);"/></label> &nbsp; <label><input id="callerout_toapi" name="callerout_to" value="api" type="radio" />culAPI被叫号码(!)</label><br/><span class="smallgray smallsize-font"> * culAPI 动态处理如是否允许呼出、黑名单、加区号等（!自动外呼无效），需在平台参数设置API</span><br/><em>网关路由：</em><label><input id="callerout_gwd" name="callerout_gw" value="default" type="radio" />默认</label> &nbsp; <label><input id="callerout_gwm" name="callerout_gw" value="set" type="radio" />设定 <input name="callerout_gw_name" value="$out_config1[callerout_gw_name]" class="inputline1" style="width:50pt;" onclick="$('#callerout_gwm').prop('checked',true);"/></label> &nbsp; &nbsp; <em>外呼前缀：</em> <input id="out_prefix" name="out_prefix" value="$out_prefix" size=4  maxlength="3" class="inputline1" /> &nbsp; <em>坐席自动外呼号码：</em> <input id="autocall_self" name="autocall_self" value="$autocall_self" size=4  maxlength="2" class="inputline1" />&nbsp; <em>自动外呼并发线数：</em> <input id="autocall_lines" name="autocall_lines" value="$autocall_lines" size=4  maxlength="4" class="inputline1" /><br/><span class="smallgray smallsize-font"> * 默认 指使用\$\${default_provider}；设定 指使用指定网关；</span></td></tr>
<script type="text/javascript">
if ('$out_config1[callerout]'=='default') $('#calleroutd').prop('checked','checked'); else if ('$out_config1[callerout]'=='set') $('#calleroutm').prop('checked','checked'); else $('#calleroutapi').prop('checked','checked'); 
if ('$out_config1[callerout_gw]'=='default') $('#callerout_gwd').prop('checked','checked'); else $('#callerout_gwm').prop('checked','checked'); 
if ('$out_config1[callerout_to]'=='') $('#callerout_to').prop('checked','checked'); else if ('$out_config1[callerout_to]'=='prefix') $('#callerout_topre').prop('checked','checked'); else $('#callerout_toapi').prop('checked','checked');
</script>
HTML;
$submitbutton = "&nbsp; <p style='float:left'>&nbsp; <input type=\"submit\" value=\"确认提交\" style=\"width:100px;height:35px;\"/></p>";
if (!empty($_POST)){
	$submitbutton = ' &nbsp; <p style="float:left">&nbsp; <a href="?editDomain='.$id.'">刷新页面</a></p>';
	$sql  .= $sql_end;
	$result = false;
	if ($dmold ==$_POST['dmold']){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
	}elseif (!$fail)
	$result = $mysqli->query($sql);
	if ($result){
		if ("{$level}_{$did}" != $_POST['didoldfile'] ){
			$result = @unlink($_SESSION['conf_dir']."/dialplan/public/$_POST[didoldfile].xml");
			$showinfo .= "<span class='bggreen'>原域的DID拨号计划被成功删除！</span><br/>";
		}
		$showinfo .= "<span class='bggreen'>操作成功！</span>";
		
		if ($change_user){
			$mysqli->query("update fs_gateways set `domain_id`= '$domain_id' where `domain_id`='$olddid' ");
			$mysqli->query("update fs_users set `domain_id`='$domain_id' where  `domain_id`='$olddid' ");
			$mysqli->query("update fs_groups set `domain_id`='$domain_id' where  `domain_id`='$olddid' ");
		}
	}else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
<script>function getinfo(sid){if (sid=='') {alert ('没有填写域名称，请先填写域名称！');}else \$.post( "Yurun/get_py.php", {string: sid}).done(function( data ) { $('#domain_id').val(data);});}</script>
</head><body><p class='pcenter' style='font-size:18pt;'>域详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回域主控页</a></p><form method="post" id="formarea"><table class="tablegreen" width="1000" align="center"><th colspan=2>$showinfo</th>$html<tr class='bg2'><th></th><th><p style="float:left">&nbsp; <span class="smallgray smallsize-font">  **用户标识（拨打两个*加用户id），如 **12345 为对12345的呼出通话进行强行代接 <br/>用户外呼录音文件均为以uuid命名</span> </p> $submitbutton</th></tr></table></form></body></html>
HTML;
	exit;
}

//-----------域管理---------ajax提交部署、停用、启用、禁用、删除等域的相关操作-------------------------------------------
$ext_result = $mysqli->query("select `domain_name`,`id` from fs_domains");
$exts = result_fetch_all($ext_result,MYSQLI_NUM);
$dmlist = array();
foreach ($exts as $one)
	$dmlist[$one[1]] = $one[0];
//----------------------显示----------域列表--------------------------------------------------------------------------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css\main.css\"/><script src=\"css\jquery.js\"></script><script>
function del(sid){var a = confirm(\"警告！！\\n删除操作同时也会清除本域全部的组及用户设置，不可撤销！！\\n你确认提交？\");if (a) { \$.post( \"FS_domains_func.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"FS_domains_func.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"FS_domains_func.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en66(sid,lab){\$.post( \"FS_domains_func.php\", { yid: sid, en6: \"66\", en1: lab})
  .done(function( data ) { alert( \"启用域外呼任务程序 \" + data);$('#info'+sid).html('外呼程序已调用！');});}
function en77(sid,lab){\$.post( \"FS_domains_func.php\", { yid: sid, en6: \"77\", en1: lab})
  .done(function( data ) { alert( \"停止域外呼任务程序 \" + data);$('#info'+sid).html('外呼程序已停止！');});}
function en88(sid,lab){\$.post( \"FS_domains_func.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"FS_domains_func.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script></head><body>";
$where = " where 1 ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['gwname'])){
	$temp = $mysqli->real_escape_string($_GET['gwname']);
	$where .= " and `domain_name` like '%$temp%' ";
	$showget .=" 域名称包含 '$temp' ";
}

$count = 20;
$getstr = "";
$totle = $mysqli->query("select count(*) from fs_domains $where");
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
	echo '<p class="pcenter" style="font-size:18pt;">域管理控制台 '.$showget.'  <a style="font-size:12pt;" href="?editDomain=0">【新建域】</a> &nbsp; <a style="font-size:10pt;" href="main.php">返回主控</a></p><table class="tablegreen" width="90%" align="center"><th colspan=7><form method="get">域名称：<input id="gwname" name="gwname" value="" size=10>  <input type="submit" value="确认"> <a href="?">【看全部】</a>	 &nbsp; <a style="font-size:10pt;" href="FS_callcenter_cp.php">【呼叫中心】</a> &nbsp; <a href="FS_tasks_cp.php">【外呼任务管理】</a></form></th>';
	$result = $mysqli->query("select * from fs_domains $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=7 align=center><span class="smallred smallsize-font"> *域新建后默认被禁用，需启用后方可应用！已应用的域可获取信息 或 停用；域设置后需启用，并需在拨号计划 或 用户管理中进行调用<br/> *必须先设置internal，禁用 force-register-domain force-subscription-domain force-register-db-domain，否则域/组/用户 设置均无效！！</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			if ($row['enabled']){
				$file_ = @$_SESSION['conf_dir']."/directory/".$row['domain_id'].".xml";
				if (is_file($file_)){
					$showalert= ' <span class="bggreen">已应用 </span>&nbsp; '.$row['id'].' &nbsp; <em class=\'red\'>'.$row['domain_name'].'</em>';
					$showtools=" <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en99($row[id],'$row[domain_id]')\" value='停用'/> <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en66($row[id],'$row[domain_id]')\" value='开始外呼'/> <input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en77($row[id],'$row[domain_id]')\" value='停止外呼'/>";
				}else{
					$showalert= ' <span class="bgblue">已停用 </span>&nbsp; '.$row['id'].' &nbsp; <em class=\'red\'>'.$row['domain_name'].'</em>';
					$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';$(this).attr('disabled','true');en88($row[id],'$row[domain_id]')\" value='部署应用'/> &nbsp;  <input type='button' onclick=\"en0($row[id])\" value='禁止'/>";
				}
			}else 
				$showalert= ' <span class="bgred">已禁止 </span>&nbsp; '.$row['id'].'  &nbsp; <em class=\'red\'>'.$row['domain_name'].'</em>';
			
			$totle = $mysqli->query("SELECT `enabled` ,COUNT(*) FROM fs_groups WHERE `domain_id` = '$row[domain_id]'  GROUP BY `enabled` order by `enabled` ");
			$dialplans = result_fetch_all($totle);
			unset($totle);
			$totle = array('0'=>0,'1'=>0);
			foreach ($dialplans as $one){
				if ($one[0]=='1') $totle['1']=$one[1];
				else $totle[$one[0]]=$one[1];
			}
			$showguser = "含组：可用<strong> $totle[1] </strong>   不可用<strong> $totle[0] </strong>  <a href='FS_groups_cp.php?dmid=$row[domain_id]'>&raquo;&nbsp;管理组</a>";
			
			$totle = $mysqli->query("SELECT `enabled` ,COUNT(*) FROM fs_users WHERE `domain_id` = '$row[domain_id]'  GROUP BY `enabled` order by `enabled` ");
			$dialplans = result_fetch_all($totle);
			unset($totle);
			$totle = array('0'=>0,'1'=>0);
			foreach ($dialplans as $one){
				if ($one[0]=='1') $totle['1']=$one[1];
				else $totle[$one[0]]=$one[1];
			}
			$showuser = "含用户：可用<strong> $totle[1] </strong>  不可用<strong> $totle[0] </strong>  <a href='FS_users_cp.php?dmid=$row[domain_id]'>&raquo;&nbsp;管理用户</a>";
			
			if ($row['parent_id'])
				$showu = " 上级域：<strong>".$dmlist[$row['parent_id']]."</strong>";
			else 
				$showu = "<span class=\"smallgray smallsize-font\">无上级域</span>";
			$options = "Level:<strong>".$row["level"]."</strong>";
			$options .= " &nbsp; DID:<strong>".$row["DID"]."</strong>";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr $bgcolor><td>$showalert <a href='FS_jump2cc.php?domainid=$row[domain_id]'>&raquo;&nbsp;管理域</a></td><td>域标识：<a href='FS_files_edit.php?domain=$row[domain_id]'><strong>$row[domain_id]</strong></a></td><td> $showu</td><td> $showguser</td><td> $showuser</td><td>$options</td><td><a href='?editDomain=$row[id]'>详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>";
			if ($row['enabled']){
				echo $showtools;
			}else 
				echo " <button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id])\">删除</button>";
			echo "</span></td></tr>";
		}
	}
$mysqli->close();