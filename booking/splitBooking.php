<?php 
	include('../tool/auth/auth.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == 'getMaxAndMinDateTime'){
			//$_POST['orders'] 原始託播單識別碼 ARRAY
						
			//選取託播單時時間
			$a = array_fill(0, count($_POST['orders']), '?');
			$sql='SELECT 廣告期間開始時間,廣告期間結束時間 FROM 託播單 WHERE 託播單識別碼 IN ('.implode(',',$a).')';
			$para = [];
			$defString = '';
			$para[] = &$defString;
			foreach($_POST['orders'] as $key=>$val){
				$defString.='i';
				$para[] = &$_POST['orders'][$key];
			}
			
			if(!$stmt=$my->prepare($sql)) {
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!call_user_func_array(array($stmt, 'bind_param'), $para)){
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$res=$stmt->get_result()){
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			//找出最小開始時間與最大結束時間
			$stt = '';$edt = '';
			foreach($res as $row){
				if($stt == ''){
					$stt =  $row['廣告期間開始時間'];
					$edt =  $row['廣告期間結束時間'];
				}else{
					if($row['廣告期間開始時間'] > $stt)
						$stt =  $row['廣告期間開始時間'];
					if($row['廣告期間結束時間'] < $edt)
						$edt =  $row['廣告期間結束時間'];
				}		
			}
			exit(json_encode(['最小開始時間'=>$stt,'最大結束時間'=>$edt],JSON_UNESCAPED_UNICODE));
		}
		exit();
	}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
</head>
<body>
<?php include('../order/_searchOrderUI.php')?>
<div id="dialog_form">
<table class = "styledTable2"><tr><th>最小開始日期</th><td><a id ='minDate'></a></td><th>最大結束日期</th><td><a id = 'maxDate'></a></td></tr></table>
<table width='100%' class = "styledTable2"><tr><th>開始</th><th>結束</th><th>刪除</th></tr><tbody id = 'splitTbody'></tbody></table>
<button id = 'addSplit' class = 'darkButton'>新增一組分割時間</button>
<hr><button id = 'commitSplit' style="float:right">確認分割</button>
</div>
<div id="dialog_form2"><div id = 'successOrder'></div><div id = 'falseOrder'></div><div id = 'message'></div></div>
<button id = 'selectall' class='darkButton'>全選</button> <button id = 'unselectall' class='darkButton'>取消全選</button> <button id = 'selectCurrent' class='darkButton'>全選本頁</button> <button id = 'unselectCurrent' class='darkButton'>取消本頁</button>
<div style="float:right" > <button id = 'splitBatch'>批次分割勾選的託播單</button></div>
<div id = "datagrid"  style="clear:both" ></div>
</body>
<script>
	var DG = null;
	$( "#dialog_form,#dialog_form2" ).dialog( {autoOpen: false, modal: true} );
	$('#selectall,#unselectall,#selectCurrent,#unselectCurrent,#splitBatch').hide();
	$('#_searchOUI_orderStateSelectoin').hide();
	Date.prototype.yyyymmdd = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		var dd  = this.getDate().toString();
		return yyyy +'-'+ (mm[1]?mm:"0"+mm[0]) +'-'+ (dd[1]?dd:"0"+dd[0]); // padding
	};
	
	var OrderSelectedOrNot={};//記錄託播單是否被選擇 結構:{orderId:true/false}
	var orderToSplits = [];//記錄要備分割的託播單識別碼
	var minDate = '',maxDate='' ;
	
	//顯示搜尋的託播單列表
	function showOrderDG(option){
		$('#selectall,#unselectall,#selectCurrent,#unselectCurrent,#splitBatch').show();
		OrderSelectedOrNot={};
		var bypost={
			searchBy:$('#_searchOUI_searchOrder').val()
			,廣告主識別碼:$('#_searchOUI_adOwner').val()
			,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
			,版位類型識別碼:$('#_searchOUI_positiontype').val()
			,版位識別碼:$("#_searchOUI_position").val()
			,開始時間:$('#_searchOUI_startDate').val()
			,結束時間:$('#_searchOUI_endDate').val()
			,狀態:6
			,素材識別碼:$('#_searchOUI_material').val()
			,素材群組識別碼:$('#_searchOUI_materialGroup').val()
			,pageNo:1
			,order:'託播單識別碼'
			,asc:'DESC'
			,'全狀態搜尋':true
		};
		//取的全部的託播單識別碼並建立是否選擇的map
		bypost['method']='全託播單識別碼';
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
			for(var row in json){
				OrderSelectedOrNot[json[row]] = false;
			}
		},'json'
		);
		//顯示託播單
		bypost['method']='OrderInfoBySearch';
		$('#datagrid').html('');			
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				var oIdIndex = $.inArray('託播單識別碼',json.header);
				json.header.splice(0, 0,['']);
				json.header.push('分割');
				for(var row in json.data){
					json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow"></input>','html']);
					json.data[row].push(['分割','button']);
				}
				
				DG=new DataGrid('datagrid',json.header,json.data);
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
					if(row[x][0]=='分割') {
						//記錄要被分割的託播單識別碼
						orderToSplits=[DG.getCellText('託播單識別碼',y)];
						//開啟分割視窗
						openSplitDialog();
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json) {
							for(var row in json.data){
								if(OrderSelectedOrNot[json.data[row][0][0]])
									json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow" checked></input>','html']);
								else
									json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow"></input>','html']);
								json.data[row].push(['分割','button']);
							}
							DG.set_data(json.data);
							
							$('#datagrid').find('.chinrow').each(function(){
								$(this).change(function(){
									OrderSelectedOrNot[$(this).val()]=$(this).prop("checked");
								});
							});
						},'json');
				}
				
				$('#datagrid').find('.chinrow').each(function(){
					$(this).change(function(){
						OrderSelectedOrNot[$(this).val()]=$(this).prop("checked");
					});
				});
			}
			,'json'
		);
	}
	
	//全選與全不選
	//全選
	$('#selectall').click(function(){
		for(var id in OrderSelectedOrNot)
			OrderSelectedOrNot[id] = true;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//全不選
	$('#unselectall').click(function(){
		for(var id in OrderSelectedOrNot)
			OrderSelectedOrNot[id] = false;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	//全選本頁
	$('#selectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
			OrderSelectedOrNot[$(this).val()] = true;
		});
	});
	//全不選本頁
	$('#unselectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
			OrderSelectedOrNot[$(this).val()] = false;
		});
	});
	//開啟分割修改視窗
	$('#splitBatch').click(function(){
		orderToSplits=[];
		for(var id in OrderSelectedOrNot){
			if(OrderSelectedOrNot[id])
				orderToSplits.push(id);
		}
		if(orderToSplits.length == 0){
			alert('未選擇任何託播單');
			return 0;
		}
		openSplitDialog();
	});
	
	//開啟分割設定視窗
	function openSplitDialog(){
		$('#splitTbody').empty();
		//取得所選託播單的最小開始時間與最大結束時間
		$.post('',{method:'getMaxAndMinDateTime',orders:orderToSplits},
			function(data){
				minDate =data['最小開始時間'];
				maxDate =data['最大結束時間'];
				$('#maxDate').text(maxDate);
				$('#minDate').text(minDate);
				dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"分割銷售前預約託播單"});
				dialog.dialog( "open" );
			},'json'
		);
	}
	
	//新增分割時段設定
	$('#addSplit').click(function(){
		//新增UI
		var d = new Date();
		var time =$.now();
		var $tr = $('<tr time ="'+time+'"></tr>');
		var $st = $( "<input calss='nonNull' id ='StartDate"+time+"' time ='"+time+"'></input>" ).datetimepicker({	
			dateFormat: "yy-mm-dd",
			showSecond: true,
			timeFormat: 'HH:mm:ss',
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			minDate: minDate,
			maxDate: maxDate,
			onClose: function( selectedDate ) {
				if(selectedDate == '')
				$( "#EndDate"+$(this).attr('time') ).datepicker( "option", "minDate", minDate );
				else
				$( "#EndDate"+$(this).attr('time') ).datepicker( "option", "minDate", selectedDate );
			}
		}).appendTo($('<td align="center"></td>').appendTo($tr));
		var $ed = $( "<input calss='nonNull' id ='EndDate"+time+"' time ='"+time+"'></input>" ).datetimepicker({
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
			minDate: minDate,
			maxDate: maxDate,
			onClose: function( selectedDate ) {
				if(selectedDate == '')
				$( "#StartDate"+$(this).attr('time') ).datepicker( "option", "maxDate", maxDate );
				else
				$( "#StartDate"+$(this).attr('time') ).datepicker( "option", "maxDate", selectedDate );
			}
		}).appendTo($('<td align="center"></td>').appendTo($tr));
		var $dbt = $('<td align="center"><button>刪除</button></td>').click(function(){
			$(this).parent().remove();
		}).appendTo($tr);
		$tr.appendTo($('#splitTbody'));
	});
	
	//確認分割
	$('#commitSplit').click(function(){
		//開啟顯示結果的視窗
		$('#successOrder,#falseOrder,#message').empty();
		$( "#dialog_form2" ).dialog({height: $(window).height()*0.7, width:$(window).width()*0.5, title:"分割銷售前預約託播單",close: function( event, ui ) {DG.update();}}).dialog( "open" );
		
		var check = true;//檢查是否所有的開始與結束時間都已有填值
		var splitTimes=[];
		$('#splitTbody tr').each(function(){
			var time = $(this).attr('time');
			if(typeof(time)!='undefined'){
				if($('#StartDate'+time).val()==''||$('#EndDate'+time).val()==''){
					check = false;
					return 0;
				}	
				splitTimes.push([$('#StartDate'+time).val(),$('#EndDate'+time).val()]);
			}
		});
		if(!check){
			alert('請設定所有新增的開始與結束時間');
			return 0;
		}
		$('#dialog_form').dialog('close');
		//逐個取得託播單資訊，
		for(var index in orderToSplits){
			$.post("../order/ajaxToDB_Order.php",{"action":"訂單資訊","託播單識別碼":orderToSplits[index]}
				,function(data){
					var newOrders=[];
					//依照分割的走期複製託播單
					for(var ti in splitTimes){
						if(splitTimes[ti][0]<data['廣告期間開始時間'] || splitTimes[ti][1]>data['廣告期間結束時間']){
							$('#falseOrder').append('<p>銷售前託播單'+data['託播單識別碼']+'分割失敗:託播單原始走期無法包含設定的走期</p>');
							return 0 ;
						}						
						var newOrder = $.extend(true,{},data);
						if(splitTimes[ti][0]>data['廣告期間開始時間'])
							newOrder['廣告期間開始時間']=splitTimes[ti][0];
						if(splitTimes[ti][0]<data['廣告期間結束時間'])
							newOrder['廣告期間結束時間']=splitTimes[ti][1];
						newOrders.push(newOrder);
					}
					//分割託播單AJAX
					$.post("ajaxToDB_Booking.php",{"action":"分割銷售前預約託播單","delete":[data['託播單識別碼']],"orders":JSON.stringify(newOrders)},
						function(data){
							if(data['success'])
								$('#successOrder').append('<p>'+data['message']+'</p>');
							else
								$('#falseOrder').append('<p>'+data['message']+'</p>');
						}
						,'json'
					)
				}
				,'json'
			);
		}
	});
</script>
</html>