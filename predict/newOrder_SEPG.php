<?php 	
	include('../tool/auth/authAJAX.php');
	if(isset($_POST['method'])){
		//廣搞主SELECTION的選項
		if($_POST['method']=='getAdOwnerSelection'){
			$sql = 'SELECT 廣告主名稱,廣告主識別碼 FROM 廣告主 WHERE DELETED_TIME IS null AND DISABLE_TIME IS null';
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}	
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$feedBack= array();
			while($row=$res->fetch_assoc()){
				$feedBack[]=$row;
			}
			echo json_encode($feedBack,JSON_UNESCAPED_UNICODE);
			exit;
		}
		//委刊單SELECTION的選項
		else if($_POST['method']=='getOrderListSelection'){
			$sql = 'SELECT 委刊單名稱,委刊單識別碼 FROM 委刊單 WHERE 廣告主識別碼=?';
			
			if(!$stmt=$my->prepare($sql)) {;
				exit('無法準備statement，請聯絡系統管理員！');
			}
						
			if(!$stmt->bind_param('i',$_POST["ownerId"])) {
				exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			$feedBack= array();
			while($row=$res->fetch_assoc()){
				$feedBack[]=$row;
			}
			echo json_encode($feedBack,JSON_UNESCAPED_UNICODE);
			exit;
		}
		//走期是否可符合板位設定 輸入:版位識別碼 StartDate EndDate
		else if($_POST['method']=='版位走期檢察'){
			$sql = 'SELECT 版位有效結束時間,版位有效起始時間,版位類型有效起始時間,版位類型有效結束時間 
				FROM 版位,版位類型 
				WHERE 版位識別碼 = ? AND 版位.版位類型識別碼 = 版位類型.版位類型識別碼
				';
			$result=$my->getResultArray($sql,'s',$_POST['版位識別碼']);
			$row=$result[0];
			$positonOption = array();
			$positonOption['StartDate'] = ($row['版位有效起始時間']==NULL)?$row['版位類型有效起始時間']:$row['版位有效起始時間'];
			$positonOption['EndDate'] = ($row['版位有效結束時間']==NULL)?$row['版位類型有效結束時間']:$row['版位有效結束時間'];
			if($positonOption['StartDate']!=NULL&&$positonOption['StartDate']>$_POST['StartDate'])
				exit(json_encode(array('success'=>false,'message'=>'版位走期無法涵蓋託播單走期'),JSON_UNESCAPED_UNICODE));
			if($positonOption['EndDate']!=NULL&&$positonOption['EndDate']<$_POST['EndDate'])
				exit(json_encode(array('success'=>false,'message'=>'版位走期無法涵蓋託播單走期'),JSON_UNESCAPED_UNICODE));
			exit(json_encode(array('success'=>true,'message'=>'success'),JSON_UNESCAPED_UNICODE));
		}
		//依照頻道號碼取得北中南全部版位
		else if($_POST['method']=='頻道取得版位'){
			$sql = 'SELECT 版位識別碼,版位名稱
				FROM 版位
				WHERE 
				';
			$sqlWhere=[];
			$typeDefString = '';
			$channelName=[];
			foreach($_POST['頻道'] as $channel => $value){
				$sqlWhere[] = '版位名稱 LIKE ?';
				$typeDefString .='s';
				$channelName[] = $channel.'__';
			}
			$sql .= ' ( '.implode(' OR ',$sqlWhere).' ) ORDER BY 版位名稱';
			$para =[$sql,$typeDefString];
			$para = array_merge($para,$channelName);
			$result=call_user_func_array(array($my,'getResultArray',),$para);
			
			$feedback=[];
			foreach($result as $row){
				$cn =mb_substr($row['版位名稱'],0,-2,'utf8');
				if(!isset($feedback[$cn]))
					$feedback[$cn]=[];
				$feedback[$cn][]=$row;
			}
			exit(json_encode(array('success'=>true,'data'=>$feedback),JSON_UNESCAPED_UNICODE));
		}
		exit;
	}
		//是否為banner類型託播單
	$sql = 'SELECT 版位識別碼
		FROM 版位
		WHERE 版位名稱 = "頻道short EPG banner"
		';
	$result=$my->getResultArray($sql);
	if($result===false)
		exit('取得使用資料過程中發生錯誤！');
		
	$row=$result[0];
	
	$positionTypeId=$row['版位識別碼'];
	$positionId=0;
	$saveBtnText = '新增託播單';
	if(isset($_GET["positionId"])) 
		$positionId=htmlspecialchars($_GET["positionId"], ENT_QUOTES, 'UTF-8'); 
	if(isset($_GET["positionTypeId"])) 
		$positionTypeId=htmlspecialchars($_GET["positionTypeId"], ENT_QUOTES, 'UTF-8'); 
	if(isset($_GET["saveBtnText"])) 
		$saveBtnText=htmlspecialchars($_GET["saveBtnText"], ENT_QUOTES, 'UTF-8');
	$orderObject=htmlspecialchars($_GET["orderObject"], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.tokenize.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-plugin/jquery.tokenize.css" />
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css"></link>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<script type="text/javascript" src="../order/newOrder_851.js?<?=time()?>"></script>
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
廣告主:<select id="adOwner"></select> 委刊單:<select id="orderList" ></select>
<div id ='tabs'>
<ul id ='tabs_uls'>
</ul>
</div>
<fieldset  style="clear: both;">
    <legend>新增託播單資訊</legend>
		<table width = '100%' align="right" class='styledTable2'>
		<tr><th>託播單名稱*:</th><td> <input id = "Name" type="text" value = "" size="38" class ="nonNull"><button id ='copyOrder' onClick = 'selectOrderFun()' class = 'darkButton'>填入現有託播單資訊</button></td></tr>
		<tr><th>託播單說明:</th><td><input id = "Info" type="text" value = "" size="38"></td></tr>
		<tr><th>預約到期日期*:</th><td><input id = "Deadline" type="text" value = "" size="15" class ="nonNull"> </td></tr>
		<tr><th>售價: </th><td><input id="售價" type="number" value = ""></td></tr>
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
		<div class ="Center"><button id="clearBtn" type="button" onclick = "clearInput()">清空</button><button id = 'saveBtn' type="button" onclick = "save()">暫存</button></div>
</div>
<button id = 'closeSelection' class = 'darkButton' style='float:right'>關閉選單</button>
<iframe id ='selectOrder' width = '100%' height = '600px' style='clear:both'></iframe>
<div id="uploadDialog"><div id = 'uploadResult_f'></div><div id = 'uploadResult_s'></div></div>
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
	Date.prototype.yyyymmdd = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		var dd  = this.getDate().toString();
		return yyyy +'-'+ (mm[1]?mm:"0"+mm[0]) +'-'+ (dd[1]?dd:"0"+dd[0]); // padding
	};
//********設定
	var positionTypeId =<?=$positionTypeId?>;
	var orderObject =parent.<?=$orderObject?>;
	//設定各頻道的tabs
	$.post('',{method:'頻道取得版位','頻道':orderObject}
		,function(feedback){
			if(!feedback.success){
				alert(feedback.message)
				return 0 ;
			}
			
			for(var i in feedback['data']){
				$('#tabs_uls').append('<li><a href="#tab-'+i+'">'+i+'</a></li>');
				var html = '<div id="tab-'+i+'"><table width = "100%" class="styledTable2"><tbody>'
				+'<tr><th>版位:</th><td><select id="ch'+i+'"  multiple="multiple"  class ="tokenize"></select></td></tr>';
				for(var date in orderObject[i]){
					html+='<tr><th>'+date+'</th><td>時段:'+orderObject[i][date].join(',')+'</tr>';
				}
				html+='</tbody></table></div>';
				$('#tabs').append(html);
			}
			//**多選 版位多選設訂
			$('.tokenize').tokenize({
				displayDropdownOnFocus:true
				,newElements:false
			});
			
			for(var i in feedback['data']){
				for(var j in feedback['data'][i]){
					$(document.createElement("option")).text(feedback['data'][i][j]['版位識別碼']+":"+feedback['data'][i][j]['版位名稱'])
					.val(feedback['data'][i][j]['版位識別碼']+":"+feedback['data'][i][j]['版位名稱'])
					.appendTo('#ch'+i);
				}
			}
			$('.tokenize>option').each(function(){
				$(this).parent().data('tokenize').tokenAdd($(this).val(),$(this).text());
			});
			$("#tabs").tabs();
		},'json'
	);
	//選擇素材視窗
	$( "#material_dialog_form" ).dialog(
			{autoOpen: false,
			width: 400,
			height: 300,
			modal: true,
			title: '選擇素材'
	});
	//新增增加小時數的DATE prototype
	Date.prototype.addHours= function(h){
		this.setHours(this.getHours()+h);
		return this;
	}
	//增加天數
	Date.prototype.addDays= function(d){
		this.setDate(this.getDate()+d);
		return this;
	}
	
	$('#saveBtn').text('<?=$saveBtnText?>');
	$("#selectOrder,#closeSelection").hide();
	$('#closeSelection').click(function(){
		$("#selectOrder,#closeSelection").hide();
		$('#mainFram').show();
	});
	$('#uploadDialog').dialog({autoOpen: false,
		width: 400,
		height: 600,
		title: '託播單新增結果'
	});
	
	var ajaxtodbPath = "../order/ajaxToDB_Order.php";
		

	//廣告主自動完成選項
	$.post('?',{method:'getAdOwnerSelection'}
		,function(json){
			for(var i in json){
				var opt = $(document.createElement("option"));
				opt.text(json[i]['廣告主識別碼']+":"+json[i]['廣告主名稱'])
				.val(json[i]['廣告主識別碼'])
				.appendTo($("#adOwner"));
			}
			setOrderListSelection($( "#adOwner option:selected" ).val(),"");
			
			$( "#adOwner" ).combobox({
				 select: function( event, ui ) {
					setOrderListSelection(this.value,"");
				 }
			});
		}
		,'json'
	);
	
	//委刊單自動完成選項
	function setOrderListSelection(ownerId){
		$('#orderList').html('');
		$.post('?',{method:'getOrderListSelection',ownerId: ownerId}
		,function(json){
			for(var i in json){
				var opt = $(document.createElement("option"));
				opt.text(json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱'])
				.val(json[i]['委刊單識別碼'])
				.appendTo($("#orderList"));
			}
			$('#orderList').combobox();
			if(json.length>0){
				$( "#orderList" ).combobox('setText',json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱']);
				$( "#orderList" ).val(json[i]['委刊單識別碼']);
			}
			else{
				$( "#orderList" ).combobox('setText','');
				$( "#orderList" ).val(null);
			}
		}
		,'json'
		);
	}
	var otherConfigObj = {};
	var materialObj = {};
	initialPositionSetting();
	//託播單名稱自動完成搜尋
	$('#Name').autocomplete({
		source :function( request, response ) {
					$.post( "../order/autoCompleteSearch.php",{term: request.term, column:'託播單名稱', table:'託播單'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	//託播單說明自動完成搜尋
	$('#Info').autocomplete({
		source :function( request, response ) {
					$.post( "../order/autoCompleteSearch.php",{term: request.term, column:'託播單說明', table:'託播單'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	//設訂素材群組資料
	$( "#MaterialGroup" ).combobox({
		select: function( event, ui ) {
			setMaterial('');
		}
	});
	$.post('../material/ajaxFunction_MaterialInfo.php',{method:'取得素材群組'},
	function(json){
		var materialGroup=json;
		$(document.createElement("option")).text('未指定').val(0).appendTo($("#MaterialGroup"));
		for(var i in materialGroup){
			var opt = $(document.createElement("option"));
			opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
			.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
			.appendTo($("#MaterialGroup"));
		}

		$("#MaterialGroup").val(0).combobox('setText', '未指定');
	}
	,'json'
	);

	//設訂素材資料
	$("#Material").combobox({
		select:function(event,ui){
			$('#matrialConifgTbody').empty();
			if($('#Material').val()!=0)
			$.post('../order/ajaxFunction_OrderInfo.php',{'method':'素材設定資訊','素材識別碼':$('#Material').val()}
				,function(data){
					for(var i in data){
						$('<tr><td>'+data[i]['區域']+'</td><td>'+data[i]['託播單狀態名稱']+'</td><td>'+data[i]['點擊後開啟類型']+'</td><td>'+data[i]['點擊後開啟位址']+'</td>'
						+'<td><button id ="selectMateriaWithConfing'+i+'" index='+i+'>套用</button><input type="hidden" id="materialJson'+i+'"></input></td></tr>')
						.appendTo('#matrialConifgTbody');
						$('#materialJson'+i).val(JSON.stringify(data[i]));
						$('#selectMateriaWithConfing'+i).click(
							function(){
								var index = $(this).attr('index');
								var config =  $.parseJSON($('#materialJson'+index).val());
								var 素材順序 = $('#選擇素材順序').text();
								materialObj[素材順序].可否點擊 = config.可否點擊;
								materialObj[素材順序].點擊後開啟類型 = config.點擊後開啟類型;
								materialObj[素材順序].點擊後開啟位址 = config.點擊後開啟位址;
								$('#可否點擊'+素材順序).prop('checked',(materialObj[素材順序].可否點擊==1)?true:false);
								$('#點擊後開啟類型'+素材順序).val(materialObj[素材順序].點擊後開啟類型);
								$('#點擊後開啟位址'+素材順序).val(materialObj[素材順序].點擊後開啟位址);
								$('#選擇素材').trigger('click');
							}
						);
					}
				}
				,'json'
			);
		}
	});
	//素材被選擇
	$('#選擇素材').click(function(){
			$('#mBtn'+$('#選擇素材順序').text()).val($('#Material option:selected').text());
			materialObj[$('#選擇素材順序').text()]['素材識別碼']=$('#Material').val();
			var temp = $('#Material option:selected').text().split(':')
			temp.splice(0,1)
			var mName = temp.join(':');
			materialObj[$('#選擇素材順序').text()]['素材名稱']=mName;
			$('#material_dialog_form').dialog('close');
		});
	function setMaterial(selectedId){
		$.post(ajaxtodbPath,{action:'取得可用素材',版位類型識別碼:positionTypeId,素材群組識別碼:$('#MaterialGroup').val(),素材順序:$('#選擇素材順序').text()},
		function(json){
			if(json.success){
				$select = $("#Material");
				$select.empty();
				$(document.createElement("option")).text('0:未指定').val(0).appendTo($select);
				for(var i in json.material){
					var opt = $(document.createElement("option"));
					opt.text(json.material[i]["素材識別碼"]+": "+json.material[i]["素材名稱"])//紀錄版位類型名稱
					.val(json.material[i]["素材識別碼"])//紀錄版位類型識別碼
					.appendTo($select);
					if(selectedId==json.material[i]["素材識別碼"])
						$select.combobox('setText', json.material[i]["素材識別碼"]+": "+json.material[i]["素材名稱"]);
				}
				if(selectedId!=''&&selectedId!=0){
					$select.val(selectedId);
				}
				else{
					$select.val(0).combobox('setText', '0:未指定');
				}
			}
		}
		,'json'
		);
	}

	$('#message').text('新增此版位類型託播單時，會依投放系統需求拆單');
	clearInput();
	
	//取得版位參數與素材設定
	function initialPositionSetting(){
		$('#configTbody,#materialTbody').empty();
		otherConfigObj = {};
		materialObj = {};
		$.ajax({
			async: false,
			type : "POST",
			url :ajaxtodbPath,
			data: {action:'取得版位素材與參數','版位識別碼':positionTypeId},
			dataType : 'json',
			success :
			function(json){
				if(json.success){
					//設定其他參數
					for(var i in json['其他參數設定']){
						var config = json['其他參數設定'][i];
						var $tr = $('<tr/>');
						$('#configTbody').append($tr);
						$('<td id ="參數名稱'+i+'"/>').text(config['版位其他參數顯示名稱']).appendTo($tr);
						$('<td/>').text(config['參數型態顯示名稱']).appendTo($tr);
						$('<td/>').text((config['版位其他參數是否必填']==0)?'否':'是').appendTo($tr);
						$('<td/>').html((config['版位其他參數是否必填']==0)?'<input id ="是否新增'+i+'" order='+i+' type="checkbox">':'<input id ="是否新增'+i+'" order='+i+' type="checkbox" checked disabled>').appendTo($tr);
						otherConfigObj[i]=config['版位其他參數預設值'];
						
						var $inputtd = $('<td/>').appendTo($tr);
						//SHORTEPG連動CSMS客制化
						if(config['版位其他參數顯示名稱']=='前置廣告連動'){
							var $連動 = $('<select  id="前置連動" order='+i+' class = "combobox"/>').val(config['版位其他參數預設值']);
							$inputtd.append($連動);
							$( "#前置連動" ).combobox({
								select: function( event, ui ) {
									otherConfigObj[$(this).attr('order')] = ($(this).val()==0)?null:$(this).val();
								}
							});
							m_setSEPGConnection($('#前置連動').val());
						}
						else{
							var addNullRadio = [1,2,4];
							//增加選擇輸入的radio
							if($.inArray(config['版位其他參數型態識別碼'],addNullRadio)!=-1)
								$inputtd.append('<input type="radio" name="valueRadio'+i+'" order='+i+' value="input" checked>');
							
							switch(config['版位其他參數型態識別碼']){
								case 1 :
									$inputtd.append($('<input type ="text" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
										})
									);
								break;
								case 2 :
									$inputtd.append($('<input type ="number" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
										})
									);
								break;
								case 3 :
									$inputtd.append($('<input type ="checkbox" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = ($(this).is(':checked'))?1:0;
										})
									);
								break;
								case 4 :
									$inputtd.append($('<input type ="number" id="configValue'+i+'" order='+i+' class ="playTimesLimit configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
										})
									);
							}
							
							//增加選擇空值的radio
							if($.inArray(config['版位其他參數型態識別碼'],addNullRadio)!=-1){
								$inputtd.append('<input type="radio" name="valueRadio'+i+'" order='+i+' value="null">NULL');
								$('input[name="valueRadio'+i+'"]').change(function(){
									var corder =$(this).attr('order');
									if($('input[name="valueRadio'+corder+'"][value="null"]').prop('checked')){
										otherConfigObj[corder] = null;
										$("#configValue"+corder).prop('disabled',true);
									}
									else{
										otherConfigObj[corder] = $("#configValue"+corder).val();
										$("#configValue"+corder).prop('disabled',false);
									}
								});
							}
						}
					}
					//設定素材
					for(var i in json['版位素材設定']){
						var material = json['版位素材設定'][i];
						var $tr = $('<tr/>');
						materialObj[i]={託播單素材是否必填:material['託播單素材是否必填'],可否點擊:0,點擊後開啟類型:'',點擊後開啟位址:'',素材識別碼:0,素材名稱:'未指定'};
						
						$('<td/>').text(material['素材順序']).appendTo($tr);
						
						if(material['素材類型名稱']=='影片')
							$('<td class = "mtype" order='+i+'/>').text(material['影片畫質名稱']+material['素材類型名稱']).appendTo($tr);
						else
							$('<td class = "mtype" order='+i+'/>').text(material['素材類型名稱']).appendTo($tr);
							
						$('<td/>').text((material['託播單素材是否必填']==0)?'否':'是').appendTo($tr);
						//可否點擊
						$('<td/>').append(
							$('<input type ="checkbox" order='+i+' id="可否點擊'+i+'">').change(function(){
								materialObj[$(this).attr('order')]['可否點擊'] = ($(this).is(':checked'))?1:0;
							})
						).appendTo($tr)
						//點擊後開啟類型
						//點擊後開啟類型
						$('<td/>').append(
							$('<select order='+i+' id="點擊後開啟類型'+i+'"/>')
							.append($('<option value="NONE">NONE</option>'))
							.append($('<option value="OVA_SERVICE">OVA_SERVICE</option>'))
							.append($('<option value="OVA_CATEGORY">OVA_CATEGORY</option>'))
							.append($('<option value="OVA_VOD_CONTENT">OVA_VOD_CONTENT</option>'))
							.append($('<option value="OVA_CHANNEL">OVA_CHANNEL</option>'))
							.appendTo($tr).change(function(){
								materialObj[$(this).attr('order')]['點擊後開啟類型'] = $(this).val();
							}).val('NONE')
						).appendTo($tr)
						materialObj[i]['點擊後開啟類型'] = 'NONE';
						//點擊後開啟位址
						$('<td/>').append(
							$('<input type ="text" order='+i+' id="點擊後開啟位址'+i+'">').appendTo($tr).change(function(){
								materialObj[$(this).attr('order')]['點擊後開啟位址'] = $(this).val();
							}).autocomplete({
								source :function( request, response ) {
											$.post( "../order/autoCompleteSearch.php",{term: request.term, column:'點擊後開啟位址', table:'託播單素材'},
												function( data ) {
												response(JSON.parse(data));
											})
										}
							}).on('autocompletechange change', function () {
								materialObj[$(this).attr('order')]['點擊後開啟位址'] =  $(this).val();
							})
						).appendTo($tr)
						
						//選擇素材
						$('<td/>').append(
							$('<input type ="button" id="mBtn'+i+'" order='+i+'>').val('0:未指定').appendTo($tr).click(function(){
								$('#選擇素材順序').text($(this).attr('order'));
								$('#選擇素材類型').text($(this).parent().parent().find('.mtype').text());
								setMaterial($(this).val().split(':')[0]);
								$('#material_dialog_form').dialog('open');
							})
						).appendTo($tr)
						$('#materialTbody').append($tr)
					}
				}
			}
		});
	}
	
	//依照託播單日期設定SEPG連動
	function m_setSEPGConnection(ids){
		var dateObj=[];
		var hours=[];
		for( channel in orderObject){
			for(date in orderObject[channel]){
				var stt=date+' 00:00:00';
				var edt = date+' 23:59:59';
				var hour=orderObject[channel][date].join(',');
				dateObj.push({'StartDate':stt,'EndDate':edt});
				hours.push(hour);
			}
		}
		setConnectOrder_SEPG('../order/newOrder.php',ids,dateObj,hours);
	}
	
	//還原輸入的資料
	function clearInput(){
		$(":text").val("");
		$(":checkbox").each(function() {
			$(this).prop("checked", false);
		});
		var jdata = {
		"託播單名稱":"",
		"託播單說明":'',
		"素材識別碼":'',
		"預約到期時間":'',
		"售價":'',
		};
		showVal(jdata);
	}
	
	//資料庫中的資料
	function getInfoFromDb(id,selectOrder){
		$.post(ajaxtodbPath,{"action":"訂單資訊","託播單識別碼":id})
				.done(function(data){
					jdata = JSON.parse(data);
					showVal(jdata)
				});
	}
	
	//顯示資料
	function showVal(jdata){
			//設定版位資料
			initialPositionSetting();
			$("#Name").val(jdata.託播單名稱);
			$("#Info").val(jdata.託播單說明);				   
			$("#Deadline").val(jdata.預約到期時間.split(" ")[0]);
			$("#售價").val(jdata.售價);
			if($("#groupID").text()==''&&typeof(jdata.託播單群組識別碼)!='undefined'&&jdata.託播單群組識別碼!=null) $("#groupID").text(jdata.託播單群組識別碼.toString().split('_')[0]);
			
			//其他參數
			if(typeof(jdata['其他參數'])!='undefined'){
				for( var i in jdata['其他參數']){
					otherConfigObj[i] = jdata['其他參數'][i];
					$('#configTbody tr td input[id = "configValue'+i+'"]').val(otherConfigObj[i]);
					$('#configTbody tr td input[type="checkbox"][id = "configValue'+i+'"]').prop('checked',(otherConfigObj[i]==1)?true:false);
					$('#configTbody tr td input[id = "是否新增'+i+'"]').prop('checked',true);
					if(otherConfigObj[i] == null){
						$('input[name = "valueRadio'+i+'"][value = "null"]').prop('checked',true);
						$('#configTbody tr td input[id = "configValue'+i+'"]').prop('disabled',true);
					}
					else{
						$('input[name = "valueRadio'+i+'"][value = "input"]').prop('checked',true);
						$('#configTbody tr td input[id = "configValue'+i+'"]').prop('disabled',false);
					}
					if($('#參數名稱'+i).text()=='前置廣告連動')
						m_sepgConnect(otherConfigObj[i]);
				}
			}
			//素材
			if(typeof(jdata['素材'])!='undefined'){
				for( var i in jdata['素材']){
					materialObj[i] = jdata['素材'][i];
					$('#可否點擊'+i).prop('checked',(materialObj[i].可否點擊==1)?true:false);
					$('#點擊後開啟類型'+i).val(materialObj[i].點擊後開啟類型);
					$('#點擊後開啟位址'+i).val(materialObj[i].點擊後開啟位址);
					$('#mBtn'+i).val(materialObj[i].素材識別碼+': '+materialObj[i].素材名稱);
				}
			}
			configOption();
	}

	
	//對應不同的動作或版位類型做不同的介面設訂(showVal處理完成後呼叫)
	function configOption(){
		var d = new Date();
		$( "#Deadline" ).datepicker({
			dateFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
			//,minDate: d.yyyymmdd()+' 00:00:00'
			});
	}

	//儲存託播單
	function save(){
		//檢查必要資訊
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

		if($("#Name").val().indexOf("'") != -1)
		{
			alert("CSMS類型託播單名稱不可包含「'」符號");
			return 0;
		}
		
		$('#uploadResult_f,#uploadResult_s').empty();
		$('#uploadDialog').dialog('open');
		//統幾總託播單數量
		var total =0;
		for( channel in orderObject){
			for(date in orderObject[channel]){
				var chs = $('#ch'+channel).val();
				if(chs != null){
					for(var i in chs){
					total++;
					}
				}
			}
		}
		//逐一建立託播單
		for( channel in orderObject){
			for(date in orderObject[channel]){
				var chs = $('#ch'+channel).val();
				if(chs != null){
					for(var i in chs){
						var jobject = {			
							"版位類型識別碼":positionTypeId,
							"版位識別碼":chs[i].split(':')[0],
							"版位名稱":chs[i].split(':')[1],
							"託播單名稱":$("#Name").val(),
							"託播單說明":$("#Info").val(),
							"廣告期間開始時間":date+' 00:00:00',
							"廣告期間結束時間":date+' 23:59:59',
							"廣告可被播出小時時段":orderObject[channel][date].join(','),
							"素材識別碼":$("#Material").val(),
							"預約到期時間":($("#Deadline").val()=="")?null:$("#Deadline").val()+" 23:59:59",
							"售價":($("#售價").val()=="")?null:$("#售價").val(),
							'其他參數':{},
							'素材':materialObj,
							'託播單CSMS群組識別碼':'temp'
						};
						$.each(otherConfigObj,function(index,value){
							if($('#是否新增'+index).prop('checked')){
								jobject['其他參數'][index]=value;
							}
						});
						
						if(jobject['預約到期時間']>jobject['廣告期間開始時間']){
							$('#uploadResult_f').append('<p>'+jobject.版位名稱+' <a style="color:blue;">'+jobject.廣告期間開始時間+'~'+jobject.廣告期間結束時間+
							'</a> <a style="color:maroon;">時段'+jobject.廣告可被播出小時時段+'</a><a style="color:red;"> 新增失敗: 預約到期時間大於廣告開始時間</a></p>');
						}	
						else
							checkTime(jobject);
					}
				}
			}
		}
		//檢察版位走ˇ期是否符合
		function checkTime(jobject){
			$.post('?',{method:'版位走期檢察',版位識別碼:jobject.版位識別碼,StartDate:jobject.廣告期間開始時間,EndDate:jobject.廣告期間結束時間}
				,function(json){
					if(json.success)
						checkMaterial(jobject);
					else{
						$('#uploadResult_f').append('<p>'+jobject.版位名稱+' <a style="color:blue;">'+jobject.廣告期間開始時間+'~'+jobject.廣告期間結束時間+
							'</a> <a style="color:maroon;">時段'+jobject.廣告可被播出小時時段+'</a><a style="color:red;"> 新增失敗: '+json['message']+'</a></p>');
						if(--total==0)
							saveOrder();
					}
				}
				,'json'
			);
		
		}
		
		
		var spliteds=[];//儲存拆單的結果
		var message=[];
		//檢察素材
		function checkMaterial(jobject){
			$.post(ajaxtodbPath,{"action":"檢察素材","orders":JSON.stringify([jobject])},
			function(data){
				if(data["success"]){
					$.post(ajaxtodbPath,{"action":"檢察素材CSMS","orders":JSON.stringify([jobject])},
						function(data){
							if(!data['success']){
							$('#uploadResult_f').append('<p>'+jobject.版位名稱+' <a style="color:blue;">'+jobject.廣告期間開始時間+'~'+jobject.廣告期間結束時間+
									'</a> <a style="color:maroon;">時段'+jobject.廣告可被播出小時時段+'</a><a style="color:red;"> 新增失敗: '+data['message']+'</a></p>');
							if(--total==0)
								saveOrder();
							}
							else{
								for(var i in data['result']){
									if(!data['result'][i]['success']){
										$('#uploadResult_f').append('<p>'+jobject.版位名稱+' <a style="color:blue;">'+jobject.廣告期間開始時間+'~'+jobject.廣告期間結束時間+
												'</a> <a style="color:maroon;">時段'+jobject.廣告可被播出小時時段+'</a><a style="color:red;"> 新增失敗: '+data['result'][i]data['message']+'</a></p>');
										if(--total==0)
											saveOrder();
										return 0;
									}
								}
								
								//檢查成功，執行拆單
								spliteds=spliteds.concat(splitOrder(jobject));
								message.push('<p>'+jobject.版位名稱+' <a style="color:blue;">'+jobject.廣告期間開始時間+'~'+jobject.廣告期間結束時間+
										'</a> <a style="color:maroon;">時段'+jobject.廣告可被播出小時時段+'</a></p>');
								for(var i in spliteds){
									var order = spliteds[i];
									spliteds[i]['託播單CSMS群組識別碼'] = order['廣告期間開始時間']+order['廣告期間結束時間']+order['廣告可被播出小時時段'];
								}
								if(--total==0){
									saveOrder();
								}
							}
						},'json'
					);
				}
				else{
					$('#uploadResult_f').append('<p>'+jobject.版位名稱+' <a style="color:blue;">'+jobject.廣告期間開始時間+'~'+jobject.廣告期間結束時間+
							'</a> <a style="color:maroon;">時段'+jobject.廣告可被播出小時時段+'</a><a style="color:red;"> 新增失敗: '+data['message']+'</a></p>');
					if(--total==0)
						saveOrder();
				}
			}
			,'json'
			);
		}
		
		//儲存
		function saveOrder(){
			if(message!=''){
				$('#uploadResult_s').append('<p>準備新增:</p>'+message.join(''));
				savedEdit={"delete":[],"edit":[]};
				$.post(ajaxtodbPath,
					 {"action":"儲存更變",
					 "orders":JSON.stringify(spliteds),
					 "orderListId":$( "#orderList option:selected" ).val(),
					 "edits":JSON.stringify(savedEdit)},
					 function(data){
						if(data["dbError"]!=undefined){
							alert(data["dbError"]);
							return 0;
						}
						if(data["success"]){
							$('#uploadResult_s').append('<p>新增成功!</p>');
						}
						else{
							$('#uploadResult_f').append('<p style="color:red;">新增失敗:'+data['message']+'</p>');
						}
					}
					,'json'
				);
			}
		}
	}
	
	//拆單
	function splitOrder(order){
		//若全時段投放不需拆單
		if(order.廣告可被播出小時時段=='0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23'){
			delete order.託播單群組識別碼;
			return [order];
		}
		
		var hoursArray = [];//存放各不連續時段用
		hoursArray.push(order.廣告可被播出小時時段.split(','));
			
		//檢查不連續時段並拆解
		for( var i=1;i<hoursArray[0].length;i++){
			if((parseInt(hoursArray[0][i-1],10)+1)!=parseInt(hoursArray[0][i],10)){
				hoursArray.push(hoursArray[0].slice(0,i));
				hoursArray[0].splice(0,i);
				i=0;
			}
		}		
		//將第一個時段array調換至最後一個(為了依照順序)
		hoursArray.push(hoursArray[0]);
		hoursArray.splice(0,1);
		
		//若第一個時段array開頭為0，最後一個時段array結束為23，跨日發生，合併
		if(hoursArray[0][0] == '0' && hoursArray[hoursArray.length-1][hoursArray[hoursArray.length-1].length-1] == '23'){
			hoursArray[hoursArray.length-1] = hoursArray[0].concat(hoursArray[hoursArray.length-1]);
			hoursArray.splice(0,1);
		}

		var st = order.廣告期間開始時間.split(/[\s,:,-]/);
		var ed = order.廣告期間結束時間.split(/[\s,:,-]/);
		var returnArray=[];//回傳結果用
		
		//複製託播單用
		function getCopyOfOrder(order){
			var copyOfOrder ={};
			$.extend(true,copyOfOrder,order);
			copyOfOrder.廣告可被播出小時時段='';
			return copyOfOrder;
		}

		for( var i=0;i<hoursArray.length;i++){
			var copyOfOrder = getCopyOfOrder(order);
			copyOfOrder.廣告可被播出小時時段 = hoursArray[i].join(',');
			returnArray.push(copyOfOrder);
		}
		
		//只有一個託播單，移除群組識別碼
		if(returnArray.length==1){
			delete returnArray[0].託播單群組識別碼;
		}
		return returnArray;
	}
	
	
	function getHours(){
		//將選擇的小時時段轉為String
		//將選擇的小時時段轉為ARRAY
		var hours="";
		var temp=new Array();
		$('input[name="hours"]:checked').each(function(){temp.push($(this).val());});
		return temp.join(',')
	}
	
	//數字補0
	function addLeadingZero(length,str){
		if(typeof(str)!='String')
		str = str.toString();
		var pad = Array(length+1).join("0");
		return pad.substring(0, length - str.length) + str;
	}
	
	//素材資訊
	function materialInfo(){
			if($("#MaterialGroup").val()!="")
			parent.openMaterialGroupInfoDialog($("#MaterialGroup").val());
	}
	
	
	//代入現有託播單資訊處理
	function selectOrderFun(){
		$('#mainFram').hide();
		$('#selectOrder').attr('src','../order/selectOrder.php?positionType='+<?=$positionTypeId?>).attr('height',$(window).height()-100).show();	
		$('#closeSelection').show();
	}
	//由selectOrder呼叫，託播單被選擇
	function orderSelected(id){
		getInfoFromDb(id,true);
		$('#selectOrder,#closeSelection').hide();
		$('#mainFram').show();
	}
	//由selectOrder呼叫，託播單群組被選擇
	function orderGroupSelected(id){
		$.post(ajaxtodbPath,{"action":"訂單資訊","託播單群組識別碼":id})
				.done(function(data){
					jdata = JSON.parse(data);
					showVal(jdata);
			});
		$('#selectOrder,#closeSelection').hide();
		$('#mainFram').show();
	}
 </script>
 
 
</body>
</html>