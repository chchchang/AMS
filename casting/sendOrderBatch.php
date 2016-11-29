<?php 
	include('../tool/auth/auth.php');
	define('PAGE_SIZE',10);
	if(isset($_POST['method'])){
		//取得搜尋的託播單資料
		if($_POST['method'] == '全託播單識別碼'){
			$searchBy='%'.((isset($_POST['searchBy']))?$_POST['searchBy']:'').'%';
			if(isset($_POST['廣告主識別碼']))
				$adowner=($_POST['廣告主識別碼']=='')?'%':$_POST['廣告主識別碼'];
			else
				$adowner='%';
			if(isset($_POST['委刊單識別碼']))
				$orderList=($_POST['委刊單識別碼']=='')?'%':$_POST['委刊單識別碼'];
			else
				$orderList='%';
			if(isset($_POST['版位類型識別碼']))
				$positionType=($_POST['版位類型識別碼']=='')?'%':$_POST['版位類型識別碼'];
			else
				$positionType='%';
			if(isset($_POST['版位識別碼']))
				$position=($_POST['版位識別碼']=='')?'%':$_POST['版位識別碼'];
			else
				$position='%';
			if(isset($_POST['開始時間']))
				$startDate=($_POST['開始時間']=='')?'0000-00-00':$_POST['開始時間'].' 00:00:00';
			else
				$startDate='0000-00-00';
			if(isset($_POST['結束時間']))
				$endDate=($_POST['結束時間']=='')?'9999-12-31':$_POST['結束時間'].' 23:59:59';
			else
				$endDate='9999-12-31';
			if(isset($_POST['狀態']))
				$state=($_POST['狀態']=='-1')?'%':$_POST['狀態'];
			else
				$state='%';
			if(isset($_POST['素材識別碼']))
				$material=($_POST['素材識別碼']=='-1'||$_POST['素材識別碼']==null)?'%':$_POST['素材識別碼'];
			else
				$material='%';
			if(isset($_POST['素材群組識別碼']))
				$materialGroup=($_POST['素材群組識別碼']=='0'||$_POST['素材群組識別碼']==null)?'%':$_POST['素材群組識別碼'];
			else
				$materialGroup='%';
			
			$sql=
				'SELECT 託播單.託播單識別碼,託播單狀態名稱 AS 託播單狀態
				FROM
					託播單
					LEFT JOIN 託播單素材 ON 託播單素材.託播單識別碼=託播單.託播單識別碼
					LEFT JOIN 素材 ON 素材.素材識別碼=託播單素材.素材識別碼
					INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
					LEFT JOIN 委刊單 ON 委刊單.委刊單識別碼=託播單.委刊單識別碼
					INNER JOIN 託播單狀態 ON 託播單狀態.託播單狀態識別碼=託播單.託播單狀態識別碼
				WHERE
					(
					'.($searchBy=='%'?'1':' 託播單.託播單識別碼=? OR 託播單CSMS群組識別碼=? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ?').'
					)
					'.($adowner=='%'?'':' AND 委刊單.廣告主識別碼 LIKE ? ').'
					'.($orderList=='%'?'':' AND 託播單.委刊單識別碼 LIKE ? ').'
					AND 上層版位識別碼 LIKE ?
					AND 託播單.版位識別碼 LIKE ?
					AND(
						(廣告期間開始時間 BETWEEN ? AND ?) OR (廣告期間結束時間 BETWEEN ? AND ?) OR (? BETWEEN 廣告期間開始時間 AND 廣告期間結束時間)
					)
					AND 託播單.託播單狀態識別碼 LIKE ?
					AND 託播單.託播單狀態識別碼 IN (1,2)'.'
			';
			$param_type = ($searchBy=='%'?'':'iiss').($adowner=='%'?'':'s').($orderList=='%'?'':'s').'ssssssss';
			$a_params = array();
			$a_params[] = &$param_type;
			if($searchBy!='%'){
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$_POST['searchBy'];
			$a_params[] = &$searchBy;
			$a_params[] = &$searchBy;
			}
			if($adowner!='%')
			$a_params[] = &$adowner;
			if($orderList!='%')
			$a_params[] = &$orderList;
			$a_params[] = &$positionType;
			$a_params[] = &$position;
			$a_params[] = &$startDate;
			$a_params[] = &$endDate;
			$a_params[] = &$startDate;
			$a_params[] = &$endDate;
			$a_params[] = &$startDate;
			$a_params[] = &$state;
			
			if($material!='%'){
				$param_type .='s';
				$sql.=' AND 託播單素材.素材識別碼 LIKE ? ';
				$a_params[] = &$material;
			}
			if($materialGroup!='%'){
				$param_type .='s';
				$sql.=' AND 素材.素材群組識別碼 LIKE ? ';
				$a_params[] = &$materialGroup;
			}
			$sql .= ' GROUP BY 託播單識別碼';
			
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			call_user_func_array(array($stmt, 'bind_param'), $a_params);
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}	
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}		
			$result =array();
			$result2 = array();
			while($row=$res->fetch_assoc()){
				$result[]=$row['託播單識別碼'];
				$result2[]=$row['託播單狀態'];
			}
			exit(json_encode(array('id'=>$result,'state'=>$result2),JSON_UNESCAPED_UNICODE));	
		}
	}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
</head>
<body>
<div id="dialog_form"><div id="dialog_body" width="100%" height="100%" frameborder="0" scrolling="yes"></div></div>
<?php include("../order/_searchOrderUI.php");?>
<div style='float:right'>
<button id = 'selectall' class='darkButton'>全選</button> <button id = 'unselectall' class='darkButton'>取消全選</button> <button id = 'selectCurrent' class='darkButton'>全選本頁</button> <button id = 'unselectCurrent' class='darkButton'>取消本頁</button>
</div>
<p style='clean:both'><br></p>
<div id = "datagrid"  style='clean:both'></div>
<div class ='basicBlock Center'>
<button id = 'sendall'>送出託播單</button>  <button id = 'unsendall'>取消送出託播單</button>
</div>
</body>
<script>
	var DG = null;
	$(function() {
		$('#selectall,#unsendall,#sendall,#unselectall,#selectCurrent,#unselectCurrent').hide();
		$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
	});

	//顯示搜尋的委刊單列表
	var OrderSelectedOrNot={};//記錄託播單是否被選擇 結構:{orderId:true/false}
	var OrderState={};//記錄託播單目前狀態 結構:{orderId:sate}
	function showOrderDG(){
		$('#selectall,#unsendall,#sendall,#unselectall,#selectCurrent,#unselectCurrent').show();
		$('#datagrid').html('');
		OrderSelectedOrNot={};
		OrderState={};
		var bypost={
				searchBy:$('#_searchOUI_searchOrder').val()
				,廣告主識別碼:$('#_searchOUI_adOwner').val()
				,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
				,開始時間:$('#_searchOUI_startDate').val()
				,結束時間:$('#_searchOUI_endDate').val()
				,狀態:$('#_searchOUI_orderStateSelectoin').val()
				,版位類型識別碼:$('#_searchOUI_positiontype').val()
				,版位識別碼:$('#_searchOUI_position').val()
				,素材識別碼:$('#_searchOUI_material').val()
				,素材群組識別碼:$('#_searchOUI_materialGroup').val()
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};
		//取的全部的託播單識別碼並建立是否選擇的map
		bypost['method']='全託播單識別碼';
		$.post('',bypost,function(json){
			for(var row=0 ;row<json['id'].length;row++){
				OrderSelectedOrNot[json['id'][row]] = false;
				OrderState[json['id'][row]]=json['state'][row];
			}
		}
		,'json'
		);
		//取得資料
		bypost['method']='OrderInfoBySearch';
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('選擇');
				var stateCol = $.inArray('託播單狀態',json.header);
				for(var row in json.data){
					if(json.data[row][stateCol][0]=='送出'||json.data[row][stateCol][0]=='確定'){
						if(OrderSelectedOrNot[json.data[row][0][0]])
							json.data[row].push(['<input type="checkbox" checked value='+json.data[row][0][0]+'></input>','html']);
						else
							json.data[row].push(['<input type="checkbox" value='+json.data[row][0][0]+'></input>','html']);
						OrderState[json.data[row][0][0]]=json.data[row][stateCol][0];
					}else
						json.data[row].push(['','text']);
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
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				DG.update=function(){
					$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json) {
							var stateCol = $.inArray('託播單狀態',json.header);
							for(var row in json.data){
								if(json.data[row][stateCol][0]=='送出'||json.data[row][stateCol][0]=='確定'){
									if(OrderSelectedOrNot[json.data[row][0][0]])
										json.data[row].push(['<input type="checkbox" checked value='+json.data[row][0][0]+'></input>','html']);
									else
										json.data[row].push(['<input type="checkbox" value='+json.data[row][0][0]+'></input>','html']);
								}else
									json.data[row].push(['','text']);
							}
							DG.set_data(json.data);
							$('#datagrid').find('input[type="checkbox"]').each(function(){
								$(this).change(function(){
									OrderSelectedOrNot[$(this).val()]=$(this).prop("checked");
								});
							});
						},'json');
				}
				
				$('#datagrid').find('input[type="checkbox"]').each(function(){
					$(this).change(function(){
						if($(this).prop("checked"))
							OrderSelectedOrNot[$(this).val()]=true;
						else
							OrderSelectedOrNot[$(this).val()]=false;
					});
				});
			}
			,'json'
		);
	}
	
	//全選與全不選
	//全選
	$('#selectall').click(function(){
		for(var id in OrderSelectedOrNot)
			OrderSelectedOrNot[id] = true;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//全不選
	$('#unselectall').click(function(){
		for(var id in OrderSelectedOrNot)
			OrderSelectedOrNot[id] = false;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	//全選本頁
	$('#selectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
			OrderSelectedOrNot[$(this).val()] = true;
		});
	});
	//全不選本頁
	$('#unselectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
			OrderSelectedOrNot[$(this).val()] = false;
		});
	});
	//確定與取消確定
	$('#sendall,#unsendall').click(function(){
		var sendFlag = true;
		if($(this).attr('id')=='unsendall')
		sendFlag = false;
		var actionText = sendFlag?'送出':'取消送出';
		var dbody = $("#dialog_body");
		dbody.empty();
		sendFlag?dbody.append('<p>託播單送出中</p>'):dbody.append('<p>託播單取消送出中</p>');
		var dialog=$( "#dialog_form" ).dialog({height: 400, width:300, title:"送出託播單"});
		dialog.dialog( "open" );
		var s=0,f=0,all=0,done=0;//成功/失敗/全部/完成數目
		//var zipFile=[];//記錄要壓縮的檔案
		var selectedOrder = [];
		for(var id in OrderSelectedOrNot){		
			if(OrderSelectedOrNot[id]){
				if((sendFlag&&OrderState[id]=='確定')
				||(!sendFlag&&(OrderState[id]=='送出'||OrderState[id]=='待處理'))){
					all++;
					selectedOrder.push(id);
				}
			}
		}
		//先將託播單分群(產生SEPG託播單群組，其版位類型的託播單都以一個托播單一個檔案處理)
		$.post('../order/ajaxToAPI.php',{'action':'群組託播單','selectedOrder':selectedOrder},
			function(json){
				//失敗的託播單
				for(var i in json.failArray){
					f++;
					done++;
					var id = json.failArray[i]['託播單識別碼'];
					dbody.append('<p>託播單(識別碼'+id+')'+actionText+'失敗:'+json.failArray[i]['message']+'</p>');
					if(done==all){
						actionDone();
					}
				}
				//單張的託播單
				for(var i in json.singleArray){
					var id = json.singleArray[i];
					$.ajax({
					async: true,
					type : "POST",
					url : '../order/ajaxToAPI.php',
					data: {action:sendFlag?'API送出託播單':'API取消託播單',託播單識別碼:id},
					dataType : 'json',
					success :
						function(json){
							done++;
							if(json.success==true){
								s++;
								dbody.append('<p>託播單(識別碼'+json.id+')'+actionText+'成功</p>');
							}
							else if(json.success==false){
								f++;
								dbody.append('<p>託播單(識別碼'+json.id+')'+actionText+'失敗:'+json.message+'</p>');
							}
							else{
								f++;
								dbody.append('<p>託播單(識別碼'+json.id+')'+actionText+'失敗'+'</p>');
							}
							if(done==all){
								actionDone();
							}
						}
					});
				}
				//群組的託播單(SEPG)
				for(var i in json.groupArray){
					$.post('../order/ajaxToAPI.php',{'action':'批次產生檔案','ptName':json.groupArray[i]['ptN'],'groupId':json.groupArray[i]['gId'],'ids':json.groupArray[i]['ids'],'APIAction':sendFlag?'send':'delete'},
						function(json){
							var temp = (json.id).split(',');
							var num = 0;
							for(var i in temp)
							if($.inArray(temp[i],selectedOrder)!=-1)
								num++;
							done+=num;
							if(json.success==true){
								s+=temp.length;
								//zipFile.push(json.id+'.xlsx');
								dbody.append('<p>託播單(識別碼'+json.id+')'+actionText+'成功</p>');
							}
							else if(json.success==false){
								f+=temp.length;
								dbody.append('<p>託播單(識別碼'+json.id+')'+actionText+'失敗:'+json.message+'</p>');
							}
							else{
								f+=temp.length;
								dbody.append('<p>託播單(識別碼'+json.id+')'+actionText+'失敗'+'</p>');
							}
							if(done==all){
								actionDone();
							}
						}
						,'json'
					)
				}
			}
			,'json'
		);
		
		function actionDone(){
			dbody.append('<p>勾選的託播單已'+actionText+' 成功:'+s+'張 失敗:'+f+'張 共計:'+(s+f)+'張</p>');
			/*if(zipFile.length!=0){
				var $downloadZipForm= $('<form method="post" action="../order/851/zipDownload.php"/>')
				$downloadZipForm.empty();
				for(var id in zipFile){
					$downloadZipForm.append($('<input type="hidden" name="files[]" value="'+zipFile[id]+'"/>'));
				}
				$downloadZipForm.append($('<input type="submit" value="下載檔案"/>'));
				dbody.append($downloadZipForm);
			}*/
			showOrderDG();
		}
		if(all==0){
			dbody.append('<p>沒有'+actionText+'的託播單</p>');
		}
	});
	
	
</script>
</html>