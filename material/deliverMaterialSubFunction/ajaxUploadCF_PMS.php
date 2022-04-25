<?php
//function uploadCF(){
	if(strtolower($_POST['副檔名'])==='ts')
		$ftp_servers=Config::$FTP_SERVERS['PMS_TS'];
	else if(strtolower($_POST['副檔名'])==='mpg')
		$ftp_servers=Config::$FTP_SERVERS['PMS'];
	else{
		$json=array('success'=>false,'error'=>'副檔名錯誤，僅支援.mpg或.ts！');
		header('Content-Type: application/json');
		exit(json_encode($json));
	}
	
	$local=MATERIALPATH.$_POST['素材識別碼'].'.'.$_POST['副檔名'];
	if(($md5_result=md5_file($local))===false){
		$json=array('success'=>false,'error'=>'計算檔案md5值失敗！');
		header('Content-Type: application/json');
		exit(json_encode($json));
	}
	$remote='_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.'.$_POST['副檔名'];
	$poscessing ='_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.ams';
	require '../tool/FTP.php';
	$result=FTP::isAllFile($ftp_servers,$remote);
	if($result[0])
		$json=array('success'=>false,'error'=>'檔案已存在，請等待PMS自動派片！');
	else{
		$result=FTP::putAll($ftp_servers,$local,$remote,$remote.'.temp');
		if(!$result[0])
			$json=array('success'=>false,'error'=>'上傳檔案失敗！');
		else{
			$my=new MyDB(true);
			$sql='UPDATE 素材 SET 影片派送時間=?,LAST_UPDATE_TIME=?,LAST_UPDATE_PEOPLE=? WHERE 素材識別碼=?';
			$sendTime=date('Y-m-d H:i:s');
			if(
				($stmt=$my->prepare($sql))
				&&($stmt->bind_param('ssii',$sendTime,$sendTime,$_SESSION['AMS']['使用者識別碼'],$_POST['素材識別碼']))
				&&($stmt->execute())
			){
				$json=array('success'=>true,'影片派送時間'=>$sendTime);
			}
			else{
				$json=array('success'=>false,'error'=>'更新派送狀態失敗！');
			}
		}
	}
	header('Content-Type: application/json');
	exit(json_encode($json));
//}
?>