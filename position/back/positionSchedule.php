<?php
include('../tool/auth/auth.php');	
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
<button id="btnExport" onclick="exportExcel();"> 匯出excel </button>
<iframe id="txtArea1" style="display:none"></iframe>
<?php include('_positionScheduleUI.php');?>
<?php include('_positionScheduleScript.php');?>
<script type="text/javascript">
function exportExcel(){
  var html = '&lt;meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8" />&lt;title>Excel&lt;/title>';
  html += '';
  html += document.getElementById('pschedule').outerHTML + '';
  window.open('data:application/vnd.ms-excel,' + encodeURIComponent(html));
}
</script>
</body>
</html>