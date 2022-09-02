<?php 
/**
 * 2022-08-18
 * 處理BD中的帶上傳播表/素材
*/
//require_once '/var/www/html/AMS/tool/MyDB.php';
require_once dirname(__FILE__).'/../../../tool/MyDB.php';
//require_once '/var/www/html/AMS/Config.php';
require_once dirname(__FILE__).'/../../../Config.php';
require_once dirname(__FILE__).'/ConvertCampsPlayList.php';
require_once dirname(__FILE__).'/UploadMaterialByPlayList.php';


class ComsumReversedSch{
	private $logWriter ;
	public $message;
	function __construct($logger=null) {
		if($logger!=null){
			$this->logWriter = $logger;
		}
		else{
			$logFilePath = dirname(__FILE__).'/../../'.Config::SECRET_FOLDER."/apiLog/ComsumReversedSch";
			if(!is_dir($logFilePath)){
				if (!mkdir($logFilePath, 0777, true)) {
					die('Failed to create log directories...');
				}
			}
			$logFilePath .="/".date("Y-m-d").".log";
			$this->logWriter = fopen($logFilePath,"a");
		}
	}

	public function handle($date,$channel_id,$hours){
		$this->message = "";

		
		
		$converCampsPlaylist = new ConverCampsPlaylist($this->logWriter);
		if(!$converCampsPlaylist->hadle($date,$channel_id,$hours)){
			$this->message.="排播表產生失敗";
			return false;
		}
		$this->message.=$converCampsPlaylist->message;


		$UploadMaterialByPlayList = new UploadMaterialByPlayList($this->logWriter);
		if(!$UploadMaterialByPlayList->hadle($date,$channel_id,$hours)){
			$this->message.="素材上傳失敗";
			return false;
		}


		if(!$converCampsPlaylist->uploadToPumping()){
			$this->message.="排播表上傳失敗";
			return false;
		}
		$this->message.=$converCampsPlaylist->message;
		return true;
	}

}



?>
 