<?php
	
	include('../tool/auth/authAJAX.php');
	include '../Config.php';
	include '../tool/PHPExcel/Classes/PHPExcel.php';
	require_once '../tool/phpExtendFunction.php';
	set_include_path('../tool/phpseclib');
	$headermessage="";
	if(isset($_POST['postAction'])){
		
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
				$data = getExcelData($_FILES["file"]["tmp_name"]);
				$industryType = getIndustryTypeArray();
				//匯入資料
				foreach($data as $i=>$row){

				}
			}
		 } else {
				$headermessage = "沒有選擇檔案";
		 }
	}
	
	//從excel中讀取資料並匯入
	//input:檔案名稱
	//output:array(<頻道號碼>=><投放上限>)
	function getExcelData($file){
		//取得產業類型
		$industryType = getIndustryTypeArray();
		//設定要被讀取的檔案
		try {
			$objPHPExcel = PHPExcel_IOFactory::load($file);
		} catch(Exception $e) {
			die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
		}
		$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
		
		$returnArray = [];
		
		foreach($sheetData as $key => $col)
		{
			//前1行為title，跳過
			if($key<=1)
				continue;
			$row = array(
			"素材群組識別碼"=>"",
			"素材名稱"=>"",
			"素材說明"=>"",
			"產業類型識別碼"=>"07",
			"素材有效開始時間"=>"",
			"素材有效結束時間"=>"",
			"素材類型識別碼"=>"",
			"文字素材內容"=>"",
			"執行結果"=>"",
			);
			/*
			A欄:素材群組識別碼
			B欄:素材名稱(必填)
			C欄位:素材說明(選填)
			D欄位:產業類型代碼(選填:預設內容)
			E欄位:素材有效開始時間(選填)
			F欄位:素材有效結束日期(選填)
			G欄位:素材類型(必填:文字/圖片/影片)
			H欄位:文字素材內容(文字類型素材才需填入)
			*/
			foreach ($col as $colkey => $colvalue) {	
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
					if($colvalue!=""){
						$row["產業類型識別碼"] = $colvalue;
					}
				}
				else if(startsWith($colkey, "E" )){
					$row["素材有效開始時間"] = $colvalue;
				}
				else if(startsWith($colkey, "F" )){
					$row["素材有效開始時間"] = $colvalue;
				}
				else if(startsWith($colkey, "G" )){
					switch($colkey){
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
					//因後面的欄位不需參考，結束loop
					break;
				}
			}
			//檢查輸入合法性
			$row["執行結果"] = checkRowInput($industryType,$row);
			if($row["執行結果"]==""){
				//輸入合法，匯入資料庫
				$row["執行結果"] = insertToDb($row);
			}
			$returnArray[]=$row;
		}
		return $returnArray;
	}
	/**
	 * 匯入資料庫
	 */
	function insertToDb($row){
		/**		
		'action':"新增素材",
		'素材類型識別碼':material[$("input[name='materailRadio']:checked").val()],
		'產業類型識別碼':$("#產業類型").val(),
		'素材名稱':$("#素材名稱").val(),
		'素材說明':$("#素材說明").val(),
		'素材原始檔名':$("#fileToUpload").val().split('\\').pop(),
		'文字素材內容':$("#文字素材內容").val(),
		'圖片素材寬度':$("#圖片素材寬度").val(),
		'圖片素材高度':$("#圖片素材高度").val(),
		'影片素材秒數':$("#影片素材秒數").val()
		,'影片畫質':$("#影片畫質").val()
		,'影片媒體編號':$("#影片媒體編號").val()
		,'影片媒體編號北':$("#影片媒體編號北").val()
		,'影片媒體編號南':$("#影片媒體編號南").val()
		,'素材群組識別碼':$("#素材群組").val()
		,'素材有效開始時間':$("#StartDate").val()
		,'素材有效結束時間':$("#EndDate").val() */
		//$bypost = array("action"=>"新增素材","data"=>$data);
		//$postvars = http_build_query($bypost);
		//$res = PHPExtendFunction::connec_to_Api($url,'POST',$postvars);
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
	 *		"執行結果"=>"",)
	 * @return 成功還傳空字串，失敗回傳失敗原因字串
	 */
	function checkRowInput($industryType,$row){
		$problems = array();
		
		if($row["素材群組識別碼"]==""){
			array_push($problems,"素材群組未選擇");
		}

		if($row["素材名稱"]==""){
			array_push($problems,"素材名稱未輸入");
		}

		if(!isset($industryType[$row["產業類型識別碼"]])){
			array_push($problems,"無效的產業類型");
		}

		if($row["素材類型識別碼"]==""){
			array_push($problems,"無效的素材類型");
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
			$IndustryType[]=array('產業類型識別碼'=>$row['產業類型識別碼'],'產業類型說明'=>$row['產業類型說明'],'產業類型名稱'=>$row['產業類型名稱']);
		}
		return $IndustryType;
	}
	function startsWith ($string, $startString) 
	{ 
		$len = strlen($startString); 
		return (substr($string, 0, $len) === $startString); 
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
		#epglimittable,#epglimittable * * *{
			border: 1px solid #5599FF
		}
		th{
			background-color: #4169E1;
			color: white;
		}
		table {
			border-collapse:separate;
			border-collapse: collapse;
			width:100%
		}
		td{
			min-width: 60px;
		}
	</style>
</head>
<body>
<fieldset>
<legend>匯入投放上限設定</legend>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" enctype="multipart/form-data">
<input type="file" name="file" id="file" /></td>
<input type="submit" name="submit" />
<font size="3" color="blue"><?=$headermessage?></font>
</form>
</fieldset>

<script type="text/javascript">


</script>
</body>
</html>
