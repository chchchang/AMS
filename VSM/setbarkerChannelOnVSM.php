<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once '../tool/MyLogger.php';
	require_once '../tool/MyDB.php';
	$logger=new MyLogger();
	require_once '../Config_VSM_Meta.php';
	//$apiUrl = 'localhost/VSMAPI/getVSMPosition.php';
	//$apiUrl = 'http://10.144.200.141/api/ams/testapi.php';	
	$apiUrl = Config_VSM_Meta::GET_AD_API();
	//連線DB
	$my=new MyDB(true);
	
	$channelData = getChannelData();
	$postvars = http_build_query(array('action'=>'sendOrder','orderData'=>$channelData),JSON_UNESCAPED_UNICODE);
	// 建立CURL連線
	if(!$apiResult=connec_to_Api($apiUrl,'POST',$postvars)){
		exit(json_encode(array("success"=>false,"message"=>'無法連接VSM廣告API'),JSON_UNESCAPED_UNICODE));	
	}
	$apiResult = json_decode($apiResult);
	print_r($apiResult);
	
	function getChannelData(){
		global $my;
		$sql = '
		SELECT 
		版位.版位名稱,
		版位類型url.版位其他參數預設值 AS url,
		版位url.版位其他參數預設值 AS url1,
		版位mat_type_id.版位其他參數預設值 AS mat_type_id,
		版位srv_category_id.版位其他參數預設值 AS srv_category_id,
		版位group_name.版位其他參數預設值 AS group_name
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 版位類型.版位名稱 = "單一平台barker"
		LEFT JOIN 版位其他參數 版位類型url ON 版位類型.版位識別碼 = 版位類型url.版位識別碼 AND 版位類型url.版位其他參數名稱 = "url"
		LEFT JOIN 版位其他參數 版位url ON 版位.版位識別碼 = 版位url.版位識別碼 AND 版位url.版位其他參數名稱 = "url"
		LEFT JOIN 版位其他參數 版位mat_type_id ON 版位.版位識別碼 = 版位mat_type_id.版位識別碼 AND 版位mat_type_id.版位其他參數名稱 = "mat_type_id"
		LEFT JOIN 版位其他參數 版位srv_category_id ON 版位.版位識別碼 = 版位srv_category_id.版位識別碼 AND 版位srv_category_id.版位其他參數名稱 = "srv_category_id"
		LEFT JOIN 版位其他參數 版位group_name ON 版位.版位識別碼 = 版位group_name.版位識別碼 AND 版位group_name.版位其他參數名稱 = "group_name"
		WHERE 1
		';
		$rowdata = $my->getResultArray($sql);
		$feedback = [];
		foreach($rowdata as $data){
			$feedback[] = [
				"transaction_id"=>'barker_channel_'.$data['srv_category_id'],
				"mat_type_id"=>$data['mat_type_id'],
				"srv_category_id"=>$data['srv_category_id'],
				"group_name"=>$data['group_name'],
				"title"=>$data['版位名稱'],
				"start_datetime"=>'1999-01-01',
				"end_datetime"=>'2999-01-01',
				"hours"=>'0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25',
				"focusImageId"=>"",
				"unfocusImageId"=>"",
				"otherConfig"=>[
					"url"=>($data['url1']==null)?$data['url']:$data['url1'],
					"weight"=>1
				]
			];
		}
		return $feedback;		
	}
	
	//連接API取的結果
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
		if(curl_errno($ch))
		{
			print_r('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $apiResult;
	}
?>