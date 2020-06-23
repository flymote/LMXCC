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
$showinfo ="";
if (!empty($_POST)){
	$post_botton_enabled = "disabled=\"disabled\"";
	$showinfo .= "<span class='bgblue'>已经提交数据，不可连续提交！</span>";
}else
	$post_botton_enabled = "";
	
function xmlentities($string){
	$value = str_replace(array("&","<",">",'"',"'"),'_', $string);
	return $value;
}
$conf = $_SESSION['conf_dir'].'/sip_profiles/external.xml';
// $conf = 'freeswitch/sip_profiles/external.test';
$xml_string = file_get_contents($conf);
$xml = simplexml_load_string($xml_string);
if (!$xml)
	die("无法读取系统配置文件！");
$settings = array();
$name = "";
foreach($xml as $key=>$one ){
	$settings[$key] = array();
	foreach ($one as $k1=>$v1){ //遍历参数
		$tmp = array();
		foreach ($v1->attributes() as $k2 =>$v2){
			$tmp["$k2"]="$v2";
		}
		$settings[$key][$k1][] = $tmp;
	}
};
$show_info = $html = '';
$content = "<profile name=\"external\">\n";
$i=0;
foreach ($settings as $key=>$data){
	$html .=  "<form method=\"post\">";
	$html .="<tr><td colspan=2 style='background:#decedd;' class='blod14'><em>$key ---- ---- ---- ----</em> </td></tr>";
	$content .= "<$key>\n";
	$posted = (@$_POST['lists']==$key); //是否提交数据，按分组提交
		if ($posted && !empty($_POST['addname'])){
			$addname= xmlentities (trim($_POST['addname']));
			if (!empty($_POST['addkey']))
				$addkey= xmlentities (trim($_POST['addkey']));
			else $addkey = "";
			if (!empty($_POST['addvalue']))
				$addvalue= xmlentities (trim($_POST['addvalue']));
			else $addvalue ="";
			if (!empty($_POST['addmore']))
				$addmore= xmlentities (trim($_POST['addmore']));
			else $addmore="";
			switch ($addkey){
				case "X-PRE-PROCESS":
					$content .=  "<$addkey cmd=\"$addname\" data=\"$addvalue\"/>\n";
					$show_info .= "添加 $addkey 控制命令：$addname $addvalue ！<br/>";
					break;
				case "alias":
					$content .=  "<$addkey name=\"$addname\"/>\n";
					$show_info .= "添加 $addkey 别名：$addname  ！ <br/>";
					break;
				case "domain":
					if ($addvalue <>'true') $addvalue = 'false';
					if ($addmore <>'true') $addmore = 'false';
					$content .=  "<$addkey name=\"$addname\" alias=\"$addvalue\" parse=\"$addmore\"/>\n";
					$show_info .= "添加 $addkey 域：$addname alas: $addvalue parse: $addmore !<br/>";
					break;
				case "param":
					$content .=  "<$addkey name=\"$addname\" value=\"$addvalue\"/>\n";
					$show_info .= "添加 $addkey 设置：$addname $addvalue ！ <br/>";
					break;
				default:
					$content .=  "<$addkey name=\"$addname\" value=\"$addvalue\"/>\n";
					$show_info .= "添加 $addkey name: $addname value: $addvalue ！ <br/>";
			}
		}
	
		foreach ($data as $k1=>$items){//分组下的一个元素组下（每个分组可以多个元素组）,如 param元素
			foreach ($items as $getone){ //一个参数，其中的参数项目也是数组
				$modi = 0;
				if(isset($getone['name']))
					$one = $getone['name'];
				elseif(isset($getone['cmd']))
					$one = $getone['cmd'];
				else $one =$getone[0];
				if ($posted && !empty($_POST['del']) && in_array("{$k1}-{$one}",$_POST['del'])) {//post 删除的 跳过
					$show_info .= "$one 被删除！<br/>";
					continue;
				}elseif ($posted && !empty($_POST['mod']) && in_array("{$k1}-{$one}",$_POST['mod'])) {//修改value
					$modi = 1;
					$modvalue=xmlentities (trim($_POST["{$k1}{$one}m"]));
					if (empty($modvalue))
						$show_info .= "$one 被修改为空值 ！<br/>";
					else 
						$show_info .= "$one 被修改为 $modvalue ！<br/>";
				};
				$str ="";
				foreach ($getone as $tmpk=>$tmpv){
					if ($modi && ($tmpk =="value" || $tmpk =="data" || ($k1=='alias' || $k1=='domain') && $tmpk =="name"))
						$tmpv = $modvalue;
					$str.="$tmpk=\"$tmpv\" ";
				}
				if (fmod($i,2)==0)
					$bg = "class='bg1'";
				else 
					$bg = "class='bg2'";
				$i++;
				$html .="<tr $bg><td class='blod14'>$i.<em class='bgblue'>$k1 </em>&nbsp; $str </td><td>【改为<input type='text' name='{$k1}{$one}m' value='' size=8 class='inputline'/><label><input type='checkbox' name='mod[]' value='{$k1}-{$one}'/>选择修改</label>】 <label>【<input type='checkbox' name='del[]' value='{$k1}-{$one}'/>选择删除】</label> </td></tr>";
				$content .= "<$k1 $str/>\n";
			}
		}
		if ($key =="gateways")
			$input ="<input type='hidden' value='X-PRE-PROCESS' name='addkey' >新加命令：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		elseif ($key =="aliases")
			$input ="<input type='hidden' value='alias' name='addkey' >新别名：<input type='text' value='' name='addname' class='inputline' size=8 >";
		elseif ($key =="domains")
			$input ="<input type='hidden' value='domain' name='addkey' >新加域：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp;是否别名：<input type='text' name='addvalue'  value='' class='inputline' size=4/>  &nbsp;是否解析：<input type='text' name='addmore'  value='' class='inputline' size=4/>";
		elseif ($key =="settings")
			$input ="<input type='hidden' value='param' name='addkey' >新设置：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		else
			$input ="项目：<input type='text' value='param' name='addkey' > 设置：<input type='text' value='' name='addname' class='inputline' size=8 >  &nbsp; 值：<input type='text' name='addvalue'  value='' class='inputline'/>";
		$html .="<tr><td  style='background:#decfff;' align='center' class='blod14' colspan=2><input type='hidden' value='$key' name='lists' >$key $input &nbsp; <input type='submit'  value='提交操作' $post_botton_enabled onclick=\"return confirm('是否确认提交，提交后会重启服务器！请务必谨慎');\"></td></tr></form>";
			$content .= "</$key>\n";
		}
if (!empty($_POST['list_add'])){
	$listname = xmlentities ($_POST['list_add']);
	$show_info .= "添加新分组 $listname ！刷新页面后可见";
	$content .= "<$listname>\n</$listname>\n";
}
$content .= "</profile>";

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body><p class='pcenter' style='font-size:18pt;'>sofia External主配置控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><th colspan=2>* 本配置文件是sofia External的主干配置，用于外呼控制，路由设置都被本配置调用<br/>修改信息前务必谨慎！！！不允许输入 &、引号、尖括号</span> <span onclick="$('#show_moreinfo_').toggle();" style='cursor:pointer;'>更多参数说明>>></span><br/><span class=red>$show_info $showinfo</span><textarea id='show_moreinfo_'  style='width:780px;height:200px;display:none;'/>
1）如果希望 FreeSWITCH在加载这个配置文件失败时停止运行，可以使用
    <param name="shutdown-on-fail" value="true"/>
2）如果启用RFC 5626 : Send reg-id and sip.instance
    <param name="enable-rfc-5626" value="true"/>
3）下面是用于共享存在信息
         manage-presence needs to be set to passive on this profile
         if you want it to behave as if it were the internal profile  for presence.
    <!-- Name of the db to use for this profile -->
    <param name="dbname" value="share_presence"/>
    <param name="presence-hosts" value="\$\${domain}"/>
    <param name="force-register-domain" value="\$\${domain}"/>
    <!--all inbound reg will stored in the db using this domain -->
    <param name="force-register-db-domain" value="\$\${domain}"/>
4）rtp ip 和 sip ip 不要用主机名，这里需要用ip地址
5）alias是设置本配置文件的别名
6）指定域的设置中设置 parse="true" 以用于从域目录中获取网关
    <domain name="\$\${domain}" parse="true"/>
    对所有域设置 parse="true" 以用于从他们的域目录中获取网关和别名
    <domain name="all" alias="true" parse="true"/></textarea></span></th>$html
<form method="post"><tr><td colspan=2 style='background:#decedd;'><em>新建分组</em> 分组名称：<input id="list_add" name="list_add" value="" size=20 class="inputline"/> <span class="smallred  smallsize-font">* 不允许&、引号、尖括号</span> &nbsp; <input type="submit" value="添加" $post_botton_enabled onclick="return confirm('是否是否确认提交，提交后会重启服务器！请务必谨慎');"/></td></tr>
<th colspan=2>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> run('sofia','profile external restart');
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
echo "</th></form></table></body></html>";