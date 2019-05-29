<?php
ini_set('display_errors','1');
error_reporting(E_ALL);
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	require_once '../Config.php';
	$logger=new MyLogger();
	$my=new MyDB(true);
	$apiurl = Config::GET_API_SERVER_852().":80/mod/AD/api/service";
	
	$ptid = creatPT();
	$pdata = getPData($apiurl);
	print_r($pdata);
	exit();
	createP($pdata);
	
	//建立版位類型
	function creatPT(){
		global $logger, $my;
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = "前置廣告投放系統"';
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位類型'.'<br>';
			return $result[0]['版位識別碼'];
		}
		
		return $ptid;
	}
	
	//從API取得板位參數
	function getPData($url){
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($url,'POST',null)){
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSMAPI'),JSON_UNESCAPED_UNICODE));	
		}
		if(!$apiResult["success"]){
			exit("API取得資料失敗");	
		}
		$PDatas = json_decode($apiResult["data"],true);
		if($PDatas["code"]!=200){
			print_r($apiResult);
			exit("API取得資料失敗:".$PDatas["status"]);	
		}
		return $PDatas["service"];
	}
	
	//建立板位
	function createP($pdata){
		global $ptid;
		$pid = "";
		foreach($pdata as $row){
			if($row["mark"]=="T")
			createP_sub($row);
		}
	}
	
	//建立版位
	function createP_sub($row){
		global $ptid, $logger, $my;
		$pName = "插廣告_".$row["name"];
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = "'.$pName.'" AND 上層版位識別碼 = '.$ptid;
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			$pid = $result[0]["版位識別碼"];
			echo '已建立過版位:'.$pName.' ID:'.$pid.'<br>';
		}else{
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
		}
		//建立板位參數
		echo "刪除舊有版位".$pid."參數<br>";
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
		echo "開始建立版位".$pid."參數<br>";
		$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
		.' VALUES'
		.'	('.$pid.',1,"ext","ext",2,1,1,"'.$row["ext"].'",1)'
		;
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		echo "版位參數建立完成<br>";
	}
	
	function connec_to_Api($url,$method){
		// 建立CURL連線
		echo "連接API:".$url."<br>";
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		$apiResult = curl_exec($ch);
		if(curl_errno($ch))
		{
			curl_close($ch);
			return array('success'=>false,'errorno'=>curl_errno($ch));
		}
		curl_close($ch);
		return array('success'=>true,'data'=>$apiResult);
	}
?>