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
		
		if($_POST['method'] == '取得廣告數資訊'){
			$url = $API852Url.'/mod/AD/api/service';
			//$byPost=array('ext'=>$positionOption['ext'],'ams_sid'=>$_POST['版位識別碼']);
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
				"service": [{
					"id": 19,
					"ext": "test",
					"title": "測試服務",
					"chtn": 0,
					"chtc": 0,
					"chts": 0,
					"iap": null,
					"ads": 3,
					"mark": 1,
					"ams_sid版位識別碼ams": "19版位識別碼test"
				}]
			}
			';*/
			//************
			$result = json_decode($apiResult,true);
			
			if($result['code']!=200)
				exit(json_encode(array('success'=> false,'data'=>'無資料'),JSON_UNESCAPED_UNICODE));
			
			$data = array();
			
			if(isset($result['service']))
				foreach($result['service'] as $service){
					if($service['mark']==1)
						exit(json_encode(array('success'=> true,'data'=>$service['ads']),JSON_UNESCAPED_UNICODE));
					else
						exit(json_encode(array('success'=> false,'data'=>'以下架'),JSON_UNESCAPED_UNICODE));
				}
			else
				exit(json_encode(array('success'=> false,'data'=>'無資料'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '修改廣告片數'){
			$url = $API852Url.'/mod/AD/api/service/update';
			$byPost=array('ext'=>$positionOption['ext'],'ads'=>intval($_POST['ads']));
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
				"service": [{
					"id": 19,
					"ext": "test",
					"title": "測試服務",
					"chtn": 0,
					"chtc": 0,
					"chts": 0,
					"iap": null,
					"ads": 3,
					"mark": 1,
					"ams_sid版位識別碼ams": "19版位識別碼test"
				}]
			}';*/
			//************
			$result = json_decode($apiResult,true);
			
			if($result['code']!=200)
				exit(json_encode(array('success'=>false,'message'=>$result['status']),JSON_UNESCAPED_UNICODE));
			else
				exit(json_encode(array('success'=>true,'message'=>'廣告片數已更新'),JSON_UNESCAPED_UNICODE));
		}
		exit;
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
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
	<script src="../tool/jquery-ui1.2/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
	<script src="../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
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
<tr><th>廣告片數</th><td><input id = 'adNumEdit'></input></td></tr>
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
	});//end of $(function{})
	
	positionDataGrid();

	//顯示搜尋的版位列表
	var selectedPId=0;
	var DG;
	function positionDataGrid(){
		$('#datagrid').html('');
		var bypost={method:'取得版位資料表',pageNo:1,order:'版位識別碼',asc:'ASC',positionType:selectedPTId,searchBy:$('#shearchText').val()};
		$.post('searchPosition.php',bypost,function(json){
				json.header.push('廣告片數','詳細資料','修改片數');
				for(var row in json.data){
					$.ajax({
						async: false,
						type : "POST",
						url : '?',
						data: {method:'取得廣告數資訊',版位識別碼:json.data[row][0][0]},
						dataType : 'json',
						success :
							function(data){
								json.data[row].push([data.data,'text']);
							}
					});
					json.data[row].push(['詳細資料','button'],['修改片數','button']);
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
							DG.collapse_row(y);
						}
						else if(row[x][0]== "修改片數"){
							selectedPId = row[0][0];
							$( "#dialog_form" ).dialog({height:200, width:250, title:"修改廣告片數"});
							$( "#dialog_form" ).dialog('open');
						}
					}
					else
						hideInfoWindow();
				}
				
				DG.update=function(){
					$.post('searchPosition.php',bypost,function(json) {
							for(var row in json.data){
								$.ajax({
									async: false,
									type : "POST",
									url : '?',
									data: {method:'取得廣告數資訊',版位識別碼:json.data[row][0][0]},
									dataType : 'json',
									success :
										function(data){
											json.data[row].push([data.data,'text']);
										}
								});
								json.data[row].push(['詳細資料','button'],['修改片數','button']);
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
	
	//修改片數
	$('#send').click(function(){
		var byPost={
			method:'修改廣告片數'
			,版位識別碼:selectedPId
			,ads:$('#adNumEdit').val()
		}
		$.post('?',byPost
			,function(json){
				alert(json.message);
				if(json.success){
					DG.update();
					$( "#dialog_form" ).dialog('close');
				}
			}
			,'json'
		);
	});


	
	function closeInfoWindow(){
		if($(".InfoWindow").length>0)
			$(".InfoWindow").hide(100,$(".InfoWindow").remove());
	}

	
</script>
</body>
</html>