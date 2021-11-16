<?php
/*****
連線資料庫並取得或更新白名單資訊
*****/
header("Content-Type: application/json; charset=utf-8");
require_once '../../Config_VSM_Meta.php';
$url_n = Config_VSM_Meta::VSM_API_ROOT.'epgBannerAuth/ajax_ad_tadret_list.php';
$url_s = Config_VSM_Meta::VSM_API_ROOT_S.'epgBannerAuth/ajax_ad_tadret_list.php';
if($_POST["area"]=="S"){
    $url = $url_s;
}
else{
    $url = $url_n;
}
$postvars = http_build_query($_POST);
// 建立CURL連線
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_TIMEOUT, 500);
//curl_setopt($ch, CURLOPT_HEADER, true);
$apiResult = curl_exec($ch);
exit($apiResult);
?>