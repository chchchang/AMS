<?php
	date_default_timezone_set("Asia/Taipei");
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/tool/MyDB.php';
	require_once dirname(__FILE__).'/tool/MyLogger.php';
	require_once dirname(__FILE__).'/tool/phpExtendFunction.php';
	define('HOME','/home/ams/IabFiles/');
	//define('HOME','order/851/');
	class EdgewareConfig
	{	
		//const DB_HOST='localhost';
		const DB_HOST='172.16.72.52:3306';
		//const DB_USER='root';		
		const DB_USER='barkeradmin';		
		//const DB_PASSWORD='root';
		const DB_PASSWORD='barkeradmin';
		const DB_NAME='Edgeware';	
		const UTC_HOFFSET=0;
	}
		
	if(isset($_GET["DATE"]))
		$searchDate = $_GET["DATE"];
	else if(isset($argv[1]))
		$searchDate = $argv[1];
	else
		$searchDate = date("Y-m-d",strtotime("-1 days"));
	//$searchDate = date("Y-m-d");
	$st = $searchDate." 00:00:00";
	$et = $searchDate." 24:00:00";
	
	//切換時區與計算offset
	$UTC = new DateTimeZone("UTC");
	$AsiaTaipei = new DateTimeZone("Asia/Taipei");
	$utcst =  new DateTime($st);
	$utcst->setTimezone( $UTC );
	$utcet =  new DateTime($et);
	$utcet->setTimezone( $UTC );
	$offsetInterval = 'PT'.abs(EdgewareConfig::UTC_HOFFSET).'H';	
	if(EdgewareConfig::UTC_HOFFSET<0){
		$utcst->sub(new DateInterval($offsetInterval));
		$utcet->sub(new DateInterval($offsetInterval));
	}
	else{
		$utcst->add(new DateInterval($offsetInterval));
		$utcet->add(new DateInterval($offsetInterval));
	}
	
	$utcst = $utcst->format('Y-m-d H:i:s');
	$utcet = $utcet->format('Y-m-d H:i:s');
	
	$my=new mysqli(EdgewareConfig::DB_HOST,EdgewareConfig::DB_USER,EdgewareConfig::DB_PASSWORD,EdgewareConfig::DB_NAME);
	if($my->connect_errno) {
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}

	if(!$my->set_charset('utf8')) {
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
	//取得資料
	echo 'Orbit排程資訊</br>';
	$sql = '
		SELECT Playout.id AS playout_id ,PlaylistClip.id AS playlist_id, PlaylistClip.asset_id, PlaylistClip.length,start_time,stop_time,PlayoutEvent.id AS playoutEid,dest_addr,dest_port
		FROM Playout
		JOIN PlayoutServer ON Playout.id = PlayoutServer.playout_id
		JOIN PlayoutEvent ON Playout.id = PlayoutEvent.playout_id
		JOIN ScheduleEvent ON ScheduleEvent.schedule_id = PlayoutEvent.schedule_id
		JOIN Playlist ON ScheduleEvent.playlist_id = Playlist.id
		JOIN PlaylistClip ON PlaylistClip.playlist_id = ScheduleEvent.playlist_id
		WHERE (? BETWEEN start_time AND stop_time ) OR (? BETWEEN start_time AND stop_time ) OR (start_time BETWEEN ? AND ? )  
		ORDER BY playout_id,playlist_id
	';
	
	
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
				
	if(!$stmt->bind_param('ssss',$utcst,$utcet,$utcst,$utcet)) {
		exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}

	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}

	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}

	$feedBack= array();
	$palyoutSeconds=[];
	$palyouthours=[];
	$adBuffer_hours=[];//每小時播出紀錄
	$fullPlayoutTime = false;
	while($row=$res->fetch_assoc()){
		
		if(!isset($palyouthours[$row['playoutEid']])||$palyouthours[$row['playoutEid']] != $row['start_time']){
			//第一個小時
			//進入新的playout
			//前一playout時間未滿且有前一個的播放紀錄
			if(!$fullPlayoutTime&&count($adBuffer_hours)>0){
				while(!$fullPlayoutTime)
				foreach($adBuffer_hours as $ad){
					//計算開始時間
					$tst = new DateTime($ad['start_time'], $UTC);
					$addInterval = 'PT'.round($palyoutSeconds[$ad['playoutEid']]).'S';
					$tst->add(new DateInterval($addInterval));
					
					//計算節束時間
					$palyoutSeconds[$ad['playoutEid']]+=$ad['length'];
					$tet = new DateTime($ad['start_time'], $UTC);
					$addInterval = 'PT'.round($palyoutSeconds[$ad['playoutEid']]).'S';
					$tet->add(new DateInterval($addInterval));
					//檢查結束時間是否超過playoutEvent的stopTime
					$tetString = $tet->format('Y-m-d H:i:s');
					if($tetString>$ad['stop_time']){
						$tet = new DateTime($ad['stop_time'], $UTC);
						$fullPlayoutTime = true;
					}
					
					//將時區從UTC切會回AsiaTaipei
					$tst->setTimezone( $AsiaTaipei );
					$tet->setTimezone( $AsiaTaipei );
					//取得時間strign
					$tstString = $tst->format('Y-m-d H:i:s');
					$tetString = $tet->format('Y-m-d H:i:s');	
					if($tetString>=$tstString)
					if(($tetString>$st && $tetString<=$et) || ($tstString>=$st && $tstString<$et) || ($tstString<=$st && $tetString>=$et) || ($tstString>$st && $tetString<$et)){
						if(!isset($feedBack[$ad['playout_id']])){
							$feedBack[$ad['playout_id']] = [];
						}
						$feedBack[$ad['playout_id']][]=['asset'=>$ad['asset_id'],'start_time'=>$tstString,'stop_time'=>$tetString,'length'=>$ad['length'],'ip'=>$ad['dest_addr'],'port'=>$ad['dest_port']];
					}	
				}
			}
			$palyouthours[$row['playoutEid']] = $row['start_time'];
			$palyoutSeconds[$row['playoutEid']] = 0;
			$adBuffer_hours=[];
			$fullPlayoutTime = false;
		}
			
		
		//計算開始時間
		$tst = new DateTime($row['start_time'], $UTC);
		$addInterval = 'PT'.round($palyoutSeconds[$row['playoutEid']]).'S';
		$tst->add(new DateInterval($addInterval));
		
		//計算節束時間
		$palyoutSeconds[$row['playoutEid']]+=$row['length'];
		$tet = new DateTime($row['start_time'], $UTC);
		$addInterval = 'PT'.round($palyoutSeconds[$row['playoutEid']]).'S';
		$tet->add(new DateInterval($addInterval));
		//檢查結束時間是否超過playoutEvent的stopTime
		$tetString = $tet->format('Y-m-d H:i:s');
		if($tetString>$row['stop_time']){
			$tet = new DateTime($row['stop_time'], $UTC);
			$fullPlayoutTime = true;
		}
		
		//將時區從UTC切會回AsiaTaipei
		$tst->setTimezone( $AsiaTaipei );
		$tet->setTimezone( $AsiaTaipei );
		//取得時間strign
		$tstString = $tst->format('Y-m-d H:i:s');
		$tetString = $tet->format('Y-m-d H:i:s');	
		//echo $row['name'].' '.$row['start_time'].'<br>';
		if($tetString>=$tstString)
		if(($tetString>$st && $tetString<=$et) || ($tstString>=$st && $tstString<$et) || ($tstString<=$st && $tetString>=$et) || ($tstString>$st && $tetString<$et)){
			$adBuffer_hours[]=$row;
			if(!isset($feedBack[$row['playout_id']])){
				$feedBack[$row['playout_id']] = [];
			}
			$feedBack[$row['playout_id']][]=['asset'=>$row['asset_id'],'start_time'=>$tstString,'stop_time'=>$tetString,'length'=>$row['length'],'ip'=>$row['dest_addr'],'port'=>$row['dest_port']];
		}
	}
	produceIabFile($feedBack);
	
	//利用AMS資料庫產生IAB報表
	function produceIabFile($orbitData){
		global $searchDate;
		$my=new MyDB(true);
		$filename = HOME.$searchDate."_orbitLogs.txt";
		if(!$file = fopen($filename,"w"))
			exit('產生檔案失敗');
		echo '取得版位資訊</br>';
		//取得版位資料
		$sql='	SELECT  版位.版位名稱,channelId參數.版位其他參數預設值 AS channel_id, playoutId參數.版位其他參數預設值 AS playout_id, serCode參數.版位其他參數預設值 AS serCode,版位.版位識別碼
				FROM 版位 
					JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
					JOIN 版位其他參數 channelId參數 ON channelId參數.版位識別碼 = 版位.版位識別碼 AND channelId參數.版位其他參數名稱="channel_id"
					JOIN 版位其他參數 playoutId參數 ON playoutId參數.版位識別碼 = 版位.版位識別碼 AND playoutId參數.版位其他參數名稱="playout_id"
                    LEFT JOIN 版位其他參數 serCode參數 ON serCode參數.版位識別碼 = 版位.版位識別碼 AND serCode參數.版位其他參數名稱="serCode"
				WHERE 
					版位類型.版位名稱 = "barker頻道" 
			';
		$positionData = $my->getResultArray($sql);
		//依照playoutId整理版位資料
		$positionDataByPlayout=[];
		foreach($positionData as $data){
			$positionDataByPlayout[$data['playout_id']]=$data;
		}
		
		//取得託播單與素材資料
		$orderData_db = getOrderData_db();//AMS資料庫版本
		$orderData = getOrderData_api();//CAMPS API版本
		//依照素材名稱整理託播單資料
		$orderDataByMaterialName=[];
		foreach($orderData as $data){
			//$orderDataByMaterialName[$data['素材原始檔名']]=$data;//AMS資料庫版本
			if(!isset($orderDataByMaterialName[$data['material_name']]))
				$orderDataByMaterialName[$data['material_name']]=[];
			$orderDataByMaterialName[$data['material_name']][]=$data;
		}
		
		//依照transactionId整理資料庫資料
		$orderDataDbByTid=[];
		foreach($orderData_db as $data){
			$orderDataDbByTid[$data['transaction_id']]=$data;//AMS資料庫版本
		}
		
		echo '產生IAB報表.....<br>';
		foreach($orbitData as $playout_id=>$sch){
			foreach($sch as $schData){
				$schData = array_map('PHPExtendFunction::n2s',$schData);
				//找到對應的版位資料
				$positionMeta = isset($positionDataByPlayout[$playout_id])?$positionDataByPlayout[$playout_id]:[];
				if($positionMeta==[])
					break;
				//找到對應的託播單與素材資訊
				$orderMetas = isset($orderDataByMaterialName[$schData['asset']])?$orderDataByMaterialName[$schData['asset']]:[];
				$orderMeta = [];
				$orderMeta_db = [];
				//找到對應的資料庫託播單資訊
				foreach($orderMetas as $meta){
					if(isset($orderDataDbByTid[$meta['transaction_id']])){
						$temp_db = $orderDataDbByTid[$meta['transaction_id']];
						if($temp_db['版位識別碼']==$positionMeta['版位識別碼']){
							$orderMeta = $meta;
							$orderMeta_db = $temp_db;
						}
					}
				}
				//$orderMeta_db = isset($orderDataDbByTid[$orderMeta['transaction_id']])?$orderDataDbByTid[$orderMeta['transaction_id']]:[];
				//整理書出資訊:互動專區代碼 IP&PORT 託播單號 媒體代碼 媒體名稱 媒體種類 媒體長度 託播型式 開始日期 開始時間 結束日期 結束時間
				$stt = explode(' ',$schData['start_time']);
				$ett = explode(' ',$schData['stop_time']);
				//$serCode  = ($positionMeta==[])?'null':($positionMeta['channel_id'].' '.$positionMeta['版位名稱']);
				$serCode  = ($positionMeta==[])?'null':($positionMeta['serCode'].' '.$positionMeta['版位名稱']);
				$IPPORT = $schData['ip'].':'.$schData['port'];
				$orderId = ($orderMeta_db==[])?'null':($orderMeta_db['託播單識別碼']);//AMS資料庫版本
				//$orderId = ($orderMeta==[])?'null':($orderMeta['transaction_id']);//CAMPS API版本
				$mediaId = ($orderMeta_db==[])?'null':($orderMeta_db['素材識別碼']);//AMS資料庫版本
				//$mediaId = ($orderMeta==[])?'null':($orderMeta['material_id']);//CAMPS API版本
				//$mediaName = ($orderMeta_db==[])?'null':($orderMeta_db['託播單名稱']);//AMS資料庫版本
				$mediaName = ($orderMeta==[])?'null':($orderMeta['ad_name']);//CAMPS API版本
				$mediaName = str_replace(",","，",$mediaName);
				$mediaType = 'v';
				$mediaLength = $schData['length'];
				$playType = 1;
				$startDate = $stt[0];
				$startTime = $stt[1];
				$endDate = $ett[0];
				$endTime = $ett[1];
				
				$temp=array($serCode,$IPPORT,$orderId,$mediaId,$mediaName,$mediaType,$mediaLength,$playType,$startDate,$startTime,$endDate,$endTime);
				fwrite($file,implode(',',$temp)."\n");
			}
		}
		fclose($file);
		echo 'IAB報表完成'.$filename.'<br>';
	}
	
	//從資料庫取得託播單與素材資料
	function getOrderData_db(){
		global $st,$et;
		$my=new MyDB(true);
		$sql='	SELECT  託播單.託播單識別碼,託播單名稱,託播單CAMPS_ID對照表.transaction_id,素材.素材識別碼,素材原始檔名,CAMPS影片媒體編號,託播單CAMPS_ID對照表.版位識別碼
				FROM 託播單 
					JOIN 版位 ON 版位.版位識別碼 = 託播單.版位識別碼
					JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
				    JOIN 託播單CAMPS_ID對照表 ON 託播單CAMPS_ID對照表.託播單識別碼 = 託播單.託播單識別碼
					JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
					JOIN 素材 ON 託播單素材.素材識別碼 = 素材.素材識別碼
				WHERE 
					版位類型.版位名稱 = "barker頻道"  
					AND ((廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間))
			';
		$orderData = $my->getResultArray($sql,'sssss',$st,$et,$st,$et,$st);
		return  $orderData;
	}
	
	
	//從CAMPS取得託播單與素材資料
	function getOrderData_api(){
		global $logger,$st,$et;
		//$materialUrl=Config::$CAMPS_API['material'];
		$orderUrl=Config::$CAMPS_API['order'];
		//$orderUrl='localhost/AMS/test.php';
		
		//查詢託播單資料
		$dates = str_replace(' ','%20',$st);
		$url = $orderUrl.'?orbit_only=1&rows=300&include_date='.$dates;
		//$url = urlencode($url);
		//$url = $orderUrl.'?orbit_only=1&rows=300&include_date=2016-09-28%2000:00:00';
		echo '取得託播單資訊vi'.$url.'</br>';
		$orderData = PHPExtendFunction::connec_to_Api($url,'GET',null);
		if(!$orderData['success']){
			$logger->error('curl錯誤代號:'.$orderData['erro_no'].'無法連接API:'.$url);
			exit('curl錯誤代號:'.$orderData['erro_no'].'無法連接API:'.$url);
		}
		$orderData = json_decode($orderData['data'],true);
		//逐託播單整理素材資料
		/*foreach($orderData as $order){
				echo $order['material_name'].'<br>';
		}*/
		
		return $orderData;
	}
?>
