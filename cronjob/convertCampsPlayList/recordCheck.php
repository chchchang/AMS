<?php
//20220530 轉換CAMPS的播放清單道新的barker server
require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once '/var/www/html/AMS/Config.php';
//require_once '../../Config.php';//dev
require_once 'BarkerConfig.php';
$date=date("Y-m-d");
$channel_ids = "";
$hours="all";
if(!isset($argc)){
    echo "argc and argv not allow, use default value: date: $date ,channel ids: all \n";
}
else{
    if(isset($argv[1])){
        $date = $argv[1];
    }
    if (isset($argv[2])){
        $channel_ids=$argv[2];
    }
    if (isset($argv[3])){
        $hours=$argv[3];
    }
}



$exect = new compareHilper();
$exect->hadle($date,$channel_ids,$hours);

class compareHilper{
    private $mydb;
    private $logWriter;
    private $recordApiURL;
    private $schListApiURL;
    private $date;
    private $targetChIds;
    private $hours;

    function __construct() {
        $this->mydb=new MyDB(true);
        //$this->logWriter = fopen("log/converCampsPlaylist".date('Y-m-d').".log","a");
        
        $this->recordApiURL = "http://172.17.233.28:8080/api/pump/getPlayRecord";
        
        $this->schListApiURL = "http://172.17.233.28:8080/api/pump/getPlaySchedule";//pro
     
    }

    function __destruct()
    {
        $this->mydb->close();
        //fclose($this->logWriter);
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s')." ".$line."\n";
        echo $message;
        //fwrite($this->logWriter,$message);
    }

    private function getDateFromApi($url,$post){
        $this->dolog("get Data form url:".$url);
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        //curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($post));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function hadle($date="",$channel_ids="",$hours="all"){
        echo "parameters: date: $date ,channel ids: $channel_ids \n";
        if($date=="")
            $this->date=date("Y-m-d");
        else
            $this->date = $date;
        if($channel_ids=="")
            $this->targetChIds = [];
        else
            $this->targetChIds = explode(",",$channel_ids);
        $this->hours =$hours;
   
        $recData=json_decode($this->getDateFromApi($this->recordApiURL,array("channel_id"=>$channel_ids,"start_date"=>$date)),true)["channelRecords"][0];
        $schData=json_decode($this->getDateFromApi($this->schListApiURL,array("channel_id"=>$channel_ids,"start_date"=>$date)),true)["channelSchedules"][0];
        echo "channel ". $recData['channel_id']." ".$schData['channel_id'];
        $recData = $recData['playRecords'];
        $schData = $schData['playSchedules'];
        $schId = 0;
        $recId = 0;
        if($hours!= "all"){
            //找到指定時段
            for(;$schId<count($schData);$schId++) {
                if($this->startsWith($schData[$schId]["start_time"],"$channel_ids $hours"))
                    break;
            }

            for(;$recId<count($recData);$recId++) {
                if($this->startsWith($recData[$recId]["start_time"],"$channel_ids $hours"))
                    break;
            }
        }

        for(;$schId<count($schData);$schId++,$recId++){
            
            if($schData[$schId]["transaction_id"]!=$recData[$recId]["transaction_id"])
                echo "no match\n";
            
            if($this->startsWith($schData[$schId]["start_time"],"$channel_ids $hours"))
                break;
        }
        

    }    

    function startsWith ($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }
  
    
}

?>