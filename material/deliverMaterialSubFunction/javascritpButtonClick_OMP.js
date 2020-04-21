var buttonOnClick=function(event){	
	function getColByName(colName){
		return $('#DG td:nth-child('+($('#DG th:contains("'+colName+'")')[0].cellIndex+1)+')')[event.target.parentElement.parentElement.rowIndex-1];
	}
	function getColValueByName(colName){
		return getColByName(colName).textContent;
	}

	var area;
	if(event.target.textContent.indexOf('北區')!==-1) area='OMP_N';
	else if(event.target.textContent.indexOf('中區')!==-1) area='OMP_C';
	else area='OMP_S';

	var local=getColValueByName('素材識別碼')+getColValueByName('素材原始檔名').substr(getColValueByName('素材原始檔名').lastIndexOf('.'))
	$(event.target).mask('處理中...');

	var remote='_____AMS_'+local;

	var mid = getColValueByName('素材識別碼');

	var 狀態node=getColByName(event.target.textContent.substr(2,2)+'狀態');
	$(狀態node).mask('...');

	if(event.target.textContent.substr(0,2)==='取得'){
		//先判斷專區資料夾下是否存在此圖檔
		$.post(null,{action:'isAllFile',area:area,type:'專區',remote:remote,'素材識別碼':mid,ajaxTarget:"OMP"},function(json){
			//再判斷EPG資料夾下是否存在此圖檔
			$.post(null,{action:'isAllFile',area:area,type:'EPG',remote:remote,'素材識別碼':mid,ajaxTarget:"OMP"},function(json2){
				var buff='';
				for(var i in json.result)
					buff+='<img src="../tool/pic/'+((json.result[i]&&json2.result[i])?'Circle_Green.png':'Circle_Red.png')+'"  title="'+i+'">';
				$(狀態node).unmask();
				$(event.target).unmask();
				狀態node.innerHTML=buff;
			},'json');
		},'json');
	}
	else{
		//先上傳圖檔到專區資料夾下
		$.post(null,{action:'putAll',area:area,type:'專區',local:local,remote:remote,'素材識別碼':mid,ajaxTarget:"OMP"},function(json){
			if(json.error!==''){
				$(狀態node).unmask();
				$(event.target).unmask();
				狀態node.innerHTML=json.error;
			}else{
				//再上傳圖檔到EPG資料夾下
				$.post(null,{action:'putAll',area:area,type:'EPG',local:local,remote:remote,'素材識別碼':mid,ajaxTarget:"OMP"},function(json2){
					if(json2.error!==''){
						$(狀態node).unmask();
						$(event.target).unmask();
						狀態node.innerHTML=json.error;
					}
					else{
						var buff='';
						for(var i in json.result)
							buff+='<img src="../tool/pic/'+((json.result[i]&&json2.result[i])?'Circle_Green.png':'Circle_Red.png')+'" title="'+i+'">';
						$(狀態node).unmask();
						$(event.target).unmask();
						狀態node.innerHTML=buff;
					}
				},'json');
			}
		},'json');
	}
}