<?php
	//function getAndPutStatus(){
	//先透過API取得狀態
		global $logger;
		$local=MATERIALPATH.$_POST['素材識別碼'].'.'.$_POST['副檔名'];
		if(is_file($local)===false){
			header('Content-Type: application/json');
			exit(json_encode(array('success'=>false,'error'=>'找不到指定素材，可能是素材未到位或檔案遺失，請上傳後再操作。')));
		}
		if(($md5_result=md5_file($local))===false){
			$json=array('success'=>false,'error'=>'計算檔案md5值失敗！');
			header('Content-Type: application/json; charset=utf-8');
			exit(json_encode($json));
		}
		$片名='_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result;
		//$url='http://172.17.251.134/PMS4/pts_media_status.php?v_id=2305&source='.$片名;
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
		$chtnStatus=(string)$xml->chtnStatus;
		$chtcStatus=(string)$xml->chtcStatus;
		$chtsStatus=(string)$xml->chtsStatus;
		$chtnIapId=(string)$xml->chtnIapId;
		$chtsIapId=(string)$xml->chtsIapId;
		//再更新到資料庫
		$my=new MyDB(true);
		$sql='UPDATE 素材 SET 影片媒體編號=?,影片媒體編號北=?,影片媒體編號南=? WHERE 素材識別碼=?';
		if(
			($stmt=$my->prepare($sql))
			&&($stmt->bind_param('sssi',$mediaId,$chtnIapId,$chtsIapId,$_POST['素材識別碼']))
			&&($stmt->execute())
		){
			//更新成功才回傳狀態
			$json=json_encode(array('success'=>true,'mediaId'=>$mediaId,'chtnStatus'=>$chtnStatus,'chtcStatus'=>$chtcStatus,'chtsStatus'=>$chtsStatus,'chtnIapId'=>$chtnIapId,'chtsIapId'=>$chtsIapId));
		}
		else{
			//更新失敗只回傳失敗
			$json=json_encode(array('success'=>false));
		}
		header('Content-Type: application/json; charset=utf-8');
		exit($json);
	//}
?>