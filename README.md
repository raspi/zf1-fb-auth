# Zend Framework 1 Facebook SDK authentication adapter
Uses official Facebook SDK API v4

https://github.com/facebook/facebook-php-sdk-v4
https://developers.facebook.com/docs/reference/php/5.0.0


# Usage example

application.ini:

    [production]
    ; ...
    facebook.id = "1234567890"
    facebook.secret = "1234567890"
    facebook.scopes[] = "public_profile"
    facebook.scopes[] = "email"
    facebook.scopes[] = "user_birthday"
    facebook.uri = "http://example.com/facebook"
    ; ...
    
FooController.php:

    <?php
    class FooController extends Zend_Controller_Action
    {
      
      // Redirect user to facebook login page
      public function loginAction()
      {
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $fbconfig = $config->getOption('facebook');

        $adapter = new Zf1auth\Zf1auth_Adapter_Facebook();
        $adapter->setFacebookId($fbconfig['id']);
        $adapter->setFacebookSecret($fbconfig['secret']);
        $adapter->setApplicationEntryUri($fbconfig['uri']);
        $adapter->setFacebookScopes($fbconfig['scopes']);

        $loginurl = $adapter->getFacebookLoginUrl();

        $this->_redirect($loginurl);
      }
      
      // Facebook redirects to this page
      public function facebookAction()
      {
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $fbconfig = $config->getOption('facebook');
        
        $adapter = new Zf1auth\Zf1auth_Adapter_Facebook();
        $adapter->setFacebookId($fbconfig['id']);
        $adapter->setFacebookSecret($fbconfig['secret']);
        $adapter->setApplicationEntryUri($fbconfig['uri']);
        $adapter->setFacebookScopes($fbconfig['scopes']);

        $auth = Zend_Auth::getInstance();
        $fb_identity = $auth->authenticate($adapter);
        
        if (!$fb_identity instanceof \Zf1auth\Zf1auth_Auth_Result)
        {
          $auth->clearIdentity();
          throw new Exception("FB login failed: invalid instance returned")
        }

        if (!$fb_identity->isValid())
        {
          $auth->clearIdentity();
          throw new Exception("FB login failed")
        }
        
        // Add user to database or start your app's registering process
        // ...
      }
    }

It's recommend that adapter throws exceptions so that you can easily catch different login error situations. This is also recommended because then you can translate possible error pages much better way.
