<?php
//20220510 增加取得狀態前的是否派送檢查
//function getAndPutStatus(){
	if(isset($_POST['素材識別碼'])&&isset($_POST['素材原始檔名'])){
		//先檢查有無派送過
		$my = new MyDB();
		$sql='SELECT * FROM barker_material_import_result WHERE material_id=?';
		$sended=$my->getResultArray($sql,'i',$_POST["素材識別碼"]);
		if($sended==null||$sended[0]["CAMPS影片派送時間"]==null||$sended[0]["CAMPS影片派送時間"]==""){
			$json=array('success'=>false,'error'=>'請先派送影片');
			header('Content-Type: application/json');
			exit(json_encode($json));
		}

		$materialUrl=Config::$CAMPS_API['material'];
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
		$remoteFileName='_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.'.$_POST['副檔名'];
		//$remoteFileName='_____AMS_'.$_POST['素材識別碼'].'_'.$_POST['素材原始檔名'];
		$url = $materialUrl.'?file_name='.$remoteFileName;
		//$url = 'localhost/AMS/test.php';
		//取得CAMPS影片媒體編號
		$ch=curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$getResult = json_decode(curl_exec($ch),true);
		$mcheck=false;
		if(count($getResult)>0){
			foreach($getResult as $id=>$mvalue){
				if($mvalue['status']=='ready' && $mvalue['md5']==$md5_result){
					$mcheck=true;
					//再更新到資料庫
					$mediaId = $mvalue['material_id'];
					$runtime = $mvalue['run_time'];
					$my=new MyDB(true);
					$sql='UPDATE 素材 SET CAMPS影片媒體編號=?,影片素材秒數=? WHERE 素材識別碼=?';
					if(
						($stmt=$my->prepare($sql))
						&&($stmt->bind_param('sii',$mediaId,$runtime,$_POST['素材識別碼']))
						&&($stmt->execute())
					){
						//更新成功才回傳狀態
						$json=json_encode(array('success'=>true,'mediaId'=>$mediaId));
					}
					else{
						//更新失敗只回傳失敗
						$json=json_encode(array('success'=>false,'error'=>'CAMPS影片媒體代碼更新失敗'));
					}
					break;
				}
			}
		}
		if(!$mcheck){
			//未取得CAMPS媒體識別碼，檢查是否仍在上傳目錄處理中
			require '../tool/FTP.php';
			$server=Config::$FTP_SERVERS['CAMPS_MATERIAL'][0];
			//$remote=$server['上傳目錄'].'_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.'.$_POST['副檔名'];
			$remote=$server['上傳目錄'].$remoteFileName;
			$result=FTP::isFile($server['host'],$server['username'],$server['password'],$remote);
			if($result){
				//檔案在上傳目錄中，回傳等待處理
				$json=json_encode(array('success'=>true,'mediaId'=>'請等待CAMPS處理'));
			}
			else
			{
				//沒有在上傳目錄中，回傳失敗
				$json=json_encode(array('success'=>true,'mediaId'=>''));
			}
		}
		header('Content-Type: application/json; charset=utf-8');
		exit($json);
	}
//}
?>