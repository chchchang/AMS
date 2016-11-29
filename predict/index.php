<?php
	include('../tool/auth/auth.php');
?>
<html>
<head>
	<meta charset="UTF-8">
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
</head>
<frameset  border="0" rows="100,*">
	<frame noresize src="status.php">
	<frame name="main" noresize src="report2.php">
</frameset>
</html>