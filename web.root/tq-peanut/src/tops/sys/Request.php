<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tops\sys;
/**
 * Class Request
 * A lightweight alternative to the Symfony Request object.
 *
 * @package Tops\sys
 */
class Request
{
    private $vars = array();
    private $requestMethod;
    private $pathInfo;
    private $scriptName;
    private $requestScheme;
    private $requestFormat;
    private $requestUri;

    public static function createFromGlobals()
    {
        $result = new Request();
        $result->requestUri = $_SERVER['REQUEST_URI'];
        $parts = explode('?',$result->requestUri);
        $result->pathInfo = $parts[0];
        $result->requestMethod =  $_SERVER['REQUEST_METHOD'];
        $result->scriptName = $_SERVER['SCRIPT_NAME'];
        $result->requestScheme = $_SERVER['REQUEST_SCHEME'];
        $array = explode(',', $_SERVER['HTTP_ACCEPT']);
        $format = array_shift($array);
        $result->requestFormat = array_pop(explode('/',$format));

        foreach ($_POST as $key => $value) {
            $result->vars[$key] = $value;
        }
        foreach ($_GET as $key => $value) {
            $result->vars[$key] = $value;
        }
        return $result;
    }

    public function getPathInfo() {
        return $this->pathInfo;
    }
    public function getScriptName() {
        return $this->scriptName;
    }
    public function get($key) {
        if (array_key_exists($key,$this->vars)) {
            return $this->vars[$key];
        }
        return false;
    }
    public function getMethod() {
        return $this->requestMethod;
    }
    public function getRequestFormat() {
        return $this->requestFormat;
    }
}
