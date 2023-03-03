<?php
	Class curlConnect{
		public static function curl($url,$method,$postvars){
			$postvars = (isset($postvars)) ? $postvars : null;
			// 建立CURL連線
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 500);
			curl_setopt($ch, CURLOPT_PROXY, '');//dev 因OA環境有PROXY才需額外取消proxy設定
			//curl_setopt($ch, CURLOPT_HEADER, true);
			$apiResult = curl_exec($ch);
			if(curl_errno($ch))
			{
				curl_close($ch);
				return array('curlErrorNo'=>curl_errno($ch));
			}
			curl_close($ch);
			return $apiResult;
		}
	}
	
	
	
?>