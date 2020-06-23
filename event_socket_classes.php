<?php
class FS_Socket
{
	private $Logger = NULL;
	/**
	 * @var int $timeOut
	 * @desc The timeout used to open the socket
	 */
	private $timeOut = 10;
	/**
	 * @var resource $connection
	 * @desc Connection resource
	 */
	private $connection = NULL;
	/**
	 * @var string $connectionState
	 * @desc
	 */
	private $connectionState = FALSE;
	/**
	 * @var float $defaultTimeout
	 * @desc Default timeout for connection to a server
	 */
	private $defaultTimeout = 30;
	/**
	 * @var bool $persistentConnection
	 * @desc Determines wether to use a persistent socket connection or not
	 */
	private $persistentConnection = FALSE;
	/**
	 * If there still was a connection alive, disconnect it
	 */
	public function __destruct()
	{
		$this->disconnect();
	}
	/**
	 * Connects to the socket with the given address and port
	 * @return void
	 */
	protected function connect($host, $port, $options = array())
	{
		// initialize our defaults
		if (!empty($options['persistentConnection']))
			$this->persistentConnection = $options['persistentConnection'];
		if (!empty($options['timeOut']))
			$this->timeOut = $options['timeOut'];
		if (!empty($options['defaultTimeout']))
				$this->defaultTimeout = $options['defaultTimeout'];
		if (!empty($options['blocking']))
			$blocking = $options['blocking'];
		else 
			$blocking = 1;

		if (!class_exists("Logger")){
			include_once 'Logger.php';
		}
		$this->Logger = new Logger( __DIR__.'/logs', LogLevel::DEBUG, array (
				'extension' => 'log', //扩展名
				'prefix' => 'ESL_',
				'flushFrequency' => 5 //缓冲写日志的行数
		));

		// decided the stream type
		$socketFunction = $this->persistentConnection ? "pfsockopen" : "fsockopen";
		// Check if the function parameters are set.
		if(empty($host))
		{
			$this->_throwError("主机地址为空 invalid host provided!");
		}
		if(empty($port))
		{
			$this->_throwError("端口为空 invalid port provided!");
		}
		// attempt to open a socket
		$errorNumber = $errorString = null;
		$connection = @$socketFunction($host, $port, $errorNumber, $errorString, $this->timeOut);
		$this->connection = $connection;
		// if we didnt get a valid socket throw an error
		if($connection == false)
		{
			$this->Logger->error("连接失败 connection to {$host}:{$port} failed: {$errorNumber}");
			$this->_throwError("连接失败 connection to {$host}:{$port} failed: {$errorNumber}");
		}else 
			$this->Logger->notice("连接成功 $socketFunction {$host}:{$port}  ");
		// initialize our stream blocking setting
		stream_set_blocking($this->connection, $blocking);
		// set this stream as connected
		$this->connectionState = TRUE;
	}
	/**
	 * Disconnects from the server
	 * @return bool
	 */
	protected function disconnect()
	{
		if($this->validateConnection())
		{
			fclose($this->connection);
			$this->connectionState = FALSE;
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * Sends a command to the server
	 * @return string
	 */
	protected function sendCmd($command)
	{
		if($this->validateConnection())
		{
			$command = trim($command);
			$this->Logger->notice('ESL Command "' .$command .'"');
			$command .= "\n\n";
			$result = fwrite($this->connection, $command, strlen($command));
			return $result;
		}
		$this->_throwError("发送命令失败 sending command \"{$command}\" failed: Not connected");
	}
	/**
	 * Gets the content/body of the response
	 * @return string
	 */
	protected function getContent($contentLength = 2048)
	{
		if($this->validateConnection())
		{
			$a = "";
			$b = 1;
			do{
				$b++;
				if ($b>1000) //防止死循环
					break;
				$a .= urldecode(fgets($this->connection,2048));
			}while (strlen($a)<$contentLength);
			return $a;
		}
		$this->_throwError("接收反馈信息失败 receiving content from server failed: Not connected");
	}
	/**
	 * Reads a line out of buffer
	 * @return string
	 */
	protected function readLine()
	{
		if($this->validateConnection()) {
			return fgets($this->connection);
		}
		$this->_throwError("read line failed: Not connected");
	}
	/**
	 * Sets the socket to blocking operations
	 * @return bool
	 */
	protected function setBlocking() {
		if($this->validateConnection()) {
			return stream_set_blocking($this->connection, 1);
		}
		$this->_throwError("set stream to blocking failed: Not connected");
	}
	/**
	 * Sets the socket to non-blocking operations
	 * @return bool
	 */
	protected function setNonBlocking() {
		if($this->validateConnection()) {
			return stream_set_blocking($this->connection, 0);
		}
		$this->_throwError("set stream to non-blocking failed: Not connected");
	}
	/**
	 *  Sets the timeout for this socket to an arbitrary value
	 * @return bool
	 */
	protected function setTimeOut($seconds = 0, $milliseconds = 0) {
		if($this->validateConnection()) {
			return stream_set_timeout ($this->connection, (int)$seconds, (int)$milliseconds);
		}
		$this->_throwError("set stream timeout failed: Not connected");
	}
	/**
	 * Restores the time out for this socket to the value it was opened with
	 * @return bool
	 */
	protected function restoreTimeOut() {
		if($this->validateConnection()) {
			return stream_set_timeout ($this->connection, $this->timeOut, 0);
		}
		$this->_throwError("restore stream timeout failed: Not connected");
	}
	/**
	 * Gets the meta data on this socket
	 * @return string
	 */
	protected function getMetaData() {
		if($this->validateConnection()) {
			return stream_get_meta_data($this->connection);
		}
		$this->_throwError("get stream meta data failed: Not connected");
	}
	/**
	 * Gets the socket status
	 * @return string
	 */
	protected function getStatus() {
		if($this->validateConnection())
		{
			return socket_get_status($this->connection);
		}
		$this->_throwError("getting socket descriptor failed: Not connected");
	}
	/**
	 * Validates the connection state
	 * @return bool
	 */
	protected  function validateConnection()
	{
		return (is_resource($this->connection) && ($this->connectionState != FALSE));
	}
	/**
	 * Throws an error
	 * @return void
	 */
	private function _throwError($errorMessage)
	{
		die("Socket {$errorMessage}");
	}
}

class ESLconnection extends FS_Socket {
	private $eventQueue = array();
	private $sentCommand = FALSE;
	private $authenticated = FALSE;
	private $eventLock = FALSE;
	private $asyncExecute = FALSE;

	public function __construct($host = NULL, $port = NULL, $auth = NULL, $options = array()) {
		try {
			// attempt to open the socket
			$this->connect($host, $port, $options);
			// get the initial header
			$event = $this->recvEvent();
			if (!$event)
				$this->_throwError(" 未正确连接服务器！");
			// did we get the request for auth?
			if ($event->getHeader('Content-Type') !=  'auth/request') {
				$this->_throwError("验证时发现非预期的信息： Type: " . $event->getType().' info: '. $event->getBody());
			}
			// send our auth
			$event = $this->sendRecv("auth {$auth}");
			// was our auth accepted?
			$reply = $event->getHeader('Reply-Text');
			if (!strstr($reply, '+OK')) {
				$this->_throwError("连接时被拒绝： {$reply}");
			}
			// we are authenticated!
			$this->authenticated = TRUE;
			return TRUE;
		} catch (Exception $e) {
			return FALSE;
		}
	}
	
	public function __destruct() {
		// cleanly exit
		$this->disconnect();
	}
	
	/**  * 返回本连接的状态 */
	public function socketDescriptor() {
		try {
			return $this->getStatus();
		} catch (Exception $e) {
			return FALSE;
		}
	}
	
	/**  测试是否还在连接中，这不仅仅是指socket连接而且还需通过了身份验证；1 connected, 0 otherwise.	 */
	public function connected() {
		if ($this->validateConnection() && $this->authenticated) {
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * 发送命令，不处理反馈（后面需要循环 recvEvent 或 recvEventTimed 获取反馈）
	 * 反馈信息需有信息头"content-type"，内容为"api/response" 或 "command/reply"；如果需同时处理反馈，使用 sendRecv() 
	 */
	public function send($command) {
		if (empty($command)) {
			$this->_throwError("requires non-blank command to send.");
		}
		// send the command out of the socket
		try {
			return $this->sendCmd($command);
		} catch (Exception $e) {
			return FALSE;
		}
	}
	/**
	 * 发送命令并获取反馈，即send($command)，而后 recvEvent()，返回 ESLevent对象
	 * recvEvent() 会循环进行，直到获取到信息头"content-type"，内容为"api/response" 或 "command/reply"；过程中的反馈均被存入队列，返回的是最后一个
	 * 当start_listevent时，是死循环以获取EVENT订阅信息，检测 $_SESSION['start_listevent'] 变量（为真）时退出
	 */
	public function sendRecv($command) {
		// setup an array of content-types to wait for
		$waitFor = array('api/response', 'command/reply'); //text/event-plain text/event-json 是事件内容,这里等待的是命令的反馈
	
		// set a flag so recvEvent ignores the event queue
		$this->sentCommand = TRUE;
		// send the command
		$this->send($command);
		// collect and queue all the events
		do {
				$event = $this->recvEvent();
				$this->eventQueue[] = $event;
		} while (!in_array($event->getHeader('Content-Type'), $waitFor));
		// clear the flag so recvEvent uses the event queue
		$this->sentCommand = FALSE;
		
		// the last queued event was of the content-type we where waiting for,
		// so pop one off
		return array_pop($this->eventQueue);
	}
	/** 发送api命令，并获取反馈，等同于sendRecv("api $command $args"). */
	public function api() {
		$args = func_get_args();
		$command = array_shift($args);
		$command = 'api ' .$command .' ' .implode(' ', $args);
		return $this->sendRecv($command);
	}
	/** 发送event命令，并获取反馈，等同于sendRecv("event $command $args"). */
	public function event() {
		$args = func_get_args();
		$command = array_shift($args);
		$command = 'event ' .$command .' ' .implode(' ', $args);
		return $this->sendRecv($command);
	}
	/** 发送nixevent命令，并获取反馈，等同于sendRecv("nixevent $command $args"). */
	public function nixevent() {
		$args = func_get_args();
		$command = array_shift($args);
		$command = 'nixevent ' .$command .' ' .implode(' ', $args);
		return $this->sendRecv($command);
	}
	/**  发送bgapi命令，并获取反馈，这是后台api命令，非阻塞模式，等同于sendRecv("bgapi $command $args")	 */
	public function bgapi() {
		$args = func_get_args();
		$command = array_shift($args);
		$command = 'bgapi ' .$command .' ' .implode(' ', $args);
		return $this->sendRecv($command);
	}
	/**  返回event信息；如果没有event过来，系统将一直等待。如果任意event被队列，第一个信息被返回 */
	public function recvEvent() {
		// if we are not waiting for an event and the event queue is not empty
		// shift one off
		if (!$this->sentCommand && !empty($this->eventQueue)) {
			return array_shift($this->eventQueue);
		}
		// wait for the first line
		$this->setBlocking();
		do {
			$line = $this->readLine();
			// if we timeout while waiting return NULL
			$streamMeta = $this->getMetaData();
			if (!empty($streamMeta['timed_out'])) {
				return NULL;
			}
		} while (empty($line));
		// save our first line
		$response = array($line);
		// keep reading the buffer untill we get a new line
		$this->setNonBlocking();
		do {
			$line = $response[] = $this->readLine();
		} while ($line != "\n");
		// build a new event from our response
		$event = new ESLevent($response);
		// if the response contains a content-length ...
		$contentLen = $event->getHeader('Content-Length');
		if ($contentLen) {
			$this->setBlocking();
			$event->addBody($this->getContent($contentLen));
		}
		$contentType = $event->getHeader('Content-Type');
		if ($contentType == 'text/disconnect-notice') {
			$this->disconnect();
			return FALSE;
		}
		// return our ESLevent object
		return $event;
	}
	
	/**  扩展recvEvent()，设置读取数据超时的毫秒数；用于轮询events	 */
	public function recvEventTimed($milliseconds) {
		// set the stream timeout to the users preference
		$this->setTimeOut(0, $milliseconds);
		// try to get an event
		$event = $this->recvEvent();
		// restore the stream time out
		$this->restoreTimeOut();
		// return the results (null or event object)
		return $event;
	}
	/** 设定event监听的类型：发送filter命令，指定相应的信息头和值  */
	public function filter($header, $value) {
		return $this->sendRecv('filter ' .$header .' ' .$value);
	}

	/**
	 * 执行 dialplan 应用，并等待反馈；没有channel时，3个参数都必须，以$uuid 指定channel；
	 * 返回 ESLevent 对象，getHeader("Reply-Text") 方法返回响应信息；"+OK [Success Message]" 或 "-ERR [Error Message]"
	 */
	public function execute($app, $arg, $uuid) {
		$command = 'sendmsg';
		if (!empty($uuid)) {
			$command .= " {$uuid}";
		}
		$command .= "\ncall-command: execute\n";
		if (!empty($app)) {
			$command .= "execute-app-name: {$app}\n";
		}
		if (!empty($arg)) {
			$command .= "execute-app-arg: {$arg}\n";
		}
		if ($this->eventLock) {
			$command .= "event-lock: true\n";
		}
		if ($this->asyncExecute) {
			$command .= "async: true\n";
		}
		return $this->sendRecv($command);
	}
	/**  等同于execute，但不等待服务器的响应，即execute() 加 "async: true"头  **/
	public function executeAsync($app, $arg, $uuid) {
		$currentAsync = $this->asyncExecute;
		$this->asyncExecute = TRUE;
		$response = $this->execute($app, $arg, $uuid);
		$this->asyncExecute = $currentAsync;
		return $response;
	}
	/** 设置execute的async状态 **/
	public function setAsyncExecute($value = NULL) {
		$this->asyncExecute = !empty($value);
		return TRUE;
	}
	/**	 * 设置eventLock状态，本命令在通话没有设置 "async" 时无效；1 强制sync模式，0 不强制	 */
	public function setEventLock($value = NULL) {
		$this->eventLock = !empty($value);
		return TRUE;
	}

	public function disconnect() {
		// if we are connected cleanly exit
		if ($this->connected()) {
			$this->send('exit');
			$this->authenticated = FALSE;
		}
		// disconnect the socket
		return parent::disconnect();
	}

	private function _throwError($errorMessage)
	{
		die("ESL {$errorMessage}");
	}
}
class ESLevent {
	private $headers = array('Event-Name' => 'COMMAND');
	private $body = NULL;
	private $hdrPointer = NULL;
	public function __construct($event) {
		if (!is_array($event)) {
			$this->addHeader('Event-Name', $event);
			return $this;
		}
		foreach ($event as $line) {
			if ($line == "\n") {
				continue;
			} else if (strstr($line, ':')) {
				list($key, $value) = explode(':', $line);
				$this->addHeader($key, $value);
			} else {
				$this->addBody($line);
			}
		}
		$this->_convertPlainEvent();
	}
	/**
	 * Turns an event into colon-separated 'name: value' pairs similar to a
	 * sip/email packet (the way it looks on '/events plain all').
	 */
	public function serialize($format = NULL) {
		$contentType = $this->getHeader('Content-Type');
		$reply = '';
		foreach ($this->headers as $key => $value) {
			if ($contentType == 'text/event-plain') {
				if ($key == 'Content-Type' || $key == 'Content-Length') {
					continue;
				}
			}
			$reply .= $key .': ' .$value ."\n";
		}
		if (!empty($this->body)) {
			$reply .= 'Content-Length: ' .strlen($this->body) ."\n\n";
			$reply .= $this->body;
		}
		return $reply;
	}
	/**
	 * Sets the priority of an event to $number in case it's fired.
	 */
	public function setPriority($number = 0) {
		switch ($number) {
			case 0:
				$priority = 'NORMAL';
				break;
			case -1:
				$priority = 'LOW';
				break;
			case 1:
				$priority = 'HIGH';
				break;
			default:
				$priority = 'NORMAL';
		}
		$this->addHeader('priority', $priority);
	}
	/**
	 * 从 event object 获得头信息，按 $header_name 定义的头部标签；如果$header_name为false，返回所有头标签
	 */
	public function getHeader($header_name) {
		if (isset($this->headers[$header_name])){
			return $this->headers[$header_name];
		}
		if (!$header_name){
			$keys = array_keys($this->headers);
			return implode(",", $keys);
		}
		return NULL;
	}
	/**
	 * Gets the body of an event object.
	 */
	public function getBody() {
		return $this->body;
	}
	/**
	 * Gets the event type of an event object.
	 */
	public function getType() {
		$eventName = $this->getHeader('Event-Name');
		if (!empty($eventName)) {
			return $eventName;
		} else {
			return 'COMMAND';
		}
	}
	/**
	 * Add $value to the body of an event object. This can be called multiple
	 * times for the same event object.
	 */
	public function addBody($value) {
		if (is_null($this->body)) {
			$this->body = $value;
		} else {
			$this->body .= $value;
		}
		$this->_convertPlainEvent();
	}
	/**
	 * Add a header with key = $header_name and value = $value to an event
	 * object. This can be called multiple times for the same event object.
	 */
	public function addHeader($header_name, $value) {
		$this->headers[trim($header_name, " \r\n")] = trim($value, " \r\n");
	}
	/**
	 * Delete the header with key $header_name from an event object.
	 */
	public function delHeader($header_name) {
		if (isset($this->headers[$header_name])){
			unset($this->headers[$header_name]);
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * Sets the pointer to the first header in an event object, and returns
	 * it's key name. This must be called before nextHeader is called.
	 */
	public function firstHeader() {
		$this->hdrPointer = array_keys($this->headers);
		return $this->nextHeader();
	}
	/**
	 * Moves the pointer to the next header in an event object, and returns
	 * it's key name. firstHeader must be called before this method to set the
	 * pointer. If you're already on the last header when this method is called,
	 * then it will return NULL.
	 */
	public function nextHeader() {
		if (is_array($this->hdrPointer)) {
			return array_shift($this->hdrPointer);
		} else {
			return NULL;
		}
	}
	private function _convertPlainEvent() {
		$contentType = $this->getHeader('Content-Type');
		// if the content-type is a plain-event
		if ($contentType == 'text/event-plain') {
			// if there is nothing in the body we are good to go
			if (empty($this->body)) return;
			// if there are contents in the body remove them....
			$body = $this->body;
			$this->body = NULL;
			// ...then convert them into headers
			$body = explode("\n", $body);
			foreach ((array)$body as $line) {
				if ($line == "\n") {
					continue;
				} else if (strstr($line, ':')) {
					list($key, $value) = explode(':', $line);
					$this->addHeader($key, $value);
				} else {
					$this->addBody($line);
				}
			}
		}
	}
}
class event_socket
{
	private $esl = NULL;
	private $extension = FALSE;
	protected $UUID;
	static private $instance;
	/**
	 * Initialize the ESL connection.  This must be called first
	 * @access public
	 * @static
	 * @return bool
	 */
	public function __construct($host='',$port='',$password='',$options=array())
	{
		if (defined('ESL_HOST')) {
			$host = ESL_HOST;
		}elseif (empty($host))
			$host = '127.0.0.1'; 
		if (defined('ESL_PORT')) {
			$port = ESL_PORT;
		}elseif (empty($port))
			$port = '8021';
		if (defined('ESL_PASSWORD')) {
			$password = ESL_PASSWORD;
		}elseif (empty($password))
			$password = 'ClueCon'; 
		$this->esl = new ESLconnection($host, $port, $password, $options); // socket connection
	}
	public static function getInstance()
	{
		if (!self::$instance)
		{
			return self::$instance = new event_socket();
		}
		return self::$instance;
	}
	public static function eventReloadXML()
	{
		self::getInstance()->reloadxml();
	}
	public static function eventReloadACL()
	{
		self::getInstance()->reloadacl();
	}
	public static function eventReloadSofia()
	{
		self::getInstance()->reload('mod_sofia');
	}
	
	/*
	 * Clean up connection when script is done executing.
	 * If connected, try to disconnect.
	 */
	public function __destruct()
	{
		if ($this->isConnected()) {
			return $this->esl->disconnect();
		}
	}
	/**
	 * Returns the connection status of the current connection
	 */
	public function isConnected()
	{
		if(!is_object($this->esl)) {
			return FALSE;
		} else {
			return $this->esl->connected();
		}
	}
	/**
	 * gets the raw ESLconnection
	 */
	public function getESL() {
		return $this->esl;
	}
	/**
	 * check if a command execution response was successfull
	 */
	public function isSuccessfull($event = NULL) {
		if ($event instanceof ESLevent) {
			$reply = $event->getHeader('Reply-Text');
			if (strstr($reply, '+OK')) {
				return TRUE;
			}
		}
		return FALSE;
	}
	/**
	 * This will return a string froma event with the most appropriate
	 * meaning
	 */
	public function getResponse($event = NULL) {
		if ($event instanceof ESLevent) {
			
			$body = $event->getBody();
			if (!empty($body)) return $body;
			$reply = $event->getHeader('Reply-Text');
			if (!empty($reply)) return $reply;
			return $event->serialize();
		}
		
		if (!is_string($event)) {
			return '未得到合理的命令反馈！';
		} else {
			return $event;
		}
	}
	/**
	 * Convience wrapper for nat operations
	 */
	public function nat($operation)
	{
		if (!$this->isConnected()) return FALSE;
		$esl = $this->esl;
		switch($operation)
		{
			case 'status':
				return $esl->api('nat_map',' status');
				break;
			case 'reinit':
				return $esl->api('nat_map',' reinit');
				break;
			case 'republish':
				return $esl->api('nat_map',' republish');
				break;
			default:
				return "NAT operation $operation not valid";
		}
	}
	
	public function get_UUID($renew = 0,$byFS=0){ 
		if (empty($this->UUID) || $renew)
			if (!$byFS){ //多线程环境uuid会重复！
				 $char = md5(uniqid(mt_rand(), true));
				 $this->UUID  = substr($chars,0,8) . '-';
				 $this->UUID .= substr($chars,8,4) . '-';
				 $this->UUID .= substr($chars,12,4) . '-';
				 $this->UUID .= substr($chars,16,4) . '-';
				 $this->UUID .= substr($chars,20,12);
		}else
			$this->UUID = $this->esl->bgapi("create_uuid")->getHeader("Job-UUID");
		if ($this->UUID)
			return $this->UUID;
		else
			return false;
	}
	
	/**
	 * originate {Avar}Auri &bridge({Bvar}Buri) 完全按自己设定参数进行拨号呼叫
	 * 可以自由实现各种呼叫，主要针对特别情况，典型的如链接两个电话：
	 * originate sofia/gateway/gatewayName/tel1 &bridge(sofia/gateway/gatewayName/tel2) 
	 * $Avar A腿通道变量设置 
	 * $Auri A腿链接 
	 * $Bvar B腿通道变量设置 
	 * $Buri B腿链接 
	 * $bridge 是否使用bridge桥接应用来转接B腿，默认不使用
	 */
	public function originate($Avar='',$Auri='',$Bvar = '',$Buri='',$bridge = 0)
	{
		if ($Avar)
			$Avar ="{".$Avar."}";
		if ($Bvar)
			$Bvar ="{".$Bvar."}";
		if (empty($Auri) && empty($Buri))
			return false;
		elseif($bridge)
			return $this->esl->bgapi("originate",	" $Avar$Auri "." &bridge("." $Bvar$Buri )");
		else
			return $this->esl->bgapi("originate",	" $Avar$Auri "." $Bvar$Buri ");
	}
	
	/**
	 * originate {origination_caller_id_number=$from_caller_id,origination_uuid=$useUUID,ignore_early_media=true,originate_timeout=30,hangup_after_bridge=false,continue_on_fail=true,execute_on_answer='sched_hangup +1000 alloted_timeout'}sofia/gateway/$gateway_name/$to_callee_id  &endless_playback('$sound')
	 * execute_on_answer='sched_hangup +1000 alloted_timeout 是最长通话时间1000s
	 * 通过指定路由呼叫指定的号码，接通后循环播放指定的声音（）
	 */
	public function originatePlay($gateway_name,$to_callee_id,$sound,$from_caller_id = '',$useUUID='')
	{
		if (empty($gateway_name) || empty($to_callee_id))
			return false;
		if (empty($sound))
			$sound = __DIR__."/bell_ring2.wav";
		$from = $uuid = "";
		if ($from_caller_id)
			$from ="origination_caller_id_number=$from_caller_id,";
		if ($useUUID)
			$uuid = "origination_uuid=$useUUID,";
		return $this->esl->bgapi("originate",	"{".$from.$useUUID."ignore_early_media=true,originate_timeout=30,hangup_after_bridge=false,continue_on_fail=true,execute_on_answer='sched_hangup +1000 alloted_timeout'}sofia/gateway/$gateway_name/$to_callee_id  &endless_playback('$sound')");
	}
	
	/**
	 * uuid_transfer $uuid $both $dest
	 * 通过uuid 转接通话，如果both ==dual ，就使用uuid_dual_transfer命令，默认是uuid_transfer
	 * dest是目标地址
	 * uuid_transfer <uuid> [-bleg|-both] <dest-exten> [<dialplan>] [<context>]
	 * uuid_dual_transfer <uuid> <A-dest-exten>[/<A-dialplan>][/<A-context>] <B-dest-exten>[/<B-dialplan>][/<B-context>]
	 */
	public function uuid_transfer($uuid,$dest,$both=''){
		if (empty($uuid) || empty($dest))
			return false;
		if($both =='dual')
			return $this->esl->bgapi("uuid_dual_transfer","$uuid $dest");
		elseif(!($both =="both" || $both == "bleg"))
			$both = "";
		else 
			$both = "-$both";
		return $this->esl->bgapi("uuid_transfer","$uuid $both $dest");
	}
	
	public function __call($name, $arguments) {
		if (!$this->isConnected()) return FALSE;
		
		$esl = $this->esl;
		// These are some convience wrappers to support common commands
		switch(strtolower($name)) {
			case 'version':
				return $esl->api('version');
				break;
			case 'status':
				return $esl->api('status');
				break;
			case 'reloadacl':
				return $esl->api('reloadacl');
				break;
			case 'reloadxml':
				return $esl->api('reloadxml');
				break;
			case 'reload':
				array_unshift($arguments, 'bgapi', 'reload', '-f');
				return  $esl->sendRecv(implode(' ', $arguments));
				break;
			case 'apireload':
				array_unshift($arguments, 'api', 'reload');
				return  $esl->sendRecv(implode(' ', $arguments));
				break;
			case 'sofia':
				array_unshift($arguments, 'sofia');
				return call_user_func(array($esl, 'api'), implode(' ', $arguments));
				break;
			case 'show':
				array_unshift($arguments, 'show');
				return call_user_func(array($esl, 'api'), implode(' ', $arguments));
				break;
			case 'channels':
				return $esl->api('show', 'channels');
				break;
			case 'calls':
				return $esl->api('show', 'calls');
				break;
			case 'global_getvar':
				return $esl->api('global_getvar');
				break;
			case 'restart':
				return $esl->api('fsctl','shutdown','restart','elegant');
				break;
			case 'execute':
			case 'executeAsync':
				$arguments = explode(" ",$arguments[0]);
				if (count($arguments)<3)
					return "必须三个参数：app args uuid";
				return $esl->$name($arguments[0],$arguments[1],$arguments[2]);
				break;
			default:
				if (!method_exists($this->esl, $name)) return FALSE;
				return call_user_func(array($this->esl, $name), implode(' ', $arguments));
		}
	}
	
	public function __clone()
	{
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}
}