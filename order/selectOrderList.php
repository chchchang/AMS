<?php
	include('../tool/auth/authAJAX.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"/>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
</head>
<body>

<div id="searchForm" class = "basicBlock">
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入委刊單識別碼、名稱、說明查詢" ></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>

<div id = "datagrid"></div>
<div id = "datagrid2"></div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="dialog_form2"><iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<script type="text/javascript">
	//是否指定顯示的廣告主
	var showAminationTime = 500;
	var selectedOwner = "<?php if(isset($_GET['ownerid'])) echo htmlspecialchars($_GET['ownerid'], ENT_QUOTES, 'UTF-8'); ?>";
		
	$(function() {
		//按下ENTER搜尋
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				$('datagrid2').empty();
				showOrderList({ownerId : selectedOwner});

			}
		}).autocomplete({
			source :function( request, response) {
					$.post( "../order/autoComplete_forOrderSearchBox.php",{term: request.term, method:'委刊單查詢'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
		});
		
		$('#searchButton').click(function(){
			$('datagrid2').empty();
			showOrderList({ownerId : selectedOwner});
		});
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});
	
	$( "#dialog_form,#dialog_form2" ).dialog( {autoOpen: false, modal: true} );
	
	showOrderList({ownerId : selectedOwner});
	//顯示搜尋的委刊單列表
	function showOrderList(option){
		$('#datagrid').html('');
		var bypost={};
		if(typeof(option.search)!='undefined'&&option.search==true)
			bypost={method:'OrderListInfo',searchBy:$('#shearchText').val(),pageNo:1,order:'委刊單識別碼',asc:'DESC'};
		else if(typeof(option.ownerId)!='undefined')
			bypost={method:'OrderListInfo',pageNo:1,order:'委刊單識別碼',asc:'DESC',searchBy:$('#shearchText').val(),廣告主識別碼:option.ownerId};
		else if(typeof(option.orderListId)!='undefined')
			bypost={method:'OrderListInfoByID',pageNo:1,order:'委刊單識別碼',asc:'DESC',委刊單識別碼:option.orderListId};
		$.post('editOrderListByPage.php',bypost,function(json){
				json.header.push('託播單資訊','選擇');
				for(var row in json.data){
					json.data[row].push(['託播單資訊','button'],['選擇','button']);
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
					else if(row[x][0]=='選擇'){
						parent.orderListSelected(DG.getCellText('委刊單識別碼',y),DG.getCellText('委刊單名稱',y));
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#shearchText').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('editOrderListByPage.php',bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['託播單資訊','button'],['選擇','button']);
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
					bypost.searchBy=$('#shearchText').val();
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
	
	/**關閉託播單詳細資訊視窗**/
	function closeOrderInfo(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
			TDG.uncollapse();
		}
	}


	

	
</script>
</body>
</html>