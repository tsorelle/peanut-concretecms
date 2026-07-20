<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/27/2017
 * Time: 8:33 AM
 */

namespace Peanut\PeanutMailings\sys;


class TNameParser
{
    private $fullName;
    public function __construct($fullName)
    {
        if (empty($fullName) || is_numeric($fullName)) {
            $this->fullName = '';
            $this->fileAsName = '';
        }
        else {
            $this->fullName = $fullName;
        }
    }

    public static function GetFileAsName($name) {
        return (new TNameParser($name))->fileAsName();
    }

    private function explodeName($name) {
        $nameParts =
            explode(' ',
                // str_replace('.',' ',
                    str_replace(',',' ',
                        strtolower(trim($name)))
            // )
            );

        $parts = array();
        $count = sizeof($nameParts);
        for ($i=0; $i<$count;$i++) {
            $part = $nameParts[$i];
            if (!empty($part)) {
                $parts[] = $part;
            }
        }
        return $parts;
    }

    private $fileAsName;

    private function parseFileAsName() {
        $parts = $this->explodeName($this->fullName);
        $defaultName = join(' ',$parts);
        $last = '';
        while (sizeof($parts) > 0) {
            $part = array_pop($parts);
            if (!$this->isEndTitle($part)) {
                $last = $part;
                break;
            }
        }

        if ($last) {
            while (sizeof($parts) > 0) {
                $part =  array_shift($parts);
                if ($this->isTitle($part)) {
                    if (empty($parts)) {
                        return $last;
                    }
                }
                else {
                    return trim($last . ',' . $part . ' ' .  join(' ',$parts));
                }
            }
        }

        return $defaultName;
    }

    public function fileAsName() {
        if (!isset($this->fileAsName)) {
            $this->fileAsName = $this->parseFileAsName();
        }
        return $this->fileAsName;
    }


    private function isTitle($word)
    {
        if (substr($word,-1) == '.') {

            $word = trim(substr($word,0, strlen($word) - 1));
        }
        switch ($word) {
            case 'mr' :
                return true;
            case 'mrs' :
                return true;
            case 'ms' :
                return true;
            case 'dr' :
                return true;
            case 'fr' :
                return true;
            case 'sr' :
                return true;
        }

        return false;

    }
    private function isEndTitle($word) {
        if (substr($word,-1) == '.') {
            $word = trim(substr($word,0, sizeof($word) - 1));
        }
        switch ($word) {
            case 'jr' :
                return true;
            case 'sr' :
                return true;
            case 'ii' :
                return true;
            case 'iii' :
                return true;
            case 'md' :
                return true;
            case 'm.d' :
                return true;
            case 'phd' :
                return true;
            case 'ph.d' :
                return true;
            case 'd.d' :
                return true;
            case 'dd' :
                return true;
            case 'dds' :
                return true;
            case 'dd.s' :
                return true;
            case 'atty' :
                return true;
        }

        return false;
    }
}