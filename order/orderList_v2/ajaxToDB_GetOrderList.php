<?php
	include('../../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
	if(isset($_POST["method"])){
		if($_POST["method"] == "取得委刊單"){
			getNewOrderList();
		}
		else if($_POST["method"] == "取得委刊單資料表"){
			getNewOrderListTable();
		}
		else if($_POST["method"] == "取得委刊單排程資料表"){
			getNewOrderListScheDuleTable();
		}
		else if($_POST["method"] == "取得委刊單排程託播單資料表"){
			getOrderTableBySchedule();
		}
		else if($_POST["method"] == "取得排程生成託播單資料"){
			genOrderInfoBySchedule();
		}
	}	
	
	function getNewOrderList(){
		global $logger, $my;
		//檢查是否有正確代入資料
		if(!isset($_POST['委刊單識別碼'])){
			exit(json_encode(array("success"=>false, "message"=>'輸入資料錯誤'),JSON_UNESCAPED_UNICODE));
		}
		//start
		$oid = $_POST['委刊單識別碼'];
		//取得委刊單基本資料
		$sql = "SELECT * FROM 委刊單 WHERE 委刊單識別碼 = ?";
		$data = $my->getResultArray($sql,"i",$oid)[0];
		//取得委刊單排程資訊
		$data['委刊單排程']=[];
		$sql = "SELECT * FROM 委刊單排程 WHERE 委刊單識別碼 = ?";
		$shceduleDataSet = $my->getResultArray($sql,"i",$oid);
		foreach($shceduleDataSet as $schedule){
			$sql = "SELECT 版位名稱 FROM 版位 WHERE 版位識別碼 = ?";
			$dataset = $my->getResultArray($sql,"i",$schedule['版位類型識別碼'])[0];
			$schedule["版位類型名稱"]=$dataset["版位名稱"];
			
			$sql = "SELECT 	委刊單排程投放方式名稱 FROM 委刊單排程投放方式 WHERE 委刊單排程投放方式識別碼 = ?";
			$dataset = $my->getResultArray($sql,"i",$schedule['委刊單排程投放方式識別碼'])[0];
			$schedule["委刊單排程投放方式名稱"]=$dataset["委刊單排程投放方式名稱"];
			
			$data['委刊單排程'][]=$schedule;
		}
		//取得廣告主資訊
		$sql = "SELECT * FROM 廣告主 WHERE 廣告主識別碼 = ?";
		$adowner = $my->getResultArray($sql,"i",$data['廣告主識別碼'])[0];
		$data['廣告主']=$adowner;
		//取得代理商資訊
		$sql = "SELECT * FROM 代理商 WHERE 代理商識別碼 = ?";
		$agent = $my->getResultArray($sql,"i",$data['代理商識別碼'])[0];
		$data['代理商']=$agent;
		exit(json_encode(array("success"=>true, "data"=>$data),JSON_UNESCAPED_UNICODE));
	}
	
	//取得委刊單資料表
	function getNewOrderListTable(){
		global $logger, $my;
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			$searchBy='%'.$_POST['searchBy'].'%';//搜尋關鍵字
			//先取得總筆數
			//有檔期開始日期的才是新版委刊單
			$sql='
				SELECT COUNT(1) COUNT
				FROM 委刊單
				WHERE (委刊單識別碼 = ? OR 委刊單名稱 LIKE ? OR 委刊單說明 LIKE ?)
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('iss',$_POST['searchBy'],$searchBy,$searchBy)) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT *
				FROM  委刊單
				WHERE (委刊單識別碼 = ? OR 委刊單名稱 LIKE ? OR 委刊單說明 LIKE ?)
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('issi',$_POST['searchBy'],$searchBy,$searchBy,$fromRowNo)) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$orders = array();
			while($row=$res->fetch_assoc()){
				$orders[]=array(array($row['委刊單識別碼'],'text'),array($row['委刊單名稱'],'text'),array(($row['委刊單說明']==null)?'':$row['委刊單說明'],'text'),array($row['素材連結位置'],'text'),array($row['備註'],'text'));
			}
	
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('委刊單識別碼','委刊單名稱','委刊單說明','素材連結位置','備註')
							,'data'=>$orders,'sortable'=>array('委刊單識別碼','委刊單名稱','委刊單說明','素材連結位置','備註')),JSON_UNESCAPED_UNICODE);
			exit;
	}
	//取得委刊單資料表
	function getNewOrderListScheDuleTable(){
		global $logger, $my;
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			//先取得總筆數
			//有檔期開始日期的才是新版委刊單
			$sql='
				SELECT COUNT(1) COUNT
				FROM 委刊單排程
				WHERE 委刊單識別碼 = ?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('i',$_POST['委刊單識別碼'])) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			//取得播放方式資料
			$sql='
				SELECT * FROM 委刊單排程投放方式
			';
			if(!$result = $my->getResultArray($sql)){
				exit('取得排程播放方式資訊失敗。');
			}
			$postType = array();
			foreach($result as $data){
				$postType[$data["委刊單排程投放方式識別碼"]]=$data["委刊單排程投放方式名稱"];
			}
			//取得版位類型資訊資訊
			$positionType= array();
			$sql='
				SELECT 版位識別碼,版位名稱
				FROM  版位
				WHERE 上層版位識別碼 IS NULL
			';
			if(!$result = $my->getResultArray($sql)){
				exit('取得版位類型資訊失敗。');
			}
			$positionType = array();
			foreach($result as $data){
				$positionType[$data["版位識別碼"]]=$data["版位名稱"];
			}
			
			//取得委刊單排程資訊
			$sql='
				SELECT *
				FROM  委刊單排程
				WHERE 委刊單識別碼 = ?
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('ii',$_POST['委刊單識別碼'],$fromRowNo)) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$orders = array();
			while($row=$res->fetch_assoc()){
				$poitionT = $positionType[$row['版位類型識別碼']];
				$postT = $postType[$row['委刊單排程投放方式識別碼']];
				
				$orders[]=array(array($row['委刊單排程識別碼'],'text'),array($poitionT,'text'),array($postT,'text'),array($row['廣告期間開始時間'],'text'),array($row['廣告期間結束時間'],'text'),array($row['已產生託播單識別碼'],'text'));
			}
	
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('委刊單排程識別碼','版位類型識別碼','委刊單排程投放方式識別碼','廣告期間開始時間','廣告期間結束時間','已產生託播單識別碼')
							,'data'=>$orders,'sortable'=>array('委刊單排程識別碼','版位類型識別碼','委刊單排程投放方式識別碼','廣告期間開始時間','廣告期間結束時間')),JSON_UNESCAPED_UNICODE);
			exit;
	}
	
	//取得委刊單排程下託播單資料表
	function getOrderTableBySchedule(){
		global $logger, $my;
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
		$totalRowCount=0;
		$searchBy = "%";
		if(isset($_POST["searchBy"]) && $_POST["searchBy"]!=""){
			$searchBy = "%".$_POST["searchBy"]."%";
		}
			

		$positionNames= array();//暫存版位名稱資訊
		$MaterialNames= array();//暫存素材名稱資訊
		
		$sql='
			SELECT * 
			FROM 委刊單排程
			WHERE 委刊單排程識別碼 = ?
		';
		if(!$result = $my->getResultArray($sql,"i",$_POST["委刊單排程識別碼"])){
			exit('取得委刊單排程資訊失敗。');
		}
		$shceduleData = $result[0];
		
		
		//已產生託播單資料
		$orderids = $shceduleData["已產生託播單識別碼"];
		$returnOrders = array();
		//取得託播單資訊
		if($orderids!=""){
			$totalRowCount=count(explode(",",$orderids));
			$sql='
				SELECT 託播單識別碼, 版位識別碼 AS 投放版位,託播單名稱,託播單說明,託播單狀態.託播單狀態名稱 AS 託播單狀態,
					   廣告期間開始時間 AS 開始,廣告期間結束時間 AS 結束,廣告可被播出小時時段 AS 時段
				FROM  託播單 JOIN 託播單狀態 ON 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
				WHERE 託播單識別碼 IN ('.$orderids.') AND (託播單名稱 LIKE ? OR 託播單說明 LIKE ?)
				ORDER BY 託播單識別碼 DESC '.
				'LIMIT ?,'.PAGE_SIZE.'';
			if(!$orders = $my->getResultArray($sql,"ssi",$searchBy,$searchBy,$fromRowNo)){
				exit('取得託播單資訊失敗。');
			}	
			//逐託播單查詢資料
			
			foreach($orders as $orderData){
				//取得版位資訊
				$pid=$orderData["投放版位"];
				if(!isset($positionNames[$pid])){
					$pdata = getPositionById($pid);
					$positionNames[$pid] = $pdata["版位名稱"];
				}
				$版位名稱 = $positionNames[$pid];
				$sql='
					SELECT COUNT(*) AS C
					FROM  託播單投放版位
					WHERE 託播單識別碼 = ? ';
				if(!$postpositionsNum = $my->getResultArray($sql,"i",$orderData["託播單識別碼"])){
					exit('取得託播單投放資訊失敗。');
				}	
				$postpositionsNum = $postpositionsNum[0]["C"];
				$orderData['投放版位'] ="「".$版位名稱."」等". $postpositionsNum."個版位";
				//取得素材資訊
				$sql='
					SELECT *
					FROM  託播單素材
					WHERE 託播單素材.託播單識別碼 = ? ';
				$matdatas = $my->getResultArray($sql,"i",$orderData["託播單識別碼"]);
				
				if($matdatas === false)
					exit('取得託播單素材資訊失敗。');
				$matstring = "";
				if(count($matdatas)>0)
				{
					$mdata = $matdatas[0];
					//檢查是否有素材暫存資訊
					if(!isset($MaterialNames[$mdata["素材識別碼"]])){
						$MaterialNames[$mdata["素材識別碼"]] = getMaterialById($mdata["素材識別碼"])['素材名稱'];
					}
					$matstring = "「".$MaterialNames[$mdata["素材識別碼"]]."」等".count($matdatas)."個素材";
				}
				$orderData['素材名稱'] = $matstring;
				
				$tempData = [
					[$orderData['託播單識別碼'],"text"],
					[$orderData['託播單名稱'],"text"],
					[$orderData['託播單說明'],"text"],
					[$orderData['託播單狀態'],"text"],
					[$orderData['投放版位'],"text"],
					[$orderData['素材名稱'],"text"],
					[$orderData['開始'],"text"],
					[$orderData['結束'],"text"],
					[$orderData['時段'],"text"]
					];
				$returnOrders[]=$tempData;
			}
			
		}
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),
			'header'=>array('託播單識別碼','託播單名稱','託播單說明','託播單狀態'
			,'投放版位','素材名稱','開始','結束','時段')
			,'data'=>$returnOrders,'sortable'=>array('託播單識別碼','託播單名稱','託播單說明','託播單狀態','投放版位'
			,'開始','結束','時段')),JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	//利用委刊單排程生成託播單基本資料
	function genOrderInfoBySchedule(){
		global $logger, $my;
		if(!isset($_POST['委刊單排程識別碼'])){
			exit(json_encode(array("success"=>false, "message"=>'輸入資料錯誤'),JSON_UNESCAPED_UNICODE));
		}
		//取得排程基本資訊
		$sql = "SELECT * from 委刊單排程 WHERE 委刊單排程識別碼 = ?";
		if(!$res = $my->getResultArray($sql,"i",$_POST['委刊單排程識別碼'])){
			exit(json_encode(array("success"=>false, "message"=>'取得排程基本資訊失敗'),JSON_UNESCAPED_UNICODE));
		}
		$shcData = $res[0];
		
		//依照排程資訊組出託播單
		$order = [
				"版位類型名稱"=>"",
				"版位名稱"=>"",
				"版位類型識別碼"=>$shcData["版位類型識別碼"],
				"版位識別碼"=>$shcData["複數版位識別碼"],
				"託播單名稱"=>"",
				"託播單說明"=>"",
				"廣告可被播出小時時段"=>$shcData["廣告可被播出小時時段"],
				"廣告期間開始時間"=>$shcData["廣告期間開始時間"],
				"廣告期間結束時間"=>$shcData["廣告期間結束時間"],
				"群組廣告期間開始時間"=>$shcData["廣告期間開始時間"],
				"群組廣告期間結束時間"=>$shcData["廣告期間結束時間"],
				"群組廣告可被播出小時時段"=>$shcData["廣告可被播出小時時段"],
				"預約到期時間"=>$shcData["廣告期間結束時間"],
				"售價"=>"",
				'其他參數'=>[],
				'素材'=>[]
		];
		//取得委刊單資訊
		$sql = "SELECT * from 委刊單 WHERE 委刊單識別碼 = ?";
		if(!$res = $my->getResultArray($sql,"i",$shcData["委刊單識別碼"])){
			exit(json_encode(array("success"=>false, "message"=>'取得委刊單資訊失敗'),JSON_UNESCAPED_UNICODE));
		}
		$orderListData = $res[0];
		$order["託播單名稱"] = $orderListData["委刊單名稱"];
		$order["託播單說明"] = $orderListData["委刊單說明"];
		$order["售價"] = $orderListData["售價"];
		//取得版位類型資訊
		$sql = "SELECT 版位名稱 from 版位 WHERE 版位識別碼 = ?";
		if(!$res = $my->getResultArray($sql,"i",$shcData["版位類型識別碼"])){
			exit(json_encode(array("success"=>false, "message"=>'取得版位類型資訊失敗'),JSON_UNESCAPED_UNICODE));
		}
		$order["版位類型名稱"] =$res[0]["版位名稱"];
		//取得版位資訊
		$pids = explode(",",$shcData["複數版位識別碼"]);
		$pnames = [];
		foreach($pids as $pid){
			$sql = "SELECT 版位名稱 from 版位 WHERE 版位識別碼 = ?";
			if(!$res = $my->getResultArray($sql,"i",$pid)){
				exit(json_encode(array("success"=>false, "message"=>'取得版位資訊失敗'),JSON_UNESCAPED_UNICODE));
			}
			$pnames[]=$res[0]["版位名稱"];
		}
		$order["版位名稱"] =implode(",",$pnames);
		$order["版位識別碼"] =$pids;
		
		exit(json_encode(array("success"=>true, "data"=>$order),JSON_UNESCAPED_UNICODE));
	}
	
	function getPositionById($id){
		global $my,$logger;
		$sql='
			SELECT * 
			FROM 版位
			WHERE 版位識別碼 = ?
		';

		if(!$result = $my->getResultArray($sql,"i",$id)){
			exit('取得版位資訊失敗。');
		}
		return $result[0];
	}
	
	function getMaterialById($id){
		global $my,$logger;
		$sql='
			SELECT * 
			FROM 素材
			WHERE 素材識別碼 = ?
		';

		if(!$result = $my->getResultArray($sql,"i",$id)){
			exit('取得版位資訊失敗。');
		}
		return $result[0];
		
	}
	exit(json_encode(array("success"=>false, "message"=>'輸入資料錯誤'),JSON_UNESCAPED_UNICODE));
?>
	