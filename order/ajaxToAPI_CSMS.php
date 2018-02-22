<?php
	//用於處理CSMS託播單，需備/order/ajaxToAPI.php使用，否則無法運作
	require_once('../tool/auth/authAJAX.php');
	if (!defined('MATERIAL_FOLDER'))
		define("MATERIAL_FOLDER", Config::GET_MATERIAL_FOLDER());
	//const VSMapiUrl = 'localhost/VSMAPI/VSMAdData.php';	
	//批次送出
	function produceFileBetch_851($action = null){
		global $API852Url,$logger,$my;
		//簡查OVASERVICE是否認定
		$checkres = checkOvaService($_POST['託播單識別碼']);
		if(!$checkres['success'])
			exit(json_encode($checkres,JSON_UNESCAPED_UNICODE));
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
	
	//產生851用的excel檔案
	function produceFile_851($action = null){
		global $API852Url,$logger,$my;
		//簡查OVASERVICE是否認定
		$checkres = checkOvaService($_POST['託播單識別碼']);
		if(!$checkres['success'])
			exit(json_encode($checkres,JSON_UNESCAPED_UNICODE));
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
				$forExcel[]=['vodscheTransactionId','ovaVodContent','vodmovBnrIsRandom','ovaVodContentBySdVodcntRecid','videoadDurationSecs','vodscheStartDate','vodscheEndDate','vodscheAssignStartTime','vodscheAssignEndTime','vodscheDisplaySequence','vodscheDisplayMax'];
				$forExcel2[]=['vodscheTransactionId','ovaVodContent','vodmovVideoIsRandom','bnradCode','bnradName','bnradAdType','bnradImg','bnradLinkType','bnradLinkValue'
				,'vodscheStartDate','vodscheEndDate','vodscheAssignStartTime','vodscheAssignEndTime','vodscheDisplaySequence','vodscheDisplayMax'];
			break;
		}
		$excelData=getExcelData($_POST["託播單識別碼"]);
		//輸出檔案
		if($result['版位類型名稱']=='Vod+廣告'){
			//VOD+版位須分開處理
			//產生檔案
			if(isset($excelData['video'])){
				$forExcel[] = $excelData['video'];
				OutputExcel::outputAll('851/'.$_POST["託播單識別碼"].'_video',$forExcel);
				$videoExcelFile='../order/851/'.$_POST["託播單識別碼"].'_video.xls';			
			}
			if(isset($excelData['banner'])){
				$forExcel2[] = $excelData['banner'];
				OutputExcel::outputAll('851/'.$_POST["託播單識別碼"].'_banner',$forExcel2);
				$bannerExcelFile='../order/851/'.$_POST["託播單識別碼"].'_banner.xls';			
			}
			
			
			if(isset($videoExcelFile)){
				if(is_file($videoExcelFile)===false){
					exit(json_encode(array("success"=>false,'message'=>'找不到介接檔案'.$videoExcelFile.'，請重新派送。'.$本地路徑,'id'=>$_POST["託播單識別碼"])));
				}
			}
			if(is_file($bannerExcelFile)===false){
				exit(json_encode(array("success"=>false,'message'=>'找不到介接檔案'.$bannerExcelFile.'，請重新派送。'.$本地路徑,'id'=>$_POST["託播單識別碼"])));
			}
			//FTP上傳
			$uploadingMeta = getUploadingMeta_VODPlus($_POST["託播單識別碼"]);
			if($uploadingMeta['area']=='IAP'){
				recordResult(($action=='send')?'insert':'delete',1,null,null);
				changeOrderSate(($action=='send')?'送出':'取消送出',isset($_POST['ids'])?$_POST['ids']:array($_POST['託播單識別碼']));	
			}
			else{
				//整理要上傳的版位名稱
				fileToFTP_VODPlus($result['版位類型名稱'],$uploadingMeta,$_POST["託播單識別碼"],$localfile,$action);		
				if(isset($_POST['ids']))
					changeOrderSate('待處理',$_POST['ids']);
				else
					changeOrderSate('待處理',array($_POST['託播單識別碼']));
			}
		}
		else{
			$forExcel[]=$excelData;
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
	
	function fileToFTP_VODPlus($ptN,$uploadingMeta,$ids,$action,$localfiles){
		$CSMSFTP =Config::$FTP_SERVERS['CSMS_'.$uploadingMeta['area']];
		//送出檔案
		if(isset($uploadingMeta['bannerSendAction'])){
			$localfile = $localfiles['banner'];
			if($action == 'send')
				$remotefile_banner = $CSMSFTP[0]['待處理資料夾路徑'].'/'.$fileName.'.'.$uploadingMeta['sendAction'].'.'.$uploadingMeta['gId'].'_banner.xls';
			else
				$remotefile_banner = $CSMSFTP[0]['待處理資料夾路徑'].'/'.$fileName.'.delete.'.$uploadingMeta['gId'].'_banner.xls';
			$result=FTP::putAll($CSMSFTP,$localfile,$remotefile_banner);
			$downloadfile = '../order/851/'.$ids.'_banner_check.xls' ;
			if(!FTP::get($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],$downloadfile,'./'.$remotefile_banner)){
				exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗','id'=>$ids)));
			}
			if(!PHPExtendFunction::isFilesSame($localfile,$downloadfile))
				exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗','id'=>$ids)));
			//刪除下載回來比較的檔案
			unlink($downloadfile);
		}
		if(isset($uploadingMeta['videoSendAction'])){
			$localfile = $localfiles['video'];
			if($action == 'send')
				$remotefile_video = $CSMSFTP[0]['待處理資料夾路徑'].'/'.$fileName.'.'.$uploadingMeta['sendAction'].'.'.$uploadingMeta['gId'].'_video.xls';
			else
				$remotefile_video = $CSMSFTP[0]['待處理資料夾路徑'].'/'.$fileName.'.delete.'.$uploadingMeta['gId'].'_video.xls';
			$result=FTP::putAll($CSMSFTP,$localfile,$remotefile_video);
			$downloadfile = '../order/851/'.$ids.'_video_check.xls' ;
			if(!FTP::get($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],$downloadfile,'./'.$remotefile_video)){
				exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗','id'=>$ids)));
			}
			if(!PHPExtendFunction::isFilesSame($localfile,$downloadfile))
				exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗','id'=>$ids)));
			//刪除下載回來比較的檔案
			unlink($downloadfile);
		}
		//刪除本地檔案
		//unlink($localfile);
		//確認檔案傳輸完成，副檔名加上.fin
		if(isset($uploadingMeta['bannerSendAction'])){
			if(!FTP::rename($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],'./'.$remotefile_banner,'./'.$remotefile_banner.'.fin'))
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗:檔案已存在','id'=>$ids)));
		}
		if(isset($uploadingMeta['videoSendAction'])){
			if(!FTP::rename($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],'./'.$remotefile_video,'./'.$remotefile_video.'.fin'))
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗:檔案已存在','id'=>$ids)));
		}
		
		DELETALLFILE:
		if(isset($uploadingMeta['bannerSendAction'])){
			if(!FTP::rename($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],'./'.$remotefile_banner,'./'.$remotefile_banner.'.fin'))
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗:檔案已存在','id'=>$ids)));
		}
		if(isset($uploadingMeta['videoSendAction'])){
			if(!FTP::rename($CSMSFTP[0]['host'],$CSMSFTP[0]['username'],$CSMSFTP[0]['password'],'./'.$remotefile_video,'./'.$remotefile_video.'.fin'))
			exit(json_encode(array("success"=>false,'message'=>'介接檔案上傳失敗:檔案已存在','id'=>$ids)));
		}
		
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
				$otherMaterial[]=$row;	//有可能有多筆素材。
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
					$mInfo = $my->getResultArray($sql,'i',$result1['素材識別碼'])[0];			
					$VOD主影片=($result1['影片畫質識別碼']===1?$result1['素材識別碼']:null);
					$fileNameA = explode('.',$mInfo['素材原始檔名']);
					$type = end($fileNameA);
					$VOD主影片 = '_____AMS_'.$VOD主影片.'_'.md5_file(MATERIAL_FOLDER.$VOD主影片.'.'.$type);
					//設定共用參數
					$data=[];
					$commitDate=[$result1['廣告期間開始時間'],$result1['廣告期間結束時間'],$startTime,$endTime,$result3['vodscheDisplaySequence'],$result3['vodscheDisplayMax']];
					foreach($otherMaterial as $material){
						if($material['素材類型識別碼']==3){
							//VOD+影片
							$sql = 'SELECT 素材原始檔名,影片素材秒數
							FROM 素材
							WHERE 素材.素材識別碼 = ?
							';
							$mInfo = $my->getResultArray($sql,'i',$material['素材識別碼'])[0];			
							$VOD影片=($material['影片畫質識別碼']===1?$material['素材識別碼']:null);
							$fileNameA = explode('.',$mInfo['素材原始檔名']);
							$type = end($fileNameA);
							$VOD影片 = '_____AMS_'.$VOD影片.'_'.md5_file(MATERIAL_FOLDER.$VOD影片.'.'.$type);
							$isRandom = ($result3['vodmovVideoIsRandom']=='1')?'Y':'N';
							$data['video']= [$result1['託播單CSMS群組識別碼'].'_video',$VOD主影片,$isRandom,$VOD影片,$mInfo['影片素材秒數']];
							$data['video']= array_merge($data['video'],$commitDate);
						}
						else if($material['素材類型識別碼']==2){
							//VOD+banner
							//獲得素材adCode
							$sql = 'SELECT 素材識別碼,素材原始檔名,產業類型名稱
									FROM 素材,產業類型
									WHERE 素材.產業類型識別碼 = 產業類型.產業類型識別碼 AND 素材.素材識別碼 = ?
								';
							$materialInfo=$my->getResultArray($sql,'i',$result1['素材識別碼'])[0];
							$adCode = $materialInfo['產業類型名稱'].str_pad($materialInfo['素材識別碼'], 8, '0', STR_PAD_LEFT);
							$mNameA =explode('.',$materialInfo['素材原始檔名']);
							$type = end($mNameA);
							$isRandom = ($result3['vodmovBnrIsRandom']=='1')?'Y':'N';
							$data['banner']= [$result1['託播單CSMS群組識別碼'].'_banner',$VOD主影片,$isRandom,$adCode,$result1['託播單名稱'],$result3['bnradAdType'],'_____AMS_'.$result1['素材識別碼'].'.'.$type,$result1['點擊後開啟類型'],$result1['點擊後開啟位址']];
							$data['banner']= array_merge($data['banner'],$commitDate);
						}
					}				
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
					//前次是insert且成功，本次動作必為 delete，所以sendAction無關緊要
					//若前次動作insert但失敗，本次動作為 insert，不須更新
					break;
				case 2:
					//前次是udate且成功，本次動作必為 delete，所以sendAction無關緊要
					//若前次動錯事update但失敗，本次send動作依然為update，更新sendAction
					if($res[0]['託播單送出後是否成功']==0)
						$sendAction = 'update';
					break;
				case 3:
					//前次是delete且失敗，本次動作必為 delete，所以sendAction無關緊要
					//若前次動錯事update且成功，本次send動作須為update，更新sendAction
					if($res[0]['託播單送出後是否成功']==1)
						$sendAction = 'update';
					break;
			}
		}
		return array('area'=>$area,'sendAction'=>$sendAction,'gId'=>$res[0]['託播單CSMS群組識別碼'],'startTime'=>$startTime,'endTime'=>$endTime);
	}
	
	function getUploadingMeta_VODPlus($id){
		//先取得基本上傳資訊
		$res = getUploadingMeta($id);
		//再依其他參數取得實際要送出的動作
		//in process
		global $my;
		$sql='
			SELECT
				託播單banner參數.託播單其他參數值 AS lastBannerResult,託播單video參數.託播單其他參數值 lastVideoResult
			FROM
				託播單 
				LEFT JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼
				LEFT JOIN 版位其他參數 版位banner參數 ON 版位.上層版位識別碼 = 版位banner參數.版位識別碼 AND 版位banner參數.版位其他參數名稱 = "bannerSendResult"
				LEFT JOIN 託播單其他參數 託播單banner參數 ON 版位banner參數.版位其他參數順序 = 託播單banner參數.託播單其他參數順序 AND 託播單banner參數.託播單識別碼 = 託播單.託播單識別碼
				LEFT JOIN 版位其他參數 版位video參數 ON 版位.上層版位識別碼 = 版位video參數.版位識別碼 AND 版位video參數.版位其他參數名稱 = "videoSendResult"
				LEFT JOIN 託播單其他參數 託播單video參數 ON 版位video參數.版位其他參數順序 = 託播單video參數.託播單其他參數順序 AND 託播單video參數.託播單識別碼 = 託播單.託播單識別碼
			WHERE
				託播單.託播單識別碼=?
		';
		$lasSend=$my->getResultArray($sql,'i',$id)[0];
		
		if($lasSend['lastVideoResult'] == NULL)
			$bannerSendAction='insert';
		else{
			//分析上次的送出記錄		
			$tempArray = explode(':',$res['videoSendAction']);
			$lastAction = $tempArray[0];
			$lastResult = $tempArray[1];
			if($lastAction == 'insert'){
				//前次是insert且成功，本次動作必為 delete，所以sendAction無關緊要
				//若前次動作insert但失敗，本次動作為 insert
				$videoSendAction='insert';
			}
			else if($lastAction == 'update'){
				//前次是udate且成功，本次動作必為 delete，所以sendAction無關緊要
				//若前次動錯事update但失敗，本次send動作依然為update，更新sendAction
				if($lastResult != 'success')
					$videoSendAction='update';
			}else if($lastAction == 'delete'){
				//前次是delete且失敗，本次動作必為 delete，所以sendAction無關緊要
				//若前次動錯事update且成功，本次send動作須為update，更新sendAction
				if($lastResult == 'success')
					$videoSendAction='update';
			}
			$res['videoSendAction']=$videoSendAction;
		}
		
		if($lasSend['lastBannerResult'] == NULL)
			$bannerSendAction='insert';
		else{
			//分析上次的送出記錄
			$tempArray = explode(':',$res['bannerSendAction']);
			$lastAction = $tempArray[0];
			$lastResult = $tempArray[1];
			if($lastAction == 'insert'){
				//前次是insert且成功，本次動作必為 delete，所以sendAction無關緊要
				//若前次動作insert但失敗，本次動作為 insert
				$bannerSendAction='insert';
			}
			else if($lastAction == 'update'){
				//前次是udate且成功，本次動作必為 delete，所以sendAction無關緊要
				//若前次動錯事update但失敗，本次send動作依然為update，更新sendAction
				if($lastResult != 'success')
					$bannerSendAction='update';
			}else if($lastAction == 'delete'){
				//前次是delete且失敗，本次動作必為 delete，所以sendAction無關緊要
				//若前次動錯事update且成功，本次send動作須為update，更新sendAction
				if($lastResult == 'success')
					$bannerSendAction='update';
			}
			$res['bannerSendAction']=$bannerSendAction;
		}
	}
	
	function getAreaByPname($pName){
		if(PHPExtendFunction::stringEndsWith($pName,'_北'))
		$area = 'N';
		else if(PHPExtendFunction::stringEndsWith($pName,'_中'))
		$area = 'C';
		else if(PHPExtendFunction::stringEndsWith($pName,'_南'))
		$area = 'S';
		else if(PHPExtendFunction::stringEndsWith($pName,'_IAP'))
		$area = 'IAP';
		return $area;
	}
	
	function checkOvaService($oid){
		global $API852Url,$logger,$my;
		//先取得託播單資訊與對應素材資訊
		$sql='
			SELECT
				版位.版位名稱,
				託播單素材.點擊後開啟類型,
				託播單素材.點擊後開啟位址,
				託播單狀態識別碼
			FROM
				託播單
				INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
				INNER JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼
				INNER JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
			WHERE
				託播單.託播單識別碼=?
		';
		$res=$my->getResultArray($sql,'i',$oid);
		$result=$res[0];
		if($result['點擊後開啟類型'] != 'OVA_SERVICE')
			return array('success'=>true,'message'=>'success');
		$area = getAreaByPname($result['版位名稱']);
		//IAP不需檢查
		if($area == 'IAP')
			return array('success'=>true,'message'=>'success');
		//利用GET簡查OVA SERVICE名稱設定
		$url = $SERVER_SITE.Config::PROJECT_ROOT.'order/checkCSMSOvaServer.php?area='.$area.'&SRVC_NAME='.$result['點擊後開啟位址'];
		$res = file_get_contents($url);
		return json_decode($res);
	}
?>