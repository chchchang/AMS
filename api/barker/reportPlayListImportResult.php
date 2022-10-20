<?php 
	/****
	回報barker播表匯入結果API
	2022 09 02 新增發送mail告警功能
	***/
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/../../tool/MyDB.php';
	require_once dirname(__FILE__).'/../../tool/MyMailer.php';
	$apiHandle = new ApiHandle();
	$apiHandle->handle();

	class ApiHandle {
		private $returnMessage;
		private $logWriter;
		public function __construct(){
			$this->returnMessage = array(
				"1"=>"",
				"001"=>"參數錯誤",
				"999"=>"其他錯誤",
			);
			$logFilePath = dirname(__FILE__).'/../../'.Config::SECRET_FOLDER."/apiLog/reportPlayListImportResult";
			if(!is_dir($logFilePath)){
				if (!mkdir($logFilePath, 0777, true)) {
					die('Failed to create log directories...');
				}
			}
			$logFilePath .="/".date("Y-m-d").".log";
			$this->logWriter = fopen($logFilePath,"a");
		}

		function __destruct() {
			fclose($this->logWriter);
		}

		public function handle(){
			if(!$this->checkPara()){
				$this->returnJson("001",$this->returnMessage["001"]);
			}
			$data = $this->processingData();
		
			if(!$this->insertToDb($data)){
				$this->returnJson("999",$this->returnMessage["999"]);
			}
			
			$this->returnJson("1",$this->returnMessage["1"]);
		}
		
		/**
		 * 檢查POST參數是否正確
		 */
		private function checkPara(){
			$this->writeLog("checking POST value");
			$this->writeLog(json_encode($_POST,JSON_UNESCAPED_UNICODE));
			if(!isset($_POST["file_path"])||!isset($_POST["import_result"])||!isset($_POST["import_time"])||!isset($_POST["message"])){
				$this->writeLog("parameter not set");
				return false;
			}
			return true;
		}
	
		/**
		 * 處理資料
		 */
		private function processingData(){
			//file_path include two parts:channel_id/file_name
			$this->writeLog("processing data");
			$pathParse = explode('/',$_POST["file_path"]);
			$channel_id = $pathParse[0];
			$file_name = $pathParse[1];
			$data = array(
				"channel_id"=>$channel_id,
				"file_name"=>$file_name,
				"import_time"=>$_POST["import_time"],
				"import_result"=>(strtolower($_POST["import_result"])=="true"||$_POST["import_result"]==1)?true:false,
				"message"=>$_POST["message"],
			);
			return $data;
		}
	
		/**
		 * 輸入資料到DB
		 */
		private function insertToDb($data){
			$this->writeLog("insert data to Db");
			$my=new MyDB(true);
			$my->begin_transaction();
			//先檢查是否有重複的資料
			$sql = "SELECT COUNT(*) AS C FROM barker_playlist_import_result WHERE 	channel_id  =? AND file_name =?";
			if(!$result = $my->getResultArray($sql,'is',$data["channel_id"],$data["file_name"])){
				$this->writeLog("cant get data from db","error");
				$my->rollback();
				$my->close();
				return false;
			}
			if($result[0]["C"]!=0){
				//data exist, use UPDATE sql
				$sql = "UPDATE barker_playlist_import_result SET import_time=?,import_result=?,message=?,last_updated_time=now() WHERE channel_id  =? AND file_name =?";
				$this->writeLog("data exist, use UPDATE sql");
			}
			else{
				//data haven't insert, use INSERT sql
				$sql = "INSERT INTO barker_playlist_import_result (import_time,import_result,message,channel_id,file_name) VALUES (?,?,?,?,?)";
				$this->writeLog("data haven't insert, use INSERT sql");
			}
			//make sure to keep the order of parameters in "update" and "insert" sql the same so that they can use the same execute fucntion
			if(!$my->execute($sql,'sisis',$data["import_time"],$data["import_result"],$data["message"],$data["channel_id"],$data["file_name"])){
				$this->writeLog("cant execute sql","error");
				$my->rollback();
				$my->close();
				return false;
			}
	
			$my->commit();
			$my->close();
			//發出告警信
			if(!$data["import_result"]){
				$mailer = new MyMailer();
				$mailer->sendMail("barker頻道:".$data["channel_id"]." ".$data["file_name"]."排播檔案匯入失敗"
				,"barker頻道:".$data["channel_id"]." ".$data["file_name"]."排播檔案匯入失敗");
			}
			
			return true;
		}
	
		private function returnJson($code,$message){
			$feedback = array(
				"returnCode"=>$code,
				"returnMessage"=>$message
			);
			$this->writeLog("done return code:".$code." message:".$message);
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}

		private function writeLog($message,$prefix="info"){
			$line = date("Y-m-d h:i:s")."[".$prefix."]".$message."\n";
			fwrite($this->logWriter,$line);
		}
	
	}



	
?>
 