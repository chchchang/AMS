<?php
	include('../tool/auth/authAJAX.php');
	if(isset($_POST['method'])){
		//檢查使用素材的託播單
		if($_POST['method']=='檢查素材託播單'){
			$sql = 'SELECT 託播單.託播單識別碼,託播單名稱 FROM 託播單,託播單素材 WHERE 託播單.託播單識別碼 = 託播單素材.託播單識別碼 AND 素材識別碼 = ? AND 託播單狀態識別碼 IN (2,4)';
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->bind_param('i',$_POST['素材識別碼'])) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$feedBack = array();
			while($row = $res->fetch_assoc()){
				$feedBack[]=$row;
			}
			exit(json_encode(array('success'=>true,'data'=>$feedBack,'id'=>intval($_POST['素材識別碼'])),JSON_UNESCAPED_UNICODE));
		}
		exit;
	}
	
	$sql = 'SELECT 產業類型名稱,產業類型識別碼,產業類型說明 FROM 產業類型 WHERE 上層產業類型識別碼 IS NULL';
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	
	$IndustryType=array();
	while($row=$res->fetch_assoc()) {
		$IndustryType[]=array('產業類型識別碼'=>$row['產業類型識別碼'],'產業類型說明'=>$row['產業類型說明'],'產業類型名稱'=>$row['產業類型名稱']);
	}
	$IndustryType=json_encode($IndustryType,JSON_UNESCAPED_UNICODE);
	
	$sql = 'SELECT 素材群組名稱,素材群組識別碼 FROM 素材群組 WHERE DISABLE_TIME IS NULL AND DELETED_TIME IS NULL';
	
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	
	$materialGroup=array();
	while($row=$res->fetch_assoc()) {
		$materialGroup[]=array('素材群組名稱'=>$row['素材群組名稱'],'素材群組識別碼'=>$row['素材群組識別碼']);
	}
	$materialGroup=json_encode($materialGroup,JSON_UNESCAPED_UNICODE);
	
	$my->close();
	
	$action='info';
	if(isset($_GET["action"]))
		$action=htmlspecialchars($_GET["action"], ENT_QUOTES, 'UTF-8'); 
	$id=htmlspecialchars($_GET["id"], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script src="../tool/iframeAutoHeight.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' /> 
<style type="text/css">
body{
	text-align: center;
}
.Center{
	text-align: center;
}

button{
	margin-top: 5px;
    margin-bottom: 5px;
	margin-right:5px; 
	margin-left:5px; 
}
</style>

</head>
<body>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="dialog_form2">新增素材資料中...</div>
<div align="center" valign="center">
	<div class ="basicBlock" style="width:625px" align="left" valign="center">
		<fieldset style="width:600px">
			<legend>素材詳細資料</legend>
			<table width = '100%' class='styledTable2'>
			<tr><th>素材群組:</th><td><select id="素材群組"></select> <button id = 'materialGroupInfo' class = 'darkButton'>詳細資料</button></td></tr>
			<tr><th><label>素材名稱*:</th><td></label><input id = "素材名稱" type="text" value = ""  class ="nonNull" style="width:400px"></td></tr>
			<tr><th><label>素材說明:</th><td></label><input id = "素材說明" type="text" value = "" style="width:400px"></td></tr>
			<tr><th>上層產業類型:</th><td><select id="上層產業類型"></select></td></tr>
			<tr><th>產業類型*:</th><td><select id="產業類型" ></select></td></tr>
			<tr><th>素材有效期間:</th><td><input id = "StartDate" type="text" value = "" size="15" >~<input id = "EndDate" type="text" value = "" size="15" ></td></tr>
			<tr><th><label>素材類型*:</label></th><td>
			<p class = "textM"><input id ="textRadio"  type="radio" name="myRadio" value="文字">文字: 文字素材內容</p><p><blockquote class = "textM"><textarea id = "文字素材內容" rows="4" cols="20" class="textInput" style="width:400px"></textarea></blockquote></p>
			<p class = "picM"><input id="picRadio" type="radio" name="myRadio" value="圖片">圖片: 圖片素材寬度 <input id = "圖片素材寬度" type="number" value = "" style="width:60px" class="picInput" readonly>，圖片素材高度 <input id = "圖片素材高度" type="number" value = "" style="width:60px" class="picInput" readonly></p>
			<p class = "filmM"><input id ="filmRadio" type="radio" name="myRadio" value="影片">影片: 影片素材秒數 <input id = "影片素材秒數" type="number" value = "" style="width:60px" class="filmInput" ><br>
				<blockquote class = "filmM">
				<table>
				<tr><td>影片畫質:</td><td><select id="影片畫質" class="filmInput"></select></td></tr>
				<tr><td>影片媒體編號:</td><td><input id = "影片媒體編號" type="text" value = "" style="width:100px" class="filmInput" readonly></td></tr>
				<tr><td>影片媒體編號北:</td><td><input id = "影片媒體編號北" type="text" value = "" style="width:100px" class="filmInput" readonly></td></tr>
				<tr><td>影片媒體編號南:</td><td><input id = "影片媒體編號南" type="text" value = "" style="width:100px" class="filmInput" readonly></td></tr>
				</table>
				</blockquote>
			</td></tr>
			<tr class="fileM"><th>上傳的素材檔案:</th><td><input type="text" id="素材原始檔名" value = "" style="width:200px" readonly><img id="fileExist" src=""><a id ="fileCheckText" style="font-size:9px"></a>
			<form action="ajaxUploadingFile.php" method="post" enctype="multipart/form-data"  class="fileM" id ="uploadFileForm">
				<input type="hidden" name="max_file_size" value="800000000">
				<input type="file" name="fileToUpload" id="fileToUpload">
				</form>
			<button id="clearFile">取消素材</button>
			<a id = 'mtypeMessage'></a>
			</td></tr>
			</table>
		</fieldset>
		<div  class ="Center"><button type="button" onclick = "clearVal()">還原</button><button type="button" id ="saveBtn">儲存</button></div>
	</div>
</div>
<script type="text/javascript">
$("input[name='myRadio']").hide();
var action = '<?=$action?>';
var id = <?=$id?>;
var material={文字:1,圖片:2,影片:3};
var IndustryType=<?=$IndustryType?>;
for(var i in IndustryType){
	var opt = $(document.createElement("option"));
	opt.text(IndustryType[i]["產業類型名稱"]+": "+IndustryType[i]["產業類型說明"])//紀錄版位類型名稱
	.val(IndustryType[i]["產業類型識別碼"])//紀錄版位類型識別碼
	.appendTo($("#上層產業類型"));
}
$( "#上層產業類型" ).combobox({
	 select: function( event, ui ) {
		setIndustry(this.value,"");
	 }
});

//清除素材按鈕
$('#clearFile').click(function(){
	$('#fileToUpload').val('');
	$("#圖片素材寬度,#圖片素材高度").val("");
});

//素材名稱自動完成
$('#素材名稱').autocomplete({
	source :function( request, response ) {
				$.post( "ajaxFunction_MaterialInfo.php",{method:'autocompleteSearch', term: request.term, column:'素材名稱'},
					function( data ) {
					response(JSON.parse(data));
				})
			}
});
//素材說明自動完成
$('#素材說明').autocomplete({
	source :function( request, response ) {
				$.post( "ajaxFunction_MaterialInfo.php",{method:'autocompleteSearch', term: request.term, column:'素材說明'},
					function( data ) {
					response(JSON.parse(data));
				})
			}
});

//設定產業資料
function setIndustry(pId,selectedId){
	$("#產業類型").empty();
	$.post( "ajaxToDB_Material.php", { action: "取得產業類型",上層產業類型識別碼:pId }, 
		function( data ) {
			for(var i in data){
				var opt = $(document.createElement("option"));
				opt.text(data[i]["產業類型名稱"]+": "+data[i]["產業類型說明"])//紀錄版位名稱
				.val(data[i]["產業類型識別碼"])//紀錄版位識別碼
				.appendTo($("#產業類型"));
			}
			if(typeof selectedId!='undefined'&&selectedId!=""){
				$( "#產業類型" ).val(selectedId);
				for(var i in data)
					if(data[i]['產業類型識別碼']==selectedId)
						$( "#產業類型" ).combobox('setText', data[i]["產業類型名稱"]+": "+data[i]["產業類型說明"]);
			}
			else{
				if(data.length>0){
					$( "#產業類型" ).combobox('setText',data[0]["產業類型名稱"]+": "+data[0]["產業類型說明"]);
					selectedId=data[0]['產業類型識別碼'];
				}
				else
					$( "#產業類型" ).combobox('setText','');
			}
			$( "#產業類型" ).val(selectedId);
		}
		,"json"
	);
}
$( "#產業類型" ).combobox();

//設訂素材群組資料
var materialGroup=<?=$materialGroup?>;
$(document.createElement("option")).text('未指定').val(0).appendTo($("#素材群組"))
for(var i in materialGroup){
	var opt = $(document.createElement("option"));
	opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
	.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
	.appendTo($("#素材群組"));
}
$( "#素材群組" ).combobox();

//設定影片畫質
$.post('ajaxFunction_MaterialInfo.php',{method:'取得影片畫質'},
function(json){
	for(var i in json){
		var opt = $(document.createElement("option"));
		opt.text(json[i]["影片畫質名稱"])//紀錄版位類型名稱
		.val(json[i]["影片畫質識別碼"])//紀錄版位類型識別碼
		.appendTo($("#影片畫質"));
	}
	$('#影片畫質').val('');
}
,'json'
);

$(function() {
	//顯示群組詳細資料視窗
	dialog=$( "#dialog_form" ).dialog({
		autoOpen: false,
		modal: true,
	});
	if($("#素材群組").val()!=""){
		$("#materialGroupInfo").click(function(){
			$("#dialog_iframe").attr("src","../material/searchMaterialGroup.php?showCertainId"+$("#素材群組").val()).css({"width":"100%","height":"100%"}); 
			dialog=$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"素材群組資訊"});
			dialog.dialog( "open" );
		});
	}
	$( "#StartDate" ).datetimepicker({	
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
	});
	$( "#EndDate" ).datetimepicker({
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		hour: 23,
		minute: 59,
		second: 59,
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
	});
		//新增檔案的視窗
	$( "#dialog_form2" ).dialog(
	{autoOpen: false,
		width: 300,
		height: 400,
		modal: true,
	});
});

if(action =='info'){
	$('button').hide();
	$("input").prop('disabled', true);
	$("#上層產業類型,#產業類型").combobox("disable");
}

$(".textInput,.picInput,.filmInput,#uploadFileBtn").prop('disabled', true);
$("input:radio").change(function(){
	var val =$("input[name='myRadio']:checked").val();
	switch(val){
		case "文字":
			$(".picInput,.filmInput").prop('disabled', true).val('');
			$(".textInput").removeAttr("disabled");
			break;
		case "圖片":
			$(".textInput,.filmInput").prop('disabled', true).val('');
			$(".picInput").removeAttr("disabled");
			break;
		case "影片":
			$(".picInput,.textInput").prop('disabled', true).val('');
			$(".filmInput").removeAttr("disabled");
			break;
		default:
			break;
	}
});
clearVal();

function clearVal(){
	$("input:not(:radio)").val("");
	$(":radio").prop('checked',false);
	if(action !='new')
		refresh();
};

var originalInfo={};
function refresh(){
	$("#uploadFileForm").hide();
	var bypost={
	action:"素材資訊表格",
	素材識別碼:id
	};
	$.post("ajaxToDB_Material.php",bypost,
	function(data){
		if(data["success"]!=undefined&&!data["success"]){
			alert(data["message"]);
			return 0;
		}
		originalInfo = data;
		$('#素材名稱').val(data["素材名稱"]);
		$('#素材說明').val(data["素材說明"]);
		$('#素材原始檔名').val(data["素材原始檔名"]);
		$("#StartDate").val((data['素材有效開始時間']==null)?'':data['素材有效開始時間']);
		$("#EndDate").val((data['素材有效結束時間']==null)?'':data['素材有效結束時間']);
		switch(data["素材類型識別碼"]){
		case 1:
			$("#textRadio").prop('checked', true);
			$(".picInput,.filmInput").val('');
			$(".filmM,.fileM,.picM").hide();
			$('#文字素材內容').val(data["文字素材內容"]);
			$('#mtypeMessage').hide();
			break;
		case 2:
			$(".textInput,.filmInput").val('');
			$("#picRadio").prop('checked', true);
			$(".textM,.filmM").hide();
			$(":radio").hide();
			$('#圖片素材寬度').val(data["圖片素材寬度"]);
			$('#圖片素材高度').val(data["圖片素材高度"]);
			$("#fileToUpload").prop('accept', "image/gif,image/jpeg,image/png,image/jpg").val('');
			$('#mtypeMessage').text('(接受gif/jpeg/png/jpg檔案)').show();
			$("#fileToUpload").change(function (e) {
				if(this.disabled) return alert('File upload not supported!');
				var F = this.files;
				if(F && F[0])
					for(var i=0; i<F.length; i++)
						readImage( F[i] );
			});
			break;
		case 3:
			$(".picInput,.textInput").val('');
			$("#filmRadio").prop('checked', true);
			$(".textM,.picM").hide();
			$('#影片素材秒數').val(data["影片素材秒數"]);
			$('#影片畫質').val(data["影片畫質識別碼"]);
			$('#影片媒體編號').val(data["影片媒體編號"]);
			$('#影片媒體編號北').val(data["影片媒體編號北"]);
			$('#影片媒體編號南').val(data["影片媒體編號南"]);
			$("#fileToUpload").prop('accept', "*").val('');
			$('#mtypeMessage').text('(接受ts/mpg檔案)').show();
			break;
		default:
				break;
		}
		if(typeof data["上層產業類型識別碼"] !='undefined'){
			$( "#上層產業類型" ).combobox('setText',data["上層產業類型名稱"]+": "+data["上層產業類型說明"]);	
			$( "#上層產業類型" ).val(data["上層產業類型識別碼"]);
		}
		setIndustry(data["上層產業類型識別碼"],data["產業類型識別碼"]);
		
		if(action!='info'){
			$(".textInput,.picInput,.filmInput").removeAttr("disabled");
		}
		
		if(typeof data["檔案存在"]!= 'undefined'){
			if(data["檔案存在"]){
				$("#fileExist").prop("src","../tool/pic/check-icon.png").show();
				$("#fileCheckText").text("素材檔案存在").css("color","#888888");
				if(action!='info')
					$("#uploadFileForm").show();
			}else{
				$("#fileExist").prop("src","../tool/pic/alert-icon.png").show();
				$("#fileCheckText").text("素材檔案遺失，請重新上傳").css("color","#FF8800");
				if(action!='info')
					$("#uploadFileForm").show();
			}
		}
		else{
			$("#fileExist").hide();
		}
		
		if($("#素材原始檔名").val()==""){
			$('#圖片素材寬度,#圖片素材高度').prop('readonly',false);
			$("#uploadFileForm").show();
		}
		if(data["素材群組識別碼"] == 0)
			$( "#素材群組" ).combobox('setText','未指定');
		else
			$( "#素材群組" ).combobox('setText',data["素材群組識別碼"]+': '+data["素材群組名稱"]);
		$( "#素材群組" ).val(data["素材群組識別碼"]);
	}
	,'json'
	);
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
                s = ~~(file.size/1024) +'KB';
            $('#圖片素材寬度').val(w);
			$('#圖片素材高度').val(h);
        };
        image.onerror= function() {
            alert('Invalid file type: '+ file.type);
        };      
    };
}


//儲存按鈕
$( "#saveBtn" ).click(function(event) {
	var nonNullEmpty= false;
	$(".nonNull").each(function(){ 
		if($.trim($(this).val())==""){
			nonNullEmpty = true;
		}
	});
	//檢查必要資訊
	if(nonNullEmpty){
		alert("請填寫必要資訊");
		$(".nonNull").css("border", "2px solid red");
		return 0;
	}
	
	if(typeof $("input[name='myRadio']:checked").val() == 'undefined'){
		alert("請選擇一樣素材總類");
		return 0;
	}
	
	if($("#產業類型").find('option:selected').text()==""){
		alert("請選擇產業類型");
		return 0;
	}
	
	if($("#StartDate").val()!=''&&$("#EndDate").val()!=''){
		if($("#StartDate").val()>$("#EndDate").val()){
				alert("有效起始時間 必須小於 有效結束時間");
				return 0;
		}
	}
		
	var type =$("input[name='myRadio']:checked").val();
	switch(type){
		case '文字':
			saveToDb();
			break;
		case '圖片':	
			if($("#fileToUpload").val()==''){
				saveToDb();
			}else
				uploadFile();
			break;
		case '影片':
			if($("#fileToUpload").val()==''){
				saveToDb();
			}else
				uploadFile();
			break;
	}
});	

function uploadFile(){
	var ext = $('#fileToUpload').val().split('.').pop().toLowerCase();
	var type =$("input[name='myRadio']:checked").val();
	switch(type){
		case '文字':
			break;
		case '圖片':
			if($("#fileToUpload").val()==''){
				alert("請選擇素材檔案");
				return 0;
			}
			if($.inArray(ext, ['gif','png','jpg','jpeg']) == -1) {
				alert('檔案類型錯誤!');
				return 0;
			}
			var fileFormat = $('#素材原始檔名').val().split('.').pop().toLowerCase();
			if(fileFormat != '' && fileFormat!=ext){
				alert('重新上傳的檔案副檔名須相同');
				return 0;
			}
			break;
		case '影片':
			if($.inArray(ext, ['ts','mpg']) == -1) {	
				alert('檔案類型錯誤!');
				return 0;
			}
			if($("#fileToUpload").val()==''){
				alert("請選擇素材檔案");
				return 0;
			}
			if($("#影片素材秒數").val()==''){
				alert("請填入秒數");
				return 0;
			}
			if($("#影片畫質").val()==''||$("#影片畫質").val()==null){
				alert("請選擇畫質");
				return 0;
			}
			break;
	}

	var options = { 
		// target:        '#output1',   // target element(s) to be updated with server response 
        //beforeSubmit:  showRequest,  // pre-submit callback 
        success:	upLoadResponse, // post-submit callback 
		dataType:	'json',
		url: 'ajaxUploadingFile.php'
        // other available options: 
        //url:       url         // override for form's 'action' attribute 
        //type:      type        // 'get' or 'post', override for form's 'method' attribute 
        //dataType:  null        // 'xml', 'script', or 'json' (expected server response type) 
        //clearForm: true        // clear all form fields after successful submit 
        //resetForm: true        // reset the form after successful submit 

        // $.ajax options can be used here too, for example: 
        //timeout:   3000 
    };
	$( "#dialog_form2" ).dialog('open');	
	$("#uploadFileForm").ajaxForm(options).submit();
	
	function upLoadResponse(response, statusText, xhr, $form)  {
		if(statusText=='success'){
			$( "#dialog_form2" ).dialog('close');
			if(response['success'])
				saveToDb();
			else
				alert(response['message']);
		}
	} 
}

function saveToDb(){
	var bypost={
		'action':"修改素材",
		'素材類型識別碼':material[$("input[name='myRadio']:checked").val()],
		'產業類型識別碼':$("#產業類型").val(),
		'素材名稱':$("#素材名稱").val(),
		'素材說明':$("#素材說明").val(),
		'素材原始檔名':($("#fileToUpload").val()=='')?$("#素材原始檔名").val():$("#fileToUpload").val().split('\\').pop(),
		'文字素材內容':$("#文字素材內容").val(),
		'圖片素材寬度':$("#圖片素材寬度").val(),
		'圖片素材高度':$("#圖片素材高度").val(),
		'影片素材秒數':$("#影片素材秒數").val()
		,'影片畫質':$("#影片畫質").val()
		,'素材群組識別碼':$("#素材群組").val()
		,'素材有效開始時間':$("#StartDate").val()
		,'素材有效結束時間':$("#EndDate").val()
		,'素材識別碼':id
	};

	if($("#fileToUpload").val()!='')
		bypost['新素材檔案上傳']=true;

	//是否有全群組相同的資訊被調整
	if($("#素材群組").val() == originalInfo['素材群組識別碼'])
	if($("#產業類型").val()!=originalInfo['產業類型識別碼']||(originalInfo['影片素材秒數']!=null && originalInfo['影片素材秒數']!=$("#影片素材秒數").val()))
	if (confirm("是否要針對整個群組修改?"))
		bypost['updateGroup']=1;
	
	
	$.post("ajaxToDB_Material.php",bypost)
	.done(function(data){
		var result=$.parseJSON(data);
		if(result["dbError"]!=undefined){
			alert(result["dbError"]);
			return 0;
		}
		if(result["success"]){
			if(bypost['新素材檔案上傳']==true){
				$.post('?',{method:'檢查素材託播單','素材識別碼':bypost['素材識別碼']},
					function(json){
						if(json['success']){
							var message = '';
							for(var i in json['data']){
								message+=json['data'][i]['託播單識別碼']+':'+json['data'][i]['託播單名稱']+'\n';
							}
							if(message!=''){
								if($("input[name='myRadio']:checked").val()=='影片'){
									message ='素材修改成功\n素材檔案更動，請重新派送素材，並重新送出以下託播單\n'+message;
								}
								else
									message ='素材修改成功\n素材檔案更動，請重新派送素材';
								alert(message);
							}
							else
								alert('素材修改成功\n素材檔案更動，請重新派送素材');
							parent.materialUpdated();
						}
					},'json'
				);
			}
			else{
				alert(result["message"]);
				parent.materialUpdated();
			}
		}
		else
			alert(result["message"]);
	});
	
}

</script>
</body>
</html>