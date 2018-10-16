var VodPosterSelector = function (callback) {
  this.callback = callback;
    
  this.openSelectDialog = function(callback){
	//找出本檔案的include路徑
	var jssrc = "";
	sc = document.getElementsByTagName("script");
	for(idx = 0; idx < sc.length; idx++)
	{
		s = sc.item(idx);
		if(s.src && s.src.match(/VodBundleSelector\.js$/)){ 
			jssrc = s.src.replace("VodBundleSelector.js",""); 
		}
	}
	//插入連結內容設定視窗需要的DOM
	$('<div id="dialog-linkValue" title=""><fieldset id = "fieldset-1"></fieldset></div>').appendTo('body');
	$('<label>internal名稱:</label><br><input type="text" name="linkValue_position" id="linkValue_position" class="text ui-widget-content ui-corner-all"><br>').appendTo('#fieldset-1');
	$('<label>海報牆名稱:</label><br><input type="text" name="linkValue_poster" id="linkValue_poster" class="text ui-widget-content ui-corner-all">').appendTo('#fieldset-1');
	$('<br><button id="linkValue_submit">產生連結內容</button>').appendTo('#dialog-linkValue');
	//產生設定視窗
	$("#dialog-linkValue").dialog({
		autoOpen: true,
		height: 300,
		width: 300,
		title:"輸入連結內容",
		modal: true,
		close: function() {
			$("#dialog-linkValue").remove();
		}
	});
	//設定視窗動作設定
	//專區名稱自動完成(使用linkTyep:internal的自動完成帶出名稱)
	$("#linkValue_position").autocomplete({
		source :function( request, response ) {
			$.post( jssrc+"autoComplete_forVSMLink.php",{term: request.term, "linkType":"internal" ,"linkValue": $('#linkValue_position').val()},
				function( data ) {
				response(JSON.parse(data));
			})
		}
	});
	
	$("#linkValue_poster").autocomplete({
		source :function( request, response ) {
			$.post( jssrc+"autoComplete_forVSMLink.php",{term: request.term, "linkType":"VODPoster" ,"linkValue": $('#linkValue_poster').val()},
				function( data ) {
				response(JSON.parse(data));
			})
		}
	});
	//確認按鈕
	$("#linkValue_submit").click(function(){
		var terms = $("#linkValue_position").val().split(":/");
		var selectedData = [];
		selectedData["internal_id"]=terms[0];
		selectedData["internal_name"]=terms[1];
		selectedData["poster_name"]=$("#linkValue_poster").val();
		
		$("#dialog-linkValue").dialog( "close" );
		callback(selectedData);
		$("#dialog-linkValue").dialog( "destroy" );
	});
  }
  
  this.openSelectDialog(callback)
  
  
};