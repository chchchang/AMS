<?php
	include('../tool/auth/authAJAX.php');
	include('ajax_checkOrder.php');
	$CSMSPTNAME = array('首頁banner','專區banner','頻道short EPG banner','專區vod');
	if ( isset($_POST['query']) && $_POST['query'] != '' )
		do_query();
	
	else if( isset($_POST['action']) && $_POST['action'] != '' ){
		switch($_POST['action']){
			case "getCount":
				get_Count();
				break;
			case "訂單資訊":
				order_info();
				break;
			case "查詢版位當月排程":
				position_timeTable();
				break;
			case "newOrderList":
				new_order_list();
				break;
			case "editOrderList":
				edit_order_list();
				break;
			case "委刊單資料表"://含託播單資料
				get_orderList();
				break;
			case "顯示委刊單資料"://只有委刊單資料表
				orderList_info();
				break;
			case "getPositionByPositionType":
				get_position_by_position_type();
				break;
			case "儲存更變":
				save_changes();
				break;
			case "委刊單下託播單資訊":
				get_order_by_orderList();
				break;
			case "託播單資訊":
				get_order();
				break;
			case "檢察素材":
				check_material();
				break;
			case "檢察素材CSMS":
				check_materialCSMS();
				break;
			case "取得可用素材":
				get_material();
				break;
			case "取得版位素材與參數":
				get_position_config();
				break;
			case "批次取得版位素材與參數":
				get_position_config_batch();
				break;
			case "投放次數比例計算":
				playing_times_percentage();
			case "取得額外投放次數百分比":
				get_extra_eprosure_percentage();
			default:
				break;
		}
	}
	else{
		
	}
	
	//***將null回傳空白用的function***//
	function nullToSpace($val){
		if($val == null)
			return '';
		else
			return $val;
	}
	
	/**直接下query**/
	function do_query(){
		include('../mysqli_connect.php');
		$sql = $_POST["query"];
		$result =$mysqli->query($sql);
		$a=array();	
		while($row = $result->fetch_array()){
			$row = array_map('urlencode', $row);
			array_push($a,$row);
		}
		$mysqli->close();
		echo urldecode(json_encode($a));
	}
	
	/**取得count**/
	function get_Count(){
		global $logger, $my;
		
		if(!$my->set_charset('utf8')) {
			$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}

		$sql = "SELECT COUNT(*) FROM ".$_POST["TABLE"]." WHERE ".$_POST["WHERE"];
		if(!$res=$my->query($sql)) {
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！',"query"=>$sql),JSON_UNESCAPED_UNICODE));
		}
				
		$a=array();	
		while($row = $res->fetch_array()){
			$row = array_map('urlencode', $row);
			array_push($a,$row);
		}
		echo urldecode(json_encode($a));
	}
	
	
	/**訂單資訊**/
	function order_info(){
		global $logger, $my;
		//取得託播單基本資訊
		$sql= "SELECT DISTINCT 託播單識別碼,廣告主名稱,委刊單名稱,BT.版位名稱 AS 版位類型名稱,BL.版位名稱, 託播單名稱, 託播單說明, 廣告期間開始時間, 廣告期間結束時間, 廣告可被播出小時時段, O.託播單狀態識別碼
		,預約到期時間, O.售價 AS 售價,BT.版位識別碼 AS 版位類型識別碼, BL.版位識別碼 AS 版位識別碼,託播單狀態名稱,託播單CSMS群組識別碼,A.廣告主識別碼,OL.委刊單識別碼
		FROM
			託播單 O 
			LEFT JOIN 委刊單 OL ON O.委刊單識別碼 = OL.委刊單識別碼
			LEFT JOIN 廣告主 A ON OL.廣告主識別碼 = A.廣告主識別碼
			JOIN 版位 BL ON O.版位識別碼=BL.版位識別碼
            JOIN 版位 BT ON BL.上層版位識別碼=BT.版位識別碼
            JOIN 託播單狀態 ON O.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
		WHERE O.託播單識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		$push_a = $res->fetch_assoc();
		//取得素材資料
		$query2 = "SELECT 素材順序,素材名稱,素材.素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址 
			FROM 託播單素材 LEFT JOIN 素材 ON(託播單素材.素材識別碼 = 素材.素材識別碼) WHERE 託播單素材.託播單識別碼 = ?";
		if(!$stmt=$my->prepare($query2)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}		
		$push_a["素材"] = array();
		while($row2 = $res->fetch_assoc()){
			$push_a["素材"][$row2["素材順序"]]=$row2;
		};
		
		$query2 = "SELECT 託播單其他參數順序,託播單其他參數值
			FROM 託播單其他參數 
			WHERE 託播單識別碼 = ?";
		if(!$stmt=$my->prepare($query2)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}		
		$push_a["其他參數"] = array();
		while($row2 = $res->fetch_assoc()){
			$push_a["其他參數"][$row2["託播單其他參數順序"]]=$row2["託播單其他參數值"];
		};
		//取得其他參數
		$query2 = "SELECT 版位其他參數順序,版位其他參數是否必填
			FROM 版位其他參數,託播單,版位
			WHERE 託播單識別碼 = ? AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 是否版位專用 = 0";
		if(!$stmt=$my->prepare($query2)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}		
		$push_a["其他參數必填"] = array();
		while($row2 = $res->fetch_assoc()){
			$push_a["其他參數必填"][$row2["版位其他參數順序"]]=$row2["版位其他參數是否必填"];
		};
		//取得多版位
		$query = "SELECT 版位識別碼
			FROM 託播單投放版位
			WHERE 託播單識別碼 = ? AND ENABLE = 1";
		if(!$stmt=$my->prepare($query)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}		
		$positionArray = array();
		while($row = $res->fetch_assoc()){
			$positionArray[]=$row['版位識別碼'];
		};
		if(count($positionArray)>0)
			$push_a['版位識別碼'] =  implode(',',$positionArray);
			
		echo json_encode($push_a,JSON_UNESCAPED_UNICODE);
	}
	
	/**排程表資訊**/
	function position_timeTable(){
		global $logger, $my;
		
		$std=$_POST['year'].'-'.sprintf('%02d',strval($_POST['month']));
		$stdwlidcard = $std.'-%';
		$std .='-01';
		$sql= "SELECT 託播單.託播單識別碼,廣告可被播出小時時段,廣告期間開始時間,廣告期間結束時間 
			FROM 託播單 LEFT JOIN 託播單投放版位 ON 託播單.託播單識別碼 = 託播單投放版位.託播單識別碼 AND 託播單投放版位.ENABLE=1		
			WHERE (託播單.版位識別碼 = ? OR 託播單投放版位.版位識別碼 = ?)
			AND (? between 廣告期間開始時間 AND 廣告期間結束時間 OR 廣告期間開始時間 LIKE ? OR 廣告期間結束時間 LIKE ?)";
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('iisss',$_POST["版位識別碼"],$_POST["版位識別碼"],$std,$stdwlidcard,$stdwlidcard)) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}		
		$a=array();	
		while($row = $res->fetch_array()){
			array_push($a,$row);
		}
		echo json_encode($a,JSON_UNESCAPED_UNICODE);
		
	}
	
	/**新增委刊單**/
	function new_order_list(){
		global $logger, $my;
		
		$sql="SELECT COUNT(*) FROM 委刊單 WHERE 委刊單名稱=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('s',$_POST["委刊單名稱"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$row = $res->fetch_array();
		if($row[0]>0){
			$feedback = array(
				"success" => false,
				"message" => urlencode("相同的委刊單名稱已存在"),
			);

			echo urldecode(json_encode($feedback));
			return 0;
		}
		
		$price = ($_POST["售價"]=="")?null:$_POST["售價"];
		
		$sql="INSERT INTO 委刊單 (委刊單名稱,委刊單說明,廣告主識別碼,售價,委刊單編號,CREATED_PEOPLE)"
		." VALUES (?,?,?,?,?,?)";
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('ssiisi',$_POST["委刊單名稱"],$_POST["委刊單說明"],$_POST["廣告主識別碼"],$price,$_POST["委刊單編號"],$_SESSION['AMS']['使用者識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if($stmt->affected_rows>0){
		
			$feedback = array(
				"success" => true,
				"message" => urlencode("成功新增委刊單資料!"),
				"insert_id" =>$stmt->insert_id
			);
			$logger->info('使用者識別碼'.$_SESSION['AMS']['使用者識別碼'].' 新增委刊單:識別碼'.$stmt->insert_id);
			
			echo urldecode(json_encode($feedback));
		}
		else{
			$feedback = array(
				"success" => false,
				"message" => urlencode("新增委刊單資料失敗"),
			);

			echo urldecode(json_encode($feedback));
		}
	}
	
	/**取得委刊單資料**/
	function get_orderList(){
		global $logger, $my;
		$sort = "" ;
		$where = array();
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
		if(isset($_POST["委刊單識別碼"]))
			array_push($where,"委刊單識別碼 =".$_POST["委刊單識別碼"]);
		if(isset($_POST["WHERE"]))
			array_push($where,"( ".$_POST["WHERE"]." )");
		if(sizeof($where)!=0)
			$wheresql = "WHERE ".implode(" AND ",$where);
		else
			$wheresql="";
			
		$query = "SELECT 委刊單識別碼, 委刊單名稱,CREATED_TIME,LAST_UPDATE_TIME FROM 委刊單 ".$wheresql
		." ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];
		
		if(!$result =$my->query($query)) {
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$a = array();
		while($row = $result->fetch_array()){
			$push_a[urlencode("委刊單識別碼")]=urlencode($row["委刊單識別碼"]);
			$push_a[urlencode("委刊單名稱")]=urlencode($row["委刊單名稱"]);
			$push_a[urlencode("CREATED_TIME")]=urlencode($row["CREATED_TIME"]);
			$push_a[urlencode("LAST_UPDATE_TIME")]=urlencode($row["LAST_UPDATE_TIME"]);
			
			$query = "SELECT COUNT(*) FROM 託播單 WHERE 委刊單識別碼 =".$row["委刊單識別碼"];
		
			if(!$result2=$my->query($query)) {
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			$countAll =$result2->fetch_array();
			if($countAll["COUNT(*)"]==0)
				$push_a[urlencode("託播單狀態")] = urlencode("未建立");
			else{
				
				$query = "SELECT COUNT(*) FROM 託播單 WHERE 委刊單識別碼 =".$row["委刊單識別碼"]." AND 託播單狀態=2";
				$count =$my->query($query)->fetch_array();
				if($countAll["COUNT(*)"]==$count["COUNT(*)"])
					$push_a[urlencode("託播單狀態")] = urlencode("全部送出");
				else if($count["COUNT(*)"]==0){
					$push_a[urlencode("託播單狀態")] = urlencode("已建立，未送出");
				}
				else{
					$push_a[urlencode("託播單狀態")] = urlencode("部分送出");
				}
			}
			
			array_push($a,$push_a);
		}
		echo urldecode(json_encode($a));	
	}
	
	//修改訂單資料
	function edit_order_list(){
		global $logger, $my;
		
		$sql="SELECT COUNT(*) FROM 委刊單 WHERE 委刊單名稱=? AND 委刊單識別碼!=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('si',$_POST["委刊單名稱"],$_POST["委刊單識別碼"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$row = $res->fetch_array();
		if($row[0]>0){
			$feedback = array(
				"success" => false,
				"message" => urlencode("相同的委刊單名稱已存在"),
			);

			echo urldecode(json_encode($feedback));
			return 0;
		}
		
		$price = ($_POST["售價"]=="")?null:$_POST["售價"];
		
		$sql="UPDATE 委刊單 SET 委刊單名稱=?,委刊單說明=?,廣告主識別碼=?,售價=?,委刊單編號=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 委刊單識別碼=?";
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('ssiisii',$_POST["委刊單名稱"],$_POST["委刊單說明"],$_POST["廣告主識別碼"],$price,$_POST["委刊單編號"],$_SESSION['AMS']['使用者識別碼'],$_POST["委刊單識別碼"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if($stmt->affected_rows>0){
		
			$feedback = array(
				"success" => true,
				"message" => urlencode("成功修改委刊單資料!"),
				"insert_id" =>$stmt->insert_id
			);
			$logger->info('使用者識別碼'.$_SESSION['AMS']['使用者識別碼'].' 修改委刊單:識別碼'.$_POST["委刊單識別碼"]);
			
			echo urldecode(json_encode($feedback));
		}
		else{
			$feedback = array(
				"success" => false,
				"message" => urlencode("修改委刊單資料失敗"),
			);

			echo urldecode(json_encode($feedback));
		}
	}
	
	//顯示委刊單資料
	function orderList_info(){
		global $logger, $my;
		
		$sql="SELECT * FROM 委刊單 WHERE 委刊單識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["委刊單識別碼"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		

		$row = $res->fetch_assoc();
		
		echo json_encode($row,JSON_UNESCAPED_UNICODE);
	}
	
	//取得版位資料
	function get_position_by_position_type(){
		global $logger, $my;
		
		$sql="SELECT 版位名稱 FROM 版位 WHERE 版位識別碼=? AND DISABLE_TIME IS NULL AND DELETED_TIME IS NULL ";
		
		$PTN = $my->getResultArray($sql,'i',isset($_POST["版位類型識別碼"])?$_POST["版位類型識別碼"]:0);
		$PTN =$PTN[0]['版位名稱'];
		
		$sql="SELECT 版位識別碼,版位名稱 FROM 版位 WHERE 上層版位識別碼=? AND DISABLE_TIME IS NULL AND DELETED_TIME IS NULL ";
		if($PTN == '頻道short EPG banner')
		$sql.=" ORDER BY SUBSTRING_INDEX(版位名稱,'_',-1),CHAR_LENGTH(版位名稱),版位名稱";
		else if ($PTN == '首頁banner'||$PTN == '專區banner'||$PTN == '專區vod')
		$sql.=" ORDER BY SUBSTRING_INDEX(版位名稱,'_',-1),版位名稱";
		else if ($PTN == '單一平台EPG')
		$sql.=" ORDER BY CHAR_LENGTH(SUBSTRING_INDEX(版位名稱,'_',1)),SUBSTRING_INDEX(版位名稱,'_',1),版位名稱";
		else
		$sql.=" ORDER BY 版位名稱";
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["版位類型識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		

		$a = array();
		while($row = $res->fetch_array()){
			array_push($a,$row);
		}
		
		echo json_encode($a,JSON_UNESCAPED_UNICODE);
	}
	
	/**儲存更變**/
	function save_changes(){
		require_once("../tool/phpExtendFunction.php");
		global $logger, $my;
		$Error;		
		//****檢查訂單是否可加入		
		$checkOrders=array();
		if(isset($_POST['orders'])){
			$orders = json_decode($_POST["orders"],true);
			$checkOrders=array_merge($checkOrders,$orders);
		}
		if(isset($_POST['edits'])){
			$edits= json_decode($_POST['edits'],true);
			if(isset($edits['edit'])){
				//*****若有修改現有訂單，將同託播單群組與託播單CSMS群組的訂單一並加入修改
				$editTemp = array();
				foreach($edits["edit"] as $edit){
					//取得修改託播單的基本資料
					$sql = "SELECT 託播單CSMS群組識別碼,廣告可被播出小時時段,版位類型.版位名稱 AS 版位類型名稱 ,託播單狀態識別碼,版位.版位名稱
					FROM 託播單,版位,版位 版位類型
					WHERE 託播單.託播單識別碼 = ?
					AND 版位.版位識別碼 = 託播單.版位識別碼
					AND 版位.上層版位識別碼 = 版位類型.版位識別碼";
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('i',$edit["託播單識別碼"])){
						exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					$row= $res->fetch_assoc();	
					$csmsgid = $row['託播單CSMS群組識別碼'];
					$hours = $row['廣告可被播出小時時段'];
					$ptN = $row['版位類型名稱'];
					$pN = $row['版位名稱'];
					$state = $row['託播單狀態識別碼'];
					//取得不可變動其他參數資料
					/*$sql = 'SELECT 版位其他參數順序
					FROM 託播單,版位,版位 版位類型,版位其他參數
					WHERE 託播單.託播單識別碼 = ?
					AND 版位.版位識別碼 = 託播單.版位識別碼
					AND 版位.上層版位識別碼 = 版位類型.版位識別碼
					AND 版位類型.版位識別碼 = 版位其他參數.版位識別碼
					AND (版位其他參數型態識別碼 = 4 OR 版位其他參數名稱 = "bannerTransactionId1" OR 版位其他參數名稱 = "preTransaction")';
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('i',$edit["託播單識別碼"])){
						exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					$nonChangeConfig =[];
					while($row = $res->fetch_assoc()){
						$nonChangeConfig[]=$row['版位其他參數順序'];
					}*/
					$area = m_get_area($pN);
					//查詢不可變動參數用的sql
					$nonChangeConfigSql = "SELECT 託播單其他參數值,託播單其他參數順序 FROM 託播單其他參數 WHERE 託播單識別碼 = ?";
					//*****修改同託播單CSMS群組且同區域，但不為凍結狀態的託播單
					$sql = "SELECT 託播單識別碼,版位.版位識別碼,託播單狀態識別碼 
						FROM 託播單, 版位 
						WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 託播單狀態識別碼 IN (0,1,2,3,4) AND 託播單CSMS群組識別碼 = ? AND 託播單識別碼 != ? AND 版位.版位名稱 LIKE ?";
					$newEdits = $my->getResultArray($sql,'iis',$csmsgid,$edit["託播單識別碼"],'%'.$area);
					if(isset($newEdits))
					foreach($newEdits as $ne){
						if($ne['託播單狀態識別碼']!= $state)
							exit(json_encode(array("success"=>false,"message"=>'CSMS群組中同區域的託播單狀態不同步'),JSON_UNESCAPED_UNICODE));
						$editTemp[]= PHPExtendFunction::arrayCopy($edit);
						$index = sizeof($editTemp)-1;
						$editTemp[$index]['託播單識別碼'] = $ne['託播單識別碼'];
						$editTemp[$index]['版位識別碼'] = $ne['版位識別碼'];
						
						/*$configs = $my->getResultArray($nonChangeConfigSql,'i',$ne['託播單識別碼']);
						if(isset($configs))
						if(!(isset($_POST['synEdit'])&&$_POST['synEdit']))
						foreach($configs as $config){
							if(in_array($config['託播單其他參數順序'],$nonChangeConfig))
								$editTemp[$index]['其他參數'][$config['託播單其他參數順序']]=$config['託播單其他參數值'];
						}*/
					}
				}
				
				$edits["edit"] = array_merge($edits["edit"], $editTemp);
				$checkOrders=array_merge($checkOrders,$edits["edit"]);
			}
			if(isset($edits['delete'])){
				foreach($edits['delete'] as $delete){
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
			}
		}
		$checkRes=m_check_order($checkOrders);
		if(!$checkRes['success']){
			echo json_encode($checkRes,JSON_UNESCAPED_UNICODE);
			return 0;
		}
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("savingOrder");
		$mutex->lock();
		//新增訂單
		$insertIds=array();
		if(isset($_POST['orders'])){
			//$orders = json_decode($_POST['orders'],true); 於排程檢察時已執行果此步驟
			$託播單CSMS群組=[];
			foreach($orders as $order){		
				//檢查是否要建立CSMS群組
				if(isset($order["託播單CSMS群組識別碼"])){
					if(!array_key_exists($order["託播單CSMS群組識別碼"],$託播單CSMS群組)){
						//建立新群組
						$sql="INSERT INTO 託播單CSMS群組 (CREATED_PEOPLE) VALUES(?)";
						if(!$stmt=$my->prepare($sql)) {
							$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						
						if(!$stmt->bind_param('i',$_SESSION['AMS']['使用者識別碼'])) {
							$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						
						if(!$stmt->execute()) {
							$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						$託播單CSMS群組[$order["託播單CSMS群組識別碼"]]=$stmt->insert_id;
					}
					$託播單CSMS群組識別碼=$託播單CSMS群組[$order["託播單CSMS群組識別碼"]];
				}
				else
					$託播單CSMS群組識別碼=NULL;
				
				if(!isset($order["廣告可被播出小時時段"]))
					$order["廣告可被播出小時時段"]='';
				$order["售價"] = ($order["售價"]=="")?null:$order["售價"];
				
				//檢查是否設定多個版位
				$orderPositions = explode(',',$order['版位識別碼']);
				//用第一個版位當作代表版位
				$order['版位識別碼'] = $orderPositions[0];

				//新增託播單
				$sql="INSERT INTO 託播單 (委刊單識別碼,版位識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價,
				CREATED_PEOPLE,託播單CSMS群組識別碼)"
				." VALUES (?,?,?,?,?,?,?,?,?,?,?)";
				$start=$order["廣告期間開始時間"];
				$end=$order["廣告期間結束時間"];
				if(!$stmt=$my->prepare($sql)) {
					$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
					goto exitWithError;
				}
				
				if(!$stmt->bind_param('iissssssiii', $_POST['orderListId'],$order["版位識別碼"],$order["託播單名稱"],$order["託播單說明"],$start,$end
										,$order["廣告可被播出小時時段"],$order["預約到期時間"],$order["售價"]
										,$_SESSION['AMS']['使用者識別碼'],$託播單CSMS群組識別碼)) {
					$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
					goto exitWithError;
				}
				
				if(!$stmt->execute()) {
					$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
					goto exitWithError;
				}
				$newId = $stmt->insert_id;
				
				//新增素材
				if(isset($order['素材']))
				foreach($order['素材'] as $mOrder=>$素材){
					if($素材['素材識別碼']!=null &&$素材['素材識別碼']!=''){
						$素材["點擊後開啟類型"]=($素材["點擊後開啟類型"]=="")?null:$素材["點擊後開啟類型"];
						$素材["點擊後開啟位址"]=($素材["點擊後開啟位址"]=="")?null:$素材["點擊後開啟位址"];
						$sql="INSERT INTO 託播單素材 (託播單識別碼,素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE)
						VALUES (?,?,?,?,?,?,?)";
						if(!$stmt=$my->prepare($sql)) {
							$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->bind_param('iiiissi', $newId,$mOrder,$素材['素材識別碼'],$素材['可否點擊'],$素材["點擊後開啟類型"]
												,$素材["點擊後開啟位址"],$_SESSION['AMS']['使用者識別碼'])) {
							$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->execute()) {
							$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
					}
				}
				
				//新增其他參數
				if(isset($order['其他參數']))
				foreach($order['其他參數'] as $cOrder=>$其他參數){
						$sql="INSERT INTO 託播單其他參數 (託播單識別碼,託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE)
						VALUES (?,?,?,?)";
						if(!$stmt=$my->prepare($sql)) {
							$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->bind_param('iisi', $newId,$cOrder,$其他參數,$_SESSION['AMS']['使用者識別碼'])) {
							$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->execute()) {
							$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
				}
				
				//若有多個版位，新增託播單投放版位資料庫
				if(count($orderPositions)>0){
					foreach($orderPositions as $op){
						$sql="INSERT INTO 託播單投放版位 (託播單識別碼,版位識別碼,CREATED_PEOPLE)
						VALUES (?,?,?)";
						if(!$stmt=$my->prepare($sql)) {
							$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->bind_param('iii', $newId,$op,$_SESSION['AMS']['使用者識別碼'])) {
							$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->execute()) {
							$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
					}
				}

				array_push($insertIds,$newId);
			}
		}
		//現有訂單異動
		$editIds=array();
		$deleteIds=array();
		if(isset($_POST['edits'])){
			//$edits= json_decode($_POST['edits'],true); 於排程檢察時已執行果此步驟
			//修改現有訂單
			if(isset($edits["edit"]))
			foreach($edits["edit"] as $edit){
				$state=0;
				//取得託播單狀態
				$sql='SELECT 託播單狀態識別碼 FROM 託播單 WHERE 託播單識別碼 = ?';
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				if(!$stmt->bind_param('i',$edit["託播單識別碼"])) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				if(!$res=$stmt->get_result()){
					$Error=(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				$row = $res->fetch_assoc();
				$state = $row['託播單狀態識別碼'];
				if($state!=1)
				$state=0;

				//更新託播單
				//檢查是否設定多個版位
				$orderPositions = explode(',',$edit['版位識別碼']);
				//用第一個版位當作代表版位
				$edit['版位識別碼'] = $orderPositions[0];
				$sql="UPDATE 託播單 SET 託播單名稱=?,託播單說明=?,廣告期間開始時間=?,廣告期間結束時間=?,廣告可被播出小時時段=?,
				預約到期時間=?,售價=?,託播單狀態識別碼=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=? ";
				$start=$edit["廣告期間開始時間"];
				$end=$edit["廣告期間結束時間"];
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				$price = ($edit["售價"]=="")?null:$edit["售價"];
				if(!$stmt->bind_param('ssssssiiii',$edit["託播單名稱"],$edit["託播單說明"],$start,$end
									,$edit["廣告可被播出小時時段"],$edit["預約到期時間"],$price
									,$state,$_SESSION['AMS']['使用者識別碼'],$edit["託播單識別碼"])) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				
				//刪除素材
				$sql="DELETE FROM 託播單素材 WHERE 託播單識別碼 = ?";
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('i',$edit["託播單識別碼"])) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				//刪除其他參數
				$sql="DELETE FROM 託播單其他參數 WHERE 託播單識別碼 = ?";
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('i',$edit["託播單識別碼"])) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}

			//新增素材
				if(isset($edit['素材']))
				foreach($edit['素材'] as $mOrder=>$素材){
					if($素材['素材識別碼']!=null &&$素材['素材識別碼']!=''){
						$素材["點擊後開啟類型"]=($素材["點擊後開啟類型"]=="")?null:$素材["點擊後開啟類型"];
						$素材["點擊後開啟位址"]=($素材["點擊後開啟位址"]=="")?null:$素材["點擊後開啟位址"];
						$sql="INSERT INTO 託播單素材 (託播單識別碼,素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE)
						VALUES (?,?,?,?,?,?,?)";
						if(!$stmt=$my->prepare($sql)) {
							$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->bind_param('iiiissi', $edit["託播單識別碼"],$mOrder,$素材['素材識別碼'],$素材['可否點擊'],$素材["點擊後開啟類型"]
												,$素材["點擊後開啟位址"],$_SESSION['AMS']['使用者識別碼'])) {
							$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->execute()) {
							$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
					}
				}
				
				//新增其他參數
				if(isset($edit['其他參數']))
				foreach($edit['其他參數'] as $cOrder=>$其他參數){
						$sql="INSERT INTO 託播單其他參數 (託播單識別碼,託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE)
						VALUES (?,?,?,?)";
						if(!$stmt=$my->prepare($sql)) {
							$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->bind_param('iisi',$edit["託播單識別碼"],$cOrder,$其他參數,$_SESSION['AMS']['使用者識別碼'])) {
							$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
						if(!$stmt->execute()) {
							$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
							goto exitWithError;
						}
				}
				
				//檢查多版位投放
				/*if(count($orderPositions)>1){
					$sql = 'SELECT * FROM 託播單投放版位 WHERE 託播單識別碼 = ?';
					if(!$stmt=$my->prepare($sql)) {
						$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						goto exitWithError;
					}
					if(!$stmt->bind_param('i',$edit["託播單識別碼"])) {
						$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						goto exitWithError;
					}
					if(!$stmt->execute()) {
						$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						goto exitWithError;
					}
					if(!$res=$stmt->get_result()){
						$Error=(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						goto exitWithError;
					}
					
					//依照版位識別碼整理投放版位，並將ENBLE預設設為0
					$positionsRecort=[];
					$positionTemplate=[];//新增新版位時使用的模板
					while($row = $res->fetch_assoc()){
						$row['ENABLE']=0;
						$positionsRecort[$row['版位識別碼']]=$row;
						if(count($positionTemplate)==0)
						$positionTemplate=$row;
					}
					
					//逐一比較版位，若有新的版位則新增，若有移出的版位則disable
					//已存在的版位不可變動投放次數
					foreach($orderPositions as $pid){
						if(isset($positionsRecort[$pid])){
							//版位存在
							$positionsRecort[$pid]['ENABLE']=1;
							$sql="UPDATE 託播單投放版位 SET 版位投放上限=?,ENABLE=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單識別碼=? AND 版位識別碼=?";
							if(!$stmt=$my->prepare($sql)) {
								$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
								exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
							}
							
							if(!$stmt->bind_param('iiiii',$$positionsRecort[$pid]['版位投放上限'],$$positionsRecort[$pid]["ENABLE"]
													,$_SESSION['AMS']['使用者識別碼'],$positionsRecort[$pid]["託播單識別碼"],$pid)) {
								$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
								exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
							}
							
							if(!$stmt->execute()) {
								$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
								exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
							}
						}
						else{
							//版位不存在
							$positionsRecort[$pid]=$positionTemplate;
							$positionsRecort[$pid]['ENABLE']=1;
							$positionsRecort[$pid]['版位識別碼']=$pid;
							$positionsRecort[$pid]['版位投放次數']=0;
							$sql="INSERT INTO 託播單投放版位 (託播單識別碼,版位識別碼,版位投放上限,版位投放次數,LAST_UPDATE_TIME,LAST_UPDATE_PEOPLE)
							VALUES (?,?,?,?,CURRENT_TIMESTAMP,?)";
							if(!$stmt=$my->prepare($sql)) {
								$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
								goto exitWithError;
							}
							if(!$stmt->bind_param('iiiii',$edit["託播單識別碼"],$pid,$positionTemplate['版位投放上限'],0,$_SESSION['AMS']['使用者識別碼'])) {
								$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
								goto exitWithError;
							}
							if(!$stmt->execute()) {
								$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
								goto exitWithError;
							}
						}
					}
				}//end of 檢查多版位投放*/
				
				array_push($editIds,$edit["託播單識別碼"]);
			}
			//刪除現有訂單
			if(isset($edits["delete"]))
			foreach($edits["delete"]as $delete){
				//刪除託播單
				$sql="DELETE FROM 託播單 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('i', $delete)) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				//刪除素材
				$sql="DELETE FROM 託播單素材 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('i', $delete)) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				//刪除其他參數
				$sql="DELETE FROM 託播單其他參數 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('i', $delete)) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				//刪除投放版位
				$sql="DELETE FROM 託播單投放版位 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					$Error=(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('i', $delete)) {
					$Error=(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					$Error=(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					goto exitWithError;
				}
				array_push($deleteIds,$delete);
			}
		}
		
		$my->commit();
		
		$message="";
		if(count($insertIds)>0)
			$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增託播單識別碼'.implode(",",$insertIds));	
		if(count($editIds)>0)
			$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'修改託播單識別碼'.implode(",",$editIds));
		if(count($deleteIds)>0)
			$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'刪除託播單識別碼'.implode(",",$deleteIds));
			
		$feedback = array(
			"success" => true,
			"message" => "訂單資訊更新成功",
		);
		
		$mutex->unlock();
		$my->close();
	
		exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		
		exitWithError:
		$my->rollback();
		$my->close();
		$mutex->unlock();
		exit($Error);
	}
	
	/**依委刊單取得託播單資料**/
	function get_order_by_orderList(){
		global $logger, $my;
		$sort = "" ;
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
		$sql="SELECT 託播單識別碼,版位.版位名稱,版位類型.版位名稱 AS 版位類型,託播單名稱,託播單狀態名稱 AS 託播單狀態,廣告期間開始時間,廣告期間結束時間 
			FROM 託播單,託播單狀態,版位,版位 版位類型
			WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 =  版位類型.版位識別碼 AND 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼 AND 委刊單識別碼=".$_POST["委刊單識別碼"]
			." ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];

		if(!$result =$my->query($sql)){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$a=array();	
		while($row = $result->fetch_assoc()){
			array_push($a,array_map('nullToSpace',$row));
		}
		echo json_encode($a,JSON_UNESCAPED_UNICODE);
	}

	/**託播單資料**/
	function get_order(){
		global $logger, $my;
		$sort = "" ;
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
			
		$sql="SELECT 託播單識別碼, 託播單名稱,託播單狀態名稱 AS 託播單狀態,廣告期間開始時間,廣告期間結束時間 FROM 託播單,託播單狀態 WHERE 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼 AND ".$_POST["WHERE"]
			." ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];

		if(!$result =$my->query($sql)){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$a=array();	
		while($row = $result->fetch_array()){
			array_push($a,$row);
		}
		echo json_encode($a,JSON_UNESCAPED_UNICODE);
	}
	
	/**檢查素材是否合規定**/
	function check_material(){
			global $logger, $my;
			$orders = json_decode($_POST["orders"],true);
			//逐託播單檢查
			foreach($orders as $order){
				//逐託播單素材檢查
				if(!isset($order['素材']))
					continue;
				$positionIDArray=explode(',',$order['版位識別碼']);
				//逐版位檢查
				foreach($positionIDArray as $positionID){
					//取得版位類型名稱
					$sql = 'SELECT 版位類型.版位名稱 AS 版位類型名稱,版位.版位名稱,版位類型.版位識別碼 AS 版位類型識別碼
							FROM 版位, 版位 版位類型
							WHERE 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 版位.版位識別碼 = ?';						
					$result = $my->getResultArray($sql,'i',$positionID)[0];
					$ptName = $result['版位類型名稱'];
					$ptId = $result['版位類型識別碼'];
					$pName = $result['版位名稱'];

					foreach($order['素材'] as $mOrder=>$material){
						if($material["素材識別碼"] == 0)
							continue;
						//檢察素材走期是否可包含託播單走期
						$sql="SELECT 素材有效開始時間,素材有效結束時間 FROM 素材 WHERE 素材識別碼=?";

						if(!$stmt=$my->prepare($sql)) {
							$my->close();
							exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						
						if(!$stmt->bind_param('i',$material["素材識別碼"])){
							$my->close();
							exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						
						if(!$stmt->execute()) {
							$my->close();
							exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						
						if(!$res=$stmt->get_result()){
							$my->close();
							exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						if(mysqli_num_rows($res)!=0){
							$row=$res->fetch_array();
							if($row["素材有效開始時間"]!=null){
								if($row["素材有效開始時間"]>$order["廣告期間開始時間"]){
									exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋託播單走期'),JSON_UNESCAPED_UNICODE));
								}
							}
							if($row["素材有效結束時間"]!=null){
								if($row["素材有效結束時間"]<$order["廣告期間結束時間"]){
									exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋託播單走期'),JSON_UNESCAPED_UNICODE));
								}
							}
						}							
						//取得版位的素材設定
						if(!isset($positionLimit[$positionID][$mOrder])){
							//取得版位類型資料
							$sql = 'SELECT 每則文字素材最大字數,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數,素材類型識別碼
								FROM 版位,版位素材類型 
								WHERE 版位.版位識別碼 =? AND 版位素材類型.素材順序 = ? AND 版位.上層版位識別碼 = 版位素材類型.版位識別碼';
							
							$positionLimit[$positionID][$mOrder]= $my->getResultArray($sql,'ii',$positionID,$mOrder)[0];
							//取得版位資料
							$sql = 'SELECT 每則文字素材最大字數,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數,素材類型識別碼
								FROM 版位素材類型 
								WHERE 版位識別碼 =? AND 版位素材類型.素材順序 = ?';
							
							$positionLimit[$positionID][$mOrder]= $my->getResultArray($sql,'ii',$positionID,$mOrder)[0];
						}
						//取得素材資料
						$sql ='SELECT 影片素材秒數,文字素材內容,圖片素材寬度,圖片素材高度 FROM 素材 WHERE 素材識別碼=? LIMIT 0,1';
						if(!$stmt=$my->prepare($sql)) {
							exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						if(!$stmt->bind_param('i',$material["素材識別碼"])){
							exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						if(!$stmt->execute()) {
							exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						if(!$res=$stmt->get_result()){
							exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
						}
						$orderMaterial=$res->fetch_array();
						$limit=$positionLimit[$positionID][$mOrder];
						//文字類型素材檢察
						if($limit["素材類型識別碼"]==1){
							if($limit["每則文字素材最大字數"]!=null&&mb_strlen($orderMaterial["文字素材內容"], "utf-8")>$limit["每則文字素材最大字數"])
								exit(json_encode(array("success"=>false,"message"=>'文字素材字數超過"'.$order['版位名稱'].'" 素材順序'.$mOrder.' 上限'),JSON_UNESCAPED_UNICODE));
						}
						
						//圖片類型素材檢察
						else if($limit["素材類型識別碼"]==2){
							if($limit["每則圖片素材最大寬度"]!=null && $orderMaterial["圖片素材寬度"]>$limit["每則圖片素材最大寬度"])
								exit(json_encode(array("success"=>false,"message"=>'圖片素材最大寬度超過"'.$order['版位名稱'].'" 素材順序'.$mOrder.' 上限'),JSON_UNESCAPED_UNICODE));
								
							else if($limit["每則圖片素材最大高度"]!=null && $orderMaterial["圖片素材高度"]>$limit["每則圖片素材最大高度"])
								exit(json_encode(array("success"=>false,"message"=>'圖片素材最大高度超過" '.$order['版位名稱'].'" 素材順序'.$mOrder.' 上限'),JSON_UNESCAPED_UNICODE));
						}
						
						//影片類型素材檢察
						else if($limit["素材類型識別碼"]==3){
							if($limit["每則影片素材最大秒數"]!=null && $orderMaterial["影片素材秒數"]>$limit["每則影片素材最大秒數"])
								exit(json_encode(array("success"=>false,"message"=>'影片素材最大秒數超過'.$order['版位名稱'].'素材順序'.$mOrder.' 上限'),JSON_UNESCAPED_UNICODE));
						}					
					}
				}//end of foreach position
			}//end of foreach order
			echo json_encode(array("success"=>true,"message"=>'success'),JSON_UNESCAPED_UNICODE);
	}
	
	//CSMS的素材規定檢查
	function check_materialCSMS(){
		global $logger,$my,$CSMSPTNAME;
		$orders = json_decode($_POST["orders"],true);
		$OrderIdsGourpByMaterial=[];//依照使用得素材群組託播單識別碼 ['素材識別碼'][區域]['託播單識別碼1',託播單識別碼2.....]
		$materialSetting=[];//各素材識別碼所使用的設定['素材識別碼'][區域]['版位類型'=>SEPG/BANNER,'託播單名稱'=>X,'可否點擊'=>X,'點擊後開啟類型'=>X,'點擊後開啟位址'=>X,'adType'=>X]
		$materialCheckResult=[];//記錄素材檢查結果['素材識別碼'][區域]['成功或失敗','失敗訊息']
		$successOrNotByIndex=[];//記錄各順序託播單檢查結果['託播單順序']['成功或失敗','失敗訊息']
		//檢查修改或新增的資料設定是否相同
		foreach($orders as $oindex=>$order){
			$positionID=$order['版位識別碼'];
			$ptName = $order['版位類型名稱'];
			$ptId = $order['版位類型識別碼'];
			$pName = $order['版位名稱'];
			$area = m_get_area($pName);
			$successOrNotByIndex[$oindex]=['success'=>true];
			//不屬於CSMS託播單，不須檢查
			if(!in_array($ptName,$CSMSPTNAME)){
				continue;
			}
			//開始檢察
			foreach($order['素材'] as $mOrder=>$material){
				if($material["素材識別碼"] == 0){
					continue;
				}
				$mid=$material["素材識別碼"];
				//記錄使用該素材託播單的ID與設定
				if(!isset($OrderIdsGourpByMaterial[$mid])){
					//第一次查訪該素材，記錄初始化
					$OrderIdsGourpByMaterial[$mid]=[];
					$materialSetting[$mid]=[];
					$materialCheckResult[$mid]=[];
				}
				
				if(!isset($OrderIdsGourpByMaterial[$mid][$area])){
					//第一次查訪該素材於該區域
					//記錄使用同素材的託播單識別碼
					$OrderIdsGourpByMaterial[$mid][$area]=[];
					if(isset($order['託播單識別碼']))
						$OrderIdsGourpByMaterial[$mid][$area][]=$order['託播單識別碼'];
					//預設為素材於該區域檢查成功
					$materialCheckResult[$mid][$area]=['success'=>true,'message'=>''];
					//記錄使用的設定
					$materialSetting[$mid][$area]=[];
					if($ptName == '頻道short EPG banner' ) 
						$materialSetting[$mid][$area]['版位類型']='SEPG';
					else if(in_array($ptName,['專區banner','首頁banner']))
						$materialSetting[$mid][$area]['版位類型']='BANNER';
					else
						$materialSetting[$mid][$area]['版位類型']='VOD';
					$materialSetting[$mid][$area]['託播單名稱']=$order['託播單名稱'];
					$materialSetting[$mid][$area]['可否點擊']=$material['可否點擊'];
					$materialSetting[$mid][$area]['點擊後開啟類型']=$material['點擊後開啟類型'];
					$materialSetting[$mid][$area]['點擊後開啟位址']=$material['點擊後開啟位址'];
					//取得版位參數名稱與順序
					$sql = 'SELECT 版位其他參數順序,版位其他參數名稱
							FROM 版位其他參數
							WHERE 版位識別碼 = ? AND (版位其他參數名稱 = "adType")';
					$result = $my->getResultArray($sql,'i',$ptId);
					//其他參數名稱與順序的對照
					$configIndex=array();
					if(isset($result))
					foreach($result as $row){
						$configIndex[$row['版位其他參數名稱']] = $row['版位其他參數順序'];
						$materialSetting[$mid][$area][$row['版位其他參數名稱']]=$order['其他參數'][$row['版位其他參數順序']];
					}
				}else{
					//記錄使用同素材的託播單識別碼
					if(isset($order['託播單識別碼']))
						$OrderIdsGourpByMaterial[$mid][$area][]=$order['託播單識別碼'];
					//未檢查出不同步的素材設定才須繼續檢查
					if($materialCheckResult[$mid][$area]['success']){					
						//檢查設定是否相同
						if($ptName == '頻道short EPG banner' && $materialSetting[$mid][$area]['版位類型']!='SEPG'){
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'頻道SEPG banner與其他banner不可共用素材。'];
						}
						else if(in_array($ptName,['專區banner','首頁banner']) && $materialSetting[$mid][$area]['版位類型']!='BANNER'){
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'頻道SEPG banner與其他banner不可共用素材。'];
						}
						
						if($materialSetting[$mid][$area]['託播單名稱']!=$order['託播單名稱']){
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區名稱不同步。'];
						}
						
						if($materialSetting[$mid][$area]['可否點擊']!=$material['可否點擊']){
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區[可否點擊]設定不同步。'];
						}

						
						if($materialSetting[$mid][$area]['點擊後開啟類型']!=$material['點擊後開啟類型']){
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區[點擊後開啟類型]設定不同步。'];
						}
						
						if($materialSetting[$mid][$area]['點擊後開啟位址']!=$material['點擊後開啟位址']){
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區[點擊後開啟位址]設定不同步。'];
						}
						
						foreach($configIndex as $cname=>$index){							
							if(strval($order['其他參數'][$index])!=strval($materialSetting[$mid][$area][$cname]))
								$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區['.$config['版位其他參數顯示名稱'].']設定不同步。'];
								
						}
						
					}
				}
				$successOrNotByIndex[$oindex]=&$materialCheckResult[$mid][$area];
			}
		}
		//檢查資料庫中使用相同託播單的資料是否吻合
		foreach($orders as $oindex=>$order){
			//若再前一步檢查以失敗的託播單，不須再次檢查
			if(!$successOrNotByIndex[$oindex]['success'])
				continue;
			//逐託播單素材檢查
			if(!isset($order['素材']))
				continue;
			$positionID=$order['版位識別碼'];
			$ptName = $order['版位類型名稱'];
			$ptId = $order['版位類型識別碼'];
			$pName = $order['版位名稱'];
			
			foreach($order['素材'] as $mOrder=>$material){
				if($material["素材識別碼"] == 0)
					continue;
				$mid = $material["素材識別碼"];
				//若為CSMS類型託播單，須比較使用同素材的託播單設定
				if(in_array($ptName,$CSMSPTNAME)){
					if(!isset($order['託播單CSMS群組識別碼']))
						$order['託播單CSMS群組識別碼'] = 0;
					$area = m_get_area($pName);
					//檢查使用該素材的託播單是否有相同的設定
					if(!isset($compareOrder)){
						$compareOrder=array();
					}
					//查詢資料庫中同區使用相同素材但不同CSMS群組的託播單
					if(!isset($compareOrder[$area])){
						//取得任一張使用該素材的託播單
						$sql = 'SELECT 託播單.託播單識別碼,託播單名稱,可否點擊,點擊後開啟類型,點擊後開啟位址,版位類型.版位名稱 AS 版位類型名稱
								FROM 託播單,託播單素材,版位, 版位 版位類型
								WHERE 託播單.託播單識別碼 = 託播單素材.託播單識別碼 AND 版位.版位識別碼 = 託播單.版位識別碼 AND 版位類型.版位識別碼 = 版位.上層版位識別碼 
								AND 版位.版位名稱 LIKE ? AND  素材識別碼 = ? AND 託播單CSMS群組識別碼 != ? AND 託播單狀態識別碼 IN (0,1,2,3,4)'
								.(count($OrderIdsGourpByMaterial[$mid][$area])>0?(' AND 託播單.託播單識別碼 NOT IN ('.implode(',',$OrderIdsGourpByMaterial[$mid][$area]).')'):'');
						$compareOrder[$area] = $my->getResultArray($sql,'sis','%'.$area,$mid,$order['託播單CSMS群組識別碼'])[0];
					}
					
					//資料庫中沒有使用同素材的託播單
					if(!isset($compareOrder[$area])){
						continue;
					}
					//檢查是否跨SEPG與其他banner使用素材
					else if(($ptName == '頻道short EPG banner' && in_array($compareOrder[$area]['版位類型名稱'],['專區banner','首頁banner'])) 
					|| ($compareOrder[$area]['版位類型名稱'] == '頻道short EPG banner' && in_array($ptName,['專區banner','首頁banner'])))
						//exit(json_encode(array("success"=>false,"message"=>'頻道SEPG banner與其他banner不可共用素材。'),JSON_UNESCAPED_UNICODE));
						$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'頻道SEPG banner與其他banner不可共用素材。'];
					//比較託播單設定是否一致
					if(strcmp($compareOrder[$area]['託播單名稱'],$order['託播單名稱']) !== 0)
						//exit(json_encode(array("success"=>false,"message"=>'使用相同素材的託播單名稱不同步。'),JSON_UNESCAPED_UNICODE));
						$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區名稱不同步。'];
		
					if($compareOrder[$area]['可否點擊'] != $material['可否點擊'])
						//exit(json_encode(array("success"=>false,"message"=>'使用相同素材的託播單[可否點擊]設定不同步。'),JSON_UNESCAPED_UNICODE));
						$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區[可否點擊]設定不同步。'];
						
					if(strcmp($compareOrder[$area]['點擊後開啟類型'],$material['點擊後開啟類型']) !== 0)
						//exit(json_encode(array("success"=>false,"message"=>'使用相同素材的託播單[點擊後開啟類型]設定不同步。'),JSON_UNESCAPED_UNICODE));
						$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區[點擊後開啟類型]設定不同步。'];
						
					if(strcmp($compareOrder[$area]['點擊後開啟位址'],$material['點擊後開啟位址']) !== 0)
						//exit(json_encode(array("success"=>false,"message"=>'使用相同素材的託播單[點擊後開啟位址]設定不同步。'),JSON_UNESCAPED_UNICODE));
						$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區[點擊後開啟位址]設定不同步。'];
						
					//比較其他參數設定是否一致
					//取得版位參數名稱與順序
					$sql = 'SELECT 版位其他參數順序,版位其他參數名稱
							FROM 版位其他參數
							WHERE 版位識別碼 = ? AND (版位其他參數名稱 = "adType")';
					$result = $my->getResultArray($sql,'i',$ptId);
					//其他參數名稱與順序的對照
					$configIndex=array();
					if(isset($result))
					foreach($result as $row){
						$configIndex[$row['版位其他參數名稱']] = $row['版位其他參數順序'];
					}
					if(!isset($compareOrderConfig)){
						$sql = 'SELECT 託播單其他參數值,版位其他參數名稱,版位其他參數顯示名稱
								FROM 託播單其他參數,版位其他參數
								WHERE 託播單識別碼 = ? AND 版位其他參數.版位識別碼 = ? AND 託播單其他參數順序 = 版位其他參數順序 AND 
								(版位其他參數名稱 = "adType")';
						$compareOrderConfig = $my->getResultArray($sql,'ii',$compareOrder[$area]['託播單識別碼'],$ptId);
					}
					if(count($compareOrderConfig)>0)
					foreach($compareOrderConfig as $config){
						if(!isset($configIndex[$config['版位其他參數名稱']]))
							//exit(json_encode(array("success"=>false,"message"=>'使用相同素材的託播單['.$config['版位其他參數顯示名稱'].']設定不同步。'),JSON_UNESCAPED_UNICODE));
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區['.$config['版位其他參數顯示名稱'].']設定不同步。'];
						
						$index = $configIndex[$config['版位其他參數名稱']];
						if(strval($order['其他參數'][$index])!=strval($config['託播單其他參數值']))
							//exit(json_encode(array("success"=>false,"message"=>'使用相同素材的託播單['.$config['版位其他參數顯示名稱'].']設定不同步。'),JSON_UNESCAPED_UNICODE));
							$materialCheckResult[$mid][$area]=['success'=>false,'message'=>'使用相同素材(素材識別碼'.$mid.')的託播單於'.$area.'區['.$config['版位其他參數顯示名稱'].']設定不同步。'];
							
					}
				}
			}
		}//end of foreach
		exit(json_encode(array("success"=>true,'result'=>$successOrNotByIndex),JSON_UNESCAPED_UNICODE));
	}
	
	/**取得版位區域**/
	function m_get_area($pn){
		require_once '../tool/phpExtendFunction.php';
		if(PHPExtendFunction::stringEndsWith($pn,'_北'))
		return  '北';
		else if(PHPExtendFunction::stringEndsWith($pn,'_中'))
		return  '中';
		else if(PHPExtendFunction::stringEndsWith($pn,'_南'))
		return  '南';
		else if(PHPExtendFunction::stringEndsWith($pn,'_IAP'))
		return  'IAP';
	}
	
	/**檢查群組託播單時段重覆**/
	function m_check_grup_overlap($orders){
		$timetable = array();//檢查有無投放託播單用 [群組識別碼][日期][小時]
		foreach($orders as $order){
			//沒有群組的託播單不必檢查
			if(!isset($order['託播單群組識別碼']) || $order['託播單群組識別碼'] == null)
				break;
				
			$sdate=date_create($order['廣告期間開始時間']);
			$edate=date_create($order['廣告期間結束時間']);
			$diff=date_diff($sdate,$edate)->format("%a");
			//逐日
			for($i =0;$i<=$diff;$i++){
				$dateP=date('Y-m-d', strtotime($order['廣告期間開始時間']. ' + '.$i.' days'));
				//逐小時
				$hours=explode(",",$order["廣告可被播出小時時段"]);
				foreach($hours as $h){
					if(!isset($timetable[$order['託播單群組識別碼']][$dateP][$h]))
					@$timetable[$order['託播單群組識別碼']][$dateP][$h] = true;
					else{
						$timetable[$order['託播單群組識別碼']][$dateP][$h] = false;
					}
				}
			}
		}
		$failgid = array();
		foreach($timetable as $gid => $v1){
			$false = false;
			foreach($v1 as $date => $v2){
				foreach($v2 as $h => $v3){
					if(!$v3)
						$false = true;
				}
			}
			if($false)
				$failgid[] = $gid;
		}
		if(sizeof($failgid)==0)
		return array('success'=>true);
		else
		return array('success'=>false,'message'=>"託播單群組".implode(',',$failgid)."的託播單修改後播出時間重疊\n");
	}
	

	//依照素材類型取得素材
	function get_material(){
		global $logger, $my;
			//取得素材資料
		if(!isset($_POST["素材群組識別碼"])||$_POST["素材群組識別碼"]==0)
			$_POST["素材群組識別碼"]='%';
		if(isset($_POST["版位類型識別碼"])){
			$sql = 'SELECT 素材名稱, 素材識別碼, 素材有效開始時間, 素材有效結束時間,素材.影片畫質識別碼 AS 素材畫質,版位素材類型.影片畫質識別碼 AS 版位畫質,素材.素材類型識別碼
			FROM 版位素材類型,素材 
			WHERE 版位素材類型.素材順序=? AND 版位素材類型.版位識別碼 LIKE ? AND 素材.素材類型識別碼=版位素材類型.素材類型識別碼
			AND 素材.素材群組識別碼 LIKE ?
			ORDER BY 素材識別碼 DESC';
		
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}

			if(!$stmt->bind_param('iis',$_POST["素材順序"],$_POST["版位類型識別碼"],$_POST["素材群組識別碼"])){
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$dateTime =  date("Y-m-d H:i:s");
			$material=array();
			while($row=$res->fetch_assoc()){
				/*if($row['素材有效開始時間']!=null)
					if($row['素材有效開始時間']>$dateTime)
						continue;*/
				if($row['素材有效結束時間']!=null)
					if($row['素材有效結束時間']<$dateTime)
						continue;
				if($row['素材類型識別碼']==3 && $row['素材畫質']!=$row['版位畫質'])
					continue;
					
				array_push($material,$row);
			}
			echo json_encode(array("success"=>true,"material"=>$material),JSON_UNESCAPED_UNICODE);
		}
		else{
			$sql = 'SELECT 素材名稱, 素材識別碼, 素材有效開始時間, 素材有效結束時間 FROM 素材
			WHERE 素材.素材群組識別碼 LIKE ? 
			ORDER BY 素材識別碼 DESC';
			if(!$result=$my->getResultArray($sql,'s',$_POST["素材群組識別碼"])) $result=array();
			$material=array();
			foreach($result as $row){
				array_push($material,$row);
			}
			echo json_encode(array("success"=>true,"material"=>$material),JSON_UNESCAPED_UNICODE);
		}
	}
	
	//取得版位素材與參數設定
	function get_position_config(){
		global $logger, $my;
		$config = array();
		$material = array();
		//版位類型設定
		$sql = 'SELECT 版位其他參數順序,版位其他參數名稱,版位其他參數預設值,版位其他參數型態識別碼,版位其他參數是否必填,版位其他參數顯示名稱,參數型態顯示名稱
			FROM 版位其他參數,版位,參數型態
			WHERE 是否版位專用 = 0 AND 版位.版位識別碼 = ? AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 參數型態.參數型態識別碼 = 版位其他參數.版位其他參數型態識別碼
			';
		if(!$res = $my->getResultArray($sql,'i',$_POST['版位識別碼'])) 
			$res= array();
		
		foreach($res as $row){
			$config[$row['版位其他參數順序']]=$row;
		}
		//版位參數
		$sql = 'SELECT 版位其他參數順序,版位其他參數名稱,版位其他參數預設值,版位其他參數型態識別碼,版位其他參數是否必填,版位其他參數顯示名稱,參數型態顯示名稱
			FROM 版位其他參數,參數型態
			WHERE 是否版位專用 = 0 AND 版位識別碼 = ? AND 參數型態.參數型態識別碼 = 版位其他參數.版位其他參數型態識別碼
			';
		if(!$res = $my->getResultArray($sql,'i',$_POST['版位識別碼'])) 
			$res= array();
		
		foreach($res as $row){
			$config[$row['版位其他參數順序']]=$row;
		}
		
		//版位類型素材
		$sql = 'SELECT 素材順序,版位素材類型.素材類型識別碼,託播單素材是否必填,影片畫質名稱,素材類型名稱
			FROM 版位,素材類型,版位素材類型 LEFT JOIN 影片畫質 ON 版位素材類型.影片畫質識別碼 = 影片畫質.影片畫質識別碼
			WHERE  版位.版位識別碼 = ? AND 版位素材類型.版位識別碼 = 版位.上層版位識別碼  AND 版位素材類型.素材類型識別碼 = 素材類型.素材類型識別碼
			';
		if(!$res = $my->getResultArray($sql,'i',$_POST['版位識別碼'])) 
			$res= array();
		
		foreach($res as $row){
			$material[$row['素材順序']]=$row;
		}
		
		//版位素材
		$sql = 'SELECT 素材順序,版位素材類型.素材類型識別碼,託播單素材是否必填,影片畫質名稱,素材類型名稱
			FROM 版位,素材類型,版位素材類型 LEFT JOIN 影片畫質 ON 版位素材類型.影片畫質識別碼 = 影片畫質.影片畫質識別碼
			WHERE  版位.版位識別碼 = ? AND 版位素材類型.版位識別碼 = 版位.版位識別碼  AND 版位素材類型.素材類型識別碼 = 素材類型.素材類型識別碼
			';
		if(!$res = $my->getResultArray($sql,'i',$_POST['版位識別碼'])) 
			$res= array();
		
		foreach($res as $row){
			$material[$row['素材順序']]=$row;
		}
			
		exit(json_encode(array('success'=>true , '其他參數設定'=>$config,'版位素材設定'=>$material),JSON_UNESCAPED_UNICODE));
	}
	
	//批次取得版位素材與參數設定
	function get_position_config_batch(){
		global $logger, $my;
		$a_params = array();
		$n = count($_POST['orderIds']);
		$sql ='SELECT 版位類型.版位識別碼,版位.版位名稱
			FROM 託播單,版位, 版位 版位類型
			WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼 ';
			
		$arrayTemp = array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= '託播單識別碼=?';
		}	
		$arrayTemp=implode(" OR ", $arrayTemp);
		if($arrayTemp!='')
		$sql.=' AND('.$arrayTemp.')';
		$sql.=' GROUP BY 版位名稱';
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
		
		$positionTIds = array();//記錄版類型識別碼位用
		$positionMaterial = array();//記錄版類型素材參數
		$positionConfig = array();//記錄版類型參數
		$positionNames = array();//記錄版位名稱
		$res = $stmt->get_result();
		while($row = $res->fetch_assoc()){
			if(!in_array($row['版位識別碼'],$positionTIds))
				array_push($positionTIds, $row['版位識別碼']);
			array_push($positionNames, $row['版位名稱']);
		}
		
		//取的版位素材參數
		$sql ='SELECT 版位素材類型.版位識別碼,素材順序,版位素材類型.素材類型識別碼,託播單素材是否必填,影片畫質名稱,素材類型名稱
			FROM 素材類型,版位素材類型 LEFT JOIN 影片畫質 ON 版位素材類型.影片畫質識別碼 = 影片畫質.影片畫質識別碼
			WHERE 版位素材類型.素材類型識別碼 = 素材類型.素材類型識別碼
		';
		$n = count($positionTIds);
		$arrayTemp = array();$a_params=array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= '版位素材類型.版位識別碼=?';
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
			$a_params[] = &$positionTIds[$i];
		}
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		 
		$stmt->execute();
		
		$res = $stmt->get_result();

		while($row = $res->fetch_assoc()){
			$positionMaterial[$row['版位識別碼']][$row['素材順序']]=$row;
		}
		//逐一比較比較相同的素材設定
		$pMaterials = $positionMaterial[$positionTIds[0]];
		$keys=array_keys($pMaterials);
		foreach($keys as $mindex){
			$material = $pMaterials[$mindex];
			$same = true;
			//逐版位
			for($i = 1; $i < $n; $i++) {
				//逐該版位的素材
				//該版位沒有此素材順序設定
				if(!isset($positionMaterial[$positionTIds[$i]][$mindex])){
					$same = false;
					break;
				}
				else{
					$compareM = $positionMaterial[$positionTIds[$i]][$mindex];
					if($material['素材類型識別碼']!=$compareM['素材類型識別碼']){
						$same = false;
								break;
					}else{
						//若為影片,比較畫質
						if($material['素材類型名稱']=='影片'){
							if($material['影片畫質名稱']!=$compareM['影片畫質名稱']){
								$same = false;
								break;
							}
						}
					}
				}				
			}
			//沒有相同素材設定,移除
			if(!$same)
				unset($pMaterials[$mindex]);
		}
		
		//取的版位其他參數
		$sql ='SELECT 版位其他參數順序,版位其他參數名稱,版位其他參數預設值,版位其他參數型態識別碼,版位其他參數是否必填,版位其他參數顯示名稱,參數型態顯示名稱,版位其他參數.版位識別碼
			FROM 版位其他參數,參數型態
			WHERE 是否版位專用 = 0 AND 參數型態.參數型態識別碼 = 版位其他參數.版位其他參數型態識別碼
			';
		$n = count($positionTIds);
		$arrayTemp = array();$a_params=array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= '版位其他參數.版位識別碼=?';
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
			$a_params[] = &$positionTIds[$i];
		}
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		 
		$stmt->execute();
		
		$res = $stmt->get_result();

		while($row = $res->fetch_assoc()){
			$positionConfig[$row['版位識別碼']][$row['版位其他參數順序']]=$row;
		}
		//逐一比較比較相同的設定
		if(isset($positionConfig[$positionTIds[0]]))
			$pConfig = $positionConfig[$positionTIds[0]];
		else
			$pConfig = array();
		$keys=array_keys($pConfig);
		foreach($keys as $index){
			$conifg = $pConfig[$index];
			$same = true;
			//逐版位
			for($i = 1; $i < $n; $i++) {
				//逐該版位的參數
				//該版位沒有此參數順序設定
				if(!isset($positionConfig[$positionTIds[$i]][$index])){
					$same = false;
					break;
				}
				else{
					$compareM = $positionConfig[$positionTIds[$i]][$index];
					if($conifg['版位其他參數名稱']!=$compareM['版位其他參數名稱']||$conifg['版位其他參數型態識別碼']!=$compareM['版位其他參數型態識別碼']){
						$same = false;
								break;
					}
				}				
			}
			//沒有相同素材設定,移除
			if(!$same)
				unset($pConfig[$index]);
		}
		
		//取得各託播單日期
		$sql ='SELECT 託播單識別碼,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段
			FROM 託播單
			WHERE 託播單識別碼 in(
		';
		$n = count($_POST['orderIds']);
		$arrayTemp = array();$a_params=array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]='?';
		}	
		$sql.=implode(" , ", $arrayTemp).')';
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
		$res = $stmt->get_result();
		$dateArray=array();
		while($row = $res->fetch_assoc()){
			$dateArray[$row['託播單識別碼']]=array('start'=>$row['廣告期間開始時間'],'end'=>$row['廣告期間結束時間']);
			if(!isset($timeArray))
				$timeArray=explode(',',$row['廣告可被播出小時時段']);
			else
				$timeArray=array_intersect($timeArray,explode(',',$row['廣告可被播出小時時段']));
		}
		exit(json_encode(array('success'=>true , '其他參數設定'=>$pConfig,'版位素材設定'=>$pMaterials,'日期'=>$dateArray
		,'時段'=>implode(',',$timeArray),'pNames'=>$positionNames),JSON_UNESCAPED_UNICODE));
	}
	
	function playing_times_percentage(){
		global $logger, $my;
		//曝光數是否一致
		$success = true;
		//記錄各版位曝光數用
		$exposureIndex = array();
		//查詢版位類型預設曝光數
		$defatultExposure = array_fill(0,24,1);
		$sql = 'SELECT * FROM 曝光數,版位 WHERE 版位.版位識別碼 = ? AND 版位.上層版位識別碼 = 曝光數.版位識別碼';
		$res = $my->getResultArray($sql,'i',$_POST['orders'][0]['版位識別碼']);
		if(count($res)!=0){
			$row = $res[0];
			$defatultExposure = array($row['曝光數0'],$row['曝光數1'],$row['曝光數2'],$row['曝光數3'],$row['曝光數4'],$row['曝光數5'],$row['曝光數6'],$row['曝光數7'],$row['曝光數8']
			,$row['曝光數9'],$row['曝光數10'],$row['曝光數11'],$row['曝光數12'],$row['曝光數13'],$row['曝光數14'],$row['曝光數15'],$row['曝光數16'],$row['曝光數17'],$row['曝光數18']
			,$row['曝光數19'],$row['曝光數20'],$row['曝光數21'],$row['曝光數22'],$row['曝光數23']);
		}
		//查詢版位曝光數
		$sql = 'SELECT * FROM 曝光數 WHERE ';
		$a_params = array();
		$n = count($_POST['orders']);
		//完成query
		$arrayTemp = array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= '版位識別碼=?';
		}	
		$arrayTemp=implode(" OR ", $arrayTemp);
		if($arrayTemp!='')
		$sql.=$arrayTemp;
		//設定參數類型
		$param_type = '';
		for($i = 0; $i < $n; $i++) {
			$param_type .='i';
		}	 
		//設定參數
		$a_params[] = &$param_type;
		for($i = 0; $i < $n; $i++) {
			$a_params[] = &$_POST['orders'][$i]['版位識別碼'];
			//初始版位曝光數記錄
			$exposureIndex[$_POST['orders'][$i]['版位識別碼']] = array();
		}
		//執行SQL
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		$stmt->execute();		
		$res = $stmt->get_result();
		//整理結果
		//覆寫版位曝光數
		while($row = $res->fetch_assoc()){
			$exposureIndex[$row['版位識別碼']][$row['星期幾']] = array($row['曝光數0'],$row['曝光數1'],$row['曝光數2'],$row['曝光數3'],$row['曝光數4'],$row['曝光數5'],$row['曝光數6'],$row['曝光數7'],$row['曝光數8']
			,$row['曝光數9'],$row['曝光數10'],$row['曝光數11'],$row['曝光數12'],$row['曝光數13'],$row['曝光數14'],$row['曝光數15'],$row['曝光數16'],$row['曝光數17'],$row['曝光數18']
			,$row['曝光數19'],$row['曝光數20'],$row['曝光數21'],$row['曝光數22'],$row['曝光數23']);
		}
		//檢查所有版位是否全有(全沒有)曝光數設定，否則離開
		foreach($exposureIndex as &$exposure){
			//如果曝光數array的count沒有全一至，代表沒有全部都有設定/沒有設定曝光數
			if(!isset($lastCount))$lastCount = count($exposure);
			if($lastCount != count($exposure))
				$success = false;
			$lastCount = count($exposure);
			if($lastCount==0)
				$exposure = array_fill(0,7,$defatultExposure);
		}
		//查詢額外百分比 全體投放次數上限外加比例
		//版位類型預設值
		$sql = 'SELECT 版位其他參數預設值 FROM 版位其他參數,版位 WHERE 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = "全體投放次數上限外加比例" AND 版位.版位識別碼 = ?';
		$res = $my->getResultArray($sql,'i',$_POST['orders'][0]['版位識別碼']);
		$defaultPercentage = $res[0]['版位其他參數預設值'];
		//版位預設值
		$sql = 'SELECT 版位其他參數預設值,版位識別碼 FROM 版位其他參數 WHERE 版位其他參數名稱 = "全體投放次數上限外加比例"';
		$a_params = array();
		$n = count($_POST['orders']);
		//完成query
		$arrayTemp = array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= '版位識別碼=?';
		}	
		$arrayTemp=implode(" OR ", $arrayTemp);
		if($arrayTemp!='')
		$sql.=' AND ('.$arrayTemp.')';
		//設定參數類型
		$param_type = '';
		for($i = 0; $i < $n; $i++) {
			$param_type .='i';
		}	 
		//設定參數
		$a_params[] = &$param_type;
		for($i = 0; $i < $n; $i++) {
			$a_params[] = &$_POST['orders'][$i]['版位識別碼'];
		}
		//執行SQL
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		$stmt->execute();		
		$res = $stmt->get_result();
		//整理結果
		$percentageIndex = array();
		while($row = $res->fetch_assoc()){
			$percentageIndex[$row['版位識別碼']] = $row['版位其他參數預設值'];
		}
		
		//計算百分比
		$feedback = array();
		$all = 0;
		foreach($_POST['orders'] as $order){
			$hours  = explode(',',$order['廣告可被播出小時時段']);
			$stt = explode(':',explode(' ',$order['廣告期間開始時間'])[1]);
			$edt = explode(':',explode(' ',$order['廣告期間結束時間'])[1]);
			$tempAll = 0;
			//跨天
			if($hours[0] == '0' && end($hours) == '23'){
				$nextDays = ($order['星期幾']==6)?0:$order['星期幾']+1;
				$tempAll += $exposureIndex[$order['版位識別碼']][$nextDays][0];
				$i = 1;
				for(; $i < count($hours) ; $i++){
					$fix = 1;
					//開始與結束不滿一小時,計算比例
					if(intval($hours[$i]) == intval($stt[0]) && ($stt[1] != '00' || $stt[2] != '00')){
						$seconds = intval($stt[1])*60+intval($stt[2]);
						$fix = (3600-$seconds)/3600;
					}
					else if(intval($hours[$i]) == intval($edt[0]) && ($edt[1] != '59' || $edt[2] != '59')){
						$fix = $seconds/3600;
					}
					if($hours[$i] == $hours[$i-1]+1)
						$tempAll += $exposureIndex[$order['版位識別碼']][$nextDays][intval($hours[$i])]*$fix;
					else 
						break;
				}
				for(; $i < count($hours) ; $i++){
					$fix = 1;
					//開始與結束不滿一小時,計算比例
					if(intval($hours[$i]) == intval($stt[0]) && ($stt[1] != '00' || $stt[2] != '00')){
						$seconds = intval($stt[1])*60+intval($stt[2]);
						$fix = (3600-$seconds)/3600;
					}
					else if(intval($hours[$i]) == intval($edt[0]) && ($edt[1] != '59' || $edt[2] != '59')){
						$fix = $seconds/3600;
					}
					$tempAll += $exposureIndex[$order['版位識別碼']][$order['星期幾']][intval($hours[$i])]*$fix;
				}
			}
			//沒有跨天
			else{
				foreach($hours as $hour){
					$fix = 1;
					//開始與結束不滿一小時,計算比例
					if(intval($hour) == intval($stt[0]) && ($stt[1] != '00' || $stt[2] != '00')){
						$seconds = intval($stt[1])*60+intval($stt[2]);
						$fix = (3600-$seconds)/3600;
					}
					else if(intval($hour) == intval($edt[0]) && ($edt[1] != '59' || $edt[2] != '59')){
						$seconds = intval($edt[1])*60+intval($edt[2]);
						$fix = $seconds/3600;
					}
					$tempAll += $exposureIndex[$order['版位識別碼']][$order['星期幾']][intval($hour)]*$fix;
				}
			}
			$feedback[]= $tempAll;
			$all += $tempAll;
		}
		//各託播單的額外百分比
		$extraPercentages = array();
		for($i = 0;$i<count($feedback);$i++){
			$order = $_POST['orders'][$i];
			$extraPercentage = isset($percentageIndex[$order['版位識別碼']])?$percentageIndex[$order['版位識別碼']]:$defaultPercentage;//額外補正的曝光數百分比
			if($extraPercentage==null) $extraPercentage=0;
			$feedback[$i] =  ($feedback[$i]/$all)*(1+$extraPercentage);
			$extraPercentages[$i] = $extraPercentage;
		}
		if(!$success)
			exit(json_encode(array('success'=>false,'message'=>'沒有預設曝光數，所有選擇版位的曝光數資料狀態須一致(都有或是都沒有曝光數資料)才可自動分配投放上限','percentage'=>$extraPercentages),JSON_UNESCAPED_UNICODE));
			
		exit(json_encode(array('success'=>true,'data'=>$feedback,'percentage'=>$extraPercentages),JSON_UNESCAPED_UNICODE));
	}
	function get_extra_eprosure_percentage(){
		global $logger, $my;
		//版位類型預設值
		$sql = 'SELECT 版位其他參數預設值 FROM 版位其他參數,版位 WHERE 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = "全體投放次數上限外加比例" AND 版位.版位識別碼 = ?';
		$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']);
		$defaultPercentage = isset($res[0]['版位其他參數預設值'])?$res[0]['版位其他參數預設值']:0;
		//版位預設值
		$sql = 'SELECT 版位其他參數預設值 FROM 版位其他參數 WHERE 版位其他參數名稱 = "全體投放次數上限外加比例" AND 版位識別碼 = ?';
		$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']);
		if(isset($res[0]['版位其他參數預設值']))
			exit(json_encode($res[0]['版位其他參數預設值']));
		else
			exit(json_encode($defaultPercentage));
	}
	
	$my->close();
	exit();
?>
	