<?php
	$sql = 'SELECT 素材類型識別碼,素材類型名稱 FROM 素材類型';
	
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	
	$materialType=array();
	while($row=$res->fetch_assoc()) {
		$materialType[]=array($row['素材類型識別碼'],$row['素材類型名稱']);
	}
	
	$materialType=json_encode($materialType,JSON_UNESCAPED_UNICODE);
?>
<div id="_searchMUI_tabs">
  <ul>
    <li><a href="#_searchMUI_tabs-1">設定有效日期條件</a></li>
    <li><a href="#_searchMUI_tabs-2">設定素材群組條件</a></li>
    <li><a id ="_searchMUI_tabs_nav-CAMPS_date" href="#_searchMUI_tabs-CAMPS_date">設定CAMPS派送日期條件</a></li>
  </ul>
	<div id ='_searchMUI_tabs-1'>
		開始日期:<input type="text" id="_searchMUI_startDate"></input> 結束日期:<input type="text" id="_searchMUI_endDate"></input>
	</div>
	<div id="_searchMUI_tabs-2">
		素材群組:<select id="_searchMUI_materialGroup"></select>
	</div>
	<div id ='_searchMUI_tabs-CAMPS_date'>
		開始日期:<input type="text" id="_searchMUI_startDate_CAMPS"></input> 結束日期:<input type="text" id="_searchMUI_endDate_CAMPS"></input>
	</div>
</div>
<div id="_searchMUI_searchForm" class = "basicBlock">
<input id = "_searchMUI_shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入素材識別碼、類型、名稱、說明查詢" ></input><input type ="button" id = "_searchMUI_searchButton" class="searchSubmit" value="查詢">
<select id="_searchMUI_materialTypeSelectoin"></select>
<input type="checkbox" name="missinFile" id="_searchMUI_missingFileOnly" value="missingFileOnly"></input><a id = "_searchMUI_missingFiletext">僅顯示檔案未到位素材</a>
</div>
<script type="text/javascript">
	$(function() {
		$( "#_searchMUI_tabs_nav-CAMPS_date" ).hide();
		$( "#_searchMUI_tabs" ).tabs();
		//預設隱藏的TABS
		//設訂素材群組資料
		$("#_searchMUI_materialGroup").combobox();
		$.post('../material/ajaxFunction_MaterialInfo.php',{method:'取得素材群組'},
		function(json){
			var materialGroup=json;
			$(document.createElement("option")).text('不指定').val(0).appendTo($("#_searchMUI_materialGroup"));
			for(var i in materialGroup){
				var opt = $(document.createElement("option"));
				opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
				.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
				.appendTo($("#_searchMUI_materialGroup"));
			}
			$( "#_searchMUI_materialGroup" ).combobox();
			$("#_searchMUI_materialGroup").val(0).combobox('setText', '不指定');
		}
		,'json'
		);
		
		//datePicker
		$( "#_searchMUI_startDate,#_searchMUI_endDate,#_searchMUI_startDate_CAMPS,#_searchMUI_endDate_CAMPS" )
		.datepicker({
			dateFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		})
		//按下enter查詢
		$("#_searchMUI_shearchText").keypress(function(event){
			if (event.keyCode == 13){
				getmDataGrid();
			}
		}).autocomplete({
			source :function( request, response) {
						$.post( "../material/autoComplete_forMaterialSearchBox.php",{term: request.term, method:'素材查詢'},
							function( data ) {
							response(JSON.parse(data));
						})
					}
		});;
		
		//查詢按鈕
		$('#_searchMUI_searchButton').click(function(){
			getmDataGrid();
		});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		var materialType=<?=$materialType?>;
		$(document.createElement("option"))
		.text("全部類型")//紀錄版位類型名稱
		.val("")//紀錄版位類型識別碼
		.appendTo($("#_searchMUI_materialTypeSelectoin"));
		for(var i in materialType){
			var opt = $(document.createElement("option"));
			opt.text(materialType[i][1])//紀錄版位類型名稱
			.val(materialType[i][0])//紀錄版位類型識別碼
			.appendTo($("#_searchMUI_materialTypeSelectoin"));
		}
		$("#_searchMUI_materialTypeSelectoin,#_searchMUI_missingFileOnly").change(function(){
			getmDataGrid();
		});

	});
</script>
