<?php
	require_once dirname(__FILE__)."/../../tool/MyDB.php";
	//$_POST["託播單識別碼"] = 47345;//dev
	$my=new MyDB(true);
	$sql= "SELECT DISTINCT 託播單識別碼,廣告主名稱,委刊單名稱,BT.版位名稱 AS 版位類型名稱,BL.版位名稱, 託播單名稱, 託播單說明, 廣告期間開始時間, 廣告期間結束時間, 廣告可被播出小時時段, O.託播單狀態識別碼
		,預約到期時間, O.售價 AS 售價,BT.版位識別碼 AS 版位類型識別碼, BL.版位識別碼 AS 版位識別碼,託播單狀態名稱,託播單CSMS群組識別碼,A.廣告主識別碼,OL.委刊單識別碼
		FROM
			託播單 O 
			LEFT JOIN 委刊單 OL ON O.委刊單識別碼 = OL.委刊單識別碼
			LEFT JOIN 廣告主 A ON OL.廣告主識別碼 = A.廣告主識別碼
			JOIN 版位 BL ON O.版位識別碼=BL.版位識別碼
            JOIN 版位 BT ON BL.上層版位識別碼=BT.版位識別碼
            JOIN 託播單狀態 ON O.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼
		WHERE O.託播單識別碼=?";
		
		$push_a = $my->getResultArray($sql,'i',$_POST["託播單識別碼"]);
		$push_a = $push_a[0];
		
		//取得素材資料
		$sql = "SELECT 素材順序,素材名稱,素材.素材識別碼,素材.影片素材秒數,可否點擊,點擊後開啟類型,點擊後開啟位址 ,產業類型名稱
			FROM 託播單素材 LEFT JOIN 素材 ON(託播單素材.素材識別碼 = 素材.素材識別碼) JOIN 產業類型 ON (素材.產業類型識別碼 = 產業類型.產業類型識別碼) WHERE 託播單素材.託播單識別碼 = ?";
		$materials = $my->getResultArray($sql,'i',$_POST["託播單識別碼"]);
		
		$push_a["素材"] = array();
		foreach($materials as $row){
			$push_a["素材"][$row["素材順序"]]=$row;
		};
		
		//取得其他參數
		$sql = "SELECT 託播單其他參數順序,託播單其他參數值
			FROM 託播單其他參數 
			WHERE 託播單識別碼 = ?";
		$paras = $my->getResultArray($sql,'i',$_POST["託播單識別碼"]);
		
		$push_a["其他參數"] = array();
		foreach($paras as $row){
			//取得參數名稱
			$sql = "SELECT 版位其他參數順序,版位其他參數是否必填,版位其他參數名稱
				FROM 版位其他參數,託播單,版位
				WHERE 託播單識別碼 = ? AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數順序 = ?";
			
			$paraname = $my->getResultArray($sql,'ii',$_POST["託播單識別碼"],$row["託播單其他參數順序"]);
			$paraname = $paraname[0]["版位其他參數名稱"];
			
			$push_a["其他參數"][$paraname]=$row["託播單其他參數值"];
		};
		
		//取得多版位
		$sql = "SELECT 版位識別碼
			FROM 託播單投放版位
			WHERE 託播單識別碼 = ? AND ENABLE = 1";
		$playpos = $my->getResultArray($sql,'i',$_POST["託播單識別碼"]);
		$positionArray = array();
		foreach($playpos as $row){
			$positionArray[]=$row['版位識別碼'];
		};
		if(count($positionArray)>0)
			$push_a['版位識別碼'] =  implode(',',$positionArray);
			
		echo json_encode($push_a,JSON_UNESCAPED_UNICODE);
?>