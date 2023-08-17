<?php 
//2023 03 16刪除沒有被任何頻道/時段指定的播表
//$htmlpath ="../../";//dev
$htmlpath ="/var/www/html/AMS/";//pro
$dataKeepMonths = 18;
require_once $htmlpath .'tool/MyDB.php';

$mydb=new MyDB(true);
//清理過期的資料playlist schedule資料
$sql = "DELETE FROM barker_playlist_schedule WHERE IF(last_update_time IS NULL, created_time < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH), created_time < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH))";
if(!$mydb->execute($sql))
    echo "delete barker_playlist_schedule fail\n";
else
    echo "delete barker_playlist_schedule success\n";

//清理過期的資料playlist schedule history資料
$sql = "DELETE FROM barker_playlist_schedule_history WHERE  created_time < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH)";
if(!$mydb->execute($sql))
    echo "delete barker_playlist_schedule_history fail\n";
else
    echo "delete barker_playlist_schedule_history success\n";

//清理未連結的playlist資料
$sql = "DELETE FROM barker_playlist WHERE playlist_id NOT IN (SELECT DISTINCT playlist_id FROM barker_playlist_schedule_history)";
if(!$mydb->execute($sql))
    echo "delete barker_playlist fail\n";
else
    echo "delete barker_playlist success\n";


$sql = "DELETE FROM barker_playlist_record WHERE playlist_id NOT IN (SELECT DISTINCT playlist_id FROM barker_playlist)";
if(!$mydb->execute($sql))
    echo "delete barker_playlist_record fail\n";
else
    echo "delete barker_playlist_record success\n";

$sql = "DELETE FROM barker_playlist_template WHERE playlist_id NOT IN (SELECT DISTINCT playlist_id FROM barker_playlist)";
if(!$mydb->execute($sql))
    echo "delete barker_playlist_template fail\n";
else
    echo "delete barker_playlist_template success\n";

$sql = "DELETE FROM barker_order_cue WHERE barker_order_cue.date < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH)";
if(!$mydb->execute($sql))
    echo "delete barker_playlist fail\n";
else
    echo "delete barker_playlist success\n";

//import紀錄清理
$sql = "DELETE FROM barker_playlist_import_result WHERE IF(last_updated_time IS NULL, created_time < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH), created_time < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH))";
if(!$mydb->execute($sql))
    echo "delete barker_playlist_import_result fail\n";
else
    echo "delete barker_playlist_import_result success\n";

$sql = "DELETE FROM barker_material_import_result WHERE IF(last_updated_time IS NULL, created_time < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH), created_time < DATE_SUB(NOW(), INTERVAL $dataKeepMonths MONTH))";
if(!$mydb->execute($sql))
    echo "delete barker_material_import_result fail\n";
else
    echo "delete barker_material_import_result success\n";


?>
