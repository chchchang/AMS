<?php
	header('Content-Type: text/html; charset=utf-8');
	require_once 'tool/MyDB.php';
	
	if(isset($_POST['mid'])){
		//取得素材基本資訊
		$my = new MyDB();
		$sql = 'SELECT 素材原始檔名,素材類型識別碼,素材識別碼 FROM 素材 WHERE 素材識別碼 = ?';
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["mid"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		$row = $res->fetch_assoc();
		if($row['素材類型識別碼']!=3)
			exit('非影片素材');
		$type = end(explode('.',$row['素材原始檔名']));
		$mid = $row['素材識別碼'];
		
		//查詢pms派送狀況
		$local='/opt/lampp/htdocs/AMS/material/uploadedFile/'.$mid.'.'.$type;
		$local=Config::GET_MATERIAL_FOLDER().$mid.'.'.$type;
		if(($md5_result=md5_file($local))===false){
			$json=array('success'=>false,'error'=>'計算檔案md5值失敗！'.$local);
			header('Content-Type: application/json');
			print_r($json);
			//exit(json_encode($json));
		}
		$remoteFileName='_____AMS_'.$mid.'_'.$md5_result;
		$url='http://172.17.251.133/api/getMediaStatus?source='.$remoteFileName;
		print_r('遠端檔案名稱:'.$remoteFileName);
		echo "<br>";
		$ch=curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$return=curl_exec($ch);
		print_r((string)$return);
		echo '</br></br>';
		libxml_use_internal_errors(true); // !!!
		if(curl_errno($ch))
		{
			$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
		}
		$return=preg_replace('~\s*(<([^-->]*)>[^<]*<!--\2-->|<[^>]*>)\s*~','$1',$return);
		$xml=simplexml_load_string($return);
		if($xml !== false)
		{
			echo '媒體編號:'.(string)$xml->mediaId;
		}
		else
		{
			foreach(libxml_get_errors() as $error)
			{
				echo($error->message);
			}
		}
			
	}
?>
<!DOCTYPE html>
<html>
<body>

<form action="?" method="post">
素材識別碼 <input type="text" name="mid" value=""><input type="submit" value="查詢">
</form>

</body>
</html>
