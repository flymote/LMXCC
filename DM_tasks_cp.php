<?php
define('APPID', 'lmxcc');
set_time_limit(60);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
$phpFileUploadErrors = array(
		0 => '文件上传成功！',
		1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。',
		2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。 ',
		3 => '文件只有部分被上传。 ',
		4 => '没有文件被上传。',
		6 => '找不到临时文件夹。',
		7 => '文件写入失败。',
		8 => '一个PHP的扩展库终止了文件上传操作。',
);
if (empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");
$domain_id = $_SESSION['domainid'];
include_once 'DM_db.php';
	
function insertPhones($info){
	global $mysqli;
	include_once 'PHPExcel/Classes/PHPExcel/FileAuto.php';
	set_time_limit(600);
	ini_set('memory_limit', '500M');
	if (file_exists ( __DIR__."/$info[domain_id]/$info[phones]"))
		$objPHPExcel = PHPExcel_IOFactory::load(__DIR__."/$info[domain_id]/$info[phones]");
	else 
		die("\n文件不存在，无法完成导入！操作取消");
	if (!$objPHPExcel)
		die("\n上传的文件不正确，无法完成导入！操作取消");
	$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
	unset($objPHPExcel);
	$co = [0,0];
	foreach ($sheetData as $one){
		if (is_numeric($one['A'])){
			$re = $mysqli->query("SELECT id FROM fs_phones WHERE ((`enabled`=1 AND `iscalled`=0 ) OR (`iscalled`=1 AND `datetime` > DATE_SUB(NOW(), INTERVAL 1 DAY))) AND `type`='$info[type]' AND `phone` = '$one[A]'");
			if ($re){
				$ro = $re->fetch_row();
				if ($ro){
					$co[0]++;
					continue;
				}else{
					$mysqli->query("insert into fs_phones (`enabled`, `iscalled`, `type`, `phone`,`taskid`,`level`,`domain_id`) values( 1,0,'$info[type]','$one[A]',$info[id],$info[level],'$info[domain_id]')");
					$co[1]++;
				}
			}else
				return 0;
		}
	}
	return $co;
}

//-------------------修改或添加域信息-----------------------------------
if (isset($_GET['edittask'])){
	$id = intval($_GET['edittask']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_tasks where id = $id");
		$sql = "update fs_tasks set ";
		$sql_end = " where id = $id";
		$showinfo .=" 任务 id $id 更新 <br/>";
	}else{
		$result = false;
		$sql = "insert into fs_tasks (`name`,`domain_id`,`type`,`level`,`sound`,`phones`,`tocc`,`enabled`,`datetime`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 新任务<br/>";
	}

$fail = 0;
if ($result)
	$row = $result->fetch_array();
else 
	$row = array();

if (!empty($_POST)){
	$task_name = $mysqli->real_escape_string($_POST['task_name']);
	if (empty($_POST['type']) || empty($task_name)) {
		$showinfo .= "<span class='bgred'>必须提交任务标识、业务类型！</span><br/>";
		$fail = 1;
	}
	$type = $_POST['type'];
	$level = intval($_POST['level']);
	$tocc = intval(@$_POST['tocc']);
	$time =	date("Ymdhis");
	$sound = $phones = "";
	if (!empty($_FILES["sound"]["name"])){ //若数据可用，保存上传文件
		// 			$name= $_FILES["userfile"]["name"];
		$size= $_FILES["sound"]["size"];
		$temp= $_FILES["sound"]["tmp_name"];
		$error= $_FILES["sound"]["error"];
		if (!empty($_FILES["sound"]["error"]))
			$error = "上传文件失败！错误代码：$error  ".$phpFileUploadErrors[$error];
		else
			$error = $phpFileUploadErrors[$error];
		$showinfo .= "任务语音上传  $error <br/>";
		if ($size<12000000 &&  $_FILES["sound"]["type"]=="audio/wav"){
			if (!file_exists ( __DIR__."/$domain_id"))
				mkdir( __DIR__."/$domain_id");
			$re = move_uploaded_file($temp, __DIR__."/$domain_id/task_$time.wav");
		}else{
			$showinfo .="语音文件的文件不能太大，文件类型需WAV文件！";
			$re = 0;
		}
		if ($re)
				$sound = "task_$time.wav";
	}
	if (!empty($_FILES["phones"]["name"])){ //若数据可用，保存上传文件
		$size= $_FILES["phones"]["size"];
		$temp= $_FILES["phones"]["tmp_name"];
		$extname = strrchr($_FILES["phones"]["name"],".");
		$error= $_FILES["phones"]["error"];
		if (!empty($_FILES["phones"]["error"]))
			$error = "上传文件失败！错误代码：$error  ".$phpFileUploadErrors[$error];
		else
			$error = $phpFileUploadErrors[$error];
		$showinfo .= "任务号码上传  $error <br/>";
		if ($size<12000000 &&  ($_FILES["phones"]["type"]=="application/vnd.ms-excel" || $_FILES["phones"]["type"]=="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")){
			if (!file_exists ( __DIR__."/$domain_id"))
				mkdir( __DIR__."/$domain_id");
			$re = move_uploaded_file($temp, __DIR__."/$domain_id/task_$time$extname");
		}else{
			$showinfo .="号码文件的文件不能太大，文件类型需excel或csv类型！";
			$re = 0;
		}
		if ($re)
			$phones = "task_$time$extname";
	}
	if ($id){
		$soundstr = $phonestr = "";
		if (!empty($sound))
			$soundstr = "`sound`='$sound',";
		if (!empty($phones))
			$phonestr = "`phones`='$phones',";
		$sql .=" `name`='$task_name',`type`='$type',`level`=$level,$soundstr$phonestr`tocc`='$tocc'";
	}else //`name`,`domain_id`,`type`,`level`,`sound`,`tocc`,`enabled`,`datetime`
		$sql .= "'$task_name','$domain_id','$type','$level','$sound','$phones','$tocc',0,now()";
	$dmold ="";
}else{
	$task_name = @$row['name'];
	$task_id = @$row['id'];
	$sound = @$row['sound'];
	$phones = @$row['phones'];
	$type = @$row['type'];
	$tocc = isset($row['tocc'])?$row['tocc']:1;
	$level = @$row['level'];
	$datetime = @$row['datetime'];
	$dmold = $task_name.$type.$level.$tocc;
}	
if ($tocc)
	$toccjs = '$("#tocc").prop("checked","checked");';
else
	$toccjs = '$("#tocc").prop("checked",false);';
if (@$row['sound'])
	$showsound="<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\" $row[name] 任务语音 \");$(\"#player\").attr(\"src\",\"$row[domain_id]/$row[sound]\");'> 【当前语音】 </button>";
else
	$showsound=" 【当前无语音】 ";
if (@$row['phones'])
	$showphones="<a href=\"$row[domain_id]/$row[phones]\" target=dd> 【当前号码文件】 </a>";
else
	$showphones=" 【当前无号码】";
$html = <<<HTML
<tr class='bg1'><td width=80><em>任务标识：</em></td><td style="line-height:60px;"><input id="task_name" name="task_name" size="20"  maxlength="20" value="$task_name" onclick="this.select();" class="inputline1"/> </td></tr>
<tr class='bg2'><td><em>话务隶属：</em></td><td style="line-height:60px;"><select id='type' name='type'  class='inputline1'><option value =''>-- 选择业务类型 --</option><option value ='房地产'>房地产</option><option value ='金融银行'>金融银行</option><option value ='互联网'>互联网</option><option value ='保险'>保险</option><option value ='教育培训'>教育培训</option><option value ='财务税务'>财务税务</option><option value ='广告创意'>广告创意</option><option value ='家具装修'>家具装修</option><option value ='法律法务'>法律法务</option><option value ='医疗保健'>医疗保健</option><option value ='旅游文化'>旅游文化</option><option value ='人力资源'>人力资源</option><option value ='娱乐休闲'>娱乐休闲</option><option value ='其他'>其他</option></select>  &nbsp; <em>优先级：</em><input name="level" maxlength="3" value="$level" onclick="this.select();" style='width:25px;' class="inputline1"/> </td></tr>
<tr class='bg1'><td><em>呼叫动作：</em></td><td style="line-height:60px;"> &nbsp; <em>接通坐席？</em> <input type="checkbox" id='tocc' name="tocc" value="1"> &nbsp; <em>播放声音？</em> <input type="hidden" name="dmold" value="$dmold"><input type="hidden" name="MAX_FILE_SIZE" value="12000000" /><input name='sound' type='file' class='fileInput'  accept='audio/wav' onchange='soundchange();'/>  <span id='soundlab' style='color:red;'></span>$showsound<script>$('#type').val('$type');$('#type').val('$type');$toccjs</script></td></tr>
<tr class='bg2'><td><em>上传号码：</em></td><td style="line-height:60px;"><input name='phones' type='file' class='fileInput'  accept='.xls,.xlsx,.csv' onchange='csvchange();'/>  <span id='csvlab' style='color:red;'></span>$showphones<br/><span class='bggray'>文件中每个号码一行！本处不验证文件正确性！！任务启用时，将验证文件并导入开始执行！</span></td></tr>
HTML;
$submitbutton = "<input type=\"submit\" value=\"确认提交\" />";
if (!empty($_POST)){
	$submitbutton = ' <a href="?edittask='.$id.'">刷新页面</a>';
	$sql  .= $sql_end;
	$result = false;
	if ($sound=='' && $phones=='' && $task_name.$type.$level.$tocc == $mysqli->real_escape_string($_POST['dmold'])){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
		$result = 0;
	}elseif (!$fail)
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
<script> function soundchange() { $("#soundlab").text('已选择文件！');};function csvchange() { $("#csvlab").text('已选择文件！');}
</script>
</head><body><div id="win" style="display:none;"><h2 id="tt"><span id="close" onclick="$('#win').css('display','none')" style="pading:5px;"> × </span></h2>
    <p id="title" class="act2"></p>
<audio id="player" controls="controls" src="">你的浏览器不支持audio标签。</audio></div><p class='pcenter' style='font-size:18pt;'>任务信息 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回任务页</a></p><table class="tablegreen" width="1000" align="center"><form  enctype="multipart/form-data" method="post"><th colspan=2>$showinfo</th>$html<tr class='bg1'><th></th><th>$submitbutton</th></tr></form></table></body></html>
HTML;
	exit;
}
//-----------域管理-------域数据库 ---POST提交操作-----------------------------
$redis = redisDB();
//删除记录
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$result = $mysqli->query("select domain_id,sound,phones from fs_tasks where id = $id");
	if ($result)
		$row = $result->fetch_array(MYSQLI_NUM);
	else die("未获取有效的域信息！");
	if($row[0] != $domain_id)
		die("非法操作！");
	if (!empty($row[1]))
		@unlink(__DIR__."/$row[0]/$row[1]");
	if (!empty($row[2]))
		@unlink(__DIR__."/$row[0]/$row[2]");
	$mysqli->query("delete from fs_tasks where id = $id and `enabled` = 0 limit 1");
	$redis->del("task_$id");
	die("id $id 操作完毕");
}
//设置启用或禁用
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	if ($id){
		if (!empty($_POST['en1'])){
			$_SESSION['POST_submit_once']=1;
			$re = $mysqli->query("select * from fs_tasks where id = $id");
			$row = $re->fetch_array();
			if ($row)
				$redis->hMset("task_$id",["enabled"=>1,"level"=>$row['level'],"sound"=>$row['sound'],"domain_id"=>$row['domain_id'],"tocc"=>$row['tocc'],"datetime"=>$row['datetime']]);
			else die("获取信息错误，无法继续！");
			$i = insertPhones($row);
			if ($i[1]){
				$mysqli->query("update fs_tasks set `enabled` = 1 where id = $id limit 1");
				die("id $id 设置为可用完毕 ！ \n 共成功提交数据 $i[1] 条，重复数据忽略 $i[0] 条  \n 外呼任务统一以域为单位批量顺序执行，请在域管理中启用/停止 ");
			}else 
				die("id $id 无成功提交数据，不能设置为可用 ！ \n 共成功提交数据 $i[1] 条，重复数据忽略 $i[0] 条");
		}
		if (!empty($_POST['en4'])){
			$_SESSION['POST_submit_once']=1;
			$mysqli->query("update fs_tasks set `enabled` = 4 where id = $id limit 1");
			$redis->hset("task_$id","enabled",0);
			$td = $redis->get("task_disabled");
			if ($td)
				$redis->set("task_disabled",$td.",$id");
			else 
				$redis->set("task_disabled",$id);
			die("id $id 设置为暂停完毕");
		}
		if (!empty($_POST['en5'])){
			$_SESSION['POST_submit_once']=1;
			$mysqli->query("update fs_tasks set `enabled` = 1 where id = $id limit 1");
			$redis->hset("task_$id","enabled",1);
			$td = $redis->get("task_disabled");
			if ($td){
				$s = explode(",", $td);
				$s1 = [];
				function w($var){global $id,$s1;if ($var && !isset($s1[$var]) && $var !=$id){ $s1[$var]=1;return $var;}}
				$s = array_filter($s,"w");
				$s = implode(",", $s);
				$redis->set("task_disabled",$s);
			}
			die("id $id 设置为恢复执行完毕");
		}
		if (!empty($_POST['en0'])){
			$_SESSION['POST_submit_once']=1;
			$mysqli->query("delete from fs_phones where `taskid`= $id and `iscalled`= 0");
			$mysqli->query("update fs_tasks set `enabled` = 0 where id = $id limit 1");
			$redis->del("task_$id");
			$td = $redis->get("task_disabled");
			if ($td){
				$s = explode(",", $td);
				$s1 = [];
				function w($var){global $id,$s1;if ($var && !isset($s1[$var]) && $var !=$id){ $s1[$var]=1;return $var;}}
				$s = array_filter($s,"w");
				$s = implode(",", $s);
				$redis->set("task_disabled",$s);
			}
			die("取消任务 id $id 完毕");
		}
	}else die("任务有误！");
}
//----------------------显示----------域数据库 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/main.css\"/><script src=\"css/jquery.js\"></script><script>
function del(sid){var a = confirm(\"删除操作不可撤销，你确认提交？\");if (a) { \$.post( \"DM_tasks_cp.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en4(sid){var a = confirm(\"暂停任务将停止当前任务的执行，你确认提交？\");if (a) { \$.post( \"DM_tasks_cp.php\", { sid: sid, en4: \"1\" })
  .done(function( data ) { alert( \"暂停操作 \" + data);window.location.reload();});}}
function en5(sid){var a = confirm(\"恢复任务将恢复当前任务的执行，你确认提交？\");if (a) { \$.post( \"DM_tasks_cp.php\", { sid: sid, en5: \"1\" })
  .done(function( data ) { alert( \"暂停操作 \" + data);window.location.reload();});}}
function en0(sid){var a = confirm(\"取消任务将停止并删除未执行的队列数据，不可撤销！你确认提交？\");if (a) { \$('#c'+sid).val('需要一定时间，请等待反馈，不能关闭页面....');\$(':button').prop('disabled', true);\$.post( \"DM_tasks_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"取消操作 \" + data);window.location.reload();});}}
function en1(sid){var a = confirm(\"启用任务将导入上传数据以便执行任务，你确认提交？\");if (a) { \$('#b'+sid).val('需要一定时间，请等待反馈，不能关闭页面....');\$(':button').prop('disabled', true);\$.post( \"DM_tasks_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}}
</script></head><body><div id=\"win\" style=\"display:none;\"><h2 id=\"tt\"><span id=\"close\" onclick=\"$('#win').css('display','none')\" style=\"pading:5px;\"> × </span></h2>
    <p id=\"title\" class=\"act2\"></p><audio id=\"player\" controls=\"controls\" src=\"\">你的浏览器不支持audio标签。</audio></div>";
$where = " where `domain_id` = '$domain_id' ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['gid'])){
	$temp = $mysqli->real_escape_string($_GET['gid']);
	$where .= " and `group_id` like '%,$temp,%' ";
	$showget .=" 组标识含 '$temp' ";
}
$count = 20;
$getstr = "";
$totle = $mysqli->query("select count(*) from fs_tasks $where");
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
	echo '<p class="pcenter" style="font-size:18pt;">任务管理 '.$showget.'  <a style="font-size:12pt;" href="?edittask=0">【新建任务】</a> </p><table class="tablegreen" width="90%" align="center"><th colspan=4><form method="get"> 组标识：<input id="gid" name="gid" value="" size=10> <input type="submit" value="确认"> <a href="?">【看全部】</a> </form></th>';
	$result = $mysqli->query("select * from fs_tasks $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=4 align=center><span class="smallred smallsize-font"> *任务新建后默认被禁用，必须启用才会开始运行！运行中可暂停可取消<br/>*以上任务以域为单位批量顺序运行，运行域任务请到 域管理 </span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			if ($row['sound'])
				$showsound="<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\" $row[name] 任务语音 \");$(\"#player\").attr(\"src\",\"$row[domain_id]/$row[sound]\");'> 【任务语音】 </button>";
			else 
				$showsound=" 【无语音】 ";
			$status = "";
			if ($row['enabled']=='1'){
				$showalert= ' <span class="bggreen">使用中 </span> &nbsp; <em class=\'red\'>'.$row['name'].'</em>';
				$showtools=" <input type='button' onclick=\"en4($row[id])\" value='暂停'/> &nbsp; <input  id='c$row[id]' type='button' onclick=\"en0($row[id])\" value='取消'/>";
				$status = $redis->hMget("task_$row[id]",['datetime',"answer","complete"]);
				if ($status['datetime'])
					$status = "<tr style='background:#E5E5E5;'><td>» » 提交队列时间：$status[datetime]</td><td colspan=4>» » 应答数：$status[answer] &nbsp; 完成数：$status[complete]</td></tr>";
				else 
					$status = "<tr style='background:#E5E5E5;'><td>» » <span class=smallgray>尚未提交队列</span></td><td colspan=4>» » <span class=smallgray>任务没有被执行</span></td></tr>";
			}elseif ($row['enabled']=='4'){
				$showalert= ' <span class="bgred">暂停中 </span> &nbsp; <em class=\'orange\'>'.$row['name'].'</em>';
				$showtools=" <input type='button' onclick=\"en5($row[id])\" value='恢复'/> &nbsp; <input id='c$row[id]' type='button' onclick=\"en0($row[id])\" value='取消'/>";
			}else{
				$showalert= ' <span class="bgred">未启用 </span> &nbsp; <em>'.$row['name'].'</em>';
				$showtools=" <input id='b$row[id]' type='button' onclick=\"en1($row[id])\" value='启用'> &nbsp; <input type='button' onclick=\"del($row[id])\" value='删除'>";
			}
			if ($row["tocc"])
				$options = "<strong>转接坐席</strong> &nbsp; ";
			else 
				$options = "<strong style='color:red;'>不接坐席</strong> &nbsp; ";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			
			echo "<tr $bgcolor><td>$showalert &nbsp; ($row[id])</td><td>业务类型：<strong>$row[type]</strong></td><td>$options $showsound</td><td><a href='?edittask=$row[id]'>详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>";
			echo $showtools;
			echo "</span></td></tr>$status";
		}
	}
$mysqli->close();
