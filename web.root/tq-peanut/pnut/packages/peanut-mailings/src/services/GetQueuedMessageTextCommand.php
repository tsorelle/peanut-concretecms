<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/22/2017
 * Time: 10:26 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\EMailQueue;
use Peanut\PeanutMailings\db\model\entity\EmailMessage;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

class GetQueuedMessageTextCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::mailAdminPermissionName);
    }

    protected function run()
    {
        $messageId = $this->getRequest();
        if (empty($messageId)) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        $repository = EMailQueue::getMessagesRepository();
        /**
         * @var $message EmailMessage
         */
        $message = $repository->get($messageId);
        if (empty($message)) {
            $this->addErrorMessage('message-not-queued');
        }
        else {
            $this->setReturnValue($message->messageText);
        }

    }
}