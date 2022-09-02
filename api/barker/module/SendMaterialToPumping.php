<?php
//2022 08 17 chia_chi_chang單獨上傳單一影片檔案到端點barker的模組
require_once dirname(__FILE__).'/../../../tool/MyDB.php';
//require_once '../../tool/MyDB.php';//dev
require_once dirname(__FILE__).'/../../../Config.php';
//require_once '../../Config.php';//dev
require_once dirname(__FILE__).'/BarkerConfig.php';
require_once dirname(__FILE__).'/../../../tool/SFTP.php';
require_once dirname(__FILE__).'/PutToWatchFolder.php';



class SendMaterialToPumping{
    private $mydb;
    private $logWriter;
    private $rawMaterialFolder;
    private $remoteMaterialFolder;
    public $message;

    function __construct($logger = null) {
        if($logger == null){
            if(!is_dir("log")){
                if (!mkdir("log", 0777, true)) {
                    die('Failed to create log directories...');
                }
            }
            $this->logWriter = fopen("log/SendMaterialToPumping".date('Y-m-d').".log","a");
        }
        else{
            $this->logWriter = $logger;
        }
        
        $this->mydb=new MyDB(true);
        $this->rawMaterialFolder = Config::GET_MATERIAL_FOLDER();
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


    public function uploadByMaterialId($mid){
        $sftp = new PutToWatchFolder();
        $mid = $_POST["素材識別碼"];
        //取的素材原始檔名
        $sql = "select 素材原始檔名 from  素材  where 素材識別碼 = ?";
        $data = $this->mydb->getResultArray($sql,'i',$mid);

        $fliename = "";
        if($data[0] != null){
            $mname = $data[0]["素材原始檔名"];
            $fliename =$mid."_".$mname;
        }


        $tmp = explode(".",$fliename);
        $mtype = end($tmp);
        $rawFileName = $mid.".".$mtype;
        $remoteFile = $this->remoteMaterialFolder."/".$fliename;
        if(checkLocalMaterial($this->rawMaterialFolder.$rawFileName)){
            if($sftp->uploadedMaterial($this->rawMaterialFolder.$rawFileName, $remoteFile)){
                $nameParse = explode('_',$fliename);
                $material_id = array_shift($nameParse);
                $sql = "
                INSERT INTO barker_material_import_result (material_id,file_name) VALUES (?,?)	
                ON DUPLICATE KEY
                UPDATE import_time=now(),import_result=0,message='已上傳，等待barker系統回報',last_updated_time=now()"
                ;
                $this->mydb->execute($sql,'is',$material_id,$fliename);
                $this->mydb->close();
                $this->message ="上傳到端點barker成功";
                return true;
                
            }else{
                $this->message ="上傳到端點barker失敗";
                return false;
            }
            
        } 
        else{
            $this->mydb->close();
            $this->message ="本地檔案不存在";
            return false;
        }

    }

    public function checkLocalMaterial($filepath){
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
                return false;
            }
            return false;
        }
    }
    
}



?>