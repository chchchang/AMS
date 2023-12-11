<?php
//function getTableData($sqlparas){
	global $my,$logger;
	$showAll = (isset($_POST['顯示全部']) && $_POST['顯示全部'])?true:false;
	$DG_header=array('素材識別碼','素材名稱','素材說明','素材原始檔名','影片素材秒數','CAMPS影片派送時間','CAMPS影片媒體編號');
	//取得資料
	$sql = 'SELECT '.join($DG_header,',').' FROM 素材 
	WHERE 素材類型識別碼=3	
		AND 素材群組識別碼 LIKE ? 
		AND(
				((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
				OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
				OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
				OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
			)
		AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? OR CAMPS影片媒體編號 LIKE ?)
		AND ((CAMPS影片派送時間 BETWEEN ? AND ?)'.($sqlparas["CAMPSTimeFlag"]?'':'OR CAMPS影片派送時間 IS NULL').')
		'.($sqlparas["unCimmitOnly"]=='true'?'AND ( CAMPS影片派送時間 IS NULL )':'').'
		'.($sqlparas["unNumberOnly"]=='true'?'AND ((CAMPS影片媒體編號 IS NULL || CAMPS影片媒體編號 = ""))':'')
		.$sqlparas["fileUploadOrNot"].'
		ORDER BY '.((isset($_POST['order']))?$_POST['order']:'素材識別碼').' '.$_POST['asc'].
		($showAll?'':(' LIMIT ?, '.PAGE_SIZE));
	$defString = 'sssssssssssssss'.($showAll?'':'i');
	$logger->info($sql);
	$a_params =[&$sql,&$defString,&$sqlparas["materialGroup"],&$sqlparas["startDate"],&$sqlparas["endDate"],&$sqlparas["startDate"],&$sqlparas["endDate"],&$sqlparas["startDate"],&$sqlparas["endDate"],&$sqlparas["startDate"],&$sqlparas["searchBy"],&$sqlparas["searchBy"],&$sqlparas["searchBy"],&$sqlparas["searchBy"],&$sqlparas["searchBy"],&$sqlparas["startDateCAMPS"],&$sqlparas["endDateCAMPS"]];
	if(!$showAll)
		$a_params[] =&$sqlparas["fromRowNo"];
	$result=call_user_func_array(array($my, 'getResultArray'), $a_params);
	$DG_header=array_merge($DG_header,array('取得結果','派送影片','影片','上傳到端點barker','上傳到破口廣告','執行結果'));
	$DG_body=array();
	foreach($result as $row){
		$DG_body[]=array(array($row['素材識別碼']),array($row['素材名稱']),array($row['素材說明']),array($row['素材原始檔名']),array($row['影片素材秒數']),array($row['CAMPS影片派送時間'])
		,array($row['CAMPS影片媒體編號']),array('取得結果','button'),array('派送影片','button'),array('刪除遠端影片','button'),array('上傳到端點barker','button'),array('上傳到破口廣告','button'),array(""));
	}
	header('Content-Type: application/json');
	exit(json_encode(array('pageNo'=>$showAll?1:(($sqlparas["fromRowNo"]/PAGE_SIZE)+1),'maxPageNo'=>$showAll?1:ceil($sqlparas["totalRowCount"]/PAGE_SIZE),'allCount'=>$sqlparas["totalRowCount"],
		'header'=>$DG_header,'sortable'=>array('素材識別碼','素材名稱','素材說明','素材原始檔名'),'body'=>$DG_body)));
//}
?>