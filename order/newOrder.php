<?php 	
	include('../tool/auth/authAJAX.php');
	require_once dirname(__FILE__).'/../tool/MyDB.php';
	$my=new MyDB(true);

	//BY POST
	if(isset($_POST["版位有效時間"])){
		$sql = 'SELECT 版位.版位名稱 AS 版位名稱
			,版位類型.版位名稱 AS 版位類型名稱
			,版位類型.版位有效起始時間 AS 版位類型有效起始時間
			,版位類型.版位有效結束時間 AS 版位類型有效結束時間
			,版位.版位有效起始時間 AS 版位有效起始時間
			,版位.版位有效結束時間 AS 版位有效結束時間
			,版位.預約到期提前日 AS 版位預約日
			,版位類型.預約到期提前日 AS 版位類型預約日
		FROM 版位 版位類型,版位 版位 WHERE 版位.上層版位識別碼 =版位類型.版位識別碼 ';
		
		//準備where condition和參數定義string
		$bindparam=[];//代入bind_param用array
		$difStrning='';//參數定義字串
		$whereCondi =[];//組成where condition用ARRAY
		$bindparam[] = &$difStrning;
		
		foreach($_POST["版位有效時間"] as $index=>$pid){
			$difStrning .= 'i';
			$bindparam[] = &$_POST["版位有效時間"][$index];
			$whereCondi[] = ' 版位.版位識別碼=? ';	
		}
		
		$sql .= 'AND ('.implode('OR',$whereCondi).')';
		
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!call_user_func_array(array($stmt, 'bind_param'), $bindparam)){
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$feedback = [];
		
		while($row= $res->fetch_assoc())
			$feedback[] = $row;
		exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST['版位資料'])){
		//取得版位類型資料
		$sql = 'SELECT 版位.版位識別碼,版位.版位名稱,版位類型.版位名稱 AS 版位類型名稱,版位類型.版位識別碼 AS 版位類型識別碼 
		FROM 版位 版位類型,版位 版位 
		WHERE 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 版位.版位識別碼=?';
		
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["版位識別碼"])){
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
			
		exit(json_encode($res->fetch_array(),JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST['連動廣告'])){
		$sql = 'SELECT 託播單識別碼,託播單名稱,廣告可被播出小時時段,託播單CSMS群組識別碼,版位.版位名稱,版位其他參數預設值
			FROM 託播單,版位,版位 版位類型,版位其他參數
			WHERE (版位類型.版位名稱="首頁banner" OR 版位類型.版位名稱="專區banner") AND 版位類型.版位識別碼 = 版位.上層版位識別碼 AND 版位.版位識別碼 = 託播單.版位識別碼 
			AND 版位.版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = "bnrSequence"
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
		
		$sql.=' ORDER BY 託播單CSMS群組識別碼';
		if(!$stmt = $my->prepare($sql)) {
			exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
		}
		call_user_func_array(array($stmt, 'bind_param'), $a_params);
		 
		$stmt->execute();
		
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$CSMSAreaIndex = [];//記錄該CSMSID的託播單有哪些區域
		$CSMSNameIndex = [];//記錄CSMSID託播單的名稱
		$CSMSBnrIndex =[];//記錄CSMSID託播單的bnrSequence值
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
				$CSMSNameIndex[$row['託播單CSMS群組識別碼']] = $row['託播單名稱'];
				$pnarray = explode('_',$row['版位名稱']);
				$areaName = $pnarray[count($pnarray)-1];
				if(!isset($CSMSAreaIndex[$row['託播單CSMS群組識別碼']]))
					$CSMSAreaIndex[$row['託播單CSMS群組識別碼']]=[];
				if(!in_array($areaName,$CSMSAreaIndex[$row['託播單CSMS群組識別碼']]))
					$CSMSAreaIndex[$row['託播單CSMS群組識別碼']][]=$areaName;
				$CSMSBnrIndex[$row['託播單CSMS群組識別碼']]=$row['版位其他參數預設值'];
			}
		}
		
		$result = ['1'=>[],'2'=>[]];//分別存放兩個bnrSquence可連動的banner託播單
		if(isset($_POST['Area'])){
			//有限定區域，檢查是否包含全部的指定區域
			foreach($CSMSAreaIndex as $CSMSID=>$area){
				$intersect = array_intersect($_POST['Area'],$area);
				if(count($intersect)==count($_POST['Area']))
					array_push($result[$CSMSBnrIndex[$CSMSID]],['區域'=>$area,'託播單名稱'=>$CSMSNameIndex[$CSMSID],'託播單CSMS群組識別碼'=>$CSMSID]);
			}
		}else{
			foreach($CSMSAreaIndex as $CSMSID=>$area){
				array_push($result[$CSMSBnrIndex[$CSMSID]],['區域'=>$area,'託播單名稱'=>$CSMSNameIndex[$CSMSID],'託播單CSMS群組識別碼'=>$CSMSID]);
			}
		}
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST['檢察託播單群組時段重複'])){
		$sql = 'select B.廣告可被播出小時時段 AS 廣告可被播出小時時段
		FROM 託播單 A, 託播單 B
		where A.託播單群組識別碼 = B.託播單群組識別碼 AND B.託播單識別碼!=A.託播單識別碼 AND B.版位識別碼 = A.版位識別碼
		AND A.託播單識別碼 = ?
		AND ((? BETWEEN B.廣告期間開始時間 AND B.廣告期間結束時間) OR (? BETWEEN B.廣告期間開始時間 AND B.廣告期間結束時間) 
			OR (B.廣告期間開始時間 BETWEEN ? AND ?) OR ? = B.廣告期間開始時間 OR ? = B.廣告期間結束時間)';
			
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('issssss',$_POST["OrderId"],$_POST["StartDate"],$_POST["EndDate"],$_POST["StartDate"],$_POST["EndDate"],$_POST["StartDate"],$_POST["EndDate"])){
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		while($row=$res->fetch_assoc()){
			$a1 = explode(",",$row['廣告可被播出小時時段']);
			$a2 = explode(",",$_POST['Hours']);
			$intersect = array_intersect($a1,$a2);
			if(sizeof($intersect) != 0)
				exit(json_encode(array("success"=>false,"message"=>'同群組託播單時段重複: '.implode(",",$intersect)),JSON_UNESCAPED_UNICODE));
		}
		exit(json_encode(array("success"=>true,"message"=>'OK!'),JSON_UNESCAPED_UNICODE));
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
			AND (版位其他參數.版位其他參數名稱 = "bannerTransactionId1" || 版位其他參數.版位其他參數名稱 = "bannerTransactionId2")
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
	else if(isset($_POST['method'])){
		if($_POST['method']=='取得前置連動託播單'){
			$sql = 'SELECT 託播單識別碼,託播單名稱,廣告可被播出小時時段
				FROM 託播單,版位,版位 版位類型
				WHERE 版位類型.版位名稱="前置廣告投放系統" AND 版位類型.版位識別碼 = 版位.上層版位識別碼 AND 版位.版位識別碼 = 託播單.版位識別碼 
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
			/* with call_user_func_array, array params must be passed by reference */
			$a_params[] = &$param_type;
			for($i = 0; $i < $n; $i++) {
				/* with call_user_func_array, array params must be passed by reference */
				$a_params[] = &$_POST['Dates'][$i]["StartDate"];
				$a_params[] = &$_POST['Dates'][$i]["EndDate"];
			}
			if(!$stmt = $my->prepare($sql)) {
				exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
			}
			 
			/* use call_user_func_array, as $stmt->bind_param('s', $param); does not accept params array */
			call_user_func_array(array($stmt, 'bind_param'), $a_params);
			 
			$stmt->execute();
			
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$result = array();
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
				if($flag)
					array_push($result,['託播單識別碼'=>$row['託播單識別碼'],'託播單名稱'=>$row['託播單名稱']]);
			}
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method']=='vod上限比例'){
			$pt = array();
			//取的版位類型預設值
			$sql = '
				SELECT 版位其他參數預設值,版位其他參數名稱
				FROM 版位其他參數,版位
				WHERE 版位.版位識別碼 = 版位其他參數.版位識別碼 and 版位名稱 = "專區vod" and 版位其他參數名稱 in ("bakadDisplayMaxPercentage","bakadschdDisplayMaxPercentage")
			';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}				
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			while($row=$res->fetch_assoc()){
				$pt[$row['版位其他參數名稱']]=intval($row['版位其他參數預設值']);
			}
			
			//逐一取得版位設定
			$percentages = array();
			$pNames = array();
			foreach($_POST['版位'] as $id){
				$percentages[$id]['bakadDisplayMaxPercentage']=$pt['bakadDisplayMaxPercentage'];
				$percentages[$id]['bakadschdDisplayMaxPercentage']=$pt['bakadschdDisplayMaxPercentage'];
				//取得版位名稱
				$sql = '
					SELECT 版位名稱
					FROM 版位
					WHERE 版位識別碼 = ?
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}				
				if(!$stmt->bind_param('i',$id)){
					exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				//記錄除去北/中/南/IAP的名稱
				$pNames[$id] =explode('_',$res->fetch_assoc()['版位名稱']);
				array_pop($pNames[$id]);
				$pNames[$id] = implode('_',$pNames[$id]);

				$sql = '
					SELECT 版位其他參數預設值,版位其他參數名稱
					FROM 版位其他參數
					WHERE 版位識別碼 = ? and 版位其他參數名稱 in ("bakadDisplayMaxPercentage","bakadschdDisplayMaxPercentage")
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}				
				if(!$stmt->bind_param('i',$id)){
					exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				while($row=$res->fetch_assoc()){
					if($row['版位其他參數名稱']=="bakadDisplayMaxPercentage")
						$percentages[$id]['bakadDisplayMaxPercentage']=intval($row['版位其他參數預設值']);
					else if($row['版位其他參數名稱']=="bakadschdDisplayMaxPercentage")
						$percentages[$id]['bakadschdDisplayMaxPercentage']=intval($row['版位其他參數預設值']);
				}
			}
			//計算相同版位名稱得加總
			$groupCount= array();
			$groupCountschd= array();
			foreach($pNames as $pid=>$pN){
				if(!isset($groupCount[$pN])){
					$groupCount[$pN]=$percentages[$pid]['bakadDisplayMaxPercentage'];
					$groupCountschd[$pN]=$percentages[$pid]['bakadschdDisplayMaxPercentage'];
				}
				else{
					$groupCount[$pN]+=$percentages[$pid]['bakadDisplayMaxPercentage'];
					$groupCountschd[$pN]+=$percentages[$pid]['bakadschdDisplayMaxPercentage'];
				}
			}
			//計算比例
			foreach($percentages as $pid=>$per){
				$percentages[$pid]['bakadDisplayMaxPercentage']/=$groupCount[$pNames[$pid]];
				$percentages[$pid]['bakadschdDisplayMaxPercentage']/=$groupCountschd[$pNames[$pid]];
			}
			exit(json_encode(array("success"=>true,"data"=>$percentages),JSON_UNESCAPED_UNICODE));
		}
		exit;
	}
	//動作類型
	if(isset($_GET["edit"]))
		$action =  "edit";
	else if(isset($_GET["info"]))
		$action =  "info";
	else if(isset($_GET["update"]))
		$action =  "update";
	else if(isset($_GET["orderInDb"]))
		$action =  "orderInDb";
	else if(isset($_GET["orderFromApi"]))
		$action =  "orderFromApi";
	else
		$action =  "new";
	
	//取得版位名稱以及初始參數
	$changedOrderId=0;$版位識別碼=0;$版位類型識別碼=0;$saveBtnText = '暫存';
	if(isset($_GET["update"])) 
		$changedOrderId=htmlspecialchars($_GET["update"], ENT_QUOTES, 'UTF-8');
	else if(isset($_GET["edit"]))
		$changedOrderId=htmlspecialchars($_GET["edit"], ENT_QUOTES, 'UTF-8');
	else if(isset($_GET["orderInDb"]))
		$changedOrderId=htmlspecialchars($_GET["orderInDb"], ENT_QUOTES, 'UTF-8');
		else if(isset($_GET["orderFromApi"]))
		$changedOrderId=htmlspecialchars($_GET["orderFromApi"], ENT_QUOTES, 'UTF-8');
			
	//有指定的託播單，檢查託播單的版位，決定是否要跳轉
	if($action!='new'){
		if($action=='edit'){
			$版位類型名稱=$_SESSION['AMS']['saveOrder'][$_GET["edit"]]['版位類型名稱'];
		}
		else if($action=='info'){
			$版位類型名稱=$_SESSION['AMS']['saveOrder'][$_GET["info"]]['版位類型名稱'];
		}
		else{
			$sql = 'SELECT 版位類型.版位名稱 AS 版位類型名稱 
			FROM 版位 版位類型,版位,託播單 
			WHERE 版位.上層版位識別碼 =版位類型.版位識別碼 AND 版位.版位識別碼 = 託播單.版位識別碼 AND 託播單識別碼=?';	
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$changedOrderId)){
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$stmt->bind_result($版位類型名稱);
			$stmt->fetch();
		}
	}
	else{
		if(isset($_GET["positionId"])) 
			$版位識別碼=htmlspecialchars($_GET["positionId"], ENT_QUOTES, 'UTF-8'); 
		if(isset($_GET["positionTypeId"])) 
			$版位類型識別碼=htmlspecialchars($_GET["positionTypeId"], ENT_QUOTES, 'UTF-8'); 
		$sql = 'SELECT 版位類型.版位名稱 AS 版位類型名稱 FROM 版位 版位類型,版位 WHERE 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 版位.版位識別碼=?';	
		if(!$stmt=$my->prepare($sql)) {
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$版位識別碼)){
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		$stmt->bind_result($版位類型名稱);
		$stmt->fetch();
	}

	if(isset($_GET["saveBtnText"])) 
		$saveBtnText=htmlspecialchars($_GET["saveBtnText"], ENT_QUOTES, 'UTF-8');
	$my->close();
	
	//自動代入委刊單名稱用
	if(isset($_GET["orderListName"]))
		$委刊單名稱 = htmlspecialchars($_GET["orderListName"], ENT_QUOTES, 'UTF-8');
	else
		$委刊單名稱 = '';
		
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.tokenize.js"></script>
<script type="text/javascript" src="newOrder_852.js?<?=time()?>"></script>
<script type="text/javascript" src="newOrder_851.js?<?=time()?>"></script>
<script src="../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-plugin/jquery.tokenize.css" />
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<style type="text/css">
  	.Center{
		position:absolute;
		left:50%;
	}
	button{
		margin-right:5px; 
		margin-left:5px; 
	}
	input[type=file]::-webkit-file-upload-button {
		width: 0;
		padding: 0;
		margin: 0;
		-webkit-appearance: none;
		border: none;
		border:0px;
	}
	x::-webkit-file-upload-button, input[type=file]:after {
		content:'選擇';
		left: 100%;
		margin-left:3px;
		position: relative;
		-webkit-appearance: button;
		padding: 3px 8px 3px;
		border:0px;
	}
	input[type=file]{
		margin-right:45px;
	}
	#playTime thead tr th,#playTime thead tr td,#playTime tbody tr th,#playTime tbody tr td{
		text-align: center;
		background-color: #DDDDDD;
		width:20px;
		height:20px;
	}
	#positiontype,#position,#版位有效起始時間,#版位有效結束時間{
		text-decoration:underline;
		margin-left:3px;
		margin-right:10px;
	}
	.tokenize{ width: 300px }
</style>

</head>
<body>
<h4 id="message"> </h4>
<div id = 'mainFram'>
<fieldset  style="clear: both;">
    <legend>託播單資訊</legend>
	<table width = '100%' class='styledTable2'>
		<tr><th>版位類型:</th><td><a id="positiontype" ></td></tr>
		<tr><th>版位名稱:</th><td><select id="position"  multiple="multiple"  class ="tokenize"></select> <button id ='clearPosition' class = 'darkButton'>清空已選版位</button></td></tr>
		<tr><th><a>CSMS群組識別碼:</a></th><td> <a id = 'csmsGroupID'></a></td></tr>
		<tr><th>託播單名稱*:</th><td><input id = "Name" type="text" value = "" size="38" class ="nonNull"> <button id ='copyOrder' onClick = 'selectOrderFun()' class = 'darkButton'>填入現有託播單資訊</button></td></tr>
		<tr><th>託播單說明:</th><td><input id = "Info" type="text" value = "" size="38"></td></tr>
		<tr><th>託播單期間*:</th><td>
		<button class='darkButton' id='newDuration'>新增一組</button>
			<table><thead><tr><th>開始</th><th>結束</th><th></th></thead></tr><tbody id = 'durationTb'>
				<tr><td><input id = "StartDate" type="text" value = "" class ="nonNull"></td><td><input id = "EndDate" type="text" value = "" class ="nonNull"></td><td></td></tr>
				</tbody>
			</table>
			</td>
		</tr>
		<tr id = "playTimeOption"><th>託播單時段*:</th><td><button id = 'allTimeBtn' class = 'darkButton'>全選</button> <button id = 'noTimeBtn' class = 'darkButton'>全不選</button>
			<table border ="0" id = "playTime">
			<thead><tr><th>0</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>9</th>
			<th>10</th><th>11</th><th>12</th><th>13</th><th>14</th><th>15</th><th>16</th><th>17</th><th>18</th><th>19</th>
			<th>20</th><th>21</th><th>22</th><th>23</th></tr></thead>
			<tbody><tr>
			<td><input type="checkbox" name="hours" value="0" checked></td><td><input type="checkbox" name="hours" value="1" checked></td><td><input type="checkbox" name="hours" value="2" checked></td>
			</td><td><input type="checkbox" name="hours" value="3" checked></td><td><input type="checkbox" name="hours" value="4" checked></td><td><input type="checkbox" name="hours" value="5" checked></td>
			</td><td><input type="checkbox" name="hours" value="6" checked></td><td><input type="checkbox" name="hours" value="7" checked></td><td><input type="checkbox" name="hours" value="8" checked></td>
			</td><td><input type="checkbox" name="hours" value="9" checked></td><td><input type="checkbox" name="hours" value="10" checked></td><td><input type="checkbox" name="hours" value="11" checked></td>
			</td><td><input type="checkbox" name="hours" value="12" checked></td><td><input type="checkbox" name="hours" value="13" checked></td><td><input type="checkbox" name="hours" value="14" checked></td>
			</td><td><input type="checkbox" name="hours" value="15" checked></td><td><input type="checkbox" name="hours" value="16" checked></td><td><input type="checkbox" name="hours" value="17" checked></td>
			</td><td><input type="checkbox" name="hours" value="18" checked></td><td><input type="checkbox" name="hours" value="19" checked></td><td><input type="checkbox" name="hours" value="20" checked></td>
			</td><td><input type="checkbox" name="hours" value="21" checked></td><td><input type="checkbox" name="hours" value="22" checked></td><td><input type="checkbox" name="hours" value="23" checked></td>
			</tr></tbody>
			</table>
		</td></tr>
		<tr><th>預約到期日期*:</th><td><input id = "Deadline" type="text" value = "" size="15" class ="nonNull"></td></tr>
		<tr><th>售價:</th><td><input id="售價" type="number" value = ""></td></tr>
	</table>
	</fieldset>
	
	<fieldset  style="clear: both;">
	<legend>其他參數</legend>
	<table width = '100%' class='styledTable2'>
		<thead><tr><th>參數名稱</th><th>類型</th><th>必填</th><th>是否新增</th><th>內容</th></tr></thead>
		<tbody id = 'configTbody'></tbody>
	</table>
	</fieldset>
	
	<fieldset  style="clear: both;">
	<legend>素材</legend>
	<table width = '100%' class='styledTable2'>
		<thead><tr><th>順序</th><th>素材類型</th><th>必填</th><th>可否點擊</th><th>點擊開啟類型</th><th>點擊開啟位址</th><th>選擇素材</th></tr></thead>
		<tbody id = 'materialTbody'></tbody>
	</table>
	</fieldset>
		<div class ="Center"><button id="clearBtn" type="button" onclick = "clearInput()">清空</button><button id = 'saveBtn' type="button" onclick = "save()">暫存</button></div>
	</div>
	<button id = 'closeSelection' class = 'darkButton' style='float:right'>關閉選單</button>
	<iframe id ='selectOrder' width = '100%' height = '600px' style='clear:both'></iframe>
	
	<div id="material_dialog_form">
	<table class='styledTable2' width = '100%'>
		<tr><th>素材順序</th><td><a id ='選擇素材順序'></a></td></tr>
		<tr><th>素材類型</th><td><a id ='選擇素材類型'></a></td></tr>
		<tr><th><a id="materialgroup">素材群組:</a></th><td><select id="MaterialGroup"></select><button id="materialInfo" type="button" onclick = "materialInfo()">詳細資訊</button></td></tr>
		<tr><th><a>素材:</a></th><td><select id="Material"></select><button id = '選擇素材'>選擇素材</button></td></tr>
	</table>
	<table class='styledTable2' id = 'matrialConifg' width = '100%'>
		<thead>
			<tr><th>區域</th><th>狀態</th><th>開啟類型</th><th>開啟位址</th><th>套用</th></tr>
		</thead>
		<tbody id = 'matrialConifgTbody'>
		</tbody>
	</table>
	</div>
<script>
	//********設定
	var positionTypeId =<?=$版位類型識別碼?>;
	var positionId =<?=$版位識別碼?>;
	//判對動作是新增訂單(new)/修改暫存訂單資訊(edit)/顯示暫存訂單資訊(info)/修改舊有訂單(update)/顯示API託播單資訊(orderFromApi)
	var action ="<?= $action?>";
	var changedOrderId = <?=$changedOrderId?>;
	
	$('#saveBtn').text('<?=$saveBtnText?>');
	
	$("#clickOption,#selectOrder,#closeSelection").hide();
</script>
<?php include('_newOrderUiScript.php');?>
<script>
	$('#clearPosition').click(function(){
		$('#position').tokenize().clear();
	});
	clearInput = function(){
		//新增託播單:清空資訊
		if(action=="new"){
			//檢查最後一筆新增的版位類型是否否本次新增相同
			$.post('orderSession.php',{'getLastOrder':1},
				function(json2){
					if(json2['版位類型識別碼']==positionTypeId){
						//最後一筆使用的資料版位類型相同
						json2['託播單CSMS群組識別碼']='';
						showVal(json2);
					}else{
						//沒有該版位類型最後一筆託播單資料
						$.post('?',{版位資料:1,版位識別碼:positionId,版位類型識別碼:positionTypeId},
							function(json){					
								var jdata = {
									'託播單群組識別碼':'',
									"版位類型名稱":json["版位類型名稱"],
									"版位名稱":json["版位名稱"],
									"版位類型識別碼":json["版位類型識別碼"],
									"版位識別碼":json["版位識別碼"],
									"託播單名稱":"<?=$委刊單名稱?>",
									"託播單說明":'',
									"廣告期間開始時間":'',
									"廣告期間結束時間":'',
									"廣告可被播出小時時段":'0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23',
									"預約到期時間":'',
									"售價":'',
									'其他參數': otherConfigObj_default
								};
								showVal(jdata);
							}
							,'json'
						);
					}
				}
				,'json'
			);
		}
		else{
			$('#newDuration').hide();
			var jdata;
			//修改或顯示暫存的新訂單 回復暫存的資訊
			if(action=="edit"||action=="info"){
				jdata = JSON.parse('<?php if(isset($_SESSION['AMS']['saveOrder'])) echo json_encode($_SESSION['AMS']['saveOrder'],JSON_UNESCAPED_UNICODE); else echo"[]"?>');
				if(action=="edit")
					jdata=jdata[<?php if(isset($_GET["edit"])) echo htmlspecialchars($_GET["edit"], ENT_QUOTES, 'UTF-8'); else echo 0;?>];
				else 
					jdata=jdata[<?php if(isset($_GET["info"])) echo htmlspecialchars($_GET["info"], ENT_QUOTES, 'UTF-8'); else echo 0;?>];
				showVal(jdata);
			}
			//顯示資料庫中的資料
			else if(action=='update'||action=='orderInDb'){
				getInfoFromDb(changedOrderId,false);
			}
			//顯示API的資料
			else if(action=='orderFromApi'){
				showVal(parent.orderInfo);
			}
		}
	}
	
	//非更動現有託播單
	if(action!="update"){
		clearInput();
	}
	else{
		//先檢查是否有暫存的修改紀錄
		var jdata="";
		$.post("orderSession.php",{"getEditList":1}).done(function(data){
			var rawjdata =[];
			if(data!=""){
				rawjdata = JSON.parse(data);
			}
			for(var i in rawjdata["edit"])
				if(rawjdata["edit"][i]["託播單識別碼"]==changedOrderId)
					jdata = rawjdata["edit"][i];
			if(jdata=="")//沒有紀錄
				clearInput();
			else{
				showVal(jdata);
				//待修正
				//heighlightInput(jdata);
			}

		});
	}

	//與原始資料比較並加亮修改的地方
	function heighlightInput(jdata){
		//取的資料庫資料
		$.post("ajaxToDB_Order.php",{"action":"訂單資訊","託播單識別碼":changedOrderId})
		.done(function(data){
			odata = JSON.parse(data);
			if(odata.版位名稱!=jdata.版位名稱)
				$("#position").css("background","#a8d1ff");
			if(odata.託播單名稱!=jdata.託播單名稱)
				$("#Name").css("background","#a8d1ff");
			if(odata.託播單說明!=jdata.託播單說明)
				$("#Info").css("background","#a8d1ff");
			if(odata.廣告期間開始時間!=jdata.廣告期間開始時間)
				$("#StartDate").css("background","#a8d1ff");
			if(odata.廣告期間結束時間!=jdata.廣告期間結束時間)
				$("#EndDate").css("background","#a8d1ff");
			if(odata.素材識別碼!=jdata.素材識別碼)
				$("#material").css("background","#a8d1ff");
			if(odata.預約到期時間!=jdata.預約到期時間)
				$("#Deadline").css("background","#a8d1ff");
			if(odata.售價!=jdata.售價)
				$("#售價").css("background","#a8d1ff");

			if(odata.廣告可被播出小時時段!=jdata.廣告可被播出小時時段){
				var hours = jdata.廣告可被播出小時時段.split(",");
				var ohours = odata.廣告可被播出小時時段.split(",");
				for(var i=0; i<24;i++){
					var jindex=$.inArray(i.toString(),hours);
					var oindex=$.inArray(i.toString(),ohours);
					if((jindex!=-1&&oindex==-1)||(jindex==-1&&oindex!=-1)){
						$('#playTime tbody tr td:nth-child('+(i+1)+')').css("background","#a8d1ff").css({"border-color":"#a8d1ff",'border-style': 'solid', 'border-width': '2px'});
					}
				}	
			}
			
			//其他參數
			if(typeof(jdata['其他參數'])!='undefined'){
				for( var i in jdata['其他參數']){
					if(jdata['其他參數'][i]!=odata['其他參數'][i])
					$('#configTbody tr td input[order = '+i+']').css("background","#a8d1ff");

					
				}
			}
			if(typeof(jdata['素材'])!='undefined'){
				for( var i in jdata['素材']){
					if(jdata['素材'][i].可否點擊!=odata['素材'][i].可否點擊)
						$('#可否點擊'+i).css({"border-color":"#a8d1ff",'border-style': 'solid', 'border-width': '2px'});
					if(jdata['素材'][i].點擊後開啟類型!=odata['素材'][i].點擊後開啟類型)
						$('#點擊後開啟類型'+i).css("background","#a8d1ff");
					if(jdata['素材'][i].點擊後開啟位址!=odata['素材'][i].點擊後開啟位址)
						$('#點擊後開啟位址'+i).css("background","#a8d1ff");
					if(jdata['素材'][i].素材識別碼!=odata['素材'][i].素材識別碼)
						$('#mBtn'+i).css({"border-color":"#a8d1ff",'border-style': 'solid', 'border-width': '2px'});
				}
			}
		});
	}
	
	function save(){
		var nonNullEmpty= false;
		$(".nonNull").each(function(){ 
			if($.trim($(this).val())==""){
				nonNullEmpty = true;
			}
		});
		if(nonNullEmpty){
			alert("請填寫必要資訊");
			$(".nonNull").css("border", "2px solid red");
			return 0;
		}
		
		$('#durationTb tr').each(function(){
			var stt = $(this).find(' input:eq(0)').val();
			var edt = $(this).find(' input:eq(1)').val();
			if(stt>edt){
				alert("起始時間 必須小於 結束時間");
				return 0;
			}
			if($("#Deadline").val()+" 00:00:00">edt){
				alert("預約到期時間 必須小於等於 結束日期");
				return 0;
			}
		});
		
		//若為CSMS類型託播單，託播單名稱不可包含"\'"字元
		switch($("#positiontype").text()){
			case "首頁banner":
			case "專區banner":
			case "頻道short EPG banner":
			case "專區vod":
			if($("#Name").val().indexOf("'") != -1)
			{
				alert("CSMS類型託播單名稱不可包含「'」符號");
				return 0;
			}
			break;
		}
		
		//將選擇的小時時段轉為ARRAY
		var hours="";
		var temp=new Array();
		$('input[name="hours"]:checked').each(function(){temp.push($(this).val());});
		hours=temp.join(',')
	
		if(hours=="" ){
			alert("請勾選播出時段");
			return 0;
		}
		
		var StartDate= $("#StartDate").val();
		var EndDate= $("#EndDate").val();
		
		//**多選
		if (!$.isArray($('#position').val())||$('#position').val().length==0){
			alert('請至少選擇一個版位');
			return 0;
		}
		//逐一檢查必選素材是否有被選擇
		var nessM=false;
		for(var i in materialObj){
			if(materialObj[i]['素材識別碼']==0){
				if(materialObj[i]['託播單素材是否必填']==1)
					nessM = true;
				else
					delete materialObj[i];
			}
			
		}
		if(nessM)
			alert('尚有必填素材沒有選擇，送出前請選擇素材');
		
		//逐一檢查版位時間試否可以播放
		$('body').mask('產生託播單中...');
		var positionArray=$('#position').val();
		fail=[];
		$.ajax({
			async: false,
			type : "POST",
			url : 'newOrder.php',
			data: {版位有效時間:positionArray},
			dataType : 'json',
			success :
				function(data){
					for(var i in data){
						$('#durationTb tr').each(function(){
							var stt = $(this).find(' input:eq(0)').val();
							var edt = $(this).find(' input:eq(1)').val();
							var startLimit = (data[i]["版位有效起始時間"]==null)?data[i]["版位類型有效起始時間"]:data[i]["版位有效起始時間"];
							var endLimit = (data[i]["版位有效結束時間"]==null)?data[i]["版位類型有效結束時間"]:data[i]["版位有效結束時間"];
							
							if(startLimit!=null)
								if(stt<startLimit){
									fail.push(data['版位名稱']);
								}
							if(endLimit!=null)
								if(edt>endLimit){
									fail.push(data['版位名稱']);
								}
						});					
					}
				}
		});
		if(fail.length>0){
			alert("託播單期間超過版位("+fail.join(',')+')的有效期間');
			return 0;
		}
		
		//更動現有託播單，檢查是否會影響連動
		if(action == 'update'){
			var check = false;
			$.ajax({
				async: false,
				type : "POST",
				url : '?',
				data: {檢察連動更動:true,託播單識別碼:changedOrderId,StartDate:StartDate,EndDate:EndDate,廣告可被播出小時時段:hours},
				dataType : 'json',
				success :
					function(data){
						check = data.success;
						if(!check)
							alert(data.message);
					}
			});
			if(!check)
			return 0;
		}
		
		//取得每個版位的託播單
		//專區vod自動分配頭放上限比例計算
		var vodPercentage;
		if($("#positiontype").text()=='專區vod'){
			$.ajax({
				async: false,
				type : "POST",
				url : '?',
				data: {method:'vod上限比例',版位:positionArray},
				dataType : 'json',
				success :
					function(data){
						if(!data.success)
							alert(data.message);
						else{
							vodPercentage = data.data;
						}
					}
			});
		}
		
		var orders=[];	
		$('#durationTb tr').each(function(){
				var stt = $(this).find(' input:eq(0)').val();
				var edt = $(this).find(' input:eq(1)').val();
			$("#position option:selected").each(function() {
				var pid = $(this).val();
				var pname = $(this).text().split(':');
				pname.splice(0,1)
				pname=pname.join(':');				
				var order = getOrderObj(pname,pid);
				order["廣告期間開始時間"] = stt;
				order["廣告期間結束時間"] = edt;
				order["群組廣告期間開始時間"] = stt;
				order["群組廣告期間結束時間"] = edt;
				orders.push(order);
			});					
		});		

		function getOrderObj(pname,pid){	
			var jobject = {
				"版位類型名稱":$("#positiontype").text(),
				"版位名稱":pname,
				"版位類型識別碼":$("#positiontype").val(),
				"版位識別碼":pid,
				"託播單名稱":$("#Name").val(),
				"託播單說明":$("#Info").val(),
				//"廣告期間開始時間":StartDate,
				//"廣告期間結束時間":EndDate,
				"廣告可被播出小時時段":hours,
				"群組廣告期間開始時間":StartDate,
				"群組廣告期間結束時間":EndDate,
				"群組廣告可被播出小時時段":hours,
				"預約到期時間":($("#Deadline").val()=="")?null:$("#Deadline").val()+" 23:59:59",
				"售價":($("#售價").val()=="")?null:$("#售價").val(),
				'其他參數':{},
				'素材':materialObj
			};
	
			$.each(otherConfigObj,function(index,value){
				if($('#是否新增'+index).prop('checked')){
					jobject['其他參數'][index]=value;
				}
			});
			//專區vod自動分配頭放上限
			if($("#positiontype").text()=='專區vod'){
				$( "#configTbody tr td:first-child" ).each(
				function(){
					if(typeof(jobject['其他參數'][$(this).attr('order')])!='undefined'){
						if($(this).text()=='影片投放上限'){
							var num = parseInt(jobject['其他參數'][$(this).attr('order')],10);
							if(isNaN(num))
								jobject['其他參數'][$(this).attr('order')]= jobject['其他參數'][$(this).attr('order')]==null?null:'';
							else
								jobject['其他參數'][$(this).attr('order')]= Math.round(num*vodPercentage[pid]['bakadDisplayMaxPercentage']);
						}
						if($(this).text()=='專區排程上限'){
							var num = parseInt(jobject['其他參數'][$(this).attr('order')],10);
							if(isNaN(num))
								jobject['其他參數'][$(this).attr('order')]= '';
							else
								jobject['其他參數'][$(this).attr('order')]= Math.round(num*vodPercentage[pid]['bakadschdDisplayMaxPercentage']);
						}
					}
					
				}
				);
			}
			if($('#csmsGroupID').text()!='')
				jobject['託播單CSMS群組識別碼'] = $('#csmsGroupID').text();
			return jobject;
		}
		
		$.post(ajaxtodbPath,{"action":"檢察素材","orders":JSON.stringify(orders)},
		function(data){
			$('body').unmask();
			if(data["success"]){
				switch($("#positiontype").text()){
					case '前置廣告投放系統':
						saveOrder_852(orders,action);
						break;
					case "首頁banner":
					case "專區banner":
					case "頻道short EPG banner":
					case "專區vod":
						saveOrder_851(orders,action);
						break;
					default:
						if(action == "new"){
							parent.newOrderSaved(orders);
						}
						else if(action=="edit"){
							parent.editOrder(orders,<?php if(isset($_GET["edit"])) echo htmlspecialchars($_GET["edit"], ENT_QUOTES, 'UTF-8'); else echo 0;?>);
						}
						else if(action=="update"){
							orders[0].託播單識別碼=changedOrderId;
							parent.updateOrder(orders[0]);
						}
						break;
				}
			}
			else
				alert(data["message"]);
		},'json'
		);
	}
	
 </script>
 
 
</body>
</html>