<?php 
	include('../tool/auth/authAJAX.php');
	$parentFunc = 'orderSelected';
	if(isset($_GET['returnParentFuncName']))
		$parentFunc = htmlspecialchars($_GET["returnParentFuncName"], ENT_QUOTES, 'UTF-8');
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
<?php include('_searchOrderUI.php');?>
<div id = "datagrid"></div>
</body>
<script>
	var DG = null,positionTypeId = '<?=htmlspecialchars((isset($_GET["positionType"]))?$_GET["positionType"]:'', ENT_QUOTES, 'UTF-8')?>'
	,positionId='<?=htmlspecialchars((isset($_GET["position"]))?$_GET["position"]:'', ENT_QUOTES, 'UTF-8')?>';
	if(positionTypeId!='')
		positionTypeId = parseInt(positionTypeId,10);
	if(positionId!='')
		positionId = parseInt(positionId,10);

	
	if(positionTypeId!=''){
		$("#_searchOUI_positiontype").attr('selectedId',positionTypeId).prop('disabled', true).combobox().combobox("disable");
	}

	
	//顯示搜尋的託播單列表
	function showOrderDG(){
		selectedId =[];
		try{
			for(var i in parent.selectedOrder)
			selectedId[i]=parent.selectedOrder[i];
		}
		catch(err){
		}
		
		$('#datagrid').html('');
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
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};

		//取得資料
		bypost['method']='OrderInfoBySearch';
		$.post('ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('選擇');
				for(var row in json.data){
						json.data[row].push(['選擇','button']);
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
					var Col1 = $.inArray('託播單識別碼',json.header);
					var Col3 = $.inArray('託播單名稱',json.header);
					if(row[x][0]=='選擇') {					
						switchChoseBtn(row[0][0],false);
						parent.<?=$parentFunc?>(row[Col1][0],row[Col3][0]);
					}
				}
				
				DG.update=function(){
					$.post('ajaxFunction_OrderInfo.php',bypost,function(json) {
							for(var row in json.data){
									json.data[row].push(['選擇','button']);
							}
							DG.set_data(json.data);
							refreshChoseBtn();
						},'json');
				}
				refreshChoseBtn();
			}
			,'json'
		);
	}
	
	//依照託播單識別碼開啟/取消選擇功能
	function switchChoseBtn(id,enableFlag)
	{
		if(!enableFlag)
			selectedId.push(id);
		else
			selectedId = $.grep(selectedId, function( a ) {
						  return a !== id;
					});
		$('#datagrid >table >tbody >tr >td:first-child').each(
			function(){
				if(parseInt($(this).text())==parseInt(id)){
					//隱藏按鈕
					if(!enableFlag)
						$(this).parent().find('button').hide();
						
					//顯示按鈕
					else{
						$(this).parent().find('button').show();
					}
				}
			}
		);
	}
	
	var selectedId=[];
	//決定目前UI上datagrid中哪些選擇按鈕須被隱藏
	function refreshChoseBtn(){
		$('#datagrid').find('button').show();
		$('#datagrid >table >tbody >tr >td:first-child').each(
			function(){
				if($.inArray( parseInt($(this).text()), selectedId )!=-1)
					$(this).parent().find('button').hide();
				else
					$(this).parent().find('button').show();
			}
		);
	}
	
	
	//更新selectedOrder得值
	function setSelectedOrder(sArray){
		selectedId=[];
		for(var i in sArray)
		selectedId[i]=sArray[i];
		refreshChoseBtn();
	}
</script>
</html>