<?php
//20220624 將資料夾中的排播表和素材上傳到barker的sftp watcher server
require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once '/var/www/html/AMS/Config.php';
//require_once '../../Config.php';//dev
require_once 'BarkerConfig.php';
require_once '/var/www/html/AMS/tool/SFTP.php';


$exect = new putToWatchFolder();
$exect->hadle();

class putToWatchFolder{
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
    function __construct() {
        $this->mydb=new MyDB(true);
        $this->logWriter = fopen("log/putToWatcherFolder".date('Y-m-d').".log","a");
        
        $this->materialFolder = BarkerConfig::$materialFolder;
        $this->playlistFolder = BarkerConfig::$playlistFolder;

        $this->remoteMaterialFolder = BarkerConfig::$remoteMaterialFolder;
        $this->remotePlaylistFolder = BarkerConfig::$remotePlaylistFolder;

        $this->doneMaterialFolder = BarkerConfig::$doneMaterialFolder;
        $this->donePlaylistFolder = BarkerConfig::$donePlaylistFolder;
     
        $this->chdir($this->doneMaterialFolder);
        $this->chdir($this->donePlaylistFolder);
        $this->chdir($this->materialFolder);
        $this->chdir($this->playlistFolder);
        

        $this->sftpInfo=BarkerConfig::$sftpInfo;
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
        echo $message;
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

    private function checkAnduploadedMaterial($filepath){
        $file_name = str_replace($this->materialFolder.'/',"",$filepath);
        $nameParse = explode('_',$file_name);
		$material_id = array_shift($nameParse);
		
        //嘗試上傳，並更新DB資料
        if(!$this->putToSftpServer($filepath,$this->remoteMaterialFolder.'/'.$file_name)){
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
            $this->dolog("move $filepath to ".$this->doneMaterialFolder."/".$file_name);
            rename($filepath, $this->doneMaterialFolder."/".$file_name);//pro
            //copy($filepath, $this->doneMaterialFolder."/".$file_name);//dev
        }
    }

    private function checkAnduploadedPlayList($filepath){
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
            $this->chdir($this->donePlaylistFolder."/".$channel_id);
            $this->dolog("move $filepath to ".$this->donePlaylistFolder."/".$channel_id."/".$file_name);
            rename($filepath, $this->donePlaylistFolder."/".$channel_id."/".$file_name);//pro
            //copy($filepath, $this->donePlaylistFolder."/".$channel_id."/".$file_name);//dev
            
        }
    }

    public function hadle($date=''){
        if($date == '')
            $date = date('Y-m-d');
        $this->dolog("------upload to sftp start-----");
        $this->dolog("target date.".$date);

 
        foreach(glob( $this->materialFolder.'/*.*') as $file) {
            //echo $file."\n";
            $this->checkAnduploadedMaterial($file);
            
        }

        foreach(glob( $this->playlistFolder.'/*/*.json') as $file) {
            //echo $file."\n";
            $this->checkAnduploadedPlayList($file);
        }

    }    
}

?>