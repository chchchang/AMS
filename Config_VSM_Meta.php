<?php
	class Config_VSM_Meta
	{
		const VSM_API_ROOT = 'http://localhost/VSMAPI_ONLINE/www/';//測試
		//const VSM_API_ROOT = 'http://172.17.155.65/';//商用
		const VSM_AD_FILE = 'api/ams/VSMAdData.php';	
		const VSM_POSITION_FILE = 'api/ams/getVSMPosition.php';	
		//const VSM_AUTOCOMPLETE_LINK_FILE = 'api/ams/autoCompleteForLink.php';	
		const VSM_AUTOCOMPLETE_LINK_FILE = 'backend/linkValueSelector/autoCompleteSearch.php';	
		const VOD_BUNDLE_SELECTOR_AJAX = 'backend/linkValueSelector/ajax_vod_bundle_selector.php';	
		
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
		
		public static function GET_VOD_BUNDLE_SELECTOR_AJAX(){
			$url = self::VSM_API_ROOT.self::VOD_BUNDLE_SELECTOR_AJAX;
			return $url;
		}

	}
?>