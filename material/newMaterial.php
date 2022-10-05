<?php
/***20220923 chia_chi_chang 新增上傳後判讀秒數功能
 */
include('../tool/auth/auth.php');	
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
<script src="../tool/HtmlSanitizer.js"></script>
<script src="../tool/mediainfo/mediainfo.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' /> 
<style type="text/css">
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
			<button id = 'selectCurrentMaterial' class = 'darkButton'>填入現有素材</button>
			<table width = '100%' class='styledTable2'>
			<tr><th>素材群組:</th><td><select id="素材群組"></select><button id = 'materialGroupInfo' class = 'darkButton'>詳細資料</button></td></tr>
			<tr><th><label>素材名稱*: </label></th><td><input id = "素材名稱" type="text" value = ""  class ="nonNull" style="width:400px"></td></tr>
			<tr><th><label>素材說明: </label></th><td><input id = "素材說明" type="text" value = "" style="width:400px"></td></tr>
			<tr><th>上層產業類型:</th><td><select id="上層產業類型" ></select></td></tr>
			<tr><th>產業類型*:</th><td><select id="產業類型" ></select></td></tr>
			<tr><th>素材有效期間:</th><td><input id = "StartDate" type="text" value = "" size="15" >~<input id = "EndDate" type="text" value = "" size="15" ></td></tr>
			<tr><th><label>素材類型*:</label></th><td>
			<p id = "textM"><input  type="radio" name="materailRadio" value="文字" id = 'textRadio'>文字: 文字素材內容
				<blockquote><textarea id = "文字素材內容" rows="4" cols="20" class="textInput" style="width:400px"></textarea></blockquote></p>
			<p id = "picM"><input  type="radio" name="materailRadio" value="圖片" id = 'picRadio'>圖片: 圖片素材寬度 <input id = "圖片素材寬度" type="number" value = "" style="width:60px" class="picInput" >，圖片素材高度 <input id = "圖片素材高度" type="number" value = "" style="width:60px" class="picInput" ></p>
			<p id = "filmM"><input  type="radio" name="materailRadio" value="影片" id = 'filmRadio'>影片: 影片素材秒數 <input id = "影片素材秒數" type="number" value = "" style="width:60px" class="filmInput"  ><br>
				<blockquote>
				<table>
				<tr><td>影片畫質:</td><td><select id="影片畫質" class="filmInput"></select></td></tr>
				<tr><td>影片媒體編號:</td><td><input id = "影片媒體編號" type="text" value = "" style="width:100px" class="filmInput" readonly></td></tr>
				<tr><td>影片媒體編號北:</td><td><input id = "影片媒體編號北" type="text" value = "" style="width:100px" class="filmInput" readonly></td></tr>
				<tr><td>影片媒體編號南:</td><td><input id = "影片媒體編號南" type="text" value = "" style="width:100px" class="filmInput" readonly></td></tr>
				</table>
				</blockquote>
			<p>
			</td></tr>
			<tr><th>上傳的素材檔案:</th><td><form action="ajaxUploadingFile.php" method="post" enctype="multipart/form-data" id="uploadFileForm">
							<input type="hidden" name="MAX_FILE_SIZE" value="800000000">
							<input type="file" name="fileToUpload" id="fileToUpload"></form><button id="clearFile">取消素材</button><a id = 'mtypeMessage'></a></form></td></tr>
			</table>
		</fieldset>
		<div  class ="Center"><button type="button" onclick = 'showCheckPositionOption()'>比對版位設定</button><button type="button" onclick = "clearVal()">清空</button><button type="button" id ="saveBtn">新增</button></div>
	</div>
</div>
<div id="material_dialog_form"><table class='styledTable2'>
	<tr><th><a id="materialgroup">素材群組:</a></th><td><select id="MaterialGroup"></select><button id="materialInfo" type="button" onclick = "materialInfo()">詳細資訊</button></td></tr>
	<tr><th><a id="material">素材:</a></th><td><select id="Material"></select></td></tr>
</table></div>

<script type="text/javascript">
var material={文字:1,圖片:2,影片:3};

$.post('ajaxFunction_MaterialInfo.php',{method:'取得產業類型'},
function(IndustryType){
	for(var i in IndustryType){
		var opt = $(document.createElement("option"));
		opt.text(IndustryType[i]["產業類型名稱"]+": "+IndustryType[i]["產業類型說明"])//紀錄版位類型名稱
		.val(IndustryType[i]["產業類型識別碼"])//紀錄版位類型識別碼
		.appendTo($("#上層產業類型"));
	}
	setIndustry($( "#上層產業類型 option:selected" ).val(),"");

	$( "#上層產業類型" ).combobox({
		 select: function( event, ui ) {
			setIndustry(this.value,"");
		 }
	});
}
,'json'
);
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
//選擇素材的dialog
$( "#material_dialog_form" ).dialog(
	{autoOpen: false,
	width: 400,
	height: 300,
	modal: true,
	title: '選擇素材'
});

$('#selectCurrentMaterial').click(
	function(){
		$('#material_dialog_form').dialog('open');
	}
);

//清除素材按鈕
$('#clearFile').click(function(){
	$('#fileToUpload').val('');
	$("#圖片素材寬度,#圖片素材高度").val("");
});

//設訂素材群組資料
$( "#MaterialGroup" ).combobox({
	select: function( event, ui ) {
		setMaterial('');
	}
});
$.post('../material/ajaxFunction_MaterialInfo.php',{method:'取得素材群組'},
function(json){
	var materialGroup=json;
	$(document.createElement("option")).text('未指定').val(0).appendTo($("#MaterialGroup"));
	for(var i in materialGroup){
		var opt = $(document.createElement("option"));
		opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
		.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
		.appendTo($("#MaterialGroup"));
	}

	$("#MaterialGroup").val(0).combobox('setText', '未指定');
}
,'json'
);

function setMaterial(selectedId){
	$.post("../order/ajaxToDB_Order.php",{action:'取得可用素材',素材群組識別碼:$('#MaterialGroup').val()},
	function(json){
		if(json.success){
			$select = $("#Material");
			$select.empty();
			$(document.createElement("option")).text('0:未指定').val(0).appendTo($select);
			for(var i in json.material){
				var opt = $(document.createElement("option"));
				opt.text(json.material[i]["素材識別碼"]+":"+json.material[i]["素材名稱"])//紀錄版位類型名稱
				.val(json.material[i]["素材識別碼"])//紀錄版位類型識別碼
				.appendTo($select);
				if(selectedId==json.material[i]["素材識別碼"])
					$select.combobox('setText', json.material[i]["素材名稱"]);
			}
			if(selectedId!=''&&selectedId!=0){
				$select.val(selectedId);
			}
			else{
				$select.val(0).combobox('setText', '0:未指定');
			}
		}
	}
	,'json'
	);
}
setMaterial('');

function setSelectedMaterial(id){
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
		setUiByMaterialType(data["素材類型識別碼"]);
		switch(data["素材類型識別碼"]){
		case 1:
			$("#textRadio").prop('checked', true);
			$(".picInput,.filmInput").val('');;
			$('#文字素材內容').val(data["文字素材內容"]);
			$('#mtypeMessage').hide();
			break;
		case 2:
			$(".textInput,.filmInput").val('');
			$("#picRadio").prop('checked', true);
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
			$('#影片素材秒數').val(data["影片素材秒數"]);
			$('#影片畫質').val(data["影片畫質識別碼"]);
			//$('#影片媒體編號').val(data["影片媒體編號"]);
			//$('#影片媒體編號北').val(data["影片媒體編號北"]);
			//$('#影片媒體編號南').val(data["影片媒體編號南"]);
			$("#fileToUpload").prop('accept', "*").val('');
			$('#mtypeMessage').text('').show();
			break;
		default:
				break;
		}
		if(typeof data["上層產業類型識別碼"] !='undefined'){
			$( "#上層產業類型" ).combobox('setText',data["上層產業類型名稱"]+": "+data["上層產業類型說明"]);	
			$( "#上層產業類型" ).val(data["上層產業類型識別碼"]);
		}
		setIndustry(data["上層產業類型識別碼"],data["產業類型識別碼"]);
		
		if(data["素材群組識別碼"] == 0)
			$( "#素材群組" ).combobox('setText','未指定');
		else
			$( "#素材群組" ).combobox('setText',data["素材群組識別碼"]+': '+data["素材群組名稱"]);
		$( "#素材群組" ).val(data["素材群組識別碼"]);
	}
	,'json'
	);
}

$("#Material").combobox({
	//素材被選擇
	select: function( event, ui ) {
		setSelectedMaterial($('#Material').val());
		$('#material_dialog_form').dialog('close');
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
$.post('ajaxFunction_MaterialInfo.php',{method:'取得素材群組'},
function(json){
	var materialGroup=json;
	$(document.createElement("option")).text('未指定').val(0).appendTo($("#素材群組"));
	for(var i in materialGroup){
		var opt = $(document.createElement("option"));
		opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
		.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
		.appendTo($("#素材群組"));
	}
	$( "#素材群組" ).combobox();
}
,'json'
);

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
		
		$("#materialGroupInfo").click(function(){
			if($("#素材群組").val()!=""){
				$("#dialog_iframe").attr("src","../material/searchMaterialGroup.php?showCertainId="+$("#素材群組").val()).css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"素材群組資訊"});
				dialog.dialog( "open" );
			}
		});
		
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


$(".textInput,.picInput,.filmInput,#fileToUpload,#uploadFileBtn").prop('disabled', true);
//設訂素材類型選擇時，input是否可輸入
$("input[name='materailRadio']").change(function(){
	var val =$("input[name='materailRadio']:checked").val();
	setUiByMaterialType(material[val]);
});

function setUiByMaterialType(val){
	switch(val){
		case 1:
			$("#fileToUpload,#uploadFileBtn").prop('disabled', true);
			$(".picInput,.filmInput,#fileToUpload").prop('disabled', true).val('');
			$(".textInput").removeAttr("disabled");
			$('#mtypeMessage').hide();
			break;
		case 2:
			$("#fileToUpload,#uploadFileBtn").prop('disabled', false);
			$("#fileToUpload").prop('accept', "image/gif,image/jpeg,image/png,image/jpg").val('');
			$(".textInput,.filmInput").prop('disabled', true).val('');
			$(".picInput").removeAttr("disabled");
			$('#mtypeMessage').text('(接受gif/jpeg/png/jpg檔案)').show();
			break;
		case 3:
			$("#fileToUpload,#uploadFileBtn").prop('disabled', false);
			$("#fileToUpload").prop('accept', "").val('');
			$(".picInput,.textInput").prop('disabled', true).val('');
			$(".filmInput").removeAttr("disabled");
			//$('#mtypeMessage').text('(接受ts/mpg檔案)').show();
			break;
		default:
			break;
	}
}

//選擇某一影片畫質，將其他畫質設訂清空
$(".filmQuality").change(function(){
	$(this).siblings('.filmQuality').val('');
});

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
            $('#圖片素材寬度').val(w).prop('disabled',true);
			$('#圖片素材高度').val(h).prop('disabled',true);        
		};
        image.onerror= function() {
            alert('Invalid file type: '+ file.type);
        };
    };

}
$("#fileToUpload").change(function (e) {
    if(this.disabled) return alert('File upload not supported!');
	
    var F = this.files;
    if(F && F[0])
		for(var i=0; i<F.length; i++)
			if($("input[name='materailRadio']:checked").val()=='圖片')
				readImage( F[i] );
});

function clearVal(){
	$("input:not(:radio)").val("");
	$(".textInput,.picInput,.filmInput,#fileToUpload,#uploadFileBtn").prop('disabled', true);
	$(":radio").prop('checked',false);
};


$( "#saveBtn" ).click(function(event) {
	var nonNullEmpty= false;
	$(".nonNull").each(function(){ 
		if($.trim($(this).val())==""){
			nonNullEmpty = true;
		}
	});
	
	if(nonNullEmpty){
		alert("請填寫必要資訊");
		$(".nonNull").css("border", "2px solid red");
		return 0;
	}
	
	if($("#產業類型").find('option:selected').text()==""){
		alert("請選擇產業類型");
		return 0;
	}
	
	if(typeof $("input[name='materailRadio']:checked").val() == 'undefined'){
		alert("請選擇一樣素材種類");
		return 0;
	}
	
	if($("#StartDate").val()!=''&&$("#EndDate").val()!='')
	if($("#StartDate").val()>$("#EndDate").val()){
			alert("有效起始時間 必須小於 有效結束時間");
			return 0;
	}
	
	var type =$("input[name='materailRadio']:checked").val();
	switch(type){
		case '文字':
			saveToDb();
			break;
		case '圖片':	
			if($("#fileToUpload").val()==''){
				if(!confirm("未選擇素材，是否繼續?"))
					return 0;
				else
					saveToDb();
			}
			else
				uploadFile();
			break;
		case '影片':
			if($("#影片畫質").val()==''||$("#影片畫質").val()==null){
					alert("請選擇畫質");
					return 0;
				}
			if($("#fileToUpload").val()==''){
				if(!confirm("未選擇素材，是否繼續?"))
				return 0;
				else
					saveToDb();
			}else{
				if($("#影片素材秒數").val()==''){
					alert("請填入秒數");
					return 0;
				}
					uploadFile();
			}
			break;
	}
});	

function uploadFile(){
	//簡查副檔名與header是否正確
	/*var ext = $('#fileToUpload').val().split('.').pop().toLowerCase();
	var type =$("input[name='materailRadio']:checked").val();
	switch(type){
		case '文字':
			break;
		case '圖片':		
			if($.inArray(ext, ['gif','png','jpg','jpeg']) == -1) {	
				alert('檔案類型錯誤!');
				return 0;
			}
			break;
		case '影片':
			if($.inArray(ext, ['ts','mpg']) == -1) {	
				alert('檔案類型錯誤!');
				return 0;
			}
			break;
	}*/
	var control = document.getElementById("fileToUpload");
	var type =$("input[name='materailRadio']:checked").val();
	switch(type){
		case '文字':
			break;
		case '圖片':
			var file = control.files[0];
			var ext = file.name.split('.').pop().toLowerCase();
			var headerType = file.type;
			var tempIndex = $.inArray(ext, ['gif','png','jpg','jpeg']);
			if( tempIndex == -1) {	
				alert('檔案類型錯誤!');
				return 0;
			}
			var headerTypes = ['image/gif','image/png','image/jpeg','image/jpeg'];
			if(headerTypes[tempIndex]!= headerType) {	
				alert('檔案header定義類型錯誤!');
				return 0;
			}
			
		
			break;
		case '影片':
			/*var file = control.files[0];
			var ext = file.name.split('.').pop().toLowerCase();
			var headerType = file.type;
			var tempIndex = $.inArray(ext, ['ts','mpg']);
			if(tempIndex == -1) {	
				alert('檔案類型錯誤!');
				return 0;
			}
			var headerTypes = ['video/vnd.dlna.mpeg-tts','video/mpeg'];
			if(headerTypes[tempIndex]!= headerType) {	
				alert('檔案header定義類型錯誤!'+headerTypes[tempIndex]+' : '+headerType);
				return 0;
			}*/
			break;
	}
	

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
		'action':"新增素材",
		'素材類型識別碼':material[$("input[name='materailRadio']:checked").val()],
		'產業類型識別碼':$("#產業類型").val(),
		'素材名稱':$("#素材名稱").val(),
		'素材說明':$("#素材說明").val(),
		'素材原始檔名':$("#fileToUpload").val().split('\\').pop(),
		'文字素材內容':$("#文字素材內容").val(),
		'圖片素材寬度':$("#圖片素材寬度").val(),
		'圖片素材高度':$("#圖片素材高度").val(),
		'影片素材秒數':$("#影片素材秒數").val()
		,'影片畫質':$("#影片畫質").val()
		,'影片媒體編號':$("#影片媒體編號").val()
		,'影片媒體編號北':$("#影片媒體編號北").val()
		,'影片媒體編號南':$("#影片媒體編號南").val()
		,'素材群組識別碼':$("#素材群組").val()
		,'素材有效開始時間':$("#StartDate").val()
		,'素材有效結束時間':$("#EndDate").val()
	};
	if($("#fileToUpload").val()!='')
		bypost['新素材檔案上傳']=true;
	$.post("ajaxToDB_Material.php",bypost)
	.done(function(data){
		var result=$.parseJSON(data);
		if(result["dbError"]!=undefined){
			alert(result["dbError"]);
			return 0;
		}
		if(result["success"]){
			//clearVal();
		}
		alert(result["message"]);
	});
}


</script>

<!--檢察素材是否符合板位設訂-->
<div id="CheckPositionDialog" >
	<fieldset style="width:600px">
		<legend>檢察素材是否符合板位設訂</legend>
		<table width = '100%' class='styledTable2'>
			<tr><th>版位類型:</th><td><select id="檢察版位類型"></select></td></tr>
			<tr><th>版位:</th><td><select id="檢察版位"></select></td></tr>
			<tr><th>版位有效期間:</th><td><a id="有效期間"></a><a id = 'timeCheckResult'></a></td></tr>
			<tr><th>素材格式檢查方式:</th><td><input type="radio" name="mCheckOption" value=0 checked>與格式設定一致 <input type="radio" name="mCheckOption" value=1>小於或等於格式設定</td></tr>
			<tr><th>素材格式檢查結果:</th><td><table class='styledTable'><thead><th>素材順序</th><th>設定</th><th>比較結果</th></thead>
			<tbody id = '素材設定tbody'></tbody>
			</table></tr>
		</table>
		
	</fieldset>
</div>
<script type="text/javascript">
$( "#CheckPositionDialog" ).dialog({
	autoOpen: false,
	modal: true,
});
function showCheckPositionOption(){
	$("#CheckPositionDialog").dialog({width:'650px', title:"依版位設定檢察素材"});
	$("#CheckPositionDialog").dialog( "open" );
	checkPositionOption();
}
//檢察素材是否符合板位設訂
//設定版位選項
$.post('../order/orderManaging.php',{method:'getPositionTypeSelection'}
	,function(positionTypeOption){
		for(var i in positionTypeOption){
			var opt = $(document.createElement("option"));
			opt.text(positionTypeOption[i][1])//紀錄版位類型名稱
			.val(positionTypeOption[i][0])//紀錄版位類型識別碼
			.appendTo($("#檢察版位類型"));
		}
		$( "#檢察版位類型" ).combobox({
			 select: function( event, ui ) {
				setPosition(this.value);
			 }
		});
		$( "#檢察版位類型" ).combobox('setText','');
		setPosition($( "#檢察版位類型 option:selected" ).val());
	}
	,'json'
);

$("input[name='mCheckOption']:checked").change(function(){
	checkPositionOption();
});
//設定版位資料
function setPosition(pId){
	$("#檢察版位").empty();
	$.post( "../order/ajaxToDB_Order.php", { action: "getPositionByPositionType",版位類型識別碼:pId }, 
		function( data ) {
			for(var i in data){
				var opt = $(document.createElement("option"));
				opt.text(data[i][1])//紀錄版位名稱
				.val(data[i][0])//紀錄版位識別碼
				.appendTo($("#檢察版位"));
			}
			$( "#檢察版位" ).combobox('setText','');
		}
		,"json"
	);
}


$( "#檢察版位" ).combobox({
	select: function( event, ui ) {
		checkPositionOption();
	}
});

function checkPositionOption(){
	$.post('ajaxFunction_MaterialInfo.php',{method:'檢查版位素材設定',版位識別碼:$( "#檢察版位" ).val()}
		,function(json){
			if(!json.success){
				alert(json.message);
			}
			else{
				$('#有效期間').text(((json.result.有效起始時間==null)?'無限制':json.result.有效起始時間)+ '~' +((json.result.有效結束時間==null)?'無限制':json.result.有效結束時間));
		
			}
			$('#素材設定tbody').empty();
			
			for(var i in json.result.素材設定)
				checkMaterialOption(json.result.素材設定[i]);
			
			//檢察走期
			var result='';
			if(json.result.有效起始時間!=null){
				if($("#StartDate").val()<json.result.有效起始時間)
					result='  走期不符';
			}
			else if(json.result.有效結束時間!=null){
				if($("#StartDate").val()<json.result.有效結束時間)
					result='  走期不符';
			}
			if(result=='')
				$('#timeCheckResult').text('  走期符合').css('color','black');
			else
				$('#timeCheckResult').text(result).css( 'color','red');
		}
		,'json'	
		);
}
			
//檢察版位素材限制
function checkMaterialOption(mLimitOption){
	if(mLimitOption==null)
		return 0;
	
	var $tr = $('<tr/>').append($('<td>'+HtmlSanitizer.SanitizeHtml(mLimitOption.素材順序)+'</td>'));
	var $td = $('<td/>');
	switch(mLimitOption.素材類型名稱){
		case '文字':
			$td.text('文字 長度:'+((mLimitOption.每則文字素材最大字數==null)?'無限制':mLimitOption.每則文字素材最大字數));
			break;
		case '圖片':
			$td.text('圖片 大小:'+((mLimitOption.每則圖片素材最大寬度==null)?'無限制':mLimitOption.每則圖片素材最大寬度)
								+'X'+((mLimitOption.每則圖片素材最大高度==null)?'無限制':mLimitOption.每則圖片素材最大高度));
			break;
		case '影片':
			var ftname = $('#影片畫質>option[value='+HtmlSanitizer.SanitizeHtml(mLimitOption.影片畫質識別碼)+']').text();
			$td.text('影片 畫質:'+ftname+' 秒數:'+((mLimitOption.每則影片素材最大秒數==null)?'無限制':mLimitOption.每則影片素材最大秒數));
			break;
	}
	$tr.append($td);		
	var result='';
	//檢察素材類型
	if($("input[name='materailRadio']:checked").val()!=mLimitOption.素材類型名稱)
		result+='素材類型不同'
	else{
		//檢察素材規格
		var fit =$("input[name='mCheckOption']:checked").val();
		if(fit==0){
			//檢查是否與限制相同
			switch(mLimitOption.素材類型名稱){
				case '文字':
					if(mLimitOption.每則文字素材最大字數!=null){
						if(parseInt(mLimitOption.每則文字素材最大字數,10)!=$("#文字素材內容").val().length)
						result+='文字字數不符'
					}	
					break;
				case '圖片':
					if(mLimitOption.每則圖片素材最大寬度!=null){
						if(parseInt(mLimitOption.每則圖片素材最大寬度,10)!=parseInt($("#圖片素材寬度").val(),10))
						result+='圖片寬度不符'
					}
					if(mLimitOption.每則圖片素材最大高度!=null){
						if(parseInt(mLimitOption.每則圖片素材最大高度,10) != parseInt($("#圖片素材高度").val(),10))
						result+=' 圖片高度不符'
					}	
					break;
				case '影片':
					if(parseInt(mLimitOption.影片畫質識別碼,10)!=parseInt($('#影片畫質').val(),10)){
						result+='影片畫質不符 ';
					}
					if(mLimitOption.每則影片素材最大秒數!=null){
						if(parseInt(mLimitOption.每則影片素材最大秒數,10) != parseInt($("#影片素材秒數").val(),10))
						result+='影片秒數不符 '
					}
					break;
			}
		}
		else{
			//檢查是否小於限制
			switch(mLimitOption.素材類型名稱){
				case '文字':
					if(mLimitOption.每則文字素材最大字數!=null){
						if(parseInt(mLimitOption.每則文字素材最大字數,10)<$("#文字素材內容").val().length)
						result+='文字字數不符'
					}	
					break;
				case '圖片':
					if(mLimitOption.每則圖片素材最大寬度!=null){
						if(parseInt(mLimitOption.每則圖片素材最大寬度,10)<parseInt($("#圖片素材寬度").val(),10))
						result+='圖片寬度不符'
					}
					if(mLimitOption.每則圖片素材最大高度!=null){
						if(parseInt(mLimitOption.每則圖片素材最大高度,10) < parseInt($("#圖片素材高度").val(),10))
						result+=' 圖片高度不符'
					}	
					break;
				case '影片':
					if(mLimitOption.每則影片素材最大秒數!=null){
						if(parseInt(mLimitOption.每則影片素材最大秒數,10) < parseInt($("#影片素材秒數").val(),10))
						result+='影片秒數不符'
					}
					break;
			}
		}
	}
	if(result==''){
		result = '符合';
		$tr.css({'color':'black'});
	}
	else
		$tr.css({'color':'red'});
	$tr.append($('<td>'+result+'</td>'));
	//顯示結果
	$('#素材設定tbody').append($tr);

}

$("input[name='mCheckOption']").change(function(){
		checkPositionOption();
});

//檢查素材秒數
const onFileChangeFile = (mediainfo) => {
	//非影片素材不檢查
	if(!$("#filmRadio").prop('checked'))
		return

	const file = document.getElementById('fileToUpload').files[0]
	if (file) {
	const getSize = () => file.size
	const readChunk = (chunkSize, offset) =>
		new Promise((resolve, reject) => {
		const reader = new FileReader()
		reader.onload = (event) => {
			if (event.target.error) {
			reject(event.target.error)
			}
			resolve(new Uint8Array(event.target.result))
		}
		reader.readAsArrayBuffer(file.slice(offset, offset + chunkSize))
		})
	mediainfo
		.analyzeData(getSize, readChunk)
		.then((result) => {
			result=JSON.parse(result);
			let sec = Math.round(result.media.track[0].Duration);
			console.log(sec);
			if($("#影片素材秒數").val()!=sec){
				if(confirm("影片素材秒數欄位("+$("#影片素材秒數").val()+")與上傳檔案資訊("+sec+")不合，是否更新。")){
					$("#影片素材秒數").val(sec);
				}
			}
			//output.value = result
		})
		.catch((error) => {
			//output.value = `An error occured:\n${error.stack}`
		})
	}
}
//綁定file input和MediaInfo檢查模組
MediaInfo({ format: 'JSON' }, (mediainfo) => {
	document.getElementById('fileToUpload').addEventListener('change', () => onFileChangeFile(mediainfo))
})
</script>
</body>
</html>