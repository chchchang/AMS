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
    public static $playlistFolder = "data";

    public static $remoteMaterialFolder = "VIDEO";//pro
    public static $remotePlaylistFolder = "JSON";//pro
    //public static $remoteMaterialFolder = "barkertest/VIDEO";//pro
    //public static $remotePlaylistFolder = "barkertest/JSON";//pro

    public static $doneMaterialFolder = "material_done";
    public static $donePlaylistFolder = "data_done";

}
?>