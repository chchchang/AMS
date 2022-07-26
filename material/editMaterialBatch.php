<?php
	include('../tool/auth/auth.php');
?>
<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"/>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css"></link>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<style type="text/css">
#materialTypeSelectoin{
	margin-right:10px;
	height:25px;
	vertical-align:top;
	padding: 0px 10px;
}
</style>
</head>
<body>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="uploadDialog"><div id = 'uploadResult_f'></div><div id = 'uploadResult_s'></div></div>
<div id="selectedDialog"><table class="styledTable" rules="all" cellpadding='5' width = "100%" id ="selectedMTable"></table></div>

已選 <span id='selectedNum'>0</span> 個素材<button id="showSelectedMaterial" class ='darkButton'>查看</button>
<div id = 'tabs'>
	<ul>
		<li><a href="#tabs-1">選擇素材</a></li>
		<li><a href="#tabs-2">設定素材更新資訊</a></li>
	</ul>
	<div id='tabs-1'>
		<?php include('_searchMaterialUI.php'); ?>
		<div id = "datagrid"></div>
		</br>
	</div>
	<div id = 'tabs-2'>
		<div class ="basicBlock" style="width:625px" align="left" valign="center">
			<fieldset style="width:600px">
				<legend>設定素材資料</legend>
				勾選要更改的欄位<button id = 'allAttBtn' class = 'darkButton'>全選</button> <button id = 'noAttBtn' class = 'darkButton'>全不選</button>
				<table width = '100%' class='styledTable2'>
				<tr><th><input type = 'checkbox' id='素材群組CB' name="updateCheckBox"></input></th><th>素材群組:</th><td><select id="素材群組"></select> <button id = 'materialGroupInfo' class = 'darkButton'>詳細資料</button></td></tr>
				<tr><th><input type = 'checkbox' id='素材名稱CB' name="updateCheckBox"></input></th><th><label>素材名稱*:</th><td></label><input id = "素材名稱" type="text" value = ""  class ="nonNull" style="width:400px"></td></tr>
				<tr><th><input type = 'checkbox' id='素材說明CB' name="updateCheckBox"></input></th><th><label>素材說明:</th><td></label><input id = "素材說明" type="text" value = "" style="width:400px"></td></tr>
				<tr><th></th><th>上層產業類型:</th><td><select id="上層產業類型"></select></td></tr>
				<tr><th><input type = 'checkbox' id='產業類型CB' name="updateCheckBox"></input></th><th>產業類型*:</th><td><select id="產業類型" ></select></td></tr>
				<tr><th><input type = 'checkbox' id='素材有效開始期間CB' name="updateCheckBox"></input></th><th>素材有效開始時間:</th><td><input id = "StartDate" type="text" value = "" size="15" ></td></tr>
				<tr><th><input type = 'checkbox' id='素材有效結束期間CB' name="updateCheckBox"></input></th><th>素材有效結束時間:</th><td><input id = "EndDate" type="text" value = "" size="15" ></td></tr>
				<tr><th><input type = 'checkbox' id='素材類型設定CB' name="updateCheckBox"></input></th><th><label>素材類型設定:</label></th><td>
				<p class = "textM"><input id ="textRadio"  type="radio" name="materailRadio" value="文字">文字: 文字素材內容</p><p><blockquote class = "textM"><textarea id = "文字素材內容" rows="4" cols="20" class="textInput" style="width:400px"></textarea></blockquote></p>
				<p class = "picM"><input id="picRadio" type="radio" name="materailRadio" value="圖片">圖片: 圖片素材寬度 <input id = "圖片素材寬度" type="number" value = "" style="width:60px" class="picInput">，圖片素材高度 <input id = "圖片素材高度" type="number" value = "" style="width:60px" class="picInput"></p>
				<p class = "filmM"><input id ="filmRadio" type="radio" name="materailRadio" value="影片">影片: 影片素材秒數 <input id = "影片素材秒數" type="number" value = "" style="width:60px" class="filmInput" ><br>
				</td></tr>
				<tr class="fileM"><th><input type = 'checkbox' id='素材原始檔名CB' name="updateCheckBox"></input></th><th>上傳的素材檔案:</th>
				<td><input id="fPicRadio" type="radio" name="fileRadio" value="圖片" checked>圖片<input type="radio" name="fileRadio" value="影片">影片
				<input type="text" id="素材原始檔名" value = "" style="width:200px" readonly><img id="fileExist" src=""><a id ="fileCheckText" style="font-size:9px"></a>
				<form action="ajaxUploadingFile.php" method="post" enctype="multipart/form-data"  class="fileM" id ="uploadFileForm">
					<input type="hidden" name="max_file_size" value="800000000">
					<input type="file" name="fileToUpload" id="fileToUpload"></form>
				<button id="clearFile">取消素材</button>
				<a id = 'mtypeMessage'>(接受gif/jpeg/png/jpg檔案)</a>
				</td></tr>
				</table>
			</fieldset>
			<div  class ="Center"><button type="button" onclick = "clearVal()">清空</button><button type="button" id ="saveBtn">儲存</button></div>
		</div>
	
	</div>
</div>
<script type="text/javascript">

	var showAminationTime = 500;
//***********************************************素材選擇tabs
	var selectedMaterial = [];
	
	//欄位全選按鈕
	$('#allAttBtn').click(function(){
		$('input[name="updateCheckBox"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//欄位全不選按鈕
	$('#noAttBtn').click(function(){
		$('input[name="updateCheckBox"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	
	$("#tabs").tabs({
		activate: function(event, ui) {
			switch (ui.newTab.index()){
			case 0:
			break;
			case 1:
				//initialPositionSetting();
			break;
		}}
	});
	
	$('#selectedDialog').dialog({autoOpen: false,
			width: 400,
			height: 400,
			title: '選擇的素材'
		});
			
	$('#showSelectedMaterial').click(function(){
		$('#selectedDialog').dialog('open');
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
	
	//清除素材按鈕
	$('#clearFile').click(function(){
		$('#fileToUpload').val('');
		$("#圖片素材寬度,#圖片素材高度").val("");
	});
	
	
	
	var ajaxtodbPath = "ajaxToDB_Material.php";
	var g_numPerPage=10;
	/**向司服器要求廣告主資料數目**/
	var TDG;//存放素材資料表用
	
	getmDataGrid();
	
	function getmDataGrid(){
		bypost={
			method:'DATAGRID素材資訊'
			,searchBy:$('#_searchMUI_shearchText').val()
			,'素材類型':$("#_searchMUI_materialTypeSelectoin").val()
			,pageNo:1,order:'素材識別碼'
			,asc:'DESC'
			,"素材群組識別碼":$("#_searchMUI_materialGroup").val()
			,"開始時間":$("#_searchMUI_startDate").val()
			,"結束時間":$("#_searchMUI_endDate").val()
		};
		($('#_searchMUI_missingFileOnly').prop('checked'))?bypost['method']='DATAGRID素材資訊_MISSING':bypost['method']='DATAGRID素材資訊';
		$.post('ajaxFunction_MaterialInfo.php',bypost,function(json){
				TDG =  new mDataGrid(json);
			}
		,'json'
		);
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
	}	
	var mydg;
	function mDataGrid(json){
		$('#datagrid').empty();
		json.header.push('選擇');
		for(var row in json.data){
			if($.inArray(json.data[row][0][0],selectedMaterial)==-1)
				json.data[row].push(['選擇','button']);
			else{
				json.data[row].push(['','text']);
			}
		}
		
		mydg=new DataGrid('datagrid',json.header,json.data);
		mydg.set_page_info(json.pageNo,json.maxPageNo);
		mydg.set_sortable(json.sortable,true);
		//頁數改變動作
		mydg.pageChange=function(toPageNo) {
			bypost.pageNo=toPageNo;
			update();
		}
		//header點擊
		mydg.headerOnClick = function(headerName,sort){
			bypost.order=headerName;
			switch(sort){
			case "increase":
				bypost.asc='ASC';
				break;
			case "decrease":
				bypost.asc='DESC';
				break;
			case "unsort":
				break;
			}
			update();
		};
		//按鈕點擊
		mydg.buttonCellOnClick=function(y,x,row) {
			if(!mydg.is_collapsed()){
				if(row[x][0]=='選擇') {
					selectedMaterial.push(row[0][0]);
					$('#selectedNum').text(selectedMaterial.length);
					var tr = $(document.createElement('tr')).appendTo($("#selectedMTable"));
					$(document.createElement('td')).text(row[0][0]).appendTo(tr);
					$(document.createElement('td')).text(row[3][0].replace('<a style="color:red">','').replace('</a>','')).appendTo(tr);
					var btd =$(document.createElement('td')).appendTo(tr);
					//刪除按鈕
					$(document.createElement('button')).text('刪除').click(function(event){
						event.preventDefault();
						var rmTd=$(this).parent();
						var rmTr= rmTd.parent();
						var inIndex = rmTr.parent().children().index(rmTr);
						$(this).parent().parent().remove();
						//從ARRAY中移除
						selectedMaterial.splice(inIndex, 1);
						$('#selectedNum').text(selectedMaterial.length);
						update();
					}).appendTo(btd);
					$('#datagrid tr:nth-child('+(y+1)+') td button').remove();
				}
			}
			else{
				
			}
		}
		
		function update(){
			$.post('ajaxFunction_MaterialInfo.php',bypost,function(json) {
					for(var row in json.data){
						if($.inArray(json.data[row][0][0],selectedMaterial)==-1)
							json.data[row].push(['選擇','button']);
						else
							json.data[row].push(['','text']);
					}
					mydg.set_data(json.data);
					setHoverImag();
				},'json');
		}
		
		this.uncollapse = function(){
			mydg.uncollapse();
		}
		
		mydg.updateData= function(){
			update();
		}
		setHoverImag();
	}
	
	//滑鼠移過預覽圖片時，顯示大圖
	function setHoverImag(){
		$('.dgImg').hover(
			function(e) {
				var mX = e.pageX;
				var mY = e.pageY;
				$('body').append('<img id="'+$(this).attr('alt').split(':')[0]+'" src="'+$(this).attr('src')+'"></img>');
				$('#'+$(this).attr('alt').split(':')[0]).css({'position':'absolute', 'top':mY,'left':mX});
			}, function() {
				$('#'+$(this).attr('alt').split(':')[0]).remove();
			}
		);
	}
	
	/**關閉素材詳細資訊視窗**/
	function closeOrderInfo(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
			TDG.uncollapse();
		}
	}
	
	function materialUpdated(){
		closeOrderInfo();
		TDG.updateData();
	}

//*******************************************素材設定tabs
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
		$('#uploadDialog').dialog({autoOpen: false,
			width: 400,
			height: 600,
			title: '修改結果'
			});
});


$(".textInput,.picInput,.filmInput,#fileToUpload,#uploadFileBtn").prop('disabled', true);
//設訂素材類型選擇時，input是否可輸入
$("input[name='materailRadio']").change(function(){
	var val =$("input[name='materailRadio']:checked").val();
	switch(val){
		case "文字":
			$(".picInput,.filmInput,#fileToUpload").prop('disabled', true).val('');
			$(".textInput").removeAttr("disabled");
			$('#mtypeMessage').hide();
			break;
		case "圖片":
			$("#fileToUpload").prop('accept', "image/gif,image/jpeg,image/png,image/jpg").val('');
			$(".textInput,.filmInput").prop('disabled', true).val('');
			$(".picInput").removeAttr("disabled");
			break;
		case "影片":
			$("#fileToUpload").prop('accept', "").val('');
			$(".picInput,.textInput").prop('disabled', true).val('');
			$(".filmInput").removeAttr("disabled");
			break;
		default:
			break;
	}
});

$("#素材原始檔名CB").change(function(){
	if($('#素材原始檔名CB').prop('checked'))	
	$("#fileToUpload,#uploadFileBtn").prop('disabled', false);
	else
	$("#fileToUpload,#uploadFileBtn").prop('disabled', true);
});

$("input[name='fileRadio']").change(function(){
	var val =$("input[name='fileRadio']:checked").val();
	switch(val){
		case "圖片":
			$("#fileToUpload").prop('accept', "image/gif,image/jpeg,image/png,image/jpg").val('');
			$('#mtypeMessage').text('(接受gif/jpeg/png/jpg檔案)').show();
			break;
		case "影片":
			$("#fileToUpload").prop('accept', "*").val('');
			//$('#mtypeMessage').text('(接受ts/mpg檔案)').show();
			break;
		default:
			break;
	}
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
			$('#素材類型設定CB,#picRadio').prop('checked',true);
			$('#素材類型設定CB,input[name="materailRadio"]').prop('disabled',true);
		};
        image.onerror= function() {
            alert('Invalid file type: '+ file.type);
        };
    };

}

//檔案上傳，若為圖片則自動取得寬高
$("#fileToUpload").change(function (e) {
    if(this.disabled) return alert('不支援上傳檔案!');
    var F = this.files;
    if(F && F[0])
		for(var i=0; i<F.length; i++)
			if($("input[name='fileRadio']:checked").val()=='圖片'){
				readImage( F[i] );
			}
});

//是否強制更新圖片寬高
$("#素材原始檔名CB,input[name='fileRadio']").change(function (e) {
	var val =$("input[name='fileRadio']:checked").val();
	if(val=='圖片'&& $("#fileToUpload").val()!=''){
		$('#圖片素材寬度,#圖片素材高度,#素材類型設定CB,input[name="materailRadio"]').prop('disabled',true);
	}
	else{
		$('#圖片素材寬度,#圖片素材高度,#素材類型設定CB,input[name="materailRadio"]').prop('disabled',false);
	}
});

function clearVal(){
	$("input:not(:radio)").val("");
	$(".textInput,.picInput,.filmInput,#fileToUpload,#uploadFileBtn").prop('disabled', true);
	$(":radio").prop('checked',false);
	$(":checkbox").prop('checked',false);
	$("#fPicRadio").prop('checked',true);
};

$( "#saveBtn" ).click(function(event) {
	if(selectedMaterial.length==0){
		alert('沒有選擇任何素材');
		return 0;
	}
	
	var checkNum=0;
	$('input[name="updateCheckBox"]').each(function() {
		if($(this).prop("checked"))
			checkNum++;
	});
	if(checkNum==0){
		alert('沒有選擇任何欄位');
		return 0;
	}
	
	if($("#素材名稱CB").prop('checked')&&$("#素材名稱CB").val()==''){
		alert("請填素材名稱");
		return 0;
	}
	
	if($("#產業類型CB").prop('checked'))
	if($("#產業類型").find('option:selected').text()==""){
		alert("請選擇產業類型");
		return 0;
	}
	
	if($("#素材類型設定CB").prop('checked'))
	if(typeof $("input[name='materailRadio']:checked").val() == 'undefined'){
		alert("請選擇一樣素材種類");
		return 0;
	}
	
	if($("#素材原始檔名CB").prop('checked'))
		uploadFile();
	else
		saveToDb();
});	

function uploadFile(){
	/*var ext = $('#fileToUpload').val().split('.').pop().toLowerCase();
	var type =$("input[name='fileRadio']:checked").val();
	switch(type){
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
				alert('檔案header定義類型錯誤!'+headerTypes[tempIndex]+headerType);
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
	$('#uploadResult_f,#uploadResult_s').empty();
	$('#uploadDialog').dialog('open');

	for(key in selectedMaterial)
	$.post("ajaxToDB_Material.php",{action:"素材資訊表格",素材識別碼:selectedMaterial[key]},
		function(data){
			
			data['action']="修改素材";
			data['影片畫質']=data["影片畫質識別碼"];
			if($('#產業類型CB').prop('checked'))
			data['產業類型識別碼']=$("#產業類型").val();
			if($('#素材名稱CB').prop('checked'))
			data['素材名稱']=$("#素材名稱").val();
			if($('#素材說明CB').prop('checked'))
			data['素材說明']=$("#素材說明").val();
			if($('#素材類型設定CB').prop('checked')){
				if(data['素材類型識別碼']!=material[$("input[name='materailRadio']:checked").val()]){
					$('#uploadResult_f').append('<p>素材'+HtmlSanitizer.SanitizeHtml(data.素材識別碼)+' 修改失敗: 素材類型不符</p>');
					doneCheck();
					return 0;
				}
				if(data['素材類型識別碼']==material['圖片'] && (data['素材原始檔名']!=''||data['素材原始檔名']!=null) && !$('#素材原始檔名CB').prop('checked') && $("#fileToUpload").val()==''){
					$('#uploadResult_f').append('<p>素材'+HtmlSanitizer.SanitizeHtml(data.素材識別碼)+' 修改失敗: 圖片素材已存在，若非新上傳檔案則無法修改寬高</p>');
					doneCheck();
					return 0;
				}
				data['文字素材內容']=$("#文字素材內容").val();
				data['圖片素材寬度']=$("#圖片素材寬度").val();
				data['圖片素材高度']=$("#圖片素材高度").val();
				data['影片素材秒數']=$("#影片素材秒數").val();
			}
			if($('#素材群組CB').prop('checked'))
			data['素材群組識別碼']=$("#素材群組").val();
			if($('#素材有效開始期間CB').prop('checked'))
			data['素材有效開始時間']=$("#StartDate").val();
			if($('#素材有效結束期間CB').prop('checked'))
			data['素材有效結束時間']=$("#EndDate").val();
			
			if($('#素材原始檔名CB').prop('checked')){
				if($("#fileToUpload").val()!=''){
					data['新素材檔案上傳']='copy';
				}
				var newname = ($("#fileToUpload").val()=='')?$("#素材原始檔名").val():$("#fileToUpload").val().split('\\').pop();
				var ftype =$("input[name='fileRadio']:checked").val();
				if(data['素材類型識別碼']!=material[ftype]){
					$('#uploadResult_f').append('<p>素材編號'+HtmlSanitizer.SanitizeHtml(data.素材識別碼)+' 修改失敗: 檔案類型不符</p>');
					doneCheck();
					return 0;
				}
				
				var type1 = data['素材原始檔名'].split('.').pop();
				var type2 = newname.split('.').pop();
				if(ftype == '圖片')
					if(type1!=''&&type1!=type2){
						$('#uploadResult_f').append('<p>素材編號'+HtmlSanitizer.SanitizeHtml(data.素材識別碼)+' 修改失敗: 圖片副檔名不同</p>');
						doneCheck();
						return 0;
					}
					
				data['素材原始檔名']=newname;
			}
			
			data['action']="修改素材";
			$.post("ajaxToDB_Material.php",data)
			.done(function(data){
				var result=$.parseJSON(data);
				if(result["dbError"]!=undefined){
					alert(result["dbError"]);
					return 0;
				}
				if(result["success"]){
					if($('#素材原始檔名CB').prop('checked')&&$("#fileToUpload").val()!=''){
						$.post('materialInfo.php',{method:'檢查素材託播單','素材識別碼':result['id']},
							function(json){
								if(json['success']){
									var message = '';
									for(var i in json['data']){
										message+=json['data'][i]['託播單識別碼']+':'+json['data'][i]['託播單名稱']+'\n';
									}
									if(message!=''){
										if($("input[name='fileRadio']:checked").val()=='影片'){
											message ='。素材檔案更動，請重新派送素材，並重新送出以下託播單:\n'+message;
										}
										else
											message ='。素材檔案更動，請重新派送素材';
										$('#uploadResult_s').append('<p>素材編號'+HtmlSanitizer.SanitizeHtml(json['id'])+' 修改成功'+message+'</p>');
									}
									else
										$('#uploadResult_s').append('<p>素材編號'+HtmlSanitizer.SanitizeHtml(json['id'])+' 修改成功。素材檔案更動，請重新派送素材</p>');
									deleteMaterial(json['id']);
								}
							},'json'
						);
					}
					else{
						$('#uploadResult_s').append('<p>素材編號'+HtmlSanitizer.SanitizeHtml(result['id'])+' 修改成功。</p>');
						deleteMaterial(result['id']);
					}
				}
				else{
					$('#uploadResult_f').append('<p>素材編號'+HtmlSanitizer.SanitizeHtml(result['id'])+' 修改失敗。'+HtmlSanitizer.SanitizeHtml(result['message'])+'</p>');
				}
				doneCheck()
			});
		}
		,'json'
	);
	
	function doneCheck(){
		if($('#selectedNum').text()=='0'){
			mydg.updateData();
		}
	}
}

//從selectedMaterial中刪除素材
function deleteMaterial(id){
	var index = $.inArray(id,selectedMaterial);
	if(index!=-1){
		selectedMaterial.splice(index, 1);
		$('#selectedNum').text(selectedMaterial.length);
	}
}
	
</script>
</body>
</html>