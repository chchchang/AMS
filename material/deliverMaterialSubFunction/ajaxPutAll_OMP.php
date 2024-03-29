<?php
//function putAll(){
	if(isset($_POST['area'])&&(array_search($_POST['area'],array('OMP_N','OMP_C','OMP_S'))!==false)&&isset($_POST['local'])&&isset($_POST['remote'])){
	$localPath=MATERIAL_FOLDER;
	if(is_file($localPath.$_POST['local'])===false){
		header('Content-Type: application/json');
		exit(json_encode(array('error'=>'找不到指定素材，可能是素材未到位或檔案遺失，請上傳後再派送。')));
	}
	$result=array();
	$hostRes = array();
	require '../tool/SFTP.php';
	foreach(Config::$FTP_SERVERS[$_POST['area']] as $server){
		$remotePath=($_POST['type']==='專區')?$server['專區banner圖片素材路徑']:$server['頻道short EPG banner圖片素材路徑'];
		$result[]=SFTP::put($server['host'],$server['username'],$server['password'],$localPath.$_POST['local'],$remotePath.$_POST['remote'])?true:false;
		//$result[]=true;
		$hostRes[$server['host']]=$result[count($result)-1];
	}
	recordResult($_POST['素材識別碼'],$hostRes);
	header('Content-Type: application/json');
	exit(json_encode(array('error'=>'','area'=>$_POST['area'],'type'=>$_POST['type'],'local'=>$_POST['local'],'remote'=>$_POST['remote'],'result'=>$hostRes)));
	}
//}
?>