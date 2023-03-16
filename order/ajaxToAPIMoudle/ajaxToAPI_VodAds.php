<?php
	//用於處理Vod插廣告託播單，需備/order/ajaxToAPI.php使用，否則無法運作
	//ini_set('display_errors','1');
error_reporting(E_ALL);
	define("VodAdsAPIUrl",Config::GET_API_SERVER_852_VOD_AD()."/mod/ads/api/vod/");
	
	

	function sendOrder_VodAds($orderId){
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
		//依據前次送出結果決定要用insert還是update
		$action = "insert";
		if($orderData["託播單送出行為識別碼"] == null){
			$action = "insert";
		}
		//前次動作為新增
		else if($orderData["託播單送出行為識別碼"] == 1){
			if($orderData["託播單送出後是否成功"]==1){
				$action = "update";
			}
			else{
				$action = "insert";
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
		
		$bypostOrder = get_order_post_data($orderId);		
		//新增
			//$apiurl = VodAdsAPIUrl.$action;
			$apiurl = VodAdsAPIUrl."insert";
		foreach($bypostOrder as $index=>$orderData){
			$postvars = http_build_query($orderData,JSON_UNESCAPED_UNICODE);
			//*****************
			//exit(json_encode(array("success"=>false,"message"=>$orderData ,'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			if(!$apiResult=connec_to_Api_json($apiurl,'POST',$postvars)){
				recordResult($action,0,"無法連Vod插廣告廣告API",null);
				$logger->error('無法連接插廣告廣告API:'.VodAdsAPIUrl);
				exit(json_encode(array("success"=>false,"message"=>'無法連接Vod插廣告廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			}
			$checkResult = json_decode($apiResult,true);
			if($checkResult['code']==200){
				/*recordResult($action,1,null,null);
				changeOrderSate('送出',array($orderId));*/
			}
			else if($checkResult['code']==400){
				//收到400，可能是insert資料已存在，改用update
				$secondTryUrl = VodAdsAPIUrl."update";
				if(!$apiResult=connec_to_Api_json($secondTryUrl,'POST',$postvars)){
					recordResult($action,0,"無法連Vod插廣告廣告API",null);
					$logger->error('無法連接插廣告廣告API:'.VodAdsAPIUrl);
					exit(json_encode(array("success"=>false,"message"=>'無法連接Vod插廣告廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
				}
				$checkResult = json_decode($apiResult,true);
				if($checkResult['code']!=200){
					recordResult($action,0,null,$checkResult['status']);
					exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 '.$checkResult['status'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
				}
			}
			else{
				recordResult($action,0,null,$checkResult['status']);
				exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 '.$checkResult['status'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			}
		}
		recordResult($action,1,null,null);
		changeOrderSate('送出',array($orderId));
	}
	
	function cancelOrder_VodAds($orderId){
		global $logger, $my;
		$action = "delete";
		$bypostOrder = get_order_post_data($orderId);	
	
		foreach($bypostOrder as $index=>$orderData){
			$orderData["mark"]="F";
			//利用更新API取消託播單
			$apiurl = VodAdsAPIUrl."update";
			$postvars = http_build_query($orderData);
			if(!$apiResult=connec_to_Api_json($apiurl,'POST',$postvars)){
				recordResult($action,0,"無法連Vod插廣告廣告API",null);
				$logger->error('無法連Vod插廣告廣告API:'.VodAdsAPIUrl);
				exit(json_encode(array("success"=>false,"message"=>'無法連接Vod插廣告廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			}
			$checkResult = json_decode($apiResult,true);
			if($checkResult['code']==200){
				/*recordResult($action,1,null,null);
				changeOrderSate('取消送出',array($orderId));*/
			}
			else if($checkResult['code']==400){
				//收到400，可能是資料不存在，改用insert
				$secondTryUrl = VodAdsAPIUrl."insert";
				if(!$apiResult=connec_to_Api_json($secondTryUrl,'POST',$postvars)){
					recordResult($action,0,"無法連Vod插廣告廣告API",null);
					$logger->error('無法連接插廣告廣告API:'.VodAdsAPIUrl);
					exit(json_encode(array("success"=>false,"message"=>'無法連接Vod插廣告廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
				}
				$checkResult = json_decode($apiResult,true);
				if($checkResult['code']!=200){
					recordResult($action,0,null,$checkResult['status']);
					exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['status'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
				}
			}
			else{
				recordResult($action,0,null,$checkResult['status']);
				exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['status'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			}
		}
		recordResult($action,1,null,null);
		changeOrderSate('取消送出',array($orderId));
	}
	
	
	
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
	
	//取得傳輸託播單資訊
	function get_order_post_data($orderId){
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
			$bypostOrder[] = array(
					"ext"=> $orderConfigData["ext"],
					"ams_vid"=> $orderData["託播單識別碼"],
					"ams_sid"=> $pid,
					"title"=> $orderData["託播單名稱"],
					"starttime"=> $orderData['廣告期間開始時間'],
					"endtime"=> $orderData['廣告期間結束時間'],
					"hours"=> $hours,
					"sec"=> $materialFilesInfo['sec'],
					"mediaSD"=> $materialFilesInfo["mediaSD"],
					"mediaHD"=> $materialFilesInfo["mediaHD"],
					"iapN"=> $materialFilesInfo["iapN"],
					"iapS"=> $materialFilesInfo["iapS"],
					"up"=> $orderConfigData['up'],
					"down"=>  $orderConfigData['down'],
					"sort"=>  $orderConfigData['sort'],
					"mark"=>  "T"
					//"pre"=>  $orderConfigData['pre'],
					//"likes"=>  $orderConfigData['likes']
				);
		}
		return $bypostOrder;
	}
	
	//取得素材資訊
	function get_material_info_by_oid($orderId){
		global $my;
		//取得素材資訊
		$sql='
			SELECT
				素材順序,
				影片素材秒數,
				影片媒體編號,
				影片媒體編號北,
				影片媒體編號南
			FROM
				託播單素材 
				INNER JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
			WHERE
				託播單識別碼=?
			ORDER BY
				託播單素材.素材順序
		';
		$orderMaterials=$my->getResultArray($sql,'i',$orderId);
		$materialFilesInfo = 
		[
			"sec"=>0
			,"mediaSD"=>""
			,"mediaHD"=>""
			,"iapN"=>""
			,"iapS"=>""
		];
		
		$materialdir = Config::GET_MATERIAL_FOLDER();
		foreach($orderMaterials as $row){
			if($row["素材順序"] == 1){
				$materialFilesInfo["sec"] = $row["影片素材秒數"];
				$materialFilesInfo["mediaSD"] = $row["影片媒體編號"];
				$materialFilesInfo["iapN"] = $row["影片媒體編號北"];
				$materialFilesInfo["iapS"] = $row["影片媒體編號南"];
			}else if($row["素材順序"] == 2){
				$materialFilesInfo["sec"] = $row["影片素材秒數"];
				$materialFilesInfo["mediaHD"] = $row["影片媒體編號"];
				$materialFilesInfo["iapN"] = $row["影片媒體編號北"];
				$materialFilesInfo["iapS"] = $row["影片媒體編號南"];
			}
		}
		return $materialFilesInfo;
	}
	
	
?>