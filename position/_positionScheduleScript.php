<script src="../tool/GeneralSanitizer.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<script src="../tool/vue/vue.min.js"></script>
<script type="text/javascript">
	//匯出excel表格
	function exportExcel(){
	  //var html = '&lt;meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8" />&lt;title>Excel&lt;/title>';
	  var html = ""; 
	  html += '';
	  html += document.getElementById('pschedule').outerHTML + '';
	  window.open('data:application/vnd.ms-excel,<meta http-equiv=Content-Type content=text/html;charset=utf-8>' + encodeURIComponent(html));

	}

	var showAminationTime=500;
	$( "#dialog_form" ).dialog(
	{autoOpen: false,
	modal: true,
	});
	var CSMSPTN = ['首頁banner','專區banner','專區vod','頻道short EPG banner'];
	var colorsCodeforTimeTable = ['#FFA488','#FFDD55','#CCFF33','#33FF33','#33CCFF','#5555FF','#9955FF','#E93EFF'];//上色用
	
	//*********UI
	$('#showArea,#showDefault').hide();
	$('#psch_position').combobox();
	//版位類型自動完成選項
	$.post('../order/orderManaging.php',{method:'getPositionTypeSelection'}
		,function(positionTypeOption){
			for(var i in positionTypeOption){
				var opt = $(document.createElement("option"));
				opt.text(positionTypeOption[i][0]+":"+positionTypeOption[i][1])//紀錄版位類型名稱
				.val(positionTypeOption[i][0])//紀錄版位類型識別碼
				.appendTo($("#psch_positiontype"));
			}
			setPosition($( "#psch_positiontype option:selected" ).val(),"");
			
			$( "#psch_positiontype" ).combobox({
				 select: function( event, ui ) {
					setPosition(this.value,"");
				 }
			});
		}
		,'json'
	);
	
	//版位自動完成選項
	function setPosition(pId){
		//版位自動完成選項
		$("#psch_position").empty();
		$.post( "../order/ajaxToDB_Order.php", { action: "getPositionByPositionType",版位類型識別碼:pId }, 
			function( data ) {
				$(document.createElement("option")).text('').val('').appendTo($("#psch_position"));
				for(var i in data){
					var opt = $(document.createElement("option"));
					opt.text(data[i][0]+":"+data[i][1])//紀錄版位名稱
					.val(data[i][0])//紀錄版位識別碼
					.appendTo($("#psch_position"));
				}
				$( "#psch_position" ).combobox('setText','');
				$( "#psch_position" ).val('');
				
			}
			,"json"
		);
		//更新版位類型參數選擇
		$("#psch_sortByProperty").empty();
		$("#psch_sortByProperty").append('<option value=-1>無</option>');
		/*$.post( "../position/positionSchedule.php", { method: "取得託播單用參數",版位類型識別碼:pId }, 
			function( data ) {
				for(i in  data){
					$("#psch_sortByProperty").append('<option value="'+HtmlSanitizer.SanitizeHtml(data[i]["版位其他參數順序"]?data[i]["版位其他參數順序"]:"")+'">'+HtmlSanitizer.SanitizeHtml(data[i]["版位其他參數顯示名稱"]?data[i]["版位其他參數顯示名稱"]:"")+'</option>');
				}
				//取得cookie紀錄的最常選用參數
				$.post( "../position/positionScheduleAjax.php", { action: "取得常用參數",版位類型識別碼:pId }, 
					function( paraid ) {
						$("#psch_sortByProperty").val(paraid);
					}
					,"json"
				);
			}
			,"json"
		);*/
	}
	//設定日期選擇器
	$( "#startDatePicker" )
	.datepicker({
		dateFormat: "yy-mm-dd",
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		onClose: function( selectedDate ) {
				$( "#endDatePicker" ).datepicker( "option", "minDate", selectedDate );
			}
	});
	
	$( "#endDatePicker" )
	.datepicker({
		dateFormat: "yy-mm-dd",
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		onClose: function( selectedDate ) {
				$( "#startDatePicker" ).datepicker( "option", "maxDate", selectedDate );
			}
	});
	
	$('#coloringMethod').change(function(){
		colorOrderSch();
	});
	
	//取得排程表
	var  getdataUrl ='../position/positionScheduleAjax.php';
	
	function setJqueryActionOnTable(){
		$('.orderSch').css("cursor","pointer").click(
			function(e){
				$("#dialog_iframe").attr("src",'../order/orderInfo.php?name='+$(this).attr('orderId')).css({"width":"100%","height":"100%"});
				$("#dialog_iframe").css({"width":"100%","height":"100%"}); 
				dialog=$( "#dialog_form" ).dialog({height:$(window).height()*0.9, width:$(window).width()*0.7, title:"託播單資料"});
				dialog.dialog( "open" );
			}
		);
	}

	//將資料表上色
	function colorOrderSch(){
		var usedValue =[];
		var element = 'orderId';
		switch($('#coloringMethod').find(":selected").val()){
			case '依版位識別碼上色':
				element = '版位識別碼';
				break;
			case '依委刊單識別碼上色':
				element = '委刊單識別碼';
				break;
			case '依素材識別碼上色':
				element = '素材識別碼';
				break;
			case '依託播單識別碼上色':
				element = 'orderId';
				break;
		}
		$('.orderSch').each(function(){
			var val = $(this).attr(element);
			var index  = $.inArray(val,usedValue);
			if(index == -1){
				index = usedValue.length;
				usedValue.push(val);
			}
			var bgcolor = colorsCodeforTimeTable[index%colorsCodeforTimeTable.length]
			$(this).attr('bgcolor',bgcolor);
		});
	}

	var playListVueTable =new Vue({
		el: '#vueApp',
		data: {
			showFlag:false,
			yearMonthRow:{},
			dateRow:[],
			dayRow:[],
			orderRows:[]
		},
		methods: {
        	getPositionSche(){
				let uiStartDate = new Date($("#startDatePicker").val());
				let uiEndDate = new Date($("#endDatePicker").val());
				let rowsRelateWithDate = this.getRowsRelateWihtDate(uiStartDate,uiEndDate);
				this.yearMonthRow = rowsRelateWithDate.yearMonthRow;
				this.dateRow = rowsRelateWithDate.dateRow;
				this.dayRow = rowsRelateWithDate.dayRow;
				this.getPositionScheduleDataFromAjax();
			},

			getRowsRelateWihtDate(startDate,endDate){
				let rowsRelateWithDate=
				{
					yearMonthRow:{},
					dateRow:[],
					dayRow:[]
				};
				if (isNaN(startDate) || isNaN(endDate)) {
   					 return rowsRelateWithDate; 
  				}

				let currentDate = startDate;
				while (currentDate <= endDate) {
					const date = new Date(currentDate);
					rowsRelateWithDate.dayRow.push(this.getDayOfWeek(date));

					let dateStr = date.toISOString().split('T')[0];
					rowsRelateWithDate.dateRow.push(dateStr);

					let yM =dateStr.substring(0,7)
					if(rowsRelateWithDate.yearMonthRow[yM] === undefined){
						rowsRelateWithDate.yearMonthRow[yM] = { colspan:0 };
					}
					rowsRelateWithDate.yearMonthRow[yM].colspan++;

					currentDate.setDate(currentDate.getDate() + 1); // 增加一天
				}
				return rowsRelateWithDate; 
			},

			getDayOfWeek(date) {
				const daysOfWeek = ['日', '一', '二', '三', '四', '五', '六'];
				return daysOfWeek[date.getDay()];
			},

			getPositionScheduleDataFromAjax(){
				var bypost = {
				action:'版位排程2.0'
				,'版位類型識別碼':$('#psch_positiontype').val()
				,'版位識別碼':$('#psch_position').val()
				,'開始日期':$('#startDatePicker').val()
				,'結束日期':$('#endDatePicker').val()
				,'顯示模式':$('input[name=displayType]:checked').val()
				,'排序條件':$("#psch_sortByProperty").val()
				,'排序條件名稱':$('#psch_sortByProperty option:selected').text()
				,'排程顯示方式':$("#psch_sortFormat").val()
				};

				//增加顯示版位區域用參數
				$('input[name="showAreaCheckBox"]:checked').each(function(){
					if(typeof(bypost['顯示區域'])=='undefined')
						bypost['顯示區域']=[];
					bypost['顯示區域'].push($(this).val());
				});

				let updateOrderRows = (data)=>{
						if(data.positionOrdersRow === undefined){
							this.orderRows = [];
						}
						else{
							this.orderRows = data.positionOrdersRow;
						}
					}
				
				$.post(getdataUrl,bypost,
					updateOrderRows
				,'json'
				);
			},

			getCustomHtmlAttribute(attrObject) {
				const customAttribute = {};
				for (const key in attrObject) {
					customAttribute[key] = attrObject[key];
        		}
				return customAttribute;
			}
		},
		updated(){
			setJqueryActionOnTable();
			colorOrderSch()
		}
	})
</script>