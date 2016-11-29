<?php
	class Config
	{
		const TEST_MOD=true;
		
		const PROJECT_ROOT='/AMS/';
		
		const DB_HOST='localhost';
		const DB_USER='';
		const DB_PASSWORD='';
		const DB_NAME='';
		
		const OMP_N_ORACLE_DB_USER='';
		const OMP_N_ORACLE_DB_PASSWORD='';
		const OMP_N_ORACLE_DB_CONN_STR='(DESCRIPTION = (ADDRESS_LIST = (LOAD_BALANCE = ON) (ADDRESS = (PROTOCOL = TCP)(HOST = )(PORT = )) (ADDRESS = (PROTOCOL = TCP)(HOST = )(PORT = )) ) (CONNECT_DATA= (SERVER = DEDICATED) (SERVICE_NAME = ) ) )';
		const OMP_C_ORACLE_DB_USER='';
		const OMP_C_ORACLE_DB_PASSWORD='';
		const OMP_C_ORACLE_DB_CONN_STR='';
		const OMP_S_ORACLE_DB_USER='';
		const OMP_S_ORACLE_DB_PASSWORD='';
		const OMP_S_ORACLE_DB_CONN_STR='';
		
		const SECRET_FOLDER='0b7d6e5a265d20715443e19a1f7609c6';
		
		const MIN_PASSWD_LENGTH=8;
		
		public static $FTP_SERVERS=array(
			'OMP_N'=>array(
				
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
			
			),
			'CSMS_C'=>array(
			),
			'CSMS_S'=>array(
			)
		);
		
		public static $SERVER_SITES=array(

			'http://localhost'
		);
		
		public static function GET_API_SERVER_852(){
			$API_SERVER_852 = '';
			$API_SERVER_852_TESTING = '';
			return (self::TEST_MOD)? $API_SERVER_852_TESTING : $API_SERVER_852;
		}
	}
?>