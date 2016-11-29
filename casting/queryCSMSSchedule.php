<?php
	include('../tool/auth/authAJAX.php');
	
	header('Content-Type: application/json; charset=utf-8');
	
	require '../tool/OracleDB.php';
	
	//if(!isset($_GET['area'])||(array_search($_GET['area'],array('N','C','S'))===false))
		//exit(json_encode(array('error'=>'區域指定錯誤，必須為N、C、S其中之一！'),JSON_UNESCAPED_UNICODE));
	
	if($_GET['area']==='N')
		$oracleDB=new OracleDB(Config::OMP_N_ORACLE_DB_USER,Config::OMP_N_ORACLE_DB_PASSWORD,Config::OMP_N_ORACLE_DB_CONN_STR);
	else if($_GET['area']==='C')
		$oracleDB=new OracleDB(Config::OMP_C_ORACLE_DB_USER,Config::OMP_C_ORACLE_DB_PASSWORD,Config::OMP_C_ORACLE_DB_CONN_STR);
	else if($_GET['area']==='S')
		$oracleDB=new OracleDB(Config::OMP_S_ORACLE_DB_USER,Config::OMP_S_ORACLE_DB_PASSWORD,Config::OMP_S_ORACLE_DB_CONN_STR);
	else
		exit(json_encode([],JSON_UNESCAPED_UNICODE));
	
	if(isset($_GET['SER_CODE'])&&isset($_GET['BNR_SEQUENCE'])&&isset($_GET['QUERY_DATE'])){
		$sql='
			SELECT
				CAS.TRANSACTION_ID,
				CS_AD.AD_CODE,
				CS_AD.AD_TYPE,
				CS_AD.AD_NAME,
				LK.LINK_TYPE,
				LK.LINK_CHAN_RECID,
				LK.LINK_CAT_RECID,
				LK.LINK_VODCNT_RECID,
				LK.LINK_SRVC_RECID,
				CS_AD.AD_IMG_OFF,
				CB.BNR_SIZETYPE,
				CS.SER_CODE,
				CSTB.BNR_SEQUENCE,
				to_char(CAS.SCHD_START_DATE,\'YYYY-MM-DD HH24:MI:SS\') SCHD_START_DATE,
				to_char(CAS.SCHD_END_DATE,\'YYYY-MM-DD HH24:MI:SS\') SCHD_END_DATE,
				CAS.ASSIGN_START_TIME,
				CAS.ASSIGN_END_TIME,
				CAS.SCHD_STATUS
			FROM CS_SERVICE CS
			INNER JOIN CS_SER_TMP_BNR CSTB ON CS.SER_RECID=CSTB.SER_RECID
			INNER JOIN CS_AD_SCHEDULE CAS ON CSTB.CS_STB_RECID=CAS.CS_STB_RECID
			INNER JOIN CS_BANNER CB ON CSTB.BNR_RECID=CB.BNR_RECID
			INNER JOIN CS_AD ON CS_AD.AD_RECID=CAS.AD_RECID
			INNER JOIN CS_LINK LK ON CS_AD.LINK_RECID=LK.LINK_RECID
			WHERE
				CS.SER_CODE=:SER_CODE
				AND CSTB.BNR_SEQUENCE=:BNR_SEQUENCE
				AND TO_DATE(:QUERY_DATE,\'yyyymmdd\') BETWEEN CAS.SCHD_START_DATE AND CAS.SCHD_END_DATE
			ORDER BY 
				CAS.SCHD_STATUS,
				CAS.TRANSACTION_ID
		';
		$vars=array(
			array('bv_name'=>':SER_CODE','variable'=>$_GET['SER_CODE']),
			array('bv_name'=>':BNR_SEQUENCE','variable'=>$_GET['BNR_SEQUENCE']),
			array('bv_name'=>':QUERY_DATE','variable'=>$_GET['QUERY_DATE'])
		);
		$result=$oracleDB->getResultArray($sql,$vars);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_GET['CHAN_NUMBER'])&&isset($_GET['QUERY_DATE'])){
		$sql='
			SELECT
				CSS.SEPG_TRANSACTION_ID,
				OC.CHAN_NUMBER,
				CS_AD.AD_CODE,
				CS_AD.AD_TYPE,
				CS_AD.AD_NAME,
				LK.LINK_TYPE,
				LK.LINK_CHAN_RECID,
				LK.LINK_CAT_RECID,
				LK.LINK_VODCNT_RECID,
				LK.LINK_SRVC_RECID,
				CS_AD.AD_IMG_OFF,
				CSS.SEPG_DEFAULT_FLAG,
				to_char(CSS.SEPG_START_DATE,\'YYYY-MM-DD HH24:MI:SS\') SEPG_START_DATE,
				to_char(CSS.SEPG_END_DATE,\'YYYY-MM-DD HH24:MI:SS\') SEPG_END_DATE,
				CSS.SEPG_ASSIGN_START_TIME,
				CSS.SEPG_ASSIGN_END_TIME,
				CSS.SEPG_STATUS
			FROM OVA_CHANNEL OC
			INNER JOIN CS_SEPG_CHAN_RELATION CSCR ON OC.CHAN_RECID=CSCR.CHAN_RECID
			INNER JOIN CS_SEPG_SCHEDULE CSS ON CSCR.SEPG_SCHDID=CSS.SEPG_SCHDID
			INNER JOIN CS_AD ON CS_AD.AD_RECID=CSS.AD_RECID
			INNER JOIN CS_LINK LK ON CS_AD.LINK_RECID=LK.LINK_RECID
			WHERE
				OC.CHAN_NUMBER=:CHAN_NUMBER
				AND TO_DATE(:QUERY_DATE,\'yyyymmdd\') BETWEEN CSS.SEPG_START_DATE AND CSS.SEPG_END_DATE
			ORDER BY
				CSS.SEPG_STATUS,
				CSS.SEPG_TRANSACTION_ID
		';
		$vars=array(
			array('bv_name'=>':CHAN_NUMBER','variable'=>$_GET['CHAN_NUMBER']),
			array('bv_name'=>':QUERY_DATE','variable'=>$_GET['QUERY_DATE'])
		);
		$result=$oracleDB->getResultArray($sql,$vars);
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_GET['SER_CODE'])&&isset($_GET['QUERY_DATE'])){
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
			FROM CS_BARKER_AD_SCHEDULE CBAS
			INNER JOIN CS_SERVICE CS ON CBAS.SER_RECID=CS.SER_RECID
			INNER JOIN CS_BARKER_AD CBA ON CBA.BAKAD_RECID=CBAS.BAKAD_RECID
			INNER JOIN CS_LINK LK ON CBA.LINK_RECID=LK.LINK_RECID
			LEFT OUTER JOIN CS_GROUP_AD CGA ON CBAS.BAKADSCHD_RECID=CGA.BAKADSCHD_RECID
			LEFT OUTER JOIN CS_AD_SCHEDULE CAS ON CAS.AD_SCHDID=CGA.AD_SCHDID
			LEFT OUTER JOIN CS_SER_TMP_BNR CSTB ON CSTB.CS_STB_RECID=CAS.CS_STB_RECID
			LEFT OUTER JOIN OVA_VOD_CONTENT OVCSD ON CBA.SD_VODCNT_RECID=OVCSD.VODCNT_RECID
			LEFT OUTER JOIN OVA_VOD_CONTENT OVCHD ON CBA.HD_VODCNT_RECID=OVCHD.VODCNT_RECID
			WHERE
				CS.SER_CODE=:SER_CODE
				AND TO_DATE(:QUERY_DATE,\'yyyymmdd\') BETWEEN CBAS.BAKADSCHD_START_DATE AND CBAS.BAKADSCHD_END_DATE
			ORDER BY
				CBAS.BAKADSCHD_STATUS,
				CBAS.BAKADSCHD_TRANSACTION_ID
		';
		$vars=array(
			array('bv_name'=>':SER_CODE','variable'=>$_GET['SER_CODE']),
			array('bv_name'=>':QUERY_DATE','variable'=>$_GET['QUERY_DATE'])
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
