<?php
	include('../tool/auth/authAJAX.php');
	if(isset($_POST['method'])){
		$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
		if($my->connect_errno) {
			$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
			exit(json_encode(array('無法連線到資料庫，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		
		if(!$my->set_charset('utf8')) {
			$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit(json_encode(array('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
		}
		//廣搞主SELECTION的選項
		if($_POST['method']=='getAdOwnerSelection'){
			$sql = 'SELECT 廣告主名稱,廣告主識別碼 FROM 廣告主 WHERE DELETED_TIME IS null AND DISABLE_TIME IS null';
			
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
			
			$feedBack= array();
			while($row=$res->fetch_assoc()){
				$feedBack[]=$row;
			}
			echo json_encode($feedBack,JSON_UNESCAPED_UNICODE);
			exit;
		}
		//委刊單SELECTION的選項
		else if($_POST['method']=='getOrderListSelection'){
			$sql = 'SELECT 委刊單名稱,委刊單識別碼 FROM 委刊單 WHERE 廣告主識別碼=? ORDER BY 委刊單識別碼 DESC';
			
			if(!$stmt=$my->prepare($sql)) {
				$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
				exit('無法準備statement，請聯絡系統管理員！');
			}
						
			if(!$stmt->bind_param('i',$_POST["ownerId"])) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit(json_encode(array("dbError"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
			
			if(!$res=$stmt->get_result()) {
				$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			
			$feedBack= array();
			while($row=$res->fetch_assoc()){
				$feedBack[]=$row;
			}
			echo json_encode($feedBack,JSON_UNESCAPED_UNICODE);
			exit;
		}
		exit;
	}
	@include('../tool/auth/auth.php');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<style>
.Center{
	text-align: center;
}
#選擇的廣告主,#選擇的委刊單{
		text-decoration:underline;
		margin-left:3px;
		margin-right:10px;
}
#tabs1Next,#tabs2Next {
    position:absolute;
    bottom:0;
    right:0;
	margin-right:20px;
	margin-bottom:10px;
}
#tabs2Pre,#tabs3Pre {
    position:absolute;
    bottom:0;
    left:0;
	margin-left:20px;
	margin-bottom:10px;
}
</style>
</head>
<body>
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">1.選擇委刊單</a></li>
    <li><a href="#tabs-2">2.選擇投放版位</a></li>
	<li><a href="#tabs-3">3.新增託播單資訊</a></li>
	<li><a href="#tabs-4">4.完成</a></li>
  </ul>
  <div id="tabs-1">
		<div  class ="Center" style="width:100%">廣告主:<select id="adOwner"></select> 委刊單:<select id="orderList" ></select></div>
		<button id = 'tabs1Next'>下一步</button>
  </div>
  <div id="tabs-2">
		<div  class ="Center" style="width:100%">版位類型:<select id="positiontype"></select> 版位名稱:<select id="position" ></select></div>
		<p><img id="positionInfoPic" src="" /></p>
		<button id = 'tabs2Pre'>上一步</button> <button id = 'tabs2Next'>下一步</button>
  </div>
  <div id='tabs-3'>
	廣告主: <a id="選擇的廣告主"></a> &nbsp &nbsp 委刊單: <a id="選擇的委刊單"></a><br>
	<iframe id ='newOrder' width='100%' class ='InfoWindow' frameborder="0" border="0" cellspacing="0" height='700'></iframe>
	<br>
	<button id = 'tabs3Pre'>上一步</button>
  </div>
  <div id='tabs-4'>
	託播單新增完成
	<br>
	<div  class ="Center" style="width:100%"><button id = 'tabs4Continue'>繼續新增託播單</button></div>
  </div>
</div>
<div id="dialog_form2"><iframe id="dialog_iframe2" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<script>
	var $tabs;
	$(function() {
		$tabs = $('#tabs').tabs({disabled: [1,2,3] }); 
		$( "#dialog_form2" ).dialog({autoOpen: false,	modal: true});
		//標籤按鈕設訂
		$("#tabs1Next").click(function(){
			if(	$( "#orderList" ).val()!=null)
				$tabs.tabs('enable', 1).tabs("option", "active", 1).tabs('disable', 0);
			else
				alert('請選擇一張委刊單');
		}); 
		$("#tabs2Next").click(function(){
			if(	$( "#position" ).val()!=null){
				$tabs.tabs('enable', 2).tabs("option", "active", 2).tabs('disable', 1);
				$('#選擇的廣告主').text($( "#adOwner option:selected" ).text());
				$('#選擇的委刊單').text($( "#orderList option:selected" ).text());
				var orderListName = $('#選擇的委刊單').text().split(':');
				orderListName.splice(0,1);
				orderListName = orderListName.join(':');
				$('#newOrder').attr("src",encodeURI("newOrder.php?orderListName="+orderListName
				+"&saveBtnText=確定新增&positionTypeId="+$("#positiontype").val()+"&positionId="+$("#position").val()));
			}else
				alert('請選擇一個版位');
		}); 
		$("#tabs2Pre").click(function(){
			$tabs.tabs('enable', 0).tabs("option", "active", 0).tabs('disable', 1);
		});
		$("#tabs3Pre").click(function(){
			$tabs.tabs('enable', 1).tabs("option", "active", 1).tabs('disable', 2);
		}); 
		$("#tabs4Continue").click(function(){
			$tabs.tabs('enable', 0).tabs("option", "active", 0).tabs('disable', 3);
		});
		
		//廣告主自動完成選項
		$.post('?',{method:'getAdOwnerSelection'}
			,function(json){
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['廣告主識別碼']+":"+json[i]['廣告主名稱'])
					.val(json[i]['廣告主識別碼'])
					.appendTo($("#adOwner"));
				}
				setOrderListSelection($( "#adOwner option:selected" ).val(),"");
				
				$( "#adOwner" ).combobox({
					 select: function( event, ui ) {
						setOrderListSelection(this.value,"");
					 }
				});
			}
			,'json'
		);
		
		//委刊單自動完成選項
		function setOrderListSelection(ownerId){
			$('#orderList').html('');
			$.post('?',{method:'getOrderListSelection',ownerId: ownerId}
			,function(json){
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱'])
					.val(json[i]['委刊單識別碼'])
					.appendTo($("#orderList"));
				}
				$('#orderList').combobox();
				if(json.length>0){
					$( "#orderList" ).combobox('setText',json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱']);
					$( "#orderList" ).val(json[i]['委刊單識別碼']);
				}
				else{
					$( "#orderList" ).combobox('setText','');
					$( "#orderList" ).val(null);
				}
			}
			,'json'
			);
		}
		
		//版位類型自動完成選項
		$.post('orderManaging.php',{method:'getPositionTypeSelection'}
			,function(positionTypeOption){
				for(var i in positionTypeOption){
					var opt = $(document.createElement("option"));
					opt.text(positionTypeOption[i][0]+":"+positionTypeOption[i][1])//紀錄版位類型名稱
					.val(positionTypeOption[i][0])//紀錄版位類型識別碼
					.appendTo($("#positiontype"));
				}
				setPosition($( "#positiontype option:selected" ).val(),"");
				
				$( "#positiontype" ).combobox({
					 select: function( event, ui ) {
						setPosition(this.value);
						setPositionInfoPic()
					 }
				});
			}
			,'json'
		);
		
		//版位自動完成選項
		function setPosition(pId){
			$("#position").empty();
			$.post( "ajaxToDB_Order.php", { action: "getPositionByPositionType",版位類型識別碼:pId }, 
				function( data ) {
					for(var i in data){
						var opt = $(document.createElement("option"));
						opt.text(data[i][0]+":"+data[i][1])//紀錄版位名稱
						.val(data[i][0])//紀錄版位識別碼
						.appendTo($("#position"));
					}
					$('#position').combobox();
					if(data.length>0){
						$( "#position" ).combobox('setText',data[i][0]+":"+data[i][1]);
						$( "#position" ).val(data[i][0]);
					}
					else{
						$( "#position" ).combobox('setText','');
						$( "#position" ).val(null);
					}
					setPositionInfoPic();
					$( "#position" ).combobox({
					select: function( event, ui ) {
						//顯示板位類型示意圖
						setPositionInfoPic();
						}
					});
				}
				,"json"
			);
		}
	});

	//顯示板位示意圖
	function setPositionInfoPic(){
		$.post("../position/ajaxPositionInfoPic.php",{"action":"getInfoPic","版位識別碼":$( "#position option:selected" ).val(),"版位類型識別碼":$( "#positiontype option:selected" ).val()}
			,function(data){
				if(data["success"]){
					$("#positionInfoPic").attr("src",data["src"]);
				}
			}
			,"json"
		);
	}
	
	//託播單新增
		function newOrderSaved(savedOrder){
			//檢查CSMS託播單規則
			$.post("ajaxToDB_Order.php",{"action":"檢察素材CSMS","orders":JSON.stringify(savedOrder)},
				function(data){
					if(!data['success'])
						alert(data['message']);
					else{
						for(var i in data['result']){
							if(!data['result'][i]['success']){
								if(!confirm(data['result'][i]['message']+'是否繼續?'))
								return 0;
							}
						}
						//儲存
						savedEdit={"delete":[],"edit":[]};
						$.post("ajaxToDB_Order.php",
							 {"action":"儲存更變",
							 "orders":JSON.stringify(savedOrder),
							 "orderListId":$( "#orderList option:selected" ).val(),
							 "edits":savedEdit},
							 function(data){
								if(data["dbError"]!=undefined){
									alert(data["dbError"]);
									return 0;
								}
								if(data["success"]){
									$tabs.tabs('enable', 3).tabs("option", "active", 3).tabs('disable', 2);
								}
								alert(data["message"]);
							}
							,'json'
						);
					
					}
				},'json'
			);
		}
		
	//由new_order呼叫，打開素材群組詳細資料視窗
	function openMaterialGroupInfoDialog(id){
		$("#dialog_iframe2").attr("src","../material/searchMaterialGroup.php?showCertainId="+id).css({"width":"100%","height":"100%"}); 
		$( "#dialog_form2" ).dialog({height:$(window).height()*0.7, width:$(window).width()*0.7, title:"素材群組資訊"});
		$( "#dialog_form2" ).dialog('open');
	}
	
</script>
</html>