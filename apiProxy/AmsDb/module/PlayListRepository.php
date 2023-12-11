<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
require_once dirname(__FILE__)."/TransactionRepository.php";
$test = new PlayListRepository();
//print_r("test");
//print_r($test->genPlaylistRecord(158,true));
//$test->begin_transaction();
//print_r($test->fixPlaylistSeconds(157));
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
        //transaction_id=-1為排播用的標籤，需跳過
        $sql = "SELECT distinct transaction_id FROM `barker_playlist_template` WHERE `playlist_id`=? AND transaction_id != -1 ";
        $tids = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $overlapDateStart = "";
        $overlapDateEnd = "";
        $overlapHour = null;
        $overlapCh = null;
        foreach($tids as $tidrow){
            $tinfo = $this->getTransactionInfo($tidrow["transaction_id"]);
            if(!$tinfo)
                continue;
            $overlapDateStart = $overlapDateStart===""?$tinfo["廣告期間開始時間"]:max($overlapDateStart,$tinfo["廣告期間開始時間"]);
            $overlapDateEnd = $overlapDateEnd===""?$tinfo["廣告期間結束時間"]:min($overlapDateEnd,$tinfo["廣告期間結束時間"]);
            $hours =  explode(",",$tinfo["廣告可被播出小時時段"]);
            $overlapHour =$overlapHour===null?$hours:array_intersect($hours,$overlapHour);
            $overlapCh = $overlapCh===null?$tinfo["channelId"]:array_intersect($overlapCh,$tinfo["channelId"]);
        }
        $overlapHour = $this->fixLeadingZero($overlapHour);

        //若有設定要更新資料，update db
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
            "overlapChannelId"=>$overlapCh,
        ];
    }
    
    /***
     依照playlist template產生playlist record
     */
    public function genPlaylistRecord($playlist_id,$triggerUpdate=false){
        //transaction_id=-1為排播用的標籤，需跳過
        $sql = "SELECT * FROM `barker_playlist_template` WHERE `playlist_id`=? AND transaction_id != -1 order by offset";
        $records = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $secondsCount = 0;
        $playlistRecord = array();
        $offset = 0;
        $i=0;
        $n = count($records);
        $deleteCount = 0;
        while($secondsCount<3600 && $deleteCount<$n){
            $i %= $n;
            //repeat_times==-1 =>重複播放次數已達標可跳過
            if($records[$i++]["repeat_times"]==-1)
                continue;
            $tid = $records[$i]["transaction_id"];
            if(!isset($this->transactionSecondsHash[$tid])){
                $minfo = $this->TransactionRepository->getTransactionMaterialInfo($tid);
                $this->transactionSecondsHash[$tid]=array_pop($minfo)["影片素材秒數"];
            }
            $endSeconds=$secondsCount+$this->transactionSecondsHash[$tid];
            array_push($playlistRecord,array(
                "playlist_id"=>$records[$i]["playlist_id"],
                "transaction_id"=>$records[$i]["transaction_id"],
                "offset"=>$offset++,
                "start_seconds"=>$secondsCount,
                "end_seconds"=>$endSeconds,
            ));
            $secondsCount=$endSeconds;
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
            if(!$this->setPlaylistRecord($playlist_id,$playlistRecord))
                return false;
        }
        return $playlistRecord;
    }

    /***
    重新計算playlistRecord的開始秒數，並補滿到3600秒(超過秒數的影片將被移除)
     */
    public function fixPlaylistSeconds($playlist_id,$triggerUpdate=true){
        //修正playlist_records
        $sql = "SELECT * FROM `barker_playlist_record` WHERE `playlist_id`=? order by offset";
        $records = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $secondsCount = 0;
        $playlistRecord = array();
        $offset = 0;
        $i=0;
        $n = count($records);
        while($n!=0 && $secondsCount<3600){
            $i %= $n;
            $tid = $records[$i]["transaction_id"];
            if(!isset($this->transactionSecondsHash[$tid])){
                $minfo = $this->TransactionRepository->getTransactionMaterialInfo($tid);
                $this->transactionSecondsHash[$tid]=array_pop($minfo)["影片素材秒數"];
            }
            $endSeconds = $secondsCount+$this->transactionSecondsHash[$tid];
            array_push($playlistRecord,array(
                "playlist_id"=>$records[$i]["playlist_id"],
                "transaction_id"=>$records[$i]["transaction_id"],
                "offset"=>$offset++,
                "start_seconds"=>$secondsCount,
                "end_seconds"=>$endSeconds
            ));
            $secondsCount=$endSeconds;
            $i++;
        }
        if($triggerUpdate){
            if(!$this->setPlaylistRecord($playlist_id,$playlistRecord)){
                return false;   
            }
        }
        //修正playlist_template
        $sql = "SELECT * FROM `barker_playlist_template` WHERE `playlist_id`=? order by offset";
        $playlistTemplate = array_values($this->mydb->getResultArray($sql,"i",$playlist_id));
        $secondsCount = 0;
        $offset = 0;
        foreach($playlistTemplate as $i=>$template){
            //廣告單號為-1為標記點，不須處理
            if($template["transaction_id"] == -1)
                continue;
            
            $tid = $template["transaction_id"];
            if(!isset($this->transactionSecondsHash[$tid])){
                $minfo = $this->TransactionRepository->getTransactionMaterialInfo($tid);
                $this->transactionSecondsHash[$tid]=array_pop($minfo)["影片素材秒數"];
            }
            $playlistTemplate[$i]["start_seconds"] = $secondsCount;
            $playlistTemplate[$i]["end_seconds"] = $secondsCount+$this->transactionSecondsHash[$tid];
            $playlistTemplate[$i]["offset"] = $offset++;
            $secondsCount=$playlistTemplate[$i]["end_seconds"];
        }      
        
        if($triggerUpdate){
            if(!$this->setPlaylistTemplate($playlist_id,$playlistTemplate)){
                return false;   
            }
        }
        return ["template"=>$playlistTemplate,"records"=>$playlistRecord];
    }
    

    private function getTransactionInfo($tid){
        if(!isset($this->transactionHash[$tid])){
            $this->transactionHash[$tid]=$this->TransactionRepository->getTransactionBasicInfo($tid);
            if($this->transactionHash[$tid] != null){
                $channels= $this->TransactionRepository->getTransactionChannelInfo($tid);
                $this->transactionHash[$tid]["channelId"] = $channels;
            }
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

    public function replacePlaylistScheduleByPlaylistId($oldPalylistId,$newPlaylistId,$commitMessage = null){
        $sql = "SELECT * FROM barker_playlist_schedule WHERE playlist_id = ?";
        $affectedRows = $this->mydb->getResultArray($sql,"i",$oldPalylistId);
		if(!$affectedRows){
			throw new RuntimeException("查詢播表歷史紀錄失敗");
		}

        $sql = "UPDATE barker_playlist_schedule SET playlist_id = ? WHERE playlist_id = ?";
        $result = $this->mydb->execute($sql,"ii",$newPlaylistId,$oldPalylistId);
        if(!$result){
			throw new RuntimeException("更新播表排程失敗");
		}
        
        forEach($affectedRows as $id => $row){
            $affectedRows[$id]["playlist_id"]  = $newPlaylistId;
        }
        try{
            $this->setPlaylistScheduleHistory($affectedRows,$commitMessage);
        }
        catch(Exception $e){
            throw $e;
        }
        return true;
    }

    /**
     * 設定playlistSchedule變動紀錄
     * @param array $historys[
     *   array [channel_id,playlist_id,date,hour]
     * ]
     * @param string $commitMessage
     * @throws RuntimeException
     * @return boolean
     */
    public function setPlaylistScheduleHistory($historys,$commitMessage = null){
        $sql = "INSERT INTO barker_playlist_schedule_history (`channel_id`, `date`, `hour`, `playlist_id`, `message`) VALUES ";
        $valuesStringArray = [];
        $valuesStringTempalte = "(?,?,?,?,?)";
        $typeStringTemplate = "issis";
        $typeString = "";
        $columValues = [];
        foreach($historys as $row){
            $valuesStringArray[]=$valuesStringTempalte;
            $typeString .= $typeStringTemplate;
            $tmp = [
                "channel_id" => null,
                "date" => null, 
                "hour" => null, 
                "playlist_id" => null
            ];
            $value = array_merge($tmp,$row);
            if($value["channel_id"]==null || $value["date"]==null || $value["hour"]==null || $value["playlist_id"]==null)
                throw new Exception("必要參數未指定");
            array_push($columValues, $value["channel_id"], $value["date"], $value["hour"], $value["playlist_id"], $commitMessage);
        }
        $sql.= implode(",",$valuesStringArray);
        $result = $this->mydb->execute($sql,$typeString,...$columValues);
		if(!$result){
			throw new Exception("新增播表排程紀錄失敗");
		}
        return true;
    }

    /**
     * 查詢Playlist歷史紀錄
     * @param array $searchOpt[channel_id, date, hour, playlist_id, message]
     * @throws RuntimeException
     * @return array $historys[
     *  array [id,channel_id,playlist_id,date,hour,message,	created_time]
     * ]
     */
    public function getPlaylistScheduleHistory($searchOpt){
        $defaultOpt = [
            "channel_id" => null, 
            "date" => null, 
            "hour" => null, 
            //"playlist_id" => null, 
            //"message" => null
        ];
        $opt = array_merge($defaultOpt, $searchOpt);
        if($opt["channel_id"]==null && $opt["date"]==null && $opt["hour"]==null){
            throw new RuntimeException("請指定要查詢的頻道/日期/時段");
        }

        $sql = "SELECT * FROM barker_playlist_schedule_history WHERE channel_id = ? AND date = ? AND hour = ?";
        $result = $this->mydb->getResultArray($sql,"iss",$opt['channel_id'],$opt['date'],$opt['hour']);
		if(!$result){
			throw new RuntimeException("查詢播表歷史紀錄失敗");
		}
        return $result;
    }

    /**
	*設定playlist record，會先刪除現有的資料再重新匯入
	**/
	public function setPlaylistRecord($playlist_id,$records){
		//先刪除現有playlist
		$sql = "delete from barker_playlist_record WHERE playlist_id =? ";
		$result = $this->mydb->execute($sql,"i",$playlist_id);
		if(!$result){
			return false;
		}
		//新增playlist record
		$valuesTemplate = "(?,?,?,?,?)";
		$valuesStringArray = array();
		$sql = "insert into barker_playlist_record (playlist_id,transaction_id,offset,start_seconds,end_seconds) VALUES ";
		$typeStirngTemplate="iiiii";
		$parameter = array();
		$typeStirng="";
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
        $offset = 0;
		foreach($records as $id=>$record){
            if($record == null)
                continue;
			$valuesStringArray[]=$valuesTemplate;
			$typeStirng.=$typeStirngTemplate;
			$parameter[]=$playlist_id;
			$parameter[]=$record["transaction_id"];
			$parameter[]=$offset++;
			$parameter[]=$record["start_seconds"];
            $parameter[]=$record["end_seconds"];
		}
		$sql.= implode(",",$valuesStringArray);
		$result=call_user_func_array(array($this->mydb,"execute"),$parameter);
		if(!$result){
			return false;
		}
        return true;
	}
	
	/**
	*設定playlist template，會先刪除現有的資料再重新匯入
	**/
	public function setPlaylistTemplate($playlist_id,$template){
		//先刪除現有playlist
		$sql = "delete from barker_playlist_template WHERE playlist_id =? ";
		$result = $this->mydb->execute($sql,"i",$playlist_id);
		if(!$result){
			return false;
		}
		//新增playlist template
		$valuesTemplate = "(?,?,?,?,?,?,?)";
		$valuesStringArray = array();
		$sql = "insert into barker_playlist_template (playlist_id,transaction_id,offset,repeat_times,start_seconds,end_seconds,tag) VALUES ";
		$typeStirngTemplate="iiiiiis";
		$parameter = array();
		$typeStirng="";
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
        $offset = 0;
		foreach($template as $id=>$record){
            if($record === null)
                continue;
			$valuesStringArray[]=$valuesTemplate;
			$typeStirng.=$typeStirngTemplate;
			$parameter[]=$playlist_id;
			$parameter[]=$record["transaction_id"];
			$parameter[]=$offset++;
			$parameter[]=$record["repeat_times"];
            $parameter[]=$record["start_seconds"];
            $parameter[]=$record["end_seconds"];
            $parameter[]=isset($record["tag"])?$record["tag"]:null;
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
	*取得playlist資訊
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
    public function getPlaylistRecord($searchPara=[]){
        $sql="select * from barker_playlist_record";
        $where = [];
        $typeString ="";
        $parameter=array();
        $orderBy = "order by playlist_id,offset";

        if(isset($searchPara["playlist_id"])){
            array_push($where,"playlist_id =?");
            $typeString .="i";
            array_push($parameter,$searchPara["playlist_id"]);
        }
        if(isset($searchPara["transaction_id"])){
            array_push($where,"transaction_id =?");
            $typeString .="i";
            array_push($parameter,$searchPara["transaction_id"]);
        }

        $sql =  $sql." WHERE ".implode(" AND ",$where)." ".$orderBy;
		$result = $this->mydb->getResultArray($sql,$typeString,...$parameter);
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
        if(!$playlistInfo["record"] =$this->getPlaylistRecord(["playlist_id"=>$playlistId])){
            return false;
        }
        return $playlistInfo;
    }

    public function setPlaylistSchedule($records,$commitMessage=null){
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
			throw new RuntimeException("新增播表失敗");
		}

        try{
            $this->setPlaylistScheduleHistory($records,$commitMessage);
        }catch(Exception $e){
            throw $e;
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
        $chlist=isset($searchTerm["channel_id"])?is_array($searchTerm["channel_id"])?$searchTerm["channel_id"]:[$searchTerm["channel_id"]]:[];
        $datelist=isset($searchTerm["date"])?is_array($searchTerm["date"])?$searchTerm["date"]:[$searchTerm["date"]]:[];
        $hourlist=isset($searchTerm["hour"])?is_array($searchTerm["hour"])?$searchTerm["hour"]:[$searchTerm["hour"]]:[];
        $chNum = count($chlist);
        $dateNum = count($datelist);
        $hourNum = count($hourlist);
        if($dateNum>0){
            $where[]="date in (".str_repeat("?,",$dateNum-1)."?".")";
            $typeString.=str_repeat("s",$dateNum);
            array_push($parameter,...$datelist);
        }
        if($chNum>0){
            $where[]="channel_id in (".str_repeat("?,",$chNum-1)."?".")";
            $typeString.=str_repeat("i",$chNum);
            array_push($parameter,...$chlist);
        }
        if($hourNum>0){
            $where[]="hour in (".str_repeat("?,",$hourNum-1)."?".")";
            $typeString.=str_repeat("s",$hourNum);
            array_push($parameter,...$hourlist);
        }
        if(isset($searchTerm["dateRange"])){
            $where[]="date between ? and ?";
            $typeString.="ss";
            array_push($parameter,...$searchTerm["dateRange"]);
        }

        if(isset($searchTerm["playlist_id"])){
            $where[]="playlist_id=?";
            $typeString.="i";
            $parameter[]=$searchTerm["playlist_id"];
        }

        $sql .= implode(" AND ",$where);
        $result = $this->mydb->getResultArray($sql,$typeString,...$parameter);
        if(!$result){
			return false;
		}
		return $result;
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

    /**
    * 查詢所有有使用特定託播單號的playlist_id
    */
    public function getDistinctPlaylistIdByTransactionId($transactionId){
        $sql="select distinct playlist_id from barker_playlist_template where transaction_id =? ";
		$result = $this->mydb->getResultArray($sql,"i",$transactionId);
		return $result;
    }

    /**
    * 取的最後一筆record結束的秒數
    */
    public function getLastRecordEndSeconds($playlist_id){
        $sql="select MAX(end_seconds) as max from barker_playlist_record where playlist_id =? ";
		$result = $this->mydb->getResultArray($sql,"i",$playlist_id);
		if(!$result){
			return false;
		}
		return $result[0]["max"];
    }

    /**
    * 取的最後一筆template開始的秒數
    */
    public function getLastTemplateStartAndEndSeconds($playlist_id){
        $sql="select MAX(start_seconds) as smax,MAX(end_seconds) as emax from barker_playlist_template where playlist_id =? ";
		$result = $this->mydb->getResultArray($sql,"i",$playlist_id);
		if(!$result){
			return false;
		}
		return ["start_seconds"=>$result[0]["smax"],"end_seconds"=>$result[0]["emax"]];
    }

    public function markAsNoOverlappingPeroid($playlist_id) {
        $sql = "update barker_playlist set overlap_hours='' WHERE playlist_id =?";
        $result = $this->mydb->execute($sql,"i",$playlist_id);
        if(!$result){
			throw new RuntimeException("新增播表失敗");
		}
        return true;
    }


}

?>