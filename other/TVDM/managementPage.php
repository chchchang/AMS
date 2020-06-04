<?php
	
	include('../../tool/auth/authAJAX.php');
	include('Config_TVDM.php');
	require_once '../../tool/phpExtendFunction.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../../tool/jquery-ui1.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.form.js"></script> 
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<link href="../../tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script type="text/javascript" src="../../tool/datagrid/CDataGrid.js"></script>
<script src="../../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<style type="text/css">
table.sortableTable {
  border: 1px solid #1C6EA4;
  background-color: #ECF5FF;
  width: 100%;
  text-align: left;
  border-collapse: collapse;
}
table.sortableTable td, table.sortableTable th {
  border: 1px solid #AAAAAA;
  padding: 3px 2px;
}
table.sortableTable tbody td {
  font-size: 13px;
}
table.sortableTable tr:nth-child(even) {
  background: #D2E9FF;
}
table.sortableTable thead {
  background: #84C1FF;
  background: -moz-linear-gradient(top, #46A3FF 0%, #66B3FF 66%, #84C1FF 100%);
  background: -webkit-linear-gradient(top, #46A3FF 0%, #66B3FF 66%, #84C1FF 100%);
  background: linear-gradient(to bottom, #46A3FF 0%, #66B3FF 66%, #84C1FF 100%);
  border-bottom: 2px solid #444444;
}
table.sortableTable thead th {
  font-size: 15px;
  font-weight: bold;

  border-left: 2px solid #D2E9FF;
}
table.sortableTable thead th:first-child {
  border-left: none;
}
table.sortableTable tbody tr:hover {
  background-color: #E0E0E0;
  border: 2px solid #9D9D9D;
}

#dialog_iframe_img{
	max-width:  100%;
	display:  flex;
	align-items: center;
	justify-content:  center;
}

</style>
</head>
<body>
<div id="_searchOUI_tabs">
  <ul>
    <li id ='_searchOUI_tabs_li-1' ><a href="#_searchOUI_tabs-1">設定走期條件</a></li>
  </ul>
	<div id ='_searchOUI_tabs-1'>
		開始日期:<input type="text" id="_searchOUI_startDate"></input> 結束日期:<input type="text" id="_searchOUI_endDate"></input>
	</div>
</div>
<div class ='basicBlock'>
<input type="text" id="_searchUI_searchInput" class="searchInput" value='' placeholder="請輸入關鍵字查詢"></input> <button id="_searchUI_searchInputButton" class="searchSubmit">查詢</button>
<input type="checkbox" id="_searchUI_update_only" name="_searchUI_update_only">
<label for="_searchUI_update_only">只顯示待派送TVDM服務</label><br>
</div>
<button id="getRemoteDataBtn" style="float: right;">取的遠端TVDM服務資訊</button>
<div id = "datagrid"></div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id="dialog_form_img"><iframe id="dialog_iframe_img" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>

<script type="text/javascript">
$( document ).ready(function() {
   showDataGrid();
});
//date picker
$( "#_searchOUI_startDate,#_searchOUI_endDate" )
.datepicker({
	dateFormat: "yy-mm-dd",
	changeMonth: true,
	changeYear: true,
	monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
	monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
})
//Enter搜尋
$("#_searchUI_searchInput").keypress(function(event){
	if (event.keyCode == 13){
		showDataGrid();
	}
}).autocomplete({
	source :function( request, response) {
				$.post( "autoComplete_forSearchBox.php",{term: request.term, method:'searchText'},
					function( data ) {
					response(JSON.parse(data));
				})
			}
});

$("#getRemoteDataBtn").click(function(){
	if(confirm("將會用遠端資料覆蓋資料庫，是否繼續?")){
		$.post('ajaxToAPI.php',{action:"getAndSaveTVDMInfo"}
		,function(json){
			if(json["success"]){
				alert("取得遠端資料成功");
			}
		}
		,"json");
	}
});

$('#_searchUI_searchInputButton').click(function(){
	showDataGrid();
});

$( "#_searchOUI_tabs" ).tabs();

var DG = null;
$( "#dialog_form, #dialog_form_img" ).dialog( {autoOpen: false, modal: true} )
$( "#dialog_form" ).on('dialogclose', function(event) {DG.update();});
 
function showDataGrid(){
	$('#datagrid').html('');
	var bypost={
			action:'getTVDMDataGrid'
			,searchBy:$('#_searchUI_searchInput').val()
			,pageNo:1
			,order:'TVDM識別碼'
			,asc:'DESC'
			,只顯示待派送:$("#_searchUI_update_only").prop('checked')
		};
	$.post('ajaxToDb_TVDM.php',bypost,function(json){
			json.header.push('修改',"派送");
			json.header.splice(0,0,['<input type="checkbox">',''])
			DG=new DataGrid('datagrid',json.header,[]);
			DG.set_page_info(json.pageNo,json.maxPageNo);
			DG.set_sortable(json.sortable,true);
			DG.shearch=function(){
				bypost.searchBy=$('#searchOrderList').val();
				DG.update();
			}
			
			
			DG.update=function(){
				$.post('ajaxToDb_TVDM.php',bypost,function(json) {
					for(var row in json.data){
						json.data[row].push(['修改','button']);
						if(json.data[row][10][0]==1){
							json.data[row][10]=['<img src="../../tool/pic/Circle_Red.png">','html'];
							json.data[row].push(['派送','button']);
						}
						else{
							json.data[row][10]=['<img src="../../tool/pic/Circle_Green.png">','html'];
							json.data[row].push(['','text']);
						}
						json.data[row].splice(0,0,['<input type="checkbox">','html'])
					}
					DG.set_data(json.data);
					
					$('#datagrid tbody .urllist').hide();
					//按鈕點擊
					$('#datagrid tbody button').not('#getAll,#putAll').click(buttonOnClick);
					
					//圖片點擊
					$('#datagrid tbody img').click(function(){
						$("#dialog_iframe_img").attr("src",encodeURI($(this).attr("src")))
						.css({"width":"100%","height":"100%"}); 
						$( "#dialog_form_img" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"圖片預覽"}).dialog( "open" );
					});
					
				},'json');
			}
			DG.update();

			
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
			
			
			/*^^^^^全部、取消全部選擇以及批次取得結果、派送影片^^^^^*/
			$('#datagrid tr').first().find('th').first().html('<input type="checkbox" id="selectAllOrNot">');
			
			$('#datagrid').css('width','100%');
			
			$('#selectAllOrNot').click(function(){
				if($(this).prop('checked')){
					$('#datagrid input[type=checkbox]').prop('checked',true);
				}
				else{
					$('#datagrid input[type=checkbox]').prop('checked',!true);
				}
			});
			
			$('#datagrid').prepend('<button id="putAll">批次派送</button>')
			
			$('#putAll').click(function(event){
				$(this).mask('...');
				var selected=$('#datagrid tr').has('input[type=checkbox]:checked').children('td').children('button:contains("派送")');
				selected.click()
				var interval=setInterval(function(){
					if(!selected.isMasked()){
						clearInterval(interval);
						$(event.target).unmask();
					}
				},1000);

			});
		}
		,'json'
	);
};

var buttonOnClick=function(event){	
	function getColByName(colName){
		console.log($('#datagrid td:nth-child('+($('#datagrid th:contains("'+colName+'")')[0].cellIndex+1)+')'));
		return $('#datagrid td:nth-child('+($('#datagrid th:contains("'+colName+'")')[0].cellIndex+1)+')')[event.target.parentElement.parentElement.rowIndex-1];
	}
	function getColValueByName(colName){
		return getColByName(colName).textContent;
	}
	
	var 狀態node=getColByName('遠端同步狀態');
	var TVDMID = getColValueByName('TVDM識別碼');
	
	if(event.target.textContent.substr(0,2)=='修改') {
					$("#dialog_iframe").attr("src",encodeURI('infoPage.php?TVDMId='+TVDMID))
					.css({"width":"100%","height":"100%"}); 
					dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.95, width:$(window).width()*0.95, title:"TVDM資訊設定"});
					dialog.dialog( "open" );
				}
	else if(event.target.textContent.substr(0,2)=='派送') {
		$(狀態node).mask('...');
		$(event.target).mask('處理中...');
		$.post('ajaxToAPI.php',{action:"updateTVDMInfo",TVDM識別碼:TVDMID}
		,function(json){
			if(json["success"]){
				$(event.target).unmask();
				狀態node.innerHTML='<img src="../../tool/pic/Circle_Green.png">';
			}
		}
		,"json");
	}else if(event.target.textContent=='顯示URL') {
		$(event.target).parent().find(".urllist").show();
		$(event.target).text('隱藏URL');
	}
	else if(event.target.textContent=='隱藏URL') {
		$(event.target).parent().find(".urllist").hide();
		$(event.target).text('顯示URL');
	}
}

function closeDialog(){
	$( "#dialog_form" ).dialog("close");
}
</script>
</body>
</html>
