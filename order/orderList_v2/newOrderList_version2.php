<?php
	include('../../tool/auth/authAJAX.php');
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
		else if($_POST['action']=='getPostTypeSelection'){
			$sql = 'SELECT 委刊單排程投放方式識別碼,委刊單排程投放方式名稱 FROM 委刊單排程投放方式';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			$options=array();
			while($row=$res->fetch_assoc()) {
				$options[]=array($row['委刊單排程投放方式識別碼'],$row['委刊單排程投放方式名稱']);
			}
			exit(json_encode($options,JSON_UNESCAPED_UNICODE));
		
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
	
	//頁面基本行為相關
	$orderListName = '';
	if(isset($_GET['orderName']))
		$orderListName = htmlspecialchars($_GET["orderName"], ENT_QUOTES, 'UTF-8');
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
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
	include('../../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../../tool/ajax/ajaxToDB.js"></script>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script src="../../tool/jquery-ui1.2/jquery-ui.js"></script>
<link rel="stylesheet" href="../../tool/jquery-ui1.2/jquery-ui.css"></link>
<script src="../../tool/iframeAutoHeight.js" type="text/javascript"></script>
<script type="text/javascript" src="../../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.tokenize.js"></script>
<link rel="stylesheet" href="../../tool/jquery-ui1.2/jquery-ui.css"></link>
<link rel='stylesheet' type='text/css' href='../../external-stylesheet.css' />
<link rel="stylesheet" type="text/css" href="../../tool/jquery-plugin/jquery.tokenize.css" />
<link href="../../tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../../order/newOrder_851.js?<?=time()?>"></script>
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
table, th, td {
  border: 1px solid black;
}
table {
  border-collapse: collapse;
}
.tokenize{ width: 300px }
</style>
</head>
<body>
<div class ="basicBlock" align="center" valign="center">
	<fieldset  style="clear: both;">
	<legend>委刊單基本資訊</legend>
	<table class="styledTable" >
		<tr id = "orderListIdRow"><th width = "300">委刊單識別碼:</th><td width = "300" ><a id = "orderListId" size="38" style="width:290"></a></td></tr>
		<tr><th width = "300">廣告主名稱*</th><th width = "300">代理商名稱</th></tr>
		<tr><td width = "300" align="center"><input type="text" id = "ownerName" class ="nonNull"></input></td><td width = "300" align="center"	 ><input type="text" id = "agentName"></input></td></td></tr>
		<tr><th width = "300">廣告主識別碼</th><th width = "300">代理商識別碼</th>
		<tr><td width = "300" align="center" id = "ownerId" ></td><td width = "300" align="center" id = "agentId" ></td></tr>
		<tr><th width = "300">廣告主統編</th><th width = "300">代理商統編</th></tr>
		<tr><td width = "300" align="center" id = "ownerVAT" ></td><td width = "300" align="center" id = "agentVAT" ></td></tr>
		<!--<tr><th colspan="2">委刊單編號</th></tr>
		<tr><td colspan="2"><input id = "委刊單編號" type="text" value = "" size="38" style="width:290"></input></td></tr> -->
		<tr><th colspan="2">委刊單名稱*</th></tr>
		<tr><td colspan="2" align="center"><input id = "orederListName" type="text" value = "" size="38" style="width:290" class ="nonNull"></input></td></tr>
		<tr><th colspan="2">委刊單說明</th></tr>
		<tr><td colspan="2" align="center"><input id = "orderListInfo" type="text" value = ""size="38" style="width:290" ></input></td></tr>
		<tr><th colspan="2">素材連結位置</th></tr>
		<tr><td colspan="2" align="center"><input id = "materialPath" type="text" value = "" size="38" style="width:290"></input></td></tr>
		<tr><th colspan="2">備註</th></tr>
		<tr><td colspan="2" align="center"><input id = "note" type="text" value = "" size="38" style="width:290"></input></td></tr>
		<tr><th colspan="2">售價</th></tr>
		<tr><td colspan="2" align="center"><input id = "orderListPrice" type="number" value = ""size="38" style="width:290" ></input></td></tr>
		<tr><th colspan="2">委刊單檔期</th></tr>
		<tr><td>起*:<input id = "orderListStartTime" class ="nonNull" type="text" value = "" autocomplete="off"></input></td><td>迄*:<input id = "orderListEndTime" class ="nonNull" type="text" value = "" autocomplete="off"></input></td></tr>	
	</table>
	</fieldset>
	<fieldset  style="clear: both;">
	
	<legend>委刊單排播資訊</legend>
		<table id="orderScheduleTable">
		<thead>
		<tr><th>版位類型</th><th>版位數量</th><th>投放方式</th><th>投放數量</th><th>投放時段</th><th>起</th><th>訖</th><th>修改</th><th>刪除</th></tr>
		</thead>
		<tbody id="orderScheduleTableBody">
		</tbody>
		</table>
		<button id = "newOrderSchedule" class = 'darkButton'>新增排程</button>
	</fieldset>
	
	<div  class ="Center" style="width:650px"><button id="clearBtn" onclick="clearInput()">還原</button> <button id="saveBtn" onclick="save()">儲存委刊單</button></div>
</div>
<div id="orderSchedule_dialog_form" class ="Center">
	<input type = "hidden" id = "scheduleTempId"></input>
	<input type = "hidden" id = "scheduleDbId"></input>
	<table>
		<tr><th>版位類型</th><td><select id="schedulePositiontype"></select></td></tr>
		<tr><th>版位</th><td><select id="schedulePositions"  multiple="multiple"  class ="tokenize" ></select></td></tr>
		<tr><th>投放方式</th><td><select id="schedulePostType"></select></td></tr>
		<tr><th>數量</th><td><input id="schedulePostTime" type="number"></td></tr>
		<tr><th>投放時段</th><td>
			<button id = 'scheduleAllTimeBtn' class = 'darkButton'>全選</button> <button id = 'scheduleNoTimeBtn' class = 'darkButton'>全不選</button>
			<table border ="0" id = "schedulePlayHours">
			<thead><tr><th>00</th><th>01</th><th>02</th><th>03</th><th>04</th><th>05</th><th>06</th><th>07</th><th>08</th><th>09</th>
			<th>10</th><th>11</th><th>12</th><th>13</th><th>14</th><th>15</th><th>16</th><th>17</th><th>18</th><th>19</th>
			<th>20</th><th>21</th><th>22</th><th>23</th></tr></thead>
			<tr>
			<td><input type="checkbox" name="schedulehours" value="0" checked></td><td><input type="checkbox" name="schedulehours" value="1" checked></td><td><input type="checkbox" name="schedulehours" value="2" checked></td>
			<td><input type="checkbox" name="schedulehours" value="3" checked></td><td><input type="checkbox" name="schedulehours" value="4" checked></td><td><input type="checkbox" name="schedulehours" value="5" checked></td>
			<td><input type="checkbox" name="schedulehours" value="6" checked></td><td><input type="checkbox" name="schedulehours" value="7" checked></td><td><input type="checkbox" name="schedulehours" value="8" checked></td>
			<td><input type="checkbox" name="schedulehours" value="9" checked></td><td><input type="checkbox" name="schedulehours" value="10" checked></td><td><input type="checkbox" name="schedulehours" value="11" checked></td>
			<td><input type="checkbox" name="schedulehours" value="12" checked></td><td><input type="checkbox" name="schedulehours" value="13" checked></td><td><input type="checkbox" name="schedulehours" value="14" checked></td>
			<td><input type="checkbox" name="schedulehours" value="15" checked></td><td><input type="checkbox" name="schedulehours" value="16" checked></td><td><input type="checkbox" name="schedulehours" value="17" checked></td>
			<td><input type="checkbox" name="schedulehours" value="18" checked></td><td><input type="checkbox" name="schedulehours" value="19" checked></td><td><input type="checkbox" name="schedulehours" value="20" checked></td>
			<td><input type="checkbox" name="schedulehours" value="21" checked></td><td><input type="checkbox" name="schedulehours" value="22" checked></td><td><input type="checkbox" name="schedulehours" value="23" checked></td></td>
			</tr>
			</table>
		</tr>
		<tr><th>起</th><td><input id = "scheduleStart" type = "text" autocomplete="off"></input></td></tr>
		<tr><th>訖</th><td><input id = "scheduleEnd" type = "text" autocomplete="off"></input></td></tr>
	</table>
	<button id = "scheduleSumit">確認</button>
</div>

<script>
	var ajaxtodbPath="../ajaxToDB_Order.php";
	var ajaxtodbPath_v2="ajaxToDB_NewOrderList.php";
	var adOwner = <?=$adOwner?>;
	var 委刊單名稱 = '<?=$orderListName?>';
	var oid = '<?=$oid?>';
	var pageAction="<?=$pageAction;?>";
	if(pageAction =="new"){
		$("#orderListIdRow").remove();
		$("#ownerId").text("<?php if(isset($_GET['ownerid'])) echo htmlspecialchars($_GET['ownerid'], ENT_QUOTES, 'UTF-8');?>");
		$('#ownerName').text(getAdOwnerName($("#ownerId").text()));
		委刊單名稱 = $('#ownerName').text();
	}
	else if(pageAction=="info"){
		$("button").hide();
		$("input").prop('disabled', true);
	}
	
	//*********委刊單排程設定相關
	//設定版位類型選項
	$.post('../orderManaging.php',{method:'getPositionTypeSelection'}
		,function(positionTypeOption){
			for(var i in positionTypeOption){
				var opt = $(document.createElement("option"));
				opt.text(positionTypeOption[i][1])//紀錄版位類型名稱
				.val(positionTypeOption[i][0])//紀錄版位類型識別碼
				.appendTo($("#schedulePositiontype"));
			}
			setPosition($( "#schedulePositiontype option:selected" ).val(),"");
			
			$( "#schedulePositiontype" ).combobox({
				 select: function( event, ui ) {
					setPosition(this.value,"");
				}
			});
		}
		,'json'
	);
	//設定版位資料
	function setPosition(pTId,selectedIds){
		$('#schedulePositions').attr('lock',true);
		$.ajax({
				async: false,
				type : "POST",
				url : ajaxtodbPath,
				data: { action: "getPositionByPositionType",版位類型識別碼:pTId},
				dataType : 'json',
				success :
					function( json ) {
						$select = $('#schedulePositions');
						$select.empty();
						for(var i in json){
							$(document.createElement("option")).text(json[i]['版位識別碼']+":"+json[i]['版位名稱'])
							.val(json[i]['版位識別碼'])
							.appendTo($select);
						}
						$('#schedulePositions').tokenize().clear();
						//**多選 版位多選設訂
						$('#schedulePositions').tokenize({
							placeholder:"輸入識別碼或關鍵字該版位類型下的版位"
							,displayDropdownOnFocus:true
							,newElements:false
							,onAddToken:function(value, text, e){
								console.log("addd");
								setSCNPosition([value],"#schedulePositions");
							}
							,onRemoveToken:function(value, e){
								removeSCNPosition([value],"#schedulePositions");
							}
						});	
						if(selectedIds!="")
							setSelectedPosition(selectedIds)
						$('#schedulePositions').attr('lock',false);
					}
			});
	}
	//設置預設版位
	function setSelectedPosition(selectedIds){	
		$('#schedulePositions>option').each(function(){
			for(var i in selectedIds)
			if($(this).val()==selectedIds[i]){
				$('#schedulePositions').data('tokenize').tokenAdd($(this).val(),$(this).text());
			}
		})
		$('#schedulePositions').val(selectedIds);
	}
	//設定投放類型選項
	$.post('',{action:'getPostTypeSelection'}
		,function(options){
			for(var i in options){
				var opt = $(document.createElement("option"));
				opt.text(options[i][1])//紀錄版位類型名稱
				.val(options[i][0])//紀錄版位類型識別碼
				.appendTo($("#schedulePostType"));
			}
			$( "#schedulePostType" ).combobox();
		}
		,'json'
	);
	//排程開始/結束
	$( "#scheduleStart" ).datetimepicker({	
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		onClose: function( selectedDate ) {
			$( "#scheduleEnd" ).datepicker( "option", "minDate", selectedDate );
		},
	});
	$( "#scheduleEnd" ).datetimepicker({
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		hour: 23,
		minute: 59,
		second: 59,
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		onClose: function( selectedDate ) {
			$( "#scheduleStart" ).datepicker( "option", "maxDate", selectedDate );
		}
	});
	//委刊單排程設定視窗
	$( "#orderSchedule_dialog_form" ).dialog(
			{autoOpen: false,
			width: 650,
			height: 450,
			modal: true,
			title: '設定委刊單版位排程'
	});
	//新增委刊單排程按鈕
	$("#newOrderSchedule").click(function(){
		clearScheduleDialog();
		$('#orderSchedule_dialog_form').dialog('open');
	});
	
	//排程時段全選按鈕
	$('#scheduleAllTimeBtn').click(function(){
		$('input[name="schedulehours"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//排程時段全不選按鈕
	$('#scheduleNoTimeBtn').click(function(){
		$('input[name="schedulehours"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	//提交排程設定按鈕
	$("#scheduleSumit").click(function(){
		//取得各設定參數
		var hours=getHoursByName("schedulehours");
		var scheduleData={
			scheduleTempId:$("#scheduleTempId").val(),
			schedulePositiontype:$('#schedulePositiontype').val(),
			schedulePositiontypeText:$('#schedulePositiontype option:selected').text(),
			schedulePositions:$('#schedulePositions').val(),
			schedulePostType:$('#schedulePostType').val(),
			schedulePostTypeText:$('#schedulePostType option:selected').text(),
			schedulePostTime:$('#schedulePostTime').val(),
			schedulehours:hours,
			scheduleStart:$('#scheduleStart').val(),
			scheduleEnd:$('#scheduleEnd').val()
		}
		if(scheduleData["scheduleTempId"]==""){
			var d = new Date();
			scheduleData["scheduleTempId"] = "schedule"+d.getTime();
			updateScheduleOnUI(scheduleData);
		}
		else{
			updateScheduleOnUI(scheduleData);
		}
		$('#orderSchedule_dialog_form').dialog('close');
	});
	
	//在UI更新排程
	function updateScheduleOnUI(scheduleData){
		var sid = scheduleData["scheduleTempId"];
		if($("#"+sid).length == 0){
			var appendRow = '<tr id = "'+sid+'"></tr>'
			$("#orderScheduleTableBody").append(appendRow);
		}
		$("#"+sid).empty();
		var append = 
		"<td>"+scheduleData["schedulePositiontypeText"]+"</td>"
		+"<td>"+scheduleData["schedulePositions"].length+"</td>"
		+"<td>"+scheduleData["schedulePostTypeText"]+"</td>"
		+"<td>"+scheduleData["schedulePostTime"]+"</td>"
		+"<td>"+scheduleData["schedulehours"]+"</td>"
		+"<td>"+scheduleData["scheduleStart"]+"</td>"
		+"<td>"+scheduleData["scheduleEnd"]+"</td>"
		
		
		if(pageAction=="info"){
			append=append+'<td><button id="'+sid+'_Update" scheduleTempId = '+sid+'>查看</button><input type="text" id = "'+sid+'_jsonvaule"></input></td>'
			+'<td></td>'
		}
		else{
			append=append+'<td><button id="'+sid+'_Update" scheduleTempId = '+sid+'>修改</button><input type="text" id = "'+sid+'_jsonvaule"></input></td>'
			+'<td><button id="'+sid+'_Remove" scheduleTempId = '+sid+'>刪除</button></td>'
		}
		$("#"+sid).append(append);
		$('#'+sid+'_jsonvaule').val(JSON.stringify(scheduleData)).hide();
		$('#'+sid+'_jsonvaule').val(JSON.stringify(scheduleData)).hide();
		$('#'+sid+'_Update').click(function(){
			var scheduleTempId = $(this).attr('scheduleTempId');
			var json = JSON.parse($('#'+scheduleTempId+'_jsonvaule').val());
			openUpdateScheduleDialog(json);
		});
		$('#'+sid+'_Remove').click(function(){
			var scheduleTempId = $(this).attr('scheduleTempId');
			removeSchedule(scheduleTempId);
		});
	
	}
	//打開更新排程的dialog(先設定好預設值在打開)
	function openUpdateScheduleDialog(scheduleData){
		//clearScheduleDialog();
		//塞入預設值
		$("#scheduleDbId").val(scheduleData["scheduleDbId"]);
		$("#scheduleTempId").val(scheduleData["scheduleTempId"]);
		$('#schedulePositiontype').combobox('setText', scheduleData["schedulePositiontypeText"]);
		$('#schedulePositiontype').val(scheduleData["schedulePositiontype"]);
		$('#schedulePostType').combobox('setText', scheduleData["schedulePostTypeText"]);
		$('#schedulePostType').val(scheduleData["schedulePostType"]);
		setPosition(scheduleData["schedulePositiontype"],scheduleData["schedulePositions"]);
		$('#schedulePostTime').val(scheduleData["schedulePostTime"]),
		setHoursByName(scheduleData["schedulehours"],"schedulehours")
		$('#scheduleStart').val(scheduleData["scheduleStart"]),
		$('#scheduleEnd').val(scheduleData["scheduleEnd"])
		$('#orderSchedule_dialog_form').dialog('open');
		
	}	
	
	//移除排程
	function removeSchedule(scheduleTempId){
		$("#"+scheduleTempId+"").remove();
	}
	
	//清空排程設定視窗的資料
	function clearScheduleDialog(){
		$("#scheduleTempId").val("");
		setPosition($('#schedulePositiontype').val(),"");
		$('#schedulePostTime').val(""),
		setHoursByName("0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23","schedulehours")
		$('#scheduleStart').val(""),
		$('#scheduleEnd').val("")
	}
	
	//***********委刊單資料顯示相關
	
	//廣告主自動完成
	$("#ownerName").autocomplete({
		source :function( request, response ) {
					$.post( "../../adowner/autoCompleteSearch.php",{term: request.term, input: "廣告主名稱"},
						function( data ) {
						//alert(data);
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			id = ui.item.id;
			$.post("../../adowner/autoCompleteSearch.php",{"getData":"廣告主資料","廣告主識別碼":id},
			function(data){
				$("#ownerName").val(data["廣告主名稱"]);
				$("#ownerVAT").text(data["廣告主統一編號"]);
				$("#ownerId").text(id);
			}
			,'json');
		}
	});
	//代理商自動完成
	$("#agentName").autocomplete({
		source :function( request, response ) {
					$.post( "../../adowner/autoCompleteSearch.php",{term: request.term, input: "代理商名稱"},
						function( data ) {
						//alert(data);
						response(JSON.parse(data));
					})
				}
		,select : function (event, ui) {
			id = ui.item.id;
			$.post("../../adowner/autoCompleteSearch.php",{"getData":"代理商資料","代理商識別碼":id},
			function(data){
				$("#agentName").val(data["代理商名稱"]);
				$("#agentVAT").text(data["代理商統一編號"]);
				$("#agentId").text(id);
			}
			,'json');
		}
	});
	//
	//委刊單檔期開始/結束
	$( "#orderListStartTime" ).datetimepicker({	
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		onClose: function( selectedDate ) {
			//依選擇限制委刊單排程的日期，與委刊單檔期的結束日期
			$( "#scheduleStart" ).datepicker( "option", "minDate", selectedDate );
			$( "#scheduleEnd" ).datepicker( "option", "minDate", selectedDate );
			$( "#orderListEndTime" ).datepicker( "option", "minDate", selectedDate );
		},
	});
	$( "#orderListEndTime" ).datetimepicker({
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		hour: 23,
		minute: 59,
		second: 59,
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		onClose: function( selectedDate ) {
			//依選擇限制委刊單排程的日期，與委刊單檔期的開始日期
			$( "#scheduleEnd" ).datepicker( "option", "maxDate", selectedDate );
			$( "#scheduleStart" ).datepicker( "option", "maxDate", selectedDate );
			$( "#orderListStartTime" ).datepicker( "option", "maxDate", selectedDate );
		}
	});
	
	//委刊單名稱自動完成
	$('#orederListName').autocomplete({
		source :function( request, response ) {
					$.post( "../../order/newOrderList.php",{action:'autocompleteSearch', term: request.term, column:'委刊單名稱'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	//委刊單說明自動完成
	$('#orderListInfo').autocomplete({
		source :function( request, response ) {
					$.post( "../../order/newOrderList.php",{action:'autocompleteSearch', term: request.term, column:'委刊單說明'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	
	
	clearInput();
	
	function getAdOwnerName(adOnwerId){
		for(var i in adOwner){
			if(adOwner[i]['廣告主識別碼']==parseInt(adOnwerId))
				return adOwner[i]['廣告主名稱'];
		}
		return '';
	}
	
	//顯示資料
	function showInfo(data){
		//顯示基本資訊
		$("#orderListId").text(data['委刊單識別碼']);
		$("#ownerName").val(data["廣告主"]["廣告主名稱"]);
		$("#ownerVAT").text(data["廣告主"]["廣告主統一編號"]);
		$("#ownerId").text(data["廣告主識別碼"]);
		$("#agentName").val(data["代理商"]["代理商名稱"]);
		$("#agentVAT").text(data["代理商"]["代理商統一編號"]);
		$("#agentId").text(data["代理商識別碼"]);
		$("#orederListName").val(data["委刊單名稱"]);
		$("#orderListInfo").val(data["委刊單說明"]);
		$("#orderListPrice").val(data["售價"]);
		$("#orderListStartTime").val(data["檔期開始日期"]);
		$("#orderListEndTime").val(data["檔期結束日期"]);
		$("#materialPath").val(data["素材連結位置"]);
		$("#note").val(data["備註"]);
		//顯示排程資訊
		for(var i in data["委刊單排程"]){
			var oringScheData = data["委刊單排程"][i];
			var d = new Date();
			
			var scheduleData={
				scheduleTempId:"schedule"+d.getTime(),
				schedulePositiontype:oringScheData["版位類型識別碼"],
				schedulePositiontypeText:oringScheData["版位類型名稱"],
				schedulePositions:oringScheData["複數版位識別碼"].split(','),
				schedulePostType:oringScheData["委刊單排程投放方式識別碼"],
				schedulePostTypeText:oringScheData["委刊單排程投放方式名稱"],
				schedulePostTime:oringScheData["播放次數"],
				schedulehours:oringScheData["廣告可被播出小時時段"],
				scheduleStart:oringScheData["廣告期間結束時間"],
				scheduleEnd:oringScheData["廣告期間開始時間"]
			}
			updateScheduleOnUI(scheduleData);
		}
		
	}
	
	//******資料相關
	//清理or還原資料
	function clearInput(){
		$(":text").val("");
		$("#orederListName").val(委刊單名稱);
		if (pageAction=="edit" || pageAction=="info"){
			getDbInfo(oid);
		}
	}
	
	//從資料庫取得資料
	function getDbInfo(id){
		$.post("ajaxToDB_GetOrderList.php",{"method":"取得委刊單","委刊單識別碼":id},
		function(ajaxback){
			showInfo(ajaxback["data"]);
		}
		,"json"
		)
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
		if(pageAction=="new"){
			newOrderList();
		}else if(pageAction="edit"){
			editOrderList();
		}
	}
	
	//新增委刊單資料
	function newOrderList(){
		//委刊單基本資訊
		OrderListData = getOrderListData();
		$.post(ajaxtodbPath_v2,{"method":"新增委刊單","orderLists":OrderListData}
		,function (data){
			if(data['success']){
				
			}
			else{
				
			}
			alert(data["message"]);
		}
		,'json'
		)
		
	}
	
	function editOrderList(){
		//委刊單基本資訊
		OrderListData = getOrderListData();
		$.post(ajaxtodbPath_v2,{"method":"更新委刊單","orderLists":OrderListData}
		,function (data){
			if(data['success']){
				
			}
			else{
				
			}
			alert(data["message"]);
		}
		,'json'
		)
	}
	
	//依照網頁元素checkbox的name取得播放小時字串
	function getHoursByName(name){
		var hours="";
		var temp=new Array();
		$('input[name="'+name+'"]:checked').each(function(){temp.push($(this).val());});
		hours=temp.join(',');
		return hours;
	}
	
	//依播放小時字串設定網頁元素checkbox，利用nam指定特定checkbox
	function setHoursByName(hours,name){
		for(var i =0;i<24;i++)
			$('input[name="'+name+'"]')[i].checked = false;
			
		if(hours!=""){
			var hoursArray = hours.split(",");
			for(var i in hoursArray){
				$('input[name="'+name+'"]')[hoursArray[i]].checked = true;
			}
		}
	}
	
	//取得目前頁面上的委刊單設定資訊
	function getOrderListData(){
		//基本資訊
		var editId = oid==""?null:oid;
		var orderListData = {
			"委刊單識別碼" : oid,
			"廣告主識別碼" : $("#ownerId").text(),
			"代理商識別碼" : $("#agentId").text(),
			"委刊單名稱" : $("#orederListName").val(),
			"委刊單說明" : $("#orderListInfo").val(),
			"售價":$("#orderListPrice").val(),
			"委刊單編號":$("#委刊單編號").val(),
			"檔期開始日期":$("#orderListStartTime").val(),
			"檔期結束日期":$("#orderListEndTime").val(),
			"素材連結位置":$("#materialPath").val(),
			"備註":$("#note").val()
		};
		//排程資訊
		var schedules = [];
		$("#orderScheduleTableBody>tr").each(function(){
			var scheduleTempId = $(this).attr("id")
			var scheduleData = JSON.parse($("#"+scheduleTempId+"_jsonvaule").val());
			//整理複數版位識別碼
			var positions = scheduleData["schedulePositions"].join(',');
			var pushData={
				"委刊單排程識別碼":scheduleData["scheduleDbId"]==""?null:scheduleData["scheduleDbId"],
				"版位類型識別碼":scheduleData["schedulePositiontype"],
				"複數版位識別碼":positions,
				"委刊單排程投放方式識別碼":scheduleData["schedulePostType"],
				"廣告期間開始時間":scheduleData["scheduleStart"],
				"廣告期間結束時間":scheduleData["scheduleEnd"],
				"廣告可被播出小時時段":scheduleData["schedulehours"],
				"播放次數":scheduleData["schedulePostTime"]
			}
			schedules.push(pushData);
		});
		orderListData["委刊單排程"]=schedules;
		
		return orderListData;
	}
 </script>
 
 
</body>
</html>