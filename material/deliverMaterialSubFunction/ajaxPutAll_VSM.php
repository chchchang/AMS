<?php
//function putAll(){
	$localPath=MATERIAL_FOLDER;
	if(is_file($localPath.$_POST['local'])===false){
		header('Content-Type: application/json');
		exit(json_encode(array('error'=>'找不到指定素材，可能是素材未到位或檔案遺失，請上傳後再派送。')));
	}
	$result=array();
	$hostRes = array();
	require '../tool/SFTP.php';
	foreach(Config::$FTP_SERVERS['VSM'] as $server){
		$remotePath=$server['圖片素材路徑'];
		$result[]=SFTP::putAndRename($server['host'],$server['username'],$server['password'],$localPath.$_POST['local'],$remotePath.$_POST['remote'],MATERIAL_FOLDER_URL.$_POST['remote'].'.temp')?true:false;
		//$result[]=true;
		$hostRes[$server['host']]=$result[count($result)-1];
	}
	recordResult($_POST['素材識別碼'],$hostRes);
	header('Content-Type: application/json');
	exit(json_encode(array('error'=>'','local'=>$_POST['local'],'remote'=>$_POST['remote'],'result'=>$result)));
//}
?>