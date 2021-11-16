<?php
	include('../tool/auth/authAJAX.php');
	define("URLPREFIX",(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".Config::PROJECT_ROOT."position");
	define('POSITIONIMGDIR',"image");
	if (isset($_POST['action'])){
		switch($_POST['action']){
			case "getInfoPic":
				if(isset($_POST['版位識別碼'])){
					$pid = $_POST['版位識別碼'];
					$path = "";
					if(isset($_POST['版位類型識別碼'])){
						$path = 	getInfoPic($pid,$_POST['版位類型識別碼']);
					}
					else{
						$path = getInfoPic($pid);
					}
					exit(json_encode(array("success"=>true,"src"=>$path)));
				}
				break;
			case "setInfoPic":
				if(isset($_POST['版位識別碼'])){
					setInfoPic($_POST['版位識別碼']);
				}
				break;
			case "deleteInfoPic":
				if(isset($_POST['版位識別碼'])){
					deleteInfoPic($_POST['版位識別碼']);
				}
				break;
			default:
				break;
		}
		exit();
	}

	
	/**取得版位示意圖**/
	function getInfoPic($pid,$ptid = null){
		global $logger;
		$inPicUrl = "";
		foreach (glob(POSITIONIMGDIR."/".$pid.".*") as $filename) {
			$inPicUrl = URLPREFIX."/$filename";
			break;
		}
		//版位識別碼查無圖片資訊，改用板位類型識別碼查詢
		if($inPicUrl == ""){
			foreach (glob(POSITIONIMGDIR."/".$ptid.".*") as $filename) {
				$inPicUrl = URLPREFIX."/$filename";
				break;
			}
		}
		return $inPicUrl;	
	}	

	function setInfoPic($pid){
		
	}

	function deleteInfoPic($pid){
		foreach (glob(POSITIONIMGDIR."/".$pid.".*") as $filename) {
			$inPicUrl = $filename;
			unlink($inPicUrl);
		}
	}
	
?>