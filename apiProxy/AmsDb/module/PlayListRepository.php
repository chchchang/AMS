<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
require_once dirname(__FILE__)."/TransactionRepository.php";
//$test = new PlayListRepository();
//print_r($test->caculateOverlapPeriod(16));
/***
 */
class PlayListRepository
{
    private $mydb = null;
    private $TransactionRepository = null;
    private $transactionHash=[];
    private $transactionSecondsHash=[];
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

    private function fixLeadingZero($arr){
        return array_map(function($n){
            return str_pad($n,2,"0",STR_PAD_LEFT);
        }
        ,
        $arr
        );
    }
    /*
    依照playlist template計算重複的走期、頻道、時段並可選擇是否更新
     */
    public function caculateOverlapPeriod($playlist_id,$triggerUpdate=false) {
        $sql = "SELECT distinct transaction_id FROM `barker_playlist_template` WHERE `playlist_id`=? ";
        $tids = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $overlapDateStart = "";
        $overlapDateEnd = "";
        $overlapHour = null;
        $overlapCh = null;
        foreach($tids as $tidrow){
            $tinfo = $this->getTransactionInfo($tidrow["transaction_id"]);
            $overlapDateStart = $overlapDateStart==""?$tinfo["廣告期間開始時間"]:max($overlapDateStart,$tinfo["廣告期間開始時間"]);
            $overlapDateEnd = $overlapDateEnd==""?$tinfo["廣告期間結束時間"]:min($overlapDateEnd,$tinfo["廣告期間結束時間"]);
            $hours =  explode(",",$tinfo["廣告可被播出小時時段"]);
            $overlapHour =$overlapHour==null?$hours:array_intersect($hours,$overlapHour);
            $overlapCh = $overlapCh==null?$tinfo["channelId"]:array_intersect($overlapCh,$tinfo["channelId"]);
        }
        $overlapHour = $this->fixLeadingZero($overlapHour);
        if($triggerUpdate){
            $sql = "update barker_playlist set overlap_start_time=?,overlap_end_time=?,overlap_hours=?,overlap_channel_id=? WHERE playlist_id =?";
		    $result = $this->mydb->execute($sql,"ssssi",$overlapDateStart,$overlapDateEnd,implode(",",$overlapHour),implode(",",$overlapCh),$playlist_id);
            if(!$result){
                return false;
            }
        }
        
        return [
            "overlapDateStart"=>$overlapDateStart,
            "overlapDateEnd"=>$overlapDateEnd,
            "overlapHour"=>$overlapHour,
            "overlapChannelId"=>$overlapCh
        ];
    }

    
    /***
     依照playlist template產生playlist record
     */
    public function genPlaylistRecord($playlist_id,$triggerUpdate=false){
        $sql = "SELECT * FROM `barker_playlist_template` WHERE `playlist_id`=? order by offset";
        $records = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $secondsCount = 0;
        $playlistRecord = array();
        $offset = 0;
        $i=0;
        $n = count($records);
        $deleteCount = 0;
        while($secondsCount<3600 && $deleteCount<$n){
            $i %= $n;
            if($records[$i++]["repeat_times"]==-1)
            continue;
            array_push($playlistRecord,array(
                "playlist_id"=>$records[$i]["playlist_id"],
                "transaction_id"=>$records[$i]["transaction_id"],
                "offset"=>$offset++,
                "start_seconds"=>$secondsCount,
            ));
            $tid = $records[$i]["transaction_id"];
            if(!isset($this->transactionSecondsHash[$tid])){
                $minfo = $this->TransactionRepository->getTransactionMaterialInfo($tid);
                $this->transactionSecondsHash[$tid]=array_pop($minfo)["影片素材秒數"];
            }
            $secondsCount+=$this->transactionSecondsHash[$tid];
            if($records[$i++]["repeat_times"]!=0){
                if($records[$i++]["repeat_times"]==1){
                    $records[$i++]["repeat_times"]=-1;
                    $deleteCount++;
                }
                else
                    $records[$i++]["repeat_times"]--;
            }
        }
        
        if($triggerUpdate){
            if(!$this->setPlaylistRecord($playlist_id,$records))
                return false;
        }
        return $playlistRecord;
    }

    /***
    重新計算playlistRecord的開始秒數，並補滿到3600秒(超過秒數的影片將被移除)
     */
    public function fixPlaylistRecordSeconds($playlist_id,$triggerUpdate=false){
        $sql = "SELECT * FROM `barker_playlist_record` WHERE `playlist_id`=? order by offset";
        $records = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $secondsCount = 0;
        $playlistRecord = array();
        $offset = 0;
        $i=0;
        $n = count($records);
        while($secondsCount<3600){
            $i %= $n;
            array_push($playlistRecord,array(
                "playlist_id"=>$records[$i]["playlist_id"],
                "transaction_id"=>$records[$i]["transaction_id"],
                "offset"=>$offset++,
                "start_seconds"=>$secondsCount,
            ));
            $tid = $records[$i]["transaction_id"];
            if(!isset($this->transactionSecondsHash[$tid])){
                $minfo = $this->TransactionRepository->getTransactionMaterialInfo($tid);
                $this->transactionSecondsHash[$tid]=array_pop($minfo)["影片素材秒數"];
            }
            $secondsCount+=$this->transactionSecondsHash[$tid];
            $i++;
        }
        if($triggerUpdate){
            if(!$this->setPlaylistRecord($playlist_id,$playlistRecord)){
                return false;   
            }
        }
        return $playlistRecord;
    }
    

    private function getTransactionInfo($tid){
        if(!isset($this->transactionHash[$tid])){
            $this->transactionHash[$tid]=$this->TransactionRepository->getTransactionBasicInfo($tid);
            $channels= $this->TransactionRepository->getTransactionChannelInfo($tid);
            $this->transactionHash[$tid]["channelId"] = $channels;
        }
        return $this->transactionHash[$tid];
    }
    
    public function insertPlaylist($overlapStartTime,$overlapEndTime,$hours,$cids){
        $sql = "insert into barker_playlist (overlap_start_time,overlap_end_time,overlap_hours,overlap_channel_id) VALUES (?,?,?,?)";
		$result = $this->mydb->execute($sql,"ssss",$overlapStartTime,$overlapEndTime,$hours,$cids);
		if(!$result){
			return false;
		}
        return $this->mydb->insert_id;
    }
    
    public function updatePlaylist($overlapStartTime,$overlapEndTime,$hours,$cids,$playlistId){
        $sql = "update barker_playlist set overlap_start_time=?,overlap_end_time=?,overlap_hours=?,overlap_channel_id=? WHERE playlist_id =?";
		$result = $this->mydb->execute($sql,"ssssi",$overlapStartTime,$overlapEndTime,$hours,$cids,$playlistId);
		if(!$result){
			return false;
		}
        return true;
    }

    /**
	*設定palylist record，會先刪除現有的資料再重新匯入
	**/
	public function setPlaylistRecord($playlist_id,$record){
		//先刪除現有palylist
		$sql = "delete from barker_playlist_record WHERE playlist_id =? ";
		$result = $this->mydb->execute($sql,"i",$playlist_id);
		if(!$result){
			return false;
		}
		//新增playlist record
		$valuesTemplate = "(?,?,?,?)";
		$valuesStringArray = array();
		$sql = "insert into barker_playlist_record (playlist_id,transaction_id,offset,start_seconds) VALUES ";
		$typeStirngTemplate="iiii";
		$parameter = array();
		$typeStirng="";
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
		foreach($record as $id=>$record){
			$valuesStringArray[]=$valuesTemplate;
			$typeStirng.=$typeStirngTemplate;
			$parameter[]=$playlist_id;
			$parameter[]=$record["transaction_id"];
			$parameter[]=$id;
			$parameter[]=$record["start_seconds"];
		}
		$sql.= implode(",",$valuesStringArray);
		$result=call_user_func_array(array($this->mydb,"execute"),$parameter);
		if(!$result){
			return false;
		}
        return true;
	}
	
	/**
	*設定palylist template，會先刪除現有的資料再重新匯入
	**/
	public function setPlaylistTemplate($playlist_id,$template){
		//先刪除現有palylist
		$sql = "delete from barker_playlist_template WHERE playlist_id =? ";
		$result = $this->mydb->execute($sql,"i",$playlist_id);
		if(!$result){
			return false;
		}
		//新增playlist template
		$valuesTemplate = "(?,?,?,?)";
		$valuesStringArray = array();
		$sql = "insert into barker_playlist_template (playlist_id,transaction_id,offset,repeat_times) VALUES ";
		$typeStirngTemplate="iiii";
		$parameter = array();
		$typeStirng="";
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
		foreach($template as $id=>$record){
			$valuesStringArray[]=$valuesTemplate;
			$typeStirng.=$typeStirngTemplate;
			$parameter[]=$playlist_id;
			$parameter[]=$record["transaction_id"];
			$parameter[]=$id;
			$parameter[]=$record["repeat_times"];
		}
		$sql.= implode(",",$valuesStringArray);
		$result=call_user_func_array(array($this->mydb,"execute"),$parameter);
		if(!$result){
			return false;
		}
        return true;
	}
	
	/**
	*確認是否有走期外的播表
	**/
	public function checkIfAnyPlayListNotInclude($data){
		extract($data);
		$sql = "select Count(*) as count from barker_playlist_schedule where playlist_id = ? and (date < ? or date > ? or hour not in (".str_repeat('?,',count($overlapHour) - 1)."?) or channel_id not in (".str_repeat('?,',count($overlapChannelId) - 1)."?))";
		$types = "iss".str_repeat("s",count($overlapHour)-1)."s".str_repeat("s",count($overlapChannelId)-1)."s";
		$result = $this->mydb->getResultArray($sql,$types,$playlist_id,$overlapStartTime,$overlapEndTime,...$overlapHour,...$overlapChannelId);
		if(!$result){
			return false;
		}
		return $result[0]["count"]==0;
	}
	/**
	*取得palylist資訊
	*/
	public function getPlaylistDataByID($playlistId){
		$sql="select * from barker_playlist where playlist_id =?";
		$result = $this->mydb->getResultArray($sql,"i",$playlistId);
		if(!$result){
			return false;
		}
		return $result[0];
	}
    /**
     * 取的playlistTemplate資訊
     */
    public function getPlaylistTemplate($playlistId){
        $sql="select * from barker_playlist_template where playlist_id =? order by offset";
		$result = $this->mydb->getResultArray($sql,"i",$playlistId);
		if(!$result){
			return false;
		}
		return $result;
    }
     /**
     * 取的playlistRecord資訊
     */
    public function getPlaylistRecord($playlistId){
        $sql="select * from barker_playlist_record where playlist_id =? order by offset";
		$result = $this->mydb->getResultArray($sql,"i",$playlistId);
		if(!$result){
			return false;
		}
		return $result;
    }
    /**
     * 取的完整playlist資訊，
     * 包含playlist基本資訊、playlistTemplat、playlistRecord
     */
    public function getFullPlaylistInfo($playlistId){
        $playlistInfo = [];
        if(!$playlistInfo["basic"] =$this->getPlaylistDataByID($playlistId)){
            return false;
        }
        if(!$playlistInfo["template"] =$this->getPlaylistTemplate($playlistId)){
            return false;
        }
        if(!$playlistInfo["record"] =$this->getPlaylistRecord($playlistId)){
            return false;
        }
        return $playlistInfo;
    }
    public function setPlaylistSchedule($records){
        //設定playlist schedule
		$parameter = array();
		$sql = "insert into barker_playlist_schedule (channel_id,date,hour,playlist_id) VALUES ";
		$valuesTemplate = "(?,?,?,?)";
		$typeStirngTemplate="issi";
        $typeStirng = "";
		$valuesStringArray = array();
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
		foreach($records as $id=>$record){
			$valuesStringArray[]=$valuesTemplate;
			$typeStirng.=$typeStirngTemplate;
			$parameter[]=$record["channel_id"];
			$parameter[]=$record["date"];
			$parameter[]=$record["hour"];
			$parameter[]=$record["playlist_id"];			
		}
		$sql.= implode(",",$valuesStringArray);
		$sql.=" ON DUPLICATE KEY UPDATE playlist_id=values(playlist_id),last_update_time=NOW()";
		$result = $this->mydb->execute(...$parameter);
		if(!$result){
			return false;
		}
        return true;
    }
    /** 
     * 回傳滿足條件的playlistShedle
     * $searchTerm=[
     * channel_id=>1, (optional)
     * date=><date>  (optional)
     * ]
     * 
     * */
    public function getPlaylistSechdule($searchTerm){
        $sql = "select * from barker_playlist_schedule where ";
        $where = array();
        $typeString="";
        $parameter=array();
        if(isset($searchTerm["channel_id"])){
            $where[]="channel_id=?";
            $typeString.="i";
            $parameter[]=$searchTerm["channel_id"];
        }
        if(isset($searchTerm["date"])){
            $where[]="date=?";
            $typeString.="s";
            $parameter[]=$searchTerm["date"];
        }

        $sql .= implode(" AND ",$where);
        $result = $this->mydb->getResultArray($sql,$typeString,...$parameter);
        if(!$result){
			return false;
		}
		return $result;
    }
    /**
     * 回傳範圍內的playist schedule資料，
     * selector=[
     *  channel=>[1,2,3...], (optional)
     *  date=>yyyy-mm-dd, (optional)
     *  hour=>[00,01,02...], (optional)
     *  dateRange=>[<startDate>,<endDate>] (optional)
     * ]
     */
    public function getPlayListScheduleInRange($selector) {
        $chlist=isset($selector["channel"])?$selector["channel"]:[];
        $datelist=isset($selector["date"])?$selector["date"]:[];
        $hourlist=isset($selector["hour"])?$selector["hour"]:[];
        $chNum = count($chlist);
        $dateNum = count($datelist);
        $hourNum = count($hourlist);
        if($chNum == 0 && $dateNum==0 && $hourNum==0 &&!isset($selector["dateRange"])&&count($selector["dateRange"])<2){
            return ;
        }
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
            $subsql[]="date between ? and ?";
            $types.="ss";
            array_push($paras,...$selector["dateRange"]);
        }
        $sql.=implode(" and ",$subsql);
        return $this->mydb->getResultArray($sql,$types,...$paras);
    }

    /**
     * 回傳範圍內不重複的playist_id，
     * selector=[
     *  channel=>[1,2,3...],
     *  date=>yyyy-mm-dd,
     *  hour=>[00,01,02...],
     *  dateRange=>[<startDate>,<endDate>]
     * ]
     */
    public function getDistinctPlayListIDInRange($selector) {
        $chlist=isset($selector["channel"])?$selector["channel"]:[];
        $datelist=isset($selector["date"])?$selector["date"]:[];
        $hourlist=isset($selector["hour"])?$selector["hour"]:[];
        $chNum = count($chlist);
        $dateNum = count($datelist);
        $hourNum = count($hourlist);
        if($chNum == 0 && $dateNum==0 && $hourNum==0){
            return ;
        }
        $sql = "select distinct playlist_id from barker_playlist_schedule where ";
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
            $subsql[]="date between ? and ?";
            $types.="ss";
            array_push($paras,...$selector["dateRange"]);
        }

        $sql.=implode(" and ",$subsql);
        $result = [];
        $data = $this->mydb->getResultArray($sql,$types,...$paras);
        foreach($data as $record){
            array_push($result,$record["playlist_id"]);
        }
        return array_values($result);
    }

}

?>