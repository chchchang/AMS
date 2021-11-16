<?php
/*****
連線VSM資料庫取得白名單資訊
*****/
require_once '../../Config_VSM_Meta.php';
class GetVsmDataAdTargetList{
    var $url = "";
    function __construct($area){
        if($area == "S"){
            $this->url =Config_VSM_Meta::VSM_API_ROOT_S.'epgBannerAuth/ajax_ad_target_list.php';
        }
        else{
            $this->url = Config_VSM_Meta::VSM_API_ROOT.'epgBannerAuth/ajax_ad_target_list.php';
        }
    }

    function getData(){
        //取得白名單基本資訊
        $postdata = array(
            "action" => "getTargetList"
        );
        $apiResult = $this->curl($postdata,$this->url);
        $apiResult = json_decode($apiResult,true);
        $result = array();
        if($apiResult["success"]==1){
            $listdata = array();
            foreach($apiResult["data"] as $adtarget){
                $temp = array();
                array_push($temp,$adtarget['ad_target_list_id'],$adtarget['start_datetime'],$adtarget['end_datetime']);
                //依白名單ID取的MD總數
                $postdata = array(
                    "action" => "getMDCountById",
                    "data" =>array("ad_target_list_id"=>$adtarget['ad_target_list_id'])
                );
                $mdCountResult = $this->curl($postdata,$this->url);
                $mdCountResult = json_decode($mdCountResult,true);
                if($mdCountResult["success"]==1){
                    array_push($temp,$mdCountResult["data"]);
                }
                else{
                    array_push($temp,0);
                }

                array_push($listdata,$temp);
            }
        

            $result = array('aaData'=>$listdata);
            
        }
        return $result;
    }

    function curl($postdata,$url){
        $postvars = http_build_query($postdata);
        // 建立CURL連線
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        //curl_setopt($ch, CURLOPT_HEADER, true);
        $apiResult = curl_exec($ch);
        return $apiResult;
    }
}



?>