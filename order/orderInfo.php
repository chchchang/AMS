<?php
	include('../tool/auth/authAJAX.php');
	
	
	//更改資料庫中託播單狀態
	if(isset($_POST['method'])) {
		switch($_POST['method']){
			case '確定':
				$sql = "UPDATE 託播單 SET 託播單狀態識別碼=1 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')確定託播單(識別碼'.$_POST["託播單識別碼"].')');
				exit (json_encode(array("success"=>true,'message'=>"託播單已確定"),JSON_UNESCAPED_UNICODE));
				break;
			case '送出':
				$sql = "UPDATE 託播單 SET 託播單狀態識別碼=2 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')送出託播單(識別碼'.$_POST["託播單識別碼"].')');
				exit (json_encode(array("success"=>true,'message'=>"託播單已送出"),JSON_UNESCAPED_UNICODE));
				break;
			case '取消確定':
				$sql = "UPDATE 託播單 SET 託播單狀態識別碼=0 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')取消確定託播單(識別碼'.$_POST["託播單識別碼"].')');
				exit (json_encode(array("success"=>true,'message'=>"取消託播單確定"),JSON_UNESCAPED_UNICODE));
				break;
			case '取消送出':
				$sql = "UPDATE 託播單 SET 託播單狀態識別碼=1 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')取消送出託播單(識別碼'.$_POST["託播單識別碼"].')');
				exit (json_encode(array("success"=>true,'message'=>"取消託播單送出"),JSON_UNESCAPED_UNICODE));
				break;
			case '待處理':
				$sql = "UPDATE 託播單 SET 託播單狀態識別碼=4 WHERE 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$logger->info('使用者(識別碼'.$_SESSION['AMS']['使用者識別碼'].')產生託播單EXCLE檔(識別碼'.$_POST["託播單識別碼"].')');
				exit (json_encode(array("success"=>true,'message'=>"檔案已產生、等待處理"),JSON_UNESCAPED_UNICODE));
				break;
		}
	}
	//取得API介接需要資訊
	else if(isset($_POST['getInfoForApi'])){
		switch($_POST['getInfoForApi']){
			case '852Send'://852用的託播單參數
				$sql = "SELECT `託播單介接API URL`,版位.其他介接參數 AS 其他介接參數,影片素材秒數,影片媒體編號,影片媒體編號北,影片媒體編號南,廣告期間開始時間
				,廣告期間結束時間
				FROM 託播單,版位,素材 
				WHERE 託播單.版位識別碼=版位.版位識別碼 AND 託播單.素材識別碼 =  素材.素材識別碼 AND 託播單識別碼=?";
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->bind_param('i',$_POST["託播單識別碼"])) {
					exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$stmt->execute()) {
					exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				if(!$res=$stmt->get_result()){
					exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				
				$result = array();
				$row=$res->fetch_assoc();
				$result=
					array(
					"API" => $row['託播單介接API URL'],
					"其他介接參數" => $row['其他介接參數'],
					"影片素材秒數"   => $row['影片素材秒數'],
					"影片媒體編號"   => $row['影片媒體編號'],
					"影片媒體編號北"   => $row['影片媒體編號北'],
					"影片媒體編號南"   => $row['影片媒體編號南']);

				exit(json_encode($result,JSON_UNESCAPED_UNICODE));
				break;
		}
	}
	
	$sql = "SELECT 廣告主識別碼,託播單.委刊單識別碼, 託播單.版位識別碼,版位類型.版位名稱 AS 版位類型名稱 
	FROM 託播單,委刊單,版位,版位 版位類型
	WHERE 託播單.委刊單識別碼=委刊單.委刊單識別碼 AND 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 託播單識別碼=?";
	if(!$stmt=$my->prepare($sql)) {
		exit(json_encode(array("dbError"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	if(!$stmt->bind_param('i',$_GET['name'])) {
		exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	if(!$stmt->execute()) {
		exit(json_encode(array("dbError"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	}
	$stmt->bind_result($廣告主識別碼,$委刊單識別碼,$版位識別碼,$版位類型名稱);
	$stmt->fetch();
	
	$parent = "";
	if(isset($_GET['parent']))
		$parent=htmlspecialchars($_GET['parent'], ENT_QUOTES, 'UTF-8');
	$change = 0;
	if(isset($_GET['異動']))
		$change=1;
	$apiInfo = 0;
	if(isset($_GET['apiInfo'])&&$_GET['apiInfo']==true)
		$apiInfo=1;
	if(isset($_GET['版位類型名稱'])&&$_GET['版位類型名稱']==true)
		$版位類型名稱=$_GET['版位類型名稱'];
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script src="../tool/iframeAutoHeight.js" type="text/javascript"></script>
<style type="text/css">
#leftBody{
	width:500px;
	float:left;
}
#rigntBody{
	width:500px;
	float:left;
}
#footer{
	clear:both;
}
.underline
{
	text-decoration:underline;
	font-weight: 900;
	color:#888888;
}
</style>
</head>
<body>
<h3 id = "alertMessage"></h3>
<table class='styledTable2'>
<tr><th id ='idText'>託播單識別碼</th><th>廣告主</th><th>委刊單</th><th>託播單狀態</th></tr>
<tr><td><?=htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8')?></td><td><a id='owner'></a></td><td><a id = 'orderlist'></a></td>
<td> <a id = "playStatus"></a> <button class="darkButton" type="button" id="checkBtn">確定</button><button class="darkButton" type="button" id="sendBtn">送出</button></td></tr>
<tr></tr>
</table>
<iframe id = 'orderInfoFrame' height = '800' width = '100%'>
</iframe>

<script>
	var ajaxToApi="ajaxToAPI.php";
	var 版位類型名稱='<?=$版位類型名稱?>';
	var id= <?php echo htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');?>;
	var change= <?=$change?>, apiInfo= <?=$apiInfo?>;
	if(apiInfo==1)
		orderInfoFromApi();
	else
		$('#orderInfoFrame').attr('src','newOrder.php?orderInDb='+id);
	if(change!=0){
		$("#alertMessage").text("此託播單有尚未儲存的異動");
		$("#sendBtn,#checkBtn").remove();
	}
	
	var fromparent = "<?=$parent?>";
	if(fromparent=="訂單管理"){
		$("#sendBtn").remove();
	}
	else if(fromparent=="投放管理"){
		$("#checkBtn").remove();
		$("#sendBtn").bind('done',function(){
			refresh();
			parent.showSchedule();
		});
	}
	else{
		$("#sendBtn,#checkBtn").remove();
	}
	
	$('#ownerIfoButton').click(function(e){	
		parent.openOnwerInfoDialog(<?=$廣告主識別碼?>);
	});
	
	$('#orderListIfoButton').click(function(e){	
		parent.openOrderListInfoDialog(<?=$委刊單識別碼?>);
	});
	
	//DB要資料
	var 託播單狀態=0;
	refresh();
	function refresh(){
		orderInfoFromDb();
	}
	
	function clearAll(){
		$('#orderInfoFrame').attr('src','');
	}
	
	//從資料庫中取得訂單資訊
	function orderInfoFromDb(){
		$("#playtimeMessage").hide();
		var byPost ="action=訂單資訊&託播單識別碼="+id;
		ajax_to_db(byPost, "ajaxToDB_Order.php",
			function(data){
				var result=$.parseJSON(data);
				if(result["dbError"]!=undefined){
								alert(result["dbError"]);
								return 0;
				}
				託播單狀態=	result["託播單狀態識別碼"];
				//依據託播單狀態更改按鈕設訂
				$("#playStatus").text(result["託播單狀態名稱"]);
				$("#owner").text(result["廣告主名稱"]);
				$("#orderlist").text(result["委刊單名稱"]);
				switch(託播單狀態){
					case 0:
						$("#checkBtn").text("確定").show();
						$("#sendBtn").hide();
						break;
					case 1:
						$("#checkBtn").text("取消確定").show();
						$("#sendBtn").text("送出").show();
						break;
					case 2:
						$("#sendBtn").text("取消送出").show();
						$("#checkBtn").hide();
						break;
					default:
						$("#sendBtn").hide();
						$("#checkBtn").hide();
						break;
				}
			}
		);
	}
	
	//從API取得訂單資訊
	function orderInfoFromApi(){
		if(版位類型名稱=='前置廣告投放系統'){
			orderInfo_852();
		}
		else if(版位類型名稱 == '專區vod' || 版位類型名稱 == '頻道short EPG banner' || 版位類型名稱 == '專區banner' || 版位類型名稱 == '首頁banner'){
			orderInfo_851();
		}
	}
	
	//確定
	$("#checkBtn").click(function(){
		switch(託播單狀態){
			case 0:
				changeOrderState("確定");
				break;
			case 1:
				changeOrderState("取消確定");
				break;
			case 2:
				break;
		}
		var $clickBtn = $(this);
	});
	
	//送出
	$("#sendBtn").click(function(){
		var method="";
		switch(託播單狀態){
			case 0:
				break;
			case 1:
				method = "送出";
				orderApi('send');
				break;
			case 2:
				orderApi('delete');
				/*if(版位類型名稱=='前置廣告投放系統'){
					cancel_852();
				}
				else{
					method = "取消送出";
					changeOrderState(method);
				}*/
				break;
		}
	});
	
	function orderApi(action){
		var sendFlag = true;
		if(action == 'delete') sendFlag =false;
		var actionText = sendFlag?'送出':'取消送出';
		//群組送出託播單
		$.post('../order/ajaxToAPI.php',{'action':'群組託播單','selectedOrder':[id]},
			function(json){
				//失敗的託播單
				for(var i in json.failArray){
					var id = json.failArray[i]['託播單識別碼'];
					alert('託播單(識別碼'+id+')'+actionText+'失敗:'+json.failArray[i]['message']);
					$("#sendBtn").trigger('done');
				}
				//單張的託播單
				for(var i in json.singleArray){
					var id = json.singleArray[i];
					$.post('ajaxToAPI.php',{'action':sendFlag?'API送出託播單':'API取消託播單', 託播單識別碼:id},
						function(json){
							if(json.success==true){
								alert('託播單(識別碼'+json.id+')'+actionText+'成功');
							}
							else if(json.success==false){
								alert('託播單(識別碼'+json.id+')'+actionText+'失敗:'+json.message);
							}
							else{
								alert('託播單(識別碼'+json.id+')'+actionText+'失敗');
							}
							$("#sendBtn").trigger('done');
						},'json'
					);
				}
					
				for(var i in json.groupArray){
					$.post('../order/ajaxToAPI.php',{'action':'批次產生檔案','ptName':json.groupArray[i]['ptN'],'groupId':json.groupArray[i]['gId'],'ids':json.groupArray[i]['ids'],'APIAction':sendFlag?'send':'delete'},
						function(json){
							if(json.success==true){
								alert('託播單(識別碼'+json.id+')'+actionText+'成功');
							}
							else if(json.success==false){
								alert('託播單(識別碼'+json.id+')'+actionText+'失敗:'+json.message);
							}
							else{
								alert('託播單(識別碼'+json.id+')'+actionText+'失敗');
							}
							$("#sendBtn").trigger('done');
						}
						,'json'
					)
				}
			}
			,'json'
		);
	}
	
	//改變託播單狀態: method: 確定/取消確定/送出/取消送出
	function changeOrderState(method){
		$.post('?',{'method':method,"託播單識別碼":id}
			,function(data){
				if(typeof data['dbError']!='undefined'){
					alert(data['dbError']);
					return 0;
				}
				if(data['success']){
					alert(data['message']);
				try {
					parent.orderStateChange(method);
				}
				catch(err) {
				}
					refresh();
				}
			}	
			,'json'
		);
	}

	//從852取得託播單資料並顯示
	var orderInfo;
	function orderInfo_852(){
		var byPost={
			'action':"852託播單資料"
			,'託播單識別碼':id
		}
		$.post(
			"ajaxToAPI.php",
			byPost,
			function(data){
				if(data['success']==true){
					orderInfo = data['orderInfo'];
					$('#orderInfoFrame').attr('src',"newOrder.php?orderFromApi="+id);
				}
				else{
					alert(data['message']);
				}
			}
			,'json'
		);
	}
	
	//用852API送出託播單
	function send_852(){
		var byPost={
			'action':"API送出託播單"
			,'託播單識別碼':id//託播單識別碼
		}
		$.post(
			ajaxToApi,
			byPost,
			function(data){
				if(data['success']==true){
					//changeOrderState('送出');
					alert(data['message']);
					try {
						parent.orderStateChange('');
					}
					catch(err) {
					}
					refresh();
				}
				else
					alert("託播單送出失敗:"+data['message']);
			}
			,'json'
		);
	}
	
	//用852API取消託播單
	function cancel_852(){
		var byPost={
				'action':"852取消託播單"
				,'託播單識別碼':id
			}
		$.post(
			ajaxToApi,
			byPost,
			function(data){
				if(data['success']==true){
					//changeOrderState('取消送出');
					alert(data['message']);
					try {
						parent.orderStateChange('');
					}
					catch(err) {
					}
					refresh();
				}
				else
					alert("託播單取消送出失敗");
			}
			,'json'
		);
	}
	
	//851託播單資訊
	function orderInfo_851(){
		$('#idText').text('CSMS群組識別碼(TransactionID)');
		var byPost={
			'action':"851託播單資料"
			,'orderInfo':parent.orderData[id]
		}
		$.post(
			"ajaxToAPI.php",
			byPost,
			function(data){
				if(data['success']==true){
					orderInfo = data['orderInfo'];
					$('#orderInfoFrame').attr('src',"newOrder.php?orderFromApi="+id);
				}
				else{
					alert(data['message']);
				}
			}
			,'json'
		);
	}
	
	//匯出851用檔案
	function getFileFor851(){
		$.post(ajaxToApi,{'action':"851產生檔案",'託播單識別碼':id}
			,function(json){
				if(typeof(json['dbError'])!='undefined')
					alert(json['dbError']);
				else if(typeof(json['success'])!='undefined' && json['success']==true){
					//changeOrderState('待處理');
					alert(json['message']);
					try {
						parent.orderStateChange(method);
					}
					catch(err) {
					}
					refresh();
				}
				else if(json['success']==false){
					alert(json['message']);
				}
			}
			,'json'
		);
	}
 </script>
 
</body>
</html>