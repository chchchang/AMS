<?php
	class Config
	{
		const TEST_MOD=true;
		
		const PROJECT_ROOT='/AMS/';
		
		const DB_HOST='localhost';
		const DB_USER='AMS_Test';
		//const DB_USER='root';
		const DB_PASSWORD='AMS_stage';
		//const DB_PASSWORD='root';
		const DB_NAME='ams_test';
		
		const OMP_N_ORACLE_DB_USER='OMPCHT15';
		const OMP_N_ORACLE_DB_TABLE_OWNER='OMPCHT15';
		const OMP_N_ORACLE_DB_PASSWORD='OMPCHT15';
		const OMP_N_ORACLE_DB_CONN_STR='(DESCRIPTION = (ADDRESS_LIST = (LOAD_BALANCE = ON) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.17.125.1)(PORT = 1521)) (ADDRESS = (PROTOCOL = TCP)(HOST = 172.17.125.3)(PORT = 1521)) ) (CONNECT_DATA= (SERVER = DEDICATED) (SERVICE_NAME = ompdb.mod.cht) ) )';
		const OMP_C_ORACLE_DB_USER='';
		const OMP_C_ORACLE_DB_TABLE_OWNER='OMPCHT15';
		const OMP_C_ORACLE_DB_PASSWORD='';
		const OMP_C_ORACLE_DB_CONN_STR='';
		const OMP_S_ORACLE_DB_USER='';
		const OMP_S_ORACLE_DB_TABLE_OWNER='OMPCHT15';
		const OMP_S_ORACLE_DB_PASSWORD='';
		const OMP_S_ORACLE_DB_CONN_STR='';
		const SECRET_FOLDER='0b7d6e5a265d20715443e19a1f7609c6';
		//const MATERIAL_FOLDER='/opt/lampp/htdocs/AMS/material/uploadedFile/';
		const MATERIAL_FOLDER= '\\material\\uploadedFile\\';
		//const MATERIAL_FOLDER= '/material/uploadedFile/';
		
		const MIN_PASSWD_LENGTH=8;
		
		const PMS_SEARCH_URL = 'http://172.17.251.133/api/getMediaStatus?source=';
		
		public static $FTP_SERVERS=array(
			'OMP_N'=>array(
				array('host'=>'172.17.145.84','username'=>'tluser','password'=>'modtluser','專區banner圖片素材路徑'=>'images/ad/','頻道short EPG banner圖片素材路徑'=>'images/tvspace/sepgad/'),
				array('host'=>'localhost','username'=>'ams','password'=>'','專區banner圖片素材路徑'=>'','頻道short EPG banner圖片素材路徑'=>'')
			),
			'OMP_C'=>array(
			),
			'OMP_S'=>array(
			),
			'PMS'=>array(
			),
			'PMS_TS'=>array(
			),
			'CSMS_N'=>array(
				array('host'=>'172.17.125.47','username'=>'ams','password'=>'testams','待處理資料夾路徑'=>'ams','處理結果資料夾路徑'=>'ams_finish')
			),
			'CSMS_C'=>array(
			),
			'CSMS_S'=>array(
			),
			'CAMPS_MATERIAL'=>array(
				//array('host'=>'172.17.251.86','username'=>'ntempall','password'=>'ntempall','上傳目錄'=>'BARKER_CHANNEL_TEST/','處理完成'=>'BARKER_CHANNEL_TEST/processedDir/','處裡錯誤'=>'BARKER_CHANNEL_TEST/error_file/')
				array('host'=>'localhost','username'=>'chchch','password'=>'chchch','上傳目錄'=>'/','處理完成'=>'BARKER_CHANNEL_TEST/processedDir/','處裡錯誤'=>'BARKER_CHANNEL_TEST/error_file/')
			),
			'VSM'=>array(
				//array('host'=>'10.144.200.141','username'=>'spscc','password'=>'spscc853','圖片素材路徑'=>'www/images/ad/','背景圖素材路徑'=>'www/images/ad/')
				array('host'=>'localhost','username'=>'ams','password'=>'','圖片素材路徑'=>'www/images/ad/','背景圖素材路徑'=>'www/images/ad/')
			),
			'IAB'=>array(
				//array('host'=>'172.17.254.152','username'=>'ams','password'=>'smasmasma@3F','awaiting'=>'upload/SepgSpMD/awaiting/','complete'=>'upload/SepgSpMD/complete/')
				array('host'=>'localhost','username'=>'ams','password'=>'','awaiting'=>'upload/SepgSpMD/awaiting/','complete'=>'upload/SepgSpMD/complete/')
			),
			'IAB_SPEPGMD_MULTI'=>array(
				//array('host'=>'172.17.254.152','username'=>'ams','password'=>'smasmasma@3F','awaiting'=>'upload/SepgSpMD_Multi/awaiting/','complete'=>'upload/SepgSpMD_Multi/complete/')
				array('host'=>'localhost','username'=>'ams','password'=>'','awaiting'=>'upload/SepgSpMD_Multi/awaiting/','complete'=>'upload/SepgSpMD_Multi/complete/')
			)
		);
		
		public static $VSM_DB = array('host'=>'10.144.200.141','username'=>'spscc','password'=>'spscc853','dbname'=>'spscc');
		
		public static $SERVER_SITES=array(
			'http://172.17.236.204',
			'http://10.144.24.224',
			'http://10.144.149.215',
			'http://localhost'
		);
		
		public static function GET_API_SERVER_852(){
			$API_SERVER_852 = 'http://172.18.4.141';
			$API_SERVER_852_TESTING = 'http://172.18.4.135';
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
			return $mf;
		}
		
		public static function GET_MATERIAL_FOLDER_URL($path){
			if (is_null(self::$_root)) self::$_root= dirname(__FILE__);
			$mf = self::$_root.self::MATERIAL_FOLDER;
			$mfu = str_replace($path,"", $mf);
			return $mf;
		}
		
		//VOD插廣告API
		public static function GET_API_SERVER_852_VOD_AD(){
			$API_SERVER_852 = 'http://172.17.156.9:80';
			$API_SERVER_852_TESTING = 'http://172.18.4.141:80';
			return (self::TEST_MOD)? $API_SERVER_852_TESTING : $API_SERVER_852;
		}

		//運動賽事API
		public static function GET_API_SERVER_852_SPORTEVENT(){
			//$sportEvent = "fifa2022";
			$sportEvent = "wbc2023";
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