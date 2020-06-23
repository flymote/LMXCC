<?php
set_time_limit(600);
session_start();
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
$showinfo ="";
if (!empty($_POST)){
	$post_botton_enabled = "disabled=\"disabled\"";
	$showinfo .= "<span class='bgblue'>已经提交数据，当前显示提交的数据，不可连续提交！</span>";
}else
	$post_botton_enabled = "";
	
function xmlentities($string){
	$value = str_replace(array("&","<",">",'"',"'"),'_', $string);
	return $value;
}
$conf = $_SESSION['conf_dir'].'/freeswitch.xml';
// $conf = 'freeswitch/freeswitch.test';
$xml_string = file_get_contents($conf);
$xml = simplexml_load_string($xml_string);
if (!$xml)
	die("无法读取系统配置文件！");
$settings = array();
$name = "";
foreach ($xml->{'X-PRE-PROCESS'} as $one){
	foreach ($one->attributes() as $key =>$two){
		if ($key=='cmd'){
			$name = "$two";
		}else
			$name .="|$two";
	}
	$settings[$name]='';
};
foreach($xml->section as $one ){
	foreach ($one->attributes() as $key =>$two){
		if ($key=='name'){
			$temp = "$two#";
		}elseif ($key=='description'){
			$temp .= "$two";
		}
	}
	$settings[$temp] = array();
	foreach ($one->{'X-PRE-PROCESS'} as $three){
		foreach ($three->attributes() as $key =>$two){
			if ($key=='cmd'){
				$name = "$two";
			}else
				$name .="|$two";
		}
		$settings[$temp][]=$name;
	}
};
$show_info = $html = '';
$content = "<?xml version=\"1.0\"?>\n<document type=\"freeswitch/xml\">\n";
$postedM = (@$_POST['lists']=='LMXmain' ); //是否提交全局数据
if ($postedM && !empty($_POST['addname'])){
	$addname= xmlentities (trim($_POST['addname']));
	$addvalue= xmlentities (trim($_POST['addvalue']));
	$content .=  "<X-PRE-PROCESS cmd=\"$addname\" data=\"$addvalue\"/>\n";
	$show_info .= "添加全局参数：$addname $addvalue ！<br/>";
}
$i = 0;
foreach ($settings as $key=>$data){
	if(!is_array($data)){ //全局 X-PRE-PROCESS
		if (!$i)
			$html .=  "<form method=\"post\">";
		if ($postedM && !empty($_POST['del']) && in_array($key, $_POST['del'])) {//post 删除的 跳过
			$show_info .= "$key 被删除！<br/>";
			continue;
		}
		$lists = explode("|", $key);
		$content .= "<X-PRE-PROCESS cmd=\"$lists[0]\" data=\"$lists[1]\"/>\n";
		$i++;
		$html .="<tr><td align='center' class='blod14'><em class='bgblue'> $lists[0] </em>&nbsp; $lists[1]<label><input type='checkbox' name='del[]' value='$key'/>标记删除</label></td></tr>";
	}else{
		if ($i){
			$html .="<tr><td  style='background:#decfff;' align='center' class='blod14'><input type='hidden' value='LMXmain' name='lists' >新加全局参数：<input type='text' value='include' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/> &nbsp; <input type='submit'  value='提交操作' $post_botton_enabled onclick=\"return confirm('是否确认提交？');\"></td></tr></form>";
			$i = 0;
		}
		$html .=  "<form method=\"post\">";
		$list = explode("#", $key);
		$html .="<tr><td style='background:#decedd;' class='blod14'><em>$list[0] ---- ---- ---- ----</em> </td></tr>";
		$content .= "<section name=\"$list[0]\" description=\"$list[1]\">\n";
		$posted = (@$_POST['lists']==$list[0]); //是否提交数据
		if ($posted && !empty($_POST['addname'])){
			$addname= xmlentities (trim($_POST['addname']));
			$addvalue= xmlentities (trim($_POST['addvalue']));
			$content .=  "<X-PRE-PROCESS cmd=\"$addname\" data=\"$addvalue\"/>\n";
			$show_info .= "添加参数：$addname $addvalue ！<br/>";
		}
		foreach ($data as $one){
			if ($posted && !empty($_POST['del']) && in_array($one, $_POST['del'])) {//post 删除的 跳过
					$show_info .= "$one 被删除！<br/>";
					continue;
			}
			$v = explode("|", $one);
			$html .="<tr><td align='center' class='blod14'><em class='bgblue'> $v[0] </em>&nbsp; $v[1]<label><input type='checkbox' name='del[]' value='$one'/>标记删除</label></td></tr>";
			$content .= "<X-PRE-PROCESS cmd=\"$v[0]\" data=\"$v[1]\"/>\n";
		}
		$html .="<tr><td  style='background:#decedd;' align='center' class='blod14'><input type='hidden' value='$list[0]' name='lists' >$list[0] 新加参数：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/> &nbsp; <input type='submit'  value='提交操作' $post_botton_enabled onclick=\"return confirm('是否确认提交，提交后会重启服务器！请务必谨慎');\"></td></tr></form>";
		$content .= "</section>\n";
	}
}
if (!empty($_POST['list_add'])){
	$listname = xmlentities ($_POST['list_add']);
	$listd = xmlentities ($_POST['list_add_d']);
	$show_info .= "添加新分组 $listname ！刷新页面后可见";
	$content .= "<section name=\"$listname\" description=\"$listd\">\n</section>\n";
}
$content .= "</document>";

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>freeSWITCH控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><th>* 本配置文件是主干配置，至关重要，修改提交即会重启服务器<br/>修改信息前务必谨慎！！！不允许输入 &、引号、尖括号</span><br/><span class=red>$show_info $showinfo</span></th>$html
<form method="post"><tr><td  style='background:#decedd;'><em>新建分组</em> 分组名称：<input id="list_add" name="list_add" value="" size=20 class="inputline"/> <span class="smallred  smallsize-font">* 不允许&、引号、尖括号</span> &nbsp; 说明：<input type="text" name="list_add_d" value="" /> &nbsp;  <input type="submit" value="添加" $post_botton_enabled onclick="return confirm('是否是否确认提交，提交后会重启服务器！请务必谨慎');"/></td></tr>
<th>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> restart_switch();
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
echo "</th></form></table></body></html>";