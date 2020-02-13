<?php
	include('../tool/auth/auth.php');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
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
	
	$returnToParent='false';
	if(isset($_GET["returnToParent"]))
		$returnToParent='true';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"/>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css"></link>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<style type="text/css">
#materialTypeSelectoin{
	margin-right:10px;
	height:25px;
	vertical-align:top;
	padding: 0px 10px;
}
</style>
</head>
<body>
<?php include('_searchMaterialUI.php'); ?>
</div>
<div id = "datagrid"></div>
<form id="downloadForm" method="post" action="ajaxToDB_Material.php" hidden>
<input type="test" name="action"></input>
<input type="test" name="file"></input>
<input type="test" name="name"></input>
<button type="submit"></button>
</form>
<script type="text/javascript">	
	var showAminationTime = 500;
	var returnToParent = <?=$returnToParent;?>;
	
	var ajaxtodbPath = "ajaxFunction_MaterialInfo.php";
	
	getmDataGrid();
	function getmDataGrid(){
		bypost={
			method:'DATAGRID素材資訊'
			,searchBy:$('#_searchMUI_shearchText').val()
			,'素材類型':$("#_searchMUI_materialTypeSelectoin").val()
			,pageNo:1,order:'素材識別碼'
			,asc:'DESC'
			,"素材群組識別碼":$("#_searchMUI_materialGroup").val()
			,"開始時間":$("#_searchMUI_startDate").val()
			,"結束時間":$("#_searchMUI_endDate").val()
		};
		($('#_searchMUI_missingFileOnly').prop('checked'))?bypost['method']='DATAGRID素材資訊_MISSING':bypost['method']='DATAGRID素材資訊';
		$.post(ajaxtodbPath,bypost,function(json){
				 new mDataGrid(json);
			}
		,'json'
		);
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
	}
	
	function mDataGrid(json){
		$('#datagrid').empty();
		json.header.push('詳細資料');
		if(returnToParent)
			json.header.push('選擇');
		json.header.push('執行派送');
		for(var row in json.data){
			json.data[row].push(["詳細資料","button"]);
			if(returnToParent)
				json.data[row].push(["選擇","button"]);	
			json.data[row].push(["執行派送","button"]);
		}
		
		mydg=new DataGrid('datagrid',json.header,json.data);
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
					mydg.collapse_row(y);
					if($(".InfoWindow").length>0)
						$(".InfoWindow").remove();
					$('body').append('<iframe id="adInfo" name="adInfo" class = "InfoWindow" scrolling="no">');
					$('#adInfo')
					.attr("src",'materialInfo.php?action=info&id='+row[0][0])
					.css("width","100%")
					.hide().fadeIn(showAminationTime);
				}
				else if(row[x][0]=="選擇"&&returnToParent){
					parent.materialChosen(row[0][0]);
				}
				else if(row[x][0]=="執行派送"){
					$.post('ajaxToDB_Material.php',{'action':'下載素材檔案檢查','素材識別碼':row[0][0]}
						,function(checkData){
							if(!checkData['success'])
								alert(checkData['message']);
							else{
								/*var $downForm = $('<form method="post" action="ajaxToDB_Material.php"></form>');
								$downForm.
								$('<input type="test" name="action"></input>').val('下載素材檔案').appendTo($downForm);
								$('<input type="test" name="file"></input>').val(checkData['file']).appendTo($downForm);
								$('<input type="test" name="name"></input>').val(checkData['name']).appendTo($downForm);
								var $downBtn = $('<button type="submit"></button>').appendTo($downForm).click();*/
								var $downForm =$('#downloadForm');
								$downForm.find("[name='action']").val('下載素材檔案');
								$downForm.find("[name='file']").val(checkData['file']);
								$downForm.find("[name='name']").val(checkData['name']);
								$downForm.find("[type='submit']").click();
							}
						}
						,'json'
					);
				}
			}
			else{
				$(".InfoWindow").remove();
				mydg.uncollapse();
			}
		}
		
		function update(){
			$.post(ajaxtodbPath,bypost,function(json) {
					for(var row in json.data){
					json.data[row].push(["詳細資料","button"]);
					if(returnToParent)
						json.data[row].push(["選擇","button"]);
					json.data[row].push(["執行派送","button"]);
					}
					mydg.set_data(json.data);
					setHoverImag();
				},'json');
		}
		
		setHoverImag();
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
	
	/**關閉素材詳細資訊視窗**/
	function closeOrderInfo(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
			TDG.uncollapse();
		}
	}


	

	
</script>
</body>
</html>