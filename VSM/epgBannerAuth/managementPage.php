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

<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../../tool/jquery-ui1.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/DataTables-1.10.15/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/FixedColumns-3.2.2/js/dataTables.fixedColumns.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/FixedHeader-3.1.2/js/dataTables.fixedHeader.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/Scroller-1.4.2/js/dataTables.scroller.min.js"></script>
<script type="text/javascript" src="../../tool/datatable/Select-1.2.2/js/dataTables.select.min.js"></script>


<button id = "newBtn" class="ui-button ui-widget ui-corner-all">新增</button>
<button id = "editBtn" class="ui-button ui-widget ui-corner-all">修改</button> 
<button id = "deleteBtn" class="ui-button ui-widget ui-corner-all">刪除</button>
<table id="example" class="display nowrap" cellspacing="0" width="100%">
	<thead>
		<tr>
			<th>MD</th>
			<th>是否停用</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>MD</th>
			<th>是否停用</th>
		</tr>
	</tfoot>
</table>

<div id="dialog-form" title="">
<form>
    <fieldset>
		<label for="MD">MD:</label>
		<input type="text" name="MD" id="MD" class="text ui-widget-content ui-corner-all">
		<hr>
		<label for="status">是否停用:</label>
		<input type="checkbox" name="status" id="status" value="enable" class="ui-corner-all">
		<hr>
    </fieldset>
	<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
</form>
</div>

<script>
var ajaxDataUrl = "ajaxData.php";
var ajaxDbUrl = "ajax_epg_banner_playing_device.php";
 
$(document).ready(function() {
	 var dialog, form;    
	$( "#status" ).prop( "disabled", true );
	//讀取table資料
    var table = $('#example').DataTable( {
        lengthChange: false,
        ajax: ajaxDataUrl,
        select: true
    } );
	
	//資料視窗
	dialog = $( "#dialog-form" ).dialog({
		autoOpen: false,
		height: 400,
		width: 350,
		modal: true,
		close: function() {
			form[ 0 ].reset();
		}
    });

 
    form = dialog.find( "form" ).on( "submit", function( event ) {
		event.preventDefault();
		addMD();
    });
	
	
	//新增按鈕
	$("#newBtn").click(function(){
		$( "#MD" ).prop( "disabled", false );
		$('#status').prop('checked', true);
		dialog.dialog('option', 'title', '新增MD授權資訊');
		dialog.dialog('option', 'buttons',
			{
				"新增": addMD,
				"取消": function() {dialog.dialog( "close" );}
			}
		);
		dialog.dialog( "open" );
	});
	
	//修改按鈕
	$("#editBtn").click(function(){
		$( "#MD" ).prop( "disabled", true );
		var selectedRows = getSelectedRowData();
		if(selectedRows.length!=0){
			var selectedRow = selectedRows[0];
			$('#MD').val(selectedRow['MD']);
			if(selectedRow['status']=="停用")
				$('#status').prop('checked', false);
			else
				$('#status').prop('checked', true);

			dialog.dialog('option', 'title', '修改MD授權資訊');
			dialog.dialog('option', 'buttons',
				{
					"修改": editMD,
					"取消": function() {dialog.dialog( "close" );}
				}
			);
			dialog.dialog( "open" );
		}
	});
	//刪除按鈕
	$("#deleteBtn").click(function(){    
		var selectedRows = getSelectedRowData();
		if(selectedRows.length!=0){
			var MD = selectedRows[0]['MD'];
			if(confirm("確認要刪除該MD("+MD+")的設定資訊?")){
				$.post(ajaxDbUrl,{"action":"deleteMdAuth","MD":MD},
					function(result){
						if(result["success"]){
							alert('刪除成功');
							table.ajax.reload();
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
	function getSelectedRowData(){
		var data=table.rows( { selected: true }).data();
		var feedback = [];
		for (var i=0; i < data.length ;i++){
			var obj = {
				"MD":data[i][0],
				"status":data[i][1],
			}
			feedback.push(obj);
        }
		console.log(feedback);
		return feedback;
	}
	
	
	//增加MD設定
	function addMD() {
		var postData = {
			'MD':$('#MD').val(),
			'status':($('#status').is(":checked")?0:1)
		}
		$.post(ajaxDbUrl,{"action":"updateMdAuth","data":postData},
			function(result){
				if(result["success"]){
					alert('新增成功');
					dialog.dialog( "close" );
					table.ajax.reload();
				}
				else{
					alert(result['message']);
				}
			}
		)
	
	}
	//更新MD設定資料
	function editMD() {
		var postData = {
			'MD':$('#MD').val(),
			'status':($('#status').is(":checked")?1:0)
		}
		$.post(ajaxDbUrl,{"action":"updateMdAuth","data":postData},
			function(result){
				if(result["success"]){
					alert('修改成功');
					dialog.dialog( "close" );
					table.ajax.reload();
				}
				else{
					alert(result['message']);
				}
			}
		)
	
	}
	
} );
 </script>
 
</body>
