<?php
	/**
	*對barkerCue資料表操作
	**/
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	require_once dirname(__FILE__)."/module/PlayListRepository.php";
	require_once dirname(__FILE__)."/module/TransactionRepository.php";
	//$_POST[]:searchChannelPlaylistSch :設定開始/結束時間以及頻道取的所有合格的playlist id
	//$_POST[]:getPlaylistInfo :取的playlist的資料，含託播單與素材秒數等
	//$_POST[]:delete :刪除資料

	$my=new MyDB(true);
	$playListRepository = new \PlayListRepository($my);
	$transactionRepository = new TransactionRepository($my);
	if(isset($_POST["searchChannelPlaylistSch"])){
		$post = $_POST["searchChannelPlaylistSch"];
		$sql = "SELECT * FROM barker_playlist_schedule WHERE channel_id  = ? AND date BETWEEN ? AND ?";
		$types = "iss";
		$paras = array($post["channel_id"],$post["startDate"],$post["endDate"]);
		$result = $my->getResultArray($sql,$types,...$paras);
		$playlistMemo = [];
		foreach($result as $i=>$row){
			if(!isset($playlistMemo[$row["playlist_id"]])){
				$lastTemplateStartAndEndSeconds =$playListRepository->getLastTemplateStartAndEndSeconds($row["playlist_id"]);
				$playlistMemo[$row["playlist_id"]] = $playListRepository->getPlaylistDataByID($row["playlist_id"]);
				$playlistMemo[$row["playlist_id"]]["lastRecordEndSeconds"]=$playListRepository->getLastRecordEndSeconds($row["playlist_id"]);
				$playlistMemo[$row["playlist_id"]]["lastTemplateStartSeconds"]=$lastTemplateStartAndEndSeconds["start_seconds"];
				$playlistMemo[$row["playlist_id"]]["lastTemplateEndSeconds"]=$lastTemplateStartAndEndSeconds["end_seconds"];
			}
			$result[$i]["playlistInfo"]=$playlistMemo[$row["playlist_id"]];
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getPlaylistInfo"])){
		$sql = "SELECT 託播單名稱,託播單識別碼,start_seconds,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,託播單狀態識別碼
		FROM
			barker_playlist_record 
			JOIN 託播單 ON 託播單識別碼 = transaction_id  
		WHERE 	playlist_id = ? ORDER BY offset";
		$types = "i";
		$paras = array($_POST["getPlaylistInfo"]["playlist_id"]);
		$result = $my->getResultArray($sql,$types,...$paras);
		$orderDataHash = array();
		foreach($result as $id=>$data){
			if(!isset($orderDataHash[$data["託播單識別碼"]])){
				$orderDataHash[$data["託播單識別碼"]]=getOrderData($data["託播單識別碼"]);
			}
			foreach($orderDataHash[$data["託播單識別碼"]] as $key=>$value)
				$result[$id][$key]=$value;

		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getPlaylistTemplateInfo"])){
		$orderDataHash = array();
		$orderBasicInfoHash = array();
		$result = $playListRepository->getPlaylistTemplate($_POST["getPlaylistTemplateInfo"]["playlist_id"]);
		foreach($result as $id=>$record){
			if($record["transaction_id"] == "-1"){
				continue;
			}
			if(!isset($orderDataHash[$record["transaction_id"]])){
				$orderDataHash[$record["transaction_id"]] = getOrderData($record["transaction_id"]);
				$orderBasicInfoHash[$record["transaction_id"]] = $transactionRepository->getTransactionBasicInfo($record["transaction_id"]);
			}
			$result[$id]["託播單名稱"]=$orderBasicInfoHash[$record["transaction_id"]]["託播單名稱"];
			$result[$id]["託播單識別碼"]=$orderBasicInfoHash[$record["transaction_id"]]["託播單識別碼"];
			$result[$id]["廣告期間開始時間"]=$orderBasicInfoHash[$record["transaction_id"]]["廣告期間開始時間"];
			$result[$id]["廣告期間結束時間"]=$orderBasicInfoHash[$record["transaction_id"]]["廣告期間結束時間"];
			$result[$id]["廣告可被播出小時時段"]=$orderBasicInfoHash[$record["transaction_id"]]["廣告可被播出小時時段"];
			$result[$id]["託播單狀態識別碼"]=$orderBasicInfoHash[$record["transaction_id"]]["託播單狀態識別碼"];
			foreach($orderDataHash[$record["transaction_id"]] as $key=>$value)
				$result[$id][$key]=$value;
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getPlaylistScheduleInfo"])){
		$sql = "SELECT *
		FROM
			barker_playlist_schedule 
		WHERE 	playlist_id = ? ORDER BY channel_id , date ,hour ";
		$types = "i";
		$paras = array($_POST["getPlaylistScheduleInfo"]["playlist_id"]);
		$result = $my->getResultArray($sql,$types,...$paras);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getPlaylistSechdule"])){
		$result = $playListRepository->getPlaylistSechdule($_POST["getPlaylistSechdule"]);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["searchTransactionPlayRecords"])){
		$potvar = $_POST["searchTransactionPlayRecords"];
		//取的託播單播放時間
		$transactionMaterialInfo = $transactionRepository->getTransactionMaterialInfo($potvar["transaction_id"]);
		$materialSeconds=$transactionMaterialInfo[0]["影片素材秒數"];
		//依照playList_record的playlist_id查看在playlist_schedule中被那些頻道時段使用
		
		$playlistScheduleHash=[];
		$recordsWithTransation=[];
		//先取得範圍內的播表
		$schedules = $playListRepository->getPlaylistSechdule(["dateRange"=>$potvar["dateRange"]]);
		foreach($schedules as $id=>$schedule){
			if(!isset($playlistScheduleHash[$schedule["playlist_id"]])){
				$records = $playListRepository->getPlaylistRecord(["transaction_id"=>$potvar["transaction_id"],"playlist_id"=>$schedule["playlist_id"]]);
				$playlistScheduleHash[$schedule["playlist_id"]] = $records;
			}
			if(is_array($playlistScheduleHash[$schedule["playlist_id"]]))
			foreach($playlistScheduleHash[$schedule["playlist_id"]] as $j=>$recordhash)
				$recordsWithTransation[]= [
					"channel_id"=>$schedule["channel_id"],
					"date"=>$schedule["date"],
					"hour"=>$schedule["hour"],
					"start_seconds"=>$recordhash["start_seconds"],
					"end_seconds"=>$recordhash["start_seconds"]+$materialSeconds
				];
		}
		$result = ["success"=>true,"records"=>$recordsWithTransation];
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getPlaylistScheduleHistory"])){
		$_POST["getPlaylistScheduleHistory"];
		$result = getPlaylistScheduleHistory($playListRepository,$_POST["getPlaylistScheduleHistory"]);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	
	function getOrderData($transaction_id){
		global $my;
		$return = array();
		$sql= "SELECT 素材.影片素材秒數,素材.素材識別碼,CAMPS影片媒體編號,產業類型.產業類型說明 AS 產業類型說明,上層產業.產業類型說明 AS 上層產業類型說明
			FROM
				託播單素材 JOIN 素材 ON 託播單素材.素材識別碼 = 素材.素材識別碼
				JOIN 產業類型 ON 產業類型.產業類型識別碼 = 素材.產業類型識別碼 
				JOIN 產業類型 上層產業 ON 上層產業.產業類型識別碼 = 產業類型.上層產業類型識別碼 
			WHERE 
				託播單素材.託播單識別碼=? 
			ORDER BY 影片畫質識別碼 ASC
			";
		$materialDataArray = $my->getResultArray($sql,'i',$transaction_id);
		foreach($materialDataArray as $mid=>$materialData){
			$return["影片素材秒數"]=$materialData["影片素材秒數"];
			$return["素材識別碼"]=$materialData["素材識別碼"];
			$return["CAMPS影片媒體編號"]=$materialData["CAMPS影片媒體編號"];
			$return["產業類型顯示名稱"]=$materialData["上層產業類型說明"]=="內廣"?$materialData["產業類型說明"]:"外廣";
			$return["產業類型說明"]=$materialData["產業類型說明"];
			$return["上層產業類型說明"]=$materialData["上層產業類型說明"];
		}
		$sql= "SELECT 版位其他參數預設值 as channel_id
		FROM
			託播單投放版位 JOIN 版位其他參數 ON 託播單投放版位.版位識別碼 = 版位其他參數.版位識別碼 
		WHERE 
			託播單投放版位.託播單識別碼=? AND 版位其他參數順序 = 1
		";
		$positionData = $my->getResultArray($sql,'i',$transaction_id);
		$return["channel_id"]=array();
		foreach($positionData as $key=>$channelData){
			array_push($return["channel_id"],$channelData["channel_id"]);
		}
		return $return;
	}

	function getPlaylistScheduleHistory($playListRepository,$postvar){
		try{
			$searchOtp = [ "channel_id" => $postvar["channel_id"], "date" => $postvar["date"], "hour" => $postvar["hour"] ];
			return $playListRepository->getPlaylistScheduleHistory( $searchOtp );
		}
		catch(Exception $e){
			exit(json_encode([]));
		}
	}
?>