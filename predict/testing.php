<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>jQuery TEST</title>

</head>
<body>
	<?php 
		$filename = 'test.xlsx';
		$file = dirname(__FILE__)."/".$filename ;
		if(file_exists($file)){
			/*header('Content-Disposition: attachment; filename=' . $filename );
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Length: ' . filesize($file));
			header('Content-Transfer-Encoding: binary');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');*/
			header("Pragma: public", true);
			header("Expires: 0"); // set expiration time
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-type:application/vnd.ms-excel");
			header("Content-Disposition: attachment; filename=".basename($file));
			header("Content-Transfer-Encoding: utf-8");
			header("Content-Length: ".filesize($file));
			echo basename($file);
			//die(file_get_contents($file));
			readfile($file);
		}
	?>
<script>
</script>
</body>
</html>