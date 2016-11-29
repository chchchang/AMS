<?php
	include('../tool/auth/authAJAX.php');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit(json_encode(array('無法連線到資料庫，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit(json_encode(array('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	
	
	
	if( isset($_POST['action']) && $_POST['action'] != '' ){
		switch($_POST['action']){
			case "getCount":
				get_Count();
				break;
			case "DATAGRID素材資訊":
				get_materialDataGrid();
				break;
			case "素材資訊表格":
				get_materialInfo();
				break;
			case "新增素材":
				new_material();
				break;
			case "取得產業類型":
				get_industry();
				break;
			case "修改素材":
				edit_material();
				break;
			case "下載素材檔案檢查":
				downloadFileCheck();
			case "下載素材檔案":
				downFile();
			default:
				break;
		}
	}
	else{
		
	}
	
	/**取得count**/
	function get_Count(){
		global $logger, $my;
		
		if(!$my->set_charset('utf8')) {
			$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}

		$sql = "SELECT COUNT(*) FROM ".$_POST["TABLE"]." WHERE ".$_POST["WHERE"];
		if(!$res=$my->query($sql)) {
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！',"query"=>$sql),JSON_UNESCAPED_UNICODE));
		}
				
		$a=array();	
		while($row = $res->fetch_array()){
			$row = array_map('urlencode', $row);
			array_push($a,$row);
		}
		echo urldecode(json_encode($a));
	}
	
	
	/**素材資料(datagrid)**/
	function get_materialDataGrid(){
		global $logger, $my;
		$sort = "" ;
		if(isset($_POST['SORT']))
			$sort = $_POST['SORT'];
		$sql="SELECT 素材識別碼, 素材類型名稱,素材名稱,素材說明 FROM 素材,素材類型 WHERE ".$_POST["WHERE"]
			." ORDER BY ".$_POST["ORDER"]." ".$sort."  LIMIT ".$_POST["PAGE"].",".$_POST["PNUMBER"];

		if(!$result =$my->query($sql)){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$a=array();	
		while($row = $result->fetch_array()){
			array_push($a,$row);
		}
		echo json_encode($a,JSON_UNESCAPED_UNICODE);
	}
	
		
	/**素材資料(表格)**/
	function get_materialInfo(){
		global $logger, $my;

		$sql="SELECT * FROM 素材 WHERE 素材識別碼=?";

		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["素材識別碼"])){
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$feedback= $res->fetch_assoc();
		
		if($feedback['素材群組識別碼']!=0){
			$sql="SELECT 素材群組名稱 FROM 素材群組 WHERE 素材群組識別碼=?";

			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('i',$feedback["素材群組識別碼"])){
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$res=$stmt->get_result()){
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row=$res->fetch_array();
			$feedback["素材群組名稱"]= $row["素材群組名稱"];
		}
		
		if($feedback["產業類型識別碼"]!=0){
			$sql="SELECT T2.產業類型識別碼 AS 上層產業類型識別碼,T2.產業類型名稱 AS 上層產業類型名稱,T2.產業類型說明 AS 上層產業類型說明,T1.產業類型說明 AS 產業類型說明
			FROM 產業類型 T1 LEFT JOIN 產業類型 T2 ON T1.上層產業類型識別碼=T2.產業類型識別碼
			WHERE T1.產業類型識別碼=?";

			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('i',$feedback["產業類型識別碼"])){
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$res=$stmt->get_result()){
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row=$res->fetch_array();
			$feedback["上層產業類型名稱"]= $row["上層產業類型名稱"];
			$feedback["上層產業類型識別碼"]= $row["上層產業類型識別碼"];
			$feedback["上層產業類型說明"]= $row["上層產業類型說明"];
			$feedback["產業類型說明"]= $row["產業類型說明"];
		}
		
		if($feedback['素材原始檔名']!=null&&$feedback['素材原始檔名']!=''){
			$explodeFileName=explode(".",$feedback['素材原始檔名']);
			$feedback["檔案存在"]= file_exists('uploadedFile/'.$feedback['素材識別碼'].".".$explodeFileName[count($explodeFileName)-1]);
		}
		
		echo json_encode($feedback,JSON_UNESCAPED_UNICODE);
	}
	
	
	//新增素材
	function new_material(){
		global $logger, $my;

		$value=array();
		($_POST["圖片素材寬度"]=='')?($value["圖片素材寬度"]=null):($value["圖片素材寬度"]=$_POST["圖片素材寬度"]);
		($_POST["圖片素材高度"]=='')?($value["圖片素材高度"]=null):($value["圖片素材高度"]=$_POST["圖片素材高度"]);
		($_POST["影片素材秒數"]=='')?($value["影片素材秒數"]=null):($value["影片素材秒數"]=$_POST["影片素材秒數"]);
		($_POST["文字素材內容"]=='')?($value["文字素材內容"]=null):($value["文字素材內容"]=$_POST["文字素材內容"]);
		($_POST["影片畫質"]=='')?($value["影片畫質"]=null):($value["影片畫質"]=$_POST["影片畫質"]);
		($_POST["影片媒體編號北"]=='')?($value["影片媒體編號北"]=null):($value["影片媒體編號北"]=$_POST["影片媒體編號北"]);
		($_POST["影片媒體編號"]=='')?($value["影片媒體編號"]=null):($value["影片媒體編號"]=$_POST["影片媒體編號"]);
		($_POST["影片媒體編號北"]=='')?($value["影片媒體編號北"]=null):($value["影片媒體編號北"]=$_POST["影片媒體編號北"]);
		($_POST["影片媒體編號南"]=='')?($value["影片媒體編號南"]=null):($value["影片媒體編號南"]=$_POST["影片媒體編號南"]);
		($_POST["素材有效開始時間"]=='')?($value["素材有效開始時間"]=null):($value["素材有效開始時間"]=$_POST["素材有效開始時間"]);
		($_POST["素材有效結束時間"]=='')?($value["素材有效結束時間"]=null):($value["素材有效結束時間"]=$_POST["素材有效結束時間"]);
		//檢察素材走期是否可包含素材群組走期
		/*$sql="SELECT 素材群組有效開始時間,素材群組有效結束時間 FROM 素材群組 WHERE 素材群組識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["素材群組識別碼"])){
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		if(mysqli_num_rows($res)!=0){
			$row=$res->fetch_array();
			if($value["素材有效開始時間"]!=null){
				if($row["素材群組有效開始時間"]==null){
					exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
				}
				else if($value["素材有效開始時間"]>$row["素材群組有效開始時間"]){
					exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
				}
			}
			if($value["素材有效結束時間"]!=null){
				if($row["素材群組有效結束時間"]==null){
					exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
				}
				else if($value["素材有效結束時間"]<$row["素材群組有效結束時間"]){
					exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
				}
			}
		}*/
		//檢察素材群組檔案格式是否一至
		/*$sql="SELECT 素材類型識別碼,影片素材秒數,產業類型識別碼,素材群組有效開始時間,素材群組有效結束時間 FROM 素材,素材群組 WHERE 素材群組.素材群組識別碼=素材.素材群組識別碼 AND 素材群組.素材群組識別碼=?";

		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["素材群組識別碼"])){
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		$row=$res->fetch_array();
		
		//檢察素材走期是否可包含素材群組走期
		if($row["素材群組有效開始時間"]!=null){
			if($row["素材有效開始時間"]==null){
				exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
			}
			else if($row["素材有效開始時間"]>$row["素材群組有效開始時間"]){
				exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
			}
		}
		if($row["素材群組有效結束時間"]!=null){
			if($row["素材有效結束時間"]==null){
				exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
			}
			else if($row["素材有效結束時間"]<$row["素材群組有效結束時間"]){
				exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
			}
		}
		
		
		//群組中已經有其他素材存在
		if(mysqli_num_rows($res)!=0){
			if($row['素材類型識別碼']!=$_POST['素材類型識別碼'])
				exit(json_encode(array("success"=>false,"message"=>'選擇的素材類型與群組不符'),JSON_UNESCAPED_UNICODE));
			if($row['產業類型識別碼']!=$_POST['產業類型識別碼'])
				exit(json_encode(array("success"=>false,"message"=>'選擇的產業類型與群組不符'),JSON_UNESCAPED_UNICODE));
			if($value['影片素材秒數']!=null && $row['影片素材秒數']!=$value['影片素材秒數'])
				exit(json_encode(array("success"=>false,"message"=>'影片秒數與群組不符'),JSON_UNESCAPED_UNICODE));
		}*/
		//mysqli_free_result($res);
		
		//上傳素材檔案
		if(isset($_POST['新素材檔案上傳'])){
			$tempDir='uploadedFile';
			if(!file_exists ($tempDir)){
				if (!mkdir($tempDir, 0777, true)) 
					exit(json_encode(array("success" => false,"message" => "檔案資料夾建立失敗"),JSON_UNESCAPED_UNICODE));
			}
			$tempDir='tempFile/'.$_SESSION['AMS']['使用者識別碼'];
			$explodeFileName=explode(".",$_POST["素材原始檔名"]);
			$convertFileName = hash('ripemd160',iconv('UTF-8', 'UCS-4', $_POST["素材原始檔名"])).'.'.end($explodeFileName);
			$tempfile = $tempDir.'/'.$convertFileName;
			switch(strtolower($explodeFileName[count($explodeFileName)-1])){
				case 'jpg':
				case 'jpeg':
				case 'png':
				case 'gif':
					$filedata=getimagesize($tempfile);
					$value["圖片素材寬度"] = $filedata[0];
					$value["圖片素材高度"] = $filedata[1];
					break;
				default:
					$value["圖片素材寬度"] = NULL;
					$value["圖片素材高度"] = NULL;
					break;
			}
			
		}
		//新資資料
		$sql="INSERT INTO 素材 (素材類型識別碼,產業類型識別碼,素材名稱,素材說明,素材原始檔名,文字素材內容,圖片素材寬度
			,圖片素材高度,影片素材秒數,影片畫質識別碼,影片媒體編號,影片媒體編號北,影片媒體編號南,素材群組識別碼,CREATED_PEOPLE,素材有效開始時間,素材有效結束時間)"
		." VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('iissssiiissssiiss',$_POST["素材類型識別碼"],$_POST["產業類型識別碼"],$_POST["素材名稱"],$_POST["素材說明"]
			,$_POST["素材原始檔名"],$value["文字素材內容"],$value["圖片素材寬度"],$value["圖片素材高度"],$value["影片素材秒數"],$value["影片畫質"]
			,$value["影片媒體編號"],$value["影片媒體編號北"],$value["影片媒體編號南"],$_POST['素材群組識別碼'],$_SESSION['AMS']['使用者識別碼'],$value["素材有效開始時間"],$value["素材有效結束時間"])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
				
		$feedback = array(
			"success" => true,
			"message" => urlencode("成功新增素材!"),
		);
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'新增素材(識別碼:'.$stmt->insert_id.')');
		echo urldecode(json_encode($feedback));
		
		if(isset($_POST['新素材檔案上傳']))
			rename($tempDir.'/'.hash('ripemd160',iconv('UTF-8', 'UCS-4', $_POST["素材原始檔名"])).'.'.end($explodeFileName),'uploadedFile/'.$stmt->insert_id.".".$explodeFileName[count($explodeFileName)-1]);
	}
	
	//取得產業類型
	function get_industry(){
		global $logger, $my;
		
		$sql="SELECT 產業類型名稱,產業類型說明,產業類型識別碼 FROM 產業類型 WHERE 上層產業類型識別碼=?";

		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["上層產業類型識別碼"])){
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		$feedback=array();
		while($row=$res->fetch_array())
			array_push($feedback,$row);
		
		echo(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		
	}
	
	//修改素材
	function edit_material(){
		global $logger, $my;

		$value=array();
		($_POST["圖片素材寬度"]=='')?($value["圖片素材寬度"]=null):($value["圖片素材寬度"]=$_POST["圖片素材寬度"]);
		($_POST["圖片素材高度"]=='')?($value["圖片素材高度"]=null):($value["圖片素材高度"]=$_POST["圖片素材高度"]);
		($_POST["影片素材秒數"]=='')?($value["影片素材秒數"]=null):($value["影片素材秒數"]=$_POST["影片素材秒數"]);
		($_POST["文字素材內容"]=='')?($value["文字素材內容"]=null):($value["文字素材內容"]=$_POST["文字素材內容"]);
		($_POST["影片畫質"]=='')?($value["影片畫質"]=null):($value["影片畫質"]=$_POST["影片畫質"]);
		($_POST["素材有效開始時間"]=='')?($value["素材有效開始時間"]=null):($value["素材有效開始時間"]=$_POST["素材有效開始時間"]);
		($_POST["素材有效結束時間"]=='')?($value["素材有效結束時間"]=null):($value["素材有效結束時間"]=$_POST["素材有效結束時間"]);
		//鎖定資料表
		$my->begin_transaction();
		if(isset($_POST['updateGroup'])){
			/*$sql="UPDATE 素材 SET 產業類型識別碼=?,影片素材秒數=?,LAST_UPDATE_PEOPLE=? WHERE 素材群組識別碼=?";
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('iiii',$_POST["產業類型識別碼"],($_POST["影片素材秒數"]=='')?null:$_POST["影片素材秒數"],$_SESSION['AMS']['使用者識別碼'],$_POST["素材群組識別碼"])){
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'修改素材群組(識別碼:'.$_POST["素材群組識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));*/
		}
		else{
			//檢察素材走期是否可包含素材群組走期
			/*$sql="SELECT 素材群組有效開始時間,素材群組有效結束時間 FROM 素材群組 WHERE 素材群組識別碼=?";
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('i',$_POST["素材群組識別碼"])){
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$res=$stmt->get_result()){
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(mysqli_num_rows($res)!=0){
				$row=$res->fetch_array();
				if($value["素材有效開始時間"]!=null){
					if($row["素材群組有效開始時間"]==null){
						exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
					}
					else if($value["素材有效開始時間"]>$row["素材群組有效開始時間"]){
						exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
					}
				}
				if($value["素材有效結束時間"]!=null){
					if($row["素材群組有效結束時間"]==null){
						exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
					}
					else if($value["素材有效結束時間"]<$row["素材群組有效結束時間"]){
						exit(json_encode(array("success"=>false,"message"=>'素材走期無法涵蓋素材群組走期'),JSON_UNESCAPED_UNICODE));
					}
				}
			}*/
		
			//檢察素材群組檔案格式是否一至
			/*$sql="SELECT 素材類型識別碼,影片素材秒數,產業類型識別碼 FROM 素材,素材群組 WHERE 素材群組.素材群組識別碼=素材.素材群組識別碼 AND 素材群組.素材群組識別碼=?";
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('i',$_POST["素材群組識別碼"])){
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$res=$stmt->get_result()){
				$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			//群組中已經有其他素材存在
			if(mysqli_num_rows($res)!=0){
				$row=$res->fetch_array();
				if($row['素材類型識別碼']!=$_POST['素材類型識別碼']){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'選擇的素材類型與群組不符'),JSON_UNESCAPED_UNICODE));
				}
				if($row['產業類型識別碼']!=$_POST['產業類型識別碼']){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'選擇的產業類型與群組不符'),JSON_UNESCAPED_UNICODE));
				}
				if($value['影片素材秒數']!=null && $row['影片素材秒數']!=$value['影片素材秒數']){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'影片秒數與群組不符'),JSON_UNESCAPED_UNICODE));
				}
			}*/
			//mysqli_free_result($res);
		}
		//檢查是否可以上傳
		$sql='	
			SELECT 版位.版位識別碼,上層版位識別碼,素材順序,版位名稱,託播單.託播單識別碼,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段
			FROM 版位 JOIN 託播單 ON 託播單.版位識別碼 = 版位.版位識別碼
				JOIN 託播單素材 ON 託播單.託播單識別碼 = 託播單素材.託播單識別碼
			WHERE 託播單狀態識別碼 IN (0,1,2,4) AND 素材識別碼 = ?
		';
		if(!$stmt=$my->prepare($sql)) {
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->bind_param('i',$_POST["素材識別碼"])){
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
		}
		if(!$stmt->execute()) {
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
		}
		if(!$res=$stmt->get_result()){
			$my->close();
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
		}
		$positionLimit=array();
		while($row = $res->fetch_assoc()){
			if(!isset($positionLimit[$row["版位識別碼"]][$row["素材順序"]])){
				//版位類型素材參數
				$sql='
					SELECT 影片畫質識別碼,每小時最大素材筆數,每小時最大影片素材合計秒數,每則文字素材最大字數,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數
					FROM 版位素材類型
					WHERE 版位識別碼 = ? AND 素材順序=?
				';
				if(!$stmt=$my->prepare($sql)) {
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('ii',$row["上層版位識別碼"],$row["素材順序"])){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				$positionLimit[$row["版位識別碼"]][$row["素材順序"]] = $res->fetch_assoc();
				//版位素材參數
				$sql='
					SELECT 影片畫質識別碼,每小時最大影片素材合計秒數,每則文字素材最大字數,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數
					FROM 版位素材類型
					WHERE 版位識別碼 = ? AND 素材順序=?
				';
				if(!$stmt=$my->prepare($sql)) {
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('ii',$row["版位識別碼"],$row["素材順序"])){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				$positionLimit[$row["版位識別碼"]][$row["素材順序"]] = $res->fetch_assoc();
			}
			if($_POST["素材類型識別碼"]==1){
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則文字素材最大字數"]!=null)
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則文字素材最大字數"]<strlen($value["文字素材內容"])){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'文字素材最大字數超過"'.$row["版位名稱"].'"上限','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
			}
			else if($_POST["素材類型識別碼"]==2){
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則圖片素材最大寬度"]!=null)
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則圖片素材最大寬度"]<$value["圖片素材寬度"]){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'每則圖片素材最大寬度超過"'.$row["版位名稱"].'"上限','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則圖片素材最大高度"]!=null)
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則圖片素材最大高度"]<$value["圖片素材高度"]){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'每則圖片素材最大高度超過"'.$row["版位名稱"].'"上限','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
			}
			else if($_POST["素材類型識別碼"]==3){
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["影片畫質識別碼"]!=null)
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["影片畫質識別碼"]!=$value["影片畫質"]){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'影片畫質不符"'.$row["版位名稱"].'"規定','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則影片素材最大秒數"]!=null)
				if($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每則影片素材最大秒數"]<$value["影片素材秒數"]){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'影片素材秒數超過"'.$row["版位名稱"].'"上限','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				//檢查排程
				//取出特定時段的託播單
				$sql='
					SELECT 影片素材秒數,素材.素材識別碼,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段
					FROM 託播單,託播單素材,素材
					WHERE 託播單.版位識別碼 = ? AND 託播單素材.素材順序=? 
					AND 託播單.託播單識別碼 = 託播單素材.託播單識別碼 
					AND 託播單素材.素材識別碼 = 素材.素材識別碼
					AND ((? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間) OR (廣告期間開始時間 BETWEEN ? AND ?)
					)
				';
				if(!$stmt=$my->prepare($sql)) {
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->bind_param('iissss',$row["版位識別碼"],$row["素材順序"],$row["廣告期間開始時間"],$row["廣告期間結束時間"],$row["廣告期間開始時間"],$row["廣告期間結束時間"])){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					$my->close();
					exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
				//累加影片時間
				//逐日、逐小時累加目前託播單							
				$sdate=date_create($row["廣告期間開始時間"]);
				$edate=date_create($row["廣告期間結束時間"]);
				$diff=date_diff($sdate,$edate)->format("%a");
				
				$checkArray=array();
				//逐託播單累加
				while($checkrow =$res->fetch_assoc()){
					if($row["廣告期間開始時間"]<$checkrow["廣告期間開始時間"])
						$offset_s=date_diff($sdate,date_create($checkrow["廣告期間開始時間"]))->format("%a");
					else
						$offset_s=0;
					
					if($row["廣告期間結束時間"]>$checkrow["廣告期間結束時間"])
						$offset_e=date_diff($sdate,date_create($checkrow["廣告期間結束時間"]))->format("%a");
					else
						$offset_e=0;
					$hours = explode(',',$checkrow['廣告可被播出小時時段']);
					//逐日
					for($i =$offset_s;$i<$diff-$offset_e;$i++){
						if(!isset($checkArray[$i]))
							$checkArray[$i]=array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
						//逐小時
						foreach($hours as $h){
							$h=intval($h,10);
							if($checkrow['素材識別碼']==$_POST['素材識別碼'])
								$checkArray[$i][$h]+=$value["影片素材秒數"];
							else
								$checkArray[$i][$h]+=$checkrow["影片素材秒數"];
							
							if(isset($positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每小時最大影片素材合計秒數"]))
							if($checkArray[$i][$h]>$positionLimit[$row["版位識別碼"]][$row["素材順序"]]["每小時最大影片素材合計秒數"])
								exit(json_encode(array("success"=>false,"message"=>'每小時最大影片素材合計秒數"'.$row["版位名稱"].'"上限','id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
						}
					}
				}
				
			}
		}
		
		
		$sql="UPDATE 素材 SET 素材類型識別碼=?,產業類型識別碼=?,素材名稱=?,素材說明=?,素材原始檔名=?,文字素材內容=?,圖片素材寬度=?
			,圖片素材高度=?,影片素材秒數=?,影片畫質識別碼=?,素材群組識別碼=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME = CURRENT_TIMESTAMP,素材有效開始時間=?,素材有效結束時間=?";
		
		//若需要重新上傳檔案
		if(isset($_POST['新素材檔案上傳'])){
			//上傳檔案
			$tempDir='uploadedFile';
			if(!file_exists ($tempDir)){
				if (!mkdir($tempDir, 0777, true)) {
					$my->rollback();
					$my->close();
					exit(json_encode(array("success" => false,"message" => "檔案資料夾建立失敗",'id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
				}
			}
			$tempDir='tempFile/'.$_SESSION['AMS']['使用者識別碼'];
			$explodeFileName=explode(".",$_POST["素材原始檔名"]);
			$convertFileName = hash('ripemd160',iconv('UTF-8', 'UCS-4', $_POST["素材原始檔名"])).'.'.end($explodeFileName);
			$tempfile = $tempDir.'/'.$convertFileName;
			$filedata=getimagesize($tempfile);
			$value["圖片素材寬度"] = $filedata[0];
			$value["圖片素材高度"] = $filedata[1];
			
			//將 圖片素材派送結果 影片派送時間 與 影片媒體編號 	影片媒體編號北/南 設為NULL  標記為未派送素材
			$sql.=",圖片素材派送結果=NULL,影片派送時間=NULL,影片媒體編號=NULL,影片媒體編號北=NULL,影片媒體編號南=NULL";
		}

		$sql.=" WHERE 素材識別碼=?";
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			$my->rollback();
			$my->close();
			exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('iissssiiisiissi',$_POST["素材類型識別碼"],$_POST["產業類型識別碼"],$_POST["素材名稱"],$_POST["素材說明"]
			,$_POST["素材原始檔名"],$value["文字素材內容"],$value["圖片素材寬度"],$value["圖片素材高度"],$value["影片素材秒數"],$value["影片畫質"]
			,$_POST["素材群組識別碼"],$_SESSION['AMS']['使用者識別碼'],$value["素材有效開始時間"],$value["素材有效結束時間"],$_POST['素材識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			$my->rollback();
			$my->close();
			exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			$my->rollback();
			$my->close();
			exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
				
		$feedback = array(
			"success" => true,
			"message" => "修改素材成功!",
			'id'=>intval($_POST['素材識別碼'])
		);
		$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'修改素材(識別碼:'.$_POST['素材識別碼'].')');
		if(isset($_POST['新素材檔案上傳'])){
			if($_POST['新素材檔案上傳']=='copy')
				copy($tempfile,'uploadedFile/'.intval($_POST['素材識別碼']).".".$explodeFileName[count($explodeFileName)-1]);
			else
				rename($tempfile,'uploadedFile/'.intval($_POST['素材識別碼']).".".$explodeFileName[count($explodeFileName)-1]);
		}
		$my->commit();
		$my->close();
		exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		
	}
	
	//下載素材檔案
	function downloadFileCheck(){
		global $my,$logger;
		//取得原始檔案名稱
		$sql="SELECT 素材原始檔名,圖片素材派送結果 FROM 素材 WHERE 素材識別碼 = ?";
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->bind_param('i',$_POST["素材識別碼"])){
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$res=$stmt->get_result()){
			$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		$row=$res->fetch_array();
		$ofile = $row['素材原始檔名'];
		//取得附檔名
		$fileType = explode('.',$ofile);
		$fileType = end($fileType);
		//取得檔案名稱(識別碼.附檔名)
		$fileName = $_POST['素材識別碼'].'.'.$fileType;
		$filePath = 'uploadedFile/'.$fileName;
		//檢查檔案是否存在
		if(!file_exists($filePath)){
			//檔案不存在
			$check = false;
			//檢查是否有圖片素材派送結果
			if($row['圖片素材派送結果']!=NULL && $row['圖片素材派送結果']!=''){
				require_once dirname(__FILE__).'/../tool/FTP.php';
				//有派送結果，取其中一個派送過的伺服器下載檔案
				$picserver = json_decode($row['圖片素材派送結果'],true);
				$picserver = $picserver[0];
				//逐個比較SERVER，找到派送過的server
				foreach(Config::$FTP_SERVERS as $area=>$servers){
					foreach($servers as $server){
						//找到上傳過的server
						if($server['host'] == $picserver){
							$遠端路徑=$server['專區banner圖片素材路徑'];
							//下載檔案
							if(FTP::get($server['host'],$server['username'],$server['password'],$filePath,$遠端路徑.'/_____AMS_'.$fileName)){
								//下載成功
								$check = true;
							}
							break 2;
						}
					}
				}
			}
			if(!$check)
			exit(json_encode(['success'=>false,'message'=>'原始檔案不存在!'],JSON_UNESCAPED_UNICODE));
		}
		//回傳可下載的檔案
		exit(json_encode(['success'=>true,'message'=>'success!','file'=>$filePath,'name'=>$ofile],JSON_UNESCAPED_UNICODE));
	}
	
	//下載檔案
	function downFile(){
		$file_name = $_POST['name'];
		$file_path = $_POST['file'];
		$file_size = filesize($file_path);
		header('Pragma: public');
		header('Expires: 0');
		header('Last-Modified: ' . gmdate('D, d M Y H:i ') . ' GMT');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: application/octet-stream');
		header('Content-Length: ' . $file_size);
		header('Content-Disposition: attachment; filename="' . $file_name . '";');
		header('Content-Transfer-Encoding: binary');
		readfile($file_path);
	}
	
	$my->close();
?>
	