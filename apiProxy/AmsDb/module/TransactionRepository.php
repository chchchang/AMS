<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
/***

 */
class TransactionRepository
{
    private $mydb = null;
    function __construct($db=null){
        $this->mydb = $db==null?new MyDB(true):$db;
    }
    function __destruct() {
    }
    
    public  function getTransactionBasicInfo($transactionId) {
        $sql = "SELECT * FROM `託播單` WHERE `託播單識別碼`=? ";
        $result = array_values($this->mydb->getResultArray($sql,"i",$transactionId));
        return $result[0];
    }

    public  function getTransactionMaterialInfo($transactionId) {
        $sql = "SELECT * FROM  素材  WHERE 素材識別碼 in (
            SELECT 素材識別碼 FROM 託播單素材 WHERE 託播單識別碼 = ?
        ) ORDER by 影片畫質識別碼";
        $result = array_values($this->mydb->getResultArray($sql,"i",$transactionId));
        return $result;
    }

    public  function getTransactionChannelInfo($transactionId) {
        $sql = "SELECT 版位其他參數預設值 as channelId FROM  託播單投放版位 JOIN 版位其他參數 ON 託播單投放版位.版位識別碼 = 版位其他參數.版位識別碼  AND 版位其他參數名稱='channel_id'
        WHERE 託播單識別碼 = ?";
        $result = [];
        $channels = array_values($this->mydb->getResultArray($sql,"i",$transactionId));
        foreach($channels as $ch){
            array_push($result,$ch["channelId"]);
        }
        return $result;
    }
}

?>