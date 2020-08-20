<?php
	/****
	更新從VSM撈取最新版位資訊並更新
	*****/
	ini_set('display_errors','1');
	error_reporting(E_ALL);
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	require_once '../Config_VSM_Meta.php';
	require '../tool/SFTP.php';
	define("MATERIAL_FOLDER", Config::GET_MATERIAL_FOLDER());
	define("MATERIAL_FOLDER_URL", "uploadedFile/");
	$logger=new MyLogger();
	//連線DB
	$my=new MyDB(true);
	
	resendBanner();
	
	function resendBanner(){
		global $my,$logger;
		$本地路徑=MATERIAL_FOLDER;
		//取得素材群組識別碼
		$sql='SELECT 素材群組識別碼 FROM 素材群組 WHERE 素材群組名稱 = ?';
		$result =$my->getResultArray($sql,'s',"VSM單一平台預設廣告");
		$mgroupId = $result[0]["素材群組識別碼"];
		//取得素材識別碼
		$sql='SELECT * FROM 素材 WHERE 素材群組識別碼 = ?';
		$result =$my->getResultArray($sql,'i',$mgroupId);
		foreach($result as $row){
			$pattern = explode(".",$row["素材原始檔名"]);
			$mname  = $row["素材識別碼"].".".end($pattern);
			//無實體檔案跳過
			echo $mname."......";
			if(is_file($本地路徑.$mname)===false){
				echo "無本機檔案\n";
				continue;
			}
			foreach(Config::$FTP_SERVERS['VSM'] as $server){
				$遠端路徑=$server['圖片素材路徑'];
				echo $server['host'];
				if(SFTP::putAndRename($server['host'],$server['username'],$server['password'],$本地路徑.$mname,$遠端路徑."_____AMS_".$mname,MATERIAL_FOLDER_URL."_____AMS_".$mname.'.temp')){
					echo "成功";
				}
				else
					echo "失敗";
			}
			echo "\n";
		}
	}
?>