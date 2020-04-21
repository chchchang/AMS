<?php
include('../tool/auth/auth.php');	
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
	<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
	<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
	<script src="../tool/jquery-plugin/tableHeadFixer.js"></script>
	<script src="../tool/jquery.loadmask.js"></script>
	<script type="text/javascript" src="../tool/timetable/TimeTable.js?<?=time()?>"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
</head>

<style type="text/css">
th {overflow:hidden;white-space:nowrap;}
#pschedule td,#pschedule th{border-style: solid; border-width: 1px; border-color:#aaaaaa}
#pschedule { border-width: 0px; }
</style>
<body>
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">多日查詢排程</a></li>
    <li><a href="#tabs-2">特定日期排程</a></li>
  </ul>
  <div id="tabs-1">
   <?php include('../position/_positionScheduleUI.php');?>
  </div>
  <div id="tabs-2">
	<p>版位類型:<select id="positiontype"></select> 版位名稱:<select id="position" ></select></p>
	<button id="preday">上一日</button><input type="text" id="datePicker" style="width:100px" readonly></input><button id="nextday">下一日</button>	
	<div id = 'timetables'>
	</div>
  </div>
</div>

<script>
$('#tabs').tabs(); 
var getBookingSch = true;
</script>
<?php include('../position/_positionScheduleScript.php');?>

<script>
	var ajaxtodbPath = "../order/ajaxToDB_Order.php";
	var selectorData=new Array();//紀錄版位類型/版位資料
	$("#dateDiv,#newOrderBtn").hide();
	
	//設定版位選項
	$.post('../order/orderManaging.php',{method:'getPositionTypeSelection'}
		,function(positionTypeOption){
			for(var i in positionTypeOption){
				var opt = $(document.createElement("option"));
				opt.text(positionTypeOption[i][1])//紀錄版位類型名稱
				.val(positionTypeOption[i][0])//紀錄版位類型識別碼
				.appendTo($("#positiontype"));
			}
			setPosition($( "#positiontype option:selected" ).val(),"");
			
			$( "#positiontype" ).combobox({
				 select: function( event, ui ) {
					setPosition(this.value,"");
				 }
			});
		}
		,'json'
	);
	
	//設定版位資料
	function setPosition(pId,selectedName){
		$("#position").empty();
		$.post( ajaxtodbPath, { action: "getPositionByPositionType",版位類型識別碼:pId }, 
			function( data ) {
				for(var i in data){
					var opt = $(document.createElement("option"));
					opt.text(data[i][1])//紀錄版位名稱
					.val(data[i][0])//紀錄版位識別碼
					.appendTo($("#position"));
				}
				var selectedId;
				if(typeof selectedName!='undefined'&&selectedName!=""){
					$( "#position" ).combobox('setText', selectedName);
					for(var i in data)
						if(data[i][1]==selectedName)
							selectedId=data[i][0];
				}
				else{
					if(data.length>0){
						$( "#position" ).combobox('setText',data[0][1]);
						selectedId=data[0][0];
					}
					else
						$( "#position" ).combobox('setText','');
				}
				$( "#position" ).val(selectedId);
				prepareTimeTable(selectedId);
			}
			,"json"
		);
	}
	

	$( "#position" ).combobox({
		select: function( event, ui ) {
			prepareTimeTable(this.value);
		}
	});
	
	var selectedDate = new Date();//切換日期資料用
	var orderDetail;//切換日期資料用
	//初始化TimeTable設定
	function prepareTimeTable(id){
		if(typeof id != 'undefined'&&id!=null){
			//設定日期選擇器
			$( "#datePicker" ).datepicker( "destroy" );
			$( "#datePicker" )
				.datepicker({
					dateFormat: "yy-mm-dd",
					showOn: "button",
					buttonImage: "../tool/pic/calendar16x16.png",
					buttonImageOnly: true,
					buttonText: "Select date",
					showButtonPanel: true,
					beforeShowDay: processDates,
					changeMonth: true,
					changeYear: true,
					monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
					monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
					onSelect: function(date) {
						var dateArray = date.split('-');
						selectedDate = new Date(parseInt(dateArray[0],10),parseInt(dateArray[1],10)-1,parseInt(dateArray[2],10));
						showSchedule();
					},
					onChangeMonthYear: function(year, month, inst){
						$.post( ajaxtodbPath, { action: "查詢版位當月排程",版位識別碼:id,year: year,month: month }, 
						function(data){
							orderDetail=data;
							$( "#datePicker" ).datepicker( "refresh" );
						},'json'
						);
					}
				})
				.click(function() {
					$('.ui-datepicker-today a', $(this).next()).removeClass('ui-state-highlight ui-state-hover');
					$('.highlight a', $(this).next()).addClass('ui-state-highlight');
				});
			$( "#datePicker" ).datepicker("setDate",selectedDate);
			showSchedule();
			function processDates(date) {
				var stringDate = dateToString(date);
				for(var i in orderDetail){
					if(stringDate>=orderDetail[i]["廣告期間開始時間"].split(" ")[0] && stringDate<=orderDetail[i]["廣告期間結束時間"].split(" ")[0])
						return [true,"highlight"];
				}
				return [true,"normal"];
			}
		}
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
	
	//選擇前一天
	$("#preday").click(function(){
		selectedDate.setDate(selectedDate.getDate()-1);
		$( "#datePicker" ).datepicker("setDate",selectedDate);
		showSchedule();
	});
	
	//選擇後一天
	$("#nextday").click(function(){
		selectedDate.setDate(selectedDate.getDate()+1);
		$( "#datePicker" ).datepicker("setDate",selectedDate);
		showSchedule();
	});
	
	//設置timetable
	function showSchedule() {
		var startTime=$.datepicker.formatDate('yy-mm-dd',$('#datePicker').datepicker('getDate'))+" 00:00:00";
		var endTime = $.datepicker.formatDate('yy-mm-dd',$('#datePicker').datepicker('getDate'))+" 23:59:59";
		$('#timetables').html('');
		$.post('../casting/ajaxFunction.php',{method:'getSchedule',版位識別碼:$('#position').val(),startTime:startTime,endTime:endTime,'待確認排程':true}
		,function(json) {
			var pName = $('#positiontype option:selected').text();
			for(var tablei in json){
				$('#timetables').append('<div id = "timetable'+tablei+'"></div>');
				var TT1;
				if(pName=='前置廣告投放系統'){
					TT1=new CreateTimetable_sequence('timetable'+tablei,{託播單:json[tablei]});
				}else if(pName=="首頁banner"||pName=="專區banner"||pName=="頻道short EPG banner"||pName=="專區vod"){
					TT1=new CreateTimetable('timetable'+tablei,{託播單:json[tablei],託播單代碼標題文字:'託播單識別碼/託播單CSMS群組識別碼'});
				}
				else{
					TT1=new CreateTimetable('timetable'+tablei,{託播單:json[tablei]});	
				}
				TT1.clickOnDataCell = function(mx,my,row,name){
					$( "#dialog_form" ).dialog('close');
					var index=-1;
					$("#dialog_iframe").attr("src",'../order/orderInfo.php?name='+name).css({"width":"100%","height":"100%"}); 
					dialog=$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"訂單資訊"});
					dialog.dialog( "open" );
				};			
			}
		}
		,'json'
		);
	}
</script>
</body>
</html>