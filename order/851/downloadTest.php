<?php 

?> 
<!DOCTYPE html>
<html>
<head>
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
</head>
<body>
<a href = 'zipDownload.php'> click me</a>

<form method="post" action="zipDownload.php" id ='downloadZipForm'>
   <input type="submit" value="submit"/>
</form>
</body>

<script>
var df = ['7.xlsx','9.xlsx','8.xlsx'];
$('#downloadZipForm').empty();
for(var id in df){
//$('#downloadZipForm').append($('<input type="hidden" name="files[]" value="'+df[id]+'"/>'));
}
$('#downloadZipForm').append($('<input type="submit" value="submit"/>'));
</script>
</html>