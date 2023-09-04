<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
/***

 */
class MaterialRepository
{
    private $mydb = null;
    function __construct($db=null){
        $this->mydb = $db==null?new MyDB(true):$db;
    }
    function __destruct() {
    }
    
    public  function getMaterialInfo($MaterialId) {
        $sql = "SELECT * FROM `素材` WHERE `素材識別碼`=? ";
        $result = array_values($this->mydb->getResultArray($sql,"i",$MaterialId));
        return $result[0];
    }

 
}

?>