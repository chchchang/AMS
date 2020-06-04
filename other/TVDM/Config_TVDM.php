<?php
	class Config_TVDM
	{
		const OMPPREFIX="http://172.17.156.20/mod/omp/event/TVDM/";
		const IAPHDREFIX="http://172.17.156.20/mod/hd/event/TVDM/";
		const IAPSDREFIX="http://172.17.156.20/mod/sd/event/TVDM/";
		
		//const TVDMAPI='http://172.17.156.9/mod/event/api/tvdm/';//正式
		const TVDMAPI='http://172.17.156.15/mod/event/api/tvdm/';//測試
		
		static protected $_root= null;
		public static function GET_OMP_URL($TVDMID){
			$url = self::$_root.self::OMPPREFIX.$TVDMID;
			return $url;
		}

		public static function GET_IAP_HD_URL($TVDMID){
			$url = self::$_root.self::IAPHDREFIX.$TVDMID;
			return $url;
		}

		public static function GET_IAP_SD_URL($TVDMID){
			$url = self::$_root.self::IAPSDREFIX.$TVDMID;
			return $url;
		}

		//VOD插廣告API
		public static function GET_API_TVDMAPI(){
			$url = self::$_root.self::TVDMAPI;
			return $url;
		}
		
		public static function GET_API_TVDMAPI_SELECT($tvdmid = null){
			$url = self::$_root.self::TVDMAPI."?action=select";
			if($tvdmid != null)
				$url .= "&id=".$tvdmid;
			return $url;
			//return "http://localhost/testing/testing.php";
		}
		
		public static function GET_API_TVDMAPI_UPDATE(){
			$url = self::$_root.self::TVDMAPI."?action=update";
			return $url;
			//return "http://localhost/testing/testing.php";
		}
		
	}
?>