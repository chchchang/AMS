<?php
	include('../tool/auth/authAJAX.php');
	//ajax
	if(isset($_POST['method'])){
		if($_POST['method']=='取得參數型態'){
			$sql ='SELECT 參數型態識別碼,參數型態名稱
				FROM  參數型態
			';
			$result=$my->getResultArray($sql);
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
	}
	
	$action = 'new';
	$order = 0;
	if(isset($_GET['action']))
		$action = htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8');
	
	$data ='object';
	if(isset($_GET['data']))
		$data = htmlspecialchars($_GET['data'], ENT_QUOTES, 'UTF-8');
	
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script> 
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' /> 
</head>
<body>
<input type="number" value = 0 id='順序' hidden>
<table class = 'styledTable2'>
<tbody>
<tr><th>是否為版位專用</th><td><input type="radio" value = 1 name='是否為版位專用' checked>是 <input type="radio" value = 0 name='是否為版位專用'>否</td></tr>
<tr><th>版位其他參數名稱</th><td><input type="text" value = '' id='版位其他參數名稱' class ="nonNull"></td></tr>
<tr><th>版位其他參數型態</th><td><select id="版位其他參數型態識別碼"></select></td></tr>
<tr><th>版位其他參數預設值</th><td><input type="radio" name="defaultValue" value="input" checked><input type="text" value = '' id='版位其他參數預設值' /> <input type="radio" name="defaultValue" value="null">NULL</td></tr>
<tr><th>版位其他參數顯示名稱</th><td><input type="text" value = '' id='版位其他參數顯示名稱' class ="nonNull"></td></tr>
<tr><th>是否必填</th><td><input type="radio" value = 1 name='是否必填' checked disabled>是 <input type="radio" value = 0 name='是否必填' disabled>否</td></tr>

</tbody>
</table>
<button id ='submitBtn' style='float:right'>確定</button>
<script type="text/javascript">

$( "#版位其他參數型態識別碼" ).combobox();
function setConfigSelection(selectedId){
	$("#版位其他參數型態識別碼").empty();
	$.post('?',{method: '取得參數型態'}
	,function(json){
		var selectedIndex = 0;
		for(var i in json){
			var opt = $(document.createElement("option"));
			opt.text(json[i]["參數型態名稱"])//紀錄版位類型名稱
			.val(json[i]["參數型態識別碼"])//紀錄版位類型識別碼
			.appendTo($("#版位其他參數型態識別碼"));
			if(selectedId == json[i]["參數型態識別碼"])
				selectedIndex = i;
		}
		$( "#版位其他參數型態識別碼" ).combobox('setText',json[selectedIndex]["參數型態名稱"]);
		$( "#版位其他參數型態識別碼" ).val(json[selectedIndex]["參數型態識別碼"]);
	}
	,'json'
)
}
var action = '<?=$action?>';


if(action =='new'){
	setConfigSelection(0);
}

if(action == 'info'){
	var jobject = parent.<?=$data?>;
	showVal(jobject);
	$('input').prop('disabled', true);
	$( "#版位其他參數型態識別碼" ).combobox('disable');
}

if(action == 'edit'){
	var jobject = parent.<?=$data?>;
	showVal(jobject);
}

if(action == 'limitedEdit'){
	var jobject = parent.<?=$data?>;
	showVal(jobject);
	$('input').prop('disabled', true);
	$( "#版位其他參數型態識別碼" ).combobox('disable');
	$('input[name="defaultValue"],#版位其他參數預設值,#版位其他參數顯示名稱').prop('disabled', false);
}

//設定參數預設值動作
$("input[name='defaultValue']").change(function(){
		if($("input[name='defaultValue'][value='null']").prop('checked')){
			$('#版位其他參數預設值').prop('disabled', true).val('');
		}
		else
			$('#版位其他參數預設值').prop('disabled', false);
	}
)

//若為版位專用
$("input[name='是否為版位專用']").change(function(){
		if($("input[name='是否為版位專用'][value=1]").prop('checked')){
			$('input[name="是否必填"][value=1]').prop('checked', true);
			$('input[name="是否必填"]').prop('disabled', true);
		}
		else{
			$('input[name="是否必填"]').prop('disabled', false);
		}
	}
)

function showVal(jobject){
	$('#順序').val(jobject.版位其他參數順序);
	$("input[name='是否為版位專用'][value='"+jobject.是否版位專用+"']").prop('checked',true);
	$("input[name='版位其他參數是否必填'][value='"+jobject.版位其他參數是否必填+"']").prop('checked',true);
	$('#版位其他參數名稱').val(jobject.版位其他參數名稱);
	$('#版位其他參數顯示名稱').val(jobject.版位其他參數顯示名稱);
	if(jobject.版位其他參數預設值==null){
		$("input[name='defaultValue'][value='null']").prop('checked',true);
		$('#版位其他參數預設值').val('');
	}
	else
	$('#版位其他參數預設值').val(jobject.版位其他參數預設值);
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
	setConfigSelection(jobject.版位其他參數型態識別碼);
}

$('#submitBtn').click(function(){
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
	
	var jobject={
		版位其他參數順序:$('#順序').val(),
		版位其他參數名稱:$('#版位其他參數名稱').val(),
		版位其他參數型態識別碼:$('#版位其他參數型態識別碼').val(),
		版位其他參數是否必填:$("input[name='是否必填']:checked").val(),
		版位其他參數預設值:($("input[name='defaultValue'][value='null']").prop('checked'))?null:$('#版位其他參數預設值').val(),
		版位其他參數顯示名稱:$('#版位其他參數顯示名稱').val(),
		是否版位專用:$("input[name='是否為版位專用']:checked").val()
	};
	if(action == 'new')
		parent.otherConfigAdd(jobject);
	else
		parent.otherConfigEdit(jobject);
});
</script>
</body>
</html>