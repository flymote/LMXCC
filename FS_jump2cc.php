<?php
session_start();
if (!isset($_SESSION['FSlmxusers']) || empty($_SESSION['ESL_HOST']))
	die("NEED LOGIN !!");
if (!empty($_GET['domainid'])){
	$_SESSION['user_id_lmxcc'] = 0;
	$_SESSION['lmxccusers'] = "SYS";
	$_SESSION['domainid'] = $_GET['domainid'];
	$_SESSION['user_is_ADMIN_'.APPID] = 9;
	header("Location:index.php");
}else 
	die('error!');