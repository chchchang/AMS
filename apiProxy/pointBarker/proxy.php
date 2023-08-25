<?php
	/**2023 03 16
	 * 若是呼叫getChannelList API 增加showOffLine參數判斷是否要顯示以下線的頻道，預設為不顯示
	*/
	require_once(__DIR__."/Utils/ApiConnector.php");

	use AMS\apiProxy\pointBarker\Utilis\ApiConnector;
	
	$ApiConnector = new ApiConnector();
	if(!isset($_REQUEST["apiName"])){
		exit(json_encode(array("success"=>false,"message"=>"parameter: API Name not set"),JSON_UNESCAPED_UNICODE));
	}
	
	$url = $ApiConnector->getApiUrlByApiName($_REQUEST["apiName"]);
	
	if(!$url){
		exit(json_encode(array("success"=>false,"message"=>"no such API:".$_REQUEST["apiName"]),JSON_UNESCAPED_UNICODE));
	}

	$output = $ApiConnector->getDataFromApi($url,$_REQUEST);

	if($_REQUEST["apiName"]=="getChannelList" && (!isset($_REQUEST["showOffLine"]) || !$_REQUEST["showOffLine"])){
		$data = json_decode($output,true);
		$return = $ApiConnector->filterOfflineChannel($data);
		exit(json_encode($return,JSON_UNESCAPED_UNICODE));
	}
	else{
		echo $output;
	}

	
	
?>