<?php
	include('../tool/auth/auth.php');
		
	//取得所有版位類型與總表
	if(isset($_POST['method'])&&$_POST['method']==='getPositions') {
		$positions=array();
		
		$sql='
			SELECT 版位類型.版位識別碼 AS 版位類型識別碼,版位類型.版位名稱 AS 版位類型名稱,版位.版位識別碼,版位.版位名稱
			FROM 版位 版位類型 LEFT JOIN 版位 ON 版位類型.版位識別碼=版位.上層版位識別碼
			WHERE 版位類型.上層版位識別碼 IS NULL AND 版位.DISABLE_TIME IS NULL AND 版位.DELETED_TIME IS NULL AND 版位類型.DISABLE_TIME IS NULL AND 版位類型.DELETED_TIME IS NULL
			ORDER BY SUBSTRING_INDEX(版位.版位名稱,"_",-1),CHAR_LENGTH(版位.版位名稱),版位.版位名稱
		';
		
		if(!$my->real_query($sql)) {
			$logger->error('無法執行real_query，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法執行real_query，請聯絡系統管理員！');
		}
		
		if(!$res=$my->use_result()) {
			$logger->error('無法執行use_result，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法執行use_result，請聯絡系統管理員！');
		}
		
		while($row=$res->fetch_assoc()) {
			if(!isset($positions[$row['版位類型識別碼']]['版位類型名稱'])) {
				$positions[$row['版位類型識別碼']]['版位類型識別碼']=$row['版位類型識別碼'];
				$positions[$row['版位類型識別碼']]['版位類型名稱']=$row['版位類型名稱'];
			}
			if($row['版位識別碼']===null)
				$positions[$row['版位類型識別碼']]['版位']=array();
			else
				$positions[$row['版位類型識別碼']]['版位'][]=array('版位識別碼'=>$row['版位識別碼'],'版位名稱'=>$row['版位名稱']);
		}
		
		sort($positions);
		
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($positions,JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	//取得所選託播單得版位資料
	if(isset($_POST['method'])&&$_POST['method']==='getOrderSchMeta') {
		$positions=array();
		
		$sql='
			SELECT 版位類型.版位識別碼 AS 版位類型識別碼,版位類型.版位名稱 AS 版位類型名稱,版位.版位識別碼,版位.版位名稱,廣告期間開始時間
			FROM 託播單 LEFT JOIN 版位 ON 版位.版位識別碼 = 託播單.版位識別碼
				LEFT JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
			WHERE 託播單.託播單識別碼 = ?
		';
		
		$res = $my->getResultArray($sql,'i',$_POST['託播單識別碼']);	
		$row=$res[0];
		
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(['版位類型識別碼'=>$row['版位類型識別碼'],'版位識別碼'=>$row['版位識別碼'],'版位類型名稱'=>$row['版位類型名稱'],'版位名稱'=>$row['版位名稱'],'廣告期間開始時間'=>$row['廣告期間開始時間']],JSON_UNESCAPED_UNICODE);
		exit;
	}
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<style>
button.positionType,button.position {
	width:200px;
	height:50px;
	vertical-align:top;
}
td.highlight {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
td.normal {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
td.normal a {background:#DDDDDD !important;border: 1px #888888 solid !important;}
td.ui-datepicker-current-day a {border: 2px #E63F00 solid !important;}
</style>
<script src="../tool/jquery-3.4.1.min.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/timetable/TimeTable.js?<?=time()?>"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<script src="../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css">
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
	//global variable
	var 版位=[];
	var 版位識別碼=0;
	$(function() {
		$('#time').prop('disabled',true);
		$.post('?',{method:'getPositions'},function(json) {	
			for(i=0;i<json.length;i++) {
				版位[json[i].版位類型識別碼]=json[i];
				var opt = $(document.createElement("option"));
				opt.text(json[i].版位類型名稱)//紀錄版位類型名稱
				.val(json[i].版位類型識別碼)//紀錄版位類型識別碼
				.appendTo($("#positiontype"));
			}
			$("#positiontype").combobox({
				 select: function( event, ui ) {
					$('#position').empty();
					for(i=0;i<版位[this.value].版位.length;i++){
						var opt = $(document.createElement("option"));
						opt.text(版位[this.value].版位[i].版位名稱)//紀錄版位類型名稱
						.val(版位[this.value].版位[i].版位識別碼)//紀錄版位類型識別碼
						.appendTo($("#position"));
					}
					$("#position").combobox('setText','');
				 }
			});
			$("#positiontype").combobox('setText','');
			$("#position").combobox({
					select: function( event, ui ) {
					版位識別碼=this.value;
					setDatePicker(new Date());
				}
			});
		},'json');
		//設定版位選項
		$.post('../order/orderManaging.php',{method:'getPositionTypeSelection'}
			,function(positionTypeOption){
				for(var i in positionTypeOption){
					var opt = $(document.createElement("option"));
					opt.text(positionTypeOption[i][1])//紀錄版位類型名稱
					.val(positionTypeOption[i][0])//紀錄版位類型識別碼
					.appendTo($("#positiontype"));
				}
				setPosition($( "#positiontype option:selected" ).val());
				
				$( "#positiontype" ).combobox({
					 select: function( event, ui ) {
						$("#position").combobox('setText','');
						setPosition(this.value);
					 }
				});
			}
			,'json'
		);
		
		//設定版位資料
		function setPosition(pId){
			$("#position").empty();
			$("#position" ).val("");
			$.post( "../order/ajaxToDB_Order.php", { action: "getPositionByPositionType",版位類型識別碼:pId }, 
				function( data ) {
					for(var i in data){
						var opt = $(document.createElement("option"));
						opt.text(data[i][1])//紀錄版位名稱
						.val(data[i][0])//紀錄版位識別碼
						.appendTo($("#position"));
					}
				}
				,"json"
			);
		}
	

		$( "#position" ).combobox({
			select: function( event, ui ) {
				版位識別碼=this.value;
				setDatePicker(new Date());
			}
		});
		
		$('#previousDate').click(function() {
			if($('#time').datepicker('getDate')) {
				$('#time').datepicker('setDate',new Date($('#time').datepicker('getDate').getTime()-86400000));
				showSchedule();
			}
		})
		
		$('#nextDate').click(function() {
			if($('#time').datepicker('getDate')) {
				$('#time').datepicker('setDate',new Date($('#time').datepicker('getDate').getTime()+86400000));
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
	});
	
	function setDatePicker(selectedDate){
		var orderDetail;
		//設定日期選擇器
		$( "#time" ).datepicker( "destroy" );
		$( "#time" )
			.datepicker({
				dateFormat: "yy-mm-dd",
				showOn: "button",
				buttonImage: "../tool/pic/calendar16x16.png",
				buttonText: "Select date",
				showButtonPanel: true,
				beforeShowDay: processDates,
				changeMonth: true,
				changeYear: true,
				monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
				monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
				onSelect:function() {
					showSchedule();
				},
				onChangeMonthYear: function(year, month, inst){
					$.post( "../order/ajaxToDB_Order.php", { action: "查詢版位當月排程",版位識別碼:$("#position").val(),year:year,month:month}, 
					function(data){
						orderDetail=data;
						$( "#time" ).datepicker( "refresh" );
					},'json'
					);
				}
			})
			.click(function() {
				$('.ui-datepicker-today a', $(this).next()).removeClass('ui-state-highlight ui-state-hover');
				$('.highlight a', $(this).next()).addClass('ui-state-highlight');
			});
		$('#time').datepicker('setDate', selectedDate);
		showSchedule();
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
	}
	
	var orderData;
	function showSchedule() {
		maskAll();
		var startTime=$.datepicker.formatDate('yy-mm-dd',$('#time').datepicker('getDate'))+" 00:00:00";
		var endTime = $.datepicker.formatDate('yy-mm-dd',$('#time').datepicker('getDate'))+" 23:59:59";
		$('#tables1,#tables2').html('');
		
		$.post('ajaxFunction.php',{method:'getSchedule',版位識別碼:版位識別碼,startTime:startTime,endTime:endTime},function(data) {
			var json = JSON.parse(data);
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
							$('#tables2').append('<div id = "TT2"></div>');
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
										var status = '';
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
			else if(pName=="單一平台barker_vod"){
				//取得VSM VOD廣告播放資料
				var byPost={
					'action':"單一平台barker_vod"
					,'版位識別碼':版位識別碼
					,'date':startTime.split(" ")[0]
				};
				var T1data = json[0];
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
							$('#tables2').append('<div id = "TT2"></div>');
							//比照取回的資訊
							for(var T1id in T1data){
								for(var transaction_id in data){
									if(T1data[T1id]["託播單代碼"] == transaction_id){
										T1data[T1id]["upTitle"]+="投放次數:"+data[transaction_id]["play_time"];
										break;
									}
								}
							}								
							var TT2=new CreateTimetable_sequence('TT2',{託播單:T1data});
						}
						unMaskAll();
					}
					,error:function(){
						alert('取得實際排程表失敗!');
						unMaskAll();
					}
				});
			}
		}//,'json'
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
		showSchedule();
		$('#orderInfoDiv').dialog('close');
	}
	
	//開啟託播單查詢視窗
	function openSelectDia(){
		$("#dialog_iframe2").attr("src","../order/selectOrder.php?returnParentFuncName=orderSelected").css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"利用託播單查詢排程"});
		$( "#dialog_form2" ).dialog('open');
	}
	
	//由selectOrder呼叫，託播單被選擇
	function orderSelected(oid,oName){
		$( "#dialog_form2" ).dialog('close');
		$.post('',{'method':'getOrderSchMeta','託播單識別碼':oid}
			,function(data){
				var ptid = data['版位類型識別碼'];
				$("#positiontype").combobox('setText',data['版位類型名稱']).val(ptid);
				$("#positiontype option").each(
					function(){
						if($(this).val == ptid)
							$(this).prop('selected',true);
					}
				);
				$('#position').empty();
				for(i=0;i<版位[ptid].版位.length;i++){
					var opt = $(document.createElement("option"));
					opt.text(版位[ptid].版位[i].版位名稱)//紀錄版位類型名稱
					.val(版位[ptid].版位[i].版位識別碼)//紀錄版位類型識別碼
					.appendTo($("#position"));
				}
				$("#position").combobox('setText',data['版位名稱']).val(data['版位識別碼']);
				版位識別碼 = data['版位識別碼'];
				selectedDate = new Date(data['廣告期間開始時間']);
				setDatePicker(selectedDate);
			}
			,'json'
		);
	}
	
</script>
</head>
<body>
<p>版位類型:<select id="positiontype" name="positiontype"></select> 版位名稱:<select id="position" ></select> <button id='selectOrder' class ='darkButton' onClick='openSelectDia()'>利用託播單查詢排程</button></p>

<div><button id="previousDate">上一日</button><input type="text" id="time"><button id="nextDate">下一日</button></div>
<br>

<fieldset>
<legend>預定排程表</legend>
<div id = 'tables1'>
</div>
</fieldset>
<br>

<fieldset>
<legend>實際排程表</legend>
<div id = 'tables2'>
</div>
</fieldset>

<div id="orderInfoDiv">
<iframe id="orderInfo" style="width:100%;height:100%"></iframe>
</div>
<div id="dialog_form2">
<iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>
</div>
</body>
</html>