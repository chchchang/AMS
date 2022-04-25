<?php
	header("X-Frame-Options: SAMEORIGIN");
	require 'tool/auth/auth.php';
	
	$msg='';
	
	if(isset($_POST['old'])&&isset($_POST['new1'])&&isset($_POST['new2'])){
		if($_POST['new1']!=$_POST['new2'])
			$msg='<script>alert("兩次新密碼輸入不一致！");</script>';
		else if($_POST['old']==$_POST['new1'])
			$msg='<script>alert("現在密碼、新密碼不可相同！");</script>';
		else if(strlen($_POST['new1'])<Config::MIN_PASSWD_LENGTH)
			$msg='<script>alert("新密碼長度過短！");</script>';
		else{
			$oldpwd=md5('AMS_USER_PASSWORD_'.$_POST['old']);
			$newpwd=md5('AMS_USER_PASSWORD_'.$_POST['new1']);
			$my=new MyDB();
			
			$sql='SELECT * FROM 使用者 WHERE 使用者識別碼=? AND 使用者密碼=?';
			$stmt=$my->prepare($sql);
			$stmt->bind_param('is',$_SESSION['AMS']['使用者識別碼'],$oldpwd);
			$stmt->execute();
			$res=$stmt->get_result();
			if(!$row=$res->fetch_assoc())
				$msg='<script>alert("當前密碼輸入錯誤！");</script>';
			else{
				if($row['父代使用者密碼']==$newpwd||$row['祖代使用者密碼']==$newpwd)
				{
					$msg='<script>alert("新密碼不可與前三代密碼相同!");</script>';
				}
				else{
					$sql='UPDATE 使用者 SET 祖代使用者密碼=父代使用者密碼,父代使用者密碼=使用者密碼,使用者密碼=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP,LAST_UPDATE_PEOPLE=? WHERE 使用者識別碼=? AND 使用者密碼=?';
					$stmt=$my->prepare($sql);
					$stmt->bind_param('sisi',$newpwd,$_SESSION['AMS']['使用者識別碼'],$_SESSION['AMS']['使用者識別碼'],$oldpwd);
					$stmt->execute();
					$msg='<script>alert("修改成功，請重新登入。");top.window.location.replace("logout.php");</script>';
				}
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
	<table cellspacing="0">
	<tr> <td>請輸入當前密碼：</td><td><input type="password" name="old" autocomplete="off"></td></tr>
	<tr>
	<td>請輸入新密碼：</td><td><input type="password" name="new1" autocomplete="off" onKeyUp=pwStrength(this.value) onBlur=pwStrength(this.value)>
	</td>
	<td>
	<font size="2">密碼強度:</font>
	<table border="1" cellspacing="0" cellpadding="1" bordercolor="#cccccc" height="23" style='display:inline'>
	<tr align="center" bgcolor="#eeeeee"> 
	<td width="25%" id="strength_L1"><font size="2">無</font></td>
	<td width="25%" id="strength_L2"><font size="2">弱</font></td>
	<td width="25%" id="strength_L3"><font size="2">良</font></td>
	<td width="25%" id="strength_L4"><font size="2">強</font></td>
	</tr>
	</table>
	</td>
	</tr>
	<tr>
	<td>請再次輸入新密碼：</td><td><input type="password" name="new2" autocomplete="off"></td><td><font size="2">密碼強度增加方式:至少8碼；大小寫、數字、特殊字元混用</font></td>
	</tr>
	</table>
	<input id="submit" type="submit" value="確認修改" disabled>
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
<script language=javascript>


//當用戶放開鍵盤或密碼輸入框失去焦點時,根據不同的級別顯示不同的顏色
function pwStrength(pwd){
document.getElementById('submit').disabled=true;
var strength =0;
//小寫
var myRe1 = /([a-z]+)/;
if(myRe1.test(pwd)){
	strength++;
}
//大寫
var myRe2 = /([A-Z]+)/;
if(myRe2.test(pwd)){
	strength++;
}
//數字
var myRe3 = /([0-9]+)/;
if(myRe3.test(pwd)) {
	strength++;
}
//特殊
var myRe4 = /([\W_])+/;
if(myRe4.test(pwd)){
	strength++;
}
//長度
if(pwd.length<8)
strength = 0;

LO_color="#eeeeee";
L1_color="#FF0000";
L2_color="#FF9900";
L3_color="#D9FF00";
L4_color="#33CC00";
Lcolor=[];
if (pwd==null||pwd==''){
	Lcolor=[LO_color,LO_color,LO_color,LO_color];
} 
else{
	switch(strength) {
		case 0:
		case 1:
		Lcolor=[L1_color,LO_color,LO_color,LO_color];
		break;
		case 2:
		Lcolor=[L1_color,L2_color,LO_color,LO_color];
		break;
		case 3:
		Lcolor=[L1_color,L2_color,L3_color,LO_color];
		document.getElementById('submit').disabled=false;
		break;
		case 4:
		Lcolor=[L1_color,L2_color,L3_color,L4_color];
		document.getElementById('submit').disabled=false;
		break;
	}
} 

document.getElementById("strength_L1").style.background=Lcolor[0];
document.getElementById("strength_L2").style.background=Lcolor[1];
document.getElementById("strength_L3").style.background=Lcolor[2];
document.getElementById("strength_L4").style.background=Lcolor[3];
return;
}

</script>
<?=$msg;?>
</body>

	