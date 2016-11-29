<?php
	//前置設定
	include('../tool/auth/authAJAX.php');
	include('checkIfMaterialSyn.php');
	require '../tool/OutputExcel.php';
	require '../tool/phpExtendFunction.php';
	require '../tool/FTP.php';
	$API852Url=Config::GET_API_SERVER_852();
	//判斷api與動作
	if( isset($_POST['action']) && $_POST['action'] != '' ){
		switch($_POST['action']){
			case "API送出託播單";
				sendOrderAPI();
				break;
			case "API取消託播單":
				cancelOrderAPI();
				break;
			case "852送出託播單";
				sendOrder_852();
				break;
			case "852取消託播單":
				cancelOrder_852();
				break;
			case "852託播單資料":
				orderInfo_852();
				break;
			case "851產生檔案":
				produceFileBetch_851(isset($_POST['APIAction'])?$_POST['APIAction']:'send');
				break;
			case "851託播單資料":
				orderInfo_851();
				break;
			case "群組託播單":
				groupingOrder();
				break;
			case "批次產生檔案"://csms
				produceFileBetch_851(isset($_POST['APIAction'])?$_POST['APIAction']:'send');
				break;
		}
	}

	//送出託播單到API，依段板位呼叫對應function
	function sendOrderAPI(){
		global $my;
		//取的版位類型名稱
		$sql = "SELECT 版位類型.版位名稱 AS 版位類型名稱 FROM 託播單,版位,版位 版位類型 WHERE 託播單識別碼 = ? AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼";
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}				
		if(!$res = $stmt->get_result()){
			exit(json_encode(array("dbError"=>'無法取得結果，請聯絡系統管理員'),JSON_UNESCAPED_UNICODE));
		}
		$ptN = $res->fetch_assoc()['版位類型名稱'];
		switch($ptN){
			case "前置廣告投放系統";
				sendOrder_852();
				break;
			case "首頁banner":
			case "專區banner":
			//case "頻道short EPG banner":
			case "專區vod":
				produceFile_851('send');
				break;
			default:{
				recordResult('insert',1,null,null);
				changeOrderSate('送出',array($_POST["託播單識別碼"]));
			}
		}
	}
	
	//檢查素材並群組託播單
	function groupingOrder(){
		global $my;
		$failArray = array();
		$groupArray = array();
		$singleArray = array();
		foreach($_POST['selectedOrder'] as $id){
			if(!isset($_POST['ignoreMaterialChecking']) || $_POST['ignoreMaterialChecking']== false){
				$mcheckRes = m_chckMaterial($id);
				if(!$mcheckRes['success']){
					$failArray[] = array('託播單識別碼'=>$id,'message'=>$mcheckRes['message']);
					continue;
				}
			}
			//取的版位類型名稱與版位資訊、託播單群組識別碼
			$sql = "SELECT 託播單CSMS群組識別碼,版位類型.版位名稱 AS 版位類型名稱,版位.版位名稱,廣告可被播出小時時段
			FROM 託播單,版位,版位 版位類型 WHERE 託播單識別碼 = ? AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼";
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$id)) {
				exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}				
			if(!$res = $stmt->get_result()){
				exit(json_encode(array("dbError"=>'無法取得結果，請聯絡系統管理員'),JSON_UNESCAPED_UNICODE));
			}
			$row = $res->fetch_assoc();
			$ptN = $row['版位類型名稱'];
			$pN = $row['版位名稱'];
			$gid = $row['託播單CSMS群組識別碼'];
			$hours = $row['廣告可被播出小時時段'];
			//判斷版位區域
			if(PHPExtendFunction::stringEndsWith($pN,'_北'))
			$area = '北';
			else if(PHPExtendFunction::stringEndsWith($pN,'_中'))
			$area = '中';
			else if(PHPExtendFunction::stringEndsWith($pN,'_南'))
			$area = '南';
			if(PHPExtendFunction::stringEndsWith($pN,'_IAP'))
			$area = 'IAP';
			switch($ptN){
				/*case "首頁banner":
				case "專區banner":
				case "專區vod":*/
				case "頻道short EPG banner":
					/***
					送出shortEPG託播單時，強制將同群組內同時段的託播單一起送出
					***/
					//檢察群組是否以經選擇過
					if(!isset($groupArray[$ptN][$area][$gid][$hours])){
						$groupArray[$ptN][$area][$gid][$hours] = array();
						//取的同群組且同時段的託播單識別碼
						$sql = "SELECT 託播單識別碼 FROM 託播單 JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼 WHERE 託播單CSMS群組識別碼 = ? AND 版位名稱 LIKE ? AND 託播單狀態識別碼 != 5";
						if(!$stmt=$my->prepare($sql)) {
							exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						$areaWildCard = '%_'.$area;
						if(!$stmt->bind_param('is',$gid,$areaWildCard)) {
							exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						if(!$stmt->execute()) {
							exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}				
						if(!$res = $stmt->get_result()){
							exit(json_encode(array("dbError"=>'無法取得結果，請聯絡系統管理員'),JSON_UNESCAPED_UNICODE));
						}
						//$fail = false;
						$tempA = [];
						$failMessage='';
						while($row = $res->fetch_assoc()){
							//檢查群組內的託播單是否都選擇素材				
							array_push($tempA,$row['託播單識別碼']);
						}
						$groupArray[$ptN][$area][$gid][$hours] = array_merge($groupArray[$ptN][$area][$gid][$hours],$tempA);
					}
					break;
				default:
					array_push($singleArray,$id);
					break;
			}
		}
		$reArrangeGroup = array();
		foreach($groupArray as $ptn=>$A1)
		foreach($A1 as $area => $A2)
		foreach($A2 as $gid => $A3)
		foreach($A3 as $hour => $ids){
			asort($ids);
			array_push($reArrangeGroup,array('ptN'=>$ptn,'area'=>$area,'gId'=>$gid,'hours'=>$hour,'ids'=>$ids));
		}
		exit (json_encode(array("success"=>true,'failArray'=>$failArray,'groupArray'=>$reArrangeGroup,'singleArray'=>$singleArray),JSON_UNESCAPED_UNICODE));
	}
	
	//檢查素材用
	function m_chckMaterial($id){
		$CSMSPTN = ['首頁banner','專區banner','專區vod','頻道short EPG banner'];
		$my=new MyDB(true);
		$sql = "SELECT COUNT(*) AS count FROM 託播單素材 WHERE 託播單識別碼 = ? AND 素材識別碼 = 0";
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$id)) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}				
		if(!$res = $stmt->get_result()){
			exit(json_encode(array("dbError"=>'無法取得結果，請聯絡系統管理員'),JSON_UNESCAPED_UNICODE));
		}
		$row = $res->fetch_assoc();
		if($row['count']>0){
			return array("success"=>false,"message"=>'必選素材未選擇。');
		}
		
		$sql='
			SELECT
				版位類型.版位名稱 版位類型名稱,版位.版位名稱,COUNT(1) 未填必填素材筆數
			FROM
				託播單
				INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
				INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
				INNER JOIN 版位素材類型 ON 版位素材類型.版位識別碼=版位.上層版位識別碼
				LEFT JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼 AND 託播單素材.素材順序=版位素材類型.素材順序
			WHERE
				版位素材類型.託播單素材是否必填=true
				AND 託播單.託播單識別碼=?
				AND 託播單素材.素材識別碼 IS NULL
		';

		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$id)) {
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}

		$row=$res->fetch_assoc();
		
		if($row['未填必填素材筆數']!=0)
			return array("success"=>false,"message"=>'必選素材未選擇。');
			
		$sql = "SELECT 素材原始檔名,素材.素材識別碼,素材類型名稱,影片媒體編號,圖片素材派送結果
			FROM 素材,託播單素材,素材類型 WHERE 託播單識別碼 = ? AND 素材.素材識別碼 = 託播單素材.素材識別碼 AND 素材.素材類型識別碼=素材類型.素材類型識別碼";
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$id)) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}				
		if(!$res = $stmt->get_result()){
			exit(json_encode(array("dbError"=>'無法取得結果，請聯絡系統管理員'),JSON_UNESCAPED_UNICODE));
		}
		//逐一檢查素材
		while($row2 = $res->fetch_assoc()){
			$area=explode("_",$row['版位名稱']);
			$area = $area[count($area)-1];
			if($area=='北'){
				$area = 'N';
			}
			else if($area=='中'){
				$area = 'C';
			}
			else if($area=='南'){
				$area = 'S';
			}
			//*!*!*!staging 註解以下程式碼
			//確認素材是否被派送
			if($row2['素材類型名稱']=='影片'){
				//檢查影片是否派送
				if($row2['影片媒體編號']==null || $row2['影片媒體編號']==''){
					return array("success"=>false,"message"=>'素材尚未派送');
				}
				$explodeFileName=explode(".",$row2['素材原始檔名']);
				$fileName='../material/uploadedFile/'.$row2['素材識別碼'].".".$explodeFileName[count($explodeFileName)-1];
				$exists= file_exists($fileName);
				if(!$exists){
					return array("success"=>false,"message"=>'素材尚未派送');
				}else{
					$片名='_____AMS_'.$row2['素材識別碼'].'_'.md5_file($fileName);
					$url='http://172.17.251.83:82/PTS/pts_media_status.php?v_id=2305&source='.$片名;
					$ch=curl_init($url);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
					$xml=simplexml_load_string(curl_exec($ch));
					$mediaId=(string)$xml->mediaId;
					if($area == 'N')
						$status=(string)$xml->chtnStatus;
					else if($area == 'C')
						$status=(string)$xml->chtcStatus;
					else if($area == 'S')
						$status=(string)$xml->chtsStatus;	
					else
						$status = 1;
						
					if(intval($status)!=1)
						return array("success"=>false,"message"=>'對應區域的伺服器尚未派送素材');
					
				}
			}else if($row2['素材類型名稱']=='圖片'){
				//版位類型
				if($row['版位類型名稱']=='頻道short EPG banner')
					$type = 'EPG';
				else if($row['版位類型名稱']=='專區banner' || $row['版位類型名稱']=='首頁banner')
					$type = '專區';
					
				if($area=='IAP')
				{
					//IAP版位目前沒有素材伺服器，不需檢查
					return array("success"=>true,"message"=>'success');
				}
				//檢查圖便是否派送
				$picRes = json_decode($row2['圖片素材派送結果']);
				if(count($picRes) == 0)
					return array("success"=>false,"message"=>'素材尚未派送');
				else{
					$serverArray = [];	
					foreach(Config::$FTP_SERVERS['OMP_'.$area] as $server){
						$serverArray[] = $server['host'];
					}
					$intersect=array_intersect($serverArray,$picRes);
					if($serverArray != $intersect)
					return array("success"=>false,"message"=>'素材尚未派送');
				}
			}
			
			//若為CSMS類型版位，確認OMP資料庫是否可以讀取到該素材
			if(in_array($row['版位類型名稱'],$CSMSPTN)){
				$byPost=[
					'素材識別碼'=>$row2['素材識別碼'],
					'區域'=>$area
					];
				$checkResult = json_decode(checkIfMaterialSyn::checkIfSyn($byPost));
				if(!$checkResult->success)
					return array("success"=>false,"message"=>'CSMS尚未同步素材');
			}
			//*!*!*!staging end
		}
		return array("success"=>true,"message"=>'success');
	}
	
	function cancelOrderAPI(){
		global $my;
			//取得介街用參數
		$sql = "SELECT 版位類型.版位名稱 AS 版位類型名稱
				FROM 託播單,版位,版位 版位類型
				WHERE 託播單.版位識別碼=版位.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 託播單識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		
		$row=$res->fetch_assoc();
		switch($row['版位類型名稱']){
			case "前置廣告投放系統";
				cancelOrder_852();
				break;
			case "首頁banner":
			case "專區banner":
			//case "頻道short EPG banner":
			case "專區vod":
				produceFile_851('delete');
				break;
			default:{
				recordResult('delete',1,null,null);
				changeOrderSate('取消送出',array($_POST["託播單識別碼"]));
			}
		}
	}
	
	//連接API取的結果
	function connec_to_Api($url,$postvars){
		global $logger;
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
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
	
	//送出託播單852
	function sendOrder_852(){
		global $API852Url,$logger,$my;
		$url = $API852Url.'/mod/AD/api/vod/insert';
		
		$my=new MyDB(true);
		//先取得託播單資訊與對應素材資訊
		$sql='
			SELECT
				託播單名稱,
				託播單.版位識別碼,
				影片素材秒數,
				影片媒體編號,
				影片媒體編號北,
				影片媒體編號南,
				廣告期間開始時間,
				廣告期間結束時間
			FROM
				託播單
				INNER JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼
				INNER JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
			WHERE
				託播單.託播單識別碼=?
			ORDER BY
				託播單素材.素材順序
		';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		$result1=$res->fetch_assoc();
		
		//再取得版位類型、版位、託播單其他參數，並依序被取代。
		$sql='
			SELECT
				版位類型其他參數.版位其他參數名稱 版位類型其他參數名稱,
				版位類型其他參數.版位其他參數預設值 版位類型其他參數預設值,
				版位其他參數.版位其他參數預設值,
				託播單其他參數.託播單其他參數值
			FROM
				託播單
				INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
				INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
				INNER JOIN 版位其他參數 版位類型其他參數 ON 版位類型其他參數.版位識別碼=版位類型.版位識別碼
				LEFT JOIN 版位其他參數 ON 版位其他參數.版位識別碼=版位.版位識別碼 AND 版位其他參數.版位其他參數順序=版位類型其他參數.版位其他參數順序
				LEFT JOIN 託播單其他參數 ON 託播單其他參數.託播單識別碼=託播單.託播單識別碼 AND 託播單其他參數.託播單其他參數順序=版位類型其他參數.版位其他參數順序
			WHERE
				託播單.託播單識別碼=?
			ORDER BY
				版位類型其他參數.版位其他參數順序
		';
		$result2=$my->getResultArray($sql,'i',$_POST["託播單識別碼"]);
		$result3=array();
		foreach($result2 as $row){
			$result3[$row['版位類型其他參數名稱']]=$row['版位類型其他參數預設值'];
			if($row['版位其他參數預設值']!=null) $result3[$row['版位類型其他參數名稱']]=$row['版位其他參數預設值'];
			if($row['託播單其他參數值']!=null) $result3[$row['版位類型其他參數名稱']]=$row['託播單其他參數值'];
		}
		
		//介接用餐數
		$orderByPost=array(
			'ext'=>$result3['ext'],
			'ams_sid'=>$result1['版位識別碼'],
			'ams_vid'=>$_POST['託播單識別碼'],
			'title'=>$result1['託播單名稱'],
			'sec'=>$result1['影片素材秒數'],
			'starttime'=>$result1['廣告期間開始時間'],
			'endtime'=>$result1['廣告期間結束時間'],
			'up'=>(!isset($result3['全體投放次數上限'])||$result3['全體投放次數上限']=='')?0:$result3['全體投放次數上限'],
			'down'=>(!isset($result3['個人投放次數上限'])||$result3['個人投放次數上限']=='')?0:$result3['個人投放次數上限'],
			'mark'=>1,
			'sort'=>$result3['影片排序'],
			'hq'=>$result1['影片媒體編號'],
			'iaps'=>$result1['影片媒體編號南'],
			'iapn'=>$result1['影片媒體編號北']
		);
		if(isset($result3['pre']))
			$orderByPost['pre'] = $result3['pre'];
		$postvars = http_build_query($orderByPost);
		//檢查託播單是否存在
		$checkByPost=array(
				'ext'=>$result3['ext'],
				'ams_sid'=>$result1['版位識別碼'],
				'ams_vid'=>$_POST['託播單識別碼']
		);
		$checkurl = $API852Url.'/mod/AD/api/vod';
		$checkPostvars = http_build_query($checkByPost);
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($checkurl,$checkPostvars)){
			$logger->error('無法連接前置廣告投放系統送出託播單API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult);
		if($checkResult->code==200){
			if(count($checkResult->vod)!=0){
				//存在，使用update
				$url = $API852Url.'/mod/AD/api/vod/update';
				// 建立CURL連線
				if(!$apiResult=connec_to_Api($url,$postvars)){
					$logger->error('無法連接前置廣告投放系統送出託播單API');
					exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
				}
				
				$result = json_decode($apiResult);
				if($result->code==200){
					//修改成功
					$logger->info('送出託播單(識別碼'.$_POST["託播單識別碼"].'):'.json_encode($orderByPost,JSON_UNESCAPED_UNICODE).'  APi_Response:'.json_encode($result->vod[0],JSON_UNESCAPED_UNICODE));
					if(m_compare_order($orderByPost,$result->vod[0])){
						if(daubleChecking($checkPostvars,$orderByPost)){
							recordResult('update',1,null,null);
						}
						else{
							//二次檢查失敗
							recordResult('update',1,'投放系統託播單資訊與AMS不一致',null);
						}
					}
					else
						recordResult('update',1,'投放系統託播單資訊與AMS不一致',null);
					
					changeOrderSate('送出',array($_POST["託播單識別碼"]));
				}
				else{
					//修改失敗
					$logger->error('前置廣告投放系統託播單送出錯誤:	code'.$result->code.' sataus'.$result->status);
					recordResult('update',0,null,$result->status);
				}
			}
			else{
				//不存在，使用insert
				// 建立CURL連線
				if(!$apiResult=connec_to_Api($url,$postvars)){
					$logger->error('無法連接前置廣告投放系統送出託播單API');
					exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
				}
				
				$result = json_decode($apiResult);
				if($result->code==200){
					//新增成功
					$logger->info('送出託播單(識別碼'.$_POST["託播單識別碼"].'):'.json_encode($orderByPost,JSON_UNESCAPED_UNICODE).'  APi_Response:'.json_encode($result->vod[0],JSON_UNESCAPED_UNICODE));
					if(m_compare_order($orderByPost,$result->vod[0])){
						if(daubleChecking($checkPostvars,$orderByPost)){
							recordResult('insert',1,null,null);
						}else{
							//二次檢查失敗
							recordResult('insert',1,'投放系統託播單資訊與AMS不一致',null);
						}
					}
					else
						recordResult('insert',1,'投放系統託播單資訊與AMS不一致',null);
						
					changeOrderSate('送出',array($_POST["託播單識別碼"]));
				}
				else{
					//新增失敗
					$logger->error('前置廣告投放系統託播單送出錯誤:	code'.$result->code.' sataus'.$result->status);
					recordResult('insert',0,null,$result->status);
				}
			}
		}
		else{
			$logger->error('無法連接前置廣告投放系統送出託播單API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
		}
		exit(json_encode(array("success"=>false,"message"=>'','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
	}
	function daubleChecking($checkPostvars,$orderByPost){
		global $API852Url,$logger,$my;
		//檢查託播單是否存在
		$checkurl = $API852Url.'/mod/AD/api/vod';
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($checkurl,$checkPostvars)){
			$logger->error('無法連接前置廣告投放系統送出託播單API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult);
		if($checkResult->code==200 && isset($checkResult->vod[0]) && $checkResult->vod[0]->mark==1){
			if(m_compare_order($orderByPost,$checkResult->vod[0])){
				return true;
			}
		}
		return false;
	}
	//比較送出資料與拖播單用
	function m_compare_order($bypost,$apivod){

		if($bypost['ext']!=$apivod->ext)	
			return false;
		
		if($bypost['title']!=$apivod->title)	
			return false;
		
		if(intval($bypost['sec'])!=$apivod->sec)	
			return false;
		
		if($bypost['starttime']!=$apivod->starttime)	
			return false;
		
		if($bypost['endtime']!=$apivod->endtime)	
			return false;
		
		if(intval($bypost['up'])!=$apivod->up)	
			return false;
		
		if(intval($bypost['down'])!=$apivod->down)	
			return false;
		
		if(intval($bypost['sort'])!=$apivod->sort)	
			return false;
		
		if($bypost['mark']!=$apivod->mark)	
			return false;
		
		if($bypost['hq']!=$apivod->hq)	
			return false;

		if(isset($bypost['pre']))
			if($bypost['pre']!=$apivod->pre)	
				return false;
		
		if($bypost['iaps']==''){
			if($apivod->iaps!= null||$apivod->iaps!= '')
				return false;
		}else if($bypost['iaps'] != $apivod->iaps){
			return false;
		}
		
		if($bypost['iapn']==''){
			if($apivod->iapn!= null||$apivod->iapn!= '')
				return false;
		}else if($bypost['iapn'] != $apivod->iapn){
			return false;
		}
		
		return true;
	}
	
	//取消852託播單
	function cancelOrder_852(){
		global $API852Url,$logger,$my;
		
		//取得版位類型、版位、託播單其他參數，並依序被取代。
		$sql='
			SELECT
				版位類型其他參數.版位其他參數名稱 版位類型其他參數名稱,
				版位類型其他參數.版位其他參數預設值 版位類型其他參數預設值,
				版位其他參數.版位其他參數預設值,
				託播單其他參數.託播單其他參數值,
				託播單.版位識別碼
			FROM
				託播單
				INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
				INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
				INNER JOIN 版位其他參數 版位類型其他參數 ON 版位類型其他參數.版位識別碼=版位類型.版位識別碼
				LEFT JOIN 版位其他參數 ON 版位其他參數.版位識別碼=版位.版位識別碼 AND 版位其他參數.版位其他參數順序=版位類型其他參數.版位其他參數順序
				LEFT JOIN 託播單其他參數 ON 託播單其他參數.託播單識別碼=託播單.託播單識別碼 AND 託播單其他參數.託播單其他參數順序=版位類型其他參數.版位其他參數順序
			WHERE
				託播單.託播單識別碼=?
			ORDER BY
				版位類型其他參數.版位其他參數順序
		';
		$result2=$my->getResultArray($sql,'i',$_POST["託播單識別碼"]);
		$result3=array();
		foreach($result2 as $row){
			$result3[$row['版位類型其他參數名稱']]=$row['版位類型其他參數預設值'];
			if($row['版位其他參數預設值']!=null) $result3[$row['版位類型其他參數名稱']]=$row['版位其他參數預設值'];
			if($row['託播單其他參數值']!=null) $result3[$row['版位類型其他參數名稱']]=$row['託播單其他參數值'];
		}
		$result3['版位識別碼'] = $result2[0]['版位識別碼'];
		
		$url = $API852Url.'/mod/AD/api/vod/update';
		$byPost=array('ext'=>$result3['ext'],'ams_vid'=>$_POST['託播單識別碼'],'mark'=>0);
		$postvars = http_build_query($byPost);
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($url,$postvars)){
			$logger->error('無法連接前置廣告投放系統送出託播單API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
		}
		
		$result = json_decode($apiResult);
		$feedback=array();
		$feedback['feedback']=$apiResult;
		if($result->code==200){
			//再次查詢確認是否下架
			$url = $API852Url.'/mod/AD/api/vod';
			$byPost=array('ext'=>$result3['ext'],'ams_sid'=>$result3['版位識別碼'],'ams_vid'=>$_POST['託播單識別碼']);
			$postvars = http_build_query($byPost);
			// 建立CURL連線
			if(!$apiResult=connec_to_Api($url,$postvars)){
				$logger->error('無法連接前置廣告投放系統送出託播單API');
				exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
			}
			$checkResult = json_decode($apiResult);
			if($checkResult->code==200 && $checkResult->vod[0]->mark==0){
				recordResult('delete',1,null,null);
				changeOrderSate('取消送出',array($_POST["託播單識別碼"]));
			}
			else{
				recordResult('delete',0,null,null);
				exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
			}
		}
		else{
			$logger->error('前置廣告投放系統託播單取消錯誤:	code'.$result->code.' sataus'.$result->status.' '.$apiResult);
			recordResult('delete',0,null,$result->status);
			$feedback['success']=false;
			$feedback['message']='前置廣告投放系統託播單取消錯誤';
			$feedback['id']=$_POST["託播單識別碼"];
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));	
		}
	}
	
	//將回傳結果紀錄到資料庫
	function recordResult($action,$success,$result_inner,$result_outter){
		global $logger,$my;
		switch($action){
			case 'insert':
				$action = 1;
				break;
			case 'update':
				$action = 2;
				break;
			case 'delete':
				$action = 3;
				break;
		}
		$sql = "UPDATE 託播單 SET 託播單送出行為識別碼=?,託播單送出後是否成功=?,託播單送出後內部錯誤訊息=?,託播單送出後外部錯誤訊息=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=?";
		if(!$stmt=$my->prepare($sql)) {
		}
		if(!$stmt->bind_param('iissii',$action,$success,$result_inner,$result_outter,$_SESSION['AMS']['使用者識別碼'],$_POST['託播單識別碼'])) {
		}
		if(!$stmt->execute()) {
		}
	}
	
	//取得前置廣告系統得託播單資料
	function orderInfo_852(){
		global $logger, $my, $API852Url;
		$url = $API852Url.'/mod/AD/api/vod';
		$config = array();
		//取得版位類型介接參數
		$sql = 'SELECT 版位其他參數預設值,版位其他參數名稱,版位其他參數順序
			FROM 託播單,版位,版位其他參數
			WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 託播單識別碼 = ?
			';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST['託播單識別碼'])) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		while($row = $res->fetch_assoc()){
			$config[$row['版位其他參數名稱']]['value']=$row['版位其他參數預設值'];
			$config[$row['版位其他參數名稱']]['order']=$row['版位其他參數順序'];
		}
		//取得版位介接參數
		$sql = 'SELECT 託播單.版位識別碼,版位其他參數預設值,版位其他參數名稱
			FROM 託播單,版位,版位其他參數
			WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.版位識別碼 = 版位其他參數.版位識別碼 AND 託播單識別碼 = ?
			';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST['託播單識別碼'])) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		while($row = $res->fetch_assoc()){
			$config[$row['版位其他參數名稱']]['value']=$row['版位其他參數預設值'];
			$config['版位識別碼']=$row['版位識別碼'];
		}
		$byPost=array('ext'=>$config['ext']['value'],'ams_sid'=>$config['版位識別碼'],'ams_vid'=>$_POST['託播單識別碼']);
		$postvars = http_build_query($byPost);
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($url,$postvars)){
			$logger->error('無法連接前置廣告投放系統送出託播單API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
		}
		
		$result = json_decode($apiResult);
		if($result->code!=200){
			//失敗
			$logger->error('前置廣告投放系統託播單資料查詢錯誤:	code'.$result->code.' sataus'.$result->status);
		}
		else{
			$vod =$result->vod[0];
			//取得版位與版位類型名稱
			$sql = 'SELECT 版位.版位名稱, 版位類型.版位名稱 AS 版位類型名稱, 版位類型.版位識別碼 AS 版位類型識別碼
				FROM 版位, 版位 版位類型
				WHERE 版位.版位識別碼 = ? AND 版位.上層版位識別碼 = 版位類型.版位識別碼
				';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_sid),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$vod->ams_sid)) {
				exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_sid),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_sid),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_sid),JSON_UNESCAPED_UNICODE));
			}
			$row = $res->fetch_assoc();
			
			$orderInfo=array();
			$orderInfo['版位類型名稱'] = $row['版位類型名稱'];
			$orderInfo['版位類型識別碼'] = $row['版位類型識別碼'];
			$orderInfo['版位名稱'] = $row['版位名稱'];
			$orderInfo['版位識別碼'] = $vod->ams_sid;
			$orderInfo['託播單名稱'] = $vod->title;
			$orderInfo['廣告期間開始時間'] = $vod->starttime;
			$orderInfo['廣告期間結束時間'] = $vod->endtime;
			$orderInfo['廣告可被播出小時時段'] = '';
			$orderInfo['預約到期時間']='';
			$orderInfo['售價']='';
			$orderInfo['其他參數'][$config['影片排序']['order']]=$vod->sort;	
			$orderInfo['其他參數'][$config['全體投放次數上限']['order']]=$vod->up;	
			$orderInfo['其他參數'][$config['個人投放次數上限']['order']]=$vod->down;	
			$orderInfo['素材']=array();
			//計算播出時段
			$temp = explode(' ',$vod->starttime);
			$std = $temp[0];
			$stt = $temp[1];
			$temp = explode(' ',$vod->endtime);
			$edd = $temp[0];
			$edt = $temp[1];
			$hours=array();
			if($std!=$edd)
				$hours = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
			else{
				$stt = explode(':',$stt);
				$edt = explode(':',$edt);
				$edh = intval($edt[0]);
				if($edt[1] == '00' && $edt[1]== '00')
				$edh -=1;
				for( $h = intval($stt[0]);$h<=$edh;$h++){
					$hours[]=$h;
				}
				
			}
			$orderInfo['廣告可被播出小時時段'] = implode(',',$hours);
			//取得素材資訊
			if(isset($vod->hq)&&$vod->hq!=null){
				$sql = 'SELECT 素材名稱,素材順序,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM 託播單素材,素材
				WHERE 託播單素材.託播單識別碼 = ? AND 影片媒體編號 = ? AND 託播單素材.素材識別碼 = 素材.素材識別碼
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('is',$vod->ams_vid,$vod->hq)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
				}
				while($row = $res->fetch_assoc()){
					$orderInfo['素材'][$row['素材順序']]=array('素材識別碼'=>$row['素材識別碼'],'可否點擊'=>$row['可否點擊'],'點擊後開啟類型'=>$row['點擊後開啟類型'],
					'點擊後開啟位址'=>$row['點擊後開啟位址'],'素材名稱'=>$row['素材名稱']);
				}		
			}
			if(isset($vod->sd)&&$vod->sd!=null){
				$sql = 'SELECT 素材名稱,素材順序,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM 託播單素材,素材
				WHERE 託播單素材.託播單識別碼 = ? AND 影片媒體編號 = ? AND 託播單素材.素材識別碼 = 素材.素材識別碼
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('is',$vod->ams_vid,$vod->sd)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
				}

				while($row = $res->fetch_assoc()){
					$orderInfo['素材'][$row['素材順序']]=array('素材識別碼'=>$row['素材識別碼'],'可否點擊'=>$row['可否點擊'],'點擊後開啟類型'=>$row['點擊後開啟類型'],
					'點擊後開啟位址'=>$row['點擊後開啟位址'],'素材名稱'=>$row['素材名稱']);
				}		
			}
			if(isset($vod->hd)&&$vod->hd!=null){
				$sql = 'SELECT 素材名稱,素材順序,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM 託播單素材,素材
				WHERE 託播單素材.託播單識別碼 = ? AND 影片媒體編號 = ? AND 託播單素材.素材識別碼 = 素材.素材識別碼
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('is',$vod->ams_vid,$vod->hd)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
				}
				while($row = $res->fetch_assoc()){
					$orderInfo['素材'][$row['素材順序']]=array('素材識別碼'=>$row['素材識別碼'],'可否點擊'=>$row['可否點擊'],'點擊後開啟類型'=>$row['點擊後開啟類型'],
					'點擊後開啟位址'=>$row['點擊後開啟位址'],'素材名稱'=>$row['素材名稱']);
				}		
			}
			if(isset($vod->iaps)&&$vod->iaps!=null){
				$sql = 'SELECT 素材名稱,素材順序,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM 託播單素材,素材
				WHERE 託播單素材.託播單識別碼 = ? AND 影片媒體編號南 = ? AND 託播單素材.素材識別碼 = 素材.素材識別碼
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('is',$vod->ams_vid,$vod->iaps)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
				}
				while($row = $res->fetch_assoc()){
					$orderInfo['素材'][$row['素材順序']]=array('素材識別碼'=>$row['素材識別碼'],'可否點擊'=>$row['可否點擊'],'點擊後開啟類型'=>$row['點擊後開啟類型'],
					'點擊後開啟位址'=>$row['點擊後開啟位址'],'素材名稱'=>$row['素材名稱']);
				}		
			}
			if(isset($vod->iapn)&&$vod->iapn!=null){
				$sql = 'SELECT 素材名稱,素材順序,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM 託播單素材,素材
				WHERE 託播單素材.託播單識別碼 = ? AND 影片媒體編號北 = ? AND 託播單素材.素材識別碼 = 素材.素材識別碼
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('is',$vod->ams_vid,$vod->iapn)) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
				}
				while($row = $res->fetch_assoc()){
					$orderInfo['素材'][$row['素材順序']]=array('素材識別碼'=>$row['素材識別碼'],'可否點擊'=>$row['可否點擊'],'點擊後開啟類型'=>$row['點擊後開啟類型'],
					'點擊後開啟位址'=>$row['點擊後開啟位址'],'素材名稱'=>$row['素材名稱']);
				}		
			}
			
			exit(json_encode(array("success"=>true,'orderInfo'=>$orderInfo,'id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
	}
	
	function orderInfo_851(){
		global $logger, $my;
		$oData = $_POST['orderInfo'];
		//取的版位資料
		$sql = 'SELECT 版位.版位名稱,版位類型.版位識別碼 AS 版位類型識別碼
			FROM 版位,版位 版位類型
			WHERE 版位.版位識別碼 = ? AND 版位.上層版位識別碼 = 版位類型.版位識別碼
			';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$oData['版位識別碼'])) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		$row = $res->fetch_assoc();
		
		$orderInfo=array();
		$orderInfo['版位類型名稱'] = $oData['版位類型名稱'];
		$orderInfo['版位類型識別碼'] = $row['版位類型識別碼'];
		$orderInfo['版位名稱'] = $row['版位名稱'];
		$orderInfo['版位識別碼'] = $oData['版位識別碼'];
		$orderInfo['售價']='';
		$orderInfo['預約到期時間']='';
				
		$config = array();
		//取得版位類型介接參數
		$sql = 'SELECT 版位其他參數預設值,版位其他參數名稱,版位其他參數順序
			FROM 版位,版位其他參數
			WHERE 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 是否版位專用=0 AND 版位.版位識別碼 = ?
			';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$oData['版位識別碼'])) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		while($row = $res->fetch_assoc()){
			$config[$row['版位其他參數名稱']]['value']=$row['版位其他參數預設值'];
			$config[$row['版位其他參數名稱']]['order']=$row['版位其他參數順序'];
		}
		//取得版位介接參數
		$sql = 'SELECT 版位其他參數預設值,版位其他參數名稱,版位其他參數順序
			FROM 版位,版位其他參數
			WHERE 版位.版位識別碼 = 版位其他參數.版位識別碼 AND 是否版位專用=0 AND 版位.版位識別碼 = ?
			';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$oData['版位識別碼'])) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		while($row = $res->fetch_assoc()){
			$config[$row['版位其他參數名稱']]['value']=$row['版位其他參數預設值'];
			$config[$row['版位其他參數名稱']]['order']=$row['版位其他參數順序'];
		}
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$oData['版位識別碼'])) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$vod->ams_vid),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		$row = $res->fetch_assoc();
		
		if($oData['版位類型名稱']=="首頁banner"||$oData['版位類型名稱']=="專區banner"){
			$orderInfo['託播單名稱'] =$oData['AD_NAME'];
			$orderInfo['廣告期間開始時間'] = $oData['SCHD_START_DATE'];
			$orderInfo['廣告期間結束時間'] = $oData['SCHD_END_DATE'];
			$orderInfo['廣告可被播出小時時段'] = $oData['hours'];
			$orderInfo['其他參數'][$config['adType']['order']]=$oData['AD_TYPE'];
			//取得素材資訊
			$sql = 'SELECT 素材名稱	FROM 素材 WHERE 素材識別碼 = ?';
			if($res = $my->getResultArray($sql,'i',$oData['AD_CODE']))
				$materialName = $res[0]['素材名稱'];
			else 
				$materialName ='';
			$openAdd='';
			if($oData['LINK_TYPE']=='OVA_SERVICE')
				$openAdd=$oData['LINK_SRVC_RECID'];
			else if($oData['LINK_TYPE']=='OVA_CATEGORY')
				$openAdd=$oData['LINK_CAT_RECID'];
			else if($oData['LINK_TYPE']=='OVA_VOD_CONTENT')
				$openAdd=$oData['LINK_VODCNT_RECID'];	
			else if($oData['LINK_TYPE']=='OVA_CHANNEL')
				$openAdd=$oData['LINK_CHAN_RECID'];	
			$orderInfo['素材']=array(1=>array('素材識別碼'=>$oData['AD_CODE'],'可否點擊'=>($openAdd=='')?0:1,'點擊後開啟類型'=>$oData['LINK_TYPE'],'點擊後開啟位址'=>$openAdd,'素材名稱'=>$materialName));
		}
		else if($oData['版位類型名稱']=="專區vod"){
			$orderInfo['託播單名稱'] ='';
			$orderInfo['廣告期間開始時間'] = $oData['BAKADSCHD_START_DATE'];
			$orderInfo['廣告期間結束時間'] = $oData['BAKADSCHD_END_DATE'];
			$orderInfo['廣告可被播出小時時段'] = $oData['hours'];
			$orderInfo['其他參數'][$config['bakadDisplayMax']['order']]=$oData['BAKAD_DISPLAY_MAX'];
			$orderInfo['其他參數'][$config['bakadschdDisplayMax']['order']]=$oData['BAKADSCHD_DISPLAY_MAX'];
			$orderInfo['其他參數'][$config['bakadschdDisplaySequence']['order']]=$oData['BAKADSCHD_DISPLAY_SEQUENCE'];
			$orderInfo['其他參數'][$config['bannerTransactionId1']['order']]=$oData['TRANSACTION_ID1'];
			$orderInfo['其他參數'][$config['bannerTransactionId2']['order']]=$oData['TRANSACTION_ID2'];
			
			//取得版位類型影片畫質設定
			$fq=array();
			$sql = 'SELECT 素材順序,影片畫質名稱 FROM 版位素材類型,影片畫質 WHERE 版位識別碼 = ? AND 版位素材類型.影片畫質識別碼 = 影片畫質.影片畫質識別碼';
			if(!$res = $my->getResultArray($sql,'i',$orderInfo['版位類型識別碼']))$res=[];
			foreach($res as $row)
				$fq[$row['影片畫質名稱']]=$row['素材順序'];
				
			$openAdd='';
			if($oData['LINK_TYPE']=='OVA_SERVICE')
				$openAdd=$oData['LINK_SRVC_RECID'];
			else if($oData['LINK_TYPE']=='OVA_CATEGORY')
				$openAdd=$oData['LINK_CAT_RECID'];
			else if($oData['LINK_TYPE']=='OVA_VOD_CONTENT')
				$openAdd=$oData['LINK_VODCNT_RECID'];	
			else if($oData['LINK_TYPE']=='OVA_CHANNEL')
				$openAdd=$oData['LINK_CHAN_RECID'];	
			
			//取得素材資訊
			$sql = 'SELECT 素材名稱	FROM 素材 WHERE 素材識別碼 = ?';
			
			if($oData['SD_VODCNT_RECID']!=null){
				if($res = $my->getResultArray($sql,'i',$oData['SD_VODCNT_RECID']))
				$materialName = $res[0]['素材名稱'];
				else 
				$materialName ='';
				$orderInfo['素材']=array($fq['SD']=>array('素材識別碼'=>$oData['SD_VODCNT_RECID'],'可否點擊'=>($openAdd=='')?0:1,'點擊後開啟類型'=>$oData['LINK_TYPE'],'點擊後開啟位址'=>$openAdd,'素材名稱'=>$materialName));
			}else{
				if($res = $my->getResultArray($sql,'i',$oData['HD_VODCNT_RECID']))
				$materialName = $res[0]['素材名稱'];
				else 
				$materialName ='';
				$orderInfo['素材']=array($fq['HD']=>array('素材識別碼'=>$oData['HD_VODCNT_RECID'],'可否點擊'=>($openAdd=='')?0:1,'點擊後開啟類型'=>$oData['LINK_TYPE'],'點擊後開啟位址'=>$openAdd,'素材名稱'=>$materialName));
			}
		
		}
		else if($oData['版位類型名稱']=="頻道short EPG banner"){
			$orderInfo['託播單名稱'] =$oData['AD_NAME'];
			$orderInfo['廣告期間開始時間'] = $oData['SEPG_START_DATE'];
			$orderInfo['廣告期間結束時間'] = $oData['SEPG_END_DATE'];
			$orderInfo['廣告可被播出小時時段'] = $oData['hours'];
			$orderInfo['其他參數'][$config['adType']['order']]=$oData['AD_TYPE'];
			$orderInfo['其他參數'][$config['sepgDefaultFlag']['order']]=$oData['SEPG_DEFAULT_FLAG'];
			//取得素材資訊
			$sql = 'SELECT 素材名稱	FROM 素材 WHERE 素材識別碼 = ?';
			if($res = $my->getResultArray($sql,'i',$oData['AD_CODE']))
				$materialName = $res[0]['素材名稱'];
			else 
				$materialName ='';
			$openAdd='';
			if($oData['LINK_TYPE']=='OVA_SERVICE')
				$openAdd=$oData['LINK_SRVC_RECID'];
			else if($oData['LINK_TYPE']=='OVA_CATEGORY')
				$openAdd=$oData['LINK_CAT_RECID'];
			else if($oData['LINK_TYPE']=='OVA_VOD_CONTENT')
				$openAdd=$oData['LINK_VODCNT_RECID'];	
			else if($oData['LINK_TYPE']=='OVA_CHANNEL')
				$openAdd=$oData['LINK_CHAN_RECID'];	
			$orderInfo['素材']=array(1=>array('素材識別碼'=>$oData['AD_CODE'],'可否點擊'=>($openAdd=='')?0:1,'點擊後開啟類型'=>$oData['LINK_TYPE'],'點擊後開啟位址'=>$openAdd,'素材名稱'=>$materialName));
		}		
			
		exit(json_encode(array("success"=>true,'orderInfo'=>$orderInfo),JSON_UNESCAPED_UNICODE));
	}
	
		//產生851用的excel檔案
	function produceFile_851($action = null){
		global $API852Url,$logger,$my;
		if(!isset($action))
			$action = 'send';
		$sql='
			SELECT
				版位類型.版位名稱 版位類型名稱
			FROM
				託播單
				INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
				INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
			WHERE
				託播單.託播單識別碼=?
			';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST['託播單識別碼'])) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		$result=$res->fetch_assoc();
		switch($result['版位類型名稱']){
			case '首頁banner':
			case '專區banner':
				$forExcel[]=['transactionId','adCode','adType','adName','adLinkType','adLinkValue','adImgOff','adSizetype','serCode','bnrSequence','schdStartDate','schdEndDate','assignStartTime','assignEndTime'];
				break;
			case '頻道short EPG banner':
				$forExcel[]=['sepgTransactionId','sepgOvaChannel','adCode','adType','adName','adLinkType','adLinkValue','adImgOff','sepgDefaultFlag','sepgStartDate','sepgEndDate','sepgAssignStartTime','sepgAssignEndTime'];
				break;
			case '專區vod':
				$forExcel[]=['bakadschdTransactionId','sdVodcntTitle','hdVodcntTitle','bakadDisplayMax','linkType','linkValue','serCode','bakadschdStartDate','bakadschdEndDate','bakadschdAssignStartTime','bakadschdAssignEndTime'
				,'bakadschdDisplaySequence','bakadschdDisplayMax','bannerTransactionId1','bannerTransactionId2'];
				break;
		}
		$forExcel[]=getExcelData($_POST["託播單識別碼"]);
		//輸出
		OutputExcel::outputAll('851/'.$_POST["託播單識別碼"],$forExcel);
		//FTP上傳
		$localfile='../order/851/'.$_POST["託播單識別碼"].'.xls';			
		if(is_file($localfile)===false){
			exit(json_encode(array("success"=>false,'message'=>'找不到介接檔案'.$localfile.'，請重新派送。'.$本地路徑,'id'=>$_POST["託播單識別碼"])));
		}
		$uploadingMeta = getUploadingMeta($_POST["託播單識別碼"]);
		if($uploadingMeta['area']=='IAP'){
			recordResult(($action=='send')?'insert':'delete',1,null,null);
			changeOrderSate(($action=='send')?'送出':'取消送出',isset($_POST['ids'])?$_POST['ids']:array($_POST['託播單識別碼']));	
		}
		else{
			fileToFTP($result['版位類型名稱'],$uploadingMeta,$_POST["託播單識別碼"],$localfile,$action);		
			if(isset($_POST['ids']))
				changeOrderSate('待處理',$_POST['ids']);
			else
				changeOrderSate('待處理',array($_POST['託播單識別碼']));
		}
		
	}
	
	//批次送出
	function produceFileBetch_851($action = null){
		global $API852Url,$logger,$my;
		if(!isset($action))
			$action = 'send';
		//處理產生檔案用參數
		$forExcel=array();
		switch($_POST['ptName']){
			case '首頁banner':
			case '專區banner':
				$forExcel[]=['transactionId','adCode','adType','adName','adLinkType','adLinkValue','adImgOff','adSizetype','serCode','bnrSequence','schdStartDate','schdEndDate','assignStartTime','assignEndTime'];
				break;
			case '頻道short EPG banner':
				$forExcel[]=['sepgTransactionId','sepgOvaChannel','adCode','adType','adName','adLinkType','adLinkValue','adImgOff','sepgDefaultFlag','sepgStartDate','sepgEndDate','sepgAssignStartTime','sepgAssignEndTime'];
				break;
			case '專區vod':
				$forExcel[]=['bakadschdTransactionId','sdVodcntTitle','hdVodcntTitle','bakadDisplayMax','linkType','linkValue','serCode','bakadschdStartDate','bakadschdEndDate','bakadschdAssignStartTime','bakadschdAssignEndTime'
				,'bakadschdDisplaySequence','bakadschdDisplayMax','bannerTransactionId1','bannerTransactionId2'];
				break;
		}
		
		if($_POST['ptName'] == '頻道short EPG banner'){
			$temp = [];
			foreach($_POST['ids'] as $id){
				if(count($temp)==0)
					$temp = getExcelData($id);
				else{
					$index = array_search('sepgOvaChannel', $forExcel[0]);
					$temp[$index].=','.getExcelData($id)[$index];
				}
			}
			$forExcel[]=$temp;
		}
		else
		foreach($_POST['ids'] as $id){
			$forExcel[]=getExcelData($id);
		}
		
		//輸出
		OutputExcel::outputAll('851/'.implode(',',$_POST['ids']),$forExcel);
		//FTP上傳
		$ids = implode(',',$_POST['ids']);
		$localfile='../order/851/'.$ids.'.xls';			
		if(is_file($localfile)===false){
			exit(json_encode(array("success"=>false,'message'=>'找不到介接檔案，請重新派送。'.$本地路徑,'id'=>$ids)));
		}
		$uploadingMeta = getUploadingMeta($_POST['ids'][0]);
		if($uploadingMeta['area']=='IAP'){
			recordResult(($action=='send')?'insert':'delete',1,null,null);
			changeOrderSate(($action=='send')?'送出':'取消送出',$_POST['ids']);
		}
		else{
			fileToFTP($_POST['ptName'],$uploadingMeta,$ids,$localfile,$action);
			changeOrderSate('待處理',$_POST['ids']);
		}
	}
	
	function fileToFTP($ptN,$uploadingMeta,$ids,$localfile,$action){
		switch($ptN){
			case '首頁banner':
			case '專區banner':
				$fileName = 'csad';
				break;
			case '頻道short EPG banner':
				$fileName = 'sepg';
				break;
			case '專區vod':
				$fileName = 'barkerad';
				break;
		}
		$CSMSFTP =Config::$FTP_SERVERS['CSMS_'.$uploadingMeta['area']];
		//送出檔案
		if($action == 'send')
			$remotefile = $CSMSFTP[0]['待處理資料夾路徑'].'/'.$fileName.'.'.$uploadingMeta['sendAction'].'.'.$uploadingMeta['gId'].'.xls';
		else
			$remotefile = $CSMSFTP[0]['待處理資料夾路徑'].'/'.$fileName.'.delete.'.$uploadingMeta['gId'].'.xls';
		$result=FTP::putAll($CSMSFTP,$localfile,$remotefile);
		$downloadfile = '../order/851/'.$ids.'_check.xls' ;
		if(!FTP::get($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],$downloadfile,'./'.$remotefile)){
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗','id'=>$ids)));
		}
		if(!PHPExtendFunction::isFilesSame($localfile,$downloadfile))
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗','id'=>$ids)));
		//刪除下載回來比較的檔案
		unlink($downloadfile);
		//刪除本地檔案
		//unlink($localfile);
		if(!FTP::rename($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],'./'.$remotefile,'./'.$remotefile.'.fin'))
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗:檔案已存在','id'=>$ids)));
	}
	
	//取得excel檔案資料
	function getExcelData($id){
		global $API852Url,$logger,$my;
		//先取得託播單資訊與對應素材資訊
			$sql='
				SELECT
					版位類型.版位名稱 版位類型名稱,
					託播單.託播單識別碼,
					託播單.託播單CSMS群組識別碼,
					託播單名稱,
					素材.素材識別碼,
					廣告期間開始時間,
					廣告期間結束時間,
					託播單素材.點擊後開啟類型,
					託播單素材.點擊後開啟位址,
					託播單狀態識別碼,
					廣告可被播出小時時段,
					影片畫質識別碼
				FROM
					託播單
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
					INNER JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼
					INNER JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單.託播單識別碼=?
			';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$id),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$id)) {
				exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$id),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$id),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
			}
			$result1=$res->fetch_assoc();			
			$result1_1=$res->fetch_assoc();	//有可能有兩筆素材，一筆為SD、另一筆為HD。
			//再取得版位類型、版位、託播單其他參數，並依序被取代。
			$sql='
				SELECT
					版位類型其他參數.版位其他參數名稱 版位類型其他參數名稱,
					版位類型其他參數.版位其他參數預設值 版位類型其他參數預設值,
					版位其他參數.版位其他參數預設值,
					託播單其他參數.託播單其他參數值
				FROM
					託播單
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
					INNER JOIN 版位其他參數 版位類型其他參數 ON 版位類型其他參數.版位識別碼=版位類型.版位識別碼
					LEFT JOIN 版位其他參數 ON 版位其他參數.版位識別碼=版位.版位識別碼 AND 版位其他參數.版位其他參數順序=版位類型其他參數.版位其他參數順序
					LEFT JOIN 託播單其他參數 ON 託播單其他參數.託播單識別碼=託播單.託播單識別碼 AND 託播單其他參數.託播單其他參數順序=版位類型其他參數.版位其他參數順序
				WHERE
					託播單.託播單識別碼=?
				ORDER BY
					版位類型其他參數.版位其他參數順序
			';
			$result2=$my->getResultArray($sql,'i',$id);
			$result3=array();
			foreach($result2 as $row){
				$result3[$row['版位類型其他參數名稱']]=$row['版位類型其他參數預設值'];
				if($row['版位其他參數預設值']!=null) $result3[$row['版位類型其他參數名稱']]=$row['版位其他參數預設值'];
				if($row['託播單其他參數值']!=null) $result3[$row['版位類型其他參數名稱']]=$row['託播單其他參數值'];
			}
			
			/*若24個時段同時出現，代表全天；
			否則，若0,23同時出現，代表跨日；
			否則，即不跨日。*/
			$hours=explode(',',$result1['廣告可被播出小時時段']);
			$hours2=array();
			foreach($hours as $hour){
				$hours2[intval($hour)]=intval($hour);
			}
			if(count($hours)===24){
				$startTime='00:00';
				$endTime='24:00';
			}
			else if(array_search('0',$hours)!==false&&array_search('23',$hours)!==false){
				for($i=1;$i<23;$i++){
					if(!isset($hours2[$i])){
						$endTime=sprintf('%02d',$i).':00';
						break;
					}
				}
				for($i=22;$i>0;$i--){
					if(!isset($hours2[$i])){
						$startTime=sprintf('%02d',($i+1)).':00';
						break;
					}
				}
			}
			else{
				for($i=0;$i<23;$i++){
					if(isset($hours2[$i])){
						$startTime=sprintf('%02d',$i).':00';
						break;
					}
				}
				for($i=23;$i>0;$i--){
					if(isset($hours2[$i])){
						$endTime=sprintf('%02d',($i+1)).':00';
						break;
					}
				}
			}
			//處理日期	
			//開始日期
			$tpdate = new DateTime($result1['廣告期間開始時間']);
			$result1['廣告期間開始時間'] = $tpdate->format('Y/m/d H:i');
			//結束日期
			$tpdate = new DateTime($result1['廣告期間結束時間']);
			//結束時間不為00秒，增加一分鐘
			$endTimeA =explode(':',$result1['廣告期間結束時間']);
			if(end($endTimeA)!='00')
				$tpdate->add(new DateInterval('PT1M'));
			$result1['廣告期間結束時間'] = $tpdate->format('Y/m/d H:i');
			
			if($result1['點擊後開啟類型']=='')
			$result1['點擊後開啟類型']='NONE';
			
			switch($result1['版位類型名稱']){
				case '首頁banner':
				case '專區banner':
					//獲得素材adCode
					$sql = 'SELECT 素材識別碼,素材原始檔名,產業類型名稱
							FROM 素材,產業類型
							WHERE 素材.產業類型識別碼 = 產業類型.產業類型識別碼 AND 素材.素材識別碼 = ?
						';
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('i',$result1['素材識別碼'])) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					$materialInfo=$res->fetch_assoc();
					$adCode = $materialInfo['產業類型名稱'].str_pad($materialInfo['素材識別碼'], 8, '0', STR_PAD_LEFT);
					$mNameA =explode('.',$materialInfo['素材原始檔名']);
					$type = end($mNameA);
					$data=[$result1['託播單CSMS群組識別碼'],$adCode,$result3['adType'],$result1['託播單名稱'],$result1['點擊後開啟類型'],$result1['點擊後開啟位址'],'_____AMS_'.$result1['素材識別碼'].'.'.$type
						,$result3['adSizetype'],$result3['serCode'],$result3['bnrSequence'],$result1['廣告期間開始時間'],$result1['廣告期間結束時間'],$startTime,$endTime];
					break;
					
				case '頻道short EPG banner':
					//獲得素材adCode
					$sql = 'SELECT 素材識別碼,素材原始檔名,產業類型名稱
							FROM 素材,產業類型
							WHERE 素材.產業類型識別碼 = 產業類型.產業類型識別碼 AND 素材.素材識別碼 = ?
						';
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('i',$result1['素材識別碼'])) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					$materialInfo=$res->fetch_assoc();
					$adCode = $materialInfo['產業類型名稱'].str_pad($materialInfo['素材識別碼'], 8, '0', STR_PAD_LEFT);
					$fileNameA = explode('.',$materialInfo['素材原始檔名']);
					$type = end($fileNameA);
					$data=[$result1['託播單CSMS群組識別碼'],$result3['sepgOvaChannel'],$adCode,$result3['adType'],$result1['託播單名稱'],$result1['點擊後開啟類型'],$result1['點擊後開啟位址']
					,'_____AMS_'.$result1['素材識別碼'].'.'.$type,$result3['sepgDefaultFlag'],$result1['廣告期間開始時間'],$result1['廣告期間結束時間'],$startTime,$endTime];
					break;
					
				case '專區vod':
					$sql = 'SELECT 素材原始檔名
							FROM 素材
							WHERE 素材.素材識別碼 = ?
						';
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('i',$result1_1['素材識別碼'])) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					$mInfo2 = $res->fetch_assoc();
					
					$SD影片=($result1['影片畫質識別碼']===1?$result1['素材識別碼']:(isset($result1_1['影片畫質識別碼'])?($result1_1['影片畫質識別碼']===1?$result1_1['素材識別碼']:null):null));
					$HD影片=($result1['影片畫質識別碼']===2?$result1['素材識別碼']:(isset($result1_1['影片畫質識別碼'])?($result1_1['影片畫質識別碼']===2?$result1_1['素材識別碼']:null):null));
					if($SD影片!=null){
						$sql = 'SELECT 素材原始檔名
								FROM 素材
								WHERE 素材.素材識別碼 = ?
							';
						if(!$stmt=$my->prepare($sql)) {
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						if(!$stmt->bind_param('i',$SD影片)) {
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						if(!$stmt->execute()) {
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						if(!$res=$stmt->get_result()){
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						$mInfo = $res->fetch_assoc();
						$fileNameA=explode('.',$mInfo['素材原始檔名']);
						$type = end($fileNameA);
						//*!*!*!staging 取代
						$SD影片 = '_____AMS_'.$SD影片.'_'.md5_file('../material/uploadedFile/'.$SD影片.'.'.$type);
						//$SD影片 = '_____AMS_24_dc433015e5a1f26282b5fcc08000a1dc';
						//*!*!*!staging end
					}
					
					if($HD影片!=null){
						$sql = 'SELECT 素材原始檔名
								FROM 素材
								WHERE 素材.素材識別碼 = ?
							';
						if(!$stmt=$my->prepare($sql)) {
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						if(!$stmt->bind_param('i',$HD影片)) {
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						if(!$stmt->execute()) {
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						if(!$res=$stmt->get_result()){
							exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
						}
						$mInfo = $res->fetch_assoc();
						$fileNameA = explode('.',$mInfo['素材原始檔名']);
						$type = end($fileNameA);
						//*!*!*!staging 取代
						$HD影片 = '_____AMS_'.$HD影片.'_'.md5_file('../material/uploadedFile/'.$HD影片.'.'.$type);
						//$HD影片 = '_____AMS_24_dc433015e5a1f26282b5fcc08000a1dc';
						//*!*!*!staging end
					}
					$data=[$result1['託播單CSMS群組識別碼'],$SD影片,$HD影片,$result3['bakadDisplayMax'],$result1['點擊後開啟類型'],$result1['點擊後開啟位址'],$result3['serCode'],$result1['廣告期間開始時間'],$result1['廣告期間結束時間']
					,$startTime,$endTime,$result3['bakadschdDisplaySequence'],$result3['bakadschdDisplayMax'],$result3['bannerTransactionId1'],$result3['bannerTransactionId2']
					];
					break;
			}			
			return $data;
	}
	
	function getUploadingMeta($id){
		global $my;
		$sql='
			SELECT
				A.託播單CSMS群組識別碼,A.廣告可被播出小時時段,A1.版位名稱,A.託播單送出行為識別碼,A.託播單送出後是否成功
			FROM
				託播單 A LEFT JOIN 版位 A1 ON A1.版位識別碼 = A.版位識別碼
			WHERE
				A.託播單識別碼=?
		';
		$res=$my->getResultArray($sql,'i',$id);
		$hours = explode(',',$res[0]['廣告可被播出小時時段']);
		if(count($hours)==24){
			$startTime = '00'; $endTime='23';
		}
		else{
			$startTime = sprintf('%02s',$hours[0]); $endTime=sprintf('%02s',end($hours));
			if($startTime=='00'&& $endTime=='23'){
				for($i=1;$i<23;$i++){
					if(intval($hours[$i])-1!=intval($hours[$i-1])){
						$endTime=sprintf('%02s',$hours[$i-1]).':00';
						break;
					}
				}
				for($i=22;$i>0;$i--){
					if(intval($hours[$i])+1!=intval($hours[$i+1])){
						$startTime=sprintf('%02s',$hours[$i+1]).':00';
						break;
					}
				}
			}
		}
		
		//判斷版位區域
		$pName = $res[0]['版位名稱'];
		if(PHPExtendFunction::stringEndsWith($pName,'_北'))
		$area = 'N';
		else if(PHPExtendFunction::stringEndsWith($pName,'_中'))
		$area = 'C';
		else if(PHPExtendFunction::stringEndsWith($pName,'_南'))
		$area = 'S';
		else if(PHPExtendFunction::stringEndsWith($pName,'_IAP'))
		$area = 'IAP';
		
		$sendAction = 'insert';
		
		if($res[0]['託播單送出行為識別碼']!= null){
			switch($res[0]['託播單送出行為識別碼']){
				case 1:
					break;
				case 2:
					if($res[0]['託播單送出後是否成功']==0)
						$sendAction = 'update';
					break;
				case 3:
					if($res[0]['託播單送出後是否成功']==1)
						$sendAction = 'update';
					break;
			}
		}
		return array('area'=>$area,'sendAction'=>$sendAction,'gId'=>$res[0]['託播單CSMS群組識別碼'],'startTime'=>$startTime,'endTime'=>$endTime);
	}
	
	//更改資料庫訂單狀態
	function changeOrderSate($state,$idArray){
		global $logger,$my;
		foreach($idArray as $id){
			switch($state){
				case '送出':
					$sql = "UPDATE 託播單 SET 託播單狀態識別碼=2,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=?";
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$id)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')送出託播單(識別碼'.implode(',',$idArray).')');
					break;
				case '取消送出':
					$sql = "UPDATE 託播單 SET 託播單狀態識別碼=1,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=?";
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$id)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')取消送出託播單(識別碼'.implode(',',$idArray).')');
					break;
				case '待處理':
					$sql = "UPDATE 託播單 SET 託播單狀態識別碼=4,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=?";
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$id)) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
					}
					$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')產生託播單EXCLE檔(識別碼'.implode(',',$idArray).')');
					break;
			}				
		}	
		switch($state){
			case '送出':
				//$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')送出託播單(識別碼'.implode(',',$idArray).')');
				exit (json_encode(array("success"=>true,'message'=>"託播單已送出",'id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
				break;
			case '取消送出':
				//$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')取消送出託播單(識別碼'.implode(',',$idArray).')');
				exit (json_encode(array("success"=>true,'message'=>"取消託播單送出",'id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
				break;
			case '待處理':
				//$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')產生託播單EXCLE檔(識別碼'.implode(',',$idArray).')');
				exit (json_encode(array("success"=>true,'message'=>"檔案已產生、等待處理",'id'=>implode(',',$idArray)),JSON_UNESCAPED_UNICODE));
				break;
		}
	}
	
	exit ;
	
?>