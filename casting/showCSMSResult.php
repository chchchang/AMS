<html>
<style type="text/css">
  	table{
		border-collapse:collapse; 
		border-color: #AAAAAA;
		border-width: 2px;
		border-style: solid;
	}
	td,th{ 
		border:#AAAAAA 1px solid !important;
	}
</style>
<?php
	include('../tool/auth/authAJAX.php');
	require '../tool/PHPExcel/Classes/PHPExcel.php';
	$id = $_GET["id"];
	$sql='
		SELECT 版位.版位名稱,版位類型.版位名稱 AS 版位類型名稱,託播單送出行為識別碼,託播單CSMS群組識別碼
		FROM 版位,託播單,版位 版位類型
		WHERE 託播單.託播單識別碼=? AND 版位.版位識別碼 = 託播單.版位識別碼 AND 版位類型.版位識別碼 = 版位.上層版位識別碼
		';
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->bind_param('i',$id)) {
		exit('無法繫結資料，請聯絡系統管理員！');
	}
	
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	$row=$res->fetch_assoc();
	
	//判斷版位區域
	require '../tool/phpExtendFunction.php';
	if(PHPExtendFunction::stringEndsWith($row['版位名稱'],'_北'))
	$area = 'N';
	else if(PHPExtendFunction::stringEndsWith($row['版位名稱'],'_中'))
	$area = 'C';
	else if(PHPExtendFunction::stringEndsWith($row['版位名稱'],'_南'))
	$area = 'S';
	if(PHPExtendFunction::stringEndsWith($row['版位名稱'],'_IAP'))
	$area = 'IAP';
	
	//判斷版位類型
	switch($row['版位類型名稱']){
		case '首頁banner':
		case '專區banner':
			$pt = 'csad';
			break;
		case '頻道short EPG banner':
			$pt = 'sepg';
			break;
		case '專區vod':
			$pt = 'barkerad';
			break;
	}
	//取得上次步驟
	$actionCode = json_decode($row['託播單送出行為識別碼']);
	switch($actionCode){
		case 1:
			$action = 'insert';
		break;
			
		case 2:
			$action = 'update';
		break;
			
		case 3:
			$action = 'delete';
		break;
		
		default:
			$action ='';
		break;
	}
	
	$filename = 'local/'.$area.'/'.$pt.'.'.$action.'.'.$row['託播單CSMS群組識別碼'].'.xls.fin';
	$objReader = new \PHPExcel_Reader_Excel5();
	 
	$objWriteHTML = new \PHPExcel_Writer_HTML($objReader->load($filename));
	$styleArray = array(
		'borders' => array(
		'allborders' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN
		)
	)
	);
	$objWriteHTML->save("php://output");
?>

</html>