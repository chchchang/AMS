<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
require_once dirname(__FILE__)."/PlayListRepository.php";
require_once dirname(__FILE__)."/TransactionRepository.php";
/***
 * 將一定範圍中的託播單取代為新的託播單
 */
/*if(isset($_GET["example"])){
    echo "example\n";
    $var = [
        "channel" => [],
        "hour" => ["00"],
        "original" => 47401,
        "new" => 47402,
        "dateRange"=>["2023-03-13","2023-03-14"]
    ];
    $replacer = new ReplaceOrderInPlaylist();
    $result=$replacer->replaceOrderInPlaylist($var["dateRange"],$var["channel"],$var["hour"],$var["original"],$var["new"]);
    print_r(array("success"=>$result,"message"=>$replacer->getExecuteMessage()));
}*/
class ReplaceOrderInPlaylist
{
    private $playListRepository = null;
    private $transactionRepository = null;
    private $message = "";
    
    function __construct(){
        $this->playListRepository = new PlayListRepository();
        $this->transactionRepository = new TransactionRepository();
    }
    
    function __destruct() {
    }

    public function getExecuteMessage(){
        return $this->message;
    }

    public  function replaceOrderInPlaylist($dateRange,$channel,$hour,$originalTransactionId,$newTransactionId,$offset=0,$interval=0) {
        $playlistSchedule=$this->playListRepository->getPlaylistSechdule(["dateRange"=>$dateRange,"channel_id"=>$channel,"hour"=>$hour]);        
        if(count($playlistSchedule)==0){
            $this->setExecuteMessage(false,"沒有符合條件的播表可取代");
            return false;
        }
        if($newTransactionId != "" || $newTransactionId != null)
        {
            $repalceOrderData = $this->transactionRepository->getTransactionBasicInfo($newTransactionId);
            if($repalceOrderData != null){
                //檢查走期
                $startDateTime = explode(" ",$repalceOrderData["廣告期間開始時間"]);
                $endDateTime = explode(" ",$repalceOrderData["廣告期間結束時間"]);
                if($startDateTime[0]>$dateRange[0]||$endDateTime[0]<$dateRange[1]){
                    $this->setExecuteMessage(false,"用來取代的託播單走期無法覆蓋取代日期範圍");
                    return false;
                }
                //檢查頻道是否可涵蓋
                if(!$this->checkIfChannelValid($newTransactionId,$playlistSchedule)){
                    $this->setExecuteMessage(false,"用來取代的託播單無法涵蓋所選頻道");
                    return false;
                }    
            }   
        }
       
		$playlistInfo =[];
		foreach($playlistSchedule as $i=>$psch){
            //取的playlist完整資訊
            $pid = $psch["playlist_id"];
            if(isset($playlistInfo[$pid])){
                continue;
            }

            $playlistInfo[$pid]= $this->playListRepository->getFullPlaylistInfo($pid);
            $playlistInfo[$pid]["action"] =null;
            
            //取代託播單資料
            $originalTransactionIdCount=0;
            foreach($playlistInfo[$pid]["record"] as $j=>$template){
                if($template["transaction_id"] == $originalTransactionId){
                    //有出現要取代的託播單，將這筆資料標記為要拆單
                    if($playlistInfo[$pid]["action"] == null){
                        $playlistInfo[$pid]["action"] ="split";
                    }

                    if($offset==0 && $interval==0){
                        $this->replaceOrUnsetTransaction($playlistInfo[$pid]["record"][$j],$newTransactionId);
                    }
                    else{
                        if($originalTransactionIdCount>=$offset && ($originalTransactionIdCount-$offset)%($interval+1)==0){
                            $this->replaceOrUnsetTransaction($playlistInfo[$pid]["record"][$j],$newTransactionId);
                        }
                    }
                    $originalTransactionIdCount++;
                }
                //為了有設定開始取代位置或區間取代的條件，增加repeat_times資料給播表樣版使用
                if($newTransactionId != null && $newTransactionId != "")
                    $playlistInfo[$pid]["record"][$j]["repeat_times"]=1;
            }
            if($offset==0&&$interval==0){
                //如果沒有設定開始取代位置和開始取代區間，直接取代排播樣版中的託播單即可
                foreach($playlistInfo[$pid]["template"] as $j=>$template){
                    if($template["transaction_id"]==$originalTransactionId){
                        $this->replaceOrUnsetTransaction($playlistInfo[$pid]["template"][$j],$newTransactionId);
                    }
                }
            }
            else{
                //有設定開始取代位置和開始取代區間，將用實際播表取代排播樣版
                //$playlistInfo[$pid]["template"] = $playlistInfo[$pid]["record"];
                $playlistInfo[$pid]["template"] = $this->overWriteTemplatesWithRecrods($playlistInfo[$pid]["template"], $playlistInfo[$pid]["record"]);
            }
		}
        //開始取代並更新資料庫
        $playlistIdMap = [];
        $this->playListRepository->begin_transaction();
        foreach($playlistInfo as $i=>$pInfo){
            if($pInfo["action"]==null){
                $playlistIdMap[$pInfo["basic"]["playlist_id"]]=$pInfo["basic"]["playlist_id"];
                continue;
            }
                
            //拆單playlist資料
            //先新增playlist
            $newPlayListId = $this->playListRepository->insertPlaylist(
                $pInfo["basic"]["overlap_start_time"],
                $pInfo["basic"]["overlap_end_time"],
                $pInfo["basic"]["overlap_hours"],
                $pInfo["basic"]["overlap_channel_id"]
            );
            if(!$newPlayListId){
                $this->setExecuteMessage(false,"新增播表資本資訊失敗");
                return false;
            }
            //新增playlistTemplate
            if(!$this->playListRepository->setPlaylistTemplate($newPlayListId,$pInfo["template"])){
                $this->setExecuteMessage(false,"設定播表樣板失敗");
                return false;
            }
            //新增playlistRecord
            if(!$this->playListRepository->setPlaylistRecord($newPlayListId,$pInfo["record"])){
                $this->setExecuteMessage(false,"設定實際播表失敗");
                return false;
            }
            //修正playlistRecord秒數
            if(!$this->playListRepository->fixPlaylistSeconds($newPlayListId,true)){
                $this->setExecuteMessage(false,"計算實際播表秒數失敗");
                return false;
            }
            //更新重疊走期/頻道/時段資訊
            $overlap=$this->playListRepository->caculateOverlapPeriod($newPlayListId,true);
            if(!$overlap){
                $this->setExecuteMessage(false,"更新重複走期/頻道/時段資訊失敗");
                return false;
            }

            $playlistIdMap[$pInfo["basic"]["playlist_id"]]=$newPlayListId;
        }
        $playlistSchedule = $this->replaceplaylistIdInSchedule($playlistIdMap,$playlistSchedule);
        try{
            $this->playListRepository->setPlaylistSchedule($playlistSchedule,isset($_POST["commitMessage"])?$_POST["commitMessage"]:"廣告抽單");
        }catch(Exception $e){
            $this->setExecuteMessage(false,"更新播表排程失敗");
            return false;
        }

        $this->setExecuteMessage(true,"更新播表排程置換成功");
        return true;
    }

    private function replaceOrUnsetTransaction(&$origin,$newTransactionId){
        if($newTransactionId == null || $newTransactionId == ""){
            $origin = null;
        }
        else{
            $origin["transaction_id"] = $newTransactionId;
        }
    }

    private function overWriteTemplatesWithRecrods($templates,$records){
        $newTemplate = [];
        $oldTemlpateSize = count($templates);
        $oldTemplatePoniter = 0;
        $newTemplatePoniter = 0;
        foreach($records as $record ){
            if($oldTemplatePoniter < $oldTemlpateSize && $templates[$oldTemplatePoniter]["tag"] != NULL){
                array_push($newTemplate,$templates[$oldTemplatePoniter]);
                $newTemplatePoniter++;
                $oldTemplatePoniter++;
            }
            $record["offset"] = $newTemplatePoniter++;
            array_push($newTemplate,$record);
            $oldTemplatePoniter++;
        }
        return $newTemplate;
    }
    /***
     * 檢查要用以取代的託播單是否完整包含要取代的頻道
     */
    private function checkIfChannelValid($replaceOrderId,$playlistSchedule){
        //檢查頻道是否可涵蓋
        $orderChannel = $this->transactionRepository->getTransactionChannelInfo($replaceOrderId);
        $currentChannels = [];
        foreach($playlistSchedule as $i=>$psch){
            $currentChannels[]=$psch["channel_id"];
        }
        $currentChannels = array_unique($currentChannels);
        if(sizeof(array_intersect($orderChannel,$currentChannels))!=sizeof($currentChannels)){
            return false;
        }
        return true;
    }

    /**
     * 把playlistSechdule中的playslist 更新成拆單後的playlist
     */
    private function replaceplaylistIdInSchedule($playlistIdMap,$playlistSchedule){
        foreach($playlistSchedule as $i=>$sch){
            if(isset($playlistIdMap[$sch["playlist_id"]]))
                $playlistSchedule[$i]["playlist_id"] = $playlistIdMap[$sch["playlist_id"]];
        }
        return $playlistSchedule;
    }

    private function setExecuteMessage($success,$message){
        $this->message = $message;
        if(!$success)
            $this->playListRepository->rollback();
        else{
            $this->playListRepository->commit();
        }
        //exit(json_encode(array("success"=>false,"message"=>$message),JSON_UNESCAPED_UNICODE));
    }

    public function fixBarkerPlaylistOverlapPeroid($orderId){
		if(!$plaslistIds = $this->playListRepository->getDistinctPlaylistIdByTransactionId($orderId)){
			return false;
		}
		foreach($plaslistIds as $row){
			//更新barker播表的重疊時間。
			if(!$this->playListRepository->caculateOverlapPeriod($row["playlist_id"],true))
				return false;
		}
		return true;
	}
}

?>