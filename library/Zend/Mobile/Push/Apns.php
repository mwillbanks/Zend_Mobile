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

require_once 'Zend/Mobile/Push/Abstract.php';
require_once 'Zend/Mobile/Push/Message/Apns.php';

/**
 * APNS Push
 *
 * @category   Zend
 * @package    Zend_Mobile
 * @subpackage Zend_Mobile_Push
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
class Zend_Mobile_Push_Apns extends Zend_Mobile_Push_Abstract
{

    /**
     * @const int apple server uri constants
     */
    const SERVER_SANDBOX_URI = 0;
    const SERVER_PRODUCTION_URI = 1;
    const SERVER_FEEDBACK_SANDBOX_URI = 2;
    const SERVER_FEEDBACK_PRODUCTION_URI = 3;

    /**
     * Apple Server URI's
     * @var array
     */
    protected $_serverUriList = array(
        'ssl://gateway.sandbox.push.apple.com:2195',
        'ssl://gateway.push.apple.com:2195',
        'ssl://feedback.push.apple.com:2196',
        'ssl://feedback.sandbox.push.apple.com:2196'
    );

    /**
     * Current Environment
     * @var int
     */
    protected $_currentEnv;

    /**
     * Socket
     * @var resource
     */
    protected $_socket;

    /**
     * Certificate
     * @var string
     */
    protected $_certificate;

    /**
     * Get Certficiate
     *
     * @return string
     */
    public function getCertificate()
    {
        return $this->_certificate;
    }

    /**
     * Set Certificate
     *
     * @param  string $cert
     * @return Zend_Mobile_Push_Apns
     * @throws Zend_Mobile_Push_Exception
     */
    public function setCertificate($cert)
    {
        if (!is_string($cert)) {
            throw new Zend_Mobile_Push_Exception('$cert must be a string');
        }
        if (!file_exists($cert)) {
            throw new Zend_Mobile_Push_Exception('$cert must be a valid path to the certificate');
        }
        $this->_certificate = $cert;
        return $this;
    }

    /**
     * Connect to the Push Server
     *
     * @param string $env
     * @return Zend_Mobile_Push_Abstract
     */
    public function connect($env = self::SERVER_PRODUCTION_URI)
    {
        if ($this->_isConnected) {
            if ($this->_currentEnv == self::SERVER_PRODUCTION_URI) {
                return $this;
            }
            $this->close();
        }

        if (!isset($this->_serverUriList[$env])) {
            throw new Zend_Mobile_Push_Exception('$env is not a valid environment');
        }

        if (!$this->_certificate) {
            throw new Zend_Mobile_Push_Exception('A certificate must be set prior to calling ::connect');
        }

        $this->_socket = stream_socket_client($this->_serverUriList[$env],
            &$errno,
            &$errstr,
            ini_get('default_socket_timeout'),
            STREAM_CLIENT_CONNECT,
            stream_context_create(array(
                'ssl' => array(
                    'local_cert' => $this->_certificate
                ),
            ))
        );

        if (!$this->_socket) {
            require_once 'Zend/Mobile/Push/Exception/ServerUnavailable.php';
            throw new Zend_Mobile_Push_Exception_ServerUnavailable(sprintf('Unable to connect: %s: %d (%s)',
                $env,
                $errno,
                $errstr
            ));
        }

        stream_set_blocking($this->_socket, 0);
        stream_set_write_buffer($this->_socket, 0);

        $this->_currentEnv = $env;
        $this->_isConnected = true;
        return $this;
    }

    /**
     * Feedback
     *
     * @return array array of arrays with indicies token and time
     */
    public function feedback()
    {
        if (!$this->_isConnected ||
            !in_array($this->_currentEnv,
                array(self::SERVER_FEEDBACK_SANDBOX_URI, self::SERVER_FEEDBACK_PRODUCTION_URI))) {
            $this->connect(self::SERVER_FEEDBACK_PRODUCTION_URI);
        }

        $tokens = array();
        while (!feof($this->_socket)) {
            $token = fread($this->_socket, 38);
            if (strlen($token) < 38) {
                continue;
            }
            $token = unpack('Ntime/ntokenLength/H*token', $token);
            $tokens[] = array('token' => $token['token'], 'time' => $token['time']);
        }
        return $tokens;
    }

    /**
     * Send Message
     *
     * @param Zend_Mobile_Push_Message_Apns $message
     * @return boolean
     * @throws Zend_Mobile_Push_Exception
     */
    public function send(Zend_Mobile_Push_Message_Abstract $message)
    {
        if (!$message->validate()) {
            throw new Zend_Mobile_Push_Exception('Zend_Mobile_Push_Message_Apns parameter is not valid');
        }

        if (!$this->_isConnected || !in_array($this->_currentEnv, array(
            self::SERVER_SANDBOX_URI,
            self::SERVER_PRODUCTION_URI))) {
            $this->connect(self::SERVER_PRODUCTION_URI);
        }

        $payload = array('aps' => array());

        $alert = $message->getAlert();
        foreach ($alert as $k => $v) {
            if ($v == null) {
                unset($alert[$k]);
            }
        }
        if (!empty($alert)) {
            $payload['aps']['alert'] = $alert;
        }
        $payload['aps']['badge'] = $message->getBadge();
        $payload['aps']['sound'] = $message->getSound();

        foreach($message->getCustomData() as $k => $v) {
            $payload[$k] = $v;
        }
        $payload = json_encode($payload);

        $expire = $message->getExpire();
        if ($expire > 0) {
            $expire += time();
        }
        $id = $message->getId();
        if (empty($id)) {
            $id = time();
        }

        $payload = pack('CNNnH*', 1, $id, $expire, 32, $message->getToken())
            . pack('n', strlen($payload))
            . $payload;
        $ret = fwrite($this->_socket, $payload);
        if ($ret === false) {
            require_once 'Zend/Mobile/Push/Exception/ServerUnavailable.php';
            throw new Zend_Mobile_Push_Exception_ServerUnavailable('Unable to send message');
        }
        // check for errors from apple
        $err = fread($this->_socket, 1024);
        if (strlen($err) > 0) {
            $err = unpack('Ccmd/Cerrno/Nid', $err);
            switch ($err['errno']) {
                case 0:
                    return true;
                    break;
                case 1:
                    throw new Zend_Mobile_Push_Exception('Apns reported a processing error.');
                    break;
                case 2:
                    require_once 'Zend/Mobile/Push/Exception/InvalidToken.php';
                    throw new Zend_Mobile_Push_Exception_InvalidToken('A token must be set to send a message to the user');
                    break;
                case 3:
                    require_once 'Zend/Mobile/Push/Exception/InvalidTopic.php';
                    throw new Zend_Mobile_Push_Exception_InvalidTopic('Missing topic');
                    break;
                case 4:
                    require_once 'Zend/Mobile/Push/Exception/InvalidPayload';
                    throw new Zend_Mobile_Push_Exception_InvalidPayload('Missing payload');
                    break;
                case 5:
                    require_once 'Zend/Mobile/Push/Exception/InvalidToken.php';
                    throw new Zend_Mobile_Push_Exception_InvalidToken('Invalid token size');
                    break;
                case 6:
                    require_once 'Zend/Mobile/Push/Exception/InvalidTopic.php';
                    throw new Zend_Mobile_Push_Exception_InvalidTopic('Invalid topic size');
                    break;
                case 7:
                    require_once 'Zend/Mobile/Push/Exception/MessageTooBig.php';
                    throw new Zend_Mobile_Push_Exception_MessageTooBig('Invalid payload size');
                    break;
                case 8:
                    require_once 'Zend/Mobile/Push/Exception/InvalidToken.php';
                    throw new Zend_Mobile_Push_Exception_InvalidToken('Invalid token');
                    break;
                default:
                    break;
            }
        }
        return true;
    }

    public function close()
    {
        if ($this->_isConnected && is_resource($this->_socket)) {
            fclose($this->_socket);
        }
        $this->_isConnected = false;
    }
}
