<?php 	
	include('../tool/auth/authAJAX.php');
	include('../order/ajax_checkOrder.php');
	if( isset($_POST['action']) && $_POST['action'] != '' ){
		switch($_POST['action']){
			case '新增銷售前預約託播單':
					addBookingOrder();
				break;
			case '修改銷售前預約託播單':
					updateBookingOrder();
				break;
			case '刪除銷售前預約託播單':
					deleteBookingOrder();
				break;
			case '分割銷售前預約託播單':
					splitBookingOrder();
				break;
			case '合併銷售前預約託播單':
					mergeBookingOrder();
				break;
			case '確認銷售前預約託播單':
					commitBookingOrder();
				break;
		}	
	}
	
	function addBookingOrder(){
		global $logger, $my;
		//****檢查訂單是否可加入
		$checkOrders=array();
		if(isset($_POST['orders'])){
			$orders = json_decode($_POST["orders"],true);
			$checkOrders=array_merge($checkOrders,$orders);
		}
		$checkRes=m_check_order($checkOrders);
		if(!$checkRes['success']){
			exit(json_encode($checkRes,JSON_UNESCAPED_UNICODE));
		}
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("bookingOrder");
		$mutex->lock();
		//新增訂單
		$addres = m_addBooking($my,$orders);
		if($addres['success']){
			$insertIds = $addres['ids'];
			if(count($insertIds)>0)
				$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增銷售前預約託播單:'.implode(",",$insertIds));	
			
			$feedback = array(
				"success" => true,
				"message" => "銷售前預約託播單資訊新增成功",
			);
			$my->commit();
			$mutex->unlock();
			$my->close();
		
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
		else{
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($addres,JSON_UNESCAPED_UNICODE));
		}
	}
	
	function m_addBooking($my,$orders,$commit = false){
		$insertIds=array();
		$state = $commit?0:6;
		
		$CSMSGroup=[];
		foreach($orders as $order){
			//檢查是否要建立CSMS群組
			if(isset($order["託播單CSMS群組識別碼"])){
				if(!array_key_exists($order["託播單CSMS群組識別碼"],$CSMSGroup)){
					//建立新群組
					$sql="INSERT INTO 託播單CSMS群組 (CREATED_PEOPLE) VALUES(?)";
					if(!$stmt=$my->prepare($sql)) {
						$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					
					if(!$stmt->bind_param('i',$_SESSION['AMS']['使用者識別碼'])) {
						$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					
					if(!$stmt->execute()) {
						$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					$CSMSGroup[$order["託播單CSMS群組識別碼"]]=$stmt->insert_id;
				}
				$CSMSGroupID=$CSMSGroup[$order["託播單CSMS群組識別碼"]];
			}
			else
				$CSMSGroupID=NULL;
			//新增託播單
			$sql="INSERT INTO 託播單 (委刊單識別碼,版位識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價,託播單狀態識別碼,
			CREATED_PEOPLE,託播單CSMS群組識別碼)"
			." VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
			$start=$order["廣告期間開始時間"];
			$end=$order["廣告期間結束時間"];
			$order["售價"]=$order["售價"]==""?null:$order["售價"];
			if($order['委刊單識別碼']==null || $order['委刊單識別碼']=='')
				$order['委刊單識別碼'] = 0;
			if(!$stmt=$my->prepare($sql)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('iissssssiiii', $order['委刊單識別碼'],$order["版位識別碼"],$order["託播單名稱"],$order["託播單說明"],$start,$end
									,(isset($order["廣告可被播出小時時段"]))?$order["廣告可被播出小時時段"]:'',$order["預約到期時間"],$order["售價"]
									,$state,$_SESSION['AMS']['使用者識別碼'],$CSMSGroupID)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			$newId = $stmt->insert_id;
			
			//新增素材
			if(isset($order['素材']))
			foreach($order['素材'] as $mOrder=>$material){
				if($material['素材識別碼']!=null &&$material['素材識別碼']!=''){
					$material["點擊後開啟類型"]=($material["點擊後開啟類型"]=="")?null:$material["點擊後開啟類型"];
					$material["點擊後開啟位址"]=($material["點擊後開啟位址"]=="")?null:$material["點擊後開啟位址"];
					$sql="INSERT INTO 託播單素材 (託播單識別碼,素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE)
					VALUES (?,?,?,?,?,?,?)";
					if(!$stmt=$my->prepare($sql)) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					if(!$stmt->bind_param('iiiissi', $newId,$mOrder,$material['素材識別碼'],$material['可否點擊'],$material["點擊後開啟類型"]
											,$material["點擊後開啟位址"],$_SESSION['AMS']['使用者識別碼'])) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					if(!$stmt->execute()) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
				}
			}
			
			//新增其他參數
			if(isset($order['其他參數']))
			foreach($order['其他參數'] as $cOrder=>$otherData){
					$otherData=($otherData==null)?null:$otherData;
					$sql="INSERT INTO 託播單其他參數 (託播單識別碼,託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE)
					VALUES (?,?,?,?)";
					if(!$stmt=$my->prepare($sql)) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					if(!$stmt->bind_param('iisi', $newId,$cOrder,$otherData,$_SESSION['AMS']['使用者識別碼'])) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					if(!$stmt->execute()) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
			}		
			array_push($insertIds,$newId);
		}
		return ['success'=>true,'ids'=>$insertIds];
	}
	
	function updateBookingOrder(){
		global $logger, $my;
		//****檢查訂單是否可加入		
		$edits= json_decode($_POST['orders'],true);
		$checkRes=m_check_order($edits);
		if(!$checkRes['success']){
			exit(json_encode($checkRes,JSON_UNESCAPED_UNICODE));
		}
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("bookingOrder");
		$mutex->lock();
		//修改訂單
		$editres = m_updateBooking($my,$edits);
		//修改完成
		if($editres['success']){
			$editIds=$editres['ids'];
			$my->commit();
			$message="";
			if(count($editIds)>0)
				$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'修改銷售前預約託播單:'.implode(",",$editIds));	
				
			$feedback = array(
				"success" => true,
				"message" => "銷售前預約託播單資訊修改成功",
			);
			$mutex->unlock();
			$my->close();		
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
		else{
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($editres,JSON_UNESCAPED_UNICODE));
		}
	}
	
	function m_updateBooking($my,$edits,$commit = false){
		//修改訂單
		$editIds=[];
		foreach($edits as $edit){
			$sql="UPDATE 託播單 SET 委刊單識別碼=?,版位識別碼=?,託播單名稱=?,託播單說明=?,廣告期間開始時間=?,廣告期間結束時間=?,廣告可被播出小時時段=?,
			預約到期時間=?,售價=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP".($commit?",託播單狀態識別碼=0":"")." WHERE 託播單識別碼=? ";
			$start=$edit["廣告期間開始時間"];
			$end=$edit["廣告期間結束時間"];
			if(!$stmt=$my->prepare($sql)) {
			return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			$edit["售價"]=($edit["售價"]=="")?null:$edit["售價"];
			if(!$stmt->bind_param('iissssssiii',$edit["委刊單識別碼"],$edit["版位識別碼"],$edit["託播單名稱"],$edit["託播單說明"],$start,$end
								,$edit["廣告可被播出小時時段"],$edit["預約到期時間"],$edit["售價"]
								,$_SESSION['AMS']['使用者識別碼'],$edit["託播單識別碼"])) {
			return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			
			//刪除素材
			$sql="DELETE FROM 託播單素材 WHERE 託播單識別碼 = ?";
			if(!$stmt=$my->prepare($sql)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('i',$edit["託播單識別碼"])) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			//刪除其他參數
			$sql="DELETE FROM 託播單其他參數 WHERE 託播單識別碼 = ?";
			if(!$stmt=$my->prepare($sql)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('i',$edit["託播單識別碼"])) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}

			//新增素材
			if(isset($edit['素材']))
			foreach($edit['素材'] as $mOrder=>$素材){
				if(!isset($素材["點擊後開啟位址"]))
					$素材["點擊後開啟位址"] = null;
				if(!isset($素材["點擊後開啟類型"]))
					$素材["點擊後開啟類型"] = null;
				if($素材['素材識別碼']!=null &&$素材['素材識別碼']!=''){
					$素材["點擊後開啟類型"]=($素材["點擊後開啟類型"]=="")?null:$素材["點擊後開啟類型"];
					$素材["點擊後開啟位址"]=($素材["點擊後開啟位址"]=="")?null:$素材["點擊後開啟位址"];
					$sql="INSERT INTO 託播單素材 (託播單識別碼,素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE)
					VALUES (?,?,?,?,?,?,?)";
					if(!$stmt=$my->prepare($sql)) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					if(!$stmt->bind_param('iiiissi', $edit["託播單識別碼"],$mOrder,$素材['素材識別碼'],$素材['可否點擊'],$素材["點擊後開啟類型"]
											,$素材["點擊後開啟位址"],$_SESSION['AMS']['使用者識別碼'])) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
					if(!$stmt->execute()) {
						return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
					}
				}
			}
			
			//新增其他參數
			if(isset($edit['其他參數']))
			foreach($edit['其他參數'] as $cOrder=>$otherData){
				$sql="INSERT INTO 託播單其他參數 (託播單識別碼,託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE)
				VALUES (?,?,?,?)";
				$otherData=($otherData==null)?null:$otherData;
				if(!$stmt=$my->prepare($sql)) {
					return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
				}
				if(!$stmt->bind_param('iisi',$edit["託播單識別碼"],$cOrder,$otherData,$_SESSION['AMS']['使用者識別碼'])) {
					return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
				}
				if(!$stmt->execute()) {
					return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
				}
			}
			array_push($editIds,$edit["託播單識別碼"]);
		}
		//修改完成
		return ['success'=>true,'ids'=>$editIds];
	}
	
	function deleteBookingOrder(){
		global $logger, $my;
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("bookingOrder");
		$mutex->lock();	
		//刪除託播單
		$deleteres = m_deleteBooking($my,$_POST['delete']);
		
		if($deleteres['success']){
			$my->commit();
			$deleteIds = $deleteres['ids'];
			if(count($deleteIds)>0)
				$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'刪除託播單識別碼'.implode(",",$deleteIds));
				
			$feedback = array(
				"success" => true,
				"message" => "託播單:".implode(",",$deleteIds)."已刪除"
			);
			
			$mutex->unlock();
			$my->close();
		
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
		
		else{
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($deleteres,JSON_UNESCAPED_UNICODE));
		}
	}
	
	function m_deleteBooking($my,$deletes){
		$deleteIds = [];
		foreach($deletes as $delete){
			//刪除託播單
			$sql="DELETE FROM 託播單 WHERE 託播單識別碼=?";
			if(!$stmt=$my->prepare($sql)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('i', $delete)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			//刪除素材
			$sql="DELETE FROM 託播單素材 WHERE 託播單識別碼=?";
			if(!$stmt=$my->prepare($sql)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('i', $delete)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			//刪除其他參數
			$sql="DELETE FROM 託播單其他參數 WHERE 託播單識別碼=?";
			if(!$stmt=$my->prepare($sql)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('i', $delete)) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				return array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！');
			}
			
			array_push($deleteIds,$delete);
		}
		return array('success'=>true, 'ids' => $deleteIds);
	}
	
	//分割銷售前預約託播單
	function splitBookingOrder(){
		global $logger, $my;
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("bookingOrder");
		$mutex->lock();	
		//刪除原有託播單
		$deleteres = m_deleteBooking($my,$_POST['delete']);
		if(!$deleteres['success']){
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($deleteres,JSON_UNESCAPED_UNICODE));
		}
		//新增託播單
		$orders = json_decode($_POST["orders"],true);
		$addres = m_addBooking($my,$orders);
		if($addres['success']){
			//分割完成
			$insertIds = $addres['ids'];
			if(count($insertIds)>0)
				$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'分割銷售前預約託播單:'.implode(",",$deleteres['ids']).' 分割後託播單:'.implode(",",$insertIds));	
			
			$feedback = array(
				"success" => true,
				"message" => "銷售前預約託播單:".implode(",",$deleteres['ids'])." 分割成功，新的託播單識別碼:".implode(",",$insertIds),
			);
			$my->commit();
			$mutex->unlock();
			$my->close();
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
		else{
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($addres,JSON_UNESCAPED_UNICODE));
		}
	}
	
	//合併銷售前預約託播單
	function mergeBookingOrder(){
		global $logger, $my;
		//****檢查訂單是否可合併
		$checkOrders=array();
		//修改的託播單
		$orders = json_decode($_POST["edit"],true);
		$checkOrders=array_merge($checkOrders,$orders);
		//刪除的託播單
		foreach($_POST["delete"] as $delete){
			//取得刪除的託播單資料
			$sql = 'SELECT 版位識別碼,託播單識別碼,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段 FROM 託播單 WHERE 託播單識別碼 =?';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$delete)){
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row= $res->fetch_assoc();
			$row['delete']=true;
			//取得刪除的託播單素材資料
			$sql = 'SELECT 素材識別碼,素材順序 FROM 託播單素材 WHERE 託播單識別碼 =?';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$delete)){
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			while($mrow= $res->fetch_array()){
				$row['素材'][$mrow['素材順序']]['素材識別碼']=$mrow['素材識別碼'];
			}
			//取得刪除的託播單其他參數資料
			$sql = 'SELECT 託播單其他參數順序,託播單其他參數值 FROM 託播單其他參數 WHERE 託播單識別碼 =?';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$delete)){
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			while($mrow= $res->fetch_array()){
				$row['其他參數'][$mrow['託播單其他參數順序']]=$mrow['託播單其他參數值'];
			}
			array_push($checkOrders,$row);
		}
		//檢查合併後是否可以被排程
		$checkRes=m_check_order($checkOrders);
		if(!$checkRes['success']){
			echo json_encode($checkRes,JSON_UNESCAPED_UNICODE);
			return 0;
		}
		
		//開始合併
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("bookingOrder");
		$mutex->lock();
		$my->begin_transaction();
		//刪除多於託播單
		$deleteres = m_deleteBooking($my,$_POST['delete']);
		if(!$deleteres['success']){
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($deleteres,JSON_UNESCAPED_UNICODE));
		}
		//修改託播單
		$updateres = m_updateBooking($my,$orders);
		if($updateres['success']){
			//合併完成
			$mergeIds = array_merge($deleteres['ids'],$updateres['ids']);
			if(count($mergeIds)>0)
				$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'合併銷售前預約託播單:'.implode(",",$mergeIds).' 到託播單:'.implode(",",$updateres['ids']));	
			
			$feedback = array(
				"success" => true,
				"message" => "銷售前預約託播單:".implode(",",$mergeIds)." 合併成功，合併託播單識別碼:".implode(",",$updateres['ids'])
			);
			$my->commit();
			$mutex->unlock();
			$my->close();
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
		else{
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($updateres,JSON_UNESCAPED_UNICODE));
		}
	}
	//確認銷售前預約託播單
	function commitBookingOrder(){
		global $logger, $my;
		$orders = json_decode($_POST["orders"],true);
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("bookingOrder");
		$mutex->lock();
		$my->begin_transaction();;
		//刪除原有託播單
		$oids=[];
		foreach($orders as $order){
			$oids[] = $order['託播單識別碼'];
		}
		$oids = array_unique($oids);
		$deleteres = m_deleteBooking($my,$oids);
		if(!$deleteres['success']){
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($deleteres,JSON_UNESCAPED_UNICODE));
		}
		//新增託播單
		$addres = m_addBooking($my,$orders,true);
		if($addres['success']){
			//完成
			$insertIds = $addres['ids'];
			if(count($insertIds)>0)
				$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'確認銷售前預約託播單:'.implode(",",$oids).'(原託播單已刪除) 確認後託播單:'.implode(",",$insertIds));	
			
			$feedback = array(
				"success" => true,
				"message" => "銷售前預約託播單:".implode(",",$oids)." 確認為預約託播單:".implode(",",$insertIds),
			);
			$my->commit();
			$mutex->unlock();
			$my->close();
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
		else{
			$my->rollback();
			$my->close();
			$mutex->unlock();
			exit(json_encode($addres,JSON_UNESCAPED_UNICODE));
		}
		
		
	}
?>