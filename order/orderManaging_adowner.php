<?php 	
	include('../tool/auth/auth.php');
	
	if(isset($_POST["廣告主查詢板位當月排程"])){
		$std=$_POST['year'].'-'.str_pad(strval($_POST['month']),2,STR_PAD_LEFT);
		$stdwlidcard = $std.'-%';
		$std .='-01';
		
		$sql= "SELECT 託播單識別碼,廣告可被播出小時時段,廣告期間開始時間,廣告期間結束時間,廣告主識別碼 FROM 託播單,版位,委刊單 WHERE 託播單.版位識別碼=版位.版位識別碼 AND 委刊單.委刊單識別碼=託播單.委刊單識別碼 
		AND 版位.版位識別碼 = ? AND (? between 廣告期間開始時間 AND 廣告期間結束時間 OR 廣告期間開始時間 LIKE ? OR 廣告期間結束時間 LIKE ?)";
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('isss',$_POST["版位識別碼"],$std,$stdwlidcard,$stdwlidcard)) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}		
		$a=array();	
		while($row = $res->fetch_array()){
			array_push($a,$row);
		}
		exit(json_encode($a,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST["orderBelongToOwner"])){
		$sql= "SELECT COUNT(*) as count FROM 託播單,委刊單 WHERE 委刊單.委刊單識別碼=託播單.委刊單識別碼 AND 委刊單.廣告主識別碼 = ? AND 託播單.託播單識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('ii',$_POST["廣告主識別碼"],$_POST["託播單識別碼"])) {
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()) {
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}		
		$row = $res->fetch_array();
		if($row['count']==0)		
			exit(json_encode(array('success'=>false),JSON_UNESCAPED_UNICODE));
		else
			exit(json_encode(array('success'=>true),JSON_UNESCAPED_UNICODE));
	}
	
	
	//取得廣告主資料
	
	$sql= 'SELECT 廣告主名稱, 廣告主.廣告主識別碼 AS 廣告主識別碼 FROM 廣告主,使用者 WHERE 廣告主.廣告主識別碼=使用者.廣告主識別碼 AND 使用者識別碼=?';
	if(!$stmt=$my->prepare($sql)) {
		$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法準備statement，請聯絡系統管理員！');
	}
	if(!$stmt->bind_param('i',$_SESSION['AMS']['使用者識別碼'])) {
		$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法準備statement，請聯絡系統管理員！');
	}
	if(!$stmt->execute()) {
		$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法執行statement，請聯絡系統管理員！');
	}
	if(!$res=$stmt->get_result()) {
		$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法取得結果集，請聯絡系統管理員！');
	}

	if(mysqli_num_rows($res)==0)
		exit('此為廣告主用頁面');
	
	$row = $res->fetch_assoc();
	$LoginOwnerName=$row["廣告主名稱"];
	$LoginOwnerId=$row["廣告主識別碼"];
	//取得版位資料
	$sql = 'SELECT 版位識別碼 AS 版位類型識別碼,版位名稱 AS 版位類型名稱 FROM 版位 WHERE 上層版位識別碼 IS NULL';
	
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
		$positionTypeOption[]=array($row['版位類型識別碼'],$row['版位類型名稱']);
	}
	$positionTypeOption=json_encode($positionTypeOption,JSON_UNESCAPED_UNICODE);
	
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
<script type="text/javascript" src="../tool/datagrid/BlockDataGrid.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
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

td.highlight,td.highlight2 {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
td.highlight2 a {background: #3CB371 !important;  border: 1px #2E8B57 solid !important;}
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
<div id="timetable"></div>
</div>
<fieldset id = "orderfieldset">
			<legend>訂單資訊</legend>
		<table width='100%' class='styledTable2'>
		<tbody>
		<tr><th>廣告主名稱</th><th>廣告主識別碼</th><th></th></tr>
		<tr><td><u id="adowner"></u></td><td><u id="adownerId"></u></td><td></td></tr>
		
		<tr><th>委刊單名稱</th><th>委刊單識別碼</th><th></th></tr>
		<tr><td><u id="ordername"></u></td><td><u id="orderListId"></u></td><td><button id="selectOrderList" class ="darkButton">選擇</button> <button id="newOrderList" class ="darkButton">新增</button> 
					<button id="editOrderList" class ="darkButton">修改</button> <button id="clearOrderList" class ="darkButton">清空</button> <button id="viewOrderList" class ="darkButton">詳細資料</button></td></tr>
		
		<tr><th colspan="3">現有託播單列表</th></tr>
		<tr><td colspan="3"><div id = "orderListDG"></div><td></tr>
		</tbody>
		</table>
</fieldset>
<script>  
	var LoginOwnerId=<?=$LoginOwnerId?>;
	var LoginOwnerName="<?=$LoginOwnerName?>";
//***版位/排程表相關***//
	var ajaxtodbPath = "ajaxToDB_Order.php";
	var selectorData=new Array();//紀錄版位類型/版位資料
	$("#dateDiv,#newOrderBtn").hide();
	
	//設定版位選項
	var ajaxtodbPath = "ajaxToDB_Order.php";
	var positionTypeOption=<?=$positionTypeOption?>;
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
				perpareTimeTable(selectedId);
			}
			,"json"
		);
	}
	

	 $( "#position" ).combobox({
         select: function( event, ui ) {
			perpareTimeTable(this.value);
         }
     });
	
	var selectedDate = new Date();//切換日期資料用
	var orderDetail;//切換日期資料用
	//初始化TimeTable設定
	function perpareTimeTable(id){
		$("#dateDiv,#newOrderBtn").hide(100,function(){
			if(typeof id != 'undefined'&&id!=null){
				selectedDate = new Date();
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
						changeMonth: true,
						changeYear: true,
						monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
						monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
						showButtonPanel: true,
						beforeShowDay: processDates,
						onSelect: function(date) {
							var dateArray = date.split('-');
							selectedDate = new Date(parseInt(dateArray[0]),parseInt(dateArray[1])-1,parseInt(dateArray[2]));
							setTimeTable(orderDetail,date);
						},
						onChangeMonthYear: function(year, month, inst){
							$.post( "", { 廣告主查詢板位當月排程:true,版位識別碼:id,year:year,month:month}, 
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
				function processDates(date) {
					var stringDate = dateToString(date);
					for(var i in orderDetail){
						if(stringDate>=orderDetail[i]["廣告期間開始時間"].split(" ")[0] && stringDate<=orderDetail[i]["廣告期間結束時間"].split(" ")[0]){
							if(orderDetail[i]["廣告主識別碼"]==LoginOwnerId)
								return [true,"highlight2"];
							else
								return [true,"highlight"];
						}
					}
					return [true,"normal"];
				}
				$( "#datePicker" ).datepicker("setDate",selectedDate);
				setTimeTable(orderDetail,dateToString(new Date()));
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
		setTimeTable(orderDetail,dateToString(selectedDate));
	});
	
	//選擇後一天
	$("#nextday").click(function(){
		selectedDate.setDate(selectedDate.getDate()+1);
		$( "#datePicker" ).datepicker("setDate",selectedDate);
		setTimeTable(orderDetail,dateToString(selectedDate));
	});
	
	//設置timetable
	function setTimeTable(result,stringDate){
		var data = new Array();
		for(var i in result){
			//ie8無法使用map功能，手動轉換
			stringArr=result[i][1].split(",");
			intArr= new Array();
			for(var j in stringArr)
				intArr.push(parseInt(stringArr[j], 10));
			if(stringDate>=result[i]["廣告期間開始時間"].split(" ")[0] && stringDate<=result[i]["廣告期間結束時間"].split(" ")[0]){
				var tempData={"託播單代碼":result[i][0],
							"hours":intArr,
							'startTime':result[i]["廣告期間開始時間"].split(" ")[1],
							'endTime':result[i]["廣告期間結束時間"].split(" ")[1]
					};
				if(result[i]["廣告主識別碼"]==LoginOwnerId)
					tempData.highlight=true;
				data.push(tempData);
			}
		};
		var pName = $('#positiontype option:selected').text();
		if(pName=='前置廣告投放系統'){
			mtb=new CreateTimetable_sequence('timetable',{託播單:data});
		}
		else
			mtb = new CreateTimetable('timetable',{託播單:data});
		
		infoSize = 1100;
		//排程資料被點擊
		mtb.clickOnDataCell = function(mx,my,row,name){
			$.post('?',{orderBelongToOwner:true,託播單識別碼:name,廣告主識別碼:LoginOwnerId}
			,function(data){
				if(typeof data['dbError']!= 'undefined'){
					alert(data['dbError']);
					return 0;
				}else{
					if(data['success']){
						closeInfoWindow();
						$("#dialog_iframe").attr("src",'OrderInfo.php?name='+name).css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"訂單資訊"});
						dialog.dialog( "open" );
					}
					else{
						alert("此託播單不屬於您!");
					}
				}				
			}
			,'json');
		};
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
				$("#dialog_iframe").attr("src","newOrderList.php?RETURN=1&ownerid="+$("#adownerId").text())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:300, width:$(window).width()*w, title:"新增委刊單"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一位廣告主");
		});
		//編輯委刊單
		$("#editOrderList").click(function(){
			if($("#orderListId").text()!=""){
				$("#dialog_iframe").attr("src","newOrderList.php?RETURN=1&action=edit&ownerid="+$("#adownerId").text()+"&orderListId="+$("#orderListId").text())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height: 310, width:$(window).width()*w, title:"編輯委刊單"});
				dialog.dialog( "open" );
			}else alert("請先選擇或新增一個委刊單");
		});
		//清空委刊單
		$("#clearOrderList").click(function(){
			$("#ordername").text("");
			$("#orderListId").text("");
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
	
	//選擇了一個廣告主
	function adOwnerSelected(id,name){
		if(clearEditSession()){
			$("#adowner").text(name);
			$("#adownerId").text(id);
			//***block****/
			/*$.post("orderSession.php",{
				"saveAdOwner":JSON.stringify( {"廣告主名稱":name,"廣告主識別碼":id} ),
				"clearOrderList":1
			});*/
			$("#ordername").text("");
			$("#orderListId").text("");
			$("#orderListDG").empty();
		}
		//dialog.dialog( "close" );
	}
	//修改廣告主完成，關閉視窗
	function closeOwnerInfoTable_edit(){
		<?php ?>
		dialog.dialog( "close" );
	}
	//新增委刊單完成
	function newOrderListCreated(id,name){
		if(clearEditSession()){
			$("#ordername").text(name);
			$("#orderListId").text(id);
			$.post("orderSession.php",{
				"saveOrderList":JSON.stringify( {"委刊單名稱":name,"委刊單識別碼":id} )
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
			//***block****/
			/*$.post("orderSession.php",{
				"saveOrderList":JSON.stringify( {"委刊單名稱":name,"委刊單識別碼":id} )
			});*/
			setOrderList();
		}
		dialog.dialog( "close" );
	}
	//修改委刊單成，關閉視窗
	function orderListUpdated(){
		dialog.dialog( "close" );
	}

	//新增託播單
	function createOrder(){
		$("#dialog_iframe").attr("src","newOrder.php?版位類型識別碼="+$("#positiontype").val()+"&版位識別碼="+$("#position").val())
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"新增託播單"});
				dialog.dialog( "open" );
		$("#orderfieldset").show();
	}
	
	//清除暫存資料警告
	function clearEditSession(){	
		/*if(savedEdit["delete"].length==0&&savedEdit["edit"].length==0){
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
		}*/
		//*****
		return true;
	}
	
	//*************暫存處理
	//暫存的訂單
	var savedOrder=new Array();
	/*$.post("orderSession.php",{"getOrder":1}).done(function(data){
		if(data!=""){
			sessionOrder=JSON.parse(String(data));
			$.each(sessionOrder,function(i,item){
					addOrder(sessionOrder[i]);
			});
			$("#orderfieldset").show();	
		}
	});*/
	//暫存的修改
	var savedEdit={"delete":new Array(),"edit":new Array()};
	/*$.post("orderSession.php",{"getEditList":1}).done(function(data){
		if(data!=""){
			savedEdit=JSON.parse(data);
		}
	});*/
	
	//***block****/
	//暫存的廣告主
	/*$.post("orderSession.php",{"getAdOwner":1}).done(function(data){
		if(data!=""){
			session=JSON.parse(String(data));
			$("#adowner").text(session["廣告主名稱"]);
			$("#adownerId").text(session["廣告主識別碼"]);
			$("#orderfieldset").show();	
		}
	});*/
	//暫存的委刊單
	/*$.post("orderSession.php",{"getOrderList":1}).done(function(data){
		if(data!=""){
			session=JSON.parse(String(data));
			$("#ordername").text(session["委刊單名稱"]);
			$("#orderListId").text(session["委刊單識別碼"]);
			$("#orderfieldset").show();
			setOrderList();
		}
	});*/

	//託播單被暫存
	function newOrderSaved(jobject){
		addOrder(jobject);
		dialog.dialog( "close" );	
	}
	//新增訂單
	function addOrder(jsonData){
		savedOrder.push(jsonData);
		$.post("orderSession.php",{"saveOrder":JSON.stringify(savedOrder)});
		//在table中增加一列
		var tr = $(document.createElement('tr'));
		$("#newOrderTable").append(tr);

		$(document.createElement('td')).addClass('PositioTypeTd').text(jsonData["版位類型名稱"]).appendTo(tr);
		$(document.createElement('td')).addClass('PositioTd').text(jsonData["版位名稱"]).appendTo(tr);
		$(document.createElement('td')).addClass('OrderNameTd').text(jsonData["託播單名稱"]).appendTo(tr);
		$(document.createElement('td')).addClass('HoursTd').text(jsonData["廣告期間開始時間"].split(" ")[0]+" ~ "+jsonData["廣告期間結束時間"].split(" ")[0]).appendTo(tr);
		//修改按鈕
		var editTd = $(document.createElement('td'));
		editTd.appendTo(tr);
		$(document.createElement('button')).css("width","100%").text("修改")
		.appendTo(editTd)
		.click(function(event){
			event.preventDefault();
			var rmTd=$(this).parent();
			var rmTr= rmTd.parent();
			var inIndex = rmTr.parent().children().index(rmTr);
			//開啟修改視窗
			$("#dialog_iframe").attr("src","newOrder.php?edit="+inIndex)
			.css({"width":"100%","height":"100%"}); 
			dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"修改暫存託播單"});
			dialog.dialog( "open" );
		});
		
		//刪除按鈕
		var deletTd = $(document.createElement('td'));
		deletTd.appendTo(tr);
		$(document.createElement('button')).css("width","100%").text("刪除")
		.appendTo(deletTd)
		.click(function(event) {
			event.preventDefault();
			var rmTd=$(this).parent();
			var rmTr= rmTd.parent();
			var inIndex = rmTr.parent().children().index(rmTr);
			$(this).parent().parent().remove();
			//從ARRAY中移除
			 savedOrder.splice(inIndex, 1);
			 $.post("orderSession.php",{"saveOrder":JSON.stringify(savedOrder)});
		});	
		
		//詳細資料按鈕
		var detailTd = $(document.createElement('td'));
		detailTd.appendTo(tr);
		$(document.createElement('button')).css("width","100%").text("詳細資料")
		.appendTo(detailTd).click(function(event){
			event.preventDefault();
			var rmTd=$(this).parent();
			var rmTr= rmTd.parent();
			var inIndex = rmTr.parent().children().index(rmTr);
			//開啟詳細資料視窗
			$("#dialog_iframe").attr("src","newOrder.php?info="+inIndex)
			.css({"width":"100%","height":"100%"}); 
			dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w, title:"暫存託播單資訊"});
			dialog.dialog( "open" );
		});
	}


	
	function editOrder(jobject,index){
		var trSelector="#newOrderTable>tbody>tr:nth-child("+(index+1)+")";
		$(trSelector+">.PositioTypeTd").text(jobject["版位類型名稱"]);
		$(trSelector+">.PositioTd").text(jobject["版位名稱"]);
		$(trSelector+">.OrderNameTd").text(jobject["託播單名稱"]);
		$(trSelector+">.HoursTd").text(jobject["廣告期間開始時間"].split(" ")[0]+" ~ "+jobject["廣告期間結束時間"].split(" ")[0]);
		dialog.dialog( "close" );
		savedOrder.splice(index, 1, jobject);
		$.post("orderSession.php",{"saveOrder":JSON.stringify(savedOrder)});
	}

	
	function updateOrder(jobject){
		var index=-1;
		for(var i in savedEdit["edit"])
			if(savedEdit["edit"][i]["託播單識別碼"]==jobject.託播單識別碼)
				index=i;
		if(index==-1)
			savedEdit["edit"].push(jobject);
		else 
			savedEdit["edit"][index]=jobject;
			
		$.post("orderSession.php",{"saveEditList":JSON.stringify(savedEdit)});
		TDG.updateData();
		dialog.dialog("close");
	}
	
	//設定委刊單下的託播單資料
	var g_numPerPage = 10;
	function setOrderList(){
		var infoQuery=[];
		infoQuery["basic"] ="action=委刊單下託播單資訊&委刊單識別碼="+$("#orderListId").text();
		infoQuery["sort"] = "&ORDER=託播單識別碼";
		infoQuery["page"] ="&PAGE="+0;
		var infoAttribute =["託播單識別碼","託播單名稱","託播單狀態","廣告期間開始時間","廣告期間結束時間"];
		//確認資料數目並建立託播單資料表
		ajax_to_db(
			"action=getCount&TABLE=託播單&WHERE=委刊單識別碼="+$("#orderListId").text(),ajaxtodbPath,
			function(data){
				var result=$.parseJSON(data);
				if(result["dbError"]!=undefined){
					alert(result["dbError"]);
					return 0;
				}
				var totalPage = Math.ceil(result[0][0]/g_numPerPage);
				TDG = new OrderDataGrid(totalPage,infoQuery,infoAttribute);
			}
		);
	}
	
	/**建立現有託播表單**/
	function OrderDataGrid(totalPage,query,attribute){
		$("#orderListDG").empty();
		//query用共同參數
		//***block****/
		//var header =["託播單識別碼","託播單群組識別碼","託播單名稱","託播單狀態","播放開始","播放結束","刪除","修改","詳細資料","異動","取消異動"];
		var header =["託播單識別碼","託播單名稱","託播單狀態","播放開始","播放結束","詳細資料"];
		var mydg = new DataGrid('orderListDG',header);
		updateData();
		mydg.set_sortable(["託播單識別碼","託播單名稱","託播單狀態","播放開始","播放結束"],true);	
		//覆寫header被點擊時的動作
		mydg.headerOnClick = function(headerName,sort){
			var hindex=$.inArray( headerName, header);
			var orderAtt=attribute[hindex];
			switch(sort){
				case "increase":
					query["sort"] = "&ORDER="+orderAtt+"&SORT=ASC"
					break;
				case "decrease":
					query["sort"] = "&ORDER="+orderAtt+"&SORT=DESC"
					break;
				case "unsort":
					break;
			}
			updateData();
		};
		
		//設定頁數資訊
		if(totalPage>1){
			mydg.set_page_info(1,totalPage);
			//覆寫改變頁數時的動作
			mydg.pageChange = function(toPage){
				var startN = (toPage-1)*g_numPerPage;
				query["page"] = "&PAGE="+startN;
				updateData();
			};
		}
		
		//更新資料
		function updateData(){
			ajax_to_db(
				query["basic"]+query["sort"]+query["page"]+"&PNUMBER="+g_numPerPage,ajaxtodbPath,
				function(data){
					var result=$.parseJSON(data);
					if(result["dbError"]!=undefined){
						alert(result["dbError"]);
						return 0;
					}
					var dataArr=[];
					for(var i in result){
						var pushArr=[];
						for(var j in attribute)
							pushArr.push([result[i][attribute[j]],"text"]);
							//***block****/
						//pushArr.push(["刪除","button"]);
						//pushArr.push(["修改","button"]);
						pushArr.push(["詳細資料","button"]);
							//***block****/
						/*if($.inArray(result[i]["託播單識別碼"],savedEdit["delete"])!=-1){
							pushArr.push(["待刪除","text"]);
							pushArr.push(["取消刪除","button"]);
						}
						else{
							var edited= false;
							for(var t in savedEdit["edit"])
								if(savedEdit["edit"][t]["託播單識別碼"]==result[i]["託播單識別碼"])
									edited = true;
							
							
							if(edited){
								pushArr.push(["待修改","text"]);
								pushArr.push(["取消修改","button"]);
							}
							else{

								pushArr.push(["無","text"]);
								pushArr.push(["無異動","text"]);
							}
						}*/
						
						dataArr.push(pushArr);						
					}
					mydg.set_data(dataArr);
				}
			); 
		}
		
		//按鈕被點擊
		mydg.buttonCellOnClick= function(row,column,rowdata){
			//顯示詳細資料
			if(header[column]=="詳細資料"){
				$("#dialog_iframe").attr("src","OrderInfo.php?name="+rowdata[0][0])
				.css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*h, width:$(window).width()*w,	 title:"選擇委刊單"});
				dialog.dialog( "open" );
			}
			//刪除
			else if(header[column]=="刪除"){
				var index = $.inArray(rowdata[0][0],savedEdit["delete"]);
				if(index==-1){
					savedEdit["delete"].push(rowdata[0][0]);
					updateData();
					$.post("orderSession.php",{"saveEditList":JSON.stringify(savedEdit)});
				}
			}
			//修改
			else if(header[column]=="修改"){
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
			else if(header[column]=="取消異動"){
				var index = $.inArray(rowdata[0][0],savedEdit["delete"]);
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
				updateData();
				$.post("orderSession.php",{"saveEditList":JSON.stringify(savedEdit)});
			}
			
		}
		
		this.uncollapse = function(){
			mydg.uncollapse();
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
		perpareTimeTable($("#position").val());
		setOrderList();
	}
	
	//清空
	$("#clearBtn").click(function(){
		clearVal()
	});
	
	//送出
	$("#sendBtn").click(function(){
		if(savedOrder.length>0||savedEdit["edit"].length>0||savedEdit["delete"].length>0){
			if($("#adownerId").text()==''||$("#orderListId").text()=='')
				alert("請選擇廣告主/委刊單");
			else
				$.post(ajaxtodbPath,
					 {"action":"儲存更變",
					 "orders":JSON.stringify(savedOrder),
					 "orderListId":$("#orderListId").text(),
					 "edits":JSON.stringify(savedEdit)},
					 function(data){
						if(data["dbError"]!=undefined){
							alert(data["dbError"]);
							return 0;
						}
						if(data["success"]){
							clearVal();
						}
						
						alert(data["message"]);
					},
					'json'
				);
		}			
	});
	
	$(function(){
		$( "#dialog_form2" ).dialog(
		{autoOpen: false,
			width: $(window).width()*w*0.7,
			height: $(window).height()*h*0.7,
			modal: true,
		});
	});
	
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
	
	//***block****/
	$("#editOrderList,#editOrderList,#newOrderBtn,#clearBtn,#sendBtn,#newOrderList,#tempOrder,#newOrderTable").remove();
	adOwnerSelected(LoginOwnerId,LoginOwnerName);
</script>





</body>
</html>