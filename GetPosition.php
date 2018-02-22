<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/tool/MyDB.php';
	require 'tool/OutputExcel.php';
	$logger=new MyLogger();
	$my=new MyDB(true);

	//首頁banner
	$sql = 'SELECT 版位.版位名稱 ,版位參數.版位其他參數預設值 AS SERCODE,版位參數2.版位其他參數預設值 AS BNR
	FROM 
	版位
	JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
	JOIN 版位其他參數 版位類型參數 ON( 版位類型.版位識別碼 = 版位類型參數.版位識別碼 AND 版位類型參數.版位其他參數名稱 = "serCode")
	JOIN 版位其他參數 版位類型參數2 ON (版位類型.版位識別碼 = 版位類型參數2.版位識別碼 AND 版位類型參數2.版位其他參數名稱 = "bnrSequence")
	JOIN 版位其他參數 版位參數 ON (版位.版位識別碼 = 版位參數.版位識別碼 AND 版位參數.版位其他參數順序 = 版位類型參數.版位其他參數順序)
	JOIN 版位其他參數 版位參數2 ON (版位.版位識別碼 = 版位參數2.版位識別碼 AND 版位參數2.版位其他參數順序 = 版位類型參數2.版位其他參數順序)
	WHERE 版位.上層版位識別碼 = 2 
	order by SERCODE,BNR,版位.版位名稱';
	$check=$my->getResultArray($sql);
	$forExcel1 = [];
	$forExcel1[]=['版位名稱','serCode','bnrSequence'];
	foreach($check as $data){
			$forExcel1[]=[$data['版位名稱'],$data['SERCODE'],$data['BNR']];
	}
	
	//專區 banner
	$sql = 'SELECT 版位.版位名稱 ,版位參數.版位其他參數預設值 AS SERCODE ,版位參數2.版位其他參數預設值 AS BNR
	FROM 
	版位
	JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
	JOIN 版位其他參數 版位類型參數 ON( 版位類型.版位識別碼 = 版位類型參數.版位識別碼 AND 版位類型參數.版位其他參數名稱 = "serCode")
	JOIN 版位其他參數 版位類型參數2 ON (版位類型.版位識別碼 = 版位類型參數2.版位識別碼 AND 版位類型參數2.版位其他參數名稱 = "bnrSequence")
	JOIN 版位其他參數 版位參數 ON (版位.版位識別碼 = 版位參數.版位識別碼 AND 版位參數.版位其他參數順序 = 版位類型參數.版位其他參數順序)
	JOIN 版位其他參數 版位參數2 ON (版位.版位識別碼 = 版位參數2.版位識別碼 AND 版位參數2.版位其他參數順序 = 版位類型參數2.版位其他參數順序)
	WHERE 版位.上層版位識別碼 = 3 
	order by SERCODE,BNR,版位.版位名稱';
	$check=$my->getResultArray($sql);
	$forExcel2 = [];
	$forExcel2[]=['版位名稱','serCode','bnrSequence'];
	foreach($check as $data){
			$forExcel2[]=[$data['版位名稱'],$data['SERCODE'],$data['BNR']];
	}

	//頻道short EPG
	$sql = 'SELECT 版位.版位名稱 ,版位參數.版位其他參數預設值 AS CHANNEL
	FROM 
	版位
	JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
	JOIN 版位其他參數 版位類型參數 ON( 版位類型.版位識別碼 = 版位類型參數.版位識別碼 AND 版位類型參數.版位其他參數名稱 = "sepgOvaChannel")
	JOIN 版位其他參數 版位參數 ON (版位.版位識別碼 = 版位參數.版位識別碼 AND 版位參數.版位其他參數順序 = 版位類型參數.版位其他參數順序)
	WHERE 版位.上層版位識別碼 = 4 order by CHAR_LENGTH(版位.版位名稱),版位.版位名稱';
	$check=$my->getResultArray($sql);
	$forExcel3 = [];
	$forExcel3[]=['版位名稱','sepgOvaChannel'];
	foreach($check as $data){
			$forExcel3[]=[$data['版位名稱'],$data['CHANNEL']];
	}
	
	//專區vod
	$sql = 'SELECT 版位.版位名稱 ,版位參數.版位其他參數預設值 AS SERCODE
	FROM 
	版位
	JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
	JOIN 版位其他參數 版位類型參數 ON( 版位類型.版位識別碼 = 版位類型參數.版位識別碼 AND 版位類型參數.版位其他參數名稱 = "serCode")
	JOIN 版位其他參數 版位參數 ON (版位.版位識別碼 = 版位參數.版位識別碼 AND 版位參數.版位其他參數順序 = 版位類型參數.版位其他參數順序)
	WHERE 版位.上層版位識別碼 = 5
	order by SERCODE,版位.版位名稱';
	$check=$my->getResultArray($sql);
	$forExcel4 = [];
	$forExcel4[]=['版位名稱','serCode'];
	foreach($check as $data){
			$forExcel4[]=[$data['版位名稱'],$data['SERCODE']];
	}
	
	//前置廣告資料
	$sql = 'SELECT 版位.版位名稱 ,版位參數.版位其他參數預設值 AS ext,版位參數2.版位其他參數預設值 AS pre
	FROM 
	版位
	JOIN 版位 版位類型 ON 版位.上層版位識別碼 = 版位類型.版位識別碼
	JOIN 版位其他參數 版位類型參數 ON( 版位類型.版位識別碼 = 版位類型參數.版位識別碼 AND 版位類型參數.版位其他參數名稱 = "ext")
	LEFT JOIN 版位其他參數 版位類型參數2 ON (版位類型.版位識別碼 = 版位類型參數2.版位識別碼 AND 版位類型參數2.版位其他參數名稱 = "pre")
	JOIN 版位其他參數 版位參數 ON (版位.版位識別碼 = 版位參數.版位識別碼 AND 版位參數.版位其他參數順序 = 版位類型參數.版位其他參數順序)
	LEFT JOIN 版位其他參數 版位參數2 ON (版位.版位識別碼 = 版位參數2.版位識別碼 AND 版位參數2.版位其他參數順序 = 版位類型參數2.版位其他參數順序)
	WHERE 版位.上層版位識別碼 = 1
	order by pre,ext,版位.版位名稱';
	$check=$my->getResultArray($sql);
	$forExcel5 = [];
	$forExcel5[]=['版位名稱','ext','pre'];
	foreach($check as $data){
			$forExcel5[]=[$data['版位名稱'],$data['ext'],$data['pre']];
	}
	
	OutputExcel::outputAll_sheet('order/851/position',array('首頁banner'=>$forExcel1,
												'專區banner'=>$forExcel2,
												'頻道short EPG banner'=>$forExcel3,
												'專區vod'=>$forExcel4,
												'前置廣告投放系統'=>$forExcel5
												));
?>