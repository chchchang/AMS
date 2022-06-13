<?php
	//20220510 增加取得狀態前的是否派送檢查
	//function isAllFile(){+
	//取得資料庫的送出結果
	$my = new MyDB();
	$sql='SELECT 圖片素材派送結果 FROM 素材 WHERE 素材識別碼=?';
	$sendedServer=$my->getResultArray($sql,'i',$_POST["素材識別碼"]);
	if($sendedServer!=null){
		$sendedServer = json_decode($sendedServer[0]['圖片素材派送結果']);
	}
	else{
		$sendedServer = [];
	}

	if(isset($_POST['area'])&&(array_search($_POST['area'],array('OMP_N','OMP_C','OMP_S'))!==false)&&isset($_POST['type'])&&(array_search($_POST['type'],array('專區','EPG'))!==false)&&isset($_POST['remote'])){
		$result=array();
		$hostRes = array();
		require '../tool/FTP.php';
		foreach(Config::$FTP_SERVERS[$_POST['area']] as $server){
			if(!in_array($server['host'], $sendedServer)){
				$hostRes[$server['host']]=false;
			}
			else{
				$remotePath=($_POST['type']==='專區')?$server['專區banner圖片素材路徑']:$server['頻道short EPG banner圖片素材路徑'];
				$result[]=FTP::isFile($server['host'],$server['username'],$server['password'],$remotePath.$_POST['remote'])?true:false;
				//$result[]=true;
				$hostRes[$server['host']]=$result[count($result)-1];
			}
		}
		recordResult($_POST['素材識別碼'],$hostRes);
		header('Content-Type: application/json');
		exit(json_encode(array('area'=>$_POST['area'],'type'=>$_POST['type'],'remote'=>$_POST['remote'],'result'=>$hostRes)));
	}
	//}
?>