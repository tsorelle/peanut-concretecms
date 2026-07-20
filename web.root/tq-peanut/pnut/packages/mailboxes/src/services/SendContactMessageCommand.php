<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/23/2017
 * Time: 7:22 AM
 */

namespace Peanut\Mailboxes\services;


use Tops\db\model\repository\MailboxesRepository;
use Tops\mail\TContentType;
use Tops\mail\TPostOffice;
use Tops\services\TFormSecurity;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;
use Tops\sys\TUser;
use Tops\sys\TWebSite;

/**
 * Class SendContactMessageCommand
 * @package Peanut\Mailboxes\services
 *
 * reguest:
 *     export interface IMailMessage {
 *         mailboxCode: string;
 *         fromName : string;
 *         fromAddress : string;
 *         subject : string;
 *         contentType: string;
 *         body : string;
 *      }
 */
class SendContactMessageCommand extends TServiceCommand
{

    /**
     * @throws \Exception
     */
    protected function run()
    {
        $message = $this->getRequest();
/*        if (!empty($message->token)) {
            $verification = TRecaptcha::Verify($message->token);
            if ($verification < 7) {
                $this->setError('Recaptcha verification failed');
                $this->setReturnValue('denied');
                return;
            }
        }*/

        if (empty($message)) {
            $this->addErrorMessage('Input request not received');
            return;
        }

        if (empty($message->body)) {
            $this->addErrorMessage('Message content not received');
            return;
        }

        $sender = TPostOffice::GetMailboxAddress('contact-form');
        if ($sender === false ) {
            $this->addErrorMessage('Contact form address not found.');
            return;
        }

        $authenticated = TUser::getCurrent()->isAuthenticated();

        if ($authenticated) {
            $message->footer = TFormSecurity::GetSecurityFooter($message);
        }
        else {
            $message = TFormSecurity::checkForProblems($message);
            if (!empty($message->error)) {
                $this->addErrorMessage($message->error);
                return;
            }
            $message->body = str_replace("\n", "<br>\n", $message->body);
        }
        $headerFormat = TLanguage::text(
            $authenticated ? 'mailbox-contact-header-format-member' : 'mailbox-contact-header-format'
        );
        $mailbox = TPostOffice::GetMailbox($message->mailboxCode);
        $header = sprintf($headerFormat,$mailbox->getName());
        $body = sprintf('<p>%s<br><a href="mailto:%s">%s (%s)</a><hr><p>%s<p><hr><div>%s</div>',
            $header,$message->fromAddress,$message->fromName,$message->fromAddress,$message->body,$message->footer);

        $contentType =  TContentType::Html;

        /*        $contentType = TConfiguration::getBoolean('contact-message-html','mail',true) ?
                    TContentType::Html : TContentType::Text;
                if ($contentType   == TContentType::Html) {
                    $message->body = str_replace("\n",'<br>',$message->body);
                    $body = sprintf('<p>%s<br><a href="mailto:%s">%s (%s)</a><hr><p>%s<p>',
                        $header,$message->fromAddress,$message->fromName,$message->fromAddress,$message->body);
                }
                else {
                    $body =
                    sprintf("%s\n%s (%s)\n\n---------------\n%s",
                        $header,$message->fromName,$message->fromAddress,$message->body);
                }*/

        // todo: configure from address
        // $fromAddress = "$message->fromName <$message->fromAddress>";
        $fromAddress = "FMA Contact <fma@austinquakers.org>";
        TPostOffice::SendMessageToUs(
            $fromAddress,
            $message->subject,$body,$contentType,
            $message->mailboxCode, // recipient
            TPostOffice::ContactMailbox // sender
        );
    }
}