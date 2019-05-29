<?php
	include('../tool/auth/authAJAX.php');
	
	header('Content-Type: application/json; charset=utf-8');
	
	require '../tool/OracleDB.php';
	define('PAGE_SIZE',10);
	
	if(!isset($_GET['area'])||(array_search($_GET['area'],array('N','C','S'))===false))
		exit(json_encode(array('error'=>'區域指定錯誤，必須為N、C、S其中之一！'),JSON_UNESCAPED_UNICODE));
	
	if($_GET['area']==='N'){
		$DB_U = Config::OMP_N_ORACLE_DB_USER;
		$DB_T_O = Config::OMP_N_ORACLE_DB_TABLE_OWNER;
		$DB_P = Config::OMP_N_ORACLE_DB_PASSWORD;
		$DB_S = Config::OMP_N_ORACLE_DB_CONN_STR;
	}
	else if($_GET['area']==='C'){
		$DB_U = Config::OMP_C_ORACLE_DB_USER;
		$DB_T_O = Config::OMP_C_ORACLE_DB_TABLE_OWNER;
		$DB_P = Config::OMP_C_ORACLE_DB_PASSWORD;
		$DB_S = Config::OMP_C_ORACLE_DB_CONN_STR;
	}
	else if($_GET['area']==='S'){
		$DB_U = Config::OMP_S_ORACLE_DB_USER;
		$DB_T_O = Config::OMP_S_ORACLE_DB_TABLE_OWNER;
		$DB_P = Config::OMP_S_ORACLE_DB_PASSWORD;
		$DB_S = Config::OMP_S_ORACLE_DB_CONN_STR;
	}
	
	$oracleDB=new OracleDB($DB_U,$DB_P,$DB_S);
	
	//先取得總筆數
	$sql='
		SELECT COUNT(1) COUNT
		FROM 委刊單
		WHERE 廣告主識別碼 LIKE ? AND
		(委刊單識別碼 = ? OR 委刊單名稱 LIKE ? OR 委刊單說明 LIKE ? OR 委刊單編號 LIKE ?)
	';
	
	
	
	//再取得資料
	$sql='
		SELECT 委刊單識別碼,委刊單編號,委刊單名稱,委刊單說明,CREATED_TIME AS 建立時間, LAST_UPDATE_TIME AS 修改時間
		FROM  委刊單
		WHERE 廣告主識別碼 LIKE ? AND (委刊單識別碼 = ? OR 委刊單名稱 LIKE ? OR 委刊單說明 LIKE ? OR 委刊單編號 LIKE ?)
		ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
		'LIMIT ?,'.PAGE_SIZE.'
	';
	
	//回傳資料
	while($row=$res->fetch_assoc())
		$orders[]=array(array($row['委刊單識別碼'],'text'),array(($row['委刊單編號']==null)?'':$row['委刊單編號'],'text'),array($row['委刊單名稱'],'text'),array(($row['委刊單說明']==null)?'':$row['委刊單說明'],'text'),array(checkOrderStates($row['委刊單識別碼']),'text'),array($row['建立時間'],'text'),array(($row['修改時間']==null)?'':$row['修改時間'],'text'));

	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('委刊單識別碼','委刊單編號','委刊單名稱','委刊單說明','託播單狀態','建立時間','修改時間')
					,'data'=>$orders,'sortable'=>array('委刊單識別碼','委刊單編號','委刊單名稱','建立時間','修改時間')),JSON_UNESCAPED_UNICODE);
	exit;
			
	if(isset($_GET['SRVC_NAME'])){
		$searchTerm = $_GET['SRVC_NAME'];
		//banner排程查詢
		$sql='
			SELECT '
				.'SRVC_RECID,'
				//.'SRVC_PARENT_ID,'
				.'SRVC_IS_DEPLOYED,'
				.'SRVC_IS_ENABLED,'
				.'SRVC_TYPE,'
				.'SRVC_SUBTYPE,'
				.'SRVC_NAME,'
				.'SRVC_DESCRIPTION,'
				.'SRVC_RSRC_REF,'
				//.'SRVC_HAS_ADVERT,'
				//.'SRVC_ADVERT_URL,'
				//.'POL_RECID,'
				//.'RAT_RECID,'
				.'SRVC_FOR_ADULT_ONLY,'
				.'SRVC_FOR_SUBSCRIBER_ONLY,'
				//.'SRVC_IMAGE_ON,'
				//.'SRVC_IMAGE_OFF,'
				//.'SRVC_IMAGE_DISABLED,'
				.'SRVC_ACCESS_URL,'
				//.'SRVC_NDS_SERVICE_ID,'
				//.'SRVC_CO_NDS_REGION,'
				//.'SRVC_DISPLAY_SEQUENCE,'
				.'SRVC_IMPORT_REFNUM'
				//.'SRVC_SHORTCODE,'
				//.'CHAN_RECID,'
				//.'IS_MULTI_ENTRY,'
				//.'SRVC_DEFAULE_NAME,'
				//.'IS_GRAPHICAL,'
				//.'IS_SHARED'
			.' FROM '.$DB_T_O.'.OVA_SERVICE OS 
			WHERE
				OS.SRVC_NAME=:SEARCH
		';
		//$sql='SELECT * FROM '.$DB_T_O.'.OVA_SERVICE OS WHERE OS.SRVC_NAME=:SEARCH';
		$vars=array(
			array('bv_name'=>':SEARCH','variable'=>$searchTerm)
		);
		$result=$oracleDB->getResultArray($sql,$vars);
		//檢查結果
		if(count($result)==0)
			exit(json_encode(array('success'=>false,'message'=>'服務名稱未建立'),JSON_UNESCAPED_UNICODE));
		$row = $result[0];
		$urlWithoutSpace = trim($row['SRVC_ACCESS_URL']);
		if(strlen($urlWithoutSpace)==0)
			exit(json_encode(array('success'=>false,'message'=>'服務名稱對應的URL未建立'),JSON_UNESCAPED_UNICODE));
			
		exit(json_encode(array('success'=>true,'message'=>'success'),JSON_UNESCAPED_UNICODE));
	}
	else{
		exit(json_encode(array('error'=>'參數錯誤！找不到正確的參數組合！'),JSON_UNESCAPED_UNICODE));
	}
?>
