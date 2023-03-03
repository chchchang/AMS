<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
//print_r(GetPlayListInRange::getPlayListInRange());
/***
 * 將playlist資料轉化為cue表型態
 * [channel_id,data,hour,playlist]
 * [playlist_id,transaction_id]
 * [transaction_id,channel_id,date,hour0~hour24]
 */
class PlaylistCueConverter
{
    private $mydb = null;
    function __construct($db=null){
        $this->mydb = $db==null?new MyDB(true):$db;
    }
    function __destruct() {
    }
    
    public  function playlistToCue($selector) {
        
        $sql = "SELECT `playlist_id`,COUNT(`transaction_id`)as C FROM `barker_playlist_record` WHERE `transaction_id`=? GROUP BY `playlist_id`";
        $playlistRecords = $this->mydb->getResultArray($sql,"i",$selector["transaction_id"]);
        $playlistIds = array_column($playlistRecords,"playlist_id");
        $playlistCountMap = array_combine($playlistIds,array_column($playlistRecords,"C"));
        $playlistIdsCount = count($playlistIds);
        if($playlistIdsCount == 0)
            return [];

        $sql = "SELECT channel_id,date,hour,playlist_id FROM `barker_playlist_schedule` WHERE ";
        $subsql = array("`playlist_id` in (".str_repeat("?,",$playlistIdsCount-1)."?)");
        $types = str_repeat("i",$playlistIdsCount);
        $paras = [];
        array_push($paras,...$playlistIds);
        if(isset($selector["startDate"]) && isset($selector["endDate"])){
            $subsql[]="date between ? and ?";
            $types.="ss";
            array_push($paras,$selector["startDate"],$selector["endDate"]);
        }
        $sql.=implode(" and ",$subsql);
        $playlistSch = $this->mydb->getResultArray($sql,$types,...$paras);
        $statistic = [];//[channel_id,channel_id,date,hour0~24]
        foreach($playlistSch as $sch){
            extract($sch);//channel_id,date,hour,playlist_id
            if(!array_key_exists($channel_id,$statistic))
                $statistic[$channel_id] = [];
            if(!array_key_exists($date,$statistic[$channel_id]))
                $statistic[$channel_id][$date] = [];
            if(!array_key_exists($hour,$statistic[$channel_id][$date]))
                $statistic[$channel_id][$date][$hour] = 0;
            $statistic[$channel_id][$date][$hour] += $playlistCountMap[$playlist_id];
        }
        $cue = [];//[channel_id,channel_id,date,hour0~24]
        foreach($statistic as $c=>$chrecord){
            $begin = date_create($selector["startDate"]);
            $end = date_create($selector["endDate"]);
            date_add($end, date_interval_create_from_date_string('1 days'));
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);

            foreach ($period as $dt) {
                $d = $dt->format("Y-m-d");
                $daterecord = [];
                if(array_key_exists($d,$chrecord)){
                    $daterecord = $chrecord[$d];
                }
                $temp = ["transaction_id"=>$selector["transaction_id"],
                    "channel_id"=>$c,
                    "date"=>$d
                ];
                for($i=0;$i<24;$i++){
                    $h = str_pad($i,2,"0",STR_PAD_LEFT);
                    if(array_key_exists($h,$daterecord)){
                        $temp["hour".$i]=$daterecord[$h];    
                    }
                    else
                    $temp["hour".$i]=0;
                }
                $cue[]=$temp;
            }
        }
        return $cue;
    }
}

?>