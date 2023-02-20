<?php
//20220530 轉換CAMPS的播放清單道新的barker server
//20220802 獨立為class
//20220816 猜分寫檔和上傳動作
//20220907 新增return資料，不寫檔的function getData
//20230220 正式取代CAMPS功能，輸出資料給端點barker
require_once dirname(__FILE__).'/../../../tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once dirname(__FILE__).'/../../../Config.php';
//require_once '../../Config.php';//dev
require_once dirname(__FILE__).'/BarkerConfig.php';
require_once dirname(__FILE__).'/../../../tool/SFTP.php';
require_once dirname(__FILE__).'/PutToWatchFolder.php';
/*$date=date("Y-m-d");
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
    echo "parameters: date: $date ,channel ids: $channel_ids \n";
}

$exect = new converCampsPlaylist();
print_r($exect->getData($date,$channel_ids,$hours));*/

class ConverCampsPlaylist{
    private $mydb;
    private $logWriter;
    private $palyListApiURL;
    private $date;
    private $transHash;//儲存CAMPS編號和託播單號的對照表{<CAMPS編號>=><託播單單號>}
    private $filenameHash;//儲存託播單號和素材名稱的對照表{託播單號=>素材名稱}
    private $playlistHash;//儲存播表內容(playlistid=>[playlistrecord])
    private $channel_id;
    private $hours;
    private $sftpInfo;
    private $playlistFolder;
    public $message;
    public $playlistFileName;
    public $remotePlaylistFolder;
    public $channelFromDbList=[
    13 // 18+專區
    ,14 // 東森專案
    ,18 //綜合
    ,30 // 霹靂
    ,36 // APUJAN
    ,40 // 學習
    ,43 // 測試BK
    ,49 // 864_SD
    ,50 // 864_HD
    ,6 //動漫
    ,7 //兒童館
    ,12 //遊戲城
    ,16 //WBC
    ,17 //單次計費
    ,48 //中職專區
    ];

    function __construct($logger = null) {
        $this->mydb=new MyDB(true);
        if($logger == null){
            if(!is_dir(dirname(__FILE__)."/../../../".Config::SECRET_FOLDER."/apiLog")){
                if (!mkdir("log", 0777, true)) {
                    die('Failed to create log directories...');
                }
            }
            $this->logWriter = fopen(dirname(__FILE__)."/../../../".Config::SECRET_FOLDER."/apiLog/converCampsPlaylist".date('Y-m-d').".log","a");
        }
        else{
            $this->logWriter = $logger;
        }

        //$this->palyListApiURL = "http://localhost/AMS/cronjob/convertCampsPlayList/test.php";//dev
        $this->palyListApiURL = Config::$CAMPS_API["playlist"];//pro
        $this->message ="";
        $this->sftpInfo=BarkerConfig::$sftpInfo;
        $this->remotePlaylistFolder = BarkerConfig::$remotePlaylistFolder;
        $this->playlistFolder = BarkerConfig::$playlistFolder;
    }

    function __destruct()
    {
        $this->mydb->close();
        //fclose($this->logWriter);
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s')." ".$line."\n";
        //echo $message;
        fwrite($this->logWriter,$message);
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
        $this->dolog("get Data :".$result);
        return $result;
    }

    public function hadle($date="",$channel_id="",$hours="all"){
             
        if($date=="")
            $this->date=date("Y-m-d");
        else
            $this->date = $date;
        if($channel_id=="")
            return false;
        else
            $this->channel_id = $channel_id;
        $this->hours =$hours;
        $this->dolog("------convert play list start-----");
        $this->dolog("target date:".$this->date." target channel:".$this->channel_id." target hours:".$hours);

        
      
        $this->dolog("processing channel:".$this->channel_id);
        $outputData = $this->getOutputData($this->channel_id);
        $this->playlistFileName = $this->getOutputFileName($this->channel_id);
        $this->writeDate($outputData,$this->playlistFileName);
        $this->dolog("頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表產生成功。");
        $this->message.="頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表產生成功。\n";
        

        return true;
    }
    //只回傳資料，不寫檔
    public function getData($date="",$channel_id="",$hours="all"){
             
        if($date=="")
            $this->date=date("Y-m-d");
        else
            $this->date = $date;
        if($channel_id=="")
            return false;
        else
            $this->channel_id = $channel_id;
        $this->hours =$hours;
        $this->dolog("------convert play list start-----");
        $this->dolog("target date:".$this->date." target channel:".$this->channel_id." target hours:".$hours);

        
      
        $this->dolog("processing channel:".$this->channel_id);
        $outputData = $this->getOutputData($this->channel_id);
        $this->message.="頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表產生成功。\n";
        

        return $outputData;
    }

    /***上傳檔案到pumping server */
    public function uploadToPumping(){
        $sftp = new putToWatchFolder();
        //查看本地否有以產生的檔案，若沒有，嘗試從PumpingServer下載
        if(!file_exists($this->playlistFileName."uploaded")){
            $remote = str_replace($this->playlistFolder,$this->remotePlaylistFolder,$this->playlistFileName);
            if(SFTP::isFile($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$this->playlistFileName,$remote))
                SFTP::get($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$this->playlistFileName,$remote);
            
        }
        //比較資料是否需上傳
        if(!$this->compareFiles($this->playlistFileName,$this->playlistFileName."uploaded")){
            if(!$sftp->uploadedPlayList($this->playlistFileName)){
                $this->dolog("頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表上傳失敗。");
                $this->message.="頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表上傳失敗。\n";
                return false;
            }
            $this->dolog("頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表異動，已重新上傳。");
            $this->message.="頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表異動，已重新上傳。\n";
            copy($this->playlistFileName, $this->playlistFileName."uploaded");
        }else{
            $this->dolog("頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表未異動，不需重複上傳。");
            $this->message.="頻道:$this->channel_id 日期:$this->date 時段:$this->hours 播表未異動，不需重複上傳。\n";
            //unlink($this->playlistFileName);
        }
        return true;
    }

    /***
     * 依照channel_id查詢Playlist資料後回傳
     */
    private function getOutputData(){
        /*if(in_array($this->channel_id,$this->channelFromDbList))
            return $this->getPlaylistFromDb();
        return $this->getPlaylistFormCamps();*/
        return $this->getPlaylistFromDb();
    }

    private function getPlaylistFormCamps(){
        //先利用API取得CAMPS playlist資料
        $url = $this->palyListApiURL."?channel_id=".$this->channel_id."&&date=".$this->date;
        if($this->hours!="all")
            $url .= "&&hour=$this->hours";

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
                $materialName = $this->getMaterialNameByOrderId($orderId);
                $this->dolog("CAMPSID: ".$trans." filename: ".$materialName." transactionid: ".$orderId);
                $transSet = array("filename"=>$materialName,"transactionid"=>$orderId);
                array_push($playlist,$transSet);
            }
            $temp = array();
            $temp["channel_id"] = $this->channel_id;
            $temp["date"] = $date;
            $temp["hour"] = $hour;
            $temp["playlist"] = $playlist;
            array_push($output,$temp);
        }   
        return $output;
    }

    public function getMaterialNameByOrderId($orderId){
        $materialName = "";
        if(isset($this->filenameHash[$orderId])){
            $materialName = $this->filenameHash[$orderId];
        }
        else{
            $materialName = $this->getFileNameByOrderId($orderId);
            $this->filenameHash[$orderId] = $materialName;
        }
        return $materialName;
    }

    public function getPlaylistFromDb()
    {
        $sql = "select * from barker_playlist_schedule where channel_id = ? and date = ?"; 
        $types  = "is";
        $paras = [$this->channel_id,$this->date];
        if($this->hours!=="all"){
            $sql .= " and hour = ?";
            $types  .="s" ;
            array_push($paras,str_pad($this->hours,2,"0",STR_PAD_LEFT));
        }
        $res = [];
        $playlists = $this->mydb->getResultArray($sql,$types,...$paras);
        if($playlists != null){
            foreach($playlists as $playlist){
                $temp = array();
                $temp["channel_id"] = $this->channel_id;
                $temp["date"] = $this->date;
                $temp["hour"] = $playlist["hour"];
                $pid  =$playlist["playlist_id"];
                if(!isset($this->playlistHash[$pid])){
                    $this->playlistHash[$pid] = $this->getPlayListRecord($pid);
                }
                $temp["playlist"] =  $this->playlistHash[$pid];
                array_push($res,$temp);
            }
        } 
        return $res;
    }

    public function getPlayListRecord($playlistId){
        $sql = "select 	transaction_id  from barker_playlist_record where playlist_id = ? order by offset"; 
        $records = $this->mydb->getResultArray($sql,"i",$playlistId);
        $res = [];
        foreach($records as $record){
            $materialName = $this->getMaterialNameByOrderId($record["transaction_id"]);
            $transSet = array("filename"=>$materialName,"transactionid"=>$record["transaction_id"]);
            array_push($res,$transSet);
        }
        return $res;
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
        $sql = "select 素材識別碼,素材原始檔名 from 託播單素材 JOIN 素材 USING(素材識別碼) where 託播單識別碼 = ?";
        $data = $this->mydb->getResultArray($sql,'i',$orderId);
        
        $fliename = "";
        if(isset($data[1]) && $data[1] != null){
            $mid = $data[1]["素材識別碼"];
            $mname = $data[1]["素材原始檔名"];
            $fliename =$mid."_".$mname;
        }
        else if($data[0] != null){
            $mid = $data[0]["素材識別碼"];
            $mname = $data[0]["素材原始檔名"];
            $fliename =$mid."_".$mname;
        }
        return $fliename;
        
    }

    //輸出檔案
    private function writeDate($data,$fliename){
        
        $txt = json_encode($data,JSON_UNESCAPED_UNICODE);
        $myfile = fopen($fliename, "w") or die("Unable to open file!");
        $this->dolog("write output data: $fliename...");
        fwrite($myfile, $txt);
        fclose($myfile);
    }

    //取得輸出檔名
    private function getOutputFileName($channel){
        $dir = BarkerConfig::$playlistFolder."/$channel";
        if(!is_dir($dir)){
            if (!mkdir($dir, 0777, true)) {
                $this->dolog('Failed to create directories '.$dir.'...');
                die('Failed to create directories '.$dir.'...');
            }
        }
        $fliename = $dir."/".$this->date."_$this->hours.json";
        return $fliename;
    }

    //比較檔案內容是否相同
    private function compareFiles($file_a, $file_b)
    {
        if (filesize($file_a) != filesize($file_b))
            return false;

        $chunksize = 4096;
        $fp_a = fopen($file_a, 'rb');
        $fp_b = fopen($file_b, 'rb');
            
        while (!feof($fp_a) && !feof($fp_b))
        {
            $d_a = fread($fp_a, $chunksize);
            $d_b = fread($fp_b, $chunksize);
            if ($d_a === false || $d_b === false || $d_a !== $d_b)
            {
                fclose($fp_a);
                fclose($fp_b);
                return false;
            }
        }
    
        fclose($fp_a);
        fclose($fp_b);
            
        return true;
    }
    

    
}

?>