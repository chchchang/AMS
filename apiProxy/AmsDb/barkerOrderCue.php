<?php
	/**
	*對barkerCue資料表操作
	**/
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	//$_POST[]:insert :新增資料，若有相同資料則更新
	//$_POST[]:delete :刪除資料
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$my=new MyDB(true);
	if(isset($_POST["insert"])){
		$valuesTemplate = "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$valuesStringArray = array();
		$typeStirngTemplate="iisiiiiiiiiiiiiiiiiiiiiiiiii";
		$sql = "insert into barker_order_cue (transaction_id,channel_id,date,hour0,hour1,hour2,hour3,hour4,hour5,hour6,hour7,hour8,hour9,hour10,hour11,hour12,hour13,hour14,hour15,hour16,hour17,hour18,hour19,hour20,hour21,hour22,hour23,enable) VALUES ";
		$typeStirng="";
		$parameter=array();
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
		foreach($_POST["insert"] as $i=>$data){
			$valuesStringArray[]=$valuesTemplate;
			$typeStirng.=$typeStirngTemplate;
			$parameter[]=&$_POST["insert"][$i]["transaction_id"];
			$parameter[]=&$_POST["insert"][$i]["channel_id"];
			$parameter[]=&$_POST["insert"][$i]["date"];
			$parameter[]=&$_POST["insert"][$i]["hour0"];
			$parameter[]=&$_POST["insert"][$i]["hour1"];
			$parameter[]=&$_POST["insert"][$i]["hour2"];
			$parameter[]=&$_POST["insert"][$i]["hour3"];
			$parameter[]=&$_POST["insert"][$i]["hour4"];
			$parameter[]=&$_POST["insert"][$i]["hour5"];
			$parameter[]=&$_POST["insert"][$i]["hour6"];
			$parameter[]=&$_POST["insert"][$i]["hour7"];
			$parameter[]=&$_POST["insert"][$i]["hour8"];
			$parameter[]=&$_POST["insert"][$i]["hour9"];
			$parameter[]=&$_POST["insert"][$i]["hour10"];
			$parameter[]=&$_POST["insert"][$i]["hour11"];
			$parameter[]=&$_POST["insert"][$i]["hour12"];
			$parameter[]=&$_POST["insert"][$i]["hour13"];
			$parameter[]=&$_POST["insert"][$i]["hour14"];
			$parameter[]=&$_POST["insert"][$i]["hour15"];
			$parameter[]=&$_POST["insert"][$i]["hour16"];
			$parameter[]=&$_POST["insert"][$i]["hour17"];
			$parameter[]=&$_POST["insert"][$i]["hour18"];
			$parameter[]=&$_POST["insert"][$i]["hour19"];
			$parameter[]=&$_POST["insert"][$i]["hour20"];
			$parameter[]=&$_POST["insert"][$i]["hour21"];
			$parameter[]=&$_POST["insert"][$i]["hour22"];
			$parameter[]=&$_POST["insert"][$i]["hour23"];
			$parameter[]=&$_POST["insert"][$i]["enable"];
			
		}
		$sql .= implode(",",$valuesStringArray);
		$sql.="ON DUPLICATE KEY UPDATE hour0=values(hour0),hour1=values(hour1),hour2=values(hour2),hour3=values(hour3),hour4=values(hour4),hour5=values(hour5),hour6=values(hour6),hour7=values(hour7),hour8=values(hour8),hour9=values(hour9),hour10=values(hour10)"
		.",hour11=values(hour11),hour12=values(hour12),hour13=values(hour13),hour14=values(hour14),hour15=values(hour15),hour16=values(hour16),hour17=values(hour17),hour18=values(hour18),hour19=values(hour19),hour20=values(hour20),hour21=values(hour21),hour22=values(hour22),hour23=values(hour23),enable=values(enable),last_update_time=NOW()";
		$result = call_user_func_array(array($my,"execute"),$parameter);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["getCueInfo"])){
		$sql = "SELECT * FROM barker_order_cue WHERE enable = 1 AND date BETWEEN ? AND ?";// dev 等待加入搜尋條件
		$types = "ss";
		$paras = array($_POST["getCueInfo"]["startDate"],$_POST["getCueInfo"]["endDate"]);
		
		if(isset($_POST["getCueInfo"]["channel_id"])){
			$sql .=" AND channel_id = ?";
			$types .="i";
			array_push($paras,$_POST["getCueInfo"]["channel_id"]);
		}
		
		if(isset($_POST["getCueInfo"]["transaction_id"])){
			$sql .=" AND transaction_id = ?";
			$types .="i";
			array_push($paras,$_POST["getCueInfo"]["transaction_id"]);
		}
		$sql .= " ORDER BY date,transaction_id";
		$result = $my->getResultArray($sql,$types,...$paras);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["disable"])){
		$sql = "UPDATE barker_order_cue set enable = 0 ,last_update_time=NOW() WHERE transaction_id = ?";// dev 等待加入搜尋條件
		$types = "i";
		$paras = array($_POST["disable"]);
		$result = $my->execute($sql,$types,...$paras);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if (isset($_POST["getCueFromPlaylist"])){
		require_once("./module/PlaylistCueConverter.php");
		$convertor = new PlaylistCueConverter($my);
		$cue = $convertor->playlistToCue(
			["transaction_id"=>$_POST["getCueFromPlaylist"]["transactionId"],
			"startDate"=>$_POST["getCueFromPlaylist"]["startDate"],
			"endDate"=>$_POST["getCueFromPlaylist"]["endDate"]
			]
			,$my);
		exit(json_encode($cue,JSON_UNESCAPED_UNICODE));
	}
?>