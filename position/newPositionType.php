<?php
	include('../tool/auth/auth.php');
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' /> 

<style type="text/css">
body{
	text-align: center;
}
</style>

</head>
<body>
<iframe src = 'positionTypeForm.php?action=new' width='100%' height='600' frameBorder="0"></iframe>

</body>
</html>