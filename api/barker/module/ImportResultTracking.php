<?php
/**20221018 
 * 定期檢查是否有送出到端點barekr pumping server但一直沒有取得回覆的影片/播表
 * 若有將嘗試主動呼叫端點API取的結果
 * 若無法取得結果獲取的結果失敗將發信告警
 * */

require_once dirname(__FILE__).'/../../../tool/MyDB.php';
require_once dirname(__FILE__).'/../../../tool/MyMailer.php';
require_once dirname(__FILE__).'/../../../tool/phpExtendFunction.php';
//require_once '../../tool/MyDB.php';//dev
require_once dirname(__FILE__).'/../../../Config.php';
//require_once '../../Config.php';//dev
require_once dirname(__FILE__).'/BarkerConfig.php';


/*$exect = new putToWatchFolder();
$exect->hadle();*/

class ImportResultTracking{
    private $mydb;
    private $logWriter;
    public $message;

    function __construct() {
        $this->mydb=new MyDB();
        //$this->logWriter = fopen("log/putToWatcherFolder".date('Y-m-d').".log","a");
        $this->message ="";
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

    public function handle(){
        //處裡播表
        $feedBack = array();
        $unprocessedPlayList = $this->findUnprocessedPlayList();
        foreach($unprocessedPlayList as $data){
            //逐一查詢是否有結果
            $checkResult = false;
            if(!$checkResult = $this->checkPlayListResult($data["channel_id"]."\\".$data["file_name"])){
                //未取的結果
                array_push($feedBack,"無法取得派送結果:".$data["channel_id"]."\\".$data["file_name"]." 超過一小時未取得派送結果");
            }
            else{
                //print_r($checkResult);
                if(!$checkResult["import_result"]){
                    //派送失敗
                    array_push($feedBack,"補查派送結果得知匯入失敗:".$data["channel_id"]."\\".$data["file_name"]." ".$data["message"]);
                }
            }
        }
        if( count($feedBack)!=0){
            $this->sendWarningMail("端點barker有播表逾一小時未處裡",implode("\n",$feedBack));
        }
        //處裡素材
        $feedBack = array();
        $unprocessedMaterial = $this->findUnprocessedMaterial();
        foreach($unprocessedMaterial as $data){
            //逐一查詢是否有結果
            $checkResult = false;
            if(!$checkResult = $this->checkMaterialResult($data["file_name"])){
                //未取的結果
                array_push($feedBack,"無法取得派送結果:".$data["file_name"]." 超過一小時未取得派送結果");
            }
            else{
                //print_r($checkResult);
                if(!$checkResult["import_result"]){
                    //派送失敗
                    array_push($feedBack,"補查派送結果得知匯入失敗:".$data["file_name"]." ".$checkResult["message"]);
                }
            }
        }
        if( count($feedBack)!=0){
            $this->sendWarningMail("端點barker有素材逾一小時未處裡",implode("\n",$feedBack));
        }
    }
    /**
     * 查詢超時未取得結果的播表
     */
    public function findUnprocessedPlayList(){
        //捲出未取得派送結果的素材
        $sql = "
        select * from barker_playlist_import_result where import_result IS NULL AND (last_updated_time BETWEEN  DATE_SUB(NOW(), INTERVAL '1' DAY) AND DATE_SUB(NOW(), INTERVAL '1' HOUR) OR created_time BETWEEN  DATE_SUB(NOW(), INTERVAL '1' DAY) AND DATE_SUB(NOW(), INTERVAL '1' HOUR))
        ";
        //$sql = "select * from barker_playlist_import_result where import_time IS NULL ";
        $sqlResult = $this->mydb->getResultArray($sql);
        return $sqlResult;
    }
    /**
     * 查詢超時未取得結果的素材
     */
    public function findUnprocessedMaterial(){
        //捲出未取得派送結果的素材
        $sql = "
        select * from barker_material_import_result where import_result IS NULL AND (last_updated_time BETWEEN  DATE_SUB(NOW(), INTERVAL '1' DAY) AND DATE_SUB(NOW(), INTERVAL '1' HOUR) OR created_time BETWEEN  DATE_SUB(NOW(), INTERVAL '1' DAY) AND DATE_SUB(NOW(), INTERVAL '1' HOUR))
        ";
        //$sql = "select * from barker_material_import_result where import_time IS NULL";
        $sqlResult = $this->mydb->getResultArray($sql);
        return $sqlResult;
    }
    /**
     * 主動查詢素材派送結果
     */
    public function checkMaterialResult($fileName){
        $this->dolog("checking Material:".$fileName);
        $url = "http://172.17.233.28:8080/api/pump/getVideoFileImportResults";
        $bypost = array("file_name"=>$fileName);
        $postvars = http_build_query($bypost);
		$res = PHPExtendFunction::connec_to_Api($url,'POST',$postvars);
        if(!$res["success"]){
            $this->dolog("Material:".$fileName." checked fail....cant connect api");
            return false;
        }
        $res  = json_decode($res["data"],true);

        if($res["returnCode"]!="1"){
            $this->dolog("Material:".$fileName." checked fail.... api returnCoed fail");
            return false;
        }
        
        //檢查MD5
        if($res["import_result"] && !$this->checkSum($res["file_name"],$res["check_sum"])){
            $this->dolog("Material:".$fileName." checked fail.... invalid check_sum");
            $res["import_message"] = "md5值錯誤，檔案異常";
            $res["import_result"] = false;
        };
        //更新派送結果
        $this->dolog("update db for Material:".$fileName);
        $nameParse = explode('_',$res["file_name"]);
		$material_id = array_shift($nameParse);
        $sql = "UPDATE barker_material_import_result SET import_time=?,import_result=?,message=?,last_updated_time=now() WHERE material_id  =? AND file_name =?";
        $this->mydb->execute($sql,"sisis",$res["import_time"],$res["import_result"],$res["import_message"],$material_id,$res["file_name"]);
        return $res;
    }

    /**
     * 主動查詢播表派送結果
     */
    public function checkPlayListResult($filePath){
        $this->dolog("checking PayList:".$filePath);
        $url = "http://172.17.233.28:8080/api/pump/getPlayListImportResults";
        $bypost = array("file_path"=>$filePath);
        $postvars = http_build_query($bypost);
		$res = PHPExtendFunction::connec_to_Api($url,'POST',$postvars);
        if(!$res["success"]){
            $this->dolog("PayList:".$filePath." check fail....cant connect api");
            return false;
        }
        $res  = json_decode($res["data"],true);

        if($res["returnCode"]!="1"){
            $this->dolog("PayList:".$filePath." check fail....api returnCode fail");
            return false;
        }
        $data = $res["importResults"][0];
        $this->dolog("update db for PayList:".$filePath);
        $pathParse = explode('\\',$data["file_path"]);
		$channel_id = $pathParse[0];
		$file_name = $pathParse[1];
        //更新派送結果
        $sql = "UPDATE barker_playlist_import_result SET import_time=?,import_result=?,message=?,last_updated_time=now() WHERE channel_id  =? AND file_name =?";
        $this->mydb->execute($sql,"sisis",$data["import_time"],$data["import_result"],$data["message"],$channel_id,$file_name);
        return $data;
    }
    /**
     * 發出警告信
     */
    public function sendWarningMail($title,$message){      
        echo $title.$message;  
        $mailer = new MyMailer();
        $mailer->sendMail($title,$message);
    }
     
    private function checkSum($file_name,$check_sum){
        if(!isset($check_sum) || $check_sum=="" || $check_sum==null)
            return true;
        $mid = explode("_",$file_name)[0];
        $tmp = explode(".",$file_name);
        $mtype = end($tmp);
        $rawFileName = $mid.".".$mtype;
        $md5_result=md5_file(Config::GET_MATERIAL_FOLDER().$rawFileName);
        if($md5_result!=$check_sum)
            return false;
        return true;
    }
    
}

?>