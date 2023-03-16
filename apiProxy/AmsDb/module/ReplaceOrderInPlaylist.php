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
        $playlistSchedule=$this->playListRepository->getPlayListScheduleInRange(["dateRange"=>$dateRange,"channel"=>$channel,"hour"=>$hour]);
		$repalceOrderData = $this->transactionRepository->getTransactionBasicInfo($newTransactionId);
        //檢查走期
        if($repalceOrderData["廣告期間開始時間"]>$dateRange[0]||$repalceOrderData["廣告期間結束時間"]<$dateRange[1]){
            $this->setExecuteMessage(false,"用來取代的託播單走其無法後蓋取代日期範圍");
            return false;
        }
        //檢查頻道是否可涵蓋
        if(!$this->checkIfChannelValid($newTransactionId,$playlistSchedule)){
            $this->setExecuteMessage(false,"用來取代的託播單無法涵蓋所選頻道");
            return false;
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
            foreach($playlistInfo[$pid]["record"] as $j=>$template){
                if($template["transaction_id"]==$originalTransactionId){
                    //有出現要取代的託播單，將這筆資料標記為要拆單
                    if($playlistInfo[$pid]["action"] ==null){
                        $playlistInfo[$pid]["action"] ="split";
                    }

                    if($offset==0&&$interval==0){
                        $playlistInfo[$pid]["record"][$j]["transaction_id"]=$newTransactionId;
                    }
                    else{
                        if($j>=$offset && ($j-$offset)%($interval+1)==0){
                            $playlistInfo[$pid]["record"][$j]["transaction_id"]=$newTransactionId;
                        }
                    }
                }
                //為了有設定開始取代位置或區間取代的條件，增加repeat_times資料給播表樣版使用
                $playlistInfo[$pid]["record"][$j]["repeat_times"]=1;
            }
            if($offset==0&&$interval==0){
                //如果沒有設定開始取代位置和開始取代區間，直接取代排播樣版中的託播單即可
                foreach($playlistInfo[$pid]["template"] as $j=>$template){
                    if($template["transaction_id"]==$originalTransactionId){
                        $playlistInfo[$pid]["template"][$j]["transaction_id"]=$newTransactionId;
                    }
                }
            }
            else{
                //有設定開始取代位置和開始取代區間，將用實際播表取代排播樣版
                $playlistInfo[$pid]["template"]=$playlistInfo[$pid]["record"];
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
            //先新增palylist
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
            //更新重疊走期/頻道/時段資訊
            $overlap=$this->playListRepository->caculateOverlapPeriod($newPlayListId,true);
            if(!$overlap){
                $this->setExecuteMessage(false,"更新重複走期/頻道/時段資訊失敗");
                return false;
            }
            //修正playlistRecord秒數
            if(!$this->playListRepository->fixPlaylistRecordSeconds($newPlayListId,true)){
                $this->setExecuteMessage(false,"計算實際播表秒數失敗");
                return false;
            }
            $playlistIdMap[$pInfo["basic"]["playlist_id"]]=$newPlayListId;
        }
        $playlistSchedule = $this->replacePalylistIdInSchedule($playlistIdMap,$playlistSchedule);
        if(!$this->playListRepository->setPlaylistSchedule($playlistSchedule)){
            $this->setExecuteMessage(false,"更新播表排程失敗");
            return false;
        }
        $this->setExecuteMessage(true,"更新播表排程置換成功");
        return true;
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
     * 把palylistSechdule中的palyslist 更新成拆單後的playlist
     */
    private function replacePalylistIdInSchedule($playlistIdMap,$playlistSchedule){
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
        else
            $this->playListRepository->commit();
        //exit(json_encode(array("success"=>false,"message"=>$message),JSON_UNESCAPED_UNICODE));
    }
}

?>