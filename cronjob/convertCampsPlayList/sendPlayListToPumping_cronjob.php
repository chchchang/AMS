<?php 
/**2022-07-29 上傳播表到端點pumping server API
 * 內容與cronjob的converCampsPlaylist基本相同，
 * 不過取的執行參數的方式改用POST
 * 因資安因素考量，才將排程conjob與API分開為兩個檔案，放置於不同環境。
 * 
*/
$htmlpath ="/var/www/html/AMS/";//pro
//require_once $htmlpath.'Config.php';
require_once $htmlpath.'api/barker/module/SendPlayListToPumping.php';
require_once($htmlpath."apiProxy/pointBarker/Utils/ApiConnector.php");

use AMS\apiProxy\pointBarker\Utilis\ApiConnector;

$logWriter = getLogger();
$hadler = new SendPlayListToPumping($logWriter);

$date = isset($argv[1])?$argv[1]:date("Y-m-d");
$hours = isset($argv[2])?$argv[2]:"all";
$channelData = [];

if(isset($argv[3])){
	$channel_id = $argv[3];
	array_push($channelData,array("channel_id"=>$channel_id));
}
else{
	$channelData = getChannelData();	
}

foreach($channelData as $i=>$chdata){
	$result = $hadler->handle($date,$chdata["channel_id"],$hours);
	echo $hadler->message."\n";
	sleep(3);
	//....todo 此處sleep避免因為過平凡連練導致尚傳失敗 修改sftp tool後拔除sleep
	//echo $date." ".$chdata["channel_id"]." ".$hours."\n";
}




function getLogger(){
	$logFilePath = dirname(__FILE__)."/log/apiLog/sendPlayListToPumping";
	if(!is_dir($logFilePath)){
		if (!mkdir($logFilePath, 0777, true)) {
			die('Failed to create log directories...');
		}
	}
	$logFilePath .="/".date("Y-m-d").".log";
	$logWriter = fopen($logFilePath,"a");
	return $logWriter;
}

function getChannelData(){
	$ApiConnector = new ApiConnector();
	$url = $ApiConnector->getApiUrlByApiName("getChannelList");
	$output = $ApiConnector->getDataFromApi($url);
	$data = json_decode($output,true);
	$channels = $ApiConnector->filterOfflineChannel($data);
	return $channels["channels"];
}



?>
 