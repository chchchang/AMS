<?php 
/**2022-07-29 上傳播表到端點pumping server API
 * 內容與cronjob的converCampsPlaylist基本相同，
 * 不過取的執行參數的方式改用POST
 * 因資安因素考量，才將排程conjob與API分開為兩個檔案，放置於不同環境。
 * 
*/
$htmlpath ="/var/www/html/AMS/";//pro
require_once $htmlpath.'Config.php';
require_once $htmlpath.'api/barker/module/SendPlayListToPumping.php';




if(isset($argv[1])){
	$date = $argv[1];
}
if(isset($argv[2])){
	$channel_id = $argv[2];
}
else{
	exit("請指定頻道");
}
if(isset($argv[3])){
	$hours = $argv[3];
}

$logFilePath = dirname(__FILE__)."/log/apiLog/sendPlayListToPumping";
if(!is_dir($logFilePath)){
	if (!mkdir($logFilePath, 0777, true)) {
		die('Failed to create log directories...');
	}
}
$logFilePath .="/".date("Y-m-d").".log";
$logWriter = fopen($logFilePath,"a");
$hadler = new SendPlayListToPumping($logWriter);
$result = $hadler->handle($date,$channel_id,$hours);
exit($hadler->message);
/*$converCampsPlaylist = new ConverCampsPlaylist($logWriter);
if(!$converCampsPlaylist->hadle($date,$channel_id,$hours))
	exit("排播表產生失敗");

echo $converCampsPlaylist->message;

$UploadMaterialByPlayList = new UploadMaterialByPlayList($logWriter);
if(!$UploadMaterialByPlayList->hadle($date,$channel_id,$hours))
	exit("素材上傳失敗");

echo $UploadMaterialByPlayList->message;

if(!$converCampsPlaylist->uploadToPumping())
	exit("排播表上傳失敗");*/

?>
 