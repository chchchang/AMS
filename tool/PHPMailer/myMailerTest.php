<?php

require_once __DIR__ . '/../MyMailer.php';

$mailer = new MyMailer();

if($mailer->sendMail("AMS warning test","testing.....AMS has some warning"))
	echo "send!";
else
	echo "fail!";


