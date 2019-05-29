<?php
ini_set('display_errors','1');
error_reporting(E_ALL);
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	require_once '../Config.php';
	$logger=new MyLogger();
	$my=new MyDB(true);
	$apiurl = Config::GET_API_SERVER_852_VOD_AD()."/mod/ads/api/vod";

	getData($apiurl);

	
	//從API取得資料
	function getData($url){
		// 建立CURL連線
		$bypost = array("ext"=>"FreeMovie","ams_sid"=>2481);
		$postvars = http_build_query($bypost);
		$apiResult=connec_to_Api($url,'POST',$postvars);	
		$apiResult = json_decode($apiResult,true);
		if($apiResult["code"]!=200){
			exit("API取得資料失敗:".$apiResult["status"]);	
		}
		print_r($apiResult);
	}
	
	function connec_to_Api($url,$method,$postvars){
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