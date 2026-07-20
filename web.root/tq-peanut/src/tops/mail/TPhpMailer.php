<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/12/2019
 * Time: 3:53 AM
 */

namespace Tops\mail;


use mysql_xdevapi\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Tops\sys\TPath;

class TPhpMailer implements IMailer
{
    public static $sendEnabled = true;

    /**
     * @var PHPMailer
     */
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer();
    }

    /**
     * @param TEMailMessage $message
     * Return true if successfull for error message e.g.
     * $result = $mailer->send($message);
     * if ($result !== true) {
     *      logError($result);
     * }
     */
    public function send(TEMailMessage $message)
    {
        try {
            return $this->sendPhpMail($message);
        }
        catch(\Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * @param TEMailMessage $message
     * @return bool|string
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendPhpMail(TEMailMessage $message)
    {
        $properties = $message->getSendProperties();
        $address = $message->getFromAddress();
        $this->mailer->setFrom($address->getAddress(), $address->getName());
        $address = $message->getReplyTo();
        $this->mailer->addReplyTo($address->getAddress(), $address->getName());
        $this->mailer->Subject = $message->getSubject();

        foreach ($message->getRecipients() as $recipient) {
            $this->mailer->addAddress($recipient->getAddress(), $recipient->getName());
        }

        foreach ($message->getCCs() as $recipient) {
            $this->mailer->addCC($recipient->getAddress(), $recipient->getName());
        }

        foreach ($message->getBCCs() as $recipient) {
            $this->mailer->addBCC($recipient->getAddress(), $recipient->getName());
        }

        // $contentType = $message->getContentType();
        $isHtml = $properties->contentType != TContentType::Text;


        $this->mailer->isHTML($isHtml);
        if ($isHtml) {
            $this->mailer->msgHTML($properties->html);
                // $message->getMessageBody(), $this->basedir);
            if ($properties->contentType == TContentType::MultiPart) {
                $this->mailer->AltBody = $properties->text;
            }
        } else {
            $this->mailer->Body = $properties->text;
        }

        $returnAddress = $message->getReturnAddress();
        if (!empty($returnAddress)) {
            $this->mailer->addCustomHeader('Return-Path',$returnAddress);
        }

        foreach ($message->getHeaders() as $key => $value) {
            $this->mailer->addCustomHeader($key,$value);
        }

        // $attachments = $message->getAttachments();

        foreach ($properties->attachments as $attachment) {
            $path = TPath::fromFileRoot($attachment);
            if ($path===false) {
                return "Attachment path not found: $attachment";
            }
            $this->mailer->addAttachment($path);
        }

        if (!$this->mailer->send()) {
            return $this->mailer->ErrorInfo;
        } else {
            return true;
        }
    }

    public function setSendEnabled($value)
    {
        self::$sendEnabled = $value;
    }
}