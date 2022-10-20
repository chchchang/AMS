<?php
	//用於處理運動版位託播單，需備/order/ajaxToAPI.php使用，否則無法運作
	//2022-10-05 世足沿用此外掛，url中tokyo2020改為fifa2022
	require_once('../tool/auth/authAJAX.php');
	require_once('../Config_VSM_Meta.php');
	//define("olympicAPIUrl",Config::GET_API_SERVER_852_OLYMPIC2021().":8080/tokyo2020/api/ad/request");
	define("olympicAPIUrl",Config::GET_API_SERVER_852_OLYMPIC2021().":8080/fifa2022/api/ad/request");//世足將沿用奧運版位，僅更改url參數為即可
	

	function sendOrder_olympic($orderId){
		global $logger, $my;
		//$orderUrl= 'localhost/AMS/test.php';
		//取得託播單資訊
		//取得託播單資訊與對應素材資訊
		$sql='
			SELECT
				託播單送出行為識別碼,
				託播單送出後是否成功
			FROM
				託播單
			WHERE
				託播單.託播單識別碼=?
		';
		$orderData=$my->getResultArray($sql,'i',$orderId)[0];
		//依據前次送出結果決定要用add還是update
		$action = "add";
		if($orderData["託播單送出行為識別碼"] == null){
			$action = "add";
		}
		//前次動作為新增
		else if($orderData["託播單送出行為識別碼"] == 1){
			if($orderData["託播單送出後是否成功"]==1){
				$action = "update";
			}
			else{
				$action = "add";
			}
		}
		//前次動作為修改
		else if($orderData["託播單送出行為識別碼"] == 2){
			$action = "update";
		}
		//前次動作為刪除
		else if($orderData["託播單送出行為識別碼"] == 3){
			$action = "update";
		}
		
		$bypostOrder = get_order_post_data($orderId,$action);		
		//********for test
		//print_r($bypostOrder);
		//exit();
		//********

		if($action == "add")
			$rcordaction = "insert";
		else if($action == "update")
			$rcordaction = "update";
		//新增
		//先用add插入廣告
		$bypostOrder["action"] = "add";
		$logger->info('data to fifa API:'.json_encode($bypostOrder,JSON_UNESCAPED_UNICODE));
		$postvars = json_encode($bypostOrder,JSON_UNESCAPED_UNICODE);
		if(!$apiResult=connec_to_Api_json(olympicAPIUrl,'POST',$postvars)){
			recordResult($rcordaction,0,"無法連接奧運廣告API",null);
			$logger->error('無法連奧運廣告API:'.olympicAPIUrl);
			exit(json_encode(array("success"=>false,"message"=>'無法連接奧運廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$logger->info('result from fifa API:'.$apiResult);
		$checkResult = json_decode($apiResult,true);
		if($checkResult['success']){
			foreach($checkResult['data'] as $mresult){
				if(!$mresult["action_status"]){
					//用add失敗，改用update送出
					$bypostOrder["action"] = "update";
					$postvars = json_encode($bypostOrder,JSON_UNESCAPED_UNICODE);
					if(!$apiResult=connec_to_Api_json(olympicAPIUrl,'POST',$postvars)){
						recordResult($rcordaction,0,"無法連接奧運廣告API",null);
						$logger->error('無法連奧運廣告API:'.olympicAPIUrl);
						exit(json_encode(array("success"=>false,"message"=>'無法連接奧運廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
					}
					$checkResult = json_decode($apiResult,true);
					if($checkResult['success']){
						foreach($checkResult['data'] as $mresult){
							if(!$mresult["action_status"]){
								$feedback=$mresult["errormsg"];
								recordResult($rcordaction,0,null,$feedback);
								exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 '.$feedback,'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
							}
						}
						recordResult($rcordaction,1,null,null);
						changeOrderSate('送出',array($orderId));
					}
				}
			}
			recordResult($rcordaction,1,null,null);
			changeOrderSate('送出',array($orderId));
		}
		else{
			recordResult($rcordaction,0,null,$checkResult['errormsg']);
			exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 '.$checkResult['errormsg'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
	}
	
	function cancelOrder_olympic($orderId){
		global $logger, $my;
		$action = "delete";
		$bypostOrder = get_order_post_data($orderId,$action);		
		
		//取消託播單
		$postvars = json_encode($bypostOrder,JSON_UNESCAPED_UNICODE);
		if(!$apiResult=connec_to_Api_json(olympicAPIUrl,'POST',$postvars)){
			recordResult($action,0,"無法連奧運廣告API",null);
			$logger->error('無法連奧運廣告API:'.olympicAPIUrl);
			exit(json_encode(array("success"=>false,"message"=>'無法連接奧運廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		if($checkResult['success']){
			recordResult($action,1,null,null);
			changeOrderSate('取消送出',array($orderId));
		}
		else{
			recordResult($action,0,null,$checkResult['errormsg']);
			exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['errormsg'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
	}
	
	
	
	function connec_to_Api_json($url,$method,$postvars){
		global $logger;
		//print_r($postvars);
		$postvars = (isset($postvars)) ? $postvars : null;
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Content-Length: ' . strlen($postvars)]);
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
		//print_r($apiResult);
		return $apiResult;
	}
	
	//取得傳輸託播單資訊
	function get_order_post_data($orderId,$action){
		global $logger, $my;
		//$orderUrl= 'localhost/AMS/test.php';
		//取得託播單資訊
		//取得託播單資訊與對應素材資訊
		$sql='
			SELECT
				託播單名稱,
				版位識別碼,
				廣告期間開始時間,
				廣告期間結束時間,
				廣告可被播出小時時段,
				託播單識別碼
			FROM
				託播單
			WHERE
				託播單.託播單識別碼=?
		';
		$orderData=$my->getResultArray($sql,'i',$orderId)[0];
		
		//再取得版位類型、版位、託播單其他參數，並依序被取代。
		$sql='
			SELECT
				版位.版位識別碼,
				版位類型.版位名稱 AS 版位類型名稱,
				版位類型其他參數.版位其他參數名稱 版位類型其他參數名稱,
				版位類型其他參數.版位其他參數預設值 版位類型其他參數預設值,
				版位其他參數.版位其他參數預設值,
				託播單其他參數.託播單其他參數值,
				版位類型其他參數.版位其他參數順序
			FROM
				託播單
				LEFT JOIN 託播單投放版位 ON 託播單投放版位.託播單識別碼=託播單.託播單識別碼 AND 託播單投放版位.ENABLE=1
				LEFT JOIN 版位 ON 版位.版位識別碼=託播單投放版位.版位識別碼
				LEFT JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
				LEFT JOIN 版位其他參數 版位類型其他參數 ON 版位類型其他參數.版位識別碼=版位類型.版位識別碼
				LEFT JOIN 版位其他參數 ON 版位其他參數.版位識別碼=版位.版位識別碼 AND 版位其他參數.版位其他參數順序=版位類型其他參數.版位其他參數順序
				LEFT JOIN 託播單其他參數 ON 託播單其他參數.託播單識別碼=託播單.託播單識別碼 AND 託播單其他參數.託播單其他參數順序=版位類型其他參數.版位其他參數順序
			WHERE
				託播單.託播單識別碼=?
			ORDER BY
				版位.版位識別碼,版位類型其他參數.版位其他參數順序
		';
		$result2=$my->getResultArray($sql,'i',$orderId);
		$orderConfig=array();
		foreach($result2 as $row){
			if(!isset($ptn))
				$ptn = $row['版位類型名稱'];
			$pid = $row['版位識別碼'];
			if(!isset($orderConfig[$pid])){
				$orderConfig[$pid]=[];
			}
			$orderConfig[$pid][$row['版位類型其他參數名稱']]=$row['版位類型其他參數預設值'];
			if($row['版位其他參數預設值']!=null) $orderConfig[$pid][$row['版位類型其他參數名稱']]=$row['版位其他參數預設值'];
			if($row['託播單其他參數值']!=null) $orderConfig[$pid][$row['版位類型其他參數名稱']]=$row['託播單其他參數值'];
		}
		$bypostOrder = [];
		$materialFilesInfo = get_material_info_by_oid($orderId);
		foreach($orderConfig as $pid=>$orderConfigData){
			$hours = explode(",",$orderData['廣告可被播出小時時段']);
			foreach($hours as $kay => $value){
				$hours[$kay] = str_pad($value,2,"0",STR_PAD_LEFT);
			}
			$hours = implode(",",$hours);
			//API基本資訊
			$bypostOrder = [
				"slot"=>$orderConfigData['slot'],
				"action"=>$action,
				"data"=>array(
					array(
					"ams_id"=> $orderData["託播單識別碼"],
					"name"=> $orderData['託播單名稱'],
					"starttime"=> $orderData['廣告期間開始時間'],
					"endtime"=> $orderData['廣告期間結束時間'],
					"hours"=> $hours,
					"sec"=> ((int)$orderConfigData['sec']==0)?null:(int)$orderConfigData['sec'],
					"src"=> $materialFilesInfo["src"],
					"mediaSD"=> $materialFilesInfo["mediaSD"],
					"mediaHD"=> $materialFilesInfo["mediaHD"],
					"uiID"=>  $orderConfigData['uiID'],
					"iapID"=>  $orderConfigData['iapID'],
					"ompID"=>  $orderConfigData['ompID'],
					"status"=>  ($action=="delete")?"F":"T"
					)
				)
			];
		}
		return $bypostOrder;
	}
	
	//取得素材資訊
	function get_material_info_by_oid($orderId){
		global $my,$SERVER_SITE;
		//取得素材資訊
		$sql='
			SELECT
				素材順序,素材名稱,素材原始檔名,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,影片媒體編號
			FROM
				託播單素材
				LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
			WHERE
				託播單素材.託播單識別碼=?
			ORDER BY
				託播單素材.素材順序
		';
		$orderMaterials=$my->getResultArray($sql,'i',$orderId);
		$materialFilesInfo = 
		[
			"src"=>""
			,"mediaSD"=>""
			,"mediaHD"=>""
		];

		foreach($orderMaterials as $row){
			$tmp=explode('.',$row['素材原始檔名']);
			$materialType = end($tmp);
			$contentFile = Config::PROJECT_ROOT.Config::MATERIAL_FOLDER.$row['素材識別碼'].'.'.$materialType;
			$contentFile =str_replace("//","/",$contentFile) ;
			if($row["素材順序"] == 1){
				$materialFilesInfo["src"] = $SERVER_SITE.$contentFile;
			}else if($row["素材順序"] == 2){
				$materialFilesInfo["mediaSD"] = $row['影片媒體編號'];
			}else if($row["素材順序"] == 3){
				$materialFilesInfo["mediaHD"] = $row['影片媒體編號'];
			}
		}
		return $materialFilesInfo;
	}
?>