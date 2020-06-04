<?php
	
	include('../../tool/auth/authAJAX.php');
	include('Config_TVDM.php');
	require_once '../../tool/phpExtendFunction.php';
	$TVDMId= $_GET["TVDMId"];
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
	//清空temp資料夾
	$files = glob($tempDir.'/*'); // get all file names
	foreach($files as $file){ // iterate files
	  if(is_file($file))
		unlink($file); // delete file
	}
	//嘗試將素材檔搬運至temp資料夾
	$sql = "SELECT * FROM TVDM廣告素材 WHERE TVDM識別碼 = ? order by 順序,畫質";
	$res = $my->getResultArray($sql,'s',$TVDMId);
	$count = 0;
	foreach($res as $material){
		if($material["畫質"]==0)
			$quality = "sd";
		else
			$quality = "hd";
		
		if($material["上傳原始檔名"]==null || $material["上傳原始檔名"]==""){
			;
		}
		else{
			$fileNameA=explode(".",$material["上傳原始檔名"]);
			$type = end($fileNameA);
			$imageFilename='images/'.$TVDMId.$quality."_".$material["順序"].".".$type;
			if(file_exists($imageFilename)){
				$tempfilename='tempFile/'.$_SESSION['AMS']['使用者識別碼'].'/'.$count.".".$type;
				copy($imageFilename,$tempfilename);
			}
		}
		$count++;
	}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../../tool/jquery-ui1.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.form.js"></script> 
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<link href="../../tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<style type="text/css">
table.sortableTable {
  border: 1px solid #1C6EA4;
  background-color: #ECF5FF;
  width: 100%;
  text-align: left;
  border-collapse: collapse;
}
table.sortableTable td, table.sortableTable th {
  border: 1px solid #AAAAAA;
  padding: 3px 2px;
}
table.sortableTable tbody td {
  font-size: 13px;
}
table.sortableTable tr:nth-child(even) {
  background: #D2E9FF;
}
table.sortableTable thead {
  background: #84C1FF;
  background: -moz-linear-gradient(top, #46A3FF 0%, #66B3FF 66%, #84C1FF 100%);
  background: -webkit-linear-gradient(top, #46A3FF 0%, #66B3FF 66%, #84C1FF 100%);
  background: linear-gradient(to bottom, #46A3FF 0%, #66B3FF 66%, #84C1FF 100%);
  border-bottom: 2px solid #444444;
}
table.sortableTable thead th {
  font-size: 15px;
  font-weight: bold;

  border-left: 2px solid #D2E9FF;
}
table.sortableTable thead th:first-child {
  border-left: none;
}
table.sortableTable tbody tr:hover {
  background-color: #E0E0E0;
  border: 2px solid #9D9D9D;
}

</style>
</head>
<body>
<fieldset>
<legend>基本資料</legend>
<table width = '100%' class='styledTable2' id = "basicInfoTable">
<tbody>
<tr><th>TVDM代號</th><td><input id = "tvdmId" type="text" disabled></input></td></tr>
<tr><th>說明</th><td><input id = "info" type="text"></input></td></tr>
<tr><th>走期</th><td><input id = "startDate" type="text" value = "">~<input id = "endDate" type="text" value = ""></td></tr>
<tr><th>售價</th><td><input id = "price" type="text" value = ""></td></tr>
<tr><th>URL</th><td>
<table>
<tbody class='styledTable'>
<tr><th>OMP:</th><td id = "ompurl"></td></tr>
<tr><th>IAP HD:</th><td id = "hdurl"></td></tr>
<tr><th>IAP SD:</th><td id = "sdurl" ></td></tr>
</tbody>
</table>
</td></tr>
<tr><th>備註</th><td><textarea id="ps" rows="6" cols="50"></textarea></td></tr>
</tbody>
</table>
</fieldset>

<fieldset>
<legend>素材設定</legend>
HD <button id="addHdMaterialBtn" class="darkButton" quality="HD">新增HD素材</button><br>
<table width = '100%' class='sortableTable' id = "HDMaterialTable">
<thead><tr><th>素材順序</th><th>上傳素材名稱</th><th>遠端URL連結</th><th>更改圖片</th><th>移除</th></tr></thead>
<tbody id = "HDMaterialTableBody" >
</tbody>
</table>
SD <button id="addSdMaterialBtn" class="darkButton" quality="SD">新增SD素材</button><br>
<table width = '100%' class='sortableTable' id = "SDMaterialTable">
<thead><tr><th>素材順序</th><th>上傳素材名稱</th><th>遠端URL連結</th><th>更改圖片</th><th>移除</th></tr></thead>
<tbody id = "SDMaterialTableBody" >
</tbody>
</table>
</fieldset>

<br>
<button id = "TVDMSubmit" onclick="saveToDb()">確定</button>

<div id = "uploadDialog">
<table class = "styledTable">
<tbody>
<tr><th>選擇上傳素材檔案:</th><td><form action="ajaxUploadingFile.php" method="post" enctype="multipart/form-data" id="uploadFileForm">
	<input type="hidden" name="MAX_FILE_SIZE" value="800000000">
	<input type="hidden" name="countid" id = "countid">
	<input type="file" name="fileToUpload" id="fileToUpload"></form><button id="clearMaterial">取消素材</button><a id = 'mtypeMessage'></a></form></td></tr>
<tr><th>預定圖片畫質</th><td><input id = "materialQuality" type="text" value = "" style="width:60px" class="picInput" disabled></input></td></tr>
<tr><th>上傳檔名</th><td><input id = "上傳原始檔名" type="text" value = "" class="picInput" disabled></input></td></tr>
<tr><th>圖片素材寬度</th><td><input id = "圖片素材寬度" type="number" value = "" style="width:60px" class="picInput" disabled></input>(需等於<a id = "wLimitText"></a>)</td></tr>
<tr><th>圖片素材高度</th><td><input id = "圖片素材高度" type="number" value = "" style="width:60px" class="picInput" disabled></input>(需等於<a id = "hLimitText"></a>)</td></tr>
<tr><th>圖片素材大小</th><td><input id = "圖片素材大小" type="number" value = "" style="width:60px" class="picInput" disabled></input>kb(需小於<a id = "sLimitText"></a>kb)</td></tr>
</tbody>
</table>
<br>
<button id = "commitMaterial">上傳</button>
</div>

<script type="text/javascript">
var materialLimit={"SD":{"width":720,"high":480,"size":200},"HD":{"width":1280,"high":720,"size":400}};
//識別每一筆素材使用的ID，與素材順序無關
var materialCount = 0;

$("#tvdmId").val("<?=$TVDMId?>");
$('#uploadDialog').dialog({
	autoOpen:false,
	//width:'80%',
	modal:true
});
//素材列表可排序
$( ".sortableTable tbody" ).sortable({
	update: function( event, ui ) {
		var order = 0;
		$(this).find("tr").each( function(e) {
			$(this).find("td:first-child").text(++order);
		});
	}
});

$( "#startDate" ).datetimepicker({	
	dateFormat: "yy-mm-dd",
	showSecond: true,
	timeFormat: 'HH:mm:ss',
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	//minDate: d.yyyymmdd()+' 00:00:00',
	onClose: function( selectedDate ) {
		$( "#endDate" ).datepicker( "option", "minDate", selectedDate );
	}
});
$( "#endDate" ).datetimepicker({
	dateFormat: "yy-mm-dd",
	showSecond: true,
	timeFormat: 'HH:mm:ss',
	hour: 23,
	minute: 59,
	second: 59,
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	onClose: function( selectedDate ) {
		$( "#startDate" ).datepicker( "option", "maxDate", selectedDate );
	}
});
//新增素材按鈕
$("#addHdMaterialBtn,#addSdMaterialBtn").click(function(){
	openMaterialDialog($(this).attr("quality"),"new");
});


//當有素材被選擇
$("#fileToUpload").change(function (e) {
    if(this.disabled) return alert('File upload not supported!');
	
    var F = this.files;
    if(F && F[0])
		for(var i=0; i<F.length; i++)
			readImage( F[i] );
});


//上傳素材按鈕
$("#commitMaterial").click(function(){
	var compareLimit =[];
	switch($("#materialQuality").val()){
		case "HD":
			compareLimit = materialLimit["HD"];
			break;
		case "SD":
			compareLimit = materialLimit["SD"];
			break;
	}
	if($("#fileToUpload").val()=='')
		alert("未選擇檔案");
	else if($('#圖片素材寬度').val()!=compareLimit["width"])
		alert("圖片寬度不符，需為"+compareLimit["width"]);
	else if($('#圖片素材高度').val()!=compareLimit["high"])
		alert("圖片高度不符，需為"+compareLimit["high"]);
	else if($('#圖片素材大小').val()>compareLimit["size"])
		alert("圖片大小不符，需小於"+compareLimit["size"]);
	else{
		mdata = [];
		mdata["畫質"] = ($("#materialQuality").val()=="SD")?0:1;
		mdata["上傳原始檔名"]=$("#上傳原始檔名").val();
		mdata["URL連結"]="";
		mdata["順序"]="";
		if($("#countid").val() == "new"){
			$("#countid").val(materialCount);
			appendMaterial(mdata);
		}else{
			updateMaterial(mdata);
		}
		uploadMaterialFile();
	}
	
});

//清除素材按鈕
$('#clearMaterial').click(function(){
	clearMaterial();
});


function clearMaterial(){
	$('#fileToUpload').val('');
	$("#圖片素材寬度,#圖片素材高度,#圖片素材大小,#上傳原始檔名").val("");
}

var bypost = {"action":"getTVDMInfo","TVDMId":$("#tvdmId").val()}
$.post("ajaxToDb_TVDM.php", bypost,
	function(data){
		if(data["success"])
			showVal(data['data']);
	},
	'json'
);
//顯示基本資訊
function showVal(data){
	$("#info").val(data['說明']);
	$("#startDate").val(data['開始日期']);
	$("#endDate").val(data['結束日期']);
	$("#price").val(data['售價']);
	$("#ps").val(data['備註']);
	$("#ompurl").text(data['ompurl']);
	$("#sdurl").text(data['sdurl']);
	$("#hdurl").text(data['hdurl']);
	for(var i in data['material']){
		appendMaterial(data['material'][i]);
	}
	$(".editMaterilBtn").click(function(){
		var cid = $(this).attr("countid");
		openMaterialDialog($(this).attr("quality"),cid);
	});
	$(".deleteMaterilBtn").click(function(){
		var cid = $(this).attr("countid");
		var quality = $(this).attr("quality");
		$("#"+quality+"mtr"+cid).remove();
	});
}
//append素材
function appendMaterial(mdata){
	var tableId = "HDMaterialTable";
	if(mdata["畫質"]==0){
		mdata["畫質"] = "SD";
		tableId = "SDMaterialTable";
	}else{
		mdata["畫質"] = "HD";
	}
	
	$( "#"+tableId+" tbody" ).append("<tr id = '"+mdata["畫質"]+"mtr"+materialCount+"'><td class = 'materialOrder'>"
	+"</td><td class = 'materialName'>"+mdata["上傳原始檔名"]
	+"</td><td class = 'materialUrl'>"+mdata["URL連結"]
	+"</td><td><button class='editMaterilBtn' quality='"+mdata["畫質"]+"' countid='"+(materialCount)+"'>更改</button></td><td><button class='deleteMaterilBtn'  quality='"+mdata["畫質"]+"' countid='"+(materialCount)+"'>移除</button></td></tr>");
	materialCount++;
	var order = 0;
	$("#"+tableId+" tbody tr").each( function(e) {
		$(this).find("td:first-child").text(++order);
	});
}
//更新素材
function updateMaterial(mdata){
	var tableId = "HDMaterialTable";
	if(mdata["畫質"]==0){
		mdata["畫質"] = "SD";
		tableId = "SDMaterialTable";
	}else{
		mdata["畫質"] = "HD";
	}
	
	$("#"+mdata["畫質"]+"mtr"+$("#countid").val()).html("<td class = 'materialOrder'>"+mdata["順序"]
	+"</td><td class = 'materialName'>"+mdata["上傳原始檔名"]
	+"</td><td class = 'materialUrl'>"+mdata["URL連結"]
	+"</td><td><button class='editMaterilBtn' quality='"+mdata["畫質"]+"' countid='"+$("#countid").val()+"'>更改</button></td><td><button class='deleteMaterilBtn'  quality='"+$("#countid").val()+"' countid=''>移除</button></td>");
	var order = 0;
	$("#"+tableId+" tbody tr").each( function(e) {
		$(this).find("td:first-child").text(++order);
	});
}

function openMaterialDialog(quality,countid){
	clearMaterial();
	$("#materialQuality").val(quality);
	$("#countid").val(countid);
	var compareLimit =[];
	switch(quality){
		case "HD":
			compareLimit = materialLimit["HD"];
			break;
		case "SD":
			compareLimit = materialLimit["SD"];
			break;
	}
	$("#wLimitText").text(compareLimit["width"]);
	$("#hLimitText").text(compareLimit["high"]);
	$("#sLimitText").text(compareLimit["size"]);
	$('#uploadDialog').dialog("open");
}

//自動判斷圖片高度
function readImage(file) {
    var reader = new FileReader();
    var image  = new Image();
    reader.readAsDataURL(file);  
    reader.onload = function(_file) {
        image.src= _file.target.result;
        image.onload = function() {
            var w = this.width,
                h = this.height,
                t = file.type,
                n = file.name,
                s = ~~(file.size/1024);
            $('#圖片素材寬度').val(w).prop('disabled',true);
			$('#圖片素材高度').val(h).prop('disabled',true);        
			$('#圖片素材大小').val(s).prop('disabled',true);        
			$('#上傳原始檔名').val(n).prop('disabled',true);        
		};
        image.onerror= function() {
            alert('Invalid file type: '+ file.type);
        };
    };
}

function uploadMaterialFile(){
	var options = { 
		// target:        '#output1',   // target element(s) to be updated with server response 
        //beforeSubmit:  showRequest,  // pre-submit callback 
        success:	upLoadResponse, // post-submit callback 
		dataType:	'json' 
        // other available options: 
        //url:       url         // override for form's 'action' attribute 
        //type:      type        // 'get' or 'post', override for form's 'method' attribute 
        //dataType:  null        // 'xml', 'script', or 'json' (expected server response type) 
        //clearForm: true        // clear all form fields after successful submit 
        //resetForm: true        // reset the form after successful submit 
        // $.ajax options can be used here too, for example: 
        //timeout:   3000 
    }; 
	$("#uploadFileForm").ajaxForm(options).submit();
	
	function upLoadResponse(response, statusText, xhr, $form)  {
		if(statusText=='success'){
			$( "#uploadDialog" ).dialog('close');
			if(response['success']){
				;
			}
			else
				alert(response['message']);
		}
	}
}


function saveToDb(){
	//TVDM服務基本資訊
	var bypost={
		'action':"儲存TVDM資訊",
		'TVDM識別碼':$("#tvdmId").val(),
		'說明':$("#info").val(),
		'售價':$("#price").val(),
		'開始日期':$("#startDate").val(),
		'結束日期':$("#endDate").val(),
		'備註':$("#ps").val(),
		"素材":{"HD":[],"SD":[]}
	};
	//TVDM素材資訊
	$("#HDMaterialTableBody tr").each( function(e) {
		temp = {};
		temp["順序"]=$(this).find("td.materialOrder").first().text();
		temp["上傳原始檔名"]=$(this).find("td.materialName").first().text();
		temp["URL連結"]=$(this).find("td.materialUrl").first().text();
		temp["countid"]=$(this).find("td button.editMaterilBtn").first().attr("countid");
		bypost["素材"]["HD"].push(temp);
	});
	$("#SDMaterialTableBody tr").each( function(e) {
		temp = {};
		temp["順序"]=$(this).find(".materialOrder").first().text();
		temp["上傳原始檔名"]=$(this).find("td.materialName").first().text();
		temp["URL連結"]=$(this).find("td.materialUrl").first().text();
		temp["countid"]=$(this).find("td button.editMaterilBtn").first().attr("countid");
		bypost["素材"]["SD"].push(temp);
	});
	if(bypost["素材"]["HD"].length==0 || bypost["素材"]["SD"].length==0){
		alert("HD與SD素材都需至少設定一筆");
		return 0;
	}
		
	bypost["素材"]=JSON.stringify(bypost["素材"]);
	
	$.post("ajaxToDb_TVDM.php",bypost)
	.done(function(data){
		var result=$.parseJSON(data);
		
		if(result["success"]){
			alert("儲存成功，請派送至TVDM系統");
			parent.closeDialog();
		}
		else{
			alert(result["message"]);
		}
	});
}

</script>
</body>
</html>
