<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	require_once '../Config.php';
	$logger=new MyLogger();
	$my=new MyDB(true);
	$apiurl = Config::GET_API_SERVER_852_VOD_AD()."mod/ads/api/service";
	
	$ptid = creatPT();
	creatPTConfig($ptid);
	creatPTMaterial($ptid);
	$pdata = getPData($apiurl);
	createP($pdata);
	
	//建立版位類型
	function creatPT(){
		global $logger, $my;
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = "Vod插廣告"';
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位類型'.'<br>';
			return $result[0]['版位識別碼'];
		}
		
		$sql='INSERT INTO 版位 (版位名稱,CREATED_PEOPLE) VALUES ("Vod插廣告",1)';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$ptid = $stmt->insert_id;
		echo '版位類型建立完成，ID'.$ptid.'<br>';
		return $ptid;
	}
	//設定版位其他參數
	function creatPTConfig($ptid){
		global $logger, $my;
		$sql='DELETE FROM 版位其他參數 WHERE 版位識別碼=?';
		
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->bind_param('i',$ptid)) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
		.' VALUES'
		.'	('.$ptid.',1,"ext","ext",2,1,1,1,1)'
		.',  ('.$ptid.',2,"pre","pre",3,2,1,1,1)'
		.',  ('.$ptid.',3,"likes","likes",3,1,0,0,1)'
		.',  ('.$ptid.',4,"全體投放次數上限","up",2,1,0,null,1)'
		.',  ('.$ptid.',5,"用戶投放次數上限","down",2,1,0,null,1)'
		.',  ('.$ptid.',6,"排序","sort",1,1,0,null,1)'
		;
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$ptid = $stmt->insert_id;
		echo '版位其他參數建立完成<br>';
		return 1;
	}
	
	//設定版位素材類型
	function creatPTMaterial($ptid){
		global $logger, $my;
		$sql='SELECT 版位識別碼 FROM 版位素材類型 WHERE 版位識別碼 = '.$ptid;
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位類型素材<br>';
			return 0;
		}
				
		$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,顯示名稱,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)'
		.' VALUES ('.$ptid.',1,"SD影片",3,0,1,1)'
		.',  ('.$ptid.',2,"HD影片",3,0,2,1)'
		;
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$ptid = $stmt->insert_id;
		echo '版位類型素材建立完成<br>';
		return 1;
	}
	
	//從API取得板位參數
	function getPData($url){
		$postvars = http_build_query(array());
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($apiUrl,'POST',$postvars)){
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSMAPI'),JSON_UNESCAPED_UNICODE));	
		}
		$PDatas = json_decode($apiResult,true);
		if($PDatas["code"]!=200){
			exit("API取得資料失敗:".$apiResult);	
		}
		return $PDatas["service"];
	}
	
	//建立板位
	function createP($pdata){
		global $ptid;
		$pid = "";
		foreach($pdata as $row){
			if($row["makr"]=="T")
			createP_sub($row);
		}
	}
	
	//建立版位
	function createP_sub($row){
		global $ptid;
		$pName = "插廣告_".$row["name"];
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = "'.$pName.'" AND 上層版位識別碼 = '.$ptid;
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位:'.$pName.'<br>';
			$pid = $result[0]["版位識別碼"];
		}
		$sql='INSERT INTO 版位 (版位名稱,上層版位識別碼,CREATED_PEOPLE) VALUES ("'.$pName.'",'.$ptid.',1)';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$pid = $stmt->insert_id;
		echo '版位'.$pName.'建立完成，ID'.$pid.'<br>';
		
		//建立板位參數
		echo "開始建立版位參數<br>";
		$sql='DELETE FROM 版位其他參數 WHERE 版位識別碼=?';
		
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->bind_param('i',$pid)) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
		.' VALUES'
		.'	('.$pid.',1,"ext","ext",2,1,1,"'.$row["ext"].'",1)'
		;
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		echo "版位參數建立完成<br>";
	}
?>