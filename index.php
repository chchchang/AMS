<?php
	header("X-Frame-Options: DENY");
	include('tool/auth/auth.php');
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="x-frame-options" content="deny">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style type="text/css">
	#toolbar{
		position: absolute;
		padding-top: 100px;
		bottom: 0px;
		right:0px;
		left:5px;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
	}
	#main{
		position: absolute;
		padding-top: 120px;
		bottom: 0px;
		height: 100%;
		right:5px;
		padding-left: 280px;
		width: 100%;
		-moz-box-sizing: border-box;
		box-sizing: border-box; 
	}
	#status{
		position: absolute;
		top: 0px;
		right:5px;
		padding-left:5px;
		width: 100%;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
	}
	</style>
<style id="antiClickjack">body{display:none !important;}</style>
</head>
<script type="text/javascript" src="tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<script src="tool/jquery.loadmask.js"></script>
<body height = '120;'>
<iframe id = 'main' name='main'  frameBorder=0/></iframe>
<iframe width='280'  height = '100%' id = 'toolbar' frameBorder=0/></iframe>
<iframe height='120' id = 'status' frameBorder=0></iframe>

<script>
	$(document).ready(function(){
		if (self === top) {
		   var antiClickjack = document.getElementById("antiClickjack");
		   antiClickjack.parentNode.removeChild(antiClickjack);	
		   $('#main').attr('src','main.php');
		   $('#toolbar').attr('src','toolbar.php');
		   $('#status').attr('src','status.php');
		} else {
			$('html').empty();
			throw new Error("拒絕存取!");
		}
	});
</script>
</body>
</html>