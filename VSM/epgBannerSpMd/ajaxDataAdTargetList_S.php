<?php
/*****
連線VSM資料庫取得白名單資訊
*****/
include('Net/SFTP.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json; charset=utf-8");
require_once 'GetVsmDataAdTargetList.php';
$getData = new GetVsmDataAdTargetList("S");
$data = $getData->getData();
exit(json_encode($data));

?>