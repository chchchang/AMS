<?php
	header("X-Frame-Options: SAMEORIGIN");
	header('Content-Type: text/html; charset=utf-8');
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
		if(!$checkRefer)
			exit('非法操作!');
	}else{
		$checkRefer = false;
		foreach(Config::$SERVER_SITES as $server){
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			$url =$protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			if(strrpos($url,$server.Config::PROJECT_ROOT , -strlen($url)) !== FALSE){
				$checkRefer = true;
				$SERVER_SITE=$server;
				break;
			}
		}
		if(!$checkRefer)
			exit('非法操作!');
	}
	
	$logger=new MyLogger();
	
	session_start();
	
	if(!isset($_SESSION['AMS']['ID'])) {
		$logger->warn('未經登入存取此頁面('.$_SERVER['REQUEST_URI'].')');
		if(strpos($_SERVER['REQUEST_URI'],'/AMS/predict/')!==false)
			exit('<script>location.replace("'.Config::PROJECT_ROOT.'predict/login.php");</script>');
		else
			exit('<script>location.replace("'.Config::PROJECT_ROOT.'login.php");</script>');
	}
	else {
		$my=new MyDB(true);
		$sql='
			SELECT 頁面.頁面路徑 FROM 權限
			INNER JOIN 使用者 ON 權限.使用者識別碼=使用者.使用者識別碼
			INNER JOIN 頁面 ON 權限.頁面識別碼=頁面.頁面識別碼
			WHERE 權限.使用者識別碼=? AND 頁面.頁面路徑=?
		';
		$result=$my->getResultArray($sql,'is',$_SESSION['AMS']['使用者識別碼'],$_SERVER['SCRIPT_NAME']);
		if($result===false)
			exit('取得使用權限資料過程中發生錯誤！');
		else if(count($result)===0)
			exit('沒有權限使用此頁面或功能！');
	}
?>