<?php
//20220530 轉換CAMPS的播放清單道新的barker server
require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once '/var/www/html/AMS/Config.php';
//require_once '../../Config.php';//dev

$exect = new converCampsPlaylist();
$exect->hadle();

class converCampsPlaylist{
    private $mydb;
    private $logWriter;
    private $channelIdApiURL;
    private $palyListApiURL;
    private $date;
    private $transHash;//儲存CAMPS編號和託播單號的對照表{<CAMPS編號>=><託播單單號>}
    private $filenameHash;//儲存託播單號和素材名稱的對照表{託播單號=>素材名稱}

    function __construct($date="") {
        $this->mydb=new MyDB(true);
        $this->logWriter = fopen("log/converCampsPlaylist".date('Y-m-d').".log","a");
        //$this->channelIdApiURL = "http://localhost/AMS/cronjob/convertCampsPlayList/campsChannel.php";//dev
        $this->channelIdApiURL = Config::$CAMPS_API["channel"]."?orbit_only=1";//pro
        //$this->palyListApiURL = "http://localhost/AMS/cronjob/convertCampsPlayList/test.php";//dev
        $this->palyListApiURL = Config::$CAMPS_API["playlist"];//pro
        if($date=="")
            $this->date=date("Y-m-d");
        else
            $this->date = $date;
    }

    function __destruct()
    {
        $this->mydb->close();
        fclose($this->logWriter);
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s').$line."\n";
        echo $message;
        //fwrite($this->logWriter,$message);
    }

    private function getDateFromApi($url){
        $this->dolog("get Data form url:".$url);
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_PROXY, '');//dev 因OA環境有PROXY才需額外取消proxy設定
        // Will return the response, if false it prints the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function hadle($date=''){
        if($date == '')
            $date = date('Y-m-d');
        $this->dolog("------convert CAMPS play list start-----");
        $this->dolog("target date.".$date);

        //先取得頻道資料
        $channelData=json_decode($this->getDateFromApi($this->channelIdApiURL),true);
        
        //逐頻道資料取的playlist資料
        foreach($channelData as $rowData){
            $channel_id = $rowData["channel_id"];
            $this->dolog("processing channel:".$channel_id);
            $outputData = $this->getOutputData($channel_id);
            $this->writeDate($outputData,$channel_id);
        }

    }

    /***
     * 依照channel_id查詢Playlist資料後回傳
     */
    private function getOutputData($channel_id){
        //先利用API取得CAMPS playlist資料
        $url = $this->palyListApiURL."?channel_id=".$channel_id."&&date=".$this->date;
        $campsPlay = json_decode($this->getDateFromApi($url),true);
        $output = array();
        foreach($campsPlay as $hoursPlayList){
            $this->dolog("processing playList ".$hoursPlayList["datetime"]);
            $datetime = explode(" ",$hoursPlayList["datetime"]);
            $date = $datetime[0];
            $hour = explode(":",$datetime[1])[0];
            //逐播放清單由trasanction id 反查託播單與素材名稱
            $playlist = array();
            foreach($hoursPlayList["tx_id_list"] as $trans){
                //transactionId反查託播單號
                $orderId = "";
                if(isset($this->transHash[$trans])){
                    $orderId = $this->transHash[$trans];
                }
                else{
                    $orderId = $this->getOrderIdByTrasanctionId($trans);
                    $this->transHash[$trans] = $orderId;
                }
                //託播單號反查素材名稱
                $materialName = "";
                if(isset($this->filenameHash[$orderId])){
                    $materialName = $this->filenameHash[$orderId];
                }
                else{
                    $materialName = $this->getFileNameByOrderId($orderId);
                    $this->filenameHash[$orderId] = $materialName;
                }
                $this->dolog("CAMPSID".$trans."filename ".$materialName."transactionid ".$orderId);
                $transSet = array("filename"=>$materialName,"transactionid"=>$orderId);
                array_push($playlist,$transSet);
            }
            $temp = array();
            $temp["channel_id"] = $channel_id;
            $temp["date"] = $date;
            $temp["hour"] = $hour;
            $temp["playlist"] = $playlist;
            array_push($output,$temp);
        }
        
        return $output;
    }

    private function getOrderIdByTrasanctionId($trasanction){
        $oid = "";
        $sql = "select 託播單識別碼 from 託播單CAMPS_ID對照表,素材 where transaction_id = ?";
        $data = $this->mydb->getResultArray($sql,'i',$trasanction);
        if($data != null)
            $oid = $data[0]["託播單識別碼"];
        return $oid;
        
    }

    private function getFileNameByOrderId($orderId){
        $sql = "select 素材識別碼,素材原始檔名 from 託播單素材 JOIN 素材 USING(素材識別碼) where 託播單識別碼 = ? AND 素材順序 = 1";
        $data = $this->mydb->getResultArray($sql,'i',$orderId);
        
        $fliename = "";
        if($data != null){
            $mid = $data[0]["素材識別碼"];
            $mname = $data[0]["素材原始檔名"];
            $fliename =$mid."_".$mname;
        }
        return $fliename;
        
    }

    //輸出檔案
    private function writeDate($data,$channel){
        $this->dolog('write output data...');
        $dir = "data/$channel";
        if(!is_dir($dir)){
            if (!mkdir($dir, 0777, true)) {
                $this->dolog('Failed to create directories '.$dir.'...');
                die('Failed to create directories '.$dir.'...');
            }
        }
        $txt = json_encode($data);
        $myfile = fopen($dir."/".$this->date."_all.json", "w") or die("Unable to open file!");
        fwrite($myfile, $txt);
        fclose($myfile);
    }
    

    
}

?>