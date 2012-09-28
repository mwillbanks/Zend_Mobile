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


/** Zend_Mobile_Push_Message_Abstract **/
require_once 'Zend/Mobile/Push/Message/Abstract.php';


class Zend_Mobile_Push_Message_Pap extends Zend_Mobile_Push_Message_Abstract {
	protected $_to = array();
	protected $_id;
	protected $_message;
	protected $_delivery;
	
	public function __construct($message, $id = null, $delivery = null) {
		$this->_message = $message;
		$this->_id = ($id) ? $id : microtime();
		if ($delivery) {
			$this->_delivery = (is_int($delivery)) ? $delivery
			: strtotime($delivery);
		} else {
			$this->_delivery = strtotime("+5 minutes");
		}
	}
	
	public function addTo($email) {
		$this->_to[] = $email;
	}
	
	
	
	public function getPushMessage($appId) {
		$addresses = '';
		foreach ($this->_to as $addr) {
			$addresses .= '<address address-value="' . $addr . '"/>';
		}
	
		$xml = '--mPsbVQo0a68eIL3OAxnm' . "\r\n"
		. 'Content-Type: application/xml; charset=UTF-8' . "\r\n\r\n"
		. '<?xml version="1.0"?>
		<!DOCTYPE pap PUBLIC "-//WAPFORUM//DTD PAP 2.1//EN" "http://www.openmobilealliance.org/tech/DTD/pap_2.1.dtd">
		<pap>
		<push-message push-id="' . $this->_id
		. '" deliver-before-timestamp="'
		. gmdate('Y-m-d\TH:i:s\Z', $this->_delivery)
		. '" source-reference="' . $appId . '">' . $addresses
		. '<quality-of-service delivery-method="unconfirmed"/>
		</push-message>
		</pap>' . "\r\n" . '--mPsbVQo0a68eIL3OAxnm' . "\r\n"
		. 'Content-Type: text/plain' . "\r\n" . 'Push-Message-ID: '
		. $this->_id . "\r\n\r\n" . urlencode($this->_message) . "\r\n"
		. '--mPsbVQo0a68eIL3OAxnm--' . "\n\r";
	
		return $xml;
	}
}
