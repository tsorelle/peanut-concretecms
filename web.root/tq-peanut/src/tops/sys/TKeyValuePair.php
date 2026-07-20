<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/6/2017
 * Time: 9:31 AM
 */

namespace Tops\sys;

/**
 * Class TKeyValuePair
 *
 * Create DTO objects to match Typscript definition: IKeyValuePair
 *
 *    export interface IKeyValuePair {
 *        Key: any;
 *        Value: any;
 *    }
 *
 * @package Tops\sys
 */
class TKeyValuePair
{
    public $Key = '';
    public $Value = null;
    public static function Create($key,$value)
    {
        $result = new TKeyValuePair();
        $result->Key = $key;
        $result->Value = $value;
        return $result;
    }

    public static function ExpandArray(array $a) {
        $result = array();
        foreach ($a as $key => $value) {
            $result[] = self::Create($key,$value);
        }
        return $result;
    }

    public static function FlattenArray(array $kvArray) {
        $result = array();
        foreach ($kvArray as $kv) {
            $result[$kv->Key] = $kv->Value;
        }
        return $result;
    }

    public static function CreateArray(array $a, $encodeJson=false, $replacements = null) {
        $result = array();
        foreach ($a as $key => $value) {
            foreach ($replacements as $search => $replace) {
                $value = str_replace($search,$replace,$value);
            }
            $kv = self::Create($key,$value);
            if ($encodeJson) {
                $kv = json_encode($kv);
            }
            $result[] = $kv;
        }
        return $result;
    }

    public static function ToArray(array $objects ) {
        $result = array();
        foreach ($objects as $kv) {
            $result[$kv->Key] = $kv->Value;
        }
        return $result;
    }

    public static function GetValues(array $objects) {
        $result = array();
        if (!empty($objects)) {
            foreach ($objects as $kv) {
                $result[] = $kv->Value;
            }
        }
        return $result;
    }


    public static function CreateCookie(array $a,$cookieName) {
        if (!headers_sent()) {
        $encoded = self::CreateArray($a,true,array('+' => '[plus]'));
        $cookie =  '['. join(',',$encoded).']';
        setcookie($cookieName,$cookie);
        }
    }

    public function getKey()
    {
        return $this->Key;
    }

    public function getValue()
    {
        return $this->Value;
    }

    public function setKey($key)
    {
        $this->Key = $key;
    }

    public function setValue($value)
    {
        $this->Value = $value;
    }

}