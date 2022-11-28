<?php
header("Content-Type:text/html;charset=utf-8");
include('../Config.php');
include('../tool/MyDB.php');

$batch = new processSftpMaterial();
$batch->execute();

class processSftpMaterial {
    var $sftpPath = "";
    var $MATERIAL_FOLDER_URL= "";

    function __construct()
    {
        //$this->sftpPath = "test\\";
        $this->sftpPath = "/sftp/amsmaterial/upload/";
        $this->MATERIAL_FOLDER_URL= Config::GET_MATERIAL_FOLDER_URL(dirname(__FILE__).'\\');
    }


    function execute(){
        //取得資料夾下檔案
        $files=scandir($this->sftpPath);
        //print_r($files);
        foreach($files as $file){
            $this->dolog("checking file...".$file);
            $fileMeta = new fileMeta($file);
            if($fileMeta->checkIfMatch()){
                if($this->cpfile($fileMeta))
                    $this->updateDb($fileMeta);
            }
            else{
                //檔名不合格
                continue;
            }
        }
        
    }

    /***
     * 複製檔案到AMS網頁資料夾
     */
    function cpfile($fileMeta){
        
        $newFilePath = $this->MATERIAL_FOLDER_URL.$fileMeta->getAMSFileName();
        $filepath = $this->sftpPath.$fileMeta->getOriginFileName();
        $this->dolog("copy file from".$filepath." to ". $newFilePath);
        return rename($filepath,$newFilePath);
        //return copy($filepath,$newFilePath);
    }

    /**
     * 更新BB資料
     */
    function updateDb($fileMeta){
        $my=new MyDB(true);
        $filename = $fileMeta->getOriginFileName();
        $mid = $fileMeta->getMaterialId();
        
        $fileSize = getimagesize($this->MATERIAL_FOLDER_URL.$fileMeta->getAMSFileName());
        if($fileSize){
            $width = $fileSize[0];
            $height = $fileSize[1];
            $sql ="update 素材 set 素材原始檔名 = ?,圖片素材寬度=?,圖片素材高度=?,LAST_UPDATE_TIME=NOW() where 素材識別碼 = ?";
            if(!$my->execute($sql,"siii",$filename,$width,$height,$mid))
                $this->dolog("update db fail");
        }
        
        else{
            $sql ="update 素材 set 素材原始檔名 = ?,LAST_UPDATE_TIME=NOW() where 素材識別碼 = ?";
            if(!$my->execute($sql,"si",$filename,$mid)){
                $this->dolog("update db fail");
            }
        }
        $this->dolog("update db. originFileName:".$filename." materialId:". $mid);
    }

    function dolog($message){
        echo date('Y-m-d H:i:s ').$message."\n";
    }
}

class fileMeta{
    var $mid="";
    var $mname="";
    var $type="";
    var $filename="";
    var $isMatch = false;

    function __construct($filename)
    {
        $this->filename = $filename;
        $pattern = "/(\d+)\_(.+)\.(.+)$/";
        preg_match($pattern, $filename, $matches);

        if(count($matches)>0){
            $this->mid = $matches[1];
            $this->mname =  $matches[2];
            $this->type =  $matches[3];
            $this->isMatch =  true;
        }
        
    }
    
    //回傳檔名是否合格
    function checkIfMatch(){
        return $this->isMatch;
    }

    //取得要上傳到AMS資料夾下的檔案名稱
    function getAMSFileName(){
        $name =  $this->mid.".".$this->type;
        return $name;
    }

    //取得原始檔名
    function getOriginFileName(){
        return $this->filename;
    }

    //取得素材識別碼
    function getMaterialId(){
        return $this->mid;
    }
}
?>