<?php
/***
 * 20220801 chia_chi_chang，修改刪除上傳超過一定時間素材的判斷，增加素材有效時間判斷
 * 20220802 chia_chi_chang，增加刪除委刊單機至，建立超過一定時間，且沒有託播單的委刊單將會被刪除
 * 20220803 chia_chi_chang，移除Orbit刪檔機制
 */
require_once dirname(__FILE__).'/Config.php';

$my = new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
$my->query("SET NAMES utf8"); 

$FILEPATH = "/var/www/html/AMS/material/uploadedFile/";
$DEADLINE = date('Y-m-d',strtotime('-1 year'));
//$DEADLINE = date('Y-m-d',strtotime('-180 days'));
$LIMIT = "1000";

print_r($DEADLINE);
deleteMaterialFileByOutDateList("/var/www/html/AMS/outdatedMaterialList.dat");//刪除上傳一定時間的素材實體檔案，不會更動素材資料
deleteOrderData(); //刪除託播單資料
deleteOrderListData(); //刪除委刊單資料
//deleteMaterialFiles(); //刪除素材資料，會嘗試刪除orbit檔案


$my->close();

function deleteOrderData(){
	global $DEADLINE,$LIMIT;
	$outDatedOrders =selectOutDatedOrders($DEADLINE,$LIMIT);
	//print_r($outDatedOrders);
	if(count($outDatedOrders["託播單識別碼"])>0)
	deleteData($outDatedOrders);

	//$DEADLINE_6M = date('Y-m-d',strtotime('-6 months'));
	//$outDatedOrders_6M =selectOutDatedOrders($DEADLINE_6M,null);
	//deleteMaterial_onlyFile($outDatedOrders_6M);
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
	$sql='SELECT 素材.素材識別碼,素材原始檔名,CAMPS影片派送時間 FROM 素材 LEFT JOIN 託播單素材 ON 素材.素材識別碼 = 託播單素材.素材識別碼 WHERE 託播單素材.素材識別碼 IS NULL AND 素材.CREATED_TIME <"'.$DEADLINE.
	'" AND (素材.素材有效結束時間 <"'.$DEADLINE.'" OR 素材.素材有效結束時間 IS NULL) LIMIT '.$LIMIT
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
		//orbit刪檔API已無法運作，有專人維護，不須在這邊觸發刪檔
		/*if($row['CAMPS影片派送時間']!=null){
			echo " delete remote file (orbit)...";
			if(!deleteRemoteFileOrbit($row['素材識別碼'])){
				echo " delete remote file (orbit) false\n";
				continue;
			}
		}*/
		
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
	$sql='SELECT distinct 素材.素材識別碼,素材原始檔名,CAMPS影片派送時間 FROM 素材 LEFT JOIN 託播單素材 ON 素材.素材識別碼 = 託播單素材.素材識別碼 WHERE 託播單素材.託播單識別碼 IN('.$deteteOrderListStr.')';
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
		if($row['CAMPS影片派送時間']!=null){
			echo " delete remote file (orbit)...";
			if(!deleteRemoteFileOrbit($row['素材識別碼'])){
				echo " delete remote file (orbit) false\n";
				continue;
			}
		}
		
		if(@unlink($fileName))
			echo " success\n";
		else 
			echo " fail\n";
	}
}

function deleteRemoteFileOrbit($mid){
	$statusCode = deleteRemote($mid);
	$feedback = false;
	if($statusCode == 200){
		//再利用API查詢一次，第二次查詢orbit找不到資料才算刪除成功
		$doublecheck = deleteRemote($mid);
		if($doublecheck == 405 || $doublecheck == 404)
			$feedback = true;
		else
			$feedback =false;
	}
	else if($statusCode == 405 || $statusCode == 404)
		$feedback = true;
	return $feedback;
}
function deleteRemote($mid){
	$api=Config::$CAMPS_API['delete_remote_material'];
	$url = $api.$mid;
	$ch=curl_init($url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$getResult = json_decode(curl_exec($ch),true);
	$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	/*
	switch($code){
	case 500:
		$feedback = 'API參數錯誤';
		break;
	case 404:
		$feedback = 'CAMPS無對應的檔案紀錄';
		break;
	case 405:
		$feedback = 'Orbit中已無此檔案';
		break;
	case 200:
		$feedback = '檔案成功從Orbit刪除';
		break;
	case 406:
		$feedback = 'API端流程中發生未知錯誤';
		break;
	default :
		$feedback = "";
	}
	*/
	echo " statusCode: ".$statusCode." ";
	return $statusCode;
}

function deleteMaterialFileByOutDateList($filename){
	global $my,$FILEPATH,$DEADLINE;
	echo "deleteMaterialFileByOutDateList\n";
	$myfile = fopen($filename, "r") or die("Unable to open file!");
	while($file = fgets($myfile)){
		$file = trim(preg_replace('/\s\s+/', ' ', $file));
		echo $file."\n";
		//解析素材識別碼
		$id =getMaterilIdByFilePath($file);
		
		//確認素材是否過期可刪除
		//用COUNT的方式做確認，是為了避免實體檔案存在，但資料庫中沒有對應素材record的狀況
		$sql='SELECT COUNT(*)as C FROM 素材 WHERE 素材識別碼=? AND 素材.素材有效結束時間 IS NOT NULL AND 素材.素材有效結束時間 >= ? ';
		if(!$stmt=$my->prepare($sql)) {
			exit($my->error);
		}
		if(!$stmt->bind_param('is',$id,$DEADLINE)){
			exit($my->error);
		}
		if(!$stmt->execute()) {
			exit($my->error);
		}
		if(!$res=$stmt->get_result()){
			exit($my->error);
		}

		$row = $res->fetch_assoc();
		if($row["C"] == 0){
			echo "已過期...";
		}
		else{
			echo "未過期...不可刪除\n";
			continue;
		}

		//確認素材是否有被託播單使用
		$sql='SELECT COUNT(*) AS C FROM 託播單素材 JOIN 託播單 ON 託播單素材.託播單識別碼 = 託播單.託播單識別碼 WHERE 託播單素材.素材識別碼='.$id.' AND 託播單.廣告期間結束時間>"'.$DEADLINE.'"';
		if(!$stmt=$my->prepare($sql)) {
			exit($my->error);
		}
		if(!$stmt->execute()) {
			exit($my->error);
		}
		if(!$res=$stmt->get_result()){
			exit($my->error);
		}

		$row = $res->fetch_assoc();
		if($row["C"] == 0){
			echo "未被走期內託播單使用，可刪除。";
			if(unlink($file))
				echo " 刪除成功\n";
			else 
				echo " 刪除失敗\n";
		}
		else{
				echo "被走期內託播單使用，不可刪除。\n";
				//echo " 刪除失敗\n";
		}
		
	}
	
	fclose($myfile);
}

function getMaterilIdByFilePath($path){
	//取得素材名稱
	$pattern = explode("/",$path);
	$name = end($pattern);
	//取得識別碼
	$pattern = explode(".",$name);
	$id = $pattern[0];
	
	return $id;
}

function deleteOrderListData(){
	global $my,$DEADLINE;
	//先選擇建立超過一定時間的委刊單
	$sql = "select 委刊單識別碼 FROM 委刊單 WHERE IF(`LAST_UPDATE_TIME` IS NOT NULL, `LAST_UPDATE_TIME`, `CREATED_TIME`) < ?";
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->bind_param('s',$DEADLINE)){
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}
	if(!$res=$stmt->get_result()){
		exit($my->error);
	}
	$deteteOrderListStr = array();
	//逐一檢查是否有建立託播單，若沒有建立託播單則刪除。
	while($row = $res->fetch_assoc()){
		echo "檢查已過期委刊單:委刊單識別碼:".$row["委刊單識別碼"]."\n";
		$sql = "select COUNT(*) AS C FROM 託播單 WHERE 委刊單識別碼 = ?";
		if(!$stmt=$my->prepare($sql)) {
			exit($my->error);
		}
		if(!$stmt->bind_param('i',$row["委刊單識別碼"])){
			exit($my->error);
		}
		if(!$stmt->execute()) {
			exit($my->error);
		}
		if(!$res2=$stmt->get_result()){
			exit($my->error);
		}
		$orderCount = $res2->fetch_assoc();
		if($orderCount["C"]==0){
			echo "沒有建立託播單，可刪除\n";
			array_push($deteteOrderListStr,$row["委刊單識別碼"]);
		}
		else{
			echo "有建立".$orderCount["C"]."張託播單，不可刪除\n";
			continue;
		}
	}
	$deteteOrderListStr = implode(",",$deteteOrderListStr);
	echo "刪除委刊單 in ".$deteteOrderListStr."\n";
	$sql='DELETE FROM 委刊單 WHERE 委刊單識別碼 IN('.$deteteOrderListStr.')';
	if(!$stmt=$my->prepare($sql)) {
		exit($my->error);
	}
	if(!$stmt->execute()) {
		exit($my->error);
	}
}

echo 'DONE';
?>