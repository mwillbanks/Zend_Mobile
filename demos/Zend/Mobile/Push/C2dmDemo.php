<?php
require_once 'Zend/Mobile/Push/C2dm.php';
require_once 'Zend/Gdata/ClientLogin.php';

try {
    $client = Zend_Gdata_ClientLogin::getHttpClient(
        'my@gmail.com', // REPLACE WITH YOUR GOOGLE ACCOUNT
        'myPassword', // REPLACE WITH YOUR PASSWORD
        Zend_Mobile_Push_C2dm::AUTH_SERVICE_NAME,
        null,
        'myAppName' // REPLACE WITH YOUR APP NAME
    );
} catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
    // manual login is required
    echo 'URL of CAPTCHA image: ' . $cre->getCaptchaUrl() . PHP_EOL;
    echo 'Token ID: ' . $cre->getCaptchaToken() . PHP_EOL;
    exit(1);
} catch (Zend_Gdata_App_AuthException $ae) {
    echo 'Problem authenticating: ' . $ae->exception() . PHP_EOL;
    exit(1);
}

$c2dm = new Zend_Mobile_Push_C2dm();
$c2dm->setLoginToken($client->getClientLoginToken());

$message = new Zend_Mobile_Push_Message_C2dm();
$message->setToken('a-device-token'); // REPLACE WITH A DEVICE TOKEN
$message->setId('testCollapseKey');
$message->setData(array(
    'title' => 'Test Notification',
    'msg' => 'This is a test notification.'
));
$c2dm->send($message);
