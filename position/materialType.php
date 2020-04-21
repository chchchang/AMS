<?php
	include('../tool/auth/authAJAX.php');
	//ajax
	if(isset($_POST['method'])){
		if($_POST['method']=='取得影片畫質'){
			$sql ='SELECT 影片畫質識別碼,影片畫質名稱
				FROM  影片畫質
			';
			$result=$my->getResultArray($sql);
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
	}
	
	$action = 'new';
	$order = 0;
	if(isset($_GET['action']))
		$action = htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8');
	
	$data ='jobject';
	if(isset($_GET['data']))
		$data = htmlspecialchars($_GET['data'], ENT_QUOTES, 'UTF-8');
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
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' /> 
</head>
<body>	
<input type="number" value = 0 id='順序' hidden>
<table class = 'styledTable2'>
<tbody>
<tr><th>顯示名稱</th><td><input id='顯示名稱' value = "" type="text"></td></tr>
<tr><th>託播單素材是否必填</th><td><input type="radio" value = 1 name='必填' checked>是 <input type="radio" value = 0 name='必填'>否</td></tr>
<tr><th>每小時最大素材筆數</th><td><input id = "每小時最大素材筆數" type="number" value = "" style="width:60px"></td></tr>
<tr id = "textM"><th><input  type="radio" name="myRadio" value="文字">文字:</th> 
<td>每則文字素材最大字數<input id = "每則文字素材最大字數" type="number" value = "" style="width:60px" class="textInput"></td></tr>
<tr id = "picM"><th><input  type="radio" name="myRadio" value="圖片">圖片:</th> 
<td>每則圖片素材最大寬度 <input id = "每則圖片素材最大寬度" type="number" value = "" style="width:60px" class="picInput">，每則圖片素材最大高度 <input id = "每則圖片素材最大高度" type="number" value = "" style="width:60px" class="picInput"></td></tr>
<tr id = "filmM"><th><input type="radio" name="myRadio" value="影片">影片:</th>
<td><select id="影片畫質識別碼" class="filmInput"></select> 每小時最大合計秒數<input id = "每小時最大影片素材合計秒數" type="number" value = "" style="width:60px" class="filmInput">
，每則影片素材最大秒數<input id = "每則影片素材最大秒數" type="number" value = "" style="width:60px" class="filmInput"></td></tr>
</tbody>
</table>
<button id ='submitBtn' style='float:right'>確定</button>
<script type="text/javascript">
var material={文字:1,圖片:2,影片:3};
var action = '<?=$action?>';
$(".textInput,.picInput,.filmInput").prop('disabled', true);
$("#影片畫質識別碼").combobox();
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


var jobject = parent.<?=$data?>;
if(action == 'new'){
	setFilmType(0);
}

if(action == 'info'){
	showVal(jobject);
	$('input').prop('disabled', true);
	$('#submitBtn').hide();
	$("#影片畫質識別碼").combobox('disable');
}

if(action == 'edit'){
	showVal(jobject);
}

if(action == 'limitedEdit'){
	showVal(jobject);
	$('input[type="radio"]').prop('disabled', true);
	$("#影片畫質識別碼").combobox('disable');
}

function setFilmType(selectedId){
	$("#影片畫質識別碼").empty();
	$.post('?',{method: '取得影片畫質'}
	,function(json){
		var selectedIndex = 0;
		for(var i in json){
			var opt = $(document.createElement("option"));
			opt.text(json[i]["影片畫質名稱"])//紀錄版位類型名稱
			.val(json[i]["影片畫質識別碼"])//紀錄版位類型識別碼
			.appendTo($("#影片畫質識別碼"));
			if(json[i]["影片畫質識別碼"]==selectedId)
				selectedIndex = i;
		}
		$("#影片畫質識別碼").combobox('setText',json[selectedIndex]["影片畫質名稱"]);
		$("#影片畫質識別碼").val(json[selectedIndex]["影片畫質識別碼"]);
	}
	,'json'
	);
}


function showVal(jobject){
	$('#順序').val(jobject.素材順序);
	$("#顯示名稱").val(jobject.顯示名稱);
	$("input[name='必填'][value='"+jobject.託播單素材是否必填+"']").prop('checked',true);
	$("input[name='myRadio'][value='"+jobject.素材類型+"']").prop('checked',true);
	$('#每小時最大素材筆數').val(jobject.每小時最大素材筆數);
	$('#每則文字素材最大字數').val(jobject.每則文字素材最大字數);
	$('#每則圖片素材最大寬度').val(jobject.每則圖片素材最大寬度);
	$('#每則圖片素材最大高度').val(jobject.每則圖片素材最大高度);
	$('#每小時最大影片素材合計秒數').val(jobject.每小時最大影片素材合計秒數);
	$('#每則影片素材最大秒數').val(jobject.每則影片素材最大秒數);
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
	setFilmType(jobject.影片畫質識別碼);
}

$('#submitBtn').click(function(){
	if(typeof($("input[name='myRadio']:checked").val())=='undefined'){
		alert('請選擇素材種類');
		return 0 ;
	}
	var jobject = {
		素材順序: $('#順序').val(),
		託播單素材是否必填: $("input[name='必填']:checked").val(),
		素材類型: $("input[name='myRadio']:checked").val(),
		素材類型識別碼:material[$("input[name='myRadio']:checked").val()],
		每小時最大素材筆數:$('#每小時最大素材筆數').val(),
		每則文字素材最大字數:$('#每則文字素材最大字數').val(),
		每則圖片素材最大寬度:$('#每則圖片素材最大寬度').val(),
		每則圖片素材最大高度:$('#每則圖片素材最大高度').val(),
		每小時最大影片素材合計秒數:$('#每小時最大影片素材合計秒數').val(),
		每則影片素材最大秒數:$('#每則影片素材最大秒數').val(),
		影片畫質識別碼:$('#影片畫質識別碼').val(),
		顯示名稱:$('#顯示名稱').val(),
	}
	if(action == 'new')
		parent.materialTypeAdd(jobject);
	else
		parent.materialTypeEdit(jobject);
});
</script>
</body>
</html>