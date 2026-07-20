<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/21/2017
 * Time: 6:01 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\model\entity\EmailList;
use Peanut\PeanutMailings\db\model\repository\EmailListsRepository;
use Tops\services\TServiceCommand;
use Tops\sys\TLanguage;
use Tops\sys\TPermissionsManager;

/**
 * Class UpdateMailingListCommand
 * @package Peanut\Mailings\services
 *
 * Service contract:
 *    Request:
 *     interface IEmailListItem extends ILookupItem {
 *          active?: number;
 *          id : any;
 *          code: string;
 *          name: string;
 *          description : string;
 *          mailBox: string;
 *      }
 *
 *      Response:
 *         Peanut.ILookupItem[];
}

 */
class UpdateMailingListCommand extends TServiceCommand
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
        if (!isset($request->id)) {
            $this->addErrorMessage('error-no-id');
            return;
        }

        if (isset($request->name) && empty($request->name)) {
            $this->addErrorMessage('form-error-name-blank');
            return;
        }

        if (empty($request->code)) {
            $this->addErrorMessage('form-error-code-blank');
            return;
        }

        $mailingListEntity = TLanguage::text('mailing-list-entity');
        $repository = new EmailListsRepository();
        $isNew = ($request->id === 0);
        $list = $repository->getEntityByCode($request->code,true);
        if ($isNew) {
            if (!empty($list)) {
                $this->addErrorMessage('error-entity-code-not-unique',[$request->code,$mailingListEntity]);
                return;
            }
            $list = new EmailList();
        }
        else {
            if (empty($list)) {
                $this->addErrorMessage('error-entity-code-not-found',[$mailingListEntity,$request->code]);
                return;
            }
        }
        $list->assignFromObject($request);
        // $list->cansubscribe = 1;
        if ($isNew) {
            $repository->insert($list,$this->getUser()->getUserName());
        }
        else {
            $repository->update($list,$this->getUser()->getUserName());
        }

        $lists = $repository->getSubscriptionListLookup($this->getUser()->isAdmin());
        $this->addInfoMessage(
            $isNew ? 'service-added-entity' : 'service-updated-entity',
            [$mailingListEntity,$request->name]);
        $this->setReturnValue($lists);
    }
}