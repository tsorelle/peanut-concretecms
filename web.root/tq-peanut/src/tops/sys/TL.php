<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/2/2017
 * Time: 6:06 AM
 */

namespace Tops\sys;

// shortcut for TLanguage::text()
class TL
{
    public static function text($resourceCode,$defaultText=null) {
        return TLanguage::text($resourceCode,$defaultText);
    }
}