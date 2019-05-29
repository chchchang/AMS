<?php
	require_once dirname(__FILE__).'/MyLogger.php';
	require_once dirname(__FILE__).'/../Config.php';
	
	class MyStmt extends mysqli_stmt {
		private $logger=null;
		private $isIgnoreError=false;
		
		public function __construct($link,$sql,$isIgnoreError=false) {
			$this->logger=new MyLogger();
			$this->isIgnoreError=$isIgnoreError;
			parent::__construct($link,$sql);
		}
		
		public function execute() {
			if(parent::execute())
				return true;
			else {
				$this->logger->error('無法執行statement，錯誤代碼('.$this->errno.')、錯誤訊息('.$this->error.')。');
				if(!$this->isIgnoreError) exit('無法執行statement，請聯絡系統管理員！');
				return false;
			}
		}
		
		public function get_result() {
			$tmp=parent::get_result();
			if(!$tmp) {
				$this->logger->error('無法取得結果集，錯誤代碼('.$this->errno.')、錯誤訊息('.$this->error.')。');
				if(!$this->isIgnoreError) exit('無法取得結果集，請聯絡系統管理員！');
				return false;
			}
			else
				return $tmp;
		}
	}
	
	class MyDB extends mysqli {
		private $logger=null;
		private $isIgnoreError=false;
		
		public function __construct($isIgnoreError=false) {
			$this->logger=new MyLogger();
			$this->isIgnoreError=$isIgnoreError;
			parent::__construct(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
			if($this->connect_errno) {
				$this->logger->error('無法連線到資料庫，錯誤代碼('.$this->connect_errno.')、錯誤訊息('.$this->connect_error.')。');
				if(!$this->isIgnoreError) exit('無法連線到資料庫，請聯絡系統管理員！');
			}
			if(!$this->set_charset('utf8')) {
				$this->logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$this->errno.')、錯誤訊息('.$this->error.')。');
				if(!$this->isIgnoreError) exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
			}
		}
		
		public function prepare($sql) {
			$tmp=new myStmt($this,$sql,$this->isIgnoreError);
			if($this->errno) {
				$this->logger->error('無法準備statement，錯誤代碼('.$this->errno.')、錯誤訊息('.$this->error.')。');
				if(!$this->isIgnoreError) exit('無法準備statement，請聯絡系統管理員！');
				return false;
			}
			else
				return $tmp;
		}
		
		public function getResult() {
			$args=func_get_args();
			if(count($args)===1) {
				$sql=$args[0];
				if(!$stmt=$this->prepare($sql)) return false;
				if(!$stmt->execute()) return false;
				return $res=$stmt->get_result();
			}
			else if(count($args)>=3) {
				$sql=$args[0];
				$types=$args[1];
				$vars=array_slice($args,2);
				$varsRef=array();
				foreach($vars as $k=>$v) {
					$varsRef[]=&$vars[$k];
				}
				if(!$stmt=$this->prepare($sql)) return false;
				if(!call_user_func_array(array($stmt,'bind_param'),array_merge(array($types),$varsRef))) return false;
				if(!$stmt->execute()) return false;
				return $res=$stmt->get_result();
			}
			else 
				return false;
		}
		
		public function getResultArray() {
			if($res=call_user_func_array(array($this,'getResult'),func_get_args())) {
				$result=array();
				while($row=$res->fetch_assoc())
					$result[]=$row;
				/*if(count($result)===0)
					return null;
				else*/
					return $result;
			}
			else
				return false;
		}
		
		public function execute() {
			$args=func_get_args();
			if(count($args)===1) {
				$sql=$args[0];
				if(!$stmt=$this->prepare($sql)) return false;
				return $stmt->execute();
			}
			else if(count($args)>=3) {
				$sql=$args[0];
				$types=$args[1];
				$vars=array_slice($args,2);
				$varsRef=array();
				foreach($vars as $k=>$v) {
					$varsRef[]=&$vars[$k];
				}
				if(!$stmt=$this->prepare($sql)) return false;
				if(!call_user_func_array(array($stmt,'bind_param'),array_merge(array($types),$varsRef))) return false;
				return $stmt->execute();
			}
			else 
				return false;
		}
	}
?>