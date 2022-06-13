<?php
//20220602 從取得上傳的影片檔案
require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once '/var/www/html/AMS/Config.php';
//require_once '../../Config.php';//dev
if(!isset($argv[1])||!isset($argv[2]))
		exit('usage:php log.php <date> <channel_id>'."\n");

$exect = new copyBarkerMaterial($argv[1],$argv[2]);
$exect->hadle();

class copyBarkerMaterial{
    private $mydb;
    private $logWriter;
    private $playListReader;
    private $date;
    private $MaterialHash;//儲存排播表素材和素材原始檔對照表{託播單號=>素材名稱}
    private $readfileName;
    private $rawMaterialFolder;
    function __construct($date="",$channel_id) {
        $dir = "material";
        if(!is_dir($dir)){
            if (!mkdir($dir, 0777, true)) {
                $this->dolog('Failed to create directories '.$dir.'...');
                die('Failed to create directories '.$dir.'...');
            }
        }

        $this->mydb=new MyDB(true);
        $this->logWriter = fopen("log/copyBarkerMaterial".date('Y-m-d').".log","a");

        if($date=="")
            $this->date=date("Y-m-d");
        else
            $this->date = $date;
        
        $this->rawMaterialFolder = Config::GET_MATERIAL_FOLDER();
            
        $this->readfileName = "data/".$channel_id."/".$this->date."_all.json";
        /*$this->playListReader = fopen("data/".$readfile,"r");

        if(!$this->playListReader){
            $this->dolog("cant open file:".$readfile);
            exit();
        }*/
    }

    function __destruct()
    {
        $this->mydb->close();
        fclose($this->logWriter);
        //fclose($this->playListReader);
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
        $this->dolog("------copy Material start-----");
        $this->dolog("target date.".$date);

        //先取得頻道資料
        //$channelData=json_decode($this->getDateFromApi($this->channelIdApiURL),true);
        
        //逐頻道資料取的playlist資料
        /*foreach($channelData as $rowData){
            $channel_id = $rowData["channel_id"];
            $this->dolog("processing channel:".$channel_id);
            $outputData = $this->getOutputData($channel_id);
            $this->writeDate($outputData,$channel_id);
        }*/
        $playlist = json_decode(file_get_contents($this->readfileName),true);//dev
        foreach($playlist as $hourData){
            $playlistData = $hourData["playlist"];
            foreach($playlistData as $fileset){
                if(isset($this->MaterialHash[$fileset["filename"]]))
                    continue;
                else{
                    $mid = explode("_",$fileset["filename"])[0];
                    $tmp = explode(".",$fileset["filename"]);
                    $mtype = end($tmp);
                    $rawFileName = $mid.".".$mtype;
                    $this->MaterialHash[$fileset["filename"]] = $rawFileName;
                    if(copy($this->rawMaterialFolder.$rawFileName, "material/".$fileset["filename"]))
                        $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."到material/".$fileset["filename"]." 成功");
                    else
                        $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."到material/".$fileset["filename"]." 失敗");
                }
            }
        }

    }    
}

?>