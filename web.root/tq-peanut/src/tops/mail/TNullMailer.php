<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/20/2015
 * Time: 1:27 PM
 */

namespace Tops\mail;


/**
 * Class TNullMailer
 * @package Tops\sys
 * Placeolder object and sends no mail
 */
class TNullMailer implements IMailer {

    /**
     * @param TEMailMessage $message
     */
    public function send(TEMailMessage $message)
    {
        // ignore
        return true;
    }

    public function setSendEnabled($value)
    {
        // ignore
    }
}