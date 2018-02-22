<?php
	require_once dirname(__FILE__).'/MyLogger.php';
	
	class FTP{
		public function __construct(){
		}
		
		public static function connect($host,$port=21,$timeout=10){
			if(!$ftp_stream=ftp_connect($host,$port,$timeout)){
				(new MyLogger())->error('無法連線到FTP server('.$host.':'.$port.')');
				return false;
			}
			return $ftp_stream;
		}
		
		public static function login($ftp_stream,$username,$password){
			if(!ftp_login($ftp_stream,$username,$password)){
				(new MyLogger())->error('無法登入到FTP server，帳號('.$username.')、密碼('.$password.')。');
				return false;
			}
			return true;
		}
		
		public static function put($host,$username,$password,$local,$remote){
			if(!is_file($local)){
				(new MyLogger())->error('找不到檔案('.$local.')，取消上傳！');
				return false;
			}
			if(!$ftp_stream=self::connect($host))
				return false;
			if(!self::login($ftp_stream,$username,$password))
				return false;
			if(!ftp_pasv($ftp_stream,true))
				return false;
			if(!ftp_put($ftp_stream,$remote,$local,FTP_BINARY)){
				(new MyLogger())->error('無法上傳檔案('.$local.')到FTP server('.$remote.')。');
				return false;
			}
			return true;
		}
		
		public static function putAll($servers,$local,$remote,$processingName = null){
			if($processingName == null)
				$processingName = $remote;
			$result=array();
			foreach($servers as $server){
				if($remote == $processingName)
					$result[] = self::put($server['host'],$server['username'],$server['password'],$local,$remote)?true:false;
				else
					$result[] = self::putAndRename($server['host'],$server['username'],$server['password'],$local,$remote,$processingName)?true:false;
			}
			return $result;
		}
		
		public static function isFile($host,$username,$password,$remote){
			if(!$ftp_stream=self::connect($host))
				return false;
			if(!self::login($ftp_stream,$username,$password))
				return false;
			if(!ftp_pasv($ftp_stream,true))
				return false;
			$result=ftp_nlist($ftp_stream,$remote);
			if(($result!==false)&&(count($result)>=1)&&($result[0]===$remote))
				return true;
			else
				return false;
		}
		
		public static function isAllFile($servers,$remote){
			$result=array();
			foreach($servers as $server)
				$result[]=self::isFile($server['host'],$server['username'],$server['password'],$remote)?true:false;
			return $result;
		}
		
		public static function get($host,$username,$password,$local,$remote){
 			if(!$ftp_stream=self::connect($host))
				return false;
			if(!self::login($ftp_stream,$username,$password))
				return false;
			if(!ftp_pasv($ftp_stream,true))
				return false;
			if(!ftp_get($ftp_stream,$local,$remote,FTP_BINARY)){
				(new MyLogger())->error('無法下載FTP server('.$remote.')檔案到('.$local.')。');
				return false;
			}
			return true;
		}
		
		public static function delete($host,$username,$password,$remote){
 			if(!$ftp_stream=self::connect($host))
				return false;
			if(!self::login($ftp_stream,$username,$password))
				return false;
			if(!ftp_delete($ftp_stream,$remote)){
				(new MyLogger())->error('無法刪除FTP server('.$remote.')檔案。');
				return false;
			}
			return true;
		}
		
		public static function rename($host,$username,$password,$old_file,$new_file){
			if(!$ftp_stream=self::connect($host))
				return false;
			if(!$login_result =self::login($ftp_stream,$username,$password))
				return false;
			if (!@ftp_rename($ftp_stream, $old_file, $new_file)) {
				(new MyLogger())->error("更改檔名($new_file)失敗！");
				return false;
			}
			return true;
		}
		
		public static function putAndRename($host,$username,$password,$local,$remote,$processingName){
			if(!is_file($local)){
				(new MyLogger())->error('找不到檔案('.$local.')，取消上傳！');
				return false;
			}
			if(!$ftp_stream=self::connect($host))
				return false;
			if(!self::login($ftp_stream,$username,$password))
				return false;
			if(!ftp_pasv($ftp_stream,true))
				return false;
			if(!ftp_put($ftp_stream,$processingName,$local,FTP_BINARY)){
				(new MyLogger())->error('無法上傳檔案('.$local.')到FTP server('.$processingName.')。');
				return false;
			}
			if (!@ftp_rename($ftp_stream, $processingName, $remote)) {
				(new MyLogger())->error("更改檔名($new_file)失敗！");
				if(!ftp_delete($ftp_stream,$processingName)){
					(new MyLogger())->error('無法刪除FTP server('.$processingName.')檔案。');
					return false;
				}
				return false;
			}
			return true;
		}
	}
?>