	//設定北中南版位
	function setSCNPosition(vals,$position){
		if(typeof($position)=='undefined')
			$position  = "#position";
		//設定北中南版位	
		for(var i in vals){
			var pname = $($position+" option[value="+vals[i]+"]").text().split(':');
			pname.splice(0,1);
			pname=pname.join(':');//pname = 版位名稱(含區域)
			pname=pname.split('_');
			pname=pname.slice(0,pname.length-1).join('_');//pname = 版位名稱(不含區域)
			$($position+'>option').each(function(){
				var name = $(this).text().split(':');
				name.splice(0,1);
				name=name.join(':')//pname = 版位名稱(含區域)
				if(name==pname+"_北"||name==pname+"_中"||name==pname+"_南"||name==pname+"_IAP")
					$($position).data('tokenize').tokenAdd($(this).val(),$(this).text());
			})
		}
	}
	//同步移除北中南IAP版位
	function removeSCNPosition(vals,$position){
		if(typeof($position)=='undefined')
			$position  = "#position";
		//設定北中南版位	
		for(var i in vals){
			var pname = $($position+" option[value="+vals[i]+"]").text().split(':');
			pname.splice(0,1);
			pname=pname.join(':');//pname = 版位名稱(含區域)
			pname=pname.split('_');
			var area = pname[pname.length-1];//區域
			pname=pname.slice(0,pname.length-1).join('_');//pname = 版位名稱(不含區域)
			if(area=="北"||area=="中"||area=="南")
			$($position+'>option:selected').each(function(){
				var name = $(this).text().split(':');
				name.splice(0,1);
				name=name.join(':')//pname = 版位名稱(含區域)
				if(name==pname+"_北"||name==pname+"_中"||name==pname+"_南"||name==pname+"_IAP"){
					$($position).data('tokenize').tokenRemove($(this).val());
				}
			})
		}
	}


	//取得並設訂連動託播單資料
	function setConnectOrder(url,valArr,dateObj,hours,areas,forceSet){
		//是否強制設定已選擇託播單的參數，若為true，則就算不在候選名單內，也會設定已選token
		if(typeof(forceSet) == 'undefined')
			forceSet = false;
		//若有多組時間:取各組可連動廣告交集
		var byPost = {'連動廣告':true,'Dates':dateObj,'Hours':hours,'Area':areas};
		$.post(url,byPost
			,function(json){
				var bnrSq = ['1','2'];
				for(var bi in bnrSq){
					$select = $('#連動廣告'+bnrSq[bi]);
					$select.data('tokenize').clear();
					$select.empty();
					var CSMS = json[bnrSq[bi]];
					var ids  = valArr[bnrSq[bi]];
					for(var i in CSMS){
							var opt = $(document.createElement("option"));
							var area = CSMS[i]['區域'].join(',');
							opt.text(CSMS[i]['託播單CSMS群組識別碼']+':'+CSMS[i]['託播單名稱']+'('+area+')')
							.val(CSMS[i]['託播單CSMS群組識別碼'])
							.appendTo($select);
							//設置預設廣告
							if($.inArray( CSMS[i]['託播單CSMS群組識別碼'].toString(), ids)!=-1)
								$select.data('tokenize').tokenAdd(CSMS[i]['託播單CSMS群組識別碼'],CSMS[i]['託播單CSMS群組識別碼']+':'+CSMS[i]['託播單名稱']+'('+area+')');
					}
					//強制設定預設廣告
					if(forceSet){
						if(valArr['1'].length>0)
						$.post('../order/ajaxFunction_OrderInfo.php',{method:'CSMSID取得連動託播單名稱',ids:valArr['1']}
						,function(data){
							$select = $('#連動廣告1');
							for(var i in data){
								if($('#連動廣告1 option[value="'+data[i]['託播單CSMS群組識別碼']+'"]').length == 0){
									var area = data[i]['區域'].join(',');
									var opt = $(document.createElement("option"));
									opt.text(data[i]['託播單CSMS群組識別碼']+':'+data[i]['託播單名稱']+'('+area+')')
									.val(data[i]['託播單CSMS群組識別碼'])
									.appendTo($select);
									$select.data('tokenize').tokenAdd(data[i]['託播單CSMS群組識別碼'],data[i]['託播單CSMS群組識別碼']+':'+data[i]['託播單名稱']+'('+area+')');
								}
							}
						}
						,'json'
						)
						
						if(valArr['2'].length>0)
						$.post('../order/ajaxFunction_OrderInfo.php',{method:'CSMSID取得連動託播單名稱',ids:valArr['2']}
						,function(data){
							$select = $('#連動廣告2');
							for(var i in data){
								if($('#連動廣告2 option[value="'+data[i]['託播單CSMS群組識別碼']+'"]').length == 0){
									var area = data[i]['區域'].join(',');
									var opt = $(document.createElement("option"));
									opt.text(data[i]['託播單CSMS群組識別碼']+':'+data[i]['託播單名稱']+'('+area+')')
									.val(data[i]['託播單CSMS群組識別碼'])
									.appendTo($select);
									$select.data('tokenize').tokenAdd(data[i]['託播單CSMS群組識別碼'],data[i]['託播單CSMS群組識別碼']+':'+data[i]['託播單名稱']+'('+area+')');
								}
							}
						}
						,'json'
						)
					}
					
				}
			}
			,'json'
		);
	}
	
	//取得並設訂連動託播單資料
	function setConnectOrder_SEPG(url,id,dateObj,hours){
		$("#前置連動").empty();
		var orderNames = null;
		var orderIds = null;
		
		$.ajax({
		async: false,
		type : "POST",
		url : url,
		data:{method:'取得前置連動託播單','Dates':dateObj,'Hours':hours},
		dataType:'json',
		success:
		function(json){
			var tempNames=[];
			var tempIds = [];
			for(var i in json){
				tempIds.push(json[i]["託播單識別碼"]);
				tempNames.push(json[i]["託播單名稱"]);
			}
			
			if(orderIds == null){
				orderIds = tempIds;
				orderNames = tempNames;
			}
			else{
				orderIds=$.arrayIntersect(orderIds, tempIds);
				orderNames=$.arrayIntersect(orderNames, tempNames);
			}
		}
		});
		
		//加入選項
		$(document.createElement("option")).text('未指定').val(0).appendTo($("#前置連動"));
		for(var i in orderIds){
			var opt = $(document.createElement("option"));
			opt.text(orderIds[i]+":"+orderNames[i])//紀錄版位類型名稱
			.val(orderIds[i])//紀錄版位類型識別碼
			.appendTo($("#前置連動"));
		}
		$("#前置連動").val(0).combobox('setText', '未指定');
		if(typeof(id)!='undefined'){
			for(var i in orderIds){
				if(id == orderIds[i]){
					$("#前置連動").combobox('setText',orderIds[i]+":"+orderNames[i]);
					$("#前置連動").val(orderIds[i]);
				}
			}
		}
	}
	
	function saveOrder_851(jobject,action){
		if(action == "new"){
			var spliteds=[];
			spliteds=spliteds.concat(splitsOrder_851(jobject));
			$(window).unbind('_setWieghDone').bind('_setWieghDone', function(){parent.newOrderSaved(spliteds);});
			_setWiegh(spliteds)
		}
		else if(action=="edit"){
			var spliteds=[];
			spliteds=spliteds.concat(splitsOrder_851(jobject));
			$(window).unbind('_setWieghDone').bind('_setWieghDone', function(){parent.editOrder(spliteds,changedOrderId);});
			_setWiegh(spliteds)
		}
		else if(action=="update"){
			//更動現有託播單，jobject中止會有一張託播單
			jobject[0].託播單識別碼=changedOrderId;
			//檢查時段連續
			hoursArray=[];
			hoursArray.push(jobject[0].廣告可被播出小時時段.split(','));
			for( var i=1;i<hoursArray[0].length;i++){
				if((parseInt(hoursArray[0][i-1],10)+1)!=parseInt(hoursArray[0][i],10)){
					hoursArray.push(hoursArray[0].slice(0,i));
					hoursArray[0].splice(0,i);
					i=0;
				}
			}
			if (hoursArray.length==2 && hoursArray[0][hoursArray[0].length-1] == '23' && hoursArray[1][0]=='0'){
				parent.updateOrder(jobject[0]);
			}
			else if (hoursArray.length==1){
				parent.updateOrder(jobject[0]);
			}
			else{
				alert('修改此版位的現有託播單時，播出時段必須連續');
			}
		}
	}
	
	
	function splitsOrder_851(jobject){
		var pids = [];
		var spliteds=[];
		for(var i in jobject){
			spliteds=spliteds.concat(splitOrder_851(jobject[i]));
			if($.inArray(jobject[i]['版位識別碼'], pids)=== -1)
				pids.push(jobject[i]['版位識別碼']);
		}
		var allPid= pids.join(',');
		
		var csmsIds=[];
		for(var i in spliteds){
			//SEPG : 將同時段的版位共用一個CSMS群組識別碼
			if(spliteds[0]['版位類型名稱']=='頻道short EPG banner'){
				var id = spliteds[i]['廣告可被播出小時時段']+spliteds[i]['廣告期間開始時間']+spliteds[i]['廣告期間結束時間'];
				if(typeof(csmsIds[id])=='undefined')
					csmsIds[id]=guidGenerator();
				spliteds[i]['託播單CSMS群組識別碼'] = csmsIds[id];
			}
			//非SEPG: 同樣類型的版位在不同區域共用一個群組識別碼
			else{
				var id = spliteds[i]['版位名稱'].split('_');
				id = id.slice(0,id.length-1).join('_')+spliteds[i]['廣告可被播出小時時段']+spliteds[i]['廣告期間開始時間']+spliteds[i]['廣告期間結束時間'];
				if(typeof(csmsIds[id])=='undefined')
					csmsIds[id]=guidGenerator();
				spliteds[i]['託播單CSMS群組識別碼'] = csmsIds[id];
			}
			spliteds[i]['託播單群組版位識別碼'] = allPid;
		}
		return spliteds;
	}
	
	
	function guidGenerator() {
			var S4 = function() {
			   return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
			};
			return (S4()+S4()+"-"+S4());
		}
	//拆單
	function splitOrder_851(order){
		if(typeof(order.託播單群組識別碼)=='undefined'||order.託播單群組識別碼==''||order.託播單群組識別碼==null)
			order.託播單群組識別碼 = guidGenerator()+'_'+order.版位識別碼;
		//若全時段投放不需拆單
		if(order.廣告可被播出小時時段=='0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23'){
			delete order.託播單群組識別碼;
			return [order];
		}
		
		var hoursArray = [];//存放各不連續時段用
		hoursArray.push(order.廣告可被播出小時時段.split(','));
			
		//檢查不連續時段並拆解
		for( var i=1;i<hoursArray[0].length;i++){
			if((parseInt(hoursArray[0][i-1],10)+1)!=parseInt(hoursArray[0][i],10)){
				hoursArray.push(hoursArray[0].slice(0,i));
				hoursArray[0].splice(0,i);
				i=0;
			}
		}		
		//將第一個時段array調換至最後一個(為了依照順序)
		hoursArray.push(hoursArray[0]);
		hoursArray.splice(0,1);
		
		//若第一個時段array開頭為0，最後一個時段array結束為23，跨日發生，合併
		if(hoursArray[0][0] == '0' && hoursArray[hoursArray.length-1][hoursArray[hoursArray.length-1].length-1] == '23'){
			hoursArray[hoursArray.length-1] = hoursArray[0].concat(hoursArray[hoursArray.length-1]);
			hoursArray.splice(0,1);
		}

		var st = order.廣告期間開始時間.split(/[\s,:,-]/);
		var ed = order.廣告期間結束時間.split(/[\s,:,-]/);
		var returnArray=[];//回傳結果用
		
		//複製託播單用
		function getCopyOfOrder(order){
			var copyOfOrder ={};
			$.extend(true,copyOfOrder,order);
			copyOfOrder.廣告可被播出小時時段='';
			return copyOfOrder;
		}

		for( var i=0;i<hoursArray.length;i++){
			var copyOfOrder = getCopyOfOrder(order);
			copyOfOrder.廣告可被播出小時時段 = hoursArray[i].join(',');
			returnArray.push(copyOfOrder);
		}
		
		//只有一個託播單，移除群組識別碼
		if(returnArray.length==1){
			delete returnArray[0].託播單群組識別碼;
		}
		return returnArray;
	}
	
	
	function getHours(){
		//將選擇的小時時段轉為String
		//將選擇的小時時段轉為ARRAY
		var hours="";
		var temp=new Array();
		$('input[name="hours"]:checked').each(function(){temp.push($(this).val());});
		return temp.join(',')
	}
	
	//數字補0
	function addLeadingZero(length,str){
		if(typeof(str)!='String')
		str = str.toString();
		var pad = Array(length+1).join("0");
		return pad.substring(0, length - str.length) + str;
	}