<?php
	require_once dirname(__FILE__)."/../../../tool/MyDB.php";
	require_once dirname(__FILE__)."/../module/BreachAdPlaylistRepository.php";
	require_once dirname(__FILE__)."/../module/TransactionRepository.php";	
	
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