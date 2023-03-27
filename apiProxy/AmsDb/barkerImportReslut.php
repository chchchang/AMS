<?php
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	require_once dirname(__FILE__).'/../../Config.php';
	require_once dirname(__FILE__).'/../../api/barker/module/ConvertCampsPlayList.php';
	$my=new MyDB(true);
	if(isset($_POST['ajaxAction'])){
		if($_POST['ajaxAction'] == "getPlayListRecord"){
			$sql = "SELECT * FROM `barker_playlist_import_result` WHERE file_name Like ?";
						
			$dbdata = $my->getResultArray($sql,'s',$_POST["searchDate"]."%");
			$return = array();
			$chNameMap = getBarkerNameMap();
			foreach($dbdata as $row){
				//channel date hours result message import_time
				preg_match("/(.+)_(.+)\.json/",$row["file_name"],$fileNameMatches);
				//$channel = $row["channel_id"].(isset($chNameMap[$row["channel_id"]])?$chNameMap[$row["channel_id"]]["版位名稱"]:"");
				$date = $fileNameMatches[1];
				$hours = $fileNameMatches[2];
				$result = $row["import_result"];
				$message = $row["message"]==null?"":$row["message"];
				$import_time = $row["import_time"]==null?"":$row["import_time"];
				
				if(!isset($return[$row["channel_id"]])){
					$return[$row["channel_id"]] = array();
				}
				$return[$row["channel_id"]][$hours] = array(
					"result"=>$result,
					"message"=>$message,
					"import_time"=>$import_time,
				);
				/*$return[$row["channel_id"]]=array(
					"channel"=>$channel,
					"hours"=>$hours,
					"result"=>$result,
					"message"=>$message,
					"import_time"=>$import_time,
				);*/
			}
			exit(json_encode($return,JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['ajaxAction'] == "getMaterialRecord"){
			$sql = "SELECT * FROM `barker_material_import_result` WHERE (created_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH) || import_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH))";
			if(isset($_POST["failOnly"])&& $_POST["failOnly"]=="true"){
				$sql .= " AND (import_result = 0 AND import_time IS NOT NULL)";
			}
			$sql .=" order by import_time desc";
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$data = array();
			while($row = $res->fetch_assoc()){
				$result = $row["import_result"]==1?"成功":"失敗";
				if($row["import_time"] == null)
					$result = "處理中";
				$message = $row["message"]==null?"":$row["message"];
				$import_time = $row["import_time"]==null?"":$row["import_time"];
				
				$data[]=array(
					"material_id"=>$row["material_id"],
					"file_name"=>$row["file_name"],
					"result"=>$result,
					"message"=>$message,
					"import_time"=>$import_time,
				);
			}
			exit(json_encode(array('success'=> true,"data"=>$data),JSON_UNESCAPED_UNICODE));
		}

		else if($_POST['ajaxAction'] == "getPlayFailRecord"){
			$sql = "SELECT * FROM `barker_play_fail_log` WHERE (created_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH) )";
			$sql .=" order by created_time desc";
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$data = array();
			$chNameMap = getBarkerNameMap();
			while($row = $res->fetch_assoc()){
				
				$channel = $row["channel_id"].(isset($chNameMap[$row["channel_id"]])?$chNameMap[$row["channel_id"]]["版位名稱"]:"");
				$message = $row["message"]==null?"":$row["message"];
				
				$data[]=array(
					"channel"=>$channel,
					"file_name"=>$row["file_name"],
					"play_time"=>$row["play_time"],
					"transaction_id"=>$row["transaction_id"],
					"message"=>$message,
					
				);
			}
			exit(json_encode(array('success'=> true,"data"=>$data),JSON_UNESCAPED_UNICODE));
		}
		
		else if($_POST['ajaxAction'] == "getPlayList"){
			if(isset($_POST["date"])){
				$date = $_POST["date"];
			}
			if(isset($_POST["channel_id"])){
				$channel_id = $_POST["channel_id"];
			}
			else{
				exit(json_encode(["seccess"=>false,"message"=>"請指定頻道"],JSON_UNESCAPED_UNICODE));
			}
			if(isset($_POST["hours"])){
				$hours = $_POST["hours"];
			}
			else{
				$hours = "all";
			}

			$hadler = new ConverCampsPlaylist();
			/*$result = $hadler->getData($date,$channel_id,$hours);
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));*/
			
			$testfile = file_get_contents('testPlayList.txt');
			exit($testfile);//dev

		}
		else if($_POST['ajaxAction'] == "getMaterialImportStatus"){
			$length = count($_POST["mids"]);
			$my=new MyDB(true);
			
			$markString = array_fill(0,$length,"?");
			$markString = implode(",",$markString);
			$typeString = str_repeat("i",$length);
			$sql= "SELECT 素材識別碼,素材名稱,import_result,barker_material_import_result.created_time,barker_material_import_result.last_updated_time FROM 素材 left JOIN barker_material_import_result on 素材識別碼 = material_id WHERE 素材類型識別碼=3 and 素材識別碼 in ($markString)";
			$result = $my->getResultArray($sql,$typeString,...$_POST["mids"]);
			$feedback = [];
			$lastTouchTimeHash = [];
			foreach($result as $id=>$data){
				$lastTouchTime = ($data["last_updated_time"] == null)?$data["created_time"]:$data["last_updated_time"];
				$data["lastTouchTime"] = $lastTouchTime;
				if(isset($lastTouchTimeHash[$data["素材識別碼"]])){
					if($lastTouchTimeHash[$data["素材識別碼"]]["lastTouchTime"] > $lastTouchTime){
						continue;
					}
				}
				$lastTouchTimeHash[$data["素材識別碼"]] = $data;				
			}
			foreach($lastTouchTimeHash as $id=>$data){
				array_push($feedback,$data);
			}
			echo json_encode($feedback,JSON_UNESCAPED_UNICODE);
		}
	}
	function getBarkerNameMap(){
		global $my;
		$sql = "
		SELECT 版位.版位識別碼,版位名稱,版位其他參數預設值 as channel_id
			FROM 版位 JOIN 版位其他參數 on 版位.版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = 'channel_id'
			WHERE 上層版位識別碼 in
			(SELECT 版位識別碼 AS 上層板位識別碼
			FROM 版位
			WHERE 版位名稱='barker頻道')
		"
		;

		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，請聯絡系統管理員！');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，請聯絡系統管理員！');
		}
		if(!$res=$stmt->get_result()) {
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		$data = array();
		while($row = $res->fetch_assoc()){
			$data[$row["channel_id"]] = $row;
		}
		return $data;
	}
?>