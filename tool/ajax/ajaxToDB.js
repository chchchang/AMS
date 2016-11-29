function ajax_to_db(by_post,ajaxtodbPath,actionWhenReady){
	var http_request=false;
	if(window.XMLHttpRequest){
		http_request=new XMLHttpRequest();
		if(http_request.overrideMimeType){
			http_request.overrideMimeType('text/xml');
		}
	}else if(window.ActiveXObject){
		try{ //6.0+
			http_request=new ActiveXObject("Msxml2.XMLHTTP");
		}catch(e){
			try{ //5.5+
			http_request=new ActiveXObject("Microsoft.XMLHTTP");
			}catch (e){}
		}
	}
	if(!http_request){
		alert('Giving up :( Cannot create a XMLHTTP instance');
		return false;
	}
	//var by_post='query='+"SELECT OWNER_ID, OWNER_NAME,CHANNEL_PROVIDER_NAME,UNDERWRITER_NAME FROM ADOWNER WHERE OWNER_NAME LIKE '%ad%' ORDER BY OWNER_ID LIMIT 0,10";
	http_request.onreadystatechange=function(){
		if(http_request.readyState==4){
			if(http_request.status==200){
				//actionWhenReady($.parseJSON(http_request.responseText));
				actionWhenReady(http_request.responseText);
			}
		}
	};
	http_request.open('POST',ajaxtodbPath,true);
	http_request.setRequestHeader("Content-Type","application/x-www-form-urlencoded;");  //**重要一定要加上
	http_request.send(by_post);
};
