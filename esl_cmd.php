<?php
set_time_limit(100);
session_start();
date_default_timezone_set('Asia/Shanghai');

if (!empty($_POST['ESL_HOST'])){
	define("ESL_HOST", $_POST['ESL_HOST']);
	define("ESL_PORT", $_POST['ESL_PORT']);
	define("ESL_PASSWORD",$_POST['ESL_PASSWORD']);
	$_SESSION['ESL_HOST'] = $_POST['ESL_HOST'];
	$_SESSION['ESL_PORT'] = $_POST['ESL_PORT'];
	$_SESSION['ESL_PASSWORD'] = $_POST['ESL_PASSWORD'];
}else{
	define("ESL_HOST", @$_SESSION['ESL_HOST']);
	define("ESL_PORT", @$_SESSION['ESL_PORT']);
	define("ESL_PASSWORD",@$_SESSION['ESL_PASSWORD']);
}
$opt = array('persistentConnection'=>true);

require_once "event_socket_classes.php";
//操作Freeswitch
class in_FS {
		// cached data
		protected $_data=array();

		// event socket
		public $event_host = 'localhost';
		public $event_port = '8021';
		public $event_password = 'ClueCon';
		public $opt = array('persistentConnection'=>false,'timeOut'=>10,'defaultTimeout'=>30,'blocking'=>1);
		protected $event_socket;
		protected $esl;
		public function __construct($event_host="", $event_port="", $event_password="",$opt = array()) {
			//do not take these settings from session as they be detecting a new switch
			if($event_host) $this->event_host = $event_host; 
			if($event_port) $this->event_port = $event_port;
			if($event_password) $this->event_password = $event_password; 
			if(is_array($opt)) $this->opt = $opt; 
			if (!$this->connect_event_socket())
				die(' Error : Failed to use event socket ! 严重错误，没有连接上服务器！');
		}
		protected function connect_event_socket(){
				$this->esl = new event_socket($this->event_host, $this->event_port, $this->event_password,$this->opt);
			if ($this->esl->isConnected()){
				return true;
			}else
				return false;
		}
		
		/*
		 *调用ESL类的方法执行相关ESL指令并获取显示结果
		 *$cmd 需要执行的指令，如 api bgapi 等
		 *$args  相关参数，用空格分开
		 *$returnhtml 返回显示结果的格式，0 为文本 1为html
		 *$isxml 反馈信息是否为xml格式，默认0
		 */
		public function run($cmd,$args='',$returnhtml=1,$isxml = 0){
			if (empty($cmd))
				return false;
			$FS_Vars = $this->esl->getResponse($this->esl->$cmd($args));
			if ($isxml){
				$xml = simplexml_load_string($FS_Vars);
				if (!$xml)
					die("无法读取XML！");
					foreach ($xml as $k=>$v){
						$this->_data[$k] = $v;
					};
			}else
			foreach (explode("\n",$FS_Vars) as $FS_Var){
				$var = explode("=", $FS_Var);
				if (isset($var[1]))
					$this->_data[$var[0]] = $var[1];
				else
					$this->_data[] = $FS_Var;
			}
			if ($returnhtml){
				echo "<ul>$cmd $args 反馈信息：";
				foreach ($this->_data as $k=>$v){
					if (is_numeric($k))
						echo "<li>&nbsp; &nbsp; $v</li>";
					else 
						echo "<li><b>$k :</b> $v</li>";
				}
				echo "</ul>";
			}else{
				echo "$cmd $args 反馈信息：\n";
				foreach ($this->_data as $k=>$v){
					if (is_numeric($k))
						echo "[ $v ]\n";
					else
						echo "[ $k : ] $v\n";
				}
				echo "\n";
			}
		}
}

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body>
HTML;
echo '<form method="post" id="form1" name="form1" style="margin-top:10pt;">
        <ul style="text-align:center;">Host <input id="ESL_HOST" name="ESL_HOST" value="'.ESL_HOST.'" size=14>：<input id="ESL_PORT" name="ESL_PORT" value="'.ESL_PORT.'" size=2> PWD <input id="ESL_PASSWORD" name="ESL_PASSWORD" value="'.ESL_PASSWORD.'" size=8>
        <li>输入命令：<input id="cmd" name="cmd" value="'.@$_POST['cmd'].'"> 参数：<input id="arg" name="arg" value=""> &nbsp; <input type="submit" value="确认" onclick="this.value=\'请等待！... ... ...\';this.submit();"/></li>
        <li>可用命令：nat(status reinit republish) version status channels calls global_getvar restart reloadacl reloadxml reload(..) apireload(..) sofia(..) show(..) api(..) bgapi(..) event(..) nixevent(..) execute(..) executeAsync(..)... </li>
        <li>查看事件：<a href="es_listener.php?cmd=event plain ALL" target="_blank">event plain ALL</a>  <a href="es_listener.php?cmd=event custom asr" target="_blank">event custom asr</a></li>
        </form></body></html>';
// $ES->list_switch_info();
if (ESL_HOST){
	$ES = new in_FS(ESL_HOST,ESL_PORT,ESL_PASSWORD,$opt);
	if (!empty($_POST['cmd'])){
		$ES->run(trim($_POST['cmd']),trim($_POST['arg']));
	}
}
