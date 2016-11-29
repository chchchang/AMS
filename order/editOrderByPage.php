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
		$sqlSelectFrom = 'SELECT 託播單識別碼,託播單名稱,託播單說明,託播單狀態名稱 AS 託播單狀態,版位名稱 AS 投放版位
				,廣告期間開始時間 AS 開始,廣告期間結束時間 AS 結束,廣告可被播出小時時段 AS 時段
				FROM 版位,託播單 LEFT JOIN 素材 ON 託播單.素材識別碼 = 素材.素材識別碼,託播單狀態 ';
		//取得搜尋的託播單資料
		if($_POST['method'] == 'OrderInfoBySearch'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;	//T.B.D.
			$searchBy='%'.$_POST['searchBy'].'%';
			
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 託播單
				WHERE 託播單識別碼 = ? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ? 
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('iss',$_POST['searchBy'],$searchBy,$searchBy)) {
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
			$sql=$sqlSelectFrom.
				'WHERE 版位.版位識別碼=託播單.版位識別碼 AND(託播單識別碼 = ? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ? ) AND 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('issi',$_POST['searchBy'],$searchBy,$searchBy,$fromRowNo)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			goto stmtExcute;
		}
		//依委刊單取得託播單
		else if($_POST['method'] == 'OrderInfoByOrderList'){

			$sqlcount='
				SELECT COUNT(1) COUNT
				FROM 託播單
				WHERE 委刊單識別碼 =  ? 
			';

			$sql=$sqlSelectFrom.
			'
				WHERE 版位.版位識別碼=託播單.版位識別碼 AND 委刊單識別碼 = ?  AND 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
			';
			$para = $_POST['委刊單識別碼'];
		}
		//依版位取的託播單
		else if($_POST['method'] == 'OrderInfoByPosition'){
			$sqlcount='
				SELECT COUNT(1) COUNT
				FROM 託播單
				WHERE 版位識別碼 =  ? 
			';
			$sql=$sqlSelectFrom.'
				WHERE 版位.版位識別碼=託播單.版位識別碼 AND 託播單.版位識別碼 = ?  AND 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
			';
			$para = $_POST['版位識別碼'];
		}
		//依廣告主取得託播單
		else if($_POST['method'] == 'OrderInfoByAdOwner'){
			$sqlcount='
				SELECT COUNT(1) COUNT
				FROM 託播單,委刊單
				WHERE 託播單.委刊單識別碼 = 委刊單.委刊單識別碼 AND 廣告主識別碼 =  ? 
			';
			$sql=$sqlSelectFrom.',委刊單 
				WHERE 託播單.委刊單識別碼 = 委刊單.委刊單識別碼 AND 版位.版位識別碼 = 託播單.版位識別碼 AND 廣告主識別碼 =  ?  AND 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
			';
			$para = $_POST['廣告主識別碼'];
		}
		//依版位類型取得託播單
		else if($_POST['method'] == 'OrderInfoByPositionType'){
			$sqlcount='
				SELECT COUNT(1) COUNT
				FROM 託播單,版位
				WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 上層版位識別碼 =  ? 
			';
			$sql=$sqlSelectFrom.'
				WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 上層版位識別碼 =  ?  AND 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
			';
			$para = $_POST['版位類型識別碼'];
		}
		
		$orders=array();
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
		$totalRowCount=0;	//T.B.D.
			
		//先取得總筆數
		
		if(!$stmt=$my->prepare($sqlcount)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('i',$para)) {
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
		$sql.=' ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
			'LIMIT ?,'.PAGE_SIZE.'
		';
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('ii',$para,$fromRowNo)) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		stmtExcute:
		if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
		}	
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}		
		while($row=$res->fetch_assoc())
				$orders[]=array(array($row['託播單識別碼'],'text'),array($row['託播單名稱'],'text')
				,array(($row['託播單說明']==null)?'':$row['託播單說明'],'text'),array($row['託播單狀態'],'text'),array($row['投放版位'],'text')
				,array(($row['素材識別碼']==null)?'':$row['素材識別碼'],'text'),array(($row['圖片寬']==null)?'':$row['圖片寬'],'text'),array(($row['圖片高']==null)?'':$row['圖片高'],'text')
				,array(($row['影片秒數']==null)?'':$row['影片秒數'],'text')
				,array($row['開始'],'text'),array($row['結束'],'text'),array($row['時段'],'text')
				);

		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('託播單識別碼','託播單名稱','託播單說明','託播單狀態','投放版位','素材識別碼'
						,'圖片寬','圖片高','影片秒數','開始','結束','時段')
						,'data'=>$orders,'sortable'=>array('託播單識別碼','託播單名稱','託播單說明','託播單狀態','投放版位','素材識別碼','圖片寬','圖片高','影片秒數','開始','結束','時段')),JSON_UNESCAPED_UNICODE);
		exit;
	}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
</head>
<body>
<?php include('_searchOrderUI.php');?>


<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id = "datagrid"></div>
<div id="dialog_form2"><iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
</body>
<script>
	var DG = null;
	$(function() {
		$( "#dialog_form2,#dialog_form" ).dialog({autoOpen: false,	modal: true});
	});
	
	//顯示搜尋的委刊單列表
	function showOrderDG(option){
		$('#datagrid').html('');
		var bypost={
				method:'OrderInfoBySearch'
				,searchBy:$('#_searchOUI_searchOrder').val()
				,廣告主識別碼:$('#_searchOUI_adOwner').val()
				,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
				,版位類型識別碼:$('#_searchOUI_positiontype').val()
				,版位識別碼:$("#_searchOUI_position").val()
				,開始時間:$('#_searchOUI_startDate').val()
				,結束時間:$('#_searchOUI_endDate').val()
				,素材識別碼:$('#_searchOUI_material').val()
				,素材群組識別碼:$('#_searchOUI_materialGroup').val()
				,狀態:$('#_searchOUI_orderStateSelectoin').val()
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};
		$.post('ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('修改託播單','刪除託播單');
				var colNum = $.inArray('託播單狀態',json.header);
				for(var row in json.data){
					json.data[row].push(['修改託播單','button']);
					if(json.data[row][colNum][0] == '預約'||json.data[row][colNum][0] == '逾期')
						json.data[row].push(['刪除託播單','button']);
					else
						json.data[row].push(['','text']);
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
					var oid = DG.getCellText('託播單識別碼',y);
					if(row[x][0]=='修改託播單') {
						$("#dialog_iframe").attr("src",encodeURI("newOrder.php?saveBtnText=修改託播單&update="+oid))
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8,modal: true, title:"編輯託播單"});
						dialog.dialog( "open" );
					}
					else if(row[x][0]=='刪除託播單') {
						if(confirm('刪除後的託播單將無法復原，確定要刪除?')){
							savedEdit={"delete":[oid],"edit":[]};
							$.post("ajaxToDB_Order.php",
									 {"action":"儲存更變",
									 "orders":JSON.stringify([]),
									 "orderListId":$( "#orderList option:selected" ).val(),
									 "edits":JSON.stringify(savedEdit)},
									 function(data){
										if(data["dbError"]!=undefined){
											alert(data["dbError"]);
											return 0;
										}
										if(data["success"]){
											DG.update();
										}
										
										alert(data["message"]);
									}
									,'json'
								);
						}
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('ajaxFunction_OrderInfo.php',bypost,function(json) {
							var colNum = $.inArray('託播單狀態',json.header);
							for(var row in json.data){
								json.data[row].push(['修改託播單','button']);
								if(json.data[row][colNum][0] == '預約')
								json.data[row].push(['刪除託播單','button']);
								else
								json.data[row].push(['','text']);
							}
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	//由newOrder呼叫,修改託播單
	function updateOrder(jobject){
		savedOrder = [];
		savedEdit={"delete":[],"edit":[jobject]};
		//檢查CSMS託播單規則
		$.post("ajaxToDB_Order.php",{"action":"檢察素材CSMS","orders":JSON.stringify(savedOrder.concat(savedEdit["edit"]))},
			function(data){
				if(!data['success'])
					alert(data['message']);
				else{
					for(var i in data['result']){
						if(!data['result'][i]['success']){
							if(!confirm(data['result'][i]['message']+'是否繼續修改?'))
							return 0;
						}
					}
					//儲存
					var byPost = {"action":"儲存更變",
							 "orders":JSON.stringify(savedOrder),
							 "orderListId":$( "#orderList option:selected" ).val(),
							 "edits":JSON.stringify(savedEdit)};
					function saveChange(){
						$.post("ajaxToDB_Order.php",
							byPost,
							function(data){
								if(data["dbError"]!=undefined){
									alert(data["dbError"]);
									return 0;
								}
								if(data["success"]){
									DG.update();
									$('#dialog_form').dialog('close');
								}
								
								alert(data["message"]);
							}
							,'json'
						);
					}
					if(jobject['版位類型名稱']=='頻道short EPG banner'){
						if(confirm('修改「頻道short EPG banner」的託播單時，同CSMS群組且同區域的託播單將一起被修改，是否繼續?')){
							saveChange();
						}
					}
					else
						saveChange();
				}
			},'json'
		);
	}
	
	//由new_order呼叫，打開素材群組詳細資料視窗
	function openMaterialGroupInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/searchMaterialGroup.php?showCertainId="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"素材群組資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
</script>
</html>