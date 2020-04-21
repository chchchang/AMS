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
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
</head>

<body>
<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入版位類型識別碼、名稱、說明查詢" ></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>
<div id = "datagrid"></div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>

<script type="text/javascript">
	var showAminationTime=500;
	
	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				showPositionList();
			}
		});
		
		$('#searchButton').click(function(){
			showPositionList();
		});
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
	});
	
	
	var ajaxtodbPath ="ajaxToDB_Position.php";
	
	showPositionList()
	//顯示搜尋的委刊單列表
	var DG=null;
	function showPositionList(){
		$('#datagrid').html('');
		var bypost={action:'版位類型資料表',searchBy:$('#shearchText').val(),pageNo:1,order:'版位類型識別碼',asc:'ASC'};

		$.post(ajaxtodbPath,bypost,function(json){
				json.header.push('詳細資料');
				for(var row in json.data){
					json.data[row].push(['詳細資料','button']);
				
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
					if(row[x][0]=='詳細資料') {
						$("#dialog_iframe").attr("src","positionTypeForm.php?action=info&id="+row[0][0])
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: 700, width:900, title:"詳細資料"});
						dialog.dialog( "open" );
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post(ajaxtodbPath,bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['詳細資料','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	
	function positionUpdated(){
		$( "#dialog_form" ).dialog('close');
		DG.update();
	}

</script>
</body>
</html>