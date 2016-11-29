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
	if(isset($_POST['term'])){
		$term = $_POST['term'];
		$result=array();
		
		switch($_POST['input']){
			case "廣告主名稱":
				$sql="SELECT 廣告主名稱 as value,廣告主識別碼 as id FROM 廣告主 WHERE 廣告主名稱 LIKE '%".$term."%'";
				break;
			case "廣告主統一編號":
				$sql="SELECT 廣告主統一編號 as value,廣告主識別碼 as id FROM 廣告主 WHERE 廣告主統一編號 LIKE '%".$term."%'";
				break;
			case "廣告主地址":
				$sql="SELECT 廣告主地址 as value,廣告主識別碼 as id FROM 廣告主 WHERE 廣告主地址 LIKE '%".$term."%'";
				break;
			case "廣告主聯絡人姓名":
				$sql="SELECT 廣告主聯絡人姓名 as value,廣告主識別碼 as id FROM 廣告主 WHERE 廣告主聯絡人姓名 LIKE '%".$term."%'";
				break;
			case "廣告主聯絡人電話":
				$sql="SELECT 廣告主聯絡人電話 as value,廣告主識別碼 as id FROM 廣告主 WHERE 廣告主聯絡人電話 LIKE '%".$term."%'";
				break;
				
			case "頻道商名稱":
				$sql="SELECT 頻道商名稱 as value,廣告主識別碼 as id FROM 廣告主 WHERE 頻道商名稱 LIKE '%".$term."%'";
				break;
			case "頻道商統一編號":
				$sql="SELECT 頻道商統一編號 as value,廣告主識別碼 as id FROM 廣告主 WHERE 頻道商統一編號 LIKE '%".$term."%'";
				break;
			case "頻道商地址":
				$sql="SELECT 頻道商地址 as value,廣告主識別碼 as id FROM 廣告主 WHERE 頻道商地址 LIKE '%".$term."%'";
				break;
			case "頻道商聯絡人姓名":
				$sql="SELECT 頻道商聯絡人姓名 as value,廣告主識別碼 as id FROM 廣告主 WHERE 頻道商聯絡人姓名 LIKE '%".$term."%'";
				break;
			case "頻道商聯絡人電話":
				$sql="SELECT 頻道商聯絡人電話 as value,廣告主識別碼 as id FROM 廣告主 WHERE 頻道商聯絡人電話 LIKE '%".$term."%'";
				break;
				
			case "承銷商名稱":
				$sql="SELECT 承銷商名稱 as value,廣告主識別碼 as id FROM 廣告主 WHERE 承銷商名稱 LIKE '%".$term."%'";
				break;
			case "承銷商統一編號":
				$sql="SELECT 承銷商統一編號 as value,廣告主識別碼 as id FROM 廣告主 WHERE 承銷商統一編號 LIKE '%".$term."%'";
				break;
			case "承銷商地址":
				$sql="SELECT 承銷商地址 as value,廣告主識別碼 as id FROM 廣告主 WHERE 承銷商地址 LIKE '%".$term."%'";
				break;
			case "承銷商聯絡人姓名":
				$sql="SELECT 承銷商聯絡人姓名 as value,廣告主識別碼 as id FROM 廣告主 WHERE 承銷商聯絡人姓名 LIKE '%".$term."%'";
				break;
			case "承銷商聯絡人電話":
				$sql="SELECT 承銷商聯絡人電話 as value,廣告主識別碼 as id FROM 廣告主 WHERE 承銷商聯絡人電話 LIKE '%".$term."%'";
				break;

		}
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
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
			$result[] = $row;
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST['getData'])){
		switch($_POST['getData']){
			case "廣告主資料":
				$sql="SELECT 廣告主名稱 ,廣告主地址,廣告主統一編號,廣告主聯絡人姓名,廣告主聯絡人電話 FROM 廣告主 WHERE 廣告主識別碼=?";
				break;
			case "承銷商資料":
				$sql="SELECT 承銷商名稱 ,承銷商地址,承銷商統一編號,承銷商聯絡人姓名,承銷商聯絡人電話 FROM 廣告主 WHERE 廣告主識別碼=?";
				break;
			case "頻道商資料":
				$sql="SELECT 頻道商名稱 ,頻道商地址,頻道商統一編號,頻道商聯絡人姓名,頻道商聯絡人電話 FROM 廣告主 WHERE 廣告主識別碼=?";
				break;
		}
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["廣告主識別碼"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		$row = $res->fetch_array();
		exit(json_encode($row,JSON_UNESCAPED_UNICODE));
	}
	

	$my->close();
?>