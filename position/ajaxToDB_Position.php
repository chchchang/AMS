<?php
	include('../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
	switch($_POST['action']){
		case "getCount":
			get_Count();
			break;
		case "newPositionType":
			new_PositionType();
			break;
		case "版位類型資料表":
			get_positionType();
			break;
		case "editPositionType":
			edit_PositionType();
			break;
		case "getPosition":
			get_position();
			break;
		case "newPosition":
			new_Position();
			break;
		case "editPosition":
			edit_Position();
			break;
		default:
			break;
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
	
	/**新增版位類型**/
	function new_PositionType(){
		global $logger, $my;
		
		$value=array();
		($_POST["版位有效起始時間"]=='')?($value["版位有效起始時間"]=null):($value["版位有效起始時間"]=$_POST["版位有效起始時間"]);
		($_POST["版位有效結束時間"]=='')?($value["版位有效結束時間"]=null):($value["版位有效結束時間"]=$_POST["版位有效結束時間"]);
		($_POST["建議售價"]=='')?($value["建議售價"]=null):($value["建議售價"]=$_POST["建議售價"]);
		($_POST["預約到期提前日"]=='')?($value["預約到期提前日"]=null):($value["預約到期提前日"]=$_POST["預約到期提前日"]);
		(isset($_POST["上層版位識別碼"]))?($value["上層版位識別碼"]=$_POST["上層版位識別碼"]):($value["上層版位識別碼"]=null);
		
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("savingPosition");
		$mutex->lock();
		
		$sql="INSERT INTO 版位 (上層版位識別碼,版位名稱,版位說明,版位有效起始時間,版位有效結束時間,`託播單介接API URL`,`排程表介接API URL`,`使用記錄介接API URL`
			,建議售價,預約到期提前日,CREATED_PEOPLE)"
		." VALUES(?,?,?,?,?,?,?,?,?,?,?)";
		
		if(!$stmt=$my->prepare($sql)) {
			$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		
		if(!$stmt->bind_param('isssssssisi',$value["上層版位識別碼"],$_POST["版位名稱"],$_POST["版位說明"],$value["版位有效起始時間"]
			,$value["版位有效結束時間"],$_POST["託播單介接API"],$_POST["排程表介接API"],$_POST["使用記錄介接API"],$value["建議售價"],$value["預約到期提前日"],$_SESSION['AMS']['使用者識別碼']))
		{
			$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		
		if(!$stmt->execute()) {
			$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		$insertId=$stmt->insert_id;
		//增加素材類型	
		if(isset($_POST['版位素材類型']))
		foreach($_POST['版位素材類型'] as $material){			
			$value=array();
			($material["每小時最大素材筆數"]=='')?($value["每小時最大素材筆數"]=null):($value["每小時最大素材筆數"]=$material["每小時最大素材筆數"]);
			($material["每小時最大影片素材合計秒數"]=='')?($value["每小時最大影片素材合計秒數"]=null):($value["每小時最大影片素材合計秒數"]=$material["每小時最大影片素材合計秒數"]);
			($material["每則文字素材最大字數"]=='')?($value["每則文字素材最大字數"]=null):($value["每則文字素材最大字數"]=$material["每則文字素材最大字數"]);
			($material["每則圖片素材最大寬度"]=='')?($value["每則圖片素材最大寬度"]=null):($value["每則圖片素材最大寬度"]=$material["每則圖片素材最大寬度"]);
			($material["每則圖片素材最大高度"]=='')?($value["每則圖片素材最大高度"]=null):($value["每則圖片素材最大高度"]=$material["每則圖片素材最大高度"]);
			($material["每則影片素材最大秒數"]=='')?($value["每則影片素材最大秒數"]=null):($value["每則影片素材最大秒數"]=$material["每則影片素材最大秒數"]);

			$sql="INSERT INTO 版位素材類型 (版位識別碼,素材順序,顯示名稱,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,每小時最大素材筆數,每小時最大影片素材合計秒數,每則文字素材最大字數
			,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數,CREATED_PEOPLE)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)";
			if(!$stmt2=$my->prepare($sql)){
				$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			
			if(!$stmt2->bind_param('iisiiiiiiiiii',$insertId,$material['素材順序'],$material['顯示名稱'],$material['素材類型識別碼'],$material['託播單素材是否必填'],$material['影片畫質識別碼']
			,$value['每小時最大素材筆數'],$value['每小時最大影片素材合計秒數'],$value['每則文字素材最大字數'],$value['每則圖片素材最大寬度'],$value['每則圖片素材最大高度']
			,$value['每則影片素材最大秒數'],$_SESSION['AMS']['使用者識別碼'])){
				$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			
			
			if(!$stmt2->execute()){
				$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
		}
		
		//增加其他參數
		if(isset($_POST['版位其他參數']))
		foreach($_POST['版位其他參數'] as $config){		
			$sql="INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,版位其他參數預設值,版位其他參數顯示名稱,是否版位專用
			,CREATED_PEOPLE)
			VALUES(?,?,?,?,?,?,?,?,?)";
			if(!$stmt2=$my->prepare($sql)){
				$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			if(!$stmt2->bind_param('iisiissii',$insertId,$config['版位其他參數順序'],$config['版位其他參數名稱'],$config['版位其他參數型態識別碼'],$config['版位其他參數是否必填']
			,$config['版位其他參數預設值'],$config['版位其他參數顯示名稱'],$config['是否版位專用'],$_SESSION['AMS']['使用者識別碼'])){
				$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			
			if(!$stmt2->execute()){
				$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
		}
		$my->commit();
		$my->close();
		$mutex->unlock();
		
		$feedback = array(
			"success" => true,
			"message" => urlencode("新增成功!"),
		);
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增版位資料 識別碼'.$insertId);
		exit(urldecode(json_encode($feedback)));
		
		//錯誤發生，取消改動
		exitWithError:
		$my->rollback();
		$my->close();
		$mutex->unlock();
		exit($Error);

	}
	
	
	/**修改版位**/
	function edit_PositionType(){
		global $logger, $my;
		
		$value=array();
		($_POST["版位有效起始時間"]=='')?($value["版位有效起始時間"]=null):($value["版位有效起始時間"]=$_POST["版位有效起始時間"]);
		($_POST["版位有效結束時間"]=='')?($value["版位有效結束時間"]=null):($value["版位有效結束時間"]=$_POST["版位有效結束時間"]);
		($_POST["建議售價"]=='')?($value["建議售價"]=null):($value["建議售價"]=$_POST["建議售價"]);
		($_POST["預約到期提前日"]=='')?($value["預約到期提前日"]=null):($value["預約到期提前日"]=$_POST["預約到期提前日"]);
		
		$my->begin_transaction();
		//鎖定資料表
		require dirname(__FILE__).'/../tool/mutex/Mutex.class.php';
		$mutex = new Mutex("savingPosition");
		$mutex->lock();
		
		$sql="UPDATE 版位 SET 版位名稱=?,版位說明=?,版位有效起始時間=?,版位有效結束時間=?,`託播單介接API URL`=?,`排程表介接API URL`=?,`使用記錄介接API URL`=?
			,建議售價=?,預約到期提前日=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME = CURRENT_TIMESTAMP WHERE 版位識別碼=?"
		;
		
		if(!$stmt=$my->prepare($sql)) {
			$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		
		if(!$stmt->bind_param('sssssssisii',$_POST["版位名稱"],$_POST["版位說明"],$value["版位有效起始時間"],$value["版位有效結束時間"]
			,$_POST["託播單介接API"],$_POST["排程表介接API"],$_POST["使用記錄介接API"],$value["建議售價"],$value["預約到期提前日"],$_SESSION['AMS']['使用者識別碼'],$_POST['版位識別碼']))
		{
			$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		
		if(!$stmt->execute()) {
			$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		
		//刪除現有素材資料
		$sql="DELETE FROM 版位素材類型
		WHERE 版位識別碼 = ?
		";
		if(!$stmt2=$my->prepare($sql)){
				$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
		}
		if(!$stmt2->bind_param('i',$_POST['版位識別碼'])){
			$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		if(!$stmt2->execute()){
			$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		//增加素材類型
		if(isset($_POST['版位素材類型']))
		foreach($_POST['版位素材類型'] as $material){			
			$value=array();
			($material["每小時最大素材筆數"]=='')?($value["每小時最大素材筆數"]=null):($value["每小時最大素材筆數"]=$material["每小時最大素材筆數"]);
			($material["每小時最大影片素材合計秒數"]=='')?($value["每小時最大影片素材合計秒數"]=null):($value["每小時最大影片素材合計秒數"]=$material["每小時最大影片素材合計秒數"]);
			($material["每則文字素材最大字數"]=='')?($value["每則文字素材最大字數"]=null):($value["每則文字素材最大字數"]=$material["每則文字素材最大字數"]);
			($material["每則圖片素材最大寬度"]=='')?($value["每則圖片素材最大寬度"]=null):($value["每則圖片素材最大寬度"]=$material["每則圖片素材最大寬度"]);
			($material["每則圖片素材最大高度"]=='')?($value["每則圖片素材最大高度"]=null):($value["每則圖片素材最大高度"]=$material["每則圖片素材最大高度"]);
			($material["每則影片素材最大秒數"]=='')?($value["每則影片素材最大秒數"]=null):($value["每則影片素材最大秒數"]=$material["每則影片素材最大秒數"]);

			$sql="INSERT INTO 版位素材類型 (版位識別碼,素材順序,顯示名稱,素材類型識別碼,託播單素材是否必填,影片畫質識別碼,每小時最大素材筆數,每小時最大影片素材合計秒數,每則文字素材最大字數
			,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數,CREATED_PEOPLE)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)";
			if(!$stmt2=$my->prepare($sql)){
				$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			
			if(!$stmt2->bind_param('iisiiiiiiiiii',$_POST['版位識別碼'],$material['素材順序'],$material['顯示名稱'],$material['素材類型識別碼'],$material['託播單素材是否必填'],$material['影片畫質識別碼']
			,$value['每小時最大素材筆數'],$value['每小時最大影片素材合計秒數'],$value['每則文字素材最大字數'],$value['每則圖片素材最大寬度'],$value['每則圖片素材最大高度']
			,$value['每則影片素材最大秒數'],$_SESSION['AMS']['使用者識別碼'])){
				$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			
			
			if(!$stmt2->execute()){
				$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
		}
		
		//刪除現有資料
		$sql="DELETE FROM 版位其他參數
		WHERE 版位識別碼 = ?
		";
		if(!$stmt2=$my->prepare($sql)){
				$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
		}
		if(!$stmt2->bind_param('i',$_POST['版位識別碼'])){
			$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		if(!$stmt2->execute()){
			$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
			goto exitWithError;
		}
		
		
		//增加其他參數
		if(isset($_POST['版位其他參數']))
		foreach($_POST['版位其他參數'] as $config){	
			$sql="INSERT INTO 版位其他參數 (版位識別碼,版位其他參數順序,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,版位其他參數預設值,版位其他參數顯示名稱,是否版位專用
			,CREATED_PEOPLE)
			VALUES(?,?,?,?,?,?,?,?,?)";
			if(!$stmt2=$my->prepare($sql)){
				$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			if(!$stmt2->bind_param('iisiissii',$_POST['版位識別碼'],$config['版位其他參數順序'],$config['版位其他參數名稱'],$config['版位其他參數型態識別碼'],$config['版位其他參數是否必填']
			,$config['版位其他參數預設值'],$config['版位其他參數顯示名稱'],$config['是否版位專用'],$_SESSION['AMS']['使用者識別碼'])){
				$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			/*$sql="UPDATE 版位其他參數 SET 版位其他參數名稱=?,版位其他參數型態識別碼=?,版位其他參數是否必填=?,版位其他參數預設值=?,版位其他參數顯示名稱=?,是否版位專用=?
			,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME = CURRENT_TIMESTAMP
			WHERE 版位識別碼 = ? AND 版位其他參數順序 = ?
			";
			if(!$stmt2=$my->prepare($sql)){
				$Error=json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
			if(!$stmt2->bind_param('siissiiii',$config['版位其他參數名稱'],$config['版位其他參數型態識別碼'],$config['版位其他參數是否必填']
			,$config['版位其他參數預設值'],$config['版位其他參數顯示名稱'],$config['是否版位專用'],$_SESSION['AMS']['使用者識別碼'],$_POST['版位識別碼'],$config['版位其他參數順序'])){
				$Error=json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}*/
			
			if(!$stmt2->execute()){
				$Error=json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE);
				goto exitWithError;
			}
		}
		$my->commit();
		$my->close();
		$mutex->unlock();
		
		$feedback = array(
			"success" => true,
			"message" => urlencode("修改成功!"),
		);
		$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'修改版位識別碼:'.$_POST["版位識別碼"]);
		exit(urldecode(json_encode($feedback)));
		
		//錯誤發生，取消改動
		exitWithError:
		$my->rollback();
		$my->close();
		$mutex->unlock();
		exit($Error);
	}
	
	/**取得版位類型資料**/
	function get_positionType(){
		global $logger, $my;
		
		$orders=array();
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
		$totalRowCount=0;	//T.B.D.
		$searchBy='%'.$_POST['searchBy'].'%';
		
		//先取得總筆數
		$sql='
			SELECT COUNT(1) COUNT
			FROM 版位
			WHERE DELETED_TIME IS null AND DISABLE_TIME IS null AND (版位識別碼 = ? OR 版位名稱 LIKE ? OR 版位說明 LIKE ?) AND 上層版位識別碼 IS null
		';
		
		if(!$res=$my->getResult($sql,'iss',$_POST['searchBy'],$searchBy,$searchBy)) {
			exit('無法取得結果集，請聯絡系統管理員！');
		}
	
		if($row=$res->fetch_assoc())
			$totalRowCount=$row['COUNT'];
		else
			exit;
		
		//再取得資料
		$sql='
			SELECT 版位識別碼 AS 版位類型識別碼,版位名稱 AS 版位類型名稱,版位說明 AS 版位類型說明
			FROM 版位
			WHERE DELETED_TIME IS null AND DISABLE_TIME IS null AND (版位識別碼 = ? OR 版位名稱 LIKE ? OR 版位說明 LIKE ?) AND 上層版位識別碼 IS null
			ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
			'LIMIT ?,'.PAGE_SIZE.'
		';
		
		if(!$res=$my->getResult($sql,'issi',$_POST['searchBy'],$searchBy,$searchBy,$fromRowNo)) {
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		while($row=$res->fetch_assoc())
			$orders[]=array(array($row['版位類型識別碼'],'text'),array($row['版位類型名稱'],'text'),array($row['版位類型說明'],'text'));
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('版位類型識別碼','版位類型名稱','版位類型說明')
						,'data'=>$orders,'sortable'=>array('版位類型識別碼','版位類型名稱','版位類型說明','狀態')),JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	/**取得版位資料**/
	function get_position(){
		global $logger, $my;
		$sort = "" ;
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
		$sql="SELECT 版位識別碼,版位名稱,版位說明 FROM 版位 WHERE 版位識別碼=".$_POST["版位識別碼"]." ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];

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
	
	
		/**新增版位**/
	function new_Position(){
		global $logger, $my;
		
		$sql= "SELECT COUNT(*) FROM 版位 WHERE 版位名稱=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('s',$_POST["版位名稱"])) {
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
				"message" => urlencode("相同的版位名稱已存在"),
			);

			echo urldecode(json_encode($feedback));
			return 0;
		}
		
		$value=array();
		($_POST["版位有效起始時間"]=='')?($value["版位有效起始時間"]=null):($value["版位有效起始時間"]=$_POST["版位有效起始時間"]);
		($_POST["版位有效結束時間"]=='')?($value["版位有效結束時間"]=null):($value["版位有效結束時間"]=$_POST["版位有效結束時間"]);
		($_POST["每小時最大素材筆數"]=='')?($value["每小時最大素材筆數"]=null):($value["每小時最大素材筆數"]=$_POST["每小時最大素材筆數"]);
		($_POST["每小時最大影片素材合計秒數"]=='')?($value["每小時最大影片素材合計秒數"]=null):($value["每小時最大影片素材合計秒數"]=$_POST["每小時最大影片素材合計秒數"]);
		($_POST["每則文字素材最大字數"]=='')?($value["每則文字素材最大字數"]=null):($value["每則文字素材最大字數"]=$_POST["每則文字素材最大字數"]);
		($_POST["每則圖片素材最大寬度"]=='')?($value["每則圖片素材最大寬度"]=null):($value["每則圖片素材最大寬度"]=$_POST["每則圖片素材最大寬度"]);
		($_POST["每則圖片素材最大高度"]=='')?($value["每則圖片素材最大高度"]=null):($value["每則圖片素材最大高度"]=$_POST["每則圖片素材最大高度"]);
		($_POST["每則影片素材最大秒數"]=='')?($value["每則影片素材最大秒數"]=null):($value["每則影片素材最大秒數"]=$_POST["每則影片素材最大秒數"]);
		($_POST["建議售價"]=='')?($value["建議售價"]=null):($value["建議售價"]=$_POST["建議售價"]);
		($_POST["預約到期提前日"]=='')?($value["預約到期提前日"]=null):($value["預約到期提前日"]=$_POST["預約到期提前日"]);
		
		$sql="INSERT INTO 版位 (版位類型識別碼,版位名稱,版位說明,版位有效起始時間,版位有效結束時間,每小時最大素材筆數,每小時最大影片素材合計秒數,
		每則文字素材最大字數,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數,`託播單介接API URL`,`排程表介接API URL`,
		`使用記錄介接API URL`,其他介接參數,建議售價,預約到期提前日,CREATED_PEOPLE)"
		." VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('issssiiiiiissssiii',$_POST["版位類型識別碼"],$_POST["版位名稱"],$_POST["版位說明"],$value["版位有效起始時間"],$value["版位有效結束時間"]
			,$value["每小時最大素材筆數"],$value["每小時最大影片素材合計秒數"],$value["每則文字素材最大字數"],$value["每則圖片素材最大寬度"],$value["每則圖片素材最大高度"]
			,$value["每則影片素材最大秒數"],$_POST["託播單介接API"],$_POST["排程表介接API"],$_POST["使用記錄介接API"],$_POST["其他介接參數"],$value["建議售價"],$value["預約到期提前日"]
			,$_SESSION['AMS']['使用者識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
				
		$feedback = array(
			"success" => true,
			"message" => urlencode("成功新增版位!"),
		);
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增版位識別碼:'.$stmt->insert_id);
		echo urldecode(json_encode($feedback));
	}
	
	
	/**修改版位**/
	function edit_Position(){
		global $logger, $my;
		
		$sql= "SELECT COUNT(*) FROM 版位 WHERE 版位名稱=? AND 版位識別碼!=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('si',$_POST["版位名稱"],$_POST["版位識別碼"])) {
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
				"message" => urlencode("相同的版位名稱已存在"),
			);

			echo urldecode(json_encode($feedback));
			return 0;
		}
		
		$value=array();
		($_POST["版位有效起始時間"]=='')?($value["版位有效起始時間"]=null):($value["版位有效起始時間"]=$_POST["版位有效起始時間"]);
		($_POST["版位有效結束時間"]=='')?($value["版位有效結束時間"]=null):($value["版位有效結束時間"]=$_POST["版位有效結束時間"]);
		($_POST["每小時最大素材筆數"]=='')?($value["每小時最大素材筆數"]=null):($value["每小時最大素材筆數"]=$_POST["每小時最大素材筆數"]);
		($_POST["每小時最大影片素材合計秒數"]=='')?($value["每小時最大影片素材合計秒數"]=null):($value["每小時最大影片素材合計秒數"]=$_POST["每小時最大影片素材合計秒數"]);
		($_POST["每則文字素材最大字數"]=='')?($value["每則文字素材最大字數"]=null):($value["每則文字素材最大字數"]=$_POST["每則文字素材最大字數"]);
		($_POST["每則圖片素材最大寬度"]=='')?($value["每則圖片素材最大寬度"]=null):($value["每則圖片素材最大寬度"]=$_POST["每則圖片素材最大寬度"]);
		($_POST["每則圖片素材最大高度"]=='')?($value["每則圖片素材最大高度"]=null):($value["每則圖片素材最大高度"]=$_POST["每則圖片素材最大高度"]);
		($_POST["每則影片素材最大秒數"]=='')?($value["每則影片素材最大秒數"]=null):($value["每則影片素材最大秒數"]=$_POST["每則影片素材最大秒數"]);
		($_POST["建議售價"]=='')?($value["建議售價"]=null):($value["建議售價"]=$_POST["建議售價"]);
		($_POST["預約到期提前日"]=='')?($value["預約到期提前日"]=null):($value["預約到期提前日"]=$_POST["預約到期提前日"]);
		
		$sql="UPDATE 版位 SET 版位類型識別碼=?,版位名稱=?,版位說明=?,版位有效起始時間=?,版位有效結束時間=?,每小時最大素材筆數=?,每小時最大影片素材合計秒數=?,
		每則文字素材最大字數=?,每則圖片素材最大寬度=?,每則圖片素材最大高度=?,每則影片素材最大秒數=?,`託播單介接API URL`=?,`排程表介接API URL`=?,
		`使用記錄介接API URL`=?,其他介接參數=?,建議售價=?,預約到期提前日=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME = CURRENT_TIMESTAMP WHERE 版位識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('issssiiiiiissssiiii',$_POST["版位類型識別碼"],$_POST["版位名稱"],$_POST["版位說明"],$value["版位有效起始時間"],$value["版位有效結束時間"]
			,$value["每小時最大素材筆數"],$value["每小時最大影片素材合計秒數"],$value["每則文字素材最大字數"],$value["每則圖片素材最大寬度"],$value["每則圖片素材最大高度"]
			,$value["每則影片素材最大秒數"],$_POST["託播單介接API"],$_POST["排程表介接API"],$_POST["使用記錄介接API"],$_POST["其他介接參數"],$value["建議售價"],$value["預約到期提前日"]
			,$_SESSION['AMS']['使用者識別碼'],$_POST["版位識別碼"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
				
		$feedback = array(
			"success" => true,
			"message" => urlencode("成功修改版位!"),
		);
		$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'修改版位資料,版位識別碼:'.$_POST["版位識別碼"]);
		echo urldecode(json_encode($feedback));
	}
?>