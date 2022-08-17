<?php
//require_once dirname(__FILE__).'/../../../tool/MyDB.php';
require_once dirname(__FILE__).'/../../../Config.php';

class PointBarkerDB 
{   
    private $db;
    function __construct()
    {   
        $dbms='mysql';     //数据库类型
        $host=Config::DB_HOST; //数据库主机名
        $dbName=Config::DB_NAME;    //使用的数据库
        $user=Config::DB_USER;      //数据库连接用户名
        $pass=Config::DB_PASSWORD;          //对应的密码
        $dsn="$dbms:host=$host;dbname=$dbName";
        
        
        try {
            $this->db = new PDO($dsn, $user, $pass); //初始化一个PDO对象
        } catch (PDOException $e) {
            die ("Error!: " . $e->getMessage() . "<br/>");
        }
        //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
        //$this->db = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
        //$this->db = new MyDB(true);
    }

    function __destruct() {
        //$this->db->close();
    }

    public function insertSch($channel_id,$date,$hour){
        $id = $this->getId($channel_id,$date,$hour);
        
        $sql = "  INSERT IGNORE INTO playlistImportSch (id,channel_id,date,hour)
                VALUES (:id,:channel_id,:date,:hour);";
        $this->db->exec('BEGIN;');
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->bindValue(':channel_id', $channel_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':hour', $hour, PDO::PARAM_STR);
        if($stmt->execute()){
            $this->db->exec('COMMIT;');
            return true;
        }
        else{
            $this->db->exec('COMMIT;');
            return false;
        }
    }

    public function batchInsertSch($channel_ids,$dates,$hours){

        $sql = "  INSERT IGNORE INTO playlistImportSch (id,channel_id,date,hour)
                VALUES ";
        $parasMap = array();
        $valuesString = array();
        foreach($channel_ids as $channel_id){
            foreach($dates as $date){
                foreach($hours as $hour){
                    $id = $this->getId($channel_id,$date,$hour);
                    array_push($valuesString,"(:id$id ,:channel_id$id ,:date$id ,:hour$id)");
                    $parasMap[$id] = array("channel_id"=>$channel_id,"date"=>$date,"hour"=>$hour);
                }
            }
        }
        
        $sql .= implode(",",$valuesString).";";
        $this->db->exec('BEGIN;');
        $stmt = $this->db->prepare($sql);
        /*$stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->bindValue(':channel_id', $channel_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':hour', $hour, PDO::PARAM_STR);*/
        foreach($parasMap as $id => $paras){
            $stmt->bindValue(":id$id", $id, PDO::PARAM_STR);
            $stmt->bindValue(":channel_id$id", $paras["channel_id"], PDO::PARAM_INT);
            $stmt->bindValue(":date$id", $paras["date"], PDO::PARAM_STR);
            $stmt->bindValue(":hour$id", $paras["hour"], PDO::PARAM_STR);
        }
        if($stmt->execute()){
            $this->db->exec('COMMIT;');
            return true;
        }
        else{
            
            $this->db->exec('COMMIT;');
            return false;
        }
    }

    public function getAllSch(){
        $sql = "SELECT * FROM playlistImportSch WHERE 1 order by date,IF(hour LIKE 'all', 1, 2), hour";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $feedback = array();
        while($row = $stmt->fetch()){
            array_push($feedback,$row);
        }
        return $feedback;
    }

    public function getFirstSch(){
        $sql = "SELECT * FROM playlistImportSch WHERE 1 order by date,IF(hour LIKE 'all', 2, 1), hour LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $feedback = array();
        while($row = $stmt->fetch()){
            array_push($feedback,$row);
        }
        return $feedback;
    }

    public function deleteSch($channel_id,$date,$hour){
        $id = $this->getId($channel_id,$date,$hour);
        $sql = "DELETE FROM playlistImportSch WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        if($stmt->execute())
            return true;
        else
            return false;
    }

    public function housKeeping(){
        $sql = "DELETE FROM playlistImportSch WHERE  date<=:date AND hour<:hour ;";
        $stmt = $this->db->prepare($sql);
        $dateHour = explode(date("Y-m-d H")," ");

        $stmt->bindValue(':date',$dateHour[0], PDO::PARAM_STR);
        $stmt->bindValue(':hour',$dateHour[1], PDO::PARAM_STR);
        if($stmt->execute())
            return true;
        else
            return false;
    }

    private function getId($chid,$date,$hour){
        $day = explode("-",$date);
        $day = $day[2];
        return $chid.$day.$hour;
    }
}
/*$mydb = new PointBarkerDB();
$mydb->deleteSch(2,"2022-08-05","all");
$mydb->insertSch(2,"2022-08-05","22");
$mydb->insertSch(2,"2022-08-05","05");
$mydb->insertSch(2,"2022-08-05","00");
$all = $mydb->getAllSch();
print_r($all);*/
?>