<?php

require_once __DIR__ . '/../MyMailer.php';

$mailer = new MyMailer();

if($mailer->sendMail("chia_chi_chang@cht.com.tw","AMS warning","AMS has some warning"))
	echo "send!";
else
	echo "fail!";


