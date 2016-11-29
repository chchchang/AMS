<!-- 權限驗證失敗時顯示的網頁 -->
<html>
<head>
<style type="text/css">
form {
	font:100% verdana,arial,sans-serif;
	margin: auto;
	padding: 0;
	min-width: 500px;
	max-width: 600px;
	width: 560px; 
	position:absolute; height:280px;
	top:0; bottom:0; left:0; right:0;
}

form fieldset {
	border-color: #000;
	border-width: 1px;
	border-style: solid;
	padding: 10px; /* padding in fieldset support spotty in IE */
	margin: 0;
}

form fieldset legend {
	font-size:2em;
	margin: 5px 0 0 ;
	text-align: center;
}

form label {
	display: block; /* block float the labels to left column, set a width */
	float: center; 
	padding: 0; 
	margin: 0 0 0 0; /* set top margin same as form input - textarea etc. elements */
	text-align: center;
}

</style>
</head>


<body>
<form>
  <fieldset>
    <legend>權限錯誤</legend>
	<label>您無權觀看此頁面</label>
	<label>如有問題請聯絡系統管理員</label>
  </fieldset>
 </form>
</body>

</html>