<?php
ini_set('display_errors','1');
error_reporting(E_ALL);
	header("Content-Type:text/html; charset=utf-8");
	require_once 'tool/MyDB.php';
	require 'tool/OutputExcel.php';
	$logger=new MyLogger();
	$my=new MyDB(true);
	//$url='localhost/AMS/test.php';
	$url='http://172.17.251.130:8080/barker/channel?orbit_only=1';
	$ptid = creatBarkerPT();
	creatBarkerPTConfig($ptid);
	creatBarkerPTMaterial($ptid);
	getPData($ptid);
	//建立BARKER頻道版位
	function creatBarkerPT(){
		global $logger, $my;
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = "barker頻道"';
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位類型'.'<br>';
			return $result[0]['版位識別碼'];
		}
		
		$sql='INSERT INTO 版位 (版位名稱,CREATED_PEOPLE) VALUES ("barker頻道",1)';
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
	function creatBarkerPTConfig($ptid){
		global $logger, $my;
		$sql='SELECT 版位識別碼 FROM 版位其他參數 WHERE 版位識別碼 = ?';
		$result =$my->getResultArray($sql,'i',$ptid);
		if(count($result)>0){
			echo '已建立過版位類型其他參數'.'<br>';
			return 0;
		}
		
		$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
		.' VALUES ('.$ptid.',1,"channel_id","channel_id",2,1,1,null,1)'
		.',  ('.$ptid.',2,"playout_id","playout_id",2,1,1,-1,1)'
		.',  ('.$ptid.',3,"english_name","english_name",1,0,1,null,1)'
		.',  ('.$ptid.',4,"online","online",3,1,1,1,1)'
		.',  ('.$ptid.',5,"sale","sale",3,1,1,0,1)'
		.',  ('.$ptid.',6,"sale_order","sale_order",3,1,0,0,1)'
		.',  ('.$ptid.',7,"transactionId(送出後產生，請勿填寫)","transaction_id",2,1,0,null,1)'
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
	function creatBarkerPTMaterial($ptid){
		global $logger, $my;
		$sql='SELECT 版位識別碼 FROM 版位素材類型 WHERE 版位識別碼 = '.$ptid;
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位類型素材<br>';
			return 0;
		}
				
		$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)'
		.' VALUES ('.$ptid.',1,3,0,1,1)'
		.',  ('.$ptid.',2,3,0,2,1)'
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
	
	
	function creatBarkerT($row,$ptid){
		global $logger, $my;
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = "'.$row['name'].'"';
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			$pid = $result[0]['版位識別碼'];
			echo $row['name'].':已建立過版位，ID:'.$pid.'<br>';
		}
		else{
			$sql='INSERT INTO 版位 (版位名稱,上層版位識別碼,CREATED_PEOPLE) VALUES ("'.$row['name'].'",'.$ptid.',1)';
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			
			$pid = $stmt->insert_id;
			echo '版位 '.$row['name'].' 建立完成，ID'.$pid.'<br>';
		}
		$sql='SELECT 版位識別碼 FROM 版位其他參數 WHERE 版位識別碼 = ?';
		$result =$my->getResultArray($sql,'i',$pid);
		if(count($result)>0){
			echo $row['name'].' 已建立過版位其他參數'.'<br>';
			$sql='DELETE FROM 版位其他參數 WHERE 版位識別碼=?';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('i',$pid)) {
				exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			//return 0;
		}
		echo $row['name'].' 開始建立版位其他參數'.'<br>';
		$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
		.' VALUES ('.$pid.',1,"channel_id","channel_id",2,1,1,?,1)'
		.',  ('.$pid.',2,"playout_id","playout_id",2,1,1,?,1)'
		.',  ('.$pid.',3,"english_name","english_name",1,0,1,?,1)'
		.',  ('.$pid.',4,"online","online",3,1,1,?,1)'
		.',  ('.$pid.',5,"sale","sale",3,1,1,?,1)'
		;
		echo $row['name'].' 準備sql<br>';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		echo $row['name'].' bind para<br>';
		$online = !$row['online']?0:$row['online'];
		$sale =(!$row['sale']?0:$row['sale']);
		if(!$stmt->bind_param('sssss',$row['channel_id'],$row['playout_id'],$row['english_name'],$online,$sale)) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		echo $row['name'].' execute<br>';
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$ptid = $stmt->insert_id;
		echo $row['name'].' 版位參數建立完成<br>';
		return 1;
	}
	
	//取得barker版位
	function getPData($ptid){
		global $url;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		//curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		$apiResult = curl_exec($ch);
		if(curl_errno($ch))
		{
			//$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			//return false;
			echo '錯誤代號:'.curl_errno($ch).'無法連接API:'.$url;
		}
		curl_close($ch);
		$data = json_decode($apiResult,true);
		print_r($data);
		foreach($data as $row){
			creatBarkerT($row,$ptid);
		}
	}
?>