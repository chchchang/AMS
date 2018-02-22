<?php 	
	include('../tool/auth/authAJAX.php');
	if(isset($_POST['method'])){
		if($my->connect_errno) {
			exit('無法連線到資料庫，請聯絡系統管理員！');
		}
		
		if(!$my->set_charset('utf8')) {
			exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
		}
		
		if($_POST['method']=='getPositionTypeSelection'){
			$sql = 'SELECT 版位識別碼,版位名稱 FROM 版位 WHERE 上層版位識別碼 IS null AND DELETED_TIME IS null AND DISABLE_TIME IS null';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			$positionTypeOption=array();
			while($row=$res->fetch_assoc()) {
				$positionTypeOption[]=array($row['版位識別碼'],$row['版位名稱']);
			}
			exit(json_encode($positionTypeOption,JSON_UNESCAPED_UNICODE));
		
		}
		
		if($_POST['method']=='getOrderState'){
			$sql = 'SELECT 託播單狀態名稱 FROM 託播單狀態,託播單 WHERE 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼 AND 託播單識別碼 = ?';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('i',$_POST['託播單識別碼'])){
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$row = $res->fetch_assoc();
			exit(json_encode(array('託播單狀態'=>$row['託播單狀態名稱']),JSON_UNESCAPED_UNICODE));
		}
		exit;
	}
	@include('../tool/auth/auth.php');
	$updateId=0;
	if(isset($_GET["update"])) 
		$updateId=htmlspecialchars($_GET["update"], ENT_QUOTES, 'UTF-8'); 
	$my->close();
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
<script type="text/javascript" src="../tool/timetable/TimeTable.js?<?=time()?>"></script>
<script type="text/javascript" src="../tool/jquery-plugin/colResizable.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script src="../tool/jquery.loadmask.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<style type="text/css">
html, body {
    height: 100%;
}
.Center{
	text-align: center;
}
.BlockDataGrid div{
	behavior: url(../tool/PIE.htc);
}

#preday{
	margin-right: 5px;
}

#nextday{
	margin-left: 5px;
}

td.highlight {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
td.normal {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
td.normal a {background:#DDDDDD !important;border: 1px #888888 solid !important;}
td.ui-datepicker-current-day a {border: 2px #E63F00 solid !important;}
.date{width:200px}

u{
	margin-left: 5px;
	margin-right: 5px;
}

#newOrderBtn{
	width:150px;
	height:30px;
	vertical-align:top;
}
#positiontype,#position{
	height:30px;
	vertical-align:center;
	margin-right: 10px;
}
</style>
</head>
<body>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="dialog_form2"><iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<p>版位類型:<select id="positiontype"></select> 版位名稱:<select id="position" ></select><button type="button" onclick = "createOrder()" id ="newOrderBtn" >新增託播單到此版位</button></p>
<div id = "dateDiv">
<button id="preday">上一日</button><input type="text" id="datePicker" style="width:100px" readonly></input><button id="nextday">下一日</button>	
<div id = 'timetables'>
</div>
</div>
<fieldset id = "orderfieldset">
		<legend>訂單資訊</legend>
		<table width='100%' class='styledTable2'>
		<tbody>
		<tr><th>廣告主名稱</th><th>廣告主識別碼</th><th></th></tr>
		<tr><td><u id="adowner"></u></td><td><u id="adownerId"></u></td><td><button class ="darkButton" id="choseOwner">選擇</button> <button class ="darkButton" id="newOwner">新增</button> <button class ="darkButton" id ="editOwner">修改</button>
										<button class ="darkButton" id="clearOnwer">清空</button> <button class ="darkButton" id="viewOwner">詳細資料</button></td></tr>
		
		<tr><th>委刊單名稱</th><th>委刊單識別碼</th><th></th></tr>
		<tr><td><u id="ordername"></u></td><td><u id="orderListId"></u></td><td><button id="selectOrderList" class ="darkButton">選擇</button> <button id="newOrderList" class ="darkButton">新增</button> 
					<button id="editOrderList" class ="darkButton">修改</button> <button id="clearOrderList" class ="darkButton">清空</button> <button id="viewOrderList" class ="darkButton">詳細資料</button></td></tr>
		
		<tr><th colspan="3">現有託播單列表</th></tr>
		<tr><td colspan="3"><div id = "searchOrders"><?php include('_searchOrderUI.php');?></div><div id = "orderListDG"></div><td></tr>
		
		<tr><th colspan="3">新增的託播單暫存列表</th></tr>
		<tr><td colspan="3">
		<table class="styledTable" rules="all" cellpadding='5' width = "100%" id ="newOrderTable">
			<thead><tr><th>版位類型</th><th>版位名稱</th><th>託播單名稱</th><th>點擊類型</th><th>點擊位址</th><th>期間</th><th>時段</th><th>修改</th><th>刪除</th><th>詳細資料</th></tr></thead>
			<tbody></tbody>
		</table>
		</td></tr>
		</tbody>
		</table>
		<div class="Center"><button id="clearBtn">清空</button> <button id="sendBtn">送出</button></div>
</fieldset>
<script>  
//***版位/排程表相關***//
	var ajaxtodbPath = "ajaxToDB_Order.php";
	var selectorData=new Array();//紀錄版位類型/版位資料
	$("#dateDiv,#newOrderBtn,#searchOrders").hide();
	$('#_searchOUI_tabs_li-2,#_searchOUI_tabs-2').remove();
	
	//設定版位選項
	$.post('',{method:'getPositionTypeSelection'}
		,function(positionTypeOption){
			for(var i in positionTypeOption){
				var opt = $(document.createElement("option"));
				opt.text(positionTypeOption[i][1])//紀錄版位類型名稱
				.val(positionTypeOption[i][0])//紀錄版位類型識別碼
				.appendTo($("#positiontype"));
			}
			setPosition($( "#positiontype option:selected" ).val(),"");
			
			$( "#positiontype" ).combobox({
				 select: function( event, ui ) {
					setPosition(this.value,"");
				 }
			});
		}
		,'json'
	);
	
	//設定版位資料
	function setPosition(pId,selectedName){
		$("#position").empty();
		$.post( ajaxtodbPath, { action: "getPositionByPositionType",版位類型識別碼:pId }, 
			function( data ) {
				for(var i in data){
					var opt = $(document.createElement("option"));
					opt.text(data[i][1])//紀錄版位名稱
					.val(data[i][0])//紀錄版位識別碼
					.appendTo($("#position"));
				}
				var selectedId;
				if(typeof selectedName!='undefined'&&selectedName!=""){
					$( "#position" ).combobox('setText', selectedName);
					for(var i in data)
						if(data[i][1]==selectedName)
							selectedId=data[i][0];
				}
				else{
					if(data.length>0){
						$( "#position" ).combobox('setText',data[0][1]);
						selectedId=data[0][0];
					}
					else
						$( "#position" ).combobox('setText','');
				}
				$( "#position" ).val(selectedId);
				prepareTimeTable(selectedId);
			}
			,"json"
		);
	}
	

	$( "#position" ).combobox({
		select: function( event, ui ) {
			prepareTimeTable(this.value);
		}
	});
	
	var selectedDate = new Date();//切換日期資料用
	var orderDetail;//切換日期資料用
	//初始化TimeTable設定
	function prepareTimeTable(id){
		$("#dateDiv,#newOrderBtn").hide(100,function(){
			if(typeof id != 'undefined'&&id!=null){
				$("#dateDiv,#newOrderBtn").show(100);
				//設定日期選擇器
				$( "#datePicker" ).datepicker( "destroy" );
				$( "#datePicker" )
					.datepicker({
						dateFormat: "yy-mm-dd",
						showOn: "button",
						buttonImage: "../tool/pic/calendar16x16.png",
						buttonImageOnly: true,
						buttonText: "Select date",
						showButtonPanel: true,
						beforeShowDay: processDates,
						changeMonth: true,
						changeYear: true,
						monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
						monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
						onSelect: function(date) {
							var dateArray = date.split('-');
							selectedDate = new Date(parseInt(dateArray[0],10),parseInt(dateArray[1],10)-1,parseInt(dateArray[2],10));
							showSchedule();
						},
						onChangeMonthYear: function(year, month, inst){
							$.post( ajaxtodbPath, { action: "查詢版位當月排程",版位識別碼:id,year: year,month: month }, 
							function(data){
								orderDetail=data;
								$( "#datePicker" ).datepicker( "refresh" );
							},'json'
							);
						}
					})
					.click(function() {
						$('.ui-datepicker-today a', $(this).next()).removeClass('ui-state-highlight ui-state-hover');
						$('.highlight a', $(this).next()).addClass('ui-state-highlight');
					});
				$( "#datePicker" ).datepicker("setDate",selectedDate);
				showSchedule();
				function processDates(date) {
					var stringDate = dateToString(date);
					for(var i in orderDetail){
						if(stringDate>=orderDetail[i]["廣告期間開始時間"].split(" ")[0] && stringDate<=orderDetail[i]["廣告期間結束時間"].split(" ")[0])
							return [true,"highlight"];
					}
					return [true,"normal"];
				}
			}
		});
	}
	
	//將date轉為yyyy-MM-dd字串
	function dateToString(date){
		var str = "" + date.getDate();
		var pad = "00"
		var getdate = pad.substring(0, pad.length - str.length) + str;
		str = "" +(date.getMonth()+1);
		var getmonth = pad.substring(0, pad.length - str.length) + str;
		var stringDate= date.getFullYear()+"-"+getmonth+"-"+getdate;
		return stringDate;
	}
	
	//選擇前一天
	$("#preday").click(function(){
		selectedDate.setDate(selectedDate.getDate()-1);
		$( "#datePicker" ).datepicker("setDate",selectedDate);
		showSchedule();
	});
	
	//選擇後一天
	$("#nextday").click(function(){
		selectedDate.setDate(selectedDate.getDate()+1);
		$( "#datePicker" ).datepicker("setDate",selectedDate);
		showSchedule();
	});
	
	//設置timetable
	function showSchedule() {
		var startTime=$.datepicker.formatDate('yy-mm-dd',$('#datePicker').datepicker('getDate'))+" 00:00:00";
		var endTime = $.datepicker.formatDate('yy-mm-dd',$('#datePicker').datepicker('getDate'))+" 23:59:59";
		$('#timetables').html('');
		$.post('../casting/ajaxFunction.php',{method:'getSchedule',版位識別碼:$('#position').val(),startTime:startTime,endTime:endTime}
		,function(json) {
			var pName = $('#positiontype option:selected').text();
			for(var tablei in json){
				$('#timetables').append('<div id = "timetable'+tablei+'"></div>');
				var TT1;
				if(pName=='前置廣告投放系統'){
					TT1=new CreateTimetable_sequence('timetable'+tablei,{託播單:json[tablei]});
				}else if(pName=="首頁banner"||pName=="專區banner"||pName=="頻道short EPG banner"||pName=="專區vod"){
					TT1=new CreateTimetable('timetable'+tablei,{託播單:json[tablei],託播單代碼標題文字:'託播單識別碼/託播單CSMS群組識別碼'});
				}
				else{
					TT1=new CreateTimetable('timetable'+tablei,{託播單:json[tablei]});	
				}
				TT1.clickOnDataCell = function(mx,my,row,name){
					closeInfoWindow();
					var index=-1;
					for(var i in savedEdit["edit"])
						if(savedEdit["edit"][i]["託播單識別碼"]==name)
							index=i;
					if(index==-1)
						$("#dialog_iframe").attr("src",'orderInfo.php?parent=訂單管理&name='+name).css({"width":"100%","height":"100%"}); 
					else//在暫存列表中，不開起送出功能
						$("#dialog_iframe").attr("src",'orderInfo.php?異動=1&name='+name).css({"width":"100%","height":"100%"}); 
					dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"訂單資訊"});
					dialog.dialog( "open" );
				};			
			}
		}
		,'json'
		);
	}
	
	
	function closeOrderInfo(){
		closeInfoWindow()
	}
		
	function closeNewOrder(){
		closeInfoWindow()
	}
	
	function closeInfoWindow(){
		if($(".InfoWindow").length>0)
			$(".InfoWindow").hide(100,$(".InfoWindow").remove());
	}

	function closeOwnerInfoTable(){
		if($("#ownerInfo").length>0)
			$("#ownerInfo").hide(100,$("#ownerInfo").remove());

	}
	
	
	
	//***訂單管理相關***//
	var dialog;
	var w = 0.8;
	var h = 0.9;
	$(function() {
		dialog=$( "#dialog_form" ).dialog(
			{autoOpen: false,
			width: $(window).width()*w,
			height: $(window).height()*h,
			modal: true,
			});
		//選擇廣告主
		$("#choseOwner").click(function(){
			$("#dialog_iframe").attr("src","../adowner/selectAdOwner.php").css({"width":"100%","height":"100%"}); 
			dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"選擇廣告主"});
			dialog.dialog( "open" );
		});
		//新增廣告主
		$("#newOwner").click(function(){
			$("#dialog_iframe").attr("src","../adowner/newOwner.php?RETURN=1").css({"width":"100%","height":"100%"});  
			dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"新增廣告主"});
			dialog.dialog( "open" );
		});
		//修改廣告主
		$("#editOwner").click(function(){
			if($("#adownerId").text()!=""){
				$("#dialog_iframe").attr("src","../adowner/ownerInfoTable_edit.php?HIDE_CLOSEBTN=1&ownerid="+$("#adownerId").text())
				.css({"width":"100%","height":"100%"});  
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"修改廣告主"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一位廣告主");
		});
		//清空廣告主
		$("#clearOnwer").click(function(){
			if(clearEditSession()){
				$("#adowner").text("");$("#adownerId").text("");
				$.post("orderSession.php",{
					"clearAdOwner":1,
					"clearOrderList":1
				});
				$("#ordername").text("");
				$("#orderListId").text("");
				$("#orderListDG").empty();
				$('#searchOrders').hide();
			}
		});
		//廣告主詳細資料
		$("#viewOwner").click(function(){
			if($("#adownerId").text()!=""){
				$("#dialog_iframe").attr("src","../adowner/searchAdOwner.php?ownerid="+$("#adownerId").text())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"廣告主詳細資料"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一位廣告主");
		});
		
		//選擇委刊單
		$("#selectOrderList").click(function(){
			if($("#adownerId").text()!=""){
				$("#dialog_iframe").attr("src","selectOrderList.php?ownerid="+$("#adownerId").text())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w,	 title:"選擇委刊單"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一位廣告主");
		});
		//新增委刊單
		$("#newOrderList").click(function(){
			if($("#adownerId").text()!=""){
				//檢查有無暫存的託播單，有的話將第一個託播單名稱代入新增委刊單頁面
				if(savedOrder.length>0)
				$("#dialog_iframe").attr("src","newOrderList.php?orderName="+savedOrder[0].託播單名稱+"&RETURN=1&ownerid="+$("#adownerId").text())
				.css({"width":"100%","height":"100%"}); 
				else
				$("#dialog_iframe").attr("src","newOrderList.php?RETURN=1&ownerid="+$("#adownerId").text())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:400, width:$(window).width()*w, title:"新增委刊單"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一位廣告主");
		});
		//編輯委刊單
		$("#editOrderList").click(function(){
			if($("#orderListId").text()!=""){
				$("#dialog_iframe").attr("src","newOrderList.php?RETURN=1&action=edit&ownerid="+$("#adownerId").text()+"&orderListId="+$("#orderListId").text())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height: 400, width:$(window).width()*w, title:"編輯委刊單"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一個委刊單");
		});
		//清空委刊單
		$("#clearOrderList").click(function(){
			if(clearEditSession()){
				$("#ordername").text("");
				$("#orderListId").text("");
				$.post("orderSession.php",{
					"clearOrderList":1
				});
				$("#orderListDG").empty();
				$('#searchOrders').hide();
			}
		});
		//檢視委刊單
		$("#viewOrderList").click(function(){
			if($("#orderListId").text()!=""){
				$("#dialog_iframe").attr("src","searchOrderList.php?RETURN=1&orderListId="+$("#orderListId").text())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"檢視委刊單"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一個委刊單");
		});
	});//end of $(function()
	
	//新增了一個廣告主
	function newOwenrCreated(id,name){
		if(clearEditSession()){
			$("#adowner").text(name);
			$("#adownerId").text(id);
			$.post("orderSession.php",{
				"saveAdOwner":{"廣告主名稱":name,"廣告主識別碼":id},
				"clearOrderList":1
			});
			$("#ordername").text("");
			$("#orderListId").text("");
			$.post("orderSession.php",{"":1})
			$("#orderListDG").empty();
			$('#searchOrders').hide();
		}
		dialog.dialog( "close" );
	}
	//選擇了一個廣告主
	function adOwnerSelected(id,name){
		if(clearEditSession()){
			$("#adowner").text(name);
			$("#adownerId").text(id);
			$.post("orderSession.php",{
				"saveAdOwner":{"廣告主名稱":name,"廣告主識別碼":id},
				"clearOrderList":1
			});
			$("#ordername").text("");
			$("#orderListId").text("");
			$("#orderListDG").empty();
			$('#searchOrders').hide();
		}
		dialog.dialog( "close" );
	}
	//修改廣告主完成，關閉視窗
	function AdOwnerUpdated(id,name){
			$("#adowner").text(name);
			$("#adownerId").text(id);
			$.post("orderSession.php",{
				"saveAdOwner":{"廣告主名稱":name,"廣告主識別碼":id},
				"clearOrderList":1
			});
		dialog.dialog( "close" );
	}
	//新增委刊單完成
	function newOrderListCreated(id,name){
		if(clearEditSession()){
			$("#ordername").text(name);
			$("#orderListId").text(id);
			$.post("orderSession.php",{
				"saveOrderList":{"委刊單名稱":name,"委刊單識別碼":id}
			});
			setOrderList();
		}
		dialog.dialog( "close" );
	}
	//選擇了一個委刊單
	function orderListSelected(id,name){
		if(clearEditSession()){
			$("#ordername").text(name);
			$("#orderListId").text(id);
			$.post("orderSession.php",{
				"saveOrderList":{"委刊單名稱":name,"委刊單識別碼":id}
			});
			setOrderList();
		}
		dialog.dialog( "close" );
	}
	//修改委刊單成，關閉視窗
	function orderListUpdated(id,name){
		if(clearEditSession()){
			$("#ordername").text(name);
			$("#orderListId").text(id);
			$.post("orderSession.php",{
				"saveOrderList":{"委刊單名稱":name,"委刊單識別碼":id}
			});
			setOrderList();
		}
		dialog.dialog( "close" );
	}

	//新增託播單
	function createOrder(){
		$("#dialog_iframe").attr("src","newOrder.php?orderListName="+$("#ordername").text()+"&positionTypeId="+$("#positiontype").val()+"&positionId="+$("#position").val())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"新增託播單"});
				dialog.dialog( "open" );
		$("#orderfieldset").show();
	}
	
	//清除暫存資料警告
	function clearEditSession(){	
		if(savedEdit["delete"].length==0&&savedEdit["edit"].length==0){
			return true;
		}
		else {
			if(confirm("此動作會取消對現有託播單的異動，確定要繼續?")){
				$.post("orderSession.php",{"claerEditList":1});
				savedEdit={"delete":new Array(),"edit":new Array()};
				return true;
			}
			else
				return false;
		}
	}
	
	//*************暫存處理
	$('#newOrderTable').colResizable({liveDrag:true,fixed:false});
	//暫存的訂單
	var savedOrder=new Array();
	$.post("orderSession.php",{"getOrder":1}).done(function(data){
		if(data!=""){
			$('body').mask('取得暫存託播單中...');
			sessionOrder=JSON.parse(String(data));
			var tempArray = [];
			$.each(sessionOrder,function(i,item){
					//依照有無暫存群組加入託播單到暫存區
					//沒有暫存群組的託播單可直接加入
					if(typeof(sessionOrder[i]['tempGroupInSession'])=='undefined')
						addOrder(sessionOrder[i]);
					else{
						//有暫存群組，依照暫存群組將託播單分組
						if(typeof(tempArray[sessionOrder[i]['tempGroupInSession']]) == 'undefined')
						tempArray[sessionOrder[i]['tempGroupInSession']] = [];
						tempArray[sessionOrder[i]['tempGroupInSession']].push(sessionOrder[i]);
					}
			});
			//將不同群組的託播單一組一組加入
			for(var i in tempArray){
				addOrder(tempArray[i]);
			}
			$('body').unmask();
			saveOrderToSession(savedOrder);
			$("#orderfieldset").show();	
		}
	});
	//暫存的修改
	var savedEdit={"delete":new Array(),"edit":new Array()};
	$.post("orderSession.php",{"getEditList":1}).done(function(data){
		if(data!=""){
			var temp = JSON.parse(data);
			if(typeof(temp['delete'])!='undefined')
			savedEdit['delete']=temp['delete'];
			if(typeof(temp['edit'])!='undefined')
			savedEdit['edit']=temp['edit'];
			
		}
	});
	
	//暫存的廣告主
	$.post("orderSession.php",{"getAdOwner":1}).done(function(data){
		if(data!=""){
			session=JSON.parse(String(data));
			$("#adowner").text(session["廣告主名稱"]);
			$("#adownerId").text(session["廣告主識別碼"]);
			$("#orderfieldset").show();	
		}
	});
	//暫存的委刊單
	$.post("orderSession.php",{"getOrderList":1}).done(function(data){
		if(data!=""){
			session=JSON.parse(String(data));
			$("#ordername").text(session["委刊單名稱"]);
			$("#orderListId").text(session["委刊單識別碼"]);
			$("#orderfieldset").show();
			setOrderList();
		}
	});

	//託播單被暫存
	function newOrderSaved(jArray){
		dialog.dialog( "close" );
		var tempGid = (new Date()).getTime();
		jArray = jQuery.map( jArray, function( a ) {
			a['tempGroupInSession']=tempGid;
			return a;
		});
		$('body').mask('取得暫存託播單中...');
		addOrder(jArray);
		$('body').unmask();
		
		
		var pidList = [];//記錄版位用
		var durationList = [];//記錄走期用
		for(var j in savedOrder){
			var order = savedOrder[j];
			if(order['tempGroupInSession'] == tempGid ){
				pids = order['版位識別碼'].split(',');
				for(index in pids){
					if($.inArray(pids[index],pidList)==-1)
						pidList.push(pids[index]);
				}
				if($.inArray(order['群組廣告期間開始時間']+','+order['群組廣告期間結束時間'],durationList)==-1)
					durationList.push(order['群組廣告期間開始時間']+','+order['群組廣告期間結束時間']);			
			}
		}
		for(var j in savedOrder){
			var order = savedOrder[j];
			if(order['tempGroupInSession'] == tempGid ){
				savedOrder[j]['託播單群組版位識別碼'] = pidList;
				savedOrder[j]['託播單群組開始與結束時間'] = durationList;		
			}
		}
		jArray[0]['託播單群組版位識別碼'] = pidList;
		jArray[0]['託播單群組開始與結束時間'] = durationList;
		//將聯集的資料新更新至session
		$.post("orderSession.php",{
			"saveLastOrder":jArray[0],
		});
		saveOrderToSession(savedOrder);
	}
	//新增訂單
	function addOrder(jArray){
		var buttonAdd = true
		for(var i in jArray){
			var jsonData = jArray[i];
			
			savedOrder.push(jsonData);
			//在table中增加一列
			var tr = $(document.createElement('tr'));
			$("#newOrderTable").append(tr);

			$(document.createElement('td')).addClass('PositioTypeTd').text(jsonData["版位類型名稱"]).appendTo(tr);
			$(document.createElement('td')).addClass('PositioTd').text(jsonData["版位名稱"]).appendTo(tr);
			$(document.createElement('td')).addClass('OrderNameTd').text(jsonData["託播單名稱"]).appendTo(tr);
			var ttd =  $(document.createElement('td')).appendTo(tr);
			for(var i in jsonData['素材'])
				ttd.append(i+':'+jsonData['素材'][i]['點擊後開啟類型']+'<br>');
			ttd =  $(document.createElement('td')).appendTo(tr);
			for(var i in jsonData['素材'])
				ttd.append(i+':'+jsonData['素材'][i]['點擊後開啟位址']+'<br>');
			$(document.createElement('td')).addClass('DateTimeTd').append(jsonData["廣告期間開始時間"]+"<br>"+jsonData["廣告期間結束時間"]).appendTo(tr);
		
			var temp = jsonData["廣告可被播出小時時段"].split(',');
			var timeString=temp[0];
			for(var i =1; i<temp.length; i++){
				if(parseInt(temp[i-1],10)!=parseInt(temp[i],10)-1){
					timeString+='~'+temp[i-1]+','+temp[i];
				}
			}
			timeString+='~'+temp[temp.length-1];
			$(document.createElement('td')).addClass('HoursTd').text(timeString).appendTo(tr);		
			
			if(buttonAdd){
			//修改按鈕
			var editTd = $(document.createElement('td')).attr('rowspan',jArray.length);
			editTd.appendTo(tr);
			$(document.createElement('button')).css({"width":"100%","height":"100%"}).text("修改")
			.appendTo(editTd)
			.click(function(event){
				event.preventDefault();
				//取的要修改的託播單index
				var rmTd=$(this).parent();
				var rmTr= rmTd.parent();
				var inIndex = rmTr.parent().children().index(rmTr);
				showSelectedOrderInfo(inIndex,"newOrder.php?edit="+inIndex);
			});
			
			//刪除按鈕
			var deletTd = $(document.createElement('td')).attr('rowspan',jArray.length);
			deletTd.appendTo(tr);
			$(document.createElement('button')).css({"width":"100%","height":"100%"}).text("刪除")
			.appendTo(deletTd)
			.click(function(event) {
				event.preventDefault();
				var rmTd=$(this).parent();
				var rmTr= rmTd.parent();
				var inIndex = rmTr.parent().children().index(rmTr);
			
				//移除之前的拆單結果
				var gorupId = savedOrder[inIndex].tempGroupInSession;
				for(var i =0;i<savedOrder.length;i++){
					if(savedOrder[i].tempGroupInSession==gorupId){
						savedOrder.splice(i, 1);
						$("#newOrderTable>tbody>tr:nth-child("+(i+1)+")").remove();
						i--;
					}
				};

				//$.post("orderSession.php",{"saveOrder":(savedOrder.length==0)?false:savedOrder});
				saveOrderToSession(savedOrder);

			});	
			
			//詳細資料按鈕
			var detailTd = $(document.createElement('td')).attr('rowspan',jArray.length);
			detailTd.appendTo(tr);
			$(document.createElement('button')).css({"width":"100%","height":"100%"}).text("詳細資料")
			.appendTo(detailTd).click(function(event){
				event.preventDefault();
				//取的要修改的託播單index
				var rmTd=$(this).parent();
				var rmTr= rmTd.parent();
				var inIndex = rmTr.parent().children().index(rmTr);
				showSelectedOrderInfo(inIndex,"newOrder.php?info="+inIndex);
			});
			buttonAdd = false;
			}
		}
		function showSelectedOrderInfo(inIndex,srcUrl){
			//開啟詳細資料視窗
			$("#dialog_iframe").attr("src",srcUrl)
			.css({"width":"100%","height":"100%"}); 
			dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"暫存託播單資訊"});
			dialog.dialog( "open" );
		}
	}


	//修改暫存託播單
	function editOrder(jobject,index){
		dialog.dialog( "close" );
		//移除之前的拆單結果
		var gorupId = savedOrder[index].tempGroupInSession;
		for(var i =0;i<savedOrder.length;i++){
			if(savedOrder[i].tempGroupInSession==gorupId){
				savedOrder.splice(i, 1);
				$("#newOrderTable>tbody>tr:nth-child("+(i+1)+")").remove();
				i--;
			}
		};
		//新增暫存訂單
		newOrderSaved(jobject);
	}
	
	//session儲存暫存資料
	function saveOrderToSession(orders){
		//批次利用POST儲存待新增的託播單
		//先清除前一次的暫存
		$.post("orderSession.php",{"clearOrder":1}
			,function(rdata){
				//儲存資料
				var bypost={};
				var count =0;
				for(var i in orders){
					bypost[i] = {};
					$.extend( true, bypost[i], orders[i] );
					if((count++)%20 == 0){
						$.post("orderSession.php",{"saveOrder":bypost});
						bypost={};
					}
				}
				if(bypost.length!=0)
					$.post("orderSession.php",{"saveOrder":bypost});
			}
		);
	}
	
	function saveEditToSession(edits){
		//批次利用POST儲存待更動的託播單
		//先清除前一次的暫存
		$.post("orderSession.php",{"claerEditList":1}
			,function(rdata){
				//儲存待刪除託播單資訊
				$.post("orderSession.php",{"saveEditList":{'delete':edits['delete']}});
				//儲存待修改託播單資訊
				var bypost={};
				var count =0;
				for(var i in edits['edit']){
					bypost[i] = {};
					$.extend( true, bypost[i], edits['edit'][i] );
					if((count++)%2 == 0){
						$.post("orderSession.php",{"saveEditList":{'edit':bypost}});
						bypost={};
					}
				}
				if(bypost.length!=0)
					$.post("orderSession.php",{"saveEditList":{'edit':bypost}});
			}
		);
	}

	//異動現有託播單
	function updateOrder(jobject){
		var index=-1;
		for(var i in savedEdit["edit"])
			if(savedEdit["edit"][i]["託播單識別碼"]==jobject.託播單識別碼)
				index=i;
		if(index==-1)
			savedEdit["edit"].push(jobject);
		else 
			savedEdit["edit"][index]=jobject;
			
		saveEditToSession(savedEdit);
		TDG.updateData();
		dialog.dialog("close");
	}
	
	//設定委刊單下的託播單資料
	function setOrderList(){		
		showOrderDG();
	}
	
	/**建立現有託播表單**/
	//顯示搜尋的託播單列表
	function showOrderDG(){
		TDG = new OrderDataGrid();
	}
	function OrderDataGrid(){
		$("#orderListDG").empty();
		$('#searchOrders').show();
		var bypost={
				searchBy:$('#_searchOUI_searchOrder').val()
				,委刊單識別碼:$("#orderListId").text()
				,開始時間:$('#_searchOUI_startDate').val()
				,結束時間:$('#_searchOUI_endDate').val()
				,狀態:$('#_searchOUI_orderStateSelectoin').val()
				,版位類型識別碼:$('#_searchOUI_positiontype').val()
				,版位識別碼:$('#_searchOUI_position').val()
				,素材識別碼:$('#_searchOUI_material').val()
				,素材群組識別碼:$('#_searchOUI_materialGroup').val()
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};
		var mydg;
		//取得資料
		bypost['method']='OrderInfoBySearch';
		$.post('ajaxFunction_OrderInfo.php',bypost,function(json){
			json.header.push('刪除');
			json.header.push('修改');
			json.header.push('詳細資料');
			json.header.push('異動');
			json.header.push('取消異動');
			for(var row in json.data){
				json.data[row].push(['刪除','button']);
				json.data[row].push(['修改','button']);
				json.data[row].push(['詳細資料','button']);
				if($.inArray(json.data[row][0][0].toString(),savedEdit["delete"])!=-1){
					json.data[row].push(["待刪除","text"]);
					json.data[row].push(["取消刪除","button"]);
				}
				else{
					var edited= false;
					for(var t in savedEdit["edit"])
						if(savedEdit["edit"][t]["託播單識別碼"]==json.data[row][0][0].toString())
							edited = true;
					
					
					if(edited){
						json.data[row].push(["待修改","text"]);
						json.data[row].push(["取消修改","button"]);
					}
					else{
						json.data[row].push(["無","text"]);
						json.data[row].push(["無異動","text"]);
					}
				}
			}
			
			mydg=new DataGrid('orderListDG',json.header,json.data);
			mydg.set_page_info(json.pageNo,json.maxPageNo);
			mydg.set_sortable(json.sortable,true);
			//頁數改變動作
			mydg.pageChange=function(toPageNo) {
				bypost.pageNo=toPageNo;
				mydg.update();
			}
			//header點擊
			mydg.headerOnClick = function(headerName,sort){
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
				mydg.update();
			}
			//按鈕點擊
			mydg.buttonCellOnClick=function(row,column,rowdata){
				//顯示詳細資料
				if(rowdata[column][0]=="詳細資料"){
				var index=-1;
				for(var i in savedEdit["edit"])
					if(savedEdit["edit"][i]["託播單識別碼"]==rowdata[0][0])
						index=i;
					if(index==-1)
						$("#dialog_iframe").attr("src",'orderInfo.php?parent=訂單管理&name='+rowdata[0][0]).css({"width":"100%","height":"100%"}); 
					else//在暫存列表中，不開起送出功能
						$("#dialog_iframe").attr("src",'orderInfo.php?異動=1&name='+rowdata[0][0]).css({"width":"100%","height":"100%"});
					$("#dialog_iframe").css({"width":"100%","height":"100%"}); 
					dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w,	 title:"選擇委刊單"});
					dialog.dialog( "open" );
				}
				//刪除
				else if(rowdata[column][0]=="刪除"){
					$.post('?',{'method':'getOrderState','託播單識別碼':rowdata[0][0]},
						function(data){
							if(data.託播單狀態!="預約"){
								alert("託播單需為預約狀態才可刪除");
							}
							else{
								var index = $.inArray(rowdata[0][0].toString(),savedEdit["delete"]);
								if(index==-1){
									savedEdit["delete"].push(rowdata[0][0].toString());
									mydg.update();
									saveEditToSession(savedEdit);
								}
							}
						}
						,'json'
					);
				}
				//修改
				else if(rowdata[column][0]=="修改"){
					//檢查是否已預備被刪除
					var index = $.inArray(rowdata[0][0],savedEdit["delete"]);
					if(index==-1){
						//開啟詳細資料視窗
						$("#dialog_iframe").attr("src","newOrder.php?update="+rowdata[0][0])
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"修改託播單"});
						dialog.dialog( "open" );
					}
				}
				//取消異動
				else if(rowdata[column][0]=="取消刪除"||rowdata[column][0]=="取消修改"){
					var index = $.inArray(rowdata[0][0].toString(),savedEdit["delete"]);
					if(index!=-1){
						//取消刪除
						savedEdit["delete"].splice(index,1);
					}else{
						for(var i in savedEdit["edit"])
							if(savedEdit["edit"][i]["託播單識別碼"]==rowdata[0][0])
								index=i;
						if(index!=-1)//取消修改
							savedEdit["edit"].splice(index,1);
					}
					mydg.update();
					saveEditToSession(savedEdit);
				}
			}
			
			mydg.shearch=function(){
				bypost.searchBy=$('#searchOrderList').val();
				mydg.update();
			}
			
			
			mydg.update=function(){
				$.post('ajaxFunction_OrderInfo.php',bypost,function(json) {
						for(var row in json.data){
							json.data[row].push(['刪除','button']);
							json.data[row].push(['修改','button']);
							json.data[row].push(['詳細資料','button']);
							if($.inArray(json.data[row][0][0].toString(),savedEdit["delete"])!=-1){
								json.data[row].push(["待刪除","text"]);
								json.data[row].push(["取消刪除","button"]);
							}
							else{
								var edited= false;
								for(var t in savedEdit["edit"])
									if(savedEdit["edit"][t]["託播單識別碼"]==json.data[row][0][0].toString())
										edited = true;
								
								
								if(edited){
									json.data[row].push(["待修改","text"]);
									json.data[row].push(["取消修改","button"]);
								}
								else{
									json.data[row].push(["無","text"]);
									json.data[row].push(["無異動","text"]);
								}
							}
						}
						mydg.set_data(json.data);
					},'json');
			}
		},'json');
		
		//更新資料
		function updateData(){
			mydg.update();
		}
		
		this.updateData = function(){
			updateData();
		}
	}
	
	function clearVal(){
		savedOrder=new Array();
		savedEdit={"delete":new Array(),"edit":new Array()};
		//$("#adowner").text("");
		//$("#adownerId").text("");
		//$("#ordername").text("");
		//$("#orderListId").text("");
		$.post("orderSession.php",{"claerEditList":1,"clearOrder":1});
		$("#newOrderTable>tbody").empty();
		$("#orderListDG").empty();
		prepareTimeTable($("#position").val());
		if($('#orderListId').text()!='')
		setOrderList();
	}
	
	//******清空
	$("#clearBtn").click(function(){
		clearVal()
	});
	
	//送出******
	$("#sendBtn").click(function(){
		$.post(ajaxtodbPath,{"action":"檢察素材CSMS","orders":JSON.stringify(savedOrder.concat(savedEdit["edit"]))},
			function(data){
				if(!data['success'])
					alert(data['message']);
				else{
					var alertMessage=[];
					for(var i in data['result']){
						if(!data['result'][i]['success']){
							alertMessage.push(data['result'][i]['message']);
						}
					}
					//有警告訊息，確認是否繼續
					if(alertMessage.length!=0){
						//制做警告訊息是窗
						$('<div id = "tempDia" style="text-align:center"><div width="100%" id = "tempDia_Message"></div><hr>是否繼續?<br><button id ="tempDia_True">是</button>&nbsp;&nbsp;&nbsp;<button id ="tempDia_False">否</button></div>').appendTo('body');
						for(var i in alertMessage){
							$('#tempDia_Message').append('<p>'+alertMessage[i]+'</p>');
						}
						$('#tempDia').dialog({
							width: $(window).width()*0.5,
							height: $(window).height()*0.7,
							modal: true,
							close: function(event,ui){$('#tempDia').remove();}
						});
						$('#tempDia_True').click(function(){
							save();
							$('#tempDia').dialog('close');
						});
						$('#tempDia_False').click(function(){
							$('#tempDia').dialog('close');
						});
					}
					else{
						save();
					}
				}
			},'json'
		);
	});
	
	function save(){
		if(savedOrder.length>0||savedEdit["edit"].length>0||savedEdit["delete"].length>0){
			if($("#adownerId").text()==''||$("#orderListId").text()=='')
				alert("請選擇廣告主/委刊單");
			else{			
				var byPost = {
					"action":"儲存更變",
					"orders":JSON.stringify(savedOrder),
					"orderListId":$("#orderListId").text(),
					"edits":JSON.stringify(savedEdit)
					};
				function saveChange(){
					$.post("ajaxToDB_Order.php",
						byPost,
						function(data){
							if(data["dbError"]!=undefined){
								alert(data["dbError"]);
								return 0;
							}
							if(data["success"]){
								clearVal();
							}
							alert(data["message"]);
						}
						,'json'
					);
				}
				var epgFlag = false;
				for(i in savedEdit["edit"]){
					if(savedEdit["edit"][i]['版位類型名稱']=='頻道short EPG banner'){
						epgFlag = true;
						break;
					}
				}
				if(epgFlag){
					if(confirm('修改「頻道short EPG banner」的託播單時，同CSMS群組且同區域的託播單將一起被修改，是否繼續?')){
						saveChange();
					}
				}
				else
					saveChange();
			}
		}
	}
	
	$(function(){
		$( "#dialog_form2" ).dialog(
		{autoOpen: false,
			width: $(window).width()*w*0.7,
			height: $(window).height()*h*0.7,
			modal: true,
		});
	});
	
	//離開前，清除最後一筆新增訂單的設定資訊
	window.onbeforeunload = function(){
		$.post("orderSession.php",{
					"claerLastOrder":1
				});
	};
	
	//由orderInfo呼叫，打開素材詳細資料視窗		
	function openMaterialInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/materialInfo.php?id="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"素材資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
	//由orderInfo呼叫，打開素材群組詳細資料視窗
	function openMaterialGroupInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/searchMaterialGroup.php?showCertainId="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"素材群組資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
	//由orderInfo呼叫，打開廣告主詳細資料視窗
	function openOnwerInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../adowner/ownerInfoTable.php?ownerid='+id).css({"width":"100%","height":"100%"}); 
		dialog=$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"廣告主詳細資料"});
		dialog.dialog( "open" );
	}
	
	//由orderInfo呼叫，打開委刊單詳細資料視窗
	function openOrderListInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../order/newOrderList.php?action=info&orderListId='+id).css({"width":"100%","height":"100%"}); 
		dialog=$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"委刊單詳細資料"});
		dialog.dialog( "open" );
	}
	
	//由orderInfo呼叫,託播單狀態改變
	function orderStateChange(sate){
		if($('#orderListId').text()!='')
			setOrderList();
	}
</script>





</body>
</html>