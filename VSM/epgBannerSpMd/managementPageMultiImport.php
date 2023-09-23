<?php
date_default_timezone_set("Asia/Taipei");
require_once('../../tool/auth/authAJAX.php');
require_once('../../Config_VSM_Meta.php');
require_once('sepgSpMdParserMulti.php');
set_include_path('../../tool/phpseclib');
include('Net/SFTP.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if(isset($_POST['postAction'])){
		$localDir = "localFileMulti/";
		$awaitingDir = Config::$FTP_SERVERS['IAB_SPEPGMD_MULTI'][0]['awaiting'];
		$completeDir = Config::$FTP_SERVERS['IAB_SPEPGMD_MULTI'][0]['complete'];
		if(!file_exists ($localDir)){
			if (!mkdir($localDir, 0777, true)) 
				exit(json_encode(array("success" => false,"message" => "原始檔案暫存資料夾建立失敗"),JSON_UNESCAPED_UNICODE));
		}
		//取得遠端檔案資訊
		if($_POST['postAction']=='getRemoteFile'){
			$conn = getConnect();
			//掃描資料夾檔案並分析生效日期
			//$nlist = ftp_nlist($conn,$dir);
			$nlist = $conn->nlist($awaitingDir);
			$srotArray = array();
			foreach($nlist as $n){
				if($n=='.'||$n=='..')
					continue;
				//去除資料夾路徑
				$ndirname = str_replace($awaitingDir,'',$n);
				//取得ID
				$id =getIdByFileName($ndirname);
				$srotArray[] = array("name"=>$ndirname,"id"=>$id);
			};
			//依照日期排序
			usort($srotArray,"cmp");
			
			$data = array();
			foreach($srotArray as $fileObj){
				$data[] = array(array($fileObj["name"],"text"),array($fileObj["id"],"text"));
			}
			
			exit(json_encode(array(
					'header'=>array("檔案名稱","單號")
					,'data'=>$data
			),JSON_UNESCAPED_UNICODE));
		}
		
		else if($_POST['postAction']=='getRemoteCompFile'){
			$conn = getConnect();
			//掃描資料夾檔案並分析生效日期
			//$nlist = ftp_nlist($conn,$dir);
			$nlist = $conn->nlist($completeDir);
			$srotArray = array();
			foreach($nlist as $n){
				if($n=='.'||$n=='..')
					continue;
				//去除資料夾路徑
				$ndirname = str_replace($completeDir,'',$n);
				//取得ID
				$id =getIdByFileName($ndirname);
				$srotArray[] = array("name"=>$ndirname,"id"=>$id);
			};
			//依照日期排序
			usort($srotArray,"cmp");
			
			$data = array();
			foreach($srotArray as $fileObj){
				$data[] = array(array($fileObj["name"],"text"),array($fileObj["id"],"text"));
			}
			
			exit(json_encode(array(
					'header'=>array("檔案名稱","單號")
					,'data'=>$data
			),JSON_UNESCAPED_UNICODE));
		}
		
		//取本地檔案資訊
		else if($_POST['postAction']=='getLocalFile'){
			//掃描資料夾檔案並分析生效日期
			$nlist = scandir($localDir);
			$srotArray = array();
			foreach($nlist as $n){
				if($n=='.'||$n=='..')
					continue;
				//取得ID
				$id =getIdByFileName($n);
				$srotArray[] = array("name"=>$n,"id"=>$id);
			};
			//依照日期排序
			usort($srotArray,"cmp");
			
			$data = array();
			foreach($srotArray as $fileObj){
				$data[] = array(array($fileObj["name"],"text"),array($fileObj["id"],"text"));
			}
			
			exit(json_encode(array(
					'header'=>array("檔案名稱","單號")
					,'data'=>$data
			),JSON_UNESCAPED_UNICODE));
		}
		//匯入遠端
		else if($_POST['postAction'] == "importRemoteFile"){
			$conn = getConnect();
			$fileName = $_POST['fileName'];
			$localFile = $localDir.$fileName;
			$remoteFile = $awaitingDir.$fileName;
			$remoteFile_complete = $completeDir.$fileName;
			//下載檔案並匯入
			//下載檔案
			if(!$conn->get($remoteFile, $localFile)){
				(new MyLogger())->error('無法下載FTP server('.$remoteFile.')檔案到('.$localFile.')。');
				exit(json_encode(["success"=>false,"message"=>"下載遠端檔案失敗"]));
			}
			//匯入檔案
			$result = importFile($localFile);
			if(!$result["success"])
				exit(json_encode($result));
			//刪除遠端檔案
			$result=deleteRemote($remoteFile,$conn);
			if(!$result["success"])
				exit(json_encode($result));
			//上傳檔案到complete資料夾
			$result=putToComplete($remoteFile_complete,$localFile,$conn);
			if(!$result["success"])
				exit(json_encode($result));
			
			exit(json_encode(["success"=>true,"message"=>"匯入遠端檔案成功"]));
		}
		else if($_POST['postAction'] == "importRemoteFileComp"){
			$conn = getConnect();
			$fileName = $_POST['fileName'];
			$localFile = $localDir.$fileName;
			$remoteFile_complete = $completeDir.$fileName;
			//下載檔案並匯入
			//下載檔案
			if(!$conn->get($remoteFile_complete, $localFile)){
				(new MyLogger())->error('無法下載FTP server('.$remoteFile.')檔案到('.$localFile.')。');
				exit(json_encode(["success"=>false,"message"=>"下載遠端檔案失敗"]));
			}
			//匯入檔案
			$result = importFile($localFile);
			if(!$result["success"])
				exit(json_encode($result));			
			exit(json_encode(["success"=>true,"message"=>"匯入遠端檔案成功"]));
		}
		
		//匯入本地檔案
		else if($_POST['postAction'] == "importLocalFile"){
			$fileName = $localDir.$_POST['fileName'];
			//匯入檔案
			$result = importFile($fileName);
			if($result["success"])
				exit(json_encode(["success"=>true,"message"=>"匯入本地檔案成功"]));
			else
				exit(json_encode($result));
		}		
		//刪除檔案
		else if($_POST['postAction'] == "deleteLocalFile"){
			$fileName = $localDir.$_POST['fileName'];
			$upload = unlink($fileName);
			// check upload status
			if (!$upload) { 
				exit(json_encode(["success"=>false,"message"=>"本地檔案刪除失敗"]));
			} else {
				exit(json_encode(["success"=>true,"message"=>"本地檔案已刪除"]));
			}
		}
	}
	
	function getConnect(){
		//設定連線資訊
		$url = Config::$FTP_SERVERS['IAB_SPEPGMD_MULTI'][0]['host'];
		$usr = Config::$FTP_SERVERS['IAB_SPEPGMD_MULTI'][0]['username'];
		$pd = Config::$FTP_SERVERS['IAB_SPEPGMD_MULTI'][0]['password'];
		/*$conn = ftp_connect($url) or die("Could not connect");
		ftp_pasv($conn, true); 
		ftp_login($conn,$usr,$pd);*/
		$conn = new Net_SFTP($url);
		if (!$conn->login($usr, $pd)) {
			(new MyLogger())->error('無法Sftp連線到FTP server('.$url.')');
			return false;
		}
		return $conn;
	}
	
	//匯入檔案
	function importFile($fileName){
		$batch = new sepgSpMdParserMulti($fileName);
		$return = $batch->getDataAndAction();
		return $return;
		//return ["success"=>true,"message"=>"移除遠端檔案失敗"];
	}
	
	//刪除遠端檔案
	function deleteRemote($fileName,$conn){
		$upload = $conn->delete($fileName); 
		// check upload status
		if (!$upload) { 
			return ["success"=>false,"message"=>"移除遠端檔案失敗"];
		} else {
			return ["success"=>true,"message"=>"已將遠端檔案移除"];
		}
	}
	
	function putToComplete($remoteFile,$localFile,$conn){
		$upload = $conn->put($remoteFile, $localFile, NET_SFTP_LOCAL_FILE);
		// check upload status
		if (!$upload) { 
			return ["success"=>false,"message"=>"檔案上傳到completey資料夾失敗"];
		} else {
			return ["success"=>true,"message"=>"已將檔案上傳到complete資料夾"];
		}
	}
	
	
	function cmp($a, $b)
	{
		return strcmp($a['id'], $b['id']);
	}

	function getIdByFileName($ndirname){
		//取得ID
		$matches = array();
		preg_match('/(\S+)_SepgSpMd\_(\S+)\_(\S+)\.dat/i', $ndirname, $matches);
		if(count($matches)==0)
			preg_match('/(\S+)_SepgSpMd\_(\S+)\.dat/i', $ndirname, $matches);
		$id = $matches[2];
		return $id;
	}

?>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="../../tool/jquery-ui1.2/jquery-ui.css">
	<script src="../../tool/jquery-ui1.2/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../../tool/timetable/TimeTable.js?<?=time()?>"></script>
	<script src="../../tool/HtmlSanitizer.js"></script>
	<script type="text/javascript" src="../../tool/datagrid/CDataGrid.js"></script>
	<script src="../../tool/jquery.loadmask.js"></script>
	<link rel="stylesheet" type="text/css" href="../../tool/jquery.loadmask.css" />
	<link rel='stylesheet' type='text/css' href='../../external-stylesheet.css'/>
	<style type="text/css">

	td.highlight {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
	td.normal {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.normal a {background:#DDDDDD !important;border: 1px #888888 solid !important;}
	td.ui-datepicker-current-day a {border: 2px #E63F00 solid !important;}
	.date{ width:200px}
	</style>
</head>
<body>


<fieldset id = "remote">
<legend>遠端待處理資料檔案</legend>
<div id = "datagridRemote" class = 'dataGrid'></div>
</fieldset>
<fieldset id = "remoteComp">
<legend>遠端已處理資料檔案</legend>
<div id = "datagridRemoteComp" class = 'dataGrid'></div>
</fieldset>
<fieldset id = "local">
<legend>AMS端資料檔案</legend>
<div id = "datagridLocal" class = 'dataGrid'></div>
</fieldset>
<script type="text/javascript">
iniRemoteDataGrid();
iniRemoteCompDataGrid();
iniLocalDataGrid();
function iniRemoteDataGrid(){
	var bypost={postAction:'getRemoteFile'};
	$.post("?",bypost,function(json){
		json.header.push('匯入');
		for(var row in json.data){
			json.data[row].push(['匯入','button']);
		}
		$("#datagridRemote").empty();
		var DG=new DataGrid("datagridRemote",json.header,json.data);
		//按鈕點擊
		DG.buttonCellOnClick=function(y,x,row) {
			if(row[x][0]== "匯入"){
				var name = row[0][0];
				var id = row[1][0];
				$('body').mask('資料匯入中...');
				importRemoteFile(name);
				
			}
		}
	}
	,'json'
	);
}



function iniRemoteCompDataGrid(){
	var bypost={postAction:'getRemoteCompFile'};
	$.post("?",bypost,function(json){
		json.header.push('匯入');
		for(var row in json.data){
			json.data[row].push(['匯入','button']);
		}
		$("#datagridRemoteComp").empty();
		var DG=new DataGrid("datagridRemoteComp",json.header,json.data);
		//按鈕點擊
		DG.buttonCellOnClick=function(y,x,row) {
			if(row[x][0]== "匯入"){
				var name = row[0][0];
				var id = row[1][0];
				$('body').mask('資料匯入中...');
				importRemoteFileComp(name);
			}
		}
	}
	,'json'
	);
}

function iniLocalDataGrid(){
	var bypost={postAction:'getLocalFile'};
	$.post("?",bypost,function(json){
		json.header.push('匯入檔案');
		json.header.push('刪除檔案');
		for(var row in json.data){
			json.data[row].push(['匯入檔案','button']);
			json.data[row].push(['刪除檔案','button']);
		}
		$("#datagridLocal").empty();
		var DG=new DataGrid("datagridLocal",json.header,json.data);
		//按鈕點擊
		DG.buttonCellOnClick=function(y,x,row) {
			if(row[x][0]== "匯入檔案"){
				var name = row[0][0];
				var id = row[1][0];
				$('body').mask('資料匯入中...');
				importLocalFile(name);
			}
			if(row[x][0]== "刪除檔案"){
				var name = row[0][0];
				var id = row[1][0];
				deleteLocalFile(name);
			}
		}
	}
	,'json'
	);
}

function importRemoteFile(fileName){
	var bypost={"postAction":'importRemoteFile',"fileName":fileName};
	$.post("?",bypost,function(json){
		if(json.success){
			iniRemoteDataGrid();
			iniLocalDataGrid();
		}
		alert(json.message);
		$('body').unmask();
	}
	,'json'
	);
}

function importRemoteFileComp(fileName){
	var bypost={"postAction":'importRemoteFileComp',"fileName":fileName};
	$.post("?",bypost,function(json){
		if(json.success){
			iniRemoteCompDataGrid();
			iniLocalDataGrid();
		}
		alert(json.message);
		$('body').unmask();
	}
	,'json'
	);
}

function importLocalFile(fileName){
	var bypost={"postAction":'importLocalFile',"fileName":fileName};
	$.post("?",bypost,function(json){
		if(json.success)
			iniLocalDataGrid();
		alert(json.message);
		$('body').unmask();
	}
	,'json'
	);
}

function deleteLocalFile(fileName){
	var bypost={"postAction":'deleteLocalFile',"fileName":fileName};
	$.post("?",bypost,function(json){
			if(json.success)
				iniLocalDataGrid();
			alert(json.message);
		}
		,'json'
	);
}
</script>
</body>