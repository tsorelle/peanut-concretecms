<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/7/2017
 * Time: 9:34 AM
 */

namespace Tops\concrete5;

use Tops\mail\IMailer;
use Tops\mail\TContentType;
use Tops\mail\TEMailMessage;
use Tops\mail\TMailConfiguration;
use Concrete\Core\Mail\Service as MailService;

use Core;
use Tops\sys\TConfiguration;

class Concrete5Mailer implements IMailer
{
    private $enabled = true;
    private $throwExceptions = false;

    public function __construct()
    {
        $settings = TMailConfiguration::GetSettings();
        if (empty($settings->sendmail)) {
            $this->enabled = false;
        }
        $this->throwExceptions = $settings->debug == 1;
        // Note: for Concrete5 other configuration settings such as SMTP are handled in the C5 admin pages.
    }

    /**
     * @param TEMailMessage $message
     * @return bool | string
     *
     * Return true if successfull or error message
     *
     * @throws \Exception
     */
    public function send(TEMailMessage $message)
    {
        if (TConfiguration::getBoolean('topsmailservice','mail',true)) {
           throw new \Exception('Cannot use C5 mailer if topsmailservice is enabled in application/config/settings.ini');
        }

        if (!$this->enabled) {
            return true;
        }

//        $sendProperties = $message->getSendProperties();
//        if ($sendProperties )

        /**
         * @var $mailService MailService
         */
        $mailService = Core::make('mail');

        $mailService->setTesting($this->throwExceptions);
        $mailService->setSubject($message->getSubject());
        $address = $message->getFromAddress();
        $mailService->from($address->getAddress(),$address->getName());

        $address = $message->getReplyTo();
        $mailService->replyto($address->getAddress(),$address->getName());

        foreach ($message->getRecipients() as $recipient) {
            $mailService->to($recipient->getAddress(), $recipient->getName());
        }

        foreach ($message->getCCs() as $recipient) {
            $mailService->cc($recipient->getAddress(), $recipient->getName());
        }

        foreach ($message->getBCCs() as $recipient) {
            $mailService->bcc($recipient->getAddress(), $recipient->getName());
        }

        $contentType = $message->getContentType();
        $isHtml = $contentType != TContentType::Text;


        if ($isHtml) {
            if ($contentType == TContentType::MultiPart) {
                $mailService->setBody($message->getTextPart());
            }
            $mailService->setBodyHTML($message->getMessageBody());
        } else {
            $mailService->setBody($message->getMessageBody());
        }

        $returnAddress = $message->getReturnAddress();
        if (!empty($returnAddress)) {
            $mailService->setAdditionalHeaders(array('Return-Path' => $returnAddress));
        }

        $attachments = $message->getAttachments();

        foreach ($attachments as $attachment) {
            if (is_numeric($attachment)) {
                $attachment = \Concrete\Core\File\File::getByID($attachment);
                $mailService->addAttachment($attachment);
            }
            else {
                $list = new \Concrete\Core\File\FileList();
                $list->sortByFilenameAscending();
            }
            // todo: retrieve file by name
        }

        return $mailService->sendMail();

    }

    public function setSendEnabled($value)
    {
        $this->enabled = $value;
    }

}