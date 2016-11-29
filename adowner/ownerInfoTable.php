<?php include('../tool/auth/authAJAX.php'); ?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/sameOriginXfsBlock.js"></script>
<script type="text/javascript">
  $(document).ready(function(){
    iframe_auto_height(); //當文件ready時才能正確取得iframe內容的高度
  });
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
  //判斷是否跨站
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
</head>
<body>
	<div class ="basicBlock" align="center" valign="center">
	<table class="styledTable" style="clear: both;">
		<tr><th width = "300">廣告主名稱</th><td width = "300" id = "ownerName"></td></tr>
		<tr><th>廣告主統一編號</th><td id = "ownerVAT"></td></tr>
		<tr><th>廣告主地址</th><td id = "ownerAdderss"></td></tr>
		<tr><th>廣告主聯絡人</th><td id = "ownerContact"></td></tr>
		<tr style="border-bottom:2px solid #AAAAAA;"><th>廣告主聯絡人電話</th><td id = "ownerPhone"></td></tr>

		<tr><th width = "300">頻道商名稱</th><td width = "300" id = "chanelName"></td></tr>
		<tr><th>頻道商統一編號</th><td id = "chanelVAT"></td></tr>
		<tr><th>頻道商地址</th><td id = "chanelAdderss"></td></tr>
		<tr><th>頻道商聯絡人</th><td id = "chanelContact"></td></tr>
		<tr style="border-bottom:2px solid #AAAAAA;"><th>頻道商聯絡人電話</th><td id = "chanelPhone"></td></tr>

		<tr><th width = "300">承銷商名稱</th><td width = "300" id = "underwName"></td></tr>
		<tr><th>承銷商統一編號</th><td id = "underwVAT"></td></tr>
		<tr><th>承銷商地址</th><td id = "underwAddress"></td></tr>
		<tr><th>承銷商聯絡人</th><td id = "underContact"></td></tr>
		<tr><th>承銷商聯絡人電話</th><td id = "underPhone"></td></tr>
	</table>
	</div>

<script>
	<?php
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}	
	$sql = "SELECT * FROM 廣告主 where 廣告主識別碼 = ".$_GET['ownerid'];
	$result =$my->query($sql);
	$row = $result->fetch_assoc();
	$my->close(); ?>
	
	$("#ownerName").text("<?php echo $row["廣告主名稱"]; ?>");
	$("#ownerVAT").text("<?php echo $row["廣告主統一編號"]; ?>");
	$("#ownerAdderss").text("<?php echo $row["廣告主地址"]; ?>");
	$("#ownerContact").text("<?php echo $row["廣告主聯絡人姓名"]; ?>");
	$("#ownerPhone").text("<?php echo $row["廣告主聯絡人電話"]; ?>");
	$("#chanelName").text("<?php echo $row["頻道商名稱"]; ?>");
	$("#chanelVAT").text("<?php echo $row["頻道商統一編號"]; ?>");
	$("#chanelAdderss").text("<?php echo $row["頻道商地址"]; ?>");
	$("#chanelContact").text("<?php echo $row["頻道商聯絡人姓名"]; ?>");
	$("#chanelPhone").text("<?php echo $row["頻道商聯絡人電話"]; ?>");
	$("#underwName").text("<?php echo $row["承銷商名稱"]; ?>");
	$("#underwVAT").text("<?php echo $row["承銷商統一編號"]; ?>");
	$("#underwAddress").text("<?php echo $row["承銷商地址"]; ?>");
	$("#underContact").text("<?php echo $row["承銷商聯絡人姓名"]; ?>");
	$("#underPhone").text("<?php echo $row["承銷商聯絡人電話"]; ?>");
	
</script>
</body>
</html>