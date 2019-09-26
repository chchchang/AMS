
<script type="text/javascript">
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
	$('input[name="showAreaCheckBox"],input[name="showDefaultCheckBox"]').click(function(){
		getPositionSchedule();
	});
	
	$('#coloringMethod').change(function(){
		colorOrderSch();
	});
	//**********method
	//取得排程表
	var  getdataUrl ='../position/positionScheduleAjax.php';
	function getPositionSchedule(){
		//取得版位類型名稱
		var ptn =  $('#psch_positiontype option:selected').text().split(':');
		ptn.splice(0,1);
		ptn = ptn.join(':');
		//若是CSMS版位類型，開啟顯示區域選項
		if($.inArray(ptn,CSMSPTN)!=-1){
			$('#showArea').show();
			if(ptn == '頻道short EPG banner')
				$('#showDefault').show();
			else{
				$('#showDefault').hide();
				$('input[name="showDefaultCheckBox"]').each(
					function(){
						$(this).prop('checked',false);
					}
				);
			}
		}
		//不是CSMS版位類型，清空顯示區域選項
		else{
			$('#showArea,#showDefault').hide();
			$('input[name="showAreaCheckBox"],input[name="showDefaultCheckBox"]').each(
				function(){
					$(this).prop('checked',false);
				}
			);
		}
		//檢查日期是否合格
		std = $('#startDatePicker').val();
		edd = $('#endDatePicker').val();
		if(std == '' || edd == ''){
			alert('請選擇日期');
			return 0;
		}
		if(std>edd){
			alert('開始日期必須不大於結束日期');
			return 0;
		}
		
		$('schDiv').mask('託播單確定中...');
		//取的資料
		//post用參數
		var bypost = {
		action:'版位排程'
		,'版位類型識別碼':$('#psch_positiontype').val()
		,'版位識別碼':$('#psch_position').val()
		,'開始日期':$('#startDatePicker').val()
		,'結束日期':$('#endDatePicker').val()
		,'顯示模式':$('input[name=displayType]:checked').val()
		};
		if(typeof(getBookingSch)!='undefined' && getBookingSch)
			bypost['待確認排程'] = true;
		//增加顯示版位區域用參數
		$('input[name="showAreaCheckBox"]:checked').each(function(){
			if(typeof(bypost['顯示區域'])=='undefined')
				bypost['顯示區域']=[];
			bypost['顯示區域'].push($(this).val());
		});
		//過濾預設廣告用參數
		$('input[name="showDefaultCheckBox"]:checked').each(function(){
			bypost['預設廣告過濾']=$(this).val();
		});
		$.post(getdataUrl,bypost,
			function(data){
				//清空資料表
				$('#pschedule').remove();
				//增加資料
				$('#schDiv').append(data.table).css({"max-height": ($(window).height()-100)+"px"});
				$('.orderSch').css("cursor","pointer").click(
					function(e){
						$("#dialog_iframe").attr("src",'../order/orderInfo.php?name='+$(this).attr('orderId')).css({"width":"100%","height":"100%"});
						$("#dialog_iframe").css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height:$(window).height()*0.9, width:$(window).width()*0.7, title:"託播單資料"});
						dialog.dialog( "open" );
					}
				);
				$('#pschedule').tableHeadFixer({"left" : 1});
				colorOrderSch();
				$('schDiv').unmask();
			}
		,'json'
		);
	}
	
	//將資料表上色
	function colorOrderSch(){
		var usedValue =[];
		var key = 'orderId';
		switch($('#coloringMethod').find(":selected").val()){
			case '依版位識別碼上色':
				key = '版位識別碼';
				break;
			case '依委刊單識別碼上色':
				key = '委刊單識別碼';
				break;
			case '依素材識別碼上色':
				key = '素材識別碼';
				break;
			case '依託播單識別碼上色':
				key = 'orderId';
				break;
		}
		$('.orderSch').each(function(){
			var val = $(this).attr(key);
			var index  = $.inArray(val,usedValue);
			if(index == -1){
				index = usedValue.length;
				usedValue.push(val);
			}
			var bgcolor = colorsCodeforTimeTable[index%colorsCodeforTimeTable.length]
			$(this).attr('bgcolor',bgcolor);
		});
	}	
</script>
