<?php
	//function isAllFile(){
	$result=array();
	$hostRes = array();
	require '../tool/SFTP.php';
	foreach(Config::$FTP_SERVERS['VSM'] as $server){
		$遠端路徑=$server['圖片素材路徑'];
		$result[]=SFTP::isFile($server['host'],$server['username'],$server['password'],$遠端路徑.$_POST['remote'])?true:false;
		//$result[]=true;
		$hostRes[$server['host']]=$result[count($result)-1];
	}
	recordResult($_POST['素材識別碼'],$hostRes);
	header('Content-Type: application/json');
	exit(json_encode(array('remote'=>$_POST['remote'],'result'=>$result)));
	//}
?>