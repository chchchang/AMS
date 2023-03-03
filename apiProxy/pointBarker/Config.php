<?php
	//namespace AMS\apiProxy\pointBarker;
	class Config
	{
		public static $BarkerApi = [
			"getPlayListImportResults" => "http://172.17.233.28:8080/api/pump/getPlayListImportResults",
			"getVideoFileImportResults"  => "http://172.17.233.28:8080/api/pump/getVideoFileImportResults",
			//"getPlayRecord" => "http://172.17.233.28:8080/api/pump/getPlayRecord",
			//"getPlaySchedule"  => "http://172.17.233.28:8080/api/pump/getPlaySchedule",
			//"getChannelList"  => "http://172.17.233.28:8080/api/pump/getChannelList",
			//"updateChannel" => "http://172.17.233.28:8080/api/pump/updateChannel",
			//"addChannel"  => "http://172.17.233.28:8080/api/pump/addChannel",
			//"deleteChannel"  => "http://172.17.233.28:8080/api/pump/deleteChannel",
			"getPlayRecord"  => "http://localhost/testing/pointBarkerData/rec.txt",
			"getPlaySchedule"  => "http://localhost/testing/pointBarkerData/sch.txt",
			"getChannelList"  => "http://localhost/testing/pointBarkerData/ch.txt",
			"updateChannel" => "http://localhost/testing/pointBarkerData/fackreponse.txt",
			"addChannel"  => "http://localhost/testing/pointBarkerData/fackreponse.txt",
			"deleteChannel"  => "http://localhost/testing/pointBarkerData/fackreponse.txt",
		];
	}
?>