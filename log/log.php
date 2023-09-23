<?php
	include('../tool/auth/auth.php');
	
	define('PAGE_SIZE',10);
	
	$my=new MyDB(true);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
	if(isset($_POST['method'])&&$_POST['method']==='getPositions'){
		$myDB=new MyDB(true);
		$sql='
			SELECT
				版位類型識別碼,
				版位類型名稱,
				版位識別碼,
				版位名稱
			FROM
				(SELECT
					版位識別碼 版位類型識別碼,
					版位名稱 版位類型名稱
				FROM
					版位
				WHERE
					上層版位識別碼 IS NULL
				) 版位類型
				LEFT JOIN 版位 ON 版位類型.版位類型識別碼=版位.上層版位識別碼
			ORDER BY
				版位類型識別碼,
				版位識別碼
		';
		$result=$myDB->getResultArray($sql);
		foreach($result as $row){
			if(!isset($positions[$row['版位類型識別碼']]['版位類型名稱'])) {
				$positions[$row['版位類型識別碼']]['版位類型識別碼']=$row['版位類型識別碼'];
				$positions[$row['版位類型識別碼']]['版位類型名稱']=$row['版位類型名稱'];
			}
			if($row['版位識別碼']===null)
				$positions[$row['版位類型識別碼']]['版位']=array();
			else
				$positions[$row['版位類型識別碼']]['版位'][]=array('版位識別碼'=>$row['版位識別碼'],'版位名稱'=>$row['版位名稱']);
		}
		sort($positions);
		header('Content-Type: application/json; charset=utf-8');
		exit(json_encode($positions,JSON_UNESCAPED_UNICODE));
	}
	else if(isset($_POST['method'])&&$_POST['method']==='getOrdersByPositionId'&&isset($_POST['版位識別碼'])) {
		$orders=array();
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
		$totalRowCount=0;	//T.B.D.
		
		//先取得總筆數
		$sql='SELECT COUNT(1) COUNT FROM 託播單 WHERE 版位識別碼=?';
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('i',$_POST['版位識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		if($row=$res->fetch_assoc())
			$totalRowCount=$row['COUNT'];
		else
			exit;
		
		//再取得資料
		$sql='
			SELECT 版位類型.版位名稱 版位類型名稱,版位.版位名稱,託播單識別碼,託播單名稱,廣告期間開始時間,廣告期間結束時間
			FROM 託播單
			INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
			INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
			WHERE 託播單.版位識別碼=?
			ORDER BY 託播單識別碼
			LIMIT ?,'.PAGE_SIZE.'
		';
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('ii',$_POST['版位識別碼'],$fromRowNo)) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		while($row=$res->fetch_assoc())
			$orders[]=array(array($row['版位類型名稱'],'text'),array($row['版位名稱'],'text'),array($row['託播單識別碼'],'text'),array($row['託播單名稱'],'text'),array($row['廣告期間開始時間'],'text'),array($row['廣告期間結束時間'],'text'),array('詳細資料','button'),array('使用記錄','button'));
		
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('版位類型名稱','版位名稱','託播單識別碼','託播單名稱','廣告期間開始時間','廣告期間結束時間','詳細資料','使用記錄'),'data'=>$orders),JSON_UNESCAPED_UNICODE);
		exit;
	}
	//依託播單識別碼、名稱、說明查詢託播單
	else if(isset($_POST['method'])&&$_POST['method']==='getOrdersBySearchBy'&&isset($_POST['searchBy'])) {
		$orders=array();
		$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
		$totalRowCount=0;	//T.B.D.
		$searchBy='%'.$_POST['searchBy'].'%';
		
		//先取得總筆數
		$sql='
			SELECT COUNT(1) COUNT
			FROM 託播單
			WHERE 託播單識別碼 = ? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ?
		';
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('iss',$_POST['searchBy'],$searchBy,$searchBy)) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		if($row=$res->fetch_assoc())
			$totalRowCount=$row['COUNT'];
		else
			exit;
		
		//再取得資料
		$sql='
			SELECT 版位類型.版位名稱 版位類型名稱,版位.版位名稱,託播單識別碼,託播單名稱,廣告期間開始時間,廣告期間結束時間
			FROM 託播單
			INNER JOIN 版位 ON 託播單.版位識別碼=版位.版位識別碼
			INNER JOIN 版位 版位類型 ON 版位.上層版位識別碼=版位類型.版位識別碼
			WHERE 託播單識別碼=? OR 託播單名稱 LIKE ? OR 託播單說明 LIKE ?
			ORDER BY 版位類型.版位識別碼,版位.版位識別碼,託播單識別碼
			LIMIT ?,'.PAGE_SIZE.'
		';
		
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('issi',$_POST['searchBy'],$searchBy,$searchBy,$fromRowNo)) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		while($row=$res->fetch_assoc())
			$orders[]=array(array($row['版位類型名稱'],'text'),array($row['版位名稱'],'text'),array($row['託播單識別碼'],'text'),array($row['託播單名稱'],'text'),array($row['廣告期間開始時間'],'text'),array($row['廣告期間結束時間'],'text'),array('詳細資料','button'),array('使用記錄','button'));
		
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('版位類型名稱','版位名稱','託播單識別碼','託播單名稱','廣告期間開始時間','廣告期間結束時間','詳細資料','使用記錄'),'data'=>$orders),JSON_UNESCAPED_UNICODE);
		exit;
	}
	//使用記錄
	else if(isset($_POST['method'])&&$_POST['method']==='getOrderLogs') {
		//先找出版位類型以決定要使用的使用記錄資料表名稱
		$sql='
			SELECT 版位類型.版位名稱 版位類型名稱
			FROM 託播單
			INNER JOIN 版位 ON 版位.版位識別碼=託播單.版位識別碼
			INNER JOIN 版位 版位類型 ON 版位類型.版位識別碼=版位.上層版位識別碼
			WHERE 託播單.託播單識別碼=?
		';
		$result=$my->getResultArray($sql,'i',$_POST['託播單識別碼']);
		if(count($result)===0){
			header('Content-Type: application/json; charset=UTF-8');
			echo '{}';
			exit;
		}
		else if(array_search($result[0]['版位類型名稱'],array('前置廣告投放系統','首頁banner','專區banner','頻道short EPG banner'))===false){
			header('Content-Type: application/json; charset=UTF-8');
			echo '{}';
			exit;
		}
		else if($result[0]['版位類型名稱']==='前置廣告投放系統'){
			$使用記錄資料表名稱='使用記錄';
		}
		else{
			$使用記錄資料表名稱='使用記錄CSMS';
		}
		
		
		//以託播單識別碼取得使用記錄
		$body=array();
		if($使用記錄資料表名稱==='使用記錄'){
			$sql='
				SELECT *
				FROM 使用記錄
				WHERE 託播單識別碼=?
				ORDER BY 日期,平台,地區
			';
			$result=$my->getResultArray($sql,'i',$_POST['託播單識別碼']);
			$header=array('託播單識別碼','日期','平台','地區','曝光數0','曝光數1','曝光數2','曝光數3','曝光數4','曝光數5','曝光數6','曝光數7','曝光數8','曝光數9','曝光數10','曝光數11','曝光數12','曝光數13','曝光數14','曝光數15','曝光數16','曝光數17','曝光數18','曝光數19','曝光數20','曝光數21','曝光數22','曝光數23','按讚數0','按讚數1','按讚數2','按讚數3','按讚數4','按讚數5','按讚數6','按讚數7','按讚數8','按讚數9','按讚數10','按讚數11','按讚數12','按讚數13','按讚數14','按讚數15','按讚數16','按讚數17','按讚數18','按讚數19','按讚數20','按讚數21','按讚數22','按讚數23','CREATED_TIME');
		}
		else{
			$sql='
				SELECT *
				FROM 使用記錄CSMS
				WHERE 託播單識別碼=?
				ORDER BY 日期,平台,地區
			';
			$result=$my->getResultArray($sql,'i',$_POST['託播單識別碼']);
			$header=array('託播單識別碼','日期','平台','地區','曝光數0','曝光數1','曝光數2','曝光數3','曝光數4','曝光數5','曝光數6','曝光數7','曝光數8','曝光數9','曝光數10','曝光數11','曝光數12','曝光數13','曝光數14','曝光數15','曝光數16','曝光數17','曝光數18','曝光數19','曝光數20','曝光數21','曝光數22','曝光數23','點擊數0','點擊數1','點擊數2','點擊數3','點擊數4','點擊數5','點擊數6','點擊數7','點擊數8','點擊數9','點擊數10','點擊數11','點擊數12','點擊數13','點擊數14','點擊數15','點擊數16','點擊數17','點擊數18','點擊數19','點擊數20','點擊數21','點擊數22','點擊數23','CREATED_TIME');
		}
		if($result){
			foreach($result as $row){
				$tmp_row=array();
				foreach($row as $col)
					$tmp_row[]=array($col);
				$body[]=$tmp_row;
			}
		}
		echo json_encode(array('header'=>$header,'body'=>$body),JSON_UNESCAPED_UNICODE);
		exit;
	}
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css">
<script src="../tool/jquery-3.4.1.min.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<script src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<style>
	button.positionType,button.position {
		width:200px;
		height:50px;
		vertical-align:top;
	}
</style>
<script>
	//global variable
	var 版位=[];
	var DG=null;
	var DG2=null;
	var 版位識別碼=0;
	var searchBy='';
	
	$(document).ready(function() {
	
		//Enter搜尋
		$("#searchOrder").keypress(function(event){
			if (event.keyCode == 13){
					showOrder();	
			}
		});
		
		$('#searchOrderButton').click(function(){
					showOrder();	
		});
		
		$( "#tabs" ).tabs();
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		$('#orderInfoDiv').dialog({
			autoOpen:false,
			width:'80%',
			modal:true
		});
		
		//廣告主自動完成選項
		$.post('../order/newOrderByPage.php',{method:'getAdOwnerSelection'}
			,function(json){
				var adOnwer =$("#adOwner");
				$(document.createElement("option")).text('').val('').appendTo(adOnwer);
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['廣告主識別碼']+":"+json[i]['廣告主名稱'])
					.val(json[i]['廣告主識別碼'])
					.appendTo(adOnwer);
				}
				setOrderListSelection($( "#adOwner option:selected" ).val());
				
				adOnwer.combobox({
					 select: function( event, ui ) {
						setOrderListSelection(this.value);
					 }
				});
			}
			,'json'
		);
		
		//委刊單自動完成選項
		function setOrderListSelection(ownerId){
			$('#orderList').html('');
			$.post('../order/newOrderByPage.php',{method:'getOrderListSelection',ownerId: ownerId}
			,function(json){
				$(document.createElement("option")).text('').val('').appendTo($("#orderList"));
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱'])
					.val(json[i]['委刊單識別碼'])
					.appendTo($("#orderList"));
				}
				$('#orderList').combobox();
				$( "#orderList" ).combobox('setText','');
				$( "#orderList" ).val('');
			}
			,'json'
			);
		}
		
		//委刊單自動完成選項
			function setOrderListSelection(ownerId){
				$('#orderList').html('');
				$.post('../order/newOrderByPage.php',{method:'getOrderListSelection',ownerId: ownerId}
				,function(json){
					$(document.createElement("option")).text('').val('').appendTo($("#orderList"));
					for(var i in json){
						var opt = $(document.createElement("option"));
						opt.text(json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱'])
						.val(json[i]['委刊單識別碼'])
						.appendTo($("#orderList"));
					}
					$('#orderList').combobox();
					$( "#orderList" ).combobox('setText','');
					$( "#orderList" ).val('');
				}
				,'json'
				);
			}
			
			//版位類型自動完成選項
			$.post('../order/orderManaging.php',{method:'getPositionTypeSelection'}
				,function(positionTypeOption){
					$(document.createElement("option")).text('').val('').appendTo($("#positiontype"));
					for(var i in positionTypeOption){
						var opt = $(document.createElement("option"));
						opt.text(positionTypeOption[i][0]+":"+positionTypeOption[i][1])//紀錄版位類型名稱
						.val(positionTypeOption[i][0])//紀錄版位類型識別碼
						.appendTo($("#positiontype"));
					}
					setPosition($( "#positiontype option:selected" ).val());
					
					$( "#positiontype" ).combobox({
						 select: function( event, ui ) {
							setPosition(this.value);
						 }
					});
				}
				,'json'
			);
			
			//版位自動完成選項
			function setPosition(pTId){
				$("#position").empty();
				$.post( "../order/ajaxToDB_Order.php", { action: "getPositionByPositionType",版位類型識別碼:pTId }, 
					function( data ) {
						$(document.createElement("option")).text('').val('').appendTo($("#position"));
						for(var i in data){
							var opt = $(document.createElement("option"));
							opt.text(data[i][0]+":"+data[i][1])//紀錄版位名稱
							.val(data[i][0])//紀錄版位識別碼
							.appendTo($("#position"));
							/*if(positionId == data[i][0])
								opt.attr('selected',true);*/
						}
						$('#position').combobox();
						$( "#position" ).combobox('setText','');
						$( "#position" ).val('');
					}
					,"json"
				);
			}
			
		//設訂素材群組資料
		$("#materialGroup").combobox();
		$.post('../material/ajaxFunction_MaterialInfo.php',{method:'取得素材群組'},
		function(json){
			var materialGroup=json;
			$(document.createElement("option")).text('不指定').val(0).appendTo($("#materialGroup"));
			for(var i in materialGroup){
				var opt = $(document.createElement("option"));
				opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
				.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
				.appendTo($("#materialGroup"));
			}
			$( "#materialGroup" ).combobox({
				 select: function( event, ui ) {
					setMaterial('');
				 }
			});
			$("#materialGroup").val(0).combobox('setText', '不指定');
			setMaterial('');
		}
		,'json'
		);
		//設訂素材資料
		$("#material").combobox();
		function setMaterial(selectedId){
			$.post('../order/ajaxToDB_Order.php',{action:'取得可用素材',素材群組識別碼:$('#materialGroup').val()},
			function(json){
				if(json.success){
					$select = $("#material");
					$select.empty();
					$(document.createElement("option")).text('不指定').val(-1).appendTo($select);
					$(document.createElement("option")).text('未選擇').val(0).appendTo($select);
					for(var i in json.material){
						var opt = $(document.createElement("option"));
						opt.text(json.material[i]["素材識別碼"]+": "+json.material[i]["素材名稱"])//紀錄版位類型名稱
						.val(json.material[i]["素材識別碼"])//紀錄版位類型識別碼
						.appendTo($select);
						if(selectedId==json.material[i]["素材識別碼"])
							$select.combobox('setText', json.material[i]["素材名稱"]);
					}
					if(selectedId!=''){
						$select.val(selectedId);
					}
					else{
						$select.val(-1).combobox('setText', '不指定');
					}
				}
			}
			,'json'
			);
		}
		
		//狀態選擇
		$.post('../order/ajaxFunction_OrderInfo.php',{method:'託播單狀態名稱'},
			function(json){
				$(document.createElement("option"))
					.text("全部類型")
					.val("-1")
					.appendTo($("#orderStateSelectoin"));
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['託播單狀態名稱'])
					.val(json[i]['託播單狀態識別碼'])
					.appendTo($("#orderStateSelectoin"));
				}
			}
			,'json'
		);
	})
	
	//顯示搜尋的託播單列表
	function showOrder(){
		selectedId =[];
		try{
			for(var i in parent.selectedOrder)
			selectedId[i]=parent.selectedOrder[i];
		}
		catch(err){
		}
		
		$('#DG').html('');
		var bypost={
				searchBy:$('#searchOrder').val()
				,廣告主識別碼:$('#adOwner').val()
				,委刊單識別碼:$( "#orderList" ).val()
				,開始時間:$('#startDate').val()
				,結束時間:$('#endDate').val()
				,狀態:$('#orderStateSelectoin').val()
				,版位類型識別碼:$('#positiontype').val()
				,版位識別碼:$('#position').val()
				,素材識別碼:$('#material').val()
				,素材群組識別碼:$('#materialGroup').val()
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};

		//取得資料
		bypost['method']='OrderInfoBySearch';
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('詳細資料','使用記錄');
				for(var row in json.data){
						json.data[row].push(['詳細資料','button'],['使用記錄','button']);
				}
				
				DG=new DataGrid('DG',json.header,json.data);
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
					if(row[x][0]=='詳細資料') {					
						$('#orderInfo').attr('src','../order/orderInfo.php?name='+row[0][0]);
						$('#orderInfoDiv').dialog('open');
					}
					else if(row[x][0]=='使用記錄'){
						if(!DG.is_collapsed()){
							DG.collapse_row(y);
							showOrderLog(1,row[0][0]);
						}
						else{
							$('#DG2').html('');
							DG.uncollapse();
						}
					}
					
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json) {
							for(var row in json.data){
									json.data[row].push(['詳細資料','button'],['使用記錄','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	function showOrderLog(pageNo,orderId){
		var postFields=null;
		
		//$('#DG2').html('');
		
		postFields={method:'getOrderLogs',託播單識別碼:orderId,pageNo:pageNo,order:'使用記錄識別碼',asc:'ASC'};
		
		$.post('?',postFields,function(json) {
			DG2=new DataGrid('DG2',json.header,json.body);
		},'json');
	}
	
		//由orderInfo呼叫，打開廣告主詳細資料視窗
	function openOnwerInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../adowner/ownerInfoTable.php?ownerid='+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"廣告主詳細資料"});
		$( "#dialog_form2" ).dialog( "open" );
	}
	
	//由orderInfo呼叫，打開委刊單詳細資料視窗
	function openOrderListInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../order/newOrderList.php?action=info&orderListId='+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"委刊單詳細資料"});
		$( "#dialog_form2" ).dialog( "open" );
	}
	
	//由orderInfo呼叫，打開素材群組詳細資料視窗
	function openMaterialGroupInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/searchMaterialGroup.php?showCertainId="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({title:"素材群組資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
	//由orderInfo呼叫，打開素材詳細資料視窗
	function openMaterialInfoDialog(id){
		$("#dialog_iframe2").attr("src",'../material/materialInfo.php?id='+id).css({"width":"100%","height":"100%"}); 
		//$( "#dialog_form2" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.7, title:"素材詳細資料"});
		$( "#dialog_form2" ).dialog( "open" );
	}
</script>
</head>
<body>

<div id="tabs">
  <ul>
    <li><a href="#tabs-1">設定走期條件</a></li>
    <li><a href="#tabs-2">設定廣告主/委刊單條件</a></li>
	<li><a href="#tabs-3">設定版位類型/版位條件</a></li>
	<li><a href="#tabs-4">設定素材條件</a></li>
  </ul>
	<div id ='tabs-1'>
		開始日期:<input type="text" id="startDate"></input> 結束日期:<input type="text" id="endDate"></input>
	</div>
	<div id="tabs-2">
		廣告主:<select id="adOwner"></select> 委刊單:<select id="orderList" ></select>
	</div>
	<div id="tabs-3">
		版位類型:<select id="positiontype"></select> 版位名稱:<select id="position" ></select>
	</div>
	<div id="tabs-4">
		素材群組:<select id="materialGroup"></select> 素材:<select id="material" ></select>
	</div>
</div>
<div  class ='basicBlock'>
<select id="orderStateSelectoin"></select>
<input type="text" id="searchOrder" class="searchInput" value='' placeholder="輸入託播單識別碼、名稱、說明查詢"></input> <button id="searchOrderButton" class="searchSubmit">查詢</button>
</div>

<br>

<fieldset>
	<legend>託播單列表</legend>
	<div id="DG"></div>
</fieldset>
<div id="DG2"></div>

<div id="orderInfoDiv">
	<iframe id="orderInfo" style="width:100%;height:100%"></iframe>
</div>
<div id="dialog_form2">
	<iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>
</div>
</body>
</html>