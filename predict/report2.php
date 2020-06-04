<?php
	header("X-Frame-Options: SAMEORIGIN");
	include('../tool/auth/auth.php');
	require '../tool/OutputExcel.php';
	require_once '../tool/phpExtendFunction.php';
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		exit('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
	}
	if(!$my->set_charset('utf8')) {
		exit('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
	}
	if(isset($_POST["取得曝光數資料"])){
		$ch_list="";//紀錄頻道選擇query
		$adType_list="";//紀錄廣告分類query
		$ch_where="";//紀錄選擇頻道query的where部分(頻道選擇query+廣告分類query)
		$date_where=" AND 預測_頻道表.生效日期=? AND 平台識別碼 LIKE ? ";//紀錄選擇日期query的where部分
		$param_type='ss';//生效日期與平台識別碼
		$actualDates=array();
		
		if($_POST['選擇平台']=='OMP')
			$選擇平台='2';
		else if($_POST['選擇平台']=='IAP')
			$選擇平台='3';
		else if($_POST['選擇平台']=='VSM')
			$選擇平台='1';
		else
			$選擇平台='%';
			
		if(isset($_POST["實際日期期間"])){
			$param_type.='ss';
			$date_where.=" AND 日期 BETWEEN ? AND ? ";
			$actualDates = phpExtendFunction::getDatesByPeriod($_POST["實際日期期間"][0],$_POST["實際日期期間"][1],"Ymd");
		}
		else if(isset($_POST["實際日期"])){
			$n = count($_POST["實際日期"]);
			$param_type.=str_repeat("s", $n);
			if($n>0)
				$date_where.="AND 日期 IN (?".str_repeat(",?", $n-1).") ";
			$actualDates = $_POST["實際日期"];
		}
		
		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			$param_type.=str_repeat("i", $n);
			if($n>0)
				$ch_list.=" 預測_實際曝光數.頻道號碼 IN(?".str_repeat(",?", $n-1).") ";
		}
		
		if(isset($_POST["廣告分類"])){
			$n = count($_POST["廣告分類"]);
			$param_type.=str_repeat("s", $n);
			if($n>0)
				$adType_list.=" 廣告分類 IN(?".str_repeat(",?", $n-1).") ";
		}
		
		//合併頻道選擇和廣告分類選擇
		if($ch_list!=""&&$adType_list!="")
			$ch_where=" AND (".$ch_list."OR".$adType_list.")";
		else if($ch_list!="")
			$ch_where=" AND".$ch_list;
		else if($adType_list!="")
			$ch_where=" AND".$adType_list;
		
		$a_params = array();
		$a_params[] = &$param_type;
		$a_params[] = &$_POST['生效日期'];
		$a_params[] = &$選擇平台;
		if(isset($_POST["實際日期期間"])){
			$a_params[] = &$_POST["實際日期期間"][0];
			$a_params[] = &$_POST["實際日期期間"][1];
		}
		else if(isset($_POST["實際日期"])){
			$n = count($_POST["實際日期"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["實際日期"][$i];
			}
		}
		
		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["頻道列表"][$i];
			}
		}
		
		if(isset($_POST["廣告分類"])){
			$n = count($_POST["廣告分類"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["廣告分類"][$i];
			}
		}
		
		$result=array();
		$sql='SELECT 預測_實際曝光數.頻道號碼,預測_頻道表.頻道名稱,廣告分類,平台識別碼,日期,曝光數0,曝光數1,曝光數2,曝光數3,曝光數4,曝光數5,曝光數6,曝光數7,曝光數8,曝光數9,曝光數10,曝光數11,曝光數12,曝光數13,曝光數14,曝光數15,曝光數16,曝光數17,曝光數18,曝光數19,曝光數20,曝光數21,曝光數22,曝光數23 
			FROM 預測_實際曝光數, 預測_頻道表 ,預測_廣告分類
			WHERE 預測_實際曝光數.頻道號碼=預測_頻道表.頻道號碼 AND 預測_頻道表.頻道號碼=預測_廣告分類.頻道號碼 '.$date_where.$ch_where.'ORDER BY 預測_實際曝光數.頻道號碼,預測_頻道表.頻道名稱,平台識別碼,日期';

		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		if(!call_user_func_array(array($stmt, 'bind_param'), $a_params)){
			exit('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		$stmt->bind_result($頻道號碼,$頻道名稱,$廣告分類,$平台識別碼,$日期,$曝光數0,$曝光數1,$曝光數2,$曝光數3,$曝光數4,$曝光數5,$曝光數6,$曝光數7,$曝光數8,$曝光數9,$曝光數10,$曝光數11,$曝光數12,$曝光數13,$曝光數14,$曝光數15,$曝光數16,$曝光數17,$曝光數18,$曝光數19,$曝光數20,$曝光數21,$曝光數22,$曝光數23);
		while($stmt->fetch()) {
			/*if($平台識別碼==2)
				$平台識別碼='OMP';
			else if($平台識別碼==3)
				$平台識別碼='IAP';*/
			$曝光數ARRAY= array($曝光數0,$曝光數1,$曝光數2,$曝光數3,$曝光數4,$曝光數5,$曝光數6,$曝光數7,$曝光數8,$曝光數9,$曝光數10,$曝光數11,$曝光數12,$曝光數13,$曝光數14,$曝光數15,$曝光數16,$曝光數17,$曝光數18,$曝光數19,$曝光數20,$曝光數21,$曝光數22,$曝光數23);
			$平台識別碼=$_POST['選擇平台'];
			for($i = 0;$i<24;$i++){
				if(isset($result[$頻道號碼][$頻道名稱][$廣告分類][$平台識別碼][strval($i)][$日期]))
					$result[$頻道號碼][$頻道名稱][$廣告分類][$平台識別碼][strval($i)][$日期]+=$曝光數ARRAY[$i];
				else
					$result[$頻道號碼][$頻道名稱][$廣告分類][$平台識別碼][strval($i)][$日期]=$曝光數ARRAY[$i];
			}
		}
		
		$ch_list="";//紀錄頻道選擇query
		$adType_list="";//紀錄廣告分類query
		$ch_where="";//紀錄選擇頻道query的where部分(頻道選擇query+廣告分類query)
		$date_where=" AND 預測_頻道表.生效日期=? AND 平台識別碼 LIKE ? ";//紀錄選擇日期query的where部分
		$param_type='ss';//生效日期與平台識別碼
		$predictDates=array();
		
		if($_POST['選擇平台']=='OMP')
			$選擇平台='2';
		else if($_POST['選擇平台']=='IAP')
			$選擇平台='3';
		else
			$選擇平台='%';
		
		if(isset($_POST["預測日期期間"])){
			$param_type.='ss';
			$date_where.=" AND 日期 BETWEEN ? AND ? ";
			$predictDates = phpExtendFunction::getDatesByPeriod($_POST["預測日期期間"][0],$_POST["預測日期期間"][1],"Ymd");
		}
		else if(isset($_POST["預測日期"])){
			$n = count($_POST["預測日期"]);
			$param_type.=str_repeat("s", $n);
			if($n>0)
				$date_where.=" AND 日期 IN (?".str_repeat(",?", $n-1).") ";
			
			$predictDates = $_POST["預測日期"];
		}
		
		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			$param_type.=str_repeat("i", $n);
			if($n>0)
				$ch_list.=" 預測_預測曝光數.頻道號碼 IN(?".str_repeat(",?", $n-1).") ";
		}
		
		if(isset($_POST["廣告分類"])){
			$n = count($_POST["廣告分類"]);
			$param_type.=str_repeat("s", $n);
			if($n>0)
				$adType_list.=" 廣告分類 IN(?".str_repeat(",?", $n-1).") ";
		}
		
		//合併頻道選擇和廣告分類選擇
		if($ch_list!=""&&$adType_list!="")
			$ch_where=" AND (".$ch_list."OR".$adType_list.")";
		else if($ch_list!="")
			$ch_where=" AND".$ch_list;
		else if($adType_list!="")
			$ch_where=" AND".$adType_list;
		
		$a_params = array();
		$a_params[] = &$param_type;
		$a_params[] = &$_POST['生效日期'];
		$a_params[] = &$選擇平台;
			
		if(isset($_POST["預測日期期間"])){
			$a_params[] = &$_POST["預測日期期間"][0];
			$a_params[] = &$_POST["預測日期期間"][1];
		}
		else if(isset($_POST["預測日期"])){
			$n = count($_POST["預測日期"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["預測日期"][$i];
			}
		}

		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["頻道列表"][$i];
			}
		}
		
		if(isset($_POST["廣告分類"])){
			$n = count($_POST["廣告分類"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["廣告分類"][$i];
			}
		}
		
		$result_predict=array();
		$sql='SELECT 預測_預測曝光數.頻道號碼,預測_頻道表.頻道名稱,廣告分類,平台識別碼,日期,預測數0,預測數1,預測數2,預測數3,預測數4,預測數5,預測數6,預測數7,預測數8,預測數9,預測數10,預測數11,預測數12,預測數13,預測數14,預測數15,預測數16,預測數17,預測數18,預測數19,預測數20,預測數21,預測數22,預測數23 
			FROM 預測_預測曝光數,預測_頻道表,預測_廣告分類
			WHERE 預測_預測曝光數.頻道號碼=預測_頻道表.頻道號碼 AND 預測_頻道表.頻道號碼=預測_廣告分類.頻道號碼 '.$date_where.$ch_where.'ORDER BY 預測_預測曝光數.頻道號碼,預測_頻道表.頻道名稱,平台識別碼,日期';
		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$sql.')。');
		}
		if(!call_user_func_array(array($stmt, 'bind_param'), $a_params)){
			exit('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}	
		$stmt->bind_result($頻道號碼,$頻道名稱,$廣告分類,$平台識別碼,$日期,$預測數0,$預測數1,$預測數2,$預測數3,$預測數4,$預測數5,$預測數6,$預測數7,$預測數8,$預測數9,$預測數10,$預測數11,$預測數12,$預測數13,$預測數14,$預測數15,$預測數16,$預測數17,$預測數18,$預測數19,$預測數20,$預測數21,$預測數22,$預測數23);
		while($stmt->fetch()) {
			/*if($平台識別碼==2)
				$平台識別碼='OMP';
			else if($平台識別碼==3)
				$平台識別碼='IAP';*/
			$預測數ARRAY= array($預測數0,$預測數1,$預測數2,$預測數3,$預測數4,$預測數5,$預測數6,$預測數7,$預測數8,$預測數9,$預測數10,$預測數11,$預測數12,$預測數13,$預測數14,$預測數15,$預測數16,$預測數17,$預測數18,$預測數19,$預測數20,$預測數21,$預測數22,$預測數23);
			$平台識別碼=$_POST['選擇平台'];
			for($i = 0;$i<24;$i++){
				if(isset($result_predict[$頻道號碼][$頻道名稱][$廣告分類][$平台識別碼][strval($i)][$日期]))
					$result_predict[$頻道號碼][$頻道名稱][$廣告分類][$平台識別碼][strval($i)][$日期]+=$預測數ARRAY[$i];
				else
					$result_predict[$頻道號碼][$頻道名稱][$廣告分類][$平台識別碼][strval($i)][$日期]=$預測數ARRAY[$i];
			}
		}
		
		if(isset($_POST['匯出報表'])){
			$fileName =uniqid();
			OutputExcel::outputAll_sheet('export/'.$fileName,array('實際'=>getExcelArray($_POST['sheetName1'],$result,false,$actualDates),
																	'預測'=>getExcelArray($_POST['sheetName2'],$result_predict,true,$predictDates)));
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			exit(json_encode(array('success'=>true,'url'=>$protocol.$_SERVER ['HTTP_HOST'].str_replace("report2.php",'export/'.$fileName.".xls",$_SERVER['PHP_SELF'])),JSON_UNESCAPED_UNICODE));
		}
		else{
			$feedBack=array($result,$result_predict);
			exit(json_encode($feedBack,JSON_UNESCAPED_UNICODE));
		}
	}
	//頻道資料表生效日期
	else if (isset($_POST['取得生效日期'])){
		$sql='SELECT 生效日期 FROM 預測_頻道表 GROUP BY 生效日期';
		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		$feedback=array();
		while($row=$res->fetch_array()){
			array_push($feedback,$row["生效日期"]);
		}
		exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
	}
	//廣告類別
	else if (isset($_POST['取得頻道資料'])){
		$sql='SELECT 頻道號碼,頻道名稱 FROM 預測_頻道表 WHERE 生效日期=?';
		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		if(!$stmt->bind_param('s',$_POST['取得頻道資料'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		$頻道=array();
		while($row=$res->fetch_array()){
			array_push($頻道,$row["頻道號碼"].":".$row["頻道名稱"]);
		}
		
		$sql='SELECT 廣告分類 FROM 預測_頻道表,預測_廣告分類 WHERE 生效日期=? AND 預測_頻道表.頻道號碼=預測_廣告分類.頻道號碼 GROUP BY 廣告分類';
		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		if(!$stmt->bind_param('s',$_POST['取得頻道資料'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
		}
		$廣告分類=array();
		while($row=$res->fetch_array()){
			array_push($廣告分類,$row["廣告分類"]);
		}
		$stmt->close();
		exit(json_encode(array('頻道'=>$頻道,'廣告分類'=>$廣告分類),JSON_UNESCAPED_UNICODE));
	}
	
	//產生excel檔案
	function getExcelArray ($sheetName,$result,$predict,$dates){
		$forExcel = array();
		//累加全部頻道曝光數用
		$allSum	= ['全頻道','','',$_POST['選擇平台'],'全時段'];
		//header
		$forExcel[]=['頻道號碼','頻道名稱','廣告分類','平台','時段'];
		/*foreach($result as $頻道號碼=>$v1){
		foreach($v1 as $頻道名稱=>$v2){
		foreach($v2 as $廣告分類=>$v3){
		foreach($v3 as $平台識別碼=>$v4){
		foreach($v4 as $時段=>$v5){
		foreach($v5 as $日期=>$v6){
			$forExcel[0][]= $日期;
			$allSum[]=0;
		}
		break;}break;}break;}break;}break;}*/
		
		foreach($dates as $date){
			$forExcel[0][]= $date;
		}
		
		$forExcel[0][]='區間加總';
		if($predict)
			$forExcel[0][]='預估值';
			
		array_unshift($forExcel,array($sheetName));
		foreach($result as $頻道號碼=>$v1){
			$c1 = $頻道號碼;
			$temp = array();
		foreach($v1 as $頻道名稱=>$v2){
			$c2 = $頻道名稱;
		foreach($v2 as $廣告分類=>$v3){
			$c3 = $廣告分類;
			foreach($v3 as $平台識別碼=>$v4){
				foreach($v4 as $時段=>$v5){
					$subRow = ['','','','',$時段];
					$localSum=0;
					//依照日期建立預設array
					$dateCount = array();
					foreach($dates as $date){
						$dateCount[$date]= 0;
					}
					//塞入資料到預設array
					foreach($v5 as $日期=>$v6){
						//$subRow[]=$v6;
						$dateCount[$日期] = $v6;
						$localSum+=$v6;
					}
					//從預設array塞入資料到row中
					foreach($dateCount as $count){
						$subRow[]=$count;
					}
					
					$subRow[]=$localSum;
					$temp[]=$subRow;
				}
			}
		}}
		//累加頻道的曝光數
		$channelSum = array_merge([$c1,$c2,$c3,$_POST['選擇平台'],'全時段'],array_fill(0, count($temp[0])-5, 0));
		for($i = 0;$i<count($temp);$i++){
			//日期col才有曝光數
			for($j = 5;$j<count($temp[$i]);$j++){
				$channelSum[$j]+=$temp[$i][$j];
				if(!isset($allSum[$j]))
				 $allSum[$j]=0;
				$allSum[$j]+=$temp[$i][$j];
			}
		}
		if($_POST['報表格式']=='分時')
			array_unshift($temp,$channelSum);
		else if ($_POST['報表格式']=='分日')
			$temp=array($channelSum);
		$forExcel=array_merge($forExcel,$temp);
		}
		$forExcel[]=$allSum;
		if($predict)
			for($i =2;$i<count($forExcel);$i++){
				$sum=0;
				for($j =5;$j<count($forExcel[$i]);$j++){
					$sum+=$forExcel[$i][$j];
				}
				$forExcel[$i][]=(floor($sum/2000000)==0)?'全':floor($sum/2000000);
			}
			
		return $forExcel;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
	<script type="text/javascript" src="tool/jquery.autocomplete.multiselect.js"></script>
	<script type="text/javascript" src="tool/jquery-ui.multidatespicker.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>predict/css/normalize.min.css">
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>predict/css/main.css">
	<style id="antiClickjack">body{display:none !important;}</style>
	<style type="text/css">
		body{
			padding-top:15px; 
			padding-left:10px; 
			padding-right:10px; 
			padding-bottom:30px; 
			font-size:10px;
		}
		.ui-datepicker .ui-datepicker-calendar .ui-state-highlight a {
			background: #743620 none;
			color: white;
		}
		.floatLeft{
			float:left;
			margin-top:10px;
			margin-lift:10px;
			margin-right:10px;
		}
		fieldset {
			float:left;
			margin-lift:10px;
			margin-right:10px;
			padding-top:10px; 
			padding-left:10px; 
			padding-right:10px; 
			padding-bottom:10px; 
			height:310px;
			border: 1px solid #708090;
		}
		fieldset legend {
			color:#000000;
			margin: 5px 5px;
			text-align: left;
		}
		
		.cssLayer{
			height: 375px;
			min-width: 600px;
			margin-top: 3px;
			border: 1px solid #B0E0E6;
			padding: 0px 20px;
			text-align: left; 
			width: 95%px;
			min-width: 1050px;
			-webkit-border-radius: 8px;
			-moz-border-radius: 8px;
			border-radius: 8px;
			-webkit-box-shadow: #666 0px 2px 3px;
			-moz-box-shadow: #666 0px 2px 3px;
			box-shadow: #666 0px 2px 3px;
			background: #fcfcfc;
			background: -webkit-gradient(linear, 0 0, 0 bottom, from(#fcfcfc), to(#B0E0E6));
			background: -moz-linear-gradient(#fcfcfc, #B0E0E6);
			-pie-background: linear-gradient(#fcfcfc, #B0E0E6);
			behavior: url(tool/PIE.htc);
		}		
		.tableDiv {
			overflow-x:scroll; 
            overflow-y:visible;
		}
		.tableDiv table {
			border-collapse:separate;
			//border-collapse: collapse;
			width:100%
		}
		.tableDiv td{
			min-width: 60px;
		}
		#adCheck{
			margin-top:5px;
			margin-bottom:5px;
			padding-top:3px; 
			padding-left:2px; 
			padding-right:2px; 
			padding-bottom:3px; 
			overflow-x:hidden;
			overflow-y:scroll;
			height:200px;
			border: 1px solid #aaaaaa;
		}
		#預測資料表,#預測資料表 * * *{
			border: 1px solid #5599FF
		}
		#實際資料表,#實際資料表 * * *{
			border: 1px solid #FFBB66
		}
		
	</style>
</head>
<body>
	<div id ="optionLayer" class= "cssLayer" >
		<p id = "optionTitle" style="font-weight:bold" width="100%">資料設定選項</p>
		<div id ="optionPannel">		
		<fieldset>
			<legend>頻道選擇</legend>
			<div>選擇頻道表日期:<select id = '頻道日期'><select></div>
			<div class = "floatLeft">選擇頻道名稱:<br>(輸入頻道號碼或關鍵字)<input id="tags"  width="100%"></input></div>
			<div class = "floatLeft"><a href="category.php" style="text-decoration:underline; color:blue;">編輯廣告分類表</a><br>依廣告分類選擇頻道:<div id ="adCheck"></div><button id="allCheck">全部</button><button id="allUnCheck">全取消</button></div>
		</fieldset>
		
		<fieldset>		<legend>實際曝光資料顯示設定(擇一)</legend>
		<div id="tabs1">
			<ul>
				<li><a href="#tabs1-1">實際曝光資料日期(複選)</a></li>
				<li><a href="#tabs1-2">實際曝光資料日期(起迄)</a></li>
			</ul>
			<div  id = 'tabs1-1'><input type="hidden" id = "realAltField"></input><div id = "realDate" type="text" value = ""></div></div>
			<div  id = 'tabs1-2'><br><div id ="realRange">開始日期<br><input type = "text" id = "realStart"></input> <br><br>結束日期<br><input type = "text" id = "realEnd"></input></div></div>
		</div>
		</fieldset>
		
		<fieldset><legend>預測曝光資料顯示設定(擇一)</legend>
		<div id = 'tabs2'>
			<ul>
				<li><a href="#tabs2-1">預測曝光資料日期(複選)</a></li>
				<li><a href="#tabs2-2">預測曝光資料日期(起迄)</a></li>
			</ul>
			<div id ='tabs2-1'><input type="hidden" id = "predictAltField"></input><div id = "predictDate" type="text" value = ""></div></div>
			<div id ='tabs2-2'><div id ="predictRange">開始日期<br><input type = "text" id = "predictStart"></input> <br><br>結束日期<br><input type = "text" id = "predictEnd"></input></div></div>
		</div>
		</fieldset>
		
		<fieldset>
			<legend>報表選項</legend>
			<div>選擇平台:<br>
			<select id = '選擇平台'>
				<option value = '全平台'>ALL</option>
				<option value = 'OMP'>OMP</option>
				<option value = 'IAP'>IAP</option>
				<option value = 'VSM'>單一平台</option>
			<select>
			</div>
			<br>
			<div>報表格式:<br>
			<select id = '報表格式'>
				<option value = '分日'>分日</option>
				<option value = '分時'>分時</option>
			<select>
			</div>
		</fieldset>
		
		<button id = "fetch">查詢</button><br><br>
		<button id = "export">匯出</button>
		</div>
	</div>
	<br>
	<button id ='produceOrder'>產生託播單</button>
	<div id = "預測"><img src="tool/pic/list_minus.gif" id="預測icon">預測曝光資料 <a id = 'message1'></a><div id = "預測資料表" class = "tableDiv"></div><br></div>
	<div id = "實際"><img src="tool/pic/list_minus.gif" id="實際icon">實際曝光資料 <a id = 'message2'></a><div id = "實際資料表" class = "tableDiv"></div><br></div>

	<div id="dialog_form"><div>
		<div id="loading">資料讀取中.....請稍候</div>
		<a id ='downloadLink' href="" target="_blank" title="下載檔案" style="text-decoration:underline; color:blue;"></a>
	</div></div>
	<div id="newOrderdialog_form" width=800 height=600><iframe id="newOrderdialog_iframe" width="100%" height="100%"></iframe></div>
	
	<script>
	if (self.location.hostname === top.location.hostname) {
		var antiClickjack = document.getElementById("antiClickjack");
		antiClickjack.parentNode.removeChild(antiClickjack);
	} else {
		throw new Error("拒絕存取!");
		//top.location = self.location;
	}
	</script>
	
	<script type="text/javascript">
			var orderObject={};
	$(function() {
		$("#實際,#預測,#loading,#downloadLink,#produceOrder").hide();
		//dialog設定
		$( "#dialog_form" ).dialog(
			{
			autoOpen: false,
			width: '80%',
			height: '80%',
			modal: true
			});
		$( "#newOrderdialog_form" ).dialog(
			{
			autoOpen: false,
			width: 800,
			height: 600,
			modal: true
		});
		//控制選向板面的縮放設定
		var optionLayerSlided=false;
		function optionLaerSlideUP(){
			$("#optionLayer").animate( { height:"40px" }, { queue:false, duration:500 });
			$("#optionPannel").slideUp(500);
			optionLayerSlided=true;
		}
		function optionLaerSlideDown(){
			$("#optionLayer").animate( { height:"400px" }, { queue:false, duration:500 });
			$("#optionPannel").slideDown(500);
			optionLayerSlided=false;
		}
		$("#optionTitle").click(function(){
			if(optionLayerSlided)
				optionLaerSlideDown();
			else
				optionLaerSlideUP();
		}).css("cursor","pointer");
		
		$( "#realDate" ).multiDatesPicker({dateFormat: 'yymmdd',altField: '#realAltField',
			onSelect: function(dateText) {
				$("#realStart,#realEnd").val("");
			}
		});
		$( "#predictDate" ).multiDatesPicker({dateFormat: 'yymmdd',altField: '#predictAltField',
			onSelect: function(dateText) {
				$("#predictStart,#predictEnd").val("");
			}
		});
		
		$("#realStart,#realEnd").datepicker({dateFormat: 'yymmdd',
			onSelect: function(dateText) {
				$("#realAltField").val("");
				$( "#realDate" ).multiDatesPicker('resetDates')
			}
		});
		
		$("#predictStart,#predictEnd").datepicker({dateFormat: 'yymmdd',
			onSelect: function(dateText) {
				$("#predictAltField").val("");
				$( "#predictDate" ).multiDatesPicker('resetDates')
			}
		});

		$( "#tabs1,#tabs2" ).tabs();
		
		//資料表隱藏/顯示
		$("#實際icon").css("cursor","pointer").click(function(){
			if($("#實際資料表").is(":visible")){
				$("#實際資料表").slideUp(300);
				$(this).prop("src","tool/pic/list_plus.gif");
			}else{
				$("#實際資料表").slideDown(300);
				$(this).prop("src","tool/pic/list_minus.gif");
			}
		});
		
		$("#預測icon").css("cursor","pointer").click(function(){
			if($("#預測資料表").is(":visible")){
				$("#預測資料表").slideUp(300);
				$(this).prop("src","tool/pic/list_plus.gif");
			}else{
				$("#預測資料表").slideDown(300);
				$(this).prop("src","tool/pic/list_minus.gif");
			}
		});
		
		//頻道資料表時間
		$.post('?',{取得生效日期:true}
			,function(json){
				for(var i in json){
					$(document.createElement('option')).text(json[i]).val(json[i]).appendTo($('#頻道日期'));
				}
				$('#頻道日期').val(json[json.length-1]);
				setChanelInfo();
			}
			,'json'
		);
		$('#頻道日期').change(function(){
			setChanelInfo();
		});
		//設定頻道資料
		function setChanelInfo(){
			$.post('?',{取得頻道資料:$('#頻道日期').val()}
				,function(json){
					//頻道多選設定
					var availableTags = json.頻道;
					$( "#tags" ).autocomplete({
						source: availableTags,
						multiselect: true
					});
					
					//廣告分類選擇
					$("#adCheck").empty();
					var adType=json.廣告分類;
					for(var i =0;i<adType.length;i++){
						$('<input type="checkbox" name="adCheckBox" value="'+adType[i]+'"/><a>'+adType[i]+'</a><br>').appendTo("#adCheck");
					}
				}
				,'json'
			);
		}
		
		//全部
		$("#allCheck").click(function(){
			$("input[name='adCheckBox']").each(function() {
				$(this).prop("checked", true);
			}); 
		});
		//全取消
		$("#allUnCheck").click(function(){
			$("input[name='adCheckBox']").each(function() {
				$(this).prop("checked", false);
			}); 
		});
		
		//查詢按鈕
		$("#fetch").click(function(){
			optionLaerSlideUP();
					
			var byPost = getByPost();
			$("#loading").show();$("#downloadLink").hide();
			$('#dialog_form').dialog({height:100, width:150,title:"讀取資料"}).dialog('open');
			$("#實際資料表,#預測資料表").empty()
			$("#實際,#預測,#produceOrder").hide();
			$.post('?'
				,byPost
				,function(data){
					//創造表單
					uncollapseRows = {'實際資料表':0,'預測資料表':0};
					if(data[0].length!=0){
						$("#實際").show();
						creatTable(data[0],"#實際資料表");
						if(data[1].length!=0){
							$("#實際資料表").slideUp(300);
							$("#實際icon").prop("src","tool/pic/list_plus.gif");
						}
						else{
							$("#實際資料表").slideDown(300);
							$("#實際icon").prop("src","tool/pic/list_minus.gif");
						}
					}
					if(data[1].length!=0){
						$("#預測,#produceOrder").show();
						creatTable(data[1],"#預測資料表");
						$("#預測資料表").slideDown(300);
						$("#預測icon").prop("src","tool/pic/list_minus.gif");
					}
					$('#dialog_form').dialog({height:100, width:150}).dialog('close');
					$("#loading").hide();
					
				}
				,'json'
			);
		});
		
		function creatTable(data,id){
			var divId = id;
			var hBgColor="#F4A460",
			evenTdColor="#FAFAD2",
			altFieldId="#realAltField",
			startDateId="#realStart",
			endDateId="#realEnd";
				
			if(divId=="#預測資料表"){
				hBgColor="#4169E1";
				evenTdColor="#CCDDFF";
				altFieldId="#predictAltField";
				startDateId="#predictStart";
				endDateId="#predictEnd";
			}
			var m_table = $(document.createElement('table'));	//建立table主體
			var m_thead = $(document.createElement('thead'));	//建立header
			var m_tbody= $(document.createElement('tbody'));	//建立body
			m_table.appendTo($(divId));
			m_thead.appendTo(m_table);
			m_tbody.appendTo(m_table);
						
			//取得實際資料的日期
			var selectedDate = [];
			if($(altFieldId).val()!="")//單選日期
				selectedDate = $(altFieldId).val().split(", ");
			else{//日期區間
				var sd = new Date($(startDateId).val().substr(0,4),($(startDateId).val().substr(4,2)-1),$(startDateId).val().substr(6,2));
				var ed = new Date($(endDateId).val().substr(0,4),($(endDateId).val().substr(4,2)-1),$(endDateId).val().substr(6,2));
				do{
					selectedDate.push(pad(sd.getFullYear(),4)+(pad(sd.getMonth()+1,2))+pad(sd.getDate(),2));
					sd.setDate(sd.getDate() + 1);
				}while(!(sd>ed));
			}
			
			//header
			var tr = $(document.createElement('tr'))
			$(document.createElement('th')).text("頻道號碼").prop("rowspan",2).appendTo(tr);
			$(document.createElement('th')).text("頻道名稱").prop("rowspan",2).appendTo(tr);
			$(document.createElement('th')).text("廣告分類").prop("rowspan",2).appendTo(tr);
			$(document.createElement('th')).text("平台").addClass('generalInfo').prop("rowspan",2).appendTo(tr);
			$(document.createElement('th')).text("時段").addClass('generalInfo').prop("rowspan",2).appendTo(tr);
			if(divId=="#實際資料表")
				$(document.createElement('th')).text("曝光數").prop('colspan',selectedDate.length+1).appendTo(tr);
			if(divId=="#預測資料表"){
				$(document.createElement('th')).text("預測曝光數").prop('colspan',selectedDate.length+1).appendTo(tr);
				$(document.createElement('th')).text("預估值").appendTo(tr);
			}
			tr.css({"background-color": hBgColor,'color': 'white'}).appendTo(m_thead);
			
			tr = $(document.createElement('tr'))
			for(var i =0;i<selectedDate.length;i++)
				$(document.createElement('th')).text(selectedDate[i]).appendTo(tr);
			$(document.createElement('th')).text("區間加總").appendTo(tr);
			if(divId=="#預測資料表")
				$(document.createElement('th')).appendTo(tr);
			
			tr.css({"background-color": hBgColor,'color': 'white'}).appendTo(m_thead)
			
			//body
			//計算頻道加總用
			var channelSum=[];
			for(var i =0;i<selectedDate.length;i++)	channelSum[i]=0;
			
			$.each(data,function(頻道號碼,value1){
			$.each(value1,function(頻道名稱,value2){
			$.each(value2,function(廣告分類,value3){
				var dateSum=[];
				for(var i =0;i<selectedDate.length;i++)
					dateSum[i]=0;
				//單一頻道時段總合列
				var tr_total = $(document.createElement('tr'))
				tr_total.attr("name",頻道號碼).addClass("summaries").appendTo(m_tbody);
				$(document.createElement('td')).text(頻道號碼).appendTo(tr_total).addClass("49tb").prop("rowspan",1);
				$(document.createElement('td')).text(頻道名稱).appendTo(tr_total).addClass("49tb").prop("rowspan",1);
				$(document.createElement('td')).text(廣告分類).appendTo(tr_total).addClass("49tb").prop("rowspan",1);
				$(document.createElement('td')).addClass('generalInfo').addClass("49tb").text($('#選擇平台').val()).appendTo(tr_total);
				$(document.createElement('td')).addClass('generalInfo').text("全時段總和").appendTo(tr_total);
				
				$.each(value3,function(平台識別碼,value4){
					var tr = $(document.createElement('tr'));
					$.each(value4,function(時段,value5){
						//本列的資料累加
						var sum=0;
						for(var i =0;i<selectedDate.length;i++){
							var num = (typeof(value5[selectedDate[i]])=='undefined')?0:value5[selectedDate[i]];
							sum+=num;
							dateSum[i]+=num;
							channelSum[i]+=num;
						}
						$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
						if(divId=="#預測資料表"){
							var floor = Math.floor(sum/2000000);
							(floor==0)?$(document.createElement('td')).text("全").appendTo(tr):$(document.createElement('td')).text(formatNumber(String(floor))).appendTo(tr);
						}
					})
				})
				//單一頻道總和列的區間總和
				var sum=0;
				for(var i =0;i<selectedDate.length;i++){
					$(document.createElement('td')).text(formatNumber(String(dateSum[i]))).appendTo(tr_total);
					sum+=dateSum[i];
				}
				$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr_total);
				if(divId=="#預測資料表"){
					var floor = Math.floor(sum/2000000);
					(floor==0)?$(document.createElement('td')).text("全").appendTo(tr_total):$(document.createElement('td')).text(formatNumber(String(floor))).appendTo(tr_total);
				}
				tr_total.css({"font-weight": "bold"});
			})
			})
			});
			//全頻道總和列
			var tr_ch = $(document.createElement('tr'))
			tr_ch.appendTo(m_tbody).css({"font-weight": "bold"}).addClass('allChSum').css({"background-color": hBgColor,'color': 'white'});
			$(document.createElement('td')).text("全頻道").appendTo(tr_ch);
			$(document.createElement('td')).appendTo(tr_ch);
			$(document.createElement('td')).addClass('generalInfo').appendTo(tr_ch);
			$(document.createElement('td')).addClass('generalInfo').appendTo(tr_ch);
			$(document.createElement('td')).appendTo(tr_ch);
			var sum=0;
			for(var i =0;i<selectedDate.length;i++){
				$(document.createElement('td')).text(formatNumber(String(channelSum[i]))).appendTo(tr_ch);
				sum+=channelSum[i];
			}
			$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr_ch);
			if(divId=="#預測資料表"){
				var floor = Math.floor(sum/2000000);
				(floor==0)?$(document.createElement('td')).text("全").appendTo(tr_ch):$(document.createElement('td')).text(formatNumber(String(floor))).appendTo(tr_ch);
			}
			
			//點擊縮放設定
			$(divId+'>table>tbody>.summaries').css("cursor","pointer").click(
				function(){
					collapse($(this));
				}
			);
			
			//縮放row的function
			collapse = function(row){
				var tbody = row.parent();
				var divId = tbody.parent().parent().attr('id');
				var name = row.attr('name');
				var tr49 =$('#'+divId+'>table>tbody>.summaries[name="'+name+'"]>.49tb');
				var preTr = row;
				//展開
				if(tr49.prop("rowspan")==1){
					if(uncollapseRows[divId]==0)
						$('#'+divId).find('.generalInfo').show();
					uncollapseRows[divId]++;
					/*if($('#選擇平台').val() == 'OMP+IAP')
							tr49.prop("rowspan",49);
						else*/
					tr49.prop("rowspan",25);
					if($('#'+divId+'>table>tbody>.detial[name="'+name+'"]').length){
						$('#'+divId+'>table>tbody>.detial[name="'+name+'"]').show();
					}
					else{
						$.each(data[name],function(頻道名稱,value2){
						$.each(value2,function(廣告分類,value3){
						$.each(value3,function(平台識別碼,value4){
							var tr = $(document.createElement('tr')).attr("name",name).addClass("detial");
							preTr.after(tr);
							preTr=tr;
							//$(document.createElement('td')).text(平台識別碼).appendTo(tr).prop("rowspan",24);
							$.each(value4,function(時段,value5){
								//加入本列的資料
								if(時段!=0){
									tr = $(document.createElement('tr')).attr("name",name).addClass("detial");
									preTr.after(tr);
									preTr=tr;

								}
								preTr.after(tr);
								preTr=tr;
								//增加時段詳細資料
								$(document.createElement('td')).text(pad(時段,2)+":00~"+pad(時段+1,2)+":00").appendTo(tr);
								var sum=0;
								//增加曝光數詳細資料
								for(var i =0;i<selectedDate.length;i++){
									var num = (typeof(value5[selectedDate[i]])=='undefined')?0:value5[selectedDate[i]];
									if(divId=="預測資料表"){
										var cbox = $(document.createElement('input')).attr({'type':'checkbox','name':'toOrder'}).val(selectedDate[i]+'_'+時段);
										$(document.createElement('td')).append(cbox).append(formatNumber(String(num))).appendTo(tr);
									}
									else
										$(document.createElement('td')).append(formatNumber(String(num))).appendTo(tr);
									sum+=num;
								}
								$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
								if(divId=="預測資料表"){
									var floor = Math.floor(sum/2000000);
									(floor==0)?$(document.createElement('td')).text("全").appendTo(tr):$(document.createElement('td')).text(formatNumber(String(floor))).appendTo(tr);
								}
							})
						})
						})
						});
					}
				}
				//縮起
				else{
					uncollapseRows[divId]--;
					if(uncollapseRows[divId]==0){
						$('#'+divId).find('.generalInfo').hide();
					}
					$('#'+divId+'>table>tbody>.detial[name="'+name+'"]').hide();
					tr49.prop("rowspan",1);
				}
				
			}	
			$(divId+">table>tbody>tr:even").not('.allChSum').css({"background-color": evenTdColor});
			showFetchMessage();
			//判斷是否要展開
			if($('#報表格式').val()=='分時')
				$(divId+'>table>tbody>.summaries').each(function(){collapse($(this));});
			else
				$('.generalInfo').hide();
		}
		
		//匯出excel
		$("#export").click(function(){
			$('#downloadLink').hide();
			$('#loading').show();
			$('#dialog_form').dialog({height:100, width:150,title:"匯出報表"}).dialog('open');
			var byPost=getByPost();
			byPost.匯出報表 = true;
			byPost.報表格式 =$('#報表格式').val();
			
			var message = $('#選擇平台').val()+$('#報表格式').val()+'報表';
			var realdate = ($("#realAltField").val()=='')?$("#realStart").val()+'到'+$("#realEnd").val():$("#realAltField").val();
			var predictdate = ($("#predictAltField").val()=='')?$("#predictStart").val()+'到'+$("#predictEnd").val():$("#realAltField").val();
			
			byPost.sheetName1 = '實際 '+realdate+' '+message;
			byPost.sheetName2 = '預測 '+predictdate+' '+message;
			$.post('?',byPost,function(data){
				if(data.success){
					$('#loading').fadeOut();
					$('#downloadLink').prop('href',data.url).text('點我下載檔案').fadeIn();
				}
			}
			,'json'
			);
				
		}
		);
		
		//產生託播單
		$('#produceOrder').click(function(){
			orderObject={};
			$('input[name="toOrder"]:checked').each(function(){
				//取得版位識別碼
				var pId = $(this).parent().parent().attr('name');
				//取得日期與時段
				temp = $(this).val().split('_');
				var date = dashDate(temp[0]);
				var time = temp[1];
				//儲存於object
				if(typeof(orderObject[pId])=='undefined')
					orderObject[pId]={}
				if(typeof(orderObject[pId][date])=='undefined')
					orderObject[pId][date]=[]
				orderObject[pId][date].push(time);
			});
			$('#newOrderdialog_iframe').attr('src','newOrder_SEPG.php?orderObject=orderObject').css({"width":"100%","height":"100%"});
			$('#newOrderdialog_form').dialog('open');
		});
		
		function getByPost(){
			var byPost={"取得曝光數資料":true,
				"實際日期":$("#realAltField").val().split(", "),
				"預測日期":$("#predictAltField").val().split(", "),
				'生效日期':$('#頻道日期').val(),
				'選擇平台':$('#選擇平台').val()
				};
				
			//選擇的頻道
			var selectedCh = []; 
			$(".ui-autocomplete-multiselect-item").each(function(){
				selectedCh.push($(this).text().split(":")[0]);
			});
			if(selectedCh.length!=0)
				byPost["頻道列表"]=selectedCh;

			//選擇日期區間設定
			if($("#realStart").val()!=""&&$("#realEnd").val()!="")
				byPost["實際日期期間"]=[$("#realStart").val(),$("#realEnd").val()];
			if($("#predictStart").val()!=""&&$("#predictEnd").val()!="")
				byPost["預測日期期間"]=[$("#predictStart").val(),$("#predictEnd").val()];
			
			//選擇的廣告分類
			var selectedAdType=[];
				$('input[name="adCheckBox"]:checked').each(function(){selectedAdType.push($(this).val());});

			if(selectedAdType.length!=0)
				byPost["廣告分類"]=selectedAdType;
			return byPost;
		}
		
		function showFetchMessage(){
			var message = $('#選擇平台').val()+$('#報表格式').val()+'報表';
			var realdate = ($("#realAltField").val()=='')?$("#realStart").val()+'到'+$("#realEnd").val():$("#realAltField").val();
			var predictdate = ($("#predictAltField").val()=='')?$("#predictStart").val()+'到'+$("#predictEnd").val():$("#realAltField").val();
			$('#message2').text(realdate+' '+message);
			$('#message1').text(predictdate+' '+message);
		}

		//INT TO STIRNG leading zero
		function pad(num, size) {
			var s = num+"";
			while (s.length < size) s = "0" + s;
			return s;
		}
		//數字format
		function formatNumber(str){
			if(str.length <= 3){
				return str;
			} else {
				return formatNumber(str.substr(0,str.length-3))+','+str.substr(str.length-3);
			}
		}
		
		//日期加入-區隔年月日
		function dashDate(str){
			return str.substring(0,4)+'-'+str.substring(4,6)+'-'+str.substring(6,8);
		}
	});//end of $(function(){})
	
	</script>
</body>
</html>