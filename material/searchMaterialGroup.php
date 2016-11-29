<?php
	include('../tool/auth/auth.php');
	define('PAGE_SIZE',10);
	
	/*$sql = 'SELECT 素材群組識別碼,素材群組名稱,素材群組說明 FROM 素材群組';
	
	if(!$stmt=$my->prepare($sql)) {
		$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->execute()) {
		$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	
	$materialGroup=array();
	while($row=$res->fetch_assoc()) {
		$materialGroup[]=array($row['素材群組識別碼'],$row['素材群組名稱'],$row['素材群組說明']);
	}
	$materialGroup=json_encode($materialGroup,JSON_UNESCAPED_UNICODE);*/
	
	$sql = 'SELECT 素材類型識別碼,素材類型名稱 FROM 素材類型';
	
	if(!$stmt=$my->prepare($sql)) {
		$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->execute()) {
		$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		exit('無法取得結果集，請聯絡系統管理員！');
	}
	
	$materialType=array();
	while($row=$res->fetch_assoc()) {
		$materialType[]=array($row['素材類型識別碼'],$row['素材類型名稱']);
	}
	$materialType=json_encode($materialType,JSON_UNESCAPED_UNICODE);
	
	$my->close();
	
	//是否要建立選擇按鈕
	$returnToParent='false';
	if(isset($_GET["returnToParent"]))
		$returnToParent='true';
		
	//是否要只顯示特定素材群組
	$showCertainId=0;
	if(isset($_GET["showCertainId"]))
		$showCertainId=intval($_GET["showCertainId"],10);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"/>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<style type="text/css">
</style>

</head>
<body>
<div id="searchForm" class = "basicBlock">
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入素材群組識別碼、名稱、說明查詢" ></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
<div id = "datagrid"></div>
<div id='materialOprion' class = "basicBlock"><select id="materialTypeSelectoin"></select> <input type="checkbox" name="missinFile" id="missinigFileOnly" value="missinigFileOnly">僅顯檔案示未到位素材</div>
<div id = "datagrid2"></div>
<script type="text/javascript">
	var showAminationTime = 500;
	var returnToParent = <?=$returnToParent;?>;
	var showCertainId = <?=$showCertainId?>;
	
	$(function() {
		//按下enter查詢
		$("#searchButton").click(function(){
			if($(".InfoWindow").length>0){
				$(".InfoWindow").remove();
				$('#materialOprion').hide();
			}
			byPost={
				method:'DATAGRID素材群組資訊',
				searchBy:$("#shearchText").val(),
				pageNo:1,
				order:'素材群組識別碼',
				asc:'DESC'
			}
			MDataGrid()
		});	
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				if($(".InfoWindow").length>0){
					$(".InfoWindow").remove();
					$('#materialOprion').hide();
				}
				byPost={
					method:'DATAGRID素材群組資訊',
					searchBy:$("#shearchText").val(),
					pageNo:1,
					order:'素材群組識別碼',
					asc:'DESC'
				}
				MDataGrid()
			}
		});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});
	
	//素材過濾相關設定
	var selectedGroupId = 0;
	var materialType=<?=$materialType?>;
	$(document.createElement("option"))
		.text("全部類型")//紀錄版位類型名稱
		.val("")//紀錄版位類型識別碼
		.appendTo($("#materialTypeSelectoin"));
	for(var i in materialType){
		var opt = $(document.createElement("option"));
		opt.text(materialType[i][1])//紀錄版位類型名稱
		.val(materialType[i][0])//紀錄版位類型識別碼
		.appendTo($("#materialTypeSelectoin"));
	}
	$("#materialTypeSelectoin,#missinigFileOnly").change(function(){
		getmDataGrid();
	});
	$('#materialOprion').hide();
	
	var ajaxtodbPath = "ajaxToDB_Material.php";
	var g_numPerPage=10;

	var TDG;//存放素材群組資料表用
	var TDG2;//存放素材資料表用
	
	var byPost={
		method:'DATAGRID素材群組資訊',
		searchBy:'',
		pageNo:1,
		order:'素材群組識別碼',
		asc:'DESC'
	}
	
	if(showCertainId==0){
		//全部資料表
		MDataGrid();
	}
	else{
		//只顯示某一素材群組
		byPost={
		method:"DATAGRID素材群組資訊",
		searchBy:'',
		pageNo:1,
		order:'素材群組識別碼',
		asc:'DESC',
		"素材群組識別碼":showCertainId
		}
		MDataGrid();
	}
	
	/**建立託播表單**/
	function MDataGrid(){
		$.post('ajaxFunction_MaterialInfo.php',byPost,function(json){
			$('#datagrid').empty();
			json.header.push('素材資料');
			if(returnToParent)
				json.header.push("選擇");
			for(var row in json.data){
				json.data[row].push(['素材資料','button']);
				if(returnToParent)
					json.data[row].push(['選擇','button']);
			}
			
			var mydg=new DataGrid('datagrid',json.header,json.data);
			mydg.set_page_info(json.pageNo,json.maxPageNo);
			mydg.set_sortable(json.sortable,true);
			//頁數改變動作
			mydg.pageChange=function(toPageNo) {
				byPost.pageNo=toPageNo;
				update();
			}
			//header點擊
			mydg.headerOnClick = function(headerName,sort){
				byPost.order=headerName;
				switch(sort){
				case "increase":
					byPost.asc='ASC';
					break;
				case "decrease":
					byPost.asc='DESC';
					break;
				case "unsort":
					break;
				}
				update();
			};
			//按鈕點擊
			mydg.buttonCellOnClick=function(y,x,row) {
				if(!mydg.is_collapsed()){
					if(json.header[x]=="素材資料"){
						mydg.collapse_row(y);
						selectedGroupId = row[0][0];
						$('#materialOprion').show();
						getmDataGrid();
					}
					else if(json.header[x]=="選擇"&&returnToParent){
						parent.materialChosen(row[0][0]);
					}
				}
				else{
					$(".InfoWindow").remove();
					$('#materialOprion').hide();
					$('#datagrid2').empty();
					mydg.uncollapse();
				}
			}
			
			function update(){
				$.post('ajaxFunction_MaterialInfo.php',byPost,function(json) {
						for(var row in json.data){
							json.data[row].push(['素材資料','button']);
							if(returnToParent)
								json.data[row].push(['選擇','button']);
						}
						mydg.set_data(json.data);
					},'json');
			}
		}
		,'json'
		);	
	}
	
	//取得素材資料表並顯示
	function getmDataGrid(){
		bypost={method:'DATAGRID素材資訊',searchBy:'','素材類型':$("#materialTypeSelectoin").val(),pageNo:1,素材群組識別碼:selectedGroupId,order:'素材識別碼',asc:'DESC'};
		($('#missinigFileOnly').prop('checked'))?bypost['method']='DATAGRID素材資訊_MISSING':bypost['method']='DATAGRID素材資訊';
		$.post('ajaxFunction_MaterialInfo.php',bypost,function(json){
			$('#datagrid2').empty();
			json.header.push('詳細資料');
			for(var row in json.data)
				json.data[row].push(['詳細資料','button']);
			
			mydg=new DataGrid('datagrid2',json.header,json.data);
			mydg.set_page_info(json.pageNo,json.maxPageNo);
			mydg.set_sortable(json.sortable,true);
			//頁數改變動作
			mydg.pageChange=function(toPageNo) {
				bypost.pageNo=toPageNo;
				update();
			}
			//header點擊
			mydg.headerOnClick = function(headerName,sort){
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
				update();
			};
			//按鈕點擊
			mydg.buttonCellOnClick=function(y,x,row) {
				if(!mydg.is_collapsed()){
					if(row[x][0]=='詳細資料') {
						mydg.collapse_row(x);
						if($(".InfoWindow").length>0)
							$(".InfoWindow").remove();
						$('body').append('<iframe id="adInfo" name="adInfo" class = "InfoWindow" scrolling="no">');
						$('#adInfo')
						.attr("src",'materialInfo.php?action=info&id='+row[0][0])
						.css("width","100%")
						.hide().fadeIn(showAminationTime);
					}
				}
				else{
					$(".InfoWindow").remove();
					mydg.uncollapse();
				}
			}
			
			function update(){
				$.post('ajaxFunction_MaterialInfo.php',bypost,function(json) {
						for(var row in json.data)
						json.data[row].push(['詳細資料','button']);
						mydg.set_data(json.data);
						setHoverImag();
					},'json');
			}
			setHoverImag();
		}
		,'json'
		);	
	}
	
	//滑鼠移過預覽圖片時，顯示大圖
	function setHoverImag(){
		$('.dgImg').hover(
			function(e) {
				var mX = e.pageX;
				var mY = e.pageY;
				$('body').append('<img id="'+$(this).attr('alt').split(':')[0]+'" src="'+$(this).attr('src')+'"></img>');
				$('#'+$(this).attr('alt').split(':')[0]).css({'position':'absolute', 'top':mY,'left':mX});
			}, function() {
				$('#'+$(this).attr('alt').split(':')[0]).remove();
			}
		);
	}


	

	
</script>
</body>
</html>