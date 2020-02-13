<?php
	
	include('../tool/auth/authAJAX.php');
	include('../Config_VSM_Meta.php');
	include '../tool/PHPExcel/Classes/PHPExcel.php';
	require_once '../tool/phpExtendFunction.php';
	set_include_path('../tool/phpseclib');
	$headermessage="";
	if(isset($_POST['postAction'])){
		//取得投放上限設定
		if($_POST['postAction'] == "getLimitTable"){
			$temp = getLimitData();
			exit(json_encode(array('success'=>true,"data"=>$temp),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['postAction'] == "setLimit"){
			$data = array($_POST['data']['chnum']=>$_POST['data']['limit']);
			if(updateLimit($data)){
				exit(json_encode(array('success'=>true,"message"=>"更新成功"),JSON_UNESCAPED_UNICODE));
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"更新失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		else if($_POST['postAction'] == "syncToVsm"){
			$url = Config_VSM_Meta::GET_SET_VAST_OPTION_API();
			$temp = getLimitData();
			$data = array();
			foreach($temp as $row){
				$data[$row["頻道號碼"]] = $row["投放上限"];
			}
			$bypost = array("action"=>"setChannelLimit","data"=>$data);
			$postvars = http_build_query($bypost);
			$res = PHPExtendFunction::connec_to_Api($url,'POST',$postvars);
			if($res["success"]){
				exit($res["data"]);
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"單一平台API連接失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
	}
	//匯入投放上限設定
	if ( isset($_POST["submit"]) ) {
	   if ( isset($_FILES["file"])) {
			if ($_FILES["file"]["error"] > 0) {
				$headermessage = "檔案上傳失敗";
			}
			else {
				//echo "Upload: " . $_FILES["file"]["name"] . "<br />";
				//echo "Type: " . $_FILES["file"]["type"] . "<br />";
				//echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
				//echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
				//從excel擋取得資料
				$data = getExcelData($_FILES["file"]["tmp_name"]);
				//匯入資料
				if(updateLimit($data))
					$headermessage = "匯入成功";
				else
					$headermessage = "匯入失敗";
			}
		 } else {
				$headermessage = "沒有選擇檔案";
		 }
	}
	
	//從excel中讀取資料
	//input:檔案名稱
	//output:array(<頻道號碼>=><投放上限>)
	function getExcelData($file){
		//設定要被讀取的檔案
		try {
			$objPHPExcel = PHPExcel_IOFactory::load($file);
		} catch(Exception $e) {
			die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
		}
		$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
		
		$returnArray = [];
		
		foreach($sheetData as $key => $col)
		{
			//前兩航為title，跳過
			if($key<=2)
				continue;
			//讀取頻道號碼與上限
			$channelNumber="";
			$limit=0;
			//A欄:頻道號碼 B欄:頻道 C欄位:分類 D欄位:版位數 E欄位:異動數 F欄位:原始數據
			foreach ($col as $colkey => $colvalue) {	
				if(startsWith($colkey, "A" )){
					//紀錄頻到號碼
					$channelNumber = $colvalue;
				}
				else if(startsWith($colkey, "D" )){
					//紀錄投放上限
					if($colvalue == "-")
						//上限"-"視為0
						$limit = 0;
					else
						$limit = $colvalue;
					//因後面的欄位不需參考，結束loop
					break;
				}
			}
			if($channelNumber!="")
				$returnArray[$channelNumber]=$limit;
		}
		return $returnArray;
	}
	
	//從excel中讀取資料
	//input:array(<頻道號碼>=><投放上限>)
	//output:true/false
	function updateLimit($data){
		global $my;
		//取得單一平台EPG版位類型識別碼
		$sql = "select 版位識別碼 from 版位 where 版位名稱 = '單一平台EPG'";
		$res=$my->getResultArray($sql);
		$ptid = $res[0]["版位識別碼"];
		//取得版位素材資料
		$sql = "select * from 版位素材類型 where 版位識別碼 = ?";
		$ptmaterials=$my->getResultArray($sql,"i",$ptid);
		$my->begin_transaction();
		foreach($data as $num=>$limit){
			//查詢是否已建立過參數		
			//取得對應頻道的版位識別碼
			$sql="select 版位.版位識別碼 
			from 版位 LEFT JOIN 版位其他參數 ON 版位.版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數.版位其他參數名稱 = 'channel_number' 
			WHERE 版位其他參數.版位其他參數預設值 = ? AND 版位.上層版位識別碼 = ? AND DISABLE_TIME IS NULL AND DELETED_TIME IS NULL";
			$res=$my->getResultArray($sql,"si",$num,$ptid);
			foreach($res as $position){
				$pid = $position["版位識別碼"];
				//檢查是否已建立過版位素材
				$sql = "select 每小時最大素材筆數 from 版位素材類型 where 版位識別碼 = ?";
				$mres=$my->getResultArray($sql,"i",$pid);
				if(count($mres)==0){
					//未建立過，新增資料
					foreach($ptmaterials as $ptmaterialdata){
						$sql = "insert into 版位素材類型 
						(
						版位識別碼,
						素材順序,
						顯示名稱,
						素材類型識別碼,
						託播單素材是否必填,
						影片畫質識別碼,
						每小時最大素材筆數,
						每小時最大影片素材合計秒數,
						每則文字素材最大字數,
						每則圖片素材最大寬度,
						每則圖片素材最大高度,
						每則影片素材最大秒數,
						CREATED_PEOPLE
						) 
						values
						(?,?,?,?,?,?,?,?,?,?,?,?,?)";
						if(!$my->execute($sql,"iisiiiiiiiiii"
						,$pid
						,$ptmaterialdata["素材順序"]
						,$ptmaterialdata["顯示名稱"]
						,$ptmaterialdata["素材類型識別碼"]
						,$ptmaterialdata["託播單素材是否必填"]
						,$ptmaterialdata["影片畫質識別碼"]
						,$limit
						,$ptmaterialdata["每小時最大影片素材合計秒數"]
						,$ptmaterialdata["每則文字素材最大字數"]
						,$ptmaterialdata["每則圖片素材最大寬度"]
						,$ptmaterialdata["每則圖片素材最大高度"]
						,$ptmaterialdata["每則影片素材最大秒數"]
						,$_SESSION['AMS']['使用者識別碼']
						)){
							$my->rollback();
							return false;
						}
					}
				}
				else{
					//以建立過 update資料庫
					$sql = "update 版位素材類型 set 每小時最大素材筆數 =?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP where 版位識別碼 = ?";
					if(!$my->execute($sql,"sii",$limit,$_SESSION['AMS']['使用者識別碼'],$pid)){
						$my->rollback();
						return false;
					}
				}
			}			
		}
		$my->commit();
		return true;
	}
	function getLimitData(){
		global $my;
		//取得單一平台EPG版位類型識別碼
		$sql = "select 版位識別碼 from 版位 where 版位名稱 = '單一平台EPG'";
		$res=$my->getResultArray($sql);
		$ptid = $res[0]["版位識別碼"];
		//依版位類型識別碼取得EPG版位
		$sql = "select 版位識別碼,版位名稱 from 版位 where 上層版位識別碼 = ? and DISABLE_TIME IS NULL AND DELETED_TIME IS NULL ORDER BY CHAR_LENGTH(SUBSTRING_INDEX(版位名稱,SUBSTRING_INDEX(版位名稱,'_',-1),1)), SUBSTRING_INDEX(版位名稱,SUBSTRING_INDEX(版位名稱,'_',-1),1)";
		$res=$my->getResultArray($sql,"i",$ptid);
		$temp = array();//整理並儲存查詢結果用
		foreach($res as $pdata){
			//依版位識別碼取的頻道號碼與投放上限資料
			//取得頻道號碼
			$sql = "select 版位其他參數預設值 from 版位其他參數 where 版位識別碼 = ? and 版位其他參數名稱 = 'channel_number'";
			$tempres=$my->getResultArray($sql,"i",$pdata["版位識別碼"]);
			if(count($tempres)==0)
				continue;
			
			$channel_number = $tempres[0]["版位其他參數預設值"];
			//取得投放上限資料
			$sql = "select 每小時最大素材筆數 from 版位素材類型 where 版位識別碼 = ?";
			$tempres=$my->getResultArray($sql,"i",$pdata["版位識別碼"]);
			$limit = "";
			if(count($tempres)>0 && $tempres[0]["每小時最大素材筆數"] != null)
				$limit = $tempres[0]["每小時最大素材筆數"];
			
			//存入資料
			$temp[]=["頻道號碼"=>$channel_number,"頻道名稱"=>$pdata["版位名稱"],"投放上限"=>$limit];
		}
		return $temp;
	}
	function startsWith ($string, $startString) 
	{ 
		$len = strlen($startString); 
		return (substr($string, 0, $len) === $startString); 
	} 
	
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
	<script src="../tool/jquery-ui/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<style type="text/css">
		#epglimittable,#epglimittable * * *{
			border: 1px solid #5599FF
		}
		th{
			background-color: #4169E1;
			color: white;
		}
		table {
			border-collapse:separate;
			//border-collapse: collapse;
			width:100%
		}
		td{
			min-width: 60px;
		}
		#syncToVsmBtn{
			float: right;
		}
	</style>
</head>
<body>
<fieldset>
<legend>匯入投放上限設定</legend>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" enctype="multipart/form-data">
<input type="file" name="file" id="file" /></td>
<input type="submit" name="submit" />
<font size="3" color="blue"><?=$headermessage?></font>
</form>
<button id="syncToVsmBtn">同步至單一平台</button>
</fieldset>

<fieldset>
<legend>投放上限資料表</legend>
<table id = "epglimittable" width="100%">
<thead><tr><th>頻道號碼</th><th>頻道名稱</th><th>投放上限</th><th>修改</th></tr></thead>
<tbody id = "epglimittablebody"><tbody>
</table>
<fieldset>

<script type="text/javascript">
refreshtable()
function refreshtable(){
	$.post("?",{"postAction":"getLimitTable"},
		function(result){
			if(result["success"]){
				data = result["data"];
				//清空table資料
				$("#epglimittablebody").empty();
				//更新table資料
				for(var i =0;i<data.length;i++){
					tr = $(document.createElement('tr'));
					tr.append("<td>"+data[i]["頻道號碼"]+"</td>"
					+"<td>"+data[i]["頻道名稱"]+"</input></td>"
					+"<td><input id='limit_"+data[i]["頻道號碼"]+"' value='"+data[i]["投放上限"]+"' disabled></input></td>"
					+"<td><button id='edit_"+data[i]["頻道號碼"]+"' class='limitEditBtn' chnum="+data[i]["頻道號碼"]+">修改</button>"
					+"<button id='submit_"+data[i]["頻道號碼"]+"' class='limitSubmitBtn' chnum="+data[i]["頻道號碼"]+">提交</button></td>"
					);
					if(i%2 == 1){
						tr.css({"background-color": "#F0F8FF"});
					}
					$("#epglimittablebody").append(tr);
				}
				//設定修改按鈕動作
				$(".limitEditBtn").click(function(){
					var chnum = $(this).attr("chnum");
					//UI更變
					$(this).hide();
					$("#limit_"+chnum).prop("disabled",false);
					$("#submit_"+chnum).show();
				});
				//設定提交按鈕動作
				//設定修改按鈕動作
				$(".limitSubmitBtn").click(function(){
					var chnum = $(this).attr("chnum");
					var limit = $("#limit_"+chnum).val();
					if(limit == ""){
						limit = null;
					}
					//ajax更新投放上限
					$.post("?",{"postAction":"setLimit","data":{limit:limit,chnum:chnum}},
						function(feedback){
							if(feedback["success"]){
								//UI更變
								$("#submit_"+chnum).hide();
								$("#limit_"+chnum).prop("disabled",true);
								$("#edit_"+chnum).show();
								refreshtable();
							}
							alert(feedback["message"]);
						}
						,"json"
					)
					
				}).hide();
			}
			else{
				alert(result["message"]);
			}
		},
		"json"
	);
}

$("#syncToVsmBtn").click(
function(){
	$.post("?",{"postAction":"syncToVsm"},
	function(feedback){
		alert(feedback["message"]);
	},
	"json"
	)
});

</script>
</body>
</html>
