<?php 
/**2022-07-29 上傳播表到端點pumping server API
 * 內容與cronjob的converCampsPlaylist基本相同，
 * 不過取的執行參數的方式改用POST
 * 因資安因素考量，才將排程conjob與API分開為兩個檔案，放置於不同環境。
 * 2022-08-16
 * 素材上傳後再上傳播表
*/
//require_once '/var/www/html/AMS/tool/MyDB.php';
require_once dirname(__FILE__).'/../../../tool/MyDB.php';
//require_once '/var/www/html/AMS/Config.php';
require_once dirname(__FILE__).'/../../../Config.php';
require_once dirname(__FILE__).'/ConvertCampsPlayList.php';
require_once dirname(__FILE__).'/UploadMaterialByPlayList.php';


class SendPlayListToPumping{
	private $logWriter ;
	public $message;
	function __construct($logger=null) {
		if($logger!=null){
			$this->logWriter = $logger;
		}
		else{
			$logFilePath = dirname(__FILE__).'/../../'.Config::SECRET_FOLDER."/apiLog/sendPlayListToPumping";
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
 