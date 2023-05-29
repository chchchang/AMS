<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
/**
 * BarkerOrderCueRepository class
 * 
 */
class BarkerOrderCueRepository
{
    private $mydb = null;
    function __construct($db=null){
        $this->mydb = $db==null?new MyDB(true):$db;
    }
    function __destruct() {
    }
    /**
     * getCueInfo function
     * 依照給訂條件回傳cue表資料
     *
     * @param Array $options {
     *      @var String startDate  查詢開始日期 
     *      @var String endDate 查詢結束日期 
     *      @var Int channel_id 查詢頻道id optional
     *      @var Int transaction_id 託播單識別碼 optional
     * }
     * @return void
     */
    public  function getCueInfo($options) {
        $defalutOptions = array(
            "startDate"=>null,//查詢開始日期
            "endDate"=>null,//查詢結束日期
            "channel_id"=>null,//頻道id
            "transaction_id"=>null,//託播單識別碼
        );
        $mergedOptions = array_merge($defalutOptions,$options);

        $sql = "SELECT * FROM barker_order_cue WHERE enable = 1 AND date BETWEEN ? AND ?";
		$types = "ss";
		$paras = array($mergedOptions["startDate"],$mergedOptions["endDate"]);
		
		if($mergedOptions["channel_id"]!=null){
			$sql .=" AND channel_id = ?";
			$types .="i";
			array_push($paras,$mergedOptions["channel_id"]);
		}
		
		if($mergedOptions["transaction_id"]!=null){
			$sql .=" AND transaction_id = ?";
			$types .="i";
			array_push($paras,$mergedOptions["transaction_id"]);
		}
		$sql .= " ORDER BY date,transaction_id";
		$result = $this->mydb->getResultArray($sql,$types,...$paras);
        return $result;
    }
    /**
     * 
     *
     * @param Array $cueData 一array包含多個要輸入DB的資料，格式如下
     * [
     *      @var Array {
     *      -   @var String transaction_id 託播單號
     *      -   @var String date 日期
     *      -   @var Boolean hour0~23 0時段到23時段是否可投放
     *      -   @var Boolean enable 是否啟用
     *      }
     * ]
     * 
     * @return void
     */
    public function insertCue($cueData){
        $valuesTemplate = "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$valuesStringArray = array();
		$typeStirngTemplate="iisiiiiiiiiiiiiiiiiiiiiiiiii";
		$sql = "insert into barker_order_cue (transaction_id,channel_id,date,hour0,hour1,hour2,hour3,hour4,hour5,hour6,hour7,hour8,hour9,hour10,hour11,hour12,hour13,hour14,hour15,hour16,hour17,hour18,hour19,hour20,hour21,hour22,hour23,enable) VALUES ";
		$typeStirng="";
		$parameter=array();
		$parameter[]=&$sql;
		$parameter[]=&$typeStirng;
		foreach($cueData as $i=>$data){
			$valuesStringArray[]=$valuesTemplate;
			$typeStirng.=$typeStirngTemplate;
			$parameter[]=&$cueData[$i]["transaction_id"];
			$parameter[]=&$cueData[$i]["channel_id"];
			$parameter[]=&$cueData[$i]["date"];
			$parameter[]=&$cueData[$i]["hour0"];
			$parameter[]=&$cueData[$i]["hour1"];
			$parameter[]=&$cueData[$i]["hour2"];
			$parameter[]=&$cueData[$i]["hour3"];
			$parameter[]=&$cueData[$i]["hour4"];
			$parameter[]=&$cueData[$i]["hour5"];
			$parameter[]=&$cueData[$i]["hour6"];
			$parameter[]=&$cueData[$i]["hour7"];
			$parameter[]=&$cueData[$i]["hour8"];
			$parameter[]=&$cueData[$i]["hour9"];
			$parameter[]=&$cueData[$i]["hour10"];
			$parameter[]=&$cueData[$i]["hour11"];
			$parameter[]=&$cueData[$i]["hour12"];
			$parameter[]=&$cueData[$i]["hour13"];
			$parameter[]=&$cueData[$i]["hour14"];
			$parameter[]=&$cueData[$i]["hour15"];
			$parameter[]=&$cueData[$i]["hour16"];
			$parameter[]=&$cueData[$i]["hour17"];
			$parameter[]=&$cueData[$i]["hour18"];
			$parameter[]=&$cueData[$i]["hour19"];
			$parameter[]=&$cueData[$i]["hour20"];
			$parameter[]=&$cueData[$i]["hour21"];
			$parameter[]=&$cueData[$i]["hour22"];
			$parameter[]=&$cueData[$i]["hour23"];
			$parameter[]=&$cueData[$i]["enable"];
			
		}
		$sql .= implode(",",$valuesStringArray);
		$sql.="ON DUPLICATE KEY UPDATE hour0=values(hour0),hour1=values(hour1),hour2=values(hour2),hour3=values(hour3),hour4=values(hour4),hour5=values(hour5),hour6=values(hour6),hour7=values(hour7),hour8=values(hour8),hour9=values(hour9),hour10=values(hour10)"
		.",hour11=values(hour11),hour12=values(hour12),hour13=values(hour13),hour14=values(hour14),hour15=values(hour15),hour16=values(hour16),hour17=values(hour17),hour18=values(hour18),hour19=values(hour19),hour20=values(hour20),hour21=values(hour21),hour22=values(hour22),hour23=values(hour23),enable=values(enable),last_update_time=NOW()";
		$result = call_user_func_array(array($this->mydb,"execute"),$parameter);
		return $result;
    }
    /**
     * 依照託播單號瑪取消cue表
     *
     * @param Int $transactionId
     * @return void
     */
    public function disableCueByTransactionId($transactionId){
        $sql = "UPDATE barker_order_cue set enable = 0 ,last_update_time=NOW() WHERE transaction_id = ?";// dev 等待加入搜尋條件
		$types = "i";
		$result = $this->mydb->execute($sql,$types,$transactionId);
		return $result;
    }
}
 

?>