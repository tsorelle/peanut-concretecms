<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/21/2017
 * Time: 3:01 PM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\EMailQueue;
use Peanut\PeanutMailings\db\model\repository\EmailMessagesRepository;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

/**
 * Class GetEmailListHistoryCommand
 * @package Peanut\Mailings\services
 *
 * Service Contract
 *      Request: none
 *      Response:
 *         interface IGetMessageHistoryResponse {
 *             status: string;
 *             pausedUntil: string;
 *             items: IMessageHistoryItem[];
 *         }
 *
 *         interface IMessageHistoryItem {
 *             messageId: any;
 *             timeSent: string;
 *             listName: string;
 *             recipientCount: number;
 *             sentCount: number;
 *             sender: string;
 *             subject: string;
 *             active: boolean;
 *         }
 */
class GetEmailListHistoryCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::mailAdminPermissionName);
    }

    protected function run()
    {
        $pageSize = 0;
        $pageNumber = 0;
        $includeInactive = false;
        $request = $this->getRequest();
        if (!empty($request)) {
            if (!empty($request->pageSize)) {
                $pageSize = $request->pageSize;
            }
            if (!empty($request->pageNumber)) {
                $pageNumber = $request->pageNumber;
            }
        }

        $response = new \stdClass();

        $repository = new EmailMessagesRepository();
        $count = $repository->getCount();
        $response->maxPages = $pageSize == 0 ? 0 : ceil($count / $pageSize);
        $response->items = $repository->getMessageHistory($pageNumber,$pageSize);
        $isPaused = EMailQueue::isPaused();
        if ($isPaused) {
            $response->status = 'paused';
            $response->pausedUntil = $isPaused;
        }
        else {
            $response->status = EMailQueue::GetActiveStatus();
            $response->pausedUntil  = '';
        }
        $this->setReturnValue($response);
    }
}