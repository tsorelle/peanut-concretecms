<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 5/16/2017
 * Time: 5:52 AM
 */

namespace Tops\services;


use Tops\sys\TStrings;

class DefaultInputHandler extends ServiceRequestInputHandler
{

    /**
     * @return 'POST' | 'GET'
     */
    protected function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return mixed
     */
    public function get($key)
    {
        if ($this->getMethod() == 'POST') {
            return (is_array($_POST) && isset($_POST[$key])) ? $_POST[$key] : null;
        }
        return (is_array($_GET) && isset($_GET[$key])) ? $_GET[$key] : null;
    }

    public function getSecurityToken()
    {
        $result = empty($_GET[ServiceRequestInputHandler::securityTokenKey]) ? null
            : $_GET[ServiceRequestInputHandler::securityTokenKey];
        if (empty($result)) {
            $result = empty($_POST[ServiceRequestInputHandler::securityTokenKey]) ? null
                : $_POST[ServiceRequestInputHandler::securityTokenKey];

        }
        return $result;
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