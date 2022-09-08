<?php
//20220802 獨立為class
//20220602 從取得上傳的影片檔案
require_once dirname(__FILE__).'/../../../tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once dirname(__FILE__).'/../../../Config.php';
//require_once '../../Config.php';//dev
require_once dirname(__FILE__).'/BarkerConfig.php';
require_once dirname(__FILE__).'/../../../tool/SFTP.php';
require_once dirname(__FILE__).'/PutToWatchFolder.php';
require_once dirname(__FILE__).'/../../../tool/MyMailer.php';

/*if(!isset($argv[1])||!isset($argv[2])||!isset($argv[3]))
		exit('usage:php log.php <date> <channel_id> <hours>'."\n");

$exect = new uploadMaterialByPlayList();
$exect->hadle($argv[1],$argv[2],$argv[3]);*/

class UploadMaterialByPlayList{
    private $mydb;
    private $logWriter;
    private $date;
    private $MaterialHash;//儲存排播表素材和素材原始檔對照表{託播單號=>素材名稱}
    private $rawMaterialFolder;
    private $remoteMaterialFolder;
    private $channel_id;
    private $hours;
    private $sftpInfo;
    public $message;

    function __construct($logger = null) {
        if($logger == null){
            if(!is_dir("log")){
                if (!mkdir("log", 0777, true)) {
                    die('Failed to create log directories...');
                }
            }
            $this->logWriter = fopen("log/uploadMaterialByPlayList".date('Y-m-d').".log","a");
        }
        else{
            $this->logWriter = $logger;
        }
        
        $this->mydb=new MyDB(true);
        $this->rawMaterialFolder = Config::GET_MATERIAL_FOLDER();
   
        $this->sftpInfo=BarkerConfig::$sftpInfo;
        $this->remoteMaterialFolder = BarkerConfig::$remoteMaterialFolder;
        $this->message ="";
    }

    function __destruct()
    {
        $this->mydb->close();
        //fclose($this->logWriter);
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s').$line."\n";
        //echo $message;
        fwrite($this->logWriter,$message);
    }


    public function hadle($date="",$channel_id,$hours="all"){
        if($date=="")
            $this->date=date("Y-m-d");
        else
        $this->date = $date;
        $this->channel_id = explode(",",$channel_id);
        $this->hours = $hours;

        $this->dolog("------copy Material start-----");
        $this->dolog("target date:".$this->date." target channel:".implode(",",$this->channel_id)." target hours:".$this->hours);

        foreach($this->channel_id as $chid){
            $this->copyFile(BarkerConfig::$playlistFolder."/".$chid."/".$this->date."_$this->hours.json");
        }

        return true;

    }

    function copyFile($readfileName){
        $sftp = new PutToWatchFolder();
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
                    $remoteFile = $this->remoteMaterialFolder."/".$fileset["filename"];
                    $this->MaterialHash[$fileset["filename"]] = $rawFileName;
                    if($this->checkLocalMaterial($this->rawMaterialFolder.$rawFileName)){
                        if($this->checkIfModified($this->rawMaterialFolder.$rawFileName, $remoteFile)){
                            if($sftp->uploadedMaterial($this->rawMaterialFolder.$rawFileName, $remoteFile)){
                                $this->dolog("$remoteFile 上傳成功");
                                $this->message.=$fileset["filename"]."上傳成功\n";
                            }
                            else{
                                $this->dolog("$remoteFile 上傳失敗");
                                $this->message.=$fileset["filename"]."上傳失敗\n";
                            }
                        }
                        else{
                            $this->dolog("複製".$this->rawMaterialFolder.$rawFileName."與遠端檔案相同，不複製");
                            $this->message.=$fileset["filename"]."與遠端檔案相同，不需複製\n";
                        }

                    } 
                    else{
                        $this->dolog("$this->rawMaterialFolder.$rawFileName 本地檔案不存在");
                        $this->message.=$fileset["filename"]."本地檔案不存在\n";
                    }
                }
            }
        }
    }

    //檢查本地檔案是否存在
    private function checkLocalMaterial($filepath){
        if(file_exists($filepath)){
            return true;
        }
        else{
            $file_name = str_replace($this->rawMaterialFolder,"",$filepath);
            $nameParse = explode('.',$file_name);
            $material_id = array_shift($nameParse);
            $mailer = new MyMailer();
			$mailer->sendMail("barker素材:".$material_id." 檔案匯入失敗","barker素材檔案匯入失敗\n素材識別碼:".$material_id."\n失敗原因:AMS端檔案不存在");
            $sql = "
            INSERT INTO barker_material_import_result (material_id,file_name) VALUES (?,?)	
            ON DUPLICATE KEY
            UPDATE import_time=now(),import_result=null,message='AMS端檔案不存在',last_updated_time=now()"
            ;
            if(!$this->mydb->execute($sql,'is',$material_id,$file_name)){
                $this->mydb->close();
                return false;
            }
            return false;
        }
    }

    //檢查遠端檔案是否須重派送
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