<?php
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	//$_POST["託播單識別碼"] = 47345;//dev
	$my=new MyDB(true);
	$sql= "SELECT 託播單.託播單識別碼,託播單名稱,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段";
	$whereStrs = ["託播單狀態識別碼=2"];//只選擇則送出的託播單
	$typeStr = "";
	$paras = [];
	if(isset($_POST["版位識別碼"])){
		$sql.="
			FROM
			託播單 JOIN 託播單投放版位 ON 託播單.託播單識別碼 = 託播單投放版位.託播單識別碼 AND 託播單投放版位.版位識別碼 = ? AND 託播單投放版位.ENABLE = 1
		";
		$typeStr = 'i';
		array_push($paras,$_POST["版位識別碼"]);
	}
	else if(isset($_POST["channel_id"])){
		$positionSql="SELECT P.版位識別碼
			FROM 版位 P
				JOIN 版位 版位類型 ON P.上層版位識別碼 = 版位類型.版位識別碼 AND 版位類型.版位名稱 = 'barker頻道'
				JOIN 版位其他參數 ON P.版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = 'channel_id'
			WHERE 版位其他參數預設值 = ?
		";
		$positionData = $my->getResultArray($positionSql,"i",$_POST["channel_id"]);
		
		if(is_array($positionData)&&isset($positionData[0]["版位識別碼"])){
			$sql.="
				FROM
				託播單 JOIN 託播單投放版位 ON 託播單.託播單識別碼 = 託播單投放版位.託播單識別碼 AND 託播單投放版位.版位識別碼 = ? AND 託播單投放版位.ENABLE = 1
			";
			$typeStr = 'i';
			array_push($paras,$positionData[0]["版位識別碼"]);
		}
		else{
			exit(json_encode([],JSON_UNESCAPED_UNICODE));
		}
	}
	else{
		$sql.="
			FROM
			託播單 JOIN 版位 P ON 託播單.版位識別碼 = P.版位識別碼
				JOIN 版位 版位類型 ON P.上層版位識別碼 = 版位類型.版位識別碼 AND 版位類型.版位名稱 = 'barker頻道'
		";
	}

	if(isset($_POST["searchTerm"])){
		$typeStr.="si";
		array_push($whereStrs,"(託播單名稱 LIKE ? OR 託播單.託播單識別碼 = ?)");
		array_push($paras,"%".$_POST["searchTerm"]."%",$_POST["searchTerm"]);
	}
	if(isset($_POST["searchDate"])){
		$typeStr.="s";
		array_push($whereStrs,"? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間");
		array_push($paras,$_POST["searchDate"]);
	}
	if(isset($_POST["searchDateStart"]) && isset($_POST["searchDateEnd"])){
		$typeStr.="ssss";
		array_push($whereStrs,"(? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 OR ? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 OR 廣告期間開始時間 BETWEEN ? AND ?)");
		array_push($paras,$_POST["searchDateStart"],$_POST["searchDateEnd"],$_POST["searchDateStart"],$_POST["searchDateEnd"]);
	}
	if(isset($_POST["hour"])){
		$_POST["hour"] = (int)$_POST["hour"];
		$typeStr.="ssss";
		array_push($whereStrs,"(廣告可被播出小時時段 = ? OR 廣告可被播出小時時段 LIKE ? OR 廣告可被播出小時時段 LIKE ? OR 廣告可被播出小時時段 LIKE ?)");
		array_push($paras,$_POST["hour"],"%,".$_POST["hour"].",%","%,".$_POST["hour"],$_POST["hour"].",%");
	}
	if(isset($_POST["託播單識別碼"])){
		$typeStr.="i";
		array_push($whereStrs,"託播單識別碼=?");
		array_push($paras,$_POST["託播單識別碼"]);
	}

	$sql .= " WHERE ".implode(" AND ",$whereStrs)." ORDER BY 廣告期間開始時間 ASC, 託播單.託播單識別碼 ASC";
	$dbdata = $my->getResultArray($sql,$typeStr,...$paras);
	foreach($dbdata as $id=>$data){
		$sql= "SELECT 素材.影片素材秒數,素材.素材識別碼,CAMPS影片媒體編號,產業類型.產業類型說明 AS 產業類型說明,上層產業.產業類型說明 AS 上層產業類型說明
			FROM
				託播單素材 JOIN 素材 ON 託播單素材.素材識別碼 = 素材.素材識別碼
				JOIN 產業類型 ON 產業類型.產業類型識別碼 = 素材.產業類型識別碼 
				JOIN 產業類型 上層產業 ON 上層產業.產業類型識別碼 = 產業類型.上層產業類型識別碼 
			WHERE 
				託播單素材.託播單識別碼=?
				ORDER BY 影片畫質識別碼 ASC
			";
		$materialDataArray = $my->getResultArray($sql,'i',$data["託播單識別碼"]);
		foreach($materialDataArray as $mid=>$materialData){
			$dbdata[$id]["影片素材秒數"]=$materialData["影片素材秒數"];
			$dbdata[$id]["素材識別碼"]=$materialData["素材識別碼"];
			$dbdata[$id]["CAMPS影片媒體編號"]=$materialData["CAMPS影片媒體編號"];
			$dbdata[$id]["產業類型顯示名稱"]=$materialData["上層產業類型說明"]=="內廣"?$materialData["產業類型說明"]:"外廣";
			$dbdata[$id]["產業類型說明"]=$materialData["產業類型說明"];
			$dbdata[$id]["上層產業類型說明"]=$materialData["上層產業類型說明"];
		}
		
		$sql= "SELECT 版位其他參數預設值 as channel_id
			FROM
				託播單投放版位 JOIN 版位其他參數 ON 託播單投放版位.版位識別碼 = 版位其他參數.版位識別碼 
			WHERE 
				託播單投放版位.託播單識別碼=? AND 版位其他參數順序 = 1
			";
		$positionData = $my->getResultArray($sql,'i',$data["託播單識別碼"]);
		$dbdata[$id]["channel_id"]=array();
		foreach($positionData as $key=>$channelData){
			array_push($dbdata[$id]["channel_id"],$channelData["channel_id"]);
		}
		
	}
	
	echo json_encode($dbdata,JSON_UNESCAPED_UNICODE);
?>