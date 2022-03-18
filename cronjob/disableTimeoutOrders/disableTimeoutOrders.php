<?php
	require_once '/var/www/html/AMS/Config.php';
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	$my->set_charset('utf8');
	$sql='UPDATE 託播單 SET 託播單狀態識別碼=3 WHERE 預約到期時間<\''.date('YmdHis').'\' AND 託播單狀態識別碼 in(0,1) AND (LAST_UPDATE_TIME<\''.date('YmdHis',strtotime('-1 hour')).'\' OR LAST_UPDATE_TIME IS NULL)';
	$stmt=$my->prepare($sql);
	$stmt->execute();
?>
