<?php
	header("X-Frame-Options: DENY");
	require_once dirname(__FILE__).'/../tool/MyDB.php';
	require_once dirname(__FILE__).'/../tool/MyLogger.php';
	
	$msg='';
	
	session_start();
	
	if(isset($_POST['id'])&&isset($_POST['password'])){
		$my=new MyDB(true);
		$logger=new MyLogger();
		
		$sql='SELECT * FROM 使用者 WHERE 使用者帳號=? AND 使用者密碼=?';
		$stmt=$my->prepare($sql);
		$md5pdw=md5('AMS_USER_PASSWORD_'.$_POST['password']);
		$stmt->bind_param('ss',$_POST['id'],$md5pdw);
		$stmt->execute();
		$res=$stmt->get_result();
		if($row=$res->fetch_assoc()) {
			$logger->info('使用者帳號('.$row['使用者帳號'].')登入成功！');
			$_SESSION['AMS']['ID']=$row['使用者帳號'];
			$_SESSION['AMS']['使用者姓名']=$row['使用者姓名'];
			$_SESSION['AMS']['使用者識別碼']=$row['使用者識別碼'];
			$msg='<script>location.replace("index.php");</script>';
		}
		else {
			$msg='<script>alert("帳號或密碼錯誤，請重新輸入！");</script>';
		}
	}
?>
<html>
<head>
<meta http-equiv="x-frame-options" content="deny">
<meta charset="UTF-8">
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<style type="text/css">
body{
text-align:center
}  

form {

	font:100% verdana,arial,sans-serif;
	margin: auto;
	padding: 0;
	min-width: 500px;
	max-width: 600px;
	width: 560px; 
	height:280px;
	top:0; bottom:0; left:0; right:0;

}

form fieldset {
	border: 2px solid #888888;
}

form fieldset legend {
	font-size:25px;
	color:#000000;
	margin: 5px 5px 20px ;
	text-align: left;
}

form label {
	display: block; /* block float the labels to left column, set a width */
	float: left; 
	width: 150px; 
	padding: 0; 
	margin: 5px 0 0; /* set top margin same as form input - textarea etc. elements */
	text-align: right;

}


form input#id,form input#password{
	float: center; 
	margin:5px 0 0 10px;
	background: #F1F1F1 url(http://html-generator.weebly.com/files/theme/input-text-40.png) no-repeat;
    background-position: 5px -7px !important;
    padding: 10px 10px 10px 25px;
    width: 270px;
    border: 1px solid #CCC;
    -moz-border-radius: 5px;
    -webkit-border-radius: 5px;
    border-radius: 5px;
    -moz-box-shadow: 0 1px 1px #ccc inset, 0 1px 0 #fff;
    -webkit-box-shadow: 0 1px 1px #CCC inset, 0 1px 0 #FFF;
    box-shadow: 0 1px 1px #CCC inset, 0 1px 0 #FFF;
	behavior: url(tool/PIE.htc);
}

form input#id{
	background: #F1F1F1 url(../tool/pic/user.png) no-repeat;
}

form input#password{
	background: #F1F1F1 url(../tool/pic/password.png) no-repeat;
}

form input#submit {
	margin-right:0px;
	float: right; 
}

#submit{
	background:url(../tool/pic/login.png);
	background-repeat: no-repeat;
	width:30px;
	height:30px;
	background-color: Transparent;
	border:0px;
	cursor:pointer;
}
#submit:hover {
	background-color:#dfdfdf;
}

.cssLayer{
	height: 380px;
	margin-top: 200px;
	border: 1px solid #e3e3e3;
	padding: 40px 20px;
	text-align: center; 
	width: 200px;
	-webkit-border-radius: 8px;
	-moz-border-radius: 8px;
	border-radius: 8px;
	-webkit-box-shadow: #666 0px 2px 3px;
	-moz-box-shadow: #666 0px 2px 3px;
	box-shadow: #666 0px 2px 3px;
	background: #ffffff;
	background: -webkit-gradient(linear, 0 0, 0 bottom, from(#ffffff), to(#e3e3e3));
	background: -moz-linear-gradient(#ffffff, #e3e3e3);
	-pie-background: linear-gradient(#ffffff, #e3e3e3);
	behavior: url(tool/PIE.htc);
}
#title_ch{
	font-size:50px;
	font-weight: bold;
}
#title_en{
	font-weight: bold;
}
</style>
<style id="antiClickjack">body{display:none !important;}</style>
</head>
<body>
<form align="center" valign="center" name="form"  method="post" autocomplete="off" class="cssLayer">
	<h2>歡迎使用</h2>
	<a id="title_ch">曝光數預測系統</a><br>
	<br>
	<br>
	<br>
	<fieldset>
	<legend>請登入系統</legend>
		<input  id="id"  type="text" name="id" placeholder="USER"/><br>
		<input  id="password"  type="password" name="password" placeholder="Password"/><br>
		<input type="submit" name="submit" id="submit" value=""/>
	</fieldset>
</form>
<script>
	if (self === top) {
	   var antiClickjack = document.getElementById("antiClickjack");
	   antiClickjack.parentNode.removeChild(antiClickjack);
	} else {
		throw new Error("拒絕存取!");
	   //top.location = self.location;
	}
</script>
<?=$msg?>
</body>
<html>