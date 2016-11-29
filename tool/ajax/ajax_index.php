<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;"/>
<title></title>
<!-- 引入 jQuery(非必要,去掉時有些寫法要改為javascript) -->
<script type="text/javascript" src="jquery-1.7.2.min.js"></script>
<!-- 引入AJAX(必要) -->
<script type="text/javascript" src="ajaxToDB.js"></script> 
<script type="text/javascript">
//賦與按鈕事件,點擊執行AJAX
/*$(document).ready(function(){
 $('#test').keyup(function(){  //當輸入時觸發test_ajax()並且傳入輸入框的值當參數
  ajax_to_query("SELECT OWNER_ID, OWNER_NAME,CHANNEL_PROVIDER_NAME,UNDERWRITER_NAME FROM ADOWNER",function(a){
	   alert(a[1]["OWNER_ID"]);
	   //alert(a);
	});  //test_ajax()由ajax_example.js引入
 });
});*/


</script>
</head>
<body>
<div>以ajax實現頁面不刷新,從前端將值傳送到後端處理,並且回傳給前端顯示</div>
<input type="text" id="test" value=""/>
<div id="show_area"></div>
<script>
  ajax_to_db("query=SELECT COUNT(*) FROM 廣告主",function(a){
	  // alert(a[1]["OWNER_ID"]);
	/*try {
		json = jQuery.parseJSON(a);
	} catch (e) {
		alert(e);
		return;
	}*/
	  alert(a);
	});  //test_ajax()由ajax_example.js引入
</script>
</body>
</html>