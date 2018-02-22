<?php
require_once dirname(__FILE__).'/tool/MyDB.php';
$my=new MyDB(true);
//$date = 'DATE_SUB(NOW(),INTERVAL 1 YEAR)';
$date = '"2016-01-01 00:00:00"';
$sql='	DELETE 託播單,託播單CAMPS_ID對照表,託播單CSMS群組,託播單其他參數,託播單投放版位,託播單素材,`頻道short EPG banner託播單移出託播單CSMS群組記錄`
		from 託播單
		LEFT JOIN 託播單CAMPS_ID對照表 ON 託播單.託播單識別碼 = 託播單CAMPS_ID對照表.託播單識別碼
		LEFT JOIN 託播單CSMS群組 ON 託播單.託播單CSMS群組識別碼 = 託播單.託播單CSMS群組識別碼
        LEFT JOIN 託播單其他參數 ON 託播單.託播單識別碼 = 託播單其他參數.託播單識別碼
        LEFT JOIN 託播單投放版位 ON 託播單.託播單識別碼 = 託播單投放版位.託播單識別碼
        LEFT JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
        LEFT JOIN `頻道short EPG banner託播單移出託播單CSMS群組記錄` ON `頻道short EPG banner託播單移出託播單CSMS群組記錄`.託播單識別碼 = 託播單.託播單識別碼'
		.'where 廣告期間結束時間 <= '.$date
		;
	if(!$stmt=$my->prepare($sql)) {
			exit($my->error);
		}
		if(!$stmt->execute()) {
			exit($my->error);
		}
echo 'Order DONE';
?>