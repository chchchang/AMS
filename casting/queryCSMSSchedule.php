<?php
	include('../tool/auth/authAJAX.php');
	
	header('Content-Type: application/json; charset=utf-8');
	
	require '../tool/OracleDB.php';
	
	//if(!isset($_GET['area'])||(array_search($_GET['area'],array('N','C','S'))===false))
		//exit(json_encode(array('error'=>'區域指定錯誤，必須為N、C、S其中之一！'),JSON_UNESCAPED_UNICODE));
	
	if($_GET['area']==='N'){
		$DB_U = Config::OMP_N_ORACLE_DB_USER;
		$DB_T_O = Config::OMP_N_ORACLE_DB_TABLE_OWNER;
		$DB_P = Config::OMP_N_ORACLE_DB_PASSWORD;
		$DB_S = Config::OMP_N_ORACLE_DB_CONN_STR;
	}
	else if($_GET['area']==='C'){
		$DB_U = Config::OMP_C_ORACLE_DB_USER;
		$DB_T_O = Config::OMP_C_ORACLE_DB_TABLE_OWNER;
		$DB_P = Config::OMP_C_ORACLE_DB_PASSWORD;
		$DB_S = Config::OMP_C_ORACLE_DB_CONN_STR;
	}
	else if($_GET['area']==='S'){
		$DB_U = Config::OMP_S_ORACLE_DB_USER;
		$DB_T_O = Config::OMP_S_ORACLE_DB_TABLE_OWNER;
		$DB_P = Config::OMP_S_ORACLE_DB_PASSWORD;
		$DB_S = Config::OMP_S_ORACLE_DB_CONN_STR;
	}
	else
		exit(json_encode([],JSON_UNESCAPED_UNICODE));
	
	$oracleDB=new OracleDB($DB_U,$DB_P,$DB_S);
	
	if(isset($_GET['SER_CODE'])&&isset($_GET['BNR_SEQUENCE'])&&isset($_GET['QUERY_DATE'])){
		//banner排程查詢
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
				to_char(CAS.SCHD_START_DATE,\'YYYY-MM-DD HH24:MI:SS\') SCHD_START_DATE,
				to_char(CAS.SCHD_END_DATE,\'YYYY-MM-DD HH24:MI:SS\') SCHD_END_DATE,
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
				CS.SER_CODE=:SER_CODE
				AND CSTB.BNR_SEQUENCE=:BNR_SEQUENCE
				AND CAS.SCHD_START_DATE <= TO_DATE(:QUERY_DATE_24,\'yyyymmddHH24MISS\')
				AND CAS.SCHD_END_DATE >= TO_DATE(:QUERY_DATE_00,\'yyyymmddHH24MISS\')
			ORDER BY 
				CAS.SCHD_STATUS,
				CAS.TRANSACTION_ID
		';
		$vars=array(
			array('bv_name'=>':SER_CODE','variable'=>$_GET['SER_CODE']),
			array('bv_name'=>':BNR_SEQUENCE','variable'=>$_GET['BNR_SEQUENCE']),
			array('bv_name'=>':QUERY_DATE_24','variable'=>$_GET['QUERY_DATE'].'235959'),
			array('bv_name'=>':QUERY_DATE_00','variable'=>$_GET['QUERY_DATE'].'000000')
		);
		$result=$oracleDB->getResultArray($sql,$vars);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_GET['CHAN_NUMBER'])&&isset($_GET['QUERY_DATE'])){
		//SEPG排程查詢
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
				to_char(CSS.SEPG_START_DATE,\'YYYY-MM-DD HH24:MI:SS\') SEPG_START_DATE,
				to_char(CSS.SEPG_END_DATE,\'YYYY-MM-DD HH24:MI:SS\') SEPG_END_DATE,
				CSS.SEPG_ASSIGN_START_TIME,
				CSS.SEPG_ASSIGN_END_TIME,
				CSS.SEPG_STATUS
			FROM '.$DB_T_O.'.OVA_CHANNEL OC
			INNER JOIN '.$DB_T_O.'.CS_SEPG_CHAN_RELATION CSCR ON OC.CHAN_RECID=CSCR.CHAN_RECID
			INNER JOIN '.$DB_T_O.'.CS_SEPG_SCHEDULE CSS ON CSCR.SEPG_SCHDID=CSS.SEPG_SCHDID
			INNER JOIN '.$DB_T_O.'.CS_AD CA ON CA.AD_RECID=CSS.AD_RECID
			INNER JOIN '.$DB_T_O.'.CS_LINK LK ON CA.LINK_RECID=LK.LINK_RECID
			WHERE
				OC.CHAN_NUMBER=:CHAN_NUMBER
				AND CSS.SEPG_START_DATE <= TO_DATE(:QUERY_DATE_24,\'yyyymmddHH24MISS\')
				AND CSS.SEPG_END_DATE >= TO_DATE(:QUERY_DATE_00,\'yyyymmddHH24MISS\')
			ORDER BY
				CSS.SEPG_STATUS,
				CSS.SEPG_TRANSACTION_ID
		';
		$vars=array(
			array('bv_name'=>':CHAN_NUMBER','variable'=>$_GET['CHAN_NUMBER']),
			array('bv_name'=>':QUERY_DATE_24','variable'=>$_GET['QUERY_DATE'].'235959'),
			array('bv_name'=>':QUERY_DATE_00','variable'=>$_GET['QUERY_DATE'].'000000')
		);
		$result=$oracleDB->getResultArray($sql,$vars);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_GET['SER_CODE'])&&isset($_GET['QUERY_DATE'])){
		//專區VOD排程查詢
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
				to_char(CBAS.BAKADSCHD_START_DATE,\'YYYY-MM-DD HH24:MI:SS\') BAKADSCHD_START_DATE,
				to_char(CBAS.BAKADSCHD_END_DATE,\'YYYY-MM-DD HH24:MI:SS\') BAKADSCHD_END_DATE,
				CBAS.BAKADSCHD_ASSIGN_START_TIME,
				CBAS.BAKADSCHD_ASSIGN_END_TIME,
				CBAS.BAKADSCHD_DISPLAY_SEQUENCE,
				CBAS.BAKADSCHD_DISPLAY_MAX,
				CAS.TRANSACTION_ID,
				CBAS.BAKADSCHD_STATUS,
				CSTB.BNR_SEQUENCE,
				CBAS.BAKADSCHD_HIT_COUNT,
				CBAS.BAKADSCHD_DISPLAY_MAX
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
				CS.SER_CODE=:SER_CODE
				AND CBAS.BAKADSCHD_START_DATE <= TO_DATE(:QUERY_DATE_24,\'yyyymmddHH24MISS\')
				AND CBAS.BAKADSCHD_END_DATE >= TO_DATE(:QUERY_DATE_00,\'yyyymmddHH24MISS\')
			ORDER BY
				CBAS.BAKADSCHD_STATUS,
				CBAS.BAKADSCHD_TRANSACTION_ID
		';
		$vars=array(
			array('bv_name'=>':SER_CODE','variable'=>$_GET['SER_CODE']),
			array('bv_name'=>':QUERY_DATE_24','variable'=>$_GET['QUERY_DATE'].'235959'),
			array('bv_name'=>':QUERY_DATE_00','variable'=>$_GET['QUERY_DATE'].'000000')
		);
		$result=$oracleDB->getResultArray($sql,$vars);
		
		//整理結果,將相同託播單但不同連動廣告的查詢結果合併，且將連動廣告依照BNR_SEQUENCE分類並用','串聯成字串
		$feedback=[];
		$bnrTid=[];
		foreach($result as $row){
			$transactionId = $row['BAKADSCHD_TRANSACTION_ID'];
			if(!array_key_exists($transactionId,$feedback))
				$feedback[$transactionId] = $row;
			if(!array_key_exists($transactionId,$feedback))
				$bnrTid[$transactionId] = [1=>[],2=>[]];
			if(!in_array($row['TRANSACTION_ID'],$bnrTid[$transactionId][$row['BNR_SEQUENCE']]))
					$bnrTid[$transactionId][$row['BNR_SEQUENCE']][]=$row['TRANSACTION_ID'];
		}
		foreach($feedback as $tid=>$data){
			$feedback[$tid]['TRANSACTION_ID1'] = implode(',',$bnrTid[$tid][1]);
			$feedback[$tid]['TRANSACTION_ID2'] = implode(',',$bnrTid[$tid][2]);
		}
		exit(json_encode(array_values($feedback),JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_GET['VOD+'])&&isset($_GET['QUERY_DATE'])){
		//VOD+廣告查詢
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
				to_char(CBAS.BAKADSCHD_START_DATE,\'YYYY-MM-DD HH24:MI:SS\') BAKADSCHD_START_DATE,
				to_char(CBAS.BAKADSCHD_END_DATE,\'YYYY-MM-DD HH24:MI:SS\') BAKADSCHD_END_DATE,
				CBAS.BAKADSCHD_ASSIGN_START_TIME,
				CBAS.BAKADSCHD_ASSIGN_END_TIME,
				CBAS.BAKADSCHD_DISPLAY_SEQUENCE,
				CBAS.BAKADSCHD_DISPLAY_MAX,
				CAS.TRANSACTION_ID,
				CBAS.BAKADSCHD_STATUS,
				CSTB.BNR_SEQUENCE,
				CBAS.BAKADSCHD_HIT_COUNT,
				CBAS.BAKADSCHD_DISPLAY_MAX
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
				CS.SER_CODE=:SER_CODE
				AND CBAS.BAKADSCHD_START_DATE <= TO_DATE(:QUERY_DATE_24,\'yyyymmddHH24MISS\')
				AND CBAS.BAKADSCHD_END_DATE >= TO_DATE(:QUERY_DATE_00,\'yyyymmddHH24MISS\')
			ORDER BY
				CBAS.BAKADSCHD_STATUS,
				CBAS.BAKADSCHD_TRANSACTION_ID
		';
		$vars=array(
			array('bv_name'=>':SER_CODE','variable'=>$_GET['SER_CODE']),
			array('bv_name'=>':QUERY_DATE_24','variable'=>$_GET['QUERY_DATE'].'235959'),
			array('bv_name'=>':QUERY_DATE_00','variable'=>$_GET['QUERY_DATE'].'000000')
		);
		$result=$oracleDB->getResultArray($sql,$vars);
		
		//整理結果,將相同託播單但不同連動廣告的查詢結果合併，且將連動廣告依照BNR_SEQUENCE分類並用','串聯成字串
		$feedback=[];
		$bnrTid=[];
		foreach($result as $row){
			$transactionId = $row['BAKADSCHD_TRANSACTION_ID'];
			if(!array_key_exists($transactionId,$feedback))
				$feedback[$transactionId] = $row;
			if(!array_key_exists($transactionId,$feedback))
				$bnrTid[$transactionId] = [1=>[],2=>[]];
			if(!in_array($row['TRANSACTION_ID'],$bnrTid[$transactionId][$row['BNR_SEQUENCE']]))
					$bnrTid[$transactionId][$row['BNR_SEQUENCE']][]=$row['TRANSACTION_ID'];
		}
		foreach($feedback as $tid=>$data){
			$feedback[$tid]['TRANSACTION_ID1'] = implode(',',$bnrTid[$tid][1]);
			$feedback[$tid]['TRANSACTION_ID2'] = implode(',',$bnrTid[$tid][2]);
		}
		exit(json_encode(array_values($feedback),JSON_UNESCAPED_UNICODE));
	}
	else{
		exit(json_encode(array('error'=>'參數錯誤！找不到正確的參數組合！'),JSON_UNESCAPED_UNICODE));
	}
?>
