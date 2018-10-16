var VodBundleSelector = function (callback) {
  this.callback = callback;
    
  this.openSelectVodDialog = function(callback){
	//找出本檔案的include路徑，並推出vodBundleSelectPage.php的路徑
	var jssrc = "";
	sc = document.getElementsByTagName("script");
	for(idx = 0; idx < sc.length; idx++)
	{
		s = sc.item(idx);
		if(s.src && s.src.match(/VodBundleSelector\.js$/)){ 
			jssrc = s.src; 
		}
	}
	jssrc = jssrc.replace("VodBundleSelector.js", "vodBundleSelectPage.php"); 
	
	$("<div id = 'vodBundleSelectorSubFrame'><iframe width = '100%' height='600' src='"+jssrc+"'></iframe> </div>").appendTo("body")
	$("#vodBundleSelectorSubFrame").dialog({
		title:"選擇VOD product",
		width:"70%",
		height:"700",
		close: function( event, ui ) {$("#vodBundleSelectorSubFrame").dialog( "destroy" ).remove();}
	}).on( "selectVod", function(event,selectedVod) {
		callback(selectedVod);
		$("#vodBundleSelectorSubFrame").dialog( "close" );
	});
  }
  
  this.openSelectVodDialog(callback)
  
  
};