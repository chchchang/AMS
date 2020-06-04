
<div id="_searchOUI_tabs">
  <ul>
    <li id ='_searchOUI_tabs_li-1' ><a href="#_searchOUI_tabs-1">設定走期條件</a></li>
    <li id ='_searchOUI_tabs_li-2' ><a href="#_searchOUI_tabs-2">設定廣告主/委刊單條件</a></li>
	<li id ='_searchOUI_tabs_li-3' ><a href="#_searchOUI_tabs-3">設定版位類型/版位條件</a></li>
	<li id ='_searchOUI_tabs_li-4' ><a href="#_searchOUI_tabs-4">設定素材條件</a></li>
  </ul>
	<div id ='_searchOUI_tabs-1'>
		開始日期:<input type="text" id="_searchOUI_startDate"></input> 結束日期:<input type="text" id="_searchOUI_endDate"></input>
	</div>
	<div id="_searchOUI_tabs-2">
		廣告主:<select id="_searchOUI_adOwner"></select> 委刊單:<select id="_searchOUI_orderList" ></select>
	</div>
	<div id="_searchOUI_tabs-3">
		版位類型:<select id="_searchOUI_positiontype"></select> 版位名稱:<select id="_searchOUI_position" ></select>
	</div>
	<div id="_searchOUI_tabs-4">
		素材群組:<select id="_searchOUI_materialGroup"></select> 素材:<select id="_searchOUI_material" ></select>
	</div>
</div>
<div class ='basicBlock'>
<select id="_searchOUI_orderStateSelectoin"></select>
<input type="text" id="_searchOUI_searchOrder" class="searchInput" value='' placeholder="輸入託播單識別碼、名稱、說明查詢"></input> <button id="_searchOUI_searchOrderButton" class="searchSubmit">查詢</button>
</div>
<script>
	$(function() {
		//datePicker
		$( "#_searchOUI_startDate,#_searchOUI_endDate" )
		.datepicker({
			dateFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		})
		//狀態選擇
		$.post('../order/ajaxFunction_OrderInfo.php',{method:'託播單狀態名稱'},
			function(json){
				//console.log(json);
				$(document.createElement("option"))
					.text("全部類型")
					.val("-1")
					.appendTo($("#_searchOUI_orderStateSelectoin"));
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['託播單狀態名稱'])
					.val(json[i]['託播單狀態識別碼'])
					.appendTo($("#_searchOUI_orderStateSelectoin"));
				}
			}
			,'json'
		);
		//Enter搜尋
		$("#_searchOUI_searchOrder").keypress(function(event){
			if (event.keyCode == 13){
				showOrderDG();
			}
		}).autocomplete({
			source :function( request, response) {
						$.post( "../order/autoComplete_forSearchBox.php",{term: request.term, method:'searchText'},
							function( data ) {
							response(JSON.parse(data));
						})
					}
		});
		
		$('#_searchOUI_searchOrderButton').click(function(){
			showOrderDG();
		});
		
		$( "#_searchOUI_tabs" ).tabs();
		
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
		
		//廣告主自動完成選項
		$.post('../order/newOrderByPage.php',{method:'getAdOwnerSelection'}
			,function(json){
				$(document.createElement("option")).text('').val('').appendTo($("#_searchOUI_adOwner"));
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['廣告主識別碼']+":"+json[i]['廣告主名稱'])
					.val(json[i]['廣告主識別碼'])
					.appendTo($("#_searchOUI_adOwner"));
				}
				setOrderListSelection($( "#_searchOUI_adOwner option:selected" ).val());
				
				$( "#_searchOUI_adOwner" ).combobox({
					 select: function( event, ui ) {
						setOrderListSelection(this.value);
					 }
				});
			}
			,'json'
		);
		
		//委刊單自動完成選項
		function setOrderListSelection(ownerId){
			$('#_searchOUI_orderList').html('');
			$.post('../order/newOrderByPage.php',{method:'getOrderListSelection',ownerId: ownerId}
			,function(json){
				$(document.createElement("option")).text('').val('').appendTo($("#_searchOUI_orderList"));
				for(var i in json){
					var opt = $(document.createElement("option"));
					opt.text(json[i]['委刊單識別碼']+":"+json[i]['委刊單名稱'])
					.val(json[i]['委刊單識別碼'])
					.appendTo($("#_searchOUI_orderList"));
				}
				$('#_searchOUI_orderList').combobox();
				$( "#_searchOUI_orderList" ).combobox('setText','');
				$( "#_searchOUI_orderList" ).val('');
			}
			,'json'
			);
		}
		
		//版位類型自動完成選項
		$.post('../order/orderManaging.php',{method:'getPositionTypeSelection'}
			,function(positionTypeOption){
				$(document.createElement("option")).text('').val('').appendTo($("#_searchOUI_positiontype"));
				for(var i in positionTypeOption){
					var opt = $(document.createElement("option"));
					opt.text(positionTypeOption[i][0]+":"+positionTypeOption[i][1])//紀錄版位類型名稱
					.val(positionTypeOption[i][0])//紀錄版位類型識別碼
					.appendTo($("#_searchOUI_positiontype"));
				}
				
				$( "#_searchOUI_positiontype" ).combobox({
					 select: function( event, ui ) {
						_searchOUI_setPosition(this.value);
					 }
				});
				
				if($("#_searchOUI_positiontype").attr('selectedId')!='undefined'){
					var sid = $("#_searchOUI_positiontype").attr('selectedId');
					$("#_searchOUI_positiontype option[value="+sid+"]").prop('selected',true);
					$( "#_searchOUI_positiontype" ).combobox('setText',$("#_searchOUI_positiontype option[value="+sid+"]").text())
					.val(sid);
				}
				_searchOUI_setPosition($("#_searchOUI_positiontype").attr('selectedId'));
				$("#_searchOUI_positiontype").trigger('_searchOUI_positiontype_iniDone');
				
			}
			,'json'
		);
		
		//版位自動完成選項
		function _searchOUI_setPosition(pId){
			$("#_searchOUI_position").empty();
			$.post( "../order/ajaxToDB_Order.php", { action: "getPositionByPositionType",版位類型識別碼:pId }, 
				function( data ) {
					$(document.createElement("option")).text('').val('').appendTo($("#_searchOUI_position"));
					for(var i in data){
						var opt = $(document.createElement("option"));
						opt.text(data[i][0]+":"+data[i][1])//紀錄版位名稱
						.val(data[i][0])//紀錄版位識別碼
						.appendTo($("#_searchOUI_position"));
					}
					$('#_searchOUI_position').combobox();
					$( "#_searchOUI_position" ).combobox('setText','');
					$( "#_searchOUI_position" ).val('');
					
				}
				,"json"
			);
		}
		
		//設訂素材群組資料
		$("#_searchOUI_materialGroup").combobox();
		$.post('../material/ajaxFunction_MaterialInfo.php',{method:'取得素材群組'},
		function(json){
			var materialGroup=json;
			$(document.createElement("option")).text('未指定').val(0).appendTo($("#_searchOUI_materialGroup"));
			for(var i in materialGroup){
				var opt = $(document.createElement("option"));
				opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
				.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
				.appendTo($("#_searchOUI_materialGroup"));
			}
			$( "#_searchOUI_materialGroup" ).combobox({
				 select: function( event, ui ) {
					setMaterial('');
				 }
			});
			$("#_searchOUI_materialGroup").val(0).combobox('setText', '未指定');
			setMaterial('');
		}
		,'json'
		);
		//設訂素材資料
		$("#_searchOUI_material").combobox();
		function setMaterial(selectedId){
			$.post('../order/ajaxToDB_Order.php',{action:'取得可用素材',素材群組識別碼:$('#_searchOUI_materialGroup').val()},
			function(json){
				if(json.success){
					$select = $("#_searchOUI_material");
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
	});
</script>