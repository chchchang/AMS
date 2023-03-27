<?php
	/**
	*對barkerCue資料表操作
	**/
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	require_once dirname(__FILE__)."/module/PlayListRepository.php";
	//$_POST[]:searchChannelPlaylistSch :設定開始/結束時間以及頻道取的所有合格的playlist id
	//$_POST[]:getPlaylistInfo :取的playlist的資料，含託播單與素材秒數等
	//$_POST[]:delete :刪除資料

	$my=new MyDB(true);
	$playListRepository = new PlayListRepository();
	if(isset($_POST["searchChannelPlaylistSch"])){
		$post = $_POST["searchChannelPlaylistSch"];
		$sql = "SELECT * FROM barker_playlist_schedule WHERE channel_id  = ? AND date BETWEEN ? AND ?";
		$types = "iss";
		$paras = array($post["channel_id"],$post["startDate"],$post["endDate"]);
		
		$result = $my->getResultArray($sql,$types,...$paras);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getPlaylistInfo"])){
		$sql = "SELECT 託播單名稱,託播單識別碼,start_seconds,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段
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
		$sql = "SELECT 託播單名稱,託播單識別碼,repeat_times,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段
		FROM
			barker_playlist_template
			JOIN 託播單 ON 託播單識別碼 = transaction_id  
		WHERE 	playlist_id = ? ORDER BY offset";
		$types = "i";
		$paras = array($_POST["getPlaylistTemplateInfo"]["playlist_id"]);
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
	else if(isset($_POST["getPlayListScheduleInRange"])){
		$result = $playListRepository->getPlayListScheduleInRange($_POST["getPlayListScheduleInRange"]);
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
			$return["產業類型說明"]=$materialData["上層產業類型說明"]=="內廣"?$materialData["產業類型說明"]:"外廣";
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
?>