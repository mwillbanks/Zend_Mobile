<?php
require_once 'Zend/Mobile/Push/Apns.php';

$apns = new Zend_Mobile_Push_Apns();
$apns->setCertificate(dirname(__FILE__) . '/mycert.pem'); // REPLACE WITH YOUR CERT

$message = new Zend_Mobile_Push_Message_Apns();
$message->setToken('some-type-of-token'); // REPLACE WITH A APNS TOKEN
$message->setId(time());
$message->setAlert('This is a test message!');
$apns->send($message);
