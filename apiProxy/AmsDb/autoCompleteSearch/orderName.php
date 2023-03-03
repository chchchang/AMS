<?php
	/**
	*查詢託託播單名稱的autoComplete ajax 服務，Get取值
	*@parameter term: 查詢的字串 、版位類型識別碼: 指定版位類型
	*@return 一包含託播單識別碼與託播單名稱的array
	**/
	//require_once dirname(__FILE__)."/../tool/MyDB.php";
	require_once dirname(__FILE__)."/../../../tool/MyDB.php";
	require_once dirname(__FILE__).'/../../../Config.php';
	
	$my=new MyDB(true);
	$sql = "SELECT 託播單識別碼,託播單名稱 FROM `託播單` WHERE (託播單名稱 Like ? OR 託播單識別碼 = ?)";
	$typeString = "si";
	$parameter = array("%".urldecode($_GET["term"])."%",$_GET["term"]);
	if(isset($_GET["版位類型名稱"])){
		$sql .= " AND 託播單.版位識別碼 IN (SELECT 版位識別碼 FROM 版位 WHERE 上層版位識別碼 = (SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = ?))";
		$typeString .= "s";
		array_push($parameter,$_GET["版位類型名稱"]);
	}
	$sql.=" ORDER BY 託播單識別碼 DESC LIMIT 50";
	$dbdata = $my->getResultArray($sql,$typeString,...$parameter);
	exit(json_encode($dbdata,JSON_UNESCAPED_UNICODE));
	
?>