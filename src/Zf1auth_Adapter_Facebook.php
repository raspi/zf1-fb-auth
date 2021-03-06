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
    $httpClient = new Facebook\HttpClients\FacebookStreamHttpClient();
    $pdh = new Facebook\PersistentData\FacebookSessionPersistentDataHandler(true);

    $options = array(
      'app_id' => $this->_facebookId,
      'app_secret' => $this->_facebookSecret,
      'default_graph_version' => 'v2.5',
      'persistent_data_handler' => $pdh,
      'http_client_handler' => $httpClient,
    );

    return new \Facebook\Facebook($options);
  }

  public function getMissingPermissions()
  {
    return $this->_missingPermissions;
  }

  protected function _getOAuth2ClientDebug(Facebook\Authentication\OAuth2Client $c)
  {
    $req = $c->getLastRequest();

    if (null === $req)
    {
      return "";
    }

    $msg = "OAuth2 Debug info:" . PHP_EOL;
    $msg .= "  URL: " . $req->getUrl() . PHP_EOL;
    $msg .= "  Method: " . $req->getMethod() . PHP_EOL;
    $msg .= "  Endpoint: " . $req->getEndpoint() . PHP_EOL;
    $msg .= PHP_EOL;

    $msg .= "  Params:" . PHP_EOL;
    $msg .= print_r($req->getParams(), true) . PHP_EOL;
    $msg .= PHP_EOL;

    $msg .= "  POST Params:" . PHP_EOL;
    $msg .= print_r($req->getPostParams(), true) . PHP_EOL;
    $msg .= PHP_EOL;

    $msg .= "  Headers:" . PHP_EOL;
    $msg .= print_r($req->getHeaders(), true) . PHP_EOL;
    $msg .= PHP_EOL;

    $msg .= "  Body:" . PHP_EOL;
    $msg .= $req->getUrlEncodedBody()->getBody() . PHP_EOL;
    $msg .= PHP_EOL;

    return $msg;
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

      $msg .= $this->_getOAuth2ClientDebug($fb->getOAuth2Client());
      $msg .= PHP_EOL;

      throw new Zf1auth_Adapter_Facebook_Exception($msg, 0, $e);
    }

    if (!$accessToken instanceof Facebook\Authentication\AccessToken)
    {
      $errmsg = "";
      $errmsg .= "Error #" . $helper->getErrorCode() . ": " . $helper->getError() . PHP_EOL;
      $errmsg .= $helper->getErrorReason() . PHP_EOL;
      $errmsg .= $helper->getErrorDescription() . PHP_EOL;
      
      return new Zf1auth_Auth_Result(\Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null, array($errmsg));
    }

    $userNode = null;
    
    try
    {
      $fb->setDefaultAccessToken($accessToken);
      $response = $fb->get('/me?fields=id,email,birthday,name,first_name,last_name,gender');
      $userNode = $response->getGraphUser();
    } catch (Facebook\Exceptions\FacebookResponseException $e)
    {
      throw new Zf1auth_Adapter_Facebook_Exception('Graph returned an error: ' . $e->getMessage(), 0, $e);
    } catch (Facebook\Exceptions\FacebookSDKException $e)
    {
      throw new Zf1auth_Adapter_Facebook_Exception('Graph returned an error: ' . $e->getMessage(), 0, $e);
    }
    
    if(!$userNode instanceof \Facebook\GraphNodes\GraphUser)
    {
      return new Zf1auth_Auth_Result(\Zend_Auth_Result::FAILURE, null, array());
    }
    
    $u = new \stdClass();
    $u->fbid = $userNode->getId();
    $u->email = strtolower($userNode->getEmail());
    $u->firstname = $userNode->getFirstName();
    $u->lastname = $userNode->getLastName();
    $u->birthday = $userNode->getBirthday();
    $u->gender = $userNode->getGender();
    
    foreach ((array) $u as $key => $val)
    {
      if(empty($val))
      {
        return new Zf1auth_Auth_Result(\Zend_Auth_Result::FAILURE, null, array('Missing values'));
      }
    }

    return new Zf1auth_Auth_Result(\Zend_Auth_Result::SUCCESS, $u, array());
  }

}
