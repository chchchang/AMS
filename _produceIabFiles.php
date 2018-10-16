<?php 
	date_default_timezone_set("Asia/Taipei");
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/tool/MyDB.php';
	require_once dirname(__FILE__).'/tool/MyLogger.php';
	define('HOME','/home/ams/IabFiles/');
	//define('HOME','order/851/');
	define('GLUE','$');
	$my=new MyDB(true);
	$logger=new MyLogger();
	static $RESPONSECODE = array(
    200=>'OK',
	400=>'Bad Request',
	405=>'Method Not Allowed',
	500=>'Internal Server Error'
	);
	
	function eixtWhitCode($code){
		global $RESPONSECODE, $logger, $my;
		$logger->error('取得IAB報表失敗('.$my->errno.')、錯誤訊息('.$my->error.')。');
		header('Content-Type: application/json');
		exit(json_encode(array('code'=>$code, 'status'=>$RESPONSECODE[$code]),JSON_UNESCAPED_UNICODE));
	}
	
	echo '產生委刊單總表....'.'<br>';
	produceOrderListFile();
	echo '委刊單總表已產生....'.'<br><br>';
	
	echo '產生託播單單總表....'.'<br>';
	produceOrderFile();
	echo '託播單總表已產生....'.'<br><br>';
	echo '產生素材總表....'.'<br>';
	produceMaterialFile();
	echo '素材總表已產生....'.'<br><br>';

	$ptPara = array(); //記錄版位類型參數用
	$pPara = array(); //記錄版位參數用
	
	//產生委刊單總表
	function produceOrderListFile(){
		$my=new MyDB(true);
		$filename = HOME.date("Y-m-d")."_orderList.txt";
		$file = fopen($filename,"w");
		//委刊單編號,客戶名稱,廣告主統一編號,客戶名稱,代理商統一編號,客戶名稱,承銷商統一編號,檔期起始日,檔期迄止日,廣告名稱填,單日期,填單者,修改日期,修改者
		$header =array("adTransactionId","CustName","CustVA","CustName","CustVAT_A","CustName","CustVAT_U","SDateTime","EDateTime","ADName","AddDateTime","AddUserId","UpdDateTime","UpdUserId");
		fwrite($file,implode(GLUE,$header)."\n");

		
		$sql='	SELECT DISTINCT 委刊單.委刊單識別碼,廣告主名稱,廣告主統一編號,頻道商名稱,頻道商統一編號,承銷商名稱,承銷商統一編號,委刊單名稱,
						委刊單.CREATED_TIME,C.使用者姓名 AS 填單者,委刊單.LAST_UPDATE_TIME,U.使用者姓名 AS 修改者
				FROM 委刊單
					JOIN 廣告主 ON 委刊單.廣告主識別碼 = 廣告主.廣告主識別碼
					JOIN 使用者 C ON 委刊單.CREATED_PEOPLE = C.使用者識別碼
					LEFT JOIN 使用者 U ON 委刊單.LAST_UPDATE_PEOPLE =U.使用者識別碼
					JOIN 託播單 ON 委刊單.委刊單識別碼 = 託播單.委刊單識別碼
					JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼
					JOIN 版位 版位類型 ON 版位類型.版位識別碼 = 版位.上層版位識別碼
				WHERE 
					版位類型.版位名稱 IN ("首頁banner","專區banner","專區vod","頻道short EPG banner","barker頻道")
			';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$res=$stmt->get_result()){
			eixtWhitCode(500);
		}
		//逐各委刊單，找屬於他的託播單資訊
		while($row = $res->fetch_assoc()){
			$row = array_map('n2s',$row);
			//取得最小開始時間與最大結束時間
			$sql='	SELECT MIN(廣告期間開始時間)AS 廣告期間開始時間, MAX(廣告期間結束時間) AS 廣告期間結束時間
			FROM 託播單
			WHERE 委刊單識別碼 = ?'
			;
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['委刊單識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res1=$stmt->get_result()){
				eixtWhitCode(500);
			}
			$row1 = array_map('n2s',$res1->fetch_assoc());
		/*
			$sql='	SELECT DISTINCT 上層產業類型.產業類型說明 AS 產業大類 , 產業類型.產業類型說明 AS 產業小類
			FROM 託播單,託播單素材,素材,產業類型 , 產業類型 上層產業類型
			WHERE 委刊單識別碼=? AND 託播單.託播單識別碼 = 託播單素材.託播單識別碼 AND 託播單素材.素材識別碼=素材.素材識別碼 AND 素材.產業類型識別碼 = 產業類型.產業類型識別碼 
			AND 產業類型.上層產業類型識別碼 = 上層產業類型.產業類型識別碼'
			;
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['委刊單識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res2=$stmt->get_result()){
				eixtWhitCode(500);
			}
			while($row2 = $res2->fetch_assoc()){
				array_map('n2s',$row2);
				$forExcel = array($row['委刊單識別碼'],$row['廣告主名稱'],$row['廣告主統一編號'],$row['頻道商名稱'],$row['頻道商統一編號'],$row['承銷商名稱'],$row['承銷商統一編號'],$row1['廣告期間開始時間']
				,$row1['廣告期間結束時間'],$row2['產業大類'],$row2['產業小類'],$row['委刊單名稱'],$row['CREATED_TIME'],$row['填單者'],$row['LAST_UPDATE_TIME'],$row['修改者']);
				fwrite($file,implode(GLUE,$forExcel)."\n");
			}*/
			$forExcel = array($row['委刊單識別碼'],nf_to_wf($row['廣告主名稱'], $types = 'nf_to_wf'),$row['廣告主統一編號'],$row['頻道商名稱'],$row['頻道商統一編號'],$row['承銷商名稱'],$row['承銷商統一編號'],$row1['廣告期間開始時間']
				,$row1['廣告期間結束時間'],nf_to_wf($row['委刊單名稱'], $types = 'nf_to_wf'),$row['CREATED_TIME'],$row['填單者'],$row['LAST_UPDATE_TIME'],$row['修改者']);
				fwrite($file,implode(GLUE,$forExcel)."\n");
		}
		fclose($file);
	}
	
	//產生託播單總表
	function produceOrderFile(){
		$my=new MyDB(true);
		$filename = HOME.date("Y-m-d")."_orders.txt";
		$file = fopen($filename,"w");
		$header =array("adTransactionId"
				,"adTransactionId_D"
				,"TblDesc"
				,"Name"
				,"UIStyleId"
				,"bnrSequence"
				,"schdStartDate"
				,"schdEndDate"
				,"assignStartTime"
				,"assignEndTime"
				,"assignTime"
				,"ADName"
				,"ADNameChar"
				,"adfootage"
				,"location"
				,"adLink_n"
				,"adLink_c"
				,"adLink_s"
				,"schdDisplayMax_n"
				,"schdDisplayMax_c"
				,"schdDisplayMax_s"
				,"Weight"
				,"AddDateTime"
				,"AddUserId"
				,"UpdDateTime"
				,"UpdUserId"
				,"materialId"
				,"bnrTransId1"
				,"bnrTransId2"
				,"bnrTransId3"
				,"bnrTransId4"
				,"coverMaterialId"
				,"static"
				)
				;
		fwrite($file,implode(GLUE,$header)."\n");
		
		$sql='	SELECT  託播單.託播單識別碼
				,CASE 
					WHEN 額外版位.版位識別碼 IS NULL THEN 版位.版位識別碼
					ELSE 額外版位.版位識別碼
				END AS 版位識別碼
				,版位類型.版位識別碼 AS 版位類型識別碼
				,版位類型.版位名稱 AS 版位類型名稱
				,CASE 
					WHEN 額外版位.版位名稱 IS NULL THEN 版位.版位名稱
					ELSE 額外版位.版位名稱
				END AS 版位名稱
				,託播單.委刊單識別碼
				,託播單CSMS群組識別碼
				,廣告期間開始時間
				,廣告期間結束時間
				,廣告可被播出小時時段
				,委刊單.委刊單名稱
				,託播單名稱
				,影片素材秒數
				,點擊後開啟類型
				,點擊後開啟位址
				,託播單.CREATED_TIME
				,C.使用者姓名 AS 填單者
				,託播單.LAST_UPDATE_TIME
				,U.使用者姓名 AS 修改者
				,託播單素材.素材識別碼
				FROM 託播單
					JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼
					JOIN 版位 版位類型 ON 版位類型.版位識別碼 = 版位.上層版位識別碼
					LEFT JOIN 託播單投放版位 ON 託播單.託播單識別碼 = 託播單投放版位.託播單識別碼 AND 託播單投放版位.ENABLE=1		
					LEFT JOIN 版位 額外版位 ON 額外版位.版位識別碼 = 託播單投放版位.版位識別碼
					JOIN 委刊單 ON 託播單.委刊單識別碼 = 委刊單.委刊單識別碼
					JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
					LEFT JOIN 素材 ON 託播單素材.素材識別碼 = 素材.素材識別碼
					JOIN 使用者 C ON 託播單.CREATED_PEOPLE = C.使用者識別碼
					LEFT JOIN 使用者 U ON 託播單.LAST_UPDATE_PEOPLE =U.使用者識別碼
				WHERE
					版位類型.版位名稱 IN ("首頁banner","專區banner","專區vod","頻道short EPG banner","barker頻道")
			';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$res=$stmt->get_result()){
			eixtWhitCode(500);
		}
	
		while($row = $res->fetch_assoc())
		{
			$row = array_map('n2s',$row);
			//投放時間
			$hours = explode(',',$row['廣告可被播出小時時段']);
			//取得區域
			$location = explode('_',$row['版位名稱']);
			$location = end($location);
			switch($location){
				case '北':
					$location = 'N';
					break;
				case '中':
					$location = 'C';
					break;
				case '南':
					$location = 'S';
					break;
			}
		
			//取得託播單其他參數
			/*$sql='	SELECT 版位其他參數名稱,託播單其他參數值
				FROM 託播單其他參數,託播單,版位,版位其他參數
				WHERE 託播單.託播單識別碼 = 託播單其他參數.託播單識別碼 AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 
				AND 版位其他參數.版位其他參數順序 = 託播單其他參數.託播單其他參數順序 AND 版位其他參數名稱 IN ("bakadschdDisplayMax","bannerTransactionId1","bannerTransactionId2","bannerTransactionId3","bannerTransactionId4","bakadschdDisplaySequence")
				AND 託播單.託播單識別碼 = ?
			';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['託播單識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res1=$stmt->get_result()){
				eixtWhitCode(500);
			}
			$orderParam=array("bakadschdDisplayMax"=>NULL,"bannerTransactionId1"=>NULL,"bannerTransactionId2"=>NULL,"bannerTransactionId3"=>NULL,"bannerTransactionId4"=>NULL,"bakadschdDisplaySequence"=>NULL);
			while($row1 = $res1->fetch_assoc()){
				$orderParam[$row1['版位其他參數名稱']]=$row1['託播單其他參數值'];
			}*/
			
			
			//取得版位其他參數
			/*$sql='	SELECT 版位其他參數名稱,版位其他參數預設值
				FROM 版位其他參數
				WHERE 
				版位其他參數名稱 IN ("adSizetype","bnrSequence")
				AND 版位識別碼 = ?
			';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['版位類型識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res1=$stmt->get_result()){
				eixtWhitCode(500);
			}
			$positionParam=array("adSizetype"=>NULL,"bnrSequence"=>NULL);
			while($row1 = $res1->fetch_assoc()){
				$positionParam[$row1['版位其他參數名稱']]=$row1['版位其他參數預設值'];
			}
			$sql='	SELECT 版位其他參數名稱,版位其他參數預設值
				FROM 版位其他參數
				WHERE 
				版位其他參數名稱 IN ("adSizetype","bnrSequence")
				AND 版位識別碼 = ?
			';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['版位識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res1=$stmt->get_result()){
				eixtWhitCode(500);
			}
			while($row1 = $res1->fetch_assoc()){
				$positionParam[$row1['版位其他參數名稱']]=$row1['版位其他參數預設值']==null?$positionParam[$row1['版位其他參數名稱']]:$row1['版位其他參數預設值'];
			}*/
			
			$paras = getOrderParater($row['託播單識別碼']);
			$paras = array_map('n2s',$paras);
			//$orderParam = array_map('n2s',$orderParam);
			//$positionParam = array_map('n2s',$positionParam);
			$oid=$row['託播單CSMS群組識別碼'];
			if($row['版位類型名稱']=='barker頻道')
				$oid=$row['託播單識別碼'];
			
			//若是可展開的banner廣告，從點擊開啟位址中頗析展開的圖片素材識別碼
			$cover_pic = 'NULL';
			if(($row['點擊後開啟類型']=="COVER_A" || $row['點擊後開啟類型']=="COVER_B") && ($location == 'N'||$location == 'C'||$location == 'S')){
				$parapart =  explode('#',$row['點擊後開啟位址']);
				$cover_pic_fileName = $parapart[1];//0100005187#_____AMS_5187.png#NONE => _____AMS_5187.png
				$cover_pic_fileName = explode('.',$cover_pic_fileName)[0];//_____AMS_5187.png =>_____AMS_5187
				$cover_pic = str_replace('_____AMS_','',$cover_pic_fileName);//_____AMS_5187=>5187
			}
			$temp = array(
				$row['委刊單識別碼'],
				$oid,
				$row['版位類型名稱'],
				$row['版位名稱'],
				(isset($positionParam['adSizetype'])?$positionParam['adSizetype']:'NULL'),
				(isset($positionParam['bnrSequence'])?$positionParam['bnrSequence']:'NULL'),
				$row['廣告期間開始時間'],
				$row['廣告期間結束時間'],
				$hours[0],
				end($hours),
				','.$row['廣告可被播出小時時段'].',',
				nf_to_wf($row['委刊單名稱'], $types = 'nf_to_wf'),
				nf_to_wf($row['託播單名稱'], $types = 'nf_to_wf'),
				$row['影片素材秒數'],
				$location,
				($location=='N'?($row['點擊後開啟類型'].'&'.$row['點擊後開啟位址']):'NULL'),
				($location=='C'?($row['點擊後開啟類型'].'&'.$row['點擊後開啟位址']):'NULL'),
				($location=='S'?($row['點擊後開啟類型'].'&'.$row['點擊後開啟位址']):'NULL'),
				/*($location=='N'?$orderParam['bakadschdDisplayMax']:'NULL'),
				($location=='C'?$orderParam['bakadschdDisplayMax']:'NULL'),
				($location=='S'?$orderParam['bakadschdDisplayMax']:'NULL'),
				$orderParam['bakadschdDisplaySequence'],*/
				(isset($paras['bakadschdDisplayMax'])?$paras['bakadschdDisplayMax']:'NULL'),
				(isset($paras['bakadschdDisplayMax'])?$paras['bakadschdDisplayMax']:'NULL'),
				(isset($paras['bakadschdDisplayMax'])?$paras['bakadschdDisplayMax']:'NULL'),
				(isset($paras['bakadschdDisplaySequence'])?$paras['bakadschdDisplaySequence']:'NULL'),
				$row['CREATED_TIME'],
				$row['填單者'],
				$row['LAST_UPDATE_TIME'],
				$row['修改者'],
				$row['素材識別碼'],
				/*$orderParam['bannerTransactionId1'],
				$orderParam['bannerTransactionId2'],
				$orderParam['bannerTransactionId3'],
				$orderParam['bannerTransactionId4'],*/
				(isset($paras['bannerTransactionId1'])?$paras['bannerTransactionId1']:'NULL'),
				(isset($paras['bannerTransactionId2'])?$paras['bannerTransactionId2']:'NULL'),
				(isset($paras['bannerTransactionId3'])?$paras['bannerTransactionId3']:'NULL'),
				(isset($paras['bannerTransactionId4'])?$paras['bannerTransactionId4']:'NULL'),
				$cover_pic,
				(isset($paras['static'])?$paras['static']:'NULL')
			);
			fwrite($file,implode(GLUE,$temp)."\n");
	
		}
		fclose($file);
		produceOrderFile_VSM();
	}
	
	//產生託播單總表_append_VSM
	function produceOrderFile_VSM(){
		$my=new MyDB(true);
		$filename = HOME.date("Y-m-d")."_orders.txt";
		$file = fopen($filename,"a");
				
		$sql='	SELECT  託播單.託播單識別碼
				,CASE 
					WHEN 額外版位.版位識別碼 IS NULL THEN 版位.版位識別碼
					ELSE 額外版位.版位識別碼
				END AS 版位識別碼
				,版位類型.版位識別碼 AS 版位類型識別碼
				,版位類型.版位名稱 AS 版位類型名稱
				,CASE 
					WHEN 額外版位.版位名稱 IS NULL THEN 版位.版位名稱
					ELSE 額外版位.版位名稱
				END AS 版位名稱
				,託播單.委刊單識別碼
				,託播單CSMS群組識別碼
				,廣告期間開始時間
				,廣告期間結束時間
				,廣告可被播出小時時段
				,委刊單.委刊單名稱
				,託播單名稱
				,影片素材秒數
				,點擊後開啟類型
				,點擊後開啟位址
				,託播單.CREATED_TIME
				,C.使用者姓名 AS 填單者
				,託播單.LAST_UPDATE_TIME
				,U.使用者姓名 AS 修改者
				,託播單素材.素材識別碼
				FROM 託播單
					JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼
					JOIN 版位 版位類型 ON 版位類型.版位識別碼 = 版位.上層版位識別碼
					LEFT JOIN 託播單投放版位 ON 託播單.託播單識別碼 = 託播單投放版位.託播單識別碼 AND 託播單投放版位.ENABLE=1		
					LEFT JOIN 版位 額外版位 ON 額外版位.版位識別碼 = 託播單投放版位.版位識別碼
					JOIN 委刊單 ON 託播單.委刊單識別碼 = 委刊單.委刊單識別碼
					JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
					LEFT JOIN 素材 ON 託播單素材.素材識別碼 = 素材.素材識別碼
					JOIN 使用者 C ON 託播單.CREATED_PEOPLE = C.使用者識別碼
					LEFT JOIN 使用者 U ON 託播單.LAST_UPDATE_PEOPLE =U.使用者識別碼
				WHERE
					版位類型.版位名稱 IN ("單一平台banner","單一平台EPG","單一平台barker_vod")
			';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$res=$stmt->get_result()){
			eixtWhitCode(500);
		}
	
		while($row = $res->fetch_assoc())
		{
			$row = array_map('n2s',$row);
			//投放時間
			$hours = explode(',',$row['廣告可被播出小時時段']);
	
			//取得託播單其他參數
			/*$sql='	SELECT 版位其他參數名稱,託播單其他參數值
				FROM 託播單其他參數,託播單,版位,版位其他參數
				WHERE 託播單.託播單識別碼 = 託播單其他參數.託播單識別碼 AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 
				AND 版位其他參數.版位其他參數順序 = 託播單其他參數.託播單其他參數順序 AND 版位其他參數名稱 IN ("bannerTransactionId","weight")
				AND 託播單.託播單識別碼 = ?
			';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['託播單識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res1=$stmt->get_result()){
				eixtWhitCode(500);
			}
			$orderParam=array("bakadschdDisplayMax"=>NULL,"bannerTransactionId"=>NULL,"bannerTransactionId2"=>NULL,"bannerTransactionId3"=>NULL,"bannerTransactionId4"=>NULL,"weight"=>NULL);
			while($row1 = $res1->fetch_assoc()){
				$orderParam[$row1['版位其他參數名稱']]=$row1['託播單其他參數值'];
			}*/
			
			
			//取得版位其他參數
			/*$sql='	SELECT 版位其他參數名稱,版位其他參數預設值
				FROM 版位其他參數
				WHERE 
				版位其他參數名稱 IN ("group_name")
				AND 版位識別碼 = ?
			';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['版位類型識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res1=$stmt->get_result()){
				eixtWhitCode(500);
			}
			$positionParam=array("adSizetype"=>NULL,"bnrSequence"=>NULL);
			while($row1 = $res1->fetch_assoc()){
				$positionParam[$row1['版位其他參數名稱']]=$row1['版位其他參數預設值'];
			}
			$sql='	SELECT 版位其他參數名稱,版位其他參數預設值
				FROM 版位其他參數
				WHERE 
				版位其他參數名稱 IN ("group_name")
				AND 版位識別碼 = ?
			';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$row['版位識別碼'])) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res1=$stmt->get_result()){
				eixtWhitCode(500);
			}
			while($row1 = $res1->fetch_assoc()){
				$positionParam[$row1['版位其他參數名稱']]=$row1['版位其他參數預設值']==null?$positionParam[$row1['版位其他參數名稱']]:$row1['版位其他參數預設值'];
			}
			
			$orderParam = array_map('n2s',$orderParam);
			$positionParam = array_map('n2s',$positionParam);*/
			$oid=$row['託播單識別碼'];
			$paras = getOrderParater($row['託播單識別碼']);
			$paras = array_map('n2s',$paras);
			
			//若是可展開的banner廣告，從點擊開啟位址中頗析展開的圖片素材識別碼
			if(($row['點擊後開啟類型']=="SEPG橫向覆蓋圖片" || $row['點擊後開啟類型']=="SEPG直向覆蓋圖片")){
				$cover_pic_fileName = $row['點擊後開啟位址'];
				$cover_pic_fileName = explode('.',$cover_pic_fileName)[0];//ad/_____AMS_5187.png =>ad/_____AMS_5187
				$cover_pic = str_replace('_____AMS_','',$cover_pic_fileName);//ad/_____AMS_5187=>ad/5187
				$cover_pic = str_replace('ad/','',$cover_pic);//ad5187=>5187
				print_r($cover_pic);
			}
			else{
				$cover_pic = 'NULL';
			}
			$temp = array(
				$row['委刊單識別碼'],//adTransactionId
				$oid,//adTransactionId_D
				$row['版位類型名稱'],//TblDesc
				$row['版位名稱'],//Name
				/*(isset($positionParam['adSizetype'])?$positionParam['adSizetype']:'NULL'),//UIStyleId
				(isset($positionParam['bnrSequence'])?$positionParam['bnrSequence']:'NULL'),//bnrSequence*/
				(isset($paras['adSizetype'])?$paras['adSizetype']:'NULL'),//UIStyleId
				(isset($paras['bnrSequence'])?$paras['bnrSequence']:'NULL'),//bnrSequence
				$row['廣告期間開始時間'],//schdStartDate
				$row['廣告期間結束時間'],//schdEndDate
				$hours[0],//assignStartTime
				end($hours),//assignEndTime
				','.$row['廣告可被播出小時時段'].',',//assignTime
				$row['委刊單名稱'],//ADName
				$row['託播單名稱'],//ADNameChar
				$row['影片素材秒數'],//adfootage
				"",//location
				$row['點擊後開啟類型'].'&'.$row['點擊後開啟位址'],
				$row['點擊後開啟類型'].'&'.$row['點擊後開啟位址'],
				$row['點擊後開啟類型'].'&'.$row['點擊後開啟位址'],
				'NULL',
				'NULL',
				'NULL',
				//$orderParam['weight'],
				(isset($paras['weight'])?$paras['weight']:'NULL'),
				$row['CREATED_TIME'],
				$row['填單者'],
				$row['LAST_UPDATE_TIME'],
				$row['修改者'],
				$row['素材識別碼'],
				/*$orderParam['bannerTransactionId'],
				$orderParam['bannerTransactionId2'],
				$orderParam['bannerTransactionId3'],
				$orderParam['bannerTransactionId4'],*/
				(isset($paras['bannerTransactionId'])?$paras['bannerTransactionId']:'NULL'),
				(isset($paras['bannerTransactionId2'])?$paras['bannerTransactionId2']:'NULL'),
				(isset($paras['bannerTransactionId3'])?$paras['bannerTransactionId3']:'NULL'),
				(isset($paras['bannerTransactionId4'])?$paras['bannerTransactionId4']:'NULL'),
				$cover_pic,	
				(isset($paras['static'])?$paras['static']:'NULL')
			);
			fwrite($file,implode(GLUE,$temp)."\n");
		}
		fclose($file);
	}
	
	//產生素材總表
	function produceMaterialFile(){
		$my=new MyDB(true);
		$filename = HOME.date("Y-m-d")."_materials.txt";
		$file = fopen($filename,"w");
		//廣告素材編號,廣告素材類型,廣告篇名,文字訊息,產業大類,產業小類,上傳日期,上傳者
		$header =array("materialId","materialType","adNameChar","adText","DName","MName","AddDateTime","AddUserId");
		fwrite($file,implode(GLUE,$header)."\n");
		
		$sql='	SELECT  DISTINCT 素材.素材識別碼,素材類型名稱,素材名稱,文字素材內容,上層產業類型.產業類型說明 AS 產業大類,產業類型.產業類型說明 AS 產業小類,素材.CREATED_TIME,使用者姓名
				FROM 素材 
					JOIN 素材類型 ON 素材.素材類型識別碼 = 素材類型.素材類型識別碼
					JOIN 使用者 ON 使用者.使用者識別碼 = 素材.CREATED_PEOPLE
					JOIN 託播單素材 ON 素材.素材識別碼 = 託播單素材.素材識別碼
					JOIN 託播單 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
					JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼
					JOIN 版位 版位類型 ON 版位類型.版位識別碼 = 版位.上層版位識別碼
					JOIN 產業類型 ON 產業類型.產業類型識別碼 = 素材.產業類型識別碼
					JOIN 產業類型 上層產業類型 ON 上層產業類型.產業類型識別碼 = 產業類型.上層產業類型識別碼
				WHERE 
					版位類型.版位名稱 IN ("首頁banner","專區banner","專區vod","頻道short EPG banner","barker頻道")
			';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$res=$stmt->get_result()){
			eixtWhitCode(500);
		}
	
		while($row = $res->fetch_assoc()){
			$row = array_map('n2s',$row);
			//轉換素材類型名稱
			switch($row['素材類型名稱']){
				case '文字':
					$row['素材類型名稱'] = 'text';
					break;
				case '圖片':
					$row['素材類型名稱'] = 'image';
					break;
				case '影片':
					$row['素材類型名稱'] = 'video';
					break;
			}
			$temp=array($row['素材識別碼'],$row['素材類型名稱'],nf_to_wf($row['素材名稱'], $types = 'nf_to_wf'),nf_to_wf($row['文字素材內容'], $types = 'nf_to_wf'),$row['產業大類'],$row['產業小類'],$row['CREATED_TIME'],$row['使用者姓名']);
			fwrite($file,implode(GLUE,$temp)."\n");
		}
		fclose($file);
	}
	
	//依照託播單識別碼取得參數
	function getOrderParater($oid){
		global $ptPara,$pPara,$my;
		//取得版位識別碼
		$sql='	SELECT 託播單.版位識別碼,版位.上層版位識別碼 FROM 託播單,版位 WHERE 託播單識別碼 = ? AND 版位.版位識別碼 = 託播單.版位識別碼';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->bind_param('i',$oid)) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$res=$stmt->get_result()){
			eixtWhitCode(500);
		}
		$row = $res->fetch_assoc();
		$pid = $row['版位識別碼'];
		$ptid = $row['上層版位識別碼'];
		//取得版位其他參數
		if(!isset($pPara[$pid])){
			$sql='	SELECT 版位其他參數順序,版位其他參數名稱,版位其他參數預設值 FROM 版位其他參數 WHERE 版位識別碼 = ?';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$pid)) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res=$stmt->get_result()){
				eixtWhitCode(500);
			}
			$temp = array();
			while($row = $res->fetch_assoc()){
				$temp[$row['版位其他參數順序']] = ['name'=>$row["版位其他參數名稱"],'value'=>$row["版位其他參數預設值"]];
			}
			$pPara[$pid] = $temp;
		}
		$pData = $pPara[$pid];
		
		//取得版位類型其他參數
		if(!isset($ptPara[$ptid])){
			$sql='	SELECT 版位其他參數順序,版位其他參數名稱,版位其他參數預設值 FROM 版位其他參數 WHERE 版位識別碼 = ?';
			if(!$stmt=$my->prepare($sql)) {
				eixtWhitCode(500);
			}
			if(!$stmt->bind_param('i',$ptid)) {
				eixtWhitCode(500);
			}
			if(!$stmt->execute()) {
				eixtWhitCode(500);
			}
			if(!$res=$stmt->get_result()){
				eixtWhitCode(500);
			}
			$temp = array();
			while($row = $res->fetch_assoc()){
				$temp[$row['版位其他參數順序']] = ['name'=>$row["版位其他參數名稱"],'value'=>$row["版位其他參數預設值"]];
			}
			$ptPara[$ptid] = $temp;
		}
		$ptData = $ptPara[$ptid];
		
		//取得託播單其他參數
		$sql='	SELECT 託播單其他參數順序,託播單其他參數值
			FROM 託播單其他參數
			WHERE 託播單識別碼 = ?
		';
		if(!$stmt=$my->prepare($sql)) {
			eixtWhitCode(500);
		}
		if(!$stmt->bind_param('i',$oid)) {
			eixtWhitCode(500);
		}
		if(!$stmt->execute()) {
			eixtWhitCode(500);
		}
		if(!$res=$stmt->get_result()){
			eixtWhitCode(500);
		}
		$temp = array();
		while($row = $res->fetch_assoc()){
			if(isset($ptData[$row['託播單其他參數順序']])){
				$temp[$row['託播單其他參數順序']] = ['name'=>$ptData[$row['託播單其他參數順序']]["name"],'value'=>$row["託播單其他參數值"]];
			}			
		}
		$oData = $temp;
		
		//整理並回傳
		//已版位類型參數為基準，逐一覆蓋
		$return = array();
		foreach($ptData as $data){
			$return [$data['name']] = $data['value'];
		}
		foreach($pData as $data){
			$return [$data['name']] = $data['value'];
		}
		foreach($oData as $data){
			$return [$data['name']] = $data['value'];
		}
		
		return $return;
	}
	//將NULL轉為字串
	function n2s($str){
		if($str == null)
			return 'NULL';
		else
			//return  nf_to_wf($str, $types = 'nf_to_wf');
			return  $str;
	}
	
	//特殊符號處理:轉全形
	function nf_to_wf($strs, $types = 'nf_to_wf'){ //全形半形轉換
		$nft = array(
			"(", ")", "[", "]", "{", "}", ".", ",", ";", ":",
			"-", "?", "!", "@", "#", "$", "%", "&", "|", "\\",
			"/", "+", "=", "*", "~", "`", "'", "\"", "<", ">",
			"^", "_", "[", "]"
		);
		$wft = array(
			"（", "）", "〔", "〕", "｛", "｝", "﹒", "，", "；", "：",
			"－", "？", "！", "＠", "＃", "＄", "％", "＆", "｜", "＼",
			"／", "＋", "＝", "＊", "～", "、", "、", "＂", "＜", "＞",
			"︿", "＿", "【", "】"
		);

		if ( $types == 'nf_to_wf' ){// 轉全形
			return str_replace($nft, $wft, $strs);
		}else if( $types == 'wf_to_nf' ){// 轉半形
			return str_replace($wft, $nft, $strs);
		}else{
			return $strtmp;
		}
	}
	
?>
