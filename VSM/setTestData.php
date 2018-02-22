<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	$logger=new MyLogger();
	$my=new MyDB(true);
	if(isset($_GET['id']))
		$oid =$_GET['id'];
	else{
	//$oid = 780;//barker
	//$oid = 720;//banner1
	$oid = 647;//banner2
	//$oid = 801;//epgbanner
	//$oid = 447;//epgbanner
	}

	setBannerData($oid);
	//setEPGBannerData($oid);

	//新增測試用banner資訊
	function setBannerData($oid){
		global $my;
		//取得託播單名稱與版位資訊
		$sql = '
		SELECT 託播單名稱,版位其他參數.版位其他參數預設值,版位類型.版位名稱 AS 版位類型名稱
		FROM 託播單 
		LEFT JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼
		LEFT JOIN 版位 版位類型 ON 版位類型.版位識別碼 = 版位.上層版位識別碼
		JOIN 版位其他參數 ON 版位其他參數.版位識別碼 = 版位.版位識別碼 AND 版位其他參數.版位其他參數名稱 = "group_name"
		WHERE 託播單識別碼 = '.$oid.'
		';
		
		$odata = $my->getResultArray($sql);
		$ordername = $odata[0]['託播單名稱'];
		$gname =  $odata[0]['版位其他參數預設值'];
		$pt =  $odata[0]['版位類型名稱'];
	
		//取得所有版位資訊
		$sql = '
		SELECT 版位.版位識別碼
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 版位類型.版位名稱 = "'.$pt.'"
		JOIN 版位其他參數 ON 版位其他參數.版位識別碼 = 版位.版位識別碼 AND 版位其他參數.版位其他參數名稱 = "group_name",
		託播單
		WHERE 版位.版位識別碼!=託播單.版位識別碼 AND 託播單.託播單識別碼="'.$oid.'" AND 版位其他參數.版位其他參數預設值 = "'.$gname.'"
		';
		//print_r($sql);
		$banner_1ids = $my->getResultArray($sql);
		
		foreach($banner_1ids as $pida){
			$pid = $pida['版位識別碼'];
			//簡查有無新增過
			$sql = '
			SELECT COUNT(*) AS C
			FROM 託播單
			WHERE 託播單名稱 = "'.$ordername.'" AND 版位識別碼='.$pid.'
			';
			$C = $my->getResultArray($sql);
			$C = $C[0]['C'];
			
			echo $C.' :'.$pid.'<br>';
			//未新增過才新增
			if($C==0){
				$sql = '
				INSERT INTO 託播單 (委刊單識別碼,版位識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價,
						CREATED_PEOPLE)
				SELECT 委刊單識別碼,"'.$pid.'",託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價,
						CREATED_PEOPLE 
					FROM 託播單 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'新增託播單:無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'新增託播單:無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$newId = $stmt->insert_id;
				//新增素材
				$sql = '
				INSERT INTO 託播單素材 (託播單識別碼,素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE)
				SELECT "'.$newId.'",素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE 
					FROM 託播單素材 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'新增素材:無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'新增素材:無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				//新增其他參數
				$sql = '
				INSERT INTO 託播單其他參數 (託播單識別碼,託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE)
				SELECT "'.$newId.'",託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE
					FROM 託播單其他參數 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'新增參數:無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'新增參數:無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				//新增投放版位
				$sql='
					INSERT INTO 託播單投放版位 (託播單識別碼,版位識別碼,CREATED_PEOPLE)
					SELECT "'.$newId.'","'.$pid.'",CREATED_PEOPLE
					FROM 託播單投放版位 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
			}
		}
	}
	
	
	function setEPGBannerData($oid){
		global $my;
		//取得託播單名稱與版位資訊
		$sql = '
		SELECT 託播單名稱,版位類型.版位名稱 AS 版位類型名稱
		FROM 託播單 
		LEFT JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼
		LEFT JOIN 版位 版位類型 ON 版位類型.版位識別碼 = 版位.上層版位識別碼
		WHERE 託播單識別碼 = '.$oid.'
		';
		
		$odata = $my->getResultArray($sql);
		$ordername = $odata[0]['託播單名稱'];
		$pt =  $odata[0]['版位類型名稱'];
	
		//取得所有版位資訊
		$sql = '
		SELECT 版位.版位識別碼
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 版位類型.版位名稱 = "'.$pt.'",
		託播單
		WHERE 版位.版位識別碼!=託播單.版位識別碼 AND 託播單.託播單識別碼="'.$oid.'"
		';
		//print_r($sql);
		$banner_1ids = $my->getResultArray($sql);
		
		foreach($banner_1ids as $pida){
			$pid = $pida['版位識別碼'];
			//簡查有無新增過
			$sql = '
			SELECT COUNT(*) AS C
			FROM 託播單
			WHERE 託播單名稱 = "'.$ordername.'" AND 版位識別碼='.$pid.'
			';
			$C = $my->getResultArray($sql);
			$C = $C[0]['C'];
			
			echo $C.' :'.$pid.'<br>';
			//未新增過才新增
			if($C==0){
				$sql = '
				INSERT INTO 託播單 (委刊單識別碼,版位識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價,
						CREATED_PEOPLE)
				SELECT 委刊單識別碼,"'.$pid.'",託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價,
						CREATED_PEOPLE 
					FROM 託播單 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'新增託播單:無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'新增託播單:無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$newId = $stmt->insert_id;
				//新增素材
				$sql = '
				INSERT INTO 託播單素材 (託播單識別碼,素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE)
				SELECT "'.$newId.'",素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE 
					FROM 託播單素材 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'新增素材:無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'新增素材:無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				//新增其他參數
				$sql = '
				INSERT INTO 託播單其他參數 (託播單識別碼,託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE)
				SELECT "'.$newId.'",託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE
					FROM 託播單其他參數 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'新增參數:無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'新增參數:無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				//新增投放版位
				$sql='
					INSERT INTO 託播單投放版位 (託播單識別碼,版位識別碼,CREATED_PEOPLE)
					SELECT "'.$newId.'","'.$pid.'",CREATED_PEOPLE
					FROM 託播單投放版位 WHERE 託播單識別碼 = '.$oid.'
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
			}
		}
	}
	
	//連接API取的結果
	function connec_to_Api($url,$method,$postvars){
		global $logger;
		$postvars = (isset($postvars)) ? $postvars : null;
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		$apiResult = curl_exec($ch);
		if(curl_errno($ch))
		{
			print_r('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $apiResult;
	}
?>