<?php
	/**
	*對barkerCue資料表操作
	**/
	require_once dirname(__FILE__)."/../../../tool/MyDB.php";
	require_once dirname(__FILE__)."/../module/PlayListRepository.php";
	require_once dirname(__FILE__)."/../module/TransactionRepository.php";
	$dateRange = $_POST["dateRange"];
	$channel = $_POST["channel"];

	$my=new MyDB(true);
	$playListRepository = new PlayListRepository($my);
	$transactionRepository = new TransactionRepository($my);
	$searchTerm = ["dateRange"=>$dateRange,"channel"=>$channel];
	$palylistIds = $playListRepository->getDistinctPlayListIDInRange($searchTerm);
	
	$return = [];
	$playListMemo = [];
	$transactionMemo = [];
	foreach($palylistIds as $pid){
		if(isset($playListMemo[$pid]))
			continue;
		$transactions = $playListRepository->getPlaylistRecord(["playlist_id"=>$pid]);
		$playListMemo[$pid] = true;
		foreach($transactions as $t){
			$tid = $t["transaction_id"];
			if(isset($transactionMemo[$tid])){
				continue;
			}
			$transactionInfo  = $transactionRepository->getTransactionBasicInfo($tid);
			$materialData  = $transactionRepository->getTransactionMaterialInfo($tid)[0];
			$transactionInfo["影片素材秒數"]=$materialData["影片素材秒數"];
			$transactionInfo["素材識別碼"]=$materialData["素材識別碼"];
			$transactionInfo["CAMPS影片媒體編號"]=$materialData["CAMPS影片媒體編號"];
			$transactionInfo["產業類型顯示名稱"]=$materialData["上層產業類型說明"]=="內廣"?$materialData["產業類型說明"]:"外廣";
			$transactionInfo["產業類型說明"]=$materialData["產業類型說明"];
			$transactionInfo["上層產業類型說明"]=$materialData["上層產業類型說明"];
			array_push($return,$transactionInfo);
			$transactionMemo[$tid] = true;
		}
	}
	
	echo json_encode($return,JSON_UNESCAPED_UNICODE);
?>