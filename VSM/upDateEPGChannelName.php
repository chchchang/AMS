<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	require_once '../Config_VSM_Meta.php';
	$logger=new MyLogger();
	$mat_type_name = ['banner','barker_vod','barker','marquee'];
	//$apiUrl = 'localhost/VSMAPI/getVSMPosition.php';	
	$apiUrl = Config_VSM_Meta::GET_POSITION_API();	
	//連線DB
	$my=new MyDB(true);
	//建立版位
	//creatPositions();
	function creatPositions(){
		global $apiUrl,$logger;
		//取得版位類型資料(material_description)
		$postvars = http_build_query(array('method'=>'getVSMPositionType'));
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($apiUrl,'POST',$postvars)){
			$logger->error('無法連接VSMAPI:'.$apiUrl);
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSMAPI'),JSON_UNESCAPED_UNICODE));	
		}
		$PTDatas = json_decode($apiResult,true);
		print_r($PTDatas);
		foreach($PTDatas as $PTData){
			//建立版位類型並取得識別碼
			$ptid = createPT($PTData);
			//取得版位資料
			//取得版位類型資料(material_description)
			$postvars = http_build_query(array('method'=>'getVSMPosition','matID'=>$PTData['mat_type_id']));
			// 建立CURL連線
			if(!$apiResult=connec_to_Api($apiUrl,'POST',$postvars)){
				exit(json_encode(array("success"=>false,"message"=>'無法連接VSMAPI'),JSON_UNESCAPED_UNICODE));	
			}
			echo($apiResult);
			$pDatas = json_decode($apiResult,true);
			//$pDatas = getVSMPosition($PTData['mat_type_id']);
			//建立版位
			foreach($pDatas as $pData){
				createP($ptid,$pData);
			}
		}
	}
		
	createEPGPositions();
	function createEPGPositions(){
		global $apiUrl;
		//取得EPG版位類型資料
		$postvars = http_build_query(array('method'=>'getVSMEPGPosition'));
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($apiUrl,'POST',$postvars)){
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSMAPI'),JSON_UNESCAPED_UNICODE));	
		}
		$PDatas = json_decode($apiResult,true);
		$ptid = createEPGPT();
		echo'建立EPG版位<br>';
		foreach($PDatas as $PData){
			createEPGP($ptid,$PData);
		}
	}
	
	//建立版位類型function
	function createPT($PTData){
		global $my,$mat_type_name,$logger;
		$ptn = '單一平台'.$PTData['mat_type_name'];
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = ?';
		$result =$my->getResultArray($sql,'s',$ptn);
		if(count($result)>0){
			echo '已建立過版位類型'.$result[0]['版位識別碼'].'<br>';
			$ptid = $result[0]['版位識別碼'];
		}
		else{
			//建立版位類型資料
			$sql='INSERT INTO 版位 (版位名稱,CREATED_PEOPLE) VALUES ("'.$ptn.'",1)';
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
		}
		//建立版位素材
		$ptindex = array_search($PTData['mat_type_name'],$mat_type_name);
		$sql='SELECT 版位識別碼 FROM 版位素材類型 WHERE 版位識別碼 = '.$ptid;
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位類型素材<br>';
		}
		else{
			//banner
			if($ptindex==0){
				$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)'
				.' VALUES ('.$ptid.',1,2,0,1,1)'
				;
				if(!$stmt=$my->prepare($sql)) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				
				if(!$stmt->execute()) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				echo '版位類型素材建立完成<br>';
			}
			//barker_vod
			else if($ptindex==1){
				$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)'
				.' VALUES ('.$ptid.',1,3,0,1,1)'
				.',  ('.$ptid.',2,3,0,2,1)'
				;
				if(!$stmt=$my->prepare($sql)) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				if(!$stmt->execute()) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				echo '版位類型素材建立完成<br>';
			}
			//barker
			else if($ptindex==2){
				$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)'
				.' VALUES ('.$ptid.',1,3,0,1,1)'
				;
				if(!$stmt=$my->prepare($sql)) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				if(!$stmt->execute()) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				echo '版位類型素材建立完成<br>';
			}
			//marquee
			else if($ptindex==3){
				$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,素材類型識別碼,託播單素材是否必填,CREATED_PEOPLE)'
				.' VALUES ('.$ptid.',1,1,1,1)'
				;
				if(!$stmt=$my->prepare($sql)) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				if(!$stmt->execute()) {
					exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				}
				echo '版位類型素材建立完成<br>';
			}
		}
		//建立版位類型其他參數
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
		//banner
		if($ptindex==0){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"weight","weight",2,1,0,1,1)'
			.',  ('.$ptid.',5,"linkType","linkType",1,1,0,"",1)'
			.',  ('.$ptid.',6,"link","link",1,1,0,"",1)'
			.',  ('.$ptid.',7,"linkParameter","linkParameter",1,1,0,"",1)'
			;
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->bind_param('ss',$PTData['mat_type_id'],$PTData['mat_type_name'])){
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
		}
		//barker_vod
		else if($ptindex==1){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"weight","weight",2,1,0,1,1)'
			.',  ('.$ptid.',5,"linkType","linkType",1,1,0,"",1)'
			.',  ('.$ptid.',6,"link","link",1,1,0,"",1)'
			.',  ('.$ptid.',7,"linkParameter","linkParameter",1,1,0,"",1)'
			.',  ('.$ptid.',8,"url","url",1,1,1,"rtsp://172.17.188.35:5004/vscontsrv%3a",1)'
			.',  ('.$ptid.',9,"bannerTransactionId","bannerTransactionId",1,1,0,"",1)'
			;
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->bind_param('ss',$PTData['mat_type_id'],$PTData['mat_type_name'])){
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
		}
		//barker
		else if($ptindex==2){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"url","url",1,1,1,"igmp://230.1.2.102:2102",1)'
			;
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->bind_param('ss',$PTData['mat_type_id'],$PTData['mat_type_name'])){
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
		}
		//marquee
		else if($ptindex==3){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"weight","weight",2,1,0,1,1)'
			.',  ('.$ptid.',5,"linkType","linkType",1,1,0,"",1)'
			.',  ('.$ptid.',6,"link","link",1,1,0,"",1)'
			.',  ('.$ptid.',7,"linkParameter","linkParameter",1,1,0,"",1)'
			.',  ('.$ptid.',8,"url","url",1,1,1,"rtsp://172.17.188.35:5004/vscontsrv%3a",1)'
			;
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->bind_param('ss',$PTData['mat_type_id'],$PTData['mat_type_name'])){
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
		}
		echo '版位其他參數建立完成<br>';
		return $ptid;
	}
	
	//建立版位類型function
	function createEPGPT(){
		global $my,$logger;
		$ptn = '單一平台EPG';
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = ?';
		$result =$my->getResultArray($sql,'s',$ptn);
		if(count($result)>0){
			echo '已建立過版位類型'.$result[0]['版位識別碼'].'<br>';
			$ptid = $result[0]['版位識別碼'];
		}
		else{
			//建立版位類型資料
			$sql='INSERT INTO 版位 (版位名稱,CREATED_PEOPLE) VALUES ("'.$ptn.'",1)';
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
		}
		//建立版位素材
		$sql='SELECT 版位識別碼 FROM 版位素材類型 WHERE 版位識別碼 = '.$ptid;
		$result =$my->getResultArray($sql);
		if(count($result)>0){
			echo '已建立過版位類型素材<br>';
		}
		else{
			//banner
			$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,2,0,1,1)'
			;
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			echo '版位類型素材建立完成<br>';
		}
		//建立版位類型其他參數
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
		.' VALUES ('.$ptid.',1,"content_id","content_id",1,1,1,"",1)'
		.',  ('.$ptid.',2,"sepgDefaultFlag","sepgDefaultFlag",3,1,0,0,1)'
		.',  ('.$ptid.',3,"adType","adType",3,1,0,0,1)'
		.',  ('.$ptid.',4,"preTransaction","preTransaction",1,1,0,"",1)'
		.',  ('.$ptid.',5,"linkType","linkType",1,1,0,"",1)'
		.',  ('.$ptid.',6,"link","link",1,1,0,"",1)'
		.',  ('.$ptid.',7,"linkParameter","linkParameter",1,1,0,"",1)'
		.',  ('.$ptid.',8,"channel_number","channel_number",1,1,1,"",1)'
		;
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}			
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		echo '版位其他參數建立完成<br>';
		return $ptid;
	}
	
	//建立版位function
	function createP($ptid,$PTData){
		global $my,$logger;
		$ptn = $PTData['srv_category_name'].'_'.$PTData['group_name'];
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = ?';
		$result =$my->getResultArray($sql,'s',$ptn);
		if(count($result)>0){
			echo '已建立過版位'.'<br>';
			$pid = $result[0]['版位識別碼'];
		}
		else{
			//建立版位類型資料
			$sql='INSERT INTO 版位 (版位名稱,上層版位識別碼,CREATED_PEOPLE) VALUES ("'.$ptn.'",'.$ptid.',1)';
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			$pid = $stmt->insert_id;
			echo '版位建立完成，ID'.$pid.'<br>';
		}
		//建立版位類型其他參數
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
		.' VALUES ('.$pid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
		.',  ('.$pid.',2,"srv_category_id","srv_category_id",1,1,1,?,1)'
		.',  ('.$pid.',3,"group_name","group_name",1,1,1,?,1)'
		;
		;
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->bind_param('sss',$PTData['mat_type_id'],$PTData['srv_category_id'],$PTData['group_name'])){
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		$ptid = $stmt->insert_id;
		echo '版位其他參數建立完成<br>';
		return $ptid;
	}
	
	function createEPGP($ptid,$PTData){
		global $my,$logger;
		$ptn = $PTData['title'];
		$sql='SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = ?';
		$result =$my->getResultArray($sql,'s',$ptn);
		echo '建立版位'.$ptn.'<br>';
		if(count($result)>0){
			echo '已建立過版位'.'<br>';
			$pid = $result[0]['版位識別碼'];
		}
		else{
			//建立版位類型資料
			$sql='INSERT INTO 版位 (版位名稱,上層版位識別碼,CREATED_PEOPLE) VALUES ("'.$ptn.'",'.$ptid.',1)';
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			}
			
			$pid = $stmt->insert_id;
			echo '版位建立完成，ID'.$pid.'<br>';
		}
		//建立版位類型其他參數
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
		.' VALUES ('.$pid.',1,"content_id","content_id",1,1,1,?,1)
		,	('.$pid.',8,"channel_number","channel_number",1,1,1,?,1)'
		;
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->bind_param('ss',$PTData['content_id'],$PTData['channel_number'])){
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		
		$ptid = $stmt->insert_id;
		echo '版位其他參數建立完成<br>';
		return $ptid;
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
			$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $apiResult;
	}
?>