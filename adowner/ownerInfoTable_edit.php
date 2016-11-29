<?php include('../tool/auth/authAJAX.php'); ?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script>
<script type="text/javascript" src="../tool/sameOriginXfsBlock.js"></script>
<script type="text/javascript">
  $(document).ready(function(){
    iframe_auto_height(); //當文件ready時才能正確取得iframe內容的高度
  });
  //iframe auto height主程式
  function iframe_auto_height(){
    //if(!this.in_site()) return;
    var iframe;
    $(parent.document).find("iframe").map(function(){ //找到自己的iframe
      if($(this).contents().get(0).location == window.location) iframe = this;
    });
    if(!iframe) return;//no parent
    var content_height = $("body").height()+30;
    content_height = typeof content_height == 'number' ? content_height+"px" : content_height;
    iframe.style.height = content_height;
  }
  //判斷是否在網頁的iframe之中
  function in_site(){
    if(parent != window && this.is_crosssite() == false) return(true);
    return(false);
  }
  //判斷是否跨站(可能是別人嵌入了你的網頁)
  function is_crosssite() {
    try {
      parent.location.host;
      return(false);
    }
    catch(e) {
      return(true);
    }
  }
  
</script>

<style type="text/css">
.Center{
	text-align: center;
}

button{
	margin-top: 5px;
    margin-bottom: 5px;
	margin-right:5px; 
	margin-left:5px; 
}
</style>
</head>
<body>
<div class ="basicBlock" align="center" valign="center">
	<table class="styledTable" style="clear: both;">
		<tr><th width = "300">廣告主名稱*</th><td width = "300" ><input id = "ownerName" type="text" value = "" size="38" class ="nonNull"></input></td></tr>
		<tr><th>廣告主統一編</th><td><input id = "ownerVAT" type="text" value = "" size="38" ></input></td></tr>
		<tr><th>廣告主地址</th><td><input id = "ownerAdderss" type="text" value = ""size="38" ></input></td></tr>
		<tr><th>廣告主聯絡人姓名</th><td><input id = "ownerContact" type="text" value = "" size="38" ></input></td></tr>
		<tr style="border-bottom:3px solid #666666;"><th>廣告主聯絡人電話</th><td><input id = "ownerPhone" type="text" value = "" size="38" ></input></td></tr>

		<tr><th width = "300">頻道商名稱</th><td width = "300"><input id = "chanelName" type="text" value = "" size="38"></td></tr>
		<tr><th>頻道商統一編號</th><td><input id = "chanelVAT" type="text" value = "" size="38"></input></td></tr>
		<tr><th>頻道商地址</th><td><input id = "chanelAdderss" type="text" value = "" size="38"></input></td></tr>
		<tr><th>頻道商聯絡人姓名</th><td><input id = "chanelContact" type="text" value = "" size="38"></input></td></tr>
		<tr style="border-bottom:3px solid #666666;"><th>頻道商聯絡人電話</th><td><input id = "chanelPhone" type="text" value = "" size="38"></input></td></tr>

		<tr><th width = "300">承銷商名稱</th><td width = "300" ><input id = "underwName" type="text" value = "" size="38"></td></tr>
		<tr><th>承銷商統一編號</th><td><input id = "underwVAT" type="text" value = "" size="38"></td></tr>
		<tr><th>承銷商地址</th><td><input id = "underwAddress" type="text" value = "" size="38"></td></tr>
		<tr><th>承銷商聯絡人姓名</th><td><input id = "underContact" type="text" value = "" size="38"></td></tr>
		<tr><th>承銷商聯絡人電話</th><td><input id = "underPhone" type="text" value = "" size="38"></td></tr>
	</table>

	<div  class ="Center"><button onclick="refresh()">還原</button><button onclick="update()">修改</button></div>
</div>
<script>

var hideClosebtn = <?php if(isset($_GET['HIDE_CLOSEBTN']))echo 'true'; else echo 'false'; ?>;
if(hideClosebtn)
	$("#closeBtn").hide();

function refresh(){
<?php 
	require_once dirname(__FILE__).'/../tool/MyLogger.php';
	$logger=new MyLogger();
	
	$mysqli=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($mysqli->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$mysqli->connect_errno.')、錯誤訊息('.$mysqli->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$mysqli->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$mysqli->errno.')、錯誤訊息('.$mysqli->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
	$sql = "SELECT * FROM 廣告主 where 廣告主識別碼 = ".$_GET['ownerid'];
	$result =$mysqli->query($sql);
	$row = $result->fetch_assoc();
?>
	
	$("#ownerName").val("<?php echo $row["廣告主名稱"]; ?>");
	$("#ownerVAT").val("<?php echo $row["廣告主統一編號"]; ?>");
	$("#ownerAdderss").val("<?php echo $row["廣告主地址"]; ?>");
	$("#ownerContact").val("<?php echo $row["廣告主聯絡人姓名"]; ?>");
	$("#ownerPhone").val("<?php echo $row["廣告主聯絡人電話"]; ?>");
	$("#chanelName").val("<?php echo $row["頻道商名稱"]; ?>");
	$("#chanelVAT").val("<?php echo $row["頻道商統一編號"]; ?>");
	$("#chanelAdderss").val("<?php echo $row["頻道商地址"]; ?>");
	$("#chanelContact").val("<?php echo $row["頻道商聯絡人姓名"]; ?>");
	$("#chanelPhone").val("<?php echo $row["頻道商聯絡人電話"]; ?>");
	$("#underwName").val("<?php echo $row["承銷商名稱"]; ?>");
	$("#underwVAT").val("<?php echo $row["承銷商統一編號"]; ?>");
	$("#underwAddress").val("<?php echo $row["承銷商地址"]; ?>");
	$("#underContact").val("<?php echo $row["承銷商聯絡人姓名"]; ?>");
	$("#underPhone").val("<?php echo $row["承銷商聯絡人電話"]; ?>");
};

refresh();

function update(){
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
	var byPost ="action=updateOwenr&"
	+"廣告主識別碼=<?php echo htmlspecialchars($_GET['ownerid'], ENT_QUOTES, 'UTF-8'); ?>&"
	+"廣告主名稱="+$("#ownerName").val()+"&"
	+"廣告主統一編號="+$("#ownerVAT").val()+"&"
	+"廣告主地址="+$("#ownerAdderss").val()+"&"
	+"廣告主聯絡人姓名="+$("#ownerContact").val()+"&"
	+"廣告主聯絡人電話="+$("#ownerPhone").val()+"&"
	+"頻道商名稱="+$("#chanelName").val()+"&"
	+"頻道商統一編號="+$("#chanelVAT").val()+"&"
	+"頻道商地址="+$("#chanelAdderss").val()+"&"
	+"頻道商聯絡人姓名="+$("#chanelContact").val()+"&"
	+"頻道商聯絡人電話="+$("#chanelPhone").val()+"&"
	+"承銷商名稱="+$("#underwName").val()+"&"
	+"承銷商統一編號="+$("#underwVAT").val()+"&"
	+"承銷商地址="+$("#underwAddress").val()+"&"
	+"承銷商聯絡人姓名="+$("#underContact").val()+"&"
	+"承銷商聯絡人電話="+$("#underPhone").val()+"&"
	+"UID=<?php echo $_SESSION['AMS']['使用者識別碼'] ?>";
	ajax_to_db(byPost,"ajaxToDB_Adowner.php",
		function(data){
		    var result=$.parseJSON(data);
			if(result["dbError"]!=undefined){
				alert(result["dbError"]);
				return 0;
			}
			alert(result["message"]);
			if(result["success"]){
				//refresh();
				parent.AdOwnerUpdated(<?php echo htmlspecialchars($_GET['ownerid'], ENT_QUOTES, 'UTF-8'); ?>,$("#ownerName").val());
			}
		}
	);
}
</script>
</body>
</html>