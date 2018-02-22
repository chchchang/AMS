<?php
	//前置設定
	include('../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
	$material_folder = Config::GET_MATERIAL_FOLDER();
	$material_folder_url = Config::GET_MATERIAL_FOLDER_URL(dirname(__FILE__).'\\');
	//$pppp=dirname(__FILE__);
	//exit($material_folder_url.' '.$material_folder.' '.$pppp);
	if(isset($_POST['method'])){
		if($_POST['method']=='DATAGRID素材資訊'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			$searchBy='%'.$_POST['searchBy'].'%';
			if(!isset($_POST['素材類型'])||$_POST['素材類型']=='')
				$_POST['素材類型'] = '%';
			if(!isset($_POST['素材群組識別碼'])||$_POST['素材群組識別碼']==''||$_POST['素材群組識別碼']==0)
				$_POST['素材群組識別碼'] = '%';
			if(isset($_POST['開始時間']))
				$startDate=($_POST['開始時間']=='')?'0000-00-00':$_POST['開始時間'].' 00:00:00';
			else
				$startDate='0000-00-00';
			if(isset($_POST['結束時間']))
				$endDate=($_POST['結束時間']=='')?'9999-12-31':$_POST['結束時間'].' 23:59:59';
			else
				$endDate='9999-12-31';

			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 素材
				WHERE 素材類型識別碼 LIKE ? 
				AND 素材群組識別碼 LIKE ? 
				AND(
						((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
						OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
					)
				AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? )';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('sssssssssssss',$_POST['素材類型'],$_POST['素材群組識別碼']
				,$startDate,$endDate,$startDate,$endDate,$startDate,$endDate,$startDate
				,$searchBy,$searchBy,$searchBy,$searchBy)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 素材識別碼, 素材.素材群組識別碼, 素材類型名稱,素材名稱,文字素材內容,圖片素材寬度,圖片素材高度,影片素材秒數,素材原始檔名,素材有效開始時間 AS 有效開始時間,素材有效結束時間 AS 有效結束時間
					,素材群組.DISABLE_TIME
				FROM 素材
				LEFT JOIN 素材群組 ON 素材.素材群組識別碼 = 素材群組.素材群組識別碼
				,素材類型
				WHERE 素材.素材類型識別碼 LIKE ? 
				AND 素材.素材類型識別碼 =  素材類型.素材類型識別碼
				AND 素材.素材群組識別碼 LIKE ? 
				AND(
						((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
						OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
					)
				AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? ) 
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('sssssssssssssi',$_POST['素材類型'],$_POST['素材群組識別碼']
				,$startDate,$endDate,$startDate,$endDate,$startDate,$endDate,$startDate
				,$searchBy,$searchBy,$searchBy,$searchBy,$fromRowNo)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}	
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}		
			while($row=$res->fetch_assoc()){
				if($row['素材群組識別碼']!=null)
					if($row['DISABLE_TIME']!=null)
						$row['素材群組識別碼'].='(已隱藏)';
				if($row['素材類型名稱']=='圖片'||$row['素材類型名稱']=='影片'){
					$exists = true;
					if($row['素材原始檔名']==''||$row['素材原始檔名']==NULL)
						$exists = false;
					else if($row['素材類型名稱']=='圖片'){
						$explodeFileName=explode(".",$row['素材原始檔名']);
						$exists= file_exists($material_folder_url.$row['素材識別碼'].".".$explodeFileName[count($explodeFileName)-1]);
					}
					
					if(!$exists){
						$start ='<a style="color:red">';$end='</a>';
						$orders[]=array(array($row['素材識別碼'],'html'),array($start.(($row['素材群組識別碼']==null)?'':$row['素材群組識別碼']).$end,'html')
						,array($start.$row['素材類型名稱'].$end,'html'),array($start.$row['素材名稱'].$end,'html')
						,array('','html')
						,($row['素材類型名稱']=='圖片')?array('<img class ="dgImg" src="'.$material_folder_url.$row['素材識別碼'].'?'.time().
						'" alt="'.$row['素材識別碼'].':'.$row['素材原始檔名'].'"style="max-width:100%;max-height:100%;border:0;">','html'):array('','text')
						,array($start.(($row['圖片素材寬度']==null)?'':$row['圖片素材寬度']).$end,'html'),array($start.(($row['圖片素材高度']==null)?'':$row['圖片素材高度']).$end,'html')
						,array($start.(($row['影片素材秒數']==null)?'':$row['影片素材秒數']).$end,'html'),array(($row['有效開始時間']==null)?'':$row['有效開始時間'],'text'),array(($row['有效結束時間']==null)?'':$row['有效結束時間'],'text')
						);
					}
					else{
						goto addOrders;
					}
				
				}
				else{
					addOrders:
						$explodeFileName=explode(".",$row['素材原始檔名']);
						$orders[]=array(array($row['素材識別碼'],'text'),array(($row['素材群組識別碼']==null)?'':$row['素材群組識別碼'],'text'),array($row['素材類型名稱'],'text'),array($row['素材名稱'],'text')
						,array(($row['文字素材內容']==null)?'':$row['文字素材內容'],'text')
						,($row['素材類型名稱']=='圖片')?array('<img class ="dgImg" src="'.$material_folder_url.$row['素材識別碼'].'.'.end($explodeFileName).'?'.time().
						'" alt="'.$row['素材識別碼'].':'.$row['素材原始檔名'].'" style="max-width:100%;max-height:100%;border:0;">','html'):array('','text')
						,array(($row['圖片素材寬度']==null)?'':$row['圖片素材寬度'],'text'),array(($row['圖片素材高度']==null)?'':$row['圖片素材高度'],'text')
						,array(($row['影片素材秒數']==null)?'':$row['影片素材秒數'],'text'),array(($row['有效開始時間']==null)?'':$row['有效開始時間'],'text'),array(($row['有效結束時間']==null)?'':$row['有效結束時間'],'text')
						);
				}
			}
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('素材識別碼','素材群組識別碼','素材類型名稱','素材名稱','文字素材內容'
									,'圖片素材內容','圖片素材寬度','圖片素材高度','影片素材秒數','有效開始時間','有效結束時間')
							,'data'=>$orders,'sortable'=>array('素材識別碼','素材群組識別碼','素材類型名稱','素材名稱','文字素材內容'
									,'圖片素材寬度','圖片素材高度','影片素材秒數','有效開始時間','有效結束時間')),JSON_UNESCAPED_UNICODE);
		}
		if($_POST['method']=='DATAGRID素材資訊_MISSING'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;	//T.B.D.
			$searchBy='%'.$_POST['searchBy'].'%';
			if(!isset($_POST['素材類型'])||$_POST['素材類型']=='')
				$_POST['素材類型'] = '%';
			if(!isset($_POST['素材群組識別碼'])||$_POST['素材群組識別碼']=='')
				$_POST['素材群組識別碼'] = '%';
			
			//取得資料
			$sql='
				SELECT 素材識別碼, 素材.素材群組識別碼, 素材類型名稱,素材名稱,文字素材內容,圖片素材寬度,圖片素材高度,影片素材秒數,素材原始檔名,素材群組.DISABLE_TIME
				FROM 素材
				LEFT JOIN 素材群組 ON 素材.素材群組識別碼 = 素材群組.素材群組識別碼,素材類型
				WHERE 素材.素材類型識別碼=素材類型.素材類型識別碼 AND (素材識別碼 = ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? ) AND 素材.素材類型識別碼 LIKE ? AND 素材.素材群組識別碼 LIKE ?
				ORDER BY '.$_POST['order'].' '.$_POST['asc'];
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('issss',$_POST['searchBy'],$searchBy,$searchBy,$_POST['素材類型'],$_POST['素材群組識別碼'])) {
			
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}	
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}		
			while($row=$res->fetch_assoc()){
				if($row['素材群組識別碼']!=null)
					if($row['DISABLE_TIME']!=null)
						$row['素材群組識別碼'].='(已隱藏)';
				if($row['素材類型名稱']=='圖片'||$row['素材類型名稱']=='影片'){
					$exists = true;
					if($row['素材原始檔名']==''||$row['素材原始檔名']==NULL)
						$exists = false;
					else if($row['素材類型名稱']=='圖片'){
						$explodeFileName=explode(".",$row['素材原始檔名']);
						$exists= file_exists($material_folder_url.$row['素材識別碼'].".".$explodeFileName[count($explodeFileName)-1]);
					}
					if(!$exists){
						$totalRowCount++;
						if(ceil($totalRowCount/PAGE_SIZE)==intval($_POST['pageNo'])){
							$start ='<a style="color:red">';$end='</a>';
							$orders[]=array(array($row['素材識別碼'],'text'),array($start.(($row['素材群組識別碼']==null)?'':$row['素材群組識別碼']).$end,'html')
							,array($start.$row['素材類型名稱'].$end,'html'),array($start.$row['素材名稱'].$end,'html')
							,array('','html')
							,($row['素材類型名稱']=='圖片')?array('<img class ="dgImg" src="'.$material_folder_url.$row['素材識別碼'].'?'.time().'" alt="'.$row['素材識別碼'].':'.$row['素材原始檔名'].'" style="max-width:100%;max-height:100%;border:0;">','html'):array('','text')
							,array($start.(($row['圖片素材寬度']==null)?'':$row['圖片素材寬度']).$end,'html'),array($start.(($row['圖片素材高度']==null)?'':$row['圖片素材高度']).$end,'html')
							,array($start.(($row['影片素材秒數']==null)?'':$row['影片素材秒數']).$end,'html')
							);
						}
					}
				}
			}
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('素材識別碼','素材群組識別碼','素材類型名稱','素材名稱','文字素材內容'
									,'圖片素材內容','圖片素材寬度','圖片素材高度','影片素材秒數')
							,'data'=>$orders,'sortable'=>array('素材識別碼','素材群組識別碼','素材類型名稱','素材名稱','文字素材內容'
									,'圖片素材寬度','圖片素材高度','影片素材秒數')),JSON_UNESCAPED_UNICODE);
		}
		else if($_POST['method']=='檢查版位素材設定'){
			//版位有效時間
			$sql = 'SELECT 版位.版位有效起始時間 版位有效起始時間,版位.版位有效結束時間 版位有效結束時間
					,版位類型.版位有效起始時間 版位類型有效起始時間,版位類型.版位有效結束時間 版位類型有效結束時間
					FROM 版位 版位,版位 版位類型
					WHERE 版位.上層版位識別碼=版位類型.版位識別碼 AND 版位.版位識別碼 =?';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$_POST['版位識別碼'])){
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row= $res->fetch_array();
			$feedback = array();
			$feedback['有效起始時間'] = ($row['版位有效起始時間']==null)?$row['版位類型有效起始時間']:$row['版位有效起始時間'];
			$feedback['有效結束時間'] = ($row['版位有效結束時間']==null)?$row['版位類型有效結束時間']:$row['版位有效結束時間'];
			
			//素材設定
			//版位類型
			$sql = '
			SELECT 
			素材順序
			,素材類型名稱
			,影片畫質識別碼
			,每小時最大素材筆數
			,每小時最大影片素材合計秒數
			,每則文字素材最大字數
			,每則圖片素材最大寬度
			,每則圖片素材最大高度
			,每則影片素材最大秒數
			FROM 
			版位,版位素材類型,素材類型
			WHERE 
			版位.上層版位識別碼=版位素材類型.版位識別碼 AND 素材類型.素材類型識別碼 = 版位素材類型.素材類型識別碼 
			AND 版位.版位識別碼 =?';
			if(!$PTresult=$my->getResultArray($sql,'i',$_POST['版位識別碼'])) $PTresult = array();
			//版位
			$sql = '
			SELECT 
			素材順序
			,素材類型名稱
			,影片畫質識別碼
			,每小時最大素材筆數
			,每小時最大影片素材合計秒數
			,每則文字素材最大字數
			,每則圖片素材最大寬度
			,每則圖片素材最大高度
			,每則影片素材最大秒數
			FROM 
			版位,版位素材類型,素材類型
			WHERE 
			版位.版位識別碼=版位素材類型.版位識別碼 AND 素材類型.素材類型識別碼 = 版位素材類型.素材類型識別碼
			AND 版位.版位識別碼 =?';
			if(!$Presult=$my->getResultArray($sql,'i',$_POST['版位識別碼'])) $Presult = array();
			
			for($i=0;$i<sizeof($PTresult);$i++){
				for($j=0;$j<sizeof($Presult);$j++){
					if($PTresult[$i]['素材順序']==$Presult[$j]['素材順序'])
						$PTresult[$i]=$Presult[$j];
				}
			}
			
			$feedback['素材設定'] = $PTresult;
			exit(json_encode(array("success"=>true,'result'=>$feedback),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method']=='取得素材群組'){
			$sql = 'SELECT 素材群組名稱,素材群組識別碼,素材群組有效開始時間,素材群組有效結束時間 FROM 素材群組 WHERE DISABLE_TIME IS NULL AND DELETED_TIME IS NULL
			ORDER BY 素材群組識別碼 DESC';
		
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$dateTime =  date("Y-m-d H:i:s");
			$materialGroup=array();
			while($row=$res->fetch_assoc()) {
				if($row['素材群組有效開始時間']!=null){
					if($row['素材群組有效開始時間']>$dateTime)
						continue;
				}
				if($row['素材群組有效結束時間']!=null){
					if($row['素材群組有效結束時間']<$dateTime)
						continue;
				}	
				$materialGroup[]=array('素材群組名稱'=>$row['素材群組名稱'],'素材群組識別碼'=>$row['素材群組識別碼']);
			}
			exit(json_encode($materialGroup,JSON_UNESCAPED_UNICODE));
		}
		//取得影片畫質選項
		else if($_POST['method']=='取得影片畫質'){
			$sql = 'SELECT 影片畫質識別碼,影片畫質名稱 FROM 影片畫質';
			if(!$result=$my->getResultArray($sql)) $result = array();
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		//取的產業類型
		else if($_POST['method']=='取得產業類型'){
			$sql = 'SELECT 產業類型名稱,產業類型識別碼,產業類型說明 FROM 產業類型 WHERE 上層產業類型識別碼 IS NULL';
		
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			$IndustryType=array();
			while($row=$res->fetch_assoc()) {
				$IndustryType[]=array('產業類型識別碼'=>$row['產業類型識別碼'],'產業類型說明'=>$row['產業類型說明'],'產業類型名稱'=>$row['產業類型名稱']);
			}
			exit(json_encode($IndustryType,JSON_UNESCAPED_UNICODE));
		}
		//取得素材群組資料表
		else if($_POST['method']=='DATAGRID素材群組資訊'){
			$searchBy='%'.$_POST['searchBy'].'%';
			if($_POST['searchBy']=='')
				$_POST['searchBy']='%';
			if(isset($_POST['素材群組識別碼'])){
				$mgId=$_POST['素材群組識別碼'];
				$searchBy='xxxx';
			}
			else
				$mgId=$_POST['searchBy'];
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$修改頁面 =(isset($_POST['修改素材群組頁面'])&&$_POST['修改素材群組頁面'])?true:false;
			//取得總筆數
			$sql="SELECT COUNT(1) COUNT
				FROM 素材群組
				WHERE (素材群組識別碼 LIKE ? OR 素材群組名稱 LIKE ? OR 素材群組說明 LIKE ?) AND DELETED_TIME IS NULL "
				.($修改頁面?"":" AND DISABLE_TIME IS NULL ");
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('iss',$mgId,$searchBy,$searchBy)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			//取得資料
			$sql="
				SELECT 素材群組識別碼,素材群組名稱,素材群組有效開始時間,素材群組有效結束時間,素材群組說明,DISABLE_TIME AS 是否隱藏
				FROM 素材群組 
				WHERE (素材群組識別碼 LIKE ? OR 素材群組名稱 LIKE ? OR 素材群組說明 LIKE ?) AND DELETED_TIME IS NULL "
				.($修改頁面?"":" AND DISABLE_TIME IS NULL ")
				."ORDER BY ".$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('issi',$mgId,$searchBy,$searchBy,$fromRowNo)) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}	
			if(!$result=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}					
			$a=array();	
			while($row = $result->fetch_assoc()){
				foreach($row as $key=>$value){
					if($value == null)
						$row[$key]='';
				}
				$temp = [[$row['素材群組識別碼']],[$row['素材群組名稱']],[$row['素材群組有效開始時間']],[$row['素材群組有效結束時間']],[$row['素材群組說明']]];
				if($修改頁面){
					if($row['是否隱藏']!='')
						$temp[]=['是'];
					else
						$temp[]=['否'];
				};
				array_push($a,$temp);
			}
			$header = array('素材群組識別碼','素材群組名稱','素材群組有效開始時間','素材群組有效結束時間','素材群組說明');
			if($修改頁面) $header[]='是否隱藏';
			exit(json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>$header
							,'data'=>$a,'sortable'=>$header),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method']=='autocompleteSearch'){
			$term = '%'.$_POST['term'].'%';
			$result=array();
			$sql="SELECT DISTINCT ".$_POST['column']." as value,".$_POST['column']." as id FROM 素材 WHERE ".$_POST['column']." LIKE ?";
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('s',$term)) {
				$logger->error('無法綁定參數，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}		
			while($row = $res->fetch_array()){
				$result[] = $row;
			}
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
	}
	exit ;
	
?>