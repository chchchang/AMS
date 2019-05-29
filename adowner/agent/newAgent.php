<?php
	header("X-Frame-Options: SAMEORIGIN");
	include('../../tool/auth/authAJAX.php');

	//頁面執行行為，新增(預設，new)、修改(edit)或顯示(info)
	$pageAction = 'new';
	if(isset($_GET["pageAction"]))
		$pageAction = htmlspecialchars($_GET["pageAction"], ENT_QUOTES, 'UTF-8');
	//要顯示或修改的委刊單識別碼
	$oid = '';
	if(isset($_GET["oid"]))
		$oid = htmlspecialchars($_GET["oid"], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<!--<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />-->
<link rel='stylesheet' type='text/css' href='../../external-stylesheet.css' />
<script type="text/javascript" src="../../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../../tool/ajax/ajaxToDB.js"></script> 
<script src="../../tool/iframeAutoHeight.js" type="text/javascript"></script>
<script src="../../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../../tool/sameOriginXfsBlock.js"></script>
<!--<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">-->
<link rel="stylesheet" href="../../tool/jquery-ui/jquery-ui.css">

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


<div class ="basicBlock" align="center" valign="center">
	<table class="styledTable" style="width:650px">
		<tr><th width = "300">代理商識別碼</th><td width = "300" ><a id = "agentId" size="38" style="width:290"></a></td></tr>
		<tr><th width = "300">代理商名稱*</th><td width = "300" ><input id = "agentName" type="text" value = "" size="38" style="width:290" class ="nonNull"></input></td></tr>
		<tr><th>代理商統一編號</th><td><input id = "agnetVAT" type="text" value = "" size="38" style="width:290"></input></td></tr>
	</table>
	<div  class ="Center" style="width:650px"><button onclick="clearVal()">清空</button> <button onclick="save()">儲存</button></div>
</div>
<script>

var ajaxtodbPath="ajaxToDB_Agent.php";
var oid = '<?=$oid?>';
$("#agentId").text(oid)
var pageAction="<?=$pageAction;?>";
if(pageAction =="new"){
	$("#ownerId").text("<?php if(isset($_GET['ownerid'])) echo htmlspecialchars($_GET['ownerid'], ENT_QUOTES, 'UTF-8');?>");
	$('#ownerName').text(getAdOwnerName($("#ownerId").text()));
	委刊單名稱 = $('#ownerName').text();
}
else if(pageAction=="info"){
	$("button").hide();
	$("input").prop('disabled', true);
}

clearVal();

function clearVal(){
	if(pageAction =="new"){
		$("#agentName").val("");
		$("#agnetVAT").val("");
	}
	else{
		var byPost =
		{
			"action":"取得代理商資料",
			"代理商識別碼":$("#agentId").text()
		}
		$.post(ajaxtodbPath,byPost,
			function(data){
				if(data["success"]){
					$("#agentName").val(data["代理商名稱"]);
					$("#agnetVAT").val(data["代理商統一編號"]);
				}
				else{
					alert(data["message"]);
				}
			}
			,"json"
		);
	}
};

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
	if(pageAction =="new"){
		var byPost =
		{
		"action":"新增代理商",
		"代理商名稱":$("#agentName").val(),
		"代理商統一編號":$("#agnetVAT").val()
		}
	}
	else if(pageAction =="edit"){
		var byPost =
		{
			"action":"更新代理商",
			"代理商識別碼":$("#agentId").text(),
			"代理商名稱":$("#agentName").val(),
			"代理商統一編號":$("#agnetVAT").val()
		}
	}
	//是否需要回傳值給parent
	var getReturn = <?php if(isset($_GET['RETURN']))echo 'true'; else echo 'false'; ?>;
	$.post(ajaxtodbPath,byPost,
		function(data){
			if(data["sussess"]){
				alert(data["message"]);
			}
			else{
				alert(data["message"]);
			}
		}
		,"json"
	);
}

//自動完成設定
 $(function(){
	$("#agentName").autocomplete({
		source :function( request, response ) {
					$.post( "../autoCompleteSearch.php",{term: request.term, input: "代理商名稱"},
						function( data ) {
						//alert(data);
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoAgent(ui.item.id);
		}
	});
	
	$("#agnetVAT").autocomplete({
		source :function( request, response ) {
					$.post( "../autoCompleteSearch.php",{term: request.term, input: "代理商統一編號"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoAgent(ui.item.id);
		}
	});
	
	
	
	function autoAgent(id){
		if(confirm("是否要將代理商的相關資料填入?"))
		{
			$.post("autoCompleteSearch.php",{"getData":"代理商資料","代理商識別碼":id},
			function(data){
				$("#agentName").val(data["代理商名稱"]);
				$("#agnetVAT").val(data["代理商統一編號"]);
			}
			,'json');
		}	
	}
	
 });
</script>
</body>
</html>