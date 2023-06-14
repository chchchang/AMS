<?php
require_once dirname(__FILE__)."/../../../tool/MyDB.php";
require_once dirname(__FILE__)."/../module/PlayListRepository.php";
$my=new MyDB(true);
$playListRepository = new PlayListRepository($my);
$sql = "select playlist_id from barker_playlist";
$palylists = $my->getResultArray($sql);
if(is_array($palylists)){
    foreach($palylists as $p){
        $playListRepository->fixPlaylistSeconds($p["playlist_id"]);
    }
}
?>