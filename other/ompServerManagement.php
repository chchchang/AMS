<?php
	
	include('../tool/auth/authAJAX.php');
	//@include('../tool/auth/auth.php')
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/timetable/TimeTable.js?<?=time()?>"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<style type="text/css">

	td.highlight {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
	td.normal {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.normal a {background:#DDDDDD !important;border: 1px #888888 solid !important;}
	td.ui-datepicker-current-day a {border: 2px #E63F00 solid !important;}
	.date{ width:200px}
	</style>
</head>
<a><?print_r(SERVER_SITE)?></a>
<body>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入版位識別碼、名稱、說明查詢" ></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>
<div id = "datagridN" class = "datagride"></div>
<div id = "datagridC"></div>
<div id = "datagridS"></div>

<script type="text/javascript">
	var showAminationTime=500;
	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				positionDataGrid();
				$(".datagride").empty();
			}
		});
		$("#searchButton").click(function(){
				positionDataGrid();		
				$(".datagride").empty();			
		});
		
		//dialog設定
		$( "#dialog_form" ).dialog(
			{
			autoOpen: false,
			width: '80%',
			height: '80%',
			modal: true
			});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});//end of $(function{})
	
	var ajaxtodbPath ="http://localhost/ajaxTest/OMP/OVA_SERVICE.php";
	var g_numPerPage=10;
	/**向司服器要求廣告主資料數目**/
	var ODG;//預備用來放datagrid的物件
	iniDataGrid();
	//顯示搜尋的版位列表
	function iniDataGrid(){
		$('.datagrid').html('');
		var bypost={method:'ToDoName',pageNo:1,order:'SRVC_RECID',asc:'ASC',searchBy:$('#shearchText').val()};
		$.post(ajaxtodbPath,bypost,function(json){
				/*json.header.push('銷售記錄','排程記錄','詳細資料');
				for(var row in json.data){
					json.data[row].push(['銷售記錄','button'],['排程記錄','button'],['詳細資料','button']);
				}*/
				var DG=new DataGrid('datagridN',json.header,json.data);
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
					if(!DG.is_collapsed()){
						if(row[x][0]== "銷售記錄"){
							//銷售記錄data grid
							showOrder(row[0][0]);
						}
						else if(row[x][0]== "排程記錄"){
							//顯示排程表 利用order的內的ajaxtodb檔案
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
					else{
						
					}
						
				}
				
				DG.update=function(){
					$.post('?',bypost,function(json) {
							/*for(var row in json.data){
								json.data[row].push(['銷售記錄','button'],['排程記錄','button'],['詳細資料','button']);
							}*/
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
</script>
</body>
</html>