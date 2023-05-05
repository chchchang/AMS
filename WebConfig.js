class WebConfig{
	static VSM_IP = "http://172.17.155.120/";
	static VSM_LINK= {
		general:[
			{value:"NONE",text:"NONE"},
			{value:"internal",text:"internal"},
			{value:"external",text:"external"},
			{value:"app",text:"app"},
			{value:"Vod",text:"Vod"},
			{value:"VODPoster",text:"VODPoster"},
			{value:"Channel",text:"Channel"},
			{value:"appid",text:"appid連結跳轉"},
		],
		單一平台EPG:[
			{value:"coverImageIdV",text:"SEPG直向覆蓋圖片"},
			{value:"coverImageIdH",text:"SEPG橫向覆蓋圖片"}
		],
		單一平台advertising_page:[
			{value:"netflixPage",text:"NETFLIX"}
		],
		單一平台banner:[
			{value:"netflixPage",text:"NETFLIX"}
		],	
	}
	static SET_EXTAPP_LINK_FOR_AMS = WebConfig.VSM_IP+"api/ams/setExtappInterLink.php";
	static GET_EXTAPP_LINK_FOR_AMS = WebConfig.VSM_IP+"api/ams/getExtappInterLink.php";
	static HOURS_COMBINATION={
		全日:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23],
		全空:[],
		上半日:[0,1,2,3,4,5,6,7,8,9,10,11],
		下半日:[12,13,14,15,16,17,18,19,20,21,22,23],
		酒類:[0,1,2,3,4,5,21,22,23],
	}
}