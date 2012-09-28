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
class Zend_Mobile_Push_Response_Pap {
		protected $_id;
		protected $_replyTime;
		protected $_responseCode;
		protected $_responseDesc;
		protected $_isError = false;
		protected $_errorCode;
		protected $_errorStr;
	
		public function __construct($response) {
			$p = xml_parser_create();
			xml_parse_into_struct($p, $response, $vals);
			$err = xml_get_error_code($p);
			if ($err > 0) {
				$this->_isError = true;
				$this->_errorCode = $err;
				$this->_errorStr = xml_error_string($err);
			} else {
				$this->_replyTime = $vals[1]['attributes']['REPLY-TIME'];
				$this->_responseCode = $vals[2]['attributes']['CODE'];
				$this->_responseDesc = $vals[2]['attributes']['DESC'];
				$this->_id = $vals[1]['attributes']['PUSH-ID'];
			}
			xml_parser_free($p);
		}
	
		public function getId() {
			return $this->_id;
		}
	
		public function getReplyTime() {
			return $this->_replyTime;
		}
	
		public function getResponseCode() {
			return $this->_responseCode;
		}
	
		public function getResponseDesc() {
			return $this->_responseDesc;
		}
	
		public function isError() {
			return $this->_isError;
		}
	
		public function getErrorCode() {
			return $this->_errorCode;
		}
	
		public function getErrorString() {
			return $this->_errorStr;
		}
	
}
