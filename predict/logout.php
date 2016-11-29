<?php
	session_start();
	unset($_SESSION['AMS']);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script>location.replace('login.php');</script>
</head>
</html>