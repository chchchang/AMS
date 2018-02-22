<?php 
	include('../tool/auth/auth.php');
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
		//取得廣告主資料
		if($_POST['method'] == 'AdOwnerInfo'){
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
				SELECT 廣告主.廣告主識別碼 AS 廣告主識別碼,廣告主名稱,頻道商名稱,承銷商名稱,COUNT(委刊單名稱)
				FROM 廣告主 LEFT JOIN 委刊單 ON 廣告主.廣告主識別碼=委刊單.廣告主識別碼
				WHERE DELETED_TIME IS null AND DISABLE_TIME IS null AND (廣告主.廣告主識別碼 LIKE ? OR 廣告主名稱 LIKE ? OR 頻道商名稱 LIKE ? OR 承銷商名稱 LIKE ?)
				GROUP BY 廣告主識別碼
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
				$orders[]=array(array($row['廣告主識別碼'],'text'),array($row['廣告主名稱'],'text'),array(($row['頻道商名稱']==null)?'':$row['頻道商名稱'],'text'),array(($row['承銷商名稱']==null)?'':$row['承銷商名稱'],'text')
				,($row['COUNT(委刊單名稱)']==0)?array('','text'):array('委刊單資料','button'),array('新增委刊單','button'));

			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('廣告主識別碼','廣告主名稱','頻道商名稱','承銷商名稱','委刊單資料','新增委刊單')
							,'data'=>$orders,'sortable'=>array('廣告主識別碼','廣告主名稱','頻道商名稱','承銷商名稱')),JSON_UNESCAPED_UNICODE);
			exit;
		}
		//取得委刊單資料
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
				SELECT 委刊單識別碼,委刊單名稱,委刊單說明,CREATED_TIME AS 建立時間,LAST_UPDATE_TIME AS 修改時間
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
			
			while($row=$res->fetch_assoc())
				$orders[]=array(array($row['委刊單識別碼'],'text'),array($row['委刊單名稱'],'text'),array(($row['委刊單說明'])?'':$row['委刊單說明'],'text'),array($row['建立時間'],'text'),array(($row['修改時間']==null)?'':$row['修改時間'],'text'));

			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('委刊單識別碼','委刊單名稱','委刊單說明','建立時間','修改時間')
							,'data'=>$orders,'sortable'=>array('委刊單識別碼','委刊單名稱','委刊單說明','建立時間','修改時間')),JSON_UNESCAPED_UNICODE);
			exit;
		}
	}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
</head>
<body>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
	<div id="searchForm" class = "basicBlock">
		<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入廣告主名稱、承銷商名稱、頻道商名稱查詢"></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
	</div>
	<div id = "datagrid"></div>
	<div id = "datagrid2"></div>
</body>
<script>
	var DG = null , DG2=null;
	
	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				search();
			}
		}).autocomplete({
			source :function( request, response) {
						$.post( "../order/autoComplete_forOrderSearchBox.php",{term: request.term, method:'廣告主查詢'},
							function( data ) {
							response(JSON.parse(data));
						})
					}
		});
		
		$('#searchButton').click(function(){search();});
		$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});
	
	$('#datagrid').html('');
	var bypost={method:'AdOwnerInfo',searchBy:$('#shearchText').val(),pageNo:1,order:'廣告主識別碼',asc:'ASC'};
	$.post('?',bypost,function(json){
		DG=new DataGrid('datagrid',json.header,json.data);
		DG.set_page_info(json.pageNo,json.maxPageNo);
		DG.set_sortable(json.sortable,true);
		//頁數改變動作
		DG.pageChange=function(toPageNo) {
			bypost.pageNo=toPageNo;
			$.post('?',bypost,function(json) {
				DG.set_data(json.data);
			}
			,'json'
			);
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
			$.post('?',bypost,function(json) {
				DG.set_data(json.data);
			},'json');
		};
		//按鈕點擊
		DG.buttonCellOnClick=function(y,x,row) {
			if(row[x][0]=='新增委刊單') {
				$("#dialog_iframe").attr("src","newOrderList.php?RETURN=1&ownerid="+row[0][0])
				.css({"width":"100%","height":"100%"}); 
				$( "#dialog_form" ).dialog({height:400, width:$(window).width()*0.8, title:"新增委刊單"});
				$( "#dialog_form" ).dialog( "open" );
			}
			else if(row[x][0]=='委刊單資料'){
				if(!DG.is_collapsed()){
					DG.collapse_row(y);
					showOrderList(row[0][0]);
				}
				else{
					$('#datagrid2').html('');
					DG.uncollapse();
				}
			}
		}
	}
	,'json'
	);
	
	//搜尋功能
	function search(){
		bypost.searchBy=$('#shearchText').val();
		$.post('?',bypost,function(json) {
				DG.set_data(json.data);
			},'json');
	}
	
	//顯示屬於特定廣告主的委刊單
	function showOrderList(ownerId){
		var bypost={method:'OrderListInfoByAdOwner',searchBy:$('#shearchText').val(),pageNo:1,order:'委刊單識別碼',asc:'DESC',廣告主識別碼:ownerId};
		$.post('?',bypost,function(json){
			DG2=new DataGrid('datagrid2',json.header,json.data);
			DG2.set_page_info(json.pageNo,json.maxPageNo);
			DG2.set_sortable(json.sortable,true);
			//DG2.hide().show(500);
			//頁數改變動作
			DG2.pageChange=function(toPageNo) {
				bypost.pageNo=toPageNo;
				$.post('?',bypost,function(json) {
					DG2.set_data(json.data);
				}
				,'json'
				);
			}
			//header點擊
			DG2.headerOnClick = function(headerName,sort){
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
				$.post('?',bypost,function(json) {
					DG2.set_data(json.data);
				},'json');
			};
			
			DG2.update=function(){
				$.post('?',bypost,function(json) {
						DG2.set_data(json.data);
					}
				,'json'
				);
			}
		}
		,'json'
		);
	}
	
	//由newOrderList呼叫，委刊單完成
	function newOrderListCreated(id,name){
		$( "#dialog_form" ).dialog( "close" );
		if(DG.is_collapsed()){
			DG2.update();
		}
	}
</script>
</html>