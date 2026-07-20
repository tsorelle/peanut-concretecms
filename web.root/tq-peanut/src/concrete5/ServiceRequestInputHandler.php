<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 5/16/2017
 * Time: 5:58 AM
 */

namespace Tops\concrete5;

// Uncomment for Concrete5
use Concrete\Core\Http\Request;

// Uncomment for Symfony or Drupal 8
// use Symfony\Component\HttpFoundation\Request;

class ServiceRequestInputHandler extends \Tops\services\ServiceRequestInputHandler
{
    /**
     * @return 'POST' | 'GET'
     */
    public function getMethod()
    {
        return Request::getInstance()->getMethod();
    }

    /**
     * @return mixed
     */
    public function get($key)
    {
        return Request::getInstance()->get($key);
    }

    public function getSecurityToken()
    {
        return Request::getInstance()->get(\Tops\services\ServiceRequestInputHandler::securityTokenKey);
    }

    public function getValues($exclude = [])
    {
        $result = new \stdClass();
        foreach ($_POST as $key => $value) {
            if (!array_key_exists($key,$exclude)) {
                $result->$key = $value;
            }
        }
        foreach ($_GET as $key => $value) {
            if (!array_key_exists($key,$exclude)) {
                if (empty($result->$key)) {
                    $result->$key = $value;
                }
            }
        }
        return $result;
    }
}