<?php
	require dirname(__FILE__).'/PHPExcel/Classes/PHPExcel.php';
	
	require dirname(__FILE__).'/PHPExcel/Classes/PHPExcel/Writer/Excel2007.php';
	
	class OutputExcel {
		private $objPHPExcel=null;
		private $currentRowIndex=1;
		private $currentSheetIndex=0;
		private $output_filename='';
		
		public function __construct($output_filename) {
			$this->objPHPExcel=new PHPExcel();
			$this->objPHPExcel->setActiveSheetIndex(0);
			$this->output_filename=$output_filename;
		}
		
		public function __destruct() {
			$this->close();
		}
		
		public static function outputAll($output_filename,$sheet) {
			$objPHPExcel=new PHPExcel();
			$objPHPExcel->setActiveSheetIndex(0);
			foreach($sheet as $rowIndex=>$rowValues) {
				foreach($rowValues as $colIndex=>$colValue) {
					$objPHPExcel->getActiveSheet()->setCellValueExplicit(PHPExcel_Cell::stringFromColumnIndex($colIndex).($rowIndex+1),$colValue,PHPExcel_Cell_DataType::TYPE_STRING);
				}
			}
			$objWriter=new PHPExcel_Writer_Excel5($objPHPExcel);
			$objWriter->save($output_filename.'.xls');
		}
		
		public static function outputAll_sheet($output_filename,$sheets) {
			$currentSheetIndex = 0;
			$objPHPExcel=new PHPExcel();
			foreach($sheets as $sheetName=>$sheet) {
				$objPHPExcel->createSheet($currentSheetIndex);
				$objPHPExcel->setActiveSheetIndex($currentSheetIndex);
				$objPHPExcel->getActiveSheet()->setTitle($sheetName);
				foreach($sheet as $rowIndex=>$rowValues) {
					foreach($rowValues as $colIndex=>$colValue) {
						$objPHPExcel->getActiveSheet()->setCellValueExplicit(PHPExcel_Cell::stringFromColumnIndex($colIndex).($rowIndex+1),$colValue,PHPExcel_Cell_DataType::TYPE_STRING);
					}
				}
				$currentSheetIndex++;
			}
			$objWriter=new PHPExcel_Writer_Excel5($objPHPExcel);
			$objWriter->save($output_filename.'.xls');
		}
		
		public function outputRow($row) {
			foreach($row as $colIndex=>$colValue) {
				$this->objPHPExcel->getActiveSheet()->setCellValueExplicit(PHPExcel_Cell::stringFromColumnIndex($colIndex).$this->currentRowIndex,$colValue,PHPExcel_Cell_DataType::TYPE_STRING);
			}
			$this->currentRowIndex++;
		}
		
		public function outputSheet($output_sheetname,$sheet) {
			$this->objPHPExcel->createSheet($this->currentSheetIndex);
			$this->objPHPExcel->setActiveSheetIndex($this->currentSheetIndex);
			$this->objPHPExcel->getActiveSheet()->setTitle($output_sheetname);
			foreach($sheet as $rowIndex=>$rowValues) {
				foreach($rowValues as $colIndex=>$colValue) {
					$this->objPHPExcel->getActiveSheet()->setCellValueExplicit(PHPExcel_Cell::stringFromColumnIndex($colIndex).($rowIndex+1),$colValue,PHPExcel_Cell_DataType::TYPE_STRING);
				}
			}
			$this->currentSheetIndex++;
		}
		
		public function close() {
			$objWriter=new PHPExcel_Writer_Excel5($this->objPHPExcel);
			$objWriter->save($this->output_filename.'.xls');
		}
		
		public function close_download() {
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
			header('Content-Disposition: attachment; filename="results.xls"');
			header("Cache-Control: max-age=0");
			$objWriter=PHPExcel_IOFactory::createWriter($this->objPHPExcel, "Excel2007");
			$objWriter->save('php://output');
			ob_clean();
		}
	}
?>