<?php
	include('../tool/auth/authAJAX.php');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
	$sql = 'SELECT 素材類型識別碼,素材類型名稱 FROM 素材類型';
	
	if(!$stmt=$my->prepare($sql)) {
		$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->execute()) {
		$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	
	$returnToParent='false';
	if(isset($_GET["returnToParent"]))
		$returnToParent='true';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"/>

<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css"></link>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
</head>
<style type="text/css">



</style>
<body>
 
<div id="tabs">
  <ul>
    <li><a href="#deliverTabs-1">圖片素材</a></li>
    <li><a href="#deliverTabs-2">影片素材</a></li>
  </ul>
  <div id="deliverTabs-1">
	<iframe height = '1800px' width = "100%" frameBorder=0 src = "deliverMaterial.php">
	</iframe>
  </div>
  <div id="deliverTabs-2">
	<iframe height = '1800px' width = "100%" frameBorder=0 src = "deliverMaterialCF.php">
	</iframe>
  </div>
  
</div>
<script>
	$( function() {
	$( "#tabs" ).tabs();
	} );
	function changeFrameHeight(){
		var ifm= document.getElementById("iframepage"); 
		ifm.height=document.documentElement.clientHeight;

	}

	window.onresize=function(){  
		 changeFrameHeight();  
	} 
</script>
 
</body>
</html>