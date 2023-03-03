<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
//print_r(GetPlayListInRange::getPlayListInRange());

class GetPlayListInRange
{
    private $mydb;
    function __construct($db=null){
        $this->mydb = $db==null?new MyDB(true):$db;
    }
    function __destruct() {
    }
    
    public static function getPlayListInRange($selector,$db=null) {
        $chlist=isset($selector["channel"])?$selector["channel"]:[];
        $datelist=isset($selector["date"])?$selector["date"]:[];
        $hourlist=isset($selector["hour"])?$selector["hour"]:[];
        $chNum = count($chlist);
        $dateNum = count($datelist);
        $hourNum = count($hourlist);
        if($chNum == 0 && $dateNum==0 && $hourNum==0){
            return ;
        }
        if($db==null)
            $db=new MyDB(true);
        $sql = "select * from barker_playlist_schedule where ";
        $subsql = array();
        $types = "";
        $paras = [];
        if($dateNum>0){
            $subsql[]="date in (".str_repeat("?,",$dateNum-1)."?".")";
            $types.=str_repeat("s",$dateNum);
            array_push($paras,...$datelist);
        }
        if($chNum>0){
            $subsql[]="channel_id in (".str_repeat("?,",$chNum-1)."?".")";
            $types.=str_repeat("i",$chNum);
            array_push($paras,...$chlist);
        }
        if($hourNum>0){
            $subsql[]="hour in (".str_repeat("?,",$hourNum-1)."?".")";
            $types.=str_repeat("s",$hourNum);
            array_push($paras,...$hourlist);
        }
        if(isset($selector["dateRange"])){
            $subsql[]="dateRange between ? and ?";
            $types.="ss";
            array_push($paras,...$selector["dateRange"]);
        }
        if(isset($selector["transactionId"])){
            $subsql[]="transaction_id =?";
            $types.="i";
            array_push($paras,$selector["transactionId"]);
        }
        
        $sql.=implode(" and ",$subsql);
        return $db->getResultArray($sql,$types,...$paras);
    }
}

?>