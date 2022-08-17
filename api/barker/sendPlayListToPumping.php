<?php 
/**2022-07-29 上傳播表到端點pumping server API
 * 內容與cronjob的converCampsPlaylist基本相同，
 * 不過取的執行參數的方式改用POST
 * 因資安因素考量，才將排程conjob與API分開為兩個檔案，放置於不同環境。
 * 2022-08-16
 * 改呼叫專用的object SendPlayListToPumping.php
*/

//require_once '/var/www/html/AMS/Config.php';
require_once dirname(__FILE__).'/../../Config.php';
require_once dirname(__FILE__).'/module/SendPlayListToPumping.php';


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
$logFilePath .="/".date("Y-m-d").".log";
$logWriter = fopen($logFilePath,"a");
$hadler = new SendPlayListToPumping($logWriter);
$result = $hadler->handle($date,$channel_id,$hours);
exit(json_encode(["success"=>$result,"message"=>$hadler->message],JSON_UNESCAPED_UNICODE));
?>
 