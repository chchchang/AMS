<?php 
	include('../tool/auth/auth.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == '曝光數參數'){
			$sql ='SELECT 版位其他參數順序
			FROM 參數型態,版位其他參數,版位
			WHERE 
				版位.上層版位識別碼 = 版位其他參數.版位識別碼 
				AND 版位其他參數.版位其他參數型態識別碼 = 參數型態.參數型態識別碼
				AND 參數型態.參數型態名稱 = "投放上限分配"
				AND 版位.版位識別碼 = ?
			';
			$result = $my->getResultArray($sql,'i',$_POST['版位識別碼']);
			if($result==null)
				$result = [];
			$feedback=[];
			foreach($result as $row){
				$feedback[]=$row['版位其他參數順序'];
			}
			
			exit(json_encode($feedback,JSON_UNESCAPED_UNICODE));
		}
		exit;
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
<link href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui-timepicker-addon.css" rel="stylesheet"></link>
<script src="../tool/jquery-ui/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
<script src="../tool/jquery-ui/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script type="text/javascript" src="../order/newOrder_852.js?<?=time()?>"></script>
<script type="text/javascript" src="../order/newOrder_851.js?<?=time()?>"></script>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<style>
#commitGroup {
	float: right;
}
.handle { background: #dddddd}
.ui-selectable>.ui-selecting { background: #FECA40; }
 </style>

</head>
<body>
<?php include('../order/_searchOrderUI.php')?>
<div id="dialog_form">
<div id ='首頁bannerGroupDiv' class='groupDiv'>
首頁banner
<table class = 'styledTable'>
<thead><tr><th><button class ='sortButton'>排序</button></th><th>原使託播單識別碼</th><th>版位</th><th>名稱</th><th>開始時間</th><th>結束時間</th><th>投放時段</th><th>群組編號</th></tr></thead>
<tbody id ='首頁bannerGroupArea' class='Sortable'></tbody>
</table>
<hr>
</div>
<div id ='專區bannerGroupDiv' class='groupDiv'>
專區banner
<table class = 'styledTable'>
<thead><tr><th><button class ='sortButton'>排序</button></th><th>原使託播單識別碼</th><th>版位</th><th>名稱</th><th>開始時間</th><th>結束時間</th><th>投放時段</th><th>群組編號</th></tr></thead>
<tbody id ='專區bannerGroupArea' class='Sortable'></tbody>
</table>
<hr>
</div>
<div id ='專區vodGroupDiv' class='groupDiv'>
專區vod
<table class = 'styledTable'>
<thead><tr><th><button class ='sortButton'>排序</button></th><th>原使託播單識別碼</th><th>版位</th><th>名稱</th><th>開始時間</th><th>結束時間</th><th>投放時段</th><th>群組編號</th></tr></thead>
<tbody id ='專區vodGroupArea' class='Sortable'></tbody>
</table>
<hr>
</div>
<div id ='sepgGroupDiv' class='groupDiv'>
頻道short EPG banner
<table class = 'styledTable'>
<thead><tr><th><button class ='sortButton'>排序</button></th><th>原使託播單識別碼</th><th>版位</th><th>名稱</th><th>開始時間</th><th>結束時間</th><th>投放時段</th><th>群組編號</th></tr></thead>
<tbody id ='sepgGroupArea' class='Sortable'></tbody>
</table>
<hr>
</div>
<button id = 'commitGroup' class='darkButton'>確認分組</button>
</div>
<div id="dialog_form2"><div id = 'successOrder'></div><div id = 'falseOrder'></div><div id = 'message'></div></div>
<button id = 'selectall' class='darkButton'>全選</button> <button id = 'unselectall' class='darkButton'>取消全選</button> <button id = 'selectCurrent' class='darkButton'>全選本頁</button> <button id = 'unselectCurrent' class='darkButton'>取消本頁</button>
<div style="float:right" > <button id = 'commitBatch'>批次確認勾選的託播單</button></div>
<div id = "datagrid" style="clear:both"></div>
</body>
<script>
	var DG = null;
	$( "#dialog_form,#dialog_form2" ).dialog( {autoOpen: false, modal: true} );
	$( "#dialog_form2" ).dialog( {close:function(){DG.update();}} );
	$('#selectall,#unselectall,#selectCurrent,#unselectCurrent,#commitBatch').hide();
	$('#_searchOUI_orderStateSelectoin').hide();
	Date.prototype.yyyymmdd = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		var dd  = this.getDate().toString();
		return yyyy +'-'+ (mm[1]?mm:"0"+mm[0]) +'-'+ (dd[1]?dd:"0"+dd[0]); // padding
	};
	
	var OrderCommitOrNot={};//記錄託播單是否被選擇 結構:{orderId:true/false}
	var orderToCommit_originalid = [];//記錄要備確認的託播單識別碼(原使)
	var orderToCommit = {
		orders:[],//記錄要備確認的託播單
		completeNum:0//已處理完的託播單數目
	};
	
	var CSMSPT = ['首頁banner','專區banner','專區vod','頻道short EPG banner'];
		
	//顯示搜尋的託播單列表
	function showOrderDG(option){
		$('#selectall,#unselectall,#selectCurrent,#unselectCurrent,#commitBatch').show();
		OrderCommitOrNot={};
		var bypost={
			searchBy:$('#_searchOUI_searchOrder').val()
			,廣告主識別碼:$('#_searchOUI_adOwner').val()
			,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
			,版位類型識別碼:$('#_searchOUI_positiontype').val()
			,版位識別碼:$("#_searchOUI_position").val()
			,開始時間:$('#_searchOUI_startDate').val()
			,結束時間:$('#_searchOUI_endDate').val()
			,狀態:6
			,素材識別碼:$('#_searchOUI_material').val()
			,素材群組識別碼:$('#_searchOUI_materialGroup').val()
			,pageNo:1
			,order:'託播單識別碼'
			,asc:'DESC'
			,'全狀態搜尋':true
		};
		//取的全部的託播單識別碼並建立是否選擇的map
		bypost['method']='全託播單識別碼';
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
			for(var row in json){
				OrderCommitOrNot[json[row]] = false;
			}
		},'json'
		);
		//顯示託播單
		bypost['method']='OrderInfoBySearch';
		$('#datagrid').html('');			
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				var oIdIndex = $.inArray('託播單識別碼',json.header);
				json.header.splice(0, 0,['']);
				json.header.push('確認');
				for(var row in json.data){
					json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow"></input>','html']);
					json.data[row].push(['確認','button']);
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
					if(row[x][0]=='確認') {
						//重新定義投放數量分配完成後的動作
						$(window).unbind('_setWieghDone').bind('_setWieghDone', function(event,order){
							$('#_setWieghDia').remove();
							$.post('../booking/ajaxToDB_Booking.php',{'action':'確認銷售前預約託播單','orders':JSON.stringify(order)},
								function(data){
									if(data.success){
										$('#successOrder').append(data.message);
									}
									else{
										$('#falseOrder').append(data.message);
									}
									DG.update();
								}
								,'json'
							);
						});
						$('#successOrder,#falseOrder').empty();
						$( "#dialog_form2" ).dialog({height: $(window).height()*0.8, width:400, title:"確認銷售前預約託播單結果"});
						$('#dialog_form2').dialog('open');
						splitAndSetWeight(DG.getCellText('託播單識別碼',y));
					}
				}
				
				DG.shearch=function(){
					bypost.searchBy=$('#searchOrderList').val();
					DG.update();
				}
				
				
				DG.update=function(){
					$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json) {
							for(var row in json.data){
								if(OrderCommitOrNot[json.data[row][0][0]])
									json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow" checked></input>','html']);
								else
									json.data[row].splice(0, 0,['<input type="checkbox" id="ch'+json.data[row][oIdIndex][0]+'" value='+json.data[row][oIdIndex][0]+' class="chinrow"></input>','html']);
								json.data[row].push(['確認','button']);
							}
							DG.set_data(json.data);
							
							$('#datagrid').find('.chinrow').each(function(){
								$(this).change(function(){
									OrderCommitOrNot[$(this).val()]=$(this).prop("checked");
								});
							});
						},'json');
				}
				
				$('#datagrid').find('.chinrow').each(function(){
					$(this).change(function(){
						OrderCommitOrNot[$(this).val()]=$(this).prop("checked");
					});
				});
			}
			,'json'
		);
	}
	
	//取得託播單資訊
	function splitAndSetWeight(bookingId,skipSetWeight){
		skipSetWeight = typeof(skipSetWeight)=='undefined'?false:true;
		$.post("../order/ajaxToDB_Order.php",{"action":"訂單資訊","託播單識別碼":bookingId},
			function(order){
				//檢查必填資訊
				if(order['委刊單識別碼']==null){
					$('#falseOrder').append('<p>託播單'+order['託播單識別碼']+'確認失敗: 委刊單未選擇</p>');
					orderToCommit.completeNum++;
					if(orderToCommit.completeNum == orderToCommit_originalid.length){
						openGroupingDialog(orderToCommit['orders']);
					}
					return 0;
				}
				if(order['託播單名稱']==null){
					$('#falseOrder').append('<p>託播單'+order['託播單識別碼']+'確認失敗: 託播單名稱未輸入</p>');
					orderToCommit.completeNum++;
					if(orderToCommit.completeNum == orderToCommit_originalid.length){
						openGroupingDialog(orderToCommit['orders']);
					}
					return 0;
				}
				//CSMS素材檢查
				$.post("../order/ajaxToDB_Order.php",{"action":"檢察素材CSMS","orders":JSON.stringify([order])},
					function(data){
						if(data['success']){
							//確認託播單
							for(var i in data['result']){
								if(!data['result'][i]['success']){
									if(!confirm(data['result'][i]['message']+'是否繼續?'))
									return ;
								}
							}
							$.ajax({
								async:false,
								url:'',
								method:'POST',
								data:{'method':'曝光數參數','版位識別碼':order['版位識別碼']},
								datatype:'json',
								success:function(explosure){
									explosure=$.parseJSON(explosure);
									if(explosure.length!=0){
										order['曝光數分配']={};
										for(var i in explosure){
											order['曝光數分配'][explosure[i]]=order['其他參數'][explosure[i]];
										}
									}
									switch(order['版位類型名稱']){
										case '前置廣告投放系統':
											var spliteds=splitOrder_852(order);
											_setWiegh(spliteds,skipSetWeight);
											
											break;
										case "首頁banner":
										case "專區banner":
										case "頻道short EPG banner":
										case "專區vod":
											order['託播單CSMS群組識別碼']= 'newCSMS';
											var spliteds=splitOrder_851(order);
											_setWiegh(spliteds,skipSetWeight);
											
											break;
										default:
											_setWiegh(spliteds,skipSetWeight);
											break;
									}
								}
							});
						}
					}
					,'json'
				);
			}
			,'json'
		);
	}
	
	//全選與全不選
	//全選
	$('#selectall').click(function(){
		for(var id in OrderCommitOrNot)
			OrderCommitOrNot[id] = true;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//全不選
	$('#unselectall').click(function(){
		for(var id in OrderCommitOrNot)
			OrderCommitOrNot[id] = false;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	//全選本頁
	$('#selectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
			OrderCommitOrNot[$(this).val()] = true;
		});
	});
	//全不選本頁
	$('#unselectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
			OrderCommitOrNot[$(this).val()] = false;
		});
	});
	
	$('#commitBatch').click(function(){
		orderToCommit_originalid=[];
		for(var id in OrderCommitOrNot){
			if(OrderCommitOrNot[id])
				orderToCommit_originalid.push(id);
		}
		if(orderToCommit_originalid.length == 0){
			alert('未選擇任何託播單');
			return 0;
		}
		
		orderToCommit.completeNum = 0;
		orderToCommit['orders']=[];
		//重新定義投放數量分配完成後的動作
		$(window).unbind('_setWieghDone').bind('_setWieghDone', function(event,order){
			$('#_setWieghDia').remove();
			orderToCommit['orders']=$.merge(orderToCommit['orders'],order);
			orderToCommit.completeNum++;
			if(orderToCommit.completeNum == orderToCommit_originalid.length){
				openGroupingDialog(orderToCommit['orders']);
			}
			
			/*$.post('../booking/ajaxToDB_Booking.php',{'action':'確認銷售前預約託播單','orders':JSON.stringify(order)},
				function(data){
					alert(data.message);
					DG.update();
				}
				,'json'
			);*/
		});
		$('#successOrder,#falseOrder').empty();
		$( "#dialog_form2" ).dialog({height: $(window).height()*0.8, width:400, title:"確認銷售前預約託播單結果"});
		$('#dialog_form2').dialog('open');
		//開始逐一分割託播單
		for(var i in orderToCommit_originalid){
			splitAndSetWeight(orderToCommit_originalid[i],true);
		}
		
	});
	
	$( ".Sortable" ).sortable({ 
		handle: ".handle"	
	}).selectable({
    filter: 'tr',
    cancel: 'input',
	stop: function() {
		var groupedId = [];
		var $SelectedArea = this;
		waitingToGroup = $( ".ui-selected", this );
		for(var wi = 0;wi< waitingToGroup.length;wi++){
			var i0 = $(waitingToGroup[wi]).attr('index');
			var newGroup = $('#groupOf'+i0).val();
			for(var wi2 = wi;wi2<waitingToGroup.length;wi2++){
				var i =$(waitingToGroup[wi2]).attr('index');
				//檢查目前ID是否被重新編排過，若是則不繼續編組
				if($.inArray($('#groupOf'+i).val(),groupedId)!=-1)
					continue;
				$('#groupOf'+i).val(newGroup);
				var checkRes =checkGroup($('#groupOf'+i).attr('grupArea'),newGroup);
				if(!checkRes.success){
					//檢查失敗，顯示警告並還原前一次的輸入值
					$('#groupOf'+i).val($('#groupOf'+i).attr('preValue'));
				}
				else{
					//檢查成功，更新前一次的輸入值為目前輸入值
					$('#groupOf'+i).attr('preValue',newGroup);
				}
			}
			groupedId.push(newGroup);
		}
	}
})/*.disableSelection()*/;
	
	//排序
	function sort($sortableTbbody) {
		var tr = $('tr', $sortableTbbody);
		tr.sort(function (a, b) {
			return ($(a).find('td input').val() > $(b).find('td input').val()) ? 1 : -1;
		});
		$sortableTbbody.append(tr);
	}
	
	//排序按鈕設定
	$('.sortButton').click(
		function(){
			var sortable = $(this).parent().parent().parent().parent().find('.Sortable');
			sort(sortable);
		}
	);
	
	//開啟結果顯示視窗
	function openGroupingDialog(orders){
		$('.Sortable').empty();
		$('.groupDiv').hide();
		var gi = {
		'首頁bannerGroupArea':0,
		'專區bannerGroupArea':0,
		'專區vodGroupArea':0,
		'sepgGroupArea':0
		}
		for(var i in orders){
			if($.inArray(orders[i]['版位類型名稱'],CSMSPT)!=-1){
				var order = orders[i];
				var temp = order['廣告可被播出小時時段'].split(',');
				var stt = temp[0];
				var edt = temp[temp.length-1];
				if(stt=='0' && edt=='23' && temp.length!=24 ){
					for(var ti =1 ;ti<temp.length;ti++){
						if(parseInt(temp[ti-1],10)!=parseInt(temp[ti],10)-1){
							stt = temp[ti];
							edt = temp[ti-1];
						}
					}
				}
				var groupArea = '';
				switch(order['版位類型名稱']){
					case '首頁banner':
						groupArea = '首頁bannerGroupArea';
						break;
					case '專區banner':
						groupArea = '專區bannerGroupArea';
						break;
					case '專區vod':
						groupArea = '專區vodGroupArea';
						break;
					case '頻道short EPG banner':
						groupArea = 'sepgGroupArea';
						break;
				}
				$('#'+groupArea).append('<tr index ='+i+'><td class ="handle"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td><td>'+order['託播單識別碼']+'</td><td>'+order['版位名稱']+'</td><td>'+order['託播單名稱']+'</td>'
				+'<td>'+order['廣告期間開始時間']+'</td><td>'+order['廣告期間結束時間']+'</td><td>'+stt+'~'+edt+'</td>'
				+'<td>群組:<input type="number" id = "groupOf'+i+'" value='+gi[groupArea]+' index ='+i+' preValue='+(gi[groupArea]++)+' grupArea = "'+groupArea+'"></input></td></tr>');
				
				$('#groupOf'+i).change(function(){
					var checkRes =checkGroup($(this).attr('grupArea'),$(this).val());
					if(!checkRes.success){
						//檢查失敗，顯示警告並還原前一次的輸入值
						alert(checkRes.message);
						$(this).val($(this).attr('preValue'));
					}
					else{
						//檢查成功，更新前一次的輸入值為目前輸入值
						$(this).attr('preValue',$(this).val());
					}
				});
			}
		}
		$( ".Sortable" ).sortable('refresh');
		$('.groupDiv').each(function(){
			if($(this).find('.Sortable tr').length>0)
				$(this).show();
			}
		)
		
		$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"銷售前預約託播單CSMS編組"});
		$( "#dialog_form" ).dialog( "open" );
	}
	
	//檢察分組是否符合標準
	function checkGroup(grupArea,gid){
		//找出目前使用相同gid的託播單
		var orders=[];	
		$( "#"+grupArea+" tr td input" ).each(
			function(){
				if($(this).val()==gid)
				orders.push(orderToCommit['orders'][$(this).attr('index')]);
			}
		)
		//開始檢查
		feedback={success:true,message:''};
		if(orders[0]['版位類型名稱']!='頻道short EPG banner'){
			//非sepg，各區致多一個託播單
			var areaCount ={};
			for(var i in orders){
				var order = orders[i];
				if(order['委刊單識別碼']!=orders[0]['委刊單識別碼']){
					feedback={success:false,message:'群組'+gid+'於'+area+'委刊單識別碼不同'};
					break;
				}
				
				var area = order['版位名稱'].split('_');
				var area = area[area.length-1];
				if(typeof(areaCount[area])=='undefined'){
					areaCount[area]=0
				}
				if((++areaCount[area])>1){
					feedback={success:false,message:'群組'+gid+'於'+area+'區託播單大於一張'};
					break;
				}
			}
		}
		else{
			//sepg，各區託播單設定須相同
			var areaOrder ={};
			for(var i in orders){
				var order = orders[i];
				if(order['委刊單識別碼']!=orders[0]['委刊單識別碼']){
					feedback={success:false,message:'群組'+gid+'於'+area+'委刊單識別碼不同'};
					break;
				}
				
				var area = order['版位名稱'].split('_');
				var area = area[area.length-1];
				if(typeof(areaOrder[area])=='undefined'){
					areaOrder[area]=order
				}
				else{
					corder = areaOrder[area];
					if(order['託播單名稱']!=corder['託播單名稱']){
						feedback={success:false,message:'群組'+gid+'於'+area+'區託播單名稱不同'};
						break;
					}
					if(order['廣告可被播出小時時段']!=corder['廣告可被播出小時時段']){
						feedback={success:false,message:'群組'+gid+'於'+area+'區廣告可被播出小時時段不同'};
						break;
					}
					if(order['廣告期間開始時間']!=corder['廣告期間開始時間']){
						feedback={success:false,message:'群組'+gid+'於'+area+'區廣告期間開始時間不同'};
						break;
					}
					if(!compare(order['其他參數'],corder['其他參數'])){
						feedback={success:false,message:'群組'+gid+'於'+area+'區其他參數設定不同'};
						break;	
					}
					if(!compare(order['素材'],corder['素材'])){
						feedback={success:false,message:'群組'+gid+'於'+area+'區素材設定不同'};
						break;
					}
				}
			}
		}
		return feedback;
	}
	
	//比較託播單用
	function compare(a,b){
		var typea = typeof(a);
		var typeb = typeof(b);
		
		if(typea!=typeb)
			return false;
		if(typea == 'object'){
			if(a.length!=b.length)
				return false;
			for(var i in a){
				if(typeof(b[i])=='undefined')
					return false;
				if(!compare(a[i],b[i]))
					return false;
			}
		}
		else if(a!=b){
			return false;
		}
		
		return true;
	}
	
	//確認分組
	$('#commitGroup').click(function(){
		var sortedIDs = []
		$( ".Sortable tr td input" ).each(
			function(){
				orderToCommit['orders'][$(this).attr('index')]['託播單CSMS群組識別碼'] =  $(this).attr('grupArea')+'_'+$(this).val();
			}
		)
		$.post('../booking/ajaxToDB_Booking.php',{'action':'確認銷售前預約託播單','orders':JSON.stringify(orderToCommit['orders'])},
			function(data){
				if(data.success){
					$('#successOrder').append('<p>'+data.message+'</p>');
				}
				else{
					$('#falseOrder').append('<p>'+data.message+'</p>');
				}
				$( "#dialog_form" ).dialog( "close" );
			}
			,'json'
		);
	});
</script>
</html>