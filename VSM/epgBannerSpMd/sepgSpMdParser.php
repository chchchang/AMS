<?php
date_default_timezone_set("Asia/Taipei");
require_once('../../tool/auth/authAJAX.php');
require_once('../../Config_VSM_Meta.php');
//header("Content-Type: application/json; charset=utf-8");
?>
<?php

class sepgSpMdParser{
	var $mdFileName = "";

	function __construct($filename){
		$this->mdFileName = $filename;
	}
	
	function execute(){
		$fileData = $this->getDataAndAction();
	}
	
	
	
	function array_map_recursive($callback, $array) {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $array[$key] = array_map_recursive($callback, $array[$key]);
            }
            else {
                $array[$key] = call_user_func($callback, $array[$key]);
            }
        }
        return $array;
    }

	//取得MD資料
	/*
	以逗號,分隔大項，分號;分隔細項
	欄位說明:
	1.	單號 （字元8碼）：
	2.	託播版位（字元8碼）：目前頻道號碼為主 000~999
	3.	 MD（字元16碼）：隱碼規則再討論
	4.	身分內途（字元8碼）：SepgSpMD : 頻道白名單 
	5.	更新日期 datetime：yyyy-mm-dd hh24:mm:ss
	6.	說明欄位（字元200碼） :  彈性應用 ex. 家有小孩;電影族;高收入

	*/

	function getDataAndAction(){
		$handle = fopen($this->mdFileName, "r");
		$batchNum = 500;
		//逐行閱讀整理資料		
		if ($handle) {
			//檔案存在，先清空先前的資料庫設定
			$url = Config_VSM_Meta::VSM_API_ROOT.'epgBannerAuth/ajax_epg_banner_playing_device.php';
			$postdata = array("action"=>"clearSepgSpMd"); 
			$postResult = json_decode($this->postToVsm($url,$postdata),true);
			if(!$postResult["success"]){
				return array("success"=>false,"message"=>"清除舊有白名單資訊失敗");
			}
			$postMDArray = array();
			while (($line = fgets($handle)) !== false) {
				//取的資料並解析
				$line = preg_replace("/([^\\\])\'|^\'/",'$1', $line);
				$temp = explode(',',$line);
				$data = array(
				"單號"=>$temp[0],
				"託播版位"=>$temp[1],
				"MD"=>$temp[2],
				"身分"=>$temp[3],
				"日期"=>$temp[4],
				"說明"=>$temp[5],
				);
				if($data['身分']!="SepgSpMD")
					continue;
				else if($data['身分']=="SepgSpMD"){
					array_push($postMDArray,$data['MD']);
					if(count($postMDArray)>=$batchNum){
						if(!$this->insertSpMd($postMDArray))
							return array("success"=>false,"message"=>"新增白名單資訊失敗");
						$postMDArray = array();
					}
				}
			}fclose($handle);
			
			if(count($postMDArray)>0){
				if(!$this->insertSpMd($postMDArray))
					return array("success"=>false,"message"=>"新增白名單資訊失敗");
			}
		} else {
			return array("success"=>false,"message"=>"檔案不存在");
		}
		return array("success"=>true,"message"=>"success");
	}
	
	function insertSpMd($MD){
		//更新資料庫
		$postdata = array(
			"action"=>"insertSpMd",
			"data"=>array(
				"MD"=>$MD
			)
		);
		$url = Config_VSM_Meta::VSM_API_ROOT.'epgBannerAuth/ajax_epg_banner_playing_device.php';
		$postResult = json_decode($this->postToVsm($url,$postdata),true);
		if(!$postResult["success"]){
			return false;
		}
		return true;
	}
	
	function postToVsm($url,$postdata){
		$postvars = http_build_query($postdata);
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		$apiResult = curl_exec($ch);
		return $apiResult;
	}

}
?>