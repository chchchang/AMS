<?php
	include('../tool/auth/auth.php');
	
	$msg='';
	
	if(isset($_POST['pageName'])&&isset($_POST['pageDesc'])&&isset($_POST['pageFilename'])) {
		$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
		if($my->connect_errno) {
			$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
			exit('無法連線到資料庫，請聯絡系統管理員！');
		}
		
		if(!$my->set_charset('utf8')) {
			$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
		}
		
		$sql='INSERT INTO 頁面(頁面名稱,頁面說明,頁面路徑,CREATED_PEOPLE) VALUES(?,?,?,?)';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('sssi',$_POST['pageName'],$_POST['pageDesc'],$_POST['pageFilename'],$_SESSION['AMS']['使用者識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			if($stmt->errno==1062)
				exit('重複的名稱: '.$stmt->error);
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		else {
			$logger->info('"使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')"新增"頁面識別碼('.$my->insert_id.')"成功');
			$msg='<script>alert("新增成功")</script>';
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<style type="text/css">
body{
	text-align: center;
}
.Center{
	text-align: center;
}

.button{
	margin-top: 5px;
    margin-bottom: 5px;
	margin-right:5px; 
	margin-left:5px; 
}
</style>
</head>
<body>
<?=$msg?>
<div class ="basicBlock" align="center" valign="center">
<form name="myForm" onsubmit="return validateForm()" method="post">
	<table class="styledTable" style="width:650px">
		<tr>
			<th width = "300">頁面名稱*</th>
			<td><input type="text" name="pageName"  class ="nonNull"></td>
		</tr>
		<tr>
			<th width = "300">頁面說明</th>
			<td><input type="text" name="pageDesc"></td>
		</tr>
		<tr>
			<th width = "300">頁面路徑*</th>
			<td><input type="text" name="pageFilename"  class ="nonNull"></td>
		</tr>
	</table>
	<div  class ="Center" style="width:650px"><input type="reset" class="button" value="清空"><input type="submit" class="button" value="新增"></div>
</form>
</div>
<script>
function validateForm() {
	var nonNullEmpty= false;
	$(".nonNull").each(function(){ 
		if($.trim($(this).val())==""){
			nonNullEmpty = true;
		}
	});
	
	if(nonNullEmpty){
		alert("請填寫必要資訊");
		$(".nonNull").css("border", "2px solid red");
		return false;
	}
}
</script>
</body>
</html>