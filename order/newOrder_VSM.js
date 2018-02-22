	//取得並設訂連動託播單資料
	/*var newOrderVSM = function(){
		
	};*/
	function setConnectOrderVSM(url,valArr,dateObj,hours,forceSet){
		//是否強制設定已選擇託播單的參數，若為true，則就算不在候選名單內，也會設定已選token
		if(typeof(forceSet) == 'undefined')
			forceSet = false;
		//若有多組時間:取各組可連動廣告交集
		var byPost = {'連動廣告':true,'Dates':dateObj,'Hours':hours};
		$.post(url,byPost
			,function(json){
				$select = $('#連動廣告');
				$select.data('tokenize').clear();
				$select.empty();
				var CSMS = json;
				var ids  = valArr;
				for(var i in CSMS){
						var opt = $(document.createElement("option"));
						opt.text(CSMS[i]['託播單識別碼']+':'+CSMS[i]['託播單名稱']+'('+CSMS[i]['版位名稱']+')')
						.val(CSMS[i]['託播單識別碼'])
						.appendTo($select);
						//設置預設廣告
						if($.inArray( CSMS[i]['託播單識別碼'].toString(), ids)!=-1){
							$select.data('tokenize').tokenAdd(CSMS[i]['託播單識別碼'],CSMS[i]['託播單識別碼']+':'+CSMS[i]['託播單名稱']+'('+CSMS[i]['版位名稱']+')');
						}
				}
				//強制設定預設廣告
				if(forceSet){
					for(var key in valArr){
						if(valArr[key].length>0)
						$.ajax({
						type: 'post',
						async: false,
						url:url,
						data:{method:'取得連動託播單名稱',ids:valArr[key]},
						success:
							function(data){
								//console.log(data);
								$select = $('#連動廣告');
								for(var i in data){
									if($('#連動廣告 option[value="'+data[i]['託播單CSMS群組識別碼']+'"]').length == 0){
										var opt = $(document.createElement("option"));
										opt.text(data[i]['託播單識別碼'],data[i]['託播單識別碼']+':'+data[i]['託播單名稱']+'('+data[i]['版位名稱']+')')
										.val(data[i]['託播單識別碼'])
										.appendTo($select);
										$select.data('tokenize').tokenAdd(data[i]['託播單識別碼'],data[i]['託播單識別碼']+':'+data[i]['託播單名稱']+'('+data[i]['版位名稱']+')');
									}
								}
							},
						dataType : 'json'
						});
					}
				}
			}
			,'json'
		);
	}