<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/22/2017
 * Time: 9:19 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\EMailQueue;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

class RemoveQueuedMessageCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::mailAdminPermissionName);
    }

    protected function run()
    {
        $messageId = $this->getRequest();
        if (empty($messageId)) {
            $this->addErrorMessage('error-no-id');
            return;
        }
        EMailQueue::RemoveMessage($messageId);
        $response = EMailQueue::GetStatus();
        $response->items = EMailQueue::GetMessageHistory();
        $this->setReturnValue($response);
    }
}