<?php 
	include('../tool/auth/auth.php');
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
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
</head>
<body>
<?php include('../order/_searchOrderUI.php')?>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="dialog_form2"><iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<button id = 'selectall' class='darkButton'>全選</button> <button id = 'unselectall' class='darkButton'>取消全選</button> <button id = 'selectCurrent' class='darkButton'>全選本頁</button> <button id = 'unselectCurrent' class='darkButton'>取消本頁</button>
<div style="float:right" > <button id = 'editBatch'>批次修改勾選的託播單</button> <button id = 'deleteBatch'>批次刪除勾選的託播單</button> </div>
<div id = "datagrid" style="clear:both"></div>
</body>
<script>
	var DG = null;
	$( "#dialog_form,#dialog_form2" ).dialog( {autoOpen: false, modal: true} );
	$('#selectall,#unselectall,#selectCurrent,#unselectCurrent,#editBatch,#deleteBatch,#_searchOUI_orderStateSelectoin').hide();
	var OrderSelectedOrNot={};//記錄託播單是否被選擇 結構:{orderId:true/false}
	//顯示搜尋的託播單列表
	function showOrderDG(option){
		$('#selectall,#unselectall,#selectCurrent,#unselectCurrent,#editBatch,#deleteBatch').show();
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
		}
		,'json'
		);
		//顯示託播單
		bypost['method']='OrderInfoBySearch';
		$('#datagrid').html('');			
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				var oIdIndex = $.inArray('託播單識別碼',json.header);
				json.header.splice(0, 0,['']);
				json.header.push('修改','刪除');
				for(var row in json.data){
					json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow"></input>','html']);
					json.data[row].push(['修改','button'],['刪除','button']);
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
					if(row[x][0]=='修改') {
						$("#dialog_iframe").attr("src",encodeURI('bookingOrder.php?update='+DG.getCellText('託播單識別碼',y)))
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"修改銷售前預約託播單"
						,close: function( event, ui ) {DG.update();}});
						dialog.dialog( "open" );
					}
					else if(row[x][0]=='刪除') {
						if(confirm('刪除受的資訊將無法復原，確定要刪除?')){
							$.post('ajaxToDB_Booking.php',{'action':'刪除銷售前預約託播單','delete':[DG.getCellText('託播單識別碼',y)]},
								function(json){
									alert(json.message);
									DG.update();
								}
								,'json'
							);
						}
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
								json.data[row].push(['修改','button'],['刪除','button']);
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
	//開啟批次修改視窗
	$('#editBatch').click(function(){
		var selectedId=[];
		for(var id in OrderSelectedOrNot){
			if(OrderSelectedOrNot[id])
				selectedId.push(id);
		}
		if(selectedId.length == 0){
			alert('未選擇任何託播單');
			return 0;
		}
		var selectedIdString = selectedId.join(',');
		$("#dialog_iframe").attr("src",encodeURI('editBookingBatch.php?selectedId='+selectedIdString))
		.css({"width":"100%","height":"100%"}); 
		dialog=$( "#dialog_form" ).dialog({
				height: $(window).height()*0.8
				,width:$(window).width()*0.8
				,title:"批次修改銷售前預約託播單"
				,close: function( event, ui ) {
					DG.update();
				}
		});
		dialog.dialog( "open" );
	});
	//批次刪除
	$('#deleteBatch').click(function(){
		var selectedId=[];
		for(var id in OrderSelectedOrNot){
			if(OrderSelectedOrNot[id])
				selectedId.push(id);
		}
		if(selectedId.length == 0){
			alert('未選擇任何託播單');
			return 0;
		}
		if(confirm('刪除受的資訊將無法復原，確定要刪除?')){
			$.post('ajaxToDB_Booking.php',{'action':'刪除銷售前預約託播單','delete':selectedId},
				function(json){
					alert(json.message);
					DG.update();
				}
				,'json'
			);
		}
	
	});
</script>
</html>