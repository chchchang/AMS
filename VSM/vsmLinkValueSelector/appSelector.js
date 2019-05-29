var appSelector = function (callback) {
  this.callback = callback;
    
  this.openSelectDialog = function(callback){
	//插入連結內容設定視窗需要的DOM
	$('<div id="dialog-linkValue" title=""><fieldset id = "fieldset-1"></fieldset></div>').appendTo('body');
	$('<label>APP名稱:</label><br><input type="text" name="app_name" id="app_name" class="text ui-widget-content ui-corner-all"><br>').appendTo('#fieldset-1');
	$('<label>APP分類:</label><br><input type="text" name="app_para" id="app_para" class="text ui-widget-content ui-corner-all">').appendTo('#fieldset-1');
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
	//確認按鈕
	$("#linkValue_submit").click(function(){
		var selectedData = [];
		selectedData["appname"]=$("#app_name").val();
		selectedData["apppara"]=$("#app_para").val();
		callback(selectedData);
		$("#dialog-linkValue").dialog( "close" );
		$("#dialog-linkValue").dialog( "destroy" );
	});
  }
  
  this.openSelectDialog(callback)
  
  
};