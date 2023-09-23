<?php 
	include('../tool/auth/auth2.php');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="../tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel='stylesheet' type='text/css' href='../external-stylesheet.css'/>
</head>
<body>
<?php include('../order/_searchOrderUI.php')?>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<div id = "datagrid"></div>
</body>
<script>
	var DG = null;
	$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
	
	//綁定可選擇的版位類型
	$("#_searchOUI_positiontype").bind('_searchOUI_positiontype_iniDone',function(){
		$( "#_searchOUI_positiontype>option" ).each(function(){
			var contex =$(this).text();
			if(contex.includes("Vod插廣告")){
				//$(this).attr('selected', true);
			}
			else if(contex.includes("單一平台banner")){
				$(this).attr('selected', true);
				$("#_searchOUI_positiontype").next().val($(this).text());     
				$("#_searchOUI_positiontype").val($(this).val()).trigger("change");
				console.log($(this).val());
			}
			else if(!contex.includes("單一平台")){
				$(this).remove();
			}
		});
		//$( "#_searchOUI_positiontype" ).trigger('select');
		
	});
	//顯示搜尋的託播單列表
	function showOrderDG(option){
		$('#datagrid').html('');
		var bypost={
				method:'OrderInfoBySearch'
				,searchBy:$('#_searchOUI_searchOrder').val()
				,廣告主識別碼:$('#_searchOUI_adOwner').val()
				,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
				,版位類型識別碼:$('#_searchOUI_positiontype').val()
				,版位識別碼:$("#_searchOUI_position").val()
				,開始時間:$('#_searchOUI_startDate').val()
				,結束時間:$('#_searchOUI_endDate').val()
				,狀態:$('#_searchOUI_orderStateSelectoin').val()
				,素材識別碼:$('#_searchOUI_material').val()
				,素材群組識別碼:$('#_searchOUI_materialGroup').val()
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};
			
		$.post('ajax_updateOrderPara.php',bypost,function(json){
				json.header.push('詳細資料');
				for(var row in json.data)
					json.data[row].push(['更新','button']);
				
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
					var oid = row[0][0];
					var startTime = $("#"+oid+"_startTime").val();
					var endTime = $("#"+oid+"_endTime").val();
					var hours = $("#"+oid+"_hours").val();
					var paras = [];
					$("."+oid+"_orderParaValues").each(
						function(){
							paras[$(this).attr("paraindex")] = $(this).val();
						}
					);
					
					
					var updatebypost={
						method:'OrderUpdate'
						,託播單識別碼:oid
						,廣告期間開始時間:startTime
						,廣告期間結束時間:endTime
						,廣告可被播出小時時段:hours
						,託播單其他參數:paras
					};
					//先更新託播放單資訊
					$.post( "ajax_updateOrderPara.php",updatebypost,function(json){
						if(json.success==true){
							//若成功再更新資料
							var apiBypost = {action:"API送出託播單",託播單識別碼:json.託播單識別碼};
							$.post( '../order/ajaxToAPI.php',apiBypost,function(json2){
								if(json2.success==true){
									alert("託播單送出成功");
								}
								else{
									alert("託播單送出失敗:"+json2.message);
								}
							},'json');
							//alert("託播單送出成功");
						}
						else{
							alert("託播單更新失敗"+json.message);
						}
					},'json'
					);
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('ajax_updateOrderPara.php',bypost,function(json) {
							for(var row in json.data)
							json.data[row].push(['更新','button']);
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
</script>
</html>