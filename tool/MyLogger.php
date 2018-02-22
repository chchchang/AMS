<?php
	require_once dirname(__FILE__).'/../Config.php';
	require dirname(__FILE__).'/log4php/Logger.php';
	
	Logger::configure(dirname(__FILE__).'/../'.Config::SECRET_FOLDER.'/log4php4MyLogger.xml');
	
	class MyLogger {
		private $logger=null;
		
		public function __construct() {
			$this->logger=Logger::getLogger('main');
		}
		
		private function log($type,$str) {
			/*
				backtrace回傳的結果陣列中一定至少有兩個元素，這是因為會先執行info或warn或error再去呼叫log，故實際發生錯誤的最近一個caller位於index為1或2。
				當index為1時，表示發生錯誤前沒有任何函數呼叫，故function設為null；
				否則index即為2，但此時需考慮include、include_once、require、require_once亦被視為函數呼叫，故要忽略。
			*/
			$tmp=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			/*if(isset($tmp[2])&&(array_search($tmp[2]['function'],array('include','include_once','require','require_once'))===false))
				$tmp=$tmp[2];
			else {
				$tmp=$tmp[1];
				$tmp['function']=null;
			}*/
			$prestr="";
			for($i=1;$i<count($tmp);$i++){
				@$prestr.='[Method:'.$tmp[$i]['function'].'(at '.$tmp[$i]['file'].' line '.$tmp[$i]['line'].')]';
			}
			$str=$prestr.' Message:'.$str;
			$str=str_replace("\r",'\r',$str);
			$str=str_replace("\n",'\n',$str);
			$this->logger->$type($str);
		}
		
		public function info($str) {
			$this->log('info',$str);
		}
		
		public function warn($str) {
			$this->log('warn',$str);
		}
		
		public function error($str) {
			$this->log('error',$str);
		}
	}
?>