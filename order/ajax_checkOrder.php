<?php

/**檢查訂單是否可以排程**/
	function m_check_order($orders){
		global $logger, $my;
		//*****暫時關閉checkOrder功能
		return array("success"=>true,"message"=>"success");
		//*****
		$positionLimit=array();//各版位的限制  結構->[position][順序][每小時最大素材筆數,每小時最大影片素材合計秒數......]
		$currentAdNum=array();//計算累積的託播單數量   結構->[position][順序]['date']['hours']=目前數目
		$currentAdSec=array();//計算累積的影片秒數   結構->[position][順序]['date']['hours']=目前秒數
		//逐一檢察待新增的託播單
		foreach($orders as $order){
			$positionIDS=explode(',',$order['版位識別碼']);
			foreach($positionIDS as $positionID)
			{
				//檢查託播單是否是預設廣告
				//先檢察投放的版位是否有預設廣告參數
				$sql ='SELECT 版位其他參數順序
				FROM 版位 LEFT JOIN 版位其他參數 ON (版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = "sepgDefaultFlag")
				WHERE 版位.版位識別碼 = ?
				';
				if(!$stmt=$my->prepare($sql)) {
					return(array("dbError"=>'無法準備statement，請聯絡系統管理員！'));
				}
				if(!$stmt->bind_param('i',$positionID)){
					return(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'));
				}
				if(!$stmt->execute()) {
					return(array("dbError"=>'無法執行statement，請聯絡系統管理員！'));
				}
				if(!$res=$stmt->get_result()){
					return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
				}
				$indexOfDefaultAdP = $res->fetch_assoc()['版位其他參數順序'];
				//版位有預設廣告參數
				if($indexOfDefaultAdP != NULL){
					//預設廣告參數設定為1 不用計算
					if(intval($order['其他參數'][$indexOfDefaultAdP])==1)
						break;
				}
						
				if(isset($order['素材']))
				foreach($order['素材'] as $順序=>$orderMaterial){
					if(!isset($positionLimit[$positionID][$順序])){
						//取得版位類型資料
						$sql = 'SELECT 每小時最大素材筆數,每小時最大影片素材合計秒數,素材類型識別碼 FROM  版位素材類型,版位 WHERE 版位.版位識別碼 =? AND 素材順序=? AND 版位.上層版位識別碼 = 版位素材類型.版位識別碼';
						if(!$stmt=$my->prepare($sql)) {
							return(array("dbError"=>'無法準備statement，請聯絡系統管理員！'));
						}
						if(!$stmt->bind_param('ii',$positionID,$順序)){
							return(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'));
						}
						if(!$stmt->execute()) {
							return(array("dbError"=>'無法執行statement，請聯絡系統管理員！'));
						}
						if(!$res=$stmt->get_result()){
							return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
						}
						@$positionLimit[$positionID][$順序]= $res->fetch_assoc();
						//取得版位資料
						$sql = 'SELECT 每小時最大素材筆數,每小時最大影片素材合計秒數,素材類型識別碼 FROM 版位素材類型 WHERE 版位識別碼 =? AND 素材順序=?';
						if(!$stmt=$my->prepare($sql)) {
							return(array("dbError"=>'無法準備statement，請聯絡系統管理員！'));
						}
						if(!$stmt->bind_param('ii',$positionID,$順序)){
							return(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'));
						}
						if(!$stmt->execute()) {
							return(array("dbError"=>'無法執行statement，請聯絡系統管理員！'));
						}
						if(!$res=$stmt->get_result()){
							return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
						}
						if(mysqli_num_rows($res)!=0)
						@$positionLimit[$positionID][$順序]= $res->fetch_assoc();
						
						//取得同版位順序託播單素材資料(不含預設廣告與凍結的託播單)
						$sql ='SELECT 託播單.託播單識別碼,廣告可被播出小時時段,影片素材秒數,廣告期間開始時間,廣告期間結束時間,版位其他參數預設值
						FROM 託播單 JOIN 版位 ON 託播單.版位識別碼 = 版位.版位識別碼 
						LEFT JOIN 版位其他參數 ON (版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = "sepgDefaultFlag")
						,託播單素材 LEFT OUTER JOIN 素材 ON 託播單素材.素材識別碼 = 素材.素材識別碼
						WHERE 託播單.託播單識別碼 = 託播單素材.託播單識別碼 AND 託播單.託播單狀態識別碼<>3 AND 託播單.託播單狀態識別碼 IN (0,1,2,3,4,6)
						AND 託播單素材.素材順序=? 
						AND 託播單.版位識別碼 =? AND ((? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間) OR (廣告期間開始時間>? AND 廣告期間結束時間<?))
						AND 版位其他參數預設值 <> 1
						';
						if(!$stmt=$my->prepare($sql)) {
							return(array("dbError"=>'無法準備statement，請聯絡系統管理員！'));
						}
						if(!$stmt->bind_param('iissss',$順序,$order["版位識別碼"],$order["廣告期間開始時間"],$order["廣告期間結束時間"],$order["廣告期間開始時間"],$order["廣告期間結束時間"])){
							return(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'));
						}
						if(!$stmt->execute()) {
							return(array("dbError"=>'無法執行statement，請聯絡系統管理員！'));
						}
						if(!$res=$stmt->get_result()){
							return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
						}
						//逐一追蹤同版位託播單 計算該版位目前每小時託播單數量、每小時播放影片的秒數
						while($row=$res->fetch_array()){
							$startDate;
							$endDate;
							($row["廣告期間開始時間"]>$order["廣告期間開始時間"])?($startDate=$row["廣告期間開始時間"]):($startDate=$order["廣告期間開始時間"]);
							($row["廣告期間結束時間"]<$order["廣告期間結束時間"])?($endDate=$row["廣告期間結束時間"]):($endDate=$order["廣告期間結束時間"]);
							$sdate=date_create($startDate);
							$edate=date_create($endDate);
							$diff=date_diff($sdate,$edate)->format("%a");
							//逐日
							for($i =0;$i<=$diff;$i++){
								$dateP=date('Y-m-d', strtotime($startDate. ' + '.$i.' days'));
								//逐小時
								$hours=explode(",",$row["廣告可被播出小時時段"]);
								foreach($hours as $h){
									@$currentAdNum[$positionID][$順序][$dateP][$h]++;
									if($positionLimit[$positionID][$順序]["素材類型識別碼"]==3 && $row["影片素材秒數"])
										@$currentAdSec[$positionID][$順序][$dateP][$h]+=$row["影片素材秒數"];
								}
							}
						}
					}
					//取得素材資料
					$sql ='SELECT 影片素材秒數 FROM 素材 WHERE 素材識別碼=? LIMIT 0,1';
					if(!$stmt=$my->prepare($sql)) {
						return(array("dbError"=>'無法準備statement，請聯絡系統管理員！'));
					}
					if(!$stmt->bind_param('i',$orderMaterial["素材識別碼"])){
						return(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'));
					}
					if(!$stmt->execute()) {
						return(array("dbError"=>'無法執行statement，請聯絡系統管理員！'));
					}
					if(!$res=$stmt->get_result()){
						return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
					}
					$orderMaterial=$res->fetch_array();
					
					//逐日、逐小時累加目前託播單							
					$sdate=date_create($order["廣告期間開始時間"]);
					$edate=date_create($order["廣告期間結束時間"]);
					$diff=date_diff($sdate,$edate)->format("%a");
					//逐日
					for($i =0;$i<=$diff;$i++){
						$dateP=date('Y-m-d', strtotime($order["廣告期間開始時間"]. ' + '.$i.' days'));
						//逐小時
						$hours=explode(",",$order["廣告可被播出小時時段"]);
						foreach($hours as $h){
							//新增的訂單:累加秒數
							if(!isset($order["託播單識別碼"])){
								@$currentAdNum[$positionID][$順序][$dateP][$h]++;
								if($positionLimit[$positionID][$順序]["素材類型識別碼"]==3){
										@$currentAdSec[$positionID][$順序][$dateP][$h]+=$orderMaterial["影片素材秒數"];					
								}
							}
							else{
								//刪除的訂單:扣除秒數
								if(isset($order["delete"])&&$order['delete']){					
									@$currentAdNum[$positionID][$順序][$dateP][$h]--;
									if($positionLimit[$positionID][$順序]["素材類型識別碼"]==3)
											@$currentAdSec[$positionID][$順序][$dateP][$h]-=$orderMaterial["影片素材秒數"];	
								}
								//修改的現有訂單:累加修改後的秒數差
								else{
									if($positionLimit[$positionID][$順序]["素材類型識別碼"]==3){
										//取得原始素材資料
										$sql = 'SELECT 影片素材秒數 FROM 素材,託播單素材 WHERE 素材.素材識別碼 = 託播單素材.素材識別碼 AND 託播單識別碼 = ? AND 素材順序 = ? LIMIT 0,1';
										if(!$stmt=$my->prepare($sql)) {
											return(array("dbError"=>'無法準備statement，請聯絡系統管理員！'));
										}
										if(!$stmt->bind_param('ii',$order["託播單識別碼"],$順序)){
											return(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'));
										}
										if(!$stmt->execute()) {
											return(array("dbError"=>'無法執行statement，請聯絡系統管理員！'));
										}
										if(!$res=$stmt->get_result()){
											return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
										}
										$originSec= $res->fetch_array()["影片素材秒數"];
										@$currentAdSec[$positionID][$順序][$dateP][$h]-=($originSec-$orderMaterial["影片素材秒數"]);
									}
								}
							}
						}
					}
				}
			}
		}
		
		$overFlow=array();//紀錄超過版位 [positionID][版位名稱,小時]
		//逐一檢查每一種有更動的版位
		$failpos=array();
		foreach(array_keys($positionLimit) as $position){
		foreach(array_keys($positionLimit[$position]) as $mOrder){
			//逐日
			$faildays=array();
			if(isset($currentAdNum[$position][$mOrder]))
			foreach(array_keys($currentAdNum[$position][$mOrder]) as $day){
				//逐小時
				$failhours=array();
				$failhours_film=array();
				foreach(array_keys($currentAdNum[$position][$mOrder][$day]) as $hour){
					if(gettype($positionLimit[$position][$mOrder]["每小時最大素材筆數"])!='NULL'){//若沒有設上限則不檢察
						if($currentAdNum[$position][$mOrder][$day][$hour]>$positionLimit[$position][$mOrder]["每小時最大素材筆數"])
							array_push($failhours,$hour);
					}
							
					if($positionLimit[$position][$mOrder]["素材類型識別碼"]==3)
					if(gettype($positionLimit[$position][$mOrder]["每小時最大影片素材合計秒數"])!='NULL'){//若沒有設上限則不檢察
						if(@$currentAdSec[$position][$mOrder][$day][$hour]>$positionLimit[$position][$mOrder]["每小時最大影片素材合計秒數"])
							array_push($failhours_film,$hour);
					}
				}
				
				$failmessage="";
				if(count($failhours)!=0)
					$failmessage="\t時段[".implode(",",$failhours)."]超過每小時素材數目上限";
				if(count($failhours_film)!=0&&$positionLimit[$position][$mOrder]["素材類型識別碼"]==3)
					$failmessage=$failmessage."\t時段[".implode(",",$failhours_film)."]超過每小時影片秒數上限";
				if($failmessage!="")
				array_push($faildays,$day.": ".$failmessage);
			}
			if(count($faildays)!=0)
			array_push($failpos,'版位:'.$position.' 順序'.$mOrder."\n".implode("\n",$faildays));
		}
		}
		if(count($failpos)!=0)
			return array("success"=>false,"message"=>implode("\n",$failpos));
		else
			return array("success"=>true,"message"=>"success");
	}

?>