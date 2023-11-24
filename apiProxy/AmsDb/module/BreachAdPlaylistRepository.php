<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
require_once dirname(__FILE__)."/TransactionRepository.php";
//print_r("test");
//print_r($test->genPlaylistRecord(158,true));
//$test->begin_transaction();
//print_r($test->fixPlaylistSeconds(157));
/***
 */
class BreachAdPlaylistRepository
{
    private $mydb = null;
    private $TransactionRepository = null;
    private $transactionHash=[];
    function __construct($db=null){
        $this->mydb = $db==null?new MyDB(true):$db;
        $this->TransactionRepository = new TransactionRepository($this->mydb);
    }
    function __destruct() {
    }
    public function begin_transaction(){
        $this->mydb->begin_transaction();
    }
    public function commit(){
        $this->mydb->commit();
    }
    public function rollback(){
        $this->mydb->rollback();
    }
   
    public function getBreachAdExcelDataBySchedule($channel,$date,$hour = "all"){
        $exelRows = [];
        array_push( $exelRows,
            [
                "播出日期",
                "播出時間",
                "結束時間",
                "片長",
                "影片毫秒",
                "播放類型",
                "節目進時間",
                "節目出時間",	
                "播出檔名",
                "播出影片",	
                "聲音調整",	
                "CG",
                "備註",
            ]
        );
        $palylistSch = $this->getBreachAdPlaylistBySchedule($channel,$date,$hour);
        foreach($palylistSch as $h=>$playlist){
            $playlistHour = $h;
            foreach($playlist as $i => $row){
                $tmp = array_fill(0,13,"");
                if($i == 0){
                    //first rows
                    $tmp[0] = $date;
                    $tmp[1] = $playlistHour.":00:00";
                }
                $tmp[9] = $row[0];
                $tmp[10] = $row[1];
                $tmp[12] = $row[2];
                array_push( $exelRows,$tmp);
            }
            
        }
        return $exelRows;
    }

    public function getBreachAdPlaylistBySchedule($channel,$date,$hour = "all"){
        
        $sql = "SELECT * FROM `barker_playlist_schedule` WHERE `channel_id`=? AND `date`=?";
        $queryString = "is";
        $queryParas = [$channel,$date];
        if($hour !== "all"){
            $sql .= " AND `hour`=?";
            $queryString .= "s";
            array_push($queryParas,$hour);
        }
        $playlistIds = $this->mydb->getResultArray($sql,$queryString,...$queryParas);
        $playlistIds = array_values($playlistIds);
        if(!is_array($playlistIds) || !count($playlistIds)>0){
            return [];
        }
        $res = [];
        foreach($playlistIds as $row){
            $tid = $row["playlist_id"];
            $res[$row["hour"]] = $this->getBreahAdPlaylistById($tid);
        }
        return $res;
    }


    public function getBreahAdPlaylistById($playlist_id){
        $sql = "SELECT * FROM `barker_playlist_template` WHERE `playlist_id`=? order by offset";
        $records = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $playlistRecord = array();
        $BreachCount=0;
        $BreachPattern = '/^(.*?)破口$/';
        $LivePattern = '/^(.*?)Live$/';
        
        foreach($records as $row){
            $playlName="";
            $playVIdeoFile="";
            $tag = "";
            if($row["transaction_id"] == -1){
                if(preg_match($BreachPattern,$row["tag"])){
                    $playlName = "破口";
                    $BreachCount++;
                }
                else if(preg_match($LivePattern,$row["tag"])){
                    $playlName = "live";
                }
                $tag = $row["tag"].$BreachCount;
            }
            else{
                $tinfo = $this->getTransactionInfo($row["transaction_id"]);
                $playlName = $tinfo["託播單名稱"];
                $playVIdeoFile = $tinfo["material"]["素材原始檔名"];
                $tag = $row["transaction_id"];
            }

            array_push($playlistRecord,[$playlName,$playVIdeoFile,$tag]);
            
        }
        return $playlistRecord;
    }
    

    private function getTransactionInfo($tid){
        if(!isset($this->transactionHash[$tid])){
            $this->transactionHash[$tid]=$this->TransactionRepository->getTransactionBasicInfo($tid);
            $materials = $this->TransactionRepository->getTransactionMaterialInfo($tid);
            $this->transactionHash[$tid]["material"] = array_pop($materials);
        }
        return $this->transactionHash[$tid];
    }
    


}

?>