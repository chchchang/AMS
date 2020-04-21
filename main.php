<?php
	include('tool/auth/auth.php');
	define('PAGE_SIZE',10);
	if(isset($_POST['action'])){
		if($_POST['action']==='尚未確定託播單'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$my=new MyDB();
			//取得總數
			$sql='
				SELECT
					COUNT(*) C
				FROM
					託播單,版位 版位,版位 版位類型,託播單狀態,使用者
				WHERE
					託播單.版位識別碼=版位.版位識別碼 AND
					版位.上層版位識別碼=版位類型.版位識別碼 AND
					託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼 AND
					託播單狀態.託播單狀態名稱 IN (\'預約\') AND
					託播單.CREATED_PEOPLE=使用者.使用者識別碼 AND
					託播單.預約到期時間>=\''.date('Ymd').'\'
				ORDER BY
					託播單.預約到期時間 ASC,
					託播單.廣告期間開始時間 ASC,
					託播單.廣告期間結束時間 ASC,
					託播單.託播單識別碼
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$totalRowCount=$result[0]['C'];
			//取得資料
			$sql='
				SELECT
					託播單狀態.託播單狀態名稱,
					託播單.託播單識別碼,託播單.託播單名稱,託播單.廣告期間開始時間,託播單.廣告期間結束時間,託播單.預約到期時間,
					版位類型.版位名稱 AS 版位類型名稱,
					版位.版位名稱,
					使用者.使用者姓名 建立者
				FROM
					託播單,版位 版位,版位 版位類型,託播單狀態,使用者
				WHERE
					託播單.版位識別碼=版位.版位識別碼 AND
					版位.上層版位識別碼=版位類型.版位識別碼 AND
					託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼 AND
					託播單狀態.託播單狀態名稱 IN (\'預約\') AND
					託播單.CREATED_PEOPLE=使用者.使用者識別碼 AND
					託播單.預約到期時間>=\''.date('Ymd').'\'
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			if(!$result=$my->getResultArray($sql,'i',$fromRowNo)) $result=array();			
			$data =[];
			foreach($result as $row){
				$data[] = [
					['檢視託播單','button'],[$row['託播單狀態名稱'],'text'],[$row['託播單識別碼'],'text'],[$row['託播單名稱'],'text'],[$row['廣告期間開始時間'],'text'],[$row['廣告期間結束時間'],'text']
					,[$row['預約到期時間'],'text'],[$row['版位類型名稱'],'text'],[$row['版位名稱'],'text'],[$row['建立者'],'text']
					];
			}
			exit(json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('檢視託播單','託播單狀態','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','預約到期時間','版位類型名稱','版位名稱','建立者')
							,'data'=>$data,'sortable'=>array('託播單狀態','版位類型名稱','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','預約到期時間','版位類型名稱','版位名稱','建立者')),JSON_UNESCAPED_UNICODE)
				);
		}
		if($_POST['action']==='尚未送出託播單'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$my=new MyDB();
			//取得總數
			$sql='
				SELECT
					COUNT(*) C
				FROM
					託播單,版位 版位,版位 版位類型,託播單狀態,使用者
				WHERE
					託播單.版位識別碼=版位.版位識別碼 AND
					版位.上層版位識別碼=版位類型.版位識別碼 AND
					託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼 AND
					託播單狀態.託播單狀態名稱 IN (\'確定\') AND
					託播單.CREATED_PEOPLE=使用者.使用者識別碼 AND
					託播單.預約到期時間>=\''.date('Ymd').'\'
				ORDER BY
					託播單.預約到期時間 ASC,
					託播單.廣告期間開始時間 ASC,
					託播單.廣告期間結束時間 ASC,
					託播單.託播單識別碼
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$totalRowCount=$result[0]['C'];
			//取得資料
			$sql='
				SELECT
					託播單狀態.託播單狀態名稱,
					託播單.託播單識別碼,託播單.託播單名稱,託播單.廣告期間開始時間,託播單.廣告期間結束時間,託播單.預約到期時間,
					版位類型.版位名稱 AS 版位類型名稱,
					版位.版位名稱,
					使用者.使用者姓名 建立者
				FROM
					託播單,版位 版位,版位 版位類型,託播單狀態,使用者
				WHERE
					託播單.版位識別碼=版位.版位識別碼 AND
					版位.上層版位識別碼=版位類型.版位識別碼 AND
					託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼 AND
					託播單狀態.託播單狀態名稱 IN (\'確定\') AND
					託播單.CREATED_PEOPLE=使用者.使用者識別碼 AND
					託播單.預約到期時間>=\''.date('Ymd').'\'
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			if(!$result=$my->getResultArray($sql,'i',$fromRowNo)) $result=array();			
			$data =[];
			foreach($result as $row){
				$data[] = [
					['檢視託播單','button'],[$row['託播單狀態名稱'],'text'],[$row['託播單識別碼'],'text'],[$row['託播單名稱'],'text'],[$row['廣告期間開始時間'],'text'],[$row['廣告期間結束時間'],'text']
					,[$row['預約到期時間'],'text'],[$row['版位類型名稱'],'text'],[$row['版位名稱'],'text'],[$row['建立者'],'text']
					];
			}
			exit(json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('檢視託播單','託播單狀態','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','預約到期時間','版位類型名稱','版位名稱','建立者')
							,'data'=>$data,'sortable'=>array('託播單狀態','版位類型名稱','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','預約到期時間','版位類型名稱','版位名稱','建立者')),JSON_UNESCAPED_UNICODE)
				);
		}
		else if($_POST['action']==='素材未選'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$my=new MyDB();
			//取得總數
			$sql='
				SELECT
					COUNT(*) C
				FROM
					託播單
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
					INNER JOIN 版位素材類型 ON 版位素材類型.版位識別碼=版位類型.版位識別碼
					INNER JOIN 素材類型 ON 素材類型.素材類型識別碼=版位素材類型.素材類型識別碼
					LEFT JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼 AND 託播單素材.素材順序=版位素材類型.素材順序
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單.託播單狀態識別碼 IN(0,1)
					AND	版位素材類型.託播單素材是否必填=true
					AND (素材.素材原始檔名 IS NULL OR 素材.素材原始檔名=\'\')
					AND	託播單.預約到期時間>=\''.date('Ymd').'\'
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$totalRowCount=$result[0]['C'];
			//取得資料
			$sql='
				SELECT
					託播單.託播單識別碼,
					託播單.託播單名稱,
					託播單.廣告期間開始時間,
					託播單.廣告期間結束時間,
					託播單.預約到期時間,
					版位類型.版位名稱 AS 版位類型名稱,
					版位.版位名稱,
					版位素材類型.素材順序,
					素材類型.素材類型名稱,
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
					INNER JOIN 版位素材類型 ON 版位素材類型.版位識別碼=版位類型.版位識別碼
					INNER JOIN 素材類型 ON 素材類型.素材類型識別碼=版位素材類型.素材類型識別碼
					LEFT JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼 AND 託播單素材.素材順序=版位素材類型.素材順序
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單.託播單狀態識別碼 IN(0,1)
					AND	版位素材類型.託播單素材是否必填=true
					AND (素材.素材原始檔名 IS NULL OR 素材.素材原始檔名=\'\')
					AND	託播單.預約到期時間>=\''.date('Ymd').'\'
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			if(!$result=$my->getResultArray($sql,'i',$fromRowNo)) $result=array();			
			$data =[];
			foreach($result as $row){
				$data[] = [
					['檢視託播單','button'],[$row['託播單識別碼'],'text'],[$row['託播單名稱'],'text'],[$row['廣告期間開始時間'],'text'],[$row['廣告期間結束時間'],'text']
					,[$row['預約到期時間'],'text'],[$row['版位類型名稱'],'text'],[$row['版位名稱'],'text'],[$row['素材順序'],'text'],[$row['素材類型名稱'],'text'],[$row['建立者'],'text']
					];
			}
			exit(json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('檢視託播單','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','預約到期時間','版位類型名稱','版位名稱','素材順序','素材類型名稱','建立者')
							,'data'=>$data,'sortable'=>array('版位類型名稱','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','預約到期時間','版位類型名稱','版位名稱','素材順序','素材類型名稱','建立者')),JSON_UNESCAPED_UNICODE)
				);
		}
		else if($_POST['action']==='取得待處理的託播單'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$my=new MyDB();
			//取得總數
			$sql='
				SELECT
					COUNT(*) C
				FROM
					託播單
					INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單狀態.託播單狀態名稱=\'待處理\'
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$totalRowCount=$result[0]['C'];
			
			$sql='
				SELECT
					託播單.託播單CSMS群組識別碼,
					託播單.託播單識別碼,
					託播單.託播單名稱,
					託播單.廣告期間開始時間,
					託播單.廣告期間結束時間,
					版位類型.版位名稱 AS 版位類型名稱,
					版位.版位名稱,
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單狀態.託播單狀態名稱=\'待處理\'
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			if(!$result=$my->getResultArray($sql,'i',$fromRowNo)) $result=array();			
			$data =[];
			foreach($result as $row){
				$data[] = [
					['檢視託播單','button'],[$row['託播單CSMS群組識別碼'],'text'],[$row['託播單識別碼'],'text'],[$row['託播單名稱'],'text'],[$row['廣告期間開始時間'],'text'],[$row['廣告期間結束時間'],'text']
					,[$row['版位類型名稱'],'text'],[$row['版位名稱'],'text'],[$row['建立者'],'text']
					];
			}
			exit(json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('檢視託播單','託播單CSMS群組識別碼','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','版位類型名稱','版位名稱','建立者')
							,'data'=>$data,'sortable'=>array('託播單CSMS群組識別碼','版位類型名稱','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','版位類型名稱','版位名稱','建立者')),JSON_UNESCAPED_UNICODE)
				);
		}
		else if($_POST['action']==='取得送出失敗的CSMS託播單'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$my=new MyDB();
			//取得總數
			$sql='
				SELECT
					COUNT(*) AS C
				FROM
					託播單
					INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					版位類型.版位名稱 IN("首頁banner","專區banner","頻道short EPG banner","專區vod")
					AND 託播單狀態.託播單狀態名稱 IN("確定","送出","逾期")
					AND 託播單.託播單送出後是否成功 = 0
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$totalRowCount=$result[0]['C'];
			$sql='
				SELECT
					版位類型.版位名稱 AS 版位類型名稱,
					版位.版位名稱,
					託播單.託播單CSMS群組識別碼,
					託播單.託播單識別碼,
					託播單.託播單名稱,
					託播單.廣告期間開始時間,
					託播單.廣告期間結束時間,
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					版位類型.版位名稱 IN("首頁banner","專區banner","頻道short EPG banner","專區vod")
					AND 託播單狀態.託播單狀態名稱 IN("確定","送出","逾期")
					AND 託播單.託播單送出後是否成功 = 0
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			if(!$result=$my->getResultArray($sql,'i',$fromRowNo)) $result=array();			
			$data =[];
			foreach($result as $row){
				$data[] = [
					['檢視託播單','button'],[$row['版位類型名稱'],'text'],[$row['版位名稱'],'text'],[$row['託播單CSMS群組識別碼'],'text'],[$row['託播單識別碼'],'text'],[$row['託播單名稱'],'text'],[$row['廣告期間開始時間'],'text']
					,[$row['廣告期間結束時間'],'text'],[$row['建立者'],'text'],['CSMS回傳結果','button']
					];
			}
			exit(json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('檢視託播單','版位類型名稱','版位名稱','託播單CSMS群組識別碼','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','建立者','CSMS回傳結果')
							,'data'=>$data,'sortable'=>array('版位類型名稱','版位名稱','託播單CSMS群組識別碼','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','建立者')),JSON_UNESCAPED_UNICODE)
				);
		}
		else if($_POST['action']==='內部錯誤訊息託播單'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$my=new MyDB();
			//取得總數
			$sql='
				SELECT
					COUNT(*) AS C
				FROM
					託播單
					INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單狀態.託播單狀態名稱 IN("送出")
					AND 託播單.託播單送出後是否成功 = 1
					AND 託播單.託播單送出後內部錯誤訊息 IS NOT NULL
					AND	託播單.廣告期間結束時間>=\''.date('Ymd').'\'
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$totalRowCount=$result[0]['C'];
			//取得資料
			$sql='
				SELECT
					版位類型.版位名稱 AS 版位類型名稱,
					版位.版位名稱,
					託播單.託播單識別碼,
					託播單.託播單名稱,
					託播單.廣告期間開始時間,
					託播單.廣告期間結束時間,
					託播單.託播單送出後內部錯誤訊息,
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單狀態.託播單狀態名稱 IN("送出")
					AND 託播單.託播單送出後是否成功 = 1
					AND 託播單.託播單送出後內部錯誤訊息 IS NOT NULL
					AND	託播單.廣告期間結束時間>=\''.date('Ymd').'\'
		
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			if(!$result=$my->getResultArray($sql,'i',$fromRowNo)) $result=array();			
			$data =[];
			foreach($result as $row){
				$data[] = [
					['檢視託播單','button'],[$row['版位類型名稱'],'text'],[$row['版位名稱'],'text'],[$row['託播單識別碼'],'text'],[$row['託播單名稱'],'text'],[$row['廣告期間開始時間'],'text'],[$row['廣告期間結束時間'],'text']
					,[$row['託播單送出後內部錯誤訊息'],'text'],[$row['建立者'],'text']
					];
			}
			exit(json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('檢視託播單','版位類型名稱','版位名稱','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','託播單送出後內部錯誤訊息','建立者')
							,'data'=>$data,'sortable'=>array('版位類型名稱','版位名稱','託播單識別碼','託播單名稱','廣告期間開始時間'
							,'廣告期間結束時間','託播單送出後內部錯誤訊息','建立者')),JSON_UNESCAPED_UNICODE)
				);
		}
	}
?>
<!doctype html>
<html>
<head>
<style type="text/css">
.statement {
	font-size:8px;
	color: gray;
}
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
	include('tool/sameOriginXfsBlock.php');
?>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<script src="tool/jquery-3.4.1.min.js"></script>
<script src="tool/jquery.loadmask.js"></script>
<script src="tool/datagrid/CDataGrid.js"></script>
<script src="tool/jquery-ui1.2/jquery-ui.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css">
<script>
$(document).ready(function(){
	//顯示託播單尚未確定的託播單
	$('#DG1').mask('取得資料中...');
	var bypost1 = {
		action:'尚未確定託播單'
		,pageNo:1
		,order:'版位類型名稱'
		,asc:'ASC'
		};
	$.post(null,bypost1
		,function(json){
			$('#DG1').unmask();
			var DG1=new DataGrid('DG1',json.header,json.data);
			DG1.set_page_info(json.pageNo,json.maxPageNo);
			DG1.set_sortable(json.sortable,true);
			//頁數改變動作
			DG1.pageChange=function(toPageNo) {
				bypost1.pageNo=toPageNo;
				DG1.update();
			}
			//header點擊
			DG1.headerOnClick = function(headerName,sort){
				bypost1.order=headerName;
				switch(sort){
				case "increase":
					bypost1.asc='ASC';
					break;
				case "decrease":
					bypost1.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG1.update();
			};
			//按鈕點擊
			DG1.buttonCellOnClick=function(y,x,row) {
				location.assign('order/orderInfo.php?name='+row[2][0]+'&parent=訂單管理');
			}
			DG1.update=function(){
					$.post('?',bypost1,function(json) {DG1.set_data(json.data);},'json');
				}
		}
	,'json');
	
	//顯示託播單尚未送出的託播單
	$('#DG2').mask('取得資料中...');
	var bypost2 = {
		action:'尚未送出託播單'
		,pageNo:1
		,order:'版位類型名稱'
		,asc:'ASC'
		};
	$.post(null,bypost2
		,function(json){
			$('#DG2').unmask();
			var DG2=new DataGrid('DG2',json.header,json.data);
			DG2.set_page_info(json.pageNo,json.maxPageNo);
			DG2.set_sortable(json.sortable,true);
			//頁數改變動作
			DG2.pageChange=function(toPageNo) {
				bypost2.pageNo=toPageNo;
				DG2.update();
			}
			//header點擊
			DG2.headerOnClick = function(headerName,sort){
				bypost2.order=headerName;
				switch(sort){
				case "increase":
					bypost2.asc='ASC';
					break;
				case "decrease":
					bypost2.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG2.update();
			};
			//按鈕點擊
			DG2.buttonCellOnClick=function(y,x,row) {
				location.assign('order/orderInfo.php?name='+row[2][0]+'&parent=訂單管理');
			}
			DG2.update=function(){
					$.post('?',bypost2,function(json) {DG2.set_data(json.data);},'json');
				}
		}
	,'json');
	
	//顯示託播單素材尚未到位的資料
	$('#DG3').mask('取得資料中...');
	var bypost3 = {
		action:'素材未選'
		,pageNo:1
		,order:'版位類型名稱'
		,asc:'ASC'
		};
	$.post(null,bypost3
		,function(json){
			$('#DG3').unmask();
			var DG3=new DataGrid('DG3',json.header,json.data);
			DG3.set_page_info(json.pageNo,json.maxPageNo);
			DG3.set_sortable(json.sortable,true);
			//頁數改變動作
			DG3.pageChange=function(toPageNo) {
				bypost3.pageNo=toPageNo;
				DG3.update();
			}
			//header點擊
			DG3.headerOnClick = function(headerName,sort){
				bypost3.order=headerName;
				switch(sort){
				case "increase":
					bypost3.asc='ASC';
					break;
				case "decrease":
					bypost3.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG3.update();
			};
			//按鈕點擊
			DG3.buttonCellOnClick=function(y,x,row) {
				location.assign('order/orderInfo.php?name='+row[1][0]+'&parent=訂單管理');
			}
			DG3.update=function(){
					$.post('?',bypost3,function(json) {DG3.set_data(json.data);},'json');
				}
		}
	,'json');
	
	//顯示託播單已送出且CSMS處理中的資料
	$('#DG4').mask('取得資料中...');
	var bypost4 = {
		action:'取得待處理的託播單'
		,pageNo:1
		,order:'版位類型名稱'
		,asc:'ASC'
		};
	$.post(null,bypost4
		,function(json){
			$('#DG4').unmask();
			var DG4=new DataGrid('DG4',json.header,json.data);
			DG4.set_page_info(json.pageNo,json.maxPageNo);
			DG4.set_sortable(json.sortable,true);
			//頁數改變動作
			DG4.pageChange=function(toPageNo) {
				bypost4.pageNo=toPageNo;
				DG4.update();
			}
			//header點擊
			DG4.headerOnClick = function(headerName,sort){
				bypost4.order=headerName;
				switch(sort){
				case "increase":
					bypost4.asc='ASC';
					break;
				case "decrease":
					bypost4.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG4.update();
			};
			//按鈕點擊
			DG4.buttonCellOnClick=function(y,x,row) {
				location.assign('order/orderInfo.php?name='+row[2][0]+'&parent=訂單管理');
			}
			DG4.update=function(){
					$.post('?',bypost4,function(json) {DG4.set_data(json.data);},'json');
				}
		}
	,'json');
	
	//顯示託播單已送出且CSMS處理失敗的資料
	$('#DG5').mask('取得資料中...');
	var bypost5 = {
		action:'取得送出失敗的CSMS託播單'
		,pageNo:1
		,order:'廣告期間開始時間'
		,asc:'DESC'
		};
	$.post(null,bypost5
		,function(json){
			$('#DG5').unmask();
			var DG5=new DataGrid('DG5',json.header,json.data);
			DG5.set_page_info(json.pageNo,json.maxPageNo);
			DG5.set_sortable(json.sortable,true);
			//頁數改變動作
			DG5.pageChange=function(toPageNo) {
				bypost5.pageNo=toPageNo;
				DG5.update();
			}
			//header點擊
			DG5.headerOnClick = function(headerName,sort){
				bypost5.order=headerName;
				switch(sort){
				case "increase":
					bypost5.asc='ASC';
					break;
				case "decrease":
					bypost5.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG5.update();
			};
			//按鈕點擊
			DG5.buttonCellOnClick=function(y,x,row) {
				var id = row[4][0];
				if(row[x][0] == '檢視託播單'){
					location.assign('order/orderInfo.php?name='+id+'&parent=訂單管理');	
				}
				else{
					//CSMS託播單送出失敗資訊
					var dialog = $('<div><iframe width="100%" height="100%" src="casting/showCSMSResult.php?id='+id+'"></iframe></div>')
						.appendTo('body')
						.dialog({
							width: '100%',
							height: 450,
							modal: true,
							title: '託播單送出結果',
							close:function(event, ui){
							dialog.dialog("close");
							dialog.remove()}
						});
				}
			}
			DG5.update=function(){
					$.post('?',bypost5,function(json) {DG5.set_data(json.data);},'json');
				}
		}
	,'json');
	
	//顯示託播單已送出但有告警訊息的託播單
	$('#DG6').mask('取得資料中...');
	var bypost6 = {
		action:'內部錯誤訊息託播單'
		,pageNo:1
		,order:'版位類型名稱'
		,asc:'ASC'
		};
	$.post(null,bypost6
		,function(json){
			$('#DG6').unmask();
			var DG6=new DataGrid('DG6',json.header,json.data);
			DG6.set_page_info(json.pageNo,json.maxPageNo);
			DG6.set_sortable(json.sortable,true);
			//頁數改變動作
			DG6.pageChange=function(toPageNo) {
				bypost6.pageNo=toPageNo;
				DG6.update();
			}
			//header點擊
			DG6.headerOnClick = function(headerName,sort){
				bypost6.order=headerName;
				switch(sort){
				case "increase":
					bypost6.asc='ASC';
					break;
				case "decrease":
					bypost6.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG6.update();
			};
			//按鈕點擊
			DG6.buttonCellOnClick=function(y,x,row) {
				location.assign('order/orderInfo.php?name='+row[3][0]+'&parent=訂單管理');
			}
			DG6.update=function(){
					$.post('?',bypost6,function(json) {DG6.set_data(json.data);},'json');
				}
		}
	,'json');
});
</script>
</head>
<body>
<div class ='statement'>中華電信股份有限公司版權所有，本系統為公司之重要資產，非經授權，不得使用；離座時請跳離畫面，以確保資料之保密性與安全性 <br>
您現在存取的是中華電信股份有限公司之系統，所有的系統使用情形將會受到監控與記錄，使用本系統表示您已同意被監控與記錄，並遵守相關法規及公司規定。如未經授權使用本系統，本公司將可能採取法律行動。</div>
<hr>
<h1>託播單尚未確定：</h1>
<div id="DG1"></div>
<hr>
<h1>託播單尚未送出：</h1>
<div id="DG2"></div>
<hr>
<h1>託播單素材尚未到位：</h1>
<div id="DG3"></div>
<hr>
<h1>託播單已送出且CSMS處理中：</h1>
<div id="DG4"></div>
<hr>
<h1>託播單已送出且CSMS處理失敗：</h1>
<div id="DG5"></div>
<hr>
<h1>託播單已送出成功但有告警訊息：</h1>
<div id="DG6"></div>
<hr>
</body>
</html>