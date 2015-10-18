<?php
namespace Zf1auth;

/**
 * Class FacebookSessionPersistentDataHandler
 *
 * @package Facebook
 */
class Zf1auth_PersistentDataHandler implements \Facebook\PersistentData\PersistentDataInterface
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
      $this->_ses = new \Zend_Session_Namespace($this->sessionPrefix, true);
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
      $this->_ses->{$key} = $value;
    }
}
