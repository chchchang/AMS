<?php 
	header("X-Frame-Options: SAMEORIGIN");
	require_once dirname(__FILE__).'/../tool/MyDB.php';
	require_once dirname(__FILE__).'/../tool/MyLogger.php';
	$my=new MyDB(true);
	
	static $RESPONSECODE = array(
    200=>'OK',
	400=>'Bad Request',
	405=>'Method Not Allowed',
	500=>'Internal Server Error'
	);
	
	function eixtWhitCode($code){
		global $RESPONSECODE; 
		header('Content-Type: application/json');
		exit(json_encode(array('code'=>$code, 'status'=>$RESPONSECODE[$code]),JSON_UNESCAPED_UNICODE));
	}
	
	
	$startTime = isset($_POST['startTime'])?$_POST['startTime']:'0000-01-01 00:00:00';
	$endTime = isset($_POST['endTime'])?$_POST['endTime']:'9999-12-31 23:59:59';
	if($startTime>$endTime)
		eixtWhitCode(400);
	
	//取得sercode對訂的版位識別碼
	$pid='%';
	if(isset($_POST['serCode'])){
		$sql='	SELECT 版位.版位識別碼 
				FROM 版位其他參數,版位,版位 版位類型 
				WHERE 版位其他參數.版位識別碼 = 版位.版位識別碼 AND 版位類型.版位識別碼 = 版位.上層版位識別碼 AND 版位類型.版位名稱="專區vod" 
				AND 版位其他參數名稱 = "serCode" AND 版位其他參數預設值 = ? AND 版位.版位名稱 LIKE "%IAP"
			';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->bind_param('s',$_POST['serCode'])) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$res=$stmt->get_result()){
			eixtWhitCode(500);
		}	
		while($row = $res->fetch_assoc()){
			$pid = $row['版位識別碼'];
		}
	}
	
	$data = [];
	//取得託播單基本資訊
	$sql='
		SELECT
			託播單.託播單識別碼,
			託播單.託播單CSMS群組識別碼,
			託播單名稱,
			廣告期間開始時間,
			廣告期間結束時間,
			託播單狀態識別碼,
			廣告可被播出小時時段
		FROM
			託播單
			INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
			INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
		WHERE
			託播單.版位識別碼 LIKE ?
			AND 版位.版位名稱 LIKE "%IAP"
			AND 版位類型.版位名稱="專區vod" 
			AND 託播單.託播單狀態識別碼 = 2
			AND( 
			(廣告期間開始時間 BETWEEN ? AND ?)
			OR (廣告期間結束時間 BETWEEN ? AND ?)
			OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間)
			)
	';
	if(!$stmt=$my->prepare($sql)) {
		eixtWhitCode(500);
	}
	if(!$stmt->bind_param('ssssss',$pid,$startTime,$endTime,$startTime,$endTime,$startTime)) {
		eixtWhitCode(500);
	}
	if(!$stmt->execute()) {
		eixtWhitCode(500);
	}
	if(!$orderRes=$stmt->get_result()){
		eixtWhitCode(500);
	}
	//逐每張託播單，取得其他參數與素材
	while($order = $orderRes->fetch_assoc()){
		//取得其他參數
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
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->bind_param('i',$order['託播單識別碼'])) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$result=$stmt->get_result()){
			eixtWhitCode(500);
		}
		$orderSetting=array();
		while($row = $result->fetch_assoc()){
			$orderSetting[$row['版位類型其他參數名稱']]=$row['版位類型其他參數預設值'];
			if($row['版位其他參數預設值']!=null) $orderSetting[$row['版位類型其他參數名稱']]=$row['版位其他參數預設值'];
			if($row['託播單其他參數值']!=null) $orderSetting[$row['版位類型其他參數名稱']]=$row['託播單其他參數值'];
		}
		//取得素材
		$sql='
			SELECT
				素材.素材識別碼,
				託播單素材.點擊後開啟類型,
				託播單素材.點擊後開啟位址,
				影片畫質識別碼,
				素材原始檔名,
				影片媒體編號北,
				影片媒體編號南
			FROM
				託播單素材
				INNER JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
			WHERE
				託播單素材.託播單識別碼=?
		';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->bind_param('i',$order['託播單識別碼'])) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$result=$stmt->get_result()){
			eixtWhitCode(500);
		}
		$sd = [];$hd = [];
		$SDVideo = '';
		$HDVideo = '';
		while($row = $result->fetch_assoc()){
			$mNameA = explode('.',$row['素材原始檔名']);
			$type = end($mNameA);
						
			if($row['影片畫質識別碼']==1){
				$sd = $row;
				$SDVideo = '_____AMS_'.$row['素材識別碼'].'_'.md5_file(Config::GET_MATERIAL_FOLDER().$row['素材識別碼'].'.'.$type);
			}
			else if($row['影片畫質識別碼']==2){
				$hd = $row;
				$HDVideo = '_____AMS_'.$row['素材識別碼'].'_'.md5_file(Config::GET_MATERIAL_FOLDER().$row['素材識別碼'].'.'.$type);
			}
		}
		
		//處理投放時間
		/*若24個時段同時出現，代表全天；
		否則，若0,23同時出現，代表跨日；
		否則，即不跨日。*/
		$hours=explode(',',$order['廣告可被播出小時時段']);
		$hours2=array();
		foreach($hours as $hour){
			$hours2[intval($hour)]=intval($hour);
		}
		if(count($hours)===24){
			$stT='00:00';
			$edT='24:00';
		}
		else if(array_search('0',$hours)!==false&&array_search('23',$hours)!==false){
			for($i=1;$i<23;$i++){
				if(!isset($hours2[$i])){
					$edT=sprintf('%02d',$i).':00';
					break;
				}
			}
			for($i=22;$i>0;$i--){
				if(!isset($hours2[$i])){
					$stT=sprintf('%02d',($i+1)).':00';
					break;
				}
			}
		}
		else{
			for($i=0;$i<23;$i++){
				if(isset($hours2[$i])){
					$stT=sprintf('%02d',$i).':00';
					break;
				}
			}
			for($i=23;$i>0;$i--){
				if(isset($hours2[$i])){
					$edT=sprintf('%02d',($i+1)).':00';
					break;
				}
			}
		}
		//處理日期	
		//開始日期
		$tpdate = new DateTime($order['廣告期間開始時間']);
		$order['廣告期間開始時間'] = $tpdate->format('Y-m-d H:i');
		//結束日期
		$tpdate = new DateTime($order['廣告期間結束時間']);
		//結束時間不為00秒，增加一分鐘
		$timeA = explode(':',$order['廣告期間結束時間']);
		if(end($timeA)!='00')
			$tpdate->add(new DateInterval('PT1M'));
		$order['廣告期間結束時間'] = $tpdate->format('Y-m-d H:i');
		
		//連動廣告1
		$bannerTransactionId1 = [];
		if($orderSetting['bannerTransactionId1'] != '' && $orderSetting['bannerTransactionId1']!=null){
			$t1 = explode(',',$orderSetting['bannerTransactionId1']);
			foreach($t1 as $csmsId){
				$sql = 'SELECT 託播單.託播單名稱
				FROM 託播單,版位
				WHERE 託播單CSMS群組識別碼 = ? AND 版位名稱 LIKE "%IAP"
					';
				if(!$stmt=$my->prepare($sql)) {
					eixtWhitCode(500);
				}
				if(!$stmt->bind_param('i',$csmsId)) {
					eixtWhitCode(500);
				}
				if(!$stmt->execute()) {
					eixtWhitCode(500);
				}
				if(!$result=$stmt->get_result()){
					eixtWhitCode(500);
				}
				$bannerTransactionId1[] = $result->fetch_assoc()['託播單名稱'];
			}
		}
		//連動廣告2
		$bannerTransactionId2 = [];
		if($orderSetting['bannerTransactionId2'] != '' && $orderSetting['bannerTransactionId2']!=null){
			$t2 = explode(',',$orderSetting['bannerTransactionId2']);
			foreach($t2 as $csmsId){
				$sql = 'SELECT 託播單.託播單名稱
				FROM 託播單,版位
				WHERE 託播單CSMS群組識別碼 = ? AND 版位名稱 LIKE "%IAP"
					';
				if(!$stmt=$my->prepare($sql)) {
					eixtWhitCode(500);
				}
				if(!$stmt->bind_param('i',$csmsId)) {
					eixtWhitCode(500);
				}
				if(!$stmt->execute()) {
					eixtWhitCode(500);
				}
				if(!$result=$stmt->get_result()){
					eixtWhitCode(500);
				}
				$bannerTransactionId2[] = $result->fetch_assoc()['託播單名稱'];
			}
		}
		//連動廣告3
		$bannerTransactionId3 = [];
		if($orderSetting['bannerTransactionId3'] != '' && $orderSetting['bannerTransactionId3']!=null){
			$t2 = explode(',',$orderSetting['bannerTransactionId3']);
			foreach($t2 as $csmsId){
				$sql = 'SELECT 託播單.託播單名稱
				FROM 託播單,版位
				WHERE 託播單CSMS群組識別碼 = ? AND 版位名稱 LIKE "%IAP"
					';
				if(!$stmt=$my->prepare($sql)) {
					eixtWhitCode(500);
				}
				if(!$stmt->bind_param('i',$csmsId)) {
					eixtWhitCode(500);
				}
				if(!$stmt->execute()) {
					eixtWhitCode(500);
				}
				if(!$result=$stmt->get_result()){
					eixtWhitCode(500);
				}
				$bannerTransactionId3[] = $result->fetch_assoc()['託播單名稱'];
			}
		}
		//連動廣告4
		$bannerTransactionId4 = [];
		if($orderSetting['bannerTransactionId4'] != '' && $orderSetting['bannerTransactionId4']!=null){
			$t2 = explode(',',$orderSetting['bannerTransactionId4']);
			foreach($t2 as $csmsId){
				$sql = 'SELECT 託播單.託播單名稱
				FROM 託播單,版位
				WHERE 託播單CSMS群組識別碼 = ? AND 版位名稱 LIKE "%IAP"
					';
				if(!$stmt=$my->prepare($sql)) {
					eixtWhitCode(500);
				}
				if(!$stmt->bind_param('i',$csmsId)) {
					eixtWhitCode(500);
				}
				if(!$stmt->execute()) {
					eixtWhitCode(500);
				}
				if(!$result=$stmt->get_result()){
					eixtWhitCode(500);
				}
				$bannerTransactionId4[] = $result->fetch_assoc()['託播單名稱'];
			}
		}
		
		$data[]=array(
			'bakadschdTransactionId'=>$order['託播單識別碼'],
			'sdVodcntTitle'=>$SDVideo,
			'hdVodcntTitle'=>$HDVideo,
			'sdChtnIapId' => $sd['影片媒體編號北'],
			'sdChtsIapId' => $sd['影片媒體編號南'],
			'hdChtnIapId' => isset($hd['影片媒體編號北'])?$hd['影片媒體編號北']:null,
			'hdChtsIapId' => isset($hd['影片媒體編號南'])?$hd['影片媒體編號南']:null,
			'bakadDisplayMax'=>$orderSetting['bakadDisplayMax'],
			'stbDisplayMax' =>$orderSetting['機上盒投放上限'],
			'linkType'=>$sd['點擊後開啟類型'],
			'linkValue'=>$sd['點擊後開啟位址'],
			'serCode'=>$orderSetting['serCode'],
			'bakadschdStartDate'=>$order['廣告期間開始時間'],
			'bakadschdEndDate'=>$order['廣告期間結束時間'],
			'bakadschdAssignStartTime'=>$stT,
			'bakadschdAssignEndTime'=>$edT,
			'bakadschdDisplaySequence'=>$orderSetting['bakadschdDisplaySequence'],
			'bakadschdDisplayMax'=>$orderSetting['bakadschdDisplayMax'],
			'bannerTransactionId1'=>implode(',',$bannerTransactionId1),
			'bannerTransactionId2'=>implode(',',$bannerTransactionId2),
			'bannerTransactionId3'=>implode(',',$bannerTransactionId3),
			'bannerTransactionId4'=>implode(',',$bannerTransactionId4)
			);
	}
	header('Content-Type: application/json');
		exit(json_encode(array('code'=>200, 'status'=>$RESPONSECODE[200],'data'=>$data),JSON_UNESCAPED_UNICODE));
	
	
?>
