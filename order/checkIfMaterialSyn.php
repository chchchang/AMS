<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once dirname(__FILE__).'/../tool/MyDB.php';	
	require_once dirname(__FILE__).'/../tool/MyLogger.php';
	require_once dirname(__FILE__).'/../tool/OracleDB.php';
	if (!defined('MATERIAL_FOLDER'))
		define("MATERIAL_FOLDER", Config::GET_MATERIAL_FOLDER());
	class checkIfMaterialSyn{
		public static function checkIfSyn($config){
			$my = new MyDB(true);;
			$area = $config['區域'];
			$mid = $config['素材識別碼'];
			return(json_encode(['success'=>true]));
						
			if($area==='N'){
				$DB_U = Config::OMP_N_ORACLE_DB_USER;
				$DB_T_O = Config::OMP_N_ORACLE_DB_TABLE_OWNER;
				$DB_P = Config::OMP_N_ORACLE_DB_PASSWORD;
				$DB_S = Config::OMP_N_ORACLE_DB_CONN_STR;
			}
			else if($area==='C'){
				$DB_U = Config::OMP_C_ORACLE_DB_USER;
				$DB_T_O = Config::OMP_C_ORACLE_DB_TABLE_OWNER;
				$DB_P = Config::OMP_C_ORACLE_DB_PASSWORD;
				$DB_S = Config::OMP_C_ORACLE_DB_CONN_STR;
			}
			else if($area==='S'){
				$DB_U = Config::OMP_S_ORACLE_DB_USER;
				$DB_T_O = Config::OMP_S_ORACLE_DB_TABLE_OWNER;
				$DB_P = Config::OMP_S_ORACLE_DB_PASSWORD;
				$DB_S = Config::OMP_S_ORACLE_DB_CONN_STR;
			}
			else 
				return(json_encode(['success'=>true]));
		
			$oracleDB=new OracleDB($DB_U,$DB_P,$DB_S);

			$sql='	SELECT 素材名稱,素材類型名稱,素材原始檔名
					FROM 素材,素材類型
					WHERE 素材.素材識別碼 = ? AND 素材.素材類型識別碼 = 素材類型.素材類型識別碼
				';
			if(!$stmt=$my->prepare($sql)) {
					return(json_encode(['success'=>false]));
			}
			if(!$stmt->bind_param('s',$mid)) {
					return(json_encode(['success'=>false]));
			}
			if(!$stmt->execute()) {
				return(json_encode(['success'=>false]));
			}
			if(!$res=$stmt->get_result()){
				return(json_encode(['success'=>false]));
			}	
			$materialInfo = $res->fetch_assoc();
			$fileNameA = explode('.',$materialInfo['素材原始檔名']);
			$type = end($fileNameA);

			switch($materialInfo['素材類型名稱']){
				//影片素材才需檢查
				case '影片':
					$sql =
					"SELECT COUNT(*) AS C
					FROM ".$DB_T_O.".OVA_VOD_CONTENT
					WHERE VODCNT_TITLE=:TITLE";
					
					$vars=array(
						array('bv_name'=>':TITLE','variable'=>'_____AMS_'.$mid.'_'.md5_file(MATERIAL_FOLDER.$mid.'.'.$type))
					);
					
					$result=$oracleDB->getResultArray($sql,$vars);
					$count = $result[0]['C'];
					if($count  == 0)
						return( json_encode(['success'=>false]));
					else 
						return( json_encode(['success'=>true]));
				break;
			}
		}
	}
	
?>