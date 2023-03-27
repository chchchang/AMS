<?php
	/**2023 03 16
	 * 若是呼叫getChannelList API 增加showOffLine參數判斷是否要顯示以下線的頻道，預設為不顯示
	*/
	require_once("Config.php");
	
	if(!isset($_POST["apiName"])){
		exit(json_encode(array("success"=>false,"message"=>"parameter: API Name not set"),JSON_UNESCAPED_UNICODE));
	}
	//else if(!isset(AMS\apiProxy\pointBarker\Config::$BarkerApi[$_POST["apiName"]])){
		else if(!isset(Config::$BarkerApi[$_POST["apiName"]])){
		exit(json_encode(array("success"=>false,"message"=>"no such API:".$_POST["apiName"]),JSON_UNESCAPED_UNICODE));
	}
	
	//post to curl
	//$url = AMS\apiProxy\pointBarker\Config::$BarkerApi[$_POST["apiName"]];
	$url = Config::$BarkerApi[$_POST["apiName"]];
	//echo "connecting to $url \n";
	//print_r($_POST);
	$postvar = http_build_query($_POST);
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,true);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$postvar);
	curl_setopt($ch, CURLOPT_PROXY, '');//dev 因OA環境有PROXY才需額外取消proxy設定
	$output = curl_exec($ch);
	curl_close($ch);
	
	
	if($_POST["apiName"]=="getChannelList" && (!isset($_POST["showOffLine"]) || !$_POST["showOffLine"])){
		$data = json_decode($output,true);
		$return = array();
		$return["channels"]=[];
		foreach($data["channels"] as $id=>$row){
			if($row["online"])
				array_push($return["channels"],$row);
		}
		exit(json_encode($return,JSON_UNESCAPED_UNICODE));
	}
	else{
		echo $output;
	}
	
	
?>