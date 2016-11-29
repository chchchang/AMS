<?php
	header("X-Frame-Options: SAMEORIGIN");
	include('../tool/auth/auth.php');
	
	$msg='';
	
	if(isset($_POST['使用者帳號'])&&isset($_POST['使用者密碼'])&&isset($_POST['使用者姓名'])&&isset($_POST['使用者電話'])) {
		$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
		if($my->connect_errno) {
			$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
			exit('無法連線到資料庫，請聯絡系統管理員！');
		}
		
		if(!$my->set_charset('utf8')) {
			$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
		}
		
		$sql='INSERT INTO 使用者(使用者帳號,使用者密碼,使用者姓名,使用者電話,CREATED_PEOPLE) VALUES(?,?,?,?,?)';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		$_POST['使用者密碼']=md5($_POST['使用者密碼']);
		if(!$stmt->bind_param('ssssi',$_POST['使用者帳號'],$_POST['使用者密碼'],$_POST['使用者姓名'],$_POST['使用者電話'],$_SESSION['AMS']['使用者識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			if($stmt->errno==1062){
				$msg='<script>alert("新增失敗-重複的使用者帳號！")</script>';
			}
			else{
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
		}
		else {
			$logger->info('"使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')"新增"使用者識別碼('.$my->insert_id.')"成功');
			$msg='<script>alert("新增成功")</script>';
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="x-frame-options" content="sameorigin">
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
<style id="antiClickjack">body{display:none !important;}</style>
</head>
<body>
<?=$msg?>
<div class ="basicBlock" align="center" valign="center">
<form name="myForm" onsubmit="return validateForm()" method="post">
	<table class="styledTable" style="width:650px">
		<tr>
			<th width = "300">使用者帳號*</th>
			<td><input type="text" name="使用者帳號" class ="nonNull"></td>
		</tr>
		<tr>
			<th width = "300">使用者密碼*</th>
			<td><input type="password" name="使用者密碼" autocomplete="off" class ="nonNull"></td>
		</tr>
		<tr>
			<th width = "300">使用者姓名*</th>
			<td><input type="text" name="使用者姓名" class ="nonNull"></td>
		</tr>
		<tr>
			<th width = "300">使用者電話</th>
			<td><input type="text" name="使用者電話"></td>
		</tr>
	</table>
	<div  class ="Center" style="width:650px"><input type="reset" class="button" value="清空"><input type="submit" class="button" value="新增"></div>
</form>

<script>
if (self.location.hostname === top.location.hostname) {
		var antiClickjack = document.getElementById("antiClickjack");
		antiClickjack.parentNode.removeChild(antiClickjack);
} else {
	throw new Error("拒絕存取!");
}
	
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
</div>
</body>
</html>