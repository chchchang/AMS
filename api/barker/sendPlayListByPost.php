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
require_once dirname(__FILE__).'/module/BarkerConfig.php';
require_once dirname(__FILE__).'/module/PutToWatchFolder.php';



$date=[];
$channel_id=[];
$hours=[];
$playlist=[];

if(isset($_POST["channel_id"])){
	$channel_id = $_POST["channel_id"];
}
else{
	exit(json_encode(["seccess"=>false,"message"=>"請指定頻道"],JSON_UNESCAPED_UNICODE));
}
if(isset($_POST["date"])){
	$date = $_POST["date"];
}
else{
	exit(json_encode(["seccess"=>false,"message"=>"請指定日期"],JSON_UNESCAPED_UNICODE));
}
if(isset($_POST["hours"])){
	$hours = $_POST["hours"];
}
else{
	exit(json_encode(["seccess"=>false,"message"=>"請指定時段"],JSON_UNESCAPED_UNICODE));
}
if(isset($_POST["playlist"])){
	$playlist = $_POST["playlist"];
}

$logFilePath = dirname(__FILE__).'/../../'.Config::SECRET_FOLDER."/apiLog/sendPlayListByPost";
if(!is_dir($logFilePath)){
	if (!mkdir($logFilePath, 0777, true)) {
		die('Failed to create log directories...');
	}
}

$fileForUpload=array();
//procuce file for upload
foreach($date as $d){
	foreach($channel_id as $c){
		foreach($hours as $h){
			$dir = BarkerConfig::$playlistFolder."/$c";
			if(!is_dir($dir)){
				if (!mkdir($dir, 0777, true)) {
					$this->dolog('Failed to create directories '.$dir.'...');
					die('Failed to create directories '.$dir.'...');
				}
			}
			$filename= $dir."/".$d."_".str_pad($h,2,'0',STR_PAD_LEFT).".json";
			$output=array();
			if($h=="all"){
				for($i=0;$i<24;$i++){
					array_push($output,
					array(
						"channel_id"=>$c,
						"date"=>$d,
						"hour"=>str_pad($i, 2, '0', STR_PAD_LEFT),
						"playlist"=>$playlist
					));
				}
			}
			else{
				array_push($output,array(
					"channel_id"=>$c,
					"date"=>$d,
					"hour"=>str_pad($h, 2, '0', STR_PAD_LEFT),
					"playlist"=>$playlist
				));
			}
			//write data
			$writer = fopen($filename,"w");
			fwrite($writer,json_encode($output,JSON_UNESCAPED_UNICODE));
			fclose($writer);
			array_push($fileForUpload,$filename);
		}
	}
}

//upload files
$sftp = new PutToWatchFolder();
foreach($fileForUpload as $upladFile){
	if(!$sftp->uploadedPlayList($upladFile));
}

exit(json_encode(["success"=>true,"message"=>"檔案已產生並上傳:".implode(",",$fileForUpload)],JSON_UNESCAPED_UNICODE));
?>
 