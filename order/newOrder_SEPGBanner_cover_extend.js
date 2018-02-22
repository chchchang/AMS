//頻道short EPG bannerc蓋版廣告設定
//配合newOrder.php與editOrderBatch.php使用

//設定典籍後開啟類click事件
$('.linkValue').click(function(){
	var order = $(this).attr('order');
	var linkType = $('#點擊後開啟類型'+order).val();
	//over_A 連結類型與 cover_B 連結類型javascript設定(OMP)
	if(linkType=='COVER_A'||linkType=='COVER_B'){
		var newDiv = $(document.createElement('div')); 
		newDiv.html('<table>'
		+'<tr><td>展開後素材名稱</td><td><input id = "COVER_material" type = text></input></td></tr>'
		+'<tr><td>展開後連結類型</td><td><select id="COVER_linkType">'
		+'<option value="NONE">NONE</option>'
		+'<option value="OVA_CATEGORY">OVA_CATEGORY</option>'
		+'<option value="OVA_VOD_CONTENT">OVA_VOD_CONTENT</option>'
		+'<option value="OVA_CHANNEL">OVA_CHANNEL</option>'
		+'</select><td></tr>'
		+'<tr><td>展開後連結位置</td><td><input id = "COVER_linkValue" type = text></input></td></tr>'
		+'</table>');
		newDiv.dialog({
			close: function( event, ui ) {$(this).dialog('destroy').remove()},
			buttons: {
				"確認": function() {
					$.post("../material/ajax_findAdCodeAndName.php",{'素材名稱':$('#COVER_material').val()},
						function(json){
							if(!json['success']){
								alert('查無素材名稱')
							}
							else{
								var adCode=json['adCode'];
								var picName=json['name'];
								var service=$("#COVER_linkType").val();

								var value = adCode+'#'+picName+'#'+service;
								if(service!='NONE')
									value+="#"+$("#COVER_linkValue").val();
								$('#點擊後開啟位址'+order).val(value).trigger("change");
								newDiv.dialog( "close" );	
							}
						}
						,'json'
					);			
			  }
			}
		});
		
		$('#COVER_material').autocomplete({
			source :function( request, response) {
						$.post( "../material/autoComplete_forMaterialSearchBox.php",{term: request.term, method:'素材查詢'},
							function( data ) {
							response(JSON.parse(data));
							$(".ui-autocomplete").css({
								"max-height": "100px",
								"overflow-y": "auto",
								"overflow-x": "hidden"
							});
						})
					}
		});
	}
	else if (linkType=='OVA_VOD_CONTENT'){
		var newDiv = $(document.createElement('div')); 
		newDiv.html('<table width="100%">'
		+'<tr><td>影片目錄</td><td><input id = "OVA_VOD_CONTENT_category" type = text></input></td></tr>'
		+'<tr><td>影片片名</td><td><input id = "OVA_VOD_CONTENT_flim" type = text></input></td></tr>'
		+'</table>');
		newDiv.dialog({
			close: function( event, ui ) {$(this).dialog('destroy').remove()},
			width: 400,
			height: 400,
			modal: true,
			title: "設定VOD資訊",
			buttons: {
				"確認": function() {
					var value = $("#OVA_VOD_CONTENT_category").val()+'#'+$("#OVA_VOD_CONTENT_flim").val();
					$('#點擊後開啟位址'+order).val(value).trigger("change");
					newDiv.dialog( "close" );						
			  }
			}
		});
		
		$('#OVA_VOD_CONTENT_flim').autocomplete({
			source :function( request, response) {
						$.post( "autoComplete_for_OVA_CONTENT.php",{term: request.term, method:'get_ova_content_title'},
							function( data ) {
							response(JSON.parse(data));
							$(".ui-autocomplete").css({
								"max-height": "200px",
								"overflow-y": "auto",
								"overflow-x": "hidden"
							});
						})
					}
		});
	}
	
	//VSM版本設定
	else if(linkType=='coverImageIdV'||linkType=='coverImageIdH'){
		var newDiv = $(document.createElement('div')); 
		newDiv.html('<table>'
		+'<tr><td>展開後素材名稱</td><td><input id = "COVER_material" type = text></input></td></tr>'
		+'</table>');
		newDiv.dialog({
			close: function( event, ui ) {$(this).dialog('destroy').remove()},
			buttons: {
				"確認": function() {
					$.post("../material/ajax_findAdCodeAndName.php",{'素材名稱':$('#COVER_material').val()},
						function(json){
							if(!json['success']){
								alert('查無素材名稱')
							}
							else{
								var adCode=json['adCode'];
								var picName=json['name'];
								var service=$("#COVER_linkType").val();

								var value = "ad/"+picName;
								$('#點擊後開啟位址'+order).val(value).trigger("change");
								newDiv.dialog( "close" );	
							}
						}
						,'json'
					);			
			  }
			}
		});
		
		$('#COVER_material').autocomplete({
			source :function( request, response) {
						$.post( "../material/autoComplete_forMaterialSearchBox.php",{term: request.term, method:'素材查詢'},
							function( data ) {
							response(JSON.parse(data));
							$(".ui-autocomplete").css({
								"max-height": "100px",
								"overflow-y": "auto",
								"overflow-x": "hidden"
							});
						})
					}
		});
	}
})



