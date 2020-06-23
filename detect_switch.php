<?php
require_once "event_socket_classes.php";
//检测Freeswitch相关信息
class detect_switch {
		// cached data
		protected $_data=array();

		// event socket
		public $event_host = 'localhost';
		public $event_port = '8021';
		public $event_password = 'ClueCon';
		protected $event_socket;
		protected $esl;
		public function __construct($event_host="", $event_port="", $event_password="") {
			//do not take these settings from session as they be detecting a new switch
			if($event_host) $this->event_host = $event_host; 
			if($event_port) $this->event_port = $event_port;
			if($event_password) $this->event_password = $event_password;  
			if (!$this->connect_event_socket())
				die(' Error : Failed to use event socket ! 严重错误，没有连接上服务器 ');
		}
		protected function connect_event_socket(){
			$options = array('persistentConnection' => 1); //	'timeOut' => 10,	'defaultTimeout' => 30,'blocking' => 1
			$this->esl = new event_socket($this->event_host, $this->event_port, $this->event_password,$options);
			if ($this->esl->isConnected())
				return true;
			else
				return false;
		}
		
		public function get_api_reply($cmd){
			return $this->esl->getResponse($this->esl->api($cmd));
		}
		
		//根据提供的服务器id（fs_setting表）获取相关信息并保存入库
		public function get_switch_info($sid){
			if(!$this->esl->isConnected()){
				die(' Error : Failed to use event socket ! 严重错误，当前并没有连接服务器 ');
			}
			global $mysqli;
			if (!is_object($mysqli)){
				die(' Error : Failed to open DB ! 严重错误，没有连接到数据库 ');
			}
			$FS_Version = $this->esl->getResponse($this->esl->version());
			$FS_Vars = $this->esl->getResponse($this->esl->global_getvar());
			$external_sip_port=$internal_sip_port=0;
			$default_provider = $conf_dir = $core_uuid=$recordings_dir=$log_dir='';
			foreach (explode("\n",$FS_Vars) as $FS_Var){
				$var = explode("=", $FS_Var);
				$var[0] = trim($var[0]);
				if (isset($var[1])){
					if ($var[0]=='core_uuid')
						$core_uuid = $var[1];
					elseif($var[0]=='sounds_dir')
						$sounds_dir = $var[1];
					elseif($var[0]=='recordings_dir')
						$recordings_dir = $var[1];
					elseif($var[0]=='conf_dir')
					$conf_dir = $var[1];
					elseif($var[0]=='log_dir')
					$log_dir   = $var[1];
					elseif($var[0]=='external_sip_port')
					$external_sip_port  = $var[1];
					elseif($var[0]=='internal_sip_port')
					$internal_sip_port   = $var[1];
					elseif($var[0]=='default_provider')
					$default_provider  = $var[1];
				}
			}
			if ($conf_dir){
				$sql ="update fs_setting set `version` ='$FS_Version',`core_uuid`='$core_uuid', `recordings_dir`='$recordings_dir',  `sounds_dir`='$sounds_dir', `default_provider`='$default_provider',`conf_dir`='$conf_dir',`log_dir`='$log_dir',`external_sip_port`='$external_sip_port',`internal_sip_port`='$internal_sip_port' where id = $sid limit 1";
				$return = $mysqli->query($sql);
				if ($return)
					return true;
				else 
					return $sql;
			}else return false;
		}
		
		public function list_switch_info(){
			if(!$this->esl->isConnected()){
				die(' Error : Failed to use event socket ! 严重错误，当前并没有连接服务器 ');
			}
			echo "<ul>";
			
			$FS_Vars = $this->esl->getResponse($this->esl->status());
			foreach (explode("\n",$FS_Vars) as $FS_Var){
				if ($FS_Var){
					$var = explode("=", $FS_Var);
					if (isset($var[1]))
						$this->_data[$var[0]] = $var[1];
					else
						$this->_data[] = $FS_Var;
				}
			}
			echo "<p style='width:1000px;margin: 0 auto;position: relative;cursor:pointer;text-align:center;background:#00A3F0;color:white;border:1px solid #DCDCDC;' onclick='$(\"#FSstatusInfoShow\").toggle(300);'> =↕= 状态信息  status =↕=</p><div id='FSstatusInfoShow' style='width:1000px;margin: 0 auto;position: relative;'>";
			foreach ($this->_data as $k=>$v){
				echo "<li>&nbsp; &nbsp; $v</li>";
			}
			$this->_data = array();
			
			$FS_Vars = $this->get_api_reply("show modules");
			foreach (explode("\n",$FS_Vars) as $k=>$FS_Var){
				if ($k && $FS_Var){ //忽略第一行和空行
					$var = explode(",", $FS_Var);
					if (isset($var[3]))
						$this->_data[$var[0]][$var[3]][$var[2]][] = $var[1];	
				}	
			}
			$check = ["mod_commands"=>1,"mod_callcenter"=>1,"mod_dptools"=>1,"mod_console"=>1,"mod_curl"=>1,"mod_db"=>1,"mod_event_socket"=>1,"mod_hash"=>1,"mod_local_stream"=>1,"mod_sofia"=>1,"mod_xml_cdr"=>1];
			$str = "</div><p style='width:1000px;margin: 0 auto;position: relative;cursor:pointer;text-align:center;background:#00A3F0;color:white;border:1px solid #DCDCDC;' onclick='$(\"#FSmodulesInfoShow\").toggle(500);'> =↕= 模块信息  modules =↕=</p><div id='FSmodulesInfoShow' style='width:1000px;margin: 0 auto;position: relative;'>";
			foreach ($this->_data as $k=>$v){
				$str .= "<p class=pcenter><span class='bgblue bold16'> &nbsp; $k &nbsp; </span></p>";
				foreach ($v as $k1=>$v1){
					$str .=  "<li><b>$k1 :</b></li>";
					foreach ($v1 as $k2=>$v2){
						if (isset($check[$k2])) unset($check[$k2]);
						$str .=  "<span class='bggreen bold12'> &nbsp;  $k2  &nbsp;  </span>".implode(" 、", $v2);
					}
				}
			}
			if (count($check)){
				echo "<p class='pcenter bgred bold14'>Freeswitch问题：必须修正！！缺失模块：".implode(" 、", array_keys($check))."</p>";
			}
			echo $str;
			$this->_data = array();
			$FS_Vars = $this->esl->getResponse($this->esl->global_getvar());
			foreach (explode("\n",$FS_Vars) as $FS_Var){
				$var = explode("=", $FS_Var);
				if (isset($var[1]))
					$this->_data[$var[0]] = $var[1];
					else
						$this->_data[] = $FS_Var;
			}
			echo "</div><p style='width:1000px;margin: 0 auto;position: relative;cursor:pointer;text-align:center;background:#00A3F0;color:white;border:1px solid #DCDCDC;' onclick='$(\"#FSglobalVariousInfoShow\").toggle(500);'> =↕= 全局变量  global_getvar =↕=</p><div id='FSglobalVariousInfoShow' style='width:1000px;margin: 0 auto;position: relative;'>";
			foreach ($this->_data as $k=>$v){
				if (is_numeric($k))
					echo "<li>&nbsp; &nbsp; $v</li>";
					else
						echo "<li><b>$k :</b> $v</li>";
			}
			$this->_data = array();
			
			$FS_Vars = $this->esl->getResponse($this->esl->sofia('status'));
			echo "</div><p style='width:1000px;margin: 0 auto;position: relative;cursor:pointer;text-align:center;background:#00A3F0;color:white;border:1px solid #DCDCDC;' onclick='$(\"#FSsofiaStatusInfoShow\").toggle(300);'> =↕=  sofia状态  sofia status =↕=</p><div id='FSsofiaStatusInfoShow' style='width:1000px;margin: 0 auto;position: relative;'><pre>";
			echo $FS_Vars;
			echo "</pre></div></ul>";
		}
		
		public function restart_switch(){
		$FS_Vars = $this->esl->getResponse($this->esl->restart());
		foreach (explode("\n",$FS_Vars) as $FS_Var){
			$var = explode("=", $FS_Var);
			if (isset($var[1]))
				$this->_data[$var[0]] = $var[1];
			else
				$this->_data[] = $FS_Var;
		}
		echo "<ul>重新启动FreeSwitch：";
		foreach ($this->_data as $k=>$v){
			if (is_numeric($k))
				echo "<li>&nbsp; &nbsp; $v</li>";
			else 
				echo "<li><b>$k :</b> $v</li>";
		}
		echo "</ul>";
		}
		
		/*
		 *调用ESL类的方法执行相关ESL指令并获取显示结果
		 *$cmd 需要执行的指令，如 api bgapi 等
		 *$args  相关参数，用空格分开
		 *$returnhtml 返回显示结果的格式，0 为显示文本 1为显示html 2为返回结果变量
		 *$isxml 反馈信息是否为xml格式，默认0
		 */
		public function run($cmd,$args='',$returnhtml=1,$isxml = 0){
			if (empty($cmd))
				return false;
			$this->_data = [];
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
			if ($returnhtml==1){
				echo "<ul>$cmd $args 反馈信息：";
				foreach ($this->_data as $k=>$v){
					if (is_numeric($k))
						echo "<li>&nbsp; &nbsp; $v</li>";
					else 
						echo "<li><b>$k :</b> $v</li>";
				}
				echo "</ul>";
			}elseif ($returnhtml==2){
				return $this->_data;
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
?>