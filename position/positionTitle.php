<?php
	include('../tool/auth/auth.php');
	define('PAGE_SIZE',10);
	$API852Url=Config::GET_API_SERVER_852();
	if(isset($_POST['method'])){
		//取得ext參數
		$sql='
			SELECT 版位其他參數名稱,版位其他參數預設值
			FROM 版位其他參數,版位
			WHERE 版位其他參數.版位識別碼 = 版位.上層版位識別碼 AND 版位.版位識別碼 = ?
		';
		if(!$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']))$res = array();
		$positionOption = array();
		
		foreach($res as $row){
			$positionOption[$row['版位其他參數名稱']]=$row['版位其他參數預設值'];
		}
		
		$sql='
			SELECT 版位其他參數名稱,版位其他參數預設值
			FROM 版位其他參數
			WHERE 版位識別碼 = ?
		';
		if(!$res = $my->getResultArray($sql,'i',$_POST['版位識別碼']))$res = array();
		foreach($res as $row){
			$positionOption[$row['版位其他參數名稱']]=$row['版位其他參數預設值'];
		}
		
		if($_POST['method'] == '取得片單資訊'){
			$url = $API852Url.'/mod/AD/api/ad';
			$byPost=array('ext'=>$positionOption['ext']);
			$postvars = http_build_query($byPost);
			// 建立CURL連線
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			
			$apiResult = curl_exec($ch);
			if(curl_errno($ch))
			{
				$logger->error('無法連接前置廣告投放系統API');
				$feedback=array('success'=>false,'message'=>'無法連接前置廣告投放系統API');
				exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));	
			}
			curl_close($ch);
			
			//*********test
			/*$apiResult = '{
			"code": 200,
			"status": "OK",
			"ad": [{
				"id": 22,
				"ext": "test",
				"title": "阿嬤的夢中情人",
				"starttime": "2015-07-10 00:00:00",
				"endtime": "2015-07-16 23:59:59",
				"mark": 1
			}, {
				"id": 23,
				"ext": "test",
				"title": "死也要畢業",
				"starttime": "2013-07-12 00:00:00",
				"endtime": "2015-07-17 00:00:00",
				"mark": 1
			},{
				"id": 24,
				"ext": "test",
				"title": "落KEY人生",
				"starttime": "2015-07-24 00:00:00",
				"endtime": "2015-07-30 23:59:59",
				"mark": 1
			}]
			}';*/
			//************
			$result = json_decode($apiResult,true);
			
			if($result['code']!=200)
				exit(json_encode(array('Error'=>$result['status']),JSON_UNESCAPED_UNICODE));
			
			$data = array();
			
			foreach($result['ad'] as $ad){
				if($ad['mark']==1)
				$data[]=array(array($ad['id'],'text'),array($ad['title'],'text'),array($ad['starttime'],'text'),array($ad['endtime'],'text'));
			}
			
			exit(json_encode(array('header'=>array('編號','標題','開始時間','結束時間')
							,'data'=>$data),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '新增片單資訊'){
			$url = $API852Url.'/mod/AD/api/ad/insert';
			$byPost=array('ext'=>$positionOption['ext'],'starttime'=>$_POST['starttime'],'endtime'=>$_POST['endtime'],'title'=>$_POST['title'],'mark'=>1);
			$postvars = http_build_query($byPost);
			// 建立CURL連線
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			
			$apiResult = curl_exec($ch);
			if(curl_errno($ch))
			{
				$logger->error('無法連接前置廣告投放系統API');
				$feedback=array('success'=>false,'message'=>'無法連接前置廣告投放系統API');
				exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));	
			}
			curl_close($ch);
			
			//*********test
			/*$apiResult = '{
			"code": 200,
			"status": "OK",
			"ad": [{
				"id": 22,
				"ext": "test",
				"title": "阿嬤的夢中情人",
				"starttime": "2015-07-10 00:00:00",
				"endtime": "2015-07-16 23:59:59",
				"mark": 1
			}, {
				"id": 23,
				"ext": "test",
				"title": "死也要畢業",
				"starttime": "2013-07-12 00:00:00",
				"endtime": "2015-07-17 00:00:00",
				"mark": 1
			},{
				"id": 24,
				"ext": "test",
				"title": "落KEY人生",
				"starttime": "2015-07-24 00:00:00",
				"endtime": "2015-07-30 23:59:59",
				"mark": 1
			}]
			}';*/
			//************
			$result = json_decode($apiResult,true);
			
			if($result['code']!=200)
				exit(json_encode(array('success'=>false,'message'=>$result['status']),JSON_UNESCAPED_UNICODE));
			else
				exit(json_encode(array('success'=>true,'message'=>'片單已新增'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '更新片單資訊'){
			$url = $API852Url.'/mod/AD/api/ad/update';
			$byPost=array('ext'=>$positionOption['ext'],'id'=>$_POST['id'],'starttime'=>$_POST['starttime'],'endtime'=>$_POST['endtime'],'title'=>$_POST['title'],'mark'=>1);
			$postvars = http_build_query($byPost);
			// 建立CURL連線
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			
			$apiResult = curl_exec($ch);
			if(curl_errno($ch))
			{
				$logger->error('無法連接前置廣告投放系統API');
				$feedback=array('success'=>false,'message'=>'無法連接前置廣告投放系統API');
				exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));	
			}
			curl_close($ch);
			
			//*********test
			/*$apiResult = '{
			"code": 200,
			"status": "OK",
			"ad": [{
				"id": 22,
				"ext": "test",
				"title": "阿嬤的夢中情人",
				"starttime": "2015-07-10 00:00:00",
				"endtime": "2015-07-16 23:59:59",
				"mark": 1
			}, {
				"id": 23,
				"ext": "test",
				"title": "死也要畢業",
				"starttime": "2013-07-12 00:00:00",
				"endtime": "2015-07-17 00:00:00",
				"mark": 1
			},{
				"id": 24,
				"ext": "test",
				"title": "落KEY人生",
				"starttime": "2015-07-24 00:00:00",
				"endtime": "2015-07-30 23:59:59",
				"mark": 1
			}]
			}';*/
			//************
			$result = json_decode($apiResult,true);
			
			if($result['code']!=200)
				exit(json_encode(array('success'=>false,'message'=>$result['status']),JSON_UNESCAPED_UNICODE));
			else
				exit(json_encode(array('success'=>true,'message'=>'片單已更新'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '刪除片單資訊'){
			$url = $API852Url.'/mod/AD/api/ad/update';
			$byPost=array('ext'=>$positionOption['ext'],'id'=>$_POST['id'],'mark'=>0);
			$postvars = http_build_query($byPost);
			// 建立CURL連線
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			$apiResult = curl_exec($ch);
			if(curl_errno($ch))
			{
				$logger->error('無法連接前置廣告投放系統API');
				$feedback=array('success'=>false,'message'=>'無法連接前置廣告投放系統API');
				exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));	
			}
			curl_close($ch);
			
			
			//*********test
			/*$apiResult = '{
			"code": 200,
			"status": "OK",
			"ad": [{
				"id": 22,
				"ext": "test",
				"title": "阿嬤的夢中情人",
				"starttime": "2015-07-10 00:00:00",
				"endtime": "2015-07-16 23:59:59",
				"mark": 1
			}, {
				"id": 23,
				"ext": "test",
				"title": "死也要畢業",
				"starttime": "2013-07-12 00:00:00",
				"endtime": "2015-07-17 00:00:00",
				"mark": 1
			},{
				"id": 24,
				"ext": "test",
				"title": "落KEY人生",
				"starttime": "2015-07-24 00:00:00",
				"endtime": "2015-07-30 23:59:59",
				"mark": 1
			}]
			}';*/
			//************
			$result = json_decode($apiResult,true);
			if($result['code']!=200)
				exit(json_encode(array('success'=>false,'message'=>$result['status']),JSON_UNESCAPED_UNICODE));
			else
				exit(json_encode(array('success'=>true,'message'=>'片單已刪除'),JSON_UNESCAPED_UNICODE));
		}
	}
	
	
	//取得前置廣告投放系統版位類型編號
	$sql='
		SELECT 版位識別碼
		FROM 版位
		WHERE 版位名稱 = "前置廣告投放系統"
	';
	
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	
	if(!$stmt->execute()) {
		exit('無法執行statement，請聯絡系統管理員！');
	}
	
	if(!$res=$stmt->get_result()) {
		exit('無法取得結果集，請聯絡系統管理員！');
	}

	if($row=$res->fetch_assoc())
		$positionTypeId=$row['版位識別碼'];
	else
		exit('無法取得前置廣告投放系統版位類型識別碼');
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
	<script src="../tool/jquery-ui/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
	<script src="../tool/jquery-ui/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
	<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<style type="text/css">
	td.highlight {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.highlight a {background: #FFAA33 !important;  border: 1px #FF8800 solid !important;}
	td.normal {border: none !important;padding: 1px 0 1px 1px !important;background: none !important;overflow:hidden;}
	td.normal a {background:#DDDDDD !important;border: 1px #888888 solid !important;}
	td.ui-datepicker-current-day a {border: 2px #E63F00 solid !important;}
	.date{ width:200px}
	</style>
</head>

<body>
<div id="dialog_form"><div id="dialog_iframe" class='Center'>
<table class = 'styledTable2'>
<tr><th>編號</th><td id = 'tId'></td></tr>
<tr><th>標題</th><td><input id = 'title' type='text'></input></td></tr>
<tr><th>開始日期</th><td><input id = 'startTime' type='text'></input></td></tr>
<tr><th>結束日期</th><td><input id = 'endTime' type='text'></input></td></tr>
</table>
<br>
<button id ='send'>修改</button>
</div></div>
<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入版位識別碼、名稱、說明查詢" ></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>
<div id = "datagrid"></div>
<br>
<button id ='create' style='float:right'>新增</button>
<div id = "datagrid2"  style='clear:both'></div>
<script type="text/javascript">
	var showAminationTime=500;
	var selectedPTId=<?=$positionTypeId;?>;
	$(function(){
		$('#create').hide();
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				positionDataGrid();
				$("#datagrid2").empty();
				$('#create').hide();
			}
		});
		$("#searchButton").click(function(){
				positionDataGrid();		
				$("#datagrid2").empty();
				$('#create').hide();				
		});
		
		//dialog設定
		$( "#dialog_form" ).dialog(
			{
			autoOpen: false,
			width: '80%',
			height: '80%',
			modal: true
			});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		//時間選擇器
		$( "#startTime" ).datetimepicker({	
			dateFormat: "yy-mm-dd",
			showSecond: true,
			timeFormat: 'HH:mm:ss',
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
		});
		$( "#endTime" ).datetimepicker({
			dateFormat: "yy-mm-dd",
			showSecond: true,
			timeFormat: 'HH:mm:ss',
			hour: 23,
			minute: 59,
			second: 59,
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"]
		});
	});//end of $(function{})
	
	positionDataGrid();

	//顯示搜尋的版位列表
	var selectedPId=0;
	function positionDataGrid(){
		$('#datagrid').html('');
		var bypost={method:'取得版位資料表',pageNo:1,order:'版位識別碼',asc:'ASC',positionType:selectedPTId,searchBy:$('#shearchText').val()};
		$.post('searchPosition.php',bypost,function(json){
				json.header.push('詳細資料','片單資訊');
				for(var row in json.data){
					json.data[row].push(['詳細資料','button'],['片單資訊','button']);
				}
				var DG=new DataGrid('datagrid',json.header,json.data);
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
						if(row[x][0]== "詳細資料"){
							//新增版位視窗
							if($(".InfoWindow").length>0)
							$(".InfoWindow").remove();
							$('body').append('<iframe id="positionTable" name="positionTable" class = "InfoWindow">');
							$('#positionTable')
							.attr("src",'positionTypeForm.php?action=info&id='+row[0][0])
							.css({"width":"100%",'height':'600px'})
							.hide().fadeIn(showAminationTime);
						}
						else if(row[x][0]== "片單資訊"){
							selectedPId=row[0][0];
							titleDataGrid();
						}
						
						DG.collapse_row(y);
					}
					else
						hideInfoWindow();
				}
				
				DG.update=function(){
					$.post('searchPosition.php',bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['詳細資料','button'],['片單資訊','button']);
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
						$("#datagrid2").empty();
						$('#create').hide();
					}
				}	
				$("#datagrid").hide().slideDown(showAminationTime);
			}
			,'json'
		);
	}
	
	//顯示片單列表
	var DG2;
	function titleDataGrid(){
		$('#datagrid2').html('');
		var bypost={method:'取得片單資訊',版位識別碼:selectedPId};
		$.post('?',bypost,function(json){
				json.header.push('修改','刪除');
				for(var row in json.data){
					json.data[row].push(['修改','button'],['刪除','button']);
				}
				DG2=new DataGrid('datagrid2',json.header,json.data);
				
				//按鈕點擊
				DG2.buttonCellOnClick=function(y,x,row) {
					$('#tId').text(row[0][0]);
					if(row[x][0]== "修改"){
						$('#title').val(row[1][0]);
						$('#startTime').val(row[2][0]);
						$('#endTime').val(row[3][0]);
						$('#send').text('修改');
						$( "#dialog_form" ).dialog({height:200, width:250, title:"修改片單名稱"});
						$( "#dialog_form" ).dialog('open');
					}
					else if(row[x][0]== "刪除"){
						if(confirm("確定要刪除片單?")){
							send('刪除片單資訊');
						}
						
					}
				}
				
				DG2.update=function(){
					$.post('?',bypost,function(json) {
							for(var row in json.data){
								json.data[row].push(['修改','button'],['刪除','button']);
							}
							DG2.set_data(json.data);
						},'json');
				}	
				$("#datagrid2,#create").hide().slideDown(showAminationTime);
			}
			,'json'
		);
	}
	
	//新增
	$('#create').click(function(){
		$('#tId').text('');
		$('#title').val('');
		$('#startTime').val('');
		$('#endTime').val('');
		$('#send').text('新增');
		$( "#dialog_form" ).dialog({height:200, width:250, title:"新增片單"});
		$( "#dialog_form" ).dialog('open');
	});
	
	//送出新增或修改
	$('#send').click(function(){
		if($(this).text()=='新增')
			send('新增片單資訊');
		else if($(this).text()=='修改')
			send('更新片單資訊');
	});
	
	//送出新增或修改
	function send(method){
		var byPost={
			method:method
			,版位識別碼:selectedPId
			,id:$('#tId').text()
			,title:$('#title').val()
			,starttime:$('#startTime').val()
			,endtime:$('#endTime').val()
		}
		$.post('?',byPost
			,function(json){
				alert(json.message);
				if(json.success){
					DG2.update();
					$( "#dialog_form" ).dialog('close');
				}
			}
			,'json'
		);
	}

	
	function closeInfoWindow(){
		if($(".InfoWindow").length>0)
			$(".InfoWindow").hide(100,$(".InfoWindow").remove());
	}

	
</script>
</body>
</html>