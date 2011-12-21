<?php
require_once 'Zend/Mobile/Push/Apns.php';

$apns = new Zend_Mobile_Push_Apns();
$apns->setCertificate(dirname(__FILE__) . '/mycert.pem'); // REPLACE WITH YOUR CERT

$tokens = $apns->feedback();
foreach ($tokens as $token => $time) {
    echo $token . ': ' . $time . PHP_EOL;
}
