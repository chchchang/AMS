<?php 
	/****
	*回報abrekr素材派送結果API
	2022 08 19 新增checksum功能
	2022 09 20 增加mail告警
	2022 10 18 回報結果為true才檢查checksum
	***/
	header("Content-Type:text/html; charset=utf-8");
	require_once __DIR__.'/../../tool/MyDB.php';
	require_once __DIR__.'/../../tool/MyMailer.php';
	require_once __DIR__.'/../../apiProxy/AmsDb/module/MaterialRepository.php';
	$apiHandle = new ApiHandle();
	$apiHandle->handle();

	class ApiHandle {
		private $returnMessage;
		private $logWriter;
		private $rawMaterialFolder = "";
		public function __construct(){
			$this->returnMessage = array(
				"1"=>"",
				"001"=>"參數錯誤",
				"999"=>"其他錯誤",
			);

			$logFilePath = __DIR__.'/../../'.Config::SECRET_FOLDER."/apiLog/reportVideoFileImportResult";
			if(!is_dir($logFilePath)){
				if (!mkdir($logFilePath, 0777, true)) {
					die('Failed to create log directories...');
				}
			}
			$logFilePath .="/".date("Y-m-d").".log";
			$this->logWriter = fopen($logFilePath,"a");
			$this->rawMaterialFolder = Config::GET_MATERIAL_FOLDER();
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
			if(!isset($_POST["file_name"])||!isset($_POST["import_result"])||!isset($_POST["import_time"])||!isset($_POST["import_message"])){
				$this->writeLog("parameter not set");
				return false;
			}
			
			return true;
		}
		private function checkSum($file_name){
			if(!isset($_POST["check_sum"]) || $_POST["check_sum"]=="" || $_POST["check_sum"]==null)
				return true;
				$this->writeLog("remote check_sum:".$_POST["check_sum"]);
			$mid = explode("_",$file_name)[0];
			$tmp = explode(".",$file_name);
			$mtype = end($tmp);
			$rawFileName = $mid.".".$mtype;
			$md5_result=md5_file($this->rawMaterialFolder.$rawFileName);
			if($md5_result!=$_POST["check_sum"]){
				$this->writeLog("check_sum dosn't match: remote:".$_POST["check_sum"]." local:".$md5_result);
				return false;
			}
			return true;
		}
		/**
		 * 處理資料
		 */
		private function processingData(){
			$this->writeLog("processing data");
			//file_name include two parts:<material_id>_<file_name>
			$nameParse = explode('_',$_POST["file_name"]);
			$material_id = array_shift($nameParse);
			//because file_name may include charater "_", we must implode the remaining array with "_"
			//$file_name = implode("_",$nameParse);
			$file_name = $_POST["file_name"];
			$data = array(
				"material_id"=>$material_id,
				"file_name"=>$file_name,
				"import_time"=>$_POST["import_time"]?$_POST["import_time"]:date("Y-m-d H:i:s", time()),
				"import_result"=>(strtolower($_POST["import_result"])=="true"||$_POST["import_result"]==1)?true:false,
				"message"=>$_POST["import_message"],
			);
			if($data["import_result"] && !$this->checkSum($file_name)){
				$data["import_result"] = false;
				$data["message"] .= "md5值錯誤，檔案異常";
			}
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
			/*$sql = "SELECT COUNT(*) AS C FROM barker_material_import_result WHERE 	material_id  =? AND file_name =?";
			if(!$result = $my->getResultArray($sql,'is',$data["material_id"],$data["file_name"])){
				$my->rollback();
				$my->close();
				return false;
			}
			if($result[0]["C"]!=0){
				//data exist, ues UPDATE sql
				$sql = "UPDATE barker_material_import_result SET import_time=?,import_result=?,message=?,last_updated_time=now() WHERE material_id  =? AND file_name =?";
				$this->writeLog("data exist, use UPDATE sql");
			}
			else{
				//data haven't insert, use INSERT sql
				$sql = "INSERT INTO barker_material_import_result (import_time,import_result,message,material_id,file_name) VALUES (?,?,?,?,?)";
				$this->writeLog("data haven't insert, use INSERT sql");
			}
			//make sure to keep the order of parameters in "update" and "insert" sql the same so that they can use the same execute fucntion
			if(!$my->execute($sql,'sisis',$data["import_time"],$data["import_result"],$data["message"],$data["material_id"],$data["file_name"])){
				$this->writeLog("cant execute sql","error");
				$my->rollback();
				$my->close();
				return false;
			}*/

			$sql = "
				INSERT INTO barker_material_import_result (import_time,import_result,message,material_id,file_name) VALUES (?,?,?,?,?)	
				ON DUPLICATE KEY
				UPDATE import_time=?,import_result=?,message=?,last_updated_time=now()"
			;
			if(!$my->execute($sql,'sisissis',$data["import_time"],$data["import_result"],$data["message"],$data["material_id"],$data["file_name"],$data["import_time"],$data["import_result"],$data["message"])){
				$this->writeLog("cant execute sql","error");
				$my->rollback();
				$my->close();
				return false;
			};

			$my->commit();
			$my->close();

			//如果匯入失敗，發出告警信
			if(!$data["import_result"]){
				$materialRepo = new MaterialRepository();
				$mInfo = $materialRepo->getMaterialInfo($data["material_id"]);
				$mailer = new MyMailer();
				$mailer->sendMail("barker素材:".$data["file_name"]." 檔案匯入失敗","barker素材:".$data["file_name"]." 檔案匯入失敗\n素材識別碼:".$data["material_id"]." 素材名稱:".$mInfo["素材名稱"]."\n失敗原因:".$data["message"]);
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
 