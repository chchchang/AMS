<?php
	include('../tool/auth/authAJAX.php');
	define('PAGE_SIZE',10);
	if(isset($_POST['method'])){
		//$my=new MyDB(true);
		if($_POST['method'] == '取得版位資料表'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			$searchBy='%'.$_POST['searchBy'].'%';//搜尋關鍵字
			$positionType =(isset($_POST['positionType'])&&$_POST['positionType']!='')?$_POST['positionType']:'%'; //版位類型
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 版位
				WHERE (版位識別碼 = ? OR 版位說明 LIKE ? OR 版位名稱 LIKE ?) AND 上層版位識別碼 LIKE ? AND DELETED_TIME IS NULL
			';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('isss',$_POST['searchBy'],$searchBy,$searchBy,$positionType)) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 版位識別碼,版位名稱,版位說明,DISABLE_TIME AS 版位狀態
				FROM  版位
				WHERE (版位識別碼 = ? OR 版位說明 LIKE ? OR 版位名稱 LIKE ?) AND 上層版位識別碼 LIKE ? AND DELETED_TIME IS NULL
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			
			if(!$stmt->bind_param('isssi',$_POST['searchBy'],$searchBy,$searchBy,$positionType,$fromRowNo)) {
				exit('無法繫結資料，請聯絡系統管理員！');
			}
			
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			while($row=$res->fetch_assoc())
				$orders[]=array(array($row['版位識別碼'],'text'),array($row['版位名稱'],'text'),array(($row['版位說明']==null)?'':$row['版位說明'],'text'),array(($row['版位狀態']==null)?'顯示':'隱藏','text'));
	
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('版位識別碼','版位名稱','版位說明','版位狀態')
							,'data'=>$orders,'sortable'=>array('版位識別碼','版位名稱','版位說明','版位狀態')),JSON_UNESCAPED_UNICODE);
			exit;
		}
		else if($_POST['method'] == '隱藏版位'){
			$sql='
				SELECT COUNT(1) COUNT
				FROM 託播單
				WHERE 版位識別碼 = ? AND 託播單狀態識別碼 IN (0,1,6)
			';
			
			if(!$res=$my->getResult($sql,'i',$_POST['版位識別碼'])) {
				exit(json_encode(array('success'=>false,'message'=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
		
			if(!$row=$res->fetch_assoc())
				exit(json_encode(array('success'=>false,'message'=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				
			if($row['COUNT']!=0)
				exit(json_encode(array('success'=>false,'message'=>'此版位仍有預約或確定的託播單'),JSON_UNESCAPED_UNICODE));
			
			$sql='
				UPDATE 版位 SET DISABLE_TIME=CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 版位識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["版位識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'隱藏版位(版位識別碼:'.$_POST["版位識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'版位已隱藏'),JSON_UNESCAPED_UNICODE));
		}	
		else if($_POST['method'] == '顯示版位'){
			$sql='
				UPDATE 版位 SET DISABLE_TIME=NULL, LAST_UPDATE_PEOPLE=? WHERE 版位識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["版位識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'顯示版位(版位識別碼:'.$_POST["版位識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'版位已取消隱藏'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '刪除版位'){
			//統計版位下的託播單
			$sql='
				SELECT COUNT(*) AS count FROM 託播單 WHERE 版位識別碼=?
			';
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$_POST["版位識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row = $res->fetch_assoc();
			if($row['count']>0)
				exit(json_encode(array("success"=>false,"message"=>'此版位已有託播單，無法刪除。'),JSON_UNESCAPED_UNICODE));
			
			$sql='
				UPDATE 版位 SET DELETED_TIME=CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 版位識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["版位識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'刪除版位(版位識別碼:'.$_POST["版位識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'版位已刪除'),JSON_UNESCAPED_UNICODE));
		}
	}
	@include('../tool/auth/auth.php');
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
	<script src="../tool/HtmlSanitizer.js"></script>
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
</head>

<body>

<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入版位識別碼、名稱、說明查詢"></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>
<div id = "datagrid"></div>
<div id = "datagrid2"></div>
<div id="dialog"></div>
<script type="text/javascript">
	var showAminationTime = 500;
	
	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				positionDataGrid();	
			}
		});
		$("#searchButton").click(function(){
				positionDataGrid();				
		});
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});
	
	
	var ajaxtodbPath ="ajaxToDB_Position.php";
	var g_numPerPage=10;
	/**向司服器要求廣告主資料數目**/
	var ODG;//預備用來放datagrid的物件
	
	var selectedPTId='';//備選擇的版位類型
	
	positionTypeDataGrid()
	//顯示搜尋的委刊單列表
	var DG=null,mydg=null;
	function positionTypeDataGrid(){
		$('#datagrid').html('');
		var bypost={action:'版位類型資料表',searchBy:'',pageNo:1,order:'版位類型識別碼',asc:'ASC'};

		$.post(ajaxtodbPath,bypost,function(json){
				json.header.push('修改此類型版位');
				for(var row in json.data){
					json.data[row].push(['修改此類型版位','button']);
				}
				mydg=new DataGrid('datagrid',json.header,json.data);
				mydg.set_page_info(json.pageNo,json.maxPageNo);
				mydg.set_sortable(json.sortable,true);
				//頁數改變動作
				mydg.pageChange=function(toPageNo) {
					bypost.pageNo=toPageNo;
					mydg.update();
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
					mydg.update();
				};
				//按鈕點擊
				mydg.buttonCellOnClick=function(y,x,row) {
					if(row[x][0]=='修改此類型版位') {
						if(!mydg.is_collapsed()){
							selectedPTId =row[0][0];
							positionDataGrid();			
							mydg.collapse_row(y);
						}
						else{
							selectedPTId ='';
							hideInfoWindow();
							//positionDataGrid();	
						}
					}
				}
				
				mydg.update=function(){
					$.post(ajaxtodbPath,bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['修改此類型版位','button']);
							}
							mydg.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	/**隱藏視窗**/
	function hideInfoWindow(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
		if(mydg.is_collapsed()){
			mydg.uncollapse();
			$("#datagrid2").children().remove();
		}
	}
	
	
	//顯示搜尋的版位列表
	function positionDataGrid(){
		$('#datagrid2').html('');
		var bypost={method:'取得版位資料表',pageNo:1,order:'版位識別碼',asc:'ASC',positionType:selectedPTId,searchBy:$('#shearchText').val()};
		$.post('?',bypost,function(json){
				json.header.push('修改版位','隱藏版位','刪除版位');
				for(var row in json.data){
					if(json.data[row][json.data[row].length-1][0]=='隱藏')
						json.data[row].push(['修改版位','button'],['顯示版位','button'],['刪除版位','button']);
					else
						json.data[row].push(['修改版位','button'],['隱藏版位','button'],['刪除版位','button']);
				}
				DG=new DataGrid('datagrid2',json.header,json.data);
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
						if(row[x][0]=='修改版位') {
							//新增版位視窗
							if($(".InfoWindow").length>0)
							$(".InfoWindow").remove();
							$('body').append('<iframe id="positionTable" name="positionTable" class = "InfoWindow">');
							$('#positionTable')
							.attr("src",'positionTypeForm.php?action=edit&positionPage=1&id='+row[0][0])
							.css({'width':'100%','height':'600px'})
							.hide().fadeIn(showAminationTime);
							DG.collapse_row(y);
						
						}
						else if(row[x][0]=='隱藏版位'){
							$.post('?',{method:'隱藏版位',版位識別碼:row[0][0]}
							,function(json){
								alert(json.message);
								if(json.success)
									DG.update();
							}
							,'json'
							);
						}
						else if(row[x][0]=='顯示版位'){
							$.post('?',{method:'顯示版位',版位識別碼:row[0][0]}
							,function(json){
								alert(json.message);
								if(json.success)
									DG.update();
							}
							,'json'
							);
						}
						else if(row[x][0]=='刪除版位'){
							if(confirm("確定要刪除版位?"))
							$.post('?',{method:'刪除版位',版位識別碼:row[0][0]}
							,function(json){
								alert(json.message);
								if(json.success){
									DG.update();
								}
								else{
									alert(json.message);
								}
							}
							,'json'
							);
						}
					}
					else
						hideInfoWindow();
				}		
				
				DG.update=function(){
					$.post('?',bypost,function(json) {
							for(var row in json.data){
								if(json.data[row][json.data[row].length-1][0]=='隱藏')
									json.data[row].push(['修改版位','button'],['顯示版位','button'],['刪除版位','button']);
								else
									json.data[row].push(['修改版位','button'],['隱藏版位','button'],['刪除版位','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
				
				/**隱藏視窗**/
				function hideInfoWindow(){
					if($(".InfoWindow").length>0){
						$(".InfoWindow").remove();
					}
					if(DG.is_collapsed()){
						DG.uncollapse();
						$("#dateDiv").hide();
						$("#datagrid3").empty();
					}
				}	
				
				$("#datagrid2").hide().slideDown(showAminationTime);
			}
			,'json'
		);
	}
	
	/**表單儲存成功，更新資料**/
	function positionUpdated(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
		DG.update();
	}
</script>
</body>
</html>