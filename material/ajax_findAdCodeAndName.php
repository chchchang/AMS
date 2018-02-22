<?php
	include('../tool/auth/authAJAX.php');
	
	//獲得素材adCode
	$sql = 'SELECT 素材識別碼,素材原始檔名,產業類型名稱
			FROM 素材,產業類型
			WHERE 素材.產業類型識別碼 = 產業類型.產業類型識別碼 AND 素材.素材名稱 = ?
		';
	if(!$stmt=$my->prepare($sql)) {
		exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
	}
	if(!$stmt->bind_param('s',$_POST['素材名稱'])) {
		exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
	}
	if(!$stmt->execute()) {
		exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
	}
	if(!$res=$stmt->get_result()){
		exit(json_encode(array("success"=>false,'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
	}
	$materialInfo=$res->fetch_assoc();
	$adCode = $materialInfo['產業類型名稱'].str_pad($materialInfo['素材識別碼'], 8, '0', STR_PAD_LEFT);
	$fileNameA = explode('.',$materialInfo['素材原始檔名']);
	$type = end($fileNameA);
	$name = '_____AMS_'.$materialInfo['素材識別碼'].'.'.$type;
	exit(json_encode(array("success"=>true,'adCode'=>$adCode,'name'=>$name),JSON_UNESCAPED_UNICODE));
?>
	