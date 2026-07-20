<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/2/2017
 * Time: 6:37 AM
 */

namespace Tops\services;


class NullMessageContainer implements IMessageContainer
{

    public function AddMessage($messageType, $text, $arg1 = null, $arg2 = null)
    {
        // ignored
    }

    public function AddInfoMessage($text, $arg1 = null, $arg2 = null)
    {
        // ignored
    }

    public function AddWarningMessage($text, $arg1 = null, $arg2 = null)
    {
        // ignored
    }

    public function AddErrorMessage($text, $arg1 = null, $arg2 = null)
    {
        // ignored
    }

    public function GetResult()
    {
        return null;
    }
}