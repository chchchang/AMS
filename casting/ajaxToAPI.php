<?php
	include('../tool/auth/authAJAX.php');
	require_once('../Config_VSM_Meta.php');
	//前置設定

	
	$API852Url=Config::GET_API_SERVER_852();
	//判斷api與動作
	if( isset($_POST['action']) && $_POST['action'] != '' ){
		switch($_POST['action']){
			case "852取得排程表":
				getSchedule_852();
				break;
			case "851取得排程表":
				getSchedule_851();
				break;
			case "單一平台barker_vod":
				getSchedule_VSM_barker();
				break;
		}
	}
	
	//連接API取的結果
	function connec_to_Api($url,$postvars){
		global $logger;
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
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
	
	
	function getSchedule_852(){
		global $logger, $my, $API852Url;
		//取得版位的ext設訂
		//版位參數
		$sql = "SELECT 版位其他參數名稱,版位其他參數預設值 
		FROM 版位其他參數,版位
		WHERE 版位.版位識別碼=? AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 是否版位專用 = 1";
		$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']);
		foreach($res as $row){
			$options[$row['版位其他參數名稱']]=$row['版位其他參數預設值'];
		}
		$sql = "SELECT 版位其他參數名稱,版位其他參數預設值 
		FROM 版位其他參數
		WHERE 版位識別碼=? AND 是否版位專用 = 1";
		if(!$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']))$res = [];
		foreach($res as $row){
			$options[$row['版位其他參數名稱']]=$row['版位其他參數預設值'];
		}
		
		$url = $API852Url.'/mod/AD/api/vod';
		$byPost=array('ext'=>$options['ext'],'ams_sid'=>$_POST['版位識別碼'],'start'=>$_POST['date'].' 00:00:00','end'=>$_POST['date'].' 23:59:59');
		$postvars = http_build_query($byPost);
		// 建立CURL連線
		if(!$apiResult=connec_to_Api($url,$postvars)){
			$logger->error('無法連接前置廣告投放系統API');
			exit(json_encode(array("Error"=>'無法連接前置廣告投放系統'),JSON_UNESCAPED_UNICODE));	
		}
		
		$result = json_decode($apiResult, true);
		//exit(json_encode(array('bypost'=>$byPost,'result'=>$apiResult,JSON_UNESCAPED_UNICODE)));
		if($result['code']!=200){
			$logger->error('前置廣告投放系統API錯誤:'.$apiResult);
			exit(json_encode(array('Error'=>'API錯誤'.$result['status']),JSON_UNESCAPED_UNICODE));
		}
		//產生排程顯示用資料
		$feedback=array();
		//$vods = getAllGroupedOrderInDays($result['vod'],$byPost);
		foreach($result['vod'] as $vod){
			if($vod["mark"]!=0){
				//$playTimeSum = getPlayTimesInSameGroup($vod['ams_vid'],$vods);
				//$playTimeSum = ($playTimeSum==0)?(intval($vod['back'])+intval($vod['finish'])):$playTimeSum;
				$playTimeSum = (intval($vod['back'])+intval($vod['finish']));
				$tempArry=array();//產生timetable用
				$tempArry['託播單代碼'] = $vod['ams_vid'];
				$tempArry['upTitle'] = '['.$vod['title'].']  ['.$vod['starttime'].'~'.$vod['endtime'].'] 沒看完次數:'.$vod['back'].' 看完次數:'.$vod['finish'].' 已播放'.(intval($vod['back'])+intval($vod['finish']))
										.' 訊號中斷:'.$vod['lose'].' 沒看到:'.$vod['error'].' 按讚次數:'.$vod['likes'].' 排序'.$vod['sort']
										.' 總次數:'.$playTimeSum
										;
				$sTime = explode(' ',$vod['starttime']);
				$eTime = explode(' ',$vod['endtime']);
				//當日大於開始日期
				if($_POST['date']>$sTime[0]){
					//當日在開始結束日期內
					if($_POST['date']<$eTime[0]){
						$tempArry['hours'] = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
						$tempArry['startTime'] = '00:00:00';
						$tempArry['endTime'] = '23:59:59';
						array_push($feedback,$tempArry);
					}//結束日期等於當日
					else if($_POST['date']==$eTime[0]&&$eTime[1]!='00:00:00'){
						$time=explode(":",$eTime[1]);
						$hours=array();
						for($i=0;$i<intval($time[0]);$i++){
							$hours[]=$i;
						}
						if($time[1]!='00'&&$time[2]!='00')
							$hours[]=intval($time[0]);
						$tempArry['hours'] = $hours;
						$tempArry['startTime'] = '00:00:00';
						$tempArry['endTime'] = $eTime[1];
						array_push($feedback,$tempArry);
					}
				}
				//當日等於開始日期
				else if($_POST['date']==$sTime[0]){
					//結束日大等於當日日
					if($eTime[0]>$_POST['date']){
						$time=explode(":",$sTime[1]);
						$hours=array();
						for($i=intval($time[0]);$i<24;$i++){
							$hours[]=$i;
						}
						$tempArry['hours'] = $hours;
						$tempArry['startTime'] = $sTime[1];
						$tempArry['endTime'] = '23:59:59';
						array_push($feedback,$tempArry);
					}
					//結束日期與開始日期都等於當日
					else if($eTime[0]=$_POST['date']){
						$time=explode(":",$sTime[1]);
						$time2=explode(":",$eTime[1]);
						$hours=array();
						for($i=intval($time[0]);$i<intval($time2[0]);$i++){
							$hours[]=$i;
						}
						if($time2[1]!='00'&&$time2[2]!='00')
							$hours[]=intval($time2[0]);
						$tempArry['hours'] = $hours;
						$tempArry['startTime'] = $sTime[1];
						$tempArry['endTime'] = $eTime[1];
						array_push($feedback,$tempArry);
					}
				}
				
			}
		}

		exit (json_encode($feedback,JSON_UNESCAPED_UNICODE));
	}
	
	function getAllGroupedOrderInDays($vods,$byPost){
		global $logger, $my, $API852Url;
		$url = $API852Url.'/mod/AD/api/vod';

		//取得同群組得託播單識別碼
		$sql = "SELECT MIN(B.廣告期間開始時間) AS ST, MAX(B.廣告期間結束時間) AS ED
		FROM 託播單 A, 託播單 B
		WHERE B.託播單群組識別碼 = A.託播單群組識別碼 ";
		
		$a_params = array();
		$n = count($vods);
		$arrayTemp = array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= 'A.託播單識別碼=?';
		}	
		$arrayTemp=implode(" OR ", $arrayTemp);
		if($arrayTemp!='')
		$sql.=' AND('.$arrayTemp.')';
		$param_type = '';
		for($i = 0; $i < $n; $i++) {
			$param_type .='i';
		}	 
	
		$a_params[] = &$param_type;
		for($i = 0; $i < $n; $i++) {
			$a_params[] = &$_POST['orderIds'][$i];
		}
		
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		 
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_assoc();
		
		$byPost['start']=$$row['ST'];
		$byPost['end']=$row['ED'];
		$postvars = http_build_query($byPost);

		// 建立CURL連線
		if(!$apiResult=connec_to_Api($url,$postvars)){
			$logger->error('無法連接前置廣告投放系統API');
			exit(json_encode(array("Error"=>'無法連接前置廣告投放系統API'),JSON_UNESCAPED_UNICODE));	
		}
	
		$result = json_decode($apiResult, true);
		
		return $result['vod'];
	}
	
	function getPlayTimesInSameGroup($oId,$ApiOrders){
		global $logger, $my;
		$sum = 0;
		//取得同群組得託播單識別碼
		$sql = "SELECT B.託播單識別碼
		FROM 託播單 A, 託播單 B
		WHERE B.託播單群組識別碼 = A.託播單群組識別碼 AND A.託播單識別碼 = ?";
		$res = $my->getResultArray($sql,'i',$oId);
		
		foreach($ApiOrders as $vod){
			foreach($res as $row){
				if(intval($row['託播單識別碼'])==intval($vod['ams_vid']))
				$sum+=intval($vod['back'])+intval($vod['finish']);
			}
		}
		return $sum;
	}
	
	function getSchedule_851(){
		global $logger, $my;
		//版位參數
		$sql = "SELECT 版位其他參數名稱,版位其他參數預設值 
		FROM 版位其他參數,版位
		WHERE 版位.版位識別碼=? AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 是否版位專用 = 1";
		$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']);
		foreach($res as $row){
			$positionConfig[$row['版位其他參數名稱']]=$row['版位其他參數預設值'];
		}
		$sql = "SELECT 版位其他參數名稱,版位其他參數預設值 
		FROM 版位其他參數
		WHERE 版位識別碼=? AND 是否版位專用 = 1";
		$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']);
		foreach($res as $row){
			$positionConfig[$row['版位其他參數名稱']]=$row['版位其他參數預設值'];
		}
		
		$sql = "SELECT 版位類型.版位名稱 AS 版位類型名稱, 版位.版位名稱
		FROM 版位 版位類型,版位
		WHERE 版位.版位識別碼=? AND 版位.上層版位識別碼 = 版位類型.版位識別碼";
		$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']);
		
		$date = str_replace('-','',$_POST['date']);
		$endwith = explode('_',$res[0]['版位名稱']);
		$area = $endwith[sizeof($endwith)-1];
		if($area =='北')
			$area='N';
		else if($area =='中')
			$area='C';
		else if($area =='南')
			$area='S';
		else if($area =='IAP')
			$area='IAP';
		$getUrl='';
		switch($res[0]['版位類型名稱']){
			case '首頁banner':
			case '專區banner':
				$getUrl='queryCSMSSchedule.php?area='.$area.'&SER_CODE='.$positionConfig['serCode'].'&BNR_SEQUENCE='.$positionConfig['bnrSequence'].'&QUERY_DATE='.$date;
			break;
			case '頻道short EPG banner':
				$getUrl='queryCSMSSchedule.php?area='.$area.'&CHAN_NUMBER='.$positionConfig['sepgOvaChannel'].'&QUERY_DATE='.$date;
			break;
			case '專區vod':
				$getUrl='queryCSMSSchedule.php?area='.$area.'&SER_CODE='.$positionConfig['serCode'].'&QUERY_DATE='.$date;
			break;
		}
		
		exit (json_encode(array('getUrl'=>$getUrl),JSON_UNESCAPED_UNICODE));
	}
	
	function getSchedule_VSM_barker(){
		global $logger, $my;
		$url = Config_VSM_Meta::GET_BARKER_VOD_PLAY_TIME_API();
		// 建立CURL連線
		$postvars = http_build_query(array());
		if(!$apiResult=connec_to_Api($url,$postvars)){
			$logger->error('無法連接單一平台API');
			exit(json_encode(array("Error"=>'無法連接單一平台API'),JSON_UNESCAPED_UNICODE));	
		}
		exit ($apiResult);
	}
?>