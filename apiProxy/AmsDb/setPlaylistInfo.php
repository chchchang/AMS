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
			exit(json_encode(array("success"=>false,"Error"=>"參數不正確"),JSON_UNESCAPED_UNICODE));
		$PlayListRepository->begin_transaction();
		foreach($postvar["overlapHour"] as $id => $h){
			$postvar["overlapHour"][$id]=str_pad($h, 2, '0', STR_PAD_LEFT);
		}
		//先新增palylist
		$hours = implode(",",$postvar["overlapHour"]);
		$cids = implode(",",$postvar["overlapChannelId"]);
		
		$newPlayListId = $PlayListRepository->insertPlaylist($postvar["overlapStartTime"],$postvar["overlapEndTime"],$hours,$cids);
		if(!$newPlayListId){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Error"=>"insert playlist to db fail"),JSON_UNESCAPED_UNICODE));
		}
		//新增playlistTemplate
		if(!$PlayListRepository->setPlaylistTemplate($newPlayListId,$postvar["playlistTemplate"])){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"設定播表樣板失敗"),JSON_UNESCAPED_UNICODE));
		}
		//新增playlistRecord
		if(!$PlayListRepository->setPlaylistRecord($newPlayListId,$postvar["playlistRecord"])){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"設定實際播表失敗"),JSON_UNESCAPED_UNICODE));
		}

		//新增playlist schedule
		$records = array(array("channel_id"=>$postvar["channel_id"],"date"=>$postvar["date"],"hour"=>$postvar["hour"],"playlist_id"=>$newPlayListId));
		if(!$PlayListRepository->setPlaylistSchedule($records)){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"insert playlist schedule to db fail"),JSON_UNESCAPED_UNICODE));
		}
	
		$PlayListRepository->commit();
		exit(json_encode(array("success"=>true,"Message"=>"單一時段播表設定成功"),JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["edit"])){
		$postvar = $_POST["edit"];
		$PlayListRepository->begin_transaction();
		foreach($postvar["overlapHour"] as $id => $h){
			$postvar["overlapHour"][$id]=str_pad($h, 2, '0', STR_PAD_LEFT);
		}
		//檢查走期是否可蓋
		if(!$PlayListRepository->checkIfAnyPlayListNotInclude($postvar))
			exit(json_encode(array("success"=>false,"Message"=>"修改後走期無法涵蓋當前播表"),JSON_UNESCAPED_UNICODE));
		//更新
		$hours = implode(",",$postvar["overlapHour"]);
		$cids = implode(",",$postvar["overlapChannelId"]);
		
		if(!$PlayListRepository->updatePlaylist($postvar["overlapStartTime"],$postvar["overlapEndTime"],$hours,$cids,$postvar["playlist_id"])){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"insert playlist to db fail"),JSON_UNESCAPED_UNICODE));
		}
		
		//新增playlistTemplate
		if(!$PlayListRepository->setPlaylistTemplate($postvar["playlist_id"],$postvar["playlistTemplate"])){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"設定播表樣板失敗"),JSON_UNESCAPED_UNICODE));
		}
		//新增playlistRecord
		if(!$PlayListRepository->setPlaylistRecord($postvar["playlist_id"],$postvar["playlistRecord"])){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"設定實際播表失敗"),JSON_UNESCAPED_UNICODE));
		}

		$PlayListRepository->commit();
		exit(json_encode(array("success"=>true,"Message"=>"播表更新成功，且使用相同播表的時段也已同步更新"),JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["setSchedule"])){
		$records = $_POST["setSchedule"];
		$playlistHash=[];

		foreach($records as $id=>$record){
			if(!isset($playlistHash[$record["playlist_id"]])){
				$result = $PlayListRepository->getPlaylistDataByID($record["playlist_id"]);
				$playlistHash[$record["playlist_id"]] = $result;
				$playlistHash[$record["playlist_id"]]["overlap_hours"] = explode(",",$playlistHash[$record["playlist_id"]]["overlap_hours"]);
				$playlistHash[$record["playlist_id"]]["overlap_channel_id"] = explode(",",$playlistHash[$record["playlist_id"]]["overlap_channel_id"]);
				$playlistHash[$record["playlist_id"]]["overlap_start_time"] = explode(" ",$playlistHash[$record["playlist_id"]]["overlap_start_time"])[0];
				$playlistHash[$record["playlist_id"]]["overlap_end_time"] = explode(" ",$playlistHash[$record["playlist_id"]]["overlap_end_time"])[0];
			}
			$playlistProp=$playlistHash[$record["playlist_id"]];
			if($record["date"]<$playlistProp["overlap_start_time"] || $record["date"]>$playlistProp["overlap_end_time"] ){
				exit(json_encode(array("false"=>false,"Message"=>"複製失敗，請確認播表託播單的走期"),JSON_UNESCAPED_UNICODE));
			}
			if(!in_array($record["channel_id"],$playlistProp["overlap_channel_id"])){
				exit(json_encode(array("false"=>false,"Message"=>"複製失敗，請確認播表託播單的投放頻道"),JSON_UNESCAPED_UNICODE));
			}
			
		}

		$PlayListRepository->begin_transaction();
		if(!$PlayListRepository->setPlaylistSchedule($records)){
			$PlayListRepository->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"insert playlist schedule to db fail"),JSON_UNESCAPED_UNICODE));
		}
	
		$PlayListRepository->commit();
		exit(json_encode(array("success"=>true,"Message"=>"播表複製成功"),JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["cloneWholeDaySchedule"])){
		$postvar = $_POST["cloneWholeDaySchedule"];
		$playlistHash=[];
		$my->begin_transaction();
		//選出出當日的播表
		$parameter = array();
		$sql = "select * from barker_playlist_schedule where channel_id =? AND date = ? ";
		$playlistSchedule = $my->getResultArray($sql,"is",$postvar["source"]["channelId"],$postvar["source"]["date"]);
		$playlistHash=[];
		$sql = "insert into barker_playlist_schedule (channel_id,date,hour,playlist_id) VALUES ";
		$parameter = [];
		$valuesTemplate = "(?,?,?,?)";
		$valuesStringArray=[];
		$typeStirngTemplate = "issi";
		$typeStirng = "";
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
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
					$my->rollback();
					exit(json_encode(array("false"=>false,"Message"=>"複製失敗，請確認播表託播單的走期"),JSON_UNESCAPED_UNICODE));
				}
				if(!in_array($target["channelId"],$playlistProp["overlap_channel_id"])){
					$my->rollback();
					exit(json_encode(array("false"=>false,"Message"=>"複製失敗，請確認播表託播單的投放頻道"),JSON_UNESCAPED_UNICODE));
				}
				array_push($valuesStringArray,$valuesTemplate);
				$typeStirng .= $typeStirngTemplate;
				$parameter[]=$target["channelId"];
				$parameter[]=$target["date"];
				$parameter[]=$schRecord["hour"];
				$parameter[]=$schRecord["playlist_id"];
			}
		}
		$sql .=implode(",",$valuesStringArray)." ON DUPLICATE KEY UPDATE playlist_id=values(playlist_id),last_update_time=NOW()";
		
		$result = $my->execute(...$parameter);
		if(!$result){
			$my->rollback();
			exit(json_encode(array("success"=>false,"Message"=>"insert whole day playlist schedule to db fail"),JSON_UNESCAPED_UNICODE));
		}
		
		$my->commit();
		exit(json_encode(array("success"=>true,"Message"=>"整日播表複製成功"),JSON_UNESCAPED_UNICODE));
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