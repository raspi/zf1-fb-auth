<?php

namespace Zf1auth;

/**
 * Facebook authentication adapter for Zend Framework 1
 * 
 * @see https://developers.facebook.com/
 * @see https://github.com/facebook/facebook-php-sdk-v4
 */
class Zf1auth_Adapter_Facebook implements \Zend_Auth_Adapter_Interface
{

  /**
   * Application ID
   * @var string
   */
  protected $_facebookId = null;

  /**
   * Application secret ID
   * @var string
   */
  protected $_facebookSecret = null;

  /**
   * URI for your domain facebook login
   * @var string
   */
  protected $_loginUri = null;

  /**
   * Facebook scopes. For example: public_profile, email, user_birthday, ..
   * @var array
   */
  protected $_facebookScopes = array();

  /**
   * Used to collect what required permissions user didn't give to notify user to give them
   * @var array
   */
  protected $_missingPermissions = array();

  /**
   * Throw exceptions?
   * @var bool
   */
  protected $_throwExceptions = true;

  /**
   * Set to throw exceptions during login fails
   * @param bool $b
   */
  public function setThrowExceptions($b = true)
  {
    $this->_throwExceptions = $b;
  }

  /**
   * Set application ID
   * @param string $id
   */
  public function setFacebookId($id)
  {
    $this->_facebookId = $id;
  }

  /**
   * Set application secret ID
   * @param string $secret
   */
  public function setFacebookSecret($secret)
  {
    $this->_facebookSecret = $secret;
  }

  /**
   * Set scopes aka permissions. See FB SDK documentation for list.
   * 
   * @param array $scopes
   */
  public function setFacebookScopes(array $scopes = array())
  {
    $this->_facebookScopes = $scopes;
  }

  /**
   * Set URI where facebook redirects back to your site
   * 
   * @param string $uri
   */
  public function setApplicationEntryUri($uri)
  {
    $this->_loginUri = $uri;
  }

  /**
   * Get URI which directs to facebook login page
   * 
   * @return string
   */
  public function getFacebookLoginUrl()
  {
    $fb = $this->_initFacebook();
    $helper = $fb->getRedirectLoginHelper();
    return $helper->getLoginUrl($this->_loginUri, $this->_facebookScopes);
  }

  /**
   * Initialize Facebook object
   * 
   * @return \Facebook\Facebook
   */
  protected function _initFacebook()
  {
    $options = array(
      'app_id' => $this->_facebookId,
      'app_secret' => $this->_facebookSecret,
      'default_graph_version' => 'v2.5',
    );

    return new Facebook\Facebook($options);
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
    $fb = $this->_initFacebook();
    $helper = $fb->getRedirectLoginHelper();
    
    $accessToken = null;
    
    try
    {
      $accessToken = $helper->getAccessToken();
    } catch (Facebook\Exceptions\FacebookResponseException $e)
    {
      throw new Zf1auth_Adapter_Facebook_Exception($e->getMessage());
    } catch (Facebook\Exceptions\FacebookSDKException $e)
    {
      throw new Zf1auth_Adapter_Facebook_Exception($e->getMessage());
    }
    
    return new Zf1auth_Auth_Result(\Zend_Auth_Result::FAILURE, null, array());

  }

}
