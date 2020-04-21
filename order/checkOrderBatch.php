<?php 
	include('../tool/auth/auth.php');
	if(isset($_POST['method'])){		
		if($_POST['method'] == '確定託播單'){
			foreach($_POST['orders'] as $key=>$id){
				$sql='
					UPDATE 託播單
					SET 託播單狀態識別碼 = 1
					WHERE 託播單識別碼=?
					';
				if(!$stmt=$my->prepare($sql)) {
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->bind_param('i',$id)) {
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->execute()) {
					exit('無法執行statement，請聯絡系統管理員！');
				}
			}
			exit(json_encode(array('success'=> true),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '取消確定託播單'){
			foreach($_POST['orders'] as $key=>$id){
				$sql='
					UPDATE 託播單
					SET 託播單狀態識別碼 = 0
					WHERE 託播單識別碼=?
					';
				if(!$stmt=$my->prepare($sql)) {
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->bind_param('i',$id)) {
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->execute()) {
					exit('無法執行statement，請聯絡系統管理員！');
				}
			}
			exit(json_encode(array('success'=> true),JSON_UNESCAPED_UNICODE));
		}
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
<script src="../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
</head>
<body>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<?php include("_searchOrderUI.php")?>
<div style='float:right'>
<button id = 'selectall' class='darkButton'>全選</button> <button id = 'unselectall' class='darkButton'>取消全選</button> <button id = 'selectCurrent' class='darkButton'>全選本頁</button> <button id = 'unselectCurrent' class='darkButton'>取消本頁</button>
</div>
<p style='clean:both'><br></p>
<div id = "datagrid" style='clean:both'></div>
<div class ='basicBlock Center'>
<button id = 'checkall'>確定託播單</button>  <button id = 'uncheckall'>取消確定託播單</button>
</div>
</body>
<script>
	var DG = null;
	$(function() {
		$('#selectall,#uncheckall,#checkall,#unselectall,#selectCurrent,#unselectCurrent').hide();
		$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
	});
	
	
	//顯示搜尋的委刊單列表
	var OrderSelectedOrNot={};//記錄託播單是否被選擇 結構:{orderId:true/false}
	function showOrderDG(){
		$('#selectall,#uncheckall,#checkall,#unselectall,#selectCurrent,#unselectCurrent').show();
		$('#datagrid').html('');
		OrderSelectedOrNot={};
		var bypost={
				searchBy:$('#_searchOUI_searchOrder').val()
				,廣告主識別碼:$('#_searchOUI_adOwner').val()
				,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
				,開始時間:$('#_searchOUI_startDate').val()
				,結束時間:$('#_searchOUI_endDate').val()
				,狀態:$('#_searchOUI_orderStateSelectoin').val()
				,版位類型識別碼:$('#_searchOUI_positiontype').val()
				,版位識別碼:$('#_searchOUI_position').val()
				,素材識別碼:$('#_searchOUI_material').val()
				,素材群組識別碼:$('#_searchOUI_materialGroup').val()
				,全託播單識別碼狀態:[0,1]
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};
		//取的全部的託播單識別碼並建立是否選擇的map
		bypost['method']='全託播單識別碼';
		$.post('ajaxFunction_OrderInfo.php',bypost,function(json){
			for(var row in json){
				OrderSelectedOrNot[json[row]] = false;
			}
		}
		,'json'
		);
		//取得資料
		bypost['method']='OrderInfoBySearch';
		delete bypost['全託播單識別碼狀態'];
		$.post('ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('選擇');
				var stateCol = $.inArray('託播單狀態',json.header);
				for(var row in json.data){
					if(json.data[row][stateCol][0]=='預約'||json.data[row][stateCol][0]=='確定'){
						if(OrderSelectedOrNot[json.data[row][0][0]])
							json.data[row].push(['<input type="checkbox" checked value='+json.data[row][0][0]+'></input>','html']);
						else
							json.data[row].push(['<input type="checkbox" value='+json.data[row][0][0]+'></input>','html']);
					}else
						json.data[row].push(['','text']);
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
					if(row[x][0]=='選擇') {
						$("#dialog_iframe").attr("src",encodeURI("newOrder.php?saveBtnText=修改託播單&update="+row[0][0]))
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"編輯託播單"});
						dialog.dialog( "open" );
					}
				}				
				
				DG.update=function(){
					$.post('ajaxFunction_OrderInfo.php',bypost,function(json) {
							var stateCol = $.inArray('託播單狀態',json.header);
							for(var row in json.data){
								if(json.data[row][stateCol][0]=='預約'||json.data[row][stateCol][0]=='確定'){
									if(OrderSelectedOrNot[json.data[row][0][0]])
										json.data[row].push(['<input type="checkbox" checked value='+json.data[row][0][0]+'></input>','html']);
									else
										json.data[row].push(['<input type="checkbox" value='+json.data[row][0][0]+'></input>','html']);
								}else
									json.data[row].push(['','text']);
							}
							DG.set_data(json.data);
							$('#datagrid').find('input[type="checkbox"]').each(function(){
								$(this).change(function(){
									OrderSelectedOrNot[$(this).val()]=$(this).prop("checked");
								});
							});
						},'json');
				}
				
				$('#datagrid').find('input[type="checkbox"]').each(function(){
					$(this).change(function(){
						if($(this).prop("checked"))
							OrderSelectedOrNot[$(this).val()]=true;
						else
							OrderSelectedOrNot[$(this).val()]=false;
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
	
	//確定與取消確定
	$('#checkall').click(function(){
		$('body').mask('託播單確定中...');
		orders =[];
		for(var id in OrderSelectedOrNot)
			if(OrderSelectedOrNot[id])
				orders.push(id);
		if(orders.length==0){
			alert('未選擇任何託播單');
			$('body').unmask();
			return 0 ;
		}
		$.post('?',{method:'確定託播單',orders:orders},function(json){
			if(json.success){
				alert('勾選的託播單已確定');
				showOrderDG();
			}
			$('body').unmask();
		}
		,'json'
		);
	});
	
	//確定與取消確定
	$('#uncheckall').click(function(){
		$('body').mask('託播單取消確定中...');
		orders =[];
		for(var id in OrderSelectedOrNot)
			if(OrderSelectedOrNot[id])
				orders.push(id);
		
		if(orders.length==0){
			alert('未選擇任何託播單');
			$('body').unmask();
			return 0 ;
		}
		$.post('?',{method:'取消確定託播單',orders:orders},function(json){
			if(json.success){
				alert('勾選的託播單已取消確定');
				showOrderDG();
			}
			$('body').unmask();
		}
		,'json'
		);
	});
	
	
</script>
</html>