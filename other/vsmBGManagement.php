<?php
	
	include('../tool/auth/authAJAX.php');
	include('../Config_VSM_Meta.php');
	set_include_path('../tool/phpseclib');
	include('Net/SFTP.php');
	//@include('../tool/auth/auth.php')
	if(isset($_POST['postAction'])){
		//設定連線資訊
		$url = Config::$FTP_SERVERS['VSM'][0]['host'];
		$usr = Config::$FTP_SERVERS['VSM'][0]['username'];
		$pd = Config::$FTP_SERVERS['VSM'][0]['password'];
		/*$conn = ftp_connect($url) or die("Could not connect");
		ftp_pasv($conn, true); 
		ftp_login($conn,$usr,$pd);*/
		$conn = new Net_SFTP($url);
		if (!$conn->login($usr, $pd)) {
			(new MyLogger())->error('無法Sftp連線到FTP server('.$url.')');
			return false;
		}
		$dramaList = "uploadedFile/staticPictures/drama/";
		$movieList = "uploadedFile/staticPictures/movie/";
		$freeMovieList = "uploadedFile/staticPictures/free_movie/";
		if($_POST['position']=="movie"){
			$dir = $movieList;
		}
		else if($_POST['position']=="drama"){
			$dir = $dramaList;
		}
		else if($_POST['position']=="free_movie"){
			$dir = $freeMovieList;
		}
		
		//取得圖片
		if($_POST['postAction']=='getPictures'){
			//掃描資料夾檔案並分析生效日期
			//$nlist = ftp_nlist($conn,$dir);
			$nlist = $conn->nlist($dir);
			$srotArray = array();
			foreach($nlist as $n){
				if($n=='.'||$n=='..')
					continue;
				//去除資料夾路徑
				$ndirname = str_replace($dir,'',$n);
				//取得日期
				preg_match('/\_\_\_\_\_([0-9]+)\_\_\./', $ndirname, $matches);
				$date = $matches[1];
				//取得名稱
				$name = str_replace('_____'.$date.'__','',$ndirname);
				$srotArray[] = array("name"=>$name,"date"=>$date);
			};
			//依照日期排序
			usort($srotArray,"cmp");
			
			$data = array();
			foreach($srotArray as $imageObj){
				$data[] = array(array($imageObj["name"],"text"),array($imageObj["date"],"text"));
			}
			
			exit(json_encode(array(
					'header'=>array("素材名稱","生效日期")
					,'data'=>$data
					,'position'=>$_POST['position']
			),JSON_UNESCAPED_UNICODE));
		}
		//上傳檔案
		else if($_POST['postAction'] == "newPicture"){
			$tempDir='tempFile';
			if(!file_exists ($tempDir)){
				if (!mkdir($tempDir, 0777, true)) 
					exit(json_encode(array("success" => false,"message" => "原始檔案暫存資料夾建立失敗"),JSON_UNESCAPED_UNICODE));
			}
			
			$tempDir='tempFile/'.$_SESSION['AMS']['使用者識別碼'];
			if(!file_exists ($tempDir)){
				if (!mkdir($tempDir, 0777, true)) 
					exit(json_encode(array("success" => false,"message" => "原始檔案暫存資料夾建立失敗"),JSON_UNESCAPED_UNICODE));
			}
			
			if($_FILES['fileToUpload']['error']>0){
				$logger->error('原始檔案上傳失敗,錯誤訊息('.json_encode($_FILES['fileToUpload']['error'],JSON_UNESCAPED_UNICODE).')。');
				exit(json_encode(array("success" => false,"message" => "原始檔案上傳失敗"),JSON_UNESCAPED_UNICODE));
			}
			$fileNameA=explode(".",$_FILES['fileToUpload']['name']);
			$type = end($fileNameA);
			$tfilename = $tempDir.'/'.hash('ripemd160',iconv('UTF-8', 'UCS-4', $_FILES['fileToUpload']['name'])).'.'.$type;
			if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'],$tfilename))//複製檔案
				//exit (json_encode(array("success" => true,"message" => "原始檔案上傳成功"),JSON_UNESCAPED_UNICODE));
			
			$date = $_POST["StartDate"];
			$index = count($fileNameA)-2;
			$fileNameA[$index] = $fileNameA[$index].'_____'.$date.'__';
			$newfilename = 	implode('.',$fileNameA);
			$target_file = $dir.$newfilename;
			//$upload = ftp_put($conn, $target_file, $tfilename, FTP_BINARY); 
			$conn->put($target_file, $tfilename, NET_SFTP_LOCAL_FILE);
			$upload = unlink($tfilename);
			// check upload status
			if (!$upload) { 
				exit(json_encode(["success"=>false,"message"=>"檔案上傳至單一平台失敗"]));
			} else {
				exit(json_encode(["success"=>true,"message"=>"已將檔案上傳至單一平台"]));
			}
		}
		
		//刪除檔案
		else if($_POST['postAction'] == "deletePicture"){
			$target_file = $dir.$_POST['fileName'];
			//$upload = ftp_delete($conn, $target_file); 
			$upload = $conn->delete($target_file); 
			// check upload status
			if (!$upload) { 
				exit(json_encode(["success"=>false,"message"=>"移除檔案失敗"]));
			} else {
				exit(json_encode(["success"=>true,"message"=>"已將檔案移除"]));
			}
		}

	}
	
	
	function cmp($a, $b)
	{
		return strcmp($a['date'], $b['date']);
	}

	
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
	<script src="../tool/jquery-ui/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/timetable/TimeTable.js?<?=time()?>"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
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
<div id="dialog_form">
<table>
<form action="?" method="post" enctype="multipart/form-data" id="uploadFileForm">
<input type="text" name="method" value="" hidden>
<input type="text" id="position" name="position" value="" hidden>
<input type="text" id="postAction" name="postAction" value="newPicture" hidden>
<tr><th>上傳的素材檔案:</th><td>
			<input type="file" name="fileToUpload" id="fileToUpload"><button id="clearFile">取消素材</button><a id = 'mtypeMessage'></a></td></tr>
<tr><th>生效日期:</th><td><input id = "StartDate" type="text" value = "" size="15" name = "StartDate"></td></tr>
</form>
</table>
<br>
<button id ="submitMaterial">確定新增</button>
</div>

<fieldset id = "movie">
<legend>電影專區背景</legend>
<button id = "newMovie" class ="newBtn">新增</button>
<div id = "datagridMovie" class = 'dataGrid'></div>
</fieldset>

<fieldset id = "drama">
<button class ="newBtn">新增</button>
<legend>戲劇專區背景</legend>
<div id = "datagridDrma" class = 'dataGrid'></div>
</fieldset>

<fieldset id = "free_movie">
<button class ="newBtn">新增</button>
<legend>免費電影</legend>
<div id = "datagridFreeMovie" class = 'dataGrid'></div>
</fieldset>

<script type="text/javascript">
	
	var ajaxPath ="?";
	var g_numPerPage=10;
	var DGArray=[];

	//上傳素材的dialog
	$( "#dialog_form" ).dialog(
		{autoOpen: false,
		width: 400,
		height: 300,
		modal: true,
		title: '新增素材'
	});
	
	$( "#StartDate" ).datepicker({	
			dateFormat: "yymmdd",
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
		});
	
	$("#submitMaterial").click(function(){
		//檢查是否有設定素材與日期
		if($("#fileToUpload").val()==''){
			alert("請選擇上傳素材");
			return 0;
		}
		else if($("#StartDate").val()==""){
			alert("請選擇生效日期");
			return 0;
		}
		
		
		/*var position = $("#dialog_form").attr("position");
		var date = $("#StartDate").val();
		var filename = $('#fileToUpload').val().split('\\').pop();
		var ftpFileName = getFtpFileName(filename,date);
		var bypost = {method:'newPicture',position:position};
		$.post(ajaxPath,bypost,
			function(json){
				alert(json.message);
			}
			,"json"
		);*/
		var options = { 
		// target:        '#output1',   // target element(s) to be updated with server response 
        //beforeSubmit:  showRequest,  // pre-submit callback 
        success:	upLoadResponse // post-submit callback 
		,dataType:	'json' 
        // other available options: 
        //url:       url         // override for form's 'action' attribute 
        //type:      type        // 'get' or 'post', override for form's 'method' attribute 
        //dataType:  null        // 'xml', 'script', or 'json' (expected server response type) 
        ,clearForm: true        // clear all form fields after successful submit 
        //resetForm: true        // reset the form after successful submit 
        // $.ajax options can be used here too, for example: 
        //timeout:   3000 
		}; 
		$("#uploadFileForm").ajaxForm(options).submit();
	});
	
	function upLoadResponse(response, statusText, xhr, $form)  {
		if(statusText=='success'){
			alert(response['message']);
			if(response['success']){
				$( "#dialog_form" ).dialog("close");
				$('fieldset').each(function(){
					var position=$(this).attr('id');

				});
			}
			
		}
		for(var i in DGArray)
			DGArray[i].update();
	}
	
	$('fieldset').each(function(){
		var position=$(this).attr('id');
		iniDataGrid(position);
		setNewBtn(position);
	});
	
	function iniDataGrid(position){
		var bypost={postAction:'getPictures',position:position};
		$.post(ajaxPath,bypost,function(json){
			json.header.push('刪除');
			for(var row in json.data){
				json.data[row].push(['刪除','button']);
			}
			var dataGridId = $("#"+json.position).find(".dataGrid").attr("id");
			$('#'.dataGridId).empty();
			var DG=new DataGrid(dataGridId,json.header,json.data);
			//按鈕點擊
			DG.buttonCellOnClick=function(y,x,row) {
				if(row[x][0]== "刪除"){
					var name = row[0][0];
					var date = row[1][0];
					var file = getFtpFileName(name,date);
					deletePicture(file,json.position);
					
				}
			}
			
			DG.update=function(){
				$.post('?',bypost,function(json) {
						for(var row in json.data){
							json.data[row].push(['刪除','button']);
						}
						DG.set_data(json.data);
					},'json');
			}
			DGArray[position]=DG;
		}
		,'json'
		);
	}
	
	function setNewBtn(position){
		$("#"+position).find(".newBtn").click(function(){
			$("#position").val(position);
			$( "#dialog_form" ).dialog("open");
		});	
	}
	
	function getFtpFileName(name,date){
		var namePart = name.split('.');
		namePart[namePart.length-2] =  namePart[namePart.length-2]+"_____"+date+"__";
		var file = namePart.join('.');
		return file;
	}
	
	function deletePicture(file,position){
		var bypost = {postAction:"deletePicture",fileName:file,position:position};
		$.post(ajaxPath,bypost,
			function(json){
				alert(json['message']);
				if(json['success']){
					for(var i in DGArray)
					DGArray[i].update();
				}
			}
			,"json"
		);
	}
</script>
</body>
</html>
