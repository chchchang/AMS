<?php
	class Config_VSM_Meta
	{
		//const VSM_API_ROOT = 'http://172.18.44.3/api/ams/';//測試
		const VSM_API_ROOT = 'http://172.17.155.65/api/ams/';//商用
		const VSM_AD_FILE = 'VSMAdData.php';	
		const VSM_POSITION_FILE = 'getVSMPosition.php';	
		const VSM_AUTOCOMPLETE_LINK_FILE = 'autoCompleteForLink.php';	
		
		public static function GET_AD_API(){
			$url = self::VSM_API_ROOT.self::VSM_AD_FILE;
			return $url;
		}
		
		public static function GET_POSITION_API(){
			$url = self::VSM_API_ROOT.self::VSM_POSITION_FILE;
			return $url;
		}
		
		public static function GET_AUTOCOMPLETE_API(){
			$url = self::VSM_API_ROOT.self::VSM_AUTOCOMPLETE_LINK_FILE;
			return $url;
		}

	}
?>