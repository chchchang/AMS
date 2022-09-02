<?php

/**
 * This example shows sending a message using a local sendmail binary.
 */

//Import the PHPMailer class into the global namespace
require_once("PHPMailer.php");
require_once("Exception.php");
require_once("SMTP.php");

use PHPMailer\PHPMailer\PHPMailer;

//require '../vendor/autoload.php';

//Create a new PHPMailer instance
$mail = new PHPMailer();
//set mail server
$mail->Host="172.17.254.11";
$mail->IsSMTP();
$mail->CharSet = "utf-8";
$mail->Port=25;
//Set PHPMailer to use the sendmail transport
//$mail->isSendmail();
//Set who the message is to be sent from
$mail->setFrom('chia_chi_chang@cht.com.tw');
//Set an alternative reply-to address
//$mail->addReplyTo('replyto@example.com', 'First Last');
//Set who the message is to be sent to
$mail->addAddress('chia_chi_chang@cht.com.tw');
//Set the subject line
$mail->Subject = 'PHPMailer sendmail test';
//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
//$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
//內文
$mail->Body="send from AMS dont replay";
//Replace the plain text body with one created manually
$mail->AltBody = 'This is a plain-text message body';
//Attach an image file
//$mail->addAttachment('images/phpmailer_mini.png');

//send the message, check for errors
if (!$mail->send()) {
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message sent!';
}