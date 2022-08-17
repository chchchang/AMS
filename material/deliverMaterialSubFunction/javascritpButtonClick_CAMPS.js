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
		var value影片媒體編號=getColValueByName('CAMPS影片媒體編號');
		var node影片派送時間=getColByName('CAMPS影片派送時間');
		var node影片媒體編號=getColByName('CAMPS影片媒體編號');
		var node執行結果=getColByName('執行結果');
		
		$(event.target).mask('處理中...');
		$(node影片派送時間).mask('處理中...');
		$(node影片媒體編號).mask('處理中...');
		
		if(buttonName==='刪除遠端影片'){
			$.post("deliverMaterialCF_CAMPS.php",{action:'deleteRemote',素材識別碼:value素材識別碼,ajaxTarget:"CAMPS"},function(json){
					//node執行結果.innerHTML=HtmlSanitizer.SanitizeHtml(json.message);
					node執行結果.innerHTML=json.message;
					$(event.target).unmask();
					$(node影片派送時間).unmask();
					$(node影片媒體編號).unmask();
				},'json');
		}
		else {
			//無論是取得結果或是派送影片皆須先取得是否有已送出的託播單使用該素材以便提醒
			$.post('deliverMaterialCF.php',{action:'getReorders',素材識別碼:value素材識別碼},function(getReordersJson){
				var showReordersAlert=function(json){
					if(json){
						msg="注意：下列託播單已送出但使用到的是舊的影片素材，請等待派片成功之後，再將這些託播單先取消送出並且再次送出後才會生效。\n\n託播單識別碼,託播單名稱\n";
						for(var i in json)
							msg+=json[i].託播單識別碼+','+json[i].託播單名稱+"\n";
						if(json.length>0)
						alert(msg);
					}
				};
				//無論是取得結果或是派送影片皆須先取得狀態(取得狀態蘊含更新狀態)
				副檔名=value素材原始檔名.substr(value素材原始檔名.lastIndexOf('.')+1);
				if (buttonName == "取得結果"){
					$.post(null,{action:'getAndPutStatus',素材識別碼:value素材識別碼,副檔名:副檔名,素材原始檔名:value素材原始檔名,ajaxTarget:"CAMPS"},function(json){
						if(!json.success)
							//node執行結果.innerHTML=HtmlSanitizer.SanitizeHtml(json.error);
							node執行結果.innerHTML=json.error;
						else{
							if(json.mediaId===''){
								//node影片媒體編號.innerHTML=HtmlSanitizer.SanitizeHtml(json.mediaId);
								node影片媒體編號.innerHTML=json.mediaId;
								node執行結果.innerHTML='查無資料，請重新派送影片。';
								/*if(buttonName==='取得結果') node執行結果.innerHTML='查無資料，請重新派送影片。';
								if(buttonName==='派送影片'){
									$.post(null,{action:'uploadCF',素材識別碼:value素材識別碼,副檔名:副檔名,素材原始檔名:value素材原始檔名,ajaxTarget:"CAMPS"},function(json){
										if(!json.success)
											node執行結果.innerHTML=HtmlSanitizer.SanitizeHtml(json.error);
										else{
											node影片派送時間.innerHTML=HtmlSanitizer.SanitizeHtml(json.CAMPS影片派送時間);
											node執行結果.innerHTML='上傳影片成功，請等待CAMPS處理影片。';
											//上傳成功後，若mediaId原先不為空表示重覆派送，則進行提醒重送已送出託播單。
											if(value影片媒體編號!=='') showReordersAlert(getReordersJson);
										}
									},'json');
								}*/
							}
							else{
								//node影片媒體編號.innerHTML=HtmlSanitizer.SanitizeHtml(json.mediaId);
								node影片媒體編號.innerHTML=json.mediaId;
								/*if(buttonName==='派送影片') node執行結果.innerHTML='已派送，請檢視各欄位結果，不可重覆派送！';
								//取得結果成功後，若mediaId原先不為空且新的mediaId不同於原先的值，則表示重覆派送需進行提醒重送已送出託播單。
								if(buttonName==='取得結果'&&value影片媒體編號!==''&&value影片媒體編號.search(json.mediaId)==-1) showReordersAlert(getReordersJson);*/
								showReordersAlert(getReordersJson);
							}
						}
					},'json');
				}
				else if(buttonName == "派送影片"){
					/*$.post(null,{action:'uploadCF',素材識別碼:value素材識別碼,副檔名:副檔名,素材原始檔名:value素材原始檔名,ajaxTarget:"CAMPS"},function(json){
						if(!json.success)
							//node執行結果.innerHTML=HtmlSanitizer.SanitizeHtml(json.error);
							node執行結果.innerHTML=json.error;
						else{
							//node影片派送時間.innerHTML=HtmlSanitizer.SanitizeHtml(json.CAMPS影片派送時間);
							node影片派送時間.innerHTML=json.CAMPS影片派送時間;
							node執行結果.innerHTML='上傳影片成功，請等待CAMPS處理影片。';
							//上傳成功後，若mediaId原先不為空表示重覆派送，則進行提醒重送已送出託播單。
							showReordersAlert(getReordersJson);
						}
					},'json');*/
					//上方是CAMPS派送，目前已不使用
					//下方式端點barker派送程式碼
					$.post("../api/barker/sendMaterialToPumping.php",{素材識別碼:value素材識別碼},function(json){
						node執行結果.innerHTML=json.message;
						showReordersAlert(getReordersJson);
					},'json');
				}
				else if(buttonName == "上傳到端點barker"){
					$.post("../api/barker/sendMaterialToPumping.php",{素材識別碼:value素材識別碼},function(json){
						node執行結果.innerHTML=json.message;
						showReordersAlert(getReordersJson);
					},'json');
				}
				$(event.target).unmask();
				$(node影片派送時間).unmask();
				$(node影片媒體編號).unmask();
			},'json');
		}
	}