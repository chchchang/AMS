<?php 	
	include('../tool/auth/auth.php');
	//動作類型
	if(isset($_GET["edit"]))
		$action =  "edit";
	else if(isset($_GET["info"]))
		$action =  "info";
	else if(isset($_GET["update"]))
		$action =  "update";
	else if(isset($_GET["orderInDb"]))
		$action =  "orderInDb";
	else if(isset($_GET["orderFromApi"]))
		$action =  "orderFromApi";
	else
		$action =  "new";
	
	//取得版位名稱以及初始參數
	$changedOrderId=0;
	if(isset($_GET["update"])) 
		$changedOrderId=htmlspecialchars($_GET["update"], ENT_QUOTES, 'UTF-8');
	else if(isset($_GET["edit"]))
		$changedOrderId=htmlspecialchars($_GET["edit"], ENT_QUOTES, 'UTF-8');
	else if(isset($_GET["orderInDb"]))
		$changedOrderId=htmlspecialchars($_GET["orderInDb"], ENT_QUOTES, 'UTF-8');
	else if(isset($_GET["orderFromApi"]))
		$changedOrderId=htmlspecialchars($_GET["orderFromApi"], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.tokenize.js"></script>
<script type="text/javascript" src="../order/newOrder_852.js?<?=time()?>"></script>
<script type="text/javascript" src="../order/newOrder_851.js?<?=time()?>"></script>
<script src="../WebConfig.js"></script>
<script src="../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-plugin/jquery.tokenize.css" />
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<style type="text/css">
  	.Center{
		position:absolute;
		left:50%;
	}
	button{
		margin-right:5px; 
		margin-left:5px; 
	}
	input[type=file]::-webkit-file-upload-button {
		width: 0;
		padding: 0;
		margin: 0;
		-webkit-appearance: none;
		border: none;
		border:0px;
	}
	x::-webkit-file-upload-button, input[type=file]:after {
		content:'選擇';
		left: 100%;
		margin-left:3px;
		position: relative;
		-webkit-appearance: button;
		padding: 3px 8px 3px;
		border:0px;
	}
	input[type=file]{
		margin-right:45px;
	}
	#playTime thead tr th,#playTime thead tr td,#playTime tbody tr th,#playTime tbody tr td{
		text-align: center;
		background-color: #DDDDDD;
		width:20px;
		height:20px;
	}
	#positiontype,#position,#版位有效起始時間,#版位有效結束時間{
		text-decoration:underline;
		margin-left:3px;
		margin-right:10px;
	}
	.tokenize{ width: 300px }
</style>

</head>
<body>
<h4 id="message"> </h4>
<div id = 'mainFram'>
<fieldset  style="clear: both;">
<legend>銷售前預約託播單必須資訊</legend>
	<button id ='copyOrder' onClick = 'selectOrderFun()' class = 'darkButton'>填入現有託播單資訊</button>
	<table width = '100%' class='styledTable2'>
	<tr><th>版位類型*:</th><td><select id="positiontypeSelection" class = 'combobox'></select><a id="positiontype" hidden></a></td></tr>
	<tr><th>版位名稱*:</th><td><select id="position"  multiple="multiple"  class ="tokenize"></select></td></tr>
	<tr><th>託播單期間*:</th><td>
		<table><thead><tr><th>開始</th><th>結束</th><th></th></thead></tr><tbody id = 'durationTb'>
			<tr><td><input id = "StartDate" type="text" value = "" class ="nonNull"></td><td><input id = "EndDate" type="text" value = "" class ="nonNull"></td><td></td></tr>
			</tbody>
		</table>
		</td>
	</tr>
	<tr><th>託播單時段*:</th><td><div  id = "playTimeCombinations"></div>
			<table border ="0" id = "playTime">
			<thead><tr><th>0</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>9</th>
			<th>10</th><th>11</th><th>12</th><th>13</th><th>14</th><th>15</th><th>16</th><th>17</th><th>18</th><th>19</th>
			<th>20</th><th>21</th><th>22</th><th>23</th></tr></thead>
			<tbody><tr>
			<td><input type="checkbox" name="hours" value="0" checked></td><td><input type="checkbox" name="hours" value="1" checked></td><td><input type="checkbox" name="hours" value="2" checked></td>
			</td><td><input type="checkbox" name="hours" value="3" checked></td><td><input type="checkbox" name="hours" value="4" checked></td><td><input type="checkbox" name="hours" value="5" checked></td>
			</td><td><input type="checkbox" name="hours" value="6" checked></td><td><input type="checkbox" name="hours" value="7" checked></td><td><input type="checkbox" name="hours" value="8" checked></td>
			</td><td><input type="checkbox" name="hours" value="9" checked></td><td><input type="checkbox" name="hours" value="10" checked></td><td><input type="checkbox" name="hours" value="11" checked></td>
			</td><td><input type="checkbox" name="hours" value="12" checked></td><td><input type="checkbox" name="hours" value="13" checked></td><td><input type="checkbox" name="hours" value="14" checked></td>
			</td><td><input type="checkbox" name="hours" value="15" checked></td><td><input type="checkbox" name="hours" value="16" checked></td><td><input type="checkbox" name="hours" value="17" checked></td>
			</td><td><input type="checkbox" name="hours" value="18" checked></td><td><input type="checkbox" name="hours" value="19" checked></td><td><input type="checkbox" name="hours" value="20" checked></td>
			</td><td><input type="checkbox" name="hours" value="21" checked></td><td><input type="checkbox" name="hours" value="22" checked></td><td><input type="checkbox" name="hours" value="23" checked></td>
			</tr></tbody>
			</table>
	</td></tr>
	</table>
</fieldset>
<fieldset  style="clear: both;">
    <legend>轉為正式預約託播單必須資訊</legend>
	<table width = '100%' class='styledTable2'>
	<tr><th>廣告主:</th><td><select id="adOwner" class = 'combobox'></select></td></tr>
	<tr><th>委刊單:</th><td><select id="orderList" class = 'combobox'></select></td></tr>
	<tr><th>託播單名稱:</th><td><input id = "Name" type="text" value = "" size="38"></td></tr>
	<tr><th>預約到期日期:</th><td><input id = "Deadline" type="text" value = "" size="15" ></td></tr>
	</table>
</fieldset>
<fieldset  style="clear: both;">
    <legend>其他託播單資訊</legend>
	<table width = '100%' class='styledTable2'>
		<tr><th>託播單說明:</th><td><input id = "Info" type="text" value = "" size="38"></td></tr>
		<tr><th>售價:</th><td><input id="售價" type="number" value = ""></td></tr>
	</table>
</fieldset>
	
	<fieldset  style="clear: both;">
	<legend>其他參數</legend>
	<table width = '100%' class='styledTable2'>
		<thead><tr><th>參數名稱</th><th>類型</th><th>必填</th><th>是否新增</th><th>內容</th></tr></thead>
		<tbody id = 'configTbody'></tbody>
	</table>
	</fieldset>
	
	<fieldset  style="clear: both;">
	<legend>素材</legend>
	<table width = '100%' class='styledTable2'>
		<thead><tr><th>順序</th><th>素材類型</th><th>必填</th><th>可否點擊</th><th>點擊開啟類型</th><th>點擊開啟位址</th><th>選擇素材</th></tr></thead>
		<tbody id = 'materialTbody'></tbody>
	</table>
	</fieldset>
		<div class ="Center"><button id="clearBtn" type="button" onclick = "clearInput()">清空</button><button id = 'saveBtn' type="button" onclick = "save()">儲存</button></div>
	</div>
	<button id = 'closeSelection' class = 'darkButton' style='float:right'>關閉選單</button>
	<iframe id ='selectOrder' width = '100%' height = '600px' style='clear:both'></iframe>
	
	<div id="material_dialog_form">
	<table class='styledTable2' width = '100%'>
		<tr><th>素材順序</th><td><a id ='選擇素材順序'></a></td></tr>
		<tr><th>素材類型</th><td><a id ='選擇素材類型'></a></td></tr>
		<tr><th><a id="materialgroup">素材群組:</a></th><td><select id="MaterialGroup"></select><button id="materialInfo" type="button" onclick = "materialInfo()">詳細資訊</button></td></tr>
		<tr><th><a>素材:</a></th><td><select id="Material"></select><button id = '選擇素材'>選擇素材</button></td></tr>
	</table>
	<table class='styledTable2' id = 'matrialConifg' width = '100%'>
		<thead>
			<tr><th>區域</th><th>狀態</th><th>開啟類型</th><th>開啟位址</th><th>套用</th></tr>
		</thead>
		<tbody id = 'matrialConifgTbody'>
		</tbody>
	</table>
	</div>
<script>
	var positionTypeId =0;
	var positionId =[];
	//判對動作是新增訂單(new)/修改暫存訂單資訊(edit)/顯示暫存訂單資訊(info)/修改舊有訂單(update)/顯示託播單資訊(orderInDb)
	action ="<?= $action?>";
	var changedOrderId = <?=$changedOrderId?>;
</script>
<?php include('../order/_newOrderUiScript.php');?>
<script>
	deadlinePreDay = 0;
	$("#selectOrder,#closeSelection").hide();
	
	clearInput = function(){
		//新增託播單:清空資訊
		if(action!='new'){
			getInfoFromDb(changedOrderId);
		}
		else{
			var jdata = {
			'託播單群組識別碼':'',
			"版位類型名稱":'',
			"版位名稱":'',
			"版位類型識別碼":positionTypeId,
			"版位識別碼":[],
			"託播單名稱":'',
			"託播單說明":'',
			"廣告期間開始時間":'',
			"廣告期間結束時間":'',
			"廣告可被播出小時時段":'0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23',
			"預約到期時間":'',
			"售價":'',
			"廣告主識別碼":'',
			"委刊單識別碼":'',
			'其他參數': otherConfigObj_default
			};
			showVal(jdata);
		}
	}
	
	//廣告主自動完成選項
	$.post('../order/newOrderByPage.php',{method:'getAdOwnerSelection'}
		,function(json){
			$(document.createElement("option")).text('').val('').appendTo($("#adOwner"));
			for(var i in json){
				var opt = $(document.createElement("option"));
				opt.text(json[i]['廣告主識別碼']+":"+json[i]['廣告主名稱'])
				.val(json[i]['廣告主識別碼'])
				.appendTo($("#adOwner"));
			}
			
			$( "#adOwner" ).combobox({
				 select: function( event, ui ) {
					setOrderListSelection(this.value,"");
				 }
			});
		}
		,'json'
	);
	
	//委刊單自動完成選項
	$('#orderList').combobox();
	function setOrderListSelection(ownerId,selectedId){
		$('#orderList').html('');
		$.post('../order/newOrderByPage.php',{method:'getOrderListSelection',ownerId: ownerId}
		,function(json){
			$(document.createElement("option")).text('').val(null).appendTo($("#orderList"));
			for(var i in json){
				var opt = $(document.createElement("option"));
				opt.text(json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱'])
				.val(json[i]['委刊單識別碼'])
				.appendTo($("#orderList"));
				if(selectedId == json[i]['委刊單識別碼']){
					$( "#orderList" ).combobox('setText',json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱']);
					$( "#orderList" ).val(json[i]['委刊單識別碼']);
				}
			}
			if(selectedId==''){
				$( "#orderList" ).combobox('setText','');
				$( "#orderList" ).val(null);
			}
		}
		,'json'
		);
	}
	//版位類型自動完成選項
	$.post('../order/orderManaging.php',{method:'getPositionTypeSelection'}
		,function(positionTypeOption){
			$(document.createElement("option")).text('').val('').appendTo($("#positiontypeSelection"));
			for(var i in positionTypeOption){
				var opt = $(document.createElement("option"));
				opt.text(positionTypeOption[i][0]+":"+positionTypeOption[i][1])//紀錄版位類型名稱
				.val(positionTypeOption[i][0])//紀錄版位類型識別碼
				.appendTo($("#positiontypeSelection"));
			}
			
			$( "#positiontypeSelection" ).combobox({
				 select: function( event, ui ) {
					positionTypeId = $('#positiontypeSelection').val();
					initialPositionSetting(positionTypeId);
					setPosition(positionTypeId,'');
				 }
			});
			
			if($("#positiontypeSelection").attr('selectedId')!='undefined'){
				var sid = $("#positiontypeSelection").attr('selectedId');
				$("#positiontypeSelection option[value="+sid+"]").prop('selected',true);
				$( "#positiontypeSelection" ).combobox('setText',$("#positiontypeSelection option[value="+sid+"]").text())
				.val(sid);
			}
			$("#positiontypeSelection").trigger('positiontype_iniDone');
			
		}
		,'json'
	);
	
	//複寫showVal(data)
	var oldShowVal = showVal;
	showVal = function(data){
		//設定選擇的版位
		$('#positiontypeSelection>option').each(function(){
			if($(this).val()==data['版位類型識別碼']){
				$(this).prop('selected',true);
				$( "#positiontypeSelection" ).combobox('setText',$(this).text());
				$( "#positiontypeSelection" ).val($(this).val());
			}
		});
		//設定選擇的廣告主
		$('#adOwner>option').each(function(){
			if($(this).val()==data['廣告主識別碼']){
				$(this).prop('selected',true);
				$( "#adOwner" ).combobox('setText',$(this).text());
				$( "#adOwner" ).val($(this).val());
			}
		});
		//設定選擇的委刊單
		setOrderListSelection($('#adOwner').val(),data['委刊單識別碼']);
		oldShowVal(data);
		if(action !='new')
		$("#positiontypeSelection").combobox('disable');
	}
	
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
		
		var exit = false;
		$('#durationTb tr').each(function(){
			var stt = $(this).find(' input:eq(0)').val();
			var edt = $(this).find(' input:eq(1)').val();
			if(stt>edt){
				exit = true;
				alert("起始時間 必須小於 結束時間");
				return 0;
			}
			if($("#Deadline").val()!='' && $("#Deadline").val()+" 00:00:00">edt){
				exit = true;
				alert("預約到期時間 必須小於等於 結束日期");
				return 0;
			}
		});
		
		if(exit)
			return 0;
		
		//若為CSMS類型託播單，託播單名稱不可包含"\'"字元
		switch($("#positiontype").text()){
			case "首頁banner":
			case "專區banner":
			case "頻道short EPG banner":
			case "專區vod":
			if($("#Name").val().indexOf("'") != -1)
			{
				alert("CSMS類型託播單名稱不可包含「'」符號");
				return 0;
			}
			break;
		}
		
		//將選擇的小時時段轉為ARRAY
		var hours="";
		var temp=new Array();
		$('input[name="hours"]:checked').each(function(){temp.push($(this).val());});
		hours=temp.join(',')
	
		if(hours=="" ){
			alert("請勾選播出時段");
			return 0;
		}
		
		var StartDate= $("#StartDate").val();
		var EndDate= $("#EndDate").val();
		
		//**多選
		if (!$.isArray($('#position').val())||$('#position').val().length==0){
			alert('請至少選擇一個版位');
			return 0;
		}
		
		//逐一檢查版位時間試否可以播放
		$('body').mask('產生託播單中...');
		var positionArray=$('#position').val();
		fail=[];
		$.ajax({
			async: false,
			type : "POST",
			url : '../order/newOrder.php',
			data: {版位有效時間:positionArray},
			dataType : 'json',
			success :
				function(data){
					for(var i in data){
						$('#durationTb tr').each(function(){
							var stt = $(this).find(' input:eq(0)').val();
							var edt = $(this).find(' input:eq(1)').val();
							var startLimit = (data[i]["版位有效起始時間"]==null)?data[i]["版位類型有效起始時間"]:data[i]["版位有效起始時間"];
							var endLimit = (data[i]["版位有效結束時間"]==null)?data[i]["版位類型有效結束時間"]:data[i]["版位有效結束時間"];
							
							if(startLimit!=null)
								if(stt<startLimit){
									fail.push(data['版位名稱']);
								}
							if(endLimit!=null)
								if(edt>endLimit){
									fail.push(data['版位名稱']);
								}
						});					
					}
				}
		});
		if(fail.length>0){
			alert("託播單期間超過版位("+fail.join(',')+')的有效期間');
			return 0;
		}
		
		//更動現有託播單，檢查是否會影響連動
		if(action == 'update'){
			var check = false;
			$.ajax({
				async: false,
				type : "POST",
				url : '../order/newOrder.php',
				data: {檢察連動更動:true,託播單識別碼:changedOrderId,StartDate:StartDate,EndDate:EndDate,廣告可被播出小時時段:hours},
				dataType : 'json',
				success :
					function(data){
						check = data.success;
						if(!check)
							alert(data.message);
					}
			});
			if(!check)
			return 0;
		}
		
		//取得每個版位的託播單
		
		var orders=[];	
		$('#durationTb tr').each(function(){
				var stt = $(this).find(' input:eq(0)').val();
				var edt = $(this).find(' input:eq(1)').val();
			$("#position option:selected").each(function() {
				var pid = $(this).val();
				var pname = $(this).text().split(':');
				pname.splice(0,1)
				pname=pname.join(':');				
				var order = getOrderObj(pname,pid);
				order["廣告期間開始時間"] = stt;
				order["廣告期間結束時間"] = edt;
				order["群組廣告期間開始時間"] = stt;
				order["群組廣告期間結束時間"] = edt;
				orders.push(order);
			});					
		});		

		function getOrderObj(pname,pid){	
			var jobject = {
				"委刊單識別碼":$('#orderList').val(),
				"版位類型名稱":$("#positiontype").text(),
				"版位名稱":pname,
				"版位類型識別碼":$("#positiontypeSelection").val(),
				"版位識別碼":pid,
				"託播單名稱":$("#Name").val(),
				"託播單說明":$("#Info").val(),
				//"廣告期間開始時間":StartDate,
				//"廣告期間結束時間":EndDate,
				"廣告可被播出小時時段":hours,
				"群組廣告期間開始時間":StartDate,
				"群組廣告期間結束時間":EndDate,
				"群組廣告可被播出小時時段":hours,
				"預約到期時間":($("#Deadline").val()=="")?null:$("#Deadline").val()+" 23:59:59",
				"售價":($("#售價").val()=="")?null:$("#售價").val(),
				'其他參數':{},
				'素材':materialObj
			};
	
			$.each(otherConfigObj,function(index,value){
				if($('#是否新增'+index).prop('checked')){
					jobject['其他參數'][index]=value;
				}
			});
			//專區vod自動分配頭放上限
			if($("#positiontype").text()=='專區vod'){
				$( "#configTbody tr td:first-child" ).each(
				function(){
					if(typeof(jobject['其他參數'][$(this).attr('order')])!='undefined'){
						if($(this).text()=='影片投放上限'){
							var num = parseInt(jobject['其他參數'][$(this).attr('order')],10);
							if(isNaN(num))
								jobject['其他參數'][$(this).attr('order')]= jobject['其他參數'][$(this).attr('order')]==null?null:'';
							else
								jobject['其他參數'][$(this).attr('order')]= Math.round(num*vodPercentage[pid]['bakadDisplayMaxPercentage']);
						}
						if($(this).text()=='專區排程上限'){
							var num = parseInt(jobject['其他參數'][$(this).attr('order')],10);
							if(isNaN(num))
								jobject['其他參數'][$(this).attr('order')]= '';
							else
								jobject['其他參數'][$(this).attr('order')]= Math.round(num*vodPercentage[pid]['bakadschdDisplayMaxPercentage']);
						}
					}
					
				}
				);
			}
			if($('#csmsGroupID').text()!='')
				jobject['託播單CSMS群組識別碼'] = $('#csmsGroupID').text();
			return jobject;
		}
		$('body').unmask();
		//檢查素材設定
		$.post("../order/ajaxToDB_Order.php",{"action":"檢察素材","orders":JSON.stringify([orders])},
			function(data){
				if(data["success"]){
					//儲存動作
					if(action == 'new')
					$.post('ajaxToDB_Booking.php',{'action':'新增銷售前預約託播單','orders':JSON.stringify(orders)},
						function(data){
							alert(data['message']);
						}
						,'json'
					);
					else if(action == 'update'){
						orders[0]['託播單識別碼'] = changedOrderId; 
						$.post('ajaxToDB_Booking.php',{'action':'修改銷售前預約託播單','orders':JSON.stringify(orders)},
						function(data){
							alert(data['message']);
						}
						,'json'
						);
					}
				}
				else
					alert(data.message);
			}
			,'json'
		);
	}
	
	clearInput();
</script>
 
 
</body>
</html>