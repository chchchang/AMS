<?php
require_once '../tool/MyDB.php';
$my=new MyDB(true);
$sql='UPDATE 
	託播單素材 A
	JOIN 託播單 託播單A ON 託播單A.託播單識別碼 = A.託播單識別碼
	JOIN 版位 版位A ON 託播單A.版位識別碼 = 版位A.版位識別碼 
	JOIN 託播單素材 B ON A.素材識別碼 = B.素材識別碼
	JOIN 託播單 託播單B ON 託播單B.託播單識別碼 = B.託播單識別碼
	JOIN 版位 版位B ON 託播單B.版位識別碼 = 版位B.版位識別碼 AND SUBSTRING_INDEX(版位A.版位名稱, "_", -1) = SUBSTRING_INDEX(版位B.版位名稱, "_", -1)
	
SET 
	A.可否點擊=B.可否點擊,
	A.點擊後開啟類型=B.點擊後開啟類型,
	A.點擊後開啟位址=B.點擊後開啟位址
WHERE
	B.託播單識別碼 = 100
	AND 託播單A.託播單狀態識別碼 IN (2,4)
	';

if(!$stmt = $my->prepare($sql)) {
	exit('prepare錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
}

if(!$stmt->execute()) {
	exit('excute錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
}
?>
