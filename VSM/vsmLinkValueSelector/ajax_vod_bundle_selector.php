<?php
require_once('../../tool/auth/authAJAX.php');
require_once('../../tool/phpExtendFunction.php');
require_once('../../Config_VSM_Meta.php');
//const VSMapiUrl = 'localhost/VSMAPI/VSMAdData.php';
//const VSMapiUrl = 'localhost/api/ams/VSMAdData.php';
	
if(isset($_POST['term'])){
	$url =Config_VSM_Meta::GET_VOD_BUNDLE_SELECTOR_AJAX();
	$term = http_build_query($_POST);
	$data = PHPExtendFunction::connec_to_Api($url,'POST',$term);
	if($data['success'])
	exit($data['data']);
}
?>