<?php
	require_once('../tool/auth/authAJAX.php');
	require_once('../Config_VSM_Meta.php');
	


	$bypost=['linkType'=>$_POST["linkType"],'term'=>$_POST["term"]];
	$postvars = http_build_query($bypost,JSON_UNESCAPED_UNICODE);
	if(!$apiResult=connec_to_Api_json(Config_VSM_Meta::GET_AUTOCOMPLETE_API(),'POST',$postvars)){
		$logger->error('無法連VSM API');
		exit(json_encode(array("success"=>false,"message"=>'無法連接VSM託播單API'),JSON_UNESCAPED_UNICODE));	
	}
	exit($apiResult);
		
	
	function connec_to_Api_json($url,$method,$postvars){
		global $logger;
		$postvars = (isset($postvars)) ? $postvars : null;
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		$apiResult = curl_exec($ch);
		//$logger->error('錯誤代號:'.$apiResult .'/無法連接API:'.$url);
		if(curl_errno($ch))
		{
			$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $apiResult;
	}

?>