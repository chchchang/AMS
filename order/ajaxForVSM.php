<?php 	
	include('../tool/auth/authAJAX.php');
	require_once dirname(__FILE__).'/../tool/MyDB.php';
	$my=new MyDB(true);

	//BY POST

	if(isset($_POST['連動廣告'])){
		$sql = 'SELECT 託播單識別碼,託播單名稱,廣告可被播出小時時段,版位.版位名稱
			FROM 託播單,版位,版位 版位類型
			WHERE (版位類型.版位名稱="單一平台banner") AND 版位類型.版位識別碼 = 版位.上層版位識別碼 AND 版位.版位識別碼 = 託播單.版位識別碼 
			';
		
		$a_params = array();
		$n = count($_POST['Dates']);
		$arrayTemp = array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= '(( ? BETWEEN 託播單.廣告期間開始時間 AND 託播單.廣告期間結束時間) AND ( ? BETWEEN 託播單.廣告期間開始時間 AND 託播單.廣告期間結束時間))';
		}	
		$arrayTemp=implode(" AND ", $arrayTemp);
		if($arrayTemp!='')
			$sql.=' AND('.$arrayTemp.')';

		$param_type = '';
		for($i = 0; $i < $n; $i++) {
			$param_type .='ss';
		}
		$a_params[] = &$param_type;
		for($i = 0; $i < $n; $i++) {
			$a_params[] = &$_POST['Dates'][$i]["StartDate"];
			$a_params[] = &$_POST['Dates'][$i]["EndDate"];
		}
		
		$sql.=' ORDER BY 託播單識別碼';
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		 
		$stmt->execute();
		
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$result = [];
		while($row=$res->fetch_assoc()){
			$a = explode(",",$row['廣告可被播出小時時段']);
			$flag = true;
			foreach($_POST['Hours'] as $hours){
				$a2 = explode(",",$hours);
				$intersect = array_intersect($a,$a2);
				if(sizeof($a2) != sizeof($intersect)){
					$flag = false;
					break;
				}
			}
			if($flag){
				array_push($result,['託播單名稱'=>$row['託播單名稱'],'託播單識別碼'=>$row['託播單識別碼'],'版位名稱'=>$row['版位名稱']]);
			}
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if($_POST['method'] == '取得連動託播單名稱'){
		$sql='
			SELECT 託播單識別碼,託播單名稱,版位名稱
			FROM 託播單,版位
			WHERE 版位.版位識別碼 = 託播單.託播單識別碼
			';
		$a_params = array();
		$n = count($_POST['ids']);
		$arrayTemp = array();
		for($i = 0; $i < $n; $i++) {
			$arrayTemp[]= '託播單識別碼 = ?';
		}	
		$arrayTemp=implode(" OR ", $arrayTemp);
		if($arrayTemp!='')
			$sql.=' AND('.$arrayTemp.')';

		$param_type = '';
		for($i = 0; $i < $n; $i++) {
			$param_type .='i';
		}
		$a_params[] = &$param_type;
		for($i = 0; $i < $n; $i++) {
			$a_params[] = &$_POST['ids'][$i];
		}
		
		$sql.=' ORDER BY 託播單識別碼';
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		 
		$stmt->execute();
		
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$result=[];
		while($row=$res->fetch_assoc()){
			array_push($result,['託播單名稱'=>$row['託播單名稱'],'託播單識別碼'=>$row['託播單識別碼'],'版位名稱'=>$row['版位名稱']]);
		}
			
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	//查詢修改後是否影響連動廣告，'檢察連動更動' '託播單識別碼' 'StartDate' 'EndDate' '廣告可被播出小時時段' 回傳true:可以修改 false:會更改到連動、不建議修改
	else if(isset($_POST['檢察連動更動'])){
		//是否為banner類型託播單
		$sql = 'SELECT 版位類型.版位名稱 AS 版位名稱,託播單CSMS群組識別碼
			FROM 託播單,版位,版位 版位類型
			WHERE 版位類型.版位識別碼 = 版位.上層版位識別碼 AND 版位.版位識別碼 = 託播單.版位識別碼 AND 託播單識別碼 = ?
			';
		$result=$my->getResultArray($sql,'i',$_POST['託播單識別碼']);
		if($result==false)
			exit(json_encode(array("success"=>false,"message"=>'取得使用資料過程中發生錯誤！'),JSON_UNESCAPED_UNICODE));
		else if($result[0]!='專區banner'){//不屬於首頁banner或專區banner、不會有連動問題
			exit(json_encode(array("success"=>true),JSON_UNESCAPED_UNICODE));
		}
		
		$csmsID = $result[0]['託播單CSMS群組識別碼'] ;
		//取得有連動的託播單資料
		$like1 = "%,".$csmsID."%";
		$like2 = $csmsID.",%";
		
		$sql = 'SELECT 託播單.託播單識別碼,廣告可被播出小時時段,廣告期間開始時間,廣告期間結束時間,託播單其他參數值
			FROM 託播單,版位,版位 版位類型,託播單其他參數,版位其他參數
			WHERE 託播單.版位識別碼=版位.版位識別碼 AND 版位.上層版位識別碼=版位類型.版位識別碼 AND 版位類型.版位名稱 = "專區vod"  
			AND 託播單.託播單識別碼 = 託播單其他參數.託播單識別碼 AND 託播單其他參數順序 = 版位其他參數順序 AND (版位.版位識別碼 = 版位其他參數.版位識別碼 OR 版位類型.版位識別碼 = 版位其他參數.版位識別碼)
			AND (版位其他參數.版位其他參數名稱 LIKE "bannerTransactionId_")
			AND (託播單其他參數值 LIKE ? OR 託播單其他參數值 LIKE ? OR 託播單其他參數值 = ?)
			';

		$result=$my->getResultArray($sql,'ssi',$like1,$like2,$csmsID);
		if(count($result)===0)//沒有連動廣告
			exit(json_encode(array("success"=>true),JSON_UNESCAPED_UNICODE));
			
		foreach($result as $row){	
			if($row['廣告期間開始時間']<$_POST['StartDate']||$row['廣告期間結束時間']>$_POST['EndDate'])
				exit(json_encode(array("success"=>false,'message'=>'託播單修改後的日期無法被'.$row['託播單識別碼'].'號託播單連動'),JSON_UNESCAPED_UNICODE));
			$a1 = explode(",",$row['廣告可被播出小時時段']);
			$a2 = explode(",",$_POST['廣告可被播出小時時段']);
			$intersect = array_intersect($a1,$a2);
			if(sizeof($a1) != sizeof($intersect))//交集的大小不同，有託播單無法被連動
				exit(json_encode(array("success"=>false,'message'=>'託播單修改後的時段無法被'.$row['託播單識別碼'].'號託播單連動'),JSON_UNESCAPED_UNICODE));
		}
		//所有託播單皆可連動
		exit(json_encode(array("success"=>true),JSON_UNESCAPED_UNICODE));
	}
		
?>