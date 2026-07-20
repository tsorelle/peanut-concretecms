<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 6/26/2017
 * Time: 7:03 AM
 */

namespace Tops\sys;

use Tops\sys\Request;

class TRequestBuilder
{
    /**
     * @var Request
     */
    private static $request;

    public static function GetRequest() {
        if (!isset(self::$request)) {
            self::$request = Request::createFromGlobals();
        }
        return self::$request;
    }

    public static function SetRequest(Request $request) {
        self::$request = $request;
    }

}