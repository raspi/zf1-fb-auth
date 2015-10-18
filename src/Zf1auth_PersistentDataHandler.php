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

  const SESSION_NAMESPACE = 'Zf1auth_Facebook';

  protected $_ses = null;

  public function __construct($enableSessionCheck = true)
  {
    if (!\Zend_Session::isStarted())
    {
      throw new Exception("Session not started yet");
    }

    $this->_initSes();

    if (!$this->_ses instanceof Zend_Session_Abstract)
    {
      throw new Exception("Invalid session instance created");
    }
  }

  protected function _initSes()
  {
    $ses = new \Zend_Session_Namespace(self::SESSION_NAMESPACE, true);
    $ses->setExpirationHops(5, null, true);
    $ses->setExpirationSeconds(60 * 60 * 24);
    $this->_ses = $ses;
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    if (isset($this->_ses->{$key}))
    {
      return $this->_ses->{$key};
    }
    
    return null;
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    $this->_ses->{$key} = $value;
  }

}
