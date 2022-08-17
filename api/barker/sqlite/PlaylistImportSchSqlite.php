<?php
class PointBarkerDB 
{   
    private $db;
    function __construct()
    {
        $this->db = new SQLite3(dirname(__FILE__).'/pointBaker.db');
        $sql ="
            CREATE TABLE IF NOT EXISTS  playlistImportSch
            (
            id         TEXT  NOT NULL PRIMARY KEY,
            channel_id INT   NOT NULL,
            date       TEXT  NOT NULL,
            hour       TEXT  NOT NULL
            );";

        $ret = $this->db->exec($sql);
        /*if(!$ret){
            echo $this->db->lastErrorMsg();
        } else {
            echo "Yes, Table created successfully<br/>\n";
        }*/
        //$this->housKeeping();
    }

    function __destruct() {
        $this->db->close();
    }

    public function insertSch($channel_id,$date,$hour){
        $id = $this->getId($channel_id,$date,$hour);
        
        $sql = "  INSERT OR IGNORE INTO playlistImportSch (id,channel_id,date,hour)
                VALUES (:id,:channel_id,:date,:hour);";
        $this->db->exec('BEGIN;');
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->bindValue(':channel_id', $channel_id, SQLITE3_INTEGER);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':hour', $hour, SQLITE3_TEXT);
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

        $sql = "  INSERT OR IGNORE INTO playlistImportSch (id,channel_id,date,hour)
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
        /*$stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->bindValue(':channel_id', $channel_id, SQLITE3_INTEGER);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':hour', $hour, SQLITE3_TEXT);*/
        foreach($parasMap as $id => $paras){
            $stmt->bindValue(":id$id", $id, SQLITE3_TEXT);
            $stmt->bindValue(":channel_id$id", $paras["channel_id"], SQLITE3_INTEGER);
            $stmt->bindValue(":date$id", $paras["date"], SQLITE3_TEXT);
            $stmt->bindValue(":hour$id", $paras["hour"], SQLITE3_TEXT);
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
        $sql = "  SELECT * FROM playlistImportSch WHERE 1 order by date,hour GLOB '[A-Za-z]*' DESC, hour";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();

        $feedback = array();
        while($row = $result->fetchArray()){
            array_push($feedback,$row);
        }
        return $feedback;
    }

    public function getFirstSch(){
        $sql = "  SELECT * FROM playlistImportSch WHERE 1 order by date,hour GLOB '[A-Za-z]*' DESC, hour LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();

        $feedback = array();
        while($row = $result->fetchArray()){
            array_push($feedback,$row);
        }
        return $feedback;
    }

    public function deleteSch($channel_id,$date,$hour){
        $id = $this->getId($channel_id,$date,$hour);
        $sql = "DELETE FROM playlistImportSch WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        if($stmt->execute())
            return true;
        else
            return false;
    }

    public function housKeeping(){
        $sql = "DELETE FROM playlistImportSch WHERE  date<=:date AND hour<:hour ;";
        $stmt = $this->db->prepare($sql);
        $dateHour = explode(date("Y-m-d H")," ");

        $stmt->bindValue(':date',$dateHour[0], SQLITE3_TEXT);
        $stmt->bindValue(':hour',$dateHour[1], SQLITE3_TEXT);
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