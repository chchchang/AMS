<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 接收 AJAX 傳遞的資料
    $apiUrl = $_POST['redirectUrl'];
    $params = $_POST;
    unset($params['apiUrl']); // 移除 apiUrl 參數

    // 建立 curl 請求
    $curl = curl_init($apiUrl);
    
    // 設定 POST 請求的參數
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
    
    // 設定接收回應的選項
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    // 執行請求並取得回應
    $response = curl_exec($curl);
    
    // 檢查是否有錯誤發生
    if ($response === false) {
        $error = curl_error($curl);
        // 處理錯誤
    } else {
        // 處理回應
        echo $response;
    }
    
    // 關閉 curl 請求
    curl_close($curl);
}
?>