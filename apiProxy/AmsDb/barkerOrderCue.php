<?php
	/**
	*對barkerCue資料表操作
	**/
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	require_once dirname(__FILE__)."/module/BarkerOrderCueRepository.php";
	require_once("./module/TransactionRepository.php");
	//$_POST[]:insert :新增資料，若有相同資料則更新
	//$_POST[]:delete :刪除資料
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$my=new MyDB(true);
	$CueRepo = new BarkerOrderCueRepository($my);
	if(isset($_POST["insert"])){
		$result = $CueRepo->insertCue($_POST["insert"]);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getCueInfo"])){
		$result = $CueRepo->getCueInfo($_POST["getCueInfo"]);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["disable"])){
		$result = $CueRepo->disableCueByTransactionId($_POST["disable"]);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if (isset($_POST["getCueFromPlaylist"])){
		require_once("./module/PlaylistCueConverter.php");
		$convertor = new PlaylistCueConverter($my);
		$cue = $convertor->playlistToCue(
			[		
			"transaction_id"=>$_POST["getCueFromPlaylist"]["transactionId"],
			"startDate"=>$_POST["getCueFromPlaylist"]["startDate"],
			"endDate"=>$_POST["getCueFromPlaylist"]["endDate"]
			]
			,$my);
		exit(json_encode($cue,JSON_UNESCAPED_UNICODE));
	}
	else if (isset($_POST["cueToPlaylist"])){
		$hour = $_POST["cueToPlaylist"]["hour"];
		$cue = $CueRepo->getCueInfo($_POST["cueToPlaylist"]);
		if(!is_array($cue)){
			exit(json_encode([],JSON_UNESCAPED_UNICODE));	
		}
		$TransactionRepo = new TransactionRepository($my);
		$transactionMap = array();
		$reslut = array();
		foreach($cue as $i=>$data){
			if($data[$hour]==0)
				continue;
			$tmp = array();
			if(!isset($transactionMap[$data["transaction_id"]])){

			}
			array_push($reslut,$tmp);
		}
		exit(json_encode($reslut,JSON_UNESCAPED_UNICODE));
	}
?>