<?php
date_default_timezone_set("Asia/Taipei");
require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once '/var/www/html/AMS/Config.php';
//require_once '../../Config.php';//dev
if(isset($argv[1])){
    $exect = new API($argv[1]);
    $exect->hadle();
}
else{
    $exect = new API();
    $exect->hadle();
}


class API{
    private $mydb;
    private $logWriter;
    private $recordApiUrl;
    private $searchDate;
    private $outWriter;
    private $positionDataByChannel;

    function __construct($date="") {
        $this->mydb=new MyDB(true);
        $this->logWriter = fopen("log/".date('Y-m-d').".getNewPumpingBarker".".log","a");
        //$this->recordApiUrl = "http://localhost/AMS/cronjob/convertCampsPlayList/getPlayRecord_CH5_20220614.json";//dev
        $this->recordApiUrl = "http://172.17.233.28:8080/api/pump/getPlayRecord";//pro
        $this->getPositionData();
        if($date=="")
            $this->searchDate = date("Y-m-d",strtotime("-1 days"));
        else
            $this->searchDate = $date;
        $this->outWriter = fopen($this->searchDate."_NewPumpingServer.txt","w");
    }

    function __destruct()
    {
        $this->mydb->close();
        fclose($this->logWriter);
        fclose($this->outWriter);
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s')." ".$line."\n";
        echo $message;//test
        fwrite($this->logWriter,$message);
    }

    private function getDateFromApi($url,$postvas){
        $this->dolog("get Data form url:".$url);
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        //curl_setopt($ch, CURLOPT_PROXY, '');//dev 因OA環境有PROXY才需額外取消proxy設定
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postvas);
        // Will return the response, if false it prints the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function hadle(){
        $this->dolog("------get log for iab start-----");
        $this->dolog("target date.".$this->searchDate);

        //先取得頻道資料
        foreach($this->positionDataByChannel as $channle_id=>$channle_data){
            $postvas = http_build_query(array("channel_id"=>$channle_id,"search_date"=>$this->searchDate));
            $apiReturn=json_decode($this->getDateFromApi($this->recordApiUrl,$postvas),true);
            if($apiReturn["returnCode"]!=1){
                $this->dolog("get date Fail: ".$apiReturn["returnMessage"]);
            }
            else{
                //逐頻道資料取的播放資料資料
                foreach($apiReturn["channelRecords"] as $channelRecord){
                    $serCode = $channelRecord["channel_id"]." ".$channle_data["版位名稱"];
                    $IPPORT = $channelRecord["channel_url"];
                    $this->dolog("processing channel:".$serCode);
                    //逐播放資料
                    foreach($channelRecord["playRecords"] as $playRecord){
                        //整理書出資訊:互動專區代碼 IP&PORT 託播單號 媒體代碼 媒體名稱 媒體種類 媒體長度 託播型式 開始日期 開始時間 結束日期 結束時間
                        // format of start_time/end_time :2022-05-26 00:00:00
                        $server_id = "";
                        $stt = explode(' ',$playRecord['start_time']);
                        $ett = explode(' ',$playRecord['end_time']);
                        //fromat of file_name: 49616_PV-22法網-PV2_SD-H264_MPEG1L2(286M).mpg  where 49616 is media_id
                        $minfo = explode('\\',$playRecord['file_name']);
                        $mnameinfo = explode("_",$minfo[count($minfo)-1]);
                        $mediaId = $mnameinfo[0];
                        $orderId = $playRecord["transaction_id"];
                        $mediaName = $playRecord["file_name"];
                        $mediaType = 'v';
                        $mediaLength = $playRecord['seconds'];
                        $playType = 1;
                        $startDate = $stt[0];
                        $startTime = $stt[1];
                        $endDate = $ett[0];
                        $endTime = $ett[1];
                        $temp=array($server_id,$serCode,$IPPORT,$orderId,$mediaId,$mediaName,$mediaType,$mediaLength,$playType,$startDate,$startTime,$endDate,$endTime);
                        fwrite($this->outWriter,implode(',',$temp)."\n");
                    }
                }
                $this->dolog("channel $channle_id processed");
            }
            $this->dolog("all channels processed");
        }
        $this->dolog("------ job done ------");
    }

    private function getPositionData(){
        //取得版位資料
        $this->dolog("getting position data from db");
		$sql='	SELECT  版位.版位名稱,channelId參數.版位其他參數預設值 AS channel_id, playoutId參數.版位其他參數預設值 AS playout_id, serCode參數.版位其他參數預設值 AS serCode,版位.版位識別碼
        FROM 版位 
            JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
            JOIN 版位其他參數 channelId參數 ON channelId參數.版位識別碼 = 版位.版位識別碼 AND channelId參數.版位其他參數名稱="channel_id"
            JOIN 版位其他參數 playoutId參數 ON playoutId參數.版位識別碼 = 版位.版位識別碼 AND playoutId參數.版位其他參數名稱="playout_id"
            LEFT JOIN 版位其他參數 serCode參數 ON serCode參數.版位識別碼 = 版位.版位識別碼 AND serCode參數.版位其他參數名稱="serCode"
        WHERE 
            版位類型.版位名稱 = "barker頻道"
        ';
        $sql .= ' and channelId參數.版位其他參數預設值 in ("12","15","2","30","42","49","50","6","7","48","20","3","5","21","13","43","17")';
        //dev
        if(!$positionData = $this->mydb->getResultArray($sql)){
            $this->dolog("getting position data fail.....");
        }
        //依照channelId整理版位資料
        else{
            foreach($positionData as $data){
                $this->positionDataByChannel[$data['channel_id']]=$data;
            }
        }
    }
    

    
}

?>