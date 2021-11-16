<?php
date_default_timezone_set("Asia/Taipei");
require_once('../../Config_VSM_Meta.php');
//header("Content-Type: application/json; charset=utf-8");
?>
<?php

/*$exec = new sepgSpMdParserMulti("localFile/VSM_SepgSpMD_202012103.dat");
print_r($exec->getDataAndAction());*/

class sepgSpMdParserMulti{
	var $mdFileName = "";
	var $logfile = "";
	var $url_n = "";
	var $url_s = "";
	function __construct($filename){
		$this->mdFileName = $filename;
		$this->url_n = Config_VSM_Meta::VSM_API_ROOT.'epgBannerAuth/ajax_ad_target_list.php';
		$this->url_s = Config_VSM_Meta::VSM_API_ROOT_S.'epgBannerAuth/ajax_ad_target_list.php';
		$this->logfile = fopen("log/sepgSpMdParserMulti.log.".date("Ymd"), "a+");
		//*******test 
		/*$this->url_n = 'http://172.18.44.99/epgBannerAuth/ajax_ad_target_list.php';
		$this->url_s = 'http://172.18.44.99/epgBannerAuth/ajax_ad_target_list.php';*/
	}
	
	function __destruct() {
	$this->dolog("__destruct");
       fclose($this->logfile);
	}
	
	function execute(){
		$fileData = $this->getDataAndAction();
	}
	
	
	
	function array_map_recursive($callback, $array) {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $array[$key] = $this->array_map_recursive($callback, $array[$key]);
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
	第一行:記錄投放廣告託播用
	1.單號 （字元8碼）： 999999  (必填欄位) 
	2.身分內途（字元8碼）：SepgSpMD : 頻道白名單 (必填欄位)
	3.廣告名稱 (字元60碼) : (必填欄位)  方便託播人員識別名單  EX. 白花油   (不得含有,號)  
	4.託播版位（字元60碼）細項用分號：目前頻道號碼為主 000~999    (暫放空白) 
	5.託播走期（字元10碼） (yyyy-mm-dd hh24:mm:ss)  開始  ：(必填欄位)
	6.託播走期（字元10碼） (yyyy-mm-dd hh24:mm:ss)  結束  ：(必填欄位)
	7.託播時段（字元60碼） (00-23)  細項用分號 :  EX  12;13;18;19;20; 00-23   (暫放空白) 
	9.投放平台( IAP/VSM)  :(必填欄位) 
	10. 更新日期 datetime：yyyy-mm-dd hh24:mm:ss (必填欄位)

	第二行後： 指定MD 列表
	1.單號 （字元8碼）： 999999  (必填欄位)
	2. MD（字元16碼）：(必填欄位)
	3. MD 投放區域：  (N/S ) (必填欄位)
	4. 說明欄位（字元200碼） :  彈性應用 ex. 家有小孩;電影族;高收入  (可暫放空白)
	*/

	function getDataAndAction(){
		$this->dolog("action start");
		$this->dolog("getfile:".$this->mdFileName);
		$handle = fopen($this->mdFileName, "r");
		$batchNum = 500;
		//逐行閱讀整理資料		
		if ($handle) {

			$postMDArray_N = array();
			$postMDArray_S = array();
			//第一行:託播基本資料
			$line = fgets($handle);
			$line = preg_replace("/([^\\\])\'|^\'/",'$1', $line);
			$temp = explode(',',$line);
			$orderdata = array(
			"單號"=>$temp[0],
			"身分內途"=>$temp[1],
			"廣告名稱"=>$temp[2],
			"託播版位"=>$temp[3],
			"託播走期開始"=>$temp[4],
			"託播走期結束"=>$temp[5],
			"託播時段"=>$temp[6],
			"投放平台"=>$temp[7],
			"更新日期"=>$temp[8]
			);
			$ad_target_list_id = $orderdata["單號"];
			/*if($orderdata['身分']!="SepgSpMD"){

			}else if($orderdata['身分']=="SepgSpMD"){
			}*/
			//輸入白名單基本資料
			if(!$this->insertTargetList($ad_target_list_id,$orderdata['託播走期開始'],$orderdata['託播走期結束'])){
				return array("success"=>false,"message"=>"輸入白名單資料失敗");
			}
			//清理白名單MD資料
			if(!$this->deleteTargetListMD($ad_target_list_id)){
				return array("success"=>false,"message"=>"清空MD資料失敗");
			}
			//第二行以後: MD資料
			$this->dolog("geting MDs......");
			while (($line = fgets($handle)) !== false) {
				//取的資料並解析
				$line = preg_replace("/([^\\\])\'|^\'/",'$1', $line);
				$temp = explode(',',$line);
				$MDdata = array(
				"單號"=>$temp[0],
				"MD"=>$temp[1],
				"投放區域"=>$temp[2],
				"說明欄位"=>$temp[3],
				);
			
				if($MDdata['投放區域']=="N"){
					array_push($postMDArray_N,$MDdata['MD']);
					if(count($postMDArray_N)>=$batchNum){
						if(!$this->insertSpMd($ad_target_list_id,$postMDArray_N,"N"))
							return array("success"=>false,"message"=>"新增北區白名單資訊失敗");
						$postMDArray_N = array();
					}
				}
				else if($MDdata['投放區域']=="S"||$MDdata['投放區域']=="C"){
					array_push($postMDArray_S,$MDdata['MD']);
					if(count($postMDArray_S)>=$batchNum){
						if(!$this->insertSpMd($ad_target_list_id,$postMDArray_S,"S"))
							return array("success"=>false,"message"=>"新增南區白名單資訊失敗");
						$postMDArray_S = array();
					}
				}
				
			}fclose($handle);
			
			//若要送出的array中還有資料，將剩餘資料POST送出
			if(count($postMDArray_N)>0){
				if(!$this->insertSpMd($ad_target_list_id,$postMDArray_N,"N"))
					return array("success"=>false,"message"=>"新增北區白名單資訊失敗");
			}
			if(count($postMDArray_S)>0){
				if(!$this->insertSpMd($ad_target_list_id,$postMDArray_S,"S"))
					return array("success"=>false,"message"=>"新增南區白名單資訊失敗");
			}
		} else {
			return array("success"=>false,"message"=>"介接檔案不存在");
		}
		$this->dolog("done");
		return array("success"=>true,"message"=>"success");
	}

	/**
    * 新增白名單MD
	* @param string $ad_target_list_id #名單ID
	* @param array $md_data #MD
	* @param string $area	#區域N/S
    * @return boolean
    */
	function insertSpMd($ad_target_list_id,$md_data,$area){
		//更新資料庫
		$postdata = array(
			"action"=>"insertTargetListMd",
			"data"=>array(
				"ad_target_list_id"=>$ad_target_list_id,
				"md_data"=>$md_data
			)
		);
		if($area=="N"){$url = $this->url_n;}
		else if($area=="S"){$url = $this->url_s;}
		$postResult = json_decode($this->postToVsm($url,$postdata),true);
		if(!$postResult["success"]){
			return false;
		}
		return true;
	}

	/**
    * 在兩區增加白名單
	* @param string $ad_target_list_id #名單ID
	* @param string $start_datetime	#開始日期
	* @param string $end_datetime	#結束日期
    * @return boolean
    */
	function insertTargetList($ad_target_list_id,$start_datetime,$end_datetime){
		$this->dolog("insertTargetList:".$ad_target_list_id);
		//更新資料庫
		$postdata = array(
			"action"=>"insertTargetList",
			"data"=>array(
				"ad_target_list_id"=>$ad_target_list_id,
				"start_datetime"=>$start_datetime,
				"end_datetime"=>$end_datetime
			)
		);

		$postResult = json_decode($this->postToVsm($this->url_n,$postdata),true);
		if(!$postResult["success"]){
			$this->dolog("insertTargetList error: area:N Message:".implode(",",$postResult));
			return false;
		}
		$postResult = json_decode($this->postToVsm($this->url_s,$postdata),true);
		if(!$postResult["success"]){
			$this->dolog("insertTargetList error: area:S Message:".implode(",",$postResult));
			return false;
		}
		return true;
	}

	/**
    * 清空兩區MD設定
	* @param string $ad_target_list_id #名單ID
    * @return boolean
    */
	function deleteTargetListMD($ad_target_list_id){
		$this->dolog("deleteTargetListMD:".$ad_target_list_id);
		//更新資料庫
		$postdata = array(
			"action"=>"deleteTargetListMD",
			"data"=>array(
				"ad_target_list_id"=>$ad_target_list_id,
			)
		);
		$postResult = json_decode($this->postToVsm($this->url_n,$postdata),true);
		if(!$postResult["success"]){
			return false;
		}
		$postResult = json_decode($this->postToVsm($this->url_s,$postdata),true);
		if(!$postResult["success"]){
			return false;
		}
		return true;
	}
	
	/**
    * curl post連線至單一平台
	* @param string $url #名單ID
	* @param array $postdata
    * @return apiResult
    */
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
		//if(!$apiResult["success"]){
			if(is_array($apiResult))
				$this->dolog("postToVsm result:".implode(",",$apiResult));
			else
				$this->dolog("postToVsm result:".$apiResult);
		//}
		return $apiResult;
	}

	/**
    * curl post連線至單一平台
	* @param string $url #名單ID
	* @param array $postdata
    * @return apiResult
    */
	function dolog($message){
		$write_data = date("H:i:s")." ".$message."\n"; 
		//echo($write_data);
		
		fwrite($this->logfile,$write_data);
	}

}
?>