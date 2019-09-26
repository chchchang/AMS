<?php
	include('../../tool/auth/authAJAX.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == '刪除委刊單'){
			//檢查是否有託播單
			$sql='
				select COUNT(*) AS C FROM 託播單 WHERE 委刊單識別碼 = ?
			';
			if(!$COUNT = $my->getResultArray($sql,'i',$_POST['委刊單識別碼'])) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'資料庫錯誤，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if($COUNT[0]["C"]!=0){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'已建立託播單，無法刪除。'),JSON_UNESCAPED_UNICODE));
			}
			//先刪除排程
			$my->begin_transaction();
			$sql = "delete from 委刊單排程 WHERE 委刊單識別碼 = ?";
			$typestr = "i";
			if(!$exe=$my->execute($sql,$typestr,$_POST["委刊單識別碼"]))
			{
				$my->rollback();
				$my->close();
				$logger->error('無法清空委刊單排程資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false, "message"=>'刪除委刊單排程失敗'),JSON_UNESCAPED_UNICODE));
			}
			
			//再刪除委刊單
			$sql = "delete from 委刊單 WHERE 委刊單識別碼 = ?";
			if(!$exe=$my->execute($sql,$typestr,$_POST["委刊單識別碼"]))
			{
				$my->rollback();
				$my->close();
				$logger->error('無法清空委刊單資料，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false, "message"=>'刪除委刊單失敗'),JSON_UNESCAPED_UNICODE));
			}
			
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'刪除委刊單(委刊單識別碼:'.$_POST["委刊單識別碼"].')');
			$my->commit();
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));
			
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;"/>
<link rel='stylesheet' type='text/css' href='../../external-stylesheet.css' />
<script type="text/javascript" src="../../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../../tool/datagrid/CDataGrid.js"></script>
<script src="../../tool/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel="stylesheet" href="../../tool/jquery-ui/jquery-ui.css">
</head>
<body>
<div class = "basicBlock">
<div>
搜尋委刊單:<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder=""></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
<button id = "newOrderListBtn" style="width:120px;height:30px;font-size:15px;">新增委刊單</button>
</div>
</div>
<div id = "datagrid"></div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id = "scheduleOperSet">
<fieldset>
<p>委刊單排程管理</p>
<iframe id="schedule_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>
</fieldset>
</div>
<script type="text/javascript">
	var showAminationTime = 500;
	
	$("#scheduleOperSet").hide();	
	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				showDataGrid();
			}
		});
		$("#searchButton").click(function(){
			showDataGrid();		
		});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});
	//dialog設定
	$( "#dialog_form" ).dialog(
		{
		autoOpen: false,
		width: '90%',
		height: '90%',
		modal: true
		});

	var ajaxtodbPath = "ajaxToDB_GetOrderList.php";
		
	
	function showDataGrid(){
		$('#datagrid').html('');
		$("#scheduleOperSet").hide();
		var bypost={method:'取得委刊單資料表',pageNo:1,order:'委刊單識別碼',asc:'DESC',searchBy:$('#shearchText').val()};
		$.post(ajaxtodbPath,bypost,function(json){
				json.header.push('修改','刪除','託播排程管理');
				for(var row in json.data){
					json.data[row].push(['修改','button'],['刪除','button'],['託播排程管理','button']);
				}
				var DG=new DataGrid('datagrid',json.header,json.data);
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
					pId = row[0][0];
					pName = row[1][0];
					if(row[x][0] == '修改'){
						$("#dialog_iframe").attr("src",'newOrderList_version2.php?oid='+pId+'&pageAction=edit').css({"width":"100%","height":"100%"}); 
						$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"訂單資訊"});
						$( "#dialog_form" ).dialog( 'open' );
						$("#dialog_form").on( "dialogclose", function( event, ui ) {DG.update();} );
					}
					else if(row[x][0] == '刪除'){
						if(confirm("確定要刪除委刊單?"))
						$.post('?',{method:'刪除委刊單','委刊單識別碼':row[0][0]}
							,function(json){
								if(json.success)
									DG.update();
								else{
									alert(json.message);
								}
							}
							,'json'
						);
					}
					else if(row[x][0] == '託播排程管理'){
						if(DG.is_collapsed()){
							$('#schedule_iframe').attr("src",'').css({"width":"100%","height":"100%"}); 
							$("#scheduleOperSet").hide();
							DG.uncollapse();
						}
						else{
							$('#schedule_iframe').attr("src",'orderListScheduleManagement.php?oid='+pId).css({"width":"100%","height":"900px"}); 
							$("#scheduleOperSet").show();
							DG.collapse_row(y);
						}
					}
					
				}
				
				DG.update=function(){
					$.post(ajaxtodbPath,bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['修改','button'],['刪除','button'],['託播排程管理','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
				
				$("#datagrid").hide().slideDown(showAminationTime);
			}
			,'json'
		);	
	}

	//新增代理商資料
	$("#newOrderListBtn").click(function(){
		$("#dialog_iframe").attr("src",'newOrderList_version2.php').css({"width":"100%","height":"100%"}); 
		$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"訂單資訊"});
		$( "#dialog_form" ).dialog( 'open' );
	});
	
</script>
</body>
</html>