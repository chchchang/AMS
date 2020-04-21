<?php
	include('../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
	if(isset($_POST['method'])){
		if($_POST['method'] == '取得版位資料表'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			$searchBy='%'.$_POST['searchBy'].'%';//搜尋關鍵字
			$positionType =(isset($_POST['positionType'])&&$_POST['positionType']!='')?$_POST['positionType']:'%'; //版位類型
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 版位
				WHERE (版位識別碼 = ? OR 版位說明 LIKE ? OR 版位名稱 LIKE ?) AND 上層版位識別碼 LIKE ? AND DISABLE_TIME IS NULL AND DELETED_TIME IS NULL
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('isss',$_POST['searchBy'],$searchBy,$searchBy,$positionType)) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 版位識別碼,版位名稱,版位說明
				FROM  版位
				WHERE (版位識別碼 = ? OR 版位說明 LIKE ? OR 版位名稱 LIKE ?) AND 上層版位識別碼 LIKE ? AND DISABLE_TIME IS NULL AND DELETED_TIME IS NULL
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('isssi',$_POST['searchBy'],$searchBy,$searchBy,$positionType,$fromRowNo)) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$orders = array();
			while($row=$res->fetch_assoc())
				$orders[]=array(array($row['版位識別碼'],'text'),array($row['版位名稱'],'text'),array(($row['版位說明']==null)?'':$row['版位說明'],'text'));
	
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('版位識別碼','版位名稱','版位說明')
							,'data'=>$orders,'sortable'=>array('版位識別碼','版位名稱','版位說明')),JSON_UNESCAPED_UNICODE);
			exit;
		}
	}
	@include('../tool/auth/auth.php')
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/timetable/TimeTable.js?<?=time()?>"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<style type="text/css">
	#preday{
	margin-right: 5px;
	}

	#nextday{
		margin-left: 5px;
	}

	td.highlight {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
	td.normal {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.normal a {background:#DDDDDD !important;border: 1px #888888 solid !important;}
	td.ui-datepicker-current-day a {border: 2px #E63F00 solid !important;}
	.date{ width:200px}
	</style>
</head>

<body>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="dialog_form2"><iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入版位識別碼、名稱、說明查詢" ></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>
<div id = "datagrid"></div>
<div id = "datagrid2"></div>
<div id = "datagrid3"></div>
<div id = "dateDiv">
	<button id="preday">上一日</button><input type="text" id="datePicker" style="width:100px"></input><button id="nextday">下一日</button>
	<div id="timetables"></div>
</div>
<script type="text/javascript">
	var showAminationTime=500;
	var selectedPositionType="";
	$(function() {
		$("#dateDiv").hide();
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				positionDataGrid();
				$("#datagrid3").empty();
				$("#dateDiv").hide();
			}
		});
		$("#searchButton").click(function(){
				positionDataGrid();		
				$("#datagrid3").empty();
				$("#dateDiv").hide();				
		});
		
		//dialog設定
		$( "#dialog_form" ).dialog(
			{
			autoOpen: false,
			width: '80%',
			height: '80%',
			modal: true
			});
		$( "#dialog_form2" ).dialog({
			autoOpen: false,
			width:	'70%',
			height: '70%',
			modal: true
			});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});//end of $(function{})
	
	var ajaxtodbPath ="ajaxToDB_Position.php";
	var selectedDate = new Date();
	var orderDetail;
	var g_numPerPage=10;
	/**向司服器要求廣告主資料數目**/
	var ODG;//預備用來放datagrid的物件
	var selectedPTId='';//備選擇的版位類型
	positionTypeDataGrid()
	//顯示搜尋的委刊單列表
	var DG=null,mydg=null;
	function positionTypeDataGrid(){
		$('#datagrid').html('');
		var bypost={action:'版位類型資料表',searchBy:'',pageNo:1,order:'版位類型識別碼',asc:'ASC'};

		$.post(ajaxtodbPath,bypost,function(json){
				json.header.push('版位資料');
				for(var row in json.data){
					json.data[row].push(['版位資料','button']);
				}
				mydg=new DataGrid('datagrid',json.header,json.data);
				mydg.set_page_info(json.pageNo,json.maxPageNo);
				mydg.set_sortable(json.sortable,true);
				//頁數改變動作
				mydg.pageChange=function(toPageNo) {
					bypost.pageNo=toPageNo;
					mydg.update();
				}
				//header點擊
				mydg.headerOnClick = function(headerName,sort){
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
					mydg.update();
				};
				//按鈕點擊
				mydg.buttonCellOnClick=function(y,x,row) {
					if(row[x][0]=='版位資料') {
						if(!mydg.is_collapsed()){
						selectedPositionType = row[1][0];
						//版位data grid
						selectedPTId = row[0][0];
						positionDataGrid();
						mydg.collapse_row(y);
						}
						else{
							hideInfoWindow();
							selectedPTId = '';
						}
					}
					
				}
				
				mydg.update=function(){
					$.post(ajaxtodbPath,bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['版位資料','button']);
							}
							mydg.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	/**隱藏視窗**/
	function hideInfoWindow(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
		if(mydg.is_collapsed()){
			mydg.uncollapse();
			$("#datagrid2").children().remove();
		}
		$("#dateDiv").hide();
	}
	

	var pName='',pId='';
	//顯示搜尋的版位列表
	function positionDataGrid(){
		$('#datagrid2').html('');
		var bypost={method:'取得版位資料表',pageNo:1,order:'版位識別碼',asc:'ASC',positionType:selectedPTId,searchBy:$('#shearchText').val()};
		$.post('?',bypost,function(json){
				json.header.push('銷售記錄','排程記錄','詳細資料');
				for(var row in json.data){
					json.data[row].push(['銷售記錄','button'],['排程記錄','button'],['詳細資料','button']);
				}
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
					pId = row[0][0];
					pName = row[1][0];
					if(!DG.is_collapsed()){
						if(row[x][0]== "銷售記錄"){
							//銷售記錄data grid
							showOrder(row[0][0]);
						}
						else if(row[x][0]== "排程記錄"){
							//顯示排程表 利用order的內的ajaxtodb檔案
							$("#dateDiv").show(100);
							//設定日期選擇器
							$( "#datePicker" ).datepicker( "destroy" );
							$( "#datePicker" )
								.datepicker({
									dateFormat: "yy-mm-dd",
									showOn: "button",
									buttonImage: "../tool/pic/calendar16x16.png",
									buttonImageOnly: true,
									buttonText: "Select date",
									//numberOfMonths: 3,
									showButtonPanel: true,
									beforeShowDay: processDates,
									onSelect: function(date) {
										var dateArray = date.split('-');
										selectedDate = new Date(parseInt(dateArray[0],10),parseInt(dateArray[1],10)-1,parseInt(dateArray[2],10));
										setTimeTable();
									},
									onChangeMonthYear: function(year, month, inst){
										$.post( "../order/ajaxToDB_Order.php", { action: "查詢版位當月排程",版位識別碼:row[0][0],year:year,month:month}, 
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
							setTimeTable();
							function processDates(date) {
								var stringDate = dateToString(date);
								for(var i in orderDetail){
									if(stringDate>=orderDetail[i]["廣告期間開始時間"].split(" ")[0] && stringDate<=orderDetail[i]["廣告期間結束時間"].split(" ")[0])
										return [true,"highlight"];
								}
								return [true,"normal"];
							}				
						}else if(row[x][0]== "詳細資料"){
							//新增版位視窗
							if($(".InfoWindow").length>0)
							$(".InfoWindow").remove();
							$('body').append('<iframe id="positionTable" name="positionTable" class = "InfoWindow">');
							$('#positionTable')
							.attr("src",'positionTypeForm.php?action=info&id='+row[0][0])
							.css({'width':'100%','height':'600px'})
							.hide().fadeIn(showAminationTime);
						}
						DG.collapse_row(y);
					}
					else
						hideInfoWindow();
				}
				
				DG.update=function(){
					$.post('?',bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['銷售記錄','button'],['排程記錄','button'],['詳細資料','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
				
				/**隱藏視窗**/
				function hideInfoWindow(){
					if($(".InfoWindow").length>0){
						$(".InfoWindow").remove();
					}
					if(DG.is_collapsed()){
						DG.uncollapse();
						$("#dateDiv").hide();
						$("#datagrid3").empty();
					}
				}	
				$("#datagrid2").hide().slideDown(showAminationTime);
			}
			,'json'
		);
	}
	
	
	//顯示搜尋的託播單列表
	function showOrder(positionId){
		$('#datagrid3').html('');
		var bypost={
				method:'OrderInfoBySearch'
				,版位識別碼:positionId
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};

		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				
				var DG=new DataGrid('datagrid3',json.header,json.data);
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
				DG.update=function(){
					$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json) {
							/*for(var row in json.data)
							json.data[row].push(['詳細資料','button']);*/
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
		$("#datagrid3").hide().slideDown(showAminationTime);
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
		setTimeTable();
	});
	
	//選擇後一天
	$("#nextday").click(function(){
		selectedDate.setDate(selectedDate.getDate()+1);
		$( "#datePicker" ).datepicker("setDate",selectedDate);
		setTimeTable();
	});
	
	//設置timetable
	function setTimeTable(){
		var startTime=$.datepicker.formatDate('yy-mm-dd',$('#datePicker').datepicker('getDate'))+" 00:00:00";
		var endTime = $.datepicker.formatDate('yy-mm-dd',$('#datePicker').datepicker('getDate'))+" 23:59:59";
		$('#timetables').html('');
		
		$.post('../casting/ajaxFunction.php',{method:'getSchedule',版位識別碼:pId,startTime:startTime,endTime:endTime}
		,function(json) {
			var pName = selectedPositionType;
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
					closeInfoWindow();
					onpeOrderInfoDialog(name);
				};			
			}
		}
		,'json'
		);
		
	}

	function closeOrderInfo(){
		closeInfoWindow()
	}
		
	function closeNewOrder(){
		closeInfoWindow()
	}
	
	function closeInfoWindow(){
		if($(".InfoWindow").length>0)
			$(".InfoWindow").hide(100,$(".InfoWindow").remove());
	}

	function closeInfoTable(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
			ODG2.uncollapse();
		}
	}
	
	function onpeOrderInfoDialog(id){
		$(function(){
		$("#dialog_iframe").attr("src",'../order/orderInfo.php?id=moreInfo&name='+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8, title:"訂單資訊"});
		$( "#dialog_form" ).dialog( 'open' );
		});
	}
	
	//由orderInfo呼叫，打開廣告主詳細資料視窗
	function openOnwerInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../adowner/ownerInfoTable.php?ownerid='+id).css({"width":"100%","height":"100%"}); 
		dialog=$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"廣告主詳細資料"});
		dialog=$( "#dialog_form2" ).dialog({title:"廣告主詳細資料"});
		dialog.dialog( "open" );
	}
	
	//由orderInfo呼叫，打開委刊單詳細資料視窗
	function openOrderListInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../order/newOrderList.php?action=info&orderListId='+id).css({"width":"100%","height":"100%"}); 
		dialog=$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"委刊單詳細資料"});
		dialog=$( "#dialog_form2" ).dialog({title:"委刊單詳細資料"});
		dialog.dialog( "open" );
	}
	
	//由orderInfo呼叫，打開素材群組詳細資料視窗
	function openMaterialGroupInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/searchMaterialGroup.php?showCertainId="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"素材群組資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
</script>
</body>
</html>