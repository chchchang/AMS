<?php
	include('../tool/auth/authAJAX.php');
	$action='info';
	if(isset($_GET["action"]))
		$action=htmlspecialchars($_GET["action"], ENT_QUOTES, 'UTF-8'); 
	$id=htmlspecialchars($_GET["id"], ENT_QUOTES, 'UTF-8');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
	if(isset($_POST['action'])){	
		if($_POST['action']=='素材群組資料'){
			$sql = 'SELECT * FROM 素材群組 WHERE 素材群組識別碼 = ? AND DISABLE_TIME IS NULL AND DELETED_TIME IS NULL';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('i',$_POST['素材群組識別碼'])) {
					$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
				
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			/*while($row=$res->fetch_assoc()) {
				$feedback=array('素材群組名稱'=>$row['素材群組名稱'],'素材群組說明'=>$row['素材群組說明']);
			}*/
			exit(json_encode($res->fetch_assoc(),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['action']=='修改素材群組'){
			//檢察每個素材是否可以包含修改後的走期
			/*$sql="SELECT 素材有效開始時間,素材有效結束時間 FROM 素材 WHERE 素材群組識別碼=?";
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('i',$_POST["素材群組識別碼"])){
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$res=$stmt->get_result()){
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}

			while($row=$res->fetch_assoc()){
				if($row["素材有效開始時間"]!=null){
					if($row["素材有效開始時間"]>$_POST["素材群組有效開始時間"]){
						exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
					}
				}
				if($row["素材有效結束時間"]!=null){
					if($row["素材有效結束時間"]<$_POST["素材群組有效結束時間"]){
						exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
					}
				}
			}*/
			
			$_POST['素材群組有效開始時間']=($_POST['素材群組有效開始時間']=='')?null:$_POST['素材群組有效開始時間'];
			$_POST['素材群組有效結束時間']=($_POST['素材群組有效結束時間']=='')?null:$_POST['素材群組有效結束時間'];
			
			$sql = 'UPDATE 素材群組 SET 素材群組名稱=?,素材群組說明=?,素材群組有效開始時間=?,素材群組有效結束時間=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME = CURRENT_TIMESTAMP WHERE 素材群組識別碼 = ?';
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('ssssii',$_POST['素材群組名稱'],$_POST['素材群組說明'],$_POST['素材群組有效開始時間'],$_POST['素材群組有效結束時間']
				,$_SESSION['AMS']['使用者識別碼'],$_POST['素材群組識別碼'])) {
					$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
						
			$feedback=array(
				'success'=>true,
				'message'=>'素材群組修改成功'
			);
			$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].' 修改素材群組(識別碼:'.$_POST['素材群組識別碼'].')');
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
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
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
		<div class ="basicBlock" style="width:625px" align="left" valign="center">
		<table class="styledTable" style="width:600px">
			<tr><th>素材群組識別碼</th><td><input id = "素材群組識別碼" type="text" value = ""  readonly ></td></tr>
			<tr><th>素材群組名稱*</th><td><input id = "素材群組名稱" type="text" value = ""  class ="nonNull" ></td></tr>
			<tr><th>素材群組說明</th><td><input id = "素材群組說明" type="text" value = ""></td></tr>
			<tr><th>素材群組有效期間:</th><td><input id = "StartDate" type="text" value = "" size="15" >~<input id = "EndDate" type="text" value = "" size="15" ></td></tr>
		</table>
		<div  class ="Center"><button type="button" onclick = "refresh()">還原</button><button type="button" id ="saveBtn">儲存</button></p></div>
		</div>
	</div>
</div>
<script type="text/javascript">
$(":radio").hide();
var action = '<?=$action?>';
var id = <?=$id?>;

if(action =='info'){
	$('button').hide();
	$("input").prop('disabled', true);
}

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

refresh();

function refresh(){
	var bypost={
	action:'素材群組資料',
	素材群組識別碼:id
	};
	$.post('',bypost,
	function(data){
		$('#素材群組識別碼').val(id);
		$('#素材群組名稱').val(data['素材群組名稱']);
		$('#素材群組說明').val(data['素材群組說明']);
		$("#StartDate").val((data['素材群組有效開始時間']==null)?'':data['素材群組有效開始時間']);
		$("#EndDate").val((data['素材群組有效結束時間']==null)?'':data['素材群組有效結束時間']);
	}
	,'json'
	);
}

//儲存按鈕
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
		'action':"修改素材群組"
		,'素材群組識別碼':id
		,'素材群組名稱':$("#素材群組名稱").val()
		,'素材群組說明':$("#素材群組說明").val()
		,'素材群組有效開始時間':$("#StartDate").val()
		,'素材群組有效結束時間':$("#EndDate").val()
	};

	$.post('',bypost
		,function(result){
			if(result["dbError"]!=undefined){
				alert(result["dbError"]);
				return 0;
			}
			if(result["success"]){
				alert(result["message"]);
				parent.materialGroupUpdated();
			}
			else
				alert(result["message"]);
		}
		,'json'
	);
	
});

</script>
</body>
</html>