<?php
	require_once dirname(__FILE__)."/../../../tool/MyDB.php";
	require_once dirname(__FILE__)."/../module/BreachAdPlaylistRepository.php";
	require_once dirname(__FILE__)."/../module/TransactionRepository.php";	
	require dirname(__FILE__).'../../../../vendor/autoload.php';

	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

	$handeler = new apiHandler();
	$handeler->handle();

	class apiHandler {
		private $date = null;
		private $channel = null;
		private $hour = null;
		private $excelData = [];
		public function handle(){
			$this->checkParas();
			$this->excelData  = $this->getBreachExcelDataFromDb();
			if(isset($_POST["uploadToPointServer"]) && isset($_POST["uploadToPointServer"])){
				$this->saveTempFileAndPutToSftp($this->excelData);
			}
			
			$this->exitWithMessage(true,"success");
		
		}

		private function checkParas(){
			if(!isset($_POST["date"]) || !isset($_POST["channel"])){
				$this->exitWithMessage(false,"缺少必要參數");
			}
			$this->date = $_POST["date"];
			$this->channel = $_POST["channel"];
			$this->hour = isset($_POST["hour"])? $_POST["hour"] : "all";
		}

		private function getBreachExcelDataFromDb(){
			$my=new MyDB(true);
			$BreachAdRepository = new BreachAdPlaylistRepository($my);
			$palylist = $BreachAdRepository->getBreachAdExcelDataBySchedule($this->channel,$this->date,$this->hour);
			return $palylist;
		}

		private function saveTempFileAndPutToSftp($excelData){
			$tmpfname = tempnam("", "bAd").".xlsx";
			$this->saveTempXslxFile($excelData,$tmpfname);
			print_r($tmpfname);
			$this->sendXslxFile($tmpfname);
			unlink($tmpfname);
		}

		private function saveTempXslxFile($excelData,$tmpfname){
			// 創建一個新的 Spreadsheet 對象
			$spreadsheet = new Spreadsheet();
			// 選擇活動工作表並將數據設置為單元格值
			$spreadsheet->getActiveSheet()->fromArray($excelData, null, 'A1');
			// 創建一個 Xlsx 寫入器並保存 Excel 檔案
			$writer = new Xlsx($spreadsheet);
			$writer->save($tmpfname);
		}

		private function sendXslxFile($tmpfname){
			//upload files
			$remoteFileName = $this->date."_".$this->hour."破口廣告.xlsx";
			$sftp = new PutToWatchFolder();
			$sftp->uploadedBreachAdPlayList($tmpfname,$remoteFileName);
		}

		private function exitWithMessage($success,$message = ""){
			$exit = [
				"success" => $success,
				"message" => $message,
				"excelData" => $this->excelData,
			];
			exit(json_encode($exit,JSON_UNESCAPED_UNICODE));
		}
	}
	
	
	
?>