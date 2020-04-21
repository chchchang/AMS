<?php
	header("X-Frame-Options: SAMEORIGIN");
	include('../tool/auth/auth.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="x-frame-options" content="sameorigin">
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script src="../tool/iframeAutoHeight.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/sameOriginXfsBlock.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">

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
<style id="antiClickjack">body{display:none !important;}</style>
</head>
<body>
<script>
	if (self.location.hostname === top.location.hostname) {
		var antiClickjack = document.getElementById("antiClickjack");
		antiClickjack.parentNode.removeChild(antiClickjack);
	} else {
		throw new Error("拒絕存取!");
		//top.location = self.location;
	}
</script>

<div class ="basicBlock" align="center" valign="center">
	<table class="styledTable" style="width:650px">
		<tr><th width = "300">廣告主名稱*</th><td width = "300" ><input id = "ownerName" type="text" value = "" size="38" style="width:290" class ="nonNull"></input></td></tr>
		<tr><th>廣告主統一編號</th><td><input id = "ownerVAT" type="text" value = "" size="38" style="width:290"></input></td></tr>
		<tr><th>廣告主地址</th><td><input id = "ownerAdderss" type="text" value = ""size="38" style="width:290" ></input></td></tr>
		<tr><th>廣告主聯絡人姓名</th><td><input id = "ownerContact" type="text" value = "" style="width:290"size="38"></input></td></tr>
		<tr style="border-bottom:2px solid #AAAAAA;"><th>廣告主聯絡人電話</th><td><input id = "ownerPhone" type="text" value = "" size="38" style="width:290"></input></td></tr>

		<tr><th width = "300">頻道商名稱</th><td width = "300"><input id = "chanelName" type="text" value = "" size="38" style="width:290"></td></tr>
		<tr><th>頻道商統一編號</th><td><input id = "chanelVAT" type="text" value = "" size="38" style="width:290"></input></td></tr>
		<tr><th>頻道商地址</th><td><input id = "chanelAdderss" type="text" value = "" size="38" style="width:290"></input></td></tr>
		<tr><th>頻道商聯絡人姓名</th><td><input id = "chanelContact" type="text" value = "" size="38" style="width:290"></input></td></tr>
		<tr style="border-bottom:2px solid #AAAAAA;"><th>頻道商聯絡人電話</th><td><input id = "chanelPhone" type="text" value = "" size="38" style="width:290"></input></td></tr>

		<tr><th width = "300">承銷商名稱</th><td width = "300" ><input id = "underwName" type="text" value = "" size="38" style="width:290"></td></tr>
		<tr><th>承銷商統一編號</th><td><input id = "underwVAT" type="text" value = "" size="38" style="width:290"></td></tr>
		<tr><th>承銷商地址</th><td><input id = "underwAddress" type="text" value = "" size="38" style="width:290"></td></tr>
		<tr><th>承銷商聯絡人姓名</th><td><input id = "underwContact" type="text" value = "" size="38" style="width:290"></td></tr>
		<tr><th>承銷商聯絡人電話</th><td><input id = "underwPhone" type="text" value = "" size="38" style="width:290"></td></tr>
	</table>
	<div  class ="Center" style="width:650px"><button onclick="clearVal()">清空</button> <button onclick="create()">新增</button></div>
</div>
<script>

var ajaxtodbPath="ajaxToDB_Adowner.php";

function clearVal(){
	$("#ownerName").val("");
	$("#ownerVAT").val("");
	$("#ownerAdderss").val("");
	$("#ownerContact").val("");
	$("#ownerPhone").val("");
	$("#chanelName").val("");
	$("#chanelVAT").val("");
	$("#chanelAdderss").val("");
	$("#chanelContact").val("");
	$("#chanelPhone").val("");
	$("#underwName").val("");
	$("#underwVAT").val("");
	$("#underwAddress").val("");
	$("#underwContact").val("");
	$("#underwPhone").val("");
};

function create(){
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
	var byPost ="action=newOwenr&"
	+"廣告主名稱="+$("#ownerName").val()+"&"
	+"廣告主統一編號="+$("#ownerVAT").val()+"&"
	+"廣告主地址="+$("#ownerAdderss").val()+"&"
	+"廣告主聯絡人姓名="+$("#ownerContact").val()+"&"
	+"廣告主聯絡人電話="+$("#ownerPhone").val()+"&"
	+"頻道商名稱="+$("#chanelName").val()+"&"
	+"頻道商統一編號="+$("#chanelVAT").val()+"&"
	+"頻道商地址="+$("#chanelAdderss").val()+"&"
	+"頻道商聯絡人姓名="+$("#chanelContact").val()+"&"
	+"頻道商聯絡人電話="+$("#chanelPhone").val()+"&"
	+"承銷商名稱="+$("#underwName").val()+"&"
	+"承銷商統一編號="+$("#underwVAT").val()+"&"
	+"承銷商地址="+$("#underwAddress").val()+"&"
	+"承銷商聯絡人姓名="+$("#underwContact").val()+"&"
	+"承銷商聯絡人電話="+$("#underwPhone").val()+"&"
	+"UID=<?php echo $_SESSION['AMS']['使用者識別碼']; ?>";
	ajax_to_db(byPost,ajaxtodbPath,
		function(data){
		    var result=$.parseJSON(data);
			if(result["dbError"]!=undefined){
				alert(result["dbError"]);
				return 0;
			}
			if(result["success"]){
				//是否需要回傳值給parent
				var getReturn = <?php if(isset($_GET['RETURN']))echo 'true'; else echo 'false'; ?>;
				if(getReturn)
					parent.newOwenrCreated(result["insert_id"],$("#ownerName").val());
				clearVal();
			}			
			setTimeout(function() { alert(result["message"]); }, 200);
		}
	);
}

//自動完成設定
 $(function(){
	//廣告主
	$("#ownerName").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "廣告主名稱"},
						function( data ) {
						//alert(data);
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoOwner(ui.item.id);
		}
	});
	
	$("#ownerVAT").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "廣告主統一編號"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoOwner(ui.item.id);
		}
	});
	
	$("#ownerAdderss").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "廣告主地址"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoOwner(ui.item.id);
		}
	});
	
	$("#ownerContact").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "廣告主聯絡人姓名"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoOwner(ui.item.id);
		}
	});
	
	$("#ownerPhone").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "廣告主聯絡人電話"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoOwner(ui.item.id);
		}
	});
	
	//頻道商
	$("#chanelName").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "頻道商名稱"},
						function( data ) {
						//alert(data);
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoChanel(ui.item.id);
		}
	});
	
	$("#chanelVAT").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "頻道商統一編號"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoChanel(ui.item.id);
		}
	});
	
	$("#chanelAdderss").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "頻道商地址"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoChanel(ui.item.id);
		}
	});
	
	$("#chanelContact").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "頻道商聯絡人姓名"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoChanel(ui.item.id);
		}
	});
	
	$("#chanelPhone").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "頻道商聯絡人電話"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoChanel(ui.item.id);
		}
	});
	
	
	//承銷商
	$("#underwName").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "承銷商名稱"},
						function( data ) {
						//alert(data);
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoUnderw(ui.item.id);
		}
	});
	
	$("#underwVAT").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "承銷商統一編號"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoUnderw(ui.item.id);
		}
	});
	
	$("#underwAddress").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "承銷商地址"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoUnderw(ui.item.id);
		}
	});
	
	$("#underwContact").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "承銷商聯絡人姓名"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoUnderw(ui.item.id);
		}
	});
	
	$("#underwPhone").autocomplete({
		source :function( request, response ) {
					$.post( "autoCompleteSearch.php",{term: request.term, input: "承銷商聯絡人電話"},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			autoUnderw(ui.item.id);
		}
	});
	
	function autoOwner(id){
		if(confirm("是否要將廣告主的相關資料填入?"))
		{
			$.post("autoCompleteSearch.php",{"getData":"廣告主資料","廣告主識別碼":id},
			function(data){
				$("#ownerName").val(data["廣告主名稱"]);
				$("#ownerVAT").val(data["廣告主統一編號"]);
				$("#ownerAdderss").val(data["廣告主地址"]);
				$("#ownerContact").val(data["廣告主聯絡人姓名"]);
				$("#ownerPhone").val(data["廣告主聯絡人電話"]);	
			}
			,'json');
		}	
	}
		
	function autoChanel(id){
		if(confirm("是否要將頻道商的相關資料填入?"))
		{
			$.post("autoCompleteSearch.php",{"getData":"頻道商資料","廣告主識別碼":id},
			function(data){
				$("#chanelName").val(data["頻道商名稱"]);
				$("#chanelVAT").val(data["頻道商統一編號"]);
				$("#chanelAdderss").val(data["頻道商地址"]);
				$("#chanelContact").val(data["頻道商聯絡人姓名"]);
				$("#chanelPhone").val(data["頻道商聯絡人電話"]);	
			}
			,'json');
		}	
	}
	
	function autoUnderw(id){
		if(confirm("是否要將承銷商的相關資料填入?"))
		{
			$.post("autoCompleteSearch.php",{"getData":"承銷商資料","廣告主識別碼":id},
			function(data){
				$("#underwName").val(data["承銷商名稱"]);
				$("#underwVAT").val(data["承銷商統一編號"]);
				$("#underwAddress").val(data["承銷商地址"]);
				$("#underwContact").val(data["承銷商聯絡人姓名"]);
				$("#underwPhone").val(data["承銷商聯絡人電話"]);	
			}
			,'json');
		}	
	}
 });
</script>
</body>
</html>