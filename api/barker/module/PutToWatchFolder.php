<?php
//20220624 將資料夾中的排播表和素材上傳到barker的sftp watcher server
require_once dirname(__FILE__).'/../../../tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once dirname(__FILE__).'/../../../Config.php';
//require_once '../../Config.php';//dev
require_once dirname(__FILE__).'/BarkerConfig.php';
require_once dirname(__FILE__).'/../../../tool/SFTP.php';


/*$exect = new putToWatchFolder();
$exect->hadle();*/

class PutToWatchFolder{
    private $mydb;
    private $logWriter;
    private $sftpInfo;
    private $date;
    private $materialFolder;
    private $playlistFolder;
    private $doneMaterialFolder;
    private $donePlaylistFolder;
    private $remoteMaterialFolder;
    private $remotePlaylistFolder;
    public $message;

    function __construct() {
        $this->mydb=new MyDB(true);
        $this->logWriter = fopen("log/putToWatcherFolder".date('Y-m-d').".log","a");
        
        $this->materialFolder = BarkerConfig::$materialFolder;
        $this->playlistFolder = BarkerConfig::$playlistFolder;

        $this->remoteMaterialFolder = BarkerConfig::$remoteMaterialFolder;
        $this->remotePlaylistFolder = BarkerConfig::$remotePlaylistFolder;

        $this->doneMaterialFolder = BarkerConfig::$doneMaterialFolder;
        $this->donePlaylistFolder = BarkerConfig::$donePlaylistFolder;
     
        //$this->chdir($this->doneMaterialFolder);
        //$this->chdir($this->donePlaylistFolder);
        //$this->chdir($this->materialFolder);
        $this->chdir($this->playlistFolder);
        

        $this->sftpInfo=BarkerConfig::$sftpInfo;
        $this->message ="";
    }

    function __destruct()
    {
        $this->mydb->close();
        fclose($this->logWriter);
        //fclose($this->playListReader);
    }

    private function chdir($dir){
        if(!is_dir($dir)){
            if (!mkdir($dir, 0777, true)) {
                die("Failed to create $dir directories...");
            }
        }
    }

    private function dolog($line){
        $message = date('Y-m-d h:i:s')." ".$line."\n";
        //echo $message;
        fwrite($this->logWriter,$message);
    }

    /**
     * 將檔案送到sftp
     */
    private function putToSftpServer($local,$remote){
        
        if(!SFTP::put($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$local,$remote)){
            $this->dolog("upload $local to $remote fail");
            return false;
        }
        $this->dolog("upload $local to $remote success");
        return true;
    }

    public function uploadedMaterial($loacl,$remote){
        $file_name = str_replace($this->remoteMaterialFolder.'/',"",$remote);
        $nameParse = explode('_',$file_name);
		$material_id = array_shift($nameParse);
		
        //嘗試上傳，並更新DB資料
        if(!$this->putToSftpServer($loacl,$remote)){
            return false;
        }
        else{
            $sql = "
            INSERT INTO barker_material_import_result (material_id,file_name) VALUES (?,?)	
            ON DUPLICATE KEY
            UPDATE import_time=NULL,import_result=NULL,message=NULL,last_updated_time=now()"
            ;
            if(!$this->mydb->execute($sql,'is',$material_id,$file_name)){
                $this->mydb->close();
                return false;
            }
            $sql = "
            UPDATE 素材 set CAMPS影片派送時間 = now(),CAMPS影片媒體編號=999 WHERE 素材識別碼 = ?"
            ;
            if(!$this->mydb->execute($sql,'i',$material_id)){
                $this->mydb->close();
                return false;
            }
        }
        return true;
    }

    public function uploadedPlayList($filepath){
        $fileNameWithChDir = str_replace($this->playlistFolder.'/',"",$filepath);
        $nameParse = explode('/',$fileNameWithChDir);
		$channel_id  = array_shift($nameParse);
		$file_name =  array_shift($nameParse);
        
        //嘗試上傳，並更新DB資料
        if(!$this->putToSftpServer($filepath,$this->remotePlaylistFolder.'/'.$fileNameWithChDir)){
            return false;
        }
        else{
            $sql = "
            INSERT INTO barker_playlist_import_result (channel_id ,file_name) VALUES (?,?)	
            ON DUPLICATE KEY
            UPDATE import_time=NULL,import_result=NULL,message=NULL,last_updated_time=now()"
            ;
            if(!$this->mydb->execute($sql,'is',$channel_id ,$file_name)){
                $this->mydb->close();
                return false;
            }            
        }
        return true;
    }

     
}

?>