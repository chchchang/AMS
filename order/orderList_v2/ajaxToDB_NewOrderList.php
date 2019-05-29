<?php
	include('../../tool/auth/authAJAX.php');
	if(isset($_POST["method"])){
		if($_POST["method"] == "新增委刊單"){
			insertNewOrderList();
		}
		if($_POST["method"] == "更新委刊單"){
			updateNewOrderList();
		}
		if($_POST["method"] == "更新委刊單排程生成託播放單"){
			updateOrderListScheduleOrder();
		}
	}	
	
	function insertNewOrderList(){
		global $logger, $my;
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'嘗試新增委刊單資料');
		//檢查是否有正確代入資料
		if(!isset($_POST['orderLists'])){
			exit(json_encode(array("success"=>false, "message"=>'輸入資料錯誤'),JSON_UNESCAPED_UNICODE));
		}
		//start
		$postData = $_POST['orderLists'];
		$my->begin_transaction();
		$sql="INSERT INTO 委刊單 (委刊單名稱,委刊單說明,廣告主識別碼,代理商識別碼,售價,檔期開始日期,檔期結束日期,素材連結位置,備註,CREATED_PEOPLE)"
		." VALUES (?,?,?,?,?,?,?,?,?,?)";
		$typestr = "ssiiissssi";			
		if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法新增委刊單資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false, "message"=>'新增委刊單資料失敗'),JSON_UNESCAPED_UNICODE));
			}
		if(!$stmt->bind_param($typestr,
			$postData["委刊單名稱"]
			,$postData["委刊單說明"]
			,$postData["廣告主識別碼"]
			,$postData["代理商識別碼"]
			,$postData["售價"]
			,$postData["檔期開始日期"]
			,$postData["檔期結束日期"]
			,$postData["素材連結位置"]
			,$postData["備註"]
			,$_SESSION['AMS']['使用者識別碼'])) {
				$logger->error('無法新增委刊單資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false, "message"=>'新增委刊單資料失敗'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法新增委刊單資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false, "message"=>'新增委刊單資料失敗'),JSON_UNESCAPED_UNICODE));
		}
		//新增委刊單排程
		$postData["委刊單識別碼"]=$stmt->insert_id;
		newOwerList_updateOrderListSchedule($postData);
		//done
		$my->commit();
		$my->close();
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增委刊單成功，委刊單識別碼:'.$postData["委刊單識別碼"]);
		exit(json_encode(array("success"=>true, "message"=>'已新增委刊單，委刊單識別碼:'.$postData["委刊單識別碼"]),JSON_UNESCAPED_UNICODE));
	}
	
	function updateNewOrderList(){
		global $logger, $my;
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'嘗試更新委刊單資料');
		//檢查是否有正確代入資料
		if(!isset($_POST['orderLists'])){
			exit(json_encode(array("success"=>false, "message"=>'輸入資料錯誤'),JSON_UNESCAPED_UNICODE));
		}
		//start
		$postData = $_POST['orderLists'];
		$my->begin_transaction();
		$sql="update 委刊單 set 委刊單名稱=?,委刊單說明=?,廣告主識別碼=?,代理商識別碼=?,售價=?,檔期開始日期=?,檔期結束日期=?,素材連結位置=?,備註=?"
		.",LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 委刊單識別碼=?"
		;
		$typestr = "ssiiissssii";			
		if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法新增委刊單資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false, "message"=>'更新委刊單資料失敗:無法準備資料庫語法'),JSON_UNESCAPED_UNICODE));
			}
		if(!$stmt->bind_param($typestr,
			$postData["委刊單名稱"]
			,$postData["委刊單說明"]
			,$postData["廣告主識別碼"]
			,$postData["代理商識別碼"]
			,$postData["售價"]
			,$postData["檔期開始日期"]
			,$postData["檔期結束日期"]
			,$postData["素材連結位置"]
			,$postData["備註"]
			,$_SESSION['AMS']['使用者識別碼']
			,$postData["委刊單識別碼"])) {
				$logger->error('無法新增委刊單資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false, "message"=>'更新委刊單資料失敗:無法綁定更新參數'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法新增委刊單資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false, "message"=>'新增委刊單資料失敗'),JSON_UNESCAPED_UNICODE));
		}
		//更新委刊單排程
		newOwerList_updateOrderListSchedule($postData);
		//done
		$my->commit();
		$my->close();
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'更新委刊單成功，委刊單識別碼:'.$postData["委刊單識別碼"]);
		exit(json_encode(array("success"=>true, "message"=>'委刊單更新完成!'),JSON_UNESCAPED_UNICODE));
	}
	
	function newOwerList_updateOrderListSchedule($OrderData){
		global $logger, $my;
		//清除現有委刊單排程資料
		$sql = "delete from 委刊單排程 WHERE 委刊單識別碼 = ?";
				$typestr = "i";
				if(!$exe=$my->execute($sql,$typestr,$OrderData["委刊單識別碼"]))
				{
					$my->rollback();
					$my->close();
					$logger->error('無法清空委刊單排程資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
					exit(json_encode(array("success"=>false, "message"=>'更新排程資料失敗'),JSON_UNESCAPED_UNICODE));
				}
		foreach($OrderData['委刊單排程'] as $schedule){
			if(isset($schedule["委刊單排程識別碼"])&&$schedule["委刊單排程識別碼"]!=""){
				//資料中有id資訊，利用指定ID新增
				$sql = "INSERT INTO 委刊單排程 (委刊單排程識別碼,委刊單識別碼,版位類型識別碼,複數版位識別碼,委刊單排程投放方式識別碼,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,播放次數,CREATED_PEOPLE)"
				."VALUES (?.?,?,?,?,?,?,?,?,?)";			
				$typestr = "iiisisssii";
				if(!$exe=$my->execute($sql,$typestr,
					$schedule["委刊單排程識別碼"],
					$OrderData["委刊單識別碼"],
					$schedule["版位類型識別碼"],
					$schedule["複數版位識別碼"],
					$schedule["委刊單排程投放方式識別碼"],
					$schedule["廣告期間開始時間"],
					$schedule["廣告期間結束時間"],
					$schedule["廣告可被播出小時時段"],
					$schedule["播放次數"],
					$_SESSION['AMS']['使用者識別碼']))
					{
						$my->rollback();
						$my->close();
						$logger->error('無法新增委刊單排程資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
						exit(json_encode(array("success"=>false, "message"=>'新增排程資料失敗'),JSON_UNESCAPED_UNICODE));
					}
			}
			else{
				//資料中沒有id資訊，新增委刊單排程
				$sql = "INSERT INTO 委刊單排程 (委刊單識別碼,版位類型識別碼,複數版位識別碼,委刊單排程投放方式識別碼,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,播放次數,CREATED_PEOPLE)"
				."VALUES (?,?,?,?,?,?,?,?,?)";			
				$typestr = "iisisssii";
				if(!$exe=$my->execute($sql,$typestr,
					$OrderData["委刊單識別碼"],
					$schedule["版位類型識別碼"],
					$schedule["複數版位識別碼"],
					$schedule["委刊單排程投放方式識別碼"],
					$schedule["廣告期間開始時間"],
					$schedule["廣告期間結束時間"],
					$schedule["廣告可被播出小時時段"],
					$schedule["播放次數"],
					$_SESSION['AMS']['使用者識別碼']))
					{
						$my->rollback();
						$my->close();
						$logger->error('無法新增委刊單排程資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
						exit(json_encode(array("success"=>false, "message"=>'新增排程資料失敗'),JSON_UNESCAPED_UNICODE));
					}
			}
			
		}
	}
	
	//更新委刊單排程資料
	function updateOrderListScheduleOrder(){
		global $logger, $my;
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'嘗試更新委刊單排程資料，委刊單排程識別碼:'.$_POST['委刊單排程識別碼']);
		//檢查是否有正確代入資料
		if(!isset($_POST['委刊單排程識別碼'])){
			exit(json_encode(array("success"=>false, "message"=>'輸入資料錯誤'),JSON_UNESCAPED_UNICODE));
		}
		//取得委刊單排程資料
		$sql="SELECT * FROM 委刊單排程 WHERE 委刊單排程識別碼=?"
		;
		if(!$res = $my->getResultArray($sql,"i",$_POST["委刊單排程識別碼"])){
			exit(json_encode(array("success"=>false, "message"=>'取得委刊單排程資料錯誤'),JSON_UNESCAPED_UNICODE));
		}
		$schData = $res[0];
		//處理更新後的已產生託播單識別碼欄位資料
		if($schData["已產生託播單識別碼"]==null)
			$oids = [];
		else 
			$oids = explode(",",$schData["已產生託播單識別碼"]);
		//新增
		if(isset($_POST["新增託播單識別碼"])){
			$oids = array_merge($oids, $_POST["新增託播單識別碼"]);
		}
		//刪除
		if(isset($_POST["刪除託播單識別碼"])){
			foreach($_POST["刪除託播單識別碼"] as $del_key=>$del_val){
				if (($key = array_search($del_val, $oids)) !== false) {
					unset($oids[$key]);
				}
			}
		}
		if(count($oids)>0)
			$oids = implode(",",$oids);
		else
			$oids = "";
		//更新資料
		$sql="update 委刊單排程 set 已產生託播單識別碼=? WHERE 委刊單排程識別碼=?"
		;
		if(!$stmt=$my->execute($sql,"si",$oids,$_POST["委刊單排程識別碼"])) {
			$logger->error('無法更新委刊單排程資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false, "message"=>'新增委刊單排程資料失敗'),JSON_UNESCAPED_UNICODE));
		}
		
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'更新委刊單排程資料成功，委刊單排程識別碼:'.$_POST['委刊單排程識別碼']);
		exit(json_encode(array("success"=>true, "message"=>'託播單資訊更新成功'),JSON_UNESCAPED_UNICODE));
	}
	
	exit();
?>
	