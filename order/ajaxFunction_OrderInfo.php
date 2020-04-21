<?php
	//前置設定
	include('../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
	if(isset($_POST['method'])){
		//取得搜尋的託播單資料
		if($_POST['method'] == 'OrderInfoBySearch'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;	//T.B.D.
			//取得sql用參數
			$parameters=getparameters();
			//若沒有輸入搜尋條件，回傳空的資料表並顯示"請設定條件"
			if(!$parameters){
				echo json_encode(array('pageNo'=>1,'maxPageNo'=>1,'header'=>array("")
							,'data'=>array(array(array("請設定收尋條件",'text'))),'sortable'=>array()),JSON_UNESCAPED_UNICODE);
				exit;
			}
			if(!isset($_POST['全託播單識別碼狀態'])){
				$_POST['全託播單識別碼狀態'] = array(0,1,2,3,4);
			}
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				';
			$sqlCon =	' FROM
					託播單
					LEFT JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					LEFT JOIN 委刊單 ON 委刊單.委刊單識別碼=託播單.委刊單識別碼
					INNER JOIN 託播單狀態 ON 託播單狀態.託播單狀態識別碼=託播單.託播單狀態識別碼
					LEFT JOIN 託播單投放版位 ON 託播單投放版位.託播單識別碼 = 託播單.託播單識別碼 AND 託播單投放版位.ENABLE=1		
					LEFT JOIN 版位 額外版位 ON 託播單投放版位.版位識別碼 = 額外版位.版位識別碼
				WHERE
					(
					'.($parameters["searchBy"]=='%'?'1':' 託播單.託播單識別碼=? OR 託播單CSMS群組識別碼=? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ?').'
					)
					'.($parameters["廣告主識別碼"]=='%'?'':' AND 委刊單.廣告主識別碼 LIKE ? ').'
					'.($parameters["委刊單識別碼"]=='%'?'':' AND 託播單.委刊單識別碼 LIKE ? ').'
					AND 版位.上層版位識別碼 LIKE ?
					AND (託播單.版位識別碼 LIKE ? OR 託播單投放版位.版位識別碼 LIKE ?)
					AND(
						(廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間)
					)
					AND 託播單.託播單狀態識別碼 LIKE ?
					'.(isset($_POST['全狀態搜尋'])?'':' AND 託播單.託播單狀態識別碼 IN ('.implode(',',$_POST['全託播單識別碼狀態']).')')
				;
			
			if(isset($_POST['OtherCondition'])){
				$sqlCon .= ' AND '.$_POST['OtherCondition'];
			}
			
			
			$param_type = ($parameters["searchBy"]=='%'?'':'iiss').($parameters["廣告主識別碼"]=='%'?'':'s').($parameters["委刊單識別碼"]=='%'?'':'s').'sssssssss';
			$a_params = array();
			$a_params[] = &$param_type;
			if($parameters["searchBy"]!='%'){
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$parameters["searchBy"];
			$a_params[] = &$parameters["searchBy"];
			}
			if($parameters["廣告主識別碼"]!='%')
			$a_params[] = &$parameters["廣告主識別碼"];
			if($parameters["委刊單識別碼"]!='%')
			$a_params[] = &$parameters["委刊單識別碼"];
			$a_params[] = &$parameters["版位類型識別碼"];
			$a_params[] = &$parameters["版位識別碼"];
			$a_params[] = &$parameters["版位識別碼"];
			$a_params[] = &$parameters["開始時間"];
			$a_params[] = &$parameters["結束時間"];
			$a_params[] = &$parameters["開始時間"];
			$a_params[] = &$parameters["結束時間"];
			$a_params[] = &$parameters["開始時間"];
			$a_params[] = &$parameters["狀態"];
			
			if($parameters["素材識別碼"]!='%'){
				$param_type .='s';
				$sqlCon.=' AND 託播單素材.素材識別碼 LIKE ? ';
				$a_params[] = &$parameters["素材識別碼"];
			}
			if($parameters["素材群組識別碼"]!='%'){
				$param_type .='s';
				$sqlCon.=' AND 素材.素材群組識別碼 LIKE ? ';
				$a_params[] = &$parameters["素材群組識別碼"];
			}
			
			$sql.=$sqlCon;
			
			if(!$stmt = $my->prepare($sql)) {
				exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
			}
			call_user_func_array(array($stmt, 'bind_param'), $a_params);
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//取得資料
			$sql=
				'
				SELECT
					託播單.託播單識別碼,
					託播單CSMS群組識別碼,
					託播單名稱,
					託播單說明,
					託播單狀態名稱 AS 託播單狀態,
					CASE  
					   WHEN 額外版位.版位名稱 IS NULL THEN 版位.版位名稱
					   ELSE 額外版位.版位名稱
					END AS 投放版位,  
					素材.素材識別碼 AS 素材識別碼,
					圖片素材寬度 AS 圖片寬,
					圖片素材高度 AS 圖片高,
					影片素材秒數 AS 影片秒數,
					點擊後開啟類型 AS 點擊類型,
					點擊後開啟位址 AS 點擊位址,
					廣告期間開始時間 AS 開始,
					廣告期間結束時間 AS 結束,
					廣告可被播出小時時段 AS 時段 
					';
			$sql.=$sqlCon.' 
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			$param_type .='s';
			$a_params[] = &$fromRowNo;
				
			if(!$stmt = $my->prepare($sql)) {
				exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
			}
			call_user_func_array(array($stmt, 'bind_param'), $a_params);

			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}	
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}		
			while($row=$res->fetch_assoc()){
					$temp = explode(',',$row['時段']);
					$timeString=$temp[0];
					for($i =1; $i<count($temp); $i++){
						if(intval($temp[$i-1],10)!=intval($temp[$i],10)-1){
							$timeString.='~'.$temp[$i-1].','.$temp[$i];
						}
					}
					//取得投放版位的區域
					$area=explode('_',$row['投放版位']);
					$area = end($area);
					//依照版位
					switch($area){
						case '北':
							$color = '#CC0000';
						break;
						case '中':
							$color = '#00AA00';
						break;
						case '南':
							$color = '#0000CC';
						break;
						default :
							$color = 'black';
						break;
					}
					$timeString.='~'.$temp[count($temp)-1];
					$orders[]=array(array($row['託播單識別碼'],'text'),array(($row['託播單CSMS群組識別碼']==null)?'':$row['託播單CSMS群組識別碼'],'text')
					,array($row['託播單名稱'],'text'),array(($row['託播單說明']==null)?'':$row['託播單說明'],'text'),array($row['託播單狀態'],'text'),array('<font color="'.$color.'">'.$row['投放版位'].'</font>','html')
					,array(($row['素材識別碼']==null)?'':'<font color="'.$color.'">'.$row['素材識別碼'].'</font>','html'),array(($row['圖片寬']==null)?'':'<font color="'.$color.'">'.$row['圖片寬'].'</font>','html')
					,array(($row['圖片高']==null)?'':'<font color="'.$color.'">'.$row['圖片高'].'</font>','html'),array(($row['影片秒數']==null)?'':'<font color="'.$color.'">'.$row['影片秒數'].'</font>','html')
					,array(($row['點擊類型']==null)?'':'<font color="'.$color.'">'.$row['點擊類型'].'</font>','html'),array(($row['點擊位址']==null)?'':'<font color="'.$color.'">'.$row['點擊位址'].'</font>','html')
					,array('<font color="'.$color.'">'.$row['開始'].'</font>','html'),array('<font color="'.$color.'">'.$row['結束'].'</font>','html'),array('<font color="'.$color.'">'.$timeString.'</font>','html')
					);
				}

			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('託播單識別碼','託播單CSMS群組識別碼','託播單名稱','託播單說明','託播單狀態'
							,'投放版位','素材識別碼','圖片寬','圖片高','影片秒數','點擊類型','點擊位址','開始','結束','時段')
							,'data'=>$orders,'sortable'=>array('託播單識別碼','託播單CSMS群組識別碼','託播單名稱','託播單說明','託播單狀態','投放版位','素材識別碼'
																,'圖片寬','圖片高','影片秒數','點擊類型','點擊位址','開始','結束','時段')),JSON_UNESCAPED_UNICODE);
			exit;
		}
		else if($_POST['method'] == '全託播單識別碼'){
			$parameters=getparameters();
			//沒設定搜尋條件，回傳空資料
			if(!$parameters)
				exit(json_encode(array(),JSON_UNESCAPED_UNICODE));
			
			$sql='
				SELECT DISTINCT 託播單.託播單識別碼,託播單狀態.託播單狀態名稱
				';
			$sqlCon =	' FROM
					託播單
					LEFT JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					LEFT JOIN 委刊單 ON 委刊單.委刊單識別碼=託播單.委刊單識別碼
					INNER JOIN 託播單狀態 ON 託播單狀態.託播單狀態識別碼=託播單.託播單狀態識別碼
					LEFT JOIN 託播單投放版位 ON 託播單投放版位.託播單識別碼 = 託播單.託播單識別碼 AND 託播單投放版位.ENABLE=1		
					LEFT JOIN 版位 額外版位 ON 託播單投放版位.版位識別碼 = 額外版位.版位識別碼
				WHERE
					(
					'.($parameters["searchBy"]=='%'?'1':' 託播單.託播單識別碼=? OR 託播單CSMS群組識別碼=? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ?').'
					)
					'.($parameters["廣告主識別碼"]=='%'?'':' AND 委刊單.廣告主識別碼 LIKE ? ').'
					'.($parameters["委刊單識別碼"]=='%'?'':' AND 託播單.委刊單識別碼 LIKE ? ').'
					AND 版位.上層版位識別碼 LIKE ?
					AND (託播單.版位識別碼 LIKE ? OR 託播單投放版位.版位識別碼 LIKE ?)
					AND(
						(廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間)
					)
					AND 託播單.託播單狀態識別碼 LIKE ?
					'.(isset($_POST['全狀態搜尋'])?'':' AND 託播單.託播單狀態識別碼 IN ('.implode(',',$_POST['全託播單識別碼狀態']).')').'
			';
			
						
			$param_type = ($parameters["searchBy"]=='%'?'':'iiss').($parameters["廣告主識別碼"]=='%'?'':'s').($parameters["委刊單識別碼"]=='%'?'':'s').'sssssssss';
			$a_params = array();
			$a_params[] = &$param_type;
			if($parameters["searchBy"]!='%'){
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$parameters["searchBy"];
			$a_params[] = &$parameters["searchBy"];
			}
			if($parameters["廣告主識別碼"]!='%')
			$a_params[] = &$parameters["廣告主識別碼"];
			if($parameters["委刊單識別碼"]!='%')
			$a_params[] = &$parameters["委刊單識別碼"];
			$a_params[] = &$parameters["版位類型識別碼"];
			$a_params[] = &$parameters["版位識別碼"];
			$a_params[] = &$parameters["版位識別碼"];
			$a_params[] = &$parameters["開始時間"];
			$a_params[] = &$parameters["結束時間"];
			$a_params[] = &$parameters["開始時間"];
			$a_params[] = &$parameters["結束時間"];
			$a_params[] = &$parameters["開始時間"];
			$a_params[] = &$parameters["狀態"];
			
			if($parameters["素材識別碼"]!='%'){
				$param_type .='s';
				$sqlCon.=' AND 託播單素材.素材識別碼 LIKE ? ';
				$a_params[] = &$parameters["素材識別碼"];
			}
			if($parameters["素材群組識別碼"]!='%'){
				$param_type .='s';
				$sqlCon.=' AND 素材.素材群組識別碼 LIKE ? ';
				$a_params[] = &$parameters["素材群組識別碼"];
			}
			
			$sql.=$sqlCon;
			
			if(!$stmt = $my->prepare($sql)) {
				exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
			}
			call_user_func_array(array($stmt, 'bind_param'), $a_params);
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}	
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}		
			$result =array();
			if(isset($_POST["回傳狀態"])&&$_POST["回傳狀態"]){
				
				$temp =array();
				$temp2 = array();
				while($row=$res->fetch_assoc()){
					$temp[]=$row['託播單識別碼'];
					$temp2[]=$row['託播單狀態名稱'];
				}
				$result = array('id'=>$temp,'state'=>$temp2);
			}
			else{
				while($row=$res->fetch_assoc()){
					$result[]=$row['託播單識別碼'];
				}
			}
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '託播單狀態名稱'){
			$sql='
				SELECT 託播單狀態識別碼,託播單狀態名稱
				FROM 託播單狀態
				WHERE 託播單狀態識別碼 IN (0,1,2,3,4)';
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$result = array();
			while($row=$res->fetch_assoc())
				$result[] =$row; 
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == 'CSMSID取得連動託播單名稱'){
			$sql='
				SELECT 託播單CSMS群組識別碼,託播單名稱,版位名稱
				FROM 託播單,版位
				WHERE 版位.版位識別碼 = 託播單.託播單識別碼
				';
			$a_params = array();
			$n = count($_POST['ids']);
			$arrayTemp = array();
			for($i = 0; $i < $n; $i++) {
				$arrayTemp[]= '託播單CSMS群組識別碼 = ?';
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
			
			while($row=$res->fetch_assoc()){
				$CSMSNameIndex[$row['託播單CSMS群組識別碼']] = $row['託播單名稱'];
				$pnarray = explode('_',$row['版位名稱']);
				$areaName = $pnarray[count($pnarray)-1];
				if(!isset($CSMSAreaIndex[$row['託播單CSMS群組識別碼']]))
					$CSMSAreaIndex[$row['託播單CSMS群組識別碼']]=[];
				if(!in_array($areaName,$CSMSAreaIndex[$row['託播單CSMS群組識別碼']]))
					$CSMSAreaIndex[$row['託播單CSMS群組識別碼']][]=$areaName;
			}
			$result=[];
			foreach($CSMSAreaIndex as $CSMSID=>$area){
				array_push($result,['區域'=>$area,'託播單名稱'=>$CSMSNameIndex[$CSMSID],'託播單CSMS群組識別碼'=>$CSMSID]);
			}
			
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		if($_POST['method'] == '素材設定資訊'){
			$sql = '
				SELECT DISTINCT SUBSTRING_INDEX(版位名稱, "_", -1) AS 區域,可否點擊,點擊後開啟類型,點擊後開啟位址,託播單狀態名稱 
				FROM 託播單,託播單素材,版位,託播單狀態 
				WHERE 
				託播單.託播單識別碼 = 託播單素材.託播單識別碼 
				AND 託播單.版位識別碼 = 版位.版位識別碼 
				AND 託播單.託播單狀態識別碼 = 託播單狀態.託播單狀態識別碼 
				AND SUBSTRING_INDEX(版位名稱, "_", -1) IN("北","中","南")
				AND 託播單狀態名稱 IN ("預約","確定","送出","待處理")
				AND 素材識別碼 = ?				
			';
			$result = $my->getResultArray($sql,'i',$_POST['素材識別碼']);
			if(!$result)
			$result = [];
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));			
		}
	}
	
	function getparameters(){
		$checkinput = false;
		if(isset($_POST['searchBy'])&&$_POST['searchBy']!='')
			$checkinput = true;
		$parameters = array();
		$parameters["searchBy"]='%'.((isset($_POST['searchBy']))?$_POST['searchBy']:'').'%';
		if(isset($_POST['廣告主識別碼'])&&$_POST['廣告主識別碼']!=''){
			$parameters["廣告主識別碼"]=$_POST['廣告主識別碼'];
			$checkinput = true;
		}
		else
			$parameters["廣告主識別碼"]='%';
		if(isset($_POST['委刊單識別碼'])&&$_POST['委刊單識別碼']!=''){
			$parameters["委刊單識別碼"]=($_POST['委刊單識別碼']=='')?'%':$_POST['委刊單識別碼'];
			$checkinput = true;
		}
		else
			$parameters["委刊單識別碼"]='%';
		if(isset($_POST['版位類型識別碼'])&&$_POST['版位類型識別碼']!=''){
			$parameters["版位類型識別碼"]=($_POST['版位類型識別碼']=='')?'%':$_POST['版位類型識別碼'];
			$checkinput = true;
		}
		else
			$parameters["版位類型識別碼"]='%';
		if(isset($_POST['版位識別碼'])&&$_POST['版位識別碼']!=''){
			$parameters["版位識別碼"]=($_POST['版位識別碼']=='')?'%':$_POST['版位識別碼'];
			$checkinput = true;
		}
		else
			$parameters["版位識別碼"]='%';
		
		$parameters["開始時間"] = date('Y-m-d', strtotime('-7 days'));
		if(isset($_POST['開始時間'])&&$_POST['開始時間']!=''){
			$parameters["開始時間"]=$_POST['開始時間'];
			$checkinput = true;
		}
		else if(isset($_POST['結束時間'])&&$_POST['結束時間']!=''){
			$parameters["開始時間"] = date('d-m-Y', strtotime("-14 day", strtotime($_POST['結束時間'])));
			$checkinput = true;
		}
		$parameters["開始時間"].=' 00:00:00';
		
		$parameters["結束時間"] = date('Y-m-d', strtotime("+14 day", strtotime($parameters["開始時間"])));
		if(isset($_POST['結束時間'])&&$_POST['結束時間']!=''){
			$parameters["結束時間"]=$_POST['結束時間'];
		}
		$parameters["結束時間"].=' 23:59:59';
	
		if(isset($_POST['狀態']))
			$parameters["狀態"]=($_POST['狀態']=='-1'||$_POST['狀態']==null)?'%':$_POST['狀態'];
		else
			$parameters["狀態"]='%';
		if(isset($_POST['素材識別碼'])&&$_POST['素材識別碼']!=''){
			$parameters["素材識別碼"]=($_POST['素材識別碼']=='-1'||$_POST['素材識別碼']==null)?'%':$_POST['素材識別碼'];
			$checkinput = true;
		}
		else
			$parameters["素材識別碼"]='%';
		if(isset($_POST['素材群組識別碼'])&&$_POST['素材群組識別碼']!=''){
			$parameters["素材群組識別碼"]=($_POST['素材群組識別碼']=='0'||$_POST['素材群組識別碼']==null)?'%':$_POST['素材群組識別碼'];
			$checkinput = true;
		}
		else
			$parameters["素材群組識別碼"]='%';
		//若沒有輸入搜尋條件，回傳空的資料"
		if(!$checkinput){
			/*echo json_encode(array('pageNo'=>1,'maxPageNo'=>1,'header'=>array("")
						,'data'=>array(array(array("請設定收尋條件",'text'))),'sortable'=>array()),JSON_UNESCAPED_UNICODE);
			exit;*/
			return false;
		}
		
		return $parameters;
	}
	exit ;
	
?>