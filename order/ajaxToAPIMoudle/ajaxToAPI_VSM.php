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
			$material_link_value = "";
			
			foreach($orderConfig as $pid=>$orderConfigData){
				if($orderMaterial['點擊後開啟類型'] == "netflixPage"){
					if($orderMaterial['點擊後開啟位址']  == ""){
						$orderMaterial['點擊後開啟位址'] = "com.netflix.ninja:/http://www.netflix.com/browse?iid=".$orderConfigData["specific_iid"];
					}
					else
						$orderMaterial['點擊後開啟位址'] = "com.netflix.ninja:/http://www.netflix.com/watch/".$orderMaterial['點擊後開啟位址'] ."?iid=".$orderConfigData["specific_iid"];
					$orderMaterial['點擊後開啟類型'] = "app";
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
			//2018/11/15 若為banner_full，於banner1 與 banner2位置放入兩張空白託播單，讓前端不顯示
			if($orderConfigData['group_name'] == "banner_full"){
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"]."_emptybanner1",
					"mat_type_id"=>4,
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>"banner_1",
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>
					[
						"focusImageId"=>"",
						"unfocusImageId"=>"",
						"linkType"=>"NONE",
						"link"=>"",
						"linkParameter"=>"",
						"weight"=>$orderConfigData['weight'],
						'material_link'=>"NONE",
						'material_link_value'=>""
					]
				];
				
				$bypostOrder[] = [
					"transaction_id"=>$orderData["託播單識別碼"]."_emptybanner2",
					"mat_type_id"=>4,
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>"banner_2",
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>
					[
						"focusImageId"=>"",
						"unfocusImageId"=>"",
						"linkType"=>"NONE",
						"link"=>"",
						"linkParameter"=>"",
						"weight"=>$orderConfigData['weight'],
						'material_link'=>"NONE",
						'material_link_value'=>""
					]
				];
			}
			
			$action = 'sendOrder';
			precancelOrder_VSM($action,$orderId);
			if(sendOrder_VSM_batch($action,$bypostOrder,$orderId))
				changeOrderSate('送出',array($orderId));
			else
				exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
		}
		
		if($ptn == '單一平台barker_vod'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,影片媒體編號,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,影片畫質識別碼
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterials=$my->getResultArray($sql,'i',$orderId);
			foreach($orderConfig as $pid=>$orderConfigData){
				$Materials = [
					"url"=>"",
					"sdUrl"=>"",
					"adContentId"=>"",
					"sdAdContentId"=>""
				];
				$material_link = "";
				$material_link_value = "";
				foreach($orderMaterials as $orderMaterial){
					
					if($orderMaterial["影片畫質識別碼"] == 2){
						//$Materials["url"]= $orderConfigData['url'].$orderMaterial['影片媒體編號'].'_f';
						$Materials["url"] = getEdgeWareUrl($orderConfigData['url'],$orderMaterial['影片媒體編號']);
						$material_link = $orderMaterial['點擊後開啟類型'];
						$material_link_value = $orderMaterial['點擊後開啟位址'];
						$Materials["adContentId"] = $orderMaterial['影片媒體編號'];
					}
					else if($orderMaterial["影片畫質識別碼"] == 1){
						//$Materials["sdUrl"]= $orderConfigData['url'].$orderMaterial['影片媒體編號'].'_f';
						$Materials["sdUrl"] = getEdgeWareUrl($orderConfigData['url'],$orderMaterial['影片媒體編號']);
						$Materials["sdAdContentId"] = $orderMaterial['影片媒體編號'];
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
						"url"=>$Materials["url"],
						"sdUrl"=>$Materials["sdUrl"],
						"linkType"=>$orderConfigData['linkType'],
						"link"=>$orderConfigData['link'],
						"linkParameter"=>$orderConfigData['linkParameter'],
						"weight"=>$orderConfigData['weight'],
						"bannerTransactionId"=>$orderConfigData['bannerTransactionId'],
						'material_link'=>$material_link,
						'material_link_value'=>$material_link_value,
						'playTimeLimit'=>$orderConfigData['playTimeLimit'],
						'adContentId'=>$Materials["adContentId"],
						'sdAdContentId'=>$Materials["sdAdContentId"]
					]
				];
			}
			$action = 'sendOrder';
			precancelOrder_VSM($action,$orderId);
			if(sendOrder_VSM_batch($action,$bypostOrder,$orderId))
				changeOrderSate('送出',array($orderId));
			else
				exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
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
			$orderMaterialArray=$my->getResultArray($sql,'i',$orderId);
			$orderMaterial=$orderMaterialArray[0];
			$explodedName = explode('.',$orderMaterial['素材原始檔名']);
			$materialType = end($explodedName);
			$materialName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
			$focusMaterialName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
			if(isset($orderMaterialArray[1])){
				$orderMaterialFocus=$orderMaterialArray[1];
				$explodedNameFocus = explode('.',$orderMaterialFocus['素材原始檔名']);
				$materialTypeFocus = end($explodedNameFocus);
				$focusMaterialName = 'ad/_____AMS_'.$orderMaterialFocus['素材識別碼'].'.'.$materialTypeFocus;
			}
			
			$action ='sendEPGOrder';
			precancelOrder_VSM($action,$orderId);
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
						"focusImageId"=>$focusMaterialName,
						"unfocusImageId"=>$materialName,
						"linkType"=>$orderConfigData['linkType'],
						"link"=>$orderConfigData['link'],
						"linkParameter"=>$orderConfigData['linkParameter'],
						'material_link'=>$orderMaterial['點擊後開啟類型'],
						'material_link_value'=>$orderMaterial['點擊後開啟位址'],
						'SpEPG'=>$orderConfigData['SpEPG'],
						'AdTargetListId'=>$orderConfigData['AdTargetListId']
					]
				];
				if(count($bypostOrder)>=20){
					if(!sendOrder_VSM_batch("insertAdTargetRelationOnly",$bypostOrder,$orderId,"S")){
						exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
					}
					if(!sendOrder_VSM_batch($action,$bypostOrder,$orderId)){
						exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
					}
					$bypostOrder = [];
				}
			}
			
			if(count($bypostOrder)!=0){
				if(!sendOrder_VSM_batch("insertAdTargetRelationOnly",$bypostOrder,$orderId,"S")){
					exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
				}
				if(!sendOrder_VSM_batch($action,$bypostOrder,$orderId)){
					exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
				}
			}
			changeOrderSate('送出',array($orderId));
				
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
						"frequence"=>$orderConfigData['frequence'],
						'material_link'=>$orderMaterial['點擊後開啟類型'],
						'material_link_value'=>$orderMaterial['點擊後開啟位址']
					]
				];
			}
			$action ='sendOrder';
			precancelOrder_VSM($action,$orderId);
			if(sendOrder_VSM_batch($action,$bypostOrder,$orderId))
				changeOrderSate('送出',array($orderId));
			else
				exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
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
			$orderMaterials=$my->getResultArray($sql,'i',$orderId);
			$materialName = "";
			$thumbNailName = "";
			$thumbNailContexName="";
			foreach($orderMaterials as $orderMaterial){
				$explodedName = explode('.',$orderMaterial['素材原始檔名']);
				$materialType = end($explodedName);
				if($orderMaterial["素材順序"] == 1){
					$materialName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
				}
				else if($orderMaterial["素材順序"] == 2){
					$thumbNailName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
				}
				else{
					$thumbNailContexName = 'ad/_____AMS_'.$orderMaterial['素材識別碼'].'.'.$materialType;
				}
			}
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
						"weight"=>$orderConfigData['weight'],
						"context"=>$orderConfigData['context'],
						"thumbnailImageId"=>$thumbNailName,
						"thumbnailWithContextImageId"=>$thumbNailContexName
					]
				];
			}
			$action = 'sendOrder';
			precancelOrder_VSM($action,$orderId);
			if(sendOrder_VSM_batch($action,$bypostOrder,$orderId))
				changeOrderSate('送出',array($orderId));
			else
				exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
		}

		if($ptn == '單一平台advertising_page'){
			//取得素材資訊
			$sql='
				SELECT
					素材順序,素材名稱,素材類型識別碼,素材原始檔名,文字素材內容,影片媒體編號,託播單素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,影片畫質識別碼
				FROM
					託播單素材
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
				WHERE
					託播單素材.託播單識別碼=?
				ORDER BY
					託播單素材.素材順序
			';
			$orderMaterial=$my->getResultArray($sql,'i',$orderId);
			foreach($orderConfig as $pid=>$orderConfigData){
				$Materials = [
					"content"=>"",
					"imageId"=>"",
					"vodURL"=>"",
					"sdVodURL"=>"",
					"adContentId"=>"",
					"sdAdContentId"=>""
				];
				$material_link = "";
				$material_link_value = "";
				foreach($orderMaterial as $om){
					
					if($om["素材類型識別碼"]==1){
						$Materials["content"] = $om["文字素材內容"];
						
					}
					if($om["素材類型識別碼"]==2){
						$explodedName = explode('.',$om['素材原始檔名']);
						$materialType = end($explodedName);
						$materialName = 'ad/_____AMS_'.$om['素材識別碼'].'.'.$materialType;
						$Materials["imageId"] = $materialName;
						$material_link = $om["點擊後開啟類型"];
						$material_link_value = $om["點擊後開啟位址"];
						if($material_link == "netflixPage"){
							if($material_link_value == ""){
								$material_link_value = "com.netflix.ninja:/http://www.netflix.com/browse?iid=".$orderConfigData["specific_iid"];
							}
							else
								$material_link_value = "com.netflix.ninja:/http://www.netflix.com/watch/".$material_link_value."?iid=".$orderConfigData["specific_iid"];
							$material_link = "app";
						}
					}
					if($om["素材類型識別碼"]==3){
						if($om["影片畫質識別碼"]==null){
							//沒有設定影片素材
							continue;
						}
						else{
							if($om['影片媒體編號']==""||$om['影片媒體編號']==null){
								exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 '.$om["素材名稱"].'未取得媒體編號','id'=>$orderId),JSON_UNESCAPED_UNICODE));
							}
							//$material_url = $orderConfigData['url'].$om['影片媒體編號'].'_f';
							$material_url = getEdgeWareUrl($orderConfigData['url'],$om['影片媒體編號']);
							if($om["影片畫質識別碼"]==2){
								$Materials["vodURL"] = $material_url;
								$Materials["adContentId"] = $om['影片媒體編號'];
							}
							else if ($om["影片畫質識別碼"]==1){
								$Materials["sdVodURL"] = $material_url;
								$Materials["sdAdContentId"] = $om['影片媒體編號'];
							}
						}
					}
				}
			
				$temp = [
					"transaction_id"=>$orderData["託播單識別碼"],
					"mat_type_id"=>$orderConfigData['mat_type_id'],
					"srv_category_id"=>$orderConfigData['srv_category_id'],
					"group_name"=>$orderConfigData['group_name'],
					"title"=>$orderData['託播單名稱'],
					"start_datetime"=>$orderData['廣告期間開始時間'],
					"end_datetime"=>$orderData['廣告期間結束時間'],
					"hours"=>$orderData['廣告可被播出小時時段'],
					"otherConfig"=>[
						"content"=>$orderConfigData['content'],
						"imageId"=>$Materials['imageId'],
						"vodURL"=>$Materials['vodURL'],
						"sdVodURL"=>$Materials['sdVodURL'],
						"titleColor"=>$orderConfigData['titleColor'],
						"subheader"=>$orderConfigData['subheader'],
						"subheaderColor"=>$orderConfigData['subheaderColor'],
						"isAdult"=>$orderConfigData['isAdult'],
						"weight"=>$orderConfigData['weight'],
						"material_link"=>$material_link,
						//"material_link"=>$orderMaterial['點擊後開啟類型'],
						"material_link_value"=>$material_link_value,
						//"material_link_value"=>$orderMaterial['點擊後開啟位址']
						"adContentId"=>$Materials["adContentId"],
						"sdAdContentId"=>$Materials["sdAdContentId"]
					]
				];
				
				if($orderConfigData['weight']==0){
					$temp["title"]="";
					$temp['otherConfig']["subheader"]="";
				}
				
				$bypostOrder[] = $temp;
			}
			
			$action ='sendOrder';
			precancelOrder_VSM($action,$orderId);
			if(sendOrder_VSM_batch($action,$bypostOrder,$orderId))
				changeOrderSate('送出',array($orderId));
			else
				exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
		}
		//20201110 漂浮廣告
		if($ptn == '單一平台floating_banner'){
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
			
			$material_link_value = "";
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
						"linkType"=>$orderConfigData['linkType'],
						"link"=>$orderConfigData['link'],
						"linkParameter"=>$orderConfigData['linkParameter'],
						"weight"=>$orderConfigData['weight'],
						"keyCode"=>$orderConfigData['keyCode'],
						'material_link'=>$orderMaterial['點擊後開啟類型'],
						'material_link_value'=>$orderMaterial['點擊後開啟位址']
					]
				];
			}			
			$action = 'sendOrder';
			precancelOrder_VSM($action,$orderId);
			if(sendOrder_VSM_batch($action,$bypostOrder,$orderId))
				changeOrderSate('送出',array($orderId));
			else
				exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗 ','id'=>$orderId),JSON_UNESCAPED_UNICODE));
		}
		
		
	
	}

	function precancelOrder_VSM($action,$orderId){
		//送出前先取消現有託播單
		if($action=='sendOrder')
			$checkResult=cancelOrder_VSM_action($orderId);
		else if($action=='sendEPGOrder')
			$checkResult=cancelEPGOrder_VSM_action($orderId);
		if(!$checkResult['success'])
			exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['message'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));
	}

	function sendOrder_VSM_batch($action,$bypostOrder,$orderId,$area="N"){
		global $logger;
		//新增
		$bypost=['action'=>$action,'orderData'=>$bypostOrder];
		$postvars = http_build_query($bypost);
		$logger->info('postvars:'.$postvars);
		$url = Config_VSM_Meta::GET_AD_API($area);
		//$url = Config_VSM_Meta::GET_AD_API();
		$logger->info('嘗試連線VSM API:'.$url);
		if(!$apiResult=connec_to_Api_json($url,'POST',$postvars)){
			$logger->error('無法連VSM API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSM託播單API:'.$url,'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		$logger->info('message:'.$checkResult["message"]);
		if($checkResult['success'])
			return true;
		else
			return false;
	}
	
	function cancelOrder_VSM($orderId){
		global $logger;
		$checkResult = cancelOrder_VSM_action($orderId);
		if($checkResult['success'])
			changeOrderSate('取消送出',array($orderId));
		else
			exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['message'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));
	}
	
	function cancelOrder_VSM_action($orderId){
		global $logger;
		$bypost=['action'=>'cancelOrder','transaction_id'=>$orderId];
		$postvars = http_build_query($bypost);
		if(!$apiResult=connec_to_Api_json(Config_VSM_Meta::GET_AD_API(),'POST',$postvars)){
			$logger->error('無法連VSM API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSM託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		return $checkResult;
	}
	
	function cancelEPGOrder_VSM($orderId){
		global $logger;
		$checkResult = cancelEPGOrder_VSM_action($orderId);
		if($checkResult['success'])
			changeOrderSate('取消送出',array($orderId));
		else
			exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗 '.$checkResult['message'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));
	}
	
	function cancelEPGOrder_VSM_action($orderId){
		global $logger;
		$bypost=['action'=>'cancelEPGOrder','transaction_id'=>$orderId];
		$postvars = http_build_query($bypost);
		if(!$apiResult=connec_to_Api_json(Config_VSM_Meta::GET_AD_API(),'POST',$postvars)){
			$logger->error('無法連VSM API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接VSM託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		return $checkResult;
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
		if(curl_errno($ch))
		{
			$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		$logger->error('apiResult:'.$apiResult);
		return $apiResult;
	}
	
	function replace_new_line_charater($array){
		$result = array();
        foreach( $array as $key => $val ) {
            if( is_array( $val ) ) {
                $result[$key] = replace_new_line_charater( $val );
			}
			else if(is_string ($val)){
				$result[$key] = str_replace("\\n", "\n", $val);
			}
            else {
                $result[$key] = $val;
            }
        }
        return $result;
	}

	function getEdgeWareUrl($relprefix,$contentId){
		$contentIdPre4 = substr($contentId, 0,4);
		$return = $relprefix.$contentIdPre4."_3a__3a_MOV0000000".$contentId."_3a__3a_".$contentId."_f.mpg";
		return $return;
	}
?>