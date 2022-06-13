<?php
	include('../tool/auth/authAJAX.php');
	//AJAX
	if(isset($_POST['method'])){
		if($_POST['method']=='版位資料'){
			$sql = '
			SELECT 上層版位識別碼,版位識別碼,版位名稱,版位說明,版位有效起始時間,版位有效結束時間,`託播單介接API URL`,`排程表介接API URL`,`使用記錄介接API URL`
			,建議售價,預約到期提前日
			FROM 版位
			WHERE 版位識別碼 = ?
			';
			if(!$result=$my->getResultArray($sql,'i',$_POST['id']))
				exit;
			
			//取的素材資料
			$sql = '
			SELECT 版位識別碼,素材順序,版位素材類型.素材類型識別碼,素材類型名稱 AS 素材類型,託播單素材是否必填,影片畫質識別碼,每小時最大素材筆數,每小時最大影片素材合計秒數,每則文字素材最大字數
			,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數,顯示名稱
			FROM 版位素材類型,素材類型
			WHERE 版位識別碼 = ? AND 素材類型.素材類型識別碼 = 版位素材類型.素材類型識別碼
			ORDER BY 素材順序
			';
			if(!$result['materialArray']=$my->getResultArray($sql,'i',$_POST['id'])) $result['materialArray']=array();
			
			//取的參數資料
			$sql = '
			SELECT 版位其他參數順序,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,版位其他參數預設值,版位其他參數顯示名稱,是否版位專用
			FROM 版位其他參數
			WHERE 版位識別碼 = ?
			ORDER BY 版位其他參數順序
			';
			if(!$result['configArray']=$my->getResultArray($sql,'i',$_POST['id'])) $result['configArray']=array();
			
			if(isset($result[0]['上層版位識別碼'])){
				//取的素材資料
				$sql = '
				SELECT 版位識別碼,素材順序,版位素材類型.素材類型識別碼,素材類型名稱 AS 素材類型,託播單素材是否必填,影片畫質識別碼,每小時最大素材筆數,每小時最大影片素材合計秒數,每則文字素材最大字數
				,每則圖片素材最大寬度,每則圖片素材最大高度,每則影片素材最大秒數
				FROM 版位素材類型,素材類型
				WHERE 版位識別碼 = ? AND 素材類型.素材類型識別碼 = 版位素材類型.素材類型識別碼
				ORDER BY 素材順序
				';
				if(!$result['materialArray_parent']=$my->getResultArray($sql,'i',$result[0]['上層版位識別碼'])) $result['materialArray_parent']=array();
				
				//取的參數資料
				$sql = '
				SELECT 版位其他參數順序,版位其他參數名稱,版位其他參數型態識別碼,版位其他參數是否必填,版位其他參數預設值,版位其他參數顯示名稱,是否版位專用
				FROM 版位其他參數
				WHERE 版位識別碼 = ?
				ORDER BY 版位其他參數順序
				';
				if(!$result['configArray_parent']=$my->getResultArray($sql,'i',$result[0]['上層版位識別碼'])) $result['configArray_parent']=array();
			}
			
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		exit;
	}
	
	
	$action = 'new';
	$id = 0;
	$positionPage = 'false';
	$limitedEdit = 'false';
	if(isset($_GET['action']))
		$action = htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8');
	if(isset($_GET['id']))
		$id = htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8');
	if(isset($_GET['positionPage'])){
		$positionPage = 'true';
		$limitedEdit ='true';
	}
	
	$sql = '
			SELECT COUNT(版位.版位名稱) AS count
			FROM  版位,版位 版位類型
			WHERE 版位類型.版位識別碼 = ? AND 版位.上層版位識別碼 = 版位類型.版位識別碼
			';
	$res=$my->getResultArray($sql,'i',$id);
	if($res[0]['count']>0 && $positionPage=='false' && $action =='edit'){
		$limitedEdit = 'true';
		echo '此版位類型已建立版位，素材與參數的修改將被限制。';
	}
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
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

<script>
  $(function() {
    $( "#StartDate,#EndDate" ).datepicker({dateFormat: 'yy-mm-dd',
								changeMonth: true,
								changeYear: true,
								monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
								monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]});
  });
</script>

</head>
<body>
<div align="center" valign="center">
	<div class ="basicBlock" style="width:625px" align="left" valign="center">
		<table class="styledTable" rules="all" cellpadding='5'>
			<tr><th width = "300px"><a id ='pNameText'>版位類型名稱*</a></th><td width = "300px"><input id = "pName" type="text" value = "" size="38" class ="nonNull"></input></td></tr>
			<tr><th><a id ='pInfoText'>版位類型說明</a></th><td><input id = "pInfo" type="text" value = "" size="38"></input></td></tr>
		</table>
	</div>
	<div class ="basicBlock" style="width:625px" align="left" valign="center">
		<fieldset style="width:600px">
			<legend>廣告投放設定</legend>
			<p><label>有效期間: </label><input id = "StartDate" type="text" value = "">~<input id = "EndDate" type="text" value = ""></p>
			<p><label>建議售價: </label><input id = "建議售價" type="text" value = "" style="width:60px"></p>
			<p>預約到期提前工作天數: <input id = "預約到期提前日" type="number" value = "" style="width:60px"></p>
			<p><label>介接資訊:</label>
			<p>託播單介接API位址: <input id = "orderApi" type="text" value = "" ></p>
			<p>排程表介接API位址: <input id = "schApi" type="text" value = "" ></p>
			<p>使用記錄介接API位址: <input id = "logApi" type="text" value = "" ></p>
			<p>版位示意圖: <img id = "infoPic" src="" onerror="this.style.display='none'"><button type="button" class="darkButton2" onclick = "openInfoPicDia()" id="updateInfoPicBtn">更新圖片</button></p>
			<p>設定素材 <button type="button" class="darkButton" onclick = "addMaterial()">新增素材</button></p>
			<ul id="sortableMaterial"></ul>
			<p><label>其他參數:</label><button type="button" class="darkButton" onclick = "addConfig()">新增參數</button></p>
			<ul id="sortableOther"></ul>
		</fieldset>
		<div  class ="Center"><button type="button" onclick = "clearVal()">清空/還原</button><button type="button" id ="saveBtn">確定</button></p></div>
	</div>
	<div id='dialogDiv'><iframe id = 'dialogIframe' width='100%' height='100%'></iframe></div>
</div>
<div id = "uploadInfoPicDialog">
<table>
	<tr><th>上傳的素材檔案:</th><td><form action="ajaxUploadingFile.php" method="post" enctype="multipart/form-data" id="uploadFileForm">
		<input type="hidden" name="MAX_FILE_SIZE" value="800000000">
		<input type="file" name="fileToUpload" id="fileToUpload" accept="image/gif,image/jpeg,image/png,image/jpg"></form><button type="button" class="darkButton2" onclick = "uploadInfoPic()">上傳</button></form></td></tr>
</table>
</div>
<script type="text/javascript">
var action = '<?=$action?>';
var id = <?=$id?>;
var positionPage=<?=$positionPage?>;
var limitedEdit = <?=$limitedEdit?>;
var removeIntent = false;
$( "#dialogDiv,#uploadInfoPicDialog" ).dialog(
	{autoOpen: false,
	width: 600,
	height: 400,
	modal: true,
});

//版位類型頁面設定
if(!positionPage){
	$( "#sortableMaterial" ).sortable({
			stop: function(e, ui) {
				$.map($(this).find('li'), function(el) {
					materialArray[$(el).attr('index')].素材順序 = $(el).index()+1;
				});
			},
			over: function () {
				removeIntent = false;
			},
			out: function () {
				removeIntent = true;
			},
			beforeStop: function (event, ui) {
				if(removeIntent == true){
					materialArray.splice($(ui.item).index(),1);
					ui.item.remove();
				}
			}
	});

	$( "#sortableOther" ).sortable({
			stop: function(e, ui) {
				$.map($(this).find('li'), function(el) {
					configArray[$(el).attr('index')].版位其他參數順序 = $(el).index()+1;
				});
			},
			over: function () {
				removeIntent = false;
			},
			out: function () {
				removeIntent = true;
			},
			beforeStop: function (event, ui) {
				if(removeIntent == true){
					configArray.splice($(ui.item).index(),1);
					ui.item.remove();
				}
			}
	});
	$( "#sortableMaterial,#sortableOther" ).disableSelection();
	if(action=='edit'||action=='info'){
		getDBVal();
		$("#updateInfoPicBtn").show();
	}

	if(action=='info'){
		$('button').hide();
		$('.ui-button').show();
		$('input').attr('disabled',true);
		 $( "#sortableMaterial,#sortableOther" ).sortable('disable');
	}
	
	if(limitedEdit){
		$('.darkButton').hide();
		$( "#sortableMaterial,#sortableOther" ).sortable('disable');
	}
}

//版位頁面設定
else{
	$('#pNameText').text('版位名稱*');
	$('#pInfoText').text('版位說明');
	getDBVal();
	$('.darkButton').hide();
	if(action=='info'){
		$('button').hide();
		$('.ui-button').show();
		$('input').attr('disabled',true);
	}
}


//從資料庫取得資料
function getDBVal(){
	$.post('?',{method:'版位資料',id:id},
		function(json){
			if(positionPage && action == 'new'){
				json[0].版位名稱 ='';
				json[0].版位說明=''
				json.materialArray_parent=json.materialArray;
				json.configArray_parent=json.configArray;
				json.materialArray=[];
				json.configArray=[];
			}
			showVal(json);
		}		
		,'json'
	);
}

//顯示資料
function showVal(json){
	$("#pName").val(json[0].版位名稱);
	$("#pInfo").val(json[0].版位說明);
	$('#StartDate').val(json[0].版位有效起始時間);
	$('#EndDate').val(json[0].版位有效結束時間);
	$("#orderApi").val(json[0].託播單介接API);
	$("#schApi").val(json[0].排程表介接API);
	$("#logApi").val(json[0].使用記錄介接API);
	$("#建議售價").val(json[0].建議售價);
	$('#預約到期提前日').val(json[0].預約到期提前日);
	if(typeof(json.materialArray_parent)!='undefined'){
		for(var i in json.materialArray_parent)
			materialTypeAdd(json.materialArray_parent[i],true);
		for(var i in json.configArray_parent)
			otherConfigAdd(json.configArray_parent[i],true);
		for(var i in json.materialArray){
			materialTypeEdit(json.materialArray[i]);
			var $li = $('.materialType').eq(json.materialArray[i].素材順序-1);
			$li.children('a').removeClass('ui-state-disabled');
			$li.find('input:checkbox').prop('checked',true);
		}
		for(var i in json.configArray){
			otherConfigEdit(json.configArray[i]);
			var $li = $('.otherConfig').eq(json.configArray[i].版位其他參數順序-1);
			$li.children('a').removeClass('ui-state-disabled');
			$li.find('input:checkbox').prop('checked',true);
		}
	}
	else{
		for(var i in json.materialArray)
			materialTypeAdd(json.materialArray[i],false);
		
		for(var i in json.configArray)
			otherConfigAdd(json.configArray[i],false);
	}
	if(!positionPage){
		$("#sortableMaterial").sortable('refresh');
		$("#sortableOther").sortable('refresh');
	}
	if(action=='info'){
		$('input').prop('disabled',true);
	}
	$.post("ajaxPositionInfoPic.php",{"action":"getInfoPic","版位識別碼":id}
		,function(data){
			if(data["success"]){
				$("#infoPic").attr("src",data["src"])
			}
		}
		,"json"
	);
}

function clearVal(){
	$("input:not(:radio)").val("");
	$(".textInput,.picInput,.filmInput").prop('disabled', true);
	$( "#sortableMaterial,#sortableOther" ).empty();
	materialArray = [];
	configArray = [];
	if(action != 'new' || positionPage){
		getDBVal();
	}
};
  

$( "#saveBtn" ).click(function(event) {
	save();
});

function save(){
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
	
	if(materialArray.length<1){
		alert("請至少新增一樣素材類型");
		return 0;
	}
	
	for(var i in configArray){
		/*if(configArray[i].是否版位專用==1 && configArray[i].版位其他參數是否必填==1)
			if(configArray[i].版位其他參數預設值 == null){
				alert('版位參數['+configArray[i].版位其他參數名稱+']不可為空');
				return 0 ;
			}*/
	}
	
	if($("#StartDate").val()!="" && $("#EndDate").val()!="")
		if($("#StartDate").val()>$("#EndDate").val()){
			alert("開始時間必須不大於結束時間");
			return 0;
		}
		
	var StartDate = ($("#StartDate").val()=="")?"":$("#StartDate").val()+" 00:00:00";
	var EndDate = ($("#EndDate").val()=="")?"":$("#EndDate").val()+" 23:59:59";
	var bypost={
		action:"newPositionType",
		"版位名稱":$("#pName").val(),
		"版位說明":$("#pInfo").val(),
		"版位有效起始時間":StartDate,
		"版位有效結束時間":EndDate,
		"託播單介接API":$("#orderApi").val(),
		"排程表介接API":$("#schApi").val(),
		"使用記錄介接API":$("#logApi").val(),
		"建議售價":$("#建議售價").val(),
		'預約到期提前日':$('#預約到期提前日').val(),
		'版位素材類型':materialArray,
		'版位其他參數':configArray
	};
	
	//版位新增，須記錄上層版位識別碼,並調整素材與參數
	if(positionPage){
		bypost.上層版位識別碼 = id;
		bypost.版位素材類型 =[];
		$('#sortableMaterial>li').each(
			function(){
				if($(this).find('input:checkbox').prop('checked'))
					bypost.版位素材類型.push(materialArray[$(this).index()]);
			}
		);
		bypost.版位其他參數 =[];
		$('#sortableOther>li').each(
			function(){
				if($(this).find('input:checkbox').prop('checked'))
					bypost.版位其他參數.push(configArray[$(this).index()]);
			}
		);
	}
	
	//修改，因無法修改版位類型(上層版位)，不在乎上層版位識別碼
	if(action == 'edit'){
		bypost.action = 'editPositionType';
		bypost.版位識別碼 = id;
	}
	$.post("ajaxToDB_Position.php",bypost)
	.done(function(data){
		var result=$.parseJSON(data);
		if(result["dbError"]!=undefined){
			alert(result["dbError"]);
			return 0;
		}
		if(result["success"]){
			alert(result.message);
			if(action == 'edit' || positionPage){
				parent.positionUpdated();
			}
			else
			clearVal();
		}
	});
};

//新增素材
function addMaterial(){
	$('#dialogIframe').attr('src','materialType.php');
	$('#dialogDiv').dialog('open');
}

//儲存姆前排訂的素材順序用
var materialArray =[];
var materialArray_parent =[];
//由materialType呼叫,素材被儲存
function materialTypeAdd(jobject,disable){
	jobject.素材順序=materialArray.length+1;
	var $li = $("<li class='ui-state-default materialType'/>");
	$c=$('<a/>')
	if(disable){
		var $cbox = $('<input type = "checkbox">');
		$cbox.change(function(){
			if(this.checked) {
				$(this).siblings('a').removeClass('ui-state-disabled');
			}
			else{
				$(this).siblings('a').addClass('ui-state-disabled');
				materialTypeEdit(materialArray_parent[$(this).parent().index()]);
			}
		});
		$li.append($cbox);
		$c.addClass('ui-state-disabled');
	};


	$c.append(HtmlSanitizer.SanitizeHtml(jobject.顯示名稱+' '));
	if(jobject.是否必填==1){
		$c.append('必填 ');
	}
	if(jobject.素材類型識別碼==1)
		jobject.素材類型 ='文字';
	else if(jobject.素材類型識別碼==2)
		jobject.素材類型 ='圖片';
	else if(jobject.素材類型識別碼==3)
		jobject.素材類型 ='影片';
	$c.append(jobject.素材類型+' 每小時上限:'+((jobject.每小時最大素材筆數=='')?'無':HtmlSanitizer.SanitizeHtml(jobject.每小時最大素材筆數)));
	switch(jobject.素材類型){
		case '文字':
			$c.append(' 字數上限:'+((jobject.每則文字素材最大字數=='')?'無':HtmlSanitizer.SanitizeHtml(jobject.每則文字素材最大字數)));
			break;
		case '圖片':
			$c.append(' 寬度上限:'+((jobject.每則圖片素材最大寬度=='')?'無':HtmlSanitizer.SanitizeHtml(jobject.每則圖片素材最大寬度))+' '
			+' 高度上限:'+((jobject.每則圖片素材最大高度=='')?'無':HtmlSanitizer.SanitizeHtml(jobject.每則圖片素材最大高度)));
			break;
		case '影片':
		$c.append(' 合計秒數上限:'+((jobject.每小時最大影片素材合計秒數=='')?'無':HtmlSanitizer.SanitizeHtml(jobject.每小時最大影片素材合計秒數))+' '
		+' 單一秒數上限:'+((jobject.每則影片素材最大秒數=='')?'無':HtmlSanitizer.SanitizeHtml(jobject.每則影片素材最大秒數)));
			break;
	}

	$c.attr('index',materialArray.length);
	$li.append($c);
    $("#sortableMaterial").append($li);
	materialArray.push(jobject);
	materialArray_parent.push(jobject);
	//此欄位被點擊 編輯素材
	$c.click(function(){
		var order = $(this).parent().index()+1;
		for(var i =0; i <materialArray.length;i++){
			if(materialArray[i].素材順序 == order){
				var data = 'materialArray['+i+']';
				if($(this).siblings('a').hasClass('ui-state-disabled')){
					data = 'materialArray_parent['+i+']';
				}
				
				if(action=='info')
					$('#dialogIframe').attr('src','materialType.php?action=info&data='+data);
				else{
					if(limitedEdit){
						if($(this).siblings('a').hasClass('ui-state-disabled'))
						$('#dialogIframe').attr('src','materialType.php?action=info&data='+data);
						else
						$('#dialogIframe').attr('src','materialType.php?action=limitedEdit&data='+data);
					}
					else
						$('#dialogIframe').attr('src','materialType.php?action=edit&data='+data);
				}
				$('#dialogDiv').dialog('open');
				break;
			}
		}
		
	});
	$('#dialogDiv').dialog('close');
}

//由materialType呼叫,素材被儲存
function materialTypeEdit(jobject){
	$li = $('.materialType').eq(jobject.素材順序-1);
	$li = $li.find('a');
	$li.text('');
	$li.append(jobject.顯示名稱+' ');
	if(jobject.是否必填==1){
		$li.append('必填 ');
	}
	$li.append(jobject.素材類型+' 每小時上限:'+((jobject.每小時最大素材筆數=='')?'無':jobject.每小時最大素材筆數));
	switch(jobject.素材類型){
		case '文字':
			$li.append(' 字數上限:'+((jobject.每則文字素材最大字數=='')?'無':jobject.每則文字素材最大字數));
			break;
		case '圖片':
			$li.append(' 寬度上限:'+((jobject.每則圖片素材最大寬度=='')?'無':jobject.每則圖片素材最大寬度)+' '
			+' 高度上限:'+((jobject.每則圖片素材最大高度=='')?'無':jobject.每則圖片素材最大高度));
			break;
		case '影片':
		$li.append(' 合計秒數上限:'+((jobject.每小時最大影片素材合計秒數=='')?'無':jobject.每小時最大影片素材合計秒數)+' '
		+' 單一秒數上限:'+((jobject.每則影片素材最大秒數=='')?'無':jobject.每則影片素材最大秒數));
			break;
	}
	materialArray.splice(jobject.素材順序-1,1,jobject);
	$('#dialogDiv').dialog('close');
}


//新增其他參數
function addConfig(){
	$('#dialogIframe').attr('src','otherConfig.php');
	$('#dialogDiv').dialog('open');
}

//儲存目前排訂的參數用
var configArray =[];
var configArray_parent =[];
//由materialType呼叫,素材被儲存
function otherConfigAdd(jobject,disable){
	jobject.版位其他參數順序=configArray.length+1;
	var $li = $("<li class='ui-state-default otherConfig'/>");
	
	$li.attr('index',configArray.length);
	$c=$('<a/>');
	if(disable){
		var $cbox = $('<input type = "checkbox">');
		$cbox.change(function(){
			if(this.checked) {
				$(this).siblings('a').removeClass('ui-state-disabled');
			}
			else{
				$(this).siblings('a').addClass('ui-state-disabled');
				otherConfigEdit(configArray_parent[$(this).parent().index()]);
			}
		});
		$li.append($cbox);
		$c.addClass('ui-state-disabled');
	};
	
	if(jobject.是否版位專用==1){
		$c.append('版位專用 ');
	}else{
		$c.append('託播單用 ');
	}
	$c.append(jobject.版位其他參數名稱+'('+jobject.版位其他參數顯示名稱+') 預設:'+jobject.版位其他參數預設值);
	$("#sortableOther").append($li.append($c));
	configArray.push(jobject);
	configArray_parent.push(jobject);
	//此欄位被點擊 編輯素材
	$c.click(function(){
		var order = $(this).parent().index()+1;
		for(var i =0; i <configArray.length;i++){
			if(configArray[i].版位其他參數順序 == order){
				var data = 'configArray['+i+']';
				if($(this).siblings('a').hasClass('ui-state-disabled')){
					data = 'configArray_parent['+i+']';
				}
				
				if(action=='info')
					$('#dialogIframe').attr('src','otherConfig.php?action=info&data='+data);
				else{
					if(limitedEdit){
						if($(this).siblings('a').hasClass('ui-state-disabled'))
						$('#dialogIframe').attr('src','otherConfig.php?action=info&data='+data);
						else
						$('#dialogIframe').attr('src','otherConfig.php?action=limitedEdit&data='+data);
					}else
						$('#dialogIframe').attr('src','otherConfig.php?action=edit&data='+data);
				}
				$('#dialogDiv').dialog('open');
				break;
			}
		}
	});
	$('#dialogDiv').dialog('close');
}

//由materialType呼叫,素材被儲存
function otherConfigEdit(jobject){
	$li = $('.otherConfig').eq(jobject.版位其他參數順序-1);
	$li = $li.find('a');
	$li.text('');
	if(jobject.是否版位專用==1){
		$li.append('版位專用 ');
	}
	else{
		$li.append('託播單用 ');
	}
	$li.append(jobject.版位其他參數名稱+'('+jobject.版位其他參數顯示名稱+') 預設:'+jobject.版位其他參數預設值);
	configArray.splice(jobject.版位其他參數順序-1,1,jobject);
	$('#dialogDiv').dialog('close');
}

function openInfoPicDia(){
	$("#uploadInfoPicDialog").dialog("open");
}

function uploadInfoPic(){
	var options = { 
		// target:        '#output1',   // target element(s) to be updated with server response 
        //beforeSubmit:  showRequest,  // pre-submit callback 
        success:	upLoadResponse, // post-submit callback 
		dataType:	'json',
		data: {
			"版位識別碼":id
        }, 
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
			
			if(response['success']){
				//saveToDb();
				$( "#uploadInfoPicDialog" ).dialog('close');
			}
			else{
				alert(response['message']);
			}
		}
	}
}

</script>
</body>
</html>