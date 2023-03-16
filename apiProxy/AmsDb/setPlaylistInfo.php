<?php
	/**
	*對barkerCue資料表操作
	**/
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	require_once dirname(__FILE__)."/module/PlayListRepository.php";
	//$_POST[]:replace :為選定的頻道/時段單獨新增播表
	//$_POST[]:edit :更新播表資料，不更動頻道/時段與播表的關係
	//$_POST[]:delete :刪除資料
	$my=new MyDB(true);
	$PlayListRepository = new PlayListRepository($my);
	if(isset($_POST["replace"])){
		$postvar = $_POST["replace"];
		if(!validatePlaylistSchColumn($postvar["channel_id"],$postvar["date"],$postvar["hour"]))
			exit(json_encode(array("success"=>false,"message"=>"參數不正確"),JSON_UNESCAPED_UNICODE));
		$PlayListRepository->begin_transaction();
		foreach($postvar["overlapHour"] as $id => $h){
			$postvar["overlapHour"][$id]=str_pad($h, 2, '0', STR_PAD_LEFT);
		}
		//先新增palylist
		$hours = implode(",",$postvar["overlapHour"]);
		$cids = implode(",",$postvar["overlapChannelId"]);
		
		$newPlayListId = $PlayListRepository->insertPlaylist($postvar["overlapStartTime"],$postvar["overlapEndTime"],$hours,$cids);
		if(!$newPlayListId){
			setMessageAndExit(false,"insert playlist to db fail");
		}
		//新增playlistTemplate
		if(!$PlayListRepository->setPlaylistTemplate($newPlayListId,$postvar["playlistTemplate"])){
			setMessageAndExit(false,"設定播表樣板失敗");
		}
		//新增playlistRecord
		if(!$PlayListRepository->setPlaylistRecord($newPlayListId,$postvar["playlistRecord"])){
			setMessageAndExit(false,"設定實際播表失敗");
		}

		//新增playlist schedule
		$records = array(array("channel_id"=>$postvar["channel_id"],"date"=>$postvar["date"],"hour"=>$postvar["hour"],"playlist_id"=>$newPlayListId));
		if(!$PlayListRepository->setPlaylistSchedule($records)){
			setMessageAndExit(false,"單一時段播表設定成功");
		}
	
		setMessageAndExit(true,"單一時段播表設定成功");
	}
	else if(isset($_POST["edit"])){
		$postvar = $_POST["edit"];
		$PlayListRepository->begin_transaction();
		foreach($postvar["overlapHour"] as $id => $h){
			$postvar["overlapHour"][$id]=str_pad($h, 2, '0', STR_PAD_LEFT);
		}
		//檢查走期是否可蓋
		if(!$PlayListRepository->checkIfAnyPlayListNotInclude($postvar))
			exit(json_encode(array("success"=>false,"message"=>"修改後走期無法涵蓋當前播表"),JSON_UNESCAPED_UNICODE));
		//更新
		$hours = implode(",",$postvar["overlapHour"]);
		$cids = implode(",",$postvar["overlapChannelId"]);
		
		if(!$PlayListRepository->updatePlaylist($postvar["overlapStartTime"],$postvar["overlapEndTime"],$hours,$cids,$postvar["playlist_id"])){
			setMessageAndExit(false,"insert playlist to db fail");
		}
		
		//新增playlistTemplate
		if(!$PlayListRepository->setPlaylistTemplate($postvar["playlist_id"],$postvar["playlistTemplate"])){
			setMessageAndExit(false,"設定播表樣板失敗");
		}
		//新增playlistRecord
		if(!$PlayListRepository->setPlaylistRecord($postvar["playlist_id"],$postvar["playlistRecord"])){
			setMessageAndExit(false,"設定實際播表失敗");
		}
		
		setMessageAndExit(true,"播表更新成功，且使用相同播表的時段也已同步更新");
	}
	else if(isset($_POST["splitPlaylist"])){
		$postvar = $_POST["splitPlaylist"];
		$playlistId = $postvar[0]["playlist_id"];
		$PlayListRepository->begin_transaction();
		//先取的資訊
		$playlistInfo =[];
		if(!$playlistInfo =$PlayListRepository->getFullPlaylistInfo($playlistId)){
			setMessageAndExit(false,"取得播表資訊失敗");
		}
		
		
		//先新增palylist		
		$newPlayListId = $PlayListRepository->insertPlaylist(
			$playlistInfo["basic"]["overlap_start_time"],
			$playlistInfo["basic"]["overlap_end_time"],
			$playlistInfo["basic"]["overlap_hours"],
			$playlistInfo["basic"]["overlap_channel_id"]
		);
		if(!$newPlayListId){
			setMessageAndExit(false,"設定播表基本資料失敗");
		}
		//新增playlistTemplate
		if(!$PlayListRepository->setPlaylistTemplate($newPlayListId,$playlistInfo["template"])){
			setMessageAndExit(false,"設定播表樣板失敗");
		}
		//新增playlistRecord
		if(!$PlayListRepository->setPlaylistRecord($newPlayListId,$playlistInfo["record"])){
			setMessageAndExit(false,"設定實際播表失敗");
		}

		//新增playlist schedule
		foreach($postvar as $i=>$record){
			$postvar[$i]["playlist_id"] = $newPlayListId;
		}
		if(!$PlayListRepository->setPlaylistSchedule($postvar)){
			setMessageAndExit(false,"播表播出時間設定失敗");
		}
		setMessageAndExit(true,"播表分離成功");
		
	}
	else if(isset($_POST["setSchedule"])){
		$records = $_POST["setSchedule"];
		$playlistHash=[];

		foreach($records as $id=>$record){
			if(!isset($playlistHash[$record["playlist_id"]])){
				$result = $PlayListRepository->getPlaylistDataByID($record["playlist_id"]);
				print_r($record);
				$playlistHash[$record["playlist_id"]] = $result;
				$playlistHash[$record["playlist_id"]]["overlap_hours"] = explode(",",$playlistHash[$record["playlist_id"]]["overlap_hours"]);
				$playlistHash[$record["playlist_id"]]["overlap_channel_id"] = explode(",",$playlistHash[$record["playlist_id"]]["overlap_channel_id"]);
				$playlistHash[$record["playlist_id"]]["overlap_start_time"] = explode(" ",$playlistHash[$record["playlist_id"]]["overlap_start_time"])[0];
				$playlistHash[$record["playlist_id"]]["overlap_end_time"] = explode(" ",$playlistHash[$record["playlist_id"]]["overlap_end_time"])[0];
			}
			$playlistProp=$playlistHash[$record["playlist_id"]];
			if($record["date"]<$playlistProp["overlap_start_time"] || $record["date"]>$playlistProp["overlap_end_time"] ){
				exit(json_encode(array("false"=>false,"message"=>"複製失敗，請確認播表託播單的走期"),JSON_UNESCAPED_UNICODE));
			}
			if(!in_array($record["channel_id"],$playlistProp["overlap_channel_id"])){
				exit(json_encode(array("false"=>false,"message"=>"複製失敗，請確認播表託播單的投放頻道"),JSON_UNESCAPED_UNICODE));
			}
			
		}

		$PlayListRepository->begin_transaction();
		if(!$PlayListRepository->setPlaylistSchedule($records)){
			setMessageAndExit(false,"insert playlist schedule to db fail");
		}
		setMessageAndExit(true,"播表複製成功");
	}
	else if(isset($_POST["cloneWholeDaySchedule"])){
		$postvar = $_POST["cloneWholeDaySchedule"];
		$playlistHash=[];
		$PlayListRepository->begin_transaction();
		//選出出當日的播表
		$parameter = array();
		$playlistSchedule = $PlayListRepository->getPlaylistSechdule(["channel_id"=>$postvar["source"]["channelId"],"date"=>$postvar["source"]["date"]]);
		if(!$playlistSchedule)
			exit(json_encode(array("false"=>false,"message"=>"要複製時取的播表資訊失敗"),JSON_UNESCAPED_UNICODE));
		$playlistHash=[];
		$cloneSchedule=[];
		foreach($playlistSchedule as $id=>$schRecord){
			if(!isset($playlistHash[$schRecord["playlist_id"]])){
				//取得playlist資料並記錄到hash
				$result = $PlayListRepository->getPlaylistDataByID($schRecord["playlist_id"]);
				$playlistHash[$schRecord["playlist_id"]] = $result;
				$playlistHash[$schRecord["playlist_id"]]["overlap_start_time"] = explode(" ",$playlistHash[$schRecord["playlist_id"]]["overlap_start_time"])[0];
				$playlistHash[$schRecord["playlist_id"]]["overlap_end_time"] = explode(" ",$playlistHash[$schRecord["playlist_id"]]["overlap_end_time"])[0];
				$playlistHash[$schRecord["playlist_id"]]["overlap_hours"] = explode(",",$playlistHash[$schRecord["playlist_id"]]["overlap_hours"]);
				$playlistHash[$schRecord["playlist_id"]]["overlap_channel_id"] = explode(",",$playlistHash[$schRecord["playlist_id"]]["overlap_channel_id"]);
			}
			$playlistProp=$playlistHash[$schRecord["playlist_id"]];
			foreach($postvar["target"] as $targetId=>$target){
				if(!validatePlaylistSchColumn($target["channelId"],$target["date"],$schRecord["hour"]))
					continue;
				if($target["date"]<$playlistProp["overlap_start_time"] || $target["date"]>$playlistProp["overlap_end_time"]){
					setMessageAndExit(false,"複製失敗，請確認播表託播單的走期");
				}
				if(!in_array($target["channelId"],$playlistProp["overlap_channel_id"])){
					setMessageAndExit(false,"複製失敗，請確認播表託播單的投放頻道");
				}
				$cloneSchedule[]=array(
					"channel_id" =>$target["channelId"],
					"date" =>$target["date"],
					"hour" =>$schRecord["hour"],
					"playlist_id" =>$schRecord["playlist_id"],
				);
			}
		}
		if(!$PlayListRepository->setPlaylistSchedule($cloneSchedule)){
			setMessageAndExit(false,"整日播表複製失敗");
		};
		setMessageAndExit(true,"整日播表複製成功");
	}
	else if(isset($_POST["replaceTransaction"])){
		require_once("module/ReplaceOrderInPlaylist.php");
		$postvar = $_POST["replaceTransaction"];
		$replacer = new ReplaceOrderInPlaylist();
    	if(!$replacer->replaceOrderInPlaylist(
				$postvar["dateRange"],
				isset($postvar["hour"])?$postvar["hour"]:[],
				isset($postvar["channel"])?$postvar["channel"]:[],
				$postvar["originalTransactionId"],
				$postvar["newTransactionId"],
				$postvar["replaceOffset"],
				$postvar["replaceInterval"])
		){
			
			setMessageAndExit(false,$replacer->getExecuteMessage());
		}
		setMessageAndExit(true,$replacer->getExecuteMessage());
	}
	
	function setMessageAndExit($success,$message){
		global $PlayListRepository;
		if(!$success)
			$PlayListRepository->rollback();
		else
		$PlayListRepository->commit();
		exit(json_encode(array("success"=>$success,"message"=>$message),JSON_UNESCAPED_UNICODE));
	}
	/**
	*比較playtemplate和一小時播表是否相同
	*如果一小時播表只是不斷重複template的順序則視為相同
	**/
	function compareTemplateWithPalylist($template,$playlist){
		$n = count($template);
		foreach($playlist as $id=>$record){
			if($record["transaction_id"]!=$template[$id%$n]["transaction_id"])
				return false;
		}
		return true;
	}
	

	function validatePlaylistSchColumn($ch,$date,$hour){
		return is_numeric($ch) && validateDate($date) && validateHour($hour);
	}

	function validateDate($date, $format = 'Y-m-d') {
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}

	function validateHour($hour) {
		return preg_match('/^(0[0-9]|1[0-9]|2[0-3])$/', $hour);
	}
?>