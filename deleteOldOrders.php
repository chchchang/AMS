<?php
require_once dirname(__FILE__).'/tool/MyDB.php';
$my=new MyDB(true);
$FILEPATH = "/opt/lampp/htdocs/AMS/material/uploadedFile/";
//$DEADLINE = "2017-08-01";
$DEADLINE = date('Y-m-d',strtotime('-1 year'));
$LIMIT = "1000";

//print_r($DEADLINE);
deleteOrderData();
deleteMaterialFiles();

function deleteOrderData(){
	global $DEADLINE,$LIMIT;
	$outDatedOrders =selectOutDatedOrders($DEADLINE,$LIMIT);
	//print_r($outDatedOrders);
	if(count($outDatedOrders["託播單識別碼"])>0)
	deleteData($outDatedOrders);

	$DEADLINE_6M = date('Y-m-d',strtotime('-6 months'));
	$outDatedOrders_6M =selectOutDatedOrders($DEADLINE_6M,null);
	deleteMaterial_onlyFile($outDatedOrders_6M);
}

//selecte the oders that must be deleted
function selectOutDatedOrders($DEADLINE,$LIMIT){
	global $my;
	if($LIMIT != null)
		$sql='select 託播單識別碼,託播單CSMS群組識別碼 FROM 託播單 WHERE`廣告期間結束時間` < "'.$DEADLINE.'" LIMIT '.$LIMIT
			;
	else
		$sql='select 託播單識別碼,託播單CSMS群組識別碼 FROM 託播單 WHERE`廣告期間結束時間` < "'.$DEADLINE.'"';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}
	if(!$res=$stmt->get_result()){
		exit($my->error);
	}
	$outDatedOrders = array("託播單識別碼"=>[],"託播單CSMS群組識別碼"=>[]);
	while($row = $res->fetch_assoc()){
		$outDatedOrders["託播單識別碼"][] = $row['託播單識別碼'];
		if($row['託播單CSMS群組識別碼']!=null)
		$outDatedOrders["託播單CSMS群組識別碼"][] = $row['託播單CSMS群組識別碼'];
	}
	return $outDatedOrders;
}

function deleteData($outDatedOrders){
	global $my;
	$deteteOrderListStr = implode(',',$outDatedOrders["託播單識別碼"]);
	$deteteCSMSOrderListStr = implode(',',$outDatedOrders["託播單CSMS群組識別碼"]);
	//delete data
	$sql='DELETE FROM 託播單其他參數 WHERE 託播單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}

	$sql='DELETE FROM 託播單投放版位 WHERE 託播單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}

	$sql='DELETE FROM 託播單CAMPS_ID對照表 WHERE 託播單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}

	$sql='DELETE FROM 託播單素材 WHERE 託播單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}
	if($deteteCSMSOrderListStr!=''){
		$sql='DELETE FROM 託播單CSMS群組 WHERE 託播單CSMS群組識別碼 IN('.$deteteCSMSOrderListStr.')';
		if(!$stmt=$my->prepare($sql)) {
			exit($my->error);
		}
		if(!$stmt->execute()) {
			exit($my->error);
		}
	}

	$sql='DELETE FROM `頻道short EPG banner託播單移出託播單CSMS群組記錄` WHERE 託播單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}

	$sql='DELETE FROM 託播單 WHERE 託播單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}
}

//刪除過期素材實體檔案與素材資料
function deleteMaterialFiles(){
	global $my,$FILEPATH,$DEADLINE,$LIMIT;
	$sql='SELECT 素材.素材識別碼,素材原始檔名 FROM 素材 LEFT JOIN 託播單素材 ON 素材.素材識別碼 = 託播單素材.素材識別碼 WHERE 託播單素材.素材識別碼 IS NULL AND 素材.CREATED_TIME <"'.$DEADLINE.
	'" LIMIT '.$LIMIT
			;
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}
	if(!$res=$stmt->get_result()){
		exit($my->error);
	}
	$deleteMaterils = array();
	$deleteFiles = array();
	while($row = $res->fetch_assoc()){
		$deleteMaterils[] = $row['素材識別碼'];
		$temp = explode('.',$row['素材原始檔名']);
		$fileName = $FILEPATH.$row['素材識別碼'].'.'.end($temp);
		echo $fileName;
		if(@unlink($fileName))
			echo " success\n";
		else 
			echo " fail\n";
		$sql="delete FROM 素材 WHERE 素材識別碼 = ".$row['素材識別碼']
			;
		if(!$stmt=$my->prepare($sql)) {
			exit($my->error);
		}
		if(!$stmt->execute()) {
			exit($my->error);
		}
	}
}

//刪除過期素材實體檔案
function deleteMaterial_onlyFile($outDatedOrders){
	global $my,$FILEPATH;
	$deteteOrderListStr = implode(',',$outDatedOrders["託播單識別碼"]);
	$sql='SELECT distinct 素材.素材識別碼,素材原始檔名 FROM 素材 LEFT JOIN 託播單素材 ON 素材.素材識別碼 = 託播單素材.素材識別碼 WHERE 託播單素材.託播單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}
	if(!$res=$stmt->get_result()){
		exit($my->error);
	}
	$deleteMaterils = array();
	$deleteFiles = array();
	while($row = $res->fetch_assoc()){
		$deleteMaterils[] = $row['素材識別碼'];
		$temp = explode('.',$row['素材原始檔名']);
		$fileName = $FILEPATH.$row['素材識別碼'].'.'.end($temp);
		echo $fileName;
		if(@unlink($fileName))
			echo " success\n";
		else 
			echo " fail\n";
	}
}

echo 'DONE';
?>