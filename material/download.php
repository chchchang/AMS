<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
if(isset($_GET['path']))
{
//Read the filename
$filename = $_GET['path'];
//Check the file exists or not
if(file_exists($filename)) {

//Define header information
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header('Content-Disposition: attachment; filename="'.basename($filename).'"');
header('Content-Length: ' . filesize($filename));
header('Pragma: public');
ob_clean();
//Clear system output buffer
flush();

//Read the size of the file
//readfile($filename);
if (readfile($filename))
{
  unlink($filename);
}
/*ignore_user_abort(true);
if (connection_aborted()) {
    unlink($filename);
}
register_shutdown_function('unlink', $filename);
//Terminate from the script
die();*/
}
else{
echo "File does not exist.";
}
}
else
echo "Filename is not defined."
?>