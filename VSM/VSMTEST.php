<?php
	date_default_timezone_set("Asia/Taipei");
	header("Content-Type:text/html; charset=utf-8");
	//require_once('../Config_VSM_Meta.php');
	//const VSMapiUrl = 'localhost/VSMAPI/VSMAdData.php';
	//const VSMapiUrl = 'localhost/api/ams/VSMAdData.php';
	if($_GET['server']==2)
		//$apiurl='http://172.18.44.3/api/service_category/getServiceCategory_t.php';
		$apiurl='http://172.17.155.65/api/ams/VSMAdData.php';
	else
		$apiurl='http://172.17.155.65/api/service_category/getServiceCategory.php';
		//$apiurl='http://172.17.155.65/api/service_category/getServiceCategory_t.php';

	if(isset($_GET['sercid']))
		$sid = $_GET['sercid'];
	else
		$sid = '1';
	if(isset($_GET['session']))
		$session = $_GET['session'];
	else 
		$session = '';
	//$session = '51a858881dd4c336530863934e2f3eb1b5ad602f08a0125542f67678e2b78502';
	//$session = 'f97e2a63011c3f7274972eb4b7a4de93877a12adbb3245e1d7161f03629c6a1a';
	$bypost=['version'=>'1.3'
	,'service_category_id'=>$sid
	,'session_id'=>$session];
	$postvars = http_build_query($bypost,JSON_UNESCAPED_UNICODE);
	if(!$apiResult=connec_to_Api_json($apiurl,'POST',$postvars)){
		exit(json_encode(array("success"=>false,"message"=>'無法連接VSMAPI'),JSON_UNESCAPED_UNICODE));	
	}
	$checkResult = json_decode($apiResult,true);
	//print_r($checkResult);
	echo $apiResult;
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
