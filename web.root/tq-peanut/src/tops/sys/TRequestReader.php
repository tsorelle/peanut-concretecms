<?php

namespace Peanut\src\tops\sys;

class TRequestReader
{

    public static function filterValue($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && function_exists('wp_unslash')) {
            // fix for wordpress
            return wp_unslash($value);
        }
        return $value;
    }

    public static function GetRequest($key = null) {
        $result = $_REQUEST[$key]  ?? null;
        return self::filterValue($result) ;
    }

    public static function GetPost($key = null) {
        $result = $_POST[$key]  ?? null;
        return self::filterValue($result) ;
    }

    public static function Get($key = null) {
        $result = $_GET[$key]  ?? null;
        return self::filterValue($result) ;
    }

    public static function GetCookieValue($key = null) {
        $result = $_COOKIE[$key]  ?? null;
        return self::filterValue($result) ;
    }
}