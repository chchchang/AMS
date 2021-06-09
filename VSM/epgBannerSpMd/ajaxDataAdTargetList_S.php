<?php
/*****
連線VSM資料庫取得白名單資訊
*****/
header("Content-Type: application/json; charset=utf-8");
require_once 'GetVsmDataAdTargetList.php';
$getData = new GetVsmDataAdTargetList("S");
$data = $getData->getData();
exit(json_encode($data));

?>