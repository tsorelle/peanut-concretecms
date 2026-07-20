<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/6/2017
 * Time: 9:42 AM
 */

namespace Tops\sys;

/**
 * Class TNameValuePair
 *
 * Create DTO objects to match Typscript definition: IKameValuePair
 *
 *    export interface INameValuePair {
 *        Name: string;
 *        Value: any;
 *    }
 *
 * @package Tops\sys
 */
class TNameValuePair
{
    public $Name = '';
    public $Value = null;
    public static function Create($name,$value)
    {
        $result = new TNameValuePair();
        $result->Name = $name;
        $result->Value = $value;
        return $result;
    }

    public static function CreateArray(array $a, $replacements = null) {
        $result = array();
        foreach ($a as $name => $value) {
            foreach ($replacements as $search => $replace) {
                $value = str_replace($search,$replace,$value);
            }
            $result[] = self::Create($name,$value);
        }
        return $result;
    }

    public static function FromArray(array $a,$startIndex=false) {
        $result = array();
        foreach ($a as $key => $name) {
            $value = $startIndex === false ? $key : $key+$startIndex;
            $result[$key] = self::Create($name,$value);
        }
        return $result;
    }


    public static function ToArray(array $objects ) {
        $result = array();
        foreach ($objects as $kv) {
            $result[$kv->Name] = $kv->Value;
        }
        return $result;
    }

    public static function Find(array $objects,$value) {
        foreach ($objects as $item) {
            if ($item->Value == $value) {
                return $item;
            }
        }
        return false;
    }

    public static function FindDuplicates(array $objects) {
        $names = [];
        $duplicates = [];
        foreach ($objects as $item) {
            if (in_array($item->Name,$names)) {
                $duplicates[] = $item;
            }
            else {
                $names[] = $item->Name;
            }
        }
        return $duplicates;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function getValue()
    {
        return $this->Value;
    }

    public function setName($name)
    {
        $this->Name = $name;
    }

    public function setValue($value)
    {
        $this->Value = $value;
    }
}