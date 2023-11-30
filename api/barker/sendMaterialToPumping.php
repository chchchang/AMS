<?php 
/**2022-07-29 上傳播表到端點pumping server API
 * 內容與cronjob的converCampsPlaylist基本相同，
 * 不過取的執行參數的方式改用POST
 * 因資安因素考量，才將排程conjob與API分開為兩個檔案，放置於不同環境。
 * 
*/
require_once "./module/SendMaterialToPumping.php";
$sender = new SendMaterialToPumping();
$mid = "";
$adType = null;
if(isset($argv[1])){
	$mid = $argv[1];
}
else{
	$mid = $_POST["素材識別碼"];
}

if(isset($argv[2]) || isset($_POST["破口"])){
	$adType = $_POST["破口"];
}

if(!$sender->uploadByMaterialId($mid,$adType)){
	exit(json_encode(["seccess"=>false,"message"=>$sender->message],JSON_UNESCAPED_UNICODE));
}
exit(json_encode(["seccess"=>true,"message"=>"上傳到端點barker成功"],JSON_UNESCAPED_UNICODE));

?>
 