<?php
namespace AMS\apiProxy\pointBarker\Utilis;
require_once(__DIR__."/../Config.php");


use AMS\apiProxy\pointBarker\Config ;

class ApiConnector{
    
    public function __construct()
    {
    
    }

    public function getApiUrlByApiName($apiName){
        
        return  isset(Config::$BarkerApi[$apiName])? Config::$BarkerApi[$apiName] : false;
    }

    
    public function getDataFromApi($url,$postData=array()){
        $postvar = http_build_query($postData);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postvar);
        //curl_setopt($ch, CURLOPT_PROXY, '');//dev 因OA環境有PROXY才需額外取消proxy設定
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    
    public function filterOfflineChannel($data){
        $filteredChannels = array();
        $filteredChannels["channels"]=[];
        foreach($data["channels"] as $id=>$row){
            if($row["online"])
                array_push($filteredChannels["channels"],$row);
        }
        return $filteredChannels;
    }
}
?>