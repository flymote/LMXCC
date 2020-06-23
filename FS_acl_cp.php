<?php
set_time_limit(600);
session_start();
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
date_default_timezone_set('Asia/Shanghai');
if(!empty($_SESSION['POST_submit_once']) && !empty($_POST)){
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
$conf = $_SESSION['conf_dir'].'/autoload_configs/acl.conf.xml';
$xml_string = file_get_contents($conf);
$xml = simplexml_load_string($xml_string);
if (!$xml)
	die("无法读取系统配置文件！");
$settings = array();
$name = "";
foreach ($xml as $k=>$a){
	foreach ($a->list as $one){
		foreach ($one->attributes() as $key =>$two){
			if ($key=='name'){
				$name = "$two";
			}else
				$name .=":$two";
		}
		$settings[$name]=array();
		foreach($one->node as $one ){
			foreach ($one->attributes() as $key =>$two){
				if ($key=='type'){
					$temp = "$two#";
				}else{
					$temp .= "$key:$two";
				}
			}
			$settings[$name][] = $temp;
		}
	};
}
$showinfo="";
if (!empty($_POST)){
	$post_botton_enabled = "disabled=\"disabled\"";
	$showinfo .= "<span class='bgblue'>已经提交数据，当前显示提交的数据，不可连续提交！</span>";
}else
	$post_botton_enabled = "";
$content = '<configuration name="acl.conf" description="Network Lists"><network-lists>';
$html ="";
foreach ($settings as $key=>$data){
	$lists = explode(":", $key);
	$posted = (@$_POST['lists']==$lists[0] ); //是否提交修改
	if ($posted && !empty($_POST['change_default']))
		$lists[1] = $_POST['change_default'];
	$html .= "<form method=\"post\">";
	if ($lists[1]=='allow')
		$show_default_acl = "<span class='bggreen'>默认 允许</span> <label><input type='checkbox' name='change_default' value='deny'/>改为 拒绝</label>";
	else 
		$show_default_acl = "<span class='bgred'>默认 拒绝</span> <label><input type='checkbox' name='change_default'  value='allow'/>改为 允许</label>";
	$content .= "<list name=\"$lists[0]\" default=\"$lists[1]\">";
	$show_info = "";
	$html .="<tr><td style='background:#decedd;'><em class='bold14'> $lists[0] </em>$show_default_acl</td></tr>";
	foreach ($data as $one){
		$list = explode("#", $one);
		if ($posted && !empty($_POST['del']) && in_array($list[1], $_POST['del'])) {//post 删除的 跳过
			$show_info .= "$list[1] 被删除！<br/>";
			continue;
		}
		if ($list[0]=='allow')
			$show_acl = "<span class='bggreen'>允许</span>";
		else
			$show_acl = "<span class='bgred'>拒绝</span>";
		$html .="<tr><td align='center' class='blod14'>$show_acl $list[1] <label><input type='checkbox' name='del[]' value='$list[1]'/>标记删除</label></td></tr>";
		$v = explode(":", $list[1]);
		$content .= "<node type=\"$list[0]\" $v[0]=\"$v[1]\"/>";
	}
	if ($posted){ //post 提交新ip
		if(!empty($_POST['add_ip'])){
			$ip = trim($_POST['add_ip']);
			if (!ip2long ($ip)){
				$show_info .= "$ip 不是合法的IP地址！<br/>";
				$ip = false;
			}else{
				$laststr = substr(strrchr($ip,'.'),1);
				if (empty($laststr))
					$ip = $ip."/24";
				else 
					$ip = $ip."/32";
				$show_info .= "添加IP：$ip ！刷新页面后可见";
			}
		}else 
			$ip = false;
		if ($ip && $_POST['cidr']=='cidr')
			$content .= "<node type=\"$_POST[list_act]\" cidr=\"$ip\"/>";
		elseif (!empty($_POST['domainame'])){
			$listname = xmlentities ($_POST['domainame']);
			$show_info .= "添加域：$listname ！";
			$content .= "<node type=\"$_POST[list_act]\" domain=\"$listname\"/>";
		}elseif ($_POST['cidr']=='domain'){
			$show_info .= "添加默认域 ！";
			$content .= "<node type=\"$_POST[list_act]\" domain=\"\$\${domain}\"/>";
		}
	}
	$html .="<tr><td  style='background:#decedd;' align='center' class='blod14'><label><input type='radio' name='cidr' value='cidr'  checked='checked'/>cidr</label> <input type='hidden' value='$lists[0]' name='lists' >$lists[0] 输入IP：<input type='text' value='' name='add_ip' class='inputline'>  &nbsp;  <label><input type='radio' name='cidr'  value='domain'/>域：<input type='text' name='domainame'  value='' size=8 class='inputline'/></label> <label><input type='radio' name='list_act' value='allow' checked='checked'/>允许</label> <label><input type='radio' name='list_act'  value='deny'/>拒绝</label> &nbsp; <input type='submit'  value='提交操作' $post_botton_enabled onclick=\"return confirm('是否确认提交？');\"></td></tr></form>";
	$content .= "</list>";
}
if (!empty($_POST['list_add'])){
	$listname = xmlentities ($_POST['list_add']);
	$show_info .= "添加新分组 $listname ！刷新页面后可见";
	$content .= "<list name=\"$listname\" default=\"$_POST[list_add_default]\"></list>";
}
$content .= "</network-lists></configuration>";

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>访问控制列表控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><th>* 下面几个预定义项是默认存在的：<br/>rfc1918.auto(RFC1918标准局域网)  nat.auto(RFC1918不含本地网)<br/>
localnet.auto(针对本地网)    loopback.auto(对本地回路(127.x.x.x))<br/>设置默认用cidr标识节点；如域用户控制，且域用户已有cidr属性，只需导入域名！</span><br/><span class=red>$show_info $showinfo</span></th>$html
<form method="post"><tr><td  style='background:#decedd;'><em>新建分组</em> 分组名称：<input id="list_add" name="list_add" value="" size=20 class="inputline"/> <span class="smallred  smallsize-font">* 不允许&、引号、尖括号</span> &nbsp; 默认：<label><input type="radio" name="list_add_default" value="allow" />允许</label> <label><input type="radio" name="list_add_default"  value="deny" checked="checked"/>拒绝</label> &nbsp;  <input type="submit" value="添加" $post_botton_enabled onclick="return confirm('是否确认提交？');"/></td></tr>
<th>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> run('reloadacl');
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
//  echo "<textarea>$content</textarea>";
echo "</th></form></table></body></html>";