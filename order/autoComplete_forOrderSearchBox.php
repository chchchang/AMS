<?php
	include('../tool/auth/authAJAX.php');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit(json_encode(array('無法連線到資料庫，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit(json_encode(array('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	$values=[];
	$result=[];
	if(isset($_POST['term'])){
		if($_POST['method']=='託播單查詢'){
			$term = '%'.$_POST['term'].'%';
			$sql="SELECT DISTINCT 託播單名稱,託播單說明 FROM 託播單 WHERE 託播單名稱 LIKE ? OR 託播單說明 LIKE ?";
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ss',$term,$term)) {
				$logger->error('無法綁定參數，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			while($row = $res->fetch_array()){
				foreach($row as $value){
					if($value!=null&&$value!='')
					if(!in_array($value,$values)){
						$result[] = ['value'=>$value,'id'=>$value];
						$values[]=$value;
					}
				}				
			}
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		if($_POST['method']=='委刊單查詢'){
			$term = '%'.$_POST['term'].'%';
			$sql="SELECT DISTINCT 委刊單名稱,委刊單說明 FROM 委刊單 WHERE 委刊單名稱 LIKE ? OR 委刊單說明 LIKE ?";
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ss',$term,$term)) {
				$logger->error('無法綁定參數，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			while($row = $res->fetch_array()){
				foreach($row as $value){
					if($value!=null&&$value!='')
					if(!in_array($value,$values)){
						$result[] = ['value'=>$value,'id'=>$value];
						$values[]=$value;
					}
				}				
			}
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		if($_POST['method']=='廣告主查詢'){
			$term = '%'.$_POST['term'].'%';
			$sql="SELECT DISTINCT 廣告主名稱,承銷商名稱,頻道商名稱 FROM 廣告主 WHERE 廣告主名稱 LIKE ? OR 承銷商名稱 LIKE ? OR 頻道商名稱 LIKE ?";
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('sss',$term,$term,$term)) {
				$logger->error('無法綁定參數，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			while($row = $res->fetch_array()){
				foreach($row as $value){
					if($value!=null&&$value!='')
					if(!in_array($value,$values)){
						$result[] = ['value'=>$value,'id'=>$value];
						$values[]=$value;
					}
				}				
			}
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
	}
	

	$my->close();
?>