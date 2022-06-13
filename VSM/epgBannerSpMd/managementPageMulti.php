<?php
	include('../../tool/auth/authAJAX.php');
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<link rel="stylesheet" type="text/css" href="../../tool/jquery-ui1.2/jquery-ui.theme.min.css"></script>
<link rel="stylesheet" type="text/css" href="../../tool/jquery-ui1.2/jquery-ui.structure.min.css"></script>
<link rel="stylesheet" type="text/css" href="../../tool/jquery-ui1.2/jquery-ui.min.css"></script>
<link rel="stylesheet" type="text/css" href="../../tool/datatable/DataTables-1.10.15/css/jquery.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="../../tool/datatable/FixedColumns-3.2.2/css/fixedColumns.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="../../tool/datatable/FixedHeader-3.1.2/css/fixedHeader.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="../../tool/datatable/Scroller-1.4.2/css/scroller.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="../../tool/datatable/Select-1.2.2/css/select.dataTables.min.css"/>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css"></link>

<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-ui1.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/DataTables-1.10.15/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/FixedColumns-3.2.2/js/dataTables.fixedColumns.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/FixedHeader-3.1.2/js/dataTables.fixedHeader.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/Scroller-1.4.2/js/dataTables.scroller.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/Select-1.2.2/js/dataTables.select.min.js"></script>
<script src="../../tool/jquery-ui1.2/jquery-ui.js"></script>
<script src="../../tool/HtmlSanitizer.js"></script>


<div id="tabs">
  <ul>
    <li><a href="#deliverTabs-1">現有白名單管理</a></li>
    <li><a href="#deliverTabs-2">白名單檔案匯入管理</a></li>
	<li><a href="#deliverTabs-3">MD查詢白名單</a></li>
  </ul>
  <div id="deliverTabs-1">
	<fieldset>
		<legend>單一平台北區</legend>
		目前MD總數:<a id = "totalMdCountText_N"></a><br>
		<button id = "deleteBtn_N" class="ui-button ui-widget ui-corner-all">刪除</button>
		<table id="dataTable_N" class="display nowrap" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th>白名單單號</th>
					<th>開始日期</th>
					<th>結束日期</th>
					<th>MD數目</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>白名單單號</th>
					<th>開始日期</th>
					<th>結束日期</th>
					<th>MD數目</th>
				</tr>
			</tfoot>
		</table>
	</fieldset>

	<fieldset>
		<legend>單一平台南區</legend>
		目前MD總數:<a id = "totalMdCountText_S"></a><br>
		<button id = "deleteBtn_S" class="ui-button ui-widget ui-corner-all">刪除</button>
		<table id="dataTable_S" class="display nowrap" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th>白名單單號</th>
					<th>開始日期</th>
					<th>結束日期</th>
					<th>MD數目</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>白名單單號</th>
					<th>開始日期</th>
					<th>結束日期</th>
					<th>MD數目</th>
				</tr>
			</tfoot>
		</table>
	</fieldset>
  </div>
  <div id="deliverTabs-2">
	<iframe height = '1800px' width = "100%" frameBorder=0 src = "managementPageMultiImport.php">
	</iframe>
  </div>
  <div id="deliverTabs-3">
  <fieldset>
	<legend>MD查詢名單</legend>
		<input type="text" id="MDSearchText" class="searchInput" value='' placeholder="輸入MD號碼查詢所屬白名單"></input> <button id="MDSearchBtn" class="searchSubmit">查詢</button>
		<div id = "MdSearchResult_N">
		</div>
		<div id = "MdSearchResult_S">
		</div>
	</fieldset>
  </div>
</div>





<script>
var ajaxDataUrl = "ajaxDataAdTargetList.php";
var ajaxDataUrl_S = "ajaxDataAdTargetList_S.php";
var ajaxDbUrl = "ajax_ad_target_list.php";
 
$(document).ready(function() { 
	//tabs UI 設定
	$( "#tabs" ).tabs();
		 
	//讀取table資料
    var table_N = $('#dataTable_N').DataTable( {
        lengthChange: false,
        ajax: ajaxDataUrl,
        select: true
	} );
	
	var table_S = $('#dataTable_S').DataTable( {
        lengthChange: false,
        ajax: ajaxDataUrl_S,
        select: true
    } );
	
	
	
	//MD總數
	$.post(ajaxDbUrl,{"action":"getTotalMDCount","area":"N"},
		function(result){
			if(result["success"]){
				$("#totalMdCountText_N").text(result["data"]);
			}
		},
		'json'
	);
	$.post(ajaxDbUrl,{"action":"getTotalMDCount","area":"S"},
		function(result){
			if(result["success"]){
				$("#totalMdCountText_S").text(result["data"]);
			}
		},
		'json'
	);
	
	//按下ENTER搜尋
	$("#MdSearchResult").keypress(function(event){
		if (event.keyCode == 13){
				searchMd();
		}
	});
	$("#MDSearchBtn").click(function(){
		searchMd();
	});
	//刪除按鈕
	$("#deleteBtn_N").click(function(){    
		var selectedRows = getSelectedRowData(table_N);
		if(selectedRows.length!=0){
			var tid = selectedRows[0]['白名單單號'];
			if(confirm("確認要刪除該白名單("+tid+")的設定資訊?")){
				$.post(ajaxDbUrl,{"action":"deleteTargetList","data":{"ad_target_list_id":tid}},
					function(result){
						if(result["success"]){
							alert('刪除成功');
							table_N.ajax.reload();
						}
						else{
							alert(result['message']);
						}
					}
				)
			}
		}
	});

	$("#deleteBtn_S").click(function(){    
		var selectedRows = getSelectedRowData(table_S);
		if(selectedRows.length!=0){
			var tid = selectedRows[0]['白名單單號'];
			if(confirm("確認要刪除該白名單("+tid+")的設定資訊?")){
				$.post(ajaxDbUrl,{"action":"deleteTargetList","data":{"ad_target_list_id":tid}},
					function(result){
						if(result["success"]){
							alert('刪除成功');
							table_S.ajax.reload();
						}
						else{
							alert(result['message']);
						}
					}
				)
			}
		}
	});
	
	//取得選擇的row
	function getSelectedRowData(table){
		var data=table.rows( { selected: true }).data();
		var feedback = [];
		for (var i=0; i < data.length ;i++){
			var obj = {
				"白名單單號":data[i][0],
				"開始日期":data[i][1],
				"結束日期":data[i][2],
				"MD數目":data[i][3]
			}
			feedback.push(obj);
        }
		console.log(feedback);
		return feedback;
	}
	//以MD搜尋白名單
	function searchMd(){
		var MD = $("#MDSearchText").val();
		console.log(MD);
		$("#MdSearchResult_N,#MdSearchResult_S").empty();
		$.post(ajaxDbUrl,{"action":"getTargetListByMD","area":"N","data":{"MD":MD}},
			function(result){
				if(result["success"]){
					$("#MdSearchResult_N").append("北區:<br>");
					result["data"].forEach(function(value){
						$("#MdSearchResult_N").append(HtmlSanitizer.SanitizeHtml(value)+"<br>");
					}
					);
				}
			},
			'json'
		);
		$.post(ajaxDbUrl,{"action":"getTargetListByMD","area":"S","data":{"MD":MD}},
			function(result){
				if(result["success"]){
					$("#MdSearchResult_S").append("南區:<br>");
					result["data"].forEach(function(value){
						$("#MdSearchResult_S").append(HtmlSanitizer.SanitizeHtml(value)+"<br>");
					}
					);
				}
			},
			'json'
		);
	}
} );
 </script>
 
</body>
