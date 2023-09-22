<?php
include('../tool/auth/authAJAX.php');

if(isset($_POST['action'])){
	if($_POST['action'] == '版位排程'){
		getPositionSchedule();
	}
	else if($_POST['action'] == "取得常用參數")
		getPositionScheduleCookie();
	else if($_POST['action'] == '版位排程2.0'){
		getPositionNoHtml();
	}
}

function getPositionNoHtml(){
	global $my;
	$bgcolor=['#EEEEEE','#ECF5FF'];
	if(isset($_POST['版位類型識別碼']))
		$positionType=($_POST['版位類型識別碼']=='')?'%':$_POST['版位類型識別碼'];
	else
		$positionType='%';
	if(isset($_POST['版位識別碼']))
		$position=($_POST['版位識別碼']=='')?'%':$_POST['版位識別碼'];
	else
		$position='%';
	//調整顯示區域用參數
	if(isset($_POST['顯示區域'])){
		$area='';
		if(is_array($_POST['顯示區域'])){
			foreach($_POST['顯示區域'] as $key=>$val)
				$_POST['顯示區域'][$key]='(版位.版位名稱 LIKE "%'.$val.'")';
			$area = implode(' OR ',$_POST['顯示區域']);
		}
	}
	else
		$area='';
			
	$startDate=($_POST['開始日期']=='')?'0000-00-00':$_POST['開始日期'].' 00:00:00';
	$endDate=($_POST['結束日期']=='')?'9999-12-31':$_POST['結束日期'].' 23:59:59';
	
	//排序用的參數編號
	if(isset($_POST['排序條件'])&&$_POST['排序條件']!=""&&$_POST['排序條件']!=null){
		$orderProperty = $_POST['排序條件'];
	}
	else
		$orderProperty = -1;
	
	//用cookie紀錄排序條件選項
	setPositionScheduleCookie($_POST['版位類型識別碼'],$orderProperty);
	
	//排程顯示方式(samePosition or sameProperty)
	$dataFormat = $_POST['排程顯示方式'];
	
	//取得託播單狀態對照表
	$sql='SELECT *
	FROM 託播單狀態
	WHERE 1
	';
	$orderStatus = array();
	$res = $my->getResultArray($sql);
	foreach($res as $id=>$data){
		$orderStatus[$data["託播單狀態識別碼"]]=$data["託播單狀態名稱"];
	}
	//取得版位類型名稱
	$sql='SELECT 版位名稱
	FROM 版位
	WHERE 版位.版位識別碼 LIKE ?
	';
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	if(!$stmt->bind_param('s',$positionType)){
		exit('無法繫結資料，請聯絡系統管理員！');
	}
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	$ptn= $res->fetch_assoc()['版位名稱'];
	//取得版位資料
	if($ptn=='專區banner'||$ptn=='首頁banner'){
		$sql='SELECT 版位.版位名稱,serCode.版位其他參數預設值 as serCode,bnrSequence.版位其他參數預設值 as bnrSequence
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
		LEFT JOIN 版位其他參數 serCode ON (serCode.版位識別碼 = 版位.版位識別碼 AND serCode.版位其他參數名稱 = "serCode")
		LEFT JOIN 版位其他參數 bnrSequence ON (bnrSequence.版位識別碼 = 版位.版位識別碼 AND bnrSequence.版位其他參數名稱 = "bnrSequence")
		WHERE 
		版位類型.版位識別碼 LIKE ?
		AND 版位.版位識別碼 LIKE ?
		ORDER BY CHAR_LENGTH(serCode),serCode,CHAR_LENGTH(bnrSequence),bnrSequence,版位.版位名稱'
		;
	}
	else if($ptn=='頻道short EPG banner'){
		$sql='SELECT 版位.版位名稱,sepgOvaChannel.版位其他參數預設值 as sepgOvaChannel
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
		LEFT JOIN 版位其他參數 sepgOvaChannel ON (sepgOvaChannel.版位識別碼 = 版位.版位識別碼 AND sepgOvaChannel.版位其他參數名稱 = "sepgOvaChannel")
		WHERE 
		版位類型.版位識別碼 LIKE ?
		AND 版位.版位識別碼 LIKE ?
		ORDER BY CHAR_LENGTH(sepgOvaChannel),sepgOvaChannel,版位.版位名稱'
		;
	}
	else if($ptn=='專區vod'){
		$sql='SELECT 版位.版位名稱,serCode.版位其他參數預設值 as serCode
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
		LEFT JOIN 版位其他參數 serCode ON (serCode.版位識別碼 = 版位.版位識別碼 AND serCode.版位其他參數名稱 = "serCode")
		WHERE 
		版位類型.版位識別碼 LIKE ?
		AND 版位.版位識別碼 LIKE ?
		ORDER BY CHAR_LENGTH(serCode),serCode,版位.版位名稱'
		;
	}
	else if($ptn=='前置廣告投放系統'){
		$sql='SELECT 版位.版位名稱,ext.版位其他參數預設值 as ext,pre.版位其他參數預設值 as pre
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
		LEFT JOIN 版位其他參數 ext ON (ext.版位識別碼 = 版位.版位識別碼 AND ext.版位其他參數名稱 = "ext")
		LEFT JOIN 版位其他參數 pre ON (pre.版位識別碼 = 版位.版位識別碼 AND pre.版位其他參數名稱 = "pre")
		WHERE 
		版位類型.版位識別碼 LIKE ?
		AND 版位.版位識別碼 LIKE ?
		ORDER BY ext,pre,版位.版位名稱'
		;
	}
	else if($ptn=='單一平台EPG'){
		$sql='SELECT 版位.版位名稱,channel_number.版位其他參數預設值 as channel_number
		FROM 版位
		JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
		LEFT JOIN 版位其他參數 channel_number ON (channel_number.版位識別碼 = 版位.版位識別碼 AND channel_number.版位其他參數名稱 = "channel_number")
		WHERE 
		版位類型.版位識別碼 LIKE ?
		AND 版位.版位識別碼 LIKE ?
		ORDER BY CAST(channel_number AS DECIMAL),版位.版位名稱'
		;
	}
	else{
		$sql='SELECT 版位.版位名稱
		FROM 版位,版位 版位類型
		WHERE 版位.上層版位識別碼 = 版位類型.版位識別碼
		AND 版位類型.版位識別碼 LIKE ?
		AND 版位.版位識別碼 LIKE ?
		ORDER BY 版位.版位名稱';
	}
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	if(!$stmt->bind_param('ss',$positionType,$position)){
		exit('無法繫結資料，請聯絡系統管理員！');
	}
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	$postionOrders = array();
	while($row=$res->fetch_assoc())
		$postionOrders[$row['版位名稱']] = [];
	//取得託播單資料
	$sql='SELECT CASE  
		   WHEN 額外版位.版位名稱 IS NULL THEN 版位.版位名稱
		   ELSE 額外版位.版位名稱
		   END AS 版位名稱,
		託播單名稱,廣告期間開始時間,廣告期間結束時間,託播單.託播單識別碼,委刊單識別碼,素材識別碼,版位.版位識別碼,託播單其他參數值,託播單狀態識別碼
	FROM 版位
	JOIN 託播單 ON 託播單.版位識別碼 = 版位.版位識別碼
	JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
	LEFT JOIN 託播單投放版位 ON 託播單.託播單識別碼 = 託播單投放版位.託播單識別碼 AND 託播單投放版位.ENABLE=1		
	LEFT JOIN 版位 額外版位 ON 額外版位.版位識別碼 = 託播單投放版位.版位識別碼
	LEFT JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
	LEFT JOIN 託播單其他參數 ON 託播單.託播單識別碼 = 託播單其他參數.託播單識別碼 AND 託播單其他參數.託播單其他參數順序='.$orderProperty.'
	WHERE 
	版位類型.版位識別碼 LIKE ?
	AND (版位.版位識別碼 LIKE ? OR 託播單投放版位.版位識別碼 LIKE ?)
	AND (
		(廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間)
		)
	AND 託播單.託播單狀態識別碼 IN ('.(isset($_POST['待確認排程'])?'6':'0,1,2,4').')
	'.($area==''?'':' AND ( '.$area.' )').
	($ptn=='頻道short EPG banner'||$ptn=='專區vod'||$ptn=='專區banner'||$ptn=='首頁banner'?
	'ORDER BY CHAR_LENGTH(SUBSTRING_INDEX(版位.版位名稱,SUBSTRING_INDEX(版位.版位名稱,"_",-1),1)),託播單其他參數值,託播單名稱'.
	', CHAR_LENGTH(SUBSTRING_INDEX(額外版位.版位名稱,SUBSTRING_INDEX(額外版位.版位名稱,"_",-1),1)),SUBSTRING_INDEX(額外版位.版位名稱,SUBSTRING_INDEX(額外版位.版位名稱,"_",-1),1),託播單其他參數值,託播單名稱'
	:'ORDER BY CAST(託播單其他參數值 AS DECIMAL),版位.版位名稱,額外版位.版位名稱,託播單狀態識別碼,託播單名稱'
	);
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	if(!$stmt->bind_param('ssssssss',$positionType,$position,$position,$startDate,$endDate,$startDate,$endDate,$startDate)){
		exit('無法繫結資料，請聯絡系統管理員！');
	}
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	while($row=$res->fetch_assoc()){
		if($dataFormat == "samePosition")
			$postionOrders[$row['版位名稱']][] = $row;
		else
			$postionOrders[$row['版位名稱']."<br>".$_POST['排序條件名稱']."=".$row['託播單其他參數值']][] = $row;
	}
	//產生排程表結構
	//產生託播單資料
	$sdate=date_create($_POST["開始日期"]);
	$edate=date_create($_POST["結束日期"]);
	$diff=date_diff($sdate,$edate)->format("%a");
	$positionCount = 0;
	$positionOrdersRow = [];
	foreach($postionOrders as $position => $orders){
		//印出有託播單資訊的row
		if($_POST['顯示模式'] == 'all' || $_POST['顯示模式'] == 'withOrder'){			
			if(count($orders)>0){
				$newRow = [];
				array_push($newRow,["text"=>$position,"attr"=>[
					"rowspan"=>count($orders),
					"bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]]]);
				$first = true;
				$orderNum = 0;
				foreach($orders as $order){
					if(!$first)
						$newRow = [];

					$orderStatusName = $orderStatus[$order["託播單狀態識別碼"]];
					array_push($newRow,["text"=>(++$orderNum),"attr"=>["bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]]]);
					array_push($newRow,["text"=>$order['託播單識別碼'],"attr"=>["bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]]]);
					array_push($newRow,["text"=>$order['託播單名稱'],"attr"=>["bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]]]);;
					array_push($newRow,["text"=>$orderStatusName,"attr"=>["bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]]]);
					$std = explode(' ',$order['廣告期間開始時間'])[0];
					$edd = explode(' ',$order['廣告期間結束時間'])[0];
					if($order['素材識別碼'] == null)
						$order['素材識別碼']=0;
					$colspanFlag = false;
					$colspanNum = 0;
					$order['託播單其他參數值'] = $order['託播單其他參數值']==null?" ":$order['託播單其他參數值'];
					for($i =0;$i<=$diff;$i++){
						$date = date('Y-m-d',strtotime($_POST["開始日期"]. ' + '.$i.' days'));
						if($std<=$date && $edd>=$date){
							$colspanNum ++;
							$colspanFlag = true;
						}
						else if($colspanFlag){
							array_push($newRow,[
									"text"=>$order['託播單其他參數值'],
									"attr"=>[
										"class"=>"orderSch",
										"orderId"=>$order['託播單識別碼'],
										"委刊單識別碼"=>$order['委刊單識別碼'],
										"素材識別碼"=>$order['素材識別碼'],
										"版位識別碼"=>$order['版位識別碼'],
										"colspan"=>$colspanNum,
										"title"=>$order['託播單識別碼'].":".$order['託播單名稱'],
									]
								]
							);
							$colspanFlag = false;
							$colspanNum = 0;
							array_push($newRow,["text"=>" ","attr"=>["bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]]]);
						}
						else{
							array_push($newRow,["text"=>" ","attr"=>["bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]]]);
						}
					}
					if($colspanFlag){
						array_push($newRow,[
								"text"=>$order['託播單其他參數值'],
								"attr"=>[
									"class"=>"orderSch",
									"orderId"=>$order['託播單識別碼'],
									"委刊單識別碼"=>$order['委刊單識別碼'],
									"素材識別碼"=>$order['素材識別碼'],
									"版位識別碼"=>$order['版位識別碼'],
									"colspan"=>$colspanNum,
									"title"=>$order['託播單識別碼'].":".$order['託播單名稱'],
								]
							]
						);
					}
					$first = false;
					array_push($positionOrdersRow,$newRow);
				}
				$positionCount++;
			}
		}
		if($_POST['顯示模式'] == 'all' || $_POST['顯示模式'] == 'withoutOder'){
			if(count($orders)==0){
				$newRow = [];
				array_push(
					$newRow,
					[
						"text"=>$position,
						"attr"=>[]
					]
				);
				for($i =0;$i<=$diff;$i++){
					array_push(
						$newRow,
						[
							"text"=>"",
							"attr"=>[
								"bgcolor"=>$bgcolor[$positionCount%count($bgcolor)]
							]
						]
					);
				}
				array_push($positionOrdersRow,$newRow);
				$positionCount++;
			}
		}
	}
	exit(json_encode(array('positionOrdersRow'=>$positionOrdersRow,'postionOrders'=>$postionOrders),JSON_UNESCAPED_UNICODE));
}
//取得版位排程資訊
function getPositionSchedule(){
	exit("版位排程2.0");
}

//紀錄常用參數cookie
function setPositionScheduleCookie($ptid,$paraid){
	$data = [];
	if(isset($_COOKIE["posirionScheduleSortPara"][$ptid])){
		$data = json_decode($_COOKIE["posirionScheduleSortPara"][$ptid],true);
	}
	if(!isset($data[$paraid])){
		$data[$paraid] = 0;
	}
	$data[$paraid] +=1;
	
	$value = json_encode($data);
	
	$cookieExpTime = mktime(0,0,0,date("m"),date("d")+7, date("Y"));
	setcookie("posirionScheduleSortPara[".$ptid."]",$value,$cookieExpTime);
	
}

//取得常用參數cookie
function getPositionScheduleCookie(){
	$ptid = $_POST["版位類型識別碼"];
	if(isset($_COOKIE["posirionScheduleSortPara"])){
		if(isset($_COOKIE["posirionScheduleSortPara"][$ptid])){
			$allcount = json_decode($_COOKIE["posirionScheduleSortPara"][$ptid]);
			$maxid = -1;
			$maxCount = 0;
			foreach($allcount as $id => $count){
				if($maxCount<$count){
					$maxid = $id;
					$maxCount=$count;
				}
			}
			exit(json_encode($maxid));
		}
	}
	exit(json_encode(-1));
}
?>