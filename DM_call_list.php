<?php
set_time_limit(600);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
if (empty($_SESSION['domainid']))
	die("没有登录！请先登录系统！");
include_once 'DM_db.php';
require 'redis_calllist_inc.php';