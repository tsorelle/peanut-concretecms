<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/22/2015
 * Time: 2:27 PM
 */

namespace Tops\mail;


interface IMailer {
    /**
     * @param TEMailMessage $message
     * @return bool | string
     *
     * Return true if successfull or error message e.g.
     * $result = $mailer->send($message);
     * if ($result !== true) {
     *      logError($result);
     * }
     */
    public function send(TEMailMessage $message);
    public function setSendEnabled($value);
}