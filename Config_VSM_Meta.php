<?php
	class Config_VSM_Meta
	{
		//const VSM_API_ROOT = 'http://localhost/VSMAPI_ONLINE/www/';//測試
		const VSM_API_ROOT = 'http://172.17.155.120/';//商用
		//const VSM_API_ROOT_S = 'http://localhost/VSMAPI_ONLINE/www/';//測試
		const VSM_API_ROOT_S = 'http://172.24.252.120/';//商用
		const VSM_AD_FILE = 'api/ams/VSMAdData.php';	
		const VSM_POSITION_FILE = 'api/ams/getVSMPosition.php';	
		const VSM_BARKER_VOD_PLAY_TIME_FILE = 'api/ams/barkerVodPlayTime/getBarkerVodPlayTime.php';	
		//const VSM_AUTOCOMPLETE_LINK_FILE = 'api/ams/autoCompleteForLink.php';	
		const VSM_AUTOCOMPLETE_LINK_FILE = 'backend/linkValueSelector/autoCompleteSearch.php';	
		const VOD_BUNDLE_SELECTOR_AJAX = 'backend/linkValueSelector/ajax_vod_bundle_selector.php';	
		const VAST_SETTING_AJAX = 'api/ams/epgVastBanner/setVastUrlOption.php';	
		
		public static function GET_AD_API($area = "N"){
			if($area==='S'){
				return  self::VSM_API_ROOT_S.self::VSM_AD_FILE;
			}
			else{
				return  self::VSM_API_ROOT.self::VSM_AD_FILE;
			}
			
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
		
		public static function GET_BARKER_VOD_PLAY_TIME_API(){
			$url = self::VSM_API_ROOT.self::VSM_BARKER_VOD_PLAY_TIME_FILE;
			return $url;
		}
		
		public static function GET_SET_VAST_OPTION_API(){
			$url = self::VSM_API_ROOT.self::VAST_SETTING_AJAX;
			return $url;
		}

	}
?>