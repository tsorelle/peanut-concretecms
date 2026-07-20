<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/22/2017
 * Time: 6:11 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\EMailQueue;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

/**
 * Class PauseMessageProcessCommand
 * @package Peanut\Mailings\services
 *
 *  Service Contract
 *      Request: none
 *      Response: IGetMessageHistoryResponse  (see GetEmailListHistoryCommand)
 *
 */
class ControlMessageProcessCommand extends TServiceCommand
{

    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::mailAdminPermissionName);
    }


    protected function run()
    {
        $response = new \stdClass();
        $action = $this->getRequest();
        if (empty($action) || $action == 'pause') {
            $paused = EMailQueue::Pause('User request');
            if ($paused === false) {
                $this->addErrorMessage('process-status-change-failed');
                return;
            }
            $response->status = 'paused';
            $response->pausedUntil = $paused;
        }
        else {
            EMailQueue::Restart();
            $response->status = EMailQueue::GetActiveStatus();
            $response->pausedUntil  = '';
        }
        $response->items = EMailQueue::GetMessageHistory();
        $this->setReturnValue($response);
    }
}