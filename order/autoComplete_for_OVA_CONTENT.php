<?php
	include('../tool/auth/authAJAX.php');
	require '../tool/OracleDB.php';
	$DB_U = Config::OMP_N_ORACLE_DB_USER;
	$DB_T_O = Config::OMP_N_ORACLE_DB_TABLE_OWNER;
	$DB_P = Config::OMP_N_ORACLE_DB_PASSWORD;
	$DB_S = Config::OMP_N_ORACLE_DB_CONN_STR;
	$oracleDB=new OracleDB($DB_U,$DB_P,$DB_S);
	
	$values=[];
	$feedback=[];
	if(isset($_POST['term'])){
		if($_POST['method']=='get_ova_content_title'){
			$term = '%'.$_POST['term'].'%';
			$sql="SELECT VODCNT_TITLE FROM ".$DB_T_O.".OVA_VOD_CONTENT WHERE VODCNT_TITLE LIKE :SEARCH ";
			$vars=array(
				array('bv_name'=>':SEARCH','variable'=>$term)
			);
			$result=$oracleDB->getResultArray($sql,$vars);
			
			foreach($result as $row){
				$value = $row['VODCNT_TITLE'];
				if($value!=null&&$value!='')
				if(!in_array($value,$values)){
					$feedback[] = ['value'=>$value,'id'=>$value];
					$values[]=$value;
				}
					
			}
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
	}
?>