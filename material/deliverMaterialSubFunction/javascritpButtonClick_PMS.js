var buttonOnClick=function(event){
		function getColByName(colName){
			return $('#DG td:nth-child('+($('#DG th:contains("'+colName+'")')[0].cellIndex+1)+')')[event.target.parentElement.parentElement.rowIndex-1];
		}
		function getColValueByName(colName){
			return getColByName(colName).textContent;
		}
		
		var buttonName=event.target.textContent;	//先記下來避加上mask之後取得的值被加上後序字串
		
		var value素材識別碼=getColValueByName('素材識別碼');
		var value素材原始檔名=getColValueByName('素材原始檔名');
		var value影片媒體編號=getColValueByName('影片媒體編號');
		var node影片派送時間=getColByName('影片派送時間');
		var node影片媒體編號=getColByName('影片媒體編號');
		var node影片媒體編號北=getColByName('影片媒體編號北');
		var node影片媒體編號南=getColByName('影片媒體編號南');
		
		$(event.target).mask('處理中...');
		$(node影片派送時間).mask('處理中...');
		$(node影片媒體編號).mask('處理中...');
		$(node影片媒體編號北).mask('處理中...');
		$(node影片媒體編號南).mask('處理中...');
		
		//無論是取得結果或是派送影片皆須先取得是否有已送出的託播單使用該素材以便提醒
		$.post(null,{action:'getReorders',素材識別碼:value素材識別碼,ajaxTarget:"PMS"},function(getReordersJson){
			var showReordersAlert=function(json){
				if(json){
					msg="注意：下列託播單已送出但使用到的是舊的影片素材，請等待派片成功之後，再將這些託播單先取消送出並且再次送出後才會生效。\n\n託播單識別碼,託播單名稱\n";
					for(var i in json)
						msg+=json[i].託播單識別碼+','+json[i].託播單名稱+"\n";
					alert(msg);
				}
			};
			//無論是取得結果或是派送影片皆須先取得狀態(取得狀態蘊含更新狀態)
			副檔名=value素材原始檔名.substr(value素材原始檔名.lastIndexOf('.')+1);
			$.post(null,{action:'getAndPutStatus',素材識別碼:value素材識別碼,副檔名:副檔名,ajaxTarget:"PMS"},function(json){
				if(!json.success)
					alert(json.error);
				else{
					var 狀態=['未開始','已完成','失敗','已刪除'];
					node影片媒體編號北.innerHTML=json.chtnIapId;
					node影片媒體編號南.innerHTML=json.chtsIapId;
					if(json.mediaId===''){
						node影片媒體編號.innerHTML=json.mediaId;
						if(buttonName==='取得結果') alert('查無資料，請重新派送影片。');
						if(buttonName==='派送影片'){
							$.post(null,{action:'uploadCF',素材識別碼:value素材識別碼,副檔名:副檔名,ajaxTarget:"PMS"},function(json){
								if(!json.success)
									alert(json.error);
								else{
									node影片派送時間.innerHTML=json.影片派送時間;
									alert('上傳影片成功，請等待PMS自動派片。');
									//上傳成功後，若mediaId原先不為空表示重覆派送，則進行提醒重送已送出託播單。
									if(value影片媒體編號!=='') showReordersAlert(getReordersJson);
								}
							},'json');
						}
					}
					else{
						node影片媒體編號.innerHTML=json.mediaId+'，北區：'+狀態[parseInt(json.chtnStatus,10)]+'、中區：'+狀態[parseInt(json.chtcStatus,10)]+'、南區：'+狀態[parseInt(json.chtsStatus,10)]+'。';
						if(buttonName==='派送影片') alert('已派送，請檢視各欄位結果，不可重覆派送！');
						//取得結果成功後，若mediaId原先不為空且新的mediaId不同於原先的值，則表示重覆派送需進行提醒重送已送出託播單。
						if(buttonName==='取得結果'&&value影片媒體編號!==''&&value影片媒體編號.search(json.mediaId)==-1) showReordersAlert(getReordersJson);
					}
				}
				$(event.target).unmask();
				$(node影片派送時間).unmask();
				$(node影片媒體編號).unmask();
				$(node影片媒體編號北).unmask();
				$(node影片媒體編號南).unmask();
			},'json');
		},'json');
	}