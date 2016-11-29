<?php
	header("X-Frame-Options: SAMEORIGIN");
	require '../tool/auth/auth.php';
	
	$msg='';
	
	if(isset($_POST['old'])&&isset($_POST['new1'])&&isset($_POST['new2'])){
		if($_POST['new1']!=$_POST['new2'])
			$msg='<script>alert("兩次新密碼輸入不一致！");</script>';
		else if($_POST['old']==$_POST['new1'])
			$msg='<script>alert("現在密碼、新密碼不可相同！");</script>';
		else if(strlen($_POST['new1'])<Config::MIN_PASSWD_LENGTH)
			$msg='<script>alert("新密碼長度過短！");</script>';
		else{
			$使用者舊密碼=md5('AMS_USER_PASSWORD_'.$_POST['old']);
			$使用者新密碼=md5('AMS_USER_PASSWORD_'.$_POST['new1']);
			$my=new MyDB();
			
			$sql='SELECT * FROM 使用者 WHERE 使用者識別碼=? AND 使用者密碼=?';
			$stmt=$my->prepare($sql);
			$stmt->bind_param('is',$_SESSION['AMS']['使用者識別碼'],$使用者舊密碼);
			$stmt->execute();
			$res=$stmt->get_result();
			if(!$row=$res->fetch_assoc())
				$msg='<script>alert("現在密碼輸入錯誤！");</script>';
			else{
				$sql='UPDATE 使用者 SET 使用者密碼=? WHERE 使用者識別碼=? AND 使用者密碼=?';
				$stmt=$my->prepare($sql);
				$stmt->bind_param('sis',$使用者新密碼,$_SESSION['AMS']['使用者識別碼'],$使用者舊密碼);
				$stmt->execute();
				$msg='<script>alert("修改成功，請重新登入。");top.window.location.replace("logout.php");</script>';
			}
		}
	}
?>
<style id="antiClickjack">body{display:none !important;}</style>
<head>
<meta http-equiv="x-frame-options" content="sameorigin">
<meta charset="UTF-8">
</head>
<body>
<form method="post">
	<fieldset>
    <legend>修改密碼：</legend>
	請輸入現在密碼：<input type="password" name="old" autocomplete="off"><br>
	請輸入新密碼：<input type="password" name="new1" autocomplete="off"><br>
	請再次輸入新密碼：<input type="password" name="new2" autocomplete="off"><br>
	<input type="submit" value="確認修改">
	</fieldset>
</form>
<script>
	if (self.location.hostname === top.location.hostname) {
		var antiClickjack = document.getElementById("antiClickjack");
		antiClickjack.parentNode.removeChild(antiClickjack);
	} else {
		throw new Error("拒絕存取!");
		//top.location = self.location;
	}
</script>
<?=$msg;?>
</body>