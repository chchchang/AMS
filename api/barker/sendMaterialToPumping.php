<?php 
/**2022-07-29 上傳播表到端點pumping server API
 * 內容與cronjob的converCampsPlaylist基本相同，
 * 不過取的執行參數的方式改用POST
 * 因資安因素考量，才將排程conjob與API分開為兩個檔案，放置於不同環境。
 * 
*/
//require_once '/var/www/html/AMS/tool/MyDB.php';
require_once dirname(__FILE__).'/../../tool/MyDB.php';
//require_once '/var/www/html/AMS/Config.php';
require_once dirname(__FILE__).'/../../Config.php';
require_once dirname(__FILE__).'/module/UploadMaterialByPlayList.php';
require_once dirname(__FILE__).'/module/PutToWatchFolder.php';
require_once dirname(__FILE__).'/module/BarkerConfig.php';

$sftp = new PutToWatchFolder();
$rawMaterialFolder = Config::GET_MATERIAL_FOLDER(); 
$sftpInfo=BarkerConfig::$sftpInfo;
$remoteMaterialFolder = BarkerConfig::$remoteMaterialFolder;
$mid = $_POST["素材識別碼"];
//取的素材原始檔名
$mydb=new MyDB(true);
$sql = "select 素材原始檔名 from  素材  where 素材識別碼 = ?";
$data = $mydb->getResultArray($sql,'i',$mid);

$fliename = "";
if($data[0] != null){
	$mname = $data[0]["素材原始檔名"];
	$fliename =$mid."_".$mname;
}





$tmp = explode(".",$fliename);
$mtype = end($tmp);
$rawFileName = $mid.".".$mtype;
$remoteFile = $remoteMaterialFolder."/".$fliename;
if(checkLocalMaterial($rawMaterialFolder.$rawFileName)){
	if($sftp->uploadedMaterial($rawMaterialFolder.$rawFileName, $remoteFile)){
		$nameParse = explode('_',$file_name);
		$material_id = array_shift($nameParse);
		$sql = "
		INSERT INTO barker_material_import_result (material_id,file_name) VALUES (?,?)	
		ON DUPLICATE KEY
		UPDATE import_time=now(),import_result=0,message='已上傳，等待barker系統回報',last_updated_time=now()"
		;
		$mydb->execute($sql,'is',$material_id,$file_name);
		$mydb->close();
		exit(json_encode(["seccess"=>true,"message"=>"上傳到端點barker成功"],JSON_UNESCAPED_UNICODE));
	}else{
		exit(json_encode(["seccess"=>false,"message"=>"上傳到端點barker失敗"],JSON_UNESCAPED_UNICODE));
	}
	
} 
else{
	$mydb->close();
	exit(json_encode(["seccess"=>false,"message"=>"本地檔案不存在"],JSON_UNESCAPED_UNICODE));
}

function checkLocalMaterial($filepath){
	global $mydb,$rawMaterialFolder;
	if(file_exists($filepath)){
		return true;
	}
	else{
		$file_name = str_replace($rawMaterialFolder,"",$filepath);
		$nameParse = explode('_',$file_name);
		$material_id = array_shift($nameParse);
		$sql = "
		INSERT INTO barker_material_import_result (material_id,file_name) VALUES (?,?)	
		ON DUPLICATE KEY
		UPDATE import_time=now(),import_result=0,message='AMS端檔案不存在',last_updated_time=now()"
		;
		if(!$mydb->execute($sql,'is',$material_id,$file_name)){
			return false;
		}
		return false;
	}
}




?>
 