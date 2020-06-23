<?php
set_time_limit(600);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['FSlmxusers'])){
	header("Location:main.php");
	die("login first!!");
}

define("ESL_HOST", @$_SESSION['ESL_HOST']);
define("ESL_PORT", @$_SESSION['ESL_PORT']);
define("ESL_PASSWORD",@$_SESSION['ESL_PASSWORD']);
if (isset($_SESSION['conf_dir']))
	$conf_dir = $_SESSION['conf_dir'];
else 
	die("请正常登录使用！不允许直接进行操作");
require_once "detect_switch.php";

$files = array("dialplan_public"=>"$conf_dir/dialplan/public.xml","dialplan_default"=>"$conf_dir/dialplan/default.xml","sip_external"=>"$conf_dir/sip_profiles/external.xml","sip_internal"=>"$conf_dir/sip_profiles/internal.xml","autoload_sofia"=>"$conf_dir/autoload_configs/sofia.conf.xml","autoload_db"=>"$conf_dir/autoload_configs/db.conf.xml","autoload_switch"=>"$conf_dir/autoload_configs/switch.conf.xml","autoload_modules"=>"$conf_dir/autoload_configs/modules.conf.xml","autoload_acl"=>"$conf_dir/autoload_configs/acl.conf.xml","autoload_ES"=>"$conf_dir/autoload_configs/event_socket.conf.xml","autoload_ivr"=>"$conf_dir/autoload_configs/ivr.conf.xml","autoload_callcenter"=>"$conf_dir/autoload_configs/callcenter.conf.xml","autoload_xmlcdr"=>"$conf_dir/autoload_configs/xml_cdr.conf.xml","vars"=>"$conf_dir/vars.xml");
$ids = array_keys($files);
$writefile = 0;
$goback = false;
$showinfo ="";
if (isset($_GET['fileid']) && isset($_GET['goback'])){
	if (@$_GET['t'] == @$_SESSION['POST_CHECK_once']){
		$_SESSION['POST_CHECK_once'] = "";
		$_POST['fileid'] = $_GET['fileid'];
		$goback =$_GET['goback'];			
	}
}
if (isset($_GET['domain'])){
	$domain = $_GET['domain'];
	$files = array("dialplan"=>"$conf_dir/dialplan/$domain.xml","directory"=>"$conf_dir/directory/$domain.xml");
	$ids = array("dialplan","directory");
}
if (in_array(@$_POST['fileid'],$ids)){
	$conf_file = $files[$_POST['fileid']];
	if ($goback==='0'){
		$goback = copy($conf_file."bak0",$conf_file);
		if ($goback)
			$showinfo .= "<span class='smallblue smallsize-font'> 已恢复初始保存 </span>";
	}elseif ($goback==='1'){
		$goback = copy($conf_file."bak1",$conf_file);
		if ($goback)
			$showinfo .= "<span class='smallblue smallsize-font'> 已恢复前次文件 </span>";
	}
	$showinfo .= "<span class='smallred smallsize-font'>$conf_dir $_POST[fileid] </span>";
	if (isset($_POST['file_content'])){
		$xml_string = trim($_POST['file_content']);
		$writefile = 1;
		$showinfo .= "<span class='smallblue smallsize-font'> 文件提交保存！ </span>";
	}else
		$xml_string = @file_get_contents($conf_file);
	if (!$xml_string)
		die("无法读取系统配置文件！");
	$time = time();
	$_SESSION['POST_CHECK_once'] = $time;
	$html = "<tr class=bg2><td colspan=2><textarea style='width:98%;height:780px;padding:5px;margin:5px;' name='file_content' id='content'>$xml_string</textarea></td></tr>";
	$html .='<tr class=bg1><th><span class="smallred smallsize-font">*提交后即刻重启服务器，请谨慎操作</span><input type="hidden" name="fileid" value="'.$_POST['fileid'].'"/></th><th align=center><a style=\'font-size:10pt;\' href=\'?goback=1&fileid='.$_POST['fileid'].'&t='.$time.'\' onclick="return confirm(\'本操作将删除当前文件并恢复前一次保存的文件，当前文件不可恢复，请确认！\')"> &raquo;&nbsp;恢复前一次保存</a> <a style=\'font-size:10pt;\' href=\'?goback=0&fileid='.$_POST['fileid'].'&t='.$time.'\' onclick="return confirm(\'本操作将删除当前文件并恢复最初保存的文件，当前文件不可恢复，请确认！\')"> &raquo;&nbsp;恢复初始保存</a> &nbsp;  &nbsp; <input type="submit" value="确认提交保存上面的内容" onclick="return confirm(\'这里的文件是全局设置，务必谨慎！提交后将重启服务器！是否确认提交？\');"/>';
}else{
	$files_result = "<option value=''>请选择..</option>";
	foreach ($files as $k=>$v)
		$files_result .= "<option value='$k'>$k</option>";
	$files_result = "<select name='fileid' id='fileid' class='inputline1'>$files_result</select>";
	$showinfo = "<span class='smallgray smallsize-font'> 未选择文件 </span>";
	$html = "<tr class=bg2><td colspan=2 align=center>请选择需编辑的文件：$files_result</td></tr>";
	$html .='<tr class=bg1><th  colspan=2 align=center><input type="submit" value="确定" />';
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>系统文件编辑工具 $showinfo <a style='font-size:10pt;' href='main.php'> &raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="1200" align="center"><form method="post"><tr><th colspan=2>* 本工具直接对相关文件进行编辑保存，对内容不进行验证，编辑务必谨慎</span><br/>
</th></tr>$html
HTML;
if ($writefile){
	if (!file_exists($conf_file.".bak0"))
		copy($conf_file,$conf_file."bak0");
	copy($conf_file,$conf_file."bak1");
	$fout = file_put_contents($conf_file,$xml_string,LOCK_EX);
	if ($fout){
		$info = new detect_switch();
		$info-> run('reloadxml');
	}
}
echo "</th></tr></form></table></body></html>";