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
			if(isset($_POST["uploadToPointServer"]) && $_POST["uploadToPointServer"] ){
				if(!$this->saveTempFileAndPutToSftp($this->excelData))
					$this->exitWithMessage(false,"上傳檔案失敗");
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
			if(!$this->sendXslxFile($tmpfname))
				return false;
			unlink($tmpfname);
			return true;
		}

		private function saveTempXslxFile($excelData,$tmpfname){
			// 創建一個新的 Spreadsheet 對象
			$spreadsheet = new Spreadsheet();
			// 選擇活動工作表並將數據設置為單元格值
			// 創建一個新的 Spreadsheet 對象
			$spreadsheet = new Spreadsheet();

			// 取得活動工作表
			$sheet = $spreadsheet->getActiveSheet();

			// 將數據填充到工作表
			$sheet->fromArray($excelData, null, 'A1');

			// 取得活動工作表的樣式對象
			$styleArray = $sheet->getStyle($sheet->calculateWorksheetDimension());

			// 取得框線對象
			$borders = $styleArray->getBorders();

			// 設置框線
			$borders->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
			$borders->getAllBorders()->getColor()->setRGB('000000'); // 設置框線顏色

			// 將 Spreadsheet 寫入檔案
			$writer = new Xlsx($spreadsheet);
			$writer->save($tmpfname);
		}

		private function sendXslxFile($tmpfname){
			//upload files
			$remoteFileName = $this->date."_".$this->hour."破口廣告.xlsx";
			$sftp = new PutToWatchFolder("breachAd");
			if(!$sftp->uploadedBreachAdPlayList($tmpfname,$remoteFileName)){
				return false;
			}
			if(!$sftp->markUploadedPlaylist($this->channel,$tmpfname)){
				return false;
			}
			return true;
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