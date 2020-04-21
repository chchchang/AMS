<?php
	include('../../tool/auth/authAJAX.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == '隱藏代理商'){
			$sql='
				UPDATE 代理商 SET DISABLE_TIME=CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 代理商識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["代理商識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'隱藏代理商(代理商識別碼:'.$_POST["代理商識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '顯示代理商'){
			$sql='
				UPDATE 代理商 SET DISABLE_TIME=NULL, LAST_UPDATE_PEOPLE=? WHERE 代理商識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["代理商識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'顯示代理商(代理商識別碼:'.$_POST["代理商識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '刪除代理商'){
			//統計代理商下的託播單
			$sql='
				SELECT COUNT(*) AS count FROM 託播單,委刊單 WHERE 託播單.委刊單識別碼=委刊單.委刊單識別碼 AND 代理商識別碼=?
			';
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$_POST["代理商識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row = $res->fetch_assoc();
			if($row['count']>0)
				exit(json_encode(array("success"=>false,"message"=>'此代理商已有託播單，無法刪除。'),JSON_UNESCAPED_UNICODE));
			
			$sql='
				UPDATE 代理商 SET DELETED_TIME=CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 代理商識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["代理商識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'刪除代理商(代理商識別碼:'.$_POST["代理商識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;"/>
<link rel='stylesheet' type='text/css' href='../../external-stylesheet.css' />
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../../tool/datagrid/CDataGrid.js"></script>
<script src="../../tool/jquery-ui1.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel="stylesheet" href="../../tool/jquery-ui1.2/jquery-ui.css">
</head>
<body>
<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder=""></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
<button id = "newAgentBtn" style="width:120px;height:30px;font-size:15px;">新增代理商</button>
</div>
</div>
<div id = "datagrid"></div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<script type="text/javascript">
	var showAminationTime = 500;
	
	$(function() {
		//按下enter查詢
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
		width: '80%',
		height: '80%',
		modal: true
		});

	var ajaxtodbPath = "ajaxToDB_Agent.php";
		
	
	function showDataGrid(){
		$('#datagrid').html('');
		var bypost={action:'取得代理商資料表',pageNo:1,order:'代理商識別碼',asc:'DESC',searchBy:$('#shearchText').val()};
		$.post(ajaxtodbPath,bypost,function(json){
				json.header.push('修改','隱藏','顯示','刪除');
				for(var row in json.data){
					json.data[row].push(['修改','button'],['隱藏','button'],['顯示','button'],['刪除','button']);
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
						$("#dialog_iframe").attr("src",'newAgent.php?oid='+pId+'&pageAction=edit').css({"width":"100%","height":"100%"}); 
						$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"修改代理商資訊"});
						$( "#dialog_form" ).dialog( 'open' );
						$("#dialog_form").on( "dialogclose", function( event, ui ) {DG.update();} );
					}
					else if(row[x][0] == '隱藏'){
						$.post('?',{method:'隱藏代理商','代理商識別碼':row[0][0]}
							,function(json){
								if(json.success)
									DG.update();
							}
							,'json'
						);
					}
					else if(row[x][0] == '顯示'){
						$.post('?',{method:'顯示代理商','代理商識別碼':row[0][0]}
							,function(json){
								if(json.success)
									DG.update();
							}
							,'json'
						);
					}
					else if(row[x][0] == '刪除'){
						if(confirm("確定要刪除代理商?"))
						$.post('?',{method:'刪除代理商','代理商識別碼':row[0][0]}
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
					
				}
				
				DG.update=function(){
					$.post(ajaxtodbPath,bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['修改','button'],['隱藏','button'],['顯示','button'],['刪除','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
				
				//隱藏視窗
				function hideInfoWindow(){
					if($(".InfoWindow").length>0){
						$(".InfoWindow").remove();
					}
					if(DG.is_collapsed()){
						DG.uncollapse();
					}
				}	
				$("#datagrid").hide().slideDown(showAminationTime);
			}
			,'json'
		);	
	}

	//新增代理商資料
	$("#newAgentBtn").click(function(){
		$("#dialog_iframe").attr("src",'newAgent.php').css({"width":"100%","height":"100%"}); 
		$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"新增代理商資訊"});
		$( "#dialog_form" ).dialog( 'open' );
	});
	
</script>
</body>
</html>