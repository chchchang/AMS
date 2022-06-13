<?php 
	include('../tool/auth/auth.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == '取得合併資訊'){
			//$_POST['orders'] 原始託播單識別碼 ARRAY
						
			//選取託播單時時間
			$a = array_fill(0, count($_POST['orders']), '?');
			$sql='SELECT 託播單識別碼,廣告期間開始時間,廣告期間結束時間,託播單.版位識別碼,廣告可被播出小時時段,預約到期時間 FROM 託播單
				WHERE 託播單識別碼 IN ('.implode(',',$a).')';
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
			
			//整理各版位要合併的託播單資訊
			$mergeBookingByPosition=[];
			foreach($res as $row){
				//初始化版位託播單記錄
				if(!isset($mergeBookingByPosition[$row['版位識別碼']])){
					$mergeBookingByPosition[$row['版位識別碼']] = [];
					$mergeBookingByPosition[$row['版位識別碼']]['廣告期間開始時間']=$row['廣告期間開始時間'];
					$mergeBookingByPosition[$row['版位識別碼']]['廣告期間結束時間']=$row['廣告期間結束時間'];
					$mergeBookingByPosition[$row['版位識別碼']]['預約到期時間']=$row['預約到期時間'];
					$mergeBookingByPosition[$row['版位識別碼']]['合併託播單']=[];
				}
				//檢查最大結束時間與最小開始時間
				if($row['廣告期間開始時間'] < $mergeBookingByPosition[$row['版位識別碼']]['廣告期間開始時間'])
					$mergeBookingByPosition[$row['版位識別碼']]['廣告期間開始時間'] =  $row['廣告期間開始時間'];
				if($row['廣告期間結束時間'] > $mergeBookingByPosition[$row['版位識別碼']]['廣告期間結束時間'])
					 $mergeBookingByPosition[$row['版位識別碼']]['廣告期間結束時間'] =  $row['廣告期間結束時間'];
				if($row['預約到期時間'] < $mergeBookingByPosition[$row['版位識別碼']]['預約到期時間'])
					 $mergeBookingByPosition[$row['版位識別碼']]['預約到期時間'] =  $row['預約到期時間'];
					
				//記錄合併的託播單單號
				$mergeBookingByPosition[$row['版位識別碼']]['合併託播單'][]=$row['託播單識別碼'];
			}
			//排序合併的託播單識別碼
			foreach($mergeBookingByPosition as $merge){
				asort($merge['合併託播單']);
			}
			exit(json_encode(['success'=>true,'merge'=>$mergeBookingByPosition],JSON_UNESCAPED_UNICODE));
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
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
</head>
<body>
<?php include('../order/_searchOrderUI.php')?>
<div id="dialog_form2"><div id = 'successOrder'></div><div id = 'falseOrder'></div><div id = 'message'></div></div>
<button id = 'selectall' class='darkButton'>全選</button> <button id = 'unselectall' class='darkButton'>取消全選</button> <button id = 'selectCurrent' class='darkButton'>全選本頁</button> <button id = 'unselectCurrent' class='darkButton'>取消本頁</button>
<div style="float:right" > <button id = 'splitBatch'>合併勾選的託播單</button></div>
<div id = "datagrid" style="clear:both"></div>
</body>
<script>
	var DG = null;
	$( "#dialog_form2" ).dialog( {autoOpen: false, modal: true} );
	$('#selectall,#unselectall,#selectCurrent,#unselectCurrent,#splitBatch').hide();
	$('#_searchOUI_orderStateSelectoin').hide();
	Date.prototype.yyyymmdd = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		var dd  = this.getDate().toString();
		return yyyy +'-'+ (mm[1]?mm:"0"+mm[0]) +'-'+ (dd[1]?dd:"0"+dd[0]); // padding
	};
	
	var OrderSelectedOrNot={};//記錄託播單是否被選擇 結構:{orderId:true/false}
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
				for(var row in json.data){
					json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow"></input>','html']);
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
	//開啟合併修改視窗
	$('#splitBatch').click(function(){
		var orderToMerge = [];//記錄要備合併的託播單識別碼
		for(var id in OrderSelectedOrNot){
			if(OrderSelectedOrNot[id])
				orderToMerge.push(id);
		}
		if(orderToMerge.length == 0){
			alert('未選擇任何託播單');
			return 0;
		}
		getMergeInfo(orderToMerge);
	});
	
	function getMergeInfo(orderToMerge){
		$.post('',{'method':'取得合併資訊','orders':orderToMerge}
			,function(data){
				mergeBooking(data['merge']);
			}
			,'json'
		);
	}
	
	function mergeBooking(merge){
		$('#successOrder,#falseOrder,#message').empty();
		$( "#dialog_form2" ).dialog({height: $(window).height()*0.7, width:$(window).width()*0.5, title:"合併銷售前預約託播單",close: function( event, ui ) {DG.update();}}).dialog( "open" );
		
		for(var index in merge){
			var orders = merge[index]['合併託播單'];
			if(orders.length>1){
				getMergeOrders(merge[index]);
			}
			else{
				$('#successOrder').append('<p>託播單'+HtmlSanitizer.SanitizeHtml(orders[0])+'沒有選擇其他同版位的託播單可合併。</p>');
			}
		}
		
		function getMergeOrders(mergeids){
				//取得最大編號的託播單資訊
				$.post("../order/ajaxToDB_Order.php",{"action":"訂單資訊","託播單識別碼":mergeids['合併託播單'][mergeids['合併託播單'].length-1]})
				.done(function(data){
					//更新託播單資訊
					jdata = JSON.parse(data);
					jdata['預約到期時間']=mergeids['預約到期時間'];
					jdata['廣告期間開始時間']=mergeids['廣告期間開始時間'];
					jdata['廣告期間結束時間']=mergeids['廣告期間結束時間'];
					//合併託播單
					$.post('ajaxToDB_Booking.php',{'action':'合併銷售前預約託播單','edit':JSON.stringify([jdata]),'delete':mergeids['合併託播單'].slice(0, mergeids['合併託播單'].length-1)}
						,function(data){
							if(data['success'])
								$('#successOrder').append('<p>'+HtmlSanitizer.SanitizeHtml(data['message'])+'</p>');
							else
								$('#falseOrder').append('<p>'+HtmlSanitizer.SanitizeHtml(data['message'])+'</p>');
						}
						,'json'
					);
					
				});
		}
	}
</script>
</html>