<?php
/*
 * 寄送信件共用模組
 * Date: 2022/09/02
 * Author: chia_chi_chang (chia_chi_chang@cht.com.tw)
 */

use PHPMailer\PHPMailer\PHPMailer;


/* The main PHPMailer class. */
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

class MyMailerFackLogger {
	function  info($s) { }
	function  warning($s) { }
	function  error($s) { }
}

class MyMailer
{
	private $logger;
	private $errorMsg;
	private $mailer;
	private $mailConfig;
	private $apiErrorCode;
	private $apiErrorMessages;
	private $defaultAddress;
	public function __construct($logger=null)
	{
		$this->mailConfig=array(
			"stmpServer" => '172.17.254.11',
			"stmpPort" => 25,
			"dafaultSender" => 'chia_chi_chang@cht.com.tw',
			"footer" => '///*****此郵件為系統自動發送，請勿直接回覆*****///',
		);
		$this->defaultAddress=array(
			"yichenchiu@cht.com.tw",//邱譯瑱	內容處 OP
			"timweng@cht.com.tw",//翁尚瑋	內容處 OP
			"yuhsuan@cht.com.tw",//藍于琁	內容處 OP
			"chia_chi_chang@cht.com.tw"//張家騏 平台處 開發者
		);
		if($logger!=null)
			$this->logger = $logger;
		else{
			$this->logger = new MyMailerFackLogger();
		}

		// 寫入開始執行LOG
		$this->logger->info('[Start MailModule process]');
		
		$this->errorMsg = null;
		//mailer 設定
		$this->mailer = new PHPMailer();
		$this->mailer->Host = $this->mailConfig["stmpServer"];
		$this->logger->info('[stmp host:'.$this->mailer->Host.']');
		$this->mailer->From = $this->mailConfig["dafaultSender"];
		$this->mailer->IsSMTP();
		$this->mailer->CharSet = "utf-8";
		$this->mailer->Port=$this->mailConfig["stmpPort"];;
		//設定API用訊息代碼
		$this->apiErrorCode="000";
		$this->apiErrorMessages=array(
			"000"=>"成功",
			"101"=>"收件者設定錯誤",
			"102"=>"標題空白",
			"103"=>"寄件者設定錯誤",
			"104"=>"副本設定錯誤",
			"105"=>"附加檔案失敗",
			"106"=>"密件副本設定錯誤",
			"107"=>"回覆設定錯誤",
			"999"=>"其他錯誤",
		);
	}
	/**
	 *寄送信件
	 * @param string $title
	 * @param string $msg
	 *  @param string|array $addresses
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function sendMail($title,$msg,$addresses = []){
		$this->logger->info('[Sitting title]');
		//設定郵件標題
		if($title!=""){
		$this->mailer->Subject = $title;
		}
		else{
			$this->apiErrorCode="102";
			$this->logger->error("empty title");
			return false;
		}
		if($addresses==[]){
			$addresses = $this->defaultAddress;
		}
		//檢查收件者地址
		$this->logger->info('[Checking recipients addresses]');
		if(!is_array($addresses)){
			$addresses = array($addresses);
		}
		foreach($addresses as $address){
			if(!$this->checkIfAddressValid($address)){
				$this->apiErrorCode="101";
				$this->logger->error("inValid recipient address".$address);
				return false;
			}
			if(!$this->mailer->AddAddress($address)){
				$this->apiErrorCode="101";
				$this->errorMsg = "add recipient fail:".$this->mailer->ErrorInfo;
				$this->logger->error($this->errorMsg);
				return false;
			}
			
		}
		
		//郵件內文
		$this->logger->info('[Sitting context]');
		$msg.="\n\n\n".$this->mailConfig["footer"];
		//$msg = str_replace("\n.", "\n..", $msg);
		$msg = str_replace("\\n", "\n", $msg);
		$this->logger->info($msg);
		//$msg = nl2br($msg);
		
		$this->mailer->Body=$msg;

		
		//送出郵件
		$this->logger->info('[Sending mail]');
		if(!@$this->mailer->Send()) {
			$this->apiErrorCode="999";
			$this->errorMsg="sendMail fail:".$this->mailer->ErrorInfo;
			$this->logger->error($this->errorMsg);
			return false;
		} 
		$this->logger->info('[Mail send successfully]');
		return true;
	}
	
	/**
	 * 取得錯誤訊息
	 * @return String
	 */
	public function getErrorMessage(){
		return $this->errorMsg;
	}
	
	/**
	 * API取得錯誤訊息
	 * @return array $return (errorcoed,errormessage)
	 */
	public function getApiErrorMessage(){
		$code = $this->apiErrorCode;
		$message = $this->apiErrorMessages[$code];
		$return = array("errorCode"=>$code,"errorMessage"=>$message);
		return $return;
	}
	/**
	 * 檢查email address是否合法
	 * @param string $email
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function checkIfAddressValid($email){
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->errorMsg="invalid emailaddress:".$email;
			$this->logger->error($this->errorMsg);
			// invalid emailaddress
			return false;
		}
		else{
			return true;
		}
	}

	/**
	 * 附加檔案
	 * @param string $filePath
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function attachFile($filePath){
		$this->logger->info("[Attaching file....]");
		$check = true;
		//檢查路徑是否合法
		if(strpos($filePath, '../')){
			$this->errorMsg="attach file fail: invalid filePath:".$filePath;
			$check = false;
		}


		if(!is_file($filePath)){
			$this->errorMsg="file dose not exists:".$filePath;
			$check = false;
		}

		if(!$this->mailer->addAttachment($filePath)){
			$this->errorMsg="attach file fail:".$this->mailer->ErrorInfo;
			$check = false;
		}

		if(!$check){
			$this->logger->error($this->errorMsg);
			$this->apiErrorCode="105";
			return false;
		}
		else{
			$this->logger->info("[Attach file success]");
			return true;
		}
	}

	/**
	 * 附加檔案
	 * @param string $filePath
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function attachFileString($fileUrl,$fineName){
		$this->logger->info("[Attaching file....]");
		if(!$this->mailer->addStringAttachment(file_get_contents($fileUrl),$fineName)){
			$this->errorMsg="attach file fail:".$this->mailer->ErrorInfo;
			$this->logger->error($this->errorMsg);
			$this->apiErrorCode="105";
			return false;
		}
		return true;
	}

	/**
	 * 附加副本收件者
	 * @param string|array $addresses
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function addCC($addresses){
		$this->logger->info("[Add cc....]");
		//檢查收件者地址
		$this->logger->info('[Checking cc addresses]');
		if(!is_array($addresses)){
			$addresses = array($addresses);
		}
		foreach($addresses as $address){
			if(!$this->checkIfAddressValid($address)){
				$this->apiErrorCode="104";
				$this->errorMsg = "副本郵件地址錯誤";
				$this->logger->error("inValid cc address".$address);
				return false;
			}
			if(!$this->mailer->addCC($address)){
				$this->apiErrorCode="104";
				$this->errorMsg = "add cc fail:".$this->mailer->ErrorInfo;
				$this->logger->error($this->errorMsg);
				return false;
			}
		}
		return true;
	}

	/**
	 * 附加副本收件者
	 * @param string|array $addresses
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function addBCC($addresses){
		$this->logger->info("[Add bcc....]");
		//檢查收件者地址
		$this->logger->info('[Checking bcc addresses]');
		if(!is_array($addresses)){
			$addresses = array($addresses);
		}
		foreach($addresses as $address){
			if(!$this->checkIfAddressValid($address)){
				$this->apiErrorCode="106";
				$this->errorMsg = "密件副本郵件地址錯誤";
				$this->logger->error("inValid bcc address".$address);
				return false;
			}
			if(!$this->mailer->addBCC($address)){
				$this->apiErrorCode="106";
				$this->errorMsg = "add bcc fail".$this->mailer->ErrorInfo;
				$this->logger->error($this->errorMsg);
				return false;
			}
		}
		return true;
	}

	/**
	 * 附加回覆收件者
	 * @param string|array $addresses
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function addReplyTo($addresses){
		$this->logger->info("[Add reply....]");
		//檢查收件者地址
		$this->logger->info('[Checking reply addresses]');
		if(!is_array($addresses)){
			$addresses = array($addresses);
		}
		foreach($addresses as $address){
			if(!$this->checkIfAddressValid($address)){
				$this->apiErrorCode="107";
				$this->errorMsg = "回覆郵件地址錯誤";
				$this->logger->error("inValid reply address".$address);
				return false;
			}
			if(!$this->mailer->addReplyTo($address)){
				$this->apiErrorCode="107";
				$this->errorMsg = "ad reply fail:".$this->mailer->ErrorInfo;
				$this->logger->error($this->errorMsg);
				return false;
			}
		}
		return true;
	}

	/**
	 * 附加回覆收件者
	 * @param string $address
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function setSender($address){
		$this->logger->info("[set sender....]");
		//檢查收件者地址
		$this->logger->info('[Checking sender addresses]');
		
		if(!$this->checkIfAddressValid($address)){
			$this->apiErrorCode="103";
			$this->errorMsg = "寄件者郵件地址錯誤";
			$this->logger->error("inValid sender address".$address);
			return false;
		}
		$this->mailer->From = $address;
		return true;
	}

	/**
     * Clear all recipients.
     */
	public function clearAddress(){
		$this->mailer->clearAddresses();
	}

	/**
     * Clear all cc
     */
	public function clearCCs(){
		$this->mailer->clearCCs();
	}

	/**
     * Clear all Bcc
     */
	public function clearBCCs(){
		$this->mailer->clearBCCs();
	}

	/**
     * Clear all Reply
     */
	public function clearReplyTos(){
		$this->mailer->clearReplyTos();
	}
}