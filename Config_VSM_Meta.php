<?php
	class Config_VSM_Meta
	{
		const VSM_API_ROOT = 'http://10.144.200.141/api/ams/';
		const VSM_AD_FILE = 'VSMAdData.php';	
		const VSM_POSITION_FILE = 'getVSMPosition.php';	
		
		public static function GET_AD_API(){
			$url = self::VSM_API_ROOT.self::VSM_AD_FILE;
			return $url;
		}
		
		public static function GET_POSITION_API(){
			$url = self::VSM_API_ROOT.self::VSM_POSITION_FILE;
			return $url;
		}

	}
?>