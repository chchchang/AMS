<?php
	//20230223 將2022世足廣告更名為運動專區外掛廣告
	//20221006 將2021奧運廣告版未類型更動為2022世足
	//20220510 增加素材尚未派送的檢查機制
	//前置設定
	include('../tool/auth/authAJAX.php');
	include('checkIfMaterialSyn.php');
	require '../tool/OutputExcel.php';
	require '../tool/phpExtendFunction.php';
	require '../tool/FTP.php';
	require_once dirname(__FILE__)."/../apiProxy/AmsDb/module/ReplaceOrderInPlaylist.php";

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
				require_once 'ajaxToAPIMoudle/ajaxToAPI_CSMS.php';
				produceFileBetch_851(isset($_POST['APIAction'])?$_POST['APIAction']:'send');
				break;
			case "851託播單資料":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_CSMS.php';
				orderInfo_851();
				break;
			case "群組託播單":
				groupingOrder();
				break;
			case "批次產生檔案"://csms
				require_once 'ajaxToAPIMoudle/ajaxToAPI_CSMS.php';
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
			case "Vod+廣告":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_CSMS.php';
				produceFile_851('send');
				break;
			case 'barker頻道':
			case '破口廣告':
				$replacer = new ReplaceOrderInPlaylist();
				if(	$replacer->fixBarkerPlaylistOverlapPeroidByOrderId($_POST["託播單識別碼"])){
					recordResult('insert',1,null,null);
					changeOrderSate('送出',array($_POST["託播單識別碼"]));
				}
				else{
					recordResult('insert',false,"修正重疊走期重疊失敗",null);
					exit(json_encode(array("success"=>false,"message"=>'修正重疊走期重疊失敗','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
				}
				break;
			//2023 11 23 使用新pumping server後不需向CAMPS送出託播
			case "單一平台banner":
			case "單一平台barker_vod":
			case "單一平台EPG":
			case "單一平台marquee":
			case "單一平台background_banner":
			case "單一平台advertising_page":
			case "單一平台floating_banner":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_VSM.php';
				sendOrder_VSM($_POST["託播單識別碼"]);
				break;
			case "鑽石版位":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_diamond.php';
				sendOrder_diamond($_POST["託播單識別碼"]);
				break;
			case "Vod插廣告":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_VodAds.php';
				sendOrder_VodAds($_POST["託播單識別碼"]);
				break;
			//case "奧運外掛專區廣告_2021":
				case "運動賽事外掛廣告"://延用2021奧運廣告外掛
				require_once 'ajaxToAPIMoudle/ajaxToAPI_Olympic2021.php';
				sendOrder_olympic($_POST["託播單識別碼"]);
				break;
			case "在地專區大BANNER廣告":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_localBigBanner.php';
				sendOrder_localBigBanner($_POST["託播單識別碼"]);
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
		if(isset($_POST['selectedOrder']))
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
		global $logger;
		$CSMSPTN = ['首頁banner','專區banner','專區vod','頻道short EPG banner','Vod+廣告'];
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
		//檢查是否未填必選素材
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
		
		//barker頻道不檢查素材
		if($row['版位類型名稱']=='barker頻道' || $row['版位類型名稱']=='破口廣告' || $row['版位類型名稱']=='三碼快速鍵'){
			return array("success"=>true,"message"=>'success');
		}
		
		if($row['未填必填素材筆數']!=0)
			return array("success"=>false,"message"=>'必選素材未選擇。');
		
		//檢查是否有選擇素材
		$sql='
			SELECT
				COUNT(1) MaterialCount
			FROM
				託播單素材 
			WHERE
				託播單識別碼=?
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
		$res=$res->fetch_assoc();
		if($res['MaterialCount']==0)
			return array("success"=>false,"message"=>'未選擇任何素材。');
		
		//逐一檢查素材	
		$sql = "SELECT 素材原始檔名,素材.素材識別碼,素材類型名稱,影片媒體編號,圖片素材派送結果,CAMPS影片媒體編號
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
				if($row['版位類型名稱']=='前置廣告投放系統' || $row['版位類型名稱']=='專區vod' || $row['版位類型名稱']=='單一平台barker_vod'){
					if($row2['影片媒體編號']==null || $row2['影片媒體編號']==''){
						return array("success"=>false,"message"=>'素材尚未派送至自動派片系統');
					}
					$explodeFileName=explode(".",$row2['素材原始檔名']);
					$fileName= Config::GET_MATERIAL_FOLDER().$row2['素材識別碼'].".".$explodeFileName[count($explodeFileName)-1];
					/*$exists= file_exists($fileName);
					if(!$exists){
						return array("success"=>false,"message"=>'素材尚未派送');
					}else{*/
						$片名='_____AMS_'.$row2['素材識別碼'].'_'.md5_file($fileName);
						//$url='http://172.17.251.83:82/PTS/pts_media_status.php?v_id=2305&source='.$片名;
						$url=Config::PMS_SEARCH_URL.$片名;
						$ch=curl_init($url);
						curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
						$xmlString= curl_exec($ch);
						if(curl_errno($ch))
						{
							$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
							curl_close($ch);
						}
						//移除多餘空白字元
						$xmlString = preg_replace('~\s*(<([^-->]*)>[^<]*<!--\2-->|<[^>]*>)\s*~','$1',$xmlString);
						$xml=simplexml_load_string($xmlString);
						$mediaId=(string)$xml->mediaId;
						if($area == 'N')
							$status=(string)$xml->chtnStatus;
						else if($area == 'C')
							$status=(string)$xml->chtcStatus;
						else if($area == 'S')
							$status=(string)$xml->chtsStatus;	
						else
							$status = 1;
							
						if(intval($status)==0)
							return array("success"=>false,"message"=>'對應區域的伺服器尚未派送素材');
						
					//}
				}
				//barker不檢查素材
			}else if($row2['素材類型名稱']=='圖片'){
				$picRes = json_decode($row2['圖片素材派送結果']);
				//版位類型
				if($row['版位類型名稱']=='頻道short EPG banner')
					$type = 'EPG';
				else if($row['版位類型名稱']=='專區banner' || $row['版位類型名稱']=='首頁banner')
					$type = '專區';
				else if(substr($row['版位類型名稱'],0,12)=='單一平台'){
					if(count($picRes) == 0){
						return array("success"=>false,"message"=>'素材尚未派送');
					}
					
					require_once '../tool/SFTP.php';
					$expiredDate = date("Y-m-d",strtotime("-3 Months"));

					foreach(Config::$FTP_SERVERS['VSM'] as $server){
						if(!in_array($server["host"], $picRes)){
							return array("success"=>false,"message"=>'素材尚未派送到VSM');
						}
						$remotePath=$server['圖片素材路徑'];
						$fileNamePatterns = explode(".",$row2['素材原始檔名']);
						$fileName ='_____AMS_'.$row2['素材識別碼'].'.'.end($fileNamePatterns);
						$isfile =SFTP::isFile($server['host'],$server['username'],$server['password'],$remotePath.$fileName);
						if(!$isfile){
							return array("success"=>false,"message"=>'素材尚未派送到VSM');
						}
						$lastMDate = date("Y-m-d", SFTP::getFileModifiedTime($server['host'],$server['username'],$server['password'],$remotePath.$fileName));
						if($expiredDate>=$lastMDate){
							return array("success"=>false,"message"=>'素材過舊，請重新派送。');
						}
					}
					return array("success"=>true,"message"=>'success');
				}else if($row['版位類型名稱']=='鑽石版位'||$row['版位類型名稱']=='運動賽事外掛廣告'||$row['版位類型名稱']=='在地專區大BANNER廣告')
				{
					//鑽石版位廣告送出時會同時派送素材，不需檢查
					//奧運外掛圖片不需檢查
					return array("success"=>true,"message"=>'success');
				}
				else if($area=='IAP')
				{
					//IAP版位目前沒有素材伺服器，不需檢查
					return array("success"=>true,"message"=>'success');
				}
				else{//OPM
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
			}
			
			//若為CSMS類型版位，確認OMP資料庫是否可以讀取到該素材
			if(in_array($row['版位類型名稱'],$CSMSPTN)){
				$byPost=[
					'素材識別碼'=>$row2['素材識別碼'],
					'區域'=>$area
					];
				/*$checkResult = json_decode(checkIfMaterialSyn::checkIfSyn($byPost));
				if(!$checkResult->success)
					return array("success"=>false,"message"=>'CSMS尚未同步素材');*/
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
			case "Vod+廣告":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_CSMS.php';
				produceFile_851('delete');
				break;
			case 'barker頻道':
			case '破口廣告':
				//2023 11 23 使用新pumping server後不需向CAMPS送出託播單，但需修正palylist重疊走期
				/*require_once 'ajaxToAPIMoudle/ajaxToAPI_CAMPS.php';
				cancelOrder_CAMPS($_POST["託播單識別碼"]);*/
				$replacer = new ReplaceOrderInPlaylist();
				if(	$replacer->markPlaylistAsNoOverlappingPeriodByOrderId($_POST["託播單識別碼"])){
					recordResult('delete',1,null,null);
					changeOrderSate('取消送出',array($_POST["託播單識別碼"]));
				}
				else{
					recordResult('delete',false,"修正重疊走期重疊失敗",null);
					exit(json_encode(array("success"=>false,"message"=>'修正重疊走期重疊失敗','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
				}
				break;
			
			case "單一平台banner":
			case "單一平台barker_vod":
			case "單一平台marquee":
			case "單一平台background_banner":
			case "單一平台advertising_page":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_VSM.php';
				cancelOrder_VSM($_POST["託播單識別碼"]);
				break;
			case "單一平台EPG":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_VSM.php';
				cancelEPGOrder_VSM($_POST["託播單識別碼"]);
					break;
			case "鑽石版位":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_diamond.php';
				cancelOrder_diamond($_POST["託播單識別碼"]);
				break;
			case "Vod插廣告":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_VodAds.php';
				cancelOrder_VodAds($_POST["託播單識別碼"]);
				break;
			//case "奧運外掛專區廣告_2021":
			case "運動賽事外掛廣告"://延用2021奧運廣告外掛
				require_once 'ajaxToAPIMoudle/ajaxToAPI_Olympic2021.php';
				cancelOrder_olympic($_POST["託播單識別碼"]);
					break;
			case "在地專區大BANNER廣告":
				require_once 'ajaxToAPIMoudle/ajaxToAPI_localBigBanner.php';
				cancelOrder_localBigBanner($_POST["託播單識別碼"]);
				break;
			default:{
				recordResult('delete',1,null,null);
				changeOrderSate('取消送出',array($_POST["託播單識別碼"]));
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
		//簡查是否有使用HD素材
		if(mysqli_num_rows($res)>1)
			$result1HD=$res->fetch_assoc();
		
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
		$orderByPost=
		array(
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
			'iapn'=>$result1['影片媒體編號北'],
			'hd'=>""  //2021-12-09 強制塞入空白hd素材編號，避免取消HD素材時沒有更新到投放系統
		);
		if(isset($result1HD['影片媒體編號']))
			$orderByPost['hd'] = $result1HD['影片媒體編號'];
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
		if(!$apiResult=connec_to_Api($checkurl,'POST',$checkPostvars)){
			$logger->error('無法連接前置廣告投放系統送出託播單API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接前置廣告投放系統送出託播單API','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult);
		if($checkResult->code==200){
			if(count($checkResult->vod)!=0){
				//存在，使用update
				$url = $API852Url.'/mod/AD/api/vod/update';
				// 建立CURL連線
				if(!$apiResult=connec_to_Api($url,'POST',$postvars)){
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
				if(!$apiResult=connec_to_Api($url,'POST',$postvars)){
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
		if(!$apiResult=connec_to_Api($checkurl,'POST',$checkPostvars)){
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
		if(!$apiResult=connec_to_Api($url,'POST',$postvars)){
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
			if(!$apiResult=connec_to_Api($url,'POST',$postvars)){
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
		if(!$apiResult=connec_to_Api($url,'POST',$postvars)){
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
	
	//更改資料庫訂單狀態
	function changeOrderSate($state,$idArray){
		global $logger,$my;
		foreach($idArray as $id){
			switch($state){
				case '送出':
					$sql = "UPDATE 託播單 SET 託播單狀態識別碼=2,託播單.託播單需重新派送 = 0,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=?";
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