<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/22/2017
 * Time: 8:53 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\EMailQueue;
use Peanut\PeanutMailings\db\model\entity\EmailMessage;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

/**
 * Class UpdateQueuedMessageCommand
 * @package Peanut\Mailings\services
 *
 * Service contract:
 *      Request:
 *        interface IEmailListMessgeUpdate {
 *            messageId: any;
 *            subject: string;
 *            template: string;
 *            messageText: string;
 *        }
 *
 */
class UpdateQueuedMessageCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::mailAdminPermissionName);
    }


    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        if (!isset($request->messageId)) {
            $this->addErrorMessage('error-no-id');
            return;
        }
        $repository = EMailQueue::getMessagesRepository();
        /**
         * @var $message EmailMessage
         */
        $message = $repository->get($request->messageId);
        if (empty($message)) {
            $this->addErrorMessage('message-not-queued');
        }
        else {
            if (!empty($request->messageText)) {
                $message->messageText = $request->messageText;
            }
            if (!empty($request->subject)) {
                $message->subject = $request->subject;
            }
        }

        $repository->update($message,$this->getUser()->getUserName());

        $response = EMailQueue::GetStatus();
        $response->items = EMailQueue::GetMessageHistory();
        $this->setReturnValue($response);
    }
}