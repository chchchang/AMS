<?php
	require_once dirname(__FILE__).'/MyLogger.php';
	set_include_path(dirname(__FILE__).'/phpseclib');
	include('Net/SFTP.php');
	define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);
	
	class SFTP{
		public function __construct(){
		}
		
		public static function connect($host,$username,$password){
			$sftp = new Net_SFTP($host);
			if (!$sftp->login($username, $password)) {
				(new MyLogger())->error('無法Sftp連線到FTP server('.$host.')');
				return false;
			}
			return $sftp;
		}
		
		public static function put($host,$username,$password,$local,$remote){
			if(!is_file($local)){
				(new MyLogger())->error('找不到檔案('.$local.')，取消上傳！');
				return false;
			}
			if(!$sftp=self::connect($host,$username,$password))
				return false;
				
			if(!$sftp->put($remote, $local, NET_SFTP_LOCAL_FILE)){
				//無法上傳檔案，嘗試建立資料夾
				$path = explode("/",$remote);
				array_pop($path);
				$path = implode("/",$path);
				(new MyLogger())->info('無法上傳檔案('.$remote.')到FTP server('.$host.')，嘗試建立資料夾('.$path.')');	
				if($sftp->mkdir($path,true)){
					if(!$sftp->put($remote, $local, NET_SFTP_LOCAL_FILE)){
						(new MyLogger())->error('無法上傳檔案('.$remote.')到FTP server('.$host.')');	
						return false;
					}
				}
				else{
					(new MyLogger())->error('無法上傳檔案('.$remote.')到FTP server('.$host.')');
					return false;
				}
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
					//$result[] = self::put($server['host'],$server['username'],$server['password'],$local,$remote,$processingName)?true:false;
			}
			return $result;
		}
		
		public static function isFile($host,$username,$password,$remote){
			if(!$sftp=self::connect($host,$username,$password))
				return false;
			$result=$sftp->stat($remote);
			if(($result!==false)&&(count($result)>=1)&&($result['size']>0))
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
 			if(!$sftp=self::connect($host,$username,$password))
				return false;
			
			if(!$sftp->get($remote, $local)){
				(new MyLogger())->error('無法下載FTP server('.$remote.')檔案到('.$local.')。');
				return false;
			}
			return true;
		}
		
		public static function delete($host,$username,$password,$remote){
 			if(!$sftp=self::connect($host,$username,$password))
				return false;
			if(!$sftp->delete($remote)){
				(new MyLogger())->error('無法刪除SFTP server('.$remote.')檔案。');
				return false;
			}
			return true;
		}
		
		public static function rename($host,$username,$password,$old_file,$new_file){
			if(!$sftp=self::connect($host,$username,$password))
				return false;
			if (!$sftp->rename($old_file,$new_file)) {
				(new MyLogger())->error("更改檔名(".$new_file.")失敗！");
				return false;
			}
			return true;
		}
		
		public static function putAndRename($host,$username,$password,$local,$remote,$processingName){
			if(!is_file($local)){
				(new MyLogger())->error('找不到檔案('.$local.')，取消上傳！');
				return false;
			}
			if(!$sftp=self::connect($host,$username,$password))
				return false;
				
			if(!$sftp->put($processingName, $local, NET_SFTP_LOCAL_FILE)){
				(new MyLogger())->error('無法上傳檔案('.$processingName.')到SFTP server('.$host.')');
				return false;
			}
			//先嘗試刪除要更更改的檔名
			$sftp->delete($remote);
			if (!$sftp->rename($processingName,$remote)) {
				(new MyLogger())->error("更改檔名($remote)失敗！");
				if(!$sftp->delete($processingName)){
					(new MyLogger())->error('無法刪除SFTP server('.$processingName.')檔案。');
					return false;
				}
				return false;
			}
			return true;
		}
		
		//取得檔案最後調整時間
		public static function getFileModifiedTime($host,$username,$password,$remote){
			if(!$sftp=self::connect($host,$username,$password))
				return false;
			return $sftp->filemtime($remote);
		}
	}
?>