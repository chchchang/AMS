<?php
//function getTableData($sqlparas){
	global $my;
	$showAll = (isset($_POST['顯示全部']) && $_POST['顯示全部'])?true:false;
	$DG_header=array('素材識別碼','素材名稱','素材說明','素材原始檔名','是否曾經派送');
	$sql = 'SELECT 素材識別碼,素材名稱,素材說明,素材原始檔名,圖片素材派送結果 AS 是否曾經派送 FROM 素材 
	WHERE 素材類型識別碼=2 
		AND 素材群組識別碼 LIKE ? 
		AND(
				((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
				OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
				OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
				OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
			)
		AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? )'.($sqlparas["unCimmitOnly"]=='true'?' AND (圖片素材派送結果 IS NULL OR 圖片素材派送結果="[]")':'')
		.$sqlparas["fileUploadOrNot"].
		' ORDER BY '.((isset($_POST['order']))?$_POST['order']:'素材識別碼').' '.$_POST['asc'].
		($showAll?'':(' LIMIT ?, '.PAGE_SIZE));
	$defString = 'ssssssssssss'.($showAll?'':'i');
	$a_params =[&$sql,&$defString,&$sqlparas["materialGroup"],&$sqlparas["startDate"],&$sqlparas["endDate"],&$sqlparas["startDate"],&$sqlparas["endDate"],&$sqlparas["startDate"],&$sqlparas["endDate"],&$sqlparas["startDate"],&$sqlparas["searchBy"],&$sqlparas["searchBy"],&$sqlparas["searchBy"],&$sqlparas["searchBy"]];
	if(!$showAll)
		$a_params[] =&$sqlparas["fromRowNo"];
	$result=call_user_func_array(array($my, 'getResultArray'), $a_params);
	
	$DG_header=array_merge($DG_header,array('圖片預覽','北區狀態','取得北區','派送北區','中區狀態','取得中區','派送中區','南區狀態','取得南區','派送南區'));
	$DG_body=array();
	if(isset($result)&&$result!=null)
	foreach($result as $row){
		$mnameA=explode('.',$row['素材原始檔名']);
		$DG_body[]=array(array($row['素材識別碼']),array($row['素材名稱']),array($row['素材說明']),array($row['素材原始檔名']),
		array('<img src="../tool/pic/'.($row['是否曾經派送']==NULL||$row['是否曾經派送']=='[]'?'Circle_Red.png':'Circle_Green.png').'">','html'),
		//array('<img class="dgImg" src="'.MATERIAL_FOLDER_URL.$row['素材識別碼'].'.'.end($mnameA).'?'.time().'" alt="'.$row['素材識別碼'].':'.$row['素材原始檔名'].'" style="max-width:100%;max-height:100%;border:0;">','html'),
		array('<img class="dgImg" src="uploadedFile/'.$row['素材識別碼'].'.'.end($mnameA).'?'.time().'" alt="'.$row['素材識別碼'].':'.$row['素材原始檔名'].'" style="max-width:100%;max-height:100%;border:0;">','html'),
		array(''),array('取得北區','button'),array('派送北區','button'),array(''),array('取得中區','button'),array('派送中區','button'),array(''),array('取得南區','button'),array('派送南區','button'));
	}
	header('Content-Type: application/json');
	exit(json_encode(array('pageNo'=>$showAll?1:(($sqlparas["fromRowNo"]/PAGE_SIZE)+1),'maxPageNo'=>$showAll?1:ceil($sqlparas["totalRowCount"]/PAGE_SIZE),'allCount'=>$sqlparas["totalRowCount"],
		'header'=>$DG_header,'sortable'=>array('素材識別碼','素材名稱','素材說明','素材原始檔名','是否曾經派送'),'body'=>$DG_body)));
//}
?>