<?php
	include('../tool/auth/auth.php');
?>
<html>
<head>
<meta charset="UTF-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script src="../tool/jquery-1.11.1.js"></script>
<style>
html{
	overflow:hidden;
}

.rightArea{
	float:right;
	margin-right:10px;
}

.logo{
	height:80px;
	color:#666;
	font-size:80px;
	font-family:Arial,Helvetica,sans-serif;
	overflow:hidden;
	margin-bottom:-10px;
}

.wellcome{
	position:absolute;
	top:20px;
	left:700px;
	font-size:30px;
}

.user{
	position:absolute;
	top:20px;
	right:70px;
}

.exit-btn{
	position:absolute;
	top:15px;
	right:20px;
	background:url(../tool/pic/logout.png);
	background-repeat:no-repeat;
	width:25px;
	height:30px;
	background-color:Transparent;
	border:0px;
	cursor:pointer;
}
.exit-btn:hover{
	background-color:#dfdfdf;
}

#alertMessage{
	position:absolute;
	top:40px;
	right:20px;
}

.changePasswd{
	position:absolute;
	top:35px;
	right:70px;
}
</style>
</head>
<body>
<form action="logout.php" target="_top" method="get">
<a class="logo">曝光數預測系統</a>
<a class="wellcome">歡迎使用本系統</a>
<a class="user"><?=htmlentities($_SESSION['AMS']['使用者姓名']);?></a>
<a class="changePasswd" target="main" href="changePasswd.php">修改密碼</a>
<a class="rightArea" align="right">
<p><button class="exit-btn" type="submit" align="middle"></button></p>
</a>
</form>
</body>
</html>