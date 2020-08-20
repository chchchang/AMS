<script type="text/javascript" src="newOrder_VSM.js?<?=time()?>"></script>
<script type="text/javascript" src="../VSM/vsmLinkValueSelector/VodBundleSelector.js"></script>
<script type="text/javascript" src="../VSM/vsmLinkValueSelector/VodPosterSelector.js"></script>
<script type="text/javascript" src="../VSM/vsmLinkValueSelector/appSelector.js"></script>
<script>
//********設定
	if(typeof(positionTypeId)=='undefined')
		alert('請設定positionTypeId');
	if(typeof(positionId)=='undefined')
		alert('請設定positionId');
	if(typeof(action)=='undefined')
		alert('請設定action');
	if(typeof(changedOrderId)=='undefined')
		alert('請設定changedOrderId');
	//ajax監控
	var runningajaxnum=0;
	$( document ).ajaxStart(function() {
		runningajaxnum++;
	});
	$( document ).ajaxStop(function() {
		runningajaxnum--;
	});
	Date.prototype.yyyymmdd = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		var dd  = this.getDate().toString();
		return yyyy +'-'+ (mm[1]?mm:"0"+mm[0]) +'-'+ (dd[1]?dd:"0"+dd[0]); // padding
	};
	$('#position').attr('lock',true);
	$( "#material_dialog_form" ).dialog(
			{autoOpen: false,
			width: 400,
			height: 450,
			modal: true,
			title: '選擇素材'
	});
	//新增增加小時數的DATE prototype
	Date.prototype.addHours= function(h){
		this.setHours(this.getHours()+h);
		return this;
	}
	//增加天數
	Date.prototype.addDays= function(d){
		this.setDate(this.getDate()+d);
		return this;
	}
	var ajaxtodbPath = "../order/ajaxToDB_Order.php";
	
	var otherConfigObj = {};
	var otherConfigObj_default = {};
	var materialObj = {};
	
	//DATE PICKER
	var deadlinePreDay = 5;//往前推算幾個工作日，在showval()中會被板位設定改變
	var d = new Date();
	$( "#StartDate" ).datetimepicker({	
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		//minDate: d.yyyymmdd()+' 00:00:00',
		onClose: function( selectedDate ) {
			$( "#EndDate" ).datepicker( "option", "minDate", selectedDate );
		},
		onSelect: function(dateText) {
			//選擇廣告開始日期後，預約日期推算
			/*var s =$("#StartDate").val().split(" ")[0].split('-')
			var deadline = new Date(parseInt(s[0],10),parseInt(s[1],10)-1,parseInt(s[2],10),00,00,00);
			for(var i =deadlinePreDay; i >0;i--){
				deadline.addDays(-1);
				while(deadline.getDay()==6||deadline.getDay()==0){
					deadline.addDays(-1);
				}
			}
			$('#Deadline').val(deadline.getFullYear()+'-'+addLeadingZero(2,deadline.getMonth()+1)+'-'+addLeadingZero(2,deadline.getDate()));*/
			var s =$("#StartDate").val().split(" ");
			$('#Deadline').val(s[0]);
		}
	});
	$( "#EndDate" ).datetimepicker({
		dateFormat: "yy-mm-dd",
		showSecond: true,
		timeFormat: 'HH:mm:ss',
		hour: 23,
		minute: 59,
		second: 59,
		changeMonth: true,
		changeYear: true,
		monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
		onClose: function( selectedDate ) {
			$( "#StartDate" ).datepicker( "option", "maxDate", selectedDate );
		}
	});
	$( "#Deadline" ).datepicker({dateFormat: "yy-mm-dd",
								changeMonth: true,
								changeYear: true,
								monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
								monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
								//minDate: 0,
								});
	
	//新增時段
	$('#newDuration').click(function(){
		addDuration();
	});
	//託播單名稱自動完成搜尋
	$('#Name').autocomplete({
		source :function( request, response ) {
					$.post( "../order/autoCompleteSearch.php",{term: request.term, column:'託播單名稱', table:'託播單'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	//託播單說明自動完成搜尋
	$('#Info').autocomplete({
		source :function( request, response ) {
					$.post( "../order/autoCompleteSearch.php",{term: request.term, column:'託播單說明', table:'託播單'},
						function( data ) {
						response(JSON.parse(data));
					})
				}
	});
	function addDuration(dst,ded){
		dst = dst || null;
		ded = ded || null;
		//新增UI
		var d = new Date();
		var time =$.now();
		var $tr = $('<tr index = '+$('#durationTb tr').length+'></tr>');
		var $st = $( "<input calss='nonNull' id ='StartDate"+time+"' time ='"+time+"'></input>" ).datetimepicker({	
			dateFormat: "yy-mm-dd",
			showSecond: true,
			timeFormat: 'HH:mm:ss',
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			//minDate: d.yyyymmdd()+' 00:00:00',
			onClose: function( selectedDate ) {
				$( "#EndDate"+$(this).attr('time') ).datepicker( "option", "minDate", selectedDate );
			}
		}).appendTo($('<td></td>').appendTo($tr));
		var $ed = $( "<input calss='nonNull' id ='EndDate"+time+"' time ='"+time+"'></input>" ).datetimepicker({
			dateFormat: "yy-mm-dd",
			showSecond: true,
			timeFormat: 'HH:mm:ss',
			hour: 23,
			minute: 59,
			second: 59,
			changeMonth: true,
			changeYear: true,
			monthNames: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			monthNamesShort: ["1","2","3","4","5","6","7","8","9","10","11","12"],
			onClose: function( selectedDate ) {
				$( "#StartDate"+$(this).attr('time') ).datepicker( "option", "maxDate", selectedDate );
			}
		}).appendTo($('<td></td>').appendTo($tr));
		var $dbt = $('<td><button>刪除</button></td>').click(function(){
			$(this).parent().remove();
		}).appendTo($tr);
		//增加預設值
		if(dst!=null)
		$st.val(dst);
		if(ded!=null)
		$ed.val(ded);
		//設定連動廣告
		//if($('#連動廣告1').length){
		if($('.連動廣告').length){
			$st.focusout(function() {
				m_setConnectOrder(getObjectForSetConnectOrder());
			});
			$ed.focusout(function() {
				m_setConnectOrder(getObjectForSetConnectOrder());
			});
			$dbt.click(function() {
				m_setConnectOrder(getObjectForSetConnectOrder());
			});
		}
		
		$tr.appendTo($('#durationTb'));
	}
	
	//取得版位參數與素材設定
	function initialPositionSetting(positionId){
		$('#configTbody,#materialTbody').empty();
		materialObj = {};
		var promise =
			$.ajax({
			async: false,
			type : "POST",
			url :ajaxtodbPath,
			data: {action:'取得版位素材與參數','版位識別碼':positionId},
			dataType : 'json',
			success :
			function(json){
				if(json.success){
					//設定其他參數
					for(var i in json['其他參數設定']){
						var config = json['其他參數設定'][i];
						var $tr = $('<tr/>');
						$('#configTbody').append($tr);
						$('<td id ="參數名稱'+i+'" order='+i+'/>').text(config['版位其他參數顯示名稱']).appendTo($tr);
						$('<td/>').text(config['參數型態顯示名稱']).appendTo($tr);
						$('<td/>').text((config['版位其他參數是否必填']==0)?'否':'是').appendTo($tr);
						$('<td/>').html((config['版位其他參數是否必填']==0)?'<input id ="是否新增'+i+'" order='+i+' type="checkbox">':'<input id ="是否新增'+i+'" order='+i+' type="checkbox" checked disabled>')
						.appendTo($tr);
						otherConfigObj[i]=config['版位其他參數預設值'];
						if(config['版位其他參數是否必填']==1)
							otherConfigObj_default[i]=config['版位其他參數預設值'];
						
						var $inputtd = $('<td/>').appendTo($tr);
						//連動廣告客制化
						var paraName = config['版位其他參數名稱'];
						if(paraName.startsWith('bannerTransactionId')){
							var connectIndex = paraName.replace('bannerTransactionId','');										
							var $連動 = $('<select  id="連動廣告'+connectIndex+'"  multiple="multiple"  class ="tokenize configValue 連動廣告" order='+i+' />');
							$inputtd.append($連動);
							$('#連動廣告'+connectIndex).tokenize({
									placeholder:"輸入CSMS群組識別碼或關鍵字選擇可連動的託播單"
									,displayDropdownOnFocus:true
									,newElements:false,
									onAddToken: 
										function(value, text, e){
											$.each($('select.連動廣告'),function(){
												var order =$(this).attr('order');
												otherConfigObj[order] = ($(this).val()!=null)?$(this).val().join(','):'';
												if(otherConfigObj[order]!=''){
													$('#是否新增'+order).prop('checked',true);
												}
											});
										},
									onRemoveToken: 
										function(value, text, e){
											$.each($('select.連動廣告'),function(){
												var order =$(this).attr('order');
												otherConfigObj[order] = ($(this).val()!=null)?$(this).val().join(','):'';
												if(otherConfigObj[order]!=''){
													$('#是否新增'+order).prop('checked',true);
												}
											});
										}
								});				
						}
						//SHORTEPG連動CSMS客制化
						else if(config['版位其他參數顯示名稱']=='前置廣告連動'){
							var $連動 = $('<select  id="前置連動" order='+i+' class = "combobox configValue"/>');
							$inputtd.append($連動);
							$( "#前置連動" ).combobox({
								select: function( event, ui ) {
									otherConfigObj[$(this).attr('order')] = ($(this).val()==0)?null:$(this).val();
									if(otherConfigObj[$(this).attr('order')]!=''){
										$('#是否新增'+$(this).attr('order')).prop('checked',true);
									}
								}
							});
							$('#allTimeBtn,#noTimeBtn').click(function(){
								m_sepgConnect($('#前置連動').val());
							});
							//連動託播單設定
							$( "input[name='hours']" ).change(function() {
								m_sepgConnect($('#前置連動').val());
							});
							$( "#StartDate,#EndDate").focusout(function() {
								m_sepgConnect($('#前置連動').val());
							});
						}
						//一般參數型態識別碼
						else{
							var addNullRadio = [1,2,4];
							//增加選擇輸入的radio
							if($.inArray(config['版位其他參數型態識別碼'],addNullRadio)!=-1)
								$inputtd.append('<input type="radio" name="valueRadio'+i+'" order='+i+' value="input" checked>');
							
							switch(config['版位其他參數型態識別碼']){
								case 1 :
									$inputtd.append($('<input type ="text" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
											if(otherConfigObj[$(this).attr('order')]!=''){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
										})
									);
								break;
								case 2 :
									$inputtd.append($('<input type ="number" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
											if(otherConfigObj[$(this).attr('order')]!=''){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
										})
									);
								break;
								case 3 :
									$inputtd.append($('<input type ="checkbox" id="configValue'+i+'" order='+i+' class = "configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = ($(this).is(':checked'))?1:0;
											if(otherConfigObj[$(this).attr('order')]==1){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
										})
									);
								break;
								case 4 :
									$inputtd.append($('<input type ="number" id="configValue'+i+'" order='+i+' class ="playTimesLimit configValue">').change(function(){
											otherConfigObj[$(this).attr('order')] = $(this).val();
											if(otherConfigObj[$(this).attr('order')]!=''){
												$('#是否新增'+$(this).attr('order')).prop('checked',true);
											}
										})
									);
							}
							
							//增加選擇空值的radio
							if($.inArray(config['版位其他參數型態識別碼'],addNullRadio)!=-1){
								$inputtd.append('<input type="radio" name="valueRadio'+i+'" order='+i+' value="null">NULL');
								$('input[name="valueRadio'+i+'"]').change(function(){
									var corder =$(this).attr('order');
									if($('input[name="valueRadio'+corder+'"][value="null"]').prop('checked')){
										otherConfigObj[corder] = null;
										$("#configValue"+corder).prop('disabled',true);
									}
									else{
										otherConfigObj[corder] = $("#configValue"+corder).val();
										$("#configValue"+corder).prop('disabled',false);
									}
								});
							}
						}
					}
					
					//if($('#連動廣告1').length!=0 ||$('#連動廣告2').length!=0||$('#連動廣告3').length!=0 ||$('#連動廣告4').length!=0){
					if($('.連動廣告')){
						//時段全選按鈕
						$('#allTimeBtn,#noTimeBtn').click(function(){
							m_setConnectOrder(getObjectForSetConnectOrder());
						});
						//連動託播單設定
						$( "input[name='hours']" ).change(function() {
							m_setConnectOrder(getObjectForSetConnectOrder());
						});
						$( "#StartDate,#EndDate").focusout(function() {
							m_setConnectOrder(getObjectForSetConnectOrder());
						});		
					}
					//設定素材
					for(var i in json['版位素材設定']){
						var material = json['版位素材設定'][i];
						var $tr = $('<tr/>');
						materialObj[i]={託播單素材是否必填:material['託播單素材是否必填'],可否點擊:0,點擊後開啟類型:'',點擊後開啟位址:'',素材識別碼:0,素材名稱:'未指定'};
						
						$('<td/>').text(material['素材順序']).appendTo($tr);
						
						/*if(material['素材類型名稱']=='影片')
							$('<td class = "mtype" order='+i+'/>').text(material['影片畫質名稱']+material['素材類型名稱']).appendTo($tr);
						else
							$('<td class = "mtype" order='+i+'/>').text(material['素材類型名稱']).appendTo($tr);*/
						$('<td class = "mtype" order='+i+'/>').text(material['顯示名稱']).appendTo($tr);
							
						$('<td/>').text((material['託播單素材是否必填']==0)?'否':'是').appendTo($tr);
						//可否點擊
						$('<td/>').append(
							$('<input type ="checkbox" order='+i+' id="可否點擊'+i+'">').change(function(){
								materialObj[$(this).attr('order')]['可否點擊'] = ($(this).is(':checked'))?1:0;
								//專區vodSH與HD素材設定強制同步
								if($("#positiontype").text()=='專區vod'){
									for(var i in materialObj){
										materialObj[i]['可否點擊'] = ($(this).is(':checked'))?1:0;
										$('#可否點擊'+i).prop('checked',(materialObj[i].可否點擊==1)?true:false);
									}
								}
							})
						).appendTo($tr)
						//點擊後開啟類型
						var ptn = $("#positiontype").text();
						if(ptn.startsWith('單一平台')){
							$('<td/>').append(
							$('<select order='+i+' id="點擊後開啟類型'+i+'" class="linkType VSM"/>')
								.append($('<option value="">NONE</option>'))
								.append($('<option value="internal">internal</option>'))
								.append($('<option value="external">external</option>'))
								.append($('<option value="app">app</option>'))
								.append($('<option value="Vod">Vod</option>'))
								.append($('<option value="VODPoster">VODPoster</option>'))
								.append($('<option value="Channel">Channel</option>'))
								.change(function(){
									materialObj[$(this).attr('order')]['點擊後開啟類型'] = $(this).val();
								}).val('NONE')
							).appendTo($tr)
							
							switch(ptn){
								case "單一平台EPG":
									$tr.find("#點擊後開啟類型"+i)
									.append($('<option value="coverImageIdV">SEPG直向覆蓋圖片</option>'))
									.append($('<option value="coverImageIdH">SEPG橫向覆蓋圖片</option>'));
									console.log($("#點擊後開啟類型"+i).val());
								break;
								
								case "單一平台advertising_page":
								case "單一平台banner":
									$tr.find("#點擊後開啟類型"+i)
									.append($('<option value="netflixPage">NETFLIX</option>'))
								break;
							}
						}
						else{
						$('<td/>').append(
							$('<select order='+i+' id="點擊後開啟類型'+i+'" class="linkType"/>')
								.append($('<option value="NONE">NONE</option>'))
								.append($('<option value="OVA_SERVICE">OVA_SERVICE</option>'))
								.append($('<option value="OVA_CATEGORY">OVA_CATEGORY</option>'))
								.append($('<option value="OVA_VOD_CONTENT">OVA_VOD_CONTENT</option>'))
								.append($('<option value="OVA_CHANNEL">OVA_CHANNEL</option>'))
								.append($('<option value="COVER_A">COVER_A</option>'))
								.append($('<option value="COVER_B">COVER_B</option>'))
								.appendTo($tr).change(function(){
									materialObj[$(this).attr('order')]['點擊後開啟類型'] = $(this).val();
									//專區vodSH與HD素材設定強制同步
									if($("#positiontype").text()=='專區vod'){
										for(var i in materialObj){
											materialObj[i]['點擊後開啟類型'] =  $(this).val();
											$('#點擊後開啟類型'+i).val(materialObj[i].點擊後開啟類型);
										}
									}
								}).val('NONE')
							).appendTo($tr)
						}
						materialObj[i]['點擊後開啟類型'] = 'NONE';
						//點擊後開啟位址
						$('<td/>').append(
							$('<input type ="text" order='+i+' id="點擊後開啟位址'+i+'" class="linkValue">').appendTo($tr).change(function(){
								materialObj[$(this).attr('order')]['點擊後開啟位址'] = $(this).val();
								//專區vodSH與HD素材設定強制同步
								if($("#positiontype").text()=='專區vod'){
									for(var i in materialObj){
										materialObj[i]['點擊後開啟位址'] =  $(this).val();
										$('#點擊後開啟位址'+i).val(materialObj[i].點擊後開啟位址);
									}
								}
							})
						).appendTo($tr)
						
						
						//選擇素材
						$('<td/>').append(
							$('<input type ="button" id="mBtn'+i+'" order='+i+' class="mBtn">').val('0:未指定').appendTo($tr).click(function(){
								$('#選擇素材順序').text($(this).attr('order'));
								$('#選擇素材類型').text($(this).parent().parent().find('.mtype').text());
								setMaterial($(this).val().split(':')[0]);
								$('#material_dialog_form').dialog('open');
							})
						).appendTo($tr)
						$('#materialTbody').append($tr)
					}
					//點擊後開啟位址autocomplete設定
					$('.linkValue').each(function(i, el) {
						var el = $(this);
						el.autocomplete({
							source :function( request, response,data) {
										var order = el.attr('order');
										if( $("#點擊後開啟類型"+order).val()=="OVA_SERVICE"){
											$.post( "../order/autoComplete_forTVDM.php",{term: request.term, target: "OMP"},
												function( data ) {
												response(JSON.parse(data));
											});
										}
										else if($("#點擊後開啟類型"+order).val()=="external"){
											$.post( "../order/autoComplete_forTVDM.php",{term: request.term, target: "VSM"},
												function( data ) {
												response(JSON.parse(data));
											});
										}
										else if($("#點擊後開啟類型"+order).hasClass('VSM'))
										$.post( "../VSM/vsmLinkValueSelector/autoComplete_forVSMLink.php",{term: request.term, linkType: $("#點擊後開啟類型"+order).val()},
											function( data ) {
											response(JSON.parse(data));
										});
										else
										$.post( "../order/autoCompleteSearch.php",{term: request.term, column:'點擊後開啟位址', table:'託播單素材'},
											function( data ) {
											response(JSON.parse(data));
										});
									}
						}).on('autocompletechange change', function () {
							materialObj[$(this).attr('order')]['點擊後開啟位址'] =  $(this).val();
						})
						el.click(function(){
							var order = el.attr('order');
							if( $("#點擊後開啟類型"+order).val()=="Vod"){
								var callback= function(selectedVod){
									el.val(selectedVod["product_id"]+":/"+selectedVod["product_name"]);
									el.trigger("change");
								};
								var VodSelector = new VodBundleSelector(callback);	
							}
							else if($("#點擊後開啟類型"+order).val()=="VODPoster"){
								var callback= function(selectedVod){
									el.val(selectedVod["internal_name"]+":/"+selectedVod["poster_name"]);
									el.trigger("change");
								};
								var VodSelector = new VodPosterSelector(callback);	
							}
							else if($("#點擊後開啟類型"+order).val()=="app"){
								var callback= function(selectedVod){
									el.val(selectedVod["appname"]+":/"+selectedVod["apppara"]);
									el.trigger("change");
								};
								var VodSelector = new appSelector(callback);	
							}
						});
					})
					
					if(action=="info"||action=="orderInDb"||action=="orderFromApi"){
						$("button").hide();
						$("input").prop('disabled', true);
						$("select").prop('disabled', true);
						$("#MaterialGroup,#Material,.combobox").combobox('disable');
						if($("#連動廣告1").length>0)
						if($(".連動廣告").length>0)
						$(".tokenize").data('tokenize').disable();
					}
				}
			}
		});
		promise.done(
			function(){
				$.getScript("newOrder_SEPGBanner_cover_extend.js");
			}
		)
	}
	
	function getObjectForSetConnectOrder(){
		var re  ={
			'1':$.isArray($('#連動廣告1').val())?$('#連動廣告1').val():[],
			'2':$.isArray($('#連動廣告2').val())?$('#連動廣告2').val():[],
			'3':$.isArray($('#連動廣告3').val())?$('#連動廣告3').val():[],
			'4':$.isArray($('#連動廣告4').val())?$('#連動廣告4').val():[]
			};
		return re;
	}
	
	//連動廣告設定
	function m_setConnectOrder(ids,forceSet){
		//是否強制設定已選擇託播單的參數，若為true，則就算不在候選名單內，也會設定已選token
		if(typeof(forceSet) == 'undefined')
			forceSet = false;
		var dateObj=[];
		$('#durationTb tr').each(function(){
			var stt = $(this).find(' input:eq(0)').val();
			var edt = $(this).find(' input:eq(1)').val();
			dateObj.push({'StartDate':stt,'EndDate':edt});
		});
		if($('#positiontype').text()=='專區vod'){
			//檢查有哪些區域
			var areas =[];				
			$.each($('#position option[selected]'),function(){
				var area = $(this).text().split('_');
				area = area[area.length-1];
				if($.inArray(area,areas)==-1){
					areas.push(area);
				}
			});
			setConnectOrder('../order/newOrder.php',ids,dateObj,[getHours()],areas,forceSet);
		}
		else if($('#positiontype').text()=='單一平台barker_vod'){
			setConnectOrderVSM('../order/ajaxForVSM.php',ids,dateObj,[getHours()],forceSet);
		}
	}
	
	function m_sepgConnect(id){
		var dateObj=[];
		$('#durationTb tr').each(function(){
			var stt = $(this).find(' input:eq(0)').val();
			var edt = $(this).find(' input:eq(1)').val();
			dateObj.push({'StartDate':stt,'EndDate':edt});
		});
		setConnectOrder_SEPG('../order/newOrder.php',id,dateObj,[getHours()]);
	}
	
	//設訂素材群組資料
	$( "#MaterialGroup" ).combobox({
		select: function( event, ui ) {
			$.post('../order/orderSession.php',{saveLastMaterialGroup:$('#MaterialGroup').val()});
			setMaterial('');
		}
	});
	$.post('../material/ajaxFunction_MaterialInfo.php',{method:'取得素材群組'},
	function(json){
		var materialGroup=json;
		$(document.createElement("option")).text('未指定').val(0).appendTo($("#MaterialGroup"));
		$("#MaterialGroup").val(0).combobox('setText', '未指定');
		
		$.post('../order/orderSession.php',{'getLastMaterialGroup':1},
			function(json){
				for(var i in materialGroup){
					var opt = $(document.createElement("option"));
					opt.text(materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"])//紀錄版位類型名稱
					.val(materialGroup[i]["素材群組識別碼"])//紀錄版位類型識別碼
					.appendTo($("#MaterialGroup"));
					if(materialGroup[i]["素材群組識別碼"] == parseInt(json,10)){
						$("#MaterialGroup").combobox('setText', materialGroup[i]["素材群組識別碼"]+": "+materialGroup[i]["素材群組名稱"]).val(materialGroup[i]["素材群組識別碼"]);
					}
				}
				setMaterial('');
			},'json');
	}
	,'json'
	);

	//設訂素材資料
	$("#Material").combobox({
		select:function(event,ui){
			$('#matrialConifgTbody').empty();
			if($('#Material').val()!=0)
			$.post('../order/ajaxFunction_OrderInfo.php',{'method':'素材設定資訊','素材識別碼':$('#Material').val()}
				,function(data){
					for(var i in data){
						$('<tr><td>'+data[i]['區域']+'</td><td>'+data[i]['託播單狀態名稱']+'</td><td>'+data[i]['點擊後開啟類型']+'</td><td>'+data[i]['點擊後開啟位址']+'</td>'
						+'<td><button id ="selectMateriaWithConfing'+i+'" index='+i+'>套用</button><input type="hidden" id="materialJson'+i+'"></input></td></tr>')
						.appendTo('#matrialConifgTbody');
						$('#materialJson'+i).val(JSON.stringify(data[i]));
						$('#selectMateriaWithConfing'+i).click(
							function(){
								var index = $(this).attr('index');
								var config =  $.parseJSON($('#materialJson'+index).val());
								var 素材順序 = $('#選擇素材順序').text();
								materialObj[素材順序].可否點擊 = config.可否點擊;
								materialObj[素材順序].點擊後開啟類型 = config.點擊後開啟類型;
								materialObj[素材順序].點擊後開啟位址 = config.點擊後開啟位址;
								$('#可否點擊'+素材順序).prop('checked',(materialObj[素材順序].可否點擊==1)?true:false);
								$('#點擊後開啟類型'+素材順序).val(materialObj[素材順序].點擊後開啟類型);
								$('#點擊後開啟位址'+素材順序).val(materialObj[素材順序].點擊後開啟位址);
								$('#選擇素材').trigger('click');
							}
						);
					}
				}
				,'json'
			);
		}
	});
	//素材被選擇
	$('#選擇素材').click(function(){
			$('#mBtn'+$('#選擇素材順序').text()).val($('#Material option:selected').text());
			materialObj[$('#選擇素材順序').text()]['素材識別碼']=$('#Material').val();
			var temp = $('#Material option:selected').text().split(':')
			temp.splice(0,1)
			var mName = temp.join(':');
			materialObj[$('#選擇素材順序').text()]['素材名稱']=mName;
			$('#material_dialog_form').dialog('close');
		});
	function setMaterial(selectedId){
		$.post(ajaxtodbPath,{action:'取得可用素材',版位類型識別碼:positionTypeId,素材群組識別碼:$('#MaterialGroup').val(),素材順序:$('#選擇素材順序').text()},
		function(json){
			if(json.success){
				$select = $("#Material");
				$select.empty();
				$(document.createElement("option")).text('0:未指定').val(0).appendTo($select);
				for(var i in json.material){
					var opt = $(document.createElement("option"));
					opt.text(json.material[i]["素材識別碼"]+":"+json.material[i]["素材名稱"])//紀錄版位類型名稱
					.val(json.material[i]["素材識別碼"])//紀錄版位類型識別碼
					.appendTo($select);
					if(selectedId==json.material[i]["素材識別碼"])
						$select.combobox('setText', json.material[i]["素材識別碼"]+':'+json.material[i]["素材名稱"]);
				}
				if(selectedId!=''&&selectedId!=0){
					$select.val(selectedId);
					$('#Material option[value='+selectedId+']').prop('selected',true);
				}
				else{
					$select.val(0).combobox('setText', '0:未指定');
					$('#Material option[value=0]').prop('selected',true);
				}
			}
		}
		,'json'
		);
	}
	//設定版位選項

	//**多選 版位多選設訂
	$('#position').tokenize({
		placeholder:"輸入識別碼或關鍵字該版位類型下的版位"
		,displayDropdownOnFocus:true
		,newElements:false
		,onAddToken:function(value, text, e){
			if(action=='new')
			setSCNPosition([value]);
			//if($('#連動廣告1').length != 0 && $('#position').attr('lock')=='false'){
			if($('.連動廣告').length != 0 && $('#position').attr('lock')=='false'){
				m_setConnectOrder(getObjectForSetConnectOrder());
			}
		}
		,onRemoveToken:function(value, e){
			removeSCNPosition([value]);
			//if($('#連動廣告1').length != 0  && $('#position').attr('lock')=='false'){
			if($('.連動廣告').length != 0  && $('#position').attr('lock')=='false'){
				m_setConnectOrder(getObjectForSetConnectOrder());
			}
		}
	});
	//設定版位資料
	function setPosition(pTId,selectedIds){
		$('#position').attr('lock',true);
		$.ajax({
				async: false,
				type : "POST",
				url : ajaxtodbPath,
				data: { action: "getPositionByPositionType",版位類型識別碼:pTId},
				dataType : 'json',
				success :
					function( json ) {
						$select = $('#position');
						$select.empty();
						for(var i in json){
							$(document.createElement("option")).text(json[i]['版位識別碼']+":"+json[i]['版位名稱'])
							.val(json[i]['版位識別碼'])
							.appendTo($select);
						}
						setSelectedPosition(selectedIds)
						$('#position').attr('lock',false);
					}
			});
	}
	//設置預設版位
	function setSelectedPosition(selectedIds){
		$('#position>option').each(function(){
			for(var i in selectedIds)
			if($(this).val()==selectedIds[i])
				$('#position').data('tokenize').tokenAdd($(this).val(),$(this).text());
		})
		$('#position').val(selectedIds);
		if(action=="new")setSCNPosition(selectedIds);
	}
	
	$('#closeSelection').click(function(){
		$("#selectOrder,#closeSelection").hide();
		$('#mainFram').show();
	});
	
	//時段全選按鈕
	$('#allTimeBtn').click(function(){
		$('input[name="hours"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//時段全不選按鈕
	$('#noTimeBtn').click(function(){
		$('input[name="hours"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	
	//還原輸入的資料/資料庫中的資料
	function clearInput(){
		alert('請定義clearInput');
	}
	
	//資料庫中的資料
	function getInfoFromDb(id,selectOrder){
		$.post("../order/ajaxToDB_Order.php",{"action":"訂單資訊","託播單識別碼":id})
				.done(function(data){
					jdata = JSON.parse(data);
					if(selectOrder){
					jdata.託播單群組識別碼 = '';
					jdata.託播單CSMS群組識別碼 = '';
					}
					jdata['版位識別碼'] = String(jdata['版位識別碼']).split(',');
					showVal(jdata)
					if(!selectOrder&&jdata.託播單狀態識別碼!=0&&jdata.託播單狀態識別碼!=3&&jdata.託播單狀態識別碼!=6&& action != 'update'){
						$("button").hide();
						$("input").not('.ui-autocomplete-input').prop('disabled', true);
						$("select").not('#MaterialGroup,#Material').prop('disabled', true);
						$(".combobox").combobox('disable');
						if(jdata.託播單狀態識別碼==1){
							$("#message").text("確定狀態的託播單只可修改素材!");
							$('#saveBtn,#clearBtn').show();
							$("#MaterialGroup,#Material").prop('disabled', false);
						}else{
							$("#message").text("託播單須為預約或逾期狀態才可修改!");
							$("#MaterialGroup,#Material").combobox('disable');
							//$("#連動廣告1").data('tokenize').disable();
						}
						$(".mBtn").prop('disabled', false);
					}
				
				});
	}
	
	//顯示資料
	function showVal(jdata){
			//若有其他AJAX在執行，暫停0.3秒後在繼續顯示
			if(runningajaxnum>0){
				setTimeout(function(){showVal(jdata);}, 300);
				return 0;
			}
			//若是有群組的暫存託播單，使用全體資料
			if(typeof(jdata.群組廣告期間開始時間)!='undefined')
				jdata.廣告期間開始時間 = jdata.群組廣告期間開始時間;
			if(typeof(jdata.群組廣告期間結束時間)!='undefined')
				jdata.廣告期間結束時間 = jdata.群組廣告期間結束時間;
			if(typeof(jdata.群組廣告可被播出小時時段)!='undefined')
				jdata.廣告可被播出小時時段 = jdata.群組廣告可被播出小時時段;
			if(typeof(jdata.託播單群組版位識別碼)!='undefined'){
				if(!$.isArray(jdata.託播單群組版位識別碼))
					jdata.託播單群組版位識別碼 = jdata.託播單群組版位識別碼.split(',');
				jdata.版位識別碼 = jdata.託播單群組版位識別碼;
			}
			//設定版位資料
			positionTypeId = jdata['版位類型識別碼'];
			/*var pid = ($.isArray(jdata["版位識別碼"]))?jdata["版位識別碼"][0]:jdata["版位識別碼"];*/
			$("#positiontype").text(jdata["版位類型名稱"]);
			$("#positiontype").val(jdata["版位類型識別碼"]);
			initialPositionSetting(positionTypeId);
			$("#csmsGroupID").text((typeof(jdata.託播單CSMS群組識別碼)!='undefined'&&jdata.託播單CSMS群組識別碼!=null)?jdata.託播單CSMS群組識別碼:'');
			//**多選
			if ($('#position').val()==null || $('#position').val().length == 0){
				if($.isArray(jdata["版位識別碼"]))
					setPosition(jdata["版位類型識別碼"],jdata["版位識別碼"]);
				else
					setPosition(jdata["版位類型識別碼"],[jdata["版位識別碼"]]);
			}
			
			$("#Name").val(jdata.託播單名稱);
			$("#Info").val(jdata.託播單說明);
			//走期
			if(typeof(jdata.託播單群組開始與結束時間)!='undefined'){
				for(var i in jdata.託播單群組開始與結束時間){
					var sted = jdata.託播單群組開始與結束時間[i].split(',');
					if(i == 0){
						$("#StartDate").val(sted[0]);
						$("#EndDate").val(sted[1]);
					}
					else
						addDuration(sted[0],sted[1]);
				}
			}
			else{
				$("#StartDate").val(jdata.廣告期間開始時間);
				$("#EndDate").val(jdata.廣告期間結束時間);
			}
			
			$("#Deadline").val(jdata.預約到期時間.split(" ")[0]);
			$("#售價").val(jdata.售價);
			
			for(var i =0;i<24;i++)
				$('input[name="hours"]')[i].checked = false;
				
			if(jdata.廣告可被播出小時時段!=""){
				var hours = jdata.廣告可被播出小時時段.split(",");
				for(var i in hours)
					$('input[name="hours"]')[hours[i]].checked = true;
			}
			//其他參數
			var connectIds={'1':[],'2':[],'3':[],'4':[]};//連動廣告用
			if(typeof(jdata['其他參數'])!='undefined'){
				for( var i in jdata['其他參數']){
					if(typeof(jdata['其他參數_原始'])!='undefined' && typeof(jdata['其他參數_原始'][i])!='undefined')
						jdata['其他參數'][i] = jdata['其他參數_原始'][i];
					otherConfigObj[i] = jdata['其他參數'][i];
					$('#configTbody tr td input[id = "configValue'+i+'"]').val(otherConfigObj[i]);
					$('#configTbody tr td input[type="checkbox"][id = "configValue'+i+'"]').prop('checked',(otherConfigObj[i]==1)?true:false);
					$('#configTbody tr td input[id = "是否新增'+i+'"]').prop('checked',true);
					if(otherConfigObj[i] == null){
						if($('input[name = "valueRadio'+i+'"][value = "null"]').length>0){
							$('input[name = "valueRadio'+i+'"][value = "null"]').prop('checked',true);
							$('#configTbody tr td input[id = "configValue'+i+'"]').prop('disabled',true);
						}
					}
					else{
						$('input[name = "valueRadio'+i+'"][value = "input"]').prop('checked',true);
						$('#configTbody tr td input[id = "configValue'+i+'"]').prop('disabled',false);
					}
					
					if($('#參數名稱'+i).text().startsWith('連動廣告') && $('#positiontype').text() == '專區vod'){
						var 連動廣告編號 = $('#參數名稱'+i).text().replace('連動廣告','');
						if(otherConfigObj[i] == null)
							connectIds[連動廣告編號] =[];
						else
							connectIds[連動廣告編號] = otherConfigObj[i].split(',');
					}
					/*else if($('#參數名稱'+i).text()=='連動廣告2' && $('#positiontype').text() == '專區vod')
						connectIds['2'] = otherConfigObj[i].split(',');*/
					else if($('#參數名稱'+i).text()=='前置廣告連動')
						m_sepgConnect(otherConfigObj[i]);
					else if($('#參數名稱'+i).text().startsWith('bannerTransactionId')){
						var 連動廣告編號 = $('#參數名稱'+i).text().replace('連動廣告','');
						if(otherConfigObj[i] == null)
							connectIds[連動廣告編號] =[];
						else
							connectIds[連動廣告編號] = otherConfigObj[i].split(',');
					}
				}
				//if($('#連動廣告1').length != 0)
				if($('.連動廣告').length != 0)
					m_setConnectOrder(connectIds,true);
				
				
			}
			//素材
			if(typeof(jdata['素材'])!='undefined'){
				for( var i in jdata['素材']){
					materialObj[i] = jdata['素材'][i];
					$('#可否點擊'+i).prop('checked',(materialObj[i].可否點擊==1)?true:false);
					$('#點擊後開啟類型'+i).val(materialObj[i].點擊後開啟類型);
					$('#點擊後開啟位址'+i).val(materialObj[i].點擊後開啟位址);
					if(materialObj[i].素材識別碼!=null && materialObj[i].素材識別碼!=0){
						$('#mBtn'+i).val(materialObj[i].素材識別碼+':'+materialObj[i].素材名稱);
					}
					else{
						$('#mBtn'+i).val('0:未指定');
					}
				}
			}
			configOption();
	}

	//對應不同的動作或版位類型做不同的介面設訂(showVal處理完成後呼叫)
	function configOption(){
		if(action!="new"){
			$("#clearBtn").text("還原");
		}
		
		//**多選 若非或修改佔存新增託播單，關閉多選功能
		if(action!="new" && action!="edit"){
			$("#position").data('tokenize').disable();
			$('#clearPosition').hide();
		}
		//***單一平台託播單開啟修改版偽功能
		if(action == 'update' && $("#positiontype").text().startsWith("單一平台")){
			$("#position").data('tokenize').enable();
		}
		
		if(action=="info"||action=="orderInDb"||action=="orderFromApi"){
			$("button").hide();
			$("input").prop('disabled', true);
			$("select").prop('disabled', true);
			$("#MaterialGroup,#Material,.combobox").combobox('disable');
		}
	}
	
	//數字補0
	function addLeadingZero(length,str){
		if(typeof(str)!='String')
		str = str.toString();
		var pad = Array(length+1).join("0");
		return pad.substring(0, length - str.length) + str;
	}
	
	function materialInfo(){
			if($("#MaterialGroup").val()!="")
			parent.openMaterialGroupInfoDialog($("#MaterialGroup").val());
	}
	
	//由selectOrder呼叫，託播單被選擇
	function orderSelected(id){
		getInfoFromDb(id,true);
		$('#selectOrder,#closeSelection').hide();
		$('#mainFram').show();
	}
	
	//代入現有託播單資訊處理
	function selectOrderFun(){
		$('#mainFram').hide();
		$('#selectOrder').attr('src','../order/selectOrder.php?positionType='+positionTypeId+'&position='+positionId).attr('height',$(window).height()-100).show();	
		$('#closeSelection').show();
	}
	
 </script>