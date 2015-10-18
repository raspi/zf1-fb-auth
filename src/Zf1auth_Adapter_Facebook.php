<?php
/**
 * Facebook Zend Framework 1 authentication adapter
 */

namespace Zf1auth;

use Facebook;

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
    $client = new Facebook\HttpClients\FacebookCurl();
    $client->init();
    $client->setopt(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    
    $curl = new Facebook\HttpClients\FacebookCurlHttpClient($client);
    
    //$pdh = new Zf1auth_PersistentDataHandler();
    $pdh = new FacebookSessionPersistentDataHandler(true);
    
    $options = array(
      'app_id' => $this->_facebookId,
      'app_secret' => $this->_facebookSecret,
      'default_graph_version' => 'v2.5',
      'persistent_data_handler' => $pdh,
      'http_client_handler' => $curl,
    );

    return new \Facebook\Facebook($options);
  }

  public function getMissingPermissions()
  {
    return $this->_missingPermissions;
  }

  /**
   * @throws \Zend_Auth_Adapter_Exception If authentication cannot be performed
   * @return \Zend_Auth_Result
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
      $msg = "";
      $msg .= 'PDH Instance: ' . get_class($helper->getPersistentDataHandler()) . PHP_EOL;
      $msg .= 'UDH Instance: ' . get_class($helper->getUrlDetectionHandler()) . PHP_EOL;
      $msg .= 'ErrorCode: ' . $helper->getErrorCode() . PHP_EOL;
      $msg .= 'Error: ' . $helper->getError() . PHP_EOL;
      $msg .= 'Error Description: ' . $helper->getErrorDescription() . PHP_EOL;
      $msg .= 'Error Reason: ' . $helper->getErrorReason() . PHP_EOL;
      $msg .= PHP_EOL;

      throw new Zf1auth_Adapter_Facebook_Exception($msg, 0, $e);
    } catch (Exception $e)
    {
      $msg = "";
      $msg .= 'Not catched!' . PHP_EOL;
      $msg .= PHP_EOL;

      throw new Zf1auth_Adapter_Facebook_Exception($msg, 0, $e);
    }

    return new Zf1auth_Auth_Result(\Zend_Auth_Result::FAILURE, null, array());
  }

}
