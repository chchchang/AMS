<?php
	include('../../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
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
			case "新增代理商":
				new_agent();
				break;
			case "取得代理商資料":
				get_agentInfo();
				break;
			case "更新代理商":
				update_angent();
				break;
			case "取得代理商資料表":
				get_angentDataGrid();
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
	
	
	/**取得count**/
	function get_Count(){
		global $logger, $my;
		
		if(!$my->set_charset('utf8')) {
			$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("message"=>'無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}

		$sql = "SELECT COUNT(*) FROM ".$_POST["TABLE"]." WHERE ".$_POST["WHERE"];
		if(!$res=$my->query($sql)) {
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("message"=>'無法取得結果集，請聯絡系統管理員！',"query"=>$sql),JSON_UNESCAPED_UNICODE));
		}
				
		$a=array();	
		while($row = $res->fetch_array()){
			$row = array_map('urlencode', $row);
			array_push($a,$row);
		}
		echo urldecode(json_encode($a));
	}
	
	/**取得代理商資料**/
	function get_agentInfo(){
		global $logger, $my;
		
		$sql="SELECT 代理商識別碼,代理商名稱,代理商統一編號,DISABLE_TIME AS 狀態
			FROM 代理商 WHERE 代理商識別碼 = ?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["代理商識別碼"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()) {
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		$row = $res->fetch_assoc();
		$row["success"] =true;
		echo json_encode($row,JSON_UNESCAPED_UNICODE);
	}
	

	
	/**新增代理商**/
	function new_agent(){
		global $logger, $my;
		
				
		$sql="INSERT INTO 代理商 (代理商名稱,代理商統一編號,CREATED_PEOPLE)"
		." VALUES (?,?,?)";
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('ssi',$_POST["代理商名稱"],$_POST["代理商統一編號"],$_SESSION['AMS']['使用者識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if($stmt->affected_rows>0){
		
			$feedback = array(
				"success" => true,
				"message" => urlencode("成功新增代理商資料!"),
				"insert_id" =>$stmt->insert_id
			);
			$logger->info('使用者識別碼'.$_SESSION['AMS']['使用者識別碼'].' 新增代理商:識別碼'.$stmt->insert_id);
			
			echo urldecode(json_encode($feedback));
		}
		else{
			$feedback = array(
				"success" => false,
				"message" => urlencode("新增代理商資料失敗"),
			);

			echo urldecode(json_encode($feedback));
		}
	}
	
	/**修改代理商**/
	function update_angent(){
		global $logger, $my;
		
		$sql="UPDATE 代理商 SET 代理商名稱 =?,代理商統一編號 =?,LAST_UPDATE_PEOPLE=?, LAST_UPDATE_TIME = CURRENT_TIMESTAMP WHERE 代理商識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('ssii',$_POST["代理商名稱"],$_POST["代理商統一編號"],$_SESSION["AMS"]["UID"],$_POST["代理商識別碼"])){
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$feedback = array(
			"success" => true,
			"message" => urlencode("成功修改代理商資料"),
		);
		$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].' 修改代理商:'.$_POST["代理商識別碼"]);
		echo urldecode(json_encode($feedback));
	}
	
	//取得代理商資料
	function get_angentDataGrid(){
		global $logger, $my;
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			$searchBy='%'.$_POST['searchBy'].'%';//搜尋關鍵字
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 代理商
				WHERE (代理商識別碼 = ? OR 代理商統一編號 LIKE ? OR 代理商名稱 LIKE ?) AND DELETED_TIME IS NULL
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
				SELECT 代理商識別碼,代理商名稱,代理商統一編號,DISABLE_TIME AS 狀態
				FROM  代理商
				WHERE (代理商識別碼 = ? OR 代理商統一編號 LIKE ? OR 代理商名稱 LIKE ?) AND DELETED_TIME IS NULL
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
				$row['狀態']=($row['狀態']==null)?'顯示':'隱藏';
				$orders[]=array(array($row['代理商識別碼'],'text'),array($row['代理商名稱'],'text'),array(($row['代理商統一編號']==null)?'':$row['代理商統一編號'],'text'),array($row['狀態'],'text'));
			}
	
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('代理商識別碼','代理商名稱','代理商統一編號','狀態')
							,'data'=>$orders,'sortable'=>array('代理商識別碼','代理商名稱','代理商統一編號')),JSON_UNESCAPED_UNICODE);
			exit;
	}
?>