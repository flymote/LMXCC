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

$conf_file = $_SESSION['conf_dir'].'/autoload_configs/xml_cdr.conf.xml';
$xml_string = file_get_contents($conf_file);
$xml = simplexml_load_string($xml_string);
if (!$xml)
	die("无法读取系统配置文件！");
$settings = array();
foreach ($xml->settings->param as $one){
	foreach ($one->attributes() as $key =>$two){
		if ($key=='name'){
			$temp = "$two";
			$settings[$temp]="";
		}elseif ($key=='value'){
			$settings[$temp]="$two";
		}
	}
};
$content = '<configuration name="xml_cdr.conf" description="XML CDR CURL logger"><settings>';

$posturlcss = "inputline";
$posturl_lab = "CDR提交的WEB URL，标准的HTTP地址，如果不设置则不进行提交";
if (!empty($_POST['posturl'])){
	$posturlcss = "inputline1";
	$posturl = xmlentities($_POST['posturl']);
}elseif(isset($settings["url"])){
	$posturlcss = "inputline1";
	$posturl = $settings["url"];
}else{
	$file =__DIR__.'/.Config';
	if (is_file($file))
		$conf = @unserialize(file_get_contents($file));
	else 
		$conf = false;
	if ($conf && !empty($conf['CDR_url']))
		$posturl = $conf['CDR_url'];
	else
		$posturl = "http://182.106.129.235:88/FSlmx/cdr_post.php";
}
$content .= "<param name=\"url\" value=\"$posturl\"/>";

$retriescss = "inputline";
$retries_lab = "设置WEB提交失败后的重复次数，不含首次重试 ，默认为2";
if (!empty($_POST['retries']) && intval($_POST['retries'])>0){
	$retriescss = "inputline1";
	$retries = intval($_POST['retries']);
}elseif(isset($settings["retries"])){
	$retriescss = "inputline1";
	$retries = $settings["retries"];
}else
	$retries = 2;
$content .= "<param name=\"retries\" value=\"$retries\"/>";

$delaycss = "inputline";
$delay_lab = "设置重试延迟时间，单位为秒，默认5秒";
if (!empty($_POST['delay']) && intval($_POST['delay'])>0){
	$delaycss = "inputline1";
	$delay = intval($_POST['delay']);
}elseif(isset($settings["delay"])){
	$delaycss = "inputline1";
	$delay = $settings["delay"];
}else
	$delay = 5;
$content .= "<param name=\"delay\" value=\"$delay\"/>";

$timeoutcss = "inputline";
$timeout_lab = "设置WEB提交超时时间，单位为秒，默认20秒";
if (!empty($_POST['timeout']) && intval($_POST['timeout'])>0){
	$timeoutcss = "inputline1";
	$timeout = intval($_POST['timeout']);
}elseif(isset($settings["timeout"])){
	$timeoutcss = "inputline1";
	$timeout = $settings["timeout"];
}else
	$timeout = 20;
$content .= "<param name=\"timeout\" value=\"$timeout\"/>";

$save = 0;
$logcheck ="";
$logcss = "inputline";
$log_lab = "日志提交给WEB和磁盘，默认是false";
if (!empty($_POST['save_log'])  && in_array($_POST['log'],array('false','true'))){
	$save = 1;
	$logcss = "inputline1";
	$log = $_POST['log'];
}elseif(isset($settings["log-http-and-disk"])){
	$logcss = "inputline1";
	$logcheck = 'checked="checked" ';
	$log = $settings["log-http-and-disk"];
}else
	$log = "false";
if ($save)
	$content .= "<param name=\"log-http-and-disk\" value=\"$log\"/>";

$log_dircss = "inputline";
$log_dir_lab = "设置绝对路径 或 相对路径（如\$\${log_dir}/a）或 空（即\$\${log_dir}/xml_cdr），目录必须存在，默认空";
if (!empty($_POST['log_dir'])){
	$log_dircss = "inputline1";
	$log_dir = xmlentities($_POST['log_dir']);
	if (!file_exists($log_dir))
		$log_dir = "";
}elseif(isset($settings["log-dir"])){
	$log_dircss = "inputline1";
	$log_dir = $settings["log-dir"];
}else
	$log_dir = "";
$content .= "<param name=\"log-dir\" value=\"$log_dir\"/>";
 
$bcss = "inputline";
$b_lab = "设置是否记录B_leg，默认是false，默认不记录b_leg的CDR";
if (!empty($_POST['b_leg']) && $_POST['b_leg']=='true'){
	$bcss = "inputline1";
	$b_leg = 'true';
}elseif(isset($settings["log-b-leg"])){
	$bcss = "inputline1";
	$b_leg = $settings["log-b-leg"];
}else
	$b_leg = "false";
$content .= "<param name=\"log-b-leg\" value=\"$b_leg\"/>";

$a_precss = "inputline";
$a_pre_lab = "设置是否在a_leg的CDR文件前面加上“a_“前缀，默认为true";
if (!empty($_POST['a_pre']) && $_POST['a_pre']=='false'){
	$a_precss = "inputline1";
	$a_pre = $_POST['a_pre'];
}elseif(isset($settings["prefix-a-leg"])){
	$a_precss = "inputline1";
	$a_pre = $settings["prefix-a-leg"];
}else
	$a_pre = "true";
$content .= "<param name=\"prefix-a-leg\" value=\"$a_pre\"/>";

$encodecss = "inputline";
$encode_lab = "设置是否对提交的信息进行编码，设置为true是进行URL编码，false不编码，'base64'是base64编码，'textxml'表示提交的是text/xml，默认为true";
if (!empty($_POST['encode']) && in_array($_POST['encode'],array('false','true'))){
	$encodecss = "inputline1";
	$encode = $_POST['encode'];
}elseif(isset($settings["encode"])){
	$encodecss = "inputline1";
	$encode = $settings["encode"];
}else
	$encode = "true";
$content .= "<param name=\"encode\" value=\"$encode\"/>";

$save = 0;
$credentialscss = "inputline";
$credentialscheck = "";
$credentials_lab = "WEB提交认证信息，按 用户名:密码 的格式设置，不含&、引号、尖括号";
if (!empty($_POST['save_credentials']) && !empty($_POST['credentials']) ){
	$credentialscss = "inputline1";
	$credentials = xmlentities($_POST['credentials']);
	$save = 1;
}elseif(isset($settings["cred"])){
	$credentialscss = "inputline1";
	$credentialscheck = 'checked="checked" ';
	$credentials = $settings["cred"];
}else
	$credentials = "user:pass";
if ($save)
	$content .=  "<param name=\"cred\" value=\"$credentials\"/>";

$save = 0;
$lighttpdcss = "inputline";
$lighttpdcheck ='';
$lighttpd_lab = "设置是否使用预期的100-continue信息，这是针对 lighttpd 的设置，默认为true";
if (!empty($_POST['save_lighttpd']) && in_array($_POST['lighttpd'],array('false','true'))){
	$lighttpdcss = "inputline1";
	$lighttpd = $_POST['lighttpd'];
	$save = 1;
}elseif(isset($settings["disable-100-continue"])){
	$lighttpdcss = "inputline1";
	$lighttpdcheck = 'checked="checked" ';
	$lighttpd = $settings["disable-100-continue"];
}else
	$lighttpd = "true";
if ($save)
	$content .= "<param name=\"disable-100-continue\" value=\"$lighttpd\"/>";

$save = 0;
$err_logcss = "inputline";
$err_logcheck ='';
$err_log_lab = "WEB提交失败后错误日志的路径，默认用上面的日志目录，需设置为已存在的绝对路径 或  相对路径（如\$\${log_dir}/a）或 空（即\$\${log_dir}/xml_cdr）";
if (!empty($_POST['save_err_log']) && !empty($_POST['err_log'])){
	$err_logcss = "inputline1";
	$save = 1;
	$err_log = xmlentities($_POST['err_log']);
	if (!file_exists($err_log))
		$err_log = "/tmp/";
}elseif(isset($settings["err-log-dir"])){
	$err_logcss = "inputline1";
	$err_logcheck = 'checked="checked" ';
	$err_log = $settings["err-log-dir"];
}else
	$err_log = '/tmp/';
if ($save)
	$content .= "<param name=\"err-log-dir\" value=\"$err_log\"/>";

$save = 0;
$authcss = "inputline";
$authcheck ='';
$auth_lab = "提交WEB时的验证模式，设置为 basic, digest, NTLM, GSS-NEGOTIATE 或 any（自动），默认是basic";
if (!empty($_POST['save_auth']) && in_array($_POST['auth'],array('basic','digest','NTLM','GSS-NEGOTIATE','any'))){
	$authcss = "inputline1";
	$save = 1;
	$auth = $_POST['auth'];
}elseif(isset($settings["auth-scheme"])){
	$authcss = "inputline1";
	$authcheck = 'checked="checked" ';
	$auth = $settings["auth-scheme"];
}else
	$auth = "basic";
if ($save)
	$content .= "<param name=\"auth-scheme\" value=\"$auth\"/>";

$save = 0;
$certcss = "inputline";
$certcheck ='';
$cert_lab = "设置为true后，会使用libcurl去验证CA的根证书，默认是false";
if (!empty($_POST['save_cert']) && in_array($_POST['cert'],array('false','true'))){
	$certcss = "inputline1";
	$save = 1;
	$cert = $_POST['cert'];
}elseif(isset($settings["enable-cacert-check"])){
	$certcss = "inputline1";
	$certcheck ='checked="checked" ';
	$cert = $settings["enable-cacert-check"];
}else
	$cert = "false";
if ($save)
	$content .= "<param name=\"enable-cacert-check\" value=\"$cert\"/>";

$save = 0;
$ssl_hostcss = "inputline";
$ssl_hostcheck = '';
 $ssl_host_lab = "SSL验证是否当前主机在CA证书列表中，默认为false";
 if (!empty($_POST['save_ssl_host']) && in_array($_POST['ssl_host'],array('false','true'))){
 	$ssl_hostcss = "inputline1";
 	$save = 1;
 	$ssl_host = $_POST['ssl_host'];
 }elseif(isset($settings["enable-ssl-verifyhost"])){
 	$ssl_hostcss = "inputline1";
 	$ssl_hostcheck = 'checked="checked" ';
 	$ssl_host = $settings["enable-ssl-verifyhost"];
 }else
 	$ssl_host = "false";
if ($save)
	$content .= "<param name=\"enable-ssl-verifyhost\" value=\"$ssl_host\"/>";

$save = 0;
$sslpcheck = '';
$sslcss = "inputline";
 $ssl_lab = "SSL通讯相关设置：设置公钥证书位置、私钥证书位置及密码，默认是\$\${base_dir}/certs/public_key.pem、private_key.pem";
 if (!empty($_POST['save_ssl_pub_cert_path']) && !empty($_POST['ssl_pub_cert_path']) && !empty($_POST['ssl_priv_cert_path'])){
 	$sslcss = "inputline1";
 	$ssl_pub_cert_path = xmlentities($_POST['ssl_pub_cert_path']);
 	$ssl_priv_cert_path = xmlentities($_POST['ssl_priv_cert_path']);
 	$save = 1;
 }elseif(isset($settings["ssl-cert-path"])){
 	$sslcss = "inputline1";
 	$sslpcheck = 'checked="checked" ';
 	$ssl_pub_cert_path = $settings["ssl-cert-path"];
 	$ssl_priv_cert_path = $settings["ssl-key-path"];
 }else{
 	$ssl_pub_cert_path = "\$\${base_dir}/certs/public_key.pem";
 	$ssl_priv_cert_path = "\$\${base_dir}/certs/private_key.pem";
 }
 if (!empty($_POST['ssl_priv_cert_pwd']))
 	$ssl_priv_cert_pwd = xmlentities($_POST['ssl_priv_cert_pwd']);
 else 
 	$ssl_priv_cert_pwd = "";
 if ($save)
 	$content .= "<param name=\"ssl-cert-path\" value=\"$ssl_pub_cert_path\"/><param name=\"ssl-key-path\" value=\"$ssl_priv_cert_path\"/><param name=\"ssl-key-password\" value=\"$ssl_priv_cert_pwd\"/>";

$save = 0;
$ssl_cacertcss = "inputline";
$ssl_cacertcheck = '';
$ssl_cacert_lab = "SSL使用自定义的CA证书，定义其路径和名称，需先在上面启用 验证CA根证书，默认文件是\$\${base_dir}/certs/cacert.pem";
if (!empty($_POST['save_ssl_cacert']) && !empty($_POST['ssl_cacert'])){
	$ssl_cacertcss = "inputline1";
	$ssl_cacert = xmlentities($_POST['ssl_cacert']);
	$save = 1;
}elseif(isset($settings["ssl-cacert-file"])){
	$ssl_cacertcss = "inputline1";
	$ssl_cacertcheck = 'checked="checked" ';
	$ssl_cacert = $settings["ssl-cacert-file"];
}else
	$ssl_cacert = "\$\${base_dir}/certs/cacert.pem";
if ($save)
	$content .= "<param name=\"ssl-cacert-file\" value=\"$ssl_cacert\"/>";

$save = 0;
$ssl_vercss = "inputline";
$ssl_vercheck = '';
$ssl_ver_lab = "SSL的版本，设置值为 SSLv3, TLSv1，其他的值 libcurl 会自动适应";
if (!empty($_POST['save_ssl_ver']) && !empty($_POST['ssl_ver'])){
	$ssl_vercss = "inputline1";
	$ssl_ver = xmlentities($_POST['ssl_ver']);
	$save = 1;
}elseif(isset($settings["ssl-version"])){
	$ssl_vercss = "inputline1";
	$ssl_vercheck ='checked="checked" ';
	$ssl_ver = $settings["ssl-version"];
}else
	$ssl_ver = "TLSv1";
if ($save)
	$content .= "<param name=\"ssl-version\" value=\"$ssl_ver\"/>";

$save = 0;
$cookie_filecss = "inputline";
$cookie_filecheck= '';
$cookie_file_lab = "启用cookie并指定到设置的文件，设置文件路径和名称，默认为 \$\${run_dir}/mod_xml_cdr-cookie.txt ";
if (!empty($_POST['save_cookie_file']) && !empty($_POST['cookie_file'])){
	$cookie_filecss = "inputline1";
	$cookie_file = xmlentities($_POST['cookie_file']);
	$save = 1;
}elseif(isset($settings["cookie-file"])){
	$cookie_filecss = "inputline1";
	$cookie_filecheck = 'checked="checked" ';
	$cookie_file = $settings["cookie-file"];
}else
	$cookie_file = "\$\${run_dir}/mod_xml_cdr-cookie.txt";
if ($save)
	$content .= "<param name=\"cookie-file\" value=\"$cookie_file\"/>";

$content .= "</settings></configuration>";
$showinfo = "";
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
</head><body><p class='pcenter' style='font-size:18pt;'>xml CDR 控制台 <a style='font-size:10pt;' href='main.php'>&raquo;&nbsp;返回主控页</a></p>
<table class="tablegreen" width="800" align="center"><form method="post"><th colspan=2>* 错误设置会导致无法正确得到CDR信息！<br/>* 参数橙色当前其存在；说明文字橙色表示其必须设置，蓝色表示可选（勾选启用）<br/>$showinfo</th>
<tr><td><em>CDR提交地址：</em><input id="posturl" name="posturl" value="$posturl" size=45 onclick="this.select();" class="$posturlcss"/></td><td width="50%"><span class="smallred smallsize-font">$posturl_lab</span></td></tr>
<tr><td><em>WEB提交重试：</em><input id="retries" name="retries" value="$retries" size=45 onclick="this.select();" class="$retriescss"/></td><td><span class="smallred smallsize-font">$retries_lab</span></td></tr>
<tr><td><em>WEB重试延迟时间：</em><input id="delay" name="delay" value="$delay" size=45 onclick="this.select();" class="$delaycss"/></td><td><span class="smallred smallsize-font">$delay_lab</span></td></tr>
<tr><td><em>WEB超时时间：</em><input id="timeout" name="timeout" value="$timeout" size=45 onclick="this.select();" class="$timeoutcss"/></td><td><span class="smallred smallsize-font">$timeout_lab</span></td></tr>
<tr><td><em>日志保存目录：</em><input id="log_dir" name="log_dir" value="$log_dir" size=45 onclick="this.select();" class="$log_dircss"/></td><td><span class="smallred smallsize-font">$log_dir_lab</span></td></tr>
<tr><td><em>b_leg保存：</em><input id="b_leg" name="b_leg" value="$b_leg" size=45 onclick="this.select();" class="$bcss"/></td><td><span class="smallred smallsize-font">$b_lab</span></td></tr>
<tr><td><em>a_leg前缀：</em><input id="a_pre" name="a_pre" value="$a_pre" size=45 onclick="this.select();" class="$a_precss"/></td><td><span class="smallred smallsize-font">$a_pre_lab</span></td></tr>
<tr><td><em>提交数据编码：</em><input id="encode" name="encode" value="$encode" size=45 onclick="this.select();" class="$encodecss"/></td><td><span class="smallred smallsize-font">$encode_lab</span></td></tr>
<tr><td><label><em>WEB用户：</em><input id="save_credentials" name="save_credentials" value="1" type="checkbox" $credentialscheck></label><input id="credentials" name="credentials" value="$credentials" size=45 onclick="this.select();" class="$credentialscss"/></td><td><span class="smallblue smallsize-font">$credentials_lab</span></td></tr>
<tr><td><label><em>日志保存方式：</em><input id="save_log" name="save_log" value="1" type="checkbox" $logcheck></label><input id="log" name="log" value="$log" size=45 onclick="this.select();" class="$logcss"/></td><td><span class="smallblue smallsize-font">$log_lab</span></td></tr>
<tr><td><label><em>lighttpd设置：</em><input id="save_lighttpd" name="save_lighttpd" value="1" type="checkbox" $lighttpdcheck></label><input id="lighttpd" name="lighttpd" value="$lighttpd" size=45 onclick="this.select();" class="$lighttpdcss"/></td><td><span class="smallblue smallsize-font">$lighttpd_lab</span></td></tr>
<tr><td><label><em>错误日志设置：</em><input id="save_err_log" name="save_err_log" value="1" type="checkbox" $err_logcheck></label><input id="err_log" name="err_log" value="$err_log" size=45 onclick="this.select();" class="$err_logcss"/></td><td><span class="smallblue smallsize-font">$err_log_lab</span></td></tr>
<tr><td><label><em>提交认证设置：</em><input id="save_auth" name="save_auth" value="1" type="checkbox" $authcheck></label><input id="auth" name="auth" value="$auth" size=45 onclick="this.select();" class="$authcss"/></td><td><span class="smallblue smallsize-font">$auth_lab</span></td></tr>
<tr><td><label><em>SSL版本设置：</em><input id="save_ssl_ver" name="save_ssl_ver" value="1" type="checkbox" $ssl_vercheck></label><input id="ssl_ver" name="ssl_ver" value="$ssl_ver" size=45 onclick="this.select();" class="$ssl_vercss"/></td><td><span class="smallblue smallsize-font">$ssl_ver_lab</span></td></tr>
<tr><td><label><em>根证书验证：</em><input id="save_cert" name="save_cert" value="1" type="checkbox" $certcheck></label><input id="cert" name="cert" value="$cert" size=45 onclick="this.select();" class="$certcss"/></td><td><span class="smallblue smallsize-font">$cert_lab</span></td></tr>
<tr><td><label><em>SSL自定义证书设置：</em><input id="save_ssl_cacert" name="save_ssl_cacert" value="1" type="checkbox" $ssl_cacertcheck></label><input id="ssl_cacert" name="ssl_cacert" value="$ssl_cacert" size=45 onclick="this.select();" class="$ssl_cacertcss"/></td><td><span class="smallblue smallsize-font">$ssl_cacert_lab</span></td></tr>
<tr><td><label><em>服务器证书验证：</em><input id="save_ssl_host" name="save_ssl_host" value="1" type="checkbox" $ssl_hostcheck></label><input id="ssl_host" name="ssl_host" value="$ssl_host" size=45 onclick="this.select();" class="$ssl_hostcss"/></td><td><span class="smallblue smallsize-font">$ssl_host_lab</span></td></tr>
<tr><td><label><em>SSL证书设置：</em><input id="save_ssl_pub_cert_path" name="save_ssl_pub_cert_path" value="1" type="checkbox" $sslpcheck><br/>
公钥文件<input id="ssl_pub_cert_path" name="ssl_pub_cert_path"  class="$sslcss" value="$ssl_pub_cert_path" size=30 onclick="this.select();"/><br/>
私钥文件<input id="ssl_priv_cert_path" name="ssl_priv_cert_path"  class="$sslcss" value="$ssl_priv_cert_path" size=30 onclick="this.select();"/><br/>
私钥密码<input id="ssl_priv_cert_pwd" name="ssl_priv_cert_pwd"  class="$sslcss" value="$ssl_priv_cert_pwd" size=30 onclick="this.select();"/></td><td><span class="smallblue smallsize-font">$ssl_lab</span></td></tr>
<tr><td><label><em>cookie文件设置：</em><input id="save_cookie_file" name="save_cookie_file" value="1" type="checkbox" $cookie_filecheck></label><input id="cookie_file" name="cookie_file" value="$cookie_file" size=45 onclick="this.select();" class="$cookie_filecss"/></td><td><span class="smallblue smallsize-font">$cookie_file_lab</span></td></tr>
<th><span class="smallred smallsize-font">*提交后即刻生效，请谨慎操作</span></th><th>  <input type="submit" value="确认提交" onclick="return confirm('是否确认提交？');" $post_botton_enabled/>
HTML;
if (!empty($_POST)){
	$fout = fopen($conf_file,"w");
	fwrite($fout, $content);
	fclose($fout);
	$info = new detect_switch();
	$info-> run('apireload','mod_xml_cdr');
	$_SESSION['POST_submit_once'] = 1;
}else 
	$_SESSION['POST_submit_once']= 0 ;
// echo "<textarea>$content</textarea>";
echo "</th></form></table></body></html>";