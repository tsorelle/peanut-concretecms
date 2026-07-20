<?php

namespace Peanut\services;

use Tops\db\model\repository\LookupTableRepository;
use Tops\sys\TPermissionsManager;
use Tops\sys\TUser;

class InitializeLookupCommand extends \Tops\services\TServiceCommand
{

    /**********
     * Service contract
     * Request:
     *     export interface ILookupItemInitRequest  {
     *        table: string,
     *       activeOnly?: any;
     *    }
     *
     * Response:
     *    export interface ILookupItemInitResponse {
     *          items: ILookupItem[];
     *         canEdit: any;
     *    }
     */

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request->table)) {
            $this->addErrorMessage('Table name is required');
        }
        $activeOnly = !empty($request->activeOnly);
        $response = new \stdClass();
        $response->canEdit = $this->getUser()->isAuthorized(TPermissionsManager::editContentPermissionsName);
        $repository = new LookupTableRepository($request->table);
        $repository->setLookupInfoColumns(['active']);
        $response->items = $repository->getLookupList(false,'',LookupTableRepository::sortByName,!$activeOnly);
        $this->setReturnValue($response);
    }
}