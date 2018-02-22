<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/tool/MyDB.php';
	require 'tool/OutputExcel.php';
	$logger=new MyLogger();
	$my=new MyDB(true);
	
	$nowDate = date('Y-m-d');
	//$startDate = $nowDate.' 00:00:00';
	//$endDate = $nowDate.' 23:59:59';
	$startDate = $_GET['startDate'];
	$endDate = $_GET['endDate'];
	$sql = 'SELECT  版位類型.版位名稱 AS 版位類型名稱,版位.版位名稱,託播單.託播單識別碼,託播單.託播單名稱,託播單素材.點擊後開啟類型,託播單素材.點擊後開啟位址,廣告期間開始時間,廣告期間結束時間
	FROM 
	託播單
	JOIN 託播單投放版位 ON 託播單投放版位.託播單識別碼 = 託播單.託播單識別碼
	JOIN 版位 ON 版位.版位識別碼 = 託播單投放版位.版位識別碼
	JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
	LEFT JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
	WHERE ((廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間))
	AND 版位類型.版位名稱 LIKE "單一平台%" 
	AND 託播單狀態識別碼 = 2
	AND 託播單名稱 NOT LIKE "預設banner%"
	ORDER BY 版位類型.版位名稱,版位.版位名稱
	';
	$result=$my->getResultArray($sql,'sssss',$startDate,$endDate,$startDate,$endDate,$startDate);
	$forExcel=[];
	foreach($result as $odata){
		if(!isset($forExcel[$odata['版位類型名稱']])){
			//產生title
			$forExcel[$odata['版位類型名稱']] = [];
			$tempArray=['版位類型','版位','託播單識別碼','託播單名稱','點擊後開啟類型','點擊後開啟位址','開始時間','結束時間'];
			
			$forExcel[$odata['版位類型名稱']][]=$tempArray;
		}
		$tempArray = [$odata['版位類型名稱'],$odata['版位名稱'],$odata['託播單識別碼'],$odata['託播單名稱'],$odata['點擊後開啟類型'],$odata['點擊後開啟位址'],$odata['廣告期間開始時間'],$odata['廣告期間結束時間']];
		$forExcel[$odata['版位類型名稱']][]=$tempArray;
	}
	
	OutputExcel::outputAll_sheet('order/851/adOrders',$forExcel);
	
	echo 'done';
?>