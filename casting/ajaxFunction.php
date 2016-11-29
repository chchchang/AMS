<?php
	include('../tool/auth/authAJAX.php');
	define('PAGE_SIZE',20);
	if(isset($_POST['method'])){
		$CSMSPosition = ['首頁banner','專區banner','頻道short EPG banner','專區vod'];
		if($_POST['method']==='getSchedule'&&isset($_POST['版位識別碼'])) {
			$ordersArray=array();//記錄排程用array:[{排程1},{排程2}...]
			$hours=array();
			if(!isset($_POST['startTime'])) $_POST['startTime']=date('Y-m-d');
			if(!isset($_POST['endTime'])) $_POST['endTime']=date('Y-m-d',strtotime('+2 day'));
			$sql='
				SELECT 託播單名稱,託播單識別碼,廣告可被播出小時時段,廣告期間開始時間,廣告期間結束時間,託播單CSMS群組識別碼,託播單狀態識別碼
				,託播單送出行為識別碼,託播單送出後是否成功,託播單送出後內部錯誤訊息
				,版位類型.版位名稱 AS 版位類型名稱
				FROM 託播單 ,版位 ,版位 版位類型
				WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼 AND
				託播單.版位識別碼=? AND ((廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (廣告期間開始時間<=? AND 廣告期間結束時間>=?))
				AND 託播單.託播單狀態識別碼 IN ('.(isset($_POST['待確認排程'])?'6':'0,1,2,3,4').')
				ORDER BY 委刊單識別碼,託播單識別碼
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('issssss',$_POST['版位識別碼'],$_POST['startTime'],$_POST['endTime'],$_POST['startTime'],$_POST['endTime'],$_POST['startTime'],$_POST['endTime'])) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			while($row=$res->fetch_assoc()) {
				$ptn = $row['版位類型名稱'];
				$hours=array();
				$row['廣告可被播出小時時段']=explode(',',$row['廣告可被播出小時時段']);
				if($_POST['startTime']<$row['廣告期間開始時間']){
					$stt = $row['廣告期間開始時間'];
					$sh = explode(":",(explode(" ",$row['廣告期間開始時間'])[1]))[0];
				}else{
					$stt = $_POST['startTime'];
					$sh = '00';
				}
					
				if($_POST['endTime']>$row['廣告期間結束時間']){
					$edt = $row['廣告期間結束時間'];
					$eh = explode(":",(explode(" ",$row['廣告期間結束時間'])[1]))[0];
				}else{
					$edt = $_POST['endTime'];
					$eh = '23';
				}
				foreach($row['廣告可被播出小時時段'] as $v)
					if(intval($v)<=intval($eh)&&intval($v)>=intval($sh))
					$hours[]=intval($v);
				
				//顯示於排程表上的字串
				$upText = '['.$row['託播單名稱'].'] ['.$row['廣告期間開始時間'].'~'.$row['廣告期間結束時間'].'] ';
				//******************************其他參數
				$sql='
				SELECT 版位其他參數名稱,託播單其他參數值,版位.版位名稱
				FROM 託播單其他參數,版位,版位其他參數,託播單,版位 版位類型
				WHERE 託播單.託播單識別碼=? AND 版位.版位識別碼 = 託播單.版位識別碼 AND 版位其他參數.版位識別碼 = 版位.上層版位識別碼 AND 版位類型.版位識別碼 = 版位.上層版位識別碼
                AND 託播單其他參數.託播單識別碼 = 託播單.託播單識別碼 AND 版位其他參數順序 = 託播單其他參數順序
				';
				
				if(!$stmt=$my->prepare($sql)) {
					exit('無法準備statement，請聯絡系統管理員！');
				}
				
				if(!$stmt->bind_param('i',$row['託播單識別碼'])) {
					exit('無法繫結資料，請聯絡系統管理員！');
				}
				
				if(!$stmt->execute()) {
					exit('無法執行statement，請聯絡系統管理員！');
				}
				
				if(!$Configres=$stmt->get_result()) {
					exit('無法取得結果集，請聯絡系統管理員！');
				}
				$oindex = 0;//要放入orders中的哪個array
				while($otherConfig=$Configres->fetch_assoc()){
					//預設廣告與非預設廣告分開顯示
					if($otherConfig['版位其他參數名稱']=='sepgDefaultFlag'){
						if($otherConfig['託播單其他參數值']=='1'){
							$oindex=2;
						}
					}
					//內外廣分開顯示，但若已為預設廣告，不考慮內外廣類型
					else if($otherConfig['版位其他參數名稱']=='adType'){
						if($oindex != 2){
							if($otherConfig['託播單其他參數值']=='0'){
								$oindex=0;
							}
							else{
								$oindex=1;
							}
						}
					}
					else if($otherConfig['版位其他參數名稱']=='影片排序'){
							$upText.=' 排序:'.$otherConfig['託播單其他參數值'];
					}
					else if($otherConfig['版位其他參數名稱']=='bakadschdDisplaySequence'){
							$upText.=' 排序:'.$otherConfig['託播單其他參數值'];
					}
				}
				if(in_array($ptn,$CSMSPosition)){
					switch($oindex){
						case 0:
							$upText.=' 內廣';
							break;
						case 1:
							$upText.=' 外廣';
							break;
						case 2;
							$upText.=' 預設廣告';
							break;
					}
				}
				if($row['託播單狀態識別碼']==4){
					$upText.=' [投放系統待處理]';
				}
				//判斷是否有失敗的送出/取消送出
				else if(gettype($row['託播單送出後是否成功'])!='NULL'){
					switch($row['託播單送出行為識別碼']){
						case 1:
						case 2:
							$upText.=' [託播單送出';
							break;
						case 3:
							$upText.=' [託播單取消送出';
							break;
						default:
							break;
					}
					if($row['託播單送出後是否成功']==1)
						$upText.='成功]';
					else{
						if(in_array($ptn,$CSMSPosition)){
							$upText.='失敗] <button class = "showSendResult_CSMS" Id="'.$row['託播單識別碼'].'">觀看結果</button>';
						}
						else{
							$upText.='失敗]';
						}
					}
					if(gettype($row['託播單送出後內部錯誤訊息'])!='NULL'){
						$upText.=' '.$row['託播單送出後內部錯誤訊息'];
					}
				}
				$tableData = array('託播單代碼'=>$row['託播單識別碼'],'hours'=>$hours,'startTime'=>explode(" ",$stt)[1],'endTime'=>explode(" ",$edt)[1]
				,'upTitle'=>$upText);
				//若為CSMS託播單，更改標題文字與顯示文字
				if(in_array($ptn,$CSMSPosition)){
					$tableData['託播單代碼替換文字'] = $row['託播單識別碼'].'/'.$row['託播單CSMS群組識別碼'];
				}
				//加入排程表資料
				$ordersArray[$oindex][]= $tableData;
			}
			if(count($ordersArray)==0)
				$ordersArray[]=array();
			exit(json_encode(($ordersArray),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method']==='logInfo') {
				$logger->info($_SESSION['AMS']['使用者姓名'].' '.$_POST['info']);
		}
		else if($_POST['method']==='下載檔案'){
			/*建立臨時壓縮檔*/  
			$file = tempnam("tmp", "zip");  
			$zip = new ZipArchive;  
			$res = $zip->open($file, ZipArchive::CREATE|ZipArchive::OVERWRITE);  
			if ($res!==true) { exit('壓縮錯誤');}  
			  
			foreach ($filePathList as $filePath){  
			   $zip->addFile($filePath, $fileName);  
			}  
			$zip->close();  
			  
			ob_end_clean();  
			header('Content-type: application/octet-stream');  
			header('Content-Transfer-Encoding: Binary');  
			header('Content-disposition: attachment; filename=pics_list.zip');  
			  
			readfile($file);  
			unlink($file);   
			exit();
		}
	}
	
	function htmlescape($data){
		if(gettype($data)=="array")
			return array_map('htmlescape',$data);
		else
			return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	}
	
?>