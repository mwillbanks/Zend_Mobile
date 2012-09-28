<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Mobile
 * @subpackage Zend_Mobile_Push
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** Zend_Mobile_Push_Interface **/
require_once 'Zend/Mobile/Push/Interface.php';

/** Zend_Mobile_Push_Exception **/
require_once 'Zend/Mobile/Push/Exception.php';

class Zend_Mobile_Push_Pap implements Zend_Mobile_Push_Interface {
	protected $_appId;
	protected $_password;
	protected $_contentProviderId;
	protected $_environment;

	public function __construct($appId, $password) {
		$this->_appId = $appId;
		$this->_password = $password;
	}

	public function setAppId($appId) {
		$this->_appId = $appId;
		return $this;
	}

	public function getAppId() {
		return $this->_appId;
	}

	public function setPassword($password) {
		$this->_password = $password;
		return $this;
	}

	public function getPassword() {
		return $this->_password;
	}

	public function setContentProviderId($contentProviderId) {
		$this->_contentProviderId = $contentProviderId;
		return $this;
	}
	public function getContentProviderId() {
		return $this->_contentProviderId;
	}

	public function setEnvironment($environment) {
		$this->_environment = $environment;
		return $this;
	}

	public function getEnvironment() {
		return $this->_environment;
	}

	public function connect() {
		// TODO: Auto-generated method stub

	}
	public function send(Zend_Mobile_Push_Message_Abstract $message) {
		$ch = curl_init();
		if ($this->_environment == 'dev')
			curl_setopt($ch, CURLOPT_URL,
					"https://pushapi.eval.blackberry.com/mss/PD_pushRequest");
		else if ($this->_environment == 'prod')
			curl_setopt($ch, CURLOPT_URL,
					"https://cp" . $this->_contentProviderId
							. ".pushapi.na.blackberry.com/mss/PD_pushRequest");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, "PHP BB Push Server/1.0");
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD,
				$this->_appId . ':' . $this->_password);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $message->getPushMessage($this->_appId));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER,
				array(
						"Content-Type: multipart/related; boundary=mPsbVQo0a68eIL3OAxnm; type=application/xml",
						"Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2",
						"Connection: keep-alive"));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		curl_close($ch);

		return new Zend_Mobile_Push_Response_Pap($response);

	}
	public function close() {
		// TODO: Auto-generated method stub

	}
	public function setOptions(array $options) {
		// TODO: Auto-generated method stub

	}

}
