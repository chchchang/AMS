<?php
	//用於處理CAPMS託播單，需備/order/ajaxToAPI.php使用，否則無法運作
	require_once('../tool/auth/authAJAX.php');
	function sendOrder_CAMPS($orderId){
		global $logger, $my;
		$orderUrl=Config::$CAMPS_API['order'];
		$materialUrl=Config::$CAMPS_API['material'];
		//$orderUrl= 'localhost/AMS/test.php';
		//取得託播單資訊
		//取得託播單資訊與對應素材資訊
		$sql='
			SELECT
				託播單名稱,
				版位識別碼,
				廣告期間開始時間,
				廣告期間結束時間,
				廣告可被播出小時時段
			FROM
				託播單
			WHERE
				託播單.託播單識別碼=?
		';
		$orderData=$my->getResultArray($sql,'i',$orderId)[0];
		//取得素材資訊
		$sql='
			SELECT
				影片素材秒數,素材順序,素材名稱,素材原始檔名,CAMPS影片媒體編號,託播單素材.素材識別碼
			FROM
				託播單素材
				LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
			WHERE
				託播單素材.託播單識別碼=?
			ORDER BY
				託播單素材.素材順序
		';
		$orderMaterial=$my->getResultArray($sql,'i',$orderId)[0];
		//再取得版位類型、版位、託播單其他參數，並依序被取代。
		$sql='
			SELECT
				版位.版位識別碼,
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
			$pid = $row['版位識別碼'];
			if(!isset($orderConfig[$pid])){
				$orderConfig[$pid]=['版位識別碼'=>$pid];
			}
			$orderConfig[$pid][$row['版位類型其他參數名稱']]=$row['版位類型其他參數預設值'];
			if($row['版位其他參數預設值']!=null) $orderConfig[$pid][$row['版位類型其他參數名稱']]=$row['版位其他參數預設值'];
			if($row['託播單其他參數值']!=null) $orderConfig[$pid][$row['版位類型其他參數名稱']]=$row['託播單其他參數值'];
		}
		//整理API用託播單資訊
		$hours = explode(',',$orderData['廣告可被播出小時時段']);
		foreach($hours as $key=>$value){
			$hours[$key] = str_pad($value, 2, "0", STR_PAD_LEFT);
		}
		
		//取得CAMPS端素材名稱
		/*$nameArray = explode('.',$orderMaterial['素材原始檔名']);
		$副檔名= end($nameArray);
		$local=	Config::GET_MATERIAL_FOLDER().$orderMaterial['素材識別碼'].'.'.$副檔名;
			if(($md5_result=md5_file($local))===false){
				$json=array('success'=>false,'error'=>'計算檔案md5值失敗！');
				header('Content-Type: application/json');
				exit(json_encode($json));
		}*/
		//$md5FileName='_____AMS_'.$orderMaterial['素材識別碼'].'_'.$md5_result.'.'.$副檔名;
		
		//取得目前正在投放的版位
		$sql='
			SELECT
				版位識別碼,transaction_id
			FROM
				託播單CAMPS_ID對照表
			WHERE
				託播單識別碼=? AND DISABLE_TIME IS NULL
			ORDER BY
				版位識別碼
		';
		if(!$result=$my->getResultArray($sql,'i',$orderId))
			$result=[];
		$tidTable=[];
		foreach($result as $date){
			$tidTable[$date['版位識別碼']] = ['transaction_id'=>$date['transaction_id'],'enable'=>false];
		}
		
		//逐版位派送到CAMPS
		foreach($orderConfig as $pid=>$orderConfigData){
			$orderByPost = [
				"channel_id"=>$orderConfigData['channel_id'],
				"ad_name"=>$orderData['託播單名稱'],
				//"material_run_time"=>$orderMaterial['影片素材秒數'],
				"hours"=>','.implode(',',$hours).',',
				"start_time"=>$orderData['廣告期間開始時間'],
				"end_time"=>$orderData['廣告期間結束時間'],
				"sale"=>($orderConfigData['sale_order']==1)?true:false,
				//"material_name"=>$remoteFileName,
				//"material_id"=>$orderMaterial['CAMPS影片媒體編號'],
				"deleted"=>false
			];
			
			if(!($orderMaterial==null || count($orderMaterial)==0)){
				if(!$materialApi=connec_to_Api($materialUrl.'?material_id='.$orderMaterial['CAMPS影片媒體編號'],'GET',null)){
					$logger->error('無法連CAMPS API');
					exit(json_encode(array("success"=>false,"message"=>'無法連接CAMPS查詢託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
				}
				$orderByPost['material_run_time']=$orderMaterial['影片素材秒數'];
				$orderByPost['material_id']=$orderMaterial['CAMPS影片媒體編號'];
				$materialApi = json_decode($materialApi,true)[0];
				$remoteFileName=$materialApi['camps_rename'];
				$orderByPost['material_name']=$remoteFileName;
			}
			else{
				//將託播單標記為需重新派送
				$sql='
					UPDATE 託播單 
					SET 託播單.託播單需重新派送 = 1
					WHERE 託播單識別碼=?
				';
				$my->execute($sql,'i',$orderId);
			}
			
			//查詢是否已新增過
			if(isset($tidTable[$orderConfigData['版位識別碼']])){
				//已新增過 PUT託播單資訊
				$tid = $tidTable[$orderConfigData['版位識別碼']]['transaction_id'];
				$action = 'update';
				//取得CAMPS託播單資訊
				$campsOrderData = getTransactionFromCampsByTid($tid);
				if($campsOrderData['material_name']!=$orderByPost['material_name']&&($campsOrderData['material_id']!=null || $campsOrderData['material_id']!='')){
					$copyOldOrder = true;					
					//設定現有的transaction要重新送出素材到ORBIT
					$orderByPost['reset_status']=true;
				}
				$ApiMethod = 'PUT';
				$orderByPost['transaction_id']=$tid;
				$tidTable[$orderConfigData['版位識別碼']]['enable'] = true;
			}
			else{
				//尚未新增過，POST託播單資訊
				$ApiMethod = 'POST';
				$action = 'insert';
			}
			
			//傳送託播單資訊
			$postvars = json_encode($orderByPost,JSON_UNESCAPED_UNICODE);
			if(!$apiResult=connec_to_Api_json($orderUrl,$ApiMethod,$postvars)){
				$logger->error('無法連CAMPS API');
				exit(json_encode(array("success"=>false,"message"=>'無法連接CAMPS送出託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			}
			$checkResult = json_decode($apiResult,true);
			//檢查是否送出成功，並更新資料庫
			if(isset($checkResult['transaction_id'])){
				if($ApiMethod == 'POST'){				
					$sql = 'INSERT INTO 託播單CAMPS_ID對照表 (託播單識別碼,版位識別碼,transaction_id,CREATED_PEOPLE)
					VALUES (?,?,?,?)';
					$my->execute($sql,'iiii',$orderId,$orderConfigData['版位識別碼'],$checkResult['transaction_id'],$_SESSION['AMS']['使用者識別碼']);
				}
				//送出成功
				if(isset($copyOldOrder)&&$copyOldOrder){
					//素材不同，改用使用舊的素材新增一筆下架狀態的transaction、並將現有transaction替換素材後重新上架
					//新增一筆使用舊素材的transaction,狀態設為下架
					$campsOrderData['start_time'] = str_replace('.000Z','',$campsOrderData['start_time']);
					$campsOrderData['start_time'] = str_replace('T',' ',$campsOrderData['start_time']);
					$campsOrderData['end_time'] = str_replace('.000Z','',$campsOrderData['end_time']);
					$campsOrderData['end_time'] = str_replace('T',' ',$campsOrderData['end_time']);
					$orderByPost_new = [
						"channel_id"=>$orderConfigData['channel_id'],
						"ad_name"=>$campsOrderData['ad_name'],
						"material_run_time"=>$campsOrderData['material_run_time'],
						"hours"=>$campsOrderData['hours'],
						"start_time"=>$campsOrderData['start_time'],
						"end_time"=>$campsOrderData['end_time'],
						"sale"=>$campsOrderData['sale'],
						"material_name"=>$campsOrderData['material_name'],
						"material_id"=>$campsOrderData['material_id'],
						"deleted"=>true
					];
					$postvars_new = json_encode($orderByPost_new,JSON_UNESCAPED_UNICODE);
					if(!$apiResult=connec_to_Api_json($orderUrl,'POST',$postvars_new)){
						$logger->error('無法連CAMPS API');
						exit(json_encode(array("success"=>false,"message"=>'無法連接CAMPS送出託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
					}
					$checkResult_new = json_decode($apiResult,true);
					if(!isset($checkResult_new['transaction_id'])){
						$logger->error('託播單識別碼'.$orderId.'送出失敗:'.$apiResult);
						//送出失敗
						$success = false;
						$result_inner = $apiResult;
						$result_outter = null;
						break;
					}
					$sql = 'INSERT INTO 託播單CAMPS_ID對照表 (託播單識別碼,版位識別碼,transaction_id,CREATED_PEOPLE,DISABLE_TIME)
					VALUES (?,?,?,?,CURRENT_TIMESTAMP)';
					$my->execute($sql,'iiii',$orderId,$orderConfigData['版位識別碼'],$checkResult_new['transaction_id'],$_SESSION['AMS']['使用者識別碼']);
				}
				
				$success = true;
				$result_inner = null;
				$result_outter = null;
			}
			else{
				//送出失敗
				$logger->error('託播單識別碼'.$orderId.'送出失敗:'.$apiResult);
				$success = false;
				$result_inner = $apiResult;
				$result_outter = null;
				break;
			}
		}//end of foreach($orderConfig as $pid=>$orderConfigData)
		//更新資料庫
		updateTidTable($tidTable);
		recordResult($action,$success,$result_inner,$result_outter);
		if($success)
			changeOrderSate('送出',[$orderId]);
		else
			exit(json_encode(array("success"=>false,"message"=>'託播單送出失敗','id'=>$orderId),JSON_UNESCAPED_UNICODE));
	}
	
	function cancelOrder_CAMPS($orderId){
		global $logger, $my;
		$orderUrl=Config::$CAMPS_API['order'];
		//取得transaction_id
		$sql = 'SELECT transaction_id
			FROM 託播單CAMPS_ID對照表
			WHERE
				託播單識別碼 = ? AND DISABLE_TIME IS NULL
		';
		$tids = $my->getResultArray($sql,'i',$orderId);
		//利用transaction_id取得CAMPS端的資訊
		foreach($tids as $data){
			$tid = $data['transaction_id'];
			$campsOrderData = getTransactionFromCampsByTid($tid);
			//將deleted改為true後送出
			$campsOrderData['deleted']= true;
			if($campsOrderData['transaction_id']!=$tid)
				exit(json_encode(array("success"=>false,"message"=>'API查詢託播單資訊錯誤'.json_encode($campsOrderData,JSON_UNESCAPED_UNICODE),'id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			
			$postvars = json_encode($campsOrderData,JSON_UNESCAPED_UNICODE);
			if(!$apiResult=connec_to_Api_json($orderUrl,'PUT',$postvars)){
				$logger->error('無法連CAMPS API');
				exit(json_encode(array("success"=>false,"message"=>'無法連接CAMPS修改託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
			}
			$checkResult = json_decode($apiResult,true);
			//檢查是否取消送出成功，並更新資料庫
			if($checkResult['deleted']==true){
				//取消送出成功
				$success = true;
				$result_inner = null;
				$result_outter = null;
			}
			else{
				$logger->error('託播單識別碼'.$orderId.'取消送出失敗:'.$apiResult);
				$success = false;
				$result_inner = $checkResult['messege'];
				$result_outter = null;
				break;
			}
		}
		//更新資料庫
		recordResult('delete',$success,$result_inner,$result_outter);
		if($success){
			changeOrderSate('取消送出',array($orderId));
		}
		else
			exit(json_encode(array("success"=>false,"message"=>'託播單取消送出失敗'.$apiResult,'id'=>$orderId),JSON_UNESCAPED_UNICODE));
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
		$apiResult = curl_exec($ch);
		if(curl_errno($ch))
		{
			$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $apiResult;
	}
	
	function getTransactionFromCampsByTid($tid){
		$orderUrl=Config::$CAMPS_API['order'];
		if(!$apiResult=connec_to_Api($orderUrl.'?transaction_id='.$tid,'GET',null)){
			$logger->error('無法連CAMPS API');
			exit(json_encode(array("success"=>false,"message"=>'無法連接CAMPS查詢託播單API','id'=>$orderId),JSON_UNESCAPED_UNICODE));	
		}
		$orderData = json_decode($apiResult,true)[0];
		return $orderData;
	}
	
	function updateTidTable($tidTable){
		global $logger, $my;
		foreach($tidTable as $pid=>$data){
			if(!$data['enable']){
				$sql = 'UPDATE 託播單CAMPS_ID對照表 SET DISABLE_TIME = CURRENT_TIMESTAMP, LAST_UPDATED_PEOPLE=?, LAST_UPDATED_TIME=CURRENT_TIMESTAMP WHERE transaction_id=?';
				$my->execute($sql,'ii',$_SESSION['AMS']['使用者識別碼'],$data['transaction_id']);
			}
			else{
				$sql = 'UPDATE 託播單CAMPS_ID對照表 SET LAST_UPDATED_PEOPLE=?, LAST_UPDATED_TIME=CURRENT_TIMESTAMP WHERE transaction_id=?';
				$my->execute($sql,'ii',$_SESSION['AMS']['使用者識別碼'],$data['transaction_id']);
			}
		}
	}
?>