<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/1/2019
 * Time: 5:49 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDirectory\db\DirectoryManager;
use Peanut\PeanutMailings\db\EMailQueue;
use Peanut\PeanutMailings\sys\MailTemplateManager;
use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TPermissionsManager;
use Tops\sys\TTemplateManager;
use Tops\sys\TWebSite;
use Zend\Uri\Mailto;

/**
 * Class SendNewsletterCommand
 * @package Peanut\QnutDocuments\services
 *
 * Service contract:
 *   Request:
 *      {messageText: string; issueDate: string; documentId: any, sendTest: boolean}
 *   Response:
 *     interface ISendNewsletterResponse {
 *        sentCount: any;
 *          queueMailings : boolean;
 *     }
 */
class SendNewsletterCommand extends TServiceCommand
{

    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::sendMailingsPermissionName);
    }

    private function fixMessageLinks($message) {
        $siteUrl = TWebSite::GetSiteUrl();
        $message = TTemplateManager::ReplaceContentTokens($message,[
            'site-url' => $siteUrl
        ]);

        $message = MailTemplateManager::ExpandLocalHrefs($message,$siteUrl);
        // $message = str_replace('"/','"'.$siteUrl.'/',$message);

        return $message;

    }

    protected function run()
    {
        $request = $this->getRequest();
        if (!$request) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        if (empty($request->messageText)) {
            $this->addErrorMessage('newsletter-error-no-text');
            return;
        }
        if (empty($request->documentId)) {
            $this->addErrorMessage('no document id');
            return;
        }

        $manager = new DirectoryManager();
        $newletterList = $manager->getEmailListByCode('newsletter');
        if (empty($newletterList)) {
            $this->addErrorMessage('Counld not retrieve email list information');
            return;
        }

        (new DocumentManager())->publishNewsletter($request->documentId);

        $issueDate = empty($request->issueDate) ? '' : $request->issueDate;
        $messageText = $this->fixMessageLinks($request->messageText);

        $sendRequest = new \stdClass();
        $sendRequest->listId = $newletterList->id;
        $sendRequest->subject = $newletterList->name.' '.$issueDate;
        $sendRequest->messageText = $messageText;
        $sendRequest->contentType = 'html'; // todo: maybe support txt later
        $sendRequest->template = 'Unsubscribe';
        $count = 0;
        $queueMailings = false;
        if (!empty($request->sendTest)) {
            $this->sendTestMessage($sendRequest);
            $count = 1;
        }
        else {
            $queue = new EMailQueue();
            $queueResult = EMailQueue::QueueMessageList($sendRequest, $this->getUser()->getUserName());
            if ($queueResult) {
                $queueMailings = TConfiguration::getBoolean('queuemailings', 'mail', true);
                if ($queueMailings) {
                    $count = $queueResult->count;
                    $action = 'submitted to message queue';
                } else {
                    $count = EMailQueue::Send($queueResult->messageId,$queue);
                    $action = 'sent';
                }
            }
            else {
                $this->addWarningMessage('No messages were queued');
            }
        }
        $response  = new \stdClass();
        $response->sentCount = $count;
        $response->queued = $queueMailings ? 1 : 0;

        $this->setReturnValue($response);
    }

    private function sendTestMessage($request) {
        $user = $this->getUser();
        $recipient = EMailQueue::UserToEmailRecipient($user);
        if (!$recipient->toAddress) {
            $this->addErrorMessage("Cannot send test message. No email found for current user");
            return;
        }
        $person = (new DirectoryManager())->getPersonByAccountId($user->getId());
        $recipient->personId = @$person->uid;
        $count = EMailQueue::SendTestMessage($request,$recipient,@$request->listId);
        if ($count == 0) {
            $this->addErrorMessage("Failed to send test message to $recipient->toAddress");
        }
        else {
            $this->addInfoMessage('Test message send to '.$recipient->toAddress);
        }
    }

}