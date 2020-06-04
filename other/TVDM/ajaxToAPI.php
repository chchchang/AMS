<?php
	include('../../tool/auth/authAJAX.php');
	include('Config_TVDM.php');

	if(isset($_POST["action"])){
		switch($_POST["action"]){
			case "getAndSaveTVDMInfo":
				getAndSaveTVDMInfo();
				break;
			case "updateTVDMInfo" :
				updateTVDMInfo();
				break;
			case "getTVDMDataGrid" :
				getTVDMDataGrid();
				break;
		}
	}
	
	function getAndSaveTVDMInfo(){
		global $my,$logger,$APIUrl;
		$getApiUrl =Config_TVDM::GET_API_TVDMAPI_SELECT();
		$apiReslut = connec_to_Api($getApiUrl,"GET");
		if(!$apiReslut)
			exit(json_encode(array("success"=>false,"message"=>"連線TVDM SELECT API錯誤"),JSON_UNESCAPED_UNICODE));
		
		$decodedResult = json_decode($apiReslut,true);
		if(substr( $decodedResult["statusCode"], 0, 1 ) != "2")
			exit(json_encode(array("success"=>false,"message"=>"連線TVDM API回傳錯誤:".$decodedResult["statusCode"]),JSON_UNESCAPED_UNICODE));
		
		$TVDMData = $decodedResult["tvdm"];
		$my->begin_transaction();
		foreach($TVDMData as $data){
			//新增或是覆蓋TVDM服務資訊
			$sql = "INSERT INTO TVDM廣告服務 (TVDM識別碼,備註,LAST_UPDATE_TIME) 
				VALUE (?,?,?)
				ON DUPLICATE KEY UPDATE
				TVDM識別碼 = ?,
				備註 = ?,
				是否需派送 = 0,
				LAST_UPDATE_TIME = ?
				";
			$reslut = $my->execute($sql,"ississ",$data["id"],$data["description"],$data["updated_at"],$data["id"],$data["description"],$data["updated_at"]);
			//移除舊有素材設定
			$sql = "DELETE FROM TVDM廣告素材 WHERE TVDM識別碼 = ?";
			$reslut = $my->execute($sql,"i",$data["id"]);
			//新增素材設定
			$presql = "insert into TVDM廣告素材 (TVDM識別碼,順序,畫質,URL連結,LAST_UPDATE_TIME,LAST_UPDATE_PEOPLE) VALUES ";
			$subsql = array();
			$typeSting="";
			$parameters=array();
			$hdMaterial = explode("\n",$data["hd"]);
			foreach($hdMaterial as $index=>$murl){
				$sql = $presql." (?,?,?,?,NOW(),?)";
				if(!$my->execute($sql,"iiisi",$data["id"],$index,1,$murl,$_SESSION['AMS']['使用者識別碼'])){
					exit(json_encode(array("success"=>false,"messqge"=>"新增HD素材資料失敗"),JSON_UNESCAPED_UNICODE));
				}
			}
			$sdMaterial = explode("\n",$data["sd"]);
			foreach($sdMaterial as $index=>$murl){
				$sql = $presql." (?,?,?,?,NOW(),?)";
				if(!$my->execute($sql,"iiisi",$data["id"],$index,0,$murl,$_SESSION['AMS']['使用者識別碼'])){
					exit(json_encode(array("success"=>false,"messqge"=>"新增SD素材資料失敗"),JSON_UNESCAPED_UNICODE));
				}
			}
		}
		$my->commit();
		exit(json_encode(array("success"=>true,"message"=>"取得遠端資料成功"),JSON_UNESCAPED_UNICODE));
	}
	
	function updateTVDMInfo(){
		global $my,$logger,$APIUrl;
		$getApiUrl =Config_TVDM::GET_API_TVDMAPI_UPDATE();
		//取得TVDM基本資訊
		$sql = "SELECT * FROM TVDM廣告服務 WHERE TVDM識別碼 = ?";
		$res = $my->getResultArray($sql,"i",$_POST["TVDM識別碼"]);
		if($res == null)
			exit(json_encode(array("success"=>false,"messqge"=>"取得TVDM服務資料失敗"),JSON_UNESCAPED_UNICODE));
		$basicInfo = $res[0];
		//取得SD素材資訊
		$SDMaterial = "";
		$sql = "SELECT * FROM TVDM廣告素材 WHERE TVDM識別碼 = ? AND 畫質=0 order by 順序";
		$res = $my->getResultArray($sql,"i",$_POST["TVDM識別碼"]);
		if($res == null)
			exit(json_encode(array("success"=>false,"messqge"=>"取得TVDM服務資料失敗"),JSON_UNESCAPED_UNICODE));
		
		$temp=array();
		foreach($res as $row){
			array_push($temp,$row["URL連結"]);
		}
		$SDMaterial = implode("\n",$temp);
		//取得HD素材資訊
		$HDMaterial = "";
		$sql = "SELECT * FROM TVDM廣告素材 WHERE TVDM識別碼 = ? AND 畫質=1 order by 順序";
		$res = $my->getResultArray($sql,"i",$_POST["TVDM識別碼"]);
		if($res == null)
			exit(json_encode(array("success"=>false,"messqge"=>"取得TVDM服務資料失敗"),JSON_UNESCAPED_UNICODE));
		
		$temp=array();
		foreach($res as $row){
			array_push($temp,$row["URL連結"]);
		}
		$HDMaterial = implode("\n",$temp);
		
		//連線API上傳資料
		$bypost = array("id"=>$_POST["TVDM識別碼"],"description"=>$basicInfo["備註"],"sd"=>$SDMaterial,"hd"=>$HDMaterial,"mark"=>"T");
		$apiReslut = connec_to_Api($getApiUrl,"POST",$bypost);
		if(!$apiReslut)
			exit(json_encode(array("success"=>false,"message"=>"連線TVDM UPDATE API錯誤"),JSON_UNESCAPED_UNICODE));
		$decodedResult = json_decode($apiReslut,true);
		
		if(substr( $decodedResult["statusCode"], 0, 1 ) != "2")
			exit(json_encode(array("success"=>false,"message"=>"連線TVDM API回傳錯誤:".$decodedResult["statusCode"]),JSON_UNESCAPED_UNICODE));
		
		//更新資料庫改為已派送
		$sql = "UPDATE TVDM廣告服務 SET 是否需派送 = 0 WHERE TVDM識別碼 = ? ";
		$res = $my->execute($sql,"i",$_POST["TVDM識別碼"]);
		if($res == null)
			exit(json_encode(array("success"=>false,"messqge"=>"更新TVDM服務派送狀態失敗"),JSON_UNESCAPED_UNICODE));
		
		
		exit(json_encode(array("success"=>true,"message"=>"資料更新成功".$decodedResult["statusCode"]),JSON_UNESCAPED_UNICODE));
	}
	
	//連接API取的結果
	function connec_to_Api($url,$method,$postvars=null){
		global $logger;
		$postvars = (isset($postvars)) ? $postvars : null;
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		$apiResult = curl_exec($ch);
		if(curl_errno($ch))
		{
			$logger->error('錯誤代號:'.curl_errno($ch).'無法連接API:'.$url);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		return $apiResult;
	}
	exit();
?>
	