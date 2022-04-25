
<?php
	include('../tool/auth/authAJAX.php');
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit(json_encode(array('無法連線到資料庫，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit(json_encode(array('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	
	if(isset($_POST['action'])){
		if($_POST['action'] == 'autocompleteSearch'){
			$term = '%'.$_POST['term'].'%';
			$result=array();
			$sql="SELECT DISTINCT ".$_POST['column']." as value,".$_POST['column']." as id FROM 委刊單 WHERE ".$_POST['column']." LIKE ?";
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('s',$term)) {
				$logger->error('無法綁定參數，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}		
			while($row = $res->fetch_array()){
				$result[] = $row;
			}
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		exit();
	}
	
	$sql = "SELECT 廣告主識別碼,廣告主名稱 FROM 廣告主";
	
	if(!$stmt=$my->prepare($sql)) {
		$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->execute()) {
		$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	$adOwner = array();
	while($row=$res->fetch_assoc())
		array_push($adOwner,$row);
	$adOwner =json_encode($adOwner,JSON_UNESCAPED_UNICODE);
	$my->close();
	
	$orderListName = '';
	if(isset($_GET['orderName']))
		$orderListName = htmlspecialchars($_GET["orderName"], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script src="../tool/iframeAutoHeight.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css"></link>
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
<div class ="basicBlock" align="center" valign="center">
	<table class="styledTable" style="width:650px">
		<tr id = "orderListIdRow"><th width = "300">委刊單識別碼:</th><td width = "300" ><a id = "orderListId" size="38" style="width:290"></a></td></tr>
		<tr><th width = "300">廣告主識別碼</th><td width = "300" id = "ownerId" ></td></tr>
		<tr><th width = "300">廣告主識名稱</th><td id = "ownerName" width = "300" ></td></tr>
		<tr><th>委刊單編號</th><td><input id = "委刊單編號" type="text" value = "" size="38" style="width:290"></input></td></tr>
		<tr><th>委刊單名稱*</th><td><input id = "orederListName" type="text" value = "" size="38" style="width:290" class ="nonNull"></input></td></tr>
		<tr><th>委刊單說明</th><td><input id = "orderListInfo" type="text" value = ""size="38" style="width:290" ></input></td></tr>
		<tr><th>售價</th><td><input id = "orderListPrice" type="number" value = ""size="38" style="width:290" ></input></td></tr>

	</table>
	<div  class ="Center" style="width:650px"><button id="clearBtn" onclick="clearInput()">清空</button> <button id="saveBtn" onclick="save()">新增</button></div>
</div>

<script>
	var ajaxtodbPath="ajaxToDB_Order.php";
	var adOwner = <?=$adOwner?>;
	var 委刊單名稱 = '<?=$orderListName?>';
	var action="<?php if(isset($_GET['action'])) echo htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8'); else echo "new";?>";
	if(action =="new"){
		$("#orderListIdRow").remove();
		$("#ownerId").text("<?php if(isset($_GET['ownerid'])) echo htmlspecialchars($_GET['ownerid'], ENT_QUOTES, 'UTF-8');?>");
		$('#ownerName').text(getAdOwnerName($("#ownerId").text()));
		委刊單名稱 = $('#ownerName').text();
	}
	else if(action=="info"){
		$("button").hide();
		$("input").prop('disabled', true);
	}
	
	//指定顯示委刊單(修改委刊單或委刊單詳細資料用)
	var orderListId="<?php if(isset($_GET['orderListId'])) echo htmlspecialchars($_GET['orderListId'], ENT_QUOTES, 'UTF-8');?>";

	if(orderListId!=""){
		$("#orderListId").text(orderListId);
		$("#saveBtn").text("修改");
		$("#clearBtn").text("還原");
	}
	//委刊單名稱自動完成
	$('#orederListName').autocomplete({
		source :function( request, response ) {
					$.post( "",{action:'autocompleteSearch', term: request.term, column:'委刊單名稱'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	//委刊單說明自動完成
	$('#orderListInfo').autocomplete({
		source :function( request, response ) {
					$.post( "",{action:'autocompleteSearch', term: request.term, column:'委刊單說明'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	clearInput();
	
	function getInfo(){
		var byPost ="action=顯示委刊單資料&委刊單識別碼="+orderListId;
		ajax_to_db(byPost,ajaxtodbPath,
			function(data){
				var result=$.parseJSON(data);
				if(result["dbError"]!=undefined){
					alert(result["dbError"]);
					return 0;
				}
				$("#ownerId").text(result["廣告主識別碼"]);
				$("#orederListName").val(result["委刊單名稱"]);
				$("#orderListInfo").val(result["委刊單說明"]);
				$("#orderListPrice").val(result["售價"]);
				$('#ownerName').text(getAdOwnerName(result["廣告主識別碼"]));
				$('#委刊單編號').val(result["委刊單編號"]);
			}
		);
	}
	
	function getAdOwnerName(adOnwerId){
		for(var i in adOwner){
			if(adOwner[i]['廣告主識別碼']==parseInt(adOnwerId))
				return adOwner[i]['廣告主名稱'];
		}
		return '無廣告主';
	}
	
	//清理or還原資料
	function clearInput(){
		if(action=="new"){
			$(":text").val("");
			$("#orederListName").val(委刊單名稱);
		}
		else{
			getInfo();
		}
	}
	
	//儲存資料
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
		if(action=="new"){
			newOrderList();
		}else if(action="edit"){
			editOrderList();
		}
	}
	
	//新增委刊單資料
	function newOrderList(){
		var byPost ="action=newOrderList&"
		+"廣告主識別碼="+$("#ownerId").text()+"&"
		+"委刊單名稱="+$("#orederListName").val()+"&"
		+"委刊單說明="+$("#orderListInfo").val()+"&"
		+"售價="+$("#orderListPrice").val()+"&"
		+"委刊單編號="+$("#委刊單編號").val();
		ajax_to_db(byPost,ajaxtodbPath,
			function(data){
				var result=$.parseJSON(data);
				if(result["dbError"]!=undefined){
					alert(result["dbError"]);
					return 0;
				}
				alert(result["message"]);
				if(result["success"]){
					//是否需要回傳值給parent(回傳被選擇/新增的id)
					var getReturn = <?php if(isset($_GET['RETURN']))echo 'true'; else echo 'false'; ?>;
					if(getReturn)
						parent.newOrderListCreated(result["insert_id"],$("#orederListName").val());
					clearInput();
				}			
			}
		);
	}
	
	function editOrderList(){
		var byPost ="action=editOrderList&"
		+"廣告主識別碼="+$("#ownerId").text()+"&"
		+"委刊單名稱="+$("#orederListName").val()+"&"
		+"委刊單說明="+$("#orderListInfo").val()+"&"
		+"售價="+$("#orderListPrice").val()+"&"
		+'委刊單識別碼='+$('#orderListId').text()+'&'
		+"委刊單編號="+$("#委刊單編號").val();
		ajax_to_db(byPost,ajaxtodbPath,
			function(data){
				var result=$.parseJSON(data);
				if(result["dbError"]!=undefined){
					alert(result["dbError"]);
					return 0;
				}
				alert(result["message"]);
				if(result["success"]){
					//是否需要回傳值給parent
					var getReturn = <?php if(isset($_GET['RETURN']))echo 'true'; else echo 'false'; ?>;
					if(getReturn)
						parent.orderListUpdated(orderListId,$("#orederListName").val());
					clearInput();
				}			
			}
		);
	}
	
 </script>
 
 
</body>
</html>