<?php
	
include('../tool/auth/authAJAX.php');
include('../Config_VSM_Meta.php');
if(isset($_POST['postAction'])){
	if($_POST['postAction'] == "urlNameAutoComplete"){
		$term = $_POST['term'];
		$sql="SELECT 聯播網廣告來源名稱 as value,聯播網廣告來源識別碼 as id FROM 聯播網廣告來源 WHERE 聯播網廣告來源名稱 LIKE '%".$term."%'";
		$res=$my->getResultArray($sql);
		$result= array();
		foreach($res as $row){
			$result[] = $row;
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if($_POST['postAction'] == "urlIdAutoComplete"){
		$term = $_POST['term'];
		$sql="SELECT 聯播網廣告來源名稱 as id,聯播網廣告來源識別碼 as value FROM 聯播網廣告來源 WHERE 聯播網廣告來源識別碼 LIKE '%".$term."%'";
		$res=$my->getResultArray($sql);
		$result= array();
		foreach($res as $row){
			$result[] = $row;
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
	<script src="../tool/jquery-ui/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	
</head>
<body>
<button id = "newScheduleBtn">新增</button>
<div id="dialog_form">
<input type="text" id="schduleId" style="width:100px" hidden class="schduleUiInput"></input>
<table>
<tr><th>頻道號碼</th><th>聯播網廣告來源識別碼</th><th>聯播網廣告來源名稱</th><th>排程開始日期</th><th>排程結束日期</th></tr>
<tr>
<td><input type="number" id="schduleChannelNumber" class="schduleUiInput"></input></td>
<td><input type="number" id="schduleUrlId" class="schduleUiInput"></input></td>
<td><input type="text" id="schduleUrlName" class="schduleUiInput"></input></td>
<td><input type="text" id="schduleStartDatePicker" style="width:100px" class="schduleUiInput"></input></td>
<td><input type="text" id="schduleEndDatePicker" style="width:100px" class="schduleUiInput"></input></td>
<td><button id = "scheduleSubmitBtn">確認</button></td>
</tr>
</table>
</div>

查詢日期:<input type="text" id="searchStartDatePicker" style="width:100px" ></input>~<input type="text" id="searchEndDatePicker" style="width:100px"></input>
<button id = "searchSubmitBtn">查詢</button>
<script type="text/javascript">
//設定URL排程dialog
$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
//URL來源自動完成
$("#schduleUrlName").autocomplete({
	source :function( request, response ) {
				$.post( "?",{term: request.term,postAction:"urlNameAutoComplete"},
					function( data ) {
					//alert(data);
					response(JSON.parse(data));
				})
			},
	select : function (event, ui) {
		$("#schduleUrlId").val(ui.item.id);
	}
});
$("#schduleUrlId").autocomplete({
	source :function( request, response ) {
				$.post( "?",{term: request.term,postAction:"urlIdAutoComplete"},
					function( data ) {
					//alert(data);
					response(JSON.parse(data));
				})
			},
	select : function (event, ui) {
		$("#schduleUrlName").val(ui.item.id);
	}
});
//設定日期選擇器
$( "#searchStartDatePicker" )
.datepicker({
	dateFormat: "yy-mm-dd",
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	onClose: function( selectedDate ) {
			$( "#searchEndDatePicker" ).datepicker( "option", "minDate", selectedDate );
		}
});

$( "#searchEndDatePicker" )
.datepicker({
	dateFormat: "yy-mm-dd",
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	onClose: function( selectedDate ) {
			$( "#searchStartDatePicker" ).datepicker( "option", "maxDate", selectedDate );
		}
});
$( "#schduleStartDatePicker" )
.datepicker({
	dateFormat: "yy-mm-dd",
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	onClose: function( selectedDate ) {
			$( "#schduleEndDatePicker" ).datepicker( "option", "minDate", selectedDate );
		}
});

$( "#schduleEndDatePicker" )
.datepicker({
	dateFormat: "yy-mm-dd",
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	onClose: function( selectedDate ) {
			$( "#schduleStartDatePicker" ).datepicker( "option", "maxDate", selectedDate );
		}
});

//新增按鈕
$("#newScheduleBtn").click(function(){
	$(".schduleUiInput").each(function( index ) {
		$(this).val("");
	});
	dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"新增排程"});
	dialog.dialog( "open" );
});
//URL排程設定確認按鈕
$("#scheduleSubmitBtn").click(function(){
	var bypost = [];
	bypost["頻道號碼"] = $("#schduleChannelNumber").val();
	bypost["聯播網廣告來源識別碼"] = $("#schduleUrlId").val();
	bypost["開始日期"] = $("#schduleStartDatePicker").val();
	bypost["結束日期"] = $("#schduleEndDatePicker").val();
	bypost["排程識別碼"] = $("#schduleEndDatePicker").val();
	//檢查必填內容
	if(bypost["頻道號碼"] == "" || bypost["頻道號碼"] == null){
		alert("頻道號碼未填");
	}
	if(bypost["聯播網廣告來源識別碼"] == "" || bypost["聯播網廣告來源識別碼"] == null){
		alert("聯播網廣告來源識別碼未填");
	}

	//上傳資料
	$.post( "?",bypost,
		function( data ) {
			alert(data);
			
		}
		,"json"
	)
});
</script>
</body>
</html>
