<?php
	header("Content-Type:text/html; charset=utf-8");
	date_default_timezone_set("Asia/Taipei");
	header("X-Frame-Options: DENY");
	include("Config.php");
	require_once dirname(__FILE__).'/tool/MyLogger.php';
	
	$logger=new MyLogger();
	
	$msg='';
	session_start();
	if(isset($_POST['csrftoken'])&&isset($_SESSION['AMS']['SESSIONTOKEN'])){
		if($_POST['csrftoken']==$_SESSION['AMS']['SESSIONTOKEN']){
			if(isset($_POST['user'])&&isset($_POST['magicword'])) {
				$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
				if($my->connect_errno) {
					$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
					exit('無法連線到資料庫，請聯絡系統管理員！');
				}
				
				if(!$my->set_charset('utf8')) {
					$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
					exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
				}
				
				$sql='SELECT * FROM 使用者 WHERE 使用者帳號=?';
				if(!$stmt=$my->prepare($sql)) {
					$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->bind_param('s',$_POST['user'])) {
					$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit('無法繫結資料，請聯絡系統管理員！');
				}
				
				if(!$stmt->execute()) {
					$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit('無法執行statement，請聯絡系統管理員！');
				}
				
				if(!$res=$stmt->get_result()) {
					$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit('無法取得結果集，請聯絡系統管理員！');
				}
				
				if($row=$res->fetch_assoc()) {
					if($row['啟用'] == 1){
						//判斷密碼是否正確
						$mcw = $_POST['magicword'];
						$md5pd=md5('AMS_USER_PASSWORD_'.$_POST['magicword']);
						if($row['使用者密碼'] == $md5pd){
							$logger->info('使用者帳號('.$row['使用者帳號'].')登入成功！');
							$_SESSION['AMS']['ID']=$row['使用者帳號'];
							$_SESSION['AMS']['使用者帳號']=$row['使用者帳號'];
							$_SESSION['AMS']['使用者姓名']=$row['使用者姓名'];
							$_SESSION['AMS']['使用者識別碼']=$row['使用者識別碼'];
							$_SESSION['AMS']['LOGINFAIL'][$row['使用者帳號']]=0;
							//簡查密碼強度
							$strength = 0;
							//小寫
							if(preg_match("/([a-z]+)/", $mcw)) {
							$strength++;
							}
							// 大寫
							if(preg_match("/([A-Z]+)/", $mcw)) {
							$strength++;
							}
							// 數字
							if(preg_match("/([0-9]+)/", $mcw)) {
							$strength++;
							}
							//其他字元
							if(preg_match("/([\W_])+/", $mcw)) {
								$strength++;
							}
							// 長度
							if (strlen($mcw) < 8) $strength=0;
							switch($strength) {
								case 0:
								case 1:
								case 2:
									$msg='<script>alert("您的密碼強度不足，提醒您登入後記得修改密碼。");location.replace("index.php");</script>';
								break;
								case 3:
								case 4:
									$date =  date("Y-m-d H:i:s",strtotime("-3 months"));
									if($date > $row['CREATED_TIME'] && $date > $row['LAST_UPDATE_TIME'])
										$msg='<script>alert("您的密碼已有段時間未變動，提醒您記得定期修改密碼。");location.replace("index.php");</script>';
									else
										$msg='<script>location.replace("index.php");</script>';
								break;
							}
						}
						else{
							//密碼錯誤 記錄登入失敗次數
							if(!isset($_SESSION['AMS']['LOGINFAIL'][$row['使用者帳號']]))
								$_SESSION['AMS']['LOGINFAIL'][$row['使用者帳號']]=0;
							if(++$_SESSION['AMS']['LOGINFAIL'][$row['使用者帳號']]>=3){
								//登入失敗次數過多
								$sql='UPDATE 使用者 SET 啟用=0 WHERE 使用者帳號=?';
								if(!$stmt=$my->prepare($sql)) {
									$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
									exit('無法準備statement，請聯絡系統管理員！');
								}
								if(!$stmt->bind_param('s',$_POST['user'])) {
									$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
									exit('無法繫結資料，請聯絡系統管理員！');
								}
								if(!$stmt->execute()) {
									$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
									exit('無法執行statement，請聯絡系統管理員！');
								}
							}	
							$msg='<script>alert("帳號或密碼錯誤，請重新輸入！");</script>';					
						}
					}
					else{
						$msg='<script>alert("此帳號已停用，請聯絡系統管理人");</script>';
						$logger->warn('已被停用的使用者帳號('.$row['使用者帳號'].')嘗試登入');
					}
				}
				else {
					$msg='<script>alert("帳號或密碼錯誤，請重新輸入！");</script>';
					$logger->info('使用者帳號('.$row['使用者帳號'].')登入失敗');
				}
			}
		}
		else{
			exit('非法登入!');
		}
	}
	require_once dirname(__FILE__).'/tool/phpExtendFunction.php';
	$_token = md5(uniqid(PHPExtendFunction::myrand(), true));
	$_SESSION['AMS']['SESSIONTOKEN']=$_token;
?>
<!DOCTYPE html>
<head>
<meta http-equiv="x-frame-options" content="deny">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="tool/jquery-plugin/jquery.placeholder.min.js"></script>
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


form input#user,form input#magicword{
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
}

form input#user{
	background: #F1F1F1 url(tool/pic/user.png) no-repeat;
}

form input#magicword{
	background: #F1F1F1 url(tool/pic/password.png) no-repeat;
}

form input#check {
	margin-right:0px;
	float: right; 
}

#check{
	background:url(tool/pic/login.png);
	background-repeat: no-repeat;
	width:30px;
	height:30px;
	background-color: Transparent;
	border:0px;
	cursor:pointer;
}
#check:hover {
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
	background: rgba(226,226,226,1);
	background: rgba(163,229,255,1);
	/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#b8e1fc+0,a9d2f3+10,90bae4+25,90bcea+37,90bff0+50,6ba8e5+51,a2daf5+83,bdf3fd+100;Blue+Gloss+%231 */
	background: rgb(184,225,252); /* Old browsers */
	background: -moz-linear-gradient(-45deg,  rgba(184,225,252,1) 0%, rgba(169,210,243,1) 10%, rgba(144,186,228,1) 25%, rgba(144,188,234,1) 37%, rgba(144,191,240,1) 50%, rgba(107,168,229,1) 51%, rgba(162,218,245,1) 83%, rgba(189,243,253,1) 100%); /* FF3.6-15 */
	background: -webkit-linear-gradient(-45deg,  rgba(184,225,252,1) 0%,rgba(169,210,243,1) 10%,rgba(144,186,228,1) 25%,rgba(144,188,234,1) 37%,rgba(144,191,240,1) 50%,rgba(107,168,229,1) 51%,rgba(162,218,245,1) 83%,rgba(189,243,253,1) 100%); /* Chrome10-25,Safari5.1-6 */
	background: linear-gradient(135deg,  rgba(184,225,252,1) 0%,rgba(169,210,243,1) 10%,rgba(144,186,228,1) 25%,rgba(144,188,234,1) 37%,rgba(144,191,240,1) 50%,rgba(107,168,229,1) 51%,rgba(162,218,245,1) 83%,rgba(189,243,253,1) 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#b8e1fc', endColorstr='#bdf3fd',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */

}
#title_ch{
	font-size:50px;
	font-weight: bold;
}
#title_en{
	font-weight: bold;
}
fieldset{
/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#ffffff+69,ffffff+100&0.75+1,0+28,0+69,0.75+100 */
background: -moz-linear-gradient(top,  rgba(255,255,255,0.75) 1%, rgba(255,255,255,0) 28%, rgba(255,255,255,0) 69%, rgba(255,255,255,0.75) 100%); /* FF3.6-15 */
background: -webkit-linear-gradient(top,  rgba(255,255,255,0.75) 1%,rgba(255,255,255,0) 28%,rgba(255,255,255,0) 69%,rgba(255,255,255,0.75) 100%); /* Chrome10-25,Safari5.1-6 */
background: linear-gradient(to bottom,  rgba(255,255,255,0.75) 1%,rgba(255,255,255,0) 28%,rgba(255,255,255,0) 69%,rgba(255,255,255,0.75) 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#bfffffff', endColorstr='#bfffffff',GradientType=0 ); /* IE6-9 */

	border: 2px solid #eeeeee !important;
}
</style>
<style id="antiClickjack">body{display:none !important;}</style>
<script>
	if (self === top) {
	   var antiClickjack = document.getElementById("antiClickjack");
	   antiClickjack.parentNode.removeChild(antiClickjack);
	} else {
		throw new Error("拒絕存取!");
	   //top.location = self.location;
	}
$(function(){
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input').placeholder();
	});

</script>
</head>
<body>

<form align="center" valign="center" name="form"  method="post" autocomplete="off" class="cssLayer">
	<h2>歡迎使用</h2>
	<a id="title_ch">廣告管理系統</a><br>
	<a id="title_en">Advertisement Management System</a>
	<br>
	<br>
	<br>
	<br>
	<fieldset>
	<p>請登入系統</p>
		<input  id="user"  type="text" name="user" placeholder="USER"/><br>
		<input  id="magicword"  type="password" name="magicword" placeholder="Password"/><br>
		 <input type="hidden" name="csrftoken" value="<?=$_token?>"/><br>
		<input type="submit" id="check" value=""/>
	</fieldset>
</form>
<?=$msg?>
</body>
<html>