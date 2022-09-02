<?php
//20220602 從取得上傳的影片檔案
require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once '/var/www/html/AMS/Config.php';
//require_once '../../Config.php';//dev
require_once 'BarkerConfig.php';
require_once '/var/www/html/AMS/tool/SFTP.php';

if(!isset($argv[1])||!isset($argv[2])||!isset($argv[3]))
		exit('usage:php log.php <date> <channel_id> <hours>'."\n");

$exect = new copyBarkerMaterial($argv[1],$argv[2],$argv[3]);
$exect->hadle();

class copyBarkerMaterial{
    private $mydb;
    private $logWriter;
    private $date;
    private $MaterialHash;//儲存排播表素材和素材原始檔對照表{託播單號=>素材名稱}
    private $rawMaterialFolder;
    private $remoteMaterialFolder;
    private $channel_id;
    private $hours;
    private $sftpInfo;

    function __construct($date="",$channel_id,$hours="all") {
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
        $this->channel_id = explode(",",$channel_id);
        $this->hours = $hours;
        $this->sftpInfo=BarkerConfig::$sftpInfo;
        $this->remoteMaterialFolder = BarkerConfig::$remoteMaterialFolder;

    }

    function __destruct()
    {
        $this->mydb->close();
        fclose($this->logWriter);
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s').$line."\n";
        echo $message;
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
        return $result;
    }

    public function hadle($date=''){
        if($date == '')
            $date = date('Y-m-d');
        $this->dolog("------copy Material start-----");
        $this->dolog("target date:".$this->date." target channel:".implode(",",$this->channel_id)." target hours:".$this->hours);

        foreach($this->channel_id as $chid){
            $this->copyFile("data/".$chid."/".$this->date."_$this->hours.json");
        }

    }

    function copyFile($readfileName){
        $playlist = json_decode(file_get_contents($readfileName),true);//dev
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
                    if($this->checkLocalMaterial($this->rawMaterialFolder.$rawFileName)){
                        /*if($this->checkIfFileSame($this->rawMaterialFolder.$rawFileName, BarkerConfig::$doneMaterialFolder."/".$fileset["filename"])){
                            $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."與".BarkerConfig::$doneMaterialFolder."/".$fileset["filename"]." 檔案相同，不複製");
                        }
                        else{
                            if(copy($this->rawMaterialFolder.$rawFileName, "material/".$fileset["filename"])){
                                $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."到material/".$fileset["filename"]." 成功");
                            }
                            else
                                $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."到material/".$fileset["filename"]." 失敗");
                        }*/

                        if($this->checkIfModified($this->rawMaterialFolder.$rawFileName,$this->remoteMaterialFolder."/".$fileset["filename"])){
                            if(copy($this->rawMaterialFolder.$rawFileName, "material/".$fileset["filename"])){
                                $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."到material/".$fileset["filename"]." 成功");
                            }
                            else
                                $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."到material/".$fileset["filename"]." 失敗");
                        }
                        else{
                            $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."與遠端檔案相同，不複製");
                        }

                    } 
                    else{
                        $this->dolog("$this->rawMaterialFolder.$rawFileName 本地檔案不存在");
                    }
                }
            }
        }
    }
    //檢查檔案是否和曾經派送過的相同
    function checkIfFileSame($file_a,$file_b){
        if(!file_exists($file_b)){
            return false;
        }
        if (filesize($file_a) == filesize($file_b)
            && md5_file($file_a) == md5_file($file_b)
        ){
            return true;
        }
        else{
            return false;
        }
    }
    //檢查本地檔案是否存在
    private function checkLocalMaterial($filepath){
        if(file_exists($filepath)){
            return true;
        }
        else{
            $file_name = str_replace($this->rawMaterialFolder,"",$filepath);
            $nameParse = explode('_',$file_name);
            $material_id = array_shift($nameParse);
            $sql = "
            INSERT INTO barker_material_import_result (material_id,file_name) VALUES (?,?)	
            ON DUPLICATE KEY
            UPDATE import_time=now(),import_result=0,message='AMS端檔案不存在',last_updated_time=now()"
            ;
            if(!$this->mydb->execute($sql,'is',$material_id,$file_name)){
                $this->mydb->close();
                return false;
            }
            return false;
        }
    }

    //檢查本地檔案是否派送成功
    private function checkIfModified($originFile,$remoteFile){       
        $remoteETime = SFTP::getFileModifiedTime($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$remoteFile);
        $localETime = filemtime($originFile);
        if(!$remoteETime){
            return true;
        }
        if($remoteETime<$localETime){
            return true;
        }
        
        return false;
        
    }
    
}

?>