<?php 
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/tool/MyDB.php';
	require_once dirname(__FILE__).'/tool/MyLogger.php';
	define('HOME','/home/ams/IabFiles/');
	//define('HOME','order/851/');
	define('GLUE','$');
	$my=new MyDB(true);
	
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
					版位類型.版位名稱 IN ("首頁banner","專區banner","專區vod","頻道short EPG banner")
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
			$forExcel = array($row['委刊單識別碼'],$row['廣告主名稱'],$row['廣告主統一編號'],$row['頻道商名稱'],$row['頻道商統一編號'],$row['承銷商名稱'],$row['承銷商統一編號'],$row1['廣告期間開始時間']
				,$row1['廣告期間結束時間'],$row['委刊單名稱'],$row['CREATED_TIME'],$row['填單者'],$row['LAST_UPDATE_TIME'],$row['修改者']);
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
				)
				;
		fwrite($file,implode(GLUE,$header)."\n");
		
		$sql='	SELECT  託播單.託播單識別碼
				,託播單.版位識別碼
				,版位類型.版位識別碼 AS 版位類型識別碼
				,版位類型.版位名稱 AS 版位類型名稱
				,版位.版位名稱
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
					JOIN 委刊單 ON 託播單.委刊單識別碼 = 委刊單.委刊單識別碼
					JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
					LEFT JOIN 素材 ON 託播單素材.素材識別碼 = 素材.素材識別碼
					JOIN 使用者 C ON 託播單.CREATED_PEOPLE = C.使用者識別碼
					LEFT JOIN 使用者 U ON 託播單.LAST_UPDATE_PEOPLE =U.使用者識別碼
				WHERE
					版位類型.版位名稱 IN ("首頁banner","專區banner","專區vod","頻道short EPG banner")
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
			$sql='	SELECT 版位其他參數名稱,託播單其他參數值
				FROM 託播單其他參數,託播單,版位,版位其他參數
				WHERE 託播單.託播單識別碼 = 託播單其他參數.託播單識別碼 AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 
				AND 版位其他參數.版位其他參數順序 = 託播單其他參數.託播單其他參數順序 AND 版位其他參數名稱 IN ("bakadschdDisplayMax","bannerTransactionId1","bannerTransactionId2","bakadschdDisplaySequence")
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
			$orderParam=array("bakadschdDisplayMax"=>NULL,"bannerTransactionId1"=>NULL,"bannerTransactionId2"=>NULL,"bakadschdDisplaySequence"=>NULL);
			while($row1 = $res1->fetch_assoc()){
				$orderParam[$row1['版位其他參數名稱']]=$row1['託播單其他參數值'];
			}
			
			
			//取得版位其他參數
			$sql='	SELECT 版位其他參數名稱,版位其他參數預設值
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
			}
			
			$orderParam = array_map('n2s',$orderParam);
			$positionParam = array_map('n2s',$positionParam);
			$temp = array(
				$row['委刊單識別碼'],
				$row['託播單CSMS群組識別碼'],
				$row['版位類型名稱'],
				$row['版位名稱'],
				(isset($positionParam['adSizetype'])?$positionParam['adSizetype']:'NULL'),
				(isset($positionParam['bnrSequence'])?$positionParam['bnrSequence']:'NULL'),
				$row['廣告期間開始時間'],
				$row['廣告期間結束時間'],
				$hours[0],
				end($hours),
				','.$row['廣告可被播出小時時段'].',',
				$row['委刊單名稱'],
				$row['託播單名稱'],
				$row['影片素材秒數'],
				$location,
				($location=='N'?($row['點擊後開啟類型'].'&'.$row['點擊後開啟位址']):'NULL'),
				($location=='C'?($row['點擊後開啟類型'].'&'.$row['點擊後開啟位址']):'NULL'),
				($location=='S'?($row['點擊後開啟類型'].'&'.$row['點擊後開啟位址']):'NULL'),
				($location=='N'?$orderParam['bakadschdDisplayMax']:'NULL'),
				($location=='C'?$orderParam['bakadschdDisplayMax']:'NULL'),
				($location=='S'?$orderParam['bakadschdDisplayMax']:'NULL'),
				$orderParam['bakadschdDisplaySequence'],
				$row['CREATED_TIME'],
				$row['填單者'],
				$row['LAST_UPDATE_TIME'],
				$row['修改者'],
				$row['素材識別碼'],
				$orderParam['bannerTransactionId1'],
				$orderParam['bannerTransactionId2']
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
					版位類型.版位名稱 IN ("首頁banner","專區banner","專區vod","頻道short EPG banner")
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
			$temp=array($row['素材識別碼'],$row['素材類型名稱'],$row['素材名稱'],$row['文字素材內容'],$row['產業大類'],$row['產業小類'],$row['CREATED_TIME'],$row['使用者姓名']);
			fwrite($file,implode(GLUE,$temp)."\n");
		}
		fclose($file);
	}
	
	//將NULL轉為字串
	function n2s($str){
		if($str == null)
			return 'NULL';
		else
			return $str;
	}
	
?>
