<?php
	/****
	更新從VSM撈取最新版位資訊並更新
	*****/
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	require_once '../Config_VSM_Meta.php';
	$logger=new MyLogger();
	$mat_type_name = ['banner','barker_vod','barker','marquee','background_banner','advertising_page','floating_banner'];
	//$apiUrl = 'localhost/VSMAPI/getVSMPosition.php';	
	$apiUrl = Config_VSM_Meta::GET_POSITION_API();	
	//連線DB
	$my=new MyDB(true);
	//建立版位
	creatPositions();
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
				$logger->error('無法準備statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
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
			$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,顯示名稱,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)';
			//banner
			if($ptindex==0){
				$sql.=' VALUES ('.$ptid.',1,"圖片",2,1,1,1)'
				;
			}
			//barker_vod
			else if($ptindex==1){
				$sql.=' VALUES ('.$ptid.',1,"影片",3,0,1,1)'
				.',  ('.$ptid.',2,"影片",3,0,2,1)'
				;
			}
			//barker
			else if($ptindex==2){
				$sql.=' VALUES ('.$ptid.',1,"影片",3,0,1,1)'
				;
			}
			//marquee
			else if($ptindex==3){
				$sql.=' VALUES ('.$ptid.',1,"文字",1,1,1,1)'
				;
			}
			//background_banner
			else if($ptindex==4){
				$sql.=' VALUES ('.$ptid.',1,"圖片",2,1,1,1) ,('.$ptid.',2,"縮圖",2,0,1,1)'
				;
			}
			//advertising_page
			else if($ptindex==5){
				$sql.=' VALUES ('.$ptid.',1,"圖片",2,1,1,1)'
				.',  ('.$ptid.',2,"HD影片",3,0,2,1)'
				.',  ('.$ptid.',3,"SD影片",3,0,1,1)'
				;
				
			}
			//floating_banner
			else if($ptindex==6){
				$sql.=' VALUES ('.$ptid.',1,"圖片",2,1,1,1)'
				;
				
			}
			else{
				return 0;
			}
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			echo '版位類型素材建立完成<br>';
			
		}
		//建立版位類型其他參數
		$sql='DELETE FROM 版位其他參數 WHERE 版位識別碼=?';
		
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->bind_param('i',$ptid)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		//banner
		if($ptindex==0){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"weight","weight",2,1,0,90,1)'
			.',  ('.$ptid.',5,"linkType","linkType",1,1,0,"",1)'
			.',  ('.$ptid.',6,"link","link",1,1,0,"",1)'
			.',  ('.$ptid.',7,"linkParameter","linkParameter",1,1,0,"",1)'
			.',  ('.$ptid.',8,"netflixId","specific_iid",1,1,1,"",1)'
			;

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
			.',  ('.$ptid.',8,"url","url",1,1,1,"rtsp://172.16.74.210:559/",1)'
			.',  ('.$ptid.',9,"bannerTransactionId","bannerTransactionId",1,1,0,"",1)'
			.',  ('.$ptid.',10,"投放上限","playTimeLimit",2,0,0,0,1)'
			;
		}
		//barker
		else if($ptindex==2){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"url","url",1,1,1,"igmp://230.1.2.102:2102",1)'
			;
		}
		//marquee
		else if($ptindex==3){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"weight","weight",2,1,0,90,1)'
			.',  ('.$ptid.',5,"linkType","linkType",1,1,0,"",1)'
			.',  ('.$ptid.',6,"link","link",1,1,0,"",1)'
			.',  ('.$ptid.',7,"linkParameter","linkParameter",1,1,0,"",1)'
			.',  ('.$ptid.',8,"顯示頻率","frequence",2,1,0,1,1)'
			;
		}
		//background_banner
		else if($ptindex==4){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"weight","weight",2,1,0,1,1)'
			.',  ('.$ptid.',5,"縮圖文字","context",1,0,0,"",1)'
			;
		}
		//advertising_page
		else if($ptindex==5){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"標題顏色","titleColor",1,0,0,"#FFFFFFFF",1)'
			.',  ('.$ptid.',5,"子標題","subheader",1,1,0,"",1)'
			.',  ('.$ptid.',6,"子標題顏色","subheaderColor",1,0,0,"#FF55555",1)'
			.',  ('.$ptid.',7,"內文","content",1,1,0,"",1)'
			.',  ('.$ptid.',8,"內文顏色","contentColor",1,0,0,"#FF55555",1)'
			.',  ('.$ptid.',9,"成人內容","isAdult",3,0,0,0,1)'
			.',  ('.$ptid.',10,"影片url","url",1,1,1,"rtsp://172.16.74.210:559/",1)'
			.',  ('.$ptid.',11,"weight","weight",2,1,0,"1",1)'
			.',  ('.$ptid.',12,"netflixId","specific_iid",1,1,1,"",1)'
			;
		}
		//floating_banner
		else if($ptindex==6){
			$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
			.',  ('.$ptid.',2,"srv_category_id","srv_category_id",1,1,1,-1,1)'
			.',  ('.$ptid.',3,"group_name","group_name",1,1,1,?,1)'
			.',  ('.$ptid.',4,"weight","weight",2,1,0,"1",1)'
			.',  ('.$ptid.',5,"觸發連結按鍵","keyCode",1,1,0,"info",1)'
			;
		}
		else{
			echo '不需建立板位<br>';
			return 0;
		}
		
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->bind_param('ss',$PTData['mat_type_id'],$PTData['mat_type_name'])){
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
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
				$logger->error('無法準備statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
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
			$sql='INSERT INTO 版位素材類型 (版位識別碼,素材順序,顯示名稱,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,CREATED_PEOPLE)'
			.' VALUES ('.$ptid.',1,"圖片",2,0,1,1)'
			.',  ('.$ptid.',2,"展開圖片",2,0,1,1)'
			;
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			echo '版位類型素材建立完成<br>';
		}
		//建立版位類型其他參數
		$sql='DELETE FROM 版位其他參數 WHERE 版位識別碼=?';
		
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->bind_param('i',$ptid)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
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
		.',  ('.$ptid.',9,"白名單EPG","SpEPG",3,1,0,0,1)'
		
		;
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}			
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		echo '版位其他參數建立完成<br>';
		return $ptid;
	}
	
	//建立版位function
	function createP($ptid,$PTData){
		global $my,$logger;
		$ptn = preg_replace('/\s+/','',$PTData['srv_category_name'].'_'.$PTData['group_name']);
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
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			
			$pid = $stmt->insert_id;
			echo '版位建立完成，ID'.$pid.'<br>';
		}
		//建立版位類型其他參數
		$sql='DELETE FROM 版位其他參數 WHERE 版位識別碼=? AND 版位其他參數順序 IN (1,2,3)';
		
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->bind_param('i',$pid)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
		.' VALUES ('.$pid.',1,"mat_type_id","mat_type_id",1,1,1,?,1)'
		.',  ('.$pid.',2,"srv_category_id","srv_category_id",1,1,1,?,1)'
		.',  ('.$pid.',3,"group_name","group_name",1,1,1,?,1)'
		;
		;
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->bind_param('sss',$PTData['mat_type_id'],$PTData['srv_category_id'],$PTData['group_name'])){
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		$ptid = $stmt->insert_id;
		echo '版位其他參數建立完成<br>';
		return $ptid;
	}
	
	function createEPGP($ptid,$PTData){
		global $my,$logger;
		$ptn = $PTData['channel_number'].'_'.$PTData['title'];
		/*$sql='SELECT 版位.版位識別碼 FROM 版位 LEFT JOIN 版位其他參數 ON 版位.版位識別碼 = 版位其他參數.版位識別碼 
			WHERE 版位其他參數.版位其他參數名稱 = "content_id" AND 版位其他參數預設值 LIKE ?';
		$result =$my->getResultArray($sql,'s',$PTData['content_id']);*/
		/*$sql='SELECT 版位.版位識別碼 FROM 版位 WHERE 版位名稱 LIKE ? AND 上層版位識別碼 = ? and DISABLE_TIME IS NULL AND DELETED_TIME IS NULL';
		$result =$my->getResultArray($sql,'si',$PTData['channel_number'].'\_%',$ptid);*/
		$sql='SELECT 版位識別碼 FROM 版位其他參數 
			WHERE 版位其他參數.版位其他參數名稱 = "content_id" AND 版位其他參數預設值 LIKE ?';
		$result =$my->getResultArray($sql,'s',$PTData['content_id']);
		echo '建立版位'.$ptn.'<br>';
		if(count($result)>0){
			echo '已建立過版位'.$result[0]['版位識別碼'].'<br>';
			$pid = $result[0]['版位識別碼'];
			$sql = "UPDATE 版位 SET 版位名稱 = ? WHERE 版位識別碼 = ?";
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			if(!$stmt->bind_param('si',$ptn,$pid)){
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			if(!$stmt->execute()) {

				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			}
			
		}
		else{
			//建立版位類型資料
			$sql='INSERT INTO 版位 (版位名稱,上層版位識別碼,CREATED_PEOPLE) VALUES ("'.$ptn.'",'.$ptid.',1)';
			if(!$stmt=$my->prepare($sql)) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			
			if(!$stmt->execute()) {
				exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
			}
			
			$pid = $stmt->insert_id;
			echo '版位建立完成，ID'.$pid.'<br>';
		}
		//建立版位類型其他參數
		$sql='DELETE FROM 版位其他參數 WHERE 版位識別碼=?';
		
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->bind_param('i',$pid)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		$sql='INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數顯示名稱,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,是否版位專用,版位其他參數預設值,CREATED_PEOPLE)'
		.' VALUES ('.$pid.',1,"content_id","content_id",1,1,1,?,1)
		,	('.$pid.',8,"channel_number","channel_number",1,1,1,?,1)'
		;
		if(!$stmt=$my->prepare($sql)) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->bind_param('ss',$PTData['content_id'],$PTData['channel_number'])){
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		if(!$stmt->execute()) {
			exit('錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->errror.')。');
		}
		
		$ptid = $stmt->insert_id;
		echo '版位其他參數建立完成'.$PTData['content_id'].':'.$PTData['channel_number'].'<br>';
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