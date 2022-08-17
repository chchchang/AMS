<?php 
/**
 * 
*/
//require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once dirname(__FILE__).'/sqlite/PlaylistImportSchSqlite.php';
require_once dirname(__FILE__).'/module/PlaylistImportSchDb.php';
//require_once '/var/www/html/AMS/Config.php';
$mydb = new PointBarkerDB();
switch($_POST["action"]){
	case "insertSch":
		$para = getPara();
		if($mydb->insertSch($para["channel_id"],$para["date"],$para["hour"]))
			exitWithMessage(true,"新增預約成功");
		else 
			exitWithMessage(false,"新增資料失敗");
		break;
	case "batchInsertSch":
		$para = getPara();
		if($mydb->batchInsertSch($para["channel_id"],$para["date"],$para["hour"]))
			exitWithMessage(true,"新增預約成功");
		else 
			exitWithMessage(false,"批次新增資料失敗");
		break;
	case "getAllSch":
			$data = $mydb->getAllSch();
			exitWithMessage(true,"getAllSch seccess",$data);
		break;
	case "getFirstSch":
			$data = $mydb->getFirstSch();
			exitWithMessage(true,"getFirstSch success",$data);
		break;
	case "deleteSch":
		$para = getPara();
		if($mydb->deleteSch($para["channel_id"],$para["date"],$para["hour"]))
			exitWithMessage(true,"刪除成功");
		else
			exitWithMessage(false,"刪除資料失敗");
		break;
	default:
			exitWithMessage(false,"No action");
		break;
}

function getPara(){
	if(!isset($_POST["channel_id"])||!isset($_POST["date"])||!isset($_POST["hour"])){
		exitWithMessage(false,"缺少必要參數");
	}

	return(array("channel_id"=>$_POST["channel_id"],"date"=>$_POST["date"],"hour"=>$_POST["hour"]));
}

function exitWithMessage($success,$message="",$data=null){
	exit(json_encode(array("success"=>$success,"message"=>$message,"data" => $data),JSON_UNESCAPED_UNICODE));
}


?>
 