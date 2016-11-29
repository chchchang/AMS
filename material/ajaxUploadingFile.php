<?php
	include('../tool/auth/authAJAX.php');
	
	$tempDir='tempFile';
	if(!file_exists ($tempDir)){
		if (!mkdir($tempDir, 0777, true)) 
			exit(json_encode(array("success" => false,"message" => "原始檔案暫存資料夾建立失敗"),JSON_UNESCAPED_UNICODE));
	}
	
	$tempDir='tempFile/'.$_SESSION['AMS']['使用者識別碼'];
	if(!file_exists ($tempDir)){
		if (!mkdir($tempDir, 0777, true)) 
			exit(json_encode(array("success" => false,"message" => "原始檔案暫存資料夾建立失敗"),JSON_UNESCAPED_UNICODE));
	}
	if(isset($_FILES['fileToUpload'])){
		if($_FILES['fileToUpload']['error']>0){
			exit(json_encode(array("success" => false,"message" => "原始檔案上傳失敗"),JSON_UNESCAPED_UNICODE));
		}
		$fileNameA=explode(".",$_FILES['fileToUpload']['name']);
		$type = end($fileNameA);
		if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'],$tempDir.'/'.hash('ripemd160',iconv('UTF-8', 'UCS-4', $_FILES['fileToUpload']['name'])).'.'.$type))//複製檔案
			exit (json_encode(array("success" => true,"message" => "原始檔案上傳成功"),JSON_UNESCAPED_UNICODE));
	}
?>
	