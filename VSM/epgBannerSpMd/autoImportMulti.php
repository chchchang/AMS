<?php
/***
 * 每日定期匯入檔案的排成使用
 * 會檢查IAB SFTP下awaiting是否有新檔案
 * 若有則下載回本地local資料夾，
 * 匯入後將等名著計"最新匯入"
 * 並將IAB SFTP中awaiting的檔案移動到complete中
*/
date_default_timezone_set("Asia/Taipei");
require_once('../../Config_VSM_Meta.php');
require_once('../../Config.php');
require_once('sepgSpMdParserMulti.php');
require_once('../../tool/MyDB.php');
set_include_path('../../tool/phpseclib');
include('Net/SFTP.php');
$autoPraser = new autoPraser();
$autoPraser->execute();

class autoPraser{
    var $localDir = "";
    var $awaitingDir = "";
    var $completeDir = "";
	var $conn = "";

    function __construct(){
		$this->localDir = "localFile/";
		//測試用用complete資料夾下的檔案
		//$this->awaitingDir = Config::$FTP_SERVERS['IAB'][0]['complete'];
		//正式
		$this->awaitingDir = Config::$FTP_SERVERS['IAB_SPEPGMD_MULTI'][0]['awaiting'];

		$this->completeDir = Config::$FTP_SERVERS['IAB_SPEPGMD_MULTI'][0]['complete'];
		$this->conn = $this->getConnect();
        
	}
	function execute(){
		if($this->conn){
			$remoteFiles = $this->getRemote();
			foreach($remoteFiles as $remoteFile){
				$fileName = $remoteFile;
				$this->importRemoteFile($fileName);
			}
		}
		else{
			echo "無法連線\n";
		}
	}
    //檢查遠端檔案
    function getRemote(){
		echo "取得".$this->awaitingDir."\n";
		//掃描資料夾檔案並分析生效日期
		$nlist = $this->conn->nlist($this->awaitingDir);
		$srotArray = array();
		foreach($nlist as $n){
			if($n=='.'||$n=='..'){
				continue;
			}
			//去除資料夾路徑
			$ndirname = str_replace($this->awaitingDir,'',$n);
			//取得ID
			preg_match('/(\S+)_SepgSpMd\_(\S+)\.dat/', $ndirname, $matches);
			if(count($matches)==0)
				preg_match('/(\S+)_SepgSpMD\_(\S+)\.dat/', $ndirname, $matches);
			
				$id = $matches[2];
			$srotArray[] = array("name"=>$ndirname,"id"=>$id);
		};
		//依照日期排序
		//usort($srotArray,array('autoPraser','cmp'));
		
		$data = array();
		foreach($srotArray as $fileObj){
			$data[] = $fileObj["name"];
		}
		return $data;
    }
    //下載遠端檔案
    function importRemoteFile($fileName){
		echo "匯入".$fileName."\n";
		$localFile = $this->localDir.$fileName;
		$remoteFile = $this->awaitingDir.$fileName;
		$remoteFile_complete = $this->completeDir.$fileName;
		//下載檔案並匯入
		//下載檔案
		if(!$this->conn->get($remoteFile, $localFile)){
			exit('無法下載FTP server('.$remoteFile.')檔案到('.$localFile.')。');
		}
		//匯入檔案
		$result = $this->importFile($localFile);
		if(!$result["success"]){
			exit(($result["message"]));
		}
		//刪除遠端檔案
		$result=$this->deleteRemote($remoteFile,$this->conn);
		if(!$result["success"]){
			exit(($result["message"]));
		}
		//上傳檔案到complete資料夾
		$result=$this->putToComplete($remoteFile_complete,$localFile,$this->conn);
		if(!$result["success"]){
			exit(($result["message"]));
		}

		echo '匯入遠端檔案成功'.$fileName.'成功\n';
		return $result;
    }

    function getConnect(){
		//設定連線資訊
		$url = Config::$FTP_SERVERS['IAB'][0]['host'];
		$usr = Config::$FTP_SERVERS['IAB'][0]['username'];
		$pd = Config::$FTP_SERVERS['IAB'][0]['password'];
		/*$conn = ftp_connect($url) or die("Could not connect");
		ftp_pasv($conn, true); 
		ftp_login($conn,$usr,$pd);*/
		$conn = new Net_SFTP($url);
		if (!$conn->login($usr, $pd)) {
			echo '無法Sftp連線到FTP server('.$url.')\n';
			return false;
		}
		return $conn;
	}
	
	//匯入檔案
	function importFile($fileName){
		$batch = new sepgSpMdParserMulti($fileName);
		$return = $batch->getDataAndAction();
		return $return;
	}
	
	//刪除遠端檔案
	function deleteRemote($fileName,$conn){
		$upload = $conn->delete($fileName); 
		// check upload status
		if (!$upload) { 
			return ["success"=>false,"message"=>'移除遠端檔案'.$fileName.'失敗'];
		} else {
			return ["success"=>true,"message"=>"已將遠端檔案移除"];
		}
	}
	
	function putToComplete($remoteFile,$localFile,$conn){
		$upload = $conn->put($remoteFile, $localFile, NET_SFTP_LOCAL_FILE);
		// check upload status
		if (!$upload) { 
			return ["success"=>false,"message"=>'檔案'.$remoteFile.'上傳到complete資料夾失敗失敗'];
		} else {
			return ["success"=>true,"message"=>"已將檔案上傳到complete資料夾"];
		}
	}

	private static function cmp($a, $b)
	{
		return strcmp($a['id'], $b['id']);
	}
}


?>