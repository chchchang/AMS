<?php
date_default_timezone_set("Asia/Taipei");
?>
<!doctype html>
<head>
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="../../external-stylesheet.css">
</head>
<body>
<input type="text" id="searchTermInput" class="searchInput" value='' placeholder="輸入關鍵字查詢"></input><button id="searchTermInputBtn" class="searchSubmit">查詢</button>
<br>
<table style="width:100%" id = "dataGrid" class = "styledTable2">
<thead>
<tr>
	<th></th>
	<th>product_id</th>
	<th>產品名稱</th> 
	<th>畫質</th> 
	<th>類型</th>
	<th>路徑</th>
	<th>上架時間</th>
	<th>下架時間</th>
</tr>
</thead>
<tbody style="width:100%" id = "dataGridTBody" >
</tbody>
</table>
</body>


<script>
//按下ENTER搜尋
$("#searchTermInput").keypress(function(event){
	if (event.keyCode == 13){
			getDataGrid($("#searchTermInput").val());
	}
})
//點擊搜尋
$('#searchTermInputBtn').click(function(){
		getDataGrid($("#searchTermInput").val());
});

//搜尋資料
function getDataGrid(term){
	$.post( "ajax_vod_bundle_selector.php",{term: term},
		function( data ) {
		response = JSON.parse(data);
		showDataGrid(response)
	})	
}


//顯示資料
function showDataGrid(gridData){
	//清空資料表
	$("#dataGridTBody").empty();
	for(var index in gridData){
		var row = gridData[index];
		var pid = row["product_id"];
		var quality = row["quality"];
		var pn = row["product_name"];
		var ptn = row["product_type_name"];
		var cid = row["category_id"];
		var pen = row["product_name_en"];
		var purl = row["poster_url"];
		var start = row["start_time"];
		var end = row["end_time"];
		$("<tr><td><button class = 'darkButton selectBtn'>選擇</button></td><td class='pid'>"+pid+"</td><td class='pn'>"+pn+"</td><td class='quality'>"+quality+"</td><td class='ptn'>"+ptn+"</td><td class='cid'>"+cid+"</td>"
		+"<td class='start_time'>"+start+"</td><td class='end_time'>"+end+"</td>"
		+"<td class='product_name_en' hidden>"+pen+"</td><td class='poster_url' hidden>"+purl+"</td></tr>").appendTo("#dataGridTBody");
	}
	setSelectBtnTrigger();
}

function setSelectBtnTrigger(){
	$(".selectBtn").click(function(){
		var selectValues = [];
		$(this).parent().siblings().each(function() {
			if($(this).hasClass("pid"))
				selectValues["product_id"] = $(this).text();
			else if($(this).hasClass("pn"))
				selectValues["product_name"] = $(this).text();
			else if($(this).hasClass("ptn"))
				selectValues["quality"] = $(this).text();
			else if($(this).hasClass("quality"))
				selectValues["product_type_name"] = $(this).text();
			else if($(this).hasClass("cid"))
				selectValues["category_id"] = $(this).text();
			else if($(this).hasClass("product_name_en"))
				selectValues["product_name_en"] = $(this).text();
			else if($(this).hasClass("poster_url"))
				selectValues["poster_url"] = $(this).text();
			else if($(this).hasClass("start_time"))
				selectValues["start_time"] = $(this).text();
			else if($(this).hasClass("end_time"))
				selectValues["end_time"] = $(this).text();
		});
		console.log(selectValues);
		window.parent.$("#vodBundleSelectorSubFrame").trigger("selectVod",[selectValues]);
	});
}


</script>
