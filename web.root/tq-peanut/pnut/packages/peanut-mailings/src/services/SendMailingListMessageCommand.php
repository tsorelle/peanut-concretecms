<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/11/2017
 * Time: 4:59 AM
 */

namespace Peanut\PeanutMailings\services;


// use Peanut\PeanutMailings\db\DirectoryManager;
use Peanut\PeanutMailings\db\EMailQueue;
use Peanut\PeanutMailings\sys\MailTemplateManager;
use Peanut\PeanutMailings\sys\SubscriptionManager;
use Tops\mail\TContentType;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TPermissionsManager;

/**
 * Class SendMailingListMessage
 * @package Peanut\Mailings\services
 *
 * Service contract
 *    Request
 *      interface IEMailListSendRequest {
 *        listId: any;
 *        subject: string;
 *        messageText: string;
 *        contentType: string,
 *        testAddress: string;
 *      }
 */
class SendMailingListMessageCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::sendMailingsPermissionName);
    }

    private function sendTestMessage($request) {
        $user = $this->getUser();
        $recipient = EMailQueue::UserToEmailRecipient($user);
        if (empty($request->testAddress) || $request->testAddress == $user->getEmail()) {
            // get UID for current user
            $submanager = SubscriptionManager::getInstance();
            $recipient->personId = $submanager->getUid($user->getId());
/*          Example implementation
            $person = $dirManager->getPersonByAccountId($user->getId());
            if ($person) {
                return $person->uid;
            }
*/
        }
        else {
            // set test values
            $recipient->toAddress = $request->testAddress;
            $recipient->personId = 'testusertoken';
        }

        if (empty($recipient->toAddress)) {
            $this->addErrorMessage("Cannot send test message. No email found");
            return;
        }
        $count = EMailQueue::SendTestMessage($request,$recipient,$request->listId);
        if ($count == 0) {
            $this->addErrorMessage("Failed to send test message to $recipient->toAddress");
        }
        else {
            $this->addInfoMessage('Test message send to '.$recipient->toAddress);
        }
    }

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        if (empty($request->subject)) {
            $this->addErrorMessage('Error, no subject');
            return;
        }
        if (empty($request->messageText)) {
            $this->addErrorMessage('Error no body');
            return;
        }

        if (empty($request->contentType)) {
            $request->contentType = TContentType::Html;
        }

        $request->template = $request->contentType == 'text' ?
            'Unsubscribe.txt' : 'Unsubscribe.html';

        $request->messageText = MailTemplateManager::ExpandLocalHrefs($request->messageText);

        if ($request->sendTest) {
            $this->sendTestMessage($request);
        }
        else {
            $queue = new EMailQueue();
            $queueResult = EMailQueue::QueueMessageList($request, $this->getUser()->getUserName());
            $queueMailings = TConfiguration::getBoolean('queuemailings', 'mail', true);
            if ($queueMailings) {
                $count = $queueResult->count;
                $action = 'submitted to message queue';
            } else {
                $count = EMailQueue::Send($queueResult->messageId,$queue);
                $action = 'sent';
            }
            $plural = $count > 1 ? 's were' : ' was';
            $this->addInfoMessage("$count message$plural $action");
        }
    }
}