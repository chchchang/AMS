<?php 
/**2022-10-18 檢查是否有送出後未取得結果的排程/素材
 * 
*/
$htmlpath ="/var/www/html/AMS/";//pro
//$htmlpath ="../../";//dev
require_once $htmlpath.'Config.php';
require_once $htmlpath.'api/barker/module/ImportResultTracking.php';


$hadler = new ImportResultTracking();
$result = $hadler->handle();
exit($result);


?>
 