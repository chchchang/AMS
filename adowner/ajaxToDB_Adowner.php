<?php
	include('../tool/auth/authAJAX.php');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit(json_encode(array('無法連線到資料庫，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit(json_encode(array('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	
	
	if ( isset($_POST['query']) && $_POST['query'] != '' )
		do_query();
	
	else if( isset($_POST['action']) && $_POST['action'] != '' ){
		switch($_POST['action']){
			case "newOwenr":
				new_owner();
				break;
			case "getCount":
				get_Count();
				break;
			case "廣告主資料表":
				get_adOnwerInfo();
				break;
			case "委刊單下託播單資訊":
				get_order_by_orderList();
				break;
			case "updateOwenr":
				update_owner();
				break;
			case "訂單資訊":
				order_info();
				break;
			case "委刊單資料表":
				get_orderList_by_adOwner();
				break;
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
		global $logger, $my;
		$sql = $_POST["query"];
		$result =$my->query($sql);
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
	
	/**取得廣到主資料**/
	function get_adOnwerInfo(){
		global $logger, $my;
		$sort = "" ;
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
		if(isset($_POST['SHOWHIDE']))
		$sql="SELECT 廣告主識別碼,廣告主名稱,頻道商名稱,承銷商名稱,DISABLE_TIME AS 狀態
			FROM 廣告主 WHERE DELETED_TIME IS null AND (".$_POST["WHERE"].") ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];
		else
		$sql="SELECT 廣告主識別碼,廣告主名稱,頻道商名稱,承銷商名稱
			FROM 廣告主 WHERE DELETED_TIME IS null AND DISABLE_TIME IS null AND (".$_POST["WHERE"].") ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];
	
		if(!$result =$my->query($sql)){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$a=array();	
		while($row = $result->fetch_assoc()){
			if(isset($_POST['SHOWHIDE']))
				$row['狀態']=($row['狀態']==null)?'顯示':'隱藏';
			array_push($a,$row);
		}
		echo json_encode($a,JSON_UNESCAPED_UNICODE);
	}
	
	
	/**依委刊單取得託播單資料**/
	function get_order_by_orderList(){
		global $logger, $my;
		$sort = "" ;
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
		$sql="SELECT 託播單識別碼, 託播單名稱,託播單狀態名稱 AS 託播單狀態,託播單.CREATED_TIME AS CREATED_TIME,託播單.LAST_UPDATE_TIME AS LAST_UPDATE_TIME
		FROM 託播單,託播單狀態 WHERE 託播單狀態.託播單狀態識別碼 = 託播單.託播單狀態識別碼 AND 委刊單識別碼=".$_POST["委刊單識別碼"]
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
	
	/**新增廣告主**/
	function new_owner(){
		global $logger, $my;
		
		/*$sql="SELECT COUNT(*) FROM 廣告主 WHERE 廣告主統一編號=? AND 頻道商統一編號=? AND 承銷商統一編號=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('sss',$_POST["廣告主統一編號"],$_POST["頻道商統一編號"],$_POST["承銷商統一編號"])) {
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
				"message" => urlencode("廣告主資料已存在"),
			);

			echo urldecode(json_encode($feedback));
			return 0;
		}*/
		
		
		$sql="INSERT INTO 廣告主 (廣告主名稱,廣告主統一編號,廣告主地址,廣告主聯絡人姓名,廣告主聯絡人電話"
		.",頻道商名稱,頻道商統一編號,頻道商地址,頻道商聯絡人姓名,頻道商聯絡人電話"
		.",承銷商名稱,承銷商統一編號,承銷商地址,承銷商聯絡人姓名,承銷商聯絡人電話,CREATED_PEOPLE)"
		." VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('sssssssssssssssi',$_POST["廣告主名稱"],$_POST["廣告主統一編號"],$_POST["廣告主地址"],$_POST["廣告主聯絡人姓名"],$_POST["廣告主聯絡人電話"]
			,$_POST["頻道商名稱"],$_POST["頻道商統一編號"],$_POST["頻道商地址"],$_POST["頻道商聯絡人姓名"],$_POST["頻道商聯絡人電話"]
			,$_POST["承銷商名稱"],$_POST["承銷商統一編號"],$_POST["承銷商地址"],$_POST["承銷商聯絡人姓名"],$_POST["承銷商聯絡人電話"],$_POST["UID"])) {
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
				"message" => urlencode("成功新增廣告主資料!"),
				"insert_id" =>$stmt->insert_id
			);
			$logger->info('使用者識別碼'.$_POST["UID"].' 新增廣告主:識別碼'.$stmt->insert_id);
			
			echo urldecode(json_encode($feedback));
		}
		else{
			$feedback = array(
				"success" => false,
				"message" => urlencode("新增廣告主資料失敗"),
			);

			echo urldecode(json_encode($feedback));
		}
	}
	
	/**修改廣告主**/
	function update_owner(){
		global $logger, $my;
		
		/*$sql="SELECT COUNT(*) FROM 廣告主 WHERE 廣告主統一編號=? AND 頻道商統一編號=? AND 承銷商統一編號=? AND 廣告主識別碼!=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('sssi',$_POST["廣告主統一編號"],$_POST["頻道商統一編號"],$_POST["承銷商統一編號"],$_POST["廣告主識別碼"])) {
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
				"message" => urlencode("存在重複的廣告主資料"),
			);

			echo urldecode(json_encode($feedback));
			return 0;
		}*/
		
		
		$sql="UPDATE 廣告主 SET 廣告主名稱 =?,廣告主統一編號 =?,廣告主地址 =?,廣告主聯絡人姓名 =?,廣告主聯絡人電話 =?
			,頻道商名稱 =?,頻道商統一編號 =?,頻道商地址 =?,頻道商聯絡人姓名 =?,頻道商聯絡人電話 =?
			,承銷商名稱 =?,承銷商統一編號 =?,承銷商地址 =?,承銷商聯絡人姓名 =?,承銷商聯絡人電話 =?
			,LAST_UPDATE_PEOPLE=?, LAST_UPDATE_TIME = CURRENT_TIMESTAMP WHERE 廣告主識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('sssssssssssssssii',$_POST["廣告主名稱"],$_POST["廣告主統一編號"],$_POST["廣告主地址"],$_POST["廣告主聯絡人姓名"],$_POST["廣告主聯絡人電話"]
								,$_POST["頻道商名稱"],$_POST["頻道商統一編號"],$_POST["頻道商地址"],$_POST["頻道商聯絡人姓名"],$_POST["頻道商聯絡人電話"]
								,$_POST["承銷商名稱"],$_POST["承銷商統一編號"],$_POST["承銷商地址"],$_POST["承銷商聯絡人姓名"],$_POST["承銷商聯絡人電話"]
								,$_POST["UID"],$_POST["廣告主識別碼"])){
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$feedback = array(
			"success" => true,
			"message" => urlencode("成功修改廣告主資料"),
		);
		$logger->info('使用者代碼:'.$_POST["UID"].' 修改廣告主:'.$_POST["廣告主識別碼"]);
		echo urldecode(json_encode($feedback));

	}
	
	/**訂單資訊**/
	function order_info(){
		global $logger, $my;
		
		$sql= "SELECT 廣告主名稱, 委刊單名稱, 版位類型名稱, 版位名稱, 託播單名稱, 託播單說明, 廣告期間開始時間, 廣告期間結束時間, 廣告可被播出小時時段, 託播單狀態識別碼, 版位投放設定資料表名稱"
		." FROM 廣告主 A, 委刊單 OL, 託播單 O, 版位總表 BL,版位類型 BT"
		." WHERE O.託播單識別碼=? AND O.委刊單識別碼 = OL.委刊單識別碼 AND OL.廣告主識別碼=A.廣告主識別碼 AND O.版位總表識別碼=BL.版位總表識別碼 AND BL.版位類型識別碼=BT.版位類型識別碼";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
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
		
		while($row = $res->fetch_array()){
			$push_a = array(
				urlencode("廣告主名稱")=>urlencode($row["廣告主名稱"]),
				urlencode("委刊單名稱")=>urlencode($row["委刊單名稱"]),
				urlencode("版位類型名稱")=>urlencode($row["版位類型名稱"]),
				urlencode("版位名稱")=>urlencode($row["版位名稱"]),
				urlencode("託播單名稱")=>urlencode($row["託播單名稱"]),
				urlencode("託播單說明")=>urlencode($row["託播單說明"]),
				urlencode("廣告期間開始時間")=>urlencode($row["廣告期間開始時間"]),
				urlencode("廣告期間結束時間")=>urlencode($row["廣告期間結束時間"]),
				urlencode("廣告可被播出小時時段")=>urlencode($row["廣告可被播出小時時段"]),
				urlencode("託播單狀態")=>urlencode($row["託播單狀態識別碼"])
			);
			
			
			$query2 = "SELECT 素材類型, 素材名稱, 素材說明, 素材位址 FROM 素材 M, 素材類型 MT, `".$row["版位投放設定資料表名稱"]."` BC"
			." WHERE BC.素材識別碼 = M.素材識別碼 AND M.素材類型識別碼 = MT.素材類型識別碼";
			
			if(!$result2 =$my->query($query2)) {
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			
			$row2 = $result2->fetch_array();
			
			$push_a[urlencode("素材類型")]=urlencode($row2["素材類型"]);
			$push_a[urlencode("素材名稱")]=urlencode($row2["素材名稱"]);
			$push_a[urlencode("素材說明")]=urlencode($row2["素材說明"]);
			$push_a[urlencode("素材位址")]=urlencode($row2["素材位址"]);
			
			echo urldecode(json_encode($push_a));
		}
		
	}
	
	
	/**取得委刊單資料**/
	function get_orderList_by_adOwner(){
		global $logger, $my;
		$sort = "" ;
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
		if(isset($_POST["WHERE"]))
			$where = "WHERE 廣告主識別碼 =".$_POST["廣告主識別碼"]." AND ( ".$_POST["WHERE"]." )";
		else
			$where = "WHERE 廣告主識別碼 =".$_POST["廣告主識別碼"];
			
		$query = "SELECT 委刊單識別碼,委刊單編號,委刊單名稱,CREATED_TIME,LAST_UPDATE_TIME FROM 委刊單 ".$where
		." ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];
		
		if(!$result =$my->query($query)) {
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$a = array();
		while($row = $result->fetch_array()){
			$push_a[urlencode("委刊單識別碼")]=urlencode($row["委刊單識別碼"]);
			$push_a[urlencode("委刊單編號")]=urlencode(($row["委刊單編號"]==null)?'':$row["委刊單編號"]);
			$push_a[urlencode("委刊單名稱")]=urlencode($row["委刊單名稱"]);
			$push_a[urlencode("CREATED_TIME")]=urlencode($row["CREATED_TIME"]);
			$push_a[urlencode("LAST_UPDATE_TIME")]=urlencode($row["LAST_UPDATE_TIME"]);
			
			$query = "SELECT 
					COUNT(*),
					SUM(CASE 託播單狀態識別碼 WHEN 0 THEN 1 ELSE 0 END) 預約,
					SUM(CASE 託播單狀態識別碼 WHEN 1 THEN 1 ELSE 0 END) 確定, 
					SUM(CASE 託播單狀態識別碼 WHEN 2 THEN 1 ELSE 0 END) 送出 ,
					SUM(CASE 託播單狀態識別碼 WHEN 3 THEN 1 ELSE 0 END) 逾期,
					SUM(CASE 託播單狀態識別碼 WHEN 4 THEN 1 ELSE 0 END) 待處理檔案 
					FROM 託播單 WHERE 委刊單識別碼 =".$row["委刊單識別碼"];
		
			if(!$result2=$my->query($query)) {
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row2 = $result2->fetch_array();
			if($row2['COUNT(*)']==0)
				$push_a[urlencode("託播單狀態")] = '未建立';
			else
				$push_a[urlencode("託播單狀態")] = '預約:'.$row2['預約'].' 確定:'.$row2['確定'].' 送出:'.$row2['送出'].' 逾期:'.$row2['逾期'].' 待處理檔案:'.$row2['待處理檔案'];
			array_push($a,$push_a);
		}
		echo urldecode(json_encode($a));	
	}
?>