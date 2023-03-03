<?php
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	$_POST["託播單識別碼"]=array(11111,11110,11109,11108,11107);//dev
	$length = count($_POST["託播單識別碼"]);
	$my=new MyDB(true);
	
	$markString = array_fill(0,$length,"?");
	$markString = implode(",",$markString);
	$typeString = str_repeat("i",$length);
	$sql= "SELECT 素材識別碼,素材名稱,CAMPS影片媒體編號,import_result FROM 素材 left JOIN barker_material_import_result on 素材識別碼 = material_id WHERE 素材類型識別碼=3 and 素材識別碼 in ($markString)";
	$push_a = $my->getResultArray($sql,$typeString,...$_POST["託播單識別碼"]);
		
	echo json_encode($push_a,JSON_UNESCAPED_UNICODE);
?>