<?php
set_time_limit(600);
session_start();
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
date_default_timezone_set('Asia/Shanghai');
if(!empty($_SESSION['POST_submit_once']) && isset($_POST)){
	$_SESSION['POST_submit_once'] = 0;
	unset($_POST);
}
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['ESL_HOST']) || empty($_SESSION['conf_dir']))
	die("请正常登录使用！不允许直接进行操作");

define("ESL_HOST", $_SESSION['ESL_HOST']);
define("ESL_PORT", $_SESSION['ESL_PORT']);
define("ESL_PASSWORD",$_SESSION['ESL_PASSWORD']);
require_once "detect_switch.php";

function xmlentities($string){
	$value = str_replace(array("&","<",">",'"',"'"),'_', $string);
	return $value;
}

$conf_file = $_SESSION['conf_dir'].'/autoload_configs/modules.conf.xml';
// $conf_file = 'freeswitch/autoload_configs/modules.conf.test';

$xml_string = file($conf_file);
$settings = $show_settings = array();
if (!$xml_string)
	die("无法读取系统配置文件！");

$file =__DIR__.'/.Config';
if (is_file($file))
	$conf = @unserialize(file_get_contents($file));
else
	$conf = false;
if (isset($conf['modules_add']))
	$in_conf = explode("|", $conf['modules_add']);
else 
	$in_conf = array();

foreach($xml_string as $one){
	$one = trim($one);
	if (strpos($one, "<load ")!==false){
		if (strpos($one,"<!--")!==false){
			$key = 0; //被注释了
			$one = trim(str_replace("<!--",'', $one));
		}else
			$key = 1; //可用
		$module = preg_replace("/<load.*module=\"(.*)\".*/", "$1", $one);
		$use = "use_$module";
		if ($key)
			if (isset($_POST['posted_modules']) && empty($_POST[$use])) { //若文件配置中模块是可用的，但post中没有提交其可用的信息，认为设置其禁用
				$$use = '';
				$key = 0;
			}else $$use = 'checked="checked" ';
		elseif (in_array($module, $in_conf))
			$$use = 'checked="checked" ';
		elseif (isset($_POST['posted_modules']) && isset($_POST[$use]) && $_POST[$use]) {
			$$use = 'checked="checked" ';
			$key = 1;
		}else
			$$use = '';
		$settings[$module] = $key ;
	}
}

$content = "<configuration name=\"modules.conf\" description=\"Modules\">\n<modules>\n";
$html = "";

$css = "inputline";
foreach ($settings as $key=>$value){
	$temp = "use_$key";
	if ($value){
		$$temp = 'checked="checked" ';
		$show = '<span class="bggreen"> 当前已启用</span>';
	}else 
		$show = '<span class="smallgray smallsize-font"> 未启用</span>';
	if ($$temp || $value)
		$content .= "<load module=\"$key\"/>\n";
	else
		$content .= "<!-- <load module=\"$key\"/> -->\n";
	$html .= "<tr><td align='center'><em>$key</em> $show <input id=\"$temp\" name=\"$temp\" value=\"1\" type=\"checkbox\" {$$temp}></td></tr>";
}

$html .= "<tr><td style='background:#decedd;' align='center'><em>添加设置</em> 模块名称：<input id=\"add_key\" name=\"add_key\" value=\"\" size=15 class=\"inputline\" /> </td></tr>";

if(!empty($_POST['add_key'])){
	$key = xmlentities($_POST['add_key']);
	$content .= "<load module=\"$key\"/>\n";
}
$content .= "</modules>\n</configuration>\n";
$showinfo ="";
if (!empty($_POST)){
	$post_botton_enabled = "disabled=\"disabled\"";
	$showinfo .= "<span class='bgblue'>已经提交数据，当前显示提交的数据，不可连续提交！</span>";
}else
	$post_botton_enabled = "";
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>系统功能模块控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><form method="post"><th>* 本配置文件每个参数按行读取 注意：功能模块需编译部署后方可配置使用</span><br/>
* 勾选表示已经启用；不允许&、引号、尖括号；提交数据后会重启服务器，请务必谨慎<br/>$showinfo
</th>$html
<th><span class="smallred smallsize-font">*提交后即刻重启服务器，请谨慎操作</span><input type="hidden" value="1" name="posted_modules" id="posted_modules"> <input type="submit" value="确认提交" $post_botton_enabled onclick="return confirm('这里的参数是全局设置，务必谨慎！提交后将重启服务器！是否确认提交？');"/>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf_file,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> restart_switch();
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
echo "</th></form></table></body></html>";