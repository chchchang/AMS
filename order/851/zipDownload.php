<?php
if(!isset($_POST['files']))
exit;
$fileList = $_POST['files'];
if(sizeof($fileList)==0)
exit;
/*«Ø¥ßÁ{®ÉÀ£ÁYÀÉ*/  
$file = tempnam("tmp", "zip");  
$zip = new ZipArchive();
$res = $zip->open($file, ZipArchive::CREATE|ZipArchive::OVERWRITE);  
if ($res!==true) { exit('À£ÁY¿ù»~');}  
  
foreach($fileList as $fileN){
	$zip->addFile($fileN,$fileN);
}

$zip->close();
  
  
ob_end_clean();  
header('Content-type: application/octet-stream');  
header('Content-Transfer-Encoding: Binary');  
header('Content-disposition: attachment; filename=CSMS.zip');  
  
readfile($file);  
unlink($file);   
exit;  
?>