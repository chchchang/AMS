<?php
include('../tool/auth/auth.php');	
if(isset($_POST["method"])){
	if($_POST["method"]=="取得託播單用參數"){
		$ptypeId = $_POST["版位類型識別碼"];
		$sql = "Select * from 版位其他參數 WHERE 版位識別碼 = ? AND 是否版位專用 = 0";
		$res = $my->getResultArray($sql,'i',$ptypeId);
		exit(json_encode($res,JSON_UNESCAPED_UNICODE));
	}
	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
	<script src="../tool/jquery-ui/jquery-ui.js"></script>
	<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
	<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
	<script src="../tool/jquery-plugin/tableHeadFixer.js"></script>
	<script src="../tool/jquery.loadmask.js"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
</head>
<style type="text/css">
th {overflow:hidden;white-space:nowrap;}
#pschedule td,#pschedule th{border-style: solid; border-width: 1px; border-color:#aaaaaa}
#pschedule { border-width: 0px; }
</style>
<body>

<iframe id="txtArea1" style="display:none"></iframe>
<?php include('_positionScheduleUI.php');?>
<?php include('_positionScheduleScript.php');?>
<script type="text/javascript">

</script>
</body>
</html>