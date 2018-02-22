<?php 
	include('../tool/auth/auth.php');
	$指訂委刊單 = -1;
	if(isset($_GET["orderListId"])) 
		$指訂委刊單=htmlspecialchars($_GET["orderListId"], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
</head>
<body>
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">依關鍵字搜尋委刊單</a></li>
    <li><a href="#tabs-2">依廣告主選擇委刊單</a></li>
  </ul>
  <div id="tabs-1">
		<input type="text" id="searchOrderList" class="searchInput" value='' placeholder="輸入委刊單識別碼、編號、名稱、說明查詢"></input><button id="searchOrderListButton" class="searchSubmit">查詢</button>
  </div>
  <div id="tabs-2">
		<input type="text" id="searchOwner" class="searchInput" value='' placeholder="輸入廣告主識別碼、廣告主名稱、承銷商名稱、頻道商名稱查詢"></input><button id="searchOwnerButton" class="searchSubmit">查詢</button>
		<div class = 'basicBlock'>
		<div id = "onwerDatagrid"></div>
		</div>
  </div>
</div>

<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="dialog_form2"><iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>

	<div id = "datagrid"></div>
	<div id = "datagrid2"></div>
</body>
<script>
	var DG = null , OwnerDG=null,OrderDG=null ;	
	$(function() {
		//按下ENTER搜尋
		$("#searchOrderList").keypress(function(event){
			if (event.keyCode == 13){
					showOrderList({search : true});
			}
		}).autocomplete({
			source :function( request, response) {
						$.post( "../order/autoComplete_forOrderSearchBox.php",{term: request.term, method:'委刊單查詢'},
							function( data ) {
							response(JSON.parse(data));
						})
					}
		});
		
		$('#searchOrderListButton').click(function(){
				showOrderList({search : true});
		});
		
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		$("#searchOwner").keypress(function(event){
			if (event.keyCode == 13){
					OwnerDG.shearch();
			}
		}).autocomplete({
			source :function( request, response) {
						$.post( "../order/autoComplete_forOrderSearchBox.php",{term: request.term, method:'廣告主查詢'},
							function( data ) {
							response(JSON.parse(data));
						})
					}
		});
		
		$('#searchOwnerButton').click(function(){
				OwnerDG.shearch();
		});
		
		$( "#dialog_form,#dialog_form2" ).dialog( {autoOpen: false, modal: true} );
		$( "#tabs" ).tabs();
	});
	
	
	$('#onwerDatagrid').html('');
	
	//廣告主資料表
	var bypost={method:'AdOwnerInfo',searchBy:$('#searchOwner').val(),pageNo:1,order:'廣告主識別碼',asc:'ASC'};
	$.post('editOrderListByPage.php',bypost,function(json){
		json.header.push('委刊單資料');
		for(var row in json.data){
			json.data[row].push(['委刊單資料','button']);
		}
		OwnerDG=new DataGrid('onwerDatagrid',json.header,json.data);
		OwnerDG.set_page_info(json.pageNo,json.maxPageNo);
		OwnerDG.set_sortable(json.sortable,true);
		//頁數改變動作
		OwnerDG.pageChange=function(toPageNo) {
			bypost.pageNo=toPageNo;
			OwnerDG.update();
		}
		//header點擊
		OwnerDG.headerOnClick = function(headerName,sort){
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
			OwnerDG.update();
		};
		//按鈕點擊
		OwnerDG.buttonCellOnClick=function(y,x,row) {
			if(row[x][0]=='委刊單資料'){
				if(!OwnerDG.is_collapsed()){
					OwnerDG.collapse_row(y);
					showOrderList({ownerId : row[0][0]});
				}
				else{
					$('#datagrid').html('');
					OwnerDG.uncollapse();
				}
			}
		}
		OwnerDG.shearch=function(){
			bypost.searchBy=$('#searchOwner').val();
			OwnerDG.update();
		}
		
		OwnerDG.update=function(){
			$.post('editOrderListByPage.php',bypost,function(json) {
				for(var row in json.data){
					json.data[row].push(['委刊單資料','button']);
				}
				OwnerDG.set_data(json.data);
				},'json');
		}
	}
	,'json'
	);
	
	var 指訂委刊單 = <?=$指訂委刊單?>;
	if(指訂委刊單!=-1){
		$('#tabs').hide();
		showOrderList({orderListId : 指訂委刊單});
	}

	//顯示搜尋的委刊單列表
	function showOrderList(option){
		$('#datagrid').html('');
		var bypost={};
		if(typeof(option.search)!='undefined'&&option.search==true)
			bypost={method:'OrderListInfo',searchBy:$('#searchOrderList').val(),pageNo:1,order:'委刊單識別碼',asc:'DESC'};
		else if(typeof(option.ownerId)!='undefined')
			bypost={method:'OrderListInfoByAdOwner',pageNo:1,order:'委刊單識別碼',asc:'DESC',廣告主識別碼:option.ownerId};
		else if(typeof(option.orderListId)!='undefined')
			bypost={method:'OrderListInfoByID',pageNo:1,order:'委刊單識別碼',asc:'DESC',委刊單識別碼:option.orderListId};
		$.post('editOrderListByPage.php',bypost,function(json){
				json.header.push('託播單資訊','委刊單詳細資訊');
				for(var row in json.data){
					json.data[row].push(['託播單資訊','button'],['委刊單詳細資訊','button']);
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
					if(row[x][0]=='託播單資訊') {
						if(!DG.is_collapsed()){
							DG.collapse_row(y);
							showOrderByOrderList({orderListId:row[0][0]});
						}else{
							$('#datagrid2').html('');
							DG.uncollapse();;
						}
					}
					else if(row[x][0]=='委刊單詳細資訊'){
						$("#dialog_iframe").attr("src",'newOrderList.php?action=info&orderListId='+row[0][0])
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: 400, width:$(window).width()*0.8, title:"編輯委刊單"});
						dialog.dialog( "open" );
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('editOrderListByPage.php',bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['託播單資訊','button'],['委刊單詳細資訊','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
		//顯示搜尋的託播單列表
	function showOrderByOrderList(option){
		$('#datagrid2').html('');
		var bypost={
				method:'OrderInfoBySearch'
				,searchBy:$('#searchOrder').val()
				,委刊單識別碼:option.orderListId
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};

		$.post('ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('詳細資料');
				for(var row in json.data)
					json.data[row].push(['詳細資料','button']);
				
				OrderDG=new DataGrid('datagrid2',json.header,json.data);
				OrderDG.set_page_info(json.pageNo,json.maxPageNo);
				OrderDG.set_sortable(json.sortable,true);
				//頁數改變動作
				OrderDG.pageChange=function(toPageNo) {
					bypost.pageNo=toPageNo;
					OrderDG.update();
				}
				//header點擊
				OrderDG.headerOnClick = function(headerName,sort){
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
					OrderDG.update();
				};
				//按鈕點擊
				OrderDG.buttonCellOnClick=function(y,x,row) {
					if(row[x][0]=='詳細資料') {
						$("#dialog_iframe").attr("src",encodeURI('../order/orderInfo.php?name='+row[0][0]))
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"託播單詳細資料"});
						dialog.dialog( "open" );
					}
				}
				
				OrderDG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					OrderDG.update();
				}
				
				
				OrderDG.update=function(){
					$.post('ajaxFunction_OrderInfo.php',bypost,function(json) {
							for(var row in json.data)
							json.data[row].push(['詳細資料','button']);
							OrderDG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	//由orderInfo呼叫，打開素材詳細資料視窗		
	function openMaterialInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/materialInfo.php?id="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"素材資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
	//由orderInfo呼叫，打開素材群組詳細資料視窗
	function openMaterialGroupInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/searchMaterialGroup.php?showCertainId="+id).css({"width":"100%","height":"100%"});	
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"素材群組資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
	//由orderInfo呼叫，打開廣告主詳細資料視窗
	function openOnwerInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../adowner/ownerInfoTable.php?ownerid='+id).css({"width":"100%","height":"100%"}); 
		dialog=$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"廣告主詳細資料"});
		dialog.dialog( "open" );
	}
	
	//由orderInfo呼叫，打開委刊單詳細資料視窗
	function openOrderListInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../order/newOrderList.php?action=info&orderListId='+id).css({"width":"100%","height":"100%"}); 
		dialog=$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"委刊單詳細資料"});
		dialog.dialog( "open" );
	}
</script>
</html>