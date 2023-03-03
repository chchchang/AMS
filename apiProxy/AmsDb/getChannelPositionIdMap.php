<?php
/**
*取得channel_id和版位識別碼的對照表
**/
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	//$_POST["託播單識別碼"] = 47345;//dev
	$my=new MyDB(true);
	$sql= "SELECT 版位.版位識別碼,版位其他參數.版位其他參數預設值 AS channel_id
		FROM
			版位 JOIN 版位其他參數 ON 版位.版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = 'channel_id'
		WHERE 版位.上層版位識別碼 = (
			SELECT 版位識別碼 FROM 版位 WHERE 版位名稱 = 'barker頻道'
		) AND DISABLE_TIME IS NULL AND DELETED_TIME IS NULL
		";
		
		$dbdata = $my->getResultArray($sql);
			
		echo json_encode($dbdata,JSON_UNESCAPED_UNICODE);
?>