<?php 
	/****
	取的廣告資訊API
	***/
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/tool/MyDB.php';
	require_once dirname(__FILE__).'/tool/MyLogger.php';
	$startTime = '';
	$endTime = '';
	//取得託播單與版位資訊
	$sql = 'SELECT 託播單.託播單識別碼 ,託播單.版位識別碼
		FROM 託播單
		LEFT JOIN 版位 ON 版位.版位識別碼 = 託播單.版位識別碼
		LEFT JOIN 版位 版位類型 ON 版位.版位識別碼 = 版位類型.版位識別碼
		WHERE ((託播單.廣告期間開始時間 BETWEEN ? AND ?) OR (託播單.廣告期間結束時間 BETWEEN ? AND ? ) OR (? BETWEEN 託播單.廣告期間開始時間 AND 託播單.廣告期間開始時間) )
	';
	
	$orderData=$my->getResultArray($sql,'sssss',$startTime,$endTime,$startTime,$endTime,$startTime);
	//依託播播單取得版位資料
	
	//取得託播單其他參數
	
	//取得託播單素材
	
?>
 