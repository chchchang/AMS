<?php
	class Config
	{
		const TEST_MOD=false;
		
		const PROJECT_ROOT='/AMS/';
		
		//const DB_HOST='172.17.236.207';
		const DB_HOST='192.168.116.139';
		const DB_USER='AMS';
		const DB_PASSWORD='AMS_production';
		const DB_NAME='AMS';
		
		const OMP_N_ORACLE_DB_USER='ams';
		const OMP_N_ORACLE_DB_TABLE_OWNER='OMPCHT15';
		const OMP_N_ORACLE_DB_PASSWORD='pro853';
		const OMP_N_ORACLE_DB_CONN_STR='(DESCRIPTION = (ADDRESS_LIST = (LOAD_BALANCE = ON) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.17.189.4)(PORT = 1521)) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.17.189.8)(PORT = 1521)) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.17.189.12)(PORT = 1521)) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.17.189.16)(PORT = 1521)) ) (CONNECT_DATA= (SERVER = DEDICATED) (SERVICE_NAME = ompdb.mod.cht) ) )';
		const OMP_C_ORACLE_DB_USER='ams';
		const OMP_C_ORACLE_DB_TABLE_OWNER='OMPCHTC';
		const OMP_C_ORACLE_DB_PASSWORD='pro853';
		const OMP_C_ORACLE_DB_CONN_STR='(DESCRIPTION = (ADDRESS_LIST = (LOAD_BALANCE = ON) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.20.171.208)(PORT = 1521)) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.20.171.212)(PORT = 1521)) ) (CONNECT_DATA = (SERVICE_NAME = ompdb.modc.cht) ) )';
		const OMP_S_ORACLE_DB_USER='ams';
		const OMP_S_ORACLE_DB_TABLE_OWNER='OMPCHTS';
		const OMP_S_ORACLE_DB_PASSWORD='ams';
		const OMP_S_ORACLE_DB_CONN_STR='(DESCRIPTION = (ADDRESS_LIST = (LOAD_BALANCE = ON) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.27.8.183)(PORT = 1521)) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.27.8.187)(PORT = 1521)) ) (CONNECT_DATA = (SERVICE_NAME = ompdb.mods.cht) ) )';
		
		const SECRET_FOLDER='0b7d6e5a265d20715443e19a1f7609c6';
		//const MATERIAL_FOLDER= '\\material\\uploadedFile\\';
		const MATERIAL_FOLDER= '/material/uploadedFile/';
		
		const MIN_PASSWD_LENGTH=8;
		
		//const PMS_SEARCH_URL = 'http://172.17.251.134/PMS4/pts_media_status.php?v_id=2305&source=';
		const PMS_SEARCH_URL = 'http://172.17.251.133/api/getMediaStatus?source=';
		
		public static $FTP_SERVERS=array(
			'OMP_N'=>array(
				array('host'=>'172.17.189.19','username'=>'ams','password'=>'1qaz2wsx#EDC','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/'),
				array('host'=>'172.17.189.22','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/'),
				array('host'=>'172.17.189.25','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/'),
				array('host'=>'172.17.189.28','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/'),
				array('host'=>'172.17.189.31','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/'),
				array('host'=>'172.17.189.50','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/'),
				array('host'=>'172.17.189.53','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/'),
				array('host'=>'172.17.189.56','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/')
				//array('host'=>'172.17.189.59','username'=>'ams','password'=>'ams@ompftptps2','專區banner圖片素材路徑'=>'ad/','頻道short EPG banner圖片素材路徑'=>'tvspace/sepgad/')
			),
			'OMP_C'=>array(
				array('host'=>'172.20.171.215','username'=>'smsup','password'=>'5rhp90','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/'),
				array('host'=>'172.20.171.218','username'=>'smsup','password'=>'5rhp90','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/'),
				array('host'=>'172.20.171.221','username'=>'smsup','password'=>'5rhp90','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/')
				//array('host'=>'172.20.171.134','username'=>'smsup','password'=>'5rhp90','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/')
			),
			'OMP_S'=>array(
				array('host'=>'172.27.8.173','username'=>'smsftp','password'=>'smsftp','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/'),
				array('host'=>'172.27.8.176','username'=>'smsftp','password'=>'smsftp','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/'),
				array('host'=>'172.27.8.179','username'=>'smsftp','password'=>'smsftp','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/')
				//array('host'=>'172.27.8.134','username'=>'smsftp','password'=>'smsftp','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/')
				//array('host'=>'172.27.8.137','username'=>'smsftp','password'=>'smsftp','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/')
				//FTP伺服器有問題，暫時停止使用
				//array('host'=>'172.27.8.140','username'=>'smsftp','password'=>'smsftp','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/')
			),
			'PMS'=>array(
				array('host'=>'172.17.251.159','username'=>'modams','password'=>'modams')
			),
			'PMS_TS'=>array(
				array('host'=>'172.17.251.159','username'=>'modams','password'=>'modams')
			),
			'CSMS_N'=>array(
				array('host'=>'172.17.236.222','username'=>'ams','password'=>'testams','待處理資料夾路徑'=>'ams','處理結果資料夾路徑'=>'ams_finish')
			),
			'CSMS_C'=>array(
				array('host'=>'172.20.171.185','username'=>'ams','password'=>'testams','待處理資料夾路徑'=>'ams','處理結果資料夾路徑'=>'ams_finish')
			),
			'CSMS_S'=>array(
				array('host'=>'172.27.8.188','username'=>'ams','password'=>'testams','待處理資料夾路徑'=>'ams','處理結果資料夾路徑'=>'ams_finish')
			),
			'CAMPS_MATERIAL'=>array(
				array('host'=>'172.17.251.160','username'=>'pmsbarker','password'=>'pmsbarker','上傳目錄'=>'','處理完成'=>'processedDir/','處理錯誤'=>'errorFile/'),
			),
			'VSM'=>array(
				//array('host'=>'172.17.155.121','username'=>'ams','password'=>'2018AdPic!@#','圖片素材路徑'=>'uploadedFile/ad/'),
				array('host'=>'172.17.155.65','username'=>'ams','password'=>'2018AdPic!@#','圖片素材路徑'=>'uploadedFile/ad/'),
				array('host'=>'172.17.155.90','username'=>'ams','password'=>'2018AdPic!@#','圖片素材路徑'=>'uploadedFile/ad/'),
				array('host'=>'172.17.155.77','username'=>'ams','password'=>'2018AdPic!@#','圖片素材路徑'=>'uploadedFile/ad/')
			),
			'IAB'=>array(
				//array('host'=>'172.17.254.152','username'=>'ams','password'=>'smasmasma@3F','awaiting'=>'upload/SepgSpMD/awaiting/','complete'=>'upload/SepgSpMD/complete/')
				array('host'=>'172.17.254.180','username'=>'ams','password'=>'1234QWERasdf','awaiting'=>'upload/SepgSpMD/awaiting/','complete'=>'upload/SepgSpMD/complete/')
			),
			'IAB_SPEPGMD_MULTI'=>array(
				array('host'=>'172.17.254.180','username'=>'ams','password'=>'1234QWERasdf','awaiting'=>'upload/SepgSpMD_Multi/awaiting/','complete'=>'upload/SepgSpMD_Multi/complete/')
			)
		);
		
		public static $SERVER_SITES=array(
			'http://localhost:20380',
			'http://localhost:30380',
			'http://172.17.236.203',
			'http://10.16.196.73',
			'http://172.17.254.84'
		);
		
		public static function GET_API_SERVER_852(){
			//$API_SERVER_852 = 'http://172.18.4.141';
			$API_SERVER_852 = 'http://172.17.156.9';
			//$API_SERVER_852_TESTING = 'http://172.18.4.135';
			$API_SERVER_852_TESTING = 'http://172.17.156.15';
			return (self::TEST_MOD)? $API_SERVER_852_TESTING : $API_SERVER_852;
		}
		
		public static $CAMPS_API=array(
			'order'=>'http://172.17.251.130:8080/barker/transaction',
			'position'=>'http://172.17.251.130:8080/barker/channel',
			'material'=>'http://172.17.251.130:8080/barker/material',
			'delete_remote_material'=>'http://172.17.251.130:8080/barker/deleteFile?AMS_FILE_ID=',
			"channel"=>'http://172.17.251.130:8080/barker/channel',
			"playlist"=>'http://172.17.251.130:8080/barker/playlist'//可帶參數:?channel_id=47&date=2022-05-05
		);
		
		static protected $_root= null;
		public static function GET_MATERIAL_FOLDER(){
			/*if (is_null(self::$_root)) self::$_root= dirname(__FILE__);
			return self::$_root.self::MATERIAL_FOLDER;*/
			if (is_null(self::$_root)) self::$_root= dirname(__FILE__);
			$mf = self::$_root.self::MATERIAL_FOLDER;
			//$mfu = str_replace($path,"", $mf);
			return $mf;
		}
		
		public static function GET_MATERIAL_FOLDER_URL($path){
			/*$mf = self::GET_MATERIAL_FOLDER();
			$mfu = str_replace($path,"", $mf);
			return $mfu;*/
			if (is_null(self::$_root)) self::$_root= dirname(__FILE__);
			$mf = self::$_root.self::MATERIAL_FOLDER;
			//$mfu = str_replace($path,"", $mf);
			return $mf;
		}
		
		//VOD插廣告API
		public static function GET_API_SERVER_852_VOD_AD(){
			$API_SERVER_852 = 'http://172.17.156.9';
			//$API_SERVER_852 = 'http://172.18.4.141';
			//$API_SERVER_852_TESTING = 'http://172.18.4.141';
			$API_SERVER_852_TESTING = 'http://172.17.156.15';
			return (self::TEST_MOD)? $API_SERVER_852_TESTING : $API_SERVER_852;
		}
		//運動賽事API
		public static function GET_API_SERVER_852_SPORTEVENT(){
			//$sportEvent = "fifa2022";
			//$sportEvent = "wbc2023";
			$sportEvent = "hangzhou2022";
			$API_SERVER_852 = "http://172.17.156.10:8080/$sportEvent/api/ad/request";
			$API_SERVER_852_TESTING = "http://172.17.156.15:8080/$sportEvent/api/ad/request";
			return (self::TEST_MOD)? $API_SERVER_852_TESTING : $API_SERVER_852;
		}
		//20211220在地專區大banner
		public static function GET_API_SERVER_852_LOCALBIGBANNER(){
			$API_SERVER_852 = 'http://172.17.156.9/mod/hd/local/api/ad/';
			$API_SERVER_852_TESTING = 'http://172.17.156.15/mod/hd/local/api/ad/';
			return (self::TEST_MOD)? $API_SERVER_852_TESTING : $API_SERVER_852;
		}
	}
?>
