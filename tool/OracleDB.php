<?php
	require_once dirname(__FILE__).'/MyLogger.php';
	require_once dirname(__FILE__).'/../Config.php';
	
	class OracleDB
	{
		private $logger=null;
		private $conn=null;
		
		public function __construct($user,$password,$conn_str){
			$this->logger=new MyLogger();
			$this->conn=oci_connect($user,$password,$conn_str,'AL32UTF8');
			if(!$this->conn){
				$this->logger->error('無法連線到資料庫，錯誤代碼('.oci_error()['code'].')、錯誤訊息('.oci_error()['message'].')。');
				exit('無法連線到資料庫，請聯絡系統管理員！');
			}
		}
		
		public function getResult(){
			$args=func_get_args();
			if(count($args)===1){
				$sql=$args[0];
				if(!($stid=oci_parse($this->conn,$sql))){
					$this->logger->error('無法剖析SQL語句。');
					exit('無法剖析SQL語句，請聯絡系統管理員！');
				}
				if(!oci_execute($stid)){
					$this->logger->error('無法執行SQL語句。');
					exit('無法執行SQL語句，請聯絡系統管理員！');
				}
				return $stid;
			}
			else if(count($args)===2){
				$sql=$args[0];
				if(!($stid=oci_parse($this->conn,$sql))){
					$this->logger->error('無法剖析SQL語句。');
					exit('無法剖析SQL語句，請聯絡系統管理員！');
				}
				$vars=$args[1];
				foreach($vars as $v){
					if(isset($v['bv_name'])&&isset($v['variable'])){
						if(!oci_bind_by_name($stid,$v['bv_name'],$v['variable'])){
							$this->logger->error('無法繫結資料。');
							exit('無法繫結資料，請聯絡系統管理員！');
						}
					}
				}
				if(!oci_execute($stid)){
					$this->logger->error('無法執行SQL語句。');
					exit('無法執行SQL語句，請聯絡系統管理員！');
				}
				return $stid;
			}
			else
				return false;
		}
		
		public function getResultArray(){
			if($stid=call_user_func_array(array($this,'getResult'),func_get_args())){
				$result=array();
				while(($row=oci_fetch_array($stid,OCI_ASSOC+OCI_RETURN_NULLS)))
					$result[]=$row;
				oci_free_statement($stid);
				oci_close($this->conn);
				if(count($result)===0)
					return null;
				else
					return $result;
			}
			else
				return false;
		}
	}
?>
