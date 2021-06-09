<?php
	include('../tool/auth/authAJAX.php');
	//include '../tool/PHPExcel/Classes/PHPExcel.php';
	require '../tool/OutputExcel.php';
	require_once '../tool/phpExtendFunction.php';
	set_include_path('../tool/phpseclib');
	$headermessage="";
	$tableData = array();
	if(isset($_POST['postAction'])){
		if($_POST['postAction']=="export"){
			$data = $_POST['data'];
			$uid =uniqid();
			@OutputExcel::outputAll_sheet('export/'.$uid,array("sheet1"=>$data));
			exit(json_encode(array("success"=>true,"filepath"=>'export/'.$uid.'.xls')));
		}
	}

	//匯入投放上限設定
	if ( isset($_POST["submit"]) ) {
		if ( isset($_FILES["file"])) {
			 if ($_FILES["file"]["error"] > 0) {
				 $headermessage = "檔案上傳失敗";
			 }
			 else {
				 //echo "Upload: " . $_FILES["file"]["name"] . "<br />";
				 //echo "Type: " . $_FILES["file"]["type"] . "<br />";
				 //echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
				 //echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
				 //從excel擋取得資料
				 $data = getInsertExcelDataResult($_FILES["file"]["tmp_name"]);
				 //print_r($data);
				 $tableData = $data;
				 //exportExcel($data);
 
			 }
		  } else {
				 $headermessage = "沒有選擇檔案";
		  }
	 }
	 //將檔案匯出到excel
	 function exportExcel($data){
		 $fileName =uniqid();
		 OutputExcel::outputAll_sheet('export/'.$fileName,array($data));
		 $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		 $filepath = 'export/'.$fileName.".xls";
		 //exit(json_encode(array('success'=>true,'url'=>$protocol.$_SERVER ['HTTP_HOST'].str_replace("report2.php",'export/'.$fileName.".xls",$_SERVER['PHP_SELF'])),JSON_UNESCAPED_UNICODE));
		 $exoprtFileLink = $protocol.$_SERVER ['HTTP_HOST'].str_replace("insertMaterialBatch.php",$filepath,$_SERVER['PHP_SELF']);
		 //echo "<a href='".$exoprtFileLink."'>下載匯入結果檔案</a>\n";
		 echo "<a href='download.php?path=".$filepath."'>下載匯入結果檔案</a>\n";
	 }
	 //從excel中讀取資料並匯入
	 //input:檔案名稱
	 //output:array
	 function getInsertExcelDataResult($file){
		 $excelArray[] = array("素材群組識別碼(必填)","素材名稱(必填)","素材說明(選填)","產業類型代碼(選填:預設內廣內容)","素材有效開始日期(選填)","素材有效結束日期(選填)"
				 ,"素材類型(必填:文字/圖片/影片)","文字素材內容(文字類型素材才需填入)","影片畫質(hd/sd)","影片秒數","執行結果");
		 //取得產業類型
		 $industryType = getIndustryTypeArray();
		 //設定要被讀取的檔案
		 try {
			 $objPHPExcel = PHPExcel_IOFactory::load($file);
		 } catch(Exception $e) {
			 die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
		 }
		 $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
		 
		 
		 foreach($sheetData as $key => $col)
		 {
			 //前1行為title，跳過
			 if($key<=1)
				 continue;
 
			 $row = array(
			 "素材群組識別碼"=>"",
			 "素材名稱"=>"",
			 "素材說明"=>"",
			 "產業類型識別碼"=>"",
			 "素材有效開始時間"=>"",
			 "素材有效結束時間"=>"",
			 "素材類型識別碼"=>"",
			 "文字素材內容"=>"",
			 "影片畫質識別碼"=>"",
			 "影片素材秒數"=>"",
			 "執行結果"=>"",
			 );
			 $excelRow=array();
			 /*
			 A欄:素材群組識別碼
			 B欄:素材名稱(必填)
			 C欄位:素材說明(選填)
			 D欄位:產業類型代碼(選填:預設內容)
			 E欄位:素材有效開始時間(選填)
			 F欄位:素材有效結束日期(選填)
			 G欄位:素材類型(必填:文字/圖片/影片)
			 H欄位:文字素材內容(文字類型素材才需填入)
			 I欄位:影片畫質識(影片類型素材才需填入)
			 J欄位:影片素材秒數(影片類型素材才需填入)
			 */
			 foreach ($col as $colkey => $colvalue) {	
				 $excelRow[] = $colvalue;
				 if(startsWith($colkey, "A" )){
					 $row["素材群組識別碼"] = $colvalue;
				 }
				 else if(startsWith($colkey, "B" )){
					 $row["素材名稱"] = $colvalue;
				 }
				 else if(startsWith($colkey, "C" )){
					 $row["素材說明"] = $colvalue;
				 }
				 else if(startsWith($colkey, "D" )){
					 $產業類型代碼 = "07";
					 if($colvalue!=""){
						 $產業類型代碼 = $colvalue;
					 }
					 if(isset($industryType[$產業類型代碼])){
						 $row["產業類型識別碼"] = $industryType[$產業類型代碼]["產業類型識別碼"];
					 }
					 else{
						 $row["執行結果"]="無效的產業類型 ";
					 }
				 }
				 else if(startsWith($colkey, "E" )){
					 $row["素材有效開始時間"] = $colvalue;
				 }
				 else if(startsWith($colkey, "F" )){
					 $row["素材有效結束時間"] = $colvalue;
				 }
				 else if(startsWith($colkey, "G" )){
					 switch($colvalue){
						 case "文字":
							 $row["素材類型識別碼"] = 1;
							 break;
						 case "圖片":
							 $row["素材類型識別碼"] = 2;
							 break;
						 case "影片":
							 $row["素材類型識別碼"] = 3;
							 break;
					 }
					 
				 }
				 else if(startsWith($colkey, "H" )){
					 $row["文字素材內容"] = $colvalue;
				 }
				 else if(startsWith($colkey, "I" )){
					 $str = strtoupper($colvalue);
					 switch($str){
						 case "SD":
							 $row["影片畫質識別碼"] = 1;
							 break;
						 case "HD":
							 $row["影片畫質識別碼"] = 2;
							 break;
					 }
				 }
				 else if(startsWith($colkey, "J" )){
					 $row["影片素材秒數"] = $colvalue;
					 //因後面的欄位不需參考，結束loop
					 break;
				 }
			 }
			 //檢查輸入合法性
			 $row["執行結果"].=checkRowInput($row);
			 
			 if($row["執行結果"]==""){
				 //輸入合法，匯入資料庫
				 $row["執行結果"] = insertToDb($row);
			 }
			 $excelRow[] = $row["執行結果"];
			 $excelArray[]=$excelRow;
		 }
		 
		 return $excelArray;
	 }
	 /**
	  * 匯入資料庫
	  */
	 function insertToDb($row){
		 global $logger,$my;
		 ($row["影片素材秒數"]=='')?($value["影片素材秒數"]=null):($value["影片素材秒數"]=$row["影片素材秒數"]);
		 ($row["文字素材內容"]=='')?($value["文字素材內容"]=null):($value["文字素材內容"]=$row["文字素材內容"]);
		 ($row["影片畫質識別碼"]=='')?($value["影片畫質識別碼"]=null):($value["影片畫質識別碼"]=$row["影片畫質識別碼"]);
		 ($row["素材有效開始時間"]=='')?($value["素材有效開始時間"]=null):($value["素材有效開始時間"]=$row["素材有效開始時間"]);
		 ($row["素材有效結束時間"]=='')?($value["素材有效結束時間"]=null):($value["素材有效結束時間"]=$row["素材有效結束時間"]);
		 //新資資料
		 $sql="INSERT INTO 素材 (素材類型識別碼,產業類型識別碼,素材名稱,素材說明,文字素材內容,
			 影片素材秒數,影片畫質識別碼,素材群組識別碼,CREATED_PEOPLE,素材有效開始時間,素材有效結束時間)"
		 ." VALUES(?,?,?,?,?,?,?,?,?,?,?)";
		 
		 if(!$stmt=$my->prepare($sql)) {
			 $logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			 return '無法準備statement，請聯絡系統管理員！';
		 }
		 
		 if(!$stmt->bind_param('iisssiiiiss',$row["素材類型識別碼"],$row["產業類型識別碼"],$row["素材名稱"],$row["素材說明"]
			 ,$value["文字素材內容"],$value["影片素材秒數"],$value["影片畫質識別碼"]
			 ,$row['素材群組識別碼'],$_SESSION['AMS']['使用者識別碼'],$value["素材有效開始時間"],$value["素材有效結束時間"])) {
			 $logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			 return '無法準備statement，請聯絡系統管理員！';
		 }
		 
		 if(!$stmt->execute()) {
			 $logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			 return '無法準備statement，請聯絡系統管理員！';
		 }
				 
	 
		 $logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增素材(識別碼:'.$stmt->insert_id.')');
		 return '新增成功，素材識別碼:'.$stmt->insert_id;
 
	 }
 
	 /**
	  * 檢查EXCEL檔案輸入合法性
	  * @parameter $industryType,
	  * 		$row:array("素材群組識別碼"=>"",
	  *		"素材名稱"=>"",
	  *		"素材說明"=>"",
	  *		"產業類型識別碼"=>"",
	  *		"素材有效開始時間"=>"",
	  *		"素材有效結束時間"=>"",
	  *		"素材類型識別碼"=>"",
	  *		"文字素材內容"=>"",
	  *		"影片畫質"=>"",
	  *		"影片秒數"=>"",
	  *		"執行結果"=>"",)
	  * @return 成功還傳空字串，失敗回傳失敗原因字串
	  */
	 function checkRowInput($row){
		 $problems = array();
		 
		 if($row["素材群組識別碼"]==""){
			 array_push($problems,"素材群組未選擇");
		 }
 
		 if($row["素材名稱"]==""){
			 array_push($problems,"素材名稱未輸入");
		 }
 
		 /*if(!isset($industryType[$row["產業類型識別碼"]])){
			 array_push($problems,"無效的產業類型");
		 }*/
		 $starttimecheck =validateDate($row["素材有效開始時間"]);
		 $endtimecheck=validateDate($row["素材有效結束時間"]);
		 if($row["素材有效開始時間"]!=""){
			if(!$starttimecheck){
				array_push($problems,"素材有效開始時間格式錯誤");
			}
		 }
		 if($row["素材有效結束時間"]!=""){
			if(!$endtimecheck){
				array_push($problems,"素材有效結束時間格式錯誤");
			}
		 }

		 if($starttimecheck&&$endtimecheck){
			if($row["素材有效開始時間"]>$row["素材有效結束時間"]){
				array_push($problems,"開始時間不得大於結束時間");
			}
		 }


		 if($row["素材類型識別碼"]==""){
			array_push($problems,"無效的素材類型");
		 }
 
		 switch($row["素材類型識別碼"]){
			 case 1://文字素材
				 if($row["文字素材內容"]==""){
					 array_push($problems,"文字素材內容為空");
				 }
				 break;
			 case 2://圖片素材
				 break;
			 case 3://影片素材
				 if($row["影片素材秒數"]==""){
					 array_push($problems,"影片素材秒數為空");
				 }
				 if($row["影片畫質識別碼"]==""){
					 array_push($problems,"影片畫質錯誤");
				 }
				 break;
		 }
		 $result = implode(",",$problems);
		 return $result;
	 }
	 
	 /**
	 * 取得產業類型 
	  */
	 function getIndustryTypeArray(){
		 global $logger,$my;
		 $sql = 'SELECT 產業類型名稱,產業類型識別碼,產業類型說明 FROM 產業類型';
			 
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
			 $IndustryType[$row['產業類型名稱']]=array('產業類型識別碼'=>$row['產業類型識別碼'],'產業類型說明'=>$row['產業類型說明'],'產業類型名稱'=>$row['產業類型名稱']);
		 }
		 return $IndustryType;
	 }
	function startsWith ($string, $startString) 
	{ 
		$len = strlen($startString); 
		return (substr($string, 0, $len) === $startString); 
	} 

	function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="../tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
	<link rel='stylesheet' type='text/css' href='../external-stylesheet.css'/>
	<style type="text/css">
	</style>
</head>
<body>
<fieldset>
表單內容範例:
<br>
<table class="styledTable" style="color:blue;">
	<tbody>
	<tr><td>素材群組識別碼(必填)</td><td>素材名稱(必填)</td><td>素材說明(選填)</td><td>產業類型代碼(選填:預設內廣內容)</td><td>素材有效開始日期(選填)</td><td>素材有效結束日期(選填)</td>
	<td>素材類型(必填:文字/圖片/影片)</td><td>文字素材內容(文字類型素材才需填入)</td><td>影片畫質(hd/sd)</td><td>影片秒數</td></tr>
	<tr><td>1</td><td>預設圖片1</td><td>banner預設圖片</td><td>07</td><td>2021-05-10 00:00:00</td><td>2021-05-17 23:59:59</td>
	<td>圖片</td><td></td><td></td><td></td></tr>
	<tr><td>1</td><td>跑馬燈</td><td>首頁跑馬燈</td><td>07</td><td>2021-05-10 00:00:00</td><td>2021-05-17 23:59:59</td>
	<td>文字</td><td>跑馬燈內容</td><td></td><td></td></tr>
	<tr><td>1</td><td>影片廣告</td><td></td><td>07</td><td></td><td></td>
	<td>影片</td><td></td><td>hd</td><td>30</td></tr>
	<tr></tr>
	</tbody>
</table>
<br>
<legend>匯入素材excel檔案</legend>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" enctype="multipart/form-data">
<input type="file" name="file" id="file" />
<input type="submit" name="submit" />
<button id="downloadExample">下載空白Excel範例檔案</button>
</form>

</fieldset>
<br><button id="downloadResult">下載執行結果Excel檔案</button>

<script type="text/javascript">
	execlData = '<?php echo json_encode($tableData);?>';
	console.log(execlData);
	if(execlData!="[]"&&execlData!=""){
		var obj = JSON.parse(execlData);
		$("#downloadResult").click(function(){
			$.post(null,{postAction:"export",data:obj},
			function(json){
				if(json["success"])
					window.location.href = "download.php?path="+json["filepath"];
			},'json');
		});
	}
	else{
		$("#downloadResult").hide();	
	}
	$("#downloadExample").click(function(){
		data = [["素材群組識別碼(必填)","素材名稱(必填)","素材說明(選填)","產業類型代碼(選填:預設內廣內容)","素材有效開始日期(選填)","素材有效結束日期(選填)"
				,"素材類型(必填:文字/圖片/影片)","文字素材內容(文字類型素材才需填入)","影片畫質(hd/sd)","影片秒數","執行結果"]];
		/*$.post("exportExcleAndDownload.php",{data:data},
			function(json){	},'json');*/
			$.post(null,{postAction:"export",data:data},
			function(json){
				if(json["success"])
					window.location.href = "download.php?path="+json["filepath"];
			},'json');
	});
</script>
</body>
</html>

<?php if ($headermessage!=""): ?>
<font size="3" color="blue"><?=$headermessage?></font>
<?php endif; ?>

<?php if (count($tableData) > 0): ?>
<table class="styledTable">
  <tbody>
<?php foreach ($tableData as $row): array_map('htmlentities', $row); ?>
    <tr>
      <td><?php echo implode('</td><td>', $row); ?></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

