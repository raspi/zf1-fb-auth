<?php
namespace Zf1auth;

class Zf1auth_Auth_Result extends \Zend_Auth_Result
{
    public function __construct($code, $identity, array $messages = array())
    {
        parent::__construct($code, $identity, $messages);
    }
}