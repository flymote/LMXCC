<?php
set_time_limit(600);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");
include_once 'DM_db.php';

if (!empty($_POST['ph'])){
	$id = intval($_POST['id']);
	if (!$id){ //如果是新插入数据，需要多验证一下是否这个数据存在！因为本页是ajax提交post的，新数据被提交后页面不刷新再次提交就会重复提交！
		$result = $mysqli->prepare("select `id` from fs_crm where `phone`=? and `domainid`=?");
		$result->bind_param('ss', $_POST['ph'],$_SESSION['domainid']);
		$result->execute();
		$result->bind_result($id);
		$result->fetch();
	}
	if ($id){
		$sql = "update fs_crm set `phone`=?,`name`=?,`sex`=?,`age`=?,`place`=?,`job`=?,`memo`=?,`edu`=?,`last_modi`=? where id = $id";
	}else{
		$sql = "insert into fs_crm (`phone`,`name`,`sex`,`age`,`place`,`job`,`memo`,`edu`,`last_modi`,`domainid`) values (?,?,?,?,?,?,?,?,?,'$_SESSION[domainid]')";
	}
	$result = $mysqli->prepare($sql);
	$result->bind_param('ssissssss', $_POST['ph'],$_POST['name'],$_POST['sex'],$_POST['age'],$_POST['place'],$_POST['job'],$_POST['memo'],$_POST['edu'],$_SESSION['lmxccusers']);
	$result->execute();
	if (!$id)
		if ($result->insert_id){
			die("$result->insert_id");
		}else 
			die("操作没有成功！请重试");
	else die("$id");
}elseif ($_POST){
	die("没有提交电话号码！");
}
$autolabel = "";
$ph = !empty($_GET['callto'])?$_GET['callto']:"";
if (!empty($_GET['account']))
	$account = intval($_GET['account']);
else
	$account = 0;
if (!$account){
	$account = "信息联动...";
	$url = "DM_call_list.php?account=0";
}else{
	if (!empty($_GET['auto'])){
		$url = "DM_call_list.php?auto=1&account=$account";
		$autolabel = " checked=\"checked\" ";
	}else
		$url = "DM_call_list.php?auto=0&account=$account";
}
$msg = $name =  $age = $place = $job = $memo = $edu = $last_mod ="";
$last_time = $sex = $id = 0;
if ($ph){
	$result = $mysqli->prepare("select `phone`,`name`,`sex`,`age`,`place`,`job`,`memo`,`edu`,`last_time`,`last_modi`,`id` from fs_crm where `phone` = ? and `domainid`='$_SESSION[domainid]'");
	$result->bind_param('s', $ph);
	$return = $result->execute();
	if ($return){
		$result->bind_result($ph,$name,$sex,$age,$place,$job,$memo,$edu,$last_time,$last_mod,$id);
		$result->fetch();
	}
	if ($id)
	$msg .=" 已发现记录，可修改更新： ";
	else
	$msg .=" 未发现记录，将添加信息： ";
}
$time = time();
$date_now = date("Y-m-d%20H:i:s",$time);
$date_end = date("Y-m-d%20H:i:s",$time-259200);
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script><script type="text/javascript" src="css/jquery.form.js"></script>
<script> function ajaxSubmitForm() {
   var option = {
   url : 'DM_crm.php?callto=$ph', type : 'POST',//dataType : 'json',
   headers : {"ClientCallMode" : "ajax"}, //添加请求头部
   success : function(data) {if (isNaN(data)) { alert("【错误】"+data); $("#labelcid").text(data);} else{ alert("【成功】保存信息成功！"); $("#cid").val(data);}},
   error: function(data) { alert("【失败】"+data);  }
    }; 
	if (a) $("#client_add").ajaxSubmit(option);
   return false;
  }
var a=0;
function gopage(){ a = parseInt($('#account').val()); if (a) window.location="?account="+a;};
function gophone(){ a = parseInt($('#phone').val()); if (a) window.location="?account=$account&callto="+a;};
function changed() { a = 1; };
function getauto() { a = $('#autoget').prop('checked'); if (a) $('#inframe').attr("src","$url&auto=1"); else $('#inframe').attr("src","$url&auto=0");  };
</script>
</head><body onunload="ajaxSubmitForm()">
HTML;
$submit = "<input class=\"btn-green\" type=\"button\" value=\"提交保存\" onclick=\"ajaxSubmitForm();\"/>";
$showinfo = "<form method='post' id=\"client_add\" onsubmit=\"return false;\"><div id=\"labelcid\" style=\"text-align:center;margin-top:10px;font-size:12pt;color:red;\">".($last_time?"<span class='smallsize-font'>&nbsp;【$last_mod 于 $last_time 提交】&nbsp; &nbsp; </span>":"")."&nbsp; &nbsp; <span style='color:gray;'>修改信息后请保存！» </span>$submit</div><input id=\"cid\" name=\"id\" type=\"hidden\" value=\"$id\"/>";
$showinfo .= <<<HTML
<div style="margin-top:10px;font-size:12pt;text-align:center;">电话号码：<input id='ph' name='ph' value='$ph' class='input-blue' size=9><span style="font-size:9pt;"> 话单：<a href="DM_cdr_list.php?startdate=$date_now&enddate=$date_end&phone0=$ph" target="_blank">主叫</a>&nbsp;<a href="DM_cdr_list.php?startdate=$date_now&enddate=$date_end&phone=$ph" target="_blank">被叫</a></span>&nbsp; &nbsp; 称呼：<input id='name' name='name' value='$name' class='input-blue'  size=7 onchange='changed();'> &nbsp;&nbsp; 性别：<label><input name="sex" id="sex0" type="radio" value="0" onchange='changed();'> 未知</label>   <label><input name="sex"  id="sex1"  type="radio" value="1"  onchange='changed();'> 男</label>   <label><input name="sex"  id="sex2"  type="radio" value="2"  onchange='changed();'> 女</label></div>
<div style="margin-top:10px;font-size:12pt;text-align:center;">年龄情况：<input id="age" name="age" class="input-blue" value="$age" size="90"  onchange='changed();'/></div>
<div style="margin-top:10px;font-size:12pt;text-align:center;">地区住所：<input id="place" name="place" class="input-blue" value="$place" size="90"  onchange='changed();'/></div>
<div style="margin-top:10px;font-size:12pt;text-align:center;">工作职业：<input id="job" name="job" class="input-blue" value="$job" size="90" onchange='changed();'/></div>
<div style="margin-top:10px;font-size:12pt;text-align:center;">素质教育：<input id="edu" name="edu" class="input-blue" value="$edu" size="90"  onchange='changed();'/></div>
<div style="margin-top:10px;font-size:12pt;text-align:center;"><textarea id='memo' name='memo' class='input-blue' style='width:600px;height:100px;' placeholder="* 本处可记录对方的相关意向、是否有反馈等信息 *"  onchange='changed();'>$memo</textarea></div>
</form>
<script>$('#sex$sex').prop("checked",true);</script>
HTML;
echo '<p class=\'pcenter\' style=\'font-size:18pt;\'>顾客信息 &nbsp; &nbsp; <span style=\'font-size:12pt;\'>坐席ID：<input id="account" name="account" value="'.$account.'" class="inputline1" size="6" onclick="select();"> <button onclick="gopage();"> &gt;开始 </button><label><input id="autoget" name="autoget" type="checkbox" value="1" '.$autolabel.' onclick="getauto();">自动</label></span> &nbsp; &nbsp; <span style=\'font-size:12pt;\'>查号码：<input id="phone" name="phone" value="'.$ph.'" class="inputline1" size="8" onclick="select();"> <button onclick="gophone();"> &gt;查看 </button></span>  <a href="?account='.$account.'" style="font-size:9pt;">【刷新页面】</a></p><table class="tablegreen" width="1260" align="center"><tr><th><div id="message">'.$msg.'</div></th></tr><tr><td>'.$showinfo.'</td></tr><tr><td><iframe src="'.$url.'" width="1260" height="650" frameborder="0" id="inframe"/></td></tr></table></body></html>';
