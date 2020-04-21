<?php
	//function uploadCF(){
	$server=Config::$FTP_SERVERS['CAMPS_MATERIAL'][0];
	$local=MATERIALPATH.$_POST['素材識別碼'].'.'.$_POST['副檔名'];
	if(is_file($local)===false){
		header('Content-Type: application/json');
		exit(json_encode(array('success'=>false,'error'=>'找不到指定素材，可能是素材未到位或檔案遺失，請上傳後再操作。')));
	}
	if(($md5_result=md5_file($local))===false){
		$json=array('success'=>false,'error'=>'計算檔案md5值失敗！');
		header('Content-Type: application/json');
		exit(json_encode($json));
	}
	$remote=$server['上傳目錄'].'_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.'.$_POST['副檔名'];
	//$remote=$server['上傳目錄'].'_____AMS_'.$_POST['素材識別碼'].'_'.$_POST['素材原始檔名'];
	
	require '../tool/FTP.php';
	$result=FTP::putAndRename($server['host'],$server['username'],$server['password'],$local,$remote,$remote.'.temp');
	if(!$result)
		$json=array('success'=>false,'error'=>'上傳檔案失敗！');
	else{
		$my=new MyDB(true);
		$sql='UPDATE 素材 SET CAMPS影片派送時間=?,LAST_UPDATE_TIME=?,LAST_UPDATE_PEOPLE=? WHERE 素材識別碼=?';
		$CAMPS影片派送時間=date('Y-m-d H:i:s');
		if(
			($stmt=$my->prepare($sql))
			&&($stmt->bind_param('ssii',$CAMPS影片派送時間,$CAMPS影片派送時間,$_SESSION['AMS']['使用者識別碼'],$_POST['素材識別碼']))
			&&($stmt->execute())
		){
			$json=array('success'=>true,'CAMPS影片派送時間'=>$CAMPS影片派送時間);
		}
		else{
			$json=array('success'=>false,'error'=>'更新派送狀態失敗！');
		}
	}
	header('Content-Type: application/json');
	$json['$remote']=$remote;
	exit(json_encode($json));
	//}
?>