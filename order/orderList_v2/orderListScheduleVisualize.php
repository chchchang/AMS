<?php
include('../tool/auth/authAJAX.php');	
?>
<!DOCTYPE html>
<html>
<head>
	<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<script src="../../tool/jquery-ui1.2/jquery-ui.js"></script>
	<script src="../../tool/jquery-ui1.2/jquery-ui-sliderAccess.js" type="text/javascript"></script>
	<script type="text/javascript" src="../../tool/autoCompleteComboBox.js"></script>
	<script src="../../tool/jquery-plugin/tableHeadFixer.js"></script>
	<script src="../../tool/jquery.loadmask.js"></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
</head>
<style type="text/css">
th {overflow:hidden;white-space:nowrap;}
#pschedule td,#pschedule th{border-style: solid; border-width: 1px; border-color:#aaaaaa}
#pschedule { border-width: 0px; }
</style>
<body>
<iframe id="txtArea1" style="display:none"></iframe>
<button id="btnExport" onclick="fnExcelReport();"> EXPORT </button>
<?php include('_positionScheduleUI.php');?>
<?php include('_positionScheduleScript.php');?>
<script type="text/javascript">
function fnExcelReport()
{
    var tab_text="<table border='2px'><tr bgcolor='#87AFC6'>";
    var textRange; var j=0;
    tab = document.getElementById('pschedule'); // id of table

    for(j = 0 ; j < tab.rows.length ; j++) 
    {     
        tab_text=tab_text+tab.rows[j].innerHTML+"</tr>";
        //tab_text=tab_text+"</tr>";
    }

    tab_text=tab_text+"</table>";
    tab_text= tab_text.replace(/<A[^>]*>|<\/A>/g, "");//remove if u want links in your table
    tab_text= tab_text.replace(/<img[^>]*>/gi,""); // remove if u want images in your table
    tab_text= tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); // reomves input params

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE "); 

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))      // If Internet Explorer
    {
        txtArea1.document.open("txt/html","replace");
        txtArea1.document.write(tab_text);
        txtArea1.document.close();
        txtArea1.focus(); 
        sa=txtArea1.document.execCommand("SaveAs",true,"Say Thanks to Sumit.xls");
    }  
    else                 //other browser not tested on IE 11
        sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));  

    return (sa);
}
</script>
</body>
</html>