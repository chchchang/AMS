<?php
//20220624 將資料夾中的排播表和素材上傳到barker的sftp watcher server
require_once '/var/www/html/AMS/tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once '/var/www/html/AMS/Config.php';
//require_once '../../Config.php';//dev
require_once '/var/www/html/AMS/tool/SFTP.php';

$exect = new putToWatchFolder();
$exect->hadle($argv[1]);

class putToWatchFolder{
    private $mydb;
    private $logWriter;
    private $sftpInfo;
    private $materialFolder;
    private $playlistFolder;
    private $doneMaterialFolder;
    private $donePlaylistFolder;
    private $romoteMaterialFolder;
    private $romotePlaylistFolder;
    function __construct() {
        $dir = "material";
        if(!is_dir($dir)){
            if (!mkdir($dir, 0777, true)) {
                $this->dolog('Failed to create directories '.$dir.'...');
                die('Failed to create directories '.$dir.'...');
            }
        }

        $this->mydb=new MyDB(true);
        $this->logWriter = fopen("log/putToWatcherFolder".date('Y-m-d').".log","a");
        
        $this->materialFolder = "material";
        $this->playlistFolder = "data";

        $this->romoteMaterialFolder = "VIDEO";
        $this->romotePlaylistFolder = "JSON";

        $this->doneMaterialFolder = "material_done";
        $this->donePlaylistFolder = "data_done";
        
        /*if (!mkdir($this->doneMaterialFolder, 0777, true)) {
            die('Failed to create log directories...');
        }
        if (!mkdir($this->donePlaylistFolder, 0777, true)) {
            die('Failed to create log directories...');
        }*/
        $this->sftpInfo=
        [
            'host'=>"172.17.233.27",
            'username'=>"pumping",
            "password"=>"Pump@2022"
        ];
        /*$this->sftpInfo=
        [
            'host'=>"localhost",
            'username'=>"ams",
            "password"=>"Ams@chttl853"
        ];*/
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

    /**
     * 將檔案送到sftp
     */
    private function putToSftpServer($local,$remote){
        echo $this->sftpInfo['host']." ".$this->sftpInfo['username']." ".$this->sftpInfo['password']." ".$local." ".$remote."\n";
        if(SFTP::isFile($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$remote)){
            return false;
        }
        return SFTP::put($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$local,$remote);
    }

    private function checkAnduploadedMaterial($filepath){
        $file_name = str_replace($this->materialFolder.'/',"",$filepath);
        $nameParse = explode('_',$file_name);
		$material_id = array_shift($nameParse);
		
        //嘗試上傳，並更新DB資料
        if(!$this->putToSftpServer($filepath,$this->romoteMaterialFolder.'/'.$file_name)){
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
            //rename($filepath, $this->doneMaterialFolder."/".$file_name);//pro
            copy($filepath, $this->doneMaterialFolder."/".$file_name);//dev
        }
    }

    private function checkAnduploadedPlayList($filepath){
        $fileNameWithChDir = str_replace($this->playlistFolder.'/',"",$filepath);
        $nameParse = explode('/',$fileNameWithChDir);
		$channel_id  = array_shift($nameParse);
		$file_name =  array_shift($nameParse);
        //嘗試上傳，並更新DB資料
        if(!$this->putToSftpServer($filepath,$this->romotePlaylistFolder.'/'.$fileNameWithChDir)){
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
            //rename($filepath, $this->donePlaylistFolder."/".$file_name);//pro
            copy($filepath, $this->donePlaylistFolder."/".$file_name);//dev
        }
    }

    public function hadle($file_name){
        echo $this->materialFolder.'/'.$file_name." --> ".$this->romoteMaterialFolder.'/'.$file_name."\n";
        //$this->putToSftpServer($this->materialFolder.'/'.$file_name,$this->romoteMaterialFolder.'/'.$file_name);
        //$mdate= SFTP::getFileModifiedTime($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$this->romoteMaterialFolder.'/'.$file_name);
        filemtime("webdictionary.txt");
        $mdate= SFTP::getFileModifiedTime($this->sftpInfo['host'],$this->sftpInfo['username'],$this->sftpInfo['password'],$this->romotePlaylistFolder.'/"2022-07-30_all.json"');
        
        if(!$mdate)
            echo "fail";
        echo date("Y-m-d H:i:s", $mdate);
    }    
}

?>