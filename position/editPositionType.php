<?php
	include('../tool/auth/auth.php');
	define('PAGE_SIZE',10);
	//AJAX
	if(isset($_POST['method'])){
		if($_POST['method']=='版位類型資料表'){
			$orders=array();
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;	//T.B.D.
			$searchBy='%'.$_POST['searchBy'].'%';
			
			//先取得總筆數
			$sql='
				SELECT COUNT(1) COUNT
				FROM 版位
				WHERE DELETED_TIME IS null AND (版位識別碼 = ? OR 版位名稱 LIKE ? OR 版位說明 LIKE ?) AND 上層版位識別碼 IS null
			';
			
			if(!$res=$my->getResult($sql,'sss',$searchBy,$searchBy,$searchBy)) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
		
			if($row=$res->fetch_assoc())
				$totalRowCount=$row['COUNT'];
			else
				exit;
			
			//再取得資料
			$sql='
				SELECT 版位識別碼 AS 版位類型識別碼,版位名稱 AS 版位類型名稱,版位說明 AS 版位類型說明,DISABLE_TIME AS 狀態
				FROM 版位
				WHERE DELETED_TIME IS null AND (版位識別碼 = ? OR 版位名稱 LIKE ? OR 版位說明 LIKE ?) AND 上層版位識別碼 IS null
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			
			if(!$res=$my->getResult($sql,'sssi',$searchBy,$searchBy,$searchBy,$fromRowNo)) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			while($row=$res->fetch_assoc())
				$orders[]=array(array($row['版位類型識別碼'],'text'),array($row['版位類型名稱'],'text'),array($row['版位類型說明'],'text'),array(($row['狀態']==NULL)?'顯示':'隱藏','text'));
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('版位類型識別碼','版位類型名稱','版位類型說明','狀態')
							,'data'=>$orders,'sortable'=>array('版位類型識別碼','版位類型名稱','版位類型說明','狀態')),JSON_UNESCAPED_UNICODE);
		}
		else if($_POST['method']=='隱藏版位類型'){
			$sql='
				SELECT COUNT(1) COUNT
				FROM 版位 ,託播單
				WHERE 上層版位識別碼 = ? AND 版位.版位識別碼 = 託播單.版位識別碼 AND (託播單狀態識別碼 = 0 OR 託播單狀態識別碼 =1)
			';
			
			if(!$res=$my->getResult($sql,'i',$_POST['版位識別碼'])) {
				exit(json_encode(array('success'=>false,'message'=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
		
			if(!$row=$res->fetch_assoc())
				exit(json_encode(array('success'=>false,'message'=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				
			if($row['COUNT']!=0)
				exit(json_encode(array('success'=>false,'message'=>'此類型版位仍有預約或確定的託播單'),JSON_UNESCAPED_UNICODE));
			
			$sql='
				UPDATE 版位 SET DISABLE_TIME = CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 版位識別碼=?
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
			exit(json_encode(array('success'=>true,'message'=>'版位類型已隱藏'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method']=='刪除版位類型'){
			$sql='
				SELECT COUNT(1) COUNT
				FROM 版位,託播單
				WHERE 上層版位識別碼 = ? AND 版位.版位識別碼 = 託播單.版位識別碼
			';
			
			if(!$res=$my->getResult($sql,'i',$_POST['版位識別碼'])) {
				exit(json_encode(array('success'=>false,'message'=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
		
			if(!$row=$res->fetch_assoc())
				exit(json_encode(array('success'=>false,'message'=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
				
			if($row['COUNT']!=0)
				exit(json_encode(array('success'=>false,'message'=>'此類型版位已有託播單，無法刪除'),JSON_UNESCAPED_UNICODE));
			
			$sql='
				UPDATE 版位 SET DELETED_TIME = CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 版位識別碼=?
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
			exit(json_encode(array('success'=>true,'message'=>'版位類型已刪除'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method']=='取消隱藏版位類型'){			
			$sql='
				UPDATE 版位 SET DISABLE_TIME = NULL, LAST_UPDATE_PEOPLE=? WHERE 版位識別碼=?
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
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'取消隱藏版位(版位識別碼:'.$_POST["版位識別碼"].')');
			exit(json_encode(array('success'=>true,'message'=>'版位類型已取消隱藏'),JSON_UNESCAPED_UNICODE));
		}
		exit;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
	<script src="../tool/jquery-ui/jquery-ui.js"></script>
	<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
</head>

<body>
<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入版位類型識別碼、名稱、說明查詢" ></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>
<div id = "datagrid"></div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>

<script type="text/javascript">
	var showAminationTime=500;
	
	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				showPositionList();
			}
		});
		
		$('#searchButton').click(function(){
			showPositionList();
		});
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
	});
	
	
	var ajaxtodbPath ="ajaxToDB_Position.php";
	
	showPositionList()
	//顯示搜尋的委刊單列表
	var DG=null;
	function showPositionList(){
		$('#datagrid').html('');
		var bypost={method:'版位類型資料表',searchBy:$('#shearchText').val(),pageNo:1,order:'版位類型識別碼',asc:'ASC'};

		$.post('?',bypost,function(json){
				json.header.push('修改版位類型','隱藏版位類型','刪除版位類型');
				for(var row in json.data){
					if(json.data[row][json.data[row].length-1][0]=='顯示')
					json.data[row].push(['修改版位類型','button'],['隱藏版位類型','button'],['刪除版位類型','button']);
					else
					json.data[row].push(['修改版位類型','button'],['取消隱藏版位類型','button'],['刪除版位類型','button']);
				}
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
					if(row[x][0]=='修改版位類型') {
						$("#dialog_iframe").attr("src","positionTypeForm.php?action=edit&id="+row[0][0])
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: 700, width:900, title:"編輯版位類型"});
						dialog.dialog( "open" );
					}
					else if (row[x][0]=='隱藏版位類型'){
						$.post('?',{method:'隱藏版位類型',版位識別碼:row[0][0]}
							,function(json){
								alert(json.message);
								if(json.success){
									DG.update();
								}
							}
							,'json'
						);
					}
					else if (row[x][0]=='取消隱藏版位類型'){
						$.post('?',{method:'取消隱藏版位類型',版位識別碼:row[0][0]}
							,function(json){
								alert(json.message);
								if(json.success){
									DG.update();
								}
							}
							,'json'
						);
					}
					else if (row[x][0]=='刪除版位類型'){
						if(confirm("確定要刪除版位類型?"))
						$.post('?',{method:'刪除版位類型',版位識別碼:row[0][0]}
							,function(json){
								alert(json.message);
								if(json.success){
									DG.update();
								}
							}
							,'json'
						);
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('?',bypost,function(json) {
							for(var row in json.data){
								if(json.data[row][json.data[row].length-1][0]=='顯示')
								json.data[row].push(['修改版位類型','button'],['隱藏版位類型','button'],['刪除版位類型','button']);
								else
								json.data[row].push(['修改版位類型','button'],['取消隱藏版位類型','button'],['刪除版位類型','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	
	function positionUpdated(){
		$( "#dialog_form" ).dialog('close');
		DG.update();
	}

</script>
</body>
</html>