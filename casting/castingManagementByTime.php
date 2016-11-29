<?php
	include('../tool/auth/auth.php');
	define('PAGE_SIZE',20);
	if(isset($_POST['action'])){
		if($_POST['action']=='走期取得版位類型排程'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			//總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 版位,託播單,版位 版位類型 
				WHERE 版位.版位識別碼 = 託播單.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼 
				AND 版位.DISABLE_TIME IS NULL AND 版位.DELETED_TIME IS NULL AND 版位類型.DISABLE_TIME IS NULL AND 版位類型.DELETED_TIME IS NULL
				AND	((? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (廣告期間結束時間 BETWEEN ? AND ? ))
			';
			if(!$result=$my->getResultArray($sql,'ssss',$_POST["start"],$_POST["end"],$_POST["start"],$_POST["end"])) $totalRowCount=0;
			$totalRowCount=$result[0]['COUNT'];
				
			$sql ='
				SELECT 版位類型.版位識別碼 AS 版位類型識別碼,版位類型.版位名稱 AS 版位類型名稱,COUNT( CASE 託播單狀態識別碼 WHEN 0 THEN 1 END) AS 預約,COUNT( CASE 託播單狀態識別碼 WHEN 1 THEN 1 END) AS 確定
				,COUNT( CASE 託播單狀態識別碼 WHEN 2 THEN 1 END) AS 送出,COUNT( CASE 託播單狀態識別碼 WHEN 3 THEN 1 END) AS 逾期,COUNT( CASE 託播單狀態識別碼 WHEN 4 THEN 1 END) AS 待處理
				FROM 版位,託播單,版位 版位類型
				WHERE 版位.版位識別碼 = 託播單.版位識別碼 AND 版位.上層版位識別碼 = 版位類型.版位識別碼
				AND 版位.DISABLE_TIME IS NULL AND 版位.DELETED_TIME IS NULL AND 版位類型.DISABLE_TIME IS NULL AND 版位類型.DELETED_TIME IS NULL 
				AND ((? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (廣告期間結束時間 BETWEEN ? AND ? ))
				GROUP BY 版位類型名稱
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			$data=array();
			if($result=$my->getResultArray($sql,'ssssi',$_POST["start"],$_POST["end"],$_POST["start"],$_POST["end"],$fromRowNo))
			foreach($result as $row)
				$data[]=array(
					array($row['版位類型識別碼'],'text'),array($row['版位類型名稱'],'text'),array($row['預約'],'text'),array($row['確定'],'text'),array($row['送出'],'text'),array($row['逾期'],'text'),array($row['待處理'],'text')
				);
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('版位類型識別碼','版位類型名稱','預約','確定','送出','逾期','待處理')
							,'data'=>$data,'sortable'=>array('版位類型識別碼','版位類型名稱','預約','確定','送出','逾期','待處理')),JSON_UNESCAPED_UNICODE);
			exit;
		}
		else if($_POST['action']=='走期取得版位排程'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			//總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 版位,託播單
				WHERE 版位.版位識別碼 = 託播單.版位識別碼 AND 上層版位識別碼 = ? AND 託播單狀態識別碼 IN (0,1,2,4) 
				AND 版位.DISABLE_TIME IS NULL AND 版位.DELETED_TIME IS NULL
				AND((? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (廣告期間結束時間 BETWEEN ? AND ? ))
				GROUP BY 版位名稱
			';
			if(!$result=$my->getResultArray($sql,'issss',$_POST['版位類型識別碼'],$_POST["start"],$_POST["end"],$_POST["start"],$_POST["end"])) $totalRowCount=0;
			$totalRowCount=$result[0]['COUNT'];
			//取得版位資料	
			$sql ='
				SELECT 版位.版位識別碼 AS 版位識別碼,版位名稱
				FROM 版位,託播單
				WHERE 版位.版位識別碼 = 託播單.版位識別碼 AND 上層版位識別碼 = ? AND 託播單狀態識別碼 IN (0,1,2,4)
				AND 版位.DISABLE_TIME IS NULL AND 版位.DELETED_TIME IS NULL
				AND ((? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (廣告期間結束時間 BETWEEN ? AND ? ))
				GROUP BY 版位識別碼
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			$data=array();
			if($result=$my->getResultArray($sql,'issssi',$_POST['版位類型識別碼'],$_POST["start"],$_POST["end"],$_POST["start"],$_POST["end"],$fromRowNo))
			foreach($result as $row){
				//取得託播單時段資料
				$sql ='
					SELECT 廣告可被播出小時時段
					FROM 託播單
					WHERE 版位識別碼 = ? AND 託播單狀態識別碼 IN (0,1,2,4) AND
					((? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間 ) OR (廣告期間結束時間 BETWEEN ? AND ? ))
				';
				if(!$result2=$my->getResultArray($sql,'issss',$row['版位識別碼'],$_POST["start"],$_POST["end"],$_POST["start"],$_POST["end"])) $result2=array();
				$timeCount = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
				foreach($result2 as $row2){
					$temp = explode(",", $row2['廣告可被播出小時時段']);
					//逐時段累加
					foreach($temp as $h){
						$timeCount[intval($h)]++;
					}
				}
				$data[]=array(array($row['版位識別碼'],'text'),array($row['版位名稱'],'text'),array($timeCount[0],'text'),array($timeCount[1],'text'),array($timeCount[2],'text'),array($timeCount[3],'text')
				,array($timeCount[4],'text'),array($timeCount[5],'text'),array($timeCount[6],'text'),array($timeCount[7],'text'),array($timeCount[8],'text'),array($timeCount[9],'text'),array($timeCount[10],'text')
				,array($timeCount[11],'text'),array($timeCount[12],'text'),array($timeCount[13],'text'),array($timeCount[14],'text'),array($timeCount[15],'text'),array($timeCount[16],'text')
				,array($timeCount[17],'text'),array($timeCount[18],'text'),array($timeCount[19],'text'),array($timeCount[20],'text'),array($timeCount[21],'text'),array($timeCount[22],'text')
				,array($timeCount[23],'text'));
			}
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE)
							,'header'=>array('版位識別碼','版位名稱','0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23')
							,'data'=>$data,'sortable'=>array('版位識別碼','版位名稱')),JSON_UNESCAPED_UNICODE);
			exit;
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<style>
td.highlight {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
td.normal {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
td.normal a {background:#DDDDDD !important;border: 1px #888888 solid !important;}
td.ui-datepicker-current-day a {border: 2px #E63F00 solid !important;}
</style>
<script src="../tool/jquery-1.11.1.js"></script>
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/timetable/TimeTable.js?<?=time()?>"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script src="../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css">
</head>
<body>
請選擇日期:</br>
<div><button id="previousDate">上一日</button><input type="text" id="time"><button id="nextDate">下一日</button></div> 
<br>
<div id='datagrid'></div>
<div id='datagrid2'></div>

<div id ='timetable'>
<fieldset>
<div><button id="tablepreviousDate">上一日</button><input type="text" id="tabletime"><button id="tablenextDate">下一日</button></div> 
<legend>預定排程表</legend>
<div id="tables1"></div>
</fieldset>
<br>

<fieldset>
<legend>實際排程表</legend>
<div id="tables2"></div>
</fieldset>
</div>

<div id="orderInfoDiv">
<iframe id="orderInfo" style="width:100%;height:100%"></iframe>
</div>
<div id="dialog_form2">
<iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>
</div>

<script>
//mask event
function maskAll(){
	$('#toolbar',parent.document)[0].contentWindow.$('body').trigger('mask');
	$('#status',parent.document)[0].contentWindow.$('body').trigger('mask');
	$('body').mask('取得資料中...');
};
function unMaskAll(){
	$('#toolbar',parent.document)[0].contentWindow.$('body').trigger('unmask');
	$('#status',parent.document)[0].contentWindow.$('body').trigger('unmask');
	$('body').unmask();
};

$('#timetable').hide();

$( "#time" ).datepicker({	
			dateFormat: "yy-mm-dd",
			showSecond: true,
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			showOn: "button",
			buttonImage: "../tool/pic/calendar16x16.png",
			buttonImageOnly: true,
			buttonText: "Select date",
			showButtonPanel: true,
			onSelect:function() {
				showPositionType();
			}
		});
$('#previousDate').click(function() {
	if($('#time').datepicker('getDate')) {
		$('#time').datepicker('setDate',new Date($('#time').datepicker('getDate').getTime()-86400000));
		showPositionType();
	}
})

$('#nextDate').click(function() {
	if($('#time').datepicker('getDate')) {
		$('#time').datepicker('setDate',new Date($('#time').datepicker('getDate').getTime()+86400000));
		showPositionType();
	}
})
var  orderDetail;
$( "#tabletime" ).datepicker({	
			dateFormat: "yy-mm-dd",
			showSecond: true,
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			showOn: "button",
			buttonImage: "../tool/pic/calendar16x16.png",
			buttonImageOnly: true,
			buttonText: "Select date",
			showButtonPanel: true,
			onSelect:function() {
				showSchedule();
			},
			beforeShowDay: processDates,
			onChangeMonthYear: function(year, month, inst){
				$.post( "../order/ajaxToDB_Order.php", { action: "查詢版位當月排程",版位識別碼:positionId,year:year,month:month}, 
				function(data){
					orderDetail=data;
					$( "#tabletime" ).datepicker( "refresh" );
				},'json'
				);
			}
		});
//為日曆上色
function processDates(date) {
	var stringDate = dateToString(date);
	for(var i in orderDetail){
		if(stringDate>=orderDetail[i]["廣告期間開始時間"].split(" ")[0] && stringDate<=orderDetail[i]["廣告期間結束時間"].split(" ")[0])
			return [true,"highlight"];
	}
	return [true,"normal"];
}
//將date轉為yyyy-MM-dd字串
function dateToString(date){
	var str = "" + date.getDate();
	var pad = "00"
	var getdate = pad.substring(0, pad.length - str.length) + str;
	str = "" +(date.getMonth()+1);
	var getmonth = pad.substring(0, pad.length - str.length) + str;
	var stringDate= date.getFullYear()+"-"+getmonth+"-"+getdate;
	return stringDate;
}
$('#tablepreviousDate').click(function() {
	if($('#tabletime').datepicker('getDate')) {
		$('#tabletime').datepicker('setDate',new Date($('#tabletime').datepicker('getDate').getTime()-86400000));
		showSchedule();
	}
})

$('#tablenextDate').click(function() {
	if($('#tabletime').datepicker('getDate')) {
		$('#tabletime').datepicker('setDate',new Date($('#tabletime').datepicker('getDate').getTime()+86400000));
		showSchedule();
	}
})

$('#orderInfoDiv').dialog({
	autoOpen:false,
	//width:'80%',
	modal:true
});

$( "#dialog_form2" ).dialog(
{	autoOpen: false,
	//width: '70%',
	modal: true
});

//顯示板位類型排程資料
function showPositionType(){
	$('#datagrid').mask('資料處理中...');
	$('#datagrid').empty();
	$('#timetable').hide();
	$("#datagrid2").empty();
	$('#tabletime').datepicker('setDate',new Date($('#time').datepicker('getDate').getTime()));
	
	var bypost = {
		action:'走期取得版位類型排程'
		,start:$('#time').val()+' 00:00:00'
		,end:$('#time').val()+' 23:59:59'
		,pageNo:1
		,order:'版位類型識別碼'
		,asc:'ASC'
		};
	
	$.post('?',bypost,function(json){
			json.header.push('顯示版位資料');
			for(var row in json.data)
				json.data[row].push(['顯示版位資料','button']);
			
			var DG=new DataGrid('datagrid',json.header,json.data);
			DG.set_page_info(json.pageNo,json.maxPageNo);
			DG.set_sortable(json.sortable,true);
			//頁數改變動作
			DG.pageChange=function(toPageNo) {
				bypost.pageNo=toPageNo;
				DG.update();
			}
			//header點擊
			DG.headerOnClick = function(headerName,sort){
				bypost.order=headerName;
				switch(sort){
				case "increase":
					bypost.asc='ASC';
					break;
				case "decrease":
					bypost.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG.update();
			};
			//按鈕點擊
			DG.buttonCellOnClick=function(y,x,row) {
				if(!DG.is_collapsed()){
					if(row[x][0]=='顯示版位資料') {
						showPosition(row[0][0]);
						ptName = row[1][0];
					}
					DG.collapse_row(y);
				}else{
					$('#timetable').hide();
					DG.uncollapse();
					$("#datagrid2").empty();
				}
			}
			
			DG.shearch=function(){
				bypost.searchBy=$('#searchOrderList').val();
				DG.update();
			}
			
			
			DG.update=function(){
				$.post('?',bypost,function(json) {
						for(var row in json.data)
						json.data[row].push(['顯示版位資料','button']);
						DG.set_data(json.data);
					},'json');
			}
			
			$('#datagrid').unmask();
		}
		,'json'
	);
}

//顯示版位排程資料
function showPosition(pid){
	$('#datagrid2').mask('資料處理中...');
	$('#datagrid2').empty();
	
	var bypost = {
		action:'走期取得版位排程'
		,start:$('#time').val()+' 00:00:00'
		,end:$('#time').val()+' 23:59:59'
		,版位類型識別碼:pid
		,pageNo:1
		,order:'版位識別碼'
		,asc:'ASC'
		};
	
	$.post('?',bypost,function(json){
			json.header.push('顯示排程');
			for(var row in json.data)
				json.data[row].push(['顯示排程','button']);
			
			var DG=new DataGrid('datagrid2',json.header,json.data);
			DG.set_page_info(json.pageNo,json.maxPageNo);
			DG.set_sortable(json.sortable,true);
			//頁數改變動作
			DG.pageChange=function(toPageNo) {
				bypost.pageNo=toPageNo;
				DG.update();
			}
			//header點擊
			DG.headerOnClick = function(headerName,sort){
				bypost.order=headerName;
				switch(sort){
				case "increase":
					bypost.asc='ASC';
					break;
				case "decrease":
					bypost.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG.update();
			};
			//按鈕點擊
			DG.buttonCellOnClick=function(y,x,row) {
				if(!DG.is_collapsed()){
					if(row[x][0]=='顯示排程') {
						positionId=row[0][0];
						var selectedDate = $('#time').datepicker('getDate');
						$.post( "../order/ajaxToDB_Order.php", { action: "查詢版位當月排程",版位識別碼:positionId,year:selectedDate.getFullYear(),month:selectedDate.getMonth()}, 
							function(data){
								orderDetail=data;
								$( "#tabletime" ).datepicker( "refresh" );
							},'json'
						);
						showSchedule();
					}
					DG.collapse_row(y);
				}else{
					$('#timetable').hide();
					DG.uncollapse();
				}
			}
			
			DG.shearch=function(){
				bypost.searchBy=$('#searchOrderList').val();
				DG.update();
			}
			
			
			DG.update=function(){
				$.post('?',bypost,function(json) {
						for(var row in json.data)
						json.data[row].push(['顯示排程','button']);
						DG.set_data(json.data);
					},'json');
			}
			
			$('#datagrid2').unmask();
		}
		,'json'
	);
}
var ptName='';//版位類型名稱，在showPositionType中會被更動
var positionId='';//版位識別碼，在showPosition中會被更動
var orderData;
function showSchedule(){
	maskAll();
	$('#timetable').show();
	var startTime=$.datepicker.formatDate('yy-mm-dd',$('#tabletime').datepicker('getDate'))+" 00:00:00";
	var endTime = $.datepicker.formatDate('yy-mm-dd',$('#tabletime').datepicker('getDate'))+" 23:59:59";
	$('#tables1,#tables2').html('');
	
	$.post('ajaxFunction.php',{method:'getSchedule',版位識別碼:positionId,startTime:startTime,endTime:endTime},function(json) {
		var pName=$("#positiontype option:selected").text();
		for(var tablei in json){
			$('#tables1').append('<div id = "TT'+tablei+'"></div>');
			var TT;
			if(pName=="前置廣告投放系統"){
				TT=new CreateTimetable_sequence('TT'+tablei,{託播單:json[tablei]});
			}else if(pName=="首頁banner"||pName=="專區banner"||pName=="頻道short EPG banner"||pName=="專區vod"){
				TT=new CreateTimetable('TT'+tablei,{託播單:json[tablei],託播單代碼標題文字:'託播單識別碼/託播單CSMS群組識別碼'});
			}
			else{
				//沒有可介接的排程表API
				var TT=new CreateTimetable('TT'+tablei,{託播單:json[tablei]});
				unMaskAll();
			}
			TT.clickOnDataCell=function(x,y,rowNo,txId) {
				$('#orderInfo').attr('src','../order/orderInfo.php?parent=投放管理&name='+txId)
				dialog = $( "#orderInfoDiv" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"訂單詳細資料"});
				dialog.dialog('open');
			}
		}
		//實際排程表			
		if(pName=="前置廣告投放系統"){
			//取得實際排程資料852
			var byPost={
				'action':"852取得排程表"
				,'版位識別碼':版位識別碼
				,'date':startTime.split(" ")[0]
			};
			$.ajax({
				url:'ajaxToAPI.php'
				,data:byPost
				,type:'POST'
				,dataType:'json'
				,timeout:5000
				,success:
				function(data){
					if(typeof(data['Error'])!='undefined'){
						alert(data['Error']);
					}else{
						var TT2=new CreateTimetable_sequence('TT2',{託播單:data});
						TT2.clickOnDataCell=function(x,y,rowNo,txId) {
							$('#orderInfo').attr('src','../order/orderInfo.php?apiInfo=true&name='+txId)
							$( "#orderInfoDiv" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"訂單詳細資料"});
							$('#orderInfoDiv').dialog('open');
						}
					}
					unMaskAll();
				}
				,error:function(){
					alert('取得實際排程表失敗!');
					unMaskAll();
				}
			});
		}
		else if(pName=="首頁banner"||pName=="專區banner"||pName=="頻道short EPG banner"||pName=="專區vod"){
			//取得實際排程資料851
			var byPost={
				'action':"851取得排程表"
				,'版位識別碼':版位識別碼
				,'date':startTime.split(" ")[0]
			};
			$.post('ajaxToAPI.php',byPost
				,function(json){
					$.ajax({
					url:json.getUrl
					,type:'GET'
					,timeout:5000
					,success:
						function(data){
							orderData = {};
							var table1={託播單:[],'託播單代碼標題文字':'託播單CSMS群組識別碼'};//存放內廣用
							var table2={託播單:[],'託播單代碼標題文字':'託播單CSMS群組識別碼'};//存放外廣用
							var table3={託播單:[],'託播單代碼標題文字':'託播單CSMS群組識別碼'};//存放預設廣告用
							for(var i in data){	
								data[i]['版位類型名稱'] = pName;
								data[i]['版位識別碼']=版位識別碼;
								if(pName=="首頁banner"||pName=="專區banner"){
									var status = '';
									switch(data[i]['SCHD_STATUS']){
										case '0':
											status = '準備中';
											break;
										case '1':
											status = '上架';
											break;
										case '2':
											status = '下架';
											break;
									}
									/*if(data[i]['SCHD_STATUS']==2)
										continue;*/
									if(startTime===data[i]['SCHD_END_DATE'])
										continue;
									orderData[data[i]['TRANSACTION_ID']] = data[i];
									//取得開始與結束時段
									var hours = getHoursString(data[i]['ASSIGN_START_TIME'],data[i]['ASSIGN_END_TIME'],startTime,data[i]['SCHD_END_DATE']);
									var temp={"託播單代碼":data[i]['TRANSACTION_ID'],
									'hours':hours,
									'upTitle':'['+status+']'+'['+data[i]['AD_NAME']+'] ['+data[i]['SCHD_START_DATE']+'~'+data[i]['SCHD_END_DATE']+'] '}
									if(data[i]['AD_TYPE']==0){
										temp['upTitle']+=' 內廣';
										table1.託播單.push(temp);
									}
									else if(data[i]['AD_TYPE']==1){
										temp['upTitle']+=' 外廣';
										table2.託播單.push(temp);
									}
									data[i]['hours'] = hours.join(',');
								}
								
								else if(pName=="專區vod"){
									var status = '';
									switch(data[i]['BAKADSCHD_STATUS']){
										case '0':
											status = '準備中';
											break;
										case '1':
											status = '上架';
											break;
										case '2':
											status = '下架';
											break;
									}
									/*if(data[i]['BAKADSCHD_STATUS']==2)
										continue;*/
									if(startTime===data[i]['BAKADSCHD_END_DATE'])
										continue;
									orderData[data[i]['BAKADSCHD_TRANSACTION_ID']] = data[i];
									//取得開始與結束時段
									var hours = getHoursString(data[i]['BAKADSCHD_ASSIGN_START_TIME'],data[i]['BAKADSCHD_ASSIGN_END_TIME'],startTime,data[i]['BAKADSCHD_END_DATE']);
									var temp={"託播單代碼":data[i]['BAKADSCHD_TRANSACTION_ID'],
									'hours':hours,
									'upTitle':'['+status+']'+'['+/*data[i]['SD_VODCNT_TITLE']+*/']['
										+data[i]['BAKADSCHD_START_DATE']+'~'+data[i]['BAKADSCHD_END_DATE']+'] 投放次數['+data[i]['BAKADSCHD_HIT_COUNT']+'/'+data[i]['BAKADSCHD_DISPLAY_MAX']+']'
									}
									table1.託播單.push(temp);
									data[i]['hours'] = hours.join(',');
								}
								
								else if(pName=="頻道short EPG banner"){
									switch(data[i]['SEPG_STATUS']){
										case '0':
											status = '準備中';
											break;
										case '1':
											status = '上架';
											break;
										case '2':
											status = '下架';
											break;
									}
									/*if(data[i]['SEPG_STATUS']==2)
										continue;*/
									if(startTime===data[i]['SEPG_END_DATE'])
										continue;
									orderData[data[i]['SEPG_TRANSACTION_ID']] = data[i];
									//取得開始與結束時段
									var hours = getHoursString(data[i]['SEPG_ASSIGN_START_TIME'],data[i]['SEPG_ASSIGN_END_TIME'],startTime,data[i]['SEPG_END_DATE']);
									var temp={"託播單代碼":data[i]['SEPG_TRANSACTION_ID'],
									'hours':hours,
									'upTitle':'['+status+']'+'['+data[i]['AD_NAME']+'] ['+data[i]['SEPG_START_DATE']+'~'+data[i]['SEPG_END_DATE']+'] '}
									if(data[i]['SEPG_DEFAULT_FLAG']==1){
										temp['upTitle']+=' 預設廣告';
										table3.託播單.push(temp);
									}
									else if(data[i]['AD_TYPE']==0){
										temp['upTitle']+=' 內廣';
										table1.託播單.push(temp);
									}
									else if(data[i]['AD_TYPE']==1){
										temp['upTitle']+=' 外廣';
										table2.託播單.push(temp);
									}
									data[i]['hours'] = hours.join(',');
								}
							}
							var tables = [];
							if(table1['託播單'].length != 0)
								tables.push(table1);
							if(table2['託播單'].length != 0)
								tables.push(table2);
							if(table3['託播單'].length != 0)
								tables.push(table3);
							
							if(tables.length == 0){
								tables.push({託播單:[],'託播單代碼標題文字':'託播單CSMS群組識別碼'});
							}
							
							for(var tablei in tables){
								$('#tables2').append('<div id = "TT2'+tablei+'"></div>');
								var TT2=new CreateTimetable('TT2'+tablei,tables[tablei]);
								TT2.clickOnDataCell=function(x,y,rowNo,txId) {
									$('#orderInfo').attr('src','../order/orderInfo.php?apiInfo=true&name='+txId+'&版位類型名稱='+pName);
									$( "#orderInfoDiv" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"訂單詳細資料"});
									$('#orderInfoDiv').dialog('open');
								}
							}
								
							unMaskAll();
						}
						,error:function(){
							alert('取得實際排程表失敗');
							unMaskAll();
						}
					});
				}
				,'json'
			);
		}
	},'json'
	);
}

	//取得開始與結束時段
	function getHoursString(sts,eds,currentDate,endDate){
		var st = parseInt(sts,10)/60;
		var et = parseInt(eds,10)/60;
		var edD = endDate.split(" ");//走期結束日期
		var edtime = edD[1].split(":");//走期結束時間
		var edh = parseInt(edtime[0],10);//走期結束小時
		if(edtime[1]=='00' && edtime[2]=='00')
			edh--;
		var checkendtime=false;
		if(currentDate.split(" ")[0] === edD[0])
			checkendtime = true;
		
		var hours=[];
		if(st<=et)
		for(var t= st;t<et;t++){
			if(checkendtime){
				if(t > edh)
					break;
			}
			hours.push(t);
		}
		else{
			for(var t= 0;t<et;t++){
				if(checkendtime){
					if(t > edh)
						break;
				}
				hours.push(t);
			}
			for(var t= st;t<24;t++){
				if(checkendtime){
					if(t > edh)
					break;
				}
				hours.push(t);
			}
		}
		return hours;
	}

	//由orderInfo呼叫，打開廣告主詳細資料視窗
	function openOnwerInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../adowner/ownerInfoTable.php?ownerid='+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"廣告主詳細資料"});
		$( "#dialog_form2" ).dialog( "open" );
	}
	
	//由orderInfo呼叫，打開委刊單詳細資料視窗
	function openOrderListInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../order/newOrderList.php?action=info&orderListId='+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"委刊單詳細資料"});
		$( "#dialog_form2" ).dialog( "open" );
	}
	
	//由orderInfo呼叫，打開素材詳細資料視窗
	function openMaterialInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../material/materialInfo.php?id='+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"素材詳細資料"});
		$( "#dialog_form2" ).dialog( "open" );
	}
	//由orderInfo呼叫，打開素材群組詳細資料視窗
	function openMaterialGroupInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/searchMaterialGroup.php?showCertainId="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"素材群組資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
	//由orderInfo呼叫，託播單狀態改變
	function orderStateChange(state){
		$('#orderInfoDiv').dialog('close');
		showSchedule();
	}
</script>

</body>
</html>