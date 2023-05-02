<?php
	/**
	*對barkerCue資料表操作
	**/
	require_once dirname(__FILE__)."/../../../tool/MyDB.php";
	require_once dirname(__FILE__)."/../module/PlayListRepository.php";
	require_once dirname(__FILE__)."/../module/TransactionRepository.php";
	//$date = "2023-04-27";
	//$channel = "2";
	$date = $_POST["date"];
	$channel_id = $_POST["channel_id"];
	$hour = isset($_POST["hour"])?$_POST["hour"]:null;

	$my=new MyDB(true);
	$playListRepository = new PlayListRepository($my);
	$transactionRepository = new TransactionRepository($my);
	$searchTerm = ["date"=>$date,"channel_id"=>$channel_id];
	if($hour!=null)
		$searchTerm["hour"]=$hour;
	$sch = $playListRepository->getPlaylistSechdule($searchTerm);
	$dailyRecords=[];
	$playlistHash = [];
	$tracsactionHash=[];
	$materialHash=[];

	foreach($sch as $i=>$records){
		if(!isset($playlistHash[$records["playlist_id"]])){
			$playlistHash[$records["playlist_id"]] = $playListRepository->getPlaylistRecord(["playlist_id"=>$records["playlist_id"]]);
		}
		foreach($playlistHash[$records["playlist_id"]] as $i=>$record){
			if(!isset($tracsactionHash[$record["transaction_id"]])){
				$tracsactionHash[$record["transaction_id"]] = $transactionRepository->getTransactionBasicInfo($record["transaction_id"]);
				$materialHash[$record["transaction_id"]] = $transactionRepository->getTransactionMaterialInfo($record["transaction_id"])[0];
			}
			$transaction = $tracsactionHash[$record["transaction_id"]];
			$material = $materialHash[$record["transaction_id"]];
			$dailyRecords[]=[
				"託播單識別碼"=>$record["transaction_id"],
				"託播單名稱"=>$transaction["託播單名稱"],
				"產業類型說明"=>$material["產業類型說明"],
				"播放時間"=> $records["hour"].":".gmdate('i:s', $record["start_seconds"])
			];
		}
	}
	exit(json_encode($dailyRecords,JSON_UNESCAPED_UNICODE))
	
?>