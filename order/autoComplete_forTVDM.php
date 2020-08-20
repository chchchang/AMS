<?php
	include('../tool/auth/authAJAX.php');
	include('../other/TVDM/Config_TVDM.php');
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
	$feedback=[];
	if(isset($_POST['term'])){
		$term = "%".$_POST['term']."%";
		$sql="SELECT TVDM識別碼 FROM TVDM廣告服務 WHERE TVDM識別碼 LIKE ?";
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('s',$term)) {
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
		$result = array();
		while($row = $res->fetch_array()){
			foreach($row as $value){
				if($value!=null&&$value!='')
				if(!in_array($value,$values)){
					$url = "";
					switch($_POST["target"]){
						case "OMP" :
							$url = "TVDM_".$value;
						break;
						case "VSM" :
							$url = Config_TVDM::GET_IAP_HD_URL($value);
						break;
					}
					$result[] = ['value'=>$url,'id'=>$url];
					$values[]=$value;
				}
			}				
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		
	}
?>