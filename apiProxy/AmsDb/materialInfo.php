<?php
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	//$_POST["託播單識別碼"] = 47345;//dev
	$my=new MyDB(true);
	$offset = isset($_POST["page"])?($_POST["page"]-1)*50:0;
	$namePattern = isset($_POST["search"])?("%".$_POST["search"]."%"):"%";
	$sql= "SELECT * FROM 素材 WHERE 素材類型識別碼=3 and 素材名稱 LIKE ? LIMIT 50 OFFSET ?";
		
	$push_a = $my->getResultArray($sql,'si',$namePattern,$offset);
		
	echo json_encode($push_a,JSON_UNESCAPED_UNICODE);
?>