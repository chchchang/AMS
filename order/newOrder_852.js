	Date.prototype.addDays= function(d){
		this.setDate(this.getDate()+d);
		return this;
	};
	function splitOrder_852(order){
		function guidGenerator() {
			var S4 = function() {
			   return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
			};
			return (S4()+S4()+"-"+S4());
		}
		if(typeof(order.託播單群組識別碼)=='undefined'||order.託播單群組識別碼==''||order.託播單群組識別碼==null)
			order.託播單群組識別碼 = guidGenerator()+'_'+order.版位識別碼;
		//若全時段投放不需拆單
		if(order.廣告可被播出小時時段=='0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23'){
			delete order.託播單群組識別碼;
			return [order];
		}
		
		var hoursArray = _spiltHour852(order.廣告可被播出小時時段);
		
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
		order.廣告期間開始時間='';order.廣告期間結束時間='';

		var stDate= new Date(st[0],parseInt(st[1],10)-1,st[2],0,0,0);
		var edDate= new Date(ed[0],parseInt(ed[1],10)-1,ed[2],0,0,0);
		//計算日期差
		var utc1 = Date.UTC(stDate.getFullYear(), stDate.getMonth(), stDate.getDate());
		var utc2 = Date.UTC(edDate.getFullYear(), edDate.getMonth(), edDate.getDate());
		var dayDiff = Math.floor((utc2 - utc1) / (1000 * 60 * 60 * 24));
		//是否有跨天的flag
		var overDay = false;
		if(hoursArray[0][0]=='0'&&hoursArray[hoursArray.length-1][hoursArray[hoursArray.length-1].length-1]=='23' && dayDiff>0)
			overDay = true;
			
		var addedDays=0;
		for(;stDate<=edDate;stDate.addDays(1)){
			for(var i =0;i<hoursArray.length;i++){
				copyOfOrder = getCopyOfOrder(order);
				//開始日期
				if(addedDays == 0){
					if(parseInt(hoursArray[i][0],10)<=parseInt(st[3],10)){//開始時段小於廣告期間的開始小時
						for(var j =0;j<hoursArray[i].length;j++){
							if(parseInt(hoursArray[i][j],10)==parseInt(st[3],10)){
								//copyOfOrder.廣告期間開始時間= st[0]+"-"+st[1]+"-"+st[2]+" "+addLeadingZero(2,hoursArray[i][j])+":00:00";
								copyOfOrder.廣告期間開始時間= st[0]+"-"+st[1]+"-"+st[2]+" "+st[3]+":"+st[4]+":"+st[5];
								break;
							}
						}
					}
					else{
						copyOfOrder.廣告期間開始時間=  st[0]+"-"+st[1]+"-"+st[2]+" "+addLeadingZero(2,hoursArray[i][0])+":00:00";
					}
					//新增拆單後的託播單
					if(copyOfOrder.廣告期間開始時間!=''){
						//連續時段跨日處理
						if(overDay && i==hoursArray.length-1){
							if(dayDiff ==1 )//下一天就是廣告結束日期
								getStartTimeOfEndOrder(copyOfOrder,hoursArray[0]);
							else{
								tempDate = new Date(stDate.getTime());
								tempDate.addDays(1);
								copyOfOrder.廣告期間結束時間=tempDate.getFullYear()+"-"+addLeadingZero(2,parseInt(tempDate.getMonth(),10)+1)+"-"+addLeadingZero(2,tempDate.getDate())+" "+addLeadingZero(2,hoursArray[0][hoursArray[0].length-1])+":59:59";
							}
						}
						else{//沒有跨日連續時段
							if(dayDiff == 0)//當天天就是廣告結束日期
								getStartTimeOfEndOrder(copyOfOrder,hoursArray[i]);
							else
								copyOfOrder.廣告期間結束時間=  st[0]+"-"+st[1]+"-"+st[2]+" "+addLeadingZero(2,hoursArray[i][hoursArray[i].length-1])+":59:59";
						}
						addtoResult(copyOfOrder);
					}
				}
				//結束日期
				else if(dayDiff == addedDays){
					if(overDay && i==0)
					continue;
				
					if(parseInt(hoursArray[i][hoursArray[i].length-1],10)>=parseInt(ed[3],10)){//開始時段大於廣告期間的結束小時
						for(var j =0;j<=hoursArray[i].length;j++){
							if(parseInt(hoursArray[i][j],10)==parseInt(ed[3],10)){
								//copyOfOrder.廣告期間結束時間= ed[0]+"-"+ed[1]+"-"+ed[2]+" "+addLeadingZero(2,hoursArray[i][j])+":59:59";
								copyOfOrder.廣告期間結束時間= ed[0]+"-"+ed[1]+"-"+ed[2]+" "+ed[3]+":"+ed[4]+":"+ed[5];
								break;
							}
						}
					}
					else{
						copyOfOrder.廣告期間結束時間= ed[0]+"-"+ed[1]+"-"+ed[2]+" "+addLeadingZero(2,hoursArray[i][hoursArray[i].length-1])+":59:59";
					}
					//新增拆單後的託播單
					if(copyOfOrder.廣告期間結束時間!=''){
							copyOfOrder.廣告期間開始時間= ed[0]+"-"+ed[1]+"-"+ed[2]+" "+addLeadingZero(2,hoursArray[i][0])+":00:00";
							addtoResult(copyOfOrder);
					}
				}
				//中間日期
				else{
					//連續時段跨日的第一個時段不用處理(前一日的跨日時段以包含)
					if(overDay && i==0)
						continue;
						
					copyOfOrder.廣告期間開始時間=stDate.getFullYear()+"-"+addLeadingZero(2,parseInt(stDate.getMonth(),10)+1)+"-"+addLeadingZero(2,stDate.getDate())+" "+addLeadingZero(2,hoursArray[i][0])+":00:00";
					//連續時段跨日處理
					if(overDay && i==hoursArray.length-1){
						if(dayDiff-1 == addedDays )//下一天就是廣告結束日期
							getStartTimeOfEndOrder(copyOfOrder,hoursArray[0]);
						else{
							tempDate = new Date(stDate.getTime());
							tempDate.addDays(1);
							copyOfOrder.廣告期間結束時間=tempDate.getFullYear()+"-"+addLeadingZero(2,parseInt(tempDate.getMonth(),10)+1)+"-"+addLeadingZero(2,tempDate.getDate())+" "+addLeadingZero(2,hoursArray[0][hoursArray[0].length-1])+":59:59";
						}
					}
					else//沒有跨日連續時段
						copyOfOrder.廣告期間結束時間=stDate.getFullYear()+"-"+addLeadingZero(2,parseInt(stDate.getMonth(),10)+1)+"-"+addLeadingZero(2,stDate.getDate())+" "+addLeadingZero(2,hoursArray[i][hoursArray[i].length-1])+":59:59";
					addtoResult(copyOfOrder);
				}
			}
			addedDays++;
		}
		
		function addtoResult(order){
			order.廣告可被播出小時時段 = getHourString(copyOfOrder.廣告期間開始時間.split(/[\s,:,-]/)[3],order.廣告期間結束時間.split(/[\s,:,-]/)[3]);
			returnArray.push(order);
		}
		
		//最後一天的廣告時間檢察機制
		function getStartTimeOfEndOrder(copyOfOrder,hours){
			var stt = order.廣告期間開始時間.split(/[\s,:,-]/);
			if(parseInt(hours[hours.length-1],10)>=parseInt(ed[3],10)){//結束時段大於廣告期間的結束小時
				for(var j =0;j<hours.length;j++){
					if(parseInt(hours[j],10)==parseInt(ed[3],10)){
						//copyOfOrder.廣告期間結束時間= ed[0]+"-"+ed[1]+"-"+ed[2]+" "+addLeadingZero(2,hours[j])+":59:59";
						copyOfOrder.廣告期間結束時間= ed[0]+"-"+ed[1]+"-"+ed[2]+" "+ed[3]+":"+ed[4]+":"+ed[5];
						break;
					}
				}
			}
			else{
				copyOfOrder.廣告期間結束時間= ed[0]+"-"+ed[1]+"-"+ed[2]+" "+addLeadingZero(2,hours[hours.length-1])+":59:59";
			}
		}
		
		//只有一個託播單，移除群組識別碼
		if(returnArray.length==1){
			delete returnArray[0].託播單群組識別碼;
		}
		return returnArray;
	}
	
	function _spiltHour852(hoursString){
		var hoursArray = [];//存放各不連續時段用
		hoursArray.push(hoursString.split(','));
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
		
		return hoursArray;
	}
	
	//依照開始與結束時段，產生可被播出小時時段的字串
	function getHourString(st,ed){
		var s = parseInt(st,10) , e= parseInt(ed,10);
		var hours=[];
		if(s<=e)
			for(var i=s ; i<=e ; i++){
				hours.push(i);
			}
		else{
			for(var i=0 ; i<=e ; i++)
				hours.push(i);
			for(var i=s ; i<24 ; i++)
				hours.push(i);
		}
		return hours.join(',');
	}
	
	function saveOrder_852(jobject,action){
		if(action == "new"){
			var spliteds=[];
			for(var i in jobject){
				spliteds=spliteds.concat(splitOrder_852(jobject[i]));
			}
			$(window).unbind('_setWieghDone').bind('_setWieghDone', function(event,orders){parent.newOrderSaved(orders);});
			_setWiegh(spliteds)
		}
		else if(action=="edit"){
			var spliteds=[];
			for(var i in jobject){
				spliteds=spliteds.concat(splitOrder_852(jobject[i]));
			}
			$(window).unbind('_setWieghDone').bind('_setWieghDone', function(event,orders){parent.editOrder(orders,changedOrderId)});
			_setWiegh(spliteds)
		}
		else if(action=="update"){
			//更動現有託播單，jobject中只會有一張託播單
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
				updateOrder();
			}
			else if (hoursArray.length==1){
				updateOrder();
			}
			else{
				alert('修改此版位的現有託播單時，播出時段必須連續');
			}
			
			//post檢察同群組的託播單
			function updateOrder(){
				jobject[0].託播單識別碼=changedOrderId;
				//檢查是否拆單
				var splitA = splitOrder_852(jobject[0]);
				if(splitA.length == 1){
					parent.updateOrder(splitA[0]);
				}
				else{
					alert('修改此版位的現有託播單時，必須確保不會拆單');
				}
			}
		}
	}
	
	//位每一張託播單設置權重
	function _setWiegh(orders,skip){
		skip = (typeof(skip) == 'undefined') ? false : true;
		if(typeof(orders[0]['曝光數分配'])=='undefined' && $('.playTimesLimit').length==0){
			$(window).trigger('_setWieghDone',[orders]);
			return;
		}
		//dialog視窗設定
		var dialog = $('<div id="_setWieghDia"></div>')
		.appendTo('body')
		.dialog({
			width: 600,
			height: 500,
			modal: true,
			title: '分配全體投放次數',
			close:function(event, ui){
			dialog.dialog("close");
			dialog.remove()}
		});
		//取得要分配的曝光數資料
		var plattimesArray={};
		if(typeof(orders[0]['曝光數分配'])!='undefined'){
			//若託播單資料有曝光數分配資料，直接使用
			plattimesArray = orders[0]['曝光數分配'];
		}
		else{
			//託播單資料沒有，從UI取得
			$.each($('.playTimesLimit'),function(){
				plattimesArray[$(this).attr('order')]=$(this).val();
			});
		}
		//依據其他參數新增設定的table
		var done = true;
		for(var corder in plattimesArray){
			if(typeof(orders[0]['其他參數'][corder])!='undefined'){
				var $html ='<table  style="border:3px #cccccc solid; width=100%" cellpadding="10" border="1"><tr><th>'+$('#參數名稱'+corder).text()+'</th><td>'+plattimesArray[corder]+'</td>'
				+'<tbody id="_weightTbody'+corder+'"><tr><th>版位</th><th>開始</th><th>結束</th><th>額外增加比例</th><th>數量</th></tr></tbody>'
				+'<tr><th>設定總次數</th><td><a id = "_allWeight'+corder+'"></a></td></tr></table> '; 
				$('#_setWieghDia').append($html);
				done = false;
			}
		}
		if(done){
			$(window).trigger('_setWieghDone',[orders]);
			return;
		}
		//利用ajqax取得投放百分比
		//整理數據 post:[{版位識別碼:i,星期幾:i,廣告可被播出小時時段:s,廣告期間開始時間:s,廣告期間結束時間:s}]
		var postOrders=[];
		for(var i in orders){
			var order = orders[i];
			var day = new Date(order['廣告期間開始時間']);
			postOrders.push({'版位識別碼':order['版位識別碼'],'星期幾':day.getDay(),'廣告可被播出小時時段':order['廣告可被播出小時時段']
			,'廣告期間開始時間':order['廣告期間開始時間'],'廣告期間結束時間':order['廣告期間結束時間']});
		}

		$.ajax
		({	async: false,
			type : "POST",
			url : '../order/ajaxToDB_Order.php',
			data: {action:'投放次數比例計算',orders:postOrders},
			dataType : 'json',
			success: 
			function(data){
				if(!data.success){
					alert(data.message);
				}
				for(var corder in plattimesArray){
					if(typeof(orders[0]['其他參數'][corder])!='undefined'){
						var sum = 0;
						for(var i in orders){
							//記錄原使參數
							if(typeof(orders[i]['其他參數_原始'])=='undefined')
							orders[i]['其他參數_原始']={};
							orders[i]['其他參數_原始'][corder] = plattimesArray[corder];
							//增加UI
							var order = orders[i];
							$('<tr></tr>').appendTo('#_weightTbody'+corder)
							.append($('<td>'+order.版位名稱+'</td>'+'<td>'+order.廣告期間開始時間+'</td>'+'<td>'+order.廣告期間結束時間+'</td><td>'+data.percentage[i]+'</td>'
							+'<td><input id =_weightInput'+i+' order='+i+' corder='+corder+' type="number" min=0></td>'));
							
							//拆單結果被分配投放次數上限是件
							$('#_weightTbody'+corder+'>tr>td>#_weightInput'+i).change(function(){
								var input = parseInt($(this).val(),10);
								var order = $(this).attr('order');
								var corder = $(this).attr('corder')
								//若拆單結果沒有設定過投放次數,初始化為0
								if(orders[order]['其他參數'][corder] =='')orders[order]['其他參數'][corder] =0;
								$('#_allWeight'+corder).text(parseInt($('#_allWeight'+corder).text(),10)+(input-parseInt(orders[order]['其他參數'][corder],10)));
								orders[order]['其他參數'][corder] =input;
							});
							//設定預設值
							if(data.success){
								var exporsure = Math.round(plattimesArray[corder]*data.data[i]);
								orders[i]['其他參數'][corder] = exporsure;
								$('#_weightTbody'+corder+'>tr>td>#_weightInput'+i).val(exporsure);
								sum+=exporsure;
							}
							else orders[i]['其他參數'][corder] = "";
						}
						$('#_allWeight'+corder).text(sum);
					}
				}
			}
		});
		
		$('#_setWieghDia').append('<button id ="_setWeightBtn">確定</button>');
		//確定按鈕
		$('#_setWeightBtn').click(function(){
			dialog.dialog("close");
			//累積各曝光數參數的總合並去除額外增加曝光數
			var positionPercent = {};//記錄版位增加的額外百分比
			//逐各曝光數參數
			for(var corder in plattimesArray){
				var sum=0;
				if(typeof(orders[0]['其他參數'][corder])=='number'){
					//逐各託播單累加
					for(var i in orders){
						var pid =orders[i]['版位識別碼'];
						if(typeof(positionPercent[pid])=='undefined')//尚未取得曝光數
							$.ajax({
								async: false,
								type : "POST",
								url : '../order/ajaxToDB_Order.php',
								data: {action:'取得額外投放次數百分比',版位識別碼:pid},
								dataType : 'json',
								success : function(data){
									positionPercent[pid] = parseFloat(data);
								}
							});
						sum+=Math.round(orders[i]['其他參數'][corder]/(1+positionPercent[pid]));
					}
					
					for(var i in orders){
						if(typeof(orders[i]['其他參數_原始'])=='undefined')
							orders[i]['其他參數_原始']={};
						orders[i]['其他參數_原始'][corder] = sum;
					}
				}
			}
			
			$(window).trigger('_setWieghDone',[orders]);
			dialog.remove();
		});
		
		//若有設定skip參數，直接觸發_setWeightDone完成分配
		if(skip)
		$('#_setWeightBtn').trigger('click');
	}