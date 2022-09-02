<?php 
	/****
	取的廣告資訊API
	***/
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/../../tool/MyDB.php';
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

			$logFilePath = dirname(__FILE__).'/../../'.Config::SECRET_FOLDER."/apiLog/reportVideoPlayFail";
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
			if(!isset($_POST["channel_id"])||!isset($_POST["file_name"])||!isset($_POST["transaction_id"])||!isset($_POST["play_time"])||!isset($_POST["message"])){
				$this->writeLog("parameter not set");
				return false;
			}
			return true;
		}

		/**
		 * 處理資料
		 */
		private function processingData(){
			$this->writeLog("processing data");
			$data = array(
				"channel_id"=>$_POST["channel_id"],
				"file_name"=>$_POST["file_name"],
				"transaction_id"=>$_POST["transaction_id"],
				"play_time"=>$_POST["play_time"],
				"message"=>$_POST["message"],
			);
			return $data;
		}
		/**
		 * 檢查MD5是否正確
		 */
		private function checkSum($chck){

		}
		/**
		 * 輸入資料到DB
		 */
		private function insertToDb($data){
			$this->writeLog("insert data to Db");
			$my=new MyDB(true);
			$my->begin_transaction();
			$sql = "INSERT INTO barker_play_fail_log (channel_id,file_name,transaction_id,play_time,message) VALUES (?,?,?,?,?)";
			//make sure to keep the order of parameters in "update" and "insert" sql the same so that they can use the same execute fucntion
			if(!$my->execute($sql,'isiss',$data["channel_id"],$data["file_name"],$data["transaction_id"],$data["play_time"],$data["message"])){
				$this->writeLog("cant execute sql","error");
				$my->rollback();
				$my->close();
				return false;
			}

			$my->commit();
			$my->close();
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
 