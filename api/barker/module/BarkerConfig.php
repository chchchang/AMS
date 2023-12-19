<?php
class BarkerConfig{
    public static $sftpInfo=
    [
        'host'=>"172.17.233.27",
        'username'=>"pumping",
        "password"=>"Pump@2022"
    ];//pro
    /*public static $sftpInfo=
    [
        'host'=>"localhost",
        'username'=>"ams",
        "password"=>"Ams@chttl853"
    ];*///dev
    public static $materialFolder = "material";
    public static $playlistFolder = "playlist_data";

    public static $remoteMaterialFolder = "VIDEO";//pro
    public static $remotePlaylistFolder = "JSON";//pro
    public static $remoteMaterialFolderBreachAd = "MAM";//pro
    public static $remotePlaylistFolderBreachAd = "XLSX";//pro
    //public static $remoteMaterialFolder = "barkertest/VIDEO";
    //public static $remotePlaylistFolder = "barkertest/JSON";

    public static $doneMaterialFolder = "material_done";
    public static $donePlaylistFolder = "playlist_data_done";

}

class BreachAdConfig extends BarkerConfig{
    public static $sftpInfo=
    [
        'host'=>"172.17.233.25",
        'username'=>"Pumping",
        "password"=>"1qaz@WSX3edc"
    ];

    public static $remoteMaterialFolder = "MAM";
    public static $remotePlaylistFolder = "XLSX";

}
?>