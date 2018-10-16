<?php
	//用於處理CAPMS託播單，需備/order/ajaxToAPI.php使用，否則無法運作
	require_once('../tool/auth/authAJAX.php');
	require_once('../Config_VSM_Meta.php');
	//const VSMapiUrl = 'localhost/VSMAPI/VSMAdData.php';
	//const VSMapiUrl = 'localhost/api/ams/VSMAdData.php';

	function sendOrder_VSM($orderId){
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
		//逐版位派送
		if($ptn == '單一平台banner'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,素材名稱,素材原始檔名,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterial=$my->getResultArray($sql,'i',$orderId)[0];
			$fileNamePatterns= explode('.',$orderMaterial['素材原始檔名']);
			$materialType = end($fileNamePatterns);
			$materialName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
			foreach($orderConfig as $pid=>$orderConfigData){
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"],
					"mat_type_id"=>$orderConfigData['mat_type_id'],
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>$orderConfigData['group_name'],
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>[
						"focusImageId"=>$materialName,
						"unfocusImageId"=>$materialName,
						"linkType"=>$orderConfigData['linkType'],
						"link"=>$orderConfigData['link'],
						"linkParameter"=>$orderConfigData['linkParameter'],
						"weight"=>$orderConfigData['weight'],
						'material_link'=>$orderMaterial['點擊後開啟類型'],
						'material_link_value'=>$orderMaterial['點擊後開啟位址']
					]
				];
			}
			$action = 'sendOrder';
		}
		
		if($ptn == '單一平台barker_vod'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,影片媒體編號,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterial=$my->getResultArray($sql,'i',$orderId)[0];
			foreach($orderConfig as $pid=>$orderConfigData){
				$material_url = $orderConfigData['url'].$orderMaterial['影片媒體編號'].'_f';
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"],
					"mat_type_id"=>$orderConfigData['mat_type_id'],
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>$orderConfigData['group_name'],
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>[
						"url"=>$material_url,
						"linkType"=>$orderConfigData['linkType'],
						"link"=>$orderConfigData['link'],
						"linkParameter"=>$orderConfigData['linkParameter'],
						"weight"=>$orderConfigData['weight'],
						"bannerTransactionId"=>$orderConfigData['bannerTransactionId'],
						'material_link'=>$orderMaterial['點擊後開啟類型'],
						'material_link_value'=>$orderMaterial['點擊後開啟位址']
					]
				];
			}
			$action = 'sendOrder';
		}
		
		if($ptn == '單一平台EPG'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,素材名稱,素材原始檔名,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterial=$my->getResultArray($sql,'i',$orderId)[0];
			$materialType = end(explode('.',$orderMaterial['素材原始檔名']));
			$materialName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
			foreach($orderConfig as $pid=>$orderConfigData){
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"],
					"content_id"=>$orderConfigData["content_id"],
					"title"=>$orderData['託播單名稱'],
					"defaultFlag"=>$orderConfigData['sepgDefaultFlag'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>[
						"focusImageId"=>$materialName,
						"unfocusImageId"=>$materialName,
						"linkType"=>$orderConfigData['linkType'],
						"link"=>$orderConfigData['link'],
						"linkParameter"=>$orderConfigData['linkParameter'],
						'material_link'=>$orderMaterial['點擊後開啟類型'],
						'material_link_value'=>$orderMaterial['點擊後開啟位址']
					]
				];
			}
			$action ='sendEPGOrder';
		}
		
		if($ptn == '單一平台marquee'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,素材名稱,文字素材內容,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterial=$my->getResultArray($sql,'i',$orderId)[0];
			foreach($orderConfig as $pid=>$orderConfigData){
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"],
					"mat_type_id"=>$orderConfigData['mat_type_id'],
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>$orderConfigData['group_name'],
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>[
						"content"=>$orderMaterial['文字素材內容'],
						"linkType"=>$orderConfigData['linkType'],
						"link"=>$orderConfigData['link'],
						"linkParameter"=>$orderConfigData['linkParameter'],
						"weight"=>$orderConfigData['weight'],
						'material_link'=>$orderMaterial['點擊後開啟類型'],
						'material_link_value'=>$orderMaterial['點擊後開啟位址']
					]
				];
			}
			$action ='sendOrder';
		}
		if($ptn == '單一平台background_banner'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,素材名稱,素材原始檔名,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterial=$my->getResultArray($sql,'i',$orderId)[0];
			$materialType = end(explode('.',$orderMaterial['素材原始檔名']));
			$materialName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
			foreach($orderConfig as $pid=>$orderConfigData){
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"],
					"mat_type_id"=>$orderConfigData['mat_type_id'],
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>$orderConfigData['group_name'],
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>[
						"imageId"=>$materialName,
						"weight"=>$orderConfigData['weight']
					]
				];
			}
			$action = 'sendOrder';
		}
		
		if($ptn == '單一平台advertising_page'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,素材名稱,素材原始檔名,文字素材內容,影片媒體編號,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterial=$my->getResultArray($sql,'i',$orderId)[0];
			foreach($orderConfig as $pid=>$orderConfigData){
				$Materials = [
					"content"=>"",
					"imageId"=>"",
					"vodURL"=>""
				];
				$material_link = "";
				$material_link_value = "";
				foreach($orderMaterial as $om){
					if($om["素材順序"]==1){
						$Materials["content"] = $om["文字素材內容"];
						$material_link = $om["點擊後開啟類型"];
						$material_link_value = $om["點擊後開啟位址"];
					}
					if($om["素材順序"]==2){
						$materialType = end(explode('.',$om['素材原始檔名']));
						$materialName = 'ad/_____AMS_'.$om['素材識別碼'].'.'.$materialType;
						$Materials["imageId"] = $materialName;
					}
					if($om["素材順序"]==3){
						$material_url = $orderConfigData['url'].$om['影片媒體編號'].'_f';
						$Materials["vodURL"] = $om["文字素材內容"];
					}
				}
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"],
					"mat_type_id"=>$orderConfigData['mat_type_id'],
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>$orderConfigData['group_name'],
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>[
						"content"=>$Materials['content'],
						"imageId"=>$Materials['imageId'],
						"vodURL"=>$Materials['vodURL'],
						"titleColor"=>$orderConfigData['titleColor'],
						"subheader"=>$orderConfigData['subheader'],
						"subheaderColor"=>$orderConfigData['subheaderColor'],
						"isAdult"=>$orderConfigData['isAdult'],
						"weight"=>"1",
						"material_link"=>$material_link,
						//"material_link"=>$orderMaterial['點擊後開啟類型'],
						"material_link_value"=>$material_link_value
						//"material_link_value"=>$orderMaterial['點擊後開啟位址']
					]
				];
			}
			
			$action ='sendOrder';
		}
		
		//新增
		$bypost=['action'=>$action,'orderData'=>$bypostOrder];
		$postvars = http_build_query($bypost,JSON_UNESCAPED_UNICODE);
		if(!$apiResult=connec_to_Api_json(Config_VSM_Meta::GET_AD_API(),'POST',$postvars)){
			$logger->error('無法連VSM API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSM託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		if($checkResult['success'])
			changeOrderSate('送出',array($orderId));
		else
			exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 '.$checkResult['message'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
	}
	
	function cancelOrder_VSM($orderId){
		global $logger, $my;
		$bypost=['action'=>'cancelOrder','transaction_id'=>$orderId];
		$postvars = http_build_query($bypost,JSON_UNESCAPED_UNICODE);
		if(!$apiResult=connec_to_Api_json(Config_VSM_Meta::GET_AD_API(),'POST',$postvars)){
			$logger->error('無法連VSM API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSM託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		if($checkResult['success'])
			changeOrderSate('取消送出',array($orderId));
		else
			exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['message'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));
	}
	
	function cancelEPGOrder_VSM($orderId){
		global $logger, $my;
		$bypost=['action'=>'cancelEPGOrder','transaction_id'=>$orderId];
		$postvars = http_build_query($bypost,JSON_UNESCAPED_UNICODE);
		if(!$apiResult=connec_to_Api_json(Config_VSM_Meta::GET_AD_API(),'POST',$postvars)){
			$logger->error('無法連VSM API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSM託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		if($checkResult['success'])
			changeOrderSate('取消送出',array($orderId));
		else
			exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['message'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));
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
?>