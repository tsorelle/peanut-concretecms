<?php

namespace Tops\mail;

use Laminas\Mail\Header\ContentType;
use Tops\mail\TNullMailer;
use Tops\sys\TConfiguration;
use Tops\sys\TPath;

/**
 * A mailer class for use in the development environment
 *
 * Setting in classes.ini
 *       [tops.mailer]
 *       type='Tops\mail\TDevMailer'
 *       singleton=1
 *
 * Writes email file in html format to a "maildrop" directory
 * Default location is "maildrop" just above the site document root
 *
 * Or override in settings.ini:
 *  [mail]
 *  maildrop='/dev/tmp/testmail'
 *
 */
class TDevMailer implements IMailer
{
    static $maildropPath;
    public function send(TEMailMessage $message)
    {
        $msg = $message->getSendProperties();
        $toList = str_replace(['<','>'],['(',')'],$msg->to );

        $body = (empty($msg->html)) ? '<pre>'.$msg->text.'</pre>' : $msg->html;

        $content = "<p>To:".$toList."\n<br>Subject: ". $msg->subject."</p>\n";

        if ($msg->cc) {
            $ccList =  str_replace(['<','>'],['(',')'],$msg->cc );
            $content .= "<p>Cc:".$ccList."</p>\n";
        }
        if ($msg->bcc) {
            $bccList = str_replace(['<','>'],['(',')'],$msg->bcc );
            $content .= "<p>Bcc:".$bccList."</p>\n";
        }
        $content .=  "\n<div>".$body.'</div>';
        $mailDrop = $this->getMaildropPath();
        $fn = $mailDrop.'/'.uniqid().'.html';
        file_put_contents($fn,$content);
        return true;
    }

    public function getMaildropPath()
    {
        if (!isset(self::$maildropPath)) {
            $default=  TPath::getFileRoot().'../maildrop';
            $mailDrop = TConfiguration::getValue('maildrop','mail',$default);
            $path = realpath($mailDrop);
            if (empty($path)) {
                throw new \Exception(
                    "Maildrop path not found. Create directory '$mailDrop' or change value in settings.ini 'mail:maildrop' ");
            }
            self::$maildropPath = $path;
        }
        return self::$maildropPath;
    }

    public function setSendEnabled($value)
    {
        return true;
    }
}