<?php
class PHPExtendFunction{
	public function __construct(){
	}
	//檢查String是否以特殊字串開頭
	public static function stringStartsWith($haystack, $needle) {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}
	//檢查String是否以特殊字串結尾
	public static function stringEndsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
	
	//檢查兩個檔案是否相同
	public static function isFilesSame($file_a, $file_b)
	{
		if (filesize($file_a) == filesize($file_b))
		{
			if(sha1_file($file_a) == sha1_file($file_b))
			return true;
		}
		return false;
	}
	
	//複製array
	public static function arrayCopy($array) {
        $result = array();
        foreach( $array as $key => $val ) {
            if( is_array( $val ) ) {
                $result[$key] = self::arrayCopy( $val );
            } elseif ( is_object( $val ) ) {
                $result[$key] = clone $val;
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
	}
	
	//安全亂數
	public static function myrand ($min=null,$max=null) {
		$min = isset($min)?$min:0;
		$max = isset($max)?$max:getrandmax();
		$range = $max-$min;
		if ($range==0) return $min;
		$range=$range+1;
		$log  = log($range,2);
		$bytes = (int) ($log / 8)+1;
		$bits = (int) $log +1;
		$filter = (int)(1<<$bits)-1;
		do {
		   $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes,$s)));
		   $rnd = $rnd & $filter;
		} while ($rnd>=$range);
		return $min+$rnd;
	}

	//連接API取的結果
	public static function connec_to_Api($url,$method,$postvars){
		$postvars = (isset($postvars)) ? $postvars : null;
		// 建立CURL連線
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		$apiResult = curl_exec($ch);
		if(curl_errno($ch))
		{
			curl_close($ch);
			return array('success'=>false,'errorno'=>curl_errno($ch));
		}
		curl_close($ch);
		return array('success'=>true,'data'=>$apiResult);
	}
	
	//將NULL轉為字串
	public static function n2s($str){
		if($str == null)
			return 'NULL';
		else
			return $str;
	}
	
	public static function getDatesByPeriod($start,$end,$returnFormat){
		$period = new DatePeriod(
			 new DateTime($start),
			 new DateInterval('P1D'),
			 new DateTime($end)
		);
		$return = array();
		foreach ($period as $key => $value) {
			$return[]=$value->format($returnFormat);
		}
		$tail = new DateTime($start);
		$return[]=$tail->format($returnFormat);
		return $return;
	}

	public static function cleanStr($string){
		$arra = str_split($string);
		$arra2 = array();
		foreach($arra as $key){
			array_push($arra2,ord($key));
		}
		$result = "";
		foreach($arra2 as $key){
			$result=$result.chr($key);
		}
		return $result;
	}
}
?>