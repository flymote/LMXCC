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
$lab = array("ext-name"=>"extension的名称标识，必须全局唯一","context-name"=>"本extension隶属的context，如public、default，默认public", "ext-continue"=>"符合后，继续后继extensions的处理 或 停止","ext-level"=>"当前排序级别，0-120，默认20，数字小优先");

include 'Shoudian_db.php';
//-------------------修改或添加Ext信息-----------------------------------
if (isset($_GET['editEXT'])){
	$id = intval($_GET['editEXT']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_extensions where id = $id");
		$sql = "update fs_extensions set ";
		$sql_end = " where id = $id";
		$showinfo .="[ id $id ] ";
	}else{
		$result = false;
		$tmp = implode(array_keys($lab), "`,`");
		$sql = "insert into fs_extensions (`$tmp`) values(";
		$sql_end = " )";
		$showinfo .=" 当前为新加 ";
	}	

	if ($result)
		$row = $result->fetch_array();
	else 
		$row = array();
$html = "";
$i = 0;
$tmp = array();
if (isset($row['ext-continue']) && $row['ext-continue']=='1')
	$row['ext-continue'] = 'true';
else 
	$row['ext-continue'] = 'false';
$post_botton_enabled = "";
if (isset($row['ext-name'])){
	$file_ = @$_SESSION['conf_dir']."/dialplan/".$row['context-name']."/".$row['ext-level']."_".$row['ext-name'].".xml";
	if (is_file($file_)){
		$post_botton_enabled = "disabled=\"disabled\"";
		$showinfo .= "<span class='bgred'>已经部署生效，不得修改，要修改需先停用！</span><br/>";
		if (!empty($_POST))
			unset($_POST);
	}
}
$css = 'inputline';
foreach ($lab as $key=>$value){
	$i++;
	if (isset($_POST[$key])){
		if ($key=="ext-level"){
			if ($_POST[$key]=='')
				$_POST[$key] = 20;
			else
				$_POST[$key] = intval($_POST[$key]);
			if ($_POST[$key]>120)
				$_POST[$key] = 120;
			elseif ($_POST[$key]<0)
				$_POST[$key] = 0;
				$showv = $v = $_POST[$key];
		}elseif ($key=='ext-continue'){
			if ($_POST[$key]!='true'){
				$_POST[$key] = 0;
				$showv ='false';
				$showinfo .= "<span class='bgblue'>是否继续 被设为 false </span><br/>";
			}else{
				$_POST[$key] = 1;
				$showv ='true';
			}
			$v = $_POST[$key];
		}else{
			if ($key=='context-name')
				if (empty($_POST[$key]))
					$_POST[$key] = 'public';
			$v = $mysqli->real_escape_string($_POST[$key]);
			$showv = htmlentities($v,ENT_QUOTES,"UTF-8");
		}
		$css = 'inputline1';
	}else{
		$v = empty($row[$key])?"":$row[$key];
		$showv = htmlentities($v,ENT_QUOTES,"UTF-8");
	}
	if ($id){
		$tmp[] = "`$key` = ".(is_int($v)?$v:"'$v'");
	}else
		$tmp[] = (is_int($v)?$v:"'$v'");
	$value = htmlentities($value,ENT_QUOTES,"UTF-8");
	if  ($key=='ext-continue') 
		$input = "执行后：<label><input id=\"yes\" name=\"$key\"  type=\"radio\" value=\"true\">不论匹配都继续  </label> <label><input id=\"no\" name=\"$key\" type=\"radio\" value=\"false\">匹配即停止，缺省</label>";
	else 
		$input = "<input id=\"$key\" name=\"$key\" value=\"$showv\" size=45 onclick=\"this.select();\" class=\"$css\"/>";
	$html .= "<tr><td><em>$key ：</em><br/>$input</td><td width=\"50%\"><span class=\"smallred smallsize-font\">$value</span></td></tr>";
}
if (!empty($_POST)){
	$post_botton_enabled = "disabled=\"disabled\"";
	$sql .= implode($tmp, ","); 
	$sql  .= $sql_end;
	$result = true;
	if (empty($_POST['ext-name'])){
		$result = false;
		$showinfo .= "<span class='bgred'>extension名称必须填写！</span><br/>";
	}else{
		$validRegExp =  '/^[a-zA-Z0-9_\-~#]+$/';
		if (!preg_match($validRegExp, $_POST['ext-name'])) {
			$result = false;
			$showinfo .= "<span class='bgred'>extension名称不能用中文不能用引号括号等，必须是英文字母数字及 _-~# </span><br/>";
		}
		if ($result)
			$result = $mysqli->query($sql);
		if ($result){
			$showinfo .= "<span class='bggreen'>操作成功！</span>";
		}else 
			$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
	}
}
$a = $row['ext-continue'];
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
 </head><body><p class='pcenter' style='font-size:18pt;'>extension详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回extension主控页</a></p>
<table class="tablegreen" width="800" align="center"><form method="post"><th colspan=2>* extension名称必须唯一，橙色说明的部分必须设置<br/>$showinfo</th>
$html
<th><script>var sfys = "$a"; if (sfys == 'true') $("#yes").attr('checked', 'checked'); else $("#no").attr('checked', 'checked');</script></th><th>  <input type="submit" value="确认提交" onclick="return confirm('请谨慎操作，是否确认提交？');" $post_botton_enabled/></th>
</table></body></html>
HTML;
exit;
}
//-----------extension管理-------extension数据库 ---POST提交操作-----------------------------
//应用部署及停用，ESL
if (empty($_SESSION['POST_submit_once']) && isset($_POST['yid'])){
	if (in_array($_POST['en0'],array("88","99"))) {
		$yid = intval($_POST['yid']);
		$name = $_POST['en1'];
		$result = $mysqli->query("select * from fs_extensions where id = $yid and `ext-name` = '$name' and `enabled`=1");
		$row = $result->fetch_array();
		if (empty($row))
			die("所选择部署的项目尚未启用，请先启用！");
		$file_ = @$_SESSION['conf_dir']."/dialplan/".$row['context-name']."/".$row['ext-level']."_".$row['ext-name'].".xml";
		$_SESSION['POST_submit_once']=1;
		if ($_POST['en0']=="99" && is_file($file_)){
			$result = unlink($file_);
			if ($result){
				require_once "detect_switch.php";
				$info = new detect_switch();
				$info-> run('reloadxml','',0);
				die(" $name extension已被停用！");
			}else 
				die("$name extension数据无法清除，无法停用！");
		}else{
			$result_dial = $mysqli->query("select * from fs_dialplans where `ext-id` = '$row[id]' and `enabled`=1 order by `level`");
			$row_dial = result_fetch_all($result_dial,MYSQLI_ASSOC);
			if (empty($row_dial))
				die("$name ，因无可用拨号计划，部署被取消！");
			$xml = "<include>\n<extension name=\"".$row['ext-name']."\"";
			if ($row['ext-continue'])
				$xml .= ' continue="true" ';
			$xml .= ">\n";
			foreach ($row_dial as $one){
				if (empty($one['prefix']) && empty($one['destnumber-len']))
						$regex = false;
				else{
					$regex = "^";
					if ($one['prefix'])
						$regex .="$one[prefix]";
					if($one['destnumber-len']){
						$one['destnumber-len'] = str_replace('-', ',', $one['destnumber-len']);
						$regex .="(\\d{".$one['destnumber-len']."})$";
					}else
						$regex .="(\\d+)$";
				}
				if  ($regex)
					$xml .= " <condition field=\"destination_number\" expression=\"$regex\"";
				
				if ($one['break'])
					$break = "break=\"$one[break]\"";
				else
					$break ="";
//处理动作命令
if (!function_exists("getACT")){
function getACT($act_string){
	if (empty($act_string))
		return "";
	$acts = explode("\n", $act_string);
	$action = "";
	foreach ($acts as $r1){
		$act_str = explode("===", trim($r1));
		if (strpos($act_str[0],'!!')===0)
			$action .="<anti-action application=\"".substr($act_str[0],2)."\"";
		else 
			$action .="<action application=\"$act_str[0]\"";
		if (isset($act_str[1])){
			$inline = (substr($act_str[1],-7)==' inline'?' inline="true"':'');
			$find = strpos($act_str[1],'![CDATA[');
			if ($find)
				$action .= " $inline >$act_str[1]</action>\n";
			else
				$action .= " data=\"$act_str[1]\" $inline />\n";
		}else 
			$action .= " />\n";
	}
	return $action;
}
}
				$action = getACT($one['act']);
				if (!empty($one['recording'])){
					if ($one['recording']=="=LMX="){
						$action .="<action application=\"set\" data=\"RECORD_TITLE=Recording \${destination_number} \${caller_id_number} \${strftime(%Y-%m-%d %H:%M:%S)}\"/>\n<action application=\"set\" data=\"RECORD_DATE=\${strftime(%Y-%m-%d %H:%M:%S)}\"/>\n<action application=\"set\" data=\"RECORD_STEREO=true\"/>\n<action application=\"record_session\" data=\"\$\${recordings_dir}/\${strftime(%Y%m%d)}/\${strftime(%Y-%m-%d-%H-%M-%S)}_\${destination_number}_\${caller_id_number}.wav\"/>\n";
					}else 
						$action .= getACT($one['recording']);
				}
				if (!empty($one['gateway'])){
					$gw = substr($one['gateway'],0,5);
					if ($gw=="=LMX="){
						$action .="<action application=\"bridge\" data=\"sofia/gateway/".substr($one['gateway'],5)."/$1\"/>\n";
					}else
						$action .= getACT($one['gateway']);
				}
				if (!empty($one['condition'])){ //有第二个条件
					if ($regex) $xml .= " />\n";
					$condition_str = explode("=LMX=", $one['condition']);
					$condition = $condition_str[0];
					$expression = @$condition_str[1];
					switch ($condition){
						case 'regex-any': 
								$xml .= " <condition regex=\"any\" $break>\n";
								$regs = explode("\n", $expression);
								foreach ($regs as $r1){
									$reg_str = explode("===", trim($r1));
									if (!isset($reg_str[1])) $reg_str[1] ="";
									$xml .= "<regex field=\"$reg_str[0]\" expression=\"$reg_str[1]\"/>\n";
								}
							break;
						case 'regex-all':
								$xml .= " <condition regex=\"all\" $break>\n";
								$regs = explode("\n", $expression);
								foreach ($regs as $r1){
									$reg_str = explode("===", trim($r1));
									if (!isset($reg_str[1])) $reg_str[1] ="";
									$xml .= "<regex field=\"$reg_str[0]\" expression=\"$reg_str[1]\"/>\n";
								}
							break;
						case 'regex-xor':
								$xml .= " <condition regex=\"xor\" $break>\n";
								$regs = explode("\n", $expression);
								foreach ($regs as $r1){
									$reg_str = explode("===", trim($r1));
									if (!isset($reg_str[1])) $reg_str[1] ="";
									$xml .= "<regex field=\"$reg_str[0]\" expression=\"$reg_str[1]\"/>\n";
								}
							break;
						case 'specify-var'://自定义变量的，在值里面用===分隔切分
							$field_str = explode("===", $expression);
							$condition = $field_str[0];
							$expression = $field_str[1];
						default:
							$find = strpos($expression,'![CDATA[');
							if ($find)
								$xml .= " <condition field=\"$condition\" expression=\"$expression\" $break>\n";
							else
								$xml .= " <condition field=\"$condition\" $break>\n<expression>$expression</expression>\n";
					}
					$xml .=$action;
					$xml .= "</condition>\n";
				}else{
					if ($regex) $xml .= " >\n";
					else $xml .="<condition>\n";
					$xml .=$action;
					$xml .= "</condition>\n";
				}
			}

			$xml .=	"</extension>\n</include>";
			$result = file_put_contents($file_, $xml);
			unset($xml);
			if ($result){
				require_once "detect_switch.php";
				$info = new detect_switch();
				$info->run('reloadxml','',0);
				die(" $name extension已被添加并更新状态！");
			}else
				die("$name extension数据添加失败！");
		}
	}
	die("信息不完整，非法提交操作！");
}
//删除extension记录
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$mysqli->query("delete from fs_extensions where id = $id and `enabled` = 0 limit 1");
	die("id $id 操作完毕");
}

//设置启用或禁用
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_extensions set `enabled` = 1 where id = $id limit 1");
		die("id $id 设置为可用完毕");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_extensions set `enabled` = 0 where id = $id limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示----------extension数据库 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script>
function del(sid){var a = confirm(\"删除操作不可撤销，你确认提交？\");if (a) { \$.post( \"FS_extensions_cp.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"FS_extensions_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"FS_extensions_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en88(sid,lab){\$.post( \"FS_extensions_cp.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"FS_extensions_cp.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script></head><body>";
$where = " where 1 ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['context'])){
	$tmp = $mysqli->real_escape_string($_GET['context']);
	$where .= " and `context-name` = '$tmp' ";
	$showget .=" context 为 '$tmp' ";
}
if (!empty($_GET['enabled'])){
	$_GET['enabled'] = intval($_GET['enabled']);
	if ($_GET['enabled']>1) $_GET['enabled'] = 0;
	$showget .=" 查看 '".($_GET['enabled']?"可用":"不可用")."' 的信息 ";
	$where .= " and `enabled` = '$_GET[enabled]' ";
}
$count = 20;
$getstr = "";
$totle = $mysqli->query("select count(*) from fs_extensions $where");
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
	echo '<p class="pcenter" style="font-size:18pt;">extension管理控制台 '.$showget.'  <a style="font-size:12pt;" href="?editEXT=0">【 新建 】</a> <a style="font-size:10pt;" href="main.php">&raquo;&nbsp;返回主控</a></p><table class="tablegreen" width="90%" align="center"><th colspan=6><form method="get">context：<input id="context" name="context" value="" size=10> 可用否：<label><input id="a" name="enabled"  type="radio" value="0" checked>全部 </label> <label><input id="y" name="enabled"  type="radio" value="1">可用 </label> <label><input id="n" name="enabled"  type="radio" value="2">不可用 </label> <input type="submit" value="确认"> <a href="?">【看全部】</a></form></th>';
	$result = $mysqli->query("select * from fs_extensions $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=6 align=center><span class="smallred smallsize-font"> *extension新建后默认禁用，需启用后方可应用！已应用的可停用 其中拨号计划设置后需先启用，无可用拨号计划无法部署生效</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			$fromuser ="";
			if ($row['enabled']){
				$file_ = @$_SESSION['conf_dir']."/dialplan/".$row['context-name']."/".$row['ext-level']."_".$row['ext-name'].".xml";
				if (is_file($file_)){
					$showalert= '<span class="smallgray smallsize-font"> [id '.$row['id'].'] </span> <span class="bggreen">已应用 </span> &nbsp; <em>'.$row['ext-name'].'</em>';
					$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';en99($row[id],'".$row['ext-name']."')\" value='停用'/>";
				}else{
					$showalert= '<span class="smallgray smallsize-font"> [id '.$row['id'].'] </span>  <span class="bgblue">已停用 </span> &nbsp; <em>'.$row['ext-name'].'</em>';
					$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';en88($row[id],'".$row['ext-name']."')\" value='部署应用'/> &nbsp;  <input type='button' onclick=\"en0($row[id])\" value='禁止'/>";
				}
			}else 
				$showalert= '<span class="smallgray smallsize-font"> [id '.$row['id'].'] </span>  <span class="bgred">已禁止 </span> &nbsp; <em>'.$row['ext-name'].'</em>';
				$showuser = $context = $continue= "";
			$totle = $mysqli->query("SELECT `enabled` ,COUNT(*) FROM fs_dialplans WHERE `ext-id` = $row[id]  GROUP BY `enabled` order by `enabled` ");
			$dialplans = result_fetch_all($totle);
			unset($totle);
			$totle = array('0'=>0,'1'=>0);
			foreach ($dialplans as $one){
				if ($one[0]=='1') $totle['1']=$one[1];
				else $totle[$one[0]]=$one[1];
			}
			$showuser .= "包含拨号计划：<strong> $totle[1] </strong> 条可用  <strong> $totle[0] </strong> 条不可用 <a href='FS_dialplans_cp.php?extid=$row[id]'>&raquo;&nbsp;管理</a>";
			if ($row['context-name'])
				$context = " 隶属context：<strong>".$row['context-name']."</strong>";
			if ($row['ext-continue']=='1')
				$continue .= "<span class=\"smallblue smallsize-font\">不论匹配都继续</span>";
			else
				$continue .= "<span class=\"smallred smallsize-font\">匹配成功后停止</span>";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr $bgcolor><td>$showalert</td><td align=center> $showuser <span id='gwinfo$row[id]'></span></td><td>$context</td><td>$continue  </td><td>优先级：".$row['ext-level']."</td><td><a href='?editEXT=$row[id]'>&raquo;&nbsp;详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>";
			if ($row['enabled']){
				echo $showtools;
			}else 
				echo " <button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id])\">删除</button>";
			echo "</span></td></tr>";
		}
	}
$mysqli->close();
