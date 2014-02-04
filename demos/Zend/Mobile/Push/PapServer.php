<?php
require_once 'Zend/Mobile/Push/Pap.php';
require_once 'Zend/Mobile/Push/Message/Pap.php';

$pap = new \Zend_Mobile_Push_Pap('xxxxxxxxx', 'xxxxxxxxx');
$pap->setEnvironment('prod');
if ($env == 'prod')
	$this->pap->setContentProviderId('000');

}

	$message = new \Zend_Mobile_Push_Message_Pap('Zend Mobile Push Example', null, '+5 seconds');
	
	$message->addTo('push_all');
	

	$response = $this->pap->send($message);
	if ($response->isError()) {
		echo 'Zend Mobile Push::error: '
		. $response->getErrorString();

	} else
		echo 'Zend Mobile Push::response_description: '
		. $response->getResponseDesc();

}


