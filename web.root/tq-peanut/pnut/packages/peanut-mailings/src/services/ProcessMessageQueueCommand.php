<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/26/2017
 * Time: 4:25 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\EMailQueue;
use Tops\services\TServiceCommand;

class ProcessMessageQueueCommand extends TServiceCommand
{

    protected function run()
    {
        if (!$this->getUser()->isAdmin()) {
            $this->addErrorMessage('Administrator permissions are required to run this service.');
            return;
        }
        $sendlimit = 0;
        $request = $this->getRequest();
        if (!empty($request)) {
            $sendlimit = $request->sendLimit ?? 0;
        }
        $count = EMailQueue::ProcessMessageQueue($sendlimit);
        $this->addInfoMessage("Sent $count messages.");
    }
}