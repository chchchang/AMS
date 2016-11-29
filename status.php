<?php
	include('tool/auth/auth.php');
	header("X-Frame-Options: SAMEORIGIN");
	
	if(isset($_POST['action'])){
		if($_POST['action']==='getSpecialOrder'){
			$my=new MyDB();
			//取得將過期但未送出的託播單建立者
			$sql='
				SELECT
					託播單狀態.託播單狀態名稱,
					使用者.使用者姓名 建立者
				FROM
					託播單,託播單狀態,使用者
				WHERE
					託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼 AND
					託播單狀態.託播單狀態名稱 IN (\'預約\',\'確定\') AND
					託播單.CREATED_PEOPLE=使用者.使用者識別碼 AND
					託播單.預約到期時間>=\''.date('Ymd').'\'
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$預約=array();
			$確定=array();
			foreach($result as $row){			
				if($row['託播單狀態名稱']==='預約')
					$預約[]=$row;
				else
					$確定[]=$row;
			}
						
			//取得將過期但尚未選擇素材的託播單建立者
			$sql='
				SELECT
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
					INNER JOIN 版位素材類型 ON 版位素材類型.版位識別碼=版位類型.版位識別碼
					INNER JOIN 素材類型 ON 素材類型.素材類型識別碼=版位素材類型.素材類型識別碼
					LEFT JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼 AND 託播單素材.素材順序=版位素材類型.素材順序
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單.託播單狀態識別碼 IN(0,1)
					AND	版位素材類型.託播單素材是否必填=true
					AND (素材.素材原始檔名 IS NULL OR 素材.素材原始檔名=\'\')
					AND	託播單.預約到期時間>=\''.date('Ymd').'\'
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$素材未到 = $result;

			//取得待處理狀態的託播單建立者
			$sql='
				SELECT
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單狀態.託播單狀態名稱=\'待處理\'
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$待處理 = $result;
			
			//取得送出失敗的託播單建立者
			$sql='
				SELECT
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
					INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					版位類型.版位名稱 IN("首頁banner","專區banner","頻道short EPG banner","專區vod")
					AND 託播單狀態.託播單狀態名稱 IN("確定","送出")
					AND 託播單.託播單送出後是否成功 = 0
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$送出失敗 = $result;
		
			//取得尚未結束播出且有內部告醒訊息的託播單建立者
			$sql='
				SELECT
					使用者.使用者姓名 建立者
				FROM
					託播單
					INNER JOIN 託播單狀態 ON 託播單.託播單狀態識別碼=託播單狀態.託播單狀態識別碼
					INNER JOIN 使用者 ON 託播單.CREATED_PEOPLE=使用者.使用者識別碼
				WHERE
					託播單狀態.託播單狀態名稱 IN("送出")
					AND 託播單.託播單送出後是否成功 = 1
					AND 託播單.託播單送出後內部錯誤訊息 IS NOT NULL
					AND	託播單.廣告期間結束時間>=\''.date('Ymd').'\'
			';
			if(!$result=$my->getResultArray($sql)) $result=array();
			$內部錯誤 = $result;
			header('Content-Type: application/json');
			exit(json_encode(['預約'=>$預約,'確定'=>$確定,'素材未到'=>$素材未到,'待處理'=>$待處理,'送出失敗'=>$送出失敗,'內部錯誤'=>$內部錯誤]));
		}
	}
?>
<!DOCTYPE html>
<head>
<meta http-equiv="x-frame-options" content="sameorigin">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="tool/jquery-1.11.1.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
	<script src="tool/jquery.loadmask.js"></script>
<style type="text/css">
html{
	/*background:url(tool/pic/banner.png);*/
	/*background: #fafafa;*/
	overflow:hidden;
	/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#e1ffff+0,e1ffff+7,e1ffff+12,fdffff+12,e6f8fd+30,c8eefb+54,bee4f8+75,b1d8f5+100;Blue+Pipe+%232 */
	background: rgb(225,255,255); /* Old browsers */
	background: -moz-linear-gradient(-45deg,  rgba(225,255,255,1) 0%, rgba(225,255,255,1) 7%, rgba(225,255,255,1) 12%, rgba(253,255,255,1) 12%, rgba(230,248,253,1) 30%, rgba(200,238,251,1) 54%, rgba(190,228,248,1) 75%, rgba(177,216,245,1) 100%); /* FF3.6-15 */
	background: -webkit-linear-gradient(-45deg,  rgba(225,255,255,1) 0%,rgba(225,255,255,1) 7%,rgba(225,255,255,1) 12%,rgba(253,255,255,1) 12%,rgba(230,248,253,1) 30%,rgba(200,238,251,1) 54%,rgba(190,228,248,1) 75%,rgba(177,216,245,1) 100%); /* Chrome10-25,Safari5.1-6 */
	background: linear-gradient(135deg,  rgba(225,255,255,1) 0%,rgba(225,255,255,1) 7%,rgba(225,255,255,1) 12%,rgba(253,255,255,1) 12%,rgba(230,248,253,1) 30%,rgba(200,238,251,1) 54%,rgba(190,228,248,1) 75%,rgba(177,216,245,1) 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e1ffff', endColorstr='#b1d8f5',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */

}
.rightArea{
	float: right;
	margin-right:10px;
}
.logo{
	height:80px;
	color:#666;
	font-size:90px;
	font-family: Arial, Helvetica, sans-serif;
	overflow:hidden;
	margin-bottom:-10px;
	background: -webkit-linear-gradient(#eee, #333);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
}

.wellcome{
  position:absolute;
  top:20px; 
  left:210px;
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
	background:url(tool/pic/logout.png);
	background-repeat: no-repeat;
	width:25px;
	height:30px;
	background-color: Transparent;
	border:0px;
	padding:0px;
	cursor:pointer;
}
.exit-btn:hover {
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

table{
	position:absolute;
	top:20px;
	left:500px;
	border-collapse: collapse;
	font-size:10px;
}

table, th, td {
    border: 2px solid black;
}

</style>
<style id="antiClickjack">body{display:none !important;}</style>
</head>
<body>
<form action="logout.php" target="_top" method="get">
	<a class="logo">AMS</a>
	<a class="wellcome">歡迎使用本系統</a>
		<?php if(Config::TEST_MOD) echo '<font size=6 color="red">測試區</font>';else echo '<font size=6 color="red">正式區</font>';?>
	<a class="user"><?=htmlentities($_SESSION['AMS']['使用者姓名']);?></a>
	<a class="changePasswd" target="main" href="changePasswd.php">修改密碼</a>
	<p id="alertMessage">
		<a id ="alertMessage1"></a><br>
		<a id ="alertMessage2"></a><br>
		<a id ="alertMessage3"></a>
	</p>
	<a  class="rightArea" align="right" >
	<!--<p><button class="exit-btn" type="submit" align="middle" ></button></p>-->
	<p><button class="exit-btn" align="middle" onclick="location.href='logout.php'"></button></p>
	</a>
</form>

<script>
function updateTimeout(){
	var 使用者=$('.user').text();
	
	$.post('?',{action:'getSpecialOrder'},function(json){
		var noOf個人預約=0;
		var noOf個人確定=0;
		
		for(var i in json.預約)
			if(json.預約[i].建立者===使用者)
				noOf個人預約++;
		for(var i in json.確定)
			if(json.確定[i].建立者===使用者)
				noOf個人確定++;
		$('#託播單尚未確定_個人').text(noOf個人預約);
		$('#託播單尚未確定_全部').text(json.預約.length);
		$('#託播單尚未送出_個人').text(noOf個人確定);
		$('#託播單尚未送出_全部').text(json.確定.length);
		
		count=0;
		for(var i in json['素材未到'])
			if(json['素材未到'][i]['建立者']===使用者)
				count++;
		$('#託播單素材尚未到位_個人').text(count);
		$('#託播單素材尚未到位_全部').text(json['素材未到'].length);
		
		count=0;
		for(var i in json['待處理'])
			if(json['待處理'][i]['建立者']===使用者)
				count++;
		$('#託播單已送出且CSMS處理中_個人').text(count);
		$('#託播單已送出且CSMS處理中_全部').text(json['待處理'].length);
		
		count=0;
		for(var i in json['送出失敗'])
			if(json['送出失敗'][i]['建立者']===使用者)
				count++;
		$('#託播單已送出且CSMS處理失敗_個人').text(count);
		$('#託播單已送出且CSMS處理失敗_全部').text(json['送出失敗'].length);
		
		var count=0;
		for(var i in json['內部錯誤'])
			if(json['內部錯誤'][i]['建立者']===使用者)
				count++;
		$('#託播單已送出但有告警_個人').text(count);
		$('#託播單已送出但有告警_全部').text(json['內部錯誤'].length);
	});
}

$(document).ready(function(){
	if (self.location.hostname === top.location.hostname) {
		var antiClickjack = document.getElementById('antiClickjack');
		antiClickjack.parentNode.removeChild(antiClickjack);
	} else {
		throw new Error('拒絕存取!');
		//top.location = self.location;
	}
	
	updateTimeout();
	setInterval(function(){
		updateTimeout();
	},3000);
});
</script>
<a href="main.php" target="main">
<table border="1">
<tr>
	<th colspan="2">託播單尚未確定</th><th colspan="2">託播單尚未送出</th><th colspan="2">託播單素材尚未到位</th><th colspan="2">託播單已送出且CSMS處理中</th><th colspan="2">託播單已送出且CSMS處理失敗</th><th colspan="2">託播單已送出但有告警訊息</th>
</tr>
<tr>
	<td>個人</td><td>全部</td><td>個人</td><td>全部</td><td>個人</td><td>全部</td><td>個人</td><td>全部</td><td>個人</td><td>全部</td><td>個人</td><td>全部</td>
</tr>
<tr>
	<td id="託播單尚未確定_個人"></td><td id="託播單尚未確定_全部"></td><td id="託播單尚未送出_個人"></td><td id="託播單尚未送出_全部"></td><td id="託播單素材尚未到位_個人"></td><td id="託播單素材尚未到位_全部"></td><td id="託播單已送出且CSMS處理中_個人"></td><td id="託播單已送出且CSMS處理中_全部"></td><td id="託播單已送出且CSMS處理失敗_個人"></td><td id="託播單已送出且CSMS處理失敗_全部"></td>
	<td id="託播單已送出但有告警_個人"></td><td id="託播單已送出但有告警_全部"></td>
</tr>
</table>
</a>
</body>
</html>