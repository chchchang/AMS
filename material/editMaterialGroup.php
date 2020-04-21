<?php
	include('../tool/auth/auth.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == '隱藏素材群組'){
			$sql ='UPDATE 素材群組 SET DISABLE_TIME = CURRENT_TIMESTAMP,LAST_UPDATE_PEOPLE = ? WHERE 素材群組識別碼 = ?';
			if(!$my->execute($sql,'ii',$_SESSION['AMS']['使用者識別碼'],$_POST['素材群組識別碼']))
				exit(json_encode(['success'=>false,'message'=>'資料庫錯誤，隱藏素材群組失敗'],JSON_UNESCAPED_UNICODE));
			$logger->info('使用者'.$_SESSION['AMS']['使用者識別碼'].'隱藏素材群組'.$_POST['素材群組識別碼']);
			exit(json_encode(['success'=>true,'message'=>'素材群組已隱藏'],JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '取消隱藏素材群組'){
			$sql ='UPDATE 素材群組 SET DISABLE_TIME = NULL,LAST_UPDATE_PEOPLE = ? WHERE 素材群組識別碼 = ?';
			if(!$my->execute($sql,'ii',$_SESSION['AMS']['使用者識別碼'],$_POST['素材群組識別碼']))
				exit(json_encode(['success'=>false,'message'=>'資料庫錯誤，取消隱藏素材群組失敗'],JSON_UNESCAPED_UNICODE));
			$logger->info('使用者'.$_SESSION['AMS']['使用者識別碼'].'取消隱藏素材群組'.$_POST['素材群組識別碼']);
			exit(json_encode(['success'=>true,'message'=>'素材群組已取消隱藏'],JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '刪除素材群組'){
			//檢查該群組下是否有素材，若有則無法刪除
			$sql ='SELECT COUNT(*) AS C FROM 素材 WHERE 素材群組識別碼 = ?';
			$res = $my->getResultArray($sql,'i',$_POST['素材群組識別碼']);
			if($res[0]['C']>0)
				exit(json_encode(['success'=>false,'message'=>'該群組中已有素材，無法刪除'],JSON_UNESCAPED_UNICODE));
			//執行刪除
			$sql ='UPDATE 素材群組 SET DELETED_TIME = CURRENT_TIMESTAMP,LAST_UPDATE_PEOPLE = ? WHERE 素材群組識別碼 = ?';
			if(!$my->execute($sql,'ii',$_SESSION['AMS']['使用者識別碼'],$_POST['素材群組識別碼']))
				exit(json_encode(['success'=>false,'message'=>'資料庫錯誤，刪除素材群組失敗'],JSON_UNESCAPED_UNICODE));
			$logger->info('使用者'.$_SESSION['AMS']['使用者識別碼'].'刪除素材群組'.$_POST['素材群組識別碼']);
			exit(json_encode(['success'=>true,'message'=>'素材群組已刪除'],JSON_UNESCAPED_UNICODE));
		}
	}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"/>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
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
<div id="searchForm" class = "basicBlock">
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入素材群組識別碼、名稱、說明查詢"></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
<div id = "datagrid"></div>
<script type="text/javascript">
	var showAminationTime = 500;

	//按下enter查詢
	$("#shearchText").keypress(function(event){
		if (event.keyCode == 13){
			byPost={
				method:'DATAGRID素材群組資訊',
				searchBy:$("#shearchText").val(),
				pageNo:1,
				order:'素材群組識別碼',
				asc:'DESC',
				'修改素材群組頁面':true
			}
			MDataGrid();
			if($(".InfoWindow").length>0){
				$(".InfoWindow").remove();
			}
		}
	});
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
	// 幫有 placeholder 屬性的輸入框加上提示效果
	$('input[placeholder]').placeholder();
	
	
	var TDG;//存放素材資料表用
	
	
	var byPost={
		method:'DATAGRID素材群組資訊',
		searchBy:'',
		pageNo:1,
		order:'素材群組識別碼',
		asc:'DESC',
		'修改素材群組頁面':true
	}
	
	MDataGrid();
	
	function MDataGrid(){
		$.post('ajaxFunction_MaterialInfo.php',byPost,function(json){
			$('#datagrid').empty();
			json.header.push('修改');
			json.header.push('隱藏');
			json.header.push('刪除');
			var testIndex = $.inArray('是否隱藏',json.header);
			for(var row in json.data){
				json.data[row].push(['修改','button']);
				if(json.data[row][testIndex] == '是')
					json.data[row].push(['取消隱藏','button']);
				else
					json.data[row].push(['隱藏','button']);
				json.data[row].push(['刪除','button']);
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
			mydg.buttonCellOnClick=function(row,column,rowdata){
				if(!mydg.is_collapsed()){
					if(rowdata[column][0]=="修改"){
						mydg.collapse_row(row);
						if($(".InfoWindow").length>0)
							$(".InfoWindow").remove();
						$('body').append('<iframe id="adInfo" name="adInfo" class = "InfoWindow" scrolling="no" height="400px">');
						$('#adInfo')
						.attr("src",'materialGroupInfo.php?action=edit&id='+rowdata[0][0])
						.css("width","100%")
						.hide().fadeIn(showAminationTime);
					}
					else if(rowdata[column][0]=="隱藏"){
						$.post('',{method:'隱藏素材群組','素材群組識別碼':rowdata[0][0]}
							,function(res){
								alert(res.message);
								if(res.success)
									update();
							}
							,'json'
						
						);
					}
					else if(rowdata[column][0]=="取消隱藏"){
						$.post('',{method:'取消隱藏素材群組','素材群組識別碼':rowdata[0][0]}
							,function(res){
								alert(res.message);
								if(res.success)
									update();
							}
							,'json'
						);
					}
					else if(rowdata[column][0]=="刪除"){
						if(confirm('刪除後的素材群組將無法被覆原，確定要刪除素材群組?')){
							$.post('',{method:'刪除素材群組','素材群組識別碼':rowdata[0][0]}
								,function(res){
									alert(res.message);
									if(res.success)
										update();
								}
								,'json'
							);
						}
					}
				}
				else{
					$(".InfoWindow").remove();
					mydg.uncollapse();
				}
			}
			
			function update(){
				$.post('ajaxFunction_MaterialInfo.php',byPost,function(json) {
						for(var row in json.data){
							json.data[row].push(['修改','button']);
							if(json.data[row][testIndex] == '是')
								json.data[row].push(['取消隱藏','button']);
							else
								json.data[row].push(['隱藏','button']);
							json.data[row].push(['刪除','button']);
						}
						mydg.set_data(json.data);
					},'json');
			}
		}
		,'json'
		);	
	}
	
	/**關閉素材詳細資訊視窗**/
	function closeOrderInfo(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
	}
	
	function materialGroupUpdated(){
		closeOrderInfo();
		MDataGrid();
	}


	

	
</script>
</body>
</html>