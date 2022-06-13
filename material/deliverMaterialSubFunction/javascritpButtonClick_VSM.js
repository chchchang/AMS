var buttonOnClick=function(event){	
	function getColByName(colName){
		return $('#DG td:nth-child('+($('#DG th:contains("'+colName+'")')[0].cellIndex+1)+')')[event.target.parentElement.parentElement.rowIndex-1];
	}
	function getColValueByName(colName){
		return getColByName(colName).textContent;
	}
	
	var local=getColValueByName('素材識別碼')+getColValueByName('素材原始檔名').substr(getColValueByName('素材原始檔名').lastIndexOf('.'))
	$(event.target).mask('處理中...');
	
	var remote='_____AMS_'+local;
	
	var mid = getColValueByName('素材識別碼');
	
	var 狀態node=getColByName('VSM狀態');
	$(狀態node).mask('...');
	
	if(event.target.textContent.substr(0,2)==='取得'){
		//判斷資料夾下是否存在此圖檔
		$.post(null,{action:'isAllFile',remote:remote,'素材識別碼':mid,ajaxTarget:"VSM"},function(json){
			var buff='';
			for(var i in json.result)
				buff+='<img src="../tool/pic/'+((json.result[i])?'Circle_Green.png':'Circle_Red.png')+'">';
			$(狀態node).unmask();
			$(event.target).unmask();
			狀態node.innerHTML=buff;
		},'json');
	}
	else{
		//上傳圖檔到資料夾下
		$.post(null,{action:'putAll',local:local,remote:remote,'素材識別碼':mid,ajaxTarget:"VSM"},function(json){
			if(json.error!==''){
				$(狀態node).unmask();
				$(event.target).unmask();
				狀態node.innerHTML=HtmlSanitizer.SanitizeHtml(json.error);
			}else{
				var buff='';
				for(var i in json.result)
					buff+='<img src="../tool/pic/'+((json.result[i])?'Circle_Green.png':'Circle_Red.png')+'">';
				$(狀態node).unmask();
				$(event.target).unmask();
				狀態node.innerHTML=buff;
			}
		},'json');
	}
}