<?php
	header("Content-Type:text/html; charset=utf-8");
	
	require_once dirname(__FILE__).'/../tool/MyDB.php';
	require_once dirname(__FILE__).'/../tool/FTP.php';
	require_once '../tool/OracleDB.php';
	$sql='
		SELECT 託播單CSMS群組識別碼,版位類型.版位名稱 版位類型名稱,版位.版位名稱,託播單識別碼,託播單送出行為識別碼,託播單送出後是否成功
		FROM 託播單 INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼 INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
		WHERE 託播單狀態識別碼=4 AND 託播單CSMS群組識別碼 IS NOT NULL
		ORDER BY 託播單CSMS群組識別碼,託播單識別碼
	';
	$my=new MyDB(true);
	$result=$my->getResultArray($sql);
	if($result===null)
		exit;
	
	//將上述從資料庫取得的結果合併為"以託播單CSMS群組識別碼為主索引的關聯式陣列"
	$result2=array();
	foreach($result as $row){
		//依據「託播單送出行為識別碼」決定送出行為
		switch($row['託播單送出行為識別碼']){
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
				$action = null;
			break;
		}
		
		$託播單CSMS群組識別碼=$row['託播單CSMS群組識別碼'];
		$result2[$託播單CSMS群組識別碼]['版位類型名稱']=$row['版位類型名稱'];	//一個"託播單CSMS群組識別碼"之下只會有一個"版位類型名稱"所以不需要再以陣列儲存
		if(preg_match('/_北$/',$row['版位名稱'])){
			$result2[$託播單CSMS群組識別碼]['託播單群組']['N'][]=array('版位名稱'=>$row['版位名稱'],'託播單識別碼'=>$row['託播單識別碼'],'託播單送出結果'=>['action'=>$action,'success'=>$row['託播單送出後是否成功']==0?false:true]);
		}
		else if(preg_match('/_中$/',$row['版位名稱'])){
			$result2[$託播單CSMS群組識別碼]['託播單群組']['C'][]=array('版位名稱'=>$row['版位名稱'],'託播單識別碼'=>$row['託播單識別碼'],'託播單送出結果'=>['action'=>$action,'success'=>$row['託播單送出後是否成功']==0?false:true]);
		}
		else if(preg_match('/_南$/',$row['版位名稱'])){
			$result2[$託播單CSMS群組識別碼]['託播單群組']['S'][]=array('版位名稱'=>$row['版位名稱'],'託播單識別碼'=>$row['託播單識別碼'],'託播單送出結果'=>['action'=>$action,'success'=>$row['託播單送出後是否成功']==0?false:true]);
		}
	}
	
	//根據"以託播單CSMS群組識別碼為主索引的關聯式陣列"到各區CSMS"處理結果資料夾路徑"下取得指定檔案(該檔案檔名由"版位類型名稱"、"託播單CSMS群組識別碼"、"託播單送出結果"等資訊組合而成)
	foreach($result2 as $託播單CSMS群組識別碼=>$result3){
		foreach($result3['託播單群組'] as $區域=>$託播單群組){
			//找出從處理結果路徑下載檔案所需的必要資訊
			$託播單識別碼群組=array();
			$託播單送出結果群組=array();
			foreach($託播單群組 as $託播單){
				$託播單識別碼群組[]=$託播單['託播單識別碼'];
				$託播單送出結果群組[]=$託播單['託播單送出結果'];
			}
			$託播單送出結果=$託播單送出結果群組[0];	//由於只有"頻道short EPG banner"版位的託播單因為要合併送出的關係而使得此群組內的託播單會超過1張，但這些託播單都是一起被送出，故送出結果也都相同，因此只需要參考任一個即可，目前固定參考第1個的值。
			/*
				託播單送出結果	下一個動作
				null	insert
				insert成功	delete
				insert失敗	insert
				update成功	delete
				update失敗	update
				delete成功	update
				delete失敗	delete
			*/
			if($託播單送出結果['action']===null)
				$下一個動作='insert';
				
			else if($託播單送出結果['action']==='insert'&&$託播單送出結果['success']===true)
				$下一個動作='delete';
				
			else if($託播單送出結果['action']==='insert'&&$託播單送出結果['success']===false)
				$下一個動作='insert';
			else if($託播單送出結果['action']==='update'&&$託播單送出結果['success']===true)
				$下一個動作='delete';
			else if($託播單送出結果['action']==='update'&&$託播單送出結果['success']===false)
				$下一個動作='update';
			else if($託播單送出結果['action']==='delete'&&$託播單送出結果['success']===true)
				$下一個動作='update';
			else if($託播單送出結果['action']==='delete'&&$託播單送出結果['success']===false)
				$下一個動作='delete';
			if(preg_match('/^首頁banner$/',$result3['版位類型名稱'])||preg_match('/^專區banner$/',$result3['版位類型名稱'])){
				$檔名='csad.'.$下一個動作.'.'.$託播單CSMS群組識別碼.'.xls.fin';
			}
			else if(preg_match('/^頻道short EPG banner$/',$result3['版位類型名稱'])){
				$檔名='sepg.'.$下一個動作.'.'.$託播單CSMS群組識別碼.'.xls.fin';
			}
			else if(preg_match('/^專區vod$/',$result3['版位類型名稱'])){
				$檔名='barkerad.'.$下一個動作.'.'.$託播單CSMS群組識別碼.'.xls.fin';
			}
			else{
				exit('版位類型名稱"'.$result3['版位類型名稱'].'"非屬白名單內，中止執行！');
			}
			
			//從處理結果路徑下載檔案進行處理
			$託播單識別碼群組列表=join(',',$託播單識別碼群組);
			$host=Config::$FTP_SERVERS['CSMS_'.$區域][0]['host'];
			$username=Config::$FTP_SERVERS['CSMS_'.$區域][0]['username'];
			$password=Config::$FTP_SERVERS['CSMS_'.$區域][0]['password'];
			$local=dirname(__FILE__).'/local/'.$區域.'/'.$檔名;
			$remote=Config::$FTP_SERVERS['CSMS_'.$區域][0]['處理結果資料夾路徑'].'/'.$檔名;;
		if(FTP::isFile($host,$username,$password,$remote)){	//遠端檔案存在
			if(FTP::get($host,$username,$password,$local,$remote)){	//下載檔案成功
				if(filesize($local)!==false){	//再次確認下載檔案成功
					/*
						下一個動作	處理結果	新的託播單狀態	新的託播單狀態識別碼	新的託播單送出結果(送出行為,送出後成功與否,送出後外部錯誤訊息)
						insert	失敗	確定	1	insert,false,<error>
						update	失敗	確定	1	update,false,<error>
						delete	成功	確定	1	delete,true,null
						insert	成功	送出	2	insert,true,null
						update	成功	送出	2	update,true,null
						delete	失敗	送出	2	delete,false,<error>
					*/
					$外部錯誤訊息 = null;
					$內部錯誤訊息 = null;
					$送出行為識別碼 = null;
					$送出是否成功 = null;
					//依照下一個動作決定送出行為識別碼
					switch($下一個動作){
						case'insert':
							$送出行為識別碼 = 1;
						break;
						case'update':
							$送出行為識別碼 = 2;
						break;
						case'delete':
							$送出行為識別碼 = 3;
						break;
					}
					
					
					if(filesize($local)===0){	//檔案大小等於0表示處理結果為成功
						$送出是否成功=1;
						//送出後的雙重檢查，若不通過，記錄內部錯誤訊息:投放系統託播單資訊與AMS不一致
						$doubleCheck=doubleCheckData($result3['版位類型名稱'],$下一個動作,$區域,$託播單識別碼群組列表,$託播單CSMS群組識別碼,$託播單識別碼群組);
						if(!$doubleCheck['success']){
							$送出是否成功 = 0;
							$新的託播單狀態識別碼=($下一個動作==='delete')?2:1;
						}
						else{
							$送出是否成功 = 1;
							$新的託播單狀態識別碼=($下一個動作==='delete')?1:2;
						}
						//刪除本地檔案
						unlink('../order/851/'.$託播單識別碼群組[0].'.xls');
						$內部錯誤訊息 = $doubleCheck['inner_error'];
					}
					else{	//檔案大小不等於0表示處理結果為失敗
						$新的託播單狀態識別碼=($下一個動作==='delete')?2:1;
						$送出是否成功 = 0;
						$外部錯誤訊息 = '<error>';
					}
					
					$stmt=$my->prepare('UPDATE 託播單 SET 託播單狀態識別碼=?,託播單送出行為識別碼=?,託播單送出後是否成功=?,託播單送出後內部錯誤訊息=?,託播單送出後外部錯誤訊息=?
										WHERE 託播單識別碼 IN('.$託播單識別碼群組列表.')');
					$stmt->bind_param('iiiss',$新的託播單狀態識別碼,$送出行為識別碼,$送出是否成功,$內部錯誤訊息,$外部錯誤訊息);
					if($stmt->execute()){
						FTP::delete($host,$username,$password,$remote);	//處理完成後刪除遠端上的檔案
						if($送出是否成功 == 1){
							$updateOrderWithSameMaterial = updateOrderWithSameMaterial($託播單識別碼群組);
							$結果=' 下載遠端檔案成功，並且更新託播單狀態識別碼與送出結果成功。'.$updateOrderWithSameMaterial['message'];
						}
						else
							$結果=' 下載遠端檔案成功，並且更新託播單狀態識別碼與送出結果成功。';
					}
					else
						$結果=' 下載遠端檔案成功，但是更新託播單狀態識別碼與送出結果失敗！';
				}
				else
					$結果='下載遠端檔案成功，但是取得本機檔案大小失敗！';
			}
			else
				$結果='下載遠端檔案失敗！';
		}
		else
			$結果='遠端檔案不存在！';
			
			echo date('Y-m-d H:i:s')."\t".'區域："'.$區域.'"'."\t".'遠端："'.$remote.'"'."\t".'本機："'.$local.'"'."\t".'託播單CSMS群組識別碼："'.$託播單CSMS群組識別碼.'"'."\t".'託播單識別碼群組列表："'.$託播單識別碼群組列表.'"'."\t".'結果："'.$結果.'"'."\n";
		}
	}
	
	function doubleCheckData($版位類型名稱,$下一個動作,$區域,$託播單識別碼群組列表,$託播單CSMS群組識別碼,$託播單識別碼群組){
		require_once '../tool/PHPExcel/Classes/PHPExcel.php';
		require_once '../tool/OracleDB.php';
		//return true;
		//讀取本地送出檔案用reader
		$filename = '../order/851/'.$託播單識別碼群組[0].'.xls';
		$reader = PHPExcel_IOFactory::load($filename);
		$sheet=$reader->getActiveSheet();
		//取得OMP資料庫檔案並比較
		if($區域==='N'){
			$DB_U = Config::OMP_N_ORACLE_DB_USER;
			$DB_T_O = Config::OMP_N_ORACLE_DB_TABLE_OWNER;
			$DB_P = Config::OMP_N_ORACLE_DB_PASSWORD;
			$DB_S = Config::OMP_N_ORACLE_DB_CONN_STR;
		}
		else if($區域==='C'){
			$DB_U = Config::OMP_C_ORACLE_DB_USER;
			$DB_T_O = Config::OMP_C_ORACLE_DB_TABLE_OWNER;
			$DB_P = Config::OMP_C_ORACLE_DB_PASSWORD;
			$DB_S = Config::OMP_C_ORACLE_DB_CONN_STR;
		}
		else{
			$DB_U = Config::OMP_S_ORACLE_DB_USER;
			$DB_T_O = Config::OMP_S_ORACLE_DB_TABLE_OWNER;
			$DB_P = Config::OMP_S_ORACLE_DB_PASSWORD;
			$DB_S = Config::OMP_S_ORACLE_DB_CONN_STR;
		}
		
		$oracleDB=new OracleDB($DB_U,$DB_P,$DB_S);
			
		$inner_error = '';//記錄內部錯誤訊息用
		//OMP資料STATUS:0 準備中, 1 上架, 2 下架
		if($版位類型名稱 == '首頁banner' || $版位類型名稱 == '專區banner'){
			$sql='
				SELECT
					CAS.TRANSACTION_ID,
					CA.AD_CODE,
					CA.AD_TYPE,
					CA.AD_NAME,
					LK.LINK_TYPE,
					LK.LINK_CHAN_RECID,
					LK.LINK_CAT_RECID,
					LK.LINK_VODCNT_RECID,
					LK.LINK_SRVC_RECID,
					CA.AD_IMG_OFF,
					CB.BNR_SIZETYPE,
					CS.SER_CODE,
					CSTB.BNR_SEQUENCE,
					to_char(CAS.SCHD_START_DATE,\'YYYY/MM/DD HH24:MI\') SCHD_START_DATE,
					to_char(CAS.SCHD_END_DATE,\'YYYY/MM/DD HH24:MI\') SCHD_END_DATE,
					CAS.ASSIGN_START_TIME,
					CAS.ASSIGN_END_TIME,
					CAS.SCHD_STATUS
				FROM '.$DB_T_O.'.CS_SERVICE CS
				INNER JOIN '.$DB_T_O.'.CS_SER_TMP_BNR CSTB ON CS.SER_RECID=CSTB.SER_RECID
				INNER JOIN '.$DB_T_O.'.CS_AD_SCHEDULE CAS ON CSTB.CS_STB_RECID=CAS.CS_STB_RECID
				INNER JOIN '.$DB_T_O.'.CS_BANNER CB ON CSTB.BNR_RECID=CB.BNR_RECID
				INNER JOIN '.$DB_T_O.'.CS_AD CA ON CA.AD_RECID=CAS.AD_RECID
				INNER JOIN '.$DB_T_O.'.CS_LINK LK ON CA.LINK_RECID=LK.LINK_RECID
				WHERE
					CAS.TRANSACTION_ID=:TID
			';
			$vars=array(
				array('bv_name'=>':TID','variable'=>$託播單CSMS群組識別碼)
			);
			$result=$oracleDB->getResultArray($sql,$vars);
			
			//比較結果
			switch($下一個動作){
				case 'insert':
					if(count($result)==0)
						return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未上架');
				case 'update':
					$data = $result[0];
					if($data['SCHD_STATUS'] == 2 )
						return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未上架');
					
					//轉換開始與結束時間
					$data['ASSIGN_START_TIME'] = str_pad((intval($data['ASSIGN_START_TIME'])/60),2,'0',STR_PAD_LEFT).':'.str_pad((intval($data['ASSIGN_START_TIME'])%60),2,'0',STR_PAD_LEFT);
					$data['ASSIGN_END_TIME'] = str_pad((intval($data['ASSIGN_END_TIME'])/60),2,'0',STR_PAD_LEFT).':'.str_pad((intval($data['ASSIGN_END_TIME'])%60),2,'0',STR_PAD_LEFT);
					
					
					//比較OMP資料是否與介接檔案相同
					if(strval($data['TRANSACTION_ID'])!=strval($sheet->getCell('A2')->getValue()))
						$inner_error.=' TransactionID';
					if(strval($data['AD_CODE'])!=strval($sheet->getCell('B2')->getValue()))
						$inner_error.=' 素材代碼';
					if(strval($data['AD_TYPE'])!=strval($sheet->getCell('C2')->getValue()))
						$inner_error.=' 內外廣';
					if(strval($data['AD_NAME'])!=strval($sheet->getCell('D2')->getValue()))
						$inner_error.=' 廣告名稱';
					if(strval($data['LINK_TYPE'])!=strval($sheet->getCell('E2')->getValue()))
						$inner_error.=' 點擊開啟類型';
					if(strval($data['AD_IMG_OFF'])!=strval($sheet->getCell('G2')->getValue()))
						$inner_error.=' adImgOff';
					/*if(strval($data['BNR_SIZETYPE'])!=strval($sheet->getCell('H2')->getValue()))
						$inner_error.=' adSizeType';*/
					if(strval($data['SER_CODE'])!=strval($sheet->getCell('I2')->getValue()))
						$inner_error.=' serCode';
					if(strval($data['BNR_SEQUENCE'])!=strval($sheet->getCell('J2')->getValue()))
						$inner_error.=' bnrSequence';
					if(strval($data['SCHD_START_DATE'])!=strval($sheet->getCell('K2')->getValue()))
						$inner_error.=' 開始日期';
					if(strval($data['SCHD_END_DATE'])!=strval($sheet->getCell('L2')->getValue()))
						$inner_error.=' 結束日期';
					if(strval($data['ASSIGN_START_TIME'])!=strval($sheet->getCell('M2')->getValue()))
						$inner_error.=' 開始時間';
					if(strval($data['ASSIGN_END_TIME'])!=strval($sheet->getCell('N2')->getValue()))
						$inner_error.=' 結束時間';
						
					/*if(!(strval($sheet->getCell('E2')->getValue())=='NONE'||
						(strval($data['LINK_CHAN_RECID'])==strval($sheet->getCell('F2')->getValue()) ||strval($data['LINK_CAT_RECID'])==strval($sheet->getCell('F2')->getValue())
						||strval($data['LINK_VODCNT_RECID'])==strval($sheet->getCell('F2')->getValue())||strval($data['LINK_SRVC_RECID'])==strval($sheet->getCell('F2')->getValue()))
						)){
							$inner_error.=' 點擊開啟位址';
						}*/
					if($inner_error != '')
						return array('success'=>true,'inner_error'=>'OMP資料庫資訊不同:'.$inner_error);
					else
						return array('success'=>true,'inner_error'=>null);
				break;
				case 'delete':
					$data = $result[0];
					if($data['SCHD_STATUS'] != 2 )
						return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未下架');
					
					return array('success'=>true,'inner_error'=>null);
				break;
			}
		}
		else if($版位類型名稱 == '頻道short EPG banner'){
			$sql='
				SELECT
					CSS.SEPG_TRANSACTION_ID,
					OC.CHAN_NUMBER,
					CA.AD_CODE,
					CA.AD_TYPE,
					CA.AD_NAME,
					LK.LINK_TYPE,
					LK.LINK_CHAN_RECID,
					LK.LINK_CAT_RECID,
					LK.LINK_VODCNT_RECID,
					LK.LINK_SRVC_RECID,
					CA.AD_IMG_OFF,
					CSS.SEPG_DEFAULT_FLAG,
					to_char(CSS.SEPG_START_DATE,\'YYYY/MM/DD HH24:MI\') SEPG_START_DATE,
					to_char(CSS.SEPG_END_DATE,\'YYYY/MM/DD HH24:MI\') SEPG_END_DATE,
					CSS.SEPG_ASSIGN_START_TIME,
					CSS.SEPG_ASSIGN_END_TIME,
					CSS.SEPG_STATUS
				FROM '.$DB_T_O.'.OVA_CHANNEL OC
				INNER JOIN '.$DB_T_O.'.CS_SEPG_CHAN_RELATION CSCR ON OC.CHAN_RECID=CSCR.CHAN_RECID
				INNER JOIN '.$DB_T_O.'.CS_SEPG_SCHEDULE CSS ON CSCR.SEPG_SCHDID=CSS.SEPG_SCHDID
				INNER JOIN '.$DB_T_O.'.CS_AD CA ON CA.AD_RECID=CSS.AD_RECID
				INNER JOIN '.$DB_T_O.'.CS_LINK LK ON CA.LINK_RECID=LK.LINK_RECID
				WHERE
					CSS.SEPG_TRANSACTION_ID=:TID
				ORDER BY
					CSS.SEPG_START_DATE,
					CSS.SEPG_TRANSACTION_ID
			';
			$vars=array(
				array('bv_name'=>':TID','variable'=>$託播單CSMS群組識別碼)
			);
			$result=$oracleDB->getResultArray($sql,$vars);
			
			//比較結果
			switch($下一個動作){
				case 'insert':
					if(count($result)==0)
						return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未上架');
				case 'update':
					//記錄出現過的頻道
					$channel = [];
					foreach($result as $data){
						if($data['SEPG_STATUS'] == 2 )
							return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未上架');
						
						//轉換開始與結束時間
						$data['SEPG_ASSIGN_START_TIME'] = str_pad((intval($data['SEPG_ASSIGN_START_TIME'])/60),2,'0',STR_PAD_LEFT).':'.str_pad((intval($data['SEPG_ASSIGN_START_TIME'])%60),2,'0',STR_PAD_LEFT);
						$data['SEPG_ASSIGN_END_TIME'] = str_pad((intval($data['SEPG_ASSIGN_END_TIME'])/60),2,'0',STR_PAD_LEFT).':'.str_pad((intval($data['SEPG_ASSIGN_END_TIME'])%60),2,'0',STR_PAD_LEFT);
						//比較OMP資料是否與介接檔案相同
						if($inner_error == ''){//因多個頻道共用相同資料，其中一個頻道有找到錯誤則不再檢查其他頻道
							if(strval($data['SEPG_TRANSACTION_ID'])!=strval($sheet->getCell('A2')->getValue())){
								$inner_error.=' TransactionID';
							}
							if(strval($data['AD_CODE'])!=strval($sheet->getCell('C2')->getValue())){
								$inner_error.=' 素材代碼';
							}
							if(strval($data['AD_TYPE'])!=strval($sheet->getCell('D2')->getValue())){
								$inner_error.=' 內外廣';
							}
							if(strval($data['AD_NAME'])!=strval($sheet->getCell('E2')->getValue())){
								$inner_error.=' 廣告名稱';
							}
							if(strval($data['LINK_TYPE'])!=strval($sheet->getCell('F2')->getValue())){
								$inner_error.=' 點擊開啟類型';
							}
							/*if(!(strval($sheet->getCell('F2')->getValue())=='NONE'||
									(strval($data['LINK_CHAN_RECID'])==strval($sheet->getCell('G2')->getValue()) ||strval($data['LINK_CAT_RECID'])==strval($sheet->getCell('G2')->getValue())
									||strval($data['LINK_VODCNT_RECID'])==strval($sheet->getCell('G2')->getValue())||strval($data['LINK_SRVC_RECID'])==strval($sheet->getCell('G2')->getValue())
									))){
								$inner_error.=' 點擊開啟位址';
							}*/
							if(strval($data['AD_IMG_OFF'])!=strval($sheet->getCell('H2')->getValue())){
								$inner_error.=' adImgOff';
							}
							if(strval($data['SEPG_DEFAULT_FLAG'])!=strval($sheet->getCell('I2')->getValue())){
								$inner_error.=' 是否為預設廣告';
							}
							if(strval($data['SEPG_START_DATE'])!=strval($sheet->getCell('J2')->getValue())){
								$inner_error.=' 開始日期';
							}
							if(strval($data['SEPG_END_DATE'])!=strval($sheet->getCell('K2')->getValue())){
								$inner_error.=' 結束日期';
							}
							if(strval($data['SEPG_ASSIGN_START_TIME'])!=strval($sheet->getCell('L2')->getValue())){
								$inner_error.=' 開始時間';
							}
							if(strval($data['SEPG_ASSIGN_END_TIME'])!=strval($sheet->getCell('M2')->getValue())){
								$inner_error.=' 結束時間';
							}
						}
						
						//記錄播放頻道
						$channel[] = strval(intval($data['CHAN_NUMBER']));
					}
					//比較頻道是否相同
					$t1 = explode(',',strval($sheet->getCell('B2')->getValue()));
					$t2 = array_intersect($t1,$channel);
					/*print_r($channel);echo '<br>';
					print_r($t1);echo '<br>';
					print_r($t2);echo '<br>';*/
					if($inner_error != '')
						return array('success'=>true,'inner_error'=>'OMP資料庫資訊不同:'.$inner_error);
					else
						return array('success'=>true,'inner_error'=>null);
				break;
				case 'delete':
					foreach($result as $data)
						if($data['SEPG_STATUS'] != 2 )
							return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未下架');
					
					return array('success'=>true,'inner_error'=>null);
				break;
			}
		}
		else if($版位類型名稱 == '專區vod'){
			$sql='
				SELECT
					CBAS.BAKADSCHD_TRANSACTION_ID,
					CBA.BAKAD_NAME,
					CBA.SD_VODCNT_RECID,
					CBA.HD_VODCNT_RECID,
					OVCSD.VODCNT_TITLE AS SD_VODCNT_TITLE,
					OVCHD.VODCNT_TITLE AS HD_VODCNT_TITLE,
					CBA.BAKAD_DISPLAY_MAX,
					LK.LINK_TYPE,
					LK.LINK_CHAN_RECID,
					LK.LINK_CAT_RECID,
					LK.LINK_VODCNT_RECID,
					LK.LINK_SRVC_RECID,
					CS.SER_CODE,
					to_char(CBAS.BAKADSCHD_START_DATE,\'YYYY/MM/DD HH24:MI\') BAKADSCHD_START_DATE,
					to_char(CBAS.BAKADSCHD_END_DATE,\'YYYY/MM/DD HH24:MI\') BAKADSCHD_END_DATE,
					CBAS.BAKADSCHD_ASSIGN_START_TIME,
					CBAS.BAKADSCHD_ASSIGN_END_TIME,
					CBAS.BAKADSCHD_DISPLAY_SEQUENCE,
					CBAS.BAKADSCHD_DISPLAY_MAX,
					CAS.TRANSACTION_ID,
					CBAS.BAKADSCHD_STATUS,
					CSTB.BNR_SEQUENCE
				FROM '.$DB_T_O.'.CS_BARKER_AD_SCHEDULE CBAS
				INNER JOIN '.$DB_T_O.'.CS_SERVICE CS ON CBAS.SER_RECID=CS.SER_RECID
				INNER JOIN '.$DB_T_O.'.CS_BARKER_AD CBA ON CBA.BAKAD_RECID=CBAS.BAKAD_RECID
				INNER JOIN '.$DB_T_O.'.CS_LINK LK ON CBA.LINK_RECID=LK.LINK_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.CS_GROUP_AD CGA ON CBAS.BAKADSCHD_RECID=CGA.BAKADSCHD_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.CS_AD_SCHEDULE CAS ON CAS.AD_SCHDID=CGA.AD_SCHDID
				LEFT OUTER JOIN '.$DB_T_O.'.CS_SER_TMP_BNR CSTB ON CSTB.CS_STB_RECID=CAS.CS_STB_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.OVA_VOD_CONTENT OVCSD ON CBA.SD_VODCNT_RECID=OVCSD.VODCNT_RECID
				LEFT OUTER JOIN '.$DB_T_O.'.OVA_VOD_CONTENT OVCHD ON CBA.HD_VODCNT_RECID=OVCHD.VODCNT_RECID
				WHERE
					CBAS.BAKADSCHD_TRANSACTION_ID=:TID
				ORDER BY
					CBAS.BAKADSCHD_START_DATE,
					CBAS.BAKADSCHD_TRANSACTION_ID
			';
			$vars=array(
				array('bv_name'=>':TID','variable'=>$託播單CSMS群組識別碼)
			);
			$result=$oracleDB->getResultArray($sql,$vars);
			
			//整理結果,將相同託播單但不同連動廣告的查詢結果合併，且將連動廣告依照BNR_SEQUENCE分類並用','串聯成字串
			$resultTemp=[];
			$bnrTid=[];
			foreach($result as $row){
				$transactionId = $row['BAKADSCHD_TRANSACTION_ID'];
				if(!array_key_exists($transactionId,$resultTemp))
					$resultTemp[$transactionId] = $row;
				if(!array_key_exists($transactionId,$resultTemp))
					$bnrTid[$transactionId] = [1=>[],2=>[]];
				if(!in_array($row['TRANSACTION_ID'],$bnrTid[$transactionId][$row['BNR_SEQUENCE']]))
					$bnrTid[$transactionId][$row['BNR_SEQUENCE']][]=$row['TRANSACTION_ID'];
			}
			foreach($resultTemp as $tid=>$data){
				$resultTemp[$tid]['TRANSACTION_ID1'] = implode(',',$bnrTid[$tid][1]);
				$resultTemp[$tid]['TRANSACTION_ID2'] = implode(',',$bnrTid[$tid][2]);
			}
			$result = $resultTemp;
			//比較結果
			switch($下一個動作){
				case 'insert':
					if(count($result)==0)
						return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未上架');
				case 'update':
					$data = current($result);
					if($data['BAKADSCHD_STATUS'] == 2 )
						return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未上架');
					
					$data['BAKADSCHD_ASSIGN_START_TIME'] = str_pad((intval($data['BAKADSCHD_ASSIGN_START_TIME'])/60),2,'0',STR_PAD_LEFT).':'.str_pad((intval($data['BAKADSCHD_ASSIGN_START_TIME'])%60),2,'0',STR_PAD_LEFT);
					$data['BAKADSCHD_ASSIGN_END_TIME'] = str_pad((intval($data['BAKADSCHD_ASSIGN_END_TIME'])/60),2,'0',STR_PAD_LEFT).':'.str_pad((intval($data['BAKADSCHD_ASSIGN_END_TIME'])%60),2,'0',STR_PAD_LEFT);
					
					if($data['BAKADSCHD_START_DATE'] == null)$data['BAKADSCHD_START_DATE'] = '';
					if($data['BAKADSCHD_END_DATE'] == null)$data['BAKADSCHD_END_DATE'] = '';
					
					//比較OMP資料是否與介接檔案相同
					if(strval($data['BAKADSCHD_TRANSACTION_ID'])!=strval($sheet->getCell('A2')->getValue()))
						$inner_error.=' TransactionID';
					if(strval($data['SD_VODCNT_TITLE'])!=strval($sheet->getCell('B2')->getValue()))
						$inner_error.=' SD影片名稱';
					if(strval($data['HD_VODCNT_TITLE'])!=strval($sheet->getCell('C2')->getValue()))
						$inner_error.=' HD影片名稱';
					if(strval($data['BAKAD_DISPLAY_MAX'])!=strval($sheet->getCell('D2')->getValue()))
						$inner_error.=' 影片投放上限';
					if(strval($data['LINK_TYPE'])!=strval($sheet->getCell('E2')->getValue()))
						$inner_error.=' 點擊開啟類型';
					/*if(!(strval($sheet->getCell('E2')->getValue())=='NONE'||
							(strval($data['LINK_CHAN_RECID'])==strval($sheet->getCell('F2')->getValue()) ||strval($data['LINK_CAT_RECID'])==strval($sheet->getCell('F2')->getValue())
							||strval($data['LINK_VODCNT_RECID'])==strval($sheet->getCell('F2')->getValue())||strval($data['LINK_SRVC_RECID'])==strval($sheet->getCell('F2')->getValue())
							))){
							$inner_error.=' 點擊開啟位址';
							}*/
					if(strval($data['SER_CODE'])!=strval($sheet->getCell('G2')->getValue()))
						$inner_error.=' serCode';
					if(strval($data['BAKADSCHD_START_DATE'])!=strval($sheet->getCell('H2')->getValue()))
						$inner_error.=' 開始日期';
					if(strval($data['BAKADSCHD_END_DATE'])!=strval($sheet->getCell('I2')->getValue()))
						$inner_error.=' 結束日期';
					if(strval($data['BAKADSCHD_ASSIGN_START_TIME'])!=strval($sheet->getCell('J2')->getValue()))
						$inner_error.=' 開始時間';
					if(strval($data['BAKADSCHD_ASSIGN_END_TIME'])!=strval($sheet->getCell('K2')->getValue()))
						$inner_error.=' 結束時間';
					if(strval($data['BAKADSCHD_DISPLAY_SEQUENCE'])!=strval($sheet->getCell('L2')->getValue()))
						$inner_error.=' 投放順序';
					if(strval($data['BAKADSCHD_DISPLAY_MAX'])!=strval($sheet->getCell('M2')->getValue()))
						$inner_error.=' 專區排程上限';
						
					//比較連動廣告
					$t1 =explode(',',strval($data['TRANSACTION_ID1']));
					$t2 = explode(',',strval($sheet->getCell('N2')->getValue()));
					$t3 = array_intersect($t2,$t1);
					/*print_r($t1);echo '<br>';
					print_r($t2);echo '<br>';
					print_r($t3);echo '<br>';*/
					if($t3 != $t2){
						$inner_error.=' 連動廣告1';
					}
						
					$t1 =explode(',',strval($data['TRANSACTION_ID2']));
					$t2 = explode(',',strval($sheet->getCell('O2')->getValue()));
					$t3 = array_intersect($t2,$t1);
					/*print_r($t1);echo '<br>';
					print_r($t2);echo '<br>';
					print_r($t3);echo '<br>';*/
					if($t3 != $t2){
						$inner_error.=' 連動廣告2';
					}
						
						
					if($inner_error != '')
						return array('success'=>true,'inner_error'=>'OMP資料庫資訊不同:'.$inner_error);
					else
						return array('success'=>true,'inner_error'=>null);
				break;
				case 'delete':
					$data =  current($result);
					if($data['BAKADSCHD_STATUS'] != 2 )
						return array('success'=>false,'inner_error'=>'檔案處理成功但託播單未下架');
					
					return array('success'=>true,'inner_error'=>null);
				break;
			}
		}
	}
	function updateOrderWithSameMaterial($託播單識別碼群組){
		require_once('../tool/MyDB.php');
		$my=new MyDB(true);
		//取得這次更新會影響的託播單識別碼 (至少會有一張)
		$sql='
			SELECT DISTINCT A.託播單識別碼
			FROM
				託播單素材 A
				JOIN 託播單 託播單A ON 託播單A.託播單識別碼 = A.託播單識別碼
				JOIN 版位 版位A ON 託播單A.版位識別碼 = 版位A.版位識別碼 
				JOIN 託播單素材 B ON A.素材識別碼 = B.素材識別碼
				JOIN 託播單 託播單B ON 託播單B.託播單識別碼 = B.託播單識別碼
				JOIN 版位 版位B ON 託播單B.版位識別碼 = 版位B.版位識別碼 AND SUBSTRING_INDEX(版位A.版位名稱, "_", -1) = SUBSTRING_INDEX(版位B.版位名稱, "_", -1)
			WHERE
				B.託播單識別碼 = ?
				AND 託播單A.託播單狀態識別碼 IN (2,4)
		';
		if(!$result = $my->getResultArray($sql,'i',$託播單識別碼群組[0]))
			return(['success'=>false,'message'=>'資料庫錯誤，無法取得使用相同素材的託播單資訊']);
		$changedOrderIds = [];
		foreach($result as $row){
			$changedOrderIds[]=$row['託播單識別碼'];	
		}
		
		//鎖定資料表
		$my->begin_transaction();
		
		//更新使用相同素材且同區域的待處理/送出狀態託播單之 託播單名稱 可否點擊 點擊後開啟類型 點擊後開啟位址
		$sql='UPDATE 
				託播單素材 A
				JOIN 託播單 託播單A ON 託播單A.託播單識別碼 = A.託播單識別碼
				JOIN 版位 版位A ON 託播單A.版位識別碼 = 版位A.版位識別碼 
				JOIN 託播單素材 B ON A.素材識別碼 = B.素材識別碼
				JOIN 託播單 託播單B ON 託播單B.託播單識別碼 = B.託播單識別碼
				JOIN 版位 版位B ON 託播單B.版位識別碼 = 版位B.版位識別碼 AND SUBSTRING_INDEX(版位A.版位名稱, "_", -1) = SUBSTRING_INDEX(版位B.版位名稱, "_", -1)
				
			SET 
				託播單A.託播單名稱 = 託播單B.託播單名稱,
				A.可否點擊=B.可否點擊,
				A.點擊後開啟類型=B.點擊後開啟類型,
				A.點擊後開啟位址=B.點擊後開啟位址
			WHERE
				B.託播單識別碼 = ?
				AND 託播單A.託播單狀態識別碼 IN (2,4)
		';
		if(!$my->execute($sql,'i',$託播單識別碼群組[0])){
			return(['success'=>false,'message'=>'資料庫錯誤，無法修改使用相同素材的託播單資訊']);
			$my->rollback();
			$my->close();
		}
		
		//更新使用相同素材且同區域的待處理/送出狀態託播單之 內外廣設定
		$sql='UPDATE 
				託播單其他參數 A
				JOIN 託播單 託播單A ON 託播單A.託播單識別碼 = A.託播單識別碼
				JOIN 版位 版位A ON 託播單A.版位識別碼 = 版位A.版位識別碼
				JOIN 版位其他參數 版位其他參數A ON 版位其他參數A.版位識別碼 = 版位A.上層版位識別碼 AND 版位其他參數A.版位其他參數順序 = A.託播單其他參數順序
				JOIN 版位其他參數 版位其他參數B ON 版位其他參數B.版位其他參數名稱 = 版位其他參數A.版位其他參數名稱
				JOIN 版位 版位B ON 版位B.上層版位識別碼 = 版位其他參數B.版位識別碼
				JOIN 託播單 託播單B ON 託播單B.版位識別碼 = 版位B.版位識別碼
				JOIN 託播單其他參數 B ON B.託播單識別碼 = 託播單B.託播單識別碼 AND 版位其他參數B.版位其他參數順序 = B.託播單其他參數順序
			SET 
				A.託播單其他參數值=B.託播單其他參數值
			WHERE
				B.託播單識別碼 = ?
				AND A.託播單識別碼 IN ('.implode(',',$changedOrderIds).')
				AND 版位其他參數B.版位其他參數名稱 ="adType"
		';
		if(!$my->execute($sql,'i',$託播單識別碼群組[0])){
			return(['success'=>false,'message'=>'資料庫錯誤，無法修改使用相同素材的託播單資訊']);
			$my->rollback();
			$my->close();
		}
		
		$my->commit();
		$my->close();
		
		return(['success'=>true,'message'=>'使用相同素材且同區的託播單'.implode(',',$changedOrderIds).'已被被修改']);
	}
	
?>