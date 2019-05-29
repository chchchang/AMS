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
			$searchBy='%'.((isset($_POST['searchBy']))?$_POST['searchBy']:'').'%';
			if(isset($_POST['廣告主識別碼']))
				$adowner=($_POST['廣告主識別碼']=='')?'%':$_POST['廣告主識別碼'];
			else
				$adowner='%';
			if(isset($_POST['委刊單識別碼']))
				$orderList=($_POST['委刊單識別碼']=='')?'%':$_POST['委刊單識別碼'];
			else
				$orderList='%';
			if(isset($_POST['版位類型識別碼']))
				$positionType=($_POST['版位類型識別碼']=='')?'%':$_POST['版位類型識別碼'];
			else
				$positionType='%';
			if(isset($_POST['版位識別碼']))
				$position=($_POST['版位識別碼']=='')?'%':$_POST['版位識別碼'];
			else
				$position='%';
			if(isset($_POST['開始時間']))
				$startDate=($_POST['開始時間']=='')?'0000-00-00':$_POST['開始時間'].' 00:00:00';
			else
				$startDate='0000-00-00';
			if(isset($_POST['結束時間']))
				$endDate=($_POST['結束時間']=='')?'9999-12-31':$_POST['結束時間'].' 23:59:59';
			else
				$endDate='9999-12-31';
			if(isset($_POST['狀態']))
				$state=($_POST['狀態']=='-1'||$_POST['狀態']==null)?'%':$_POST['狀態'];
			else
				$state='%';
			if(isset($_POST['素材識別碼']))
				$material=($_POST['素材識別碼']=='-1'||$_POST['素材識別碼']==null)?'%':$_POST['素材識別碼'];
			else
				$material='%';
			if(isset($_POST['素材群組識別碼']))
				$materialGroup=($_POST['素材群組識別碼']=='0'||$_POST['素材群組識別碼']==null)?'%':$_POST['素材群組識別碼'];
			else
				$materialGroup='%';
				
			if($positionType=='%'){
				exit(json_encode(array('success'=>false , 'message'=>'請選擇版位類型'),JSON_UNESCAPED_UNICODE));
			}
			
			//先取得總筆數
			$sql='
				SELECT DISTINCT COUNT(1) COUNT
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
					'.($searchBy=='%'?'1':' 託播單.託播單識別碼=? OR 託播單CSMS群組識別碼=? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ?').'
					)
					'.($adowner=='%'?'':' AND 委刊單.廣告主識別碼 LIKE ? ').'
					'.($orderList=='%'?'':' AND 託播單.委刊單識別碼 LIKE ? ').'
					AND 版位.上層版位識別碼 LIKE ?
					AND (託播單.版位識別碼 LIKE ? OR 託播單投放版位.版位識別碼 LIKE ?)
					AND(
						(廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間)
					)
					AND 託播單.託播單狀態識別碼 LIKE ?
					'.(isset($_POST['全狀態搜尋'])?'':' AND 託播單.託播單狀態識別碼 IN (0,1,2,3,4)').'
			';
			
			if(isset($_POST['OtherCondition'])){
				$sqlCon .= ' AND '.$_POST['OtherCondition'];
			}
			
			
			$param_type = ($searchBy=='%'?'':'iiss').($adowner=='%'?'':'s').($orderList=='%'?'':'s').'sssssssss';
			$a_params = array();
			$a_params[] = &$param_type;
			if($searchBy!='%'){
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$searchBy;
			$a_params[] = &$searchBy;
			}
			if($adowner!='%')
			$a_params[] = &$adowner;
			if($orderList!='%')
			$a_params[] = &$orderList;
			$a_params[] = &$positionType;
			$a_params[] = &$position;
			$a_params[] = &$position;
			$a_params[] = &$startDate;
			$a_params[] = &$endDate;
			$a_params[] = &$startDate;
			$a_params[] = &$endDate;
			$a_params[] = &$startDate;
			$a_params[] = &$state;
			
			if($material!='%'){
				$param_type .='s';
				$sqlCon.=' AND 託播單素材.素材識別碼 LIKE ? ';
				$a_params[] = &$material;
			}
			if($materialGroup!='%'){
				$param_type .='s';
				$sqlCon.=' AND 素材.素材群組識別碼 LIKE ? ';
				$a_params[] = &$materialGroup;
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

			//再取得資料
			$sql=
				'
				SELECT DISTINCT
					託播單.託播單識別碼,
					託播單CSMS群組識別碼,
					託播單名稱,
					託播單說明,
					託播單狀態名稱 AS 託播單狀態,
					CASE  
					   WHEN 額外版位.版位名稱 IS NULL THEN 版位.版位名稱
					   ELSE 額外版位.版位名稱
					END AS 投放版位,  
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
					/*$timeString=$temp[0];
					for($i =1; $i<count($temp); $i++){
						if(intval($temp[$i-1],10)!=intval($temp[$i],10)-1){
							$timeString.='~'.$temp[$i-1].','.$temp[$i];
						}
					}
					//取得投放版位的區域
					$timeString.='~'.$temp[count($temp)-1];*/
					//取得託播單參數資訊
					$orderParas = getOrderPara($row['託播單識別碼']);
					$orderData=array(array($row['託播單識別碼'],'text')
					,array($row['託播單名稱'],'text')
					,array($row['投放版位'],'text')
					,array('<input type="text" id ="'.$row['託播單識別碼'].'_startTime" value="'.$row['開始'].'">','html')
					,array('<input type="text" id ="'.$row['託播單識別碼'].'_endTime" value="'.$row['結束'].'">','html')
					,array('<input type="text" id ="'.$row['託播單識別碼'].'_hours" value="'.$row['時段'].'">','html')
					);
					foreach($orderParas as $para){
						$orderData[] = array('<input type="text" class="'.$row['託播單識別碼'].'_orderParaValues" id="'.$row['託播單識別碼'].'_'.$para['託播單其他參數順序'].'" paraindex="'.$para['託播單其他參數順序'].'" value="'.$para['託播單其他參數值'].'">','html');
					}
					$orders[] = $orderData;
				}	
			
			//取的參數資料
			$positionParas = getpositionTypePara($positionType);
			$header = array('託播單識別碼','託播單名稱','投放版位','開始','結束','時段');
			foreach($positionParas as $para){
				$header[]=$para["版位其他參數顯示名稱"];	
			}
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE)
							,'header'=>$header
							,'data'=>$orders,'sortable'=>array('託播單識別碼','託播單名稱','投放版位','開始','結束','時段')
							),JSON_UNESCAPED_UNICODE);
			exit;
		}
		else if($_POST['method'] == 'OrderUpdate'){
			$oid = $_POST["託播單識別碼"];
			$stt = $_POST["廣告期間開始時間"];
			$edt = $_POST["廣告期間結束時間"];
			$hours = $_POST["廣告可被播出小時時段"];
			$paras = $_POST["託播單其他參數"];
			//更新託播單
			$sql = "
				update 託播單 set 廣告期間開始時間 = ?,廣告期間結束時間 = ?,廣告可被播出小時時段 = ? WHERE 託播單識別碼 = ?
			";
			if(!$result = $my->execute($sql,"sssi",$stt,$edt,$hours,$oid)){
				exit (json_encode(array("success"=>false,"message"=>"更新託播單資料表失敗"),JSON_UNESCAPED_UNICODE));
			}
			//更新託播單其他參數
			foreach($paras as $index => $data){
				$sql = "
					update 託播單其他參數 set 託播單其他參數值 = ? WHERE 託播單識別碼 = ? AND 託播單其他參數順序 = ?
				";
				if(!$result = $my->execute($sql,"sii",$data,$oid,$index)){
					exit (json_encode(array("success"=>false,"message"=>"更新託播單參數失敗"),JSON_UNESCAPED_UNICODE));
				}
			}
			exit (json_encode(array("success"=>true,"message"=>"更新託播單資訊成功","託播單識別碼"=>$oid),JSON_UNESCAPED_UNICODE));
		}
	}
	function getpositionTypePara($positionType){
		global 	$my;
		$sql=
			'
				SELECT
					版位其他參數顯示名稱,
					版位其他參數順序,
					版位其他參數預設值
				FROM 版位其他參數
				WHERE 版位識別碼 = ? AND 是否版位專用 = 0
				ORDER BY 版位其他參數順序
			';
		$paras=$my->getResultArray($sql,'i',$positionType);
		$sortedParas = array();
		foreach($paras as $para){
			$sortedParas[$para["版位其他參數順序"]]=$para;
		}
		
		return $sortedParas;
	}
	function getOrderPara($oid){
		global 	$my;
		$sql=
			'
				SELECT
					託播單其他參數順序,
					託播單其他參數值
				FROM 託播單其他參數
				WHERE 託播單識別碼 = ?
				ORDER BY 託播單其他參數順序
			';
		$paras=$my->getResultArray($sql,'i',$oid);
		$sortedParas = array();
		foreach($paras as $para){
			$sortedParas[$para["託播單其他參數順序"]]=$para;
		}
		
		return $sortedParas;
	}
	exit ;
	
?>