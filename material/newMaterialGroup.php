<?php
	include('../tool/auth/auth.php');
	
	if(isset($_POST['ajax'])){
		if($_POST['ajax']=='新增素材群組'){
			$sql = 'INSERT INTO 素材群組 (素材群組名稱,素材群組說明,素材群組有效開始時間,素材群組有效結束時間,CREATED_PEOPLE) values(?,?,?,?,?)';
			$_POST['素材群組有效開始時間']=(!isset($_POST['素材群組有效開始時間'])||$_POST['素材群組有效開始時間']=='')?null:$_POST['素材群組有效開始時間'];
			$_POST['素材群組有效結束時間']=(!isset($_POST['素材群組有效結束時間'])||$_POST['素材群組有效結束時間']=='')?null:$_POST['素材群組有效結束時間'];
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('ssssi',$_POST['素材群組名稱'],$_POST['素材群組說明'],$_POST['素材群組有效開始時間']
			,$_POST['素材群組有效結束時間'],$_SESSION['AMS']['使用者識別碼'])) {
				exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			$feedback = array(
				"success" => true,
				"message" => "成功新增素材群組，識別碼: ".$stmt->insert_id
			);
			$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增素材群組(識別碼:'.$stmt->insert_id.')');
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
	}
	$my->close();
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' /> 
<style type="text/css">
body{
	text-align: center;
}
.Center{
	text-align: center;
}

button{
	margin-top: 5px;
    margin-bottom: 5px;
	margin-right:5px; 
	margin-left:5px; 
}
#StartDate,#EndDate{
	width:150px;
}
</style>

</head>
<body>
<div align="center" valign="center">
	<div class ="basicBlock" style="width:625px" align="left" valign="center">
		<table class="styledTable" style="width:600px">
			<tr><th>素材群組名稱*</th><td><input id = "素材群組名稱" type="text" value = ""  class ="nonNull" ></td></tr>
			<tr><th>素材群組說明</th><td><input id = "素材群組說明" type="text" value = ""></td></tr>
			<tr><th>素材群組有效期間:</th><td><input id = "StartDate" type="text" value = "" size="15" > ~ <input id = "EndDate" type="text" value = "" size="15" ></td></tr>
		</table>
		<div  class ="Center"><button type="button" onclick = "clearVal()">清空</button><button type="button" id ="saveBtn">新增</button></p></div>
	</div>
</div>
<script type="text/javascript">

$( "#StartDate" ).datetimepicker({	
	dateFormat: "yy-mm-dd",
	showSecond: true,
	timeFormat: 'HH:mm:ss',
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
});
$( "#EndDate" ).datetimepicker({
	dateFormat: "yy-mm-dd",
	showSecond: true,
	timeFormat: 'HH:mm:ss',
	hour: 23,
	minute: 59,
	second: 59,
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
});

function clearVal(){
	$("input:not(:radio)").val("");
};


$( "#saveBtn" ).click(function(event) {
	var nonNullEmpty= false;
	$(".nonNull").each(function(){ 
		if($.trim($(this).val())==""){
			nonNullEmpty = true;
		}
	});
	
	if(nonNullEmpty){
		alert("請填寫必要資訊");
		$(".nonNull").css("border", "2px solid red");
		return 0;
	}
	var bypost={
		'ajax':"新增素材群組"
		,'素材群組名稱':$('#素材群組名稱').val()
		,'素材群組說明':$('#素材群組說明').val()
		,'素材群組有效開始時間':$("#StartDate").val()
		,'素材群組有效結束時間':$("#EndDate").val()
	};
	$.post("?",bypost
		,function(json){
			if(json["dbError"]!=undefined){
				alert(json["dbError"]);
				return 0;
			}
			if(json["success"]){
				alert(json["message"]);
				clearVal();
			}
		}
		,'json'
	)
});	


</script>
</body>
</html>