<?php
	date_default_timezone_set("Asia/Taipei");
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/../MyDB.php';
	require_once dirname(__FILE__).'/../MyLogger.php';
	$logger=new MyLogger();
	session_start();
	$my=new MyDB(true);
	if(!isset($_SESSION['AMS']['ID']))
		exit('<script>location.replace("'.Config::PROJECT_ROOT.'login.php");</script>');
?>