<?php
	include('../tool/auth/authAJAX.php');
	
	$dir='image';
	if(isset($_FILES['fileToUpload'])){
		if($_FILES['fileToUpload']['error']>0){
			$logger->error('原始檔案上傳失敗,錯誤訊息('.json_encode($_FILES['fileToUpload']['error'],JSON_UNESCAPED_UNICODE).')。');
			exit(json_encode(array("success" => false,"message" => "原始檔案上傳失敗"),JSON_UNESCAPED_UNICODE));
		}
		$awaitingFile = $_SESSION['AMS']['使用者識別碼']."_await";
		//移除暫存檔案
		foreach (glob($dir."/".$awaitingFile.".*") as $filename) {
			unlink($filename);
		}
		//判斷有無版位識別碼
		if(!isset($_POST['版位識別碼'])){
			exit (json_encode(array("success" => false,"message" => "無法取得版位識別碼"),JSON_UNESCAPED_UNICODE));
		}
		$newFileName = $_POST['版位識別碼'];
		if($_POST['版位識別碼']==""||$_POST['版位識別碼']==null)
			$newFileName = $awaitingFile;
		$fileNameA=explode(".",$_FILES['fileToUpload']['name']);
		$type = end($fileNameA);
		if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'],$dir.'/'.$newFileName.'.'.$type))//複製檔案
			exit (json_encode(array("success" => true,"message" => "原始檔案上傳成功"),JSON_UNESCAPED_UNICODE));
	}
?>
	