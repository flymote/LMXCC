<?php
set_time_limit(300);
session_start();
date_default_timezone_set('Asia/Shanghai');
ob_start(); //打开输出缓冲区
ob_end_flush();
ob_implicit_flush(1); //立即输出
ob_flush();

class FreeSwitchEventListener
{
	
	var $password = "ClueCon";
	var $port = "8021";
	var $host = "127.0.0.1";
	var $limit = 100000;
	var $fp = null ;
	
	var $iRetryCurrentNumber = 0 ;
	var $iRetryMaxNumber = 10 ;
	
	function __construct($host = null, $port = null , $password = null)
	{
		if( !is_null( $host) ) {
			$this->setHost( $host) ;
		}
		
		if( !is_null( $port) ) {
			$this->setPort( $port) ;
		}
		
		if( !is_null( $password) ) {
			$this->setPassword( $password) ;
		}
		
		return $this ;
	}
	
	
	public function setHost( $host ){
		$this->host = $host ;
		return $this;
	}
	
	public function setPort( $port ){
		$this->port = $port ;
		return $this;
	}
	
	public function setPassword( $password ){
		$this->password = $password ;
		return $this;
	}
	
	private function event_socket_create() {
		try {
			$this->fp = @fsockopen($this->host, $this->port, $errno, $errdesc)  ;
		} catch (Exception $e) {
			die("【event_socket_create】fail to connection! $e");
		}
		
		if (!$this->fp) {
			echo "<li>【event_socket_create】{$this->host} 连接失败 重试{$this->iRetryCurrentNumber}</li>";
			if( $this->iRetryCurrentNumber < $this->iRetryMaxNumber ){
				$this->iRetryCurrentNumber++ ;
				sleep(1);
				return $this->event_socket_create() ;
			}else{
				die("【event_socket_create】Connection to $this->host failed");
			}
		}
		echo "<li>【event_socket_create】{$this->host} 连接成功 ****</li>";
		$this->iRetryCurrentNumber = 0 ;
		socket_set_blocking($this->fp,false);
		
		if ($this->fp) {
			while (!feof($this->fp)) {
				$buffer = fgets($this->fp,10240);
				usleep(50); //allow time for reponse
				if (trim($buffer) == "Content-Type: auth/request") {
					echo "<li>【event_socket_create】$buffer >>> send auth >>></li>";
					fputs($this->fp, "auth $this->password\n\n");
					break;
				}else 
					echo "<li>【event_socket_create】".urldecode($buffer)."</li>";
			}
			return $this->fp;
			
		}else {
			return false;
		}
	}
	
	public function event_socket_close() {
		$this->fp->close();
	}
	
	
	public function event_socket_request( $cmd ) {
		
		if( is_null( $this->fp )  ){
			$this->event_socket_create();
		}
		
		if ($this->fp) {
			echo "<ul>【event_socket_request】$cmd ";
			fputs($this->fp, $cmd."\n\n");
			usleep(100); //allow time for response
			
			$response = '';
			$length = 0;
			$x = 0;
			while (!feof($this->fp) )
			{
				$x++;
				usleep(100);
				$theNewData = stream_get_line($this->fp, 10240, "\n");
				if ($theNewData){
					$response = urldecode(trim($theNewData));
					echo "<li>【event_socket_request $x 】$response</li>";
						$response = $this->getJsonReponseClean( $response ) ;
						if(is_array($response)){
							if (!empty($response['result'])){
								
							}
						}
				}
				
				if ($x > $this->limit){
					echo "<li>【event_socket_request $x 】 达到限制计数 停止！！ </li>";
					break;
				}
			}
			$this->fp = null;

			echo "</ul>";
		}else {
			echo "<li>【event_socket_request】no handle</li>";
		}
	}
	
	private function getJsonReponseClean( $response ){		
		$start = strpos($response,"{");
		if ($start === false)
			return false;
		$end = strrpos($response,"}");
		if ($end === false)
			return false;
		$response = substr($response, $start, $end+1 );
		try {
			$response = json_decode($response, true ) ;
		} catch (Exception $e) {
			return false ;
		}
		if( is_array( $response ) ){
			return $response ;
		}
		return false ;
	}
	
}

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="css/main.css"/><script type="text/javascript" src="css/jquery.js"></script>
</head><body>
HTML;
flush();
// The command to send to FreeSWITCH
if (!empty($_GET['cmd']))
	$cmd = $_GET['cmd'];
else
	$cmd = "event plain ALL";
if (!empty($_SESSION['ESL_HOST'])){
	define("ESL_HOST", $_SESSION['ESL_HOST']);
	define("ESL_PORT", $_SESSION['ESL_PORT']);
	define("ESL_PASSWORD",$_SESSION['ESL_PASSWORD']);
	$myFSEventListener = new FreeSwitchEventListener() ;
	$myFSEventListener->setHost(ESL_HOST) ;
	$myFSEventListener->setPort(ESL_PORT);
	$myFSEventListener->setPassword(ESL_PASSWORD);
	$myFSEventListener->event_socket_request($cmd);
}else 
	echo "<p class=pcenter>请使用 <a href='esl_cmd.php'>ESL 命令工具</a></p> ";

echo "</body></html";