<?php
namespace Zf1auth;

use Facebook;

/**
 * Class FacebookSessionPersistentDataHandler
 *
 * @package Facebook
 */
class Zf1auth_PersistentDataHandler implements Facebook\PersistentData\PersistentDataInterface
{
    /**
     * @var string Prefix to use for session variables.
     */
    protected $sessionPrefix = 'Zf1auth_facebook';
    protected $_ses = null;

    /**
     * Init the session handler.
     *
     * @param boolean $enableSessionCheck
     *
     * @throws FacebookSDKException
     */
    public function __construct($enableSessionCheck = true)
    {
       $this->_initSes();
    }
    
    protected function _initSes()
    {
      $this->_ses = new \Zend_Session_Namespace($this->sessionPrefix, false);
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
      return $this->_ses->{$key};
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
      $log = Zend_Registry::get('log');
      
      $log->log("Setting key '$key' to value '$value'", \Zend_Log::DEBUG);
      
      $this->_ses->{$key} = $value;
    }
}
