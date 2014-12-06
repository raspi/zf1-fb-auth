<?php

class Zf1auth_Adapter_Facebook implements Zend_Auth_Adapter_Interface
{

  /**
   *
   * @var string
   */
  protected $_facebookId = null;

  /**
   *
   * @var string
   */
  protected $_facebookSecret = null;

  /**
   *
   * @var string
   */
  protected $_loginUri = null;
  
  /**
   *
   * @var array
   */
  protected $_facebookScopes = array();

  /**
   *
   * @var array
   */
  protected $_missingPermissions = array();

  /**
   * Throw exceptions?
   * @var bool
   */
  protected $_throwExceptions = true;

  /**
   * 
   * @param string $id
   */
  public function setFacebookId($id)
  {
    $this->_facebookId = $id;
  }

  /**
   * 
   * @param string $secret
   */
  public function setFacebookSecret($secret)
  {
    $this->_facebookSecret = $secret;
  }

  /**
   * 
   * @param array $scopes
   */
  public function setFacebookScopes(array $scopes = array())
  {
    $this->_facebookScopes = $scopes;
  }

  /**
   * 
   * @param string $uri
   */
  public function setApplicationEntryUri($uri)
  {
    $this->_loginUri = $uri;
  }

  /**
   * @return string
   */
  public function getFacebookLoginUrl()
  {
    $helper = $this->_getFaceBookHelper();
    $loginurl = $helper->getLoginUrl($this->_facebookScopes);
    return $loginurl;
  }

  public function getFacebookLogoutUrl(\Facebook\FacebookSession $session, $url)
  {
    $helper = $this->_getFaceBookHelper();
    return $helper->getLogoutUrl($session, $url);
  }

  /**
   * 
   * @return \Facebook\FacebookRedirectLoginHelper
   */
  protected function _getFaceBookHelper()
  {
    $this->_initFacebookSDK();
    return new \Facebook\FacebookRedirectLoginHelper($this->_loginUri);
  }

  protected function _initFacebookSDK()
  {
    \Facebook\FacebookSession::setDefaultApplication($this->_facebookId, $this->_facebookSecret);
  }

  public function getMissingPermissions()
  {
    return $this->_missingPermissions;
  }

  /**
   * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
   * @return Zend_Auth_Result
   */
  public function authenticate()
  {
    $helper = $this->_getFaceBookHelper();

    try
    {
      $fbses = $helper->getSessionFromRedirect();
    } catch (Facebook\FacebookAuthorizationException $e)
    {

      if ($this->_throwExceptions)
      {
        throw $e;
      }

      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array('Facebook error: ' . $e->getMessage()));
    } catch (\Facebook\FacebookSDKException $e)
    {
      if ($this->_throwExceptions)
      {
        throw $e;
      }

      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array('Facebook error: ' . $e->getMessage()));
    }


    if (null === $fbses)
    {
      // Redirect was invalid
      // User possibly clicked cancel 

      $errs = array();

      $err = 'Invalid redirect from facebook to this page.';
      $errs[] = $err;

      $front = Zend_Controller_Front::getInstance();
      $req = $front->getRequest();

      foreach ($req->getQuery() as $param => $val)
      {
        $errs[] = $param . ': ' . $val;
      }

      if ($this->_throwExceptions)
      {
        throw new Zend_Auth_Adapter_Facebook_Redirect_Exception(join(PHP_EOL, $errs));
      }

      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, $errs);
    }

    if (!$fbses instanceof \Facebook\FacebookSession)
    {
      $err = "Internal error. Facebook session couldn't be created by Facebook SDK library.";

      if ($this->_throwExceptions)
      {
        throw new Zend_Auth_Adapter_Exception($err);
      }

      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array($err));
    }

    if (!$fbses->validate())
    {
      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array('Invalid Facebook session'));
    }

    $request = new \Facebook\FacebookRequest($fbses, 'GET', '/me');
    $response = $request->execute();
    $MeGraphObject = $response->getGraphObject();

    $fbid = $MeGraphObject->getProperty('id');

    $request = new \Facebook\FacebookRequest($fbses, 'GET', '/' . $fbid . '/permissions');
    $response = $request->execute();
    $PermissionsGraphObject = $response->getGraphObject();

    $gotPermissions = array();

    foreach ($PermissionsGraphObject->getPropertyNames() as $propertyName)
    {
      $prop = $PermissionsGraphObject->getProperty($propertyName);
      $status = $prop->getProperty('status');

      if (strtolower($status) === 'granted')
      {
        $gotPermissions[] = $prop->getProperty('permission');
      }
    }

    foreach ($this->_facebookScopes as $scope)
    {
      if (!in_array($scope, $gotPermissions))
      {
        $this->_missingPermissions[] = $scope;
      }
    }

    if (0 !== count($this->_missingPermissions))
    {
      // User didn't give all required permissions to application
      // Try to remove application from user's facebook applications
      $request = new \Facebook\FacebookRequest($fbses, 'DELETE', '/' . $fbid . '/permissions');

      $response = $request->execute();
      $revoked_result = (bool) $response->getGraphObject()->asArray();

      if (true !== $revoked_result)
      {
        $err = "Couldn't revoke Facebook application permissions. You have to remove application from your Facebook's 'Settings Application' page and then try login again.";

        if ($this->_throwExceptions)
        {
          throw new Zend_Auth_Adapter_Facebook_Permissions_Revoke_Exception($err);
        }

        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array($err));
      }

      $err = "Facebook error: User didn't grant following permissions to application: " . join(', ', $this->_missingPermissions);

      if ($this->_throwExceptions)
      {
        throw new Zend_Auth_Adapter_Facebook_Permissions_Exception($err);
      }

      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array($err));
    }

    // Construct identity
    $o = new stdClass();

    foreach ($MeGraphObject->getPropertyNames() as $propertyName)
    {
      $o->{$propertyName} = $MeGraphObject->getProperty($propertyName);
    }

    $o->_facebookSession = $fbses;

    return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $o, array());
  }

}
