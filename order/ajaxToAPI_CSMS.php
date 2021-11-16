<?php
	//用於處理CSMS託播單，需備/order/ajaxToAPI.php使用，否則無法運作
	require_once('../tool/auth/authAJAX.php');
	if (!defined('MATERIAL_FOLDER'))
		define("MATERIAL_FOLDER", Config::GET_MATERIAL_FOLDER());
	//const VSMapiUrl = 'localhost/VSMAPI/VSMAdData.php';	
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
				,'bakadschdDisplaySequence','bakadschdDisplayMax','bannerTransactionId1','bannerTransactionId2','bannerTransactionId3','bannerTransactionId4'];
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
		
		$ids = $_POST['ids'][0];
		//輸出
		//OutputExcel::outputAll('851/'.implode(',',$_POST['ids']),$forExcel);
		OutputExcel::outputAll('851/'.$ids,$forExcel);
		//FTP上傳
		//$ids = implode(',',$_POST['ids']);
		$localfile='../order/851/'.$ids.'.xls';			
		if(is_file($localfile)===false){
			exit(json_encode(array("success"=>false,'message'=>'找不到介接檔案，請重新派送。'.$本地路徑,'id'=>implode(',',$_POST['ids']))));
		}
		$uploadingMeta = getUploadingMeta($_POST['ids'][0],$action);
		if($uploadingMeta['area']=='IAP'){
			recordResult(($action=='send')?'insert':'delete',1,null,null);
			changeOrderSate(($action=='send')?'送出':'取消送出',$_POST['ids']);
		}
		else{
			fileToFTP($_POST['ptName'],$uploadingMeta,$ids,$localfile);
			changeOrderSate('待處理',$_POST['ids']);
		}
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
				,'bakadschdDisplaySequence','bakadschdDisplayMax','bannerTransactionId1','bannerTransactionId2','bannerTransactionId3','bannerTransactionId4'];
				break;
			case 'Vod+廣告':
				$forExcel[]=['vodscheTransactionId','ovaVodContent','vodmovVideoIsRandom','ovaVodContentBySdVodcntRecid','videoadDurationSecs','vodscheStartDate','vodscheEndDate','vodscheAssignStartTime','vodscheAssignEndTime','vodscheDisplaySequence','vodscheDisplayMax'];
				$forExcel2[]=['vodscheTransactionId','ovaVodContent','vodmovVideoIsRandom','bnradCode','bnradName','bnradAdType','bnradImg','bnradLinkType','bnradLinkValue'
				,'vodscheStartDate','vodscheEndDate','vodscheAssignStartTime','vodscheAssignEndTime','vodscheDisplaySequence','vodscheDisplayMax'];
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
		$uploadingMeta = getUploadingMeta($_POST["託播單識別碼"],$action);
		if($uploadingMeta['area']=='IAP'){
			recordResult(($action=='send')?'insert':'delete',1,null,null);
			changeOrderSate(($action=='send')?'送出':'取消送出',isset($_POST['ids'])?$_POST['ids']:array($_POST['託播單識別碼']));	
		}
		else{
			fileToFTP($result['版位類型名稱'],$uploadingMeta,$_POST["託播單識別碼"],$localfile);		
			if(isset($_POST['ids']))
				changeOrderSate('待處理',$_POST['ids']);
			else
				changeOrderSate('待處理',array($_POST['託播單識別碼']));
		}
		
	}
	
	//取得CSMS託播單資訊
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
	
	function fileToFTP($ptN,$uploadingMeta,$ids,$localfile){
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
		$remotefile = $CSMSFTP[0]['待處理資料夾路徑'].'/'.$fileName.'.'.$uploadingMeta['sendAction'].'.'.$uploadingMeta['gId'].'.xls';

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
		@FTP::delete($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],'./'.$remotefile.'.fin');
			
		if(!FTP::rename($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],'./'.$remotefile,'./'.$remotefile.'.fin'))
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗:更名介接檔案失敗','id'=>$ids)));
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
					影片畫質識別碼,
					素材類型識別碼
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
			if($res->num_rows>1){
				$otherMaterial=[];
				while($row = $res->fetch_assoc())
				$otherMaterial[]=$row;	//有可能有兩筆素材，一筆為SD、另一筆為HD。
			}
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
					if(!$stmt->bind_param('i',$otherMaterial[0]['素材識別碼'])) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤','id'=>$_POST["託播單識別碼"]),JSON_UNESCAPED_UNICODE));
					}
					$mInfo2 = $res->fetch_assoc();
					
					$SD影片=($result1['影片畫質識別碼']===1?$result1['素材識別碼']:(isset($otherMaterial[0]['影片畫質識別碼'])?($otherMaterial[0]['影片畫質識別碼']===1?$otherMaterial[0]['素材識別碼']:null):null));
					$HD影片=($result1['影片畫質識別碼']===2?$result1['素材識別碼']:(isset($otherMaterial[0]['影片畫質識別碼'])?($otherMaterial[0]['影片畫質識別碼']===2?$otherMaterial[0]['素材識別碼']:null):null));
					if($SD影片!=null){
						$sql = 'SELECT 素材原始檔名,
									影片素材秒數
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
						$SD影片 = '_____AMS_'.$SD影片.'_'.md5_file(MATERIAL_FOLDER.$SD影片.'.'.$type);
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
						$HD影片 = '_____AMS_'.$HD影片.'_'.md5_file(MATERIAL_FOLDER.$HD影片.'.'.$type);
						//$HD影片 = '_____AMS_24_dc433015e5a1f26282b5fcc08000a1dc';
						//*!*!*!staging end
					}
					$data=[$result1['託播單CSMS群組識別碼'],$SD影片,$HD影片,$result3['bakadDisplayMax'],$result1['點擊後開啟類型'],$result1['點擊後開啟位址'],$result3['serCode'],$result1['廣告期間開始時間'],$result1['廣告期間結束時間']
					,$startTime,$endTime,$result3['bakadschdDisplaySequence'],$result3['bakadschdDisplayMax'],$result3['bannerTransactionId1'],$result3['bannerTransactionId2'],$result3['bannerTransactionId3'],$result3['bannerTransactionId4']
					];
					break;
				case 'Vod+廣告':
					$sql = 'SELECT 素材原始檔名
							FROM 素材
							WHERE 素材.素材識別碼 = ?
							';
					$data['video']= [$result1['託播單CSMS群組識別碼'].'_video',$result3['vodmovBnrIsRandom']];
					$VOD主影片=($result1['影片畫質識別碼']===1?$result1['素材識別碼']:null);
					$fileNameA = explode('.',$mInfo['素材原始檔名']);
					$type = end($fileNameA);
					$VOD主影片 = '_____AMS_'.$VOD主影片.'_'.md5_file(MATERIAL_FOLDER.$VOD主影片.'.'.$type);
					$data = [];
					foreach($otherMaterial as $material){
						if($material['素材類型識別碼']==3){
							
						}
					}
					$data['banner']= [];
					break;
			}			
			return $data;
	}
	
	function getUploadingMeta($id,$action){
		global $my;
		$sql='
			SELECT
				A.託播單CSMS群組識別碼,A.廣告可被播出小時時段,A1.版位名稱,A.託播單送出行為識別碼,A.託播單送出後是否成功,A2.版位名稱 AS 版位類型名稱
			FROM
				託播單 A LEFT JOIN 版位 A1 ON A1.版位識別碼 = A.版位識別碼 LEFT JOIN 版位 A2 ON A1.上層版位識別碼 = A2.版位識別碼
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
		
		if($action=="send")
			$sendAction = 'insert';
		else
			$sendAction = 'delete';
		
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
		
		//$sendAction = getActionFormRemote($res[0]['版位類型名稱'],$sendAction,$area,$res[0]['託播單CSMS群組識別碼']);
		return array('area'=>$area,'sendAction'=>$sendAction,'gId'=>$res[0]['託播單CSMS群組識別碼'],'startTime'=>$startTime,'endTime'=>$endTime);
	}
	
	
	function getActionFormRemote($版位類型名稱,$動作,$區域,$託播單CSMS群組識別碼){
		require_once '../tool/OracleDB.php';
		//return true;
		//取得OMP資料庫檔案並比較
		if($區域==='N'){
			$DB_U = Config::OMP_N_ORACLE_DB_USER;
			$DB_T_O = Config::OMP_N_ORACLE_DB_TABLE_OWNER;
			$DB_P = Config::OMP_N_ORACLE_DB_PASSWORD;
			$DB_S = Config::OMP_N_ORACLE_DB_CONN_STR;
		}
		else if($區域==='C'){
			$DB_U = Config::OMP_C_ORACLE_DB_USER;
			$DB_T_O = Config::OMP_C_ORACLE_DB_TABLE_OWNER;
			$DB_P = Config::OMP_C_ORACLE_DB_PASSWORD;
			$DB_S = Config::OMP_C_ORACLE_DB_CONN_STR;
		}
		else{
			$DB_U = Config::OMP_S_ORACLE_DB_USER;
			$DB_T_O = Config::OMP_S_ORACLE_DB_TABLE_OWNER;
			$DB_P = Config::OMP_S_ORACLE_DB_PASSWORD;
			$DB_S = Config::OMP_S_ORACLE_DB_CONN_STR;
		}
		
		$oracleDB=new OracleDB($DB_U,$DB_P,$DB_S);
			
		$inner_error = '';//記錄內部錯誤訊息用
		//OMP資料STATUS:0 準備中, 1 上架, 2 下架
		if($版位類型名稱 == '首頁banner' || $版位類型名稱 == '專區banner'){
			$sql='
				SELECT
					CAS.TRANSACTION_ID,
					CA.AD_CODE,
					CA.AD_TYPE,
					CA.AD_NAME,
					LK.LINK_TYPE,
					LK.LINK_CHAN_RECID,
					LK.LINK_CAT_RECID,
					LK.LINK_VODCNT_RECID,
					LK.LINK_SRVC_RECID,
					CA.AD_IMG_OFF,
					CB.BNR_SIZETYPE,
					CS.SER_CODE,
					CSTB.BNR_SEQUENCE,
					to_char(CAS.SCHD_START_DATE,\'YYYY/MM/DD HH24:MI\') SCHD_START_DATE,
					to_char(CAS.SCHD_END_DATE,\'YYYY/MM/DD HH24:MI\') SCHD_END_DATE,
					CAS.ASSIGN_START_TIME,
					CAS.ASSIGN_END_TIME,
					CAS.SCHD_STATUS
				FROM '.$DB_T_O.'.CS_SERVICE CS
				INNER JOIN '.$DB_T_O.'.CS_SER_TMP_BNR CSTB ON CS.SER_RECID=CSTB.SER_RECID
				INNER JOIN '.$DB_T_O.'.CS_AD_SCHEDULE CAS ON CSTB.CS_STB_RECID=CAS.CS_STB_RECID
				INNER JOIN '.$DB_T_O.'.CS_BANNER CB ON CSTB.BNR_RECID=CB.BNR_RECID
				INNER JOIN '.$DB_T_O.'.CS_AD CA ON CA.AD_RECID=CAS.AD_RECID
				INNER JOIN '.$DB_T_O.'.CS_LINK LK ON CA.LINK_RECID=LK.LINK_RECID
				WHERE
					CAS.TRANSACTION_ID=:TID
			';
			$vars=array(
				array('bv_name'=>':TID','variable'=>$託播單CSMS群組識別碼)
			);
			$result=$oracleDB->getResultArray($sql,$vars);
		}
		else if($版位類型名稱 == '頻道short EPG banner'){
			$sql='
				SELECT
					CSS.SEPG_TRANSACTION_ID,
					OC.CHAN_NUMBER,
					CA.AD_CODE,
					CA.AD_TYPE,
					CA.AD_NAME,
					LK.LINK_TYPE,
					LK.LINK_CHAN_RECID,
					LK.LINK_CAT_RECID,
					LK.LINK_VODCNT_RECID,
					LK.LINK_SRVC_RECID,
					CA.AD_IMG_OFF,
					CSS.SEPG_DEFAULT_FLAG,
					to_char(CSS.SEPG_START_DATE,\'YYYY/MM/DD HH24:MI\') SEPG_START_DATE,
					to_char(CSS.SEPG_END_DATE,\'YYYY/MM/DD HH24:MI\') SEPG_END_DATE,
					CSS.SEPG_ASSIGN_START_TIME,
					CSS.SEPG_ASSIGN_END_TIME,
					CSS.SEPG_STATUS
				FROM '.$DB_T_O.'.OVA_CHANNEL OC
				INNER JOIN '.$DB_T_O.'.CS_SEPG_CHAN_RELATION CSCR ON OC.CHAN_RECID=CSCR.CHAN_RECID
				INNER JOIN '.$DB_T_O.'.CS_SEPG_SCHEDULE CSS ON CSCR.SEPG_SCHDID=CSS.SEPG_SCHDID
				INNER JOIN '.$DB_T_O.'.CS_AD CA ON CA.AD_RECID=CSS.AD_RECID
				INNER JOIN '.$DB_T_O.'.CS_LINK LK ON CA.LINK_RECID=LK.LINK_RECID
				WHERE
					CSS.SEPG_TRANSACTION_ID=:TID
				ORDER BY
					CSS.SEPG_START_DATE,
					CSS.SEPG_TRANSACTION_ID
			';
			$vars=array(
				array('bv_name'=>':TID','variable'=>$託播單CSMS群組識別碼)
			);
			$result=$oracleDB->getResultArray($sql,$vars);
		}
		else if($版位類型名稱 == '專區vod'){
			$sql='
				SELECT
					CBAS.BAKADSCHD_TRANSACTION_ID,
					CBA.BAKAD_NAME,
					CBA.SD_VODCNT_RECID,
					CBA.HD_VODCNT_RECID,
					OVCSD.VODCNT_TITLE AS SD_VODCNT_TITLE,
					OVCHD.VODCNT_TITLE AS HD_VODCNT_TITLE,
					CBA.BAKAD_DISPLAY_MAX,
					LK.LINK_TYPE,
					LK.LINK_CHAN_RECID,
					LK.LINK_CAT_RECID,
					LK.LINK_VODCNT_RECID,
					LK.LINK_SRVC_RECID,
					CS.SER_CODE,
					to_char(CBAS.BAKADSCHD_START_DATE,\'YYYY/MM/DD HH24:MI\') BAKADSCHD_START_DATE,
					to_char(CBAS.BAKADSCHD_END_DATE,\'YYYY/MM/DD HH24:MI\') BAKADSCHD_END_DATE,
					CBAS.BAKADSCHD_ASSIGN_START_TIME,
					CBAS.BAKADSCHD_ASSIGN_END_TIME,
					CBAS.BAKADSCHD_DISPLAY_SEQUENCE,
					CBAS.BAKADSCHD_DISPLAY_MAX,
					CAS.TRANSACTION_ID,
					CBAS.BAKADSCHD_STATUS,
					CSTB.BNR_SEQUENCE
				FROM '.$DB_T_O.'.CS_BARKER_AD_SCHEDULE CBAS
				INNER JOIN '.$DB_T_O.'.CS_SERVICE CS ON CBAS.SER_RECID=CS.SER_RECID
				INNER JOIN '.$DB_T_O.'.CS_BARKER_AD CBA ON CBA.BAKAD_RECID=CBAS.BAKAD_RECID
				INNER JOIN '.$DB_T_O.'.CS_LINK LK ON CBA.LINK_RECID=LK.LINK_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.CS_GROUP_AD CGA ON CBAS.BAKADSCHD_RECID=CGA.BAKADSCHD_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.CS_AD_SCHEDULE CAS ON CAS.AD_SCHDID=CGA.AD_SCHDID
				LEFT OUTER JOIN '.$DB_T_O.'.CS_SER_TMP_BNR CSTB ON CSTB.CS_STB_RECID=CAS.CS_STB_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.OVA_VOD_CONTENT OVCSD ON CBA.SD_VODCNT_RECID=OVCSD.VODCNT_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.OVA_VOD_CONTENT OVCHD ON CBA.HD_VODCNT_RECID=OVCHD.VODCNT_RECID
				WHERE
					CBAS.BAKADSCHD_TRANSACTION_ID=:TID
				ORDER BY
					CBAS.BAKADSCHD_START_DATE,
					CBAS.BAKADSCHD_TRANSACTION_ID
			';
			$vars=array(
				array('bv_name'=>':TID','variable'=>$託播單CSMS群組識別碼)
			);
			$result=$oracleDB->getResultArray($sql,$vars);
			
			//整理結果,將相同託播單但不同連動廣告的查詢結果合併，且將連動廣告依照BNR_SEQUENCE分類並用','串聯成字串
			$resultTemp=[];
			$bnrTid=[];
			foreach($result as $row){
				$transactionId = $row['BAKADSCHD_TRANSACTION_ID'];
				if(!array_key_exists($transactionId,$resultTemp))
					$resultTemp[$transactionId] = $row;
				if(!array_key_exists($transactionId,$resultTemp))
					$bnrTid[$transactionId] = [1=>[],2=>[]];
				if(!in_array($row['TRANSACTION_ID'],$bnrTid[$transactionId][$row['BNR_SEQUENCE']]))
					$bnrTid[$transactionId][$row['BNR_SEQUENCE']][]=$row['TRANSACTION_ID'];
			}
			foreach($resultTemp as $tid=>$data){
				$resultTemp[$tid]['TRANSACTION_ID1'] = implode(',',$bnrTid[$tid][1]);
				$resultTemp[$tid]['TRANSACTION_ID2'] = implode(',',$bnrTid[$tid][2]);
			}
			$result = $resultTemp;
		}
		switch($動作){
			case 'insert':
			case 'update':
				if(count($result)==0){
					changeLastAction("insert",0);
					return "insert";
				}
				else{
					changeLastAction("update",0);
					return "update";
				}
			break;
			case 'delete':
				changeLastAction("delete",0);
					return "delete";
			break;
		}
	}
	
	//更改託播單送出行為
	function changeLastAction($action,$success){
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
		$sql = "UPDATE 託播單 SET 託播單送出行為識別碼=?,託播單送出後是否成功=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=?";
		if(!$stmt=$my->prepare($sql)) {
		}
		if(!$stmt->bind_param('iisi',$action,$success,$_SESSION['AMS']['使用者識別碼'],$_POST['託播單識別碼'])) {
		}
		if(!$stmt->execute()) {
		}
	}
	
	
?>
