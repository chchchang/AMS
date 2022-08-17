<?php 
//$htmlpath ="../../";//dev
$htmlpath ="/var/www/html/AMS/";//pro
require_once $htmlpath.'api/barker/module/PlaylistImportSchDb.php';
//require_once $htmlpath.'api/barker/module/ConvertCampsPlayList.php';
//require_once $htmlpath.'api/barker/module/UploadMaterialByPlayList.php';
require_once $htmlpath.'api/barker/module/SendPlayListToPumping.php';



//touch a file to notified os that a prosses is running
touch("importDealerProcessing");
//initialize
$logFilePath = dirname(__FILE__)."/log/reservedImportDealer";
if(!is_dir($logFilePath)){
	if (!mkdir($logFilePath, 0777, true)) {
		die('Failed to create log directories...');
	}
}
$logFilePath .="/".date("Y-m-d").".log";
$logWriter = fopen($logFilePath,"a");

//get reversed data
$mydb = new PointBarkerDB();

$date = "";
$channel_id = "";
$hours = "";
//try to get reserved import or exit
while(1){
    $data = $mydb->getFirstSch();
    print_r($data);
    if($data==[]){
        unlink("importDealerProcessing");
        exit();
    }
    $data = $data[0];
    $date = $data["date"];
    $channel_id = $data["channel_id"];
    $hours = $data["hour"];
    dolog("$channel_id : $date - $hours");
    $cdate = date("Y-m-d");
    if($date < $cdate || ($date==$cdate && $hours!="all" && $hours<date("H"))){
        dolog("outdated data, houskeeping");
        $mydb->housKeeping();
    }
    else{
        //exit();
        break;
    }
}

/*//upload playlist
$converCampsPlaylist = new ConverCampsPlaylist($logWriter);
if(!$converCampsPlaylist->hadle($date,$channel_id,$hours)){
    dolog("排播表產生/上傳失敗");
    unlink("importDealerProcessing");
	exit("排播表產生/上傳失敗");
}

dolog($converCampsPlaylist->message);
echo $converCampsPlaylist->message;

//uploadMaterial
$UploadMaterialByPlayList = new UploadMaterialByPlayList($logWriter);
if(!$UploadMaterialByPlayList->hadle($date,$channel_id,$hours)){
    unlink("importDealerProcessing");
    exit("素材上傳失敗");
}
	
dolog($UploadMaterialByPlayList->message);*/

$hadler = new SendPlayListToPumping($logWriter);
$result = $hadler->handle($date,$channel_id,$hours);
dolog($hadler->message);

//delete reversed
dolog("delete data $channel_id : $date - $hours .....");
$mydb->deleteSch($channel_id,$date, $hours);

unlink("importDealerProcessing");
function dolog($line){
    global $logWriter;
    $message = date('Y-m-d h:i:s')." ".$line."\n";
    echo $message;
    fwrite($logWriter,$message);
}
?>
 