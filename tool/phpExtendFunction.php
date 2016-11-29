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
	function myrand ($min=null,$max=null) {
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


}
?>