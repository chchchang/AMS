<?php
	include('../tool/auth/auth.php');
	//ajax service
	if(isset($_POST['action'])){
		if($_POST['action']=='刪除素材'){
			
			//檢察素材是否被託播單使用
			$sql = 'SELECT COUNT(*) AS C FROM 託播單素材 WHERE 素材識別碼 = ?';
			$result=$my->getResultArray($sql,'i',$_POST['素材識別碼'])[0];
			if($result['C']!=0)
				exit(json_encode(['success'=>false,'message'=>'此素材已被託播單使用，無法刪除。'],JSON_UNESCAPED_UNICODE));
				
			//檢察素材是否送出
			$sql = 'SELECT 圖片素材派送結果, 影片派送時間,素材原始檔名,素材識別碼 FROM 素材 WHERE 素材識別碼 = ?';
			$mresult=$my->getResultArray($sql,'i',$_POST['素材識別碼'])[0];
			if($mresult['圖片素材派送結果']!=NULL || $mresult['影片派送時間']!=NULL)
				exit(json_encode(['success'=>false,'message'=>'此素材已被派送，無法刪除。'],JSON_UNESCAPED_UNICODE));
			
			//刪除素材
			$sql = 'DELETE FROM 素材 WHERE 素材識別碼 = ?';
			$result=$my->execute($sql,'i',$_POST['素材識別碼']);
			if($result){
				$logger->info('使用者識別碼:'.$_SESSION['AMS']['使用者識別碼'].'刪除素材(識別碼:'.$_POST['素材識別碼'].')');
				//移除素材原始檔案
				$fileNameA=explode('.',$mresult['素材原始檔名']);
				//為了避免path manipulation,使用資料庫中取得的素材識別碼而非POST的素材識別碼
				$file = 'uploadedFile/'.$mresult['素材識別碼'].'.'.end($fileNameA);
				if(file_exists($file))
					unlink($file);
				
				exit(json_encode(['success'=>true,'message'=>'素材已成功刪除'],JSON_UNESCAPED_UNICODE));
			}
			else
				exit(json_encode(['success'=>false,'message'=>'素材刪除失敗'],JSON_UNESCAPED_UNICODE));
		}
		exit();
	}
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
<div id = "datagrid"></div>
<script type="text/javascript">
	
	var showAminationTime = 500;		
	var ajaxtodbPath = "ajaxToDB_Material.php";
	var g_numPerPage=10;
	/**向司服器要求廣告主資料數目**/
	var TDG;//存放素材資料表用
	
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
		$.post('ajaxFunction_MaterialInfo.php',bypost,function(json){
				TDG =  new mDataGrid(json);
			}
		,'json'
		);
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
	}	
	
	function mDataGrid(json){
		$('#datagrid').empty();
		json.header.push('修改素材','刪除素材');
		for(var row in json.data)
			json.data[row].push(['修改素材','button'],['刪除素材','button']);
		
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
				if(row[x][0]=='修改素材') {
					mydg.collapse_row(y);
					if($(".InfoWindow").length>0)
						$(".InfoWindow").remove();
					$('body').append('<iframe id="adInfo" name="adInfo" class = "InfoWindow" scrolling="no">');
					$('#adInfo')
					.attr("src",'materialInfo.php?action=edit&id='+mydg.getCellText('素材識別碼',y))
					.css("width","100%")
					.hide().fadeIn(showAminationTime);
				}
				else if(row[x][0]=='刪除素材'){
					if(confirm('刪除後的素材將無法恢復，確定要繼續?')){
						$.post('',{action:'刪除素材','素材識別碼':mydg.getCellText('素材識別碼',y)}
							,function(json){
								alert(json.message);
								if(json.success)
									materialUpdated();
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
		setHoverImag();
		
		function update(){
			$.post('ajaxFunction_MaterialInfo.php',bypost,function(json) {
					for(var row in json.data)
					json.data[row].push(['修改素材','button'],['刪除素材','button']);
					mydg.set_data(json.data);
					setHoverImag();
				},'json');
		}
		
		this.uncollapse = function(){
			mydg.uncollapse();
		}
		
		this.updateData= function(){
			update();
		}
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
	
	function materialUpdated(){
		closeOrderInfo();
		TDG.updateData();
	}


	

	
</script>
</body>
</html>