<?php 
	include('../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
	if(isset($_POST['method'])){
		$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
		if($my->connect_errno) {
			$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
			exit(json_encode(array('無法連線到資料庫，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$my->set_charset('utf8')) {
			$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		//取得搜尋的委刊單資料
		if($_POST['method'] == 'OrderListInfo'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			$searchBy='%'.$_POST['searchBy'].'%';
			$ownerId= isset($_POST['廣告主識別碼'])?$_POST['廣告主識別碼']:'%';
			
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 委刊單
				WHERE 廣告主識別碼 LIKE ? AND
				(委刊單識別碼 = ? OR 委刊單名稱 LIKE ? OR 委刊單說明 LIKE ? OR 委刊單編號 LIKE ?)
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('sisss',$ownerId,$_POST['searchBy'],$searchBy,$searchBy,$searchBy)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 委刊單識別碼,委刊單編號,委刊單名稱,委刊單說明,CREATED_TIME AS 建立時間, LAST_UPDATE_TIME AS 修改時間
				FROM  委刊單
				WHERE 廣告主識別碼 LIKE ? AND (委刊單識別碼 = ? OR 委刊單名稱 LIKE ? OR 委刊單說明 LIKE ? OR 委刊單編號 LIKE ?)
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('sisssi',$ownerId,$_POST['searchBy'],$searchBy,$searchBy,$searchBy,$fromRowNo)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		}
		//取得廣告主資料
		else if($_POST['method'] == 'AdOwnerInfo'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;	//T.B.D.
			$searchBy='%'.$_POST['searchBy'].'%';
			
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 廣告主
				WHERE DELETED_TIME IS null AND DISABLE_TIME IS null AND (廣告主識別碼 = ? OR 廣告主名稱 LIKE ? OR 頻道商名稱 LIKE ? OR 承銷商名稱 LIKE ?)
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('isss',$_POST['searchBy'],$searchBy,$searchBy,$searchBy)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 廣告主識別碼,廣告主名稱,頻道商名稱,承銷商名稱
				FROM 廣告主
				WHERE DELETED_TIME IS null AND DISABLE_TIME IS null AND (廣告主識別碼 LIKE ? OR 廣告主名稱 LIKE ? OR 頻道商名稱 LIKE ? OR 承銷商名稱 LIKE ?)
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('isssi',$_POST['searchBy'],$searchBy,$searchBy,$searchBy,$fromRowNo)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			while($row=$res->fetch_assoc())
				$orders[]=array(array($row['廣告主識別碼'],'text'),array($row['廣告主名稱'],'text'),array($row['頻道商名稱'],'text'),array($row['承銷商名稱'],'text'));
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('廣告主識別碼','廣告主名稱','頻道商名稱','承銷商名稱')
							,'data'=>$orders,'sortable'=>array('廣告主識別碼','廣告主名稱','頻道商名稱','承銷商名稱','委刊單資料')),JSON_UNESCAPED_UNICODE);
			exit;
		}
		//依廣告主取得委刊單資料
		else if($_POST['method'] == 'OrderListInfoByAdOwner'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;	//T.B.D.
			
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 委刊單
				WHERE 廣告主識別碼 = ?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('i',$_POST['廣告主識別碼'])) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 委刊單識別碼,委刊單編號,委刊單名稱,委刊單說明,CREATED_TIME AS 建立時間,LAST_UPDATE_TIME AS 修改時間
				FROM 委刊單
				WHERE 廣告主識別碼 = ?
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('ii',$_POST['廣告主識別碼'],$fromRowNo)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		}
		//依委刊單識別碼得委刊單資料
		else if($_POST['method'] == 'OrderListInfoByID'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;	//T.B.D.
			
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 委刊單
				WHERE 委刊單識別碼 = ?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('i',$_POST['委刊單識別碼'])) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 委刊單識別碼,委刊單編號,委刊單名稱,委刊單說明,CREATED_TIME AS 建立時間,LAST_UPDATE_TIME AS 修改時間
				FROM 委刊單
				WHERE 委刊單識別碼 = ?
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('ii',$_POST['委刊單識別碼'],$fromRowNo)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		}
		
		while($row=$res->fetch_assoc())
			$orders[]=array(array($row['委刊單識別碼'],'text'),array(($row['委刊單編號']==null)?'':$row['委刊單編號'],'text'),array($row['委刊單名稱'],'text'),array(($row['委刊單說明']==null)?'':$row['委刊單說明'],'text'),array(checkOrderStates($row['委刊單識別碼']),'text'),array($row['建立時間'],'text'),array(($row['修改時間']==null)?'':$row['修改時間'],'text'));

		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('委刊單識別碼','委刊單編號','委刊單名稱','委刊單說明','託播單狀態','建立時間','修改時間')
						,'data'=>$orders,'sortable'=>array('委刊單識別碼','委刊單編號','委刊單名稱','建立時間','修改時間')),JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	function checkOrderStates($orderListId){
			$my2=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
			if($my2->connect_errno) {
				$logger->error('無法連線到資料庫，錯誤代碼('.$my2->connect_errno.')、錯誤訊息('.$my2->connect_error.')。');
				exit(json_encode(array('無法連線到資料庫，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$my2->set_charset('utf8')) {
				$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my2->errno.')、錯誤訊息('.$my2->error.')。');
				exit(json_encode(array('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			global $logger;
			
			$sql="SELECT 
				COUNT(*),
				SUM(CASE 託播單狀態識別碼 WHEN 0 THEN 1 ELSE 0 END) 預約,
				SUM(CASE 託播單狀態識別碼 WHEN 1 THEN 1 ELSE 0 END) 確定, 
				SUM(CASE 託播單狀態識別碼 WHEN 2 THEN 1 ELSE 0 END) 送出 ,
				SUM(CASE 託播單狀態識別碼 WHEN 3 THEN 1 ELSE 0 END) 逾期,
				SUM(CASE 託播單狀態識別碼 WHEN 4 THEN 1 ELSE 0 END) 待處理檔案 
				FROM 託播單 WHERE 委刊單識別碼 = ? ";
			
			if(!$stmt=$my2->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my2->errno.')、錯誤訊息('.$my2->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('i',$orderListId)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}

			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}

			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$row = $res->fetch_assoc();
			if($row['COUNT(*)']==0)
				return '未建立';
			else
				return '預約:'.$row['預約'].' 確定:'.$row['確定'].' 送出:'.$row['送出'].' 逾期:'.$row['逾期'].' 待處理檔案:'.$row['待處理檔案'];
		}
	@include('../tool/auth/auth.php');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
</head>
<body>
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">依關鍵字搜尋委刊單</a></li>
    <li><a href="#tabs-2">依廣告主選擇委刊單</a></li>
  </ul>
  <div id="tabs-1">
		<input type="text" id="searchOrderList" class="searchInput" value='' placeholder="輸入委刊單識別碼、編號、名稱、說明查詢"></input><button id="searchOrderListButton" class="searchSubmit">查詢</button>
  </div>
  <div id="tabs-2">
		<input type="text" id="searchOwner" class="searchInput" value='' placeholder="輸入廣告主識別碼、廣告主名稱、承銷商名稱、頻道商名稱查詢"></input><button id="searchOwnerButton" class="searchSubmit">查詢</button>
		<div class = 'basicBlock'>
		<div id = "onwerDatagrid"></div>
		</div>
  </div>
</div>

<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>


	<div id = "datagrid"></div>
</body>
<script>
	var DG = null , OwnerDG=null;
	$(function() {
		//按下ENTER搜尋
		$("#searchOrderList").keypress(function(event){
			if (event.keyCode == 13){
					showOrderList({search : true});
			}
		}).autocomplete({
			source :function( request, response) {
						$.post( "../order/autoComplete_forOrderSearchBox.php",{term: request.term, method:'委刊單查詢'},
							function( data ) {
							response(JSON.parse(data));
						})
					}
		});
		
		$('#searchOrderListButton').click(function(){
				showOrderList({search : true});
		});
		
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		$("#searchOwner").keypress(function(event){
			if (event.keyCode == 13){
					OwnerDG.shearch();
			}
		}).autocomplete({
			source :function( request, response) {
						$.post( "../order/autoComplete_forOrderSearchBox.php",{term: request.term, method:'廣告主查詢'},
							function( data ) {
							response(JSON.parse(data));
						})
					}
		});
		
		$('#searchOwnerButton').click(function(){
				OwnerDG.shearch();
		});
		
		$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
		$( "#tabs" ).tabs();
	});
	
	
	$('#onwerDatagrid').html('');
	//廣告主資料表
	var bypost={method:'AdOwnerInfo',searchBy:$('#searchOwner').val(),pageNo:1,order:'廣告主識別碼',asc:'ASC'};
	$.post('?',bypost,function(json){
		json.header.push('委刊單資料');
		for(var row in json.data){
			json.data[row].push(['委刊單資料','button']);
		}
		OwnerDG=new DataGrid('onwerDatagrid',json.header,json.data);
		OwnerDG.set_page_info(json.pageNo,json.maxPageNo);
		OwnerDG.set_sortable(json.sortable,true);
		//頁數改變動作
		OwnerDG.pageChange=function(toPageNo) {
			bypost.pageNo=toPageNo;
			OwnerDG.update();
		}
		//header點擊
		OwnerDG.headerOnClick = function(headerName,sort){
			bypost.order=headerName;
			switch(sort){
			case "increase":
				bypost.asc='ASC';
				break;
			case "decrease":
				bypost.asc='DESC';
				break;
			case "unsort":
				break;
			}
			OwnerDG.update();
		};
		//按鈕點擊
		OwnerDG.buttonCellOnClick=function(y,x,row) {
			if(row[x][0]=='委刊單資料'){
				if(!OwnerDG.is_collapsed()){
					OwnerDG.collapse_row(y);
					showOrderList({ownerId : row[0][0]});
				}
				else{
					$('#datagrid').html('');
					OwnerDG.uncollapse();
				}
			}
		}
		OwnerDG.shearch=function(){
			bypost.searchBy=$('#searchOwner').val();
			OwnerDG.update();
		}
		
		OwnerDG.update=function(){
			$.post('?',bypost,function(json) {
				for(var row in json.data){
					json.data[row].push(['委刊單資料','button']);
				}
				OwnerDG.set_data(json.data);
				},'json');
		}
	}
	,'json'
	);

	//顯示搜尋的委刊單列表
	function showOrderList(option){
		$('#datagrid').html('');
		var bypost={};
		if(typeof(option.search)!='undefined'&&option.search==true)
			bypost={method:'OrderListInfo',searchBy:$('#searchOrderList').val(),pageNo:1,order:'委刊單識別碼',asc:'DESC'};
		else if(typeof(option.ownerId)!='undefined')
			bypost={method:'OrderListInfoByAdOwner',pageNo:1,order:'委刊單識別碼',asc:'DESC',廣告主識別碼:option.ownerId};
		$.post('?',bypost,function(json){
				json.header.push('修改委刊單');
				for(var row in json.data){
					json.data[row].push(['修改委刊單','button']);
				}
				DG=new DataGrid('datagrid',json.header,json.data);
				DG.set_page_info(json.pageNo,json.maxPageNo);
				DG.set_sortable(json.sortable,true);
				//頁數改變動作
				DG.pageChange=function(toPageNo) {
					bypost.pageNo=toPageNo;
					DG.update();
				}
				//header點擊
				DG.headerOnClick = function(headerName,sort){
					bypost.order=headerName;
					switch(sort){
					case "increase":
						bypost.asc='ASC';
						break;
					case "decrease":
						bypost.asc='DESC';
						break;
					case "unsort":
						break;
					}
					DG.update();
				};
				//按鈕點擊
				DG.buttonCellOnClick=function(y,x,row) {
					if(row[x][0]=='修改委刊單') {
						$("#dialog_iframe").attr("src","newOrderList.php?RETURN=1&action=edit&orderListId="+row[0][0])
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: 400, width:$(window).width()*0.8, title:"編輯委刊單"});
						dialog.dialog( "open" );
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('?',bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['修改委刊單','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	//由newOrderList呼叫,修改委刊單成，關閉視窗
	function orderListUpdated(id,name){
		DG.update();
		dialog.dialog( "close" );
	}
</script>
</html>