<html>
<head>
	<meta charset="UTF-8">
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script type="text/javascript" src="BlockDataGrid.js"></script>
	<title>datagirdtesting</title> 
<script language="JavaScript"> 

function new_grid(){
	var data = [
				{"head":"a","cells":["a", "b", "c"]}, 
				{"head":"b","cells":["1", "2", "3","ddsa"]}, 
				{"head":"c","cells":["4", "5", "6"]}
			]; 
			
	new BlockDataGrid('test',data);
};

</script>
<style type="text/css">

.BlockDataGrid div{
	-moz-border-radius:28px;
	-webkit-border-radius:28px;
	border-radius:28px;
	display:inline-block;
	color:#ffffff;
	padding:8px 15px;
	text-decoration:none;
	text-shadow:0px 1px 0px #2f6627;
	behavior: url(../PIE.htc);
}


</style>
</head> 
<body> 
	

	<input type ="button" onclick = "new_grid()" value ="getgrid">
	<br>
	<br>
	<div id="test"></div>
	<br>


</body> 
</html>