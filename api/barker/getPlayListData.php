<?php 
/**2022-09-05 取得頻導播表資料，不寫檔，回傳json資訊
*/

//require_once '/var/www/html/AMS/Config.php';
require_once dirname(__FILE__).'/../../Config.php';
require_once dirname(__FILE__).'/module/ConvertCampsPlayList.php';


//exit(json_encode(["seccess"=>true,"message"=>"111\n222111\nsad111\nsae2q111\nadfw46111\n111\n111\n"],JSON_UNESCAPED_UNICODE));//dev

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

$logFilePath = dirname(__FILE__).'/../../'.Config::SECRET_FOLDER."/apiLog/sendPlayListToPumping";
if(!is_dir($logFilePath)){
	if (!mkdir($logFilePath, 0777, true)) {
		die('Failed to create log directories...');
	}
}
$hadler = new ConverCampsPlaylist();
$result = $hadler->getData($date,$channel_id,$hours);
exit(json_encode($result,JSON_UNESCAPED_UNICODE));
?>
 