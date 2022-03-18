<?php
	//用於處理鑽石版位託播單，需備/order/ajaxToAPI.php使用，否則無法運作
	require_once('../tool/auth/authAJAX.php');
	require_once('../Config_VSM_Meta.php');
	define("dianomdOrderAPIUrl",Config::GET_API_SERVER_852().":8080/area/ad");
	//const dianomdOrderAPIUrl="localhost/api/testapi_order.php";
	define("dianomdMaterialAPIUrl",Config::GET_API_SERVER_852().":8080/area/upload");
	//const dianomdMaterialAPIUrl="localhost/api/testapi_material.php";
	//const dianomdMaterialAPIUrl="http://localhost/testing/mutipart.php";
	

	function sendOrder_diamond($orderId){
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
		$materialFilesInfo = get_material_info_by_oid($orderId);		
		//送出素材
		$materialURL = dianomdMaterialAPIUrl."?type=".$bypostOrder['type'];
		$uploadMaterialResult = upload_material_by_from($materialURL,$materialFilesInfo,[]);
		
		//送出素材失敗
		if($action == "add")
			$rcordaction = "insert";
		else if($action == "update")
			$rcordaction = "update";
			
		if(!$uploadMaterialResult["success"]){
			recordResult($rcordaction,0,null,$uploadMaterialResult['message']);
			exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗:'.$uploadMaterialResult['message'],'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		
		//新增
		$postvars = json_encode($bypostOrder,JSON_UNESCAPED_UNICODE);
		if(!$apiResult=connec_to_Api_json(dianomdOrderAPIUrl,'POST',$postvars)){
			recordResult($rcordaction,0,"無法連鑽石版位廣告API",null);
			$logger->error('無法連鑽石版位廣告API:'.dianomdOrderAPIUrl);
			exit(json_encode(array("success"=>false,"message"=>'無法連接鑽石版位廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		if($checkResult['status']=="Success"){
			foreach($checkResult['data'] as $mresult){
				if($mresult["action_status"]!="Success"){
					$feedback=$mresult["error_msg"].":";
					if(isset($mresult["imgBannerSD"]))
						$feedback.="imgBannerSD";
					else if(isset($mresult["imgBannerHD"]))
					$feedback.="imgBannerHD";
					else if(isset($mresult["imgEpgSD"]))
					$feedback.="imgEpgSD";
					else if(isset($mresult["imgEpgHD"]))
					$feedback.="imgEpgHD";
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
	
	function cancelOrder_diamond($orderId){
		global $logger, $my;
		$action = "delete";
		$bypostOrder = get_order_post_data($orderId,$action);		
		
		//取消託播單
		$postvars = json_encode($bypostOrder,JSON_UNESCAPED_UNICODE);
		if(!$apiResult=connec_to_Api_json(dianomdOrderAPIUrl,'POST',$postvars)){
			recordResult($action,0,"無法連鑽石版位廣告API",null);
			$logger->error('無法連鑽石版位廣告API:'.dianomdOrderAPIUrl);
			exit(json_encode(array("success"=>false,"message"=>'無法連接鑽石版位廣告API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$checkResult = json_decode($apiResult,true);
		if($checkResult['status']=="Success"){
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
				"type"=>$orderConfigData['type'],
				"action"=>$action,
				"data"=>array(
					array(
					"ams_id"=> $orderData["託播單識別碼"],
					"name"=> $orderData['託播單名稱'],
					"starttime"=> $orderData['廣告期間開始時間'],
					"endtime"=> $orderData['廣告期間結束時間'],
					"hours"=> $hours,
					"sec"=> $orderConfigData['sec'],
					"imgBannerSD"=> ($materialFilesInfo["imgBannerSD"]=="")?"":$materialFilesInfo["imgBannerSD"]["filename"],
					"imgEpgSD"=> ($materialFilesInfo["imgEpgSD"]=="")?"":$materialFilesInfo["imgEpgSD"]["filename"],
					"imgBannerHD"=> ($materialFilesInfo["imgBannerHD"]=="")?"":$materialFilesInfo["imgBannerHD"]["filename"],
					"imgEpgHD"=> ($materialFilesInfo["imgEpgHD"]=="")?"":$materialFilesInfo["imgEpgHD"]["filename"],
					"status"=> $orderConfigData['status'],
					"uiID"=>  $orderConfigData['uiID'],
					"iapID"=>  $orderConfigData['iapID'],
					"ompID"=>  $orderConfigData['ompID']
					)
				)
			];
		}
		return $bypostOrder;
	}
	
	//取得素材資訊
	function get_material_info_by_oid($orderId){
		global $my;
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
		$materialFilesInfo = 
		[
			"imgBannerSD"=>""//array("filename"=>"testbannerupload.JPG","content"=>"testbanner.JPG")
			,"imgEpgSD"=>""
			,"imgBannerHD"=>""
			,"imgEpgHD"=>""
		];
		//1:imgEpgSD 2:imgEpgHD 3:imgBannerSD 4:imgBannerHD
		$materialdir = Config::GET_MATERIAL_FOLDER();
		foreach($orderMaterials as $row){
			$materialType = end(explode('.',$row['素材原始檔名']));
			$mfileName = '_____AMS_'.$row['素材識別碼'].'.'.$materialType;
			$contentFile = $materialdir.$row['素材識別碼'].'.'.$materialType;
			$mData = ["filename"=>$mfileName,"content"=>$contentFile];
			if($row["素材順序"] == 1){
				$materialFilesInfo["imgEpgSD"] = $mData;
			}else if($row["素材順序"] == 2){
				$materialFilesInfo["imgEpgHD"] = $mData;
			}else if($row["素材順序"] == 3){
				$materialFilesInfo["imgBannerSD"] = $mData;
			}else if($row["素材順序"] == 4){
				$materialFilesInfo["imgBannerHD"] = $mData;
			}
		}
		return $materialFilesInfo;
	}
	
	//上傳素材
	function upload_material_by_from($url,$filenames,$fields){
		global $logger;
		// data fields for POST request
		//$fields = array("f1"=>"value1", "another_field2"=>"anothervalue");
		// files to upload
		/*$filenames = array("imgBannerSD"=>array("filename"=>"testbannerupload.JPG","content"=>"testbanner.JPG")
		,"imgEpgSD"=>""
		,"imgBannerHD"=>""
		,"imgEpgHD"=>""
		);*/
		$files = array();
		foreach ($filenames as $name => $file){
			if($file!="")
				$files[$name] = ["filename"=>$file["filename"],"content"=>file_get_contents($file['content'])];
			else
				$files[$name] = ["filename"=>"","content"=>""];
		}
		$logger->info('鑽石版位素材上傳API:'.json_encode($files));
		// curl
		$curl = curl_init();
		//$url_data = http_build_query($data);
		$boundary = uniqid();
		$delimiter = '-------------' . $boundary;
		$post_data = build_html_form_data_files($boundary, $fields, $files);

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => 1,
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POST => 1,
		  CURLOPT_POSTFIELDS => $post_data,
		  CURLOPT_HTTPHEADER => array(
			//"Authorization: Bearer $TOKEN",
			"Content-Type: multipart/form-data; boundary=" . $delimiter,
			"Content-Length: " . strlen($post_data)

		  ),		  
		));
		//
		$response = curl_exec($curl);
		$info = curl_getinfo($curl);
		$logger->info('鑽石版位素材上傳API回復:'.json_encode($response));
		//echo "code: ${info['http_code']}";
		//print_r($info['request_header']);
		//var_dump($response);
		$err = curl_error($curl);
		curl_close($curl);
		if($err!=""){
			$logger->error('鑽石版位素材上傳API錯誤:'.$url." ".$err);
			return ["success"=>false,"message"=>"素材上傳API連線失敗".$err];
		}
		
		$response_decode = json_decode($response, true);
		if($response_decode["status"]!="Success"){
			return ["success"=>false,"message"=>"素材上傳失敗"];
		}
		
		foreach($response_decode["data"] as $resulData){
			if($resulData["action_status"]!="Success"){
				return ["success"=>false,"message"=>"素材上傳失敗:".$resulData["errormsg"]];
			}
		}
		
		return ["success"=>true];
		
	}
	
	//使用html format傳送素材使用
	function build_html_form_data_files($boundary, $fields, $files){
		$data = '';
		$eol = "\r\n";

		$delimiter = '-------------' . $boundary;

		foreach ($fields as $name => $content) {
			$data .= "--" . $delimiter . $eol
				. 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
				. $content . $eol;
		}


		foreach ($files as $name => $filedata) {
			$data .= "--" . $delimiter . $eol
				. 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $filedata['filename'] . '"' . $eol
				//. 'Content-Type: image/png'.$eol
				. 'Content-Transfer-Encoding: binary'.$eol
				;

			$data .= $eol;
			$data .= $filedata["content"] . $eol;
		}
		$data .= "--" . $delimiter . "--".$eol;


		return $data;
	}
?>