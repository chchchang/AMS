<?php
	header("X-Frame-Options: SAMEORIGIN");
	header('Content-Type: text/html; charset=utf-8');
	date_default_timezone_set("Asia/Taipei");
	require_once dirname(__FILE__).'/../MyDB.php';
	require_once dirname(__FILE__).'/../MyLogger.php';
	
	if(isset($_SERVER["HTTP_REFERER"])){
		$checkRefer = false;
		foreach(Config::$SERVER_SITES as $server){
			if(strrpos($_SERVER["HTTP_REFERER"],$server.Config::PROJECT_ROOT , -strlen($_SERVER["HTTP_REFERER"])) !== FALSE){
				$checkRefer = true;
				$SERVER_SITE=$server;
				break;
			}
		}
		if(!$checkRefer){
			header('Content-Type: text/html; charset=utf-8');
			exit('非法操作!');
		}
	}
	
	$logger=new MyLogger();
	
	session_start();
	
	if(!isset($_SESSION['AMS']['ID'])){
		$logger->warn('未經登入存取此AJAX服務('.$_SERVER['REQUEST_URI'].')');
		header('Content-Type: application/json; charset=utf-8');
		exit(json_encode('未經登入存取此AJAX服務！',JSON_UNESCAPED_UNICODE));
	}
	$my=new MyDB(true);
?>