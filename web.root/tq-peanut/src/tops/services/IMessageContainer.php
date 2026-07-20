<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 7/7/2015
 * Time: 6:37 AM
 */

namespace Tops\services;


interface IMessageContainer {
    const translated = true;
    public function AddMessage($messageType,$text,$arg1=null, $arg2=null);
    public function AddInfoMessage($text,$arg1=null, $arg2=null);
    public function AddWarningMessage($text,$arg1=null, $arg2=null);
    public function AddErrorMessage($text,$arg1=null, $arg2=null);
    public function GetResult();
}