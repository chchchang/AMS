<?php 	
	include('../tool/auth/authAJAX.php');
	require_once dirname(__FILE__).'/../tool/MyDB.php';
	$my=new MyDB(true);
	if(isset($_POST['action'])){
		//檢察要修改的託播單資料是否可符合板位與素材設定 輸入:託播單識別碼 素材識別碼 StartDate EndDate
		if($_POST['action']=='版位檢察'){
			$sql ='
				SELECT 版位類型.版位名稱 AS 版位類型名稱,版位類型.版位有效起始時間 AS 版位類型有效起始時間,版位類型.版位有效結束時間 AS 版位類型有效結束時間,
				版位.版位有效起始時間,版位.版位有效結束時間
				FROM 版位,版位 版位類型,託播單
				WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼
				AND 託播單識別碼 = ?
			';
			$result=$my->getResultArray($sql,'i',$_POST['託播單識別碼']);
			if($result===false)
				exit(json_encode(array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				
			$row=$result[0];
			//版位的日期限制
			$positionOpt= array();
			$positionOpt['StartDate']=($row['版位有效起始時間']==NULL)?$row['版位類型有效起始時間']:$row['版位有效起始時間'];
			$positionOpt['EndDate']=($row['版位有效結束時間']==NULL)?$row['版位類型有效結束時間']:$row['版位有效結束時間'];
			if($positionOpt['StartDate']!=NULL &&$positionOpt['StartDate']>$_POST['StartDate'])
				exit(json_encode(array('success'=>false,'message'=>'版位走期無法包含託播單'),JSON_UNESCAPED_UNICODE));
			if($positionOpt['EndDate']!=NULL &&$positionOpt['EndDate']<$_POST['EndDate'])
				exit(json_encode(array('success'=>false,'message'=>'版位走期無法包含託播單'),JSON_UNESCAPED_UNICODE));
				
			exit(json_encode(array('success'=>true,'message'=>'success'),JSON_UNESCAPED_UNICODE));
		}
		if($_POST['action']=='託播單名稱'){
			$sql ='
					SELECT 託播單名稱,託播單識別碼,託播單狀態識別碼 AS 託播單狀態
					FROM 託播單
					WHERE 託播單識別碼 = ?
				';
			$result=$my->getResultArray($sql,'i',$_POST['託播單識別碼']);
			if($result===false)
				exit(json_encode(array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			$row=$result[0];
				exit(json_encode(array('success'=>true,'data'=>$row),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['action']=='vod上限比例'){
			$pt = array();
			
			//取的版位類型預設值
			$sql = '
				SELECT 版位其他參數預設值,版位其他參數名稱
				FROM 版位其他參數,版位
				WHERE 版位.版位識別碼 = 版位其他參數.版位識別碼 and 版位名稱 = "專區vod" and 版位其他參數名稱 in ("bakadDisplayMaxPercentage","bakadschdDisplayMaxPercentage")
			';
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}				
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			while($row=$res->fetch_assoc()){
				$pt[$row['版位其他參數名稱']]=intval($row['版位其他參數預設值']);
			}
			
			//取得每張託播單的版位
			$orderPositionMap= array();
			foreach($_POST['託播單'] as $id){
				
				//取得版位名稱
				$sql = '
					SELECT 版位識別碼
					FROM 託播單
					WHERE 託播單識別碼 =?
				';
				if(!$stmt=$my->prepare($sql)) {
					exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}				
				if(!$stmt->bind_param('i',$id)){
					exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$stmt->execute()) {
					exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				if(!$res=$stmt->get_result()){
					exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				}
				$orderPositionMap[$id] =$res->fetch_assoc()['版位識別碼'];
			}
			
			//逐一取得版位設定
			$percentages = array();
			$pNames = array();
			foreach($orderPositionMap as $id){
				if(!isset($percentages[$id]['bakadDisplayMaxPercentage'])){
					$percentages[$id]['bakadDisplayMaxPercentage']=$pt['bakadDisplayMaxPercentage'];
					$percentages[$id]['bakadschdDisplayMaxPercentage']=$pt['bakadschdDisplayMaxPercentage'];
					//取得版位名稱
					$sql = '
						SELECT 版位名稱
						FROM 版位,託播單
						WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 託播單識別碼 =?
					';
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}				
					if(!$stmt->bind_param('i',$id)){
						exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					//記錄除去北/中/南/IAP的名稱
					$pNames[$id] =explode('_',$res->fetch_assoc()['版位名稱']);
					array_pop($pNames[$id]);
					$pNames[$id] = implode('_',$pNames[$id]);

					$sql = '
						SELECT 版位其他參數預設值,版位其他參數名稱
						FROM 版位其他參數
						WHERE 版位識別碼 = ? and 版位其他參數名稱 in ("bakadDisplayMaxPercentage","bakadschdDisplayMaxPercentage")
					';
					if(!$stmt=$my->prepare($sql)) {
						exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}				
					if(!$stmt->bind_param('i',$id)){
						exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()) {
						exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					if(!$res=$stmt->get_result()){
						exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
					}
					while($row=$res->fetch_assoc()){
						if($row['版位其他參數名稱']=="bakadDisplayMaxPercentage")
							$percentages[$id]['bakadDisplayMaxPercentage']=intval($row['版位其他參數預設值']);
						else if($row['版位其他參數名稱']=="bakadschdDisplayMaxPercentage")
							$percentages[$id]['bakadschdDisplayMaxPercentage']=intval($row['版位其他參數預設值']);
					}
				}
			}
			//計算相同版位名稱得加總
			$groupCount= array();
			$groupCountschd= array();
			foreach($pNames as $pid=>$pN){
				if(!isset($groupCount[$pN])){
					$groupCount[$pN]=$percentages[$pid]['bakadDisplayMaxPercentage'];
					$groupCountschd[$pN]=$percentages[$pid]['bakadschdDisplayMaxPercentage'];
				}
				else{
					$groupCount[$pN]+=$percentages[$pid]['bakadDisplayMaxPercentage'];
					$groupCountschd[$pN]+=$percentages[$pid]['bakadschdDisplayMaxPercentage'];
				}
			}
			//計算比例
			foreach($percentages as $pid=>$per){
				$percentages[$pid]['bakadDisplayMaxPercentage']/=$groupCount[$pNames[$pid]];
				$percentages[$pid]['bakadschdDisplayMaxPercentage']/=$groupCountschd[$pNames[$pid]];
			}
			
			//各託播單的比例
			foreach($orderPositionMap as $id=>$value){
				$orderPositionMap[$id] = $percentages[$value];
			}
			exit(json_encode(array("success"=>true,"data"=>$orderPositionMap),JSON_UNESCAPED_UNICODE));
		}
	}
	@include('../tool/auth/auth.php');
	
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script src="../tool/jquery-ui/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script src="../tool/iframeAutoHeight.js" type="text/javascript"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.tokenize.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-plugin/jquery.tokenize.css" />
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css"></link>
<script type="text/javascript" src="newOrder_852.js?<?=time()?>"></script>
<script type="text/javascript" src="newOrder_851.js?<?=time()?>"></script>
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
	#position,#版位有效起始時間,#版位有效結束時間{
		text-decoration:underline;
		margin-left:3px;
		margin-right:10px;
	}
	.tokenize{ width: 300px }
</style>

</head>
<body>
已選 <span id='selectedNum'>0</span> 張託播單<button id="showSelectedOrder" class ='darkButton'>查看</button>

<div id = 'tabs'>
<ul>
	<li><a href="#tabs-1">選擇託播單</a></li>
	<li><a href="#tabs-2">設定託播單更新資訊</a></li>
</ul>
<div id ='tabs-1'>
	<iframe id = 'editOrders' src = 'selectOrder.php?returnParentFuncName=addOrdersToEdit' width = '100%' height = '600px'></iframe>
</div>
<div id ='tabs-2'>
	<div id = 'mainFram'>
	<fieldset  style="clear: both;">
    <legend>託播更新單資訊</legend>
	勾選要更改的欄位<button id = 'allAttBtn' class = 'darkButton'>全選</button> <button id = 'noAttBtn' class = 'darkButton'>全不選</button>
	<table width = '100%' class='styledTable2'>
		<tr><th><input type="checkbox" name="updateCheckBox" id="NameCB"></th><th>託播單名稱*:</th><td><input id = "Name" type="text" value = "" size="38" > <button id ='copyOrder' onClick = 'selectOrderFun()' class = 'darkButton'>填入現有託播單資訊</button></td></tr>
		<tr><th><input type="checkbox" name="updateCheckBox" id="InfoCB"></th><th>託播單說明:</th><td><input id = "Info" type="text" value = "" size="38"></td></tr>
		<tr><th><input type="checkbox" name="updateCheckBox" id="StartDateCB"></th><th>託播單開始期間*:</th><td><input id = "StartDate" type="text" value = "" width='30' ></td></tr>
		<tr><th><input type="checkbox" name="updateCheckBox" id="EndDateCB"></th><th>託播單結束期間*:</th><td><input id = "EndDate" type="text" value = "" width='30'></td></tr>
		<tr><th><input type="checkbox" name="updateCheckBox" id="hoursCB"></th><th>託播單時段*:</th><td><button id = 'allTimeBtn' class = 'darkButton'>全選</button> <button id = 'noTimeBtn' class = 'darkButton'>全不選</button>
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
		<tr><th><input type="checkbox" name="updateCheckBox" id="DeadlineCB"></th><th>預約到期日期*:</th><td><input id = "Deadline" type="text" value = "" size="15"></td></tr>
		<tr><th><input type="checkbox" name="updateCheckBox" id="售價CB"></th><th>售價:</th><td><input id="售價" type="number" value = ""></td></tr>
	</table>
	</fieldset>
	<fieldset  style="clear: both;" id = 'otherConfigTable'>
	<legend>其他參數</legend>
	<table width = '100%' class='styledTable2'>
		<thead><tr><th></th><th>參數名稱</th><th>類型</th><th>是否新增</th><th>內容</th></tr></thead>
		<tbody id = 'configTbody'></tbody>
	</table>
	</fieldset>
	
	<fieldset  style="clear: both;">
	<legend>素材</legend>
	<table width = '100%' class='styledTable2'>
		<thead><tr><th>順序</th><th>素材類型</th><th></th><th>可否點擊</th><th></th><th>點擊開啟類型</th><th></th><th>點擊開啟位址</th><th></th><th>選擇素材</th></tr></thead>
		<tbody id = 'materialTbody'></tbody>
	</table>
		<div class ="Center"><button id="clearBtn" type="button" onclick = "clearInput()">清空</button><button id = 'saveBtn' type="button" onclick = "save()">儲存</button></div>
	</div>
	<button id = 'closeSelection' class = 'darkButton' style='float:right'>關閉選單</button>
	<iframe id ='selectOrder' width = '100%' height = '600px' style='clear:both'></iframe>
</div>
</div>
<div id="selectedDialog"><table class="styledTable" rules="all" cellpadding='5' width = "100%" id ="selectedOrderTable"></table></div>
<div id="uploadDialog"><div id = 'uploadResult_f'></div><div id = 'uploadResult_s'></div></div>
<div id="material_dialog_form"><table class='styledTable2' width = '100%'>
		<a id ='版位類型識別碼' hidden></a>
		<tr><th>素材順序</th><td><a id ='選擇素材順序'></a></td></tr>
		<tr><th>素材類型</th><td><a id ='選擇素材類型'></a></td></tr>
		<tr><th><a id="materialgroup">素材群組:</a></th><td><select id="MaterialGroup"></select><button id="materialInfo" type="button" onclick = "materialInfo()">詳細資訊</button></td></tr>
		<tr><th><a id="material">素材:</a></th><td><select id="Material"></select><button id = '選擇素材'>選擇素材</button></td></tr>
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
	var selectedOrder= [];//id;
	var otherConfigObj = {};
	var  materialObj = {};
	var orderDates={};//託播單的播出時間
	var timeIntersect='';//託播單共有的時段
	var pNames = [];//已選託播單的全部版位名稱
	Date.prototype.yyyymmdd = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		var dd  = this.getDate().toString();
		return yyyy +'-'+ (mm[1]?mm:"0"+mm[0]) +'-'+ (dd[1]?dd:"0"+dd[0]); // padding
	};
//********設定
	$("#tabs").tabs({
		activate: function(event, ui) {
			switch (ui.newTab.index()){
			case 0:
				$('#editOrders')[0].contentWindow.setSelectedOrder(selectedOrder);
			break;
			case 1:
				initialPositionSetting();
			break;
		}}
	});
	$( "#material_dialog_form" ).dialog(
			{autoOpen: false,
			width: 500,
			height: 450,
			modal: true,
			title: '選擇素材'
	});
	$('#selectedDialog').dialog({autoOpen: false,
			width: 400,
			height: 400,
			modal: true,
			title: '選擇的託播單'
			});
	$('#uploadDialog').dialog({autoOpen: false,
			width: 400,
			height: 600,
			modal: true,
			title: '託播單修改結果'
			});
	$('#showSelectedOrder').click(function(){
		$('#selectedDialog').dialog('open');
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
	
	//設訂素材群組資料
	$("#MaterialGroup").combobox();
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
		$( "#MaterialGroup" ).combobox({
			 select: function( event, ui ) {
				setMaterial('');
			 }
		});
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
		$.post(ajaxtodbPath,{action:'取得可用素材',版位類型識別碼:$('#版位類型識別碼').text(),素材群組識別碼:$('#MaterialGroup').val(),素材順序:$('#選擇素材順序').text()},
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
				if(selectedId!=''){
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
	
	//設定版位選項
	var ajaxtodbPath = "ajaxToDB_Order.php";
	
	$("#selectOrder,#closeSelection").hide();
	
	$('#closeSelection').click(function(){
		$("#selectOrder,#closeSelection").hide();
		$('#mainFram').show();
	});
	
	//時段全選按鈕
	$('#allTimeBtn').click(function(){
		$('input[name="hours"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//時段全不選按鈕
	$('#noTimeBtn').click(function(){
		$('input[name="hours"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	//欄位全選按鈕
	$('#allAttBtn').click(function(){
		$('input[name="updateCheckBox"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//欄位全不選按鈕
	$('#noAttBtn').click(function(){
		$('input[name="updateCheckBox"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	//選擇廣告開始日期後，預約日期推算
	var deadlinePreDay = 5;
	$('#StartDate').change(function(){
		var s =$("#StartDate").val().split(" ")[0].split('-')
		var deadline = new Date(parseInt(s[0],10),parseInt(s[1],10)-1,parseInt(s[2],10),00,00,00);
		for(var i =deadlinePreDay; i >0;i--){
			deadline.addDays(-1);
			while(deadline.getDay()==6||deadline.getDay()==0){
				deadline.addDays(-1);
			}
		}
		$('#Deadline').val(deadline.getFullYear()+'-'+addLeadingZero(2,deadline.getMonth()+1)+'-'+addLeadingZero(2,deadline.getDate()));
	});
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
	
	clearInput();
	
	//動態增加版位的其他參數和素材設定
	function initialPositionSetting(){
		$('#configTbody,#materialTbody').empty();
		otherConfigObj = {};
		materialObj = {};
		$.ajax({
			async: false,
			type : "POST",
			url :ajaxtodbPath,
			data: {action:'批次取得版位素材與參數','orderIds':selectedOrder},
			dataType : 'json',
			success :
			function(json){
				if(json.success){
					orderDates = json['日期'];
					timeIntersect = json['時段'];
					pNames = json['pNames'];
					//設定其他參數
					for(var i in json['其他參數設定']){
						var config = json['其他參數設定'][i];
						var $tr = $('<tr/>');
						$('#configTbody').append($tr);
						$('<th/>').append($('<input type ="checkbox" name="updateCheckBox" id="configCB'+i+'">')).appendTo($tr);
						$('<td id ="參數名稱'+i+'"/>').text(config['版位其他參數顯示名稱']).appendTo($tr);
						$('<td/>').text(config['參數型態顯示名稱']).appendTo($tr);
						$('<td/>').html((config['版位其他參數是否必填']==0)?'<input id ="是否新增'+i+'" order='+i+' type="checkbox">':'<input id ="是否新增'+i+'" order='+i+' type="checkbox" checked disabled>')
						.appendTo($tr);
						otherConfigObj[i]=config['版位其他參數預設值'];
						
						var $inputtd = $('<td/>').appendTo($tr);
						//連動廣告客制化
						if(config['版位其他參數名稱']=='bannerTransactionId1'||config['版位其他參數名稱']=='bannerTransactionId2'){
							var connectIndex = 1;
							if (config['版位其他參數名稱']=='bannerTransactionId2')
							connectIndex = 2;
							
							var $連動 = $('<select  id="連動廣告'+connectIndex+'"  multiple="multiple"  class ="tokenize configValue" order='+i+' />').val(config['版位其他參數預設值']);
							$inputtd.append($連動);
							$('#連動廣告'+connectIndex).tokenize({
									placeholder:"輸入CSMS群組識別碼或關鍵字選擇可連動的託播單"
									,displayDropdownOnFocus:true
									,newElements:false,
									onAddToken: 
										function(value, text, e){
											var order1 =$('#連動廣告1').attr('order');
											var order2 =$('#連動廣告2').attr('order');
											otherConfigObj[order1] = ($('#連動廣告1').val()!=null)?$('#連動廣告1').val().join(','):'';
											otherConfigObj[order2] = ($('#連動廣告2').val()!=null)?$('#連動廣告2').val().join(','):'';
											if(otherConfigObj[order1]!=''){
												$('#是否新增'+order1).prop('checked',true);
											}
											if(otherConfigObj[order2]!=''){
												$('#是否新增'+order2).prop('checked',true);
											}
										},
									onRemoveToken: 
										function(value, text, e){
											var order1 =$('#連動廣告1').attr('order');
											var order2 =$('#連動廣告2').attr('order');
											otherConfigObj[order1] = ($('#連動廣告1').val()!=null)?$('#連動廣告1').val().join(','):'';
											otherConfigObj[order2] = ($('#連動廣告2').val()!=null)?$('#連動廣告2').val().join(','):'';
											if(otherConfigObj[order1]!=''){
												$('#是否新增'+order1).prop('checked',true);
											}
											if(otherConfigObj[order2]!=''){
												$('#是否新增'+order2).prop('checked',true);
											}
										}
								});				
						}
						
						//SHORTEPG連動CSMS客制化
						else if(config['版位其他參數顯示名稱']=='前置廣告連動'){
							var $連動 = $('<select  id="前置連動" order='+i+' class = "combobox"/>').val(config['版位其他參數預設值']);
							$inputtd.append($連動);
							$( "#前置連動" ).combobox({
								select: function( event, ui ) {
									otherConfigObj[$(this).attr('order')] = ($(this).val()==0)?null:$(this).val();
									if(otherConfigObj[$(this).attr('order')]!=''){
										$('#是否新增'+$(this).attr('order')).prop('checked',true);
									}
								}
							});
							$('#allTimeBtn,#noTimeBtn').click(function(){
								m_setSEPGConnection($('#前置連動').val());
							});
							//連動託播單設定
							$( "input[name='hours'],#StartDateCB,#EndDateCB,#hoursCB" ).change(function() {
								m_setSEPGConnection($('#前置連動').val());
							});
							$( "#StartDate,#EndDate").focusout(function() {
								m_setSEPGConnection($('#前置連動').val());
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
											if(otherConfigObj[$(this).attr('order')]!=''){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
										})
									);
								break;
								case 2 :
									$inputtd.append($('<input type ="number" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
											if(otherConfigObj[$(this).attr('order')]!=''){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
										})
									);
								break;
								case 3 :
									$inputtd.append($('<input type ="checkbox" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = ($(this).is(':checked'))?1:0;
											if(otherConfigObj[$(this).attr('order')]==1){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
										})
									);
								break;
								case 4 :
									$inputtd.append($('<input type ="number" id="configValue'+i+'" order='+i+' class ="playTimesLimit configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
											if(otherConfigObj[$(this).attr('order')]!=''){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
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
					if($('#連動廣告1').length!=0||$('#連動廣告2').length!=0){
						m_setConnectionOrder({									
							'1':$.isArray($('#連動廣告1').val())?$('#連動廣告1').val():[],
							'2':$.isArray($('#連動廣告2').val())?$('#連動廣告2').val():[]
						});
						//時段全選按鈕
						$('#allTimeBtn,#noTimeBtn').click(function(){
							m_setConnectionOrder({									
								'1':$.isArray($('#連動廣告1').val())?$('#連動廣告1').val():[],
								'2':$.isArray($('#連動廣告2').val())?$('#連動廣告2').val():[]
							});
						});
						$( "input[name='hours'],#StartDateCB,#EndDateCB,#hoursCB" ).change(function() {
							m_setConnectionOrder({									
								'1':$.isArray($('#連動廣告1').val())?$('#連動廣告1').val():[],
								'2':$.isArray($('#連動廣告2').val())?$('#連動廣告2').val():[]
							});
						});
						$( "#StartDate,#EndDate").focusout(function() {
							m_setConnectionOrder({									
								'1':$.isArray($('#連動廣告1').val())?$('#連動廣告1').val():[],
								'2':$.isArray($('#連動廣告2').val())?$('#連動廣告2').val():[]
							});
						});	
						
					}
					//設定素材
					for(var i in json['版位素材設定']){
						var material = json['版位素材設定'][i];
						var $tr = $('<tr/>');
						materialObj[i]={版位識別碼:material['版位識別碼'],可否點擊:0,點擊後開啟類型:'',點擊後開啟位址:'',素材識別碼:0,素材名稱:'未指定'};
						$('#版位類型識別碼').text(material['版位識別碼']);
						$('<td/>').text(material['素材順序']).appendTo($tr);
						
						if(material['素材類型名稱']=='影片')
							$('<td class = "mtype" order='+i+'/>').text(material['影片畫質名稱']+material['素材類型名稱']).appendTo($tr);
						else
							$('<td class = "mtype" order='+i+'/>').text(material['素材類型名稱']).appendTo($tr);
							
						//可否點擊
						$('<th/>').append($('<input type ="checkbox" name="updateCheckBox" id="materialClickableCB'+i+'" class="materialCB">')).appendTo($tr);
						$('<td/>').append(
							$('<input type ="checkbox" order='+i+' id="可否點擊'+i+'">').change(function(){
								materialObj[$(this).attr('order')]['可否點擊'] = ($(this).is(':checked'))?1:0;
							})
						).appendTo($tr)
						//點擊後開啟類型
						$('<th/>').append($('<input type ="checkbox" name="updateCheckBox" id="materialCTypeCB'+i+'" class="materialCB">')).appendTo($tr);
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
						$('<th/>').append($('<input type ="checkbox" name="updateCheckBox" id="materialCAdCB'+i+'" class="materialCB">')).appendTo($tr);
						$('<td/>').append(
							$('<input type ="text" order='+i+' id="點擊後開啟位址'+i+'">').appendTo($tr).change(function(){
								materialObj[$(this).attr('order')]['點擊後開啟位址'] = $(this).val();
							})
							.autocomplete({
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
						$('<th/>').append($('<input type ="checkbox" name="updateCheckBox" id="materialFileCB'+i+'" class="materialCB materialFileCB">')).appendTo($tr);
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
	//依照多組日期設定連動管告
	function m_setConnectionOrder(ids){
		if($('#連動廣告1').length==0)
		return ;
		//取得全部託播單的日期
		var dateObj=[];
		for(var i in orderDates){
			var st =orderDates[i]['start'];
			if($('#StartDateCB').prop('checked')) st = $('#StartDate').val();
			var ed =orderDates[i]['end'];
			if($('#EndDateCB').prop('checked')) ed = $('#EndDate').val();
			dateObj.push({'StartDate':st,'EndDate':ed});
		}
		//全部託播單共有的時段交集
		var hours= timeIntersect;
		if($('#hoursCB').prop('checked')) hours = getHours();
		//全部託播單包含的區域
		var areas = [];
		for(var i in pNames){
			var area = pNames[i].split('_');
			area = area[area.length-1];
			if($.inArray(area,areas)==-1){
				areas.push(area);
			}
		}
		setConnectOrder('newOrder.php',ids,dateObj,[hours],areas);
	}
	
	//依照多組日期設定SEPG連動
	function m_setSEPGConnection(ids){
		if($('#前置連動').length==0)
		return 0;
		var dateObj=[];
		for(var i in orderDates){
			var st =orderDates[i]['start'];
			if($('#StartDateCB').prop('checked')) st = $('#StartDate').val();
			var ed =orderDates[i]['end'];
			if($('#EndDateCB').prop('checked')) ed = $('#EndDate').val();
			dateObj.push({'StartDate':st,'EndDate':ed});
		}
		var hours= timeIntersect;
		if($('#hoursCB').prop('checked')) hours = getHours();
		setConnectOrder_SEPG('newOrder.php',ids,dateObj,[hours]);
	}
	//對應不同的動作或版位類型做不同的介面設訂(showVal處理完成後呼叫)
	function configOption(){
		var d = new Date();
		$( "#StartDate" ).datetimepicker({	
			dateFormat: "yy-mm-dd",
			showSecond: true,
			timeFormat: 'HH:mm:ss',
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			minDate: d.yyyymmdd()+' 00:00:00',
			onClose: function( selectedDate ) {
				$( "#EndDate" ).datepicker( "option", "minDate", selectedDate );
			}
		});
		$( "#EndDate" ).datetimepicker({
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
				$( "#StartDate" ).datepicker( "option", "maxDate", selectedDate );
			}
		});
		$( "#Deadline" ).datepicker({dateFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			minDate: 0
		});		
	}
	

	//還原輸入的資料/
	function clearInput(){		
		var jdata = {
			"託播單名稱":'',
			"託播單說明":'',
			"廣告期間開始時間":'',
			"廣告期間結束時間":'',
			"廣告可被播出小時時段":'0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23',
			"預約到期時間":'',
			"可否點擊":false,
			"點擊後開啟類型":'',
			"點擊後開啟位址":'',
			"售價":''
		};
		showVal(jdata);
	}
	
	//顯示資料
	function showVal(jdata){
			$("#Name").val(jdata.託播單名稱);
			$("#Info").val(jdata.託播單說明);

			$("#StartDate").val(jdata.廣告期間開始時間);
			$("#EndDate").val(jdata.廣告期間結束時間);
			
			$("#Deadline").val(jdata.預約到期時間.split(" ")[0]);
			$("#售價").val(jdata.售價);
			
			for(var i =0;i<24;i++)
				$('input[name="hours"]')[i].checked = false;
				
			if(jdata.廣告可被播出小時時段!=""){
				var hours = jdata.廣告可被播出小時時段.split(",");
				for(var i in hours)
					$('input[name="hours"]')[hours[i]].checked = true;
			}
			//其他參數
			/*if(typeof(jdata['其他參數'])!='undefined'){
				for( var i in otherConfigObj){
					otherConfigObj[i] = jdata['其他參數'][i];
					$('#configTbody tr td input[order = '+i+']').val(otherConfigObj[i]);
					$('#configTbody tr td input[type="checkbox"][order = '+i+']').prop('checked',(otherConfigObj[i]==1)?true:false);
					if($('#參數名稱'+i).text()=='連動廣告' && $('#positiontype').text() == '專區vod')
							m_setConnectionOrder(otherConfigObj[i]);
					else if($('#參數名稱'+i).text()=='前置廣告連動')
						m_setSEPGConnection(otherConfigObj[i]);
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
			}*/
			
			if(typeof(jdata['其他參數'])!='undefined'){
				var connectAd1=[];
				var connectAd2=[];
				for( var i in otherConfigObj){
					if($('#參數名稱'+i).text()=='連動廣告1')
						connectAd1 = otherConfigObj[i].split(',');
					else if($('#參數名稱'+i).text()=='連動廣告2')
						connectAd2 = otherConfigObj[i].split(',');
					else if($('#參數名稱'+i).text()=='前置廣告連動')
						m_setSEPGConnection(otherConfigObj[i]);
				}
				if($('#連動廣告1').length!=0 ||$('#連動廣告2').length!=0)
					m_setConnectionOrder({'1':connectAd1,'2':connectAd2});
			}
			
			configOption();
	}

	var vodPercentage;
	function save(){
		if(selectedOrder.length==0){
			alert('沒有選擇任何託播單');
			return 0;
		}
		var checkNum=0;
		$('input[name="updateCheckBox"]').each(function() {
			if($(this).prop("checked"))
				checkNum++;
		});
		if(checkNum==0){
			alert('沒有選擇任何欄位');
			return 0;
		}
		
		if($("#NameCB").prop('checked')&&$("#Name").val()==''){
			alert('請填寫託播單名稱');
			return 0;
		}
		
		if($("#StartDateCB").prop('checked')&&$("#StartDate").val()==''){
			alert('請選擇開始日期');
			return 0;
		}
		
		if($("#EndDateCB").prop('checked')&&$("#EndDate").val()==''){
			alert('請選擇結束日期');
			return 0;
		}
		
		if($("#EndDateCB").prop('checked')&&$("#StartDateCB").prop('checked')){
			if($("#StartDate").val()>$("#EndDate").val()){
				alert("起始時間 必須小於 結束時間");
				return 0;
			}
		}
		
		if($("#DeadlineCB").prop('checked')&&$("#StartDateCB").prop('checked')){	
			if($("#Deadline").val()+" 00:00:00">$("#StartDate").val()){
				alert("預約到期時間 必須小於等於 開始時間");
				return 0;
			}
		}
		
		if($("#DeadlineCB").prop('checked')&&$("#EndDateCB").prop('checked')){	
			if($("#Deadline").val()+" 23:59:59">$("#EndDate").val()){
				alert("預約到期時間 必須小於 結束時間");
				return 0;
			}
		}
		
		//將選擇的小時時段轉為ARRAY
		var hours="";
		var temp=new Array();
		$('input[name="hours"]:checked').each(function(){temp.push($(this).val());});
		hours=temp.join(',')
		if(hours==""&&$('#hoursCB').prop('checked')){
			alert("請勾選播出時段");
			return 0;
		}
		
		var StartDate= $("#StartDate").val();
		var EndDate= $("#EndDate").val();
		
		$('#uploadResult_f,#uploadResult_s').empty();

		//專區vod自動分配頭放上限比例計算
		/*$.ajax({
			async: false,
			type : "POST",
			url : '?',
			data: {action:'vod上限比例','託播單':selectedOrder},
			dataType : 'json',
			success :
				function(data){
					if(!data.success)
						alert(data.message);
					else{
						vodPercentage = data.data;
					}
				}
		});*/
		
		var sepgCsmsIndex = [];
		//先取得所有會送出的sepg託播單識別碼
		$.post('../order/ajaxToAPI.php',{'action':'群組託播單','selectedOrder':selectedOrder,'ignoreMaterialChecking':true},
			function(data){		
				if(data['groupArray'].length>0){
					if(confirm('修改「頻道short EPG banner」的託播單時，同CSMS群組且同區域的託播單將一起被修改，是否繼續?')){
						sepgCsmsIndex=data['groupArray'];
						//相同CSMS群組中只需留一張已選託播即可
						for(var i in sepgCsmsIndex){
							//選出已選託播單中不屬於目前CSMS群組的託播單
							selectedOrder =  $.grep(selectedOrder, function( a ) {return ($.inArray(a,sepgCsmsIndex[i]['ids'])==-1);});
							//將目前CSMS群組託播單的第一筆加入
							selectedOrder.push(sepgCsmsIndex[i]['ids'][0]);
						}
						$('#uploadDialog').dialog('open');
						getOrderInfo();
					}
				}
				else{
					$('#uploadDialog').dialog('open');
					getOrderInfo();
				}
			},'json'
		);
		
		//針對每張託播單
		function getOrderInfo(){
			var updatedOrders = [];//記錄成功更新後的託播單
			for(var oId in selectedOrder){
				//取得資料庫中的託播單資料
				$.ajax({
					async: false,
					type : "POST",
					url : "ajaxToDB_Order.php",
					data: {action:'訂單資訊','託播單識別碼':selectedOrder[oId]},
					dataType : 'json',
					success :
					function(json){
						if(json.託播單狀態名稱=='確定'){
							var check=false;
							$('input[name="updateCheckBox"]').not('.materialFileCB').each(function() {
								if($(this).prop("checked"))
									check=true;
							});
							if(check){
								$('#uploadResult_f').append('<p>託播單'+json.託播單識別碼+' 修改失敗: 確定狀態的託播單只能重新指定素材</p>');
								return 0 ;
							}
						}
						
						if($("#NameCB").prop('checked'))
							json.託播單名稱 = $("#Name").val();
						if($("#InfoCB").prop('checked'))
							json.託播單說明 = $("#Info").val();
						if($("#StartDateCB").prop('checked'))
							json.廣告期間開始時間=$('#StartDate').val();
						if($("#EndDateCB").prop('checked'))
							json.廣告期間結束時間=$('#EndDate').val();
						if($("#hoursCB").prop('checked'))
							json.廣告可被播出小時時段=hours;

						if($("#DeadlineCB").prop('checked'))
							json.預約到期時間=($("#Deadline").val()=="")?null:$("#Deadline").val()+" 23:59:59";						
						if($("#售價CB").prop('checked'))
							json.售價=($("#售價").val()=="")?null:$("#售價").val();
						//其他參數檢查與設定
						var check =true;
						$.each(otherConfigObj,function(key,value){
							if($('#configCB'+key).prop('checked')){
								//新增參數
								if($('#是否新增'+key).prop('checked')){
									json.其他參數[key]=value;
									//vod上限比例計算
									/*if($('#參數名稱'+key).text()=='影片投放上限'){
										json.其他參數[key]= Math.round(parseInt(json.其他參數[key],10)*vodPercentage[selectedOrder[oId]]['bakadDisplayMaxPercentage']);
									}
									if($('#參數名稱'+key).text()=='專區排程上限')
										json.其他參數[key]= Math.round(parseInt(json.其他參數[key],10)*vodPercentage[selectedOrder[oId]]['bakadschdDisplayMaxPercentage']);*/
								}
								//移除參數
								else{
									delete json.其他參數[key];
								}
									
							}
						});
						//素材檢查與設定
						$.each(materialObj,function(key,value){
							if(typeof(json.素材[key])=='undefined'){
								//若有修改素材參數，但尚未建立過該順序的託播單素材
								if($('#materialFileCB'+key).prop('checked') || $('#materialClickableCB'+key).prop('checked') 
								|| $('#materialCAdCB'+key).prop('checked') || $('#materialCTypeCB'+key).prop('checked')){
									json.素材[key] = value;
									$.ajax({
										async: false,
										type : "POST",
										url :"ajaxToDB_Order.php",
										data: {action:'取得版位素材與參數','版位識別碼':json.版位識別碼},
										dataType : 'json',
										success :
										function(cog){
											if(cog.success){
												//若沒有勾選 使用版位預設值
												for(var index in cog['版位素材設定']){
													if(cog['版位素材設定'][index]['素材順序']==key){
														var material = cog['版位素材設定'][index];
														if(!$('#materialFileCB'+key).prop('checked')){
															json.素材[key].素材識別碼=null;
														}
														
														if(!$('#materialClickableCB'+key).prop('checked')){
															json.素材[key].可否點擊=material.可否點擊;
														}
														
														if(!$('#materialCAdCB'+key).prop('checked')){
															json.素材[key].點擊後開啟位址=material.點擊後開啟位址;
														}
														
														if(!$('#materialCTypeCB'+key).prop('checked')){
															json.素材[key].點擊後開啟類型=material.點擊後開啟類型;
														}
														break;
													}
												}
											}
										}
									});
								}
							}
							else{
								//已建立的託播單素材
								if($('#materialFileCB'+key).prop('checked')){
									json.素材[key].素材識別碼=value.素材識別碼;
								}
								
								if($('#materialClickableCB'+key).prop('checked')){
									json.素材[key].可否點擊=value.可否點擊;
								}
								
								if($('#materialCAdCB'+key).prop('checked')){
									json.素材[key].點擊後開啟位址=value.點擊後開啟位址;
								}
								
								if($('#materialCTypeCB'+key).prop('checked')){
									json.素材[key].點擊後開啟類型=value.點擊後開啟類型;
								}
							}
							//專區vod強至同步sd與hd的素材屬性
							if(json.版位類型名稱 == '專區vod'){
								for(var i in json.素材){
									json.素材[i].可否點擊 = json.素材[key].可否點擊;
									json.素材[i].點擊後開啟位址 = json.素材[key].點擊後開啟位址;
									json.素材[i].點擊後開啟類型 = json.素材[key].點擊後開啟類型;
								}
							}
						});
						
						//日期檢察
						if(json.廣告期間結束時間<=json.廣告期間開始時間){
							$('#uploadResult_f').append('<p>託播單'+json.託播單識別碼+' 修改失敗: 廣告開始時間必須小於結束時間</p>');
							return 0;
						}
						
						//日期檢察
						if(json.預約到期時間.split(' ')[0]>json.廣告期間結束時間.split(' ')[0]){
							$('#uploadResult_f').append('<p>託播單'+json.託播單識別碼+' 修改失敗:預約到期時間必須小於等於廣告期間結束時間</p>');
							return 0;
						}
						
						//851/852連續時段檢察
						if(json.版位類型名稱 == '首頁banner' || json.版位類型名稱  == '專區banner' || json.版位類型名稱  =='頻道short EPG banner' || json.版位類型名稱  == '專區vod'){
							hoursArray=[];
							hoursArray.push(json.廣告可被播出小時時段.split(','));
							for( var i=1;i<hoursArray[0].length;i++){
								if((parseInt(hoursArray[0][i-1],10)+1)!=parseInt(hoursArray[0][i],10)){
									hoursArray.push(hoursArray[0].slice(0,i));
									hoursArray[0].splice(0,i);
									i=0;
								}
							}
							if (hoursArray.length==2 && hoursArray[0][hoursArray[0].length-1] == '23' && hoursArray[1][0]=='0'){
							}
							else if (hoursArray.length==1){
							}
							else{
								$('#uploadResult_f').append('<p>託播單'+json.託播單識別碼+' 修改失敗: 修改CSMS託播單時段必須連續</p>');
								return 0;
							}
							$.ajax({
								async: false,
								type : "POST",
								url :"newOrder.php",
								data: {檢察連動更動:true,託播單識別碼:json.託播單識別碼,StartDate:json.廣告期間開始時間,EndDate:json.廣告期間結束時間
									  ,廣告可被播出小時時段:json.廣告可被播出小時時段},
								dataType : 'json',
								success:
								function(data){
									if(!data.success)
										$('#uploadResult_f').append('<p>託播單'+json.託播單識別碼+' 修改失敗: '+data.message+'</p>');
									else
										updatedOrders.push(json);
								}
							});
						}
						//852的拆單檢察
						else if(json.版位類型名稱  == '前置廣告投放系統'){
							json = splitOrder_852(json);
							if(json.length != 1){
								$('#uploadResult_f').append('<p>託播單'+json[0].託播單識別碼+' 修改失敗: 修改前置廣告投放系統之託播單時必須不拆單</p>');
								return 0;
							}
							json =json[0];
							updatedOrders.push(json);
						}
						else{
							updatedOrders.push(json);
						}	
					}	
				});//end of ajax
			}
			csmsMcheck(updatedOrders);
		}
		//CSMS素材規則檢查
		function csmsMcheck(updatedOrders){
			$.post("ajaxToDB_Order.php",{"action":"檢察素材CSMS","orders":JSON.stringify(updatedOrders)},
				function(data){
					if(!data['success'])
						alert(data['message']);
					else{
						var alertMessage=[];
						for(var i in data['result']){
							if(!data['result'][i]['success']){
								alertMessage.push(data['result'][i]['message']);
							}
						}
						//有警告訊息，確認是否繼續
						if(alertMessage.length!=0){
							//制做警告訊息是窗
							$('<div id = "tempDia" style="text-align:center"><div width="100%" id = "tempDia_Message"></div><hr>是否繼續?<br><button id ="tempDia_True">是</button>&nbsp;&nbsp;&nbsp;<button id ="tempDia_False">否</button></div>').appendTo('body');
							for(var i in alertMessage){
								$('#tempDia_Message').append('<p>'+alertMessage[i]+'</p>');
							}
							$('#tempDia').dialog({
								width: $(window).width()*0.5,
								height: $(window).height()*0.7,
								modal: true,
								close: function(event,ui){$('#tempDia').remove();}
							});
							$('#tempDia_True').click(function(){
								save();
								$('#tempDia').dialog('close');
							});
							$('#tempDia_False').click(function(){
								$('#tempDia').dialog('close');
							});
						}
						else{
							save();
						}
						function save(){
							for(var i in data['result']){								
								checkTime(updatedOrders[i]);
							}
						}
					}
				}
				,'json'
			);
		}
		
		//檢察版位與素材走期
		function checkTime(order){
			$.post('?',{action:"版位檢察",託播單識別碼:order.託播單識別碼,StartDate:order.廣告期間開始時間,StartDate:order.廣告期間開始時間,EndDate:order.廣告期間結束時間}
				,function(json){
					if(!json.success)
						$('#uploadResult_f').append('<p>託播單'+order.託播單識別碼+' 修改失敗: '+json.message+'</p>');
					else
						checkMetrial(order)
				}
				,'json'
			);
		}
		//檢查素材設定
		function checkMetrial(order){
			$.post(ajaxtodbPath,{"action":"檢察素材","orders":JSON.stringify([order])},
			function(data){
				if(data["success"]){
					updateOrder(order);
				}
				else
					$('#uploadResult_f').append('<p>託播單'+order.託播單識別碼+' 修改失敗: '+data.message+'</p>');
			}
			,'json'
			);
		}
		
		//更新託播單
		function updateOrder(jobject){
			savedOrder = [];
			savedEdit={"delete":[],"edit":[jobject]};
			var bypost ={"action":"儲存更變",
					"orders":JSON.stringify(savedOrder),
					"orderListId":$( "#orderList option:selected" ).val(),
					"edits":JSON.stringify(savedEdit),
					};

			$.post("ajaxToDB_Order.php",
					bypost,
					function(data){
						if(data["dbError"]!=undefined){
							$('#uploadResult_f').append('<p>託播單'+jobject.託播單識別碼+' 修改失敗: '+data.dbError+'</p>');
							return 0;
						}
						if(data["success"]){
								var id = parseInt(jobject.託播單識別碼,10);
								for(var i in sepgCsmsIndex){
									if($.inArray(id,sepgCsmsIndex[i]['ids'])!=-1){
										id = sepgCsmsIndex[i]['ids'].join(',');
										break;
									}
								}
								$('#uploadResult_s').append('<p>託播單'+id+' 修改成功</p>');
								deleteOrder(jobject.託播單識別碼);
						}
						else{
							$('#uploadResult_f').append('<p>託播單'+jobject.託播單識別碼+' 修改失敗: '+data.message+'</p>');
						}
					}
					,'json'
				);
		}
	}
	
	
	function getHours(){
		//將選擇的小時時段轉為String
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
	
	//選擇現有託播單並代入資訊
	function selectOrderFun(){
		$('#mainFram').hide();
		$('#selectOrder').attr('src','selectOrder.php').show();	
		$('#closeSelection').show();
	}
	
	//由selectOrder呼叫，託播單被選擇
	function orderSelected(id){
		$.post("ajaxToDB_Order.php",{"action":"訂單資訊","託播單識別碼":id})
		.done(function(data){
			jdata = JSON.parse(data);
			showVal(jdata)
		});
		$('#selectOrder,#closeSelection').hide();
		$('#mainFram').show();
	}
	
	
	//由tabs的selectOrder呼叫，欲修改的託播單儲存
	function addOrdersToEdit(id,name){
		if($.inArray(id,selectedOrder)==-1){
			selectedOrder.push(id);
			$('#selectedNum').text(selectedOrder.length);
			addSelectedOrder(id);
		}
	}
		
	//增加已選託播單
	function addSelectedOrder(id){
		var $table = $('#selectedOrderTable');
		$.ajax({
			async: false,
			type : "POST",
			url : '?',
			data: {action:'託播單名稱',託播單識別碼:id},
			dataType : 'json',
			success :
				function(json){
					if(json.data['託播單狀態']==0||json.data['託播單狀態']==3||json.data['託播單狀態']==1){
						var tr = $(document.createElement('tr')).appendTo($table);
						$(document.createElement('td')).text(json.data['託播單識別碼']).appendTo(tr);
						$(document.createElement('td')).text(json.data['託播單名稱']).appendTo(tr);
						var btd =$(document.createElement('td')).appendTo(tr);
						//刪除按鈕
						$(document.createElement('button')).text('刪除').click(function(event){
							event.preventDefault();
							var rmTd=$(this).parent();
							var rmTr= rmTd.parent();
							var inIndex = rmTr.parent().children().index(rmTr);
							$(this).parent().parent().remove();
							//從ARRAY中移除
							selectedOrder.splice(inIndex, 1);
							$('#selectedNum').text(selectedOrder.length);
							$('#editOrders')[0].contentWindow.switchChoseBtn(json.data['託播單識別碼'],true);
							initialPositionSetting();
						}).appendTo(btd);
					}
					else{
						alert('此託播單的狀態不允許修改託播單');
						deleteOrder(json.data['託播單識別碼']);
						$('#editOrders')[0].contentWindow.switchChoseBtn(json.data['託播單識別碼'],true);
					}
				}
			});
	}
	
	//從selectedOrder中刪除託播單
	function deleteOrder(id){
		var index = $.inArray(id,selectedOrder);
		if(index!=-1){
			selectedOrder.splice(index, 1);
			$('#selectedNum').text(selectedOrder.length);
		}
		//$('#editOrders').attr( 'src', 'selectOrder.php?returnParentFuncName=addOrdersToEdit&ignoreOrders='+JSON.stringify(selectedOrder));
	}
	
	
 </script>
 
 
</body>
</html>