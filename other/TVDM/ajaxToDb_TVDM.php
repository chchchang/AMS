<?php
	include('../../tool/auth/authAJAX.php');
	include('Config_TVDM.php');
	define('PAGE_SIZE',10);
	if(isset($_POST["action"])){
		switch($_POST["action"]){
			case "getTVDMInfo":
				getTVDMInfo();
				break;
			case "儲存TVDM資訊" :
				saveTVDMInfo();
				break;
			case "getTVDMDataGrid" :
				getTVDMDataGrid();
				break;
		}
	}
	
	function getTVDMInfo(){
		global $my;
		$TVDMId = $_POST["TVDMId"];
		//基本資料
		$sql = "SELECT * FROM TVDM廣告服務 WHERE TVDM識別碼 = ?";
		$infores = $my->getResultArray($sql,'s',$TVDMId)[0];
		$infores["material"] = array();
		//素材資料
		$sql = "SELECT * FROM TVDM廣告素材 WHERE TVDM識別碼 = ? order by 順序,畫質";
		$res = $my->getResultArray($sql,'s',$TVDMId);
		foreach($res as $material){
			array_push($infores["material"],$material);
		}
			//URL設定
		$infores["ompurl"] = Config_TVDM::GET_OMP_URL($TVDMId);
		$infores["hdurl"] = Config_TVDM::GET_IAP_HD_URL($TVDMId);
		$infores["sdurl"] = Config_TVDM::GET_IAP_SD_URL($TVDMId);
		exit(json_encode(array("success"=>true,"data"=>$infores),JSON_UNESCAPED_UNICODE));
	}
	
	function saveTVDMInfo(){
		global $my,$SERVER_SITE;
		$my->begin_transaction();
		//更新基本資訊
		$sql = "update TVDM廣告服務 set 說明=?,開始日期=?,結束日期=?,售價=?,備註=?,是否需派送=1, LAST_UPDATE_TIME = NOW(),LAST_UPDATE_PEOPLE = ? WHERE TVDM識別碼 = ?";
		if(!$my->execute($sql,"sssisii",$_POST["說明"],$_POST["開始日期"],$_POST["結束日期"],$_POST["售價"],$_POST["備註"],$_SESSION['AMS']['使用者識別碼'],$_POST["TVDM識別碼"])){
			exit(json_encode(array("success"=>false,"messqge"=>"更新TVDM基本資訊失敗"),JSON_UNESCAPED_UNICODE));
		}
		//刪除舊素材資料
		$sql = "DELETE FROM TVDM廣告素材 WHERE TVDM識別碼 = ?";
		if(!$my->execute($sql,"i",$_POST["TVDM識別碼"])){
			exit(json_encode(array("success"=>false,"messqge"=>"清除舊素材資料失敗"),JSON_UNESCAPED_UNICODE));
		}
		//新增素材資料
		$presql = "insert into TVDM廣告素材 (TVDM識別碼,順序,畫質,上傳原始檔名,URL連結,LAST_UPDATE_TIME,LAST_UPDATE_PEOPLE) VALUES ";
		$subsql = array();
		$typeSting="";
		$parameters=array();
		$postMaterial = json_decode($_POST["素材"],true);
		//清除舊圖檔
		$files = glob("images".'/'.$_POST["TVDM識別碼"]."hd_*");
		foreach($files as $file){
		  if(is_file($file))
			unlink($file);
		}
		$files = glob("images".'/'.$_POST["TVDM識別碼"]."sd_*");
		foreach($files as $file){
		  if(is_file($file))
			unlink($file);
		}
		foreach($postMaterial["HD"] as $material){
			$sql = $presql." (?,?,?,?,?,NOW(),?)";
			//檢查暫存檔案是否存在，不存在不更新素材遠端url位置
			$murl = $material["URL連結"];
			$temp = explode('.',$material["上傳原始檔名"]);
			$filetype =end($temp);
			$tempFile = "tempFile/".$_SESSION['AMS']['使用者識別碼']."/".$material["countid"].".".$filetype;
			if(file_exists ( $tempFile )){
				$newFile = $_POST["TVDM識別碼"]."hd_".$material["順序"].'.'.$filetype;
				copy($tempFile,"images/".$newFile);
				$murl = $SERVER_SITE.Config::PROJECT_ROOT."other/TVDM/images/".$newFile;
			}
			
			if(!$my->execute($sql,"iiissi",$_POST["TVDM識別碼"],$material["順序"],1,$material["上傳原始檔名"],$murl,$_SESSION['AMS']['使用者識別碼'])){
				exit(json_encode(array("success"=>false,"messqge"=>"新增HD素材資料失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		foreach($postMaterial["SD"] as $material){
			$sql = $presql." (?,?,?,?,?,NOW(),?)";
			//檢查暫存檔案是否存在，不存在不更新素材遠端url位置
			$murl = $material["URL連結"];
			$temp = explode('.',$material["上傳原始檔名"]);
			$filetype =end($temp);
			$tempFile = "tempFile/".$_SESSION['AMS']['使用者識別碼']."/".$material["countid"].".".$filetype;
			if(file_exists ( $tempFile )){
				$newFile = $_POST["TVDM識別碼"]."sd_".$material["順序"].'.'.$filetype;
				copy($tempFile,"images/".$newFile);
				$murl = $SERVER_SITE.Config::PROJECT_ROOT."other/TVDM/images/".$newFile;
			}
			if(!$my->execute($sql,"iiissi",$_POST["TVDM識別碼"],$material["順序"],0,$material["上傳原始檔名"],$murl,$_SESSION['AMS']['使用者識別碼'])){
				exit(json_encode(array("success"=>false,"messqge"=>"新增SD素材資料失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		$my->commit();
		exit(json_encode(array("success"=>true,"messqge"=>"資料更新成功"),JSON_UNESCAPED_UNICODE));
	}
	
	//取得資料表資訊
	function getTVDMDataGrid(){
		global $my,$SERVER_SITE;
		
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
		$totalRowCount=0;	//T.B.D.
		$parameters = array();
		$parameters["searchBy"]='%'.((isset($_POST['searchBy']))?$_POST['searchBy']:'').'%';
		
		$parameters["開始日期"] ="1990-01-01 00:00:00";
		if(isset($_POST['開始日期'])&&$_POST['開始日期']!=''){
			$parameters["開始日期"]=$_POST['開始日期'];
			$checkinput = true;
		}
		
		
		$parameters["結束日期"] = "2100-01-01 00:00:00";
		if(isset($_POST['結束日期'])&&$_POST['結束日期']!=''){
			$parameters["結束日期"]=$_POST['結束日期'];
		}
		
		//取的總筆數
		
		$sql = "
		SELECT COUNT(*) AS C FROM TVDM廣告服務 WHERE 
		(說明 LIKE ? OR 備註 LIKE ? OR TVDM識別碼 LIKE ?)
		AND (
			(開始日期 BETWEEN ? AND ?) OR (結束日期 BETWEEN ? AND ?) OR (? BETWEEN 開始日期 AND 結束日期)
		)";
		if(isset($_POST["只顯示待派送"])&&$_POST["只顯示待派送"]=="true"){
			$sql .=" AND 是否需派送=1";
		}
		
		$totalRowCount = $my->getResultArray($sql,"ssssssss",$parameters["searchBy"],
		$parameters["searchBy"],
		$parameters["searchBy"],
		$parameters["開始日期"],
		$parameters["結束日期"],
		$parameters["開始日期"],
		$parameters["結束日期"],
		$parameters["開始日期"]
		)[0]["C"];
		
		
		$sql = "
		SELECT * FROM TVDM廣告服務 WHERE 
		(說明 LIKE ? OR 備註 LIKE ? OR TVDM識別碼 LIKE ?)
		AND (
			(開始日期 BETWEEN ? AND ?) OR (結束日期 BETWEEN ? AND ?) OR (? BETWEEN 開始日期 AND 結束日期)
		)";
		
		if(isset($_POST["只顯示待派送"])&&$_POST["只顯示待派送"]=="true"){
			$sql .=" AND 是否需派送=1 ";
		}
		$sql .="ORDER BY CHAR_LENGTH(TVDM識別碼),TVDM識別碼	LIMIT ?, ".PAGE_SIZE;
			
		$TVDMData = $my->getResultArray($sql,"ssssssssi",
		$parameters["searchBy"],
		$parameters["searchBy"],
		$parameters["searchBy"],
		$parameters["開始日期"],
		$parameters["結束日期"],
		$parameters["開始日期"],
		$parameters["結束日期"],
		$parameters["開始日期"],
		$fromRowNo);
		
		$data = array();
		foreach($TVDMData as $row){
			$TVDMId =$row['TVDM識別碼'];
			$ompurl = Config_TVDM::GET_OMP_URL($TVDMId);
			$hdurl = Config_TVDM::GET_IAP_HD_URL($TVDMId);
			$sdurl = Config_TVDM::GET_IAP_SD_URL($TVDMId);
			//取得素材資訊
			$sql = "SELECT * FROM TVDM廣告素材 WHERE TVDM識別碼 = ? order by 順序,畫質";
			$res = $my->getResultArray($sql,'s',$TVDMId);
			$sdtemp = array();
			$hdtemp = array();
			foreach($res as $material){
				$htmlelement = '<img class ="dgImg" src="'.$material['URL連結'].'?'.time().'" alt="'.$material['上傳原始檔名'].'" style="max-width:100%;max-height:100%;border:0;">';
				if($material["畫質"]==0)
					array_push($sdtemp,$htmlelement);
				else
					array_push($hdtemp,$htmlelement);
			}
			$sdpicelement = implode("<br>",$sdtemp);
			$hdpicelement = implode("<br>",$hdtemp);
			$urllist = "<div class ='urllist'>OMP<br>".$ompurl."<br>HD<br>".$hdurl."<br>SD<br>".$sdurl."</div>";
			$urlbutton = "<button>顯示URL</button>";
			$data[]=array(
			array($TVDMId,'text')
			,array($row['說明'],'text')
			,array("TVDM_".$TVDMId,'text')
			,array($urlbutton."<br>".$urllist,'html')
			,array($sdpicelement,'html')
			,array($hdpicelement,'html')
			,array($row['開始日期'],'text')
			,array($row['結束日期'],'text')
			,array($row['售價'],'text')
			,array($row['備註'],'text')
			,array($row['是否需派送'],'text')
			//,array('<img src="../../tool/pic/'.($row['是否需派送']==NULL||$row['是否需派送']==0?'Circle_Green.png':'Circle_Red.png').'">','html')
			);
		}

		$feedback = json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),
						'header'=>array('TVDM識別碼','說明','服務名稱','三區URL','SD素材預覽','HD素材預覽','開始日期','結束日期','售價','備註','遠端同步狀態')
						,'data'=>$data
						,'sortable'=>array()),JSON_UNESCAPED_UNICODE);
		exit($feedback);
		
	}
	exit();
?>
	