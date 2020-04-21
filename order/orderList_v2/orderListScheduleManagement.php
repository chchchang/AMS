<?php
	include('../../tool/auth/authAJAX.php');
	if(!isset($_GET['oid'])){
		exit("非法輸入");
	}
	$oid = htmlspecialchars($_GET["oid"], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;"/>
<link rel='stylesheet' type='text/css' href='../../external-stylesheet.css' />
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../../tool/datagrid/CDataGrid.js"></script>
<script src="../../tool/jquery-ui1.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel="stylesheet" href="../../tool/jquery-ui1.2/jquery-ui.css">
</head>
<body>
<div id = "scheuleDatagrid"></div>
<div class = "orderOperSet">
<fieldset>
<p>委刊單排程下託播單管理</p>
搜尋託播單:<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder=""></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
<button id="neworderBtn" class="darkButton">+新增託播單於此排程</button>
<button id="batchEdit" class="darkButton">修改選擇託播單</button>
<button id="batchDelete" class="darkButton">刪除選擇託播單</button>
<button id="selectAllOrder" class="darkButton">全選託播單</button>
<button id="unSelectAllOrder" class="darkButton">取消全選託播單</button>
<div id = "orderDatagrid"></div>
</fieldset>
</div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<script type="text/javascript">
	var showAminationTime = 500;
	var orderListId = <?=$oid?>;
	var selectedScheduleId = "";
	//dialog設定
	$( "#dialog_form" ).dialog(
		{
		autoOpen: false,
		width: '80%',
		height: '80%',
		modal: true
		}
	);
	//按下enter查詢託播單
	$("#shearchText").keypress(function(event){
		if (event.keyCode == 13){
			showOrderGrid(selectedId);
		}
	});
	$("#searchButton").click(function(){
		showOrderGrid(selectedId);		
	});

	var ajaxtodbPath = "ajaxToDB_GetOrderList.php";
		
	showDataGrid();
	function showDataGrid(){
		$(".orderOperSet").hide();
		$('#scheuleDatagrid').html('');
		var bypost={method:'取得委刊單排程資料表',pageNo:1,order:'委刊單排程識別碼',asc:'ASC','委刊單識別碼':orderListId};
		$.post(ajaxtodbPath,bypost,function(json){
				json.header.push('託播單管理');
				for(var row in json.data){
					json.data[row].push(['託播單管理','button']);
				}
				var DG=new DataGrid('scheuleDatagrid',json.header,json.data);
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
					selectedId = row[0][0];
					if(row[x][0] == '託播單管理'){
						if(DG.is_collapsed()){
							$('#orderDatagrid').html('');
							$(".orderOperSet").hide();
							DG.uncollapse();
						}
						else{
							selectedScheduleId = selectedId;
							showOrderGrid(selectedId);
							DG.collapse_row(y);
						}
					}
					
				}
				
				DG.update=function(){
					$.post(ajaxtodbPath,bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['託播單管理','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
				$("#scheuleDatagrid").hide().slideDown(showAminationTime);
			}
			,'json'
		);
	}
	
	function showOrderGrid(selectedId){
		$(".orderOperSet").show();
		$('#orderDatagrid').html('');
		var bypost={method:'取得委刊單排程託播單資料表',pageNo:1,order:'委刊單排程識別碼',asc:'DESC','委刊單排程識別碼':selectedId,"searchBy":$("#shearchText").val()};
		$.post(ajaxtodbPath,bypost,function(json){
				json.header.push('修改','刪除','多選');
				for(var row in json.data){
					json.data[row].push(['修改','button']);
					json.data[row].push(['刪除','button']);
					json.data[row].push(['<input type="checkbox" name="orderCheckBox" oid ="'+json.data[row][0][0]+'" status ="'+json.data[row][3][0]+'">','html']);
				}
				var DG=new DataGrid('orderDatagrid',json.header,json.data);
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
					var id = row[0][0];
					var Name = row[1][0];
					if(row[x][0] == '修改'){
						$("#dialog_iframe").attr("src",encodeURI("../newOrder.php?saveBtnText=修改託播單&update="+id))
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8,modal: true, title:"編輯託播單"});
						dialog.dialog( "open" );
					}
					else if(row[x][0] == '刪除'){
						if(row[3][0]!="預約"){
							alert("託播單需為預約狀態才可刪除");
						}
						else{
							//儲存
							savedEdit={"delete":[id],"edit":[]};
							var input = {
								"orders":[],
								"orderListId":orderListId,
								"edits":savedEdit
							};
							saveChange(input);
							showOrderGrid(selectedScheduleId);
						}
					}
				}
				
				DG.update=function(){
					$.post(ajaxtodbPath,bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['修改','button'],['刪除','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
				
				$("#orderDatagrid").hide().slideDown(showAminationTime);
			}
			,'json'
		);	
	}

	//新增資料
	$("#neworderBtn").click(function(){
		//於 session中增加託播單，再讓newOrder.php讀取
		var bypost = {method:"取得排程生成託播單資料",'委刊單排程識別碼':selectedId};
		$.post(ajaxtodbPath,bypost,
		function(data){
			if(!data["success"]){
				alert(data["message"]);
			}
			else{
				order = data["data"];
				//先清除前一次的暫存
				$.post("../orderSession.php",{"saveLastOrder":order}
					,function(rdata){
						//儲存資料
						//開啟新增託播單畫面
						//pids = order["版位識別碼"].split(',');
						pids = order["版位識別碼"];
						$("#dialog_iframe").attr("src","../newOrder.php?saveBtnText=儲存&orderListName="+order["委刊單名稱"]+"&positionTypeId="+order["版位類型識別碼"]+"&positionId="+pids[0])
						.css({"width":"100%","height":"100%"}); 
						$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"新增託播單"});
						$( "#dialog_form" ).dialog( 'open' );
					}
				);
			}
		},"json"
		)
	});
	
	//備newOrder.php呼叫，儲存託播單的動作
	function newOrderSaved(orders){
		$.post("../ajaxToDB_Order.php",{"action":"檢察素材CSMS","orders":JSON.stringify(orders)},
			function(data){
				if(!data['success'])
					alert(data['message']);
				else{
					for(var i in data['result']){
						if(!data['result'][i]['success']){
							if(!confirm(data['result'][i]['message']+'是否繼續?'))
							return 0;
						}
					}
					//儲存
					var input={
						"orders":orders,
						"orderListId":orderListId,
						"edits":[]
					};
					saveChange(input);	
					showOrderGrid(selectedScheduleId);
				}
			},'json'
		);
	}
	
	//備newOrder.php呼叫，儲存託播單的動作
	function updateOrder(jobject){
		savedOrder = [];
		savedEdit={"delete":[],"edit":[jobject]};
		//檢查CSMS託播單規則
		$.post("../ajaxToDB_Order.php",{"action":"檢察素材CSMS","orders":JSON.stringify(savedOrder.concat(savedEdit["edit"]))},
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
					var data = {"orders":savedOrder,
							 "orderListId":$( "#orderList option:selected" ).val(),
							 "edits":savedEdit};
					if(jobject['版位類型名稱']=='頻道short EPG banner'){
						if(confirm('修改「頻道short EPG banner」的託播單時，同CSMS群組且同區域的託播單將一起被修改，是否繼續?')){
							saveChange(data);
						}
					}
					else
						saveChange(data);
					showOrderGrid(selectedScheduleId);
				}
			},'json'
		);
	}
	
	//全選託播單按鈕
	$("#selectAllOrder").click(function(){
		$("input[name=orderCheckBox]").each(function(){
			$(this).prop( "checked", true );
		});
	});
	
	//取消全選託播單按鈕
	$("#unSelectAllOrder").click(function(){
		$("input[name=orderCheckBox]").each(function(){
			$(this).prop( "checked", false );
		});
	});
	
	$("#batchEdit").click( function(){
		var allid = getAllCheckedOrderId();
		$("#dialog_iframe").attr("src","../editOrderBatch.php?oids="+allid.join(","))
			.css({"width":"100%","height":"100%"}); 
			$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"新增託播單"});
			$( "#dialog_form" ).dialog( 'open' );
	});
	
	$("#batchDelete").click( function(){
		var allid = getAllCheckedOrderId();
		savedEdit={"delete":allid,"edit":[]};
		var input = {
			"orders":[],
			"orderListId":orderListId,
			"edits":savedEdit
		};
		saveChange(input);
		showOrderGrid(selectedScheduleId);
	});
	
	//選擇所有預約狀態的託播單
	function getAllCheckedOrderId(){
		var idarray=[];
		$("input[name=orderCheckBox]:checked").each(function(){
			if($(this).attr( "status" )== "預約")
			idarray.push($(this).attr( "oid" ));
		});
		return idarray;
	}
	
	//儲存託播單
	function saveChange(input){
		var byPost = {"action":"儲存更變",
					 "orders":JSON.stringify(input["orders"]),
					 "orderListId":input["orderListId"],
					 "edits":JSON.stringify(input["edits"])};
		/*$.post("../ajaxToDB_Order.php",
			byPost,
			function(data){
				if(data["dbError"]!=undefined){
					alert(data["dbError"]);
					return 0;
				}
				if(data["success"]){
					var insertIds = data["insertIds"];
					var deleteIds = data["deleteIds"];
					$.post("ajaxToDB_NewOrderList.php",
						{method:"更新委刊單排程生成託播放單",
						"新增託播單識別碼":insertIds,
						"委刊單排程識別碼":selectedScheduleId,
						"刪除託播單識別碼":deleteIds},
						function(json){
							if(json["success"]){
								alert(json["message"]);
								$( "#dialog_form" ).dialog( 'close' );
							}
							else{
								alert(json["message"]);
							}
						}
						,"json"
					);
				}
			}
			,'json'
		);*/
		
		$.ajax({
			"type":"post",
			"url":"../ajaxToDB_Order.php",
			"async":false,
			"dataType": 'json',
			"data":byPost,
			"success": 
			function(data){
				if(data["dbError"]!=undefined){
					alert(data["dbError"]);
					return 0;
				}
				if(data["success"]){
					var insertIds = data["insertIds"];
					var deleteIds = data["deleteIds"];
					$.ajax({
						"type":"post",
						"url":"ajaxToDB_NewOrderList.php",
						"async":false,
						"dataType": 'json',
						"data":{method:"更新委刊單排程生成託播放單",
								"新增託播單識別碼":insertIds,
								"委刊單排程識別碼":selectedScheduleId,
								"刪除託播單識別碼":deleteIds},
						"success": 
							function(json){
								if(json["success"]){
									alert(json["message"]);
									$( "#dialog_form" ).dialog( 'close' );
								}
								else{
									alert(json["message"]);
								}
							}
					})
				}
			}
		});
	}
</script>
</body>
</html>